# PayCal Security Interrogation Evidence (2026-03-23)
This document re-answers the 7 interrogation items with implementation evidence from the current codebase, including source snippets, file references, and verified test/static-analysis outcomes.

### Delta Report: Cycle 37 (2026-03-23)

1. What changed (files and behaviors)
  - `html/js/calendar/calendar.js`
    - Added DOM sensitivity scrub behavior in zeroization path (`scrubSensitiveCalendarDom`) to clear runtime payload artifacts (grid data attributes, rendered cell content, modal/context-menu state, and clipboard cache key).
    - Added dev-only guarded test hooks used by smoke lifecycle verification (`forceHasDek`, `setProfileMarker`, `getState`).
  - `tests/smoke-ui/dev-bypass-smoke.spec.js`
    - Added unlocked hidden-delay expiry regression proof.
    - Added deterministic post-zeroization re-unlock recovery proof.
  - `html/tests/Integration/CalendarControllerIntegrationTest.php`
  - `html/tests/Integration/SitesControllerIntegrationTest.php`
    - Removed subprocess-isolation helper usage to satisfy integration harness contract consistency (direct invocation pattern only).

2. What was verified (tests/commands and results)
  - Command: `npx playwright test tests/smoke-ui/dev-bypass-smoke.spec.js --config=playwright.smoke.config.js`
  - Result: `8 passed`
  - Command: `npm run test:js`
  - Result: pass (`eslint` + JS sink check)
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`
  - `runTests` full suite
  - Result: `passed=1056 failed=0`

3. Which statuses changed (Q1..Q7)
  - No interrogation label changed.
  - Workstream D runtime closure evidence moved from conditional to closed in current cycle artifacts.

4. Remaining blockers and next immediate step
  - No open high-severity blocker remains in this in-scope control set.
  - Next immediate step: keep lifecycle and harness regressions pinned in CI as required release gates.

## 1) Integrity of Zero-Knowledge Model (plaintext site metadata)

### Finding
- `site_name` is not returned plaintext by client encryption anymore.
- `site_id` remains plaintext for routing/keying and is bound as AES-GCM AAD.
- Server-side persistence path stores ciphertext-only payloads for work-entry financial data.
- Guardian now exposes an explicit metadata-correlation policy surface and a formal policy document exists.

### Evidence Snippet A: client encryption return shape
File: `html/js/calendar/crypto-worker.js:442`
```javascript
const aad = String(entry.site_id || '');
...
return {
  site_id: entry.site_id,
  // site_name is now encrypted in the blob, not returned as plaintext
  encrypted_blob: btoa(JSON.stringify({
    dek_version: self.cryptoState.dekVersion || 1,
    ciphertext: bytesToB64(new Uint8Array(ciphertext)),
    nonce: bytesToB64(nonce),
    aad,
  })),
};
```

### Evidence Snippet B: server enforces encrypted blob on calendar writes
File: `html/src/Controllers/CalendarController.php:260`
```php
if ('' === $blob) {
  Response::error('[CC] encrypted_blob required for all calendar entries.', [], HttpStatus::HTTP_UNPROCESSABLE);
  return;
}
```

File: `html/src/Controllers/CalendarController.php:276`
```php
// site_name is now encrypted in the blob, not stored as plaintext
```

### Evidence Snippet C: zero-knowledge storage contract (ciphertext only)
File: `html/src/Domain/WorkEntry.php:481`
```php
// Zero-knowledge storage contract: persist ciphertext only.
$fieldsToStore = ['encrypted_blob' => $blob];
Database::hset($workEntryKey, $fieldsToStore);
```

### Evidence Snippet D: explicit Guardian metadata-correlation policy artifact
File: `html/js/guardian.js:34`
```javascript
const metadataCorrelationPolicy = Object.freeze({
  version: '2026-03-23',
  defaultDecision: 'deny',
  allowedContexts: Object.freeze(['security-incident', 'fraud-investigation', 'regulatory-legal-hold']),
});

function getMetadataCorrelationPolicy() { return metadataCorrelationPolicy; }
function canCorrelateMetadata(context) { ... }
```

File: `docs/GUARDIAN_METADATA_CORRELATION_POLICY.md:1`
```markdown
# Guardian Metadata Correlation Policy
Correlation is denied by default.
Allowed Contexts (Allowlist)
Required Controls for Any Allowed Correlation
```

### Evidence Snippet E: server-side metadata correlation deny gate at read boundary
File: `html/src/Domain/Security/MetadataCorrelationPolicy.php:1`
```php
final class MetadataCorrelationPolicy
{
  private const ALLOWED_CONTEXTS = [
    'self-service-earnings' => ['site_metadata:financial_payload'],
    'security-incident' => ['site_metadata:financial_payload'],
  ];
}
```

File: `html/src/Controllers/EarningsController.php:383`
```php
if (!self::canCorrelateSiteMetadataWithFinancialPayload()) {
  Response::error('[EC] Correlation context denied.', [
    'context' => self::correlationContext(),
    'reason' => 'metadata_correlation_denied',
  ], HttpStatus::HTTP_FORBIDDEN);
  return;
}
```

### Assessment against requested proof
- Plaintext `site_id` necessity is demonstrated (AAD/keying/path selection).
- Explicit Guardian metadata-correlation policy artifact exists in runtime surface and documentation.
- Server-side deny-by-default enforcement now exists on earnings read boundaries via `MetadataCorrelationPolicy` gate.
- Caveat: broader enforcement rollout is still needed beyond earnings endpoints.

### Remaining Gap (explicit)
- Requested proof of an explicit Guardian policy artifact is now satisfiable.
- Remaining gap is enforcement depth: deny-by-default is exposed via Guardian runtime/documentation, but full server-side deny checkpoints should still be added for complete closure.

### Required Remediation for Q1 Proof Closure
1. Add a formal server-side correlation policy artifact.
  - Example target: `html/src/Domain/Security/MetadataCorrelationPolicy.php`.
  - Policy intent: explicitly deny any code path that joins plaintext site metadata with decrypted financial payloads outside authenticated user-owned, least-privilege contexts.
2. Enforce policy at read/query boundaries.
  - Add guard calls in earnings/reporting aggregation entry points before payload composition.
  - Return explicit denial telemetry/log event when policy rejects a request.
3. Add negative integration tests (required for proof).
  - Assert forbidden correlation attempts fail with expected status.
  - Assert no plaintext financial fields are exposed when only metadata context is present.
4. Add positive integration tests (narrowly scoped).
  - Assert allowed same-user, authorized decrypt + render flow still succeeds.
5. Add auditability evidence.
  - Log policy decision outcomes with non-PII identifiers and reason codes.
  - Document policy invariants in a dedicated security section.

### Acceptance Criteria for Q1 Closure
- A dedicated policy class/rule exists and is invoked by production read paths.
- Failing tests demonstrate blocked metadata-to-financial correlation outside allowed context.
- Passing tests demonstrate authorized same-user flows remain functional.
- Security docs include policy invariants, decision points, and test references.

---

## 2) Verification of PHPStan Level 9

### Finding
- Level 9 is configured and enforced in CI/hooks with no baseline allowed.
- Latest strict run in this workspace is **clean** after remediation of `AdminPageController.php` nullability.

### Evidence Snippet A: phpstan config level
File: `html/phpstan.neon:2`
```neon
parameters:
  level: 9
```

### Evidence Snippet B: CI enforces Level 9 and forbids baseline
File: `.github/workflows/phpstan.yml:32`
```yaml
- name: Enforce No Baseline Policy
  run: |
    if grep -q "baseline" phpstan.neon; then
      echo "❌ Baselines are not allowed."
      exit 1
    fi
```

File: `.github/workflows/phpstan.yml:43`
```yaml
- name: Run PHPStan
  run: vendor/bin/phpstan analyse --level=9 --no-progress --memory-limit=512M
```

### Evidence Snippet C: local hooks enforce same policy
File: `githooks/pre-push:21`
```bash
vendor/bin/phpstan analyse --level=9 --memory-limit=1G --no-progress
```

### Verified run output (2026-03-23)
Historical run:
```text
[ERROR] Found 2 errors
Line :124 Parameter #1 $string of function strlen expects string, string|null given.
Line :129 Parameter #1 $string of function substr expects string, string|null given.
File: Controllers/AdminPageController.php
```

Remediation run:
Command run: `cd <REPO_ROOT>/html && composer run phpstan:strict`
```text
[OK] No errors
```

### Evidence Snippet D: typed core domain sample (no mixed signatures shown)
File: `html/src/Domain/Taxes.php:38`
```php
interface TaxCalculatorInterface
{
  public function calculateCents(int $amountCents): int;
}
```

File: `html/src/Domain/Taxes.php:252`
```php
public function calculateTaxesCents(int $incomeCents): array
```

---

## 3) Protection of injected PII (`window.PAYCAL_USER_PROFILE`)

### Finding
- Plaintext high-value profile bootstrap payload in `earnings/index.php` has been removed.
- `window.PAYCAL_USER_PROFILE_ENCRYPTED` is currently initialized as an empty placeholder object to avoid server-side plaintext injection in page source.
- Earnings export path no longer reads `window.PAYCAL_USER_PROFILE` for report generation.
- CSP + Trusted Types + Guardian reduce some XSS vectors but do not prove protection against all same-origin script execution or browser extensions.

### Evidence Snippet A: PII injected into global object
File: `html/js/earnings/index.php:179`
```php
// Do not expose plaintext profile PII in page source.
// Profile data must be fetched/decrypted through authenticated runtime paths only.
window.PAYCAL_USER_PROFILE_ENCRYPTED = {};
```

File: `html/js/earnings/index.php:735`
```javascript
const employee = window.PAYCAL_USER_UUID || 'PayCal User';
const reportParams = {
  year: exportYear,
  employee,
  email: '',
  phone: '',
  ipAddress: 'unknown',
  address: '',
  rows,
};
```

### Evidence Snippet B: CSP and Trusted Types
File: `html/header.php:107`
```php
'script-src' => array_merge(
  ["'self'", $origin, 'www.googletagmanager.com'],
  Environment::devAllowInlineScripts() ? ["'unsafe-inline'"] : []
),
```

File: `html/header.php:123`
```php
$trustedTypesPolicy = trim($policy . " require-trusted-types-for 'script'; trusted-types default paycal;");
header('Content-Security-Policy: '.$trustedTypesPolicy);
```

### Evidence Snippet C: Guardian write-guard
File: `html/js/guardian.js:1`
```javascript
// TrustedHTML-oriented DOM write guard ...
// strips script/iframe/object/embed and inline handlers
```

### Assessment against requested proof
- CSP/Guardian controls exist and plaintext bootstrap injection in this path has been removed.
- Main-thread export/profile-global dependency has been removed and worker profile retrieval actions have been reduced.
- Caveat remains for browser-extension and same-origin script risk classes that are not solvable by app code alone.

---

## 4) Deterministic KEK robustness across format/rotation changes

### Finding
- Deterministic KEK derivation and compatibility migration are explicitly implemented.
- Recovery material path provides continuity independent of passkey KEK path.

### Evidence Snippet A: derivation mode support
File: `html/js/calendar/crypto-worker.js:85`
```javascript
async function derivePasskeyKEK(credentialId, userId, saltBase64, derivationMode = 'credential-only') {
  const ikmMaterial = (derivationMode === 'credential-user')
    ? encoder.encode(`${credentialId}|${userId || ''}`)
    : encoder.encode(String(credentialId || ''));
```

### Evidence Snippet B: canonical-first, legacy fallback, canonical re-wrap
File: `html/js/calendar/calendar.js:513`
```javascript
console.warn('[CRYPTO] Canonical unwrap failed, attempting legacy fallback:', ...);
...
derivationMode: 'credential-user',
...
// Legacy unwrap succeeded: immediately re-wrap with canonical derivation
derivationMode: 'credential-only',
```

### Evidence Snippet C: hard guard against accidental DEK regeneration
File: `html/js/calendar/calendar.js:589`
```javascript
if (bootstrapData.wrappedDek || bootstrapData.wrappedDekPasskey) {
  throw new Error('[CRYPTO] DEK regeneration forbidden while wrapped DEK exists');
}
```

### Evidence Snippet D: recovery continuity material
File: `html/js/calendar/crypto-worker.js:209`
```javascript
async function generateRecoveryMaterial(payload) {
  const recoveryKeyBytes = crypto.getRandomValues(new Uint8Array(32));
  ...
  return {
    recoveryKey: formatRecoveryKey(encodedRecoveryKey),
    accountRecoverySalt: bytesToB64(saltBytes),
    recoveryProofKey: bytesToB64(new Uint8Array(proofKeyBits)),
    wrappedDekRecovery: btoa(JSON.stringify({ ... })),
  };
}
```

File: `html/src/Controllers/AccountRecoveryController.php:327`
```php
Database::hset(Keys::USER . ':' . $transaction->userUuid() . ':passkey_wrapped_deks', [$credentialId => $wrappedDekPasskey]);
```

---

## 5) Telemetry de-anonymization risks

### Finding
- Recovery abuse telemetry counters are aggregated by metric + day key.
- Telemetry ingest now scrubs direct identifiers and logs only pseudonymous rotating tokens.
- Client telemetry send path now batches events and applies randomized flush jitter.
- Formal retention/access-boundary policy is now defined and mapped into runtime telemetry stream metadata.

### Evidence Snippet A: daily aggregate counters
File: `html/src/Domain/AccountRecoveryAbuseGuard.php:66`
```php
$key = Keys::accountRecoveryTelemetry($metric, date('Y-m-d'));
if (!Database::exists($key)) {
  Database::set($key, '0', self::METRIC_TTL_SECONDS);
}
Database::incr($key);
```

### Evidence Snippet B: server telemetry endpoint now pseudonymizes and scrubs
File: `html/src/Controllers/TelemetryController.php:38`
```php
private static function telemetrySubjectToken(string $userUUID): string
{
  return substr(hash('sha256', $userUUID . '|' . date('Y-m-d-H')), 0, 24);
}

private static function scrubFields(array $fields): array
{
  $blocked = ['user_uuid', 'uuid', 'userId', 'user_id', 'ip', 'ip_address', 'email', 'phone', 'address', 'full_name'];
  // ... remove blocked identifiers from payload
}

$log = [
  'timestamp' => date('c'),
  'type' => $type,
  'fields' => $safeFields,
  'subject_token' => self::telemetrySubjectToken($userUUID),
  'network_token' => self::networkClassToken(),
  'user_agent_hash' => substr(hash('sha256', $userAgent), 0, 24),
];
```

### Evidence Snippet C: client telemetry batching + jitter flush
File: `html/js/phantomwing/index.php:115`
```javascript
const _pw_telemetry_delivery = {
  minBatchSize: 3,
  baseDelayMs: 4000,
  jitterMs: 2500,
  maxHoldMs: 15000,
};

const jitter = Math.floor(Math.random() * _pw_telemetry_delivery.jitterMs);
const delay = _pw_telemetry_delivery.baseDelayMs + jitter;

await _pw_post_telemetry(`pw.${entry.category}.${entry.type}`, {
  count: entry.count,
  bucket_minute: Math.floor(now / 60000),
  flush_reason: reason,
});
```

### Evidence Snippet D: explicit retention/access boundary policy and runtime mapping
File: `html/src/Domain/TelemetryPolicy.php:1`
```php
final class TelemetryPolicy
{
  public const STREAM_PRODUCT = 'product';
  public const STREAM_SECURITY = 'security';
  public const RETENTION_DAYS_PRODUCT = 30;
  public const RETENTION_DAYS_SECURITY = 90;
}
```

File: `html/src/Controllers/TelemetryController.php:199`
```php
$streamMeta = TelemetryPolicy::describeStream(TelemetryPolicy::STREAM_PRODUCT);
...
'stream' => TelemetryPolicy::STREAM_PRODUCT,
'retention_days' => $streamMeta['retention_days'],
'access_boundary' => $streamMeta['access_boundary'],
```

File: `docs/TELEMETRY_RETENTION_ACCESS_POLICY.md:1`
```markdown
# Telemetry Retention and Access Boundary Policy
Product telemetry retention: 30 days.
Security telemetry retention: 90 days.
Cross-stream joins are denied by default.
```

### Assessment against requested proof
- Anti-correlation controls are implemented across pseudonymization, jittered batching, and explicit retention/access boundary policy.
- Caveat: enforcement telemetry now carries stream metadata, but full role-gated datastore/query enforcement remains a recommended hardening step.

---

## 6) Brute-force resistance of recovery proofs

### Finding
- Recovery flow has layered controls (secret-bound transaction, nonce, TTL, client binding, replay/IP block).
- Dedicated `RateLimiter` windows on `/api/v1/auth/recovery/*` endpoints are now enforced at controller entry.
- Per-route policies are wired to `SystemConfig` keys and return deterministic 429 metadata.

### Evidence Snippet A: secret-bound transaction + hash-equals checks
File: `html/src/Domain/AccountRecoveryTransaction.php:35`
```php
$txnSecret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
```

File: `html/src/Domain/AccountRecoveryTransaction.php:109`
```php
return $txnSecret !== '' && hash_equals((string) ($this->data['txn_secret_hash'] ?? ''), hash('sha256', $txnSecret));
```

### Evidence Snippet B: proof nonce + TTL + client binding
File: `html/src/Domain/AccountRecoveryTransaction.php:173`
```php
$proofNonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
```

File: `html/src/Domain/AccountRecoveryTransaction.php:187`
```php
&& $this->matchesClientBinding($fingerprintHash, $ipClass)
&& hash_equals((string) ($this->data['proof_nonce_hash'] ?? ''), hash('sha256', $proofNonce))
&& time() <= (int) ($this->data['proof_nonce_expires_at'] ?? '0')
&& hash_equals($expectedProof, $proof);
```

### Evidence Snippet C: replay block with Redis counters
File: `html/src/Domain/AccountRecoveryAbuseGuard.php:24`
```php
$windowSeconds = max(60, (int) SystemConfig::get('account_recovery_replay_block_window_seconds'));
$count = Database::incr($counterKey);
...
return $count >= max(1, (int) SystemConfig::get('account_recovery_replay_block_threshold'));
```

### Evidence Snippet D: explicit route-level limiter policy + controller enforcement
File: `html/src/Domain/RateLimiter.php:84`
```php
public static function checkRecoveryEndpointLimit(string $route, string $clientIP, string $txnId = ''): array
{
  $policies = [
    'start' => ['config' => 'account_recovery_max_starts_per_day', 'window' => 86400, ...],
    'resend' => ['config' => 'account_recovery_max_resends_per_hour', 'window' => 3600, ...],
    'prove-key' => ['config' => 'account_recovery_max_verify_attempts', 'window' => 3600, ...],
    'bootstrap' => ['config' => 'account_recovery_max_verify_attempts', 'window' => 3600, ...],
  ];
  // ... checkWindowLimit(...)
}
```

File: `html/src/Controllers/AccountRecoveryController.php:486`
```php
private function enforceRecoveryRateLimit(string $route, string $txnId = ''): void
{
  $rate = RateLimiter::checkRecoveryEndpointLimit($route, Security::getClientIPAddress(), $txnId);
  if ($rate['allowed']) {
    return;
  }

  Response::error('Recovery rate limit exceeded.', [
    'route' => $route,
    'remaining_requests' => $rate['remaining'],
    'quota' => $rate['limit'],
    'window_seconds' => $rate['window_seconds'],
    'reset_at' => $rate['reset_at'],
  ], HttpStatus::HTTP_TOO_MANY_REQUESTS);
}
```

### Evidence Snippet E: integration proof of deterministic 429 envelope
File: `html/tests/Integration/AccountRecoveryControllerIntegrationTest.php:245`
```php
SystemConfig::set('account_recovery_max_starts_per_day', 1);
$second = $this->runControllerCall('start', ['email' => 'limit-b@example.com']);

$this->assertSame('error', $second['status'] ?? null);
$this->assertSame('Recovery rate limit exceeded.', $second['message'] ?? null);
$this->assertSame('start', $second['route'] ?? null);
$this->assertSame(1, (int) ($second['quota'] ?? 0));
$this->assertSame(86400, (int) ($second['window_seconds'] ?? 0));
```
### Assessment against requested proof
- Strong anti-replay controls exist.
- Explicit endpoint window proof is now demonstrable from controller/RateLimiter integration with deterministic 429 metadata.
- Additional per-route saturation tests are still advisable, but core requested control is implemented.

---

## 7) Consistency of "Never Dual-Write" policy

### Finding
- Production write-path currently enforces encrypted-only work-entry persistence.
- Unit/integration tests validating encrypted-only behavior passed in this workspace.
- Legacy Phase 1 dual-write assertions were replaced with encrypted-only contract assertions.

### Evidence Snippet A: encrypted-only write
File: `html/src/Domain/WorkEntry.php:481`
```php
// Zero-knowledge storage contract: persist ciphertext only.
$fieldsToStore = ['encrypted_blob' => $blob];
Database::hset($workEntryKey, $fieldsToStore);
```

### Evidence Snippet B: reject missing encrypted blob
File: `html/src/Domain/WorkEntry.php:455`
```php
if ('' === $blob) {
  ...
  return false;
}
```

### Evidence Snippet C: tests for encrypted-only expectations
File: `html/tests/Unit/WorkEntryEncryptionTest.php:105`
```php
$result = WorkEntry::updateWorkEntry($workDetails, $userUUID);
$this->assertFalse($result);
$stored = Database::hgetall($workEntryKey);
$this->assertArrayNotHasKey('encrypted_blob', $stored);
```

File: `html/tests/Unit/WorkEntryEncryptionTest.php:156`
```php
public function testGetWorkEntryRejectsPlaintextWhenCryptoRequired(): void
```

### Evidence Snippet D: stale dual-write assertions replaced with encrypted-only assertions
File: `html/tests/Integration/EncryptionPhase1ExitCriteriaTest.php:178`
```php
public function testEncryptedOnlyContractPersistsCiphertextWithoutPlaintextFields(): void
{
  $this->assertArrayNotHasKey('hours', $stored, 'Plaintext hours must not be stored');
  $this->assertArrayNotHasKey('notes', $stored, 'Plaintext notes must not be stored');
}
```

### Evidence Snippet E: contract proof for Guardian policy surface + document
File: `html/tests/Contract/GuardianMetadataCorrelationPolicyContractTest.php:8`
```php
$this->assertStringContainsString("defaultDecision: 'deny'", $guardianJs);
$this->assertStringContainsString('Correlation is denied by default.', $doc);
```

### Verified test outcomes run during this evidence collection
- `runTests` on:
  - `html/tests/Unit/WorkEntryEncryptionTest.php`
  - `html/tests/Unit/SecurityInvariantsTest.php`
  - `html/tests/Integration/WorkEntryLifecycleTest.php`
  - Result: `passed=29 failed=0`
- Additional `runTests` on:
  - `html/tests/Unit/WorkEntryEncryptionTest.php`
  - `html/tests/Unit/SecurityInvariantsTest.php`
  - Result: `passed=11 failed=0`

---

## Consolidated Status (by requested proof standard)

- Q1: **Met with caveat** (explicit Guardian policy surface exists and three server-side controller boundaries now enforce deny-by-default correlation checks; remaining work is narrowing or gating residual admin/export paths).
- Q2: **Met** (Level 9 config/enforcement proven and strict run currently clean).
- Q3: **Met with caveat** (plaintext bootstrap injection removed and main-thread profile-global consumption removed; residual extension/runtime trust model caveat remains).
- Q4: **Met with caveat** (derivation compatibility + recovery continuity are implemented; still depends on retaining either valid passkey path or recovery key).
- Q5: **Met with caveat** (pseudonymous telemetry logging + client batching/jitter + retention/access policy artifact implemented; deeper datastore/query enforcement remains recommended).
- Q6: **Met** (all recovery endpoints now have explicit rate-limit windows plus route-level saturation tests proving 429 metadata on exhaustion).
- Q7: **Met with caveat** (runtime encrypted-only path proven and stale dual-write assertions replaced; file naming/history still references Phase-1).

---

## Feedback Assimilation (2026-03-23)

The external review is accepted and incorporated as authoritative guidance for remediation priority:

- Enterprise-grade proof is fully met in only one category at this time.
- Highest-risk blockers are:
  - plaintext PII exposure in page context,
  - correlatable telemetry data paths,
  - static-analysis non-clean state at enforced PHPStan Level 9.

This section translates that verdict into a concrete correction plan.

---

## Corrective Action Plan and Execution Report

### Objectives

1. Remove plaintext high-value profile data from frontend globals.
2. Make telemetry correlation-resistant by design.
3. Restore and keep PHPStan Level 9 clean.
4. Add formal policy artifacts and test evidence for unresolved proof gaps.
5. Add internal and public-facing accessibility documentation updates (a11y/ARIA/WCAG), including explicit Playwright, Lightpanda, and headless-browser verification coverage aligned with implemented controls.

### Priority Order

1. P0: Q3 Protection of Injected PII (**Partially Met**, runtime isolation gap remains)
2. P0: Q5 Telemetry de-anonymization controls (**Met with caveat**, add datastore/query enforcement checks)
3. P1: Q6 Recovery endpoint explicit rate-limit windows (**Met**, maintain existing route-level saturation matrix)
4. P2: Q1 formal metadata-correlation policy artifact (**Met with caveat**, add server-side deny checkpoints)
5. P2: Q7 retire stale Phase-1 dual-write test (**Met with caveat**, optionally rename legacy file)
6. P3: Add a11y/ARIA/WCAG documentation internally and on transparency page(s), explicitly documenting Playwright + Lightpanda + headless-browser coverage and execution cadence (**Planned**, ensure implementation-to-doc parity and publishing cadence)

Note: Q2 (PHPStan Level 9) has moved to maintenance mode after clean strict run.

---

### Workstream A: Eliminate Plaintext Profile Injection (Q3)

#### Planned Changes

1. Stop injecting raw profile fields into `window.PAYCAL_USER_PROFILE_ENCRYPTED`.
2. Move profile payload creation to server-side encrypted envelope generation before render.
3. Inject only encrypted profile envelope metadata needed for decrypt-on-unlock flow.
4. Ensure decrypted profile lives only in worker memory and short-lived in main thread when strictly required.
5. Add explicit zeroization/nulling after profile-dependent rendering.

#### Candidate Implementation Targets

- `html/js/earnings/index.php`
- `html/js/calendar/crypto-worker.js`
- `html/js/calendar/calendar.js`
- `html/src/Controllers/EarningsController.php` (or equivalent profile bootstrap source)

#### Proof/Validation Required

1. Static grep check: no plaintext `full_name/email/phone/address/ip` injected into window bootstrap objects.
2. Integration test: rendered page source contains encrypted envelope fields only.
3. Runtime test: profile decrypt succeeds only after DEK unlock.
4. Security test: failed unlock does not expose profile fields in global scope.

#### Exit Criteria

- Q3 status upgraded from **Not Met** to **Partially Met** or **Met** based on extension-risk stance.

---

### Workstream B: PHPStan Level 9 Clean Restoration (Q2)

#### Planned Changes

1. Fix the two reported nullability violations in `AdminPageController.php`.
2. Add defensive narrowing (`is_string`, null coalescing, typed helper) before `strlen`/`substr` calls.
3. Re-run strict analysis and preserve no-baseline policy.

#### Candidate Implementation Target

- `html/src/Controllers/AdminPageController.php`

#### Proof/Validation Required

1. Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`.
2. Output must be error-free with `--level=9`.
3. CI/workflow parity retained (`.github/workflows/phpstan.yml`).

#### Exit Criteria

- Q2 status upgraded to **Met**.

---

### Workstream C: Telemetry Anti-Correlation Hardening (Q5)

#### Planned Changes

1. Remove direct `user_uuid` and raw IP from application telemetry logs unless explicitly required for security incident pipelines.
2. Introduce privacy-preserving telemetry identity model:
   - short-lived rotating pseudonymous session token,
   - coarse-grained time buckets,
   - minimum aggregation threshold before write/publish.
3. Add client-side send jitter and batch windows for non-critical telemetry.
4. Separate security event logs from product telemetry counters with strict retention and access boundaries.

#### Candidate Implementation Targets

- `html/src/Controllers/TelemetryController.php`
- `html/js/phantomwing/index.php` (or telemetry client path)
- `html/src/Domain/AccountRecoveryAbuseGuard.php`

#### Proof/Validation Required

1. Code evidence of jitter/batching and aggregation thresholds.
2. Test that single-session event timing cannot be mapped 1:1 to stored counter updates.
3. Documentation update for telemetry privacy model and retention controls.

#### Exit Criteria

- Q5 status upgraded from **Not Met** to **Partially Met** or **Met**.

---

### Workstream D: Recovery Endpoint Explicit Rate-Limit Windows (Q6)

#### Planned Changes

1. Add dedicated `RateLimiter` checks for `/api/v1/auth/recovery/*` routes.
2. Define explicit per-route and per-IP windows (for example: start, resend, prove-key, bootstrap).
3. Wire limits to `SystemConfig` keys and enforce at controller entry points.
4. Emit deterministic 429 responses with remaining/quota metadata.

#### Candidate Implementation Targets

- `html/src/Domain/RateLimiter.php`
- `html/src/Controllers/AccountRecoveryController.php`
- `html/src/Domain/Config/SystemConfig.php`
- `html/src/system-limits-master.php`

#### Proof/Validation Required

1. Integration tests that exceed route quotas and verify 429 behavior.
2. Evidence that limits apply before expensive proof verification operations.
3. Security regression tests for distributed attempt simulation by IP/user dimensions.

#### Exit Criteria

- Q6 status upgraded to **Met**.

---

### Workstream E: Formal Metadata Correlation Policy (Q1)

#### Planned Changes

1. Introduce explicit policy artifact (for example `MetadataCorrelationPolicy`).
2. Enforce policy in reporting/query services that touch both metadata and decrypted financial fields.
3. Add deny-by-default behavior for unauthorized correlation contexts.

#### Proof/Validation Required

1. Unit tests for policy allow/deny matrix.
2. Integration tests asserting blocked joins/derivations outside authorized scope.
3. Audit logs with reason codes for policy denials.

#### Exit Criteria

- Q1 status upgraded from **Partially Met** to **Met**.

---

### Workstream F: Remove Legacy Dual-Write Test Debt (Q7)

#### Planned Changes

1. Retire or rewrite `EncryptionPhase1ExitCriteriaTest` assertions that require plaintext dual-write.
2. Replace with encrypted-only invariants aligned to current production contract.
3. Keep historical migration behavior documented separately from active invariants.

#### Candidate Implementation Target

- `html/tests/Integration/EncryptionPhase1ExitCriteriaTest.php`

#### Proof/Validation Required

1. Updated integration tests pass under encrypted-only model.
2. No test in active suite requires plaintext financial field persistence.

#### Exit Criteria

- Q7 status upgraded to **Met**.

---

## Reporting Framework (for ongoing feedback rounds)

For each remediation cycle, append a short delta report under this section:

1. What changed (files and behaviors).
2. What was verified (tests/commands and results).
3. Which statuses changed (Q1..Q7).
4. Remaining blockers and next immediate step.

### Current Execution State

### Delta Report: Cycle 24 (2026-03-23)
Tags: cycle-24, workstream-B, P0, telemetry-governance, contract-matrix, stream-role-enforcement

1. What changed (files and behaviors)
  - `html/tests/Contract/TelemetryAccessGovernanceContractTest.php` (new)
    - Added consolidated stream-role matrix contract assertions for product and security telemetry streams.
    - Added repository contract proof that cross-stream joins are denied with `cross_stream_join_denied`.
    - Added contract proof that same-stream join still enforces stream authorization (`telemetry_access_denied` when role is insufficient).
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream B progress to include stream-role matrix governance contract coverage.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Contract/TelemetryAccessGovernanceContractTest.php`
    - `tests/Contract/TelemetryPolicyContractTest.php`
    - `tests/Unit/TelemetryRepositoryTest.php`
    - `tests/Unit/TelemetryControllerTest.php`
    - `tests/Integration/TelemetryControllerIntegrationTest.php`
    - `tests/Integration/TelemetryControllerPayloadIntegrationTest.php`
  - Result: `passed=20 failed=0`
  - Command: `cd <REPO_ROOT>/html && ./vendor/bin/phpstan analyse tests/Contract/TelemetryAccessGovernanceContractTest.php src/Domain/Telemetry/TelemetryRepository.php src/Domain/TelemetryPolicy.php --level=9`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1/Workstream B governance confidence increased: stream-role access invariants and join restrictions are now codified as contract tests.

4. Remaining blockers and next immediate step
  - Remaining blocker: endpoint-level proof currently covers write paths and selected summary paths, but broader telemetry consumers still need explicit repository-token migration verification.
  - Next immediate step: inventory remaining telemetry read consumers and migrate at least one additional consumer to repository-token boundary with endpoint-level deny-path assertion.

- Plan defined and prioritized.
- Cycle 1 through Cycle 5 remediation code changes have been applied and validated.
- Next recommended implementation start: caveat-closure hardening for **Q1/Q5** (service-boundary deny enforcement tests) and expanded saturation coverage for **Q6**.

### Delta Report: Cycle 3 (2026-03-23)

1. What changed (files and behaviors)
  - `html/src/Domain/RateLimiter.php`
    - Added `checkRecoveryEndpointLimit()` with explicit per-route windows and `SystemConfig`-driven quotas.
    - Added `checkWindowLimit()` returning deterministic metadata (`remaining`, `limit`, `window_seconds`, `reset_at`).
  - `html/src/Controllers/AccountRecoveryController.php`
    - Added route-entry enforcement via `enforceRecoveryRateLimit()`.
    - Applied enforcement to recovery routes (`start`, `resend`, `verify-email`, `proof-payload`, `prove-key`, `bootstrap`, passkey registration, `complete`, `cancel`).
  - `html/tests/Integration/AccountRecoveryControllerIntegrationTest.php`
    - Added saturation test proving 429 envelope for `start` when `account_recovery_max_starts_per_day` is exceeded.

2. What was verified (tests/commands and results)
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`
  - `runTests` on `html/tests/Integration/AccountRecoveryControllerIntegrationTest.php`
  - Result: `passed=4 failed=0`

3. Which statuses changed (Q1..Q7)
  - Q6: `Partially met` -> `Met with caveat`

4. Remaining blockers and next immediate step
  - Remaining blocker: broaden Q6 saturation matrix and add deeper server-side deny checkpoints for Q1/Q5 caveat closure.
  - Next immediate step: expand route-by-route recovery saturation tests and add service-boundary enforcement tests for correlation-deny controls.

### Delta Report: Cycle 5 (2026-03-23)

1. What changed (files and behaviors)
  - `html/src/Domain/TelemetryPolicy.php`
    - Added explicit stream separation, retention windows, and access-boundary mapping helpers.
  - `docs/TELEMETRY_RETENTION_ACCESS_POLICY.md`
    - Added formal telemetry retention/access boundary policy artifact.
  - `html/src/Controllers/TelemetryController.php`
    - Wired telemetry stream metadata (`stream`, `retention_days`, `access_boundary`) from `TelemetryPolicy` into structured logs.
  - `html/tests/Contract/TelemetryPolicyContractTest.php`
    - Added contract tests validating retention values and stream access separation.

2. What was verified (tests/commands and results)
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`
  - `runTests` on:
    - `html/tests/Contract/GuardianMetadataCorrelationPolicyContractTest.php`
    - `html/tests/Contract/TelemetryPolicyContractTest.php`
    - `html/tests/Integration/EncryptionPhase1ExitCriteriaTest.php`
  - Result: `passed=4 failed=0`

3. Which statuses changed (Q1..Q7)
  - Q5: `Partially met` -> `Met with caveat`

4. Remaining blockers and next immediate step
  - Remaining blocker: Q1/Q5 caveat closure via deeper service-boundary enforcement tests; Q6 saturation breadth.
  - Next immediate step: add enforcement tests for deny-by-default correlation contexts and expand recovery route saturation cases beyond `start`.

### Delta Report: Cycle 4 (2026-03-23)

1. What changed (files and behaviors)
  - `html/js/guardian.js`
    - Added explicit metadata correlation policy surface (`getMetadataCorrelationPolicy`, `canCorrelateMetadata`) with deny-by-default allowlist model.
  - `docs/GUARDIAN_METADATA_CORRELATION_POLICY.md`
    - Added formal policy artifact with allowed contexts, prohibited pairs, and required controls.
  - `html/tests/Integration/EncryptionPhase1ExitCriteriaTest.php`
    - Replaced stale dual-write assertions with encrypted-only contract assertions.
    - Renamed class to `EncryptionExitCriteriaTest` to reflect current model.
  - `html/tests/Contract/GuardianMetadataCorrelationPolicyContractTest.php`
    - Added contract checks ensuring Guardian policy surface and policy doc invariants exist.

2. What was verified (tests/commands and results)
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`
  - `runTests` on:
    - `html/tests/Integration/EncryptionPhase1ExitCriteriaTest.php`
    - `html/tests/Contract/GuardianMetadataCorrelationPolicyContractTest.php`
  - Result: `passed=2 failed=0`

3. Which statuses changed (Q1..Q7)
  - Q1: `Partially met` -> `Met with caveat`
  - Q7: `Partially met` -> `Met with caveat`

4. Remaining blockers and next immediate step
  - Remaining blocker: telemetry retention/access boundary policy completion (Q5).
  - Next immediate step: implement Q5 retention/access policy artifact and add verification tests for cross-stream access boundaries.

### Delta Report: Cycle 2 (2026-03-23)

1. What changed (files and behaviors)
  - `html/src/Controllers/TelemetryController.php`
    - Added `telemetrySubjectToken()`, `networkClassToken()`, and `scrubFields()` helpers.
    - Removed raw `user_uuid`, raw IP, and raw user-agent logging.
    - Replaced with pseudonymous rotating hourly tokens and user-agent hash.
  - `html/js/phantomwing/index.php`
    - Added queued telemetry delivery model with bounded queue and minimum batch threshold.
    - Added randomized flush jitter (`baseDelayMs + random(jitterMs)`) and max-hold behavior.
    - Added coarse minute bucket aggregation and `visibilitychange`/`beforeunload` flush paths.

2. What was verified (tests/commands and results)
  - File diagnostics:
    - `html/src/Controllers/TelemetryController.php` -> No errors found
    - `html/js/phantomwing/index.php` -> No errors found
  - Command: `cd <REPO_ROOT>/html && php -l src/Controllers/TelemetryController.php`
  - Result: `No syntax errors detected in src/Controllers/TelemetryController.php`

3. Which statuses changed (Q1..Q7)
  - Q5: `Not met` -> `Partially met`

4. Remaining blockers and next immediate step
  - Remaining blocker: explicit recovery endpoint per-route rate-limit windows and 429 proof (Q6), plus formal telemetry retention/access policy artifact (Q5 completion).
  - Next immediate step: implement Workstream D route-level recovery rate-limit enforcement and evidence.

### Delta Report: Cycle 1 (2026-03-23)

1. What changed (files and behaviors)
  - `html/src/Controllers/AdminPageController.php`
    - Added nullability narrowing for `preg_replace` output in `maskPhoneNumber()` before `strlen()` and `substr()` use.
  - `html/js/earnings/index.php`
    - Removed server-rendered plaintext profile bootstrap payload.
    - Replaced `window.PAYCAL_USER_PROFILE_ENCRYPTED` initialization with empty placeholder object.

2. What was verified (tests/commands and results)
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - Q2: `Partially met` -> `Met`
  - Q3: `Not met` -> `Partially met` (bootstrap plaintext removal complete in this path)

4. Remaining blockers and next immediate step
  - Remaining blocker: telemetry correlation controls (Q5) and explicit recovery endpoint windows (Q6).
  - Next immediate step: implement Workstream C telemetry pseudonymization + batching/jitter policy and evidence.

### Delta Report: Cycle 6 (2026-03-23)

1. What changed (files and behaviors)
  - `html/js/earnings/index.php`
    - Removed remaining runtime dependency on `window.PAYCAL_USER_PROFILE` from `runScopedExport()`.
    - Export reports now use non-PII-safe defaults (`email/phone/address=''`, `ipAddress='unknown'`) and stable non-sensitive employee fallback (`window.PAYCAL_USER_UUID`).
    - Removed legacy `window.PAYCAL_USER_PROFILE = null` bootstrap global.
  - `html/js/calendar/crypto-worker.js`
    - Removed profile-retention behavior in worker (`self.cryptoState.profile` assignments).
    - Removed `decryptProfile` and `getProfile` message actions to prevent returning full profile payloads across worker boundary.

2. What was verified (tests/commands and results)
  - Search verification: no `window.PAYCAL_USER_PROFILE` references remain in active runtime JS paths.
  - `get_errors` diagnostics on edited files:
    - `html/js/earnings/index.php` -> no errors
    - `html/js/calendar/crypto-worker.js` -> no errors

3. Which statuses changed (Q1..Q7)
  - Q3: `Partially met` -> `Met with caveat`

4. Remaining blockers and next immediate step
  - Remaining blocker: caveat closure hardening for Q1/Q5 service-boundary deny enforcement and broader Q6 saturation matrix.
  - Next immediate step: add deny-enforcement integration tests for metadata-correlation and telemetry stream boundary checks.

### Delta Report: Cycle 7 (2026-03-23)

1. What changed (files and behaviors)
  - `html/tests/Integration/TelemetryControllerPayloadIntegrationTest.php`
    - Added runtime enforcement test `testRecordEventScrubsSensitiveFieldsAndAddsStreamBoundaries()`.
    - Test now captures emitted telemetry log envelope and asserts:
      - blocked PII fields are removed from `fields`,
      - raw `user_uuid`/IP are absent from top-level log fields,
      - stream boundary metadata (`stream`, `retention_days`, `access_boundary`) is present and correct,
      - pseudonymous network/subject tokens are emitted.
  - `html/tests/Integration/AccountRecoveryControllerIntegrationTest.php`
    - Added route saturation test `testVerifyEmailRouteReturns429WithQuotaMetadataWhenLimitExceeded()`.
    - Confirms deterministic 429 envelope for `verify-email` route (`route`, `quota`, `window_seconds`).

2. What was verified (tests/commands and results)
  - `runTests` on:
    - `html/tests/Integration/TelemetryControllerPayloadIntegrationTest.php`
    - `html/tests/Integration/AccountRecoveryControllerIntegrationTest.php`
  - Result: `passed=8 failed=0`
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q5 and Q6 caveats were reduced via stronger integration-level enforcement evidence.

4. Remaining blockers and next immediate step
  - Remaining blocker: Q1 server-side metadata-correlation deny enforcement is still policy-surface heavy and not yet proven at controller/service read boundaries.
  - Next immediate step: introduce server policy gate tests that assert forbidden metadata+financial correlation requests are denied before payload composition.

### Delta Report: Cycle 8 (2026-03-23)

1. What changed (files and behaviors)
  - `html/src/Domain/Security/MetadataCorrelationPolicy.php`
    - Added server-side deny-by-default correlation policy with explicit allowlist contexts.
  - `html/src/Controllers/EarningsController.php`
    - Added correlation context gate for earnings read endpoints (`getVerificationYear`, `getGross`, `getDaily`).
    - Added explicit 403 response envelope when context is denied (`reason=metadata_correlation_denied`).
  - `html/tests/Unit/MetadataCorrelationPolicyTest.php`
    - Added unit tests for deny-by-default and allowlisted context behavior.
  - `html/tests/Integration/EarningsControllerIntegrationTest.php`
    - Added `testGetDailyRejectsUnknownCorrelationContext()` to prove deny enforcement at controller boundary.

2. What was verified (tests/commands and results)
  - Command:
    - `cd <REPO_ROOT>/html && ./vendor/bin/phpunit -c phpunit.xml --no-progress tests/Unit/MetadataCorrelationPolicyTest.php tests/Integration/EarningsControllerIntegrationTest.php tests/Integration/TelemetryControllerPayloadIntegrationTest.php tests/Integration/AccountRecoveryControllerIntegrationTest.php`
  - Result: `OK (15 tests, 79 assertions)`
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 caveat was reduced by adding first server-boundary deny gate with integration proof.

4. Remaining blockers and next immediate step
  - Remaining blocker: extend server-side metadata correlation deny gates beyond earnings endpoints.
  - Next immediate step: apply policy checks to additional controllers/services where metadata and decrypted financial payloads can be joined.

### Delta Report: Cycle 9 (2026-03-23)

1. What changed (files and behaviors)
  - `html/src/Controllers/SitesController.php`
    - Added `correlationContext()` and `canCorrelateSiteMetadataWithFinancialPayload()` helpers.
    - Added denial gate at start of `getSiteEarnings()`: returns HTTP 403 with `reason=metadata_correlation_denied` for any context not in the `MetadataCorrelationPolicy` allowlist.
    - Import of `MetadataCorrelationPolicy` added.
  - `html/tests/Integration/SitesControllerIntegrationTest.php`
    - Added `runSiteEarningsSubprocess()` subprocess helper.
    - Added `testGetSiteEarningsRejectsUnknownCorrelationContext()` to prove enforcement at the sites earnings boundary.
  - `html/tests/Integration/AccountRecoveryControllerIntegrationTest.php`
    - Added `testResendRouteReturns429WithQuotaMetadataWhenLimitExceeded()` — exercises `resend` route saturation (config key: `account_recovery_max_resends_per_hour`, window=3600).
    - Added `testProofPayloadRouteReturns429WithQuotaMetadataWhenLimitExceeded()` — exercises `proof-payload` route saturation (config key: `account_recovery_max_verify_attempts`, window=3600).
    - Added `testCancelRouteReturns429WithQuotaMetadataWhenLimitExceeded()` — exercises `cancel` route saturation (config key: `account_recovery_max_verify_attempts`, window=3600).
    - Added `testProveKeyRouteReturns429WithQuotaMetadataWhenLimitExceeded()` — exercises `prove-key` route saturation.
    - Added `testBootstrapRouteReturns429WithQuotaMetadataWhenLimitExceeded()` — exercises `bootstrap` route saturation.
    - Added `testRegisterPasskeyStartRouteReturns429WithQuotaMetadataWhenLimitExceeded()` — exercises `register-passkey-start` route saturation.
    - Added `testRegisterPasskeyFinishRouteReturns429WithQuotaMetadataWhenLimitExceeded()` — exercises `register-passkey-finish` route saturation.
    - Added `testCompleteRouteReturns429WithQuotaMetadataWhenLimitExceeded()` — exercises `complete` route saturation.

2. What was verified (tests/commands and results)
  - Test run: `tests/Unit/MetadataCorrelationPolicyTest.php tests/Integration/EarningsControllerIntegrationTest.php tests/Integration/SitesControllerIntegrationTest.php tests/Integration/AccountRecoveryControllerIntegrationTest.php`
  - Result: `passed=20 failed=0`
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - Q1: caveat further reduced — `sites/earnings` endpoint now proves second independent controller with deny gate; both server-side isolation surfaces (`EarningsController` + `SitesController`) are gated and tested.
  - Q6: status upgraded from `Met with caveat` to `Met`; saturation coverage now spans all 10 recovery routes with explicit 429 + quota + window_seconds assertions on the second call.

4. Remaining blockers and next immediate step
  - Q1/Q5: remaining controllers have no explicit correlation gate; further sweep of `CalendarController`, `AdminController`, and export pathways recommended.
  - Q6 blocker closed for controller-route coverage; no additional saturation expansion is required for this question.
  - Next immediate step: verify `MetadataCorrelationPolicy` is invoked or exclude remaining controllers from risk scope by tracing their data join patterns.

### Delta Report: Cycle 10 (2026-03-23)

1. What changed (files and behaviors)
  - `html/src/Controllers/CalendarController.php`
    - Added `correlationContext()` and `canCorrelateSiteMetadataWithFinancialPayload()` helpers using `MetadataCorrelationPolicy`.
    - Added explicit HTTP 403 deny gates to `getCalendar()` and `getMonthData()` with `reason=metadata_correlation_denied`.
    - Added a safe fallback in `buildWeekPayload()` so mutation responses do not emit correlated week payload data when the context is denied.
  - `html/src/Domain/Security/MetadataCorrelationPolicy.php`
    - Added allowlisted context `self-service-calendar` for reviewed calendar self-service joins.
  - `html/tests/Integration/CalendarControllerIntegrationTest.php`
    - Added subprocess-based integration proofs for `getCalendar()` and `getMonthData()` denying unknown correlation contexts.
  - `html/tests/Unit/MetadataCorrelationPolicyTest.php`
    - Added unit coverage proving `self-service-calendar` is explicitly allowlisted.

2. What was verified (tests/commands and results)
  - Test run: `tests/Integration/CalendarControllerIntegrationTest.php tests/Integration/EarningsControllerIntegrationTest.php tests/Integration/SitesControllerIntegrationTest.php tests/Integration/AccountRecoveryControllerIntegrationTest.php tests/Unit/MetadataCorrelationPolicyTest.php`
  - Result: `passed=17 failed=0`
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 caveat was reduced again: server-side deny enforcement is now proven at three independent controller boundaries (`EarningsController`, `SitesController`, `CalendarController`).

4. Remaining blockers and next immediate step
  - Remaining blocker: determine whether any admin/export/controller pathways still join site metadata with financial payload without passing through a reviewed correlation context.
  - Next immediate step: trace `AdminController` and export-related endpoints; either add identical deny gates or explicitly document why they are out of scope for Q1/Q5.

### Delta Report: Cycle 11 (2026-03-23)

1. What changed (files and behaviors)
  - `html/src/Domain/Security/MetadataCorrelationPolicy.php`
    - Expanded reviewed security contexts (`security-incident`, `fraud-investigation`, `regulatory-legal-hold`) to explicitly allow `user_profile:session_metadata` and `user_profile:credential_metadata` correlation pairs in addition to existing `site_metadata:financial_payload`.
  - `html/src/Controllers/AdminPageController.php`
    - Added `canCorrelateAdminAccountSecurityMetadata()` policy gate for admin account/session/credential correlation.
    - Updated `dashboard()` to apply safe exclusion behavior: session snapshot and credential-enrichment joins are only performed when policy allows them.
    - Default behavior on policy deny is data minimization (no session/credential enrichment), avoiding broad join expansion.
  - `html/tests/Unit/MetadataCorrelationPolicyTest.php`
    - Added unit coverage for security-incident allow cases (`user_profile` with `session_metadata` and `credential_metadata`).
    - Added deny proof that self-service earnings context cannot correlate `user_profile:session_metadata`.

2. What was verified (tests/commands and results)
  - Test run: `cd <REPO_ROOT>/html && ./vendor/bin/phpunit -c phpunit.xml --no-progress tests/Unit/MetadataCorrelationPolicyTest.php`
  - Result: `OK (7 tests, 7 assertions)`
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`
  - Export-path trace:
    - `html/js/earnings/index.php` `runScopedExport()` fetches `daily/year/{year}` via `fetchDailyYearData()`.
    - `daily/year/{year}` is served by `EarningsController::getDaily()`, which already enforces `MetadataCorrelationPolicy` deny-by-default gating.
    - Conclusion: current earnings export flow inherits existing self-service controller correlation gates; no additional export-specific gate was required for this path.

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 caveat narrowed for admin surfaces: reviewed policy artifacts now explicitly cover admin account/session/credential join classes with deny-safe exclusion behavior at render time.

4. Remaining blockers and next immediate step
  - Remaining blocker: add direct integration coverage for admin dashboard exclusion behavior when policy denies account/session/credential correlation.
  - Next immediate step: introduce a focused controller-level test seam (or integration harness) to assert `dashboard()` omits session/credential enrichment when policy context is not allowlisted.

### Delta Report: Cycle 12 (2026-03-23)
Tags: cycle-12, workstream-A, P0, roadmap-alignment, domain-model

1. What changed (files and behaviors)
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Added long-lived phase-closure roadmap baseline with four workstreams (Correlation Broker, Telemetry Query Governance, Admin Policy Closure, Runtime Lifecycle Hardening) and cycle evidence requirements.
  - `docs/SECURITY_PRIVACY_HARDENING_PLAN.md`
    - Added explicit alignment section to link tactical hardening execution to the phase-closure architecture roadmap.
  - `html/src/Domain/Security/CorrelationContext.php` (new)
    - Added normalized value object for correlation requests (`context_name`, `user_uuid`, `privilege_level`, `purpose_code`, `correlation_pairs_requested`, `audit_reason_code`).
  - `html/src/Domain/Security/CorrelationDecision.php` (new)
    - Added decision envelope model for broker/policy outcomes with structured `allowed/reason/denied_pairs` payload shape.
  - `html/tests/Unit/CorrelationContextTest.php` (new)
    - Added unit coverage for pair normalization, privileged-context detection, and structured decision payload shape.

2. What was verified (tests/commands and results)
  - Test run: `cd <REPO_ROOT>/html && ./vendor/bin/phpunit -c phpunit.xml --no-progress tests/Unit/MetadataCorrelationPolicyTest.php tests/Unit/CorrelationContextTest.php`
  - Result: `OK (11 tests, 11 assertions)`
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 closure groundwork advanced: policy model evolution has moved from ad hoc controller checks toward explicit, reusable security domain objects required by broker centralization.

4. Remaining blockers and next immediate step
  - Remaining blocker: `CorrelationBroker` service implementation and controller migration are not yet complete.
  - Next immediate step: implement `CorrelationBroker::compose(...)` with policy evaluation + denial envelope and migrate one existing controller path as the first enforcement slice.

### Delta Report: Cycle 13 (2026-03-23)
Tags: cycle-13, workstream-A, P0, broker-scaffold, unit-proof

1. What changed (files and behaviors)
  - `html/src/Domain/Security/CorrelationBroker.php` (new)
    - Added centralized correlation evaluation entrypoint and structured `compose(...)` envelope (`success`/`denied`) backed by `MetadataCorrelationPolicy`.
  - `html/tests/Unit/CorrelationBrokerTest.php` (new)
    - Added unit proof for deny behavior on unauthorized pair/context and success behavior for allowlisted pair/context.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream A status to show A2 broker scaffold in progress.

2. What was verified (tests/commands and results)
  - Test run: `cd <REPO_ROOT>/html && ./vendor/bin/phpunit -c phpunit.xml --no-progress tests/Unit/MetadataCorrelationPolicyTest.php tests/Unit/CorrelationContextTest.php tests/Unit/CorrelationBrokerTest.php`
  - Result: `OK (14 tests, 20 assertions)`
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 architecture maturity increased: a centralized broker path now exists for controlled composition, reducing future controller-side join drift risk.

4. Remaining blockers and next immediate step
  - Remaining blocker: controllers are not yet migrated to call `CorrelationBroker::compose(...)` as the sole join path.
  - Next immediate step: migrate one concrete controller boundary (recommended: `EarningsController` correlation gate path) to broker-first composition and add integration proof.

### Delta Report: Cycle 14 (2026-03-23)
Tags: cycle-14, workstream-A, P0, controller-migration, broker-evaluation

1. What changed (files and behaviors)
  - `html/src/Controllers/AdminPageController.php`
    - Migrated admin enrichment gate from direct `MetadataCorrelationPolicy::allows(...)` checks to broker-based evaluation via `CorrelationContext` + `CorrelationBroker::evaluate(...)`.
    - Admin enrichment behavior remains deny-safe (session/credential joins are omitted when denied).

2. What was verified (tests/commands and results)
  - Command: `cd <REPO_ROOT>/html && php -l src/Controllers/AdminPageController.php && php -l src/Domain/Security/CorrelationBroker.php`
  - Result: no syntax errors.
  - Test run: `cd <REPO_ROOT>/html && ./vendor/bin/phpunit -c phpunit.xml --no-progress tests/Unit/MetadataCorrelationPolicyTest.php tests/Unit/CorrelationContextTest.php tests/Unit/CorrelationBrokerTest.php`
  - Result: `OK (14 tests, 20 assertions)`
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 architecture migration advanced: one production controller boundary now relies on the broker model for correlation decisions.

4. Remaining blockers and next immediate step
  - Remaining blocker: broad controller migration to `CorrelationBroker` is incomplete and integration proof for denied admin dashboard enrichment is still pending.
  - Next immediate step: add an integration harness for admin dashboard deny-path proof, then migrate `EarningsController` correlation checks to broker evaluation/composition.

### Delta Report: Cycle 15 (2026-03-23)
Tags: cycle-15, workstream-C, P1, admin-integration-proof, deny-path

1. What changed (files and behaviors)
  - `html/src/Controllers/AdminPageController.php`
    - Added test seam `adminCorrelationContext()` (default remains `security-incident`) so policy context can be explicitly exercised in integration tests.
    - Admin enrichment gate now consumes that context through broker evaluation, preserving deny-safe omission behavior.
  - `html/tests/Integration/AdminPageControllerIntegrationTest.php` (new)
    - Added integration proof that allowed context includes session/credential enrichment.
    - Added integration proof that denied context omits session hash and credential enrichment attributes.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream C progress to mark C1 integration proof started.

2. What was verified (tests/commands and results)
  - Test run: `cd <REPO_ROOT>/html && ./vendor/bin/phpunit -c phpunit.xml --no-progress tests/Integration/AdminPageControllerIntegrationTest.php tests/Unit/CorrelationBrokerTest.php tests/Unit/CorrelationContextTest.php tests/Unit/MetadataCorrelationPolicyTest.php`
  - Result: `OK (16 tests, 28 assertions)`
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1/C1 confidence increased: admin deny-path omission behavior is now integration-verified, not only inferred from code inspection.

4. Remaining blockers and next immediate step
  - Remaining blocker: full controller adoption of broker composition is not complete (self-service controllers still use direct policy checks).
  - Next immediate step: migrate `EarningsController` to broker evaluation/composition path and add/adjust integration proofs accordingly.

### Delta Report: Cycle 16 (2026-03-23)
Tags: cycle-16, workstream-A, workstream-C, P0, broker-migration, integration-tags

1. What changed (files and behaviors)
  - `html/src/Controllers/EarningsController.php`
    - Migrated correlation gate to broker-first evaluation using `CorrelationContext` + `CorrelationBroker::evaluate(...)`.
    - Removed direct controller dependency on `MetadataCorrelationPolicy`.
  - `html/tests/Integration/AdminPageControllerIntegrationTest.php`
    - Added PHPUnit grouping tags (`integration`, `security`) for targeted suite execution and evidence traceability.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream A progress to reflect controller-level broker adoption in admin and earnings boundaries.

2. What was verified (tests/commands and results)
  - Test run: `cd <REPO_ROOT>/html && ./vendor/bin/phpunit -c phpunit.xml --no-progress tests/Integration/EarningsControllerIntegrationTest.php tests/Integration/AdminPageControllerIntegrationTest.php tests/Unit/CorrelationBrokerTest.php tests/Unit/CorrelationContextTest.php tests/Unit/MetadataCorrelationPolicyTest.php`
  - Result: `OK (20 tests, 46 assertions)`
  - Command: `cd <REPO_ROOT>/html && composer run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 architecture closure moved forward: one major self-service controller path now uses broker-based correlation gating.

4. Remaining blockers and next immediate step
  - Remaining blocker: `SitesController` and `CalendarController` still use direct policy checks and are not yet migrated to broker-first evaluation/composition.
  - Next immediate step: migrate `SitesController` correlation checks to broker evaluation, then re-run targeted integration and strict static analysis.

### Delta Report: Cycle 17 (2026-03-23)
Tags: cycle-17, workstream-A, P0, sites-migration, broker-evaluation

1. What changed (files and behaviors)
  - `html/src/Controllers/SitesController.php`
    - Migrated sites correlation gate to broker-first evaluation using `CorrelationContext` + `CorrelationBroker::evaluate(...)`.
    - Removed direct controller dependency on `MetadataCorrelationPolicy`.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/SitesControllerIntegrationTest.php`
    - `tests/Integration/EarningsControllerIntegrationTest.php`
    - `tests/Integration/AdminPageControllerIntegrationTest.php`
    - `tests/Unit/CorrelationBrokerTest.php`
    - `tests/Unit/CorrelationContextTest.php`
    - `tests/Unit/MetadataCorrelationPolicyTest.php`
  - Result: `passed=20 failed=0`
  - Command: `composer -d <REPO_ROOT>/html run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 architecture closure advanced again: Sites boundary now evaluates correlation through broker model.

4. Remaining blockers and next immediate step
  - Remaining blocker: `CalendarController` still uses direct policy checks and has not yet been migrated to broker-first evaluation/composition.
  - Next immediate step: migrate calendar correlation checks to broker evaluation and rerun targeted integration tests.

### Delta Report: Cycle 18 (2026-03-23)
Tags: cycle-18, workstream-A, P0, calendar-migration, static-enforcement

1. What changed (files and behaviors)
  - `html/src/Controllers/CalendarController.php`
    - Migrated calendar correlation gate to broker-first evaluation using `CorrelationContext` + `CorrelationBroker::evaluate(...)`.
    - Removed direct controller dependency on `MetadataCorrelationPolicy`.
  - `scripts/check-correlation-broker-enforcement.sh` (new)
    - Added static grep enforcement rule that fails when controllers directly reference `MetadataCorrelationPolicy`.
    - Enforces controller migration discipline toward broker-based correlation decisions.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream A progress to include full migration of current self-service/admin controller boundaries and static enforcement rule.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/CalendarControllerIntegrationTest.php`
    - `tests/Integration/SitesControllerIntegrationTest.php`
    - `tests/Integration/EarningsControllerIntegrationTest.php`
    - `tests/Integration/AdminPageControllerIntegrationTest.php`
    - `tests/Unit/CorrelationBrokerTest.php`
    - `tests/Unit/CorrelationContextTest.php`
    - `tests/Unit/MetadataCorrelationPolicyTest.php`
  - Result: `passed=16 failed=0`
  - Command: `composer -d <REPO_ROOT>/html run phpstan:strict`
  - Result: `[OK] No errors`
  - Command: `<REPO_ROOT>/scripts/check-correlation-broker-enforcement.sh`
  - Result: `[broker-enforcement] OK: controllers rely on broker-based correlation enforcement.`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 architectural closure materially improved: key controller boundaries now converge on broker evaluation with an automated guard against direct-policy regressions.

4. Remaining blockers and next immediate step
  - Remaining blocker: controllers currently broker-evaluate but do not yet consistently route join composition through `CorrelationBroker::compose(...)` return envelopes.
  - Next immediate step: migrate one endpoint payload composition path (recommended `EarningsController::getDaily`) to broker compose-envelope output and add integration assertion for structured denied envelope shape.

### Delta Report: Cycle 19 (2026-03-23)
Tags: cycle-19, workstream-A, P0, compose-envelope, denied-structure

1. What changed (files and behaviors)
  - `html/src/Controllers/EarningsController.php`
    - Added `siteFinancialCorrelationComposeProbe()` using `CorrelationBroker::compose(...)`.
    - Updated `getVerificationYear()`, `getGross()`, and `getDaily()` to deny using structured compose decision envelope.
    - Denied responses now include `decision` payload with normalized deny details.
  - `html/tests/Integration/EarningsControllerIntegrationTest.php`
    - Extended denied-context assertion to verify structured decision envelope (`decision.reason`, `decision.denied_pairs`).
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream A progress to include first production compose-envelope adoption.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/EarningsControllerIntegrationTest.php`
    - `tests/Integration/SitesControllerIntegrationTest.php`
    - `tests/Integration/CalendarControllerIntegrationTest.php`
    - `tests/Integration/AdminPageControllerIntegrationTest.php`
    - `tests/Unit/CorrelationBrokerTest.php`
    - `tests/Unit/CorrelationContextTest.php`
    - `tests/Unit/MetadataCorrelationPolicyTest.php`
  - Result: `passed=16 failed=0`
  - Additional focused test run (tool):
    - `tests/Integration/EarningsControllerIntegrationTest.php`
    - `tests/Unit/CorrelationBrokerTest.php`
    - `tests/Unit/CorrelationContextTest.php`
    - `tests/Unit/MetadataCorrelationPolicyTest.php`
  - Result: `passed=18 failed=0`
  - Command: `composer -d <REPO_ROOT>/html run phpstan:strict`
  - Result: `[OK] No errors`
  - Command: `<REPO_ROOT>/scripts/check-correlation-broker-enforcement.sh`
  - Result: `[broker-enforcement] OK: controllers rely on broker-based correlation enforcement.`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 architecture maturity advanced: at least one production endpoint family now emits broker compose-based structured deny envelopes.

4. Remaining blockers and next immediate step
  - Remaining blocker: `SitesController` and `CalendarController` currently use broker-evaluate gating, but not compose-envelope response shaping.
  - Next immediate step: adopt compose-envelope deny structure in one additional controller path (`SitesController::getSiteEarnings`) and extend integration assertions.

### Delta Report: Cycle 20 (2026-03-23)
Tags: cycle-20, workstream-A, P0, sites-compose-envelope, denied-structure

1. What changed (files and behaviors)
  - `html/src/Controllers/SitesController.php`
    - Added `siteFinancialCorrelationComposeProbe()` with `CorrelationBroker::compose(...)`.
    - Updated `getSiteEarnings()` deny path to include structured `decision` envelope.
  - `html/tests/Integration/SitesControllerIntegrationTest.php`
    - Extended denied-context assertions to validate `decision.reason` and `decision.denied_pairs`.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/SitesControllerIntegrationTest.php`
    - `tests/Integration/EarningsControllerIntegrationTest.php`
    - `tests/Unit/CorrelationBrokerTest.php`
    - `tests/Unit/CorrelationContextTest.php`
    - `tests/Unit/MetadataCorrelationPolicyTest.php`
  - Result: `passed=18 failed=0`
  - Command: `composer -d <REPO_ROOT>/html run phpstan:strict`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 closure progressed: second controller boundary now emits compose-based denied decision structure.

4. Remaining blockers and next immediate step
  - Remaining blocker: `CalendarController` still needed compose-envelope deny shaping and assertion coverage.
  - Next immediate step: migrate `CalendarController` deny paths to compose-envelope and extend integration assertions.

### Delta Report: Cycle 21 (2026-03-23)
Tags: cycle-21, workstream-A, P0, calendar-compose-envelope, denied-structure

1. What changed (files and behaviors)
  - `html/src/Controllers/CalendarController.php`
    - Added `siteFinancialCorrelationComposeProbe()` with `CorrelationBroker::compose(...)`.
    - Updated `getCalendar()` and `getMonthData()` deny paths to include structured `decision` envelope.
  - `html/tests/Integration/CalendarControllerIntegrationTest.php`
    - Extended denied-context assertions for both `getCalendar()` and `getMonthData()` to validate `decision.reason` and `decision.denied_pairs`.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated progress to reflect compose-envelope adoption across Earnings, Sites, and Calendar.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/CalendarControllerIntegrationTest.php`
    - `tests/Integration/SitesControllerIntegrationTest.php`
    - `tests/Integration/EarningsControllerIntegrationTest.php`
    - `tests/Integration/AdminPageControllerIntegrationTest.php`
    - `tests/Unit/CorrelationBrokerTest.php`
    - `tests/Unit/CorrelationContextTest.php`
    - `tests/Unit/MetadataCorrelationPolicyTest.php`
  - Result: `passed=16 failed=0`
  - Command: `composer -d <REPO_ROOT>/html run phpstan:strict`
  - Result: `[OK] No errors`
  - Command: `<REPO_ROOT>/scripts/check-correlation-broker-enforcement.sh`
  - Result: `[broker-enforcement] OK: controllers rely on broker-based correlation enforcement.`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 architecture closure strengthened: all primary self-service controller deny paths now emit broker compose-based decision envelopes with integration proof.

4. Remaining blockers and next immediate step
  - Remaining blocker: admin dashboard uses broker evaluate-based gating and omission behavior (integration-proven), but not compose-envelope response semantics due page-render flow.
  - Next immediate step: begin Workstream B by introducing `TelemetryRepository` abstraction and access-token model skeleton to move query governance to datastore boundary.

### Delta Report: Cycle 22 (2026-03-23)
Tags: cycle-22, workstream-B, P0, telemetry-governance, stream-isolation

1. What changed (files and behaviors)
  - `html/src/Domain/Telemetry/TelemetryAccessToken.php` (new)
    - Introduced stream/role/retention/aggregation token model for telemetry access decisions.
    - Added `allowsStream(...)` helper backed by `TelemetryPolicy::canAccess(...)`.
  - `html/src/Domain/Telemetry/TelemetryRepository.php` (new)
    - Introduced centralized telemetry boundary methods:
      - `authorize(...)` for stream access checks.
      - `guardCrossStreamJoin(...)` that denies mixed-stream joins via `cross_stream_join_denied`.
      - `incrementEventCounter(...)` for controlled write path.
  - `html/src/Controllers/TelemetryController.php`
    - Replaced direct counter increment with repository boundary call.
    - Added explicit stream authorization via `TelemetryAccessToken` + `TelemetryRepository::authorize(...)` before write.
    - External response contract and event logging behavior remain unchanged for allowed product stream writes.
  - `html/src/Domain/MetricsService.php`
    - Migrated `getTelemetryEvents()` query path to `TelemetryRepository::fetchWhitelistedEventCounts(...)` with tokenized stream authorization.
    - Removed direct telemetry event key reads from metrics query path.
  - `html/tests/Unit/TelemetryRepositoryTest.php` (new)
    - Added unit proof for stream allow/deny and cross-stream join guard behavior.
    - Added unit proof for tokenized telemetry query deny/allow behavior.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream B progress to reflect B1/B2/B3 scaffold start.

2. What was verified (tests/commands and results)
  - Focused telemetry test run (tool):
    - `tests/Unit/TelemetryRepositoryTest.php`
    - `tests/Unit/TelemetryControllerTest.php`
    - `tests/Contract/TelemetryPolicyContractTest.php`
    - `tests/Integration/TelemetryControllerIntegrationTest.php`
    - `tests/Integration/TelemetryControllerPayloadIntegrationTest.php`
  - Result: `passed=14 failed=0`
  - Command: `php -l html/src/Domain/Telemetry/TelemetryAccessToken.php && php -l html/src/Domain/Telemetry/TelemetryRepository.php && php -l html/src/Controllers/TelemetryController.php`
  - Result: `No syntax errors detected` (all files)
  - Command: `cd <REPO_ROOT>/html && ./vendor/bin/phpstan analyse src/Domain/Telemetry/TelemetryAccessToken.php src/Domain/Telemetry/TelemetryRepository.php src/Controllers/TelemetryController.php src/Domain/MetricsService.php tests/Unit/TelemetryRepositoryTest.php --level=9`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 architecture closure extended: query-layer telemetry boundary now has explicit stream access and join governance primitives.

4. Remaining blockers and next immediate step
  - Remaining blocker: one telemetry read path is now migrated, but additional telemetry analytics/query surfaces still read datastore directly.
  - Next immediate step: expand tokenized repository read governance into additional telemetry aggregation surfaces and add integration proof for denied cross-stream access at endpoint level.

### Delta Report: Cycle 23 (2026-03-23)
Tags: cycle-23, workstream-B, P0, telemetry-governance, encryption-summary, endpoint-deny-proof

1. What changed (files and behaviors)
  - `html/src/Domain/Telemetry/TelemetryRepository.php`
    - Added `fetchEncryptionClientCounters(...)` for security-stream telemetry reads behind tokenized authorization.
    - Enforces bounded telemetry type set and returns structured allow/deny envelope.
  - `html/src/Controllers/EncryptionController.php`
    - `getTelemetrySummary()` now routes reads through `TelemetryRepository::fetchEncryptionClientCounters(...)`.
    - Added `guardCrossStreamJoin(...)` check with explicit deny response for mixed stream requests.
  - `html/tests/Unit/TelemetryRepositoryTest.php`
    - Added unit proof for encryption telemetry counter read deny/allow behavior.
  - `html/tests/Integration/EncryptionControllerIntegrationTest.php`
    - Added endpoint-level integration proof that admin request with `join_stream=product` is denied with `cross_stream_join_denied`.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream B progress to include second migrated query path and endpoint deny proof.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Unit/TelemetryRepositoryTest.php`
    - `tests/Integration/EncryptionControllerIntegrationTest.php`
    - `tests/Unit/TelemetryControllerTest.php`
    - `tests/Contract/TelemetryPolicyContractTest.php`
    - `tests/Integration/TelemetryControllerIntegrationTest.php`
    - `tests/Integration/TelemetryControllerPayloadIntegrationTest.php`
  - Result: `passed=19 failed=0`
  - Command: `cd /private/var/www/paycal/dev/html && ./vendor/bin/phpstan analyse src/Domain/Telemetry/TelemetryRepository.php src/Controllers/TelemetryController.php src/Controllers/EncryptionController.php src/Domain/MetricsService.php tests/Unit/TelemetryRepositoryTest.php tests/Integration/EncryptionControllerIntegrationTest.php --level=9`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Q1 architecture closure and Workstream B maturity improved: two distinct telemetry read paths now enforce tokenized repository governance, with endpoint-level cross-stream deny proof.

4. Remaining blockers and next immediate step
  - Remaining blocker: additional telemetry consumers (outside `MetricsService` and `EncryptionController` summary) still may use direct telemetry datastore reads.
  - Next immediate step: inventory and migrate remaining telemetry read surfaces to repository-token boundary and add one consolidated integration contract for stream-role matrix enforcement.

### Delta Report: Cycle 25 (2026-03-23)
Tags: cycle-25, workstream-B, P0, telemetry-governance, endpoint-integration-proof

1. What changed (files and behaviors)
  - `html/tests/Integration/EncryptionControllerIntegrationTest.php`
    - Restored admin-authenticated endpoint deny proof for `getTelemetrySummary()` when `join_stream=product`.
    - Added explicit endpoint allow proof for `getTelemetrySummary()` when `join_stream=security`.
    - Preserved existing unauthenticated behavior checks for `getVersionInfo()` and `getConfig()`.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream B progress to reflect restored allow/deny endpoint integration coverage.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/EncryptionControllerIntegrationTest.php`
    - `tests/Contract/TelemetryAccessGovernanceContractTest.php`
    - `tests/Unit/TelemetryRepositoryTest.php`
    - `tests/Unit/TelemetryControllerTest.php`
    - `tests/Integration/TelemetryControllerIntegrationTest.php`
    - `tests/Integration/TelemetryControllerPayloadIntegrationTest.php`
  - Result: `passed=22 failed=0`
  - Command: `cd <REPO_ROOT>/html && ./vendor/bin/phpstan analyse tests/Integration/EncryptionControllerIntegrationTest.php tests/Contract/TelemetryAccessGovernanceContractTest.php src/Controllers/EncryptionController.php src/Domain/Telemetry/TelemetryRepository.php --level=9`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream B endpoint-level governance assurance improved: summary endpoint now has explicit positive and negative integration proof aligned with repository token guards.

4. Remaining blockers and next immediate step
  - Remaining blocker: telemetry query governance is implemented on core paths, but additional telemetry read consumers may still bypass repository-token boundaries.
  - Next immediate step: inventory telemetry read consumers and migrate one additional consumer to repository-token access checks with endpoint-level tests.

### Delta Report: Cycle 26 (2026-03-23)
Tags: cycle-26, workstream-B, P0, telemetry-governance, session-metrics, health-endpoint-proof

1. What changed (files and behaviors)
  - `html/src/Domain/Telemetry/TelemetryRepository.php`
    - Added `fetchSessionLifecycleMetrics(...)` to centralize read access for login/logout and duration-bucket telemetry counters behind stream-token authorization.
  - `html/src/Domain/MetricsService.php`
    - Migrated `getSessionMetrics()` telemetry reads to `TelemetryRepository::fetchSessionLifecycleMetrics(...)` with tokenized authorization.
    - Removed direct auth/session telemetry key reads from this read path.
  - `html/tests/Unit/TelemetryRepositoryTest.php`
    - Added unit deny/allow coverage for session lifecycle metrics query method.
  - `html/tests/Integration/HealthControllerIntegrationTest.php` (new)
    - Added admin-authenticated endpoint proof for `HealthController::getSessionHealth()` validating repository-backed session telemetry values.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream B progress to include migrated session metrics consumer and health endpoint proof.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/HealthControllerIntegrationTest.php`
    - `tests/Unit/TelemetryRepositoryTest.php`
    - `tests/Contract/TelemetryAccessGovernanceContractTest.php`
    - `tests/Integration/EncryptionControllerIntegrationTest.php`
    - `tests/Unit/TelemetryControllerTest.php`
    - `tests/Integration/TelemetryControllerIntegrationTest.php`
    - `tests/Integration/TelemetryControllerPayloadIntegrationTest.php`
  - Result: `passed=25 failed=0`
  - Command: `cd <REPO_ROOT>/html && ./vendor/bin/phpstan analyse src/Domain/Telemetry/TelemetryRepository.php src/Domain/MetricsService.php tests/Unit/TelemetryRepositoryTest.php tests/Integration/HealthControllerIntegrationTest.php --level=9`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream B telemetry query governance advanced: a third read consumer is now repository-token mediated with endpoint-level proof.

4. Remaining blockers and next immediate step
  - Remaining blocker: additional telemetry aggregation surfaces (for example scraper/contact telemetry reads) still rely on direct datastore paths.
  - Next immediate step: migrate one remaining telemetry aggregation consumer to repository-token checks and extend endpoint or contract assertions accordingly.

### Delta Report: Cycle 27 (2026-03-23)
Tags: cycle-27, workstream-B, P0, telemetry-governance, contact-metrics, health-snapshot-proof

1. What changed (files and behaviors)
  - `html/src/Domain/Telemetry/TelemetryRepository.php`
    - Added `fetchContactSupportMetrics(...)` for contact telemetry snapshot reads with tokenized stream authorization.
  - `html/src/Domain/MetricsService.php`
    - Migrated `getContactSupportMetrics()` to `TelemetryRepository::fetchContactSupportMetrics(...)`.
    - Removed direct contact telemetry read dependency from metrics service boundary.
  - `html/tests/Unit/TelemetryRepositoryTest.php`
    - Added deny/allow unit coverage for contact telemetry snapshot query method.
  - `html/tests/Integration/HealthControllerIntegrationTest.php`
    - Added integration assertion that admin `getHealthSnapshot()` includes expected `contact.total_submissions` sourced from repository-backed telemetry path.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream B progress for contact metrics migration and health snapshot proof.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/HealthControllerIntegrationTest.php`
    - `tests/Unit/TelemetryRepositoryTest.php`
    - `tests/Contract/TelemetryAccessGovernanceContractTest.php`
    - `tests/Integration/EncryptionControllerIntegrationTest.php`
    - `tests/Unit/TelemetryControllerTest.php`
    - `tests/Integration/TelemetryControllerIntegrationTest.php`
    - `tests/Integration/TelemetryControllerPayloadIntegrationTest.php`
  - Result: `passed=28 failed=0`
  - Command: `cd <REPO_ROOT>/html && ./vendor/bin/phpstan analyse src/Domain/Telemetry/TelemetryRepository.php src/Domain/MetricsService.php tests/Unit/TelemetryRepositoryTest.php tests/Integration/HealthControllerIntegrationTest.php --level=9`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream B telemetry governance advanced: contact metrics consumer now enforces repository-token access and has endpoint-level proof in health snapshot output.

4. Remaining blockers and next immediate step
  - Remaining blocker: scraper-defense telemetry aggregation path remains direct and not yet repository-token mediated.
  - Next immediate step: migrate scraper-defense aggregation reads behind telemetry repository checks and add focused endpoint-level or contract assertions.

### Delta Report: Cycle 28 (2026-03-23)
Tags: cycle-28, workstream-B, P0, telemetry-governance, scraper-metrics, health-snapshot-proof

1. What changed (files and behaviors)
  - `html/src/Domain/Telemetry/TelemetryRepository.php`
    - Added `fetchScraperDefenseMetrics(...)` with tokenized stream authorization and repository-backed aggregation for scraper totals, bucket counters, and top netblocks.
  - `html/src/Domain/MetricsService.php`
    - Migrated `getScraperDefenseMetrics()` to `TelemetryRepository::fetchScraperDefenseMetrics(...)`.
    - Removed direct datastore telemetry reads from scraper-defense metrics boundary.
  - `html/tests/Unit/TelemetryRepositoryTest.php`
    - Added deny/allow unit coverage for scraper-defense repository query method.
  - `html/tests/Integration/HealthControllerIntegrationTest.php`
    - Added integration assertion that admin `getHealthSnapshot()` includes expected `scraper_defense.total_attempts` and `scraper_defense.attempts_today` from repository-backed path.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream B progress to include scraper-defense migration and expanded health snapshot proof.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/HealthControllerIntegrationTest.php`
    - `tests/Unit/TelemetryRepositoryTest.php`
    - `tests/Contract/TelemetryAccessGovernanceContractTest.php`
    - `tests/Integration/EncryptionControllerIntegrationTest.php`
    - `tests/Unit/TelemetryControllerTest.php`
    - `tests/Integration/TelemetryControllerIntegrationTest.php`
    - `tests/Integration/TelemetryControllerPayloadIntegrationTest.php`
  - Result: `passed=29 failed=0`
  - Command: `cd <REPO_ROOT>/html && ./vendor/bin/phpstan analyse src/Domain/Telemetry/TelemetryRepository.php src/Domain/MetricsService.php tests/Unit/TelemetryRepositoryTest.php tests/Integration/HealthControllerIntegrationTest.php --level=9`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream B telemetry governance advanced: scraper-defense aggregation is now repository-token mediated with endpoint-level integration proof.

4. Remaining blockers and next immediate step
  - Remaining blocker: broad Workstream C and Workstream D closure tasks remain pending beyond telemetry read-governance migration.
  - Next immediate step: return to Workstream C admin-surface policy closure tasks and extend deny-safe integration proofs.

### Delta Report: Cycle 29 (2026-03-23)
Tags: cycle-29, workstream-C, P1, admin-policy-closure, deny-safe-proof

1. What changed (files and behaviors)
  - `html/tests/Integration/AdminPageControllerIntegrationTest.php`
    - Strengthened `testDashboardOmitsAdminEnrichmentWhenContextDenied()` to assert explicit deny-safe fallback attributes in rendered admin user controls:
      - `data-credential-count='0'`
      - `data-last-session-hash=''`
      - `data-last-passkey-used-at=''`
    - Existing deny assertions for omission of correlated session hash and credential enrichment remain in place.
  - `html/tests/Integration/HealthControllerIntegrationTest.php`
    - Added deterministic fixture hygiene for cached metrics snapshots by clearing:
      - `cache:metrics:contact_support`
      - `cache:metrics:scraper_defense`
    - Prevents cross-test cache bleed from masking telemetry fixture values.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream C progress to reflect expanded deny-safe integration proof depth.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/AdminPageControllerIntegrationTest.php`
    - `tests/Integration/HealthControllerIntegrationTest.php`
    - `tests/Unit/TelemetryRepositoryTest.php`
  - Result: `passed=19 failed=0`
  - Command: `cd <REPO_ROOT>/html && ./vendor/bin/phpstan analyse tests/Integration/AdminPageControllerIntegrationTest.php tests/Integration/HealthControllerIntegrationTest.php tests/Unit/TelemetryRepositoryTest.php src/Controllers/AdminPageController.php --level=9`

  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream C assurance improved: denied-correlation admin rendering now has explicit redaction-attribute assertions beyond omission-only checks.

4. Remaining blockers and next immediate step
  - Remaining blocker: Workstream C still needs broader controller/service-level enforcement proofs beyond current admin dashboard rendering path.
  - Next immediate step: identify additional admin enrichment endpoints and add deny-safe integration coverage for each path.

### Delta Report: Cycle 30 (2026-03-23)
Tags: cycle-30, workstream-C, P1, admin-policy-closure, deny-safe-proof-depth

1. What changed (files and behaviors)
  - `html/tests/Integration/AdminPageControllerIntegrationTest.php`
    - Expanded allow-context proof to assert session-derived timestamps are populated when correlation context is allowlisted:
      - `data-last-session-at='\\d+'`
      - `data-registered-at='\\d+'`
    - Expanded deny-context proof to assert session-derived timestamps are explicitly empty under denied correlation context:
      - `data-last-session-at=''`
      - `data-registered-at=''`
    - Existing deny-safe assertions for credential/session hash/passkey redaction remain in place.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream C progress to reflect timestamp-level deny-safe assertion coverage.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/AdminPageControllerIntegrationTest.php`
    - `tests/Integration/HealthControllerIntegrationTest.php`
    - `tests/Unit/TelemetryRepositoryTest.php`
  - Result: `passed=19 failed=0`
  - Command: `cd <REPO_ROOT>/html && ./vendor/bin/phpstan analyse tests/Integration/AdminPageControllerIntegrationTest.php tests/Integration/HealthControllerIntegrationTest.php tests/Unit/TelemetryRepositoryTest.php src/Controllers/AdminPageController.php --level=9`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream C assurance improved: deny-safe admin rendering proof now covers both enrichment identifiers and session-derived timestamp fields.

4. Remaining blockers and next immediate step
  - Remaining blocker: Workstream C still lacks equivalent deny-safe proof on additional admin enrichment surfaces beyond dashboard rendering.
  - Next immediate step: identify admin API/query paths that emit correlated account-security metadata and apply the same allow/deny test pattern.

### Delta Report: Cycle 31 (2026-03-23)
Tags: cycle-31, workstream-C, P1, privileged-role-hardening, superadmin-singleton

1. What changed (files and behaviors)
  - `html/src/Domain/Enums/AuthLevel.php`
    - Added `SUPERADMIN` role with rank above `ADMIN`.
  - `html/src/Domain/User.php`
    - `isAdmin()` now uses rank-based admin-or-higher semantics, so `SUPERADMIN` satisfies admin checks by construction.
    - Added explicit `isSuperAdmin()` helper.
    - `isManager()` now uses rank-based `atLeast(MANAGER)` semantics.
  - `html/src/Domain/UserRepository.php`
    - Added singleton enforcement for `SUPERADMIN` in `setUser(...)`: assigning a new superadmin demotes any existing superadmin holder(s) to admin.
  - `html/tests/Unit/UserRepositorySuperAdminTest.php` (new)
    - Added hierarchy/rank proof and singleton-demotion proof for superadmin reassignment.
    - Added fixture safety restoration to preserve any pre-existing superadmin holders outside test scope.
  - Runtime operator action:
    - Elevated active operator account UUID `U2f3695e3` to `superadmin` through repository path.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Unit/UserRepositorySuperAdminTest.php`
    - `tests/Integration/AdminPageControllerIntegrationTest.php`
    - `tests/Integration/HealthControllerIntegrationTest.php`
  - Result: `passed=8 failed=0`
  - Command: `cd <REPO_ROOT>/html && ./vendor/bin/phpstan analyse src/Domain/Enums/AuthLevel.php src/Domain/User.php src/Domain/UserRepository.php tests/Unit/UserRepositorySuperAdminTest.php tests/Integration/AdminPageControllerIntegrationTest.php --level=9`
  - Result: `[OK] No errors`
  - Command: superadmin holder verification by datastore scan
  - Result: `holders:U2f3695e3` and `target_level:superadmin`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream C assurance improved: privileged-role model now has explicit top-tier role and singleton governance constraints.

4. Remaining blockers and next immediate step
  - Remaining blocker: broader admin API surfaces still need explicit deny-safe correlation coverage beyond dashboard rendering controls.
  - Next immediate step: add integration-level allow/deny policy proofs on the next admin API surface that composes account-security metadata.

### Delta Report: Cycle 32 (2026-03-23)
Tags: cycle-32, workstream-C, P1, privileged-role-hardening, mutation-guards

1. What changed (files and behaviors)
  - `html/src/Controllers/AdminController.php`
    - Added `canManageAuthLevelChange(...)` enforcement in `updateUser()`.
    - Non-superadmin admins are now blocked from:
      - modifying users already at `ADMIN` or higher,
      - assigning `ADMIN`/`SUPERADMIN` to any target.
    - Only `SUPERADMIN` may perform privileged auth-level mutations.
  - `html/tests/Integration/AdminControllerIntegrationTest.php`
    - Added deny proof: admin cannot promote a user to `SUPERADMIN`.
    - Added allow proof: superadmin can promote a user to `ADMIN`.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream C role-hardening progress for privileged mutation guards.

2. What was verified (tests/commands and results)
  - Focused test run (tool):
    - `tests/Integration/AdminControllerIntegrationTest.php`
    - `tests/Unit/UserRepositorySuperAdminTest.php`
  - Result: `passed=2 failed=0`
  - Command: `cd <REPO_ROOT>/html && ./vendor/bin/phpstan analyse src/Controllers/AdminController.php src/Domain/Enums/AuthLevel.php src/Domain/User.php src/Domain/UserRepository.php tests/Integration/AdminControllerIntegrationTest.php tests/Unit/UserRepositorySuperAdminTest.php --level=9`
  - Result: `[OK] No errors`

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream C assurance improved: privileged role mutation is no longer admin-writable by default and now requires superadmin authority.

4. Remaining blockers and next immediate step
  - Remaining blocker: need broader API-surface policy tests for other admin endpoints with sensitive joins/operations.
  - Next immediate step: enforce/verify superadmin-only controls on highest-impact destructive admin actions where appropriate.

### Delta Report: Cycle 33 (2026-03-23)
Tags: cycle-33, workstream-D, P2, runtime-lifecycle, zeroization

1. What changed (files and behaviors)
  - `html/js/calendar/calendar.js`
    - Added lifecycle-driven crypto zeroization helpers:
      - `zeroizeCryptoState(reason, options)`
      - `resetMainThreadCryptoState()`
      - `bindCryptoLifecycleZeroization()`
    - Added zeroization triggers for:
      - `document.visibilitychange` when tab becomes hidden,
      - `window.pagehide` (navigation away),
      - `window.beforeunload` (navigation/close best-effort path).
    - Updated explicit `PayCalCrypto.clear()` to route through shared zeroization path.
    - Main-thread reset now clears unlock flags/context (`hasDek`, versions, `credentialId`, `userId`, profile encryption marker).

2. What was verified (tests/commands and results)
  - File diagnostics:
    - `html/js/calendar/calendar.js` -> no errors.
  - Command: `cd <REPO_ROOT> && npm run test:js`
  - Result: passed (`eslint` + JS security sink checks clean).

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream D moved from open-only status to first concrete lifecycle control implementation (D2 started).

4. Remaining blockers and next immediate step
  - Remaining blocker: lifecycle hardening still needs idle-time zeroization window controls and broader DOM sensitivity guard proofs.
  - Next immediate step: add idle-timer based zeroization and corresponding focused browser/runtime tests.

### Delta Report: Cycle 34 (2026-03-23)
Tags: cycle-34, workstream-D, P2, runtime-lifecycle, regression-proof, calendar

1. What changed (files and behaviors)
  - `html/js/calendar/calendar.js`
    - Advanced visibility lifecycle behavior by keeping hidden-tab zeroization delayed/cancelable, preventing transient hidden->visible races from clearing active decrypt context during immediate tab return.
  - `tests/smoke-ui/dev-bypass-smoke.spec.js`
    - Added browser regression test `calendar transient hide/show does not clear decrypted profile marker`.
    - Test simulates rapid visibility transitions and verifies decrypted profile marker remains present and calendar month navigation still renders grid cells.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream D progress with delayed hidden-tab zeroization and regression-proof coverage.

2. What was verified (tests/commands and results)
  - Command: `cd <REPO_ROOT> && npx playwright test tests/smoke-ui/dev-bypass-smoke.spec.js --config=playwright.smoke.config.js`
  - Result: `4 passed` (including transient hide/show regression case).
  - Command: `cd <REPO_ROOT> && npm run test:js`
  - Result: passed (`eslint` + JS security sink checks clean).

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream D assurance improved: lifecycle hardening now includes automated browser regression coverage for the previously observed blank-entry race path.

4. Remaining blockers and next immediate step
  - Remaining blocker: D3 DOM sensitivity guards and broader lifecycle stress coverage (long-hidden tab, multi-navigation, and session-timeout interaction) are still pending.
  - Next immediate step: add focused runtime tests for long-hidden tab behavior and verify deterministic re-unlock/re-render path after intentional zeroization.

### Delta Report: Cycle 35 (2026-03-23)
Tags: cycle-35, workstream-D, P2, runtime-lifecycle, explicit-zeroization, regression-proof

1. What changed (files and behaviors)
  - `tests/smoke-ui/dev-bypass-smoke.spec.js`
    - Added browser regression test `calendar explicit crypto clear zeroizes marker and keeps grid usable`.
    - Test verifies explicit zeroization path (`PayCalCrypto.clear()`) clears decrypted profile marker state and does not break calendar month grid navigation.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream D progress to include explicit-clear regression proof coverage.

2. What was verified (tests/commands and results)
  - Command: `cd <REPO_ROOT> && npx playwright test tests/smoke-ui/dev-bypass-smoke.spec.js --config=playwright.smoke.config.js`
  - Result: `5 passed` (includes explicit clear zeroization regression case).
  - Command: `cd <REPO_ROOT> && npm run test:js`
  - Result: passed (`eslint` + JS security sink checks clean).

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream D assurance improved: intentional in-session zeroization path now has deterministic browser coverage tied to post-clear UI operability.

4. Remaining blockers and next immediate step
  - Remaining blocker: D3 DOM sensitivity guards and deeper lifecycle stress scenarios (long-hidden tab and session-timeout interaction) remain open.
  - Next immediate step: add focused runtime coverage for hidden-tab delay expiry behavior under a true unlocked state and verify expected re-unlock UX path.

### Delta Report: Cycle 36 (2026-03-23)
Tags: cycle-36, workstream-D, P2, runtime-lifecycle, hidden-delay, locked-state-guard

1. What changed (files and behaviors)
  - `tests/smoke-ui/dev-bypass-smoke.spec.js`
    - Added browser regression test `calendar hidden-delay does not clear marker when DEK is absent`.
    - Test verifies delayed hidden-tab lifecycle path does not trigger profile-marker zeroization while `PayCalCrypto.hasDek` is false (locked state), and month-grid navigation remains functional.
  - `docs/security/PHASE_CLOSURE_PROGRAM.md`
    - Updated Workstream D progress to include locked-state hidden-delay guard proof.

2. What was verified (tests/commands and results)
  - Command: `cd <REPO_ROOT> && npx playwright test tests/smoke-ui/dev-bypass-smoke.spec.js --config=playwright.smoke.config.js`
  - Result: `6 passed` (includes new hidden-delay/no-DEK case).
  - Command: `cd <REPO_ROOT> && npm run test:js`
  - Result: passed (`eslint` + JS security sink checks clean).

3. Which statuses changed (Q1..Q7)
  - No status label changed in this cycle.
  - Workstream D assurance improved: delayed visibility lifecycle behavior now has deterministic locked-state guard coverage in browser smoke tests.

4. Remaining blockers and next immediate step
  - Remaining blocker: explicit long-hidden-tab zeroization proof under a true unlocked DEK state and post-zeroization re-unlock UX assertions are not yet automated.
  - Next immediate step: add a controlled test seam or harness fixture for unlocked-DEK simulation to verify hidden-delay expiry zeroization and deterministic recovery flow.

