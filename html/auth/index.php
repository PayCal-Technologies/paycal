<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_AUTH';

require_once __DIR__ . '/../config.php';

$requestUriRaw = $_SERVER['REQUEST_URI'] ?? '/auth/';
$requestUri = is_scalar($requestUriRaw) ? (string) $requestUriRaw : '/auth/';
$requestQueryRaw = parse_url($requestUri, PHP_URL_QUERY);
$requestQuery = [];
if (is_string($requestQueryRaw) && $requestQueryRaw !== '') {
  parse_str($requestQueryRaw, $requestQuery);
}

$requestedLanguage = strtolower(trim((string) (isset($requestQuery['l']) && is_scalar($requestQuery['l']) ? $requestQuery['l'] : '')));
$authLanguageQuery = '';

if ($requestedLanguage !== '' && in_array($requestedLanguage, Language::getCodes(), true)) {
  if (!defined('PAYCAL_PAGE_LANGUAGE_OVERRIDE')) {
    define('PAYCAL_PAGE_LANGUAGE_OVERRIDE', $requestedLanguage);
  }
  $authLanguageQuery = '?l=' . rawurlencode($requestedLanguage);
}

if (Authentication::validateAndTouchSession()) {
  // Normalize cookie scope for browsers holding stale /auth-scoped auth cookies.
  $sessionHash = Authentication::getCookie();
  if ($sessionHash !== '') {
    Authentication::setCookie($sessionHash);
  }

  header('Location: ' . Environment::appURL('/'));
  exit;
}

$authTabRaw = InputSanitizer::getString('auth_tab') ?? 'signin';
$authTab = $authTabRaw === 'register' ? 'register' : 'signin';

$signinMessage = InputSanitizer::getString('signin_message') ?? '';
$verificationSuccess = (InputSanitizer::getString('verification_success') ?? '') === '1';
$verificationError = InputSanitizer::getString('verification_error') ?? '';

$successMessage = '';
$errorMessage = '';
$emailValue = '';
$registerFullNameValue = '';
$registerEmailValue = '';
$registerInviteValue = '';

if ($signinMessage !== '') {
  $successMessage = $signinMessage;
  $authTab = 'signin';
}

if ($verificationError !== '') {
  $errorMessage = $verificationError;
  $authTab = 'signin';
}


$emailInput = InputSanitizer::postString('email');
$emailValue = htmlspecialchars(InputSanitizer::sanitizeEmail($emailInput), ENT_QUOTES, 'UTF-8');

$registerFullNameInput = InputSanitizer::postString('full_name');
$registerEmailInput = InputSanitizer::postString('register_email');
$registerInviteInput = InputSanitizer::postString('invite_code');

$registerFullNameValue = htmlspecialchars($registerFullNameInput, ENT_QUOTES, 'UTF-8');
$registerEmailValue = htmlspecialchars(InputSanitizer::sanitizeEmail($registerEmailInput), ENT_QUOTES, 'UTF-8');
$registerInviteValue = htmlspecialchars($registerInviteInput, ENT_QUOTES, 'UTF-8');

$cssVersion = (string) time();
$isRegisterTab = $authTab === 'register';
$accountRecoveryEnabled = filter_var(\PayCal\Domain\Config\SystemConfig::get('account_recovery_enabled'), FILTER_VALIDATE_BOOLEAN);
$siteName = Strings::headerI18n('SITE_NAME');

$i18nKeys = [
  'AUTH_BETA_NOTICE',
  'AUTH_DIVIDER_OR',
  'AUTH_PAGE_HEADING',
  'AUTH_PRIVACY_LINK',
  'AUTH_RECOVER_ACCOUNT',
  'AUTH_REGISTER_CREATE_BUTTON',
  'AUTH_REGISTER_DEVICE_LABEL',
  'AUTH_REGISTER_DEVICE_PLACEHOLDER',
  'AUTH_REGISTER_EMAIL_LABEL',
  'AUTH_REGISTER_FULL_NAME_LABEL',
  'AUTH_REGISTER_INVITE_LABEL',
  'AUTH_REGISTER_PANEL_ARIA',
  'AUTH_REGISTER_PASSKEY_STATUS',
  'AUTH_SIGNIN_EMAIL_ARIA',
  'AUTH_SIGNIN_EMAIL_LABEL',
  'AUTH_SIGNIN_OTHER_DEVICE',
  'AUTH_SIGNIN_PANEL_ARIA',
  'AUTH_SIGNIN_PASSKEY_BUTTON',
  'AUTH_SIGNIN_PASSKEY_STATUS',
  'AUTH_TAB_REGISTER',
  'AUTH_TAB_SIGNIN',
  'AUTH_TABS_ARIA',
  'AUTH_TERMS_ACK_AND',
  'AUTH_TERMS_ACK_PREFIX',
  'AUTH_TERMS_LINK',
  'AUTH_VERIFICATION_MESSAGE',
  'AUTH_VERIFICATION_STEP_1',
  'AUTH_VERIFICATION_STEP_2',
  'AUTH_VERIFICATION_STEP_3',
  'AUTH_VERIFICATION_TITLE',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

require_once __DIR__ . '/../header.php';
?>
  <div id="auth-feedback-banner" class="auth-feedback-banner" role="alert" aria-live="assertive" aria-atomic="true"></div>

  <div class="auth-container">
  <div class="auth-shell<?php echo $isRegisterTab ? ' is-register' : ''; ?>" id="auth-shell">
    <?php if ($errorMessage !== '') { ?>
      <p class="auth-message error" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>
    <?php if ($successMessage !== '') { ?>
      <p class="auth-message success" role="status"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>

    <div class="auth-layout">
      <section class="auth-hero" role="img" aria-roledescription="hero image" aria-label="<?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($i18n['AUTH_PAGE_HEADING'], ENT_QUOTES, 'UTF-8'); ?>">
        <img class="auth-hero-image" src="/images/paycal-auth-hero-win10.png" alt="" loading="eager" decoding="async" aria-hidden="true">
        <div class="auth-hero-overlay" aria-hidden="true"></div>
        <div class="auth-hero-content">
          <h1 class="visually_hidden"><?php echo htmlspecialchars($i18n['AUTH_PAGE_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="auth-hero-note"><?php echo htmlspecialchars($i18n['AUTH_BETA_NOTICE'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </section>

      <div class="auth-card">
        <div class="auth-tabs-wrapper">
          <div class="auth-tabs" role="tablist" aria-label="<?php echo htmlspecialchars($i18n['AUTH_TABS_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
            <button type="button" id="tab-signin" class="auth-tab<?php echo $isRegisterTab ? '' : ' active'; ?>" data-tab="signin" role="tab" aria-controls="panel-signin" aria-selected="<?php echo $isRegisterTab ? 'false' : 'true'; ?>"><?php echo htmlspecialchars($i18n['AUTH_TAB_SIGNIN'], ENT_QUOTES, 'UTF-8'); ?></button>
            <button type="button" id="tab-register" class="auth-tab<?php echo $isRegisterTab ? ' active' : ''; ?>" data-tab="register" role="tab" aria-controls="panel-register" aria-selected="<?php echo $isRegisterTab ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($i18n['AUTH_TAB_REGISTER'], ENT_QUOTES, 'UTF-8'); ?></button>
          </div>
        </div>

        <div class="auth-viewport">
        <div class="auth-track">
          <section class="auth-panel" id="panel-signin" role="tabpanel" aria-labelledby="tab-signin" aria-label="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_PANEL_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="<?php echo $isRegisterTab ? 'true' : 'false'; ?>"<?php echo $isRegisterTab ? ' inert' : ''; ?>>
            <?php if ($verificationSuccess): ?>
              <section class="auth-verification-panel" aria-labelledby="auth_verification_panel_title">
                <h2 id="auth_verification_panel_title" class="auth-verification-title"><?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_TITLE'], ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="auth-verification-message"><?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_MESSAGE'], ENT_QUOTES, 'UTF-8'); ?></p>
                <ul class="auth-verification-list">
                  <li><?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_STEP_1'], ENT_QUOTES, 'UTF-8'); ?></li>
                  <li><?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_STEP_2'], ENT_QUOTES, 'UTF-8'); ?></li>
                  <li><?php echo htmlspecialchars($i18n['AUTH_VERIFICATION_STEP_3'], ENT_QUOTES, 'UTF-8'); ?></li>
                </ul>
              </section>
            <?php endif; ?>

            <form id="signin-form" method="POST" action="/auth/<?php echo $authLanguageQuery; ?>">
              <section>
                <label for="email"><?php echo htmlspecialchars($i18n['AUTH_SIGNIN_EMAIL_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="email" id="email" name="email" value="<?php echo $emailValue; ?>" autocomplete="username webauthn" aria-label="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_EMAIL_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" required>
              </section>

              <section>
                <button id="signin-passkey" type="button" class="btn btn_primary" aria-label="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_PASSKEY_BUTTON'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($i18n['AUTH_SIGNIN_PASSKEY_BUTTON'], ENT_QUOTES, 'UTF-8'); ?></button>
                <p class="divider-or"><?php echo htmlspecialchars($i18n['AUTH_DIVIDER_OR'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p class="centered">
                  <button id="signin-passkey-phone" type="button" class="btn-link" aria-label="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_OTHER_DEVICE'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($i18n['AUTH_SIGNIN_OTHER_DEVICE'], ENT_QUOTES, 'UTF-8'); ?></button>
                </p>
                <p class="status" id="signin-passkey-status" role="status" aria-live="polite" aria-atomic="true"><?php echo htmlspecialchars($i18n['AUTH_SIGNIN_PASSKEY_STATUS'], ENT_QUOTES, 'UTF-8'); ?></p>
                <?php if ($accountRecoveryEnabled) { ?>
                  <hr class="auth-recover-divider" aria-hidden="true">
                  <p class="auth-recover-link"><a href="/auth/recover/<?php echo $authLanguageQuery; ?>"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_ACCOUNT'], ENT_QUOTES, 'UTF-8'); ?></a></p>
                <?php } ?>
              </section>

              <p>By signing in you agree to our <a href="/policies/#terms"><?php echo htmlspecialchars($i18n['AUTH_TERMS_LINK'], ENT_QUOTES, 'UTF-8'); ?></a> <?php echo htmlspecialchars($i18n['AUTH_TERMS_ACK_AND'], ENT_QUOTES, 'UTF-8'); ?> <a href="/policies/#privacy"><?php echo htmlspecialchars($i18n['AUTH_PRIVACY_LINK'], ENT_QUOTES, 'UTF-8'); ?></a>.</p>
            </form>
          </section>

          <section class="auth-panel" id="panel-register" role="tabpanel" aria-labelledby="tab-register" aria-label="<?php echo htmlspecialchars($i18n['AUTH_REGISTER_PANEL_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="<?php echo $isRegisterTab ? 'false' : 'true'; ?>"<?php echo $isRegisterTab ? '' : ' inert'; ?>>
            <form id="register-form" method="POST" action="/auth/<?php echo $authLanguageQuery; ?>">

              <section>
                <label for="register-full-name"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_FULL_NAME_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="register-full-name" name="full_name" value="<?php echo $registerFullNameValue; ?>" autocomplete="name" required>
              </section>

              <section>
                <label for="register-email"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_EMAIL_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="email" id="register-email" name="register_email" value="<?php echo $registerEmailValue; ?>" autocomplete="email" required>
              </section>

              <section>
                <label for="invite_code"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_INVITE_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="invite_code" name="invite_code" value="<?php echo $registerInviteValue; ?>" autocomplete="off">
              </section>

              <section>
                <label for="register-device-name"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_DEVICE_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="register-device-name" name="device_name" value="" placeholder="<?php echo htmlspecialchars($i18n['AUTH_REGISTER_DEVICE_PLACEHOLDER'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required>
              </section>

              <button id="register-passkey" type="button" class="btn btn_primary" aria-label="<?php echo htmlspecialchars($i18n['AUTH_REGISTER_CREATE_BUTTON'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_CREATE_BUTTON'], ENT_QUOTES, 'UTF-8'); ?></button>
              <p class="status" id="register-passkey-status" role="status" aria-live="polite" aria-atomic="true"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_PASSKEY_STATUS'], ENT_QUOTES, 'UTF-8'); ?></p>
            </form>
          </section>
        </div>
      </div>
      </div>
    </div>
  </div>
  </div>

  <script src="<?php echo Environment::appURL('js/signin/'); ?>?v=<?php echo $cssVersion; ?>" nonce="<?php echo User::nonce(); ?>"></script>

<?php require_once __DIR__ . '/../footer.php'; ?>
