<?php
/**
 * Public Transparency: Readable Account Recovery Codes — June 2026
 *
 * PURPOSE:
 * Explain why PayCal redesigned account recovery around shorter human-readable
 * codes, checksum typo detection, strict server rate limits, email verification,
 * and passkey replacement boundaries.
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
$pageTitle = 'Readable Recovery Codes Without Weakening Account Recovery - [PayCal]';
$pageLabel = 'Readable Recovery Codes Without Weakening Account Recovery';
$pageMetaDescription = 'PayCal redesigned account recovery with readable Recovery Codes, short-lived email Verification Codes, checksum typo detection, rate limits, and passkey replacement safeguards.';
$pageMetaDescriptionLong = 'PayCal redesigned account recovery in June 2026 to make recovery faster and less error-prone for real users while preserving strict server-side security boundaries.';
$pageSocialTitle = 'Readable Recovery Codes Without Weakening Account Recovery';
$pageOgDescription = 'A public explanation of PayCal account recovery: readable codes, checksum typo detection, two-factor recovery, rate limits, and why the server remains authoritative.';
$pageTwitterTitle = 'Readable Recovery Codes Without Weakening Account Recovery';
$pageTwitterDescription = 'How PayCal balanced account recovery usability with strict recovery security.';
$pageDcTitle = 'Readable Recovery Codes Without Weakening Account Recovery';
$pageDcDescription = $pageMetaDescription;
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Readable Recovery Codes</span>
  </nav>

  <header class="doc-article-header">
    <h1>Readable Recovery Codes Without Weakening Account Recovery</h1>
    <p class="deck">
      In June 2026 we redesigned PayCal account recovery so the process is easier
      for real users under stress: shorter saved Recovery Codes, a clear email
      Verification Code, typo-resistant checksums, forgiving paste behavior, and
      one calm form. The security rule did not change: recovery must prove both
      inbox access and possession of the saved account secret.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-06-21">2026-06-21</time> &middot; Last updated: <time datetime="2026-06-21">2026-06-21</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Executive Summary</h2>
      <table class="doc-table" aria-label="Account recovery redesign summary">
        <tbody>
          <tr>
            <td><strong>Why we did it</strong></td>
            <td>Account recovery should be fast, readable, and calm without becoming permissive</td>
          </tr>
          <tr>
            <td><strong>Saved factor</strong></td>
            <td>Recovery Code: <code>XXXXXX-XXXXXX-CC</code>, where 12 characters are secret and 2 are checksum</td>
          </tr>
          <tr>
            <td><strong>Email factor</strong></td>
            <td>Verification Code: <code>XXXXCC</code>, where 4 characters are secret and 2 are checksum</td>
          </tr>
          <tr>
            <td><strong>Alphabet</strong></td>
            <td><code>ABCDEFGHJKLMNPQRTUWXYZ346789</code>, chosen to avoid visually confusing characters</td>
          </tr>
          <tr>
            <td><strong>Server limits</strong></td>
            <td>10-minute email-code window, one-time use, attempt limits, resend cooldown, transaction TTL, and abuse telemetry</td>
          </tr>
          <tr>
            <td><strong>Security boundary</strong></td>
            <td>Email code proves inbox access. Recovery Code proves saved-account-secret possession. Neither is enough alone for protected recovery.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>The Problem We Were Solving</h2>
      <p>
        Recovery is not a normal login. People reach it when something has already
        gone wrong: a lost device, a missing passkey, a new phone, or a deadline.
        A recovery design can be cryptographically sound and still fail users if
        the code is too long, hard to read, hard to copy, or easy to mistype.
      </p>
      <p>
        The goal was not to make recovery casual. The goal was to make the honest
        path less painful while keeping the server strict. That meant reducing
        transcription mistakes, keeping all important fields on one screen, making
        paste forgiving, and validating obvious typos before the user waits on a
        server response.
      </p>
    </section>

    <section class="doc-section">
      <h2>The New Codes</h2>
      <p>
        Both codes use the same PayCal accessibility alphabet:
      </p>
      <div class="doc-code-block" data-label="PayCal code alphabet">
        <pre><code>ABCDEFGHJKLMNPQRTUWXYZ346789</code></pre>
      </div>
      <p>
        We intentionally exclude characters that are commonly confused when read,
        copied, printed, or typed. Inputs are normalized by uppercasing and removing
        spaces and hyphens, so users can paste codes in grouped or ungrouped form.
      </p>
      <table class="doc-table" aria-label="Recovery and verification code formats">
        <thead>
          <tr>
            <th>Code</th>
            <th>Display format</th>
            <th>Secret characters</th>
            <th>Checksum characters</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Recovery Code</td>
            <td><code>XXXXXX-XXXXXX-CC</code></td>
            <td>12</td>
            <td>2</td>
          </tr>
          <tr>
            <td>Verification Code</td>
            <td><code>XXXXCC</code></td>
            <td>4</td>
            <td>2</td>
          </tr>
        </tbody>
      </table>
      <p>
        The checksum is not a secret and does not add security entropy. It exists
        to catch honest mistakes. If the last two characters do not match the
        secret portion, the browser can immediately say, "Check the last two
        characters," before the user burns a server attempt.
      </p>
    </section>

    <section class="doc-section highlight">
      <h2>The Math</h2>
      <p>
        The Recovery Code has 12 secret characters drawn from 28 possible symbols.
        That gives:
      </p>
      <div class="doc-code-block" data-label="Recovery Code search space">
        <pre><code>28^12 = 232,218,265,089,212,416 possibilities
log2(28^12) ~= 57.7 bits</code></pre>
      </div>
      <p>
        The email Verification Code has 4 secret characters:
      </p>
      <div class="doc-code-block" data-label="Email Verification Code search space">
        <pre><code>28^4 = 614,656 possibilities
log2(28^4) ~= 19.2 bits</code></pre>
      </div>
      <p>
        When the two factors are considered together, the secret search space is:
      </p>
      <div class="doc-code-block" data-label="Combined recovery search space">
        <pre><code>28^16 = 142,734,349,946,674,946,768,896 possibilities
log2(28^16) ~= 76.9 bits</code></pre>
      </div>
      <p>
        A single random guess against the saved Recovery Code is about 1 in
        232 quadrillion. A single random guess against both factors together is
        about 1 in 142 septillion. With five combined guesses, the chance is still
        about 3.5 x 10^-23, or roughly 1 in 28.5 sextillion.
      </p>
    </section>

    <section class="doc-section">
      <h2>Why Online Brute Force Takes So Long</h2>
      <p>
        The important phrase is <strong>online recovery</strong>. Attackers do not
        get to run unlimited guesses through PayCal as fast as their hardware can
        compute. The server controls the pace, counts failures, expires codes,
        records abuse telemetry, and requires transaction state to line up.
      </p>
      <p>
        Using the current conservative defaults, recovery is bounded by controls
        such as:
      </p>
      <ul class="doc-list">
        <li>email Verification Codes expire after 10 minutes;</li>
        <li>email Verification Codes are single-use;</li>
        <li>verification and proof endpoints are attempt-limited;</li>
        <li>recovery starts are limited per day;</li>
        <li>resends are limited per hour and have a cooldown;</li>
        <li>server-seen checksum failures count toward abuse telemetry and attempt policy;</li>
        <li>recovery transactions and bootstrap windows expire;</li>
        <li>the server, not the browser, remains authoritative.</li>
      </ul>
      <p>
        At five guesses per hour, trying half of the Recovery Code space would
        take about 2.65 trillion years. Trying half of the combined Recovery Code
        plus email-code space at the same online pace would take about 1.63
        quintillion years. That is the "really, really long time" we mean: not
        because the user has to carry a huge code, but because online recovery
        has multiple factors and the server will not let guesses run freely.
      </p>
    </section>

    <section class="doc-section">
      <h2>The 10-Minute Window</h2>
      <p>
        The email code is intentionally short because it is not meant to protect
        the account by itself. It is time-limited, delivered to the user's inbox,
        attempt-limited, and consumed on success.
      </p>
      <p>
        With 614,656 possible email-code secrets, five guesses inside one
        10-minute code window gives at most a 0.000813% chance of guessing the
        email code by chance, or about 1 in 122,931. That still would not complete
        protected recovery, because the saved Recovery Code and recovery proof
        must also pass.
      </p>
    </section>

    <section class="doc-section">
      <h2>What Changed in the User Experience</h2>
      <p>
        We reduced the recovery screen to one centered form. The user sees the
        email field, the Verification Code field, the Recovery Code field, and
        one primary action: Verify and continue.
      </p>
      <ul class="doc-list">
        <li>The user can paste the saved Recovery Code while waiting for email.</li>
        <li>Spaces and hyphens are accepted and normalized.</li>
        <li>Recovery Codes are auto-formatted as <code>XXXXXX-XXXXXX-CC</code>.</li>
        <li>Verification Codes are auto-formatted as <code>XXXXCC</code>.</li>
        <li>Invalid alphabet characters show a clear inline message.</li>
        <li>Checksum failures are caught locally before the server request.</li>
        <li>When both formats look right, the form shows "Checking..." and submits automatically after a short debounce.</li>
        <li>The Verify and continue button remains available as a fallback.</li>
      </ul>
      <p>
        The result is faster for the normal user: fewer steps, fewer surprises,
        less hidden state, and less punishment for copying a code with spaces or
        hyphens.
      </p>
    </section>

    <section class="doc-section">
      <h2>What Changed in the Security Model</h2>
      <p>
        The redesign also tightened several recovery boundaries:
      </p>
      <ul class="doc-list">
        <li>Raw Recovery Codes are not emailed. They are displayed once when created and must be saved by the user.</li>
        <li>PayCal stores recovery-wrapped material and verifier state, never the raw Recovery Code.</li>
        <li>Magic links cannot make an existing protected account passkey-ready by themselves.</li>
        <li>Email-only bootstrap is allowed only for first-passkey setup when there is no existing protected crypto material and no existing passkey.</li>
        <li>Completing recovery requires the passkey credential ID registered in the recovery transaction to match the completion request.</li>
        <li>Old passkeys are revoked after successful account recovery so the new passkey becomes the trusted credential.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>What This Does Not Mean</h2>
      <p>
        This is not a claim that a 12-character human-readable code is a
        256-bit offline secret. PayCal account recovery is an online,
        server-mediated, two-factor recovery flow. The Recovery Code is one factor;
        inbox access and transaction state are another; passkey registration is
        the final device-bound step.
      </p>
      <p>
        That distinction matters. We made the code readable because people need to
        save it and type it correctly. We kept the server strict because readable
        recovery should not mean weak recovery.
      </p>
    </section>

    <section class="doc-section highlight">
      <h2>The Rule We Now Enforce</h2>
      <div class="doc-code-block" data-label="Recovery security rule">
        <pre><code>Email code proves inbox access.
Recovery Code proves saved-account-secret possession.
Neither one is enough alone for protected account recovery.</code></pre>
      </div>
      <p>
        That is the balance we wanted: recovery that feels easy, fast, and simple
        for the account owner, while remaining strict enough that guessing through
        the server is not a practical path.
      </p>
    </section>
  </div>
</article>
<?php require_once HTML.'/footer.php'; ?>
