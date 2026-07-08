<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_AUTH';

require_once __DIR__ . '/../config.php';

\PayCal\Observability\Lens::boot('auth');

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

$authTabRaw = InputSanitizer::getString('auth_tab') ?? '';
$signupTierRaw = strtolower(trim((string) (InputSanitizer::getString('tier') ?? '')));
if ($authTabRaw === 'register' || $signupTierRaw !== '') {
  $signupQuery = [];
  if (in_array($signupTierRaw, ['free', 'premium', 'business'], true)) {
    $signupQuery['tier'] = $signupTierRaw;
  }
  if ($requestedLanguage !== '') {
    $signupQuery['l'] = $requestedLanguage;
  }

  $signupRedirect = '/auth/signup/';
  if ($signupQuery !== []) {
    $signupRedirect .= '?' . http_build_query($signupQuery, '', '&', PHP_QUERY_RFC3986);
  }
  header('Location: ' . Environment::appURL($signupRedirect), true, 302);
  exit;
}

$signinMessage = InputSanitizer::getString('signin_message') ?? '';
$verificationSuccess = (InputSanitizer::getString('verification_success') ?? '') === '1';
$verificationError = InputSanitizer::getString('verification_error') ?? '';

$successMessage = '';
$errorMessage = '';

if ($signinMessage !== '') {
  $successMessage = $signinMessage;
}

if ($verificationError !== '') {
  $errorMessage = $verificationError;
}

$emailInput = InputSanitizer::postString('email');
$emailValue = htmlspecialchars(InputSanitizer::sanitizeEmail($emailInput), ENT_QUOTES, 'UTF-8');

$cssVersion = (string) time();
$accountRecoveryEnabled = filter_var(\PayCal\Domain\Config\SystemConfig::get('account_recovery_enabled'), FILTER_VALIDATE_BOOLEAN);
$immediateUiAllowed = EarlyAccessImmediateUi::signedOutAllowed();
$immediateUiRuntimeEnabled = EarlyAccessImmediateUi::runtimeEnabled();
$siteName = Strings::headerI18n('SITE_NAME');
$signupUrl = '/auth/signup/' . $authLanguageQuery;

$i18nKeys = [
  'AUTH_BETA_NOTICE',
  'AUTH_PAGE_HEADING',
  'AUTH_RECOVER_ACCOUNT',
  'AUTH_SIGNIN_EMAIL_ARIA',
  'AUTH_SIGNIN_EMAIL_LABEL',
  'AUTH_SIGNIN_EMAIL_PLACEHOLDER',
  'AUTH_SIGNIN_OTHER_DEVICE',
  'AUTH_SIGNIN_PANEL_ARIA',
  'AUTH_SIGNIN_PASSKEY_BUTTON',
  'AUTH_SIGNIN_PASSKEY_STATUS',
  'AUTH_TAB_SIGNIN',
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
  <div class="auth-shell is-signin-only" id="auth-shell">
    <?php if ($errorMessage !== '') { ?>
      <p class="auth-message error" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>
    <?php if ($successMessage !== '') { ?>
      <p class="auth-message success" role="status"><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>

    <div class="auth-layout">
      <section class="auth-hero" role="img" aria-roledescription="hero image" aria-label="<?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?> - <?php echo htmlspecialchars($i18n['AUTH_PAGE_HEADING'], ENT_QUOTES, 'UTF-8'); ?>">
        <picture>
          <source srcset="/images/paycal-auth-hero-win10.webp" type="image/webp">
          <img class="auth-hero-image" src="/images/paycal-auth-hero-win10.jpg" alt="" loading="eager" decoding="async" aria-hidden="true">
        </picture>
        <div class="auth-hero-overlay" aria-hidden="true"></div>
        <div class="auth-hero-content">
          <h1 class="visually_hidden"><?php echo htmlspecialchars($i18n['AUTH_PAGE_HEADING'], ENT_QUOTES, 'UTF-8'); ?></h1>
          <p class="auth-hero-note"><?php echo htmlspecialchars($i18n['AUTH_BETA_NOTICE'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
      </section>

      <div class="auth-card auth-signin-card">
        <div class="auth-card-heading">
          <h2><?php echo htmlspecialchars($i18n['AUTH_TAB_SIGNIN'], ENT_QUOTES, 'UTF-8'); ?></h2>
        </div>

        <div class="auth-viewport">
        <div class="auth-track">
          <section class="auth-panel" id="panel-signin" aria-label="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_PANEL_ARIA'], ENT_QUOTES, 'UTF-8'); ?>">
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
                <input type="email" id="email" name="email" value="<?php echo $emailValue; ?>" autocomplete="username webauthn" placeholder="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_EMAIL_PLACEHOLDER'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_EMAIL_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" aria-describedby="signin-passkey-status signin_email_error signin-notice"<?php echo $verificationSuccess ? '' : ' autofocus'; ?>>
                <p id="signin_email_error" class="auth-field-error" role="alert" aria-live="polite"></p>
              </section>

              <section class="auth-signin-primary">
                <button id="signin-passkey" type="button" class="btn btn_primary" aria-label="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_PASSKEY_BUTTON'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($i18n['AUTH_SIGNIN_PASSKEY_BUTTON'], ENT_QUOTES, 'UTF-8'); ?></button>
                <button id="signin-passkey-phone" type="button" class="btn btn_secondary" aria-label="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_OTHER_DEVICE'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($i18n['AUTH_SIGNIN_OTHER_DEVICE'], ENT_QUOTES, 'UTF-8'); ?></button>
              </section>

              <p class="status" id="signin-passkey-status" role="status" aria-live="polite" aria-atomic="true"><?php echo htmlspecialchars($i18n['AUTH_SIGNIN_PASSKEY_STATUS'], ENT_QUOTES, 'UTF-8'); ?></p>
              <div id="signin-notice" class="auth-signin-notice" role="status" aria-live="polite" hidden></div>
              <div id="signin-error-actions" class="auth-signin-error-actions" hidden></div>

              <section class="auth-signin-alternate">
                <hr class="auth-signin-divider" role="separator" aria-hidden="true">
                <p class="auth-account-switch">New? <a href="<?php echo htmlspecialchars($signupUrl, ENT_QUOTES, 'UTF-8'); ?>">Sign up.</a></p>
                <div id="federated-signin" class="federated-signin" hidden>
                  <div id="federated-signin-providers" class="federated-signin-providers"></div>
                </div>
                <?php if ($accountRecoveryEnabled) { ?>
                  <hr class="auth-signin-divider" role="separator" aria-hidden="true">
                  <p class="auth-recover-link"><a href="/auth/recover/<?php echo $authLanguageQuery; ?>"><?php echo htmlspecialchars($i18n['AUTH_RECOVER_ACCOUNT'], ENT_QUOTES, 'UTF-8'); ?></a></p>
                <?php } ?>
              </section>
            </form>
          </section>
        </div>
      </div>
      </div>
    </div>
  </div>
  </div>

  <script
    type="module"
    src="<?php echo Environment::appURL('js/signin/'); ?>?v=<?php echo $cssVersion; ?>"
    nonce="<?php echo User::nonce(); ?>"
    data-immediate-ui-allowed="<?php echo $immediateUiAllowed ? '1' : '0'; ?>"
    data-immediate-ui-runtime-enabled="<?php echo $immediateUiRuntimeEnabled ? '1' : '0'; ?>"
  ></script>

<?php require_once __DIR__ . '/../footer.php'; ?>
