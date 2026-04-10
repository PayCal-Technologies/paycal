<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

require_once '../config.php';

// Verify user is authenticated but unverified
Authentication::redirectHomeIfUnauthenticated();

$currentUser = User::current();
if ($currentUser->email_verified ?? false) {
  header('Location: ' . Environment::appURL('/'));
  exit;
}

$cssVersion = Environment::appVersion();
$userEmail = htmlspecialchars($currentUser->email ?? 'user@paycal.app', ENT_QUOTES, 'UTF-8');
$verificationError = InputSanitizer::getString('verification_error') ?? '';
$i18nKeys = [
  'CONTACT',
  'HELP',
  'PROFILE',
  'SETTINGS',
  'SITE_NAME',
  'UNVERIFIED_CODE_PLACEHOLDER',
  'UNVERIFIED_ENTER_CODE_LABEL',
  'UNVERIFIED_HEADING',
  'UNVERIFIED_LOGOUT',
  'UNVERIFIED_LOGOUT_ARIA',
  'UNVERIFIED_META_TITLE',
  'UNVERIFIED_PRIVACY_LINK',
  'UNVERIFIED_PROFILE_MENU_ARIA',
  'UNVERIFIED_RESEND_BUTTON',
  'UNVERIFIED_RESEND_PROMPT',
  'UNVERIFIED_SENT_CODE_TO',
  'UNVERIFIED_TERMS_LINK',
  'UNVERIFIED_VERIFY_BUTTON',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title><?php echo htmlspecialchars($i18n['UNVERIFIED_META_TITLE'], ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/'); ?>?v=<?php echo $cssVersion; ?>">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/utilities/'); ?>?v=<?php echo $cssVersion; ?>">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/unverified/'); ?>?v=<?php echo $cssVersion; ?>">
</head>
<body>
  <header>
    <div class="header-logo"><?php echo htmlspecialchars($i18n['SITE_NAME'], ENT_QUOTES, 'UTF-8'); ?></div>
    <div class="header-profile">
      <button class="profile-btn" id="profile-btn" aria-label="<?php echo htmlspecialchars($i18n['UNVERIFIED_PROFILE_MENU_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" aria-haspopup="menu" aria-controls="profile-menu" aria-expanded="false"><?php echo htmlspecialchars($i18n['PROFILE'], ENT_QUOTES, 'UTF-8'); ?></button>
      <nav class="profile-menu" id="profile-menu" aria-label="<?php echo htmlspecialchars($i18n['UNVERIFIED_PROFILE_MENU_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
        <a href="/settings/"><?php echo htmlspecialchars($i18n['SETTINGS'], ENT_QUOTES, 'UTF-8'); ?></a>
        <a href="/help/"><?php echo htmlspecialchars($i18n['HELP'], ENT_QUOTES, 'UTF-8'); ?></a>
        <button id="logout-btn" aria-label="<?php echo htmlspecialchars($i18n['UNVERIFIED_LOGOUT_ARIA'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($i18n['UNVERIFIED_LOGOUT'], ENT_QUOTES, 'UTF-8'); ?></button>
      </nav>
    </div>
  </header>

  <main>
    <div class="verification-shell">
      <div class="verification-card">
        <h1><?php echo htmlspecialchars($i18n['UNVERIFIED_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h1>
        <?php if ($verificationError !== '') { ?>
          <p class="verification-message verification-message-error" role="alert"><?php echo htmlspecialchars($verificationError, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php } ?>
        <p class="description"><?php echo htmlspecialchars($i18n['UNVERIFIED_SENT_CODE_TO'], ENT_QUOTES, 'UTF-8'); ?></p>
        <span class="email"><?php echo $userEmail; ?></span>

        <form id="verification-form" class="verification-form" method="GET" action="/auth/verify-email/">
          <section>
            <label for="code-input"><?php echo htmlspecialchars($i18n['UNVERIFIED_ENTER_CODE_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
            <input
              type="text"
              id="code-input"
              name="code"
              placeholder="<?php echo htmlspecialchars($i18n['UNVERIFIED_CODE_PLACEHOLDER'], ENT_QUOTES, 'UTF-8'); ?>"
              maxlength="64"
              required
              autocomplete="off"
              autofocus
            >
          </section>

          <button type="submit" class="btn btn-primary"><?php echo htmlspecialchars($i18n['UNVERIFIED_VERIFY_BUTTON'], ENT_QUOTES, 'UTF-8'); ?></button>

          <p class="status is-hidden" id="form-status" role="status" aria-live="polite"></p>
        </form>

        <p class="resend-link">
          <?php echo htmlspecialchars($i18n['UNVERIFIED_RESEND_PROMPT'], ENT_QUOTES, 'UTF-8'); ?> <button type="button" id="resend-email-link"><?php echo htmlspecialchars($i18n['UNVERIFIED_RESEND_BUTTON'], ENT_QUOTES, 'UTF-8'); ?></button>
        </p>
      </div>
    </div>
  </main>

  <footer>
    <a href="/policies/#privacy"><?php echo htmlspecialchars($i18n['UNVERIFIED_PRIVACY_LINK'], ENT_QUOTES, 'UTF-8'); ?></a> · <a href="/policies/#terms"><?php echo htmlspecialchars($i18n['UNVERIFIED_TERMS_LINK'], ENT_QUOTES, 'UTF-8'); ?></a> · <a href="/contact/"><?php echo htmlspecialchars($i18n['CONTACT'], ENT_QUOTES, 'UTF-8'); ?></a>
  </footer>

  <?php $verificationReminderSriAttribute = Render::sriAttribute('js/signin/verification-reminder.js'); ?>
  <script src="<?php echo Environment::appURL('js/signin/verification-reminder.js'); ?>?v=<?php echo $cssVersion; ?>"<?php echo $verificationReminderSriAttribute; ?>></script>
</body>
</html>
