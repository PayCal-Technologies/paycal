<?php
/**
 * Public Transparency: Auth, Passkey, and Redis Hardening — May 2026
 *
 * PURPOSE: Disclose all findings from the May 12, 2026 internal security audit of
 * authentication, passkey, and Redis infrastructure. Describes each flaw, its
 * risk, and exactly how it was fixed.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Auth, Passkey &amp; Redis Hardening — May 2026 - [PayCal]';
$pageLabel = 'Auth, Passkey & Redis Hardening — May 2026';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Auth, Passkey &amp; Redis Hardening — May 2026</span>
  </nav>

  <header class="doc-article-header">
    <h1>Auth, Passkey &amp; Redis Hardening — May 2026</h1>
    <p class="deck">
      On May 12, 2026 we conducted an internal audit of our authentication, passkey, and Redis
      infrastructure. We found eleven issues — every one of them in code we wrote ourselves.
      This article documents what we found, why it mattered, and exactly what we changed.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-05-12">2026-05-12</time> &nbsp;&bull;&nbsp; Commit: <code>493d5e44</code> &nbsp;&bull;&nbsp; Files changed: 11</p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Executive Summary</h2>
      <table class="doc-table" aria-label="Executive summary of audit findings">
        <tbody>
          <tr>
            <td><strong>Audit date</strong></td>
            <td>May 12, 2026</td>
          </tr>
          <tr>
            <td><strong>Scope</strong></td>
            <td>Authentication, passkey (WebAuthn), and Redis infrastructure</td>
          </tr>
          <tr>
            <td><strong>Total findings</strong></td>
            <td>11</td>
          </tr>
          <tr>
            <td><strong>Severity breakdown</strong></td>
            <td>
              <span class="doc-badge high">3 High</span>
              <span class="doc-badge medium">5 Medium</span>
              <span class="doc-badge low">3 Low</span>
            </td>
          </tr>
          <tr>
            <td><strong>Remediation status</strong></td>
            <td>All findings resolved in commit <code>493d5e44</code>. Full test suite passed. No regression.</td>
          </tr>
          <tr>
            <td><strong>Exploitation evidence</strong></td>
            <td>None</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section highlight">
      <h2>Why We Are Publishing This</h2>
      <p>
        We found these issues in our own application code and infrastructure layers — not in
        third-party dependencies or external services. Code we reviewed, committed, and shipped.
      </p>
      <p>
        We are publishing this because security transparency requires more than disclosing
        external CVEs or passing audits. It means being publicly accountable when your own
        team ships code that does not meet the bar you set for yourself.
      </p>
      <p>
        We are not embarrassed by this. The greater failure would have been discovering these
        issues and choosing not to disclose them.
      </p>
    </section>

    <section class="doc-section">
      <h2>Audit Methodology</h2>
      <p>
        This audit was conducted internally by the engineering team on May 12, 2026. The review
        covered all code paths related to authentication state management, WebAuthn credential
        lifecycle, and Redis key handling.
      </p>
      <ul class="doc-list">
        <li><strong>Manual code review</strong> of all controller, domain, and infrastructure files involved in session creation, passkey registration, passkey login, and account recovery flows.</li>
        <li><strong>Static analysis</strong> via PHPStan at Level 9 — zero-tolerance for type-unsafe or unreachable code paths.</li>
        <li><strong>Threat modeling</strong> against the WebAuthn Level 2 specification (§6.1 authenticator data, §7.1 registration ceremony, §7.2 authentication ceremony).</li>
        <li><strong>Regression testing</strong> with the full PHPUnit regression suite post-remediation. All tests passed.</li>
      </ul>
      <p>No external auditor, bug bounty report, or security incident preceded this review. These issues were identified through routine internal process.</p>
    </section>

    <section class="doc-section highlight">
      <h2>Our Engineering Philosophy</h2>
      <p>This audit surfaced failures across three principles we hold as foundational:</p>
      <ul class="doc-list">
        <li>
          <strong>Atomicity before correctness.</strong> If two operations must happen together,
          treat them as one operation or do not attempt the design at all. A system that is
          &ldquo;correct most of the time&rdquo; is not correct.
        </li>
        <li>
          <strong>Layered defense.</strong> No single check should be the sole barrier to a
          security boundary. If the database flags a credential as revoked, the registration
          path must also enforce it. Defense must not have gaps between layers.
        </li>
        <li>
          <strong>Information asymmetry as a design goal.</strong> An attacker who probes the
          system should learn as little as possible about what is happening inside it. Error
          messages, log entries, and response timing are all surfaces.
        </li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Finding 1 &mdash; Non-Atomic <code>hset + expire</code> (Redis Race Condition) <span class="doc-badge high">High</span></h2>
      <p><strong>Category: Redis / Atomicity</strong></p>
      <p>
        Across eight callsites, a Redis hash was written with <code>HSET</code> and then
        immediately had a TTL applied with a separate <code>EXPIRE</code> command:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — two separate round trips
Database::hset($key, $fields);
Database::expire($key, $ttlSeconds);</code></pre>
      </div>
      <p>
        These are two separate round trips to Redis. If the PHP process dies, is interrupted,
        hits a timeout, or if Redis experiences a momentary failure between them, the hash is
        written without an expiry — and lives forever in Redis.
      </p>
      <p>The affected callsites and their security implications:</p>
      <table class="doc-table" aria-label="Affected callsites for non-atomic hset+expire">
        <thead>
          <tr>
            <th scope="col">Callsite</th>
            <th scope="col">Key type</th>
            <th scope="col">Consequence of missing TTL</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>Authentication::createSession()</code></td>
            <td>Session record</td>
            <td>Session never expires — account accessible past intended lifetime</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (signup challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Stale challenge data persists beyond intended lifetime, increasing replay risk</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (register challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Same as above</td>
          </tr>
          <tr>
            <td><code>PasskeyController</code> (login challenge)</td>
            <td>WebAuthn challenge</td>
            <td>Same as above</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryController</code></td>
            <td>Recovery passkey challenge</td>
            <td>Recovery session data never expires</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (code issue)</td>
            <td>Recovery email code</td>
            <td>One-time codes survive past their intended expiry window</td>
          </tr>
          <tr>
            <td><code>RecoveryEmailController</code> (code resend)</td>
            <td>Recovery email code</td>
            <td>Same as above</td>
          </tr>
          <tr>
            <td><code>CapabilityTokenService</code></td>
            <td>One-shot admin tokens</td>
            <td>Tokens designed to expire in 5 minutes may survive indefinitely</td>
          </tr>
          <tr>
            <td><code>AccountRecoveryTransaction</code></td>
            <td>Recovery transaction record</td>
            <td>Recovery transaction state never cleaned up</td>
          </tr>
        </tbody>
      </table>
      <p>
        For sessions, this is a direct access-lifetime violation. A session should have a hard
        ceiling. If the TTL is never set, that ceiling does not exist.
      </p>
      <p>
        For one-shot capability tokens, a token designed to be valid for exactly 300 seconds
        may still be valid days later.
      </p>
      <p><strong>The fix:</strong> We introduced <code>Database::hsetex()</code> — a wrapper that
      executes both operations inside a Redis <code>MULTI/EXEC</code> transaction, making them atomic.
      The operations are executed in the same execution unit so the key cannot exist without its TTL
      being applied. The key either has data and a TTL, or it has nothing.</p>
      <div class="doc-code-block">
        <pre><code>// After — atomic MULTI/EXEC
Database::hsetex($key, $fields, $ttlSeconds);</code></pre>
      </div>
      <p>Every callsite that issued a <code>hset</code> followed by <code>expire</code> on the same key was converted.</p>
    </section>

    <section class="doc-section">
      <h2>Finding 2 &mdash; Logout and CSRF Invalidation Could Silently Fail <span class="doc-badge high">High</span></h2>
      <p><strong>Category: Redis / Logout, CSRF</strong></p>
      <p>
        The <code>Database::del()</code> method — responsible for deleting Redis keys by pattern —
        was enumerating keys using the <em>read replica</em> and then issuing <code>DEL</code> commands
        to the <em>primary</em>:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — key enumeration on replica
$keys = self::getReadInstance()->client->keys($pattern);</code></pre>
      </div>
      <p>
        Redis replication is asynchronous. If the replica lags — even by milliseconds — it may
        not yet hold the key that was just written. In that case <code>keys()</code> returns an
        empty list and no <code>DEL</code> is issued to the primary. The key survives.
      </p>
      <p>The two most critical callers of <code>del()</code>:</p>
      <ul class="doc-list">
        <li>
          <strong><code>destroySession()</code> — logout:</strong> When a user logs out, we delete
          their session key. If the replica is behind, the session key list returns empty, the delete
          never fires, and the session continues to exist on the primary. The user believes they are
          logged out. They are not.
        </li>
        <li>
          <strong><code>validateCSRFToken()</code> — nonce invalidation:</strong> CSRF tokens are
          one-shot nonces. After first use they must be deleted. If the delete never fires, the token
          can be reused on a second request. One-shot becomes reusable.
        </li>
      </ul>
      <p>
        This bug is subtle because it only surfaces under load or transient replica lag. In development
        against a single Redis instance it never triggers.
      </p>
      <p><strong>The fix:</strong> Key enumeration and deletion must target the same instance.</p>
      <div class="doc-code-block">
        <pre><code>// After — enumerate against write instance
$keys = self::getWriteInstance()->client->keys($pattern);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Finding 3 &mdash; WebAuthn User Verification Bypass <span class="doc-badge high">High</span></h2>
      <p><strong>Category: Authentication</strong></p>
      <p>
        In <code>AccountRecoveryController</code>, when a passkey was being registered as part of
        account recovery, the <code>processCreate()</code> call passed <code>false</code> for
        <code>requireUserVerification</code>:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — UV not enforced on verification
$result = $webauthn->processCreate(
    $clientDataJSON, $attestationObject, $challengeBinary,
    false,  // requireUserVerification — should be true
    true
);</code></pre>
      </div>
      <p>
        The challenge issued to the client specified <code>userVerification: 'required'</code> — the
        authenticator was told the user must complete a biometric check or PIN. But when verifying the
        response, we were telling the library not to enforce that the UV flag was set.
      </p>
      <p>
        A modified client could submit an authenticator response with the UV bit cleared. Our server
        would accept it without requiring biometric verification to have actually occurred.
      </p>
      <p>
        The account recovery flow is the path a user takes when they have lost access to their other
        credentials. This is the highest-risk authentication surface we operate. Weakening biometric
        enforcement here is exactly the wrong trade-off.
      </p>
      <p><strong>The fix:</strong> UV is now enforced. A response where the authenticator data does not
      carry the UV flag set is rejected.</p>
      <div class="doc-code-block">
        <pre><code>// After — UV enforced
$result = $webauthn->processCreate(
    $clientDataJSON, $attestationObject, $challengeBinary,
    true,   // requireUserVerification — enforced
    true
);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Finding 4 &mdash; Sign Count Clone Detection Missed Replay Attacks <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Category: Authentication</strong></p>
      <p>Our passkey clone detection was checking:</p>
      <div class="doc-code-block">
        <pre><code>// Before — misses equal-count replay
$suspectedClone = $newSignCount > 0 && $oldSignCount > 0 && $newSignCount &lt; $oldSignCount;</code></pre>
      </div>
      <p>
        The WebAuthn Level 2 specification (§6.1) states: if the stored sign count is non-zero and
        the new sign count is <em>not strictly greater than</em> the stored value, the credential
        should be considered as possibly cloned. Our condition required <code>&lt;</code>, not
        <code>&lt;=</code>, so an equal sign count — as in a replay attack — passed without
        triggering the clone flag.
      </p>
      <p><strong>The fix:</strong> Aligned to the spec.</p>
      <div class="doc-code-block">
        <pre><code>// After — covers replay (equal) and rollback (less-than)
$suspectedClone = $oldSignCount > 0 && $newSignCount &lt;= $oldSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Finding 5 &mdash; Sign Count Not Always Persisted <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Category: Authentication</strong></p>
      <p>After a successful passkey login, the sign count update was gated on it being non-zero:</p>
      <div class="doc-code-block">
        <pre><code>// Before — zero counts never written
if ($newSignCount > 0) {
    $updateFields['sign_count'] = (string) $newSignCount;
}</code></pre>
      </div>
      <p>
        Some authenticators return <code>0</code> as a sentinel meaning &ldquo;this device does not
        implement a counter.&rdquo; If a device later starts returning a real counter (firmware update,
        or the user registers the same credential on a counter-supporting platform), we would never
        persist the initial real count because we had stored <code>0</code> forever.
      </p>
      <p>
        Clone detection (Finding 4) requires the stored count to be non-zero — an authenticator we
        permanently tag as <code>0</code> is permanently opted out of counter-based protection.
      </p>
      <p><strong>The fix:</strong> The sign count is always written. The clone detection threshold handles
      interpretation.</p>
      <div class="doc-code-block">
        <pre><code>// After — always persist sign count
$updateFields['sign_count'] = (string) $newSignCount;</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Finding 6 &mdash; Revoked Passkey Could Be Re-Registered <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Category: Authentication</strong></p>
      <p>
        When a credential is marked revoked (clone detection triggered), there was no check in the
        registration path preventing re-registration of that same <code>credential_id</code>. An
        adversary with the raw passkey credential and account access could re-register the revoked
        credential, clearing its tainted history.
      </p>
      <p>
        Revocation is only meaningful if it is permanent. If it can be overwritten by re-registration
        using the same credential, clone detection provides no lasting protection.
      </p>
      <p><strong>The fix:</strong> If <code>revoked_at</code> is non-empty on an existing credential record,
      the re-registration is blocked with HTTP 403 and a security log entry is written.</p>
      <div class="doc-code-block">
        <pre><code>if (($existing['revoked_at'] ?? '') !== '') {
    SecurityLog::log('passkey_revoked_reregistration_blocked', [...]);
    Response::error('Registration failed.', ['error' => 'passkey_revoked'], HttpStatus::HTTP_FORBIDDEN);
    return;
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Finding 7 &mdash; Account Enumeration via Differing Error Responses <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Category: Information Disclosure</strong></p>
      <p>
        When a passkey login was attempted with an unrecognized email, the error response body took a
        different shape than other failure cases — an empty <code>[]</code> data payload vs. the
        <code>{'error': 'passkey_invalid'}</code> body returned elsewhere. A client probing the API
        could distinguish &ldquo;this email has no account&rdquo; from &ldquo;this email exists but
        the challenge failed&rdquo; by inspecting the response body.
      </p>
      <p>
        Additionally, the raw email address was being written to the observability log. Log aggregation
        pipelines should never hold raw user email addresses — if the log system is compromised, every
        enumeration attempt becomes a list of email addresses.
      </p>
      <p><strong>The fix:</strong> Both &ldquo;email not found&rdquo; and &ldquo;no credentials
      registered&rdquo; now return the same error body. The observability log records a SHA-256 hash of
      the email only — sufficient for incident correlation, insufficient to reconstruct the address.</p>
      <div class="doc-code-block">
        <pre><code>// Before
Lens::add('[PASSKEY] Login email not found', ['email' => $email]);
Response::error('Authentication failed.', [], HttpStatus::HTTP_UNAUTHORIZED);

// After
Lens::add('[PASSKEY] Login email not found', ['email_hash' => hash('sha256', $email)]);
Response::error('Authentication failed.', ['error' => 'passkey_invalid'], HttpStatus::HTTP_UNAUTHORIZED);</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Finding 8 &mdash; Recovery Key State Written Before Email Delivery Confirmed <span class="doc-badge medium">Medium</span></h2>
      <p><strong>Category: Data Integrity</strong></p>
      <p>
        During account recovery key generation, the server was writing <code>recovery_key_generated = 1</code>
        and <code>recovery_proof_key</code> to the user record <em>before</em> sending the recovery key email:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before — DB written first, email second
Database::hset(Keys::USER.':'.$user->user_uuid, [
    'recovery_key_generated' => '1',
    'recovery_proof_key' => $recoveryProofKey,
]);
$sent = EmailGarum::sendRecoveryKeyEmail(...);</code></pre>
      </div>
      <p>
        If the email failed to send, the database would show <code>recovery_key_generated = 1</code> —
        the system believes a key was issued. The user never received it.
      </p>
      <p>
        There is no regeneration path for a user in this state. Account recovery is permanently broken
        for that account until manual intervention.
      </p>
      <p><strong>The fix:</strong> Email delivery is confirmed first. Database state reflects what
      actually happened.</p>
      <div class="doc-code-block">
        <pre><code>// After — email first, then persist
$sent = EmailGarum::sendRecoveryKeyEmail(...);
if ($sent) {
    Database::hset(Keys::USER.':'.$user->user_uuid, [
        'recovery_key_generated' => '1',
        'recovery_proof_key' => $recoveryProofKey,
    ]);
}</code></pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Finding 9 &mdash; Disabled Registration Path Still Collected Password Fields <span class="doc-badge low">Low</span></h2>
      <p><strong>Category: Attack Surface</strong></p>
      <p>
        <code>RegistrationController</code> was still reading <code>password</code> and
        <code>confirm_password</code> from POST even though password-based registration has been
        disabled. PayCal registration is passkey-only.
      </p>
      <p>
        Collecting fields that serve no function is not harmless. Every value read from user input is
        a surface: it can be logged, audited, passed to other functions inadvertently, or included in
        error payloads. The principle of least surface area requires that we do not collect what we
        do not use.
      </p>
      <p><strong>The fix:</strong> The two fields were removed from the input collection map.</p>
    </section>

    <section class="doc-section">
      <h2>Finding 10 &mdash; User Email in Email Verification 403 Response <span class="doc-badge low">Low</span></h2>
      <p><strong>Category: Information Disclosure</strong></p>
      <p>
        <code>EmailVerificationGuard</code> — the middleware enforcing email verification before granting
        access to protected resources — was including <code>user_email</code> in the 403 response body:
      </p>
      <div class="doc-code-block">
        <pre><code>// Before
Response::error('Email verification required...', [
    'email_verified' => false,
    'user_email' => $user->email,  // disclosed to caller
], HttpStatus::HTTP_FORBIDDEN);</code></pre>
      </div>
      <p>
        If an attacker gains a valid but unverified session token (through session fixation or a
        compromised temporary link), they can learn the email address associated with the account from
        the 403 response body — without having supplied the email themselves. The only party who
        benefits from the email in this error payload is someone who has the session token but not
        the email.
      </p>
      <p><strong>The fix:</strong> The email field was removed from the error payload.</p>
    </section>

    <section class="doc-section">
      <h2>Finding 11 &mdash; Dead Code in <code>EmailGarum::verifyNewUserEmail()</code> <span class="doc-badge low">Low</span></h2>
      <p><strong>Category: Dead Code / Attack Surface</strong></p>
      <p>
        <code>EmailGarum</code> contained a 90-line method, <code>verifyNewUserEmail()</code>, handling
        a password-based email change flow. This flow was superseded when the platform moved to
        passkey-only authentication. The method was not called anywhere in the codebase.
      </p>
      <p>
        Dead code is not neutral. It occupies space in the security review surface, in static analysis,
        and in the cognitive load of anyone reading the file. It also represents a risk that a future
        developer, not knowing it was intentionally abandoned, might re-wire it into a new flow without
        full context.
      </p>
      <p><strong>The fix:</strong> Deleted. All callsites were confirmed empty before removal.</p>
    </section>

    <section class="doc-section">
      <h2>Summary of All Findings</h2>
      <table class="doc-table" aria-label="Summary of all findings">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Finding</th>
            <th scope="col">Severity</th>
            <th scope="col">Category</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>1</td><td>Non-atomic <code>hset + expire</code> across 9 callsites</td><td><span class="doc-badge high">High</span></td><td>Redis / Atomicity</td></tr>
          <tr><td>2</td><td><code>del()</code> using read replica for key enumeration</td><td><span class="doc-badge high">High</span></td><td>Redis / Logout, CSRF</td></tr>
          <tr><td>3</td><td>WebAuthn UV bypass in account recovery registration</td><td><span class="doc-badge high">High</span></td><td>Authentication</td></tr>
          <tr><td>4</td><td>Sign count clone detection missed replay attacks</td><td><span class="doc-badge medium">Medium</span></td><td>Authentication</td></tr>
          <tr><td>5</td><td>Sign count not persisted when authenticator returns zero</td><td><span class="doc-badge medium">Medium</span></td><td>Authentication</td></tr>
          <tr><td>6</td><td>Revoked passkey could be re-registered</td><td><span class="doc-badge medium">Medium</span></td><td>Authentication</td></tr>
          <tr><td>7</td><td>Account enumeration via error body + raw email in logs</td><td><span class="doc-badge medium">Medium</span></td><td>Information Disclosure</td></tr>
          <tr><td>8</td><td>Recovery key DB state written before email confirmed</td><td><span class="doc-badge medium">Medium</span></td><td>Data Integrity</td></tr>
          <tr><td>9</td><td>Disabled registration still collecting password fields</td><td><span class="doc-badge low">Low</span></td><td>Attack Surface</td></tr>
          <tr><td>10</td><td>User email in email verification 403 response</td><td><span class="doc-badge low">Low</span></td><td>Information Disclosure</td></tr>
          <tr><td>11</td><td>Dead method <code>verifyNewUserEmail()</code> in EmailGarum</td><td><span class="doc-badge low">Low</span></td><td>Dead Code</td></tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section success">
      <h2>What We Got Right</h2>
      <p>In the interest of a complete picture, the foundations that were already in place:</p>
      <ul class="doc-list">
        <li>
          <strong>Passkey-first authentication.</strong> The platform runs on WebAuthn with no
          password fallback for passkey users. The UV bypass and clone detection issues were defects
          within a fundamentally sound architecture.
        </li>
        <li>
          <strong>One-shot capability tokens.</strong> Admin-level mutations already required fresh,
          time-limited tokens. The atomicity fix hardened an existing protection rather than adding
          a missing one.
        </li>
        <li>
          <strong>Signed security log.</strong> Every security event — including the new
          <code>passkey_revoked_reregistration_blocked</code> events added in this commit — is
          written to a signed, append-only log with structured fields.
        </li>
        <li>
          <strong>PHPStan at Level 9.</strong> All 11 modified files were validated at maximum static
          analysis strictness. The full regression suite passed without regression.
        </li>
        <li>
          <strong>Clone detection existed.</strong> The logic was present and partially correct.
          Finding 4 was a boundary condition error, not a missing feature.
        </li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Customer Impact</h2>
      <ul class="doc-list">
        <li><strong>No evidence of exploitation.</strong> All findings were identified internally through routine code review. No external report, CVE, or incident preceded this disclosure.</li>
        <li><strong>No plaintext credential exposure.</strong> No passwords or recovery keys were exposed. Credential data at rest remains encrypted. Biometric data never leaves the authenticator device and is never transmitted to or stored by PayCal.</li>
        <li><strong>No evidence of unauthorized account access.</strong> Security logs show no anomalous patterns consistent with exploitation of these vectors.</li>
        <li><strong>All findings remediated before disclosure.</strong> Every issue described in this article was fixed, committed, and tested before this page was published.</li>
        <li><strong>Full regression suite passed.</strong> Complete PHPUnit suite and PHPStan Level 9 static analysis completed cleanly post-remediation.</li>
        <li><strong>Monitoring expanded.</strong> New security log events were added for passkey revocation enforcement (Finding 6) to surface future anomalies earlier.</li>
      </ul>
    </section>

    <section class="doc-section highlight">
      <h2>Prevention and Recurrence Controls</h2>
      <p>Two engineering rules adopted as standing policy from this audit:</p>
      <div class="subject-example-cutout" role="note" aria-label="New engineering rule: hsetex as default Redis write pattern">
        <h3><code>hsetex</code> is the default Redis write pattern</h3>
        <p>
          Any future code that needs to write a hash with a TTL must use
          <code>Database::hsetex()</code>. The old two-step pattern is no longer permitted.
          PHPStan rules will be written to flag new occurrences.
        </p>
      </div>
      <div class="subject-example-cutout" role="note" aria-label="New engineering rule: write-instance primacy for all key operations">
        <h3>Write-instance primacy for all key operations</h3>
        <p>
          Any Redis operation whose correctness depends on reading back what was just written must
          use the write instance. Read replicas are for read-heavy non-critical queries only.
        </p>
      </div>
      <p>
        Self-audits at this level of specificity are a standing commitment. We will continue
        publishing what we find. Future reports will be posted to the
        <a href="<?php echo transparency_href('/transparency/'); ?>">Transparency Hub</a>.
      </p>
    </section>

    <section class="doc-section">
      <h2>Disclosure Timeline</h2>
      <table class="doc-table" aria-label="Disclosure timeline">
        <thead>
          <tr>
            <th scope="col">Date</th>
            <th scope="col">Event</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><time datetime="2026-05-12">May 12, 2026</time></td>
            <td>Findings identified during routine internal audit session</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">May 12, 2026</time></td>
            <td>All fixes implemented and committed (<code>493d5e44</code>)</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">May 12, 2026</time></td>
            <td>Full PHPUnit regression suite passed, PHPStan Level 9 clean</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">May 12, 2026</time></td>
            <td>Pushed to origin/main</td>
          </tr>
          <tr>
            <td><time datetime="2026-05-12">May 12, 2026</time></td>
            <td>This transparency article published</td>
          </tr>
        </tbody>
      </table>
      <p>
        All findings were identified internally. No external report, CVE, or breach preceded this
        disclosure. There is no evidence that any finding was exploited.
      </p>
    </section>

  </div>
</article>
<?php
require_once HTML.'/footer.php';
