<?php
/**
 * Public Transparency: Faster Passkey Sign-In Early Access
 *
 * PURPOSE: Explain the Early Access passkey sign-in shortcut, user benefit,
 *          browser behavior, privacy boundaries, and fallback behavior.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../_link.php';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Faster Passkey Sign-In Early Access - [PayCal]';
$pageLabel = 'Faster Passkey Sign-In Early Access';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Transparency Hub</a>
    <span class="separator">/</span>
    <span class="current">Faster Passkey Sign-In Early Access</span>
  </nav>

  <header class="doc-article-header">
    <h1>Faster Passkey Sign-In Early Access</h1>
    <p class="deck">
      Faster Passkey Sign-In lets an opted-in browser show available PayCal passkeys as soon
      as you select Sign in, so you can skip typing your email address when a local passkey is
      ready to use.
    </p>
    <p class="doc-article-meta">
      Published: <time datetime="2026-06-23">2026-06-23</time>
      &middot; Last updated: <time datetime="2026-06-23">2026-06-23</time>
      &middot; Status: Early Access
    </p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>What It Does</h2>
      <p>
        The standard passkey sign-in flow starts with an email address. PayCal uses that email
        to find the matching passkey records, asks the browser for one of those credentials, and
        then verifies the signed response before creating a session.
      </p>
      <p>
        Faster Passkey Sign-In removes the email-first step when the current browser already has
        an available PayCal passkey. After you enable the setting, selecting Sign in can open the
        browser's passkey prompt immediately. You choose the PayCal passkey, complete the local
        device check, and PayCal signs you in after the normal server verification succeeds.
      </p>
    </section>

    <section class="doc-section success">
      <h2>How It Helps</h2>
      <ul class="doc-fact-list">
        <li><strong>Fewer steps:</strong> returning users can skip entering an email address on trusted browsers.</li>
        <li><strong>Better account switching:</strong> a family or shared workstation can show the PayCal passkeys that browser and operating system make available.</li>
        <li><strong>Same security ceremony:</strong> the shortcut changes the prompt timing, not the cryptographic verification.</li>
        <li><strong>Graceful fallback:</strong> if the shortcut cannot run, the regular sign-in options still appear.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>How It Works</h2>
      <ol class="doc-list">
        <li>You sign in normally and enable Faster Passkey Sign-In from Settings, under Early Access.</li>
        <li>PayCal stores a signed activation cookie for this browser only.</li>
        <li>When you return to the sign-in page, PayCal checks the feature flag and that signed cookie.</li>
        <li>If the feature is allowed, selecting Sign in asks the browser for a discoverable PayCal passkey using Chrome's <a href="https://developer.chrome.com/docs/identity/immediate-ui-mode" rel="noopener noreferrer">Immediate UI mode</a>.</li>
        <li>The browser may show available PayCal passkeys without requiring an email address first.</li>
        <li>PayCal verifies the fresh WebAuthn challenge response, origin, relying party ID, credential signature, and credential record before creating a session.</li>
      </ol>
    </section>

    <section class="doc-section">
      <h2>What The Cookie Does</h2>
      <p>
        The activation cookie is a browser preference, not a login token. It tells PayCal that
        this browser opted in to the faster prompt. It contains only feature metadata such as
        version and expiry, and it is signed by PayCal so the browser cannot safely forge or
        alter it.
      </p>
      <p>
        The cookie does not contain your email address, user UUID, passkey credential ID, session
        token, biometric data, or a secret that can sign you in. Clearing site data or disabling
        the setting removes the browser opt-in.
      </p>
    </section>

    <section class="doc-section">
      <h2>What Does Not Change</h2>
      <ul class="doc-fact-list">
        <li>Your existing passkeys are not modified.</li>
        <li>PayCal still creates a fresh challenge for the sign-in attempt.</li>
        <li>No session is created unless WebAuthn verification succeeds on the server.</li>
        <li>Biometrics stay on your device and are never sent to PayCal.</li>
        <li>Your operating system may still require Touch ID, Apple Watch approval, or the system password before releasing the passkey assertion.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Shared Devices</h2>
      <p>
        This setting applies only to the browser where it was enabled. On a shared device, the
        browser or operating system may offer any available PayCal passkey it can discover for
        the site. That can be helpful when multiple household members use the same computer, but
        it is also why we label the feature Early Access and describe the shared-device behavior
        directly in Settings.
      </p>
    </section>

    <section class="doc-section">
      <h2>Fallbacks And Limits</h2>
      <p>
        Faster Passkey Sign-In is a progressive enhancement. If the browser does not support the
        prompt mode, private browsing blocks the needed browser state, no local PayCal passkey is
        available, the prompt is dismissed, or PayCal turns off the runtime flag, the sign-in page
        falls back to the standard options.
      </p>
      <p>
        The first Early Access target is Chrome's Immediate UI behavior. Other browsers may expose
        similar passkey experiences differently, so PayCal treats support as a runtime capability
        rather than assuming every browser behaves the same way.
      </p>
    </section>

    <section class="doc-section highlight">
      <h2>Why We Are Testing It</h2>
      <p>
        Passkeys are strongest when they are easy enough to use every day. This feature keeps the
        security model intact while removing a small but repeated step from sign-in. Early Access
        gives us a controlled way to learn how the browser prompt behaves across real devices,
        shared computers, private browsing, and account-switching situations before making it a
        default experience.
      </p>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
