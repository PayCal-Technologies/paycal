<?php declare(strict_types=1);

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Render;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[Group('unit')]
final class EmailTemplateRenderTest extends TestCase
{
  private const TEST_HEADING = 'This is a test email of this template.';

  protected function setUp(): void
  {
    $htmlRoot = dirname(__DIR__, 2);

    Environment::bootstrap($this->envDefaults([
      'APP_HOME' => $htmlRoot . '/',
    ]));

    Render::setStrictMode(true);
  }

  protected function tearDown(): void
  {
    Render::setStrictMode(false);
  }

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

  #[Test]
  #[DataProvider('templateProvider')]
  public function everyEmailTemplateRendersWithFakeTestContent(string $templateName): void
  {
    $pairs = [
      '__PC_NAME__' => 'PayCal',
      '__USER_NAME__' => self::TEST_HEADING,
      '__VERIFICATION_CODE__' => self::TEST_HEADING,
      '__VERIFICATION_URL__' => 'https://example.test/verify?token=fake-token',
      '__RECOVERY_KEY__' => 'TEST-RECOVERY-KEY-1234-5678',
      '__ACCOUNT_EMAIL__' => 'test@example.test',
      '__SOURCE_URL__' => 'https://example.test/auth/',
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
      '__CONTACT_SUBJECT__' => 'Template Test Subject',
      '__CONTACT_TOPIC__' => 'general',
      '__MESSAGE_CONTENT__' => self::TEST_HEADING,
    ];

    $output = Render::template($templateName, $pairs);

    $this->assertStringNotContainsString('<!-- Template ', $output, $templateName . ' should resolve to a real template file');
    $this->assertStringContainsString(self::TEST_HEADING, $output, $templateName . ' should include obvious fake test content');
  }

  #[Test]
  public function verificationReminderTemplateContainsAllRequiredPlaceholders(): void
  {
    $templatePath = dirname(__DIR__, 3) . '/templates/verification-reminder.php';
    $this->assertFileExists($templatePath, 'templates/verification-reminder.php must exist in repo root');

    $body = (string) file_get_contents($templatePath);

    $requiredPlaceholders = [
      '__SITE_REGISTER_VERIFY_URL__',
      '__SITE_API_RESEND_VERIFY_URL__',
      '__VERIFICATION_REMINDER_JS_URL__',
      '__VERIFICATION_REMINDER_JS_INTEGRITY_ATTR__',
      '__CSP_NONCE__',
    ];

    foreach ($requiredPlaceholders as $placeholder) {
      $this->assertStringContainsString(
        $placeholder,
        $body,
        "templates/verification-reminder.php must contain {$placeholder}"
      );
    }
  }

  #[Test]
  public function verificationReminderTemplateRendersWithSriAndResendEndpointSubstituted(): void
  {
    $fakeResendUrl = 'https://localhost/api/v1/account/resend-verification';
    $fakeVerifyUrl = 'https://localhost/verify/';
    $fakeSriAttr   = ' integrity="sha384-FAKEHASHFORTEST" crossorigin="anonymous"';
    $fakeJsUrl     = 'https://localhost/js/signin/verification-reminder.js?v=0.0.0';
    $fakeNonce     = 'abc123testnonce';

    $output = Render::template('verification-reminder', [
      '__SITE_REGISTER_VERIFY_URL__'           => $fakeVerifyUrl,
      '__SITE_API_RESEND_VERIFY_URL__'         => $fakeResendUrl,
      '__VERIFICATION_REMINDER_JS_URL__'       => $fakeJsUrl,
      '__VERIFICATION_REMINDER_JS_INTEGRITY_ATTR__' => $fakeSriAttr,
      '__CSP_NONCE__'                          => $fakeNonce,
      '__AUTH_VERIFICATION_REMINDER_TITLE__' => 'Verify your email',
      '__AUTH_VERIFICATION_REMINDER_BODY__' => 'Use the code below to finish sign-in.',
      '__AUTH_VERIFICATION_REMINDER_CODE_PLACEHOLDER__' => 'Enter verification code',
      '__AUTH_VERIFICATION_REMINDER_VERIFY_BUTTON__' => 'Verify',
      '__AUTH_VERIFICATION_REMINDER_RESEND_LINK__' => 'Resend verification email',
    ]);

    $this->assertStringNotContainsString(
      '<!-- Template ',
      $output,
      'verification-reminder template must resolve to a real file'
    );
    $this->assertStringContainsString(
      'value="' . $fakeResendUrl . '"',
      $output,
      '__SITE_API_RESEND_VERIFY_URL__ must be wired to the hidden resend-verification-endpoint input value'
    );
    $this->assertStringContainsString(
      'action="' . $fakeVerifyUrl . '"',
      $output,
      '__SITE_REGISTER_VERIFY_URL__ must be wired to the form action'
    );
    $this->assertStringContainsString(
      $fakeSriAttr,
      $output,
      '__VERIFICATION_REMINDER_JS_INTEGRITY_ATTR__ must be rendered verbatim into the script tag'
    );
    $this->assertStringContainsString(
      'src="' . $fakeJsUrl . '"',
      $output,
      '__VERIFICATION_REMINDER_JS_URL__ must be wired to the script src'
    );
  }

  /**
   * @return array<string, string>
   */
  private function envDefaults(array $overrides = []): array
  {
    $defaults = [
      'APP_ENV' => 'dev',
      'APP_SCHEME' => 'https',
      'APP_DOMAIN' => 'localhost',
      'APP_HOME' => '/private/var/www/paycal/dev/html/',
      'API_VERSION' => 'v1',
      'REDIS_SERVER' => 'localhost',
      'REDIS_PORT' => '6379',
      'REDIS_READ_PORT' => '6379',
      'REDIS_WRITE_PORT' => '6379',
      'REDIS_DB' => '0',
      'REDIS_USER' => '',
      'REDIS_PASSWORD' => '',
      'REDIS_NEW_SESSION_TTL' => '3600',
      'PC_EMAIL_SMTP_SERVER' => 'localhost',
      'PC_EMAIL_SMTP_PORT' => '25',
      'PC_EMAIL_CONTACT' => 'noreply@example.com',
      'PC_EMAIL_DEBUG' => 'debug@example.com',
      'PC_EMAIL_REPLYTO' => 'reply@example.com',
      'PC_EMAIL_PASSWORD' => 'x',
      'PC_INVITE_CODE' => 'invite',
      'PAYROLL_SIGNING_PRIVATE_KEY' => '',
      'PAYROLL_SIGNING_PUBLIC_KEY' => '',
      'DEV_ALLOW_INLINE_SCRIPTS' => 'true',
      'DEV_SECURITY_DISABLED' => 'false',
      'ENCRYPTION_ENABLED' => 'false',
    ];

    return array_merge($defaults, $overrides);
  }
}
