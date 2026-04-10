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
    $settingsPage = (string) file_get_contents($projectRoot . '/html/settings/index.php');
    $sitesPage = (string) file_get_contents($projectRoot . '/html/sites/index.php');

    $this->assertStringContainsString('change_email_new_email_error', $settingsPage);
    $this->assertStringContainsString('aria-describedby="recovery_email_send_status recovery_email_input_error"', $settingsPage);

    $this->assertStringContainsString('edit_site_name_error', $sitesPage);
    $this->assertStringContainsString("aria-describedby='recovery_site_form_status recovery_site_name_error'", $sitesPage);
  }
}
