<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_REGISTER';
$pageTitle = 'Create a PayCal account - [PayCal]';
$pageMetaDescription = 'Create a PayCal account, choose a plan, personalize your settings, and set up a secure passkey.';
$pageMetaDescriptionLong = 'Create a PayCal account with a guided setup for your plan, display preferences, pay rhythm, workspace name, and passkey security.';

require_once __DIR__ . '/../../config.php';

\PayCal\Observability\Lens::boot('auth');

$requestUriRaw = $_SERVER['REQUEST_URI'] ?? '/auth/signup/';
$requestUri = is_scalar($requestUriRaw) ? (string) $requestUriRaw : '/auth/signup/';
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
  $sessionHash = Authentication::getCookie();
  if ($sessionHash !== '') {
    Authentication::setCookie($sessionHash);
  }

  header('Location: ' . Environment::appURL('/'));
  exit;
}

$signupTierRaw = strtolower(trim((string) (InputSanitizer::getString('tier') ?? '')));
$signupTier = in_array($signupTierRaw, ['free', 'premium', 'business'], true) ? $signupTierRaw : 'free';
$signupLanguage = $requestedLanguage !== '' ? $requestedLanguage : Language::DEFAULT;

$registerFullNameInput = InputSanitizer::postString('full_name');
$registerEmailInput = InputSanitizer::postString('register_email');
$registerInviteInput = InputSanitizer::postString('invite_code');

$registerFullNameValue = htmlspecialchars($registerFullNameInput, ENT_QUOTES, 'UTF-8');
$registerEmailValue = htmlspecialchars(InputSanitizer::sanitizeEmail($registerEmailInput), ENT_QUOTES, 'UTF-8');
$registerInviteValue = htmlspecialchars($registerInviteInput, ENT_QUOTES, 'UTF-8');
$signupError = InputSanitizer::getString('signup_error') ?? InputSanitizer::getString('signin_error') ?? '';

$cssVersion = (string) time();
$signinUrl = '/auth/' . $authLanguageQuery;
$signupAccentKeys = ['blue', 'green', 'purple', 'amber', 'red', 'slate'];
$allAccentPresets = UserPreferenceDefaults::accentPresets();
$signupAccentPresets = array_intersect_key($allAccentPresets, array_flip($signupAccentKeys));
$signupAccentPresetKeys = array_keys($allAccentPresets);
$immediateUiAllowed = false;
$immediateUiRuntimeEnabled = false;

$i18nKeys = [
  'AUTH_REGISTER_CREATE_BUTTON',
  'AUTH_REGISTER_DEVICE_LABEL',
  'AUTH_REGISTER_DEVICE_PLACEHOLDER',
  'AUTH_REGISTER_EMAIL_LABEL',
  'AUTH_REGISTER_FULL_NAME_LABEL',
  'AUTH_REGISTER_INVITE_LABEL',
  'AUTH_REGISTER_PASSKEY_STATUS',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

require_once __DIR__ . '/../../header.php';
?>
  <div id="auth-feedback-banner" class="auth-feedback-banner" role="alert" aria-live="assertive" aria-atomic="true"></div>

  <div class="auth-container auth-create-container">
    <div class="auth-shell auth-create-shell is-register" id="auth-shell">
      <?php if ($signupError !== '') { ?>
        <p class="auth-message error" role="alert"><?php echo htmlspecialchars($signupError, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php } ?>

      <div class="auth-create-layout">
        <section class="auth-card auth-create-card" aria-labelledby="create-account-heading">
          <div class="auth-create-header">
            <p class="auth-create-kicker">Create account</p>
            <h1 id="create-account-heading">Set up your PayCal</h1>
            <p>Choose a tier, tune the workspace, then create your passkey.</p>
            <a class="auth-account-switch" href="<?php echo htmlspecialchars($signinUrl, ENT_QUOTES, 'UTF-8'); ?>">Have an account? Sign in.</a>
          </div>

          <form
            id="register-form"
            class="auth-panel auth-create-form"
            method="POST"
            action="/auth/signup/<?php echo $authLanguageQuery; ?>"
            data-signup-initial-tier="<?php echo htmlspecialchars($signupTier, ENT_QUOTES, 'UTF-8'); ?>"
            data-signup-initial-language="<?php echo htmlspecialchars($signupLanguage, ENT_QUOTES, 'UTF-8'); ?>"
            data-signup-current-step="tier"
            novalidate
          >
            <div class="auth-signup-progress" aria-label="Create account steps">
              <button type="button" class="is-active" data-signup-step-trigger="tier" aria-current="step">Choose tier</button>
              <button type="button" data-signup-step-trigger="personalize">Personalize</button>
              <button type="button" data-signup-step-trigger="secure">Secure account</button>
            </div>

            <section class="auth-signup-step is-active" data-signup-step="tier" aria-labelledby="signup-tier-heading" aria-hidden="false">
              <div class="auth-signup-step-heading">
                <h2 id="signup-tier-heading" data-signup-step-heading tabindex="-1">Choose your PayCal</h2>
                <p class="auth-signup-intro">The pricing tiers are built into setup so your account starts on the right track.</p>
              </div>

              <fieldset class="auth-signup-group auth-signup-tier-group">
                <legend>Pick a tier</legend>
                <div class="auth-tier-options" role="radiogroup" aria-label="Choose your PayCal tier">
                  <label class="auth-tier-card" data-signup-tier-card="free">
                    <input type="radio" name="signup_tier" value="free"<?php echo $signupTier === 'free' ? ' checked' : ''; ?>>
                    <span class="auth-tier-card-body">
                      <span class="auth-tier-card-title">Free Personal</span>
                      <span class="auth-tier-card-price">$0 <span>CAD / month</span></span>
                      <span class="auth-tier-card-copy">Personal work calendar, pay periods, and reports.</span>
                    </span>
                  </label>
                  <label class="auth-tier-card" data-signup-tier-card="premium">
                    <input type="radio" name="signup_tier" value="premium"<?php echo $signupTier === 'premium' ? ' checked' : ''; ?>>
                    <span class="auth-tier-card-body">
                      <span class="auth-tier-card-title">Premium</span>
                      <span class="auth-tier-card-price">$4.99 <span>CAD / month</span></span>
                      <span class="auth-tier-card-copy">Personal forecasting, exports, and advanced reports.</span>
                    </span>
                  </label>
                  <label class="auth-tier-card" data-signup-tier-card="business">
                    <input type="radio" name="signup_tier" value="business"<?php echo $signupTier === 'business' ? ' checked' : ''; ?>>
                    <span class="auth-tier-card-body">
                      <span class="auth-tier-card-title">Business</span>
                      <span class="auth-tier-card-price">$29.99 <span>CAD / month total</span></span>
                      <span class="auth-tier-card-copy">Workspace setup for teams, sites, groups, and reports.</span>
                      <span class="auth-tier-card-badge">Compare: adds shared workspace.</span>
                    </span>
                  </label>
                </div>
              </fieldset>

              <div class="auth-step-actions">
                <button type="button" class="btn btn_primary" data-signup-next="personalize">Continue</button>
              </div>
            </section>

            <section class="auth-signup-step" data-signup-step="personalize" aria-labelledby="signup-personalize-heading" aria-hidden="true" hidden inert>
              <div class="auth-signup-step-heading">
                <h2 id="signup-personalize-heading" data-signup-step-heading tabindex="-1">Pick your settings</h2>
                <p class="auth-signup-intro">This is the setup you'll start with. You can change it anytime.</p>
              </div>

              <fieldset class="auth-signup-group">
                <legend>Theme and display</legend>

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
                      $accentIndex = array_search($accentKey, $signupAccentPresetKeys, true);
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
              </fieldset>

              <fieldset class="auth-signup-group">
                <legend>Calendar defaults</legend>

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

              <div class="auth-step-actions">
                <button type="button" class="btn btn_secondary" data-signup-back="tier">Back</button>
                <button type="button" class="btn btn_primary" data-signup-next="secure">Continue</button>
              </div>
            </section>

            <section class="auth-signup-step" data-signup-step="secure" aria-labelledby="signup-secure-heading" aria-hidden="true" hidden inert>
              <div class="auth-signup-step-heading">
                <h2 id="signup-secure-heading" data-signup-step-heading tabindex="-1">Create your passkey</h2>
                <p class="auth-signup-intro">Your passkey signs you in without a password and stays protected by your device.</p>
              </div>

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

              <div class="auth-step-actions">
                <button type="button" class="btn btn_secondary" data-signup-back="personalize">Back</button>
                <button id="register-passkey" type="button" class="btn btn_primary" aria-label="<?php echo htmlspecialchars($i18n['AUTH_REGISTER_CREATE_BUTTON'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_CREATE_BUTTON'], ENT_QUOTES, 'UTF-8'); ?></button>
              </div>
              <p class="status" id="register-passkey-status" role="status" aria-live="polite" aria-atomic="true"><?php echo htmlspecialchars($i18n['AUTH_REGISTER_PASSKEY_STATUS'], ENT_QUOTES, 'UTF-8'); ?></p>
            </section>
          </form>
        </section>

        <aside class="auth-signup-preview auth-create-preview" aria-labelledby="signup-preview-title" aria-live="polite">
          <p class="auth-signup-preview-kicker" data-signup-tier-confirm>Your starting setup: Free Personal.</p>
          <h2 id="signup-preview-title" data-signup-preview-title>Your PayCal</h2>
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
        </aside>
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

<?php require_once __DIR__ . '/../../footer.php'; ?>
