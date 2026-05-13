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
      <a class="recovery-back" href="/auth/<?php echo $authLanguageQuery; ?>"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_BACK_TO_SIGNIN'], ENT_QUOTES, 'UTF-8'); ?></a>
      <h1><?php echo htmlspecialchars($i18n['AUTH_RECOVER_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h1>
      <p><?php echo htmlspecialchars($i18n['AUTH_RECOVER_INTRO_LINE_1'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($i18n['AUTH_RECOVER_INTRO_LINE_2'], ENT_QUOTES, 'UTF-8'); ?></p>
    </header>
    <section class="recovery-card">
      <ol class="recovery-steps" aria-label="<?php echo htmlspecialchars($i18n['AUTH_RECOVER_STEPS_ARIA_LABEL'], ENT_QUOTES, 'UTF-8'); ?>">
        <li<?php echo $hasMagicLinkToken ? '' : ' class="is-active"'; ?> data-step-indicator="1"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_STEP_VERIFY'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li<?php echo $hasMagicLinkToken ? ' class="is-active"' : ''; ?> data-step-indicator="2"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_STEP_PASSKEY'], ENT_QUOTES, 'UTF-8'); ?></li>
        <li data-step-indicator="3"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_STEP_SUCCESS'], ENT_QUOTES, 'UTF-8'); ?></li>
      </ol>
      <p class="recovery-status" id="recovery-status" aria-live="assertive"><?php echo htmlspecialchars($hasMagicLinkToken ? $i18n['AUTH_RECOVER_STATUS_MAGIC_LINK'] : $i18n['AUTH_RECOVER_STATUS_START'], ENT_QUOTES, 'UTF-8'); ?></p>
      <section class="recovery-panel<?php echo $hasMagicLinkToken ? ' is-hidden' : ''; ?>" data-step="1">
        <form id="recovery-start-form">
          <label for="recovery-email"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_ACCOUNT_EMAIL'], ENT_QUOTES, 'UTF-8'); ?></label>
          <input id="recovery-email" name="email" type="email" autocomplete="email" required>
          <button id="recovery-send-code" type="submit" class="btn btn_primary"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_SEND_CODE'], ENT_QUOTES, 'UTF-8'); ?></button>
        </form>
        <form id="recovery-verify-form" class="is-hidden">
          <div id="recovery-code-block">
            <label for="recovery-code"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_CODE_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
            <input id="recovery-code" name="code" type="text" autocomplete="one-time-code" maxlength="6" required>
          </div>
          <label for="recovery-key"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_KEY_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
          <input id="recovery-key" name="recoveryKey" type="text" autocomplete="off" spellcheck="false" required>
          <div class="recovery-actions">
            <button type="button" id="recovery-back-signin" class="btn btn_secondary"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_BACK_TO_SIGNIN_BUTTON'], ENT_QUOTES, 'UTF-8'); ?></button>
            <button type="submit" class="btn btn_primary"><?php echo htmlspecialchars($i18n['CONTINUE'], ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
        </form>
      </section>
      <section class="recovery-panel<?php echo $hasMagicLinkToken ? '' : ' is-hidden'; ?>" data-step="2">
        <p><?php echo htmlspecialchars($i18n['AUTH_RECOVER_VERIFIED_REGISTER_PASSKEY'], ENT_QUOTES, 'UTF-8'); ?></p>
        <label for="recovery-device-name"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_NEW_PASSKEY_NAME'], ENT_QUOTES, 'UTF-8'); ?></label>
        <input id="recovery-device-name" name="deviceName" type="text" autocomplete="off" value="<?php echo htmlspecialchars($i18n['AUTH_RECOVER_DEFAULT_DEVICE_NAME'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="recovery-actions">
          <button type="button" id="recovery-register-passkey" class="btn btn_primary"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_REGISTER_NEW_PASSKEY'], ENT_QUOTES, 'UTF-8'); ?></button>
          <button type="button" id="recovery-cancel" class="btn btn_secondary"><?php echo htmlspecialchars($i18n['CANCEL'], ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
        <p class="recovery-hint" id="recovery-existing-passkey-hint" aria-live="polite">
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
  <script src="/js/auth-recovery/?v=<?php echo $assetVersion; ?>" defer></script>
</body>
</html>
