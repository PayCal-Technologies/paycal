<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Contract: frontend/backend response shapes are flat and do not rely on
 * compatibility fallbacks for deprecated nested data envelopes.
 */
#[Group('contract')]
final class NetworkTrafficContractTest extends TestCase
{
  public function testCalendarControllerDoesNotRewrapCalendarPayloadUnderData(): void
  {
    $controller = $this->readProjectFile('src/Controllers/CalendarController.php');

    $this->assertStringNotContainsString("['data' => \$calendarData]", $controller);
    $this->assertStringContainsString("Response::success('[CC] Calendar data retrieved.', \$calendarData", $controller);
  }

  public function testLegacyCalendarLoaderUsesFlatPayloadShape(): void
  {
    $calendarLoader = $this->readProjectFile('js/calendar/index.php');

    $this->assertStringNotContainsString('calendarData = data.data;', $calendarLoader);
    $this->assertStringNotContainsString("data.status !== 'success' || !data.data", $calendarLoader);
    $this->assertStringNotContainsString('const responseData = (payload && typeof payload === \'object\' && payload.data && typeof payload.data === \'object\')', $calendarLoader);
    $this->assertStringContainsString("if (payload.status !== 'success' || !payload || !payload.weeks)", $calendarLoader);
    $this->assertStringContainsString('calendarData = payload;', $calendarLoader);
  }

  public function testBillingFrontendsUseFlatPayloadsOnly(): void
  {
    $businessJs = $this->readProjectFile('js/business/index.php');
    $businessWorkspaceJs = $this->readProjectFile('js/business/workspace.js.php');
    $businessProfileBillingJs = $this->readProjectFile('js/business/core/profile-billing.js.php');
    $settingsJs = $this->readProjectFile('js/settings/index.php');

    $this->assertFileDoesNotExist(__DIR__ . '/../../js/businesses/index.php');

    $this->assertStringNotContainsString('payload.data?.checkout_url', $businessWorkspaceJs);
    $this->assertStringNotContainsString('payload.data?.portal_url', $businessWorkspaceJs);
    $this->assertStringNotContainsString('subData = payload.data', $businessWorkspaceJs);
    $this->assertStringNotContainsString('const responseData = (payload && typeof payload === \'object\' && payload.data && typeof payload.data === \'object\')', $businessWorkspaceJs);
    $this->assertStringContainsString('const subData = billingController.subscription;', $businessProfileBillingJs);
    $this->assertStringContainsString('initializeBillingSection', $businessJs);
    $this->assertStringContainsString('initializeBillingSection({', $businessProfileBillingJs);

    $this->assertStringNotContainsString('payload.data?.checkout_url', $settingsJs);
    $this->assertStringNotContainsString('payload.data?.portal_url', $settingsJs);
    $this->assertStringNotContainsString('subData = payload.data', $settingsJs);
    $this->assertStringNotContainsString('const responseData = (payload && typeof payload === \'object\' && payload.data && typeof payload.data === \'object\')', $settingsJs);
    $this->assertStringContainsString('initializeBillingSection({', $settingsJs);
  }

  public function testPublicNavigationCannotRenderAsSavedSidebarPreference(): void
  {
    $navigationCss = $this->readProjectFile('css/navigation/index.php');
    $header = $this->readProjectFile('header.php');
    $render = $this->readProjectFile('src/Domain/Render.php');

    $this->assertStringContainsString('Public pages never use the authenticated sidebar', $navigationCss);
    $this->assertStringContainsString("\$navPrimaryPosition = 'top';", $header);
    $this->assertStringContainsString('Render::publicLanguageBar($activeLanguageForNav)', $header);
    $this->assertStringContainsString('<li class="pages public_brand"><a href="/" aria-label="PayCal">PayCal</a></li>', $header);
    $this->assertStringContainsString('public_language_cluster', $header);
    $this->assertStringContainsString('public_appearance_button', $header);
    $this->assertStringNotContainsString('PAYCAL_HTML_PUBLIC', $header);
    $this->assertStringContainsString('public static function publicLanguageBar', $render);
    $this->assertStringContainsString('class="public_language_link', $render);
    $this->assertStringContainsString('body:has(#page_header.nav_component--public) #page_header.nav_component--header.nav_component--public', $navigationCss);
    $this->assertStringContainsString('position: sticky !important;', $navigationCss);
    $this->assertStringContainsString('width: 100% !important;', $navigationCss);
    $this->assertStringContainsString('#101112 !important;', $navigationCss);
    $this->assertStringContainsString('.public_brand', $navigationCss);
    $this->assertStringContainsString('.public_language_bar', $navigationCss);
    $this->assertStringContainsString('.public_language_link.is-active', $navigationCss);
    $this->assertStringContainsString('.public_appearance_button', $navigationCss);
    $this->assertStringContainsString('body:has(#page_header.nav_component--public) #main', $navigationCss);
    $this->assertStringContainsString('margin-left: 0 !important;', $navigationCss);
  }

  public function testAuthHeroUsesOptimizedResponsiveAssets(): void
  {
    $authPage = $this->readProjectFile('auth/index.php');

    $this->assertStringContainsString('<picture>', $authPage);
    $this->assertStringContainsString('/images/paycal-auth-hero-win10.webp', $authPage);
    $this->assertStringContainsString('/images/paycal-auth-hero-win10.jpg', $authPage);
    $this->assertStringNotContainsString('/images/paycal-auth-hero-win10.png', $authPage);

    $webpPath = __DIR__ . '/../../images/paycal-auth-hero-win10.webp';
    $jpgPath = __DIR__ . '/../../images/paycal-auth-hero-win10.jpg';
    $pngPath = __DIR__ . '/../../images/paycal-auth-hero-win10.png';

    $this->assertFileExists($webpPath);
    $this->assertFileExists($jpgPath);
    $this->assertFileDoesNotExist($pngPath);
    $this->assertLessThan(80 * 1024, filesize($webpPath));
    $this->assertLessThan(200 * 1024, filesize($jpgPath));
  }

  public function testAuthLocalizedCopyAndControlsHandleLongTranslations(): void
  {
    $authPage = $this->readProjectFile('auth/index.php');
    $authCss = $this->readProjectFile('css/auth/index.php');

    $this->assertStringContainsString('class="auth-signin-divider"', $authPage);
    $this->assertStringContainsString('role="separator"', $authPage);
    $this->assertStringContainsString('aria-hidden="true"', $authPage);
    $this->assertStringNotContainsString("\$i18n['AUTH_TERMS_ACK_PREFIX']", $authPage);
    $this->assertStringNotContainsString('By signing in you agree to our', $authPage);

    $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr));', $authCss);
    $this->assertStringContainsString('letter-spacing: 0 !important;', $authCss);
    $this->assertStringContainsString('white-space: normal !important;', $authCss);
    $this->assertStringContainsString('overflow-wrap: anywhere !important;', $authCss);
    $this->assertStringContainsString('.auth-signin-divider {', $authCss);

    foreach (['fr', 'de', 'es', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'] as $languageCode) {
      $strings = $this->readRootFile('strings/' . $languageCode . '.txt');

      $this->assertStringNotContainsString('FOOTER_TRADEMARK PayCal™ is a trademark of PayCal Technologies Inc. (registration pending).', $strings);
    }
  }

  public function testAccountRecoveryStartResponseAndFrontendAreFlatOnly(): void
  {
    $recoveryController = $this->readProjectFile('src/Controllers/AccountRecoveryController.php');
    $recoveryFrontend = $this->readProjectFile('js/auth-recovery/index.php');
    $recoveryPage = $this->readProjectFile('auth/recover/index.php');

    $this->assertStringNotContainsString("'data' => [", $recoveryController);
    $this->assertStringContainsString("'txnId' =>", $recoveryController);
    $this->assertStringContainsString("'txnSecret' =>", $recoveryController);

    $this->assertStringNotContainsString('state.txnId = payload.data?.txnId', $recoveryFrontend);
    $this->assertStringNotContainsString('state.txnSecret = payload.data?.txnSecret', $recoveryFrontend);
    $this->assertStringNotContainsString('const responseData = (payload && typeof payload === \'object\' && payload.data && typeof payload.data === \'object\')', $recoveryFrontend);
    $this->assertStringContainsString("state.txnId = payload?.txnId || '';", $recoveryFrontend);
    $this->assertStringContainsString("state.txnSecret = payload?.txnSecret || '';", $recoveryFrontend);
    $this->assertStringNotContainsString('data-step-indicator', $recoveryPage);
    $this->assertStringContainsString('<section class="recovery-panel" data-step="1">', $recoveryPage);
    $this->assertStringContainsString('<section class="recovery-panel is-hidden" data-step="2">', $recoveryPage);
    $this->assertStringContainsString('id="recovery-start-form" class="recovery-email-form"', $recoveryPage);
    $this->assertStringContainsString('id="recovery-verify-form" class="recovery-code-form"', $recoveryPage);
    $this->assertSame(1, substr_count($recoveryPage, '&lt; Back to sign in'));
    $this->assertStringContainsString('<h1><?php echo htmlspecialchars($i18n[\'AUTH_RECOVER_HEADING\']', $recoveryPage);
    $this->assertStringContainsString('AUTH_RECOVER_SUBHEADING', $recoveryPage);
    $this->assertStringContainsString('<label for="recovery-email">Email address</label>', $recoveryPage);
    $this->assertStringContainsString('<label for="recovery-code">Verification code</label>', $recoveryPage);
    $this->assertStringContainsString('<label for="recovery-key">Recovery code</label>', $recoveryPage);
    $this->assertStringContainsString('>Send code</button>', $recoveryPage);
    $this->assertStringContainsString('Sent to your email.', $recoveryPage);
    $this->assertStringContainsString('Saved when you secured your account.', $recoveryPage);
    $this->assertStringContainsString('Verify and continue', $recoveryPage);
    $this->assertStringContainsString('recovery-disclosure', $recoveryPage);
    $this->assertStringContainsString('<div id="recovery-key-block" class="recovery-field is-hidden">', $recoveryPage);
    $this->assertStringContainsString('id="recovery-key" name="recoveryKey" type="text" autocomplete="off" spellcheck="false" maxlength="60"', $recoveryPage);
    $this->assertStringContainsString('id="recovery-code" name="code" type="text" autocomplete="one-time-code" maxlength="6" required', $recoveryPage);
    $this->assertStringContainsString('id="recovery-register-passkey" class="btn btn_primary" disabled aria-disabled="true"', $recoveryPage);
    $this->assertStringContainsString('class="recovery-hint is-prominent" id="recovery-existing-passkey-hint"', $recoveryPage);
    $this->assertStringContainsString('state.bootstrapReady && state.txnId !== \'\' && state.txnSecret !== \'\' && state.bootstrap !== null', $recoveryFrontend);
    $this->assertStringContainsString('state.emailCodeVerified = true;', $recoveryFrontend);
    $this->assertStringContainsString('verifyPayload?.passkeyReady === true && verifyPayload?.recoveryKeyRequired === false', $recoveryFrontend);
    $this->assertStringContainsString('verifyPayload?.recoveryUnavailable === true', $recoveryFrontend);
    $this->assertStringContainsString('Sign in with your passkey, then create one from Settings', $recoveryFrontend);
    $this->assertStringContainsString('hideRecoveryKeyInput();', $recoveryFrontend);
    $this->assertStringContainsString('verifyPayload?.recoveryKeyRequired === true', $recoveryFrontend);
    $this->assertStringContainsString('RECOVERY_T.AUTH_RECOVER_EMAIL_SENT', $recoveryFrontend);
    $this->assertStringContainsString("setStatus('Checking...');", $recoveryFrontend);
    $this->assertStringContainsString("const CHECKSUM_ERROR = 'Check the last two characters.';", $recoveryFrontend);
    $this->assertStringContainsString('Verification code looks good.', $recoveryFrontend);
    $this->assertStringContainsString('Recovery code looks good.', $recoveryFrontend);
    $this->assertStringContainsString('This recovery code does not match this account.', $recoveryFrontend);
    $this->assertStringContainsString('updateVerifyButtonState', $recoveryFrontend);
    $this->assertStringContainsString('storeRecoveryPrefill({', $recoveryFrontend);
    $this->assertStringContainsString("source: 'magic-link'", $recoveryFrontend);
    $this->assertStringContainsString("currentUrl.searchParams.delete('ml_token');", $recoveryFrontend);
    $this->assertStringContainsString('setStep(state.bootstrapReady ? 2 : 1);', $recoveryFrontend);
    $this->assertStringContainsString('setRecoveryErrorStatus', $recoveryFrontend);
    $this->assertStringContainsString('requestNewRecoveryLink', $recoveryFrontend);
    $this->assertStringContainsString('Request New Link', $recoveryFrontend);
    $this->assertStringContainsString('className = \'recovery-status-action\'', $recoveryFrontend);
  }

  public function testPublicDocumentationRoutesUsePermanentPaycaltechRedirects(): void
  {
    foreach (['about', 'blog', 'faq', 'policies', 'transparency'] as $route) {
      $page = $this->readProjectFile($route . '/index.php');

      $this->assertStringContainsString('paycaltech.com', $page);
      $this->assertMatchesRegularExpression(
        "/header\\('Location: .*?, true, 301\\);/s",
        $page,
        '/' . $route . '/ must use a permanent redirect',
      );
    }
  }

  public function testSettingsRecoveryCodeSurfaceUsesCurrentFormatAndCopy(): void
  {
    $settingsStrings = $this->readRootFile('strings/en.txt');
    $settingsFrontend = $this->readProjectFile('js/settings/index.php');
    $cryptoWorker = $this->readProjectFile('js/calendar/crypto-worker.js');
    $emailGarum = $this->readProjectFile('src/Domain/EmailGarum.php');
    $accountController = $this->readProjectFile('src/Controllers/AccountController.php');
    $emailVerificationController = $this->readProjectFile('src/Controllers/EmailVerificationController.php');

    $this->assertStringContainsString('SETTINGS_RECOVERY_KEY_TITLE Recovery Code', $settingsStrings);
    $this->assertStringContainsString('SETTINGS_RECOVERY_KEY_CREATE_BUTTON Create Recovery Code', $settingsStrings);
    $this->assertStringContainsString('SETTINGS_RECOVERY_KEY_REGENERATE_BUTTON Generate New Recovery Code', $settingsStrings);
    $this->assertStringContainsString('SETTINGS_RECOVERY_KEY_SUCCESS_CREATE Recovery Code created. Save it now.', $settingsStrings);
    $this->assertStringContainsString('SETTINGS_RECOVERY_KEY_DISPLAY_INSTRUCTION Save this code now. PayCal cannot show it again.', $settingsStrings);
    $this->assertStringContainsString('EMAIL_ACCOUNT_RECOVERY_CODE_TITLE Verification Code', $settingsStrings);
    $this->assertStringNotContainsString('SETTINGS_RECOVERY_KEY_TITLE Recovery Key', $settingsStrings);
    $this->assertStringNotContainsString('EMAIL_ACCOUNT_RECOVERY_CODE_TITLE Account Recovery Code', $settingsStrings);
    $this->assertStringNotContainsString('Create Recovery Key', $settingsStrings);
    $this->assertStringNotContainsString('Generate New Recovery Key', $settingsStrings);

    $this->assertStringContainsString('Unable to create Recovery Code.', $settingsFrontend);
    $this->assertStringContainsString('showRecoveryCodeOnce(recoveryCodeForDisplay);', $settingsFrontend);
    $this->assertStringNotContainsString('Unable to create Recovery Key.', $settingsFrontend);

    $this->assertStringContainsString('function generatePayCalRecoveryCode()', $cryptoWorker);
    $this->assertStringContainsString('PAYCAL_A11Y_ALPHABET', $cryptoWorker);
    $this->assertStringContainsString('return formatRecoveryCode(`${secret}${payCalChecksum(secret)}`);', $cryptoWorker);
    $this->assertStringContainsString('Recovery Code does not match this account. Check the code and try again.', $cryptoWorker);
    $this->assertStringNotContainsString('Recovery Key does not match this account. Check the key and try again.', $cryptoWorker);
    $this->assertStringNotContainsString('Missing recovery key unwrap inputs', $cryptoWorker);

    $this->assertStringContainsString("'] - Verification Code'", $emailGarum);
    $this->assertStringContainsString('Raw Recovery Codes must not be emailed.', $emailGarum);
    $this->assertStringNotContainsString('Your Recovery Code', $emailGarum);
    $this->assertStringNotContainsString('Your Recovery Key', $emailGarum);
    $this->assertStringNotContainsString('Account Recovery Code', $emailGarum);
    $this->assertStringNotContainsString('sendRecoveryKeyEmail(', $accountController);
    $this->assertStringNotContainsString('sendRecoveryKeyEmail(', $emailVerificationController);
  }

  public function testAccountRecoverySecurityDefaultsAreConservative(): void
  {
    $systemConfig = $this->readProjectFile('src/Domain/Config/SystemConfig.php');
    $rateLimiter = $this->readProjectFile('src/Infrastructure/RateControl/RateLimiter.php');

    $this->assertStringContainsString("'account_recovery_code_ttl_minutes' => [", $systemConfig);
    $this->assertStringContainsString("'default' => 10", $systemConfig);
    $this->assertStringContainsString("'account_recovery_max_verify_attempts' => [", $systemConfig);
    $this->assertStringContainsString("'default' => 5", $systemConfig);
    $this->assertStringContainsString("'account_recovery_max_starts_per_day' => [", $systemConfig);
    $this->assertStringContainsString("'account_recovery_max_resends_per_hour' => [", $systemConfig);
    $this->assertStringContainsString("'account_recovery_bootstrap_ttl_seconds' => [", $systemConfig);
    $this->assertStringContainsString("'default' => 300", $systemConfig);
    $this->assertStringContainsString("'magic-link-consume' => ['config' => 'account_recovery_max_verify_attempts', 'fallback' => 5", $rateLimiter);
  }

  private function readProjectFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }

  private function readRootFile(string $relativePath): string
  {
    $absolutePath = __DIR__ . '/../../../' . $relativePath;
    $contents = @file_get_contents($absolutePath);

    $this->assertNotFalse($contents, 'Unable to read file: ' . $relativePath);

    return (string) $contents;
  }
}
