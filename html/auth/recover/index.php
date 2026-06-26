<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

$assetVersion = Environment::devSecurityDisabled() ? (string) time() : Environment::appVersion();
$languageRaw = strtolower(trim((string) InputSanitizer::getString('l')));
$language = ($languageRaw !== '' && in_array($languageRaw, Language::getCodes(), true)) ? $languageRaw : '';
$authLanguageQuery = $language !== '' ? '?l=' . rawurlencode($language) : '';
if ($language !== '' && !defined('PAYCAL_PAGE_LANGUAGE_OVERRIDE')) {
  define('PAYCAL_PAGE_LANGUAGE_OVERRIDE', $language);
}
$magicLinkToken = trim((string) InputSanitizer::getString('ml_token'));
$hasMagicLinkToken = $magicLinkToken !== '';

$i18nKeys = [
  'AUTH_RECOVER_ACCOUNT_EMAIL',
  'AUTH_RECOVER_ALREADY_HAVE_PASSKEY',
  'AUTH_RECOVER_BACK_TO_SIGNIN',
  'AUTH_RECOVER_BACK_TO_SIGNIN_BUTTON',
  'AUTH_RECOVER_CODE_LABEL',
  'AUTH_RECOVER_COMPLETE_HEADING',
  'AUTH_RECOVER_COMPLETE_MESSAGE',
  'AUTH_RECOVER_CONTINUE_TO_PAYCAL',
  'AUTH_RECOVER_DISCLOSURE',
  'AUTH_RECOVER_EMAIL_SENT',
  'AUTH_RECOVER_SUBHEADING',
  'AUTH_RECOVER_DEFAULT_DEVICE_NAME',
  'AUTH_RECOVER_HEADING',
  'AUTH_RECOVER_INTRO_LINE_1',
  'AUTH_RECOVER_INTRO_LINE_2',
  'AUTH_RECOVER_KEY_LABEL',
  'AUTH_RECOVER_META_TITLE',
  'AUTH_RECOVER_NEW_PASSKEY_NAME',
  'AUTH_RECOVER_REGISTER_NEW_PASSKEY',
  'AUTH_RECOVER_SEND_CODE',
  'AUTH_RECOVER_SIGN_IN_INSTEAD',
  'AUTH_RECOVER_STATUS_MAGIC_LINK',
  'AUTH_RECOVER_STATUS_START',
  'AUTH_RECOVER_STEPS_ARIA_LABEL',
  'AUTH_RECOVER_STEP_PASSKEY',
  'AUTH_RECOVER_STEP_SUCCESS',
  'AUTH_RECOVER_STEP_VERIFY',
  'AUTH_RECOVER_VERIFIED_REGISTER_PASSKEY',
  'CANCEL',
  'CONTINUE',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($language !== '' ? $language : 'en', ENT_QUOTES, 'UTF-8'); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?php echo htmlspecialchars($i18n['AUTH_RECOVER_META_TITLE'], ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/'); ?>?v=<?php echo $assetVersion; ?>">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/utilities/'); ?>?v=<?php echo $assetVersion; ?>">
  <link rel="stylesheet" href="/css/auth-recovery/?v=<?php echo $assetVersion; ?>">
</head>
<body data-worker-version="<?php echo htmlspecialchars($assetVersion, ENT_QUOTES, 'UTF-8'); ?>">
  <main class="recovery-shell">
    <header class="recovery-header">
      <a class="recovery-back" href="/auth/<?php echo $authLanguageQuery; ?>">&lt; Back to sign in</a>
      <h1><?php echo htmlspecialchars($i18n['AUTH_RECOVER_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h1>
      <p><?php echo htmlspecialchars($i18n['AUTH_RECOVER_SUBHEADING'], ENT_QUOTES, 'UTF-8'); ?></p>
    </header>
    <section class="recovery-card">
      <section class="recovery-panel" data-step="1">
        <form id="recovery-start-form" class="recovery-email-form">
          <div class="recovery-field recovery-email-field">
            <label for="recovery-email">Email address</label>
            <div class="recovery-email-row">
              <input id="recovery-email" name="email" type="email" autocomplete="email" required aria-required="true" aria-describedby="recovery-status recovery-email-error">
              <button id="recovery-send-code" type="submit" class="btn btn_secondary">Send code</button>
            </div>
            <p class="recovery-field-error" id="recovery-email-error" role="alert" aria-live="polite"></p>
          </div>
          <p class="recovery-status" id="recovery-status" aria-live="assertive"><?php echo htmlspecialchars($hasMagicLinkToken ? $i18n['AUTH_RECOVER_STATUS_MAGIC_LINK'] : '', ENT_QUOTES, 'UTF-8'); ?></p>
        </form>
        <form id="recovery-verify-form" class="recovery-code-form">
          <div id="recovery-code-block" class="recovery-field">
            <label for="recovery-code">Verification code</label>
            <input id="recovery-code" name="code" type="text" autocomplete="one-time-code" maxlength="6" required aria-required="true" aria-describedby="recovery-code-error">
            <p class="recovery-hint">Sent to your email.</p>
            <p class="recovery-field-error" id="recovery-code-error" role="alert" aria-live="polite"></p>
          </div>
          <div id="recovery-key-block" class="recovery-field is-hidden">
            <label for="recovery-key">Recovery code</label>
            <input id="recovery-key" name="recoveryKey" type="text" autocomplete="off" spellcheck="false" maxlength="60" aria-describedby="recovery-key-error">
            <p class="recovery-hint">Saved when you secured your account.</p>
            <p class="recovery-field-error" id="recovery-key-error" role="alert" aria-live="polite"></p>
          </div>
          <div class="recovery-actions">
            <button type="submit" class="btn btn_primary" disabled aria-disabled="true">Verify and continue</button>
          </div>
        </form>
      </section>
      <section class="recovery-panel is-hidden" data-step="2">
        <p><?php echo htmlspecialchars($i18n['AUTH_RECOVER_VERIFIED_REGISTER_PASSKEY'], ENT_QUOTES, 'UTF-8'); ?></p>
        <p class="recovery-disclosure"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_DISCLOSURE'], ENT_QUOTES, 'UTF-8'); ?></p>
        <label for="recovery-device-name"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_NEW_PASSKEY_NAME'], ENT_QUOTES, 'UTF-8'); ?></label>
        <input id="recovery-device-name" name="deviceName" type="text" autocomplete="off" value="<?php echo htmlspecialchars($i18n['AUTH_RECOVER_DEFAULT_DEVICE_NAME'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="recovery-actions">
          <button type="button" id="recovery-register-passkey" class="btn btn_primary" disabled aria-disabled="true"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_REGISTER_NEW_PASSKEY'], ENT_QUOTES, 'UTF-8'); ?></button>
          <button type="button" id="recovery-cancel" class="btn btn_secondary"><?php echo htmlspecialchars($i18n['CANCEL'], ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
        <p class="recovery-hint is-prominent" id="recovery-existing-passkey-hint" aria-live="polite">
          <?php echo htmlspecialchars($i18n['AUTH_RECOVER_ALREADY_HAVE_PASSKEY'], ENT_QUOTES, 'UTF-8'); ?> <a href="/auth/" id="recovery-signin-instead"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_SIGN_IN_INSTEAD'], ENT_QUOTES, 'UTF-8'); ?></a>
        </p>
      </section>
      <section class="recovery-panel is-hidden" data-step="3">
        <h2><?php echo htmlspecialchars($i18n['AUTH_RECOVER_COMPLETE_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo htmlspecialchars($i18n['AUTH_RECOVER_COMPLETE_MESSAGE'], ENT_QUOTES, 'UTF-8'); ?></p>
        <a class="btn btn_primary" href="/"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_CONTINUE_TO_PAYCAL'], ENT_QUOTES, 'UTF-8'); ?></a>
      </section>
    </section>
  </main>
  <script type="module" src="/js/auth-recovery/?v=<?php echo $assetVersion; ?>" defer></script>
</body>
</html>
