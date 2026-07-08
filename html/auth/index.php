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

$authTabRaw = InputSanitizer::getString('auth_tab') ?? 'signin';
$authTab = $authTabRaw === 'register' ? 'register' : 'signin';
$signupTierRaw = strtolower(trim((string) (InputSanitizer::getString('tier') ?? '')));
$signupTier = in_array($signupTierRaw, ['free', 'premium', 'business'], true) ? $signupTierRaw : 'free';
if ($signupTierRaw !== '') {
  $authTab = 'register';
}

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
$immediateUiAllowed = EarlyAccessImmediateUi::signedOutAllowed();
$immediateUiRuntimeEnabled = EarlyAccessImmediateUi::runtimeEnabled();
$siteName = Strings::headerI18n('SITE_NAME');
$signupLanguage = $requestedLanguage !== '' ? $requestedLanguage : Language::DEFAULT;
$signupAccentKeys = ['blue', 'green', 'purple', 'amber', 'red', 'slate'];
$signupAccentPresets = array_intersect_key(UserPreferenceDefaults::accentPresets(), array_flip($signupAccentKeys));

$i18nKeys = [
  'AUTH_BETA_NOTICE',
  'AUTH_DIVIDER_OR',
  'AUTH_PAGE_HEADING',
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
  'AUTH_SIGNIN_EMAIL_PLACEHOLDER',
  'AUTH_SIGNIN_OTHER_DEVICE',
  'AUTH_SIGNIN_PANEL_ARIA',
  'AUTH_SIGNIN_PASSKEY_BUTTON',
  'AUTH_SIGNIN_PASSKEY_STATUS',
  'AUTH_TAB_REGISTER',
  'AUTH_TAB_SIGNIN',
  'AUTH_TABS_ARIA',
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
                <input type="email" id="email" name="email" value="<?php echo $emailValue; ?>" autocomplete="username webauthn" placeholder="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_EMAIL_PLACEHOLDER'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars($i18n['AUTH_SIGNIN_EMAIL_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" aria-describedby="signin-passkey-status signin_email_error signin-notice"<?php echo ($isRegisterTab || $verificationSuccess) ? '' : ' autofocus'; ?>>
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

          <section class="auth-panel" id="panel-register" role="tabpanel" aria-labelledby="tab-register" aria-label="<?php echo htmlspecialchars($i18n['AUTH_REGISTER_PANEL_ARIA'], ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="<?php echo $isRegisterTab ? 'false' : 'true'; ?>"<?php echo $isRegisterTab ? '' : ' inert'; ?>>
            <form id="register-form" method="POST" action="/auth/<?php echo $authLanguageQuery; ?>" data-signup-initial-tier="<?php echo htmlspecialchars($signupTier, ENT_QUOTES, 'UTF-8'); ?>" data-signup-initial-language="<?php echo htmlspecialchars($signupLanguage, ENT_QUOTES, 'UTF-8'); ?>">

              <section class="auth-signup-personalization" aria-labelledby="signup-personalization-heading">
                <div class="auth-signup-progress" aria-label="Signup steps">
                  <span class="is-active">Choose tier</span>
                  <span>Personalize</span>
                  <span>Secure account</span>
                </div>

                <h2 id="signup-personalization-heading">Make PayCal yours</h2>
                <p class="auth-signup-intro">This is the setup you'll start with. You can change it anytime.</p>

                <fieldset class="auth-signup-group auth-signup-tier-group">
                  <legend>Choose your PayCal</legend>
                  <div class="auth-tier-options" role="radiogroup" aria-label="Choose your PayCal tier">
                    <label class="auth-tier-card" data-signup-tier-card="free">
                      <input type="radio" name="signup_tier" value="free"<?php echo $signupTier === 'free' ? ' checked' : ''; ?>>
                      <span class="auth-tier-card-title">Free Personal</span>
                      <span class="auth-tier-card-copy">Personal work calendar, pay periods, and reports.</span>
                    </label>
                    <label class="auth-tier-card" data-signup-tier-card="premium">
                      <input type="radio" name="signup_tier" value="premium"<?php echo $signupTier === 'premium' ? ' checked' : ''; ?>>
                      <span class="auth-tier-card-title">Premium</span>
                      <span class="auth-tier-card-copy">Personal forecasting, exports, and advanced reports.</span>
                    </label>
                    <label class="auth-tier-card" data-signup-tier-card="business">
                      <input type="radio" name="signup_tier" value="business"<?php echo $signupTier === 'business' ? ' checked' : ''; ?>>
                      <span class="auth-tier-card-title">Business</span>
                      <span class="auth-tier-card-copy">Workspace setup for teams, sites, groups, and reports.</span>
                    </label>
                  </div>
                </fieldset>

                <fieldset class="auth-signup-group">
                  <legend>Personalize your PayCal</legend>

                  <div class="auth-signup-control">
                    <span class="auth-signup-control-label" id="signup-theme-mode-label">Theme</span>
                    <div class="auth-segmented" role="radiogroup" aria-labelledby="signup-theme-mode-label">
                      <label><input type="radio" name="signup_theme_mode" value="light"><span>Light</span></label>
                      <label><input type="radio" name="signup_theme_mode" value="dark"><span>Dark</span></label>
                      <label><input type="radio" name="signup_theme_mode" value="system" checked><span>System</span></label>
                    </div>
                  </div>

                  <div class="auth-signup-control">
                    <span class="auth-signup-control-label" id="signup-accent-label">Accent</span>
                    <div class="auth-accent-options" id="signup-accent-options" role="group" aria-labelledby="signup-accent-label">
                      <?php foreach ($signupAccentPresets as $accentKey => $accentSpec) {
                        $accentIndex = array_search($accentKey, array_keys(UserPreferenceDefaults::accentPresets()), true);
                        if (!is_int($accentIndex)) {
                          $accentIndex = 0;
                        }
                      ?>
                        <button
                          type="button"
                          class="auth-accent-swatch settings_accent_swatch"
                          data-accent-idx="<?php echo $accentIndex; ?>"
                          data-signup-accent="<?php echo htmlspecialchars((string) $accentKey, ENT_QUOTES, 'UTF-8'); ?>"
                          aria-label="<?php echo htmlspecialchars((string) $accentSpec['label'], ENT_QUOTES, 'UTF-8'); ?>"
                          aria-pressed="<?php echo $accentKey === UserPreferenceDefaults::DEFAULT_ACCENT_PRESET ? 'true' : 'false'; ?>"
                          title="<?php echo htmlspecialchars((string) $accentSpec['label'], ENT_QUOTES, 'UTF-8'); ?>"
                        ><span><?php echo htmlspecialchars((string) $accentSpec['label'], ENT_QUOTES, 'UTF-8'); ?></span></button>
                      <?php } ?>
                    </div>
                  </div>

                  <div class="auth-signup-control">
                    <span class="auth-signup-control-label" id="signup-text-size-label">Text size</span>
                    <div class="auth-segmented" role="radiogroup" aria-labelledby="signup-text-size-label">
                      <label><input type="radio" name="signup_text_size" value="standard" checked><span>Standard</span></label>
                      <label><input type="radio" name="signup_text_size" value="larger"><span>Larger</span></label>
                    </div>
                  </div>

                  <div class="auth-signup-control">
                    <span class="auth-signup-control-label" id="signup-spacing-label">Calendar feel</span>
                    <div class="auth-segmented" role="radiogroup" aria-labelledby="signup-spacing-label">
                      <label><input type="radio" name="signup_spacing" value="compact"><span>Compact</span></label>
                      <label><input type="radio" name="signup_spacing" value="comfortable" checked><span>Comfortable</span></label>
                    </div>
                  </div>

                  <div class="auth-signup-grid">
                    <label for="signup-language">Language</label>
                    <select id="signup-language" name="signup_language">
                      <?php foreach (Language::AVAILABLE as $languageCode => $languageLabel) { ?>
                        <option value="<?php echo htmlspecialchars((string) $languageCode, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $signupLanguage === $languageCode ? ' selected' : ''; ?>><?php echo htmlspecialchars((string) $languageLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                      <?php } ?>
                    </select>

                    <label for="signup-pay-frequency">Pay rhythm</label>
                    <select id="signup-pay-frequency" name="signup_pay_frequency">
                      <option value="weekly">Weekly</option>
                      <option value="biweekly" selected>Biweekly</option>
                      <option value="semimonthly">Semimonthly</option>
                      <option value="monthly">Monthly</option>
                    </select>
                  </div>

                  <div class="auth-signup-control">
                    <span class="auth-signup-control-label" id="signup-intent-label">First use</span>
                    <div class="auth-segmented" role="radiogroup" aria-labelledby="signup-intent-label">
                      <label><input type="radio" name="signup_intent" value="worker" checked><span>Worker</span></label>
                      <label><input type="radio" name="signup_intent" value="manager"><span>Manager</span></label>
                      <label><input type="radio" name="signup_intent" value="business"><span>Business</span></label>
                    </div>
                  </div>

                  <div class="auth-signup-grid">
                    <label for="signup-dashboard-name">Calendar or workspace name <span>(optional)</span></label>
                    <input type="text" id="signup-dashboard-name" name="signup_dashboard_name" maxlength="64" autocomplete="organization" placeholder="My PayCal">
                  </div>
                </fieldset>

                <section class="auth-signup-preview" aria-labelledby="signup-preview-title" aria-live="polite">
                  <p class="auth-signup-preview-kicker" data-signup-tier-confirm>You chose Free Personal.</p>
                  <h3 id="signup-preview-title" data-signup-preview-title>Your PayCal</h3>
                  <p data-signup-preview-meta>System theme - Blue accent - Biweekly pay</p>
                  <ul data-signup-preview-list>
                    <li>Comfortable calendar spacing</li>
                    <li>Standard text size</li>
                    <li>Worker setup</li>
                  </ul>
                  <div class="auth-signup-preview-calendar" aria-hidden="true">
                    <div>Mon</div><div>Tue</div><div>Wed</div><div>Thu</div><div>Fri</div><div>Sat</div><div>Sun</div>
                    <strong>6h</strong><strong>8h</strong><span>--</span><strong>10h</strong><strong>8h</strong><span>--</span><span>--</span>
                  </div>
                </section>
              </section>

              <section>
                <label for="register-full-name"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_FULL_NAME_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="register-full-name" name="full_name" value="<?php echo $registerFullNameValue; ?>" autocomplete="name" required aria-required="true" aria-describedby="register-passkey-status register_full_name_error">
                <p id="register_full_name_error" class="auth-field-error" role="alert" aria-live="polite"></p>
              </section>

              <section>
                <label for="register-email"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_EMAIL_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="email" id="register-email" name="register_email" value="<?php echo $registerEmailValue; ?>" autocomplete="email" required aria-required="true" aria-describedby="register-passkey-status register_email_error">
                <p id="register_email_error" class="auth-field-error" role="alert" aria-live="polite"></p>
              </section>

              <section>
                <label for="invite_code"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_INVITE_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="invite_code" name="invite_code" value="<?php echo $registerInviteValue; ?>" autocomplete="off" aria-describedby="register-passkey-status register_invite_code_error">
                <p id="register_invite_code_error" class="auth-field-error" role="alert" aria-live="polite"></p>
              </section>

              <section>
                <label for="register-device-name"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_DEVICE_LABEL'], ENT_QUOTES, 'UTF-8'); ?></label>
                <input type="text" id="register-device-name" name="device_name" value="" placeholder="<?php echo htmlspecialchars($i18n['AUTH_REGISTER_DEVICE_PLACEHOLDER'], ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off" required aria-required="true" aria-describedby="register-passkey-status register_device_name_error">
                <p id="register_device_name_error" class="auth-field-error" role="alert" aria-live="polite"></p>
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

  <script
    type="module"
    src="<?php echo Environment::appURL('js/signin/'); ?>?v=<?php echo $cssVersion; ?>"
    nonce="<?php echo User::nonce(); ?>"
    data-immediate-ui-allowed="<?php echo $immediateUiAllowed ? '1' : '0'; ?>"
    data-immediate-ui-runtime-enabled="<?php echo $immediateUiRuntimeEnabled ? '1' : '0'; ?>"
  ></script>

<?php require_once __DIR__ . '/../footer.php'; ?>
