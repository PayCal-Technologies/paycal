<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\EmailTransport;
use PayCal\Domain\Render;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Sends live test emails for each template.
 *
 * Safety gate:
 * - Only runs when PAYCAL_RUN_LIVE_EMAIL_SWEEP=1 is set.
 */
final class LiveEmailTemplateSweepTest extends TestCase
{
  private const TEST_HEADING = 'This is a test email of this template.';
  private const DEFAULT_RECIPIENT = 'info@paycal.app';

  /**
   * @return array<string, array{0:string}>
   */
  public static function templateProvider(): array
  {
    return [
      'contact_html' => ['contact-email-html'],
      'contact_text' => ['contact-email-text'],
      'verify_code_html' => ['email-verification-code-html'],
      'verify_code_text' => ['email-verification-code-text'],
      'verify_link_html' => ['email-verification-link-html'],
      'verify_link_text' => ['email-verification-link-text'],
      'recovery_key_html' => ['email-recovery-key-html'],
      'recovery_key_text' => ['email-recovery-key-text'],
      'recovery_email_code_html' => ['email-recovery-email-code-html'],
      'recovery_email_code_text' => ['email-recovery-email-code-text'],
      'account_recovery_code_html' => ['email-account-recovery-code-html'],
      'account_recovery_code_text' => ['email-account-recovery-code-text'],
      'change_code_old_html' => ['email-change-code-old-html'],
      'change_code_old_text' => ['email-change-code-old-text'],
      'change_code_new_html' => ['email-change-code-new-html'],
      'change_code_new_text' => ['email-change-code-new-text'],
      'change_confirmation_old_html' => ['email-change-confirmation-old-html'],
      'change_confirmation_old_text' => ['email-change-confirmation-old-text'],
      'change_confirmation_new_html' => ['email-change-confirmation-new-html'],
      'change_confirmation_new_text' => ['email-change-confirmation-new-text'],
    ];
  }

  #[Group('skip')]
  protected function setUp(): void
  {
    parent::setUp();

    if (getenv('PAYCAL_RUN_LIVE_EMAIL_SWEEP') !== '1') {
      $this->markTestSkipped('Set PAYCAL_RUN_LIVE_EMAIL_SWEEP=1 to run live email sweep.');
    }

    // Ensure environment is bootstrapped with real SMTP settings.
    require_once dirname(__DIR__, 2) . '/config.php';

    if (Environment::smtpServer() === '' || Environment::smtpPort() === 0) {
      $this->markTestSkipped('SMTP is not configured in environment.');
    }

    if (Environment::emailReplyTo() === '' || Environment::emailPassword() === '') {
      $this->markTestSkipped('SMTP credentials are missing in environment.');
    }
  }

  #[DataProvider('templateProvider')]
  #[Group('skip')]
  public function testSendsLiveTemplateEmail(string $templateName): void
  {
    $recipient = getenv('PAYCAL_LIVE_EMAIL_RECIPIENT') ?: self::DEFAULT_RECIPIENT;

    $pairs = [
      '__PC_NAME__' => 'PayCal',
      '__USER_NAME__' => self::TEST_HEADING,
      '__VERIFICATION_CODE__' => self::TEST_HEADING,
      '__VERIFICATION_URL__' => 'https://example.test/verify?token=fake-token',
      '__RECOVERY_KEY__' => 'TEST-RECOVERY-KEY-1234-5678',
      '__ACCOUNT_EMAIL__' => 'test@example.test',
      '__ISSUED_AT__' => '2099-01-01 00:00:00 UTC',
      '__SUPPORT_TOKEN__' => 'TESTSUPPORT01',
      '__EXPIRES_IN_MINUTES__' => '15',
      '__EMAIL_TYPE__' => 'current',
      '__TXN_ID__' => 'TXN-TEST-0001',
      '__CONTEXT_LABEL__' => 'changed to',
      '__OTHER_EMAIL__' => 'other@example.test',
      '__CHANGE_TIME__' => '2099-01-01 00:00:00 UTC',
      '__SENDER_NAME__' => 'Template Test Sender',
      '__SENDER_EMAIL__' => 'sender@example.test',
      '__CONTACT_SUBJECT__' => self::TEST_HEADING,
      '__MESSAGE_CONTENT__' => self::TEST_HEADING,
    ];

    $rendered = Render::template($templateName, $pairs);

    $htmlBody = str_ends_with($templateName, '-html')
      ? $rendered
      : '<html><body><pre>' . htmlspecialchars($rendered, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</pre></body></html>';

    $textBody = str_ends_with($templateName, '-text')
      ? $rendered
      : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $rendered));

    $subject = '[PayCal TEST] Template Sweep - ' . $templateName;
    $transport = new EmailTransport();

    $sent = $transport->send(
      to: $recipient,
      subject: $subject,
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: Environment::emailReplyTo(),
      bcc: []
    );

    $this->assertTrue($sent, 'Failed sending ' . $templateName . ': ' . $transport->getLastError());
  }
}
