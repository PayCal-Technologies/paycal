<?php
/**
 * Public Transparency: Opt-in Diagnostics & Phantom Wing
 *
 * PURPOSE:
 * Explain how PayCal's optional diagnostics system works, what data it collects
 * (and what it never collects), who controls it, and how it helps troubleshoot
 * problems without compromising privacy.
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
$pageTitle = 'Opt-in Diagnostics & Phantom Wing - [PayCal]';
$pageLabel = 'Opt-in Diagnostics & Phantom Wing';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current">Opt-in Diagnostics &amp; Phantom Wing</span>
  </nav>

  <header class="doc-article-header">
    <h1>Opt-in Diagnostics &amp; Phantom Wing</h1>
    <p class="deck">
      PayCal includes an optional diagnostics layer that you control. Here is exactly what it
      collects, what stays on your device, and how it is used.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-05">2026-04-05</time></p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Overview</h2>
      <p>
        PayCal ships with a built-in diagnostics layer called <strong>Phantom Wing</strong>.
        By default it is almost entirely silent — it only captures severe, unhandled errors
        and never sends anything without your explicit opt-in.
      </p>
      <p>
        If you run into a problem and want to share more context with support, you can enable
        extra diagnostics in <a href="/settings/">Settings → Debugging (Optional)</a>.
        Each setting is independent; you can turn on just the one that is relevant.
        All three default to <strong>Off</strong>.
      </p>
    </section>

    <section class="doc-section">
      <h2>The Three Opt-in Controls</h2>
      <p>
        Each control lives in the <strong>Debugging (Optional)</strong> panel at the bottom of
        your Settings page. They are designed for troubleshooting only — turning them on may
        slow down page interactions slightly because extra work happens in the browser.
      </p>

      <table class="doc-table">
        <thead>
          <tr>
            <th>Setting</th>
            <th>What it enables</th>
            <th>Who sees it</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>Console Messages</strong></td>
            <td>
              Emits warnings, informational logs, and performance markers to your browser
              developer console. Useful for self-diagnosis — open DevTools and look for
              messages prefixed with <code>[PayCal]</code> or emoji markers.
            </td>
            <td>You only (your browser console, never transmitted)</td>
          </tr>
          <tr>
            <td><strong>Detailed Diagnostics</strong></td>
            <td>
              Enables step-by-step internal event logging. Phantom Wing captures the full
              lifecycle of operations (calendar loads, form submissions, session events) to
              an in-memory log that is included in any support report you choose to share.
            </td>
            <td>You only, unless you share a support report</td>
          </tr>
          <tr>
            <td><strong>Network Insights</strong></td>
            <td>
              Logs API request timing — how long each server round-trip takes, response
              sizes, and whether batching or caching was applied. Helps diagnose slowness
              on specific operations.
            </td>
            <td>You only (your browser console, never transmitted)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>What Phantom Wing Does by Default</h2>
      <p>
        Even with all three controls off, Phantom Wing runs a lightweight baseline monitor
        that captures only severe failures:
      </p>
      <ul class="doc-list">
        <li>Uncaught JavaScript exceptions (<code>window.onerror</code>)</li>
        <li>Unhandled promise rejections</li>
        <li>Fetch calls that fail with a network error (not HTTP errors — those are handled per-feature)</li>
      </ul>
      <p>
        This baseline data stays entirely in memory and is never transmitted anywhere.
        It is displayed in a one-second summary in the browser console at page load
        so you can quickly see if anything went wrong, then discarded.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Baseline output when all clear (console, diagnostics off):
[PHANTOM WING] All clear - no errors or warnings detected.

// Baseline output when issues exist:
[PHANTOM WING] Error Summary
Total issues: 2 across 2 grouped location(s).
WARN 1: FormSubmit timed out after 8000ms
ERROR 1: Uncaught TypeError in calendar renderer</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Phantom Wing &amp; Telemetry</h2>
      <p>
        Phantom Wing has a lightweight telemetry channel used to measure feature
        reliability in aggregate — for example, detecting if a particular operation
        is failing at an unusual rate across the platform.
      </p>
      <h3>What telemetry sends</h3>
      <ul class="doc-list">
        <li>Anonymized event counts bucketed per hour (e.g. <code>pw.performance.metrics: count=1, bucket_hour=2026030914</code>)</li>
        <li>Error category and type — never the full error message or stack trace</li>
        <li>No user identifiers, no session tokens, no IP addresses</li>
      </ul>
      <h3>What telemetry never sends</h3>
      <ul class="doc-list">
        <li>Your name, email, or any account details</li>
        <li>Earnings, pay period, or financial data</li>
        <li>Full error messages or stack traces</li>
        <li>URL paths or query strings</li>
        <li>Keystrokes or form field values</li>
      </ul>
      <h3>Rate limiting &amp; back-off</h3>
      <p>
        Telemetry submissions are rate-limited server-side per user per minute. If your
        client exceeds the threshold, the server acknowledges silently and discards the
        excess — nothing is stored. The client also applies exponential back-off: after
        two consecutive server-side failures it disables telemetry submission for ten
        minutes automatically.
      </p>
      <div class="doc-code-block">
        <pre class="doc-code">// Telemetry payload shape (no personal data):
{
  "type": "pw.performance.metrics",
  "fields": {
    "count": 1,
    "bucket_hour": 2026030914,
    "flush_reason": "timer"
  }
}</pre>
      </div>
    </section>

    <section class="doc-section">
      <h2>Data Redaction</h2>
      <p>
        Before any value is stored in memory or transmitted through telemetry, Phantom Wing
        applies an automatic redaction pass. Values that match known sensitive patterns are
        replaced with <code>[REDACTED]</code>:
      </p>
      <ul class="doc-list">
        <li>Email addresses</li>
        <li>Bearer tokens and authorization header values</li>
        <li>CSRF tokens</li>
        <li>Strings that look like cryptographic keys or base64-encoded blobs above a minimum length</li>
      </ul>
      <p>
        Redaction operates on all arguments passed to the intercepted console methods and
        all telemetry field values before queuing. It cannot be bypassed by diagnostic
        settings being enabled.
      </p>
    </section>

    <section class="doc-section">
      <h2>Scope Guards: Pages Where Diagnostics Are Suppressed</h2>
      <p>
        Telemetry submission is completely suppressed on authentication pages
        (<code>/auth/</code>). This means that even if Network Insights is turned on,
        no telemetry is flushed while you are on the sign-in, sign-up, or recovery flows.
        This is a defense-in-depth measure to prevent any possibility of credential-adjacent
        data appearing in diagnostic channels.
      </p>
    </section>

    <section class="doc-section">
      <h2>Your Control</h2>
      <p>
        All three diagnostic settings are stored as account preferences, not browser cookies.
        They follow your account across devices and sessions and default to
        <strong>Off</strong> for every account — including new accounts.
        You can change them at any time in
        <a href="/settings/">Settings → Debugging (Optional)</a>.
      </p>
      <p>
        Turning a setting off takes effect immediately on the next page load.
        No diagnostic data is retained between sessions: Phantom Wing's in-memory log is
        cleared when you navigate away or close the tab.
      </p>
    </section>

    <section class="doc-section">
      <h2>Summary</h2>
      <ol class="doc-list">
        <li>All three debug controls default to <strong>Off</strong> and must be explicitly enabled by you</li>
        <li>Console Messages and Network Insights never leave your device</li>
        <li>Detailed Diagnostics stays in-memory and is only shared if you choose to share a support report</li>
        <li>Telemetry sends only anonymized, aggregate event counts — zero personal data</li>
        <li>All values are redacted before storage or transmission, regardless of diagnostic settings</li>
        <li>Telemetry is fully suppressed on all authentication pages</li>
        <li>Rate limiting and automatic client back-off prevent any accidental over-reporting</li>
      </ol>
      <p style="margin-top: 1.5rem;">
        Phantom Wing is engineered so you can safely leave all diagnostics off indefinitely.
        The opt-in controls exist to give you and the support team a shared language when
        something does go wrong — not to collect data by default.
      </p>
    </section>

  </div>

</article>

<?php require_once HTML.'/footer.php'; ?>
