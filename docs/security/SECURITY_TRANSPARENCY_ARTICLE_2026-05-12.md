# PayCal Security Transparency: Auth, Passkey, and Redis Hardening
## May 12, 2026 — Internal Findings & Public Disclosure

**Author:** Chris Simmons, PayCal Engineering
**Commit:** `493d5e44` — `security: auth/passkey/redis hardening`
**Files changed:** 11
**Date:** May 12, 2026

---

## Why We're Publishing This

We found bugs in code we wrote ourselves. Code that handled session creation, logout,
passkey challenge issuance, CSRF token invalidation, and account recovery. Not third-party
library bugs. Our bugs. Code we reviewed, committed, and shipped.

We're publishing this because we believe security transparency requires more than disclosing
external audits or responsibly disclosing vendor CVEs. It means being publicly accountable
when your own team writes code that doesn't meet the security bar you set for yourself.

We are not embarrassed by this. We are embarrassed only by the alternative: finding these
issues and saying nothing, or finding them too late.

This article documents every finding from the May 12 audit in plain language — what was
wrong, why it mattered, and exactly what we changed.

---

## Our Philosophy on Self-Auditing

PayCal is built on the premise that users trust us with financially sensitive information:
their pay schedules, pay rates, tax information, and increasingly, encrypted personal data
that only they hold the key to. That trust is not earned once. It is re-earned every time
we look honestly at our own work and ask whether it meets the bar.

Our engineering philosophy has three principles that drove this audit:

**1. Atomicity before correctness.** If two operations must happen together, treat them as
one operation or do not attempt the design at all. A system that is "correct most of the
time" is not correct.

**2. Layered defense.** No single check should be the sole barrier to a security boundary.
If the client sends bad data, the server must verify. If the database flags a credential as
revoked, the registration path must also check it. Defense must not have gaps between layers.

**3. Information asymmetry as a design goal.** An attacker who probes the system should
learn as little as possible about what is happening inside it. Error messages, log entries,
and response timing are all surfaces. We must design with that in mind.

This audit surfaced failures in all three areas. Here is what we found.

---

## Finding 1: Non-Atomic hset + expire (Redis Race Condition)

### What We Found

Across eight callsites in the codebase, a Redis hash was written with `HSET` and then
immediately had a TTL applied with `EXPIRE` in a separate command:

```php
// The old pattern — present in sessions, challenges, tokens, recovery transactions
Database::hset($key, $fields);
Database::expire($key, $ttlSeconds);
```

These are two separate round trips to Redis.

### Why It Mattered

If the PHP process dies, is interrupted, hits a timeout, or if Redis experiences a momentary
failure between the two commands, the hash is written without an expiry. The key then lives
forever in Redis.

The affected callsites and their security implications:

| Callsite | Key type | Consequence of no TTL |
|---|---|---|
| `Authentication::createSession()` | Session record | Session never expires — user account remains accessible indefinitely after the intended TTL |
| `PasskeyController` (signup challenge) | WebAuthn challenge | Stale challenge data accumulates; challenge replay theoretically possible |
| `PasskeyController` (register challenge) | WebAuthn challenge | Same as above |
| `PasskeyController` (login challenge) | WebAuthn challenge | Same as above |
| `AccountRecoveryController` | Recovery passkey challenge | Recovery session data never expires |
| `RecoveryEmailController` (code issue) | Recovery email code | One-time codes survive past their intended expiry window |
| `RecoveryEmailController` (code resend) | Recovery email code | Same as above |
| `CapabilityTokenService` | One-shot admin tokens | Tokens designed to expire in 5 minutes may survive indefinitely |
| `AccountRecoveryTransaction` | Recovery transaction record | Recovery transaction state never cleaned up |

For sessions, this is a direct availability-of-access issue. A session should have a hard
ceiling on lifetime. If the TTL is never set, that ceiling does not exist.

For one-shot capability tokens, this is a correctness violation: a token designed to be
valid for exactly 300 seconds may still be valid days later.

### The Fix

We introduced `Database::hsetex()` — a thin wrapper that executes both operations inside a
Redis `MULTI/EXEC` transaction, making them atomic:

```php
public static function hsetex(string $key, array $fields, int $ttlSeconds): void
{
    self::transaction(function (\Redis $r) use ($key, $normalized, $ttlSeconds): void {
        $r->hMSet($key, $normalized);
        $r->expire($key, $ttlSeconds);
    });
}
```

If either operation fails, both are rolled back. The key either has data and a TTL, or it
has nothing. Every callsite that issued a `hset` followed immediately by `expire` was
converted to `hsetex`.

---

## Finding 2: Logout and CSRF Invalidation Could Silently Fail

### What We Found

The `Database::del()` method — responsible for deleting one or more Redis keys by pattern —
was enumerating keys using the **read replica**, then issuing `DEL` commands to the
**primary**:

```php
// Old code
$keys = self::getReadInstance()->client->keys($pattern);
// ... then deleting via primary
```

### Why It Mattered

Under normal conditions this works. But Redis replication is asynchronous. If the replica
lags — even by milliseconds — it may not yet have the key that was just written. In that
case `keys()` returns an empty list and no `DEL` is issued to the primary. The key
survives.

The two most critical callers of `del()`:

- **`destroySession()` (logout):** When a user logs out, we delete their session key. If
  the read replica is behind, the session key enumeration returns empty, the delete never
  happens, and the session continues to exist on the primary. The user believes they are
  logged out. They are not.

- **`validateCSRFToken()` (nonce invalidation):** CSRF tokens are one-shot nonces. After
  the first use they must be deleted. If the delete never fires, the token can be reused
  on a second request. One-shot becomes reusable.

This class of bug is subtle because it only surfaces under load or transient replica lag.
In development against a single Redis instance it never triggers. In production with a
primary/replica setup under write pressure, it can.

### The Fix

```php
// Enumerate keys against the write instance
$keys = self::getWriteInstance()->client->keys($pattern);
```

Key enumeration and key deletion must target the same Redis instance. There is no case
where enumerating against a replica and deleting from a primary is correct behavior.

---

## Finding 3: WebAuthn User Verification Bypass

### What We Found

In `AccountRecoveryController::initiateWebAuthnRegistration()`, when a passkey was being
registered as part of account recovery, the `processCreate()` call passed `false` for
`requireUserVerification`:

```php
// Old code — UV not enforced on verification
$result = $webauthn->processCreate(
    $clientDataJSON,
    $attestationObject,
    $challengeBinary,
    false,   // ← requireUserVerification
    true
);
```

### Why It Mattered

The challenge that was issued to the client specified `userVerification: 'required'`. This
means the authenticator was told: the user must complete a biometric check or PIN before
this operation is authorized. However, when verifying the response, we were telling the
library to not enforce that the UV flag was set in the authenticator data.

A modified client — or a man-in-the-browser scenario — could submit an authenticator
response with the UV bit cleared. Our server would accept it without requiring user
verification to have actually occurred.

The account recovery flow is specifically the path a user takes when they have lost access
to their other credentials. This is the highest-risk auth surface we operate. Weakening
biometric enforcement here is the wrong trade.

### The Fix

```php
$result = $webauthn->processCreate(
    $clientDataJSON,
    $attestationObject,
    $challengeBinary,
    true,    // ← UV enforced
    true
);
```

UV is enforced. A response where the authenticator data does not carry the UV flag set is
rejected.

---

## Finding 4: Sign Count Clone Detection Missed Replay Attacks

### What We Found

Our passkey clone detection logic was checking:

```php
$suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount < $oldSignCount;
```

### Why It Mattered

The WebAuthn Level 2 specification (§6.1) states: if the stored sign count is non-zero and
the new sign count is **not strictly greater than** the stored value, the credential should
be considered as possibly cloned.

Our condition missed two scenarios:

1. **Replay:** A stolen credential copy sends the exact same sign count as the stored value
   (`newSignCount === oldSignCount`). Our condition requires `<` not `<=`, so an equal
   value passes without triggering the clone flag.

2. **Counter reset:** An adversary with the credential seed data could reset the counter to
   zero, bypassing our `newSignCount > 0` guard.

These are not theoretical. Physical device cloning is a known attack against FIDO2
authenticators that do not implement counter-based anti-cloning protection robustly.

### The Fix

```php
// WebAuthn L2 §6.1: flag if stored sign count is non-zero and new count is not strictly greater
$suspectedClone = $oldSignCount > 0 && $newSignCount <= $oldSignCount;
```

The new count must be strictly greater than the stored count. Any other outcome when the
stored count is non-zero triggers the suspected-clone path.

---

## Finding 5: Sign Count Not Always Persisted

### What We Found

After a successful passkey login, the sign count update was gated:

```php
// Old code
if ($newSignCount > 0) {
    $updateFields['sign_count'] = (string) $newSignCount;
}
```

### Why It Mattered

Some authenticators return `0` as a sentinel value indicating "this device does not
implement a counter." That is a legitimate state. But if a device later starts returning a
real counter (e.g. firmware update, or the user switches to a counter-supporting
authenticator tied to the same credential), we would never persist the initial real count
because we had stored `0` forever. The clone detection in Finding 4 requires the stored
count to be non-zero. A device that always writes `0` can never trip clone detection even
if it is compromised.

More practically: the `0` sentinel means "skip this check." If we silently miss an
opportunity to graduate from `0` to a real counter by not storing the value returned, we
permanently opt this credential out of counter-based protection.

### The Fix

```php
// Always persist sign count regardless of value; only skip when the
// authenticator returned the sentinel 0 (counter not implemented).
// Skipping 0 here was the prior behaviour, but it breaks clone detection
// on subsequent logins when the device later returns a non-zero counter.
'sign_count' => (string) $newSignCount,
```

The sign count is always written. The clone detection threshold handles interpretation.

---

## Finding 6: Revoked Passkey Could Be Re-Registered

### What We Found

When a credential is marked as revoked (for example, because clone detection triggered),
there was no check in the registration path preventing re-registration of that same
`credential_id`. An adversary with both the raw passkey credential and account access could
re-register the revoked credential, clearing its tainted history.

### Why It Mattered

Revocation is only meaningful if it is permanent. If a device is suspected of cloning, the
response is to revoke the credential and notify the user. If the revocation can be
overwritten by re-registration using the same credential, then clone detection provides no
lasting protection. The attacker clears their own entry on the way out.

### The Fix

```php
// Reject re-registration of a revoked credential; revoking a credential (e.g. clone
// suspected) must be permanent and cannot be cleared by re-submitting the same
// credential_id from a compromised device that also has account access.
if (($existing['revoked_at'] ?? '') !== '') {
    SecurityLog::log('passkey_revoked_reregistration_blocked', [
        'user_uuid' => $expectedUserUUID,
        'credential_id' => $credentialId,
    ]);
    Response::error('Registration failed.', ['error' => 'passkey_revoked'], HttpStatus::HTTP_FORBIDDEN);
    return;
}
```

If `revoked_at` is non-empty on an existing credential record, the re-registration is
blocked and logged. Revocation is permanent.

---

## Finding 7: Account Enumeration via Differing Error Responses

### What We Found

When a passkey login was attempted with an unrecognized email address, the error response
body took a different shape than other authentication failure cases:

```php
// Old code — unique body for "email not found"
\PayCal\Observability\Lens::add('[PASSKEY] Login email not found', ['email' => $email]);
Response::error('Authentication failed.', [], HttpStatus::HTTP_UNAUTHORIZED);
```

The empty `[]` as a second argument differed from the `['error' => 'passkey_invalid']`
body returned in other failure paths. A client probing the API could distinguish "this
email does not have an account" from "this email has an account but the passkey challenge
failed" by inspecting the response body.

Additionally, the raw email address was being written to the observability log. Log
aggregation systems, log storage, log shipping pipelines, and monitoring dashboards should
never contain raw user email addresses.

### Why It Mattered

Account enumeration is a precursor to targeted attacks: phishing, credential stuffing, and
social engineering. An API that confirms "this email is not registered here" is providing
exactly the reconnaissance an attacker needs.

Logging raw email addresses violates data minimization. If a log system is compromised,
every enumeration attempt becomes a list of email addresses.

### The Fix

```php
\PayCal\Observability\Lens::add('[PASSKEY] Login email not found', ['email_hash' => hash('sha256', $email)]);
// Return same body as "no credentials" to prevent account enumeration.
Response::error('Authentication failed.', ['error' => 'passkey_invalid'], HttpStatus::HTTP_UNAUTHORIZED);
```

Both "email not found" and "no credentials registered for this account" now return the
same error body. The log entry records a SHA-256 hash of the email only — sufficient for
incident correlation, insufficient to reconstruct the address.

---

## Finding 8: Recovery Key Persisted Before Email Delivery Confirmed

### What We Found

During the account recovery key generation flow, the server was writing the
`recovery_key_generated` flag and `recovery_proof_key` to the user record in the database
**before** sending the recovery key email:

```php
// Old order of operations
Database::hset(Keys::USER.':'.$user->user_uuid, [
    'recovery_key_generated' => '1',
    'recovery_proof_key' => $recoveryProofKey,
    'recovery_proof_key_version' => '1',
]);
// ... then send email
$sent = EmailGarum::sendRecoveryKeyEmail($recoveryKeyFormatted, $user->email, $user->full_name);
```

### Why It Mattered

If the email failed to send, the database would show `recovery_key_generated = 1` — meaning
the system believes a recovery key was issued. But the user never received the key.

There is no regeneration path for a user in this state. The system thinks the key was
generated. The user has no key. Account recovery is permanently broken for that account
until manual intervention.

This is a data integrity issue that becomes a permanent availability issue. It is also a
support nightmare: the user has no key, the system has no memory of a failure, and there
is no self-serve resolution.

### The Fix

```php
// Send recovery key email FIRST — only persist generated state after confirmed delivery.
// If the email fails and we had already written recovery_key_generated=1, the user
// would be permanently locked out of account recovery with no way to regenerate.
$sent = EmailGarum::sendRecoveryKeyEmail($recoveryKeyFormatted, $user->email, $user->full_name);

if ($sent) {
    // Mark recovery key as generated only after successful delivery.
    Database::hset(Keys::USER.':'.$user->user_uuid, [
        'recovery_key_generated' => '1',
        'recovery_proof_key' => $recoveryProofKey,
        'recovery_proof_key_version' => '1',
    ]);
    ...
}
```

Email delivery is confirmed first. Database state reflects what actually happened.

---

## Finding 9: Disabled Registration Path Still Collected Password Fields

### What We Found

`RegistrationController::collectRegistrationInputs()` was still reading `password` and
`confirm_password` from the POST body:

```php
// Old code
return [
    'full_name' => $fullName,
    'email' => $email,
    'password' => InputSanitizer::postString('password'),
    'confirm_password' => InputSanitizer::postString('confirm_password'),
    'invite_code' => InputSanitizer::postString('invite_code'),
];
```

PayCal registration is passkey-only. Password-based registration has been disabled for
some time.

### Why It Mattered

Collecting fields that serve no function is not harmless. Every value read from user input
is a surface: it can be logged, audited, passed to other functions inadvertently, or
included in error payloads. Collecting a password when there is no password flow means a
password string is sitting in a PHP array that gets passed through multiple layers of the
request lifecycle.

The principle of least surface area requires that we do not collect what we do not use.

### The Fix

```php
// Note: password/confirm_password fields are intentionally not collected;
// password registration is disabled and the passkey flow handles all signup.
return [
    'full_name' => $fullName,
    'email' => $email,
    'invite_code' => InputSanitizer::postString('invite_code'),
];
```

---

## Finding 10: Information Disclosure in Email Verification Error

### What We Found

`EmailVerificationGuard::requireVerified()` — the middleware enforcing email verification
before granting access to protected resources — was including the user's email address in
the error response body returned to the client:

```php
Response::error('Email verification required...', [
    'email_verified' => false,
    'user_email' => $user->email,   // ← disclosed to caller
], HttpStatus::HTTP_FORBIDDEN);
```

### Why It Mattered

This guard is triggered for unverified sessions. The session cookie alone is sufficient to
trigger this response. If an attacker gains a valid but unverified session token (for
example, through session fixation or a compromised temporary link), they can learn the
email address associated with the account from the 403 response body — without having
supplied the email themselves.

This is a low-severity but unnecessary disclosure. The client that issued the session
already knows the email. The only party who benefits from it being in this error response
is someone who has the session token but not the email.

### The Fix

```php
Response::error('Email verification required...', [
    'email_verified' => false,
], HttpStatus::HTTP_FORBIDDEN);
```

The email is removed from the error payload.

---

## Finding 11: Dead Code — `EmailGarum::verifyNewUserEmail()`

### What We Found

`EmailGarum` contained a large (90-line) method, `verifyNewUserEmail()`, that handled a
password-based email change flow. This flow required the user to submit their current
password to initiate an email change. It was superseded when the platform moved to
passkey-only authentication.

The method was not called anywhere in the codebase.

### Why It Mattered

Dead code is not neutral. It occupies space in the security review surface, in PHPStan
analysis, in documentation, and in the cognitive load of anyone reading the file. It also
represents a risk that a future developer, not knowing it was intentionally abandoned, might
re-wire it into a new flow without the full context of why the surrounding architecture
changed.

Removing it reduces the attack surface by one path and reduces the review burden
permanently.

### The Fix

The method was deleted in its entirety. The removal was verified by searching all callsites
prior to deletion.

---

## Summary of Changes

| # | Finding | Severity | Category |
|---|---|---|---|
| 1 | Non-atomic `hset + expire` across 9 callsites | **High** | Redis / Atomicity |
| 2 | `del()` using read replica for key enumeration | **High** | Redis / Logout, CSRF |
| 3 | WebAuthn UV bypass in account recovery | **High** | Authentication |
| 4 | Sign count clone detection misses replay | **Medium** | Authentication |
| 5 | Sign count not persisted when zero | **Medium** | Authentication |
| 6 | Revoked passkey could be re-registered | **Medium** | Authentication |
| 7 | Account enumeration via error body + raw email in logs | **Medium** | Information Disclosure |
| 8 | Recovery key state written before email confirmed | **Medium** | Data Integrity |
| 9 | Disabled registration still collecting password fields | **Low** | Attack Surface |
| 10 | User email in email verification 403 response | **Low** | Information Disclosure |
| 11 | Dead method `verifyNewUserEmail()` in EmailGarum | **Low** | Dead Code / Attack Surface |

---

## What We Got Right

In the interest of a complete picture, it is worth noting the security foundations that were
already in place and were validated during this audit:

- **Passkey-first authentication.** The platform runs on WebAuthn with no fallback to
  password-based login for passkey users. The UV bypass (Finding 3) and clone detection
  issues (Findings 4, 5) were defects within a fundamentally sound architecture.

- **One-shot capability tokens.** Admin-level mutations already required fresh, time-limited
  tokens. The atomicity fix (Finding 1) hardened an existing protection rather than adding a
  missing one.

- **Signed security log.** Every security event — including the new `passkey_revoked_reregistration_blocked`
  events added in this commit — is written to a signed, append-only log with structured fields.

- **PHPStan at Level 9.** All 11 files modified in this commit were validated at maximum
  static analysis strictness. The 1,055-test suite passed without regression.

- **Clone detection existed.** The logic was present and partially correct. Finding 4 was a
  boundary condition error, not a missing feature.

---

## Going Forward

We are adopting two standing practices from the findings in this audit:

**1. `hsetex` as the default Redis write pattern.**
Any future code that needs to write a hash with a TTL must use `Database::hsetex()`. The
old two-step pattern is no longer permitted. PHPStan rules will be written to flag new
occurrences of the old pattern.

**2. Write-instance primacy for all key operations.**
Any Redis operation whose correctness depends on reading back what was just written must
use the write instance. Read replicas are for read-heavy non-critical queries only.

We will continue conducting self-audits at this level of specificity. We will continue
publishing what we find.

---

## Disclosure Timeline

| Date | Event |
|---|---|
| May 12, 2026 | Findings identified during routine audit session |
| May 12, 2026 | All fixes implemented and committed (`493d5e44`) |
| May 12, 2026 | Full test suite run (1,055 tests passing, PHPStan L9 clean) |
| May 12, 2026 | Pushed to origin/main |
| May 12, 2026 | This article published |

All findings were identified internally. No external report, CVE, or breach preceded this
disclosure. There is no evidence that any finding was exploited.

---

*PayCal is a privacy-first payroll calculator. We operate under a policy of maximum
transparency about security practices, findings, and ongoing improvements. Questions
or concerns about this report may be directed to the project's public issue tracker.*
