<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
#[Group('a11y')]
final class FormErrorRecoveryContractTest extends TestCase
{
  #[Test]
  public function contactFormFieldsExposeErrorBindingContracts(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $templatePath = $projectRoot . '/templates/contact-page.php';
    $template = (string) file_get_contents($templatePath);

    $expectedBindings = [
      "name')) { ?>aria-invalid=\"true\" aria-describedby=\"name_error\"",
      "email')) { ?>aria-invalid=\"true\" aria-describedby=\"email_error\"",
      "subject')) { ?>aria-invalid=\"true\" aria-describedby=\"subject_error\"",
      "reason')) { ?>aria-invalid=\"true\" aria-describedby=\"reason_error\"",
      "message')) { ?>aria-invalid=\"true\" aria-describedby=\"message_error\"",
      'id="contact_status"',
      '<article class="article doc-article contact-page" aria-labelledby="contact-page-title" aria-describedby="contact-page-deck">',
      '<h1 id="contact-page-title">',
      'aria-labelledby="contact-form-heading"',
      'aria-describedby="contact-page-deck contact_status"',
      '<section class="contact-form-section contact-form-section--top" aria-labelledby="contact-form-basics-heading">',
      '<section class="contact-form-section contact-form-section--bottom" aria-labelledby="contact-form-notes-heading">',
      '<aside class="contact-details-panel" aria-labelledby="contact-details-heading">',
      'class="contact-help-chips" role="group" aria-labelledby="contact-diagnostics-heading" aria-describedby="contact-diagnostics-desc"',
      'id="contact_success_card" class="contact-success-card" role="status" aria-live="polite" aria-atomic="true" aria-labelledby="contact_success_title"',
      'class="contact-status contact-status--<?php echo htmlspecialchars($formStatusType, ENT_QUOTES,',
      'aria-live="<?php echo $contactStatusLive; ?>"',
      'aria-atomic="true"',
      'aria-describedby="contact_status contact_cooldown_hint"',
    ];

    foreach ($expectedBindings as $bindingSnippet) {
      $this->assertStringContainsString($bindingSnippet, $template);
    }
  }

  #[Test]
  public function settingsAndSitesPagesRetainErrorDescriptionsForSensitiveFlows(): void
  {
    $projectRoot = dirname(__DIR__, 4);
    $settingsPage = (string) file_get_contents($projectRoot . '/html/settings/_partials/modals.php')
      . (string) file_get_contents($projectRoot . '/html/settings/_partials/panel_account.php');
    $sitesPage = (string) file_get_contents($projectRoot . '/html/sites/index.php');
    $siteEditorDialogs = (string) file_get_contents($projectRoot . '/html/sites/_partials/site_editor_dialogs.php');

    $this->assertStringContainsString('change_email_new_email_error', $settingsPage);
    $this->assertStringContainsString('aria-describedby="recovery_email_send_status recovery_email_input_error"', $settingsPage);

    $this->assertStringContainsString('site_editor_dialogs.php', $sitesPage);
    $this->assertStringContainsString('edit_site_name_error', $siteEditorDialogs);
    $this->assertStringContainsString("aria-describedby='recovery_site_form_status recovery_site_name_error'", $sitesPage);
  }
}
