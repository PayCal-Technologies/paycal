<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class FormValidationAriaContractTest extends TestCase
{
  private function projectRoot(): string
  {
    return dirname(__DIR__, 4);
  }

  private function htmlRoot(): string
  {
    return $this->projectRoot() . '/html';
  }

  #[Test]
  public function sharedFormsHelperWiresInvalidStateAndErrorMessageBinding(): void
  {
    $formsJs = (string) file_get_contents($this->htmlRoot() . '/js/core/forms.js');

    $this->assertStringContainsString('function syncFieldErrorMessageBinding(inputEl, errorEl, hasError)', $formsJs);
    $this->assertStringContainsString("inputEl.setAttribute('aria-errormessage', errorId);", $formsJs);
    $this->assertStringContainsString("inputEl.removeAttribute('aria-errormessage');", $formsJs);
    $this->assertStringContainsString('syncFieldErrorMessageBinding(inputEl, errorEl, hasError);', $formsJs);
  }

  #[Test]
  public function authSignInAndRegisterFieldsExposeValidationContracts(): void
  {
    $authPage = (string) file_get_contents($this->htmlRoot() . '/auth/index.php');
    $signupPage = (string) file_get_contents($this->htmlRoot() . '/auth/signup/index.php');

    $expectedBindings = [
      'id="signin_email_error"',
      'aria-describedby="signin-passkey-status signin_email_error signin-notice"',
      'role="alert" aria-live="polite"',
    ];

    foreach ($expectedBindings as $bindingSnippet) {
      $this->assertStringContainsString($bindingSnippet, $authPage);
    }

    $expectedSignupBindings = [
      'id="register_full_name_error"',
      'id="register_email_error"',
      'id="register_invite_code_error"',
      'id="register_device_name_error"',
      'aria-required="true"',
      'aria-describedby="register-passkey-status register_full_name_error"',
      'aria-describedby="register-passkey-status register_email_error"',
      'aria-describedby="register-passkey-status register_invite_code_error"',
      'aria-describedby="register-passkey-status register_device_name_error"',
      'role="alert" aria-live="polite"',
    ];

    foreach ($expectedSignupBindings as $bindingSnippet) {
      $this->assertStringContainsString($bindingSnippet, $signupPage);
    }
  }

  #[Test]
  public function authSigninJsUsesSharedFieldErrorHelperForRegisterValidation(): void
  {
    $signinJs = (string) file_get_contents($this->htmlRoot() . '/js/signin/index.php');

    $this->assertStringContainsString("from '/js/core/forms.js';", $signinJs);
    $this->assertStringContainsString('clearFieldErrorStates', $signinJs);
    $this->assertStringContainsString('setFieldErrorState', $signinJs);
    $this->assertStringContainsString('REGISTER_FIELD_PAIRS', $signinJs);
    $this->assertStringContainsString('validateRegisterFields', $signinJs);
    $this->assertStringContainsString('applyRegisterFieldErrorsFromMessage', $signinJs);
    $this->assertStringContainsString("'signin_email_error'", $signinJs);
    $this->assertStringNotContainsString('Full name and email are required.', $signinJs);
  }

  #[Test]
  public function authRecoveryFieldsExposeValidationErrorBindings(): void
  {
    $recoveryPage = (string) file_get_contents($this->htmlRoot() . '/auth/recover/index.php');
    $recoveryJs = (string) file_get_contents($this->htmlRoot() . '/js/auth-recovery/index.php');

    $this->assertStringContainsString("aria-describedby=\"recovery-status recovery-email-error\"", $recoveryPage);
    $this->assertStringContainsString('id="recovery-email-error"', $recoveryPage);
    $this->assertStringContainsString('aria-describedby="recovery-code-error"', $recoveryPage);
    $this->assertStringContainsString('aria-describedby="recovery-key-error"', $recoveryPage);
    $this->assertStringContainsString('aria-required="true"', $recoveryPage);
    $this->assertStringContainsString("input.setAttribute('aria-errormessage', errorEl.id);", $recoveryJs);
    $this->assertStringContainsString('recovery-email-error', $recoveryJs);
  }

  #[Test]
  public function siteEditorValidationHelpersWireErrorMessageReferences(): void
  {
    $siteEditorJs = (string) file_get_contents($this->htmlRoot() . '/js/sites/site-editor-core.php');
    $siteEditorDialogs = (string) file_get_contents($this->htmlRoot() . '/sites/_partials/site_editor_dialogs.php');

    $this->assertStringContainsString("input.setAttribute('aria-errormessage', errorEl.id);", $siteEditorJs);
    $this->assertStringContainsString("input.removeAttribute('aria-errormessage');", $siteEditorJs);
    $this->assertStringContainsString("aria-required='true'", $siteEditorDialogs);
    $this->assertStringContainsString('site_name_error', $siteEditorDialogs);
    $this->assertStringContainsString('edit_site_name_error', $siteEditorDialogs);
  }

  #[Test]
  public function settingsChangeEmailModalRetainsFieldErrorDescribedByTargets(): void
  {
    $modals = (string) file_get_contents($this->htmlRoot() . '/settings/_partials/modals.php');
    $settingsJs = (string) file_get_contents($this->htmlRoot() . '/js/settings/index.php');

    $this->assertStringContainsString('aria-describedby="change_email_status change_email_new_email_error"', $modals);
    $this->assertStringContainsString('id="change_email_new_email_error"', $modals);
    $this->assertStringContainsString('id="delete_account_confirm_error"', $modals);
    $this->assertStringContainsString('from "../core/forms.js";', $settingsJs);
    $this->assertStringContainsString('setFieldErrorState', $settingsJs);
  }

  #[Test]
  public function profileAccountValidationHelperWiresErrorMessageReferences(): void
  {
    $profileAccountJs = (string) file_get_contents($this->htmlRoot() . '/js/business/core/profile-account.js.php');

    $this->assertStringContainsString("input.setAttribute('aria-errormessage', errorElement.id);", $profileAccountJs);
    $this->assertStringContainsString("input.removeAttribute('aria-errormessage');", $profileAccountJs);
    $this->assertStringContainsString('edit_details_full_name_error', $profileAccountJs);
  }
}
