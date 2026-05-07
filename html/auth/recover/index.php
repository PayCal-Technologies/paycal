<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

$assetVersion = Environment::devSecurityDisabled() ? (string) time() : Environment::appVersion();
$language = strtolower(trim((string) InputSanitizer::getString('l')));
$authLanguageQuery = '';
if ($language !== '' && in_array($language, Language::getCodes(), true)) {
  $authLanguageQuery = '?l=' . rawurlencode($language);
}
$magicLinkToken = trim((string) InputSanitizer::getString('ml_token'));
$hasMagicLinkToken = $magicLinkToken !== '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Recover Account - PayCal</title>
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/'); ?>?v=<?php echo $assetVersion; ?>">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/utilities/'); ?>?v=<?php echo $assetVersion; ?>">
  <link rel="stylesheet" href="/css/auth-recovery/?v=<?php echo $assetVersion; ?>">
</head>
<body data-worker-version="<?php echo htmlspecialchars($assetVersion, ENT_QUOTES, 'UTF-8'); ?>">
  <main class="recovery-shell">
    <header class="recovery-header">
      <a class="recovery-back" href="/auth/<?php echo $authLanguageQuery; ?>">Back to sign in</a>
      <h1>Recover your account</h1>
      <p>We'll help you regain access in three steps.<br>Use either a recovery code or magic link from your email, plus your Recovery Key.</p>
    </header>
    <section class="recovery-card">
      <ol class="recovery-steps" aria-label="Recovery steps">
        <li<?php echo $hasMagicLinkToken ? '' : ' class="is-active"'; ?> data-step-indicator="1">Verify</li>
        <li<?php echo $hasMagicLinkToken ? ' class="is-active"' : ''; ?> data-step-indicator="2">Passkey</li>
        <li data-step-indicator="3">Success</li>
      </ol>
      <p class="recovery-status" id="recovery-status" aria-live="assertive"><?php echo $hasMagicLinkToken ? 'Verifying recovery link...' : 'Enter your account email to begin.'; ?></p>
      <section class="recovery-panel<?php echo $hasMagicLinkToken ? ' is-hidden' : ''; ?>" data-step="1">
        <form id="recovery-start-form">
          <label for="recovery-email">Account email</label>
          <input id="recovery-email" name="email" type="email" autocomplete="email" required>
          <button id="recovery-send-code" type="submit" class="btn btn_primary">Send code</button>
        </form>
        <form id="recovery-verify-form" class="is-hidden">
          <div id="recovery-code-block">
            <label for="recovery-code">Recovery code</label>
            <input id="recovery-code" name="code" type="text" autocomplete="one-time-code" maxlength="6" required>
          </div>
          <label for="recovery-key">Recovery Key</label>
          <input id="recovery-key" name="recoveryKey" type="text" autocomplete="off" spellcheck="false" required>
          <div class="recovery-actions">
            <button type="button" id="recovery-back-signin" class="btn btn_secondary">Back to signin</button>
            <button type="submit" class="btn btn_primary">Continue</button>
          </div>
        </form>
      </section>
      <section class="recovery-panel<?php echo $hasMagicLinkToken ? '' : ' is-hidden'; ?>" data-step="2">
        <p>Verified. Now register a new passkey for this account.</p>
        <label for="recovery-device-name">New passkey name</label>
        <input id="recovery-device-name" name="deviceName" type="text" autocomplete="off" value="Recovered Passkey">
        <div class="recovery-actions">
          <button type="button" id="recovery-register-passkey" class="btn btn_primary">Register new passkey</button>
          <button type="button" id="recovery-cancel" class="btn btn_secondary">Cancel</button>
        </div>
        <p class="recovery-hint" id="recovery-existing-passkey-hint" aria-live="polite">
          Already have a passkey? <a href="/auth/" id="recovery-signin-instead">Sign in instead</a>
        </p>
      </section>
      <section class="recovery-panel is-hidden" data-step="3">
        <h2>Recovery complete</h2>
        <p>Your data remains encrypted and unchanged.</p>
        <a class="btn btn_primary" href="/">Continue to PayCal</a>
      </section>
    </section>
  </main>
  <script src="/js/auth-recovery/?v=<?php echo $assetVersion; ?>" defer></script>
</body>
</html>
