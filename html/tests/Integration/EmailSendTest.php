<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\EmailTransport;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Sends a single live test email to verify SMTP, DKIM, DMARC, and Message-ID headers.
 *
 * Safety gate: only runs when PAYCAL_RUN_LIVE_EMAIL=1 is set.
 * Recipient defaults to info@paycal.app; override with PAYCAL_LIVE_EMAIL_RECIPIENT.
 */
final class EmailSendTest extends TestCase
{
  #[Group('skip')]
  protected function setUp(): void
  {
    parent::setUp();

    if (getenv('PAYCAL_RUN_LIVE_EMAIL') !== '1') {
      $this->markTestSkipped('Set PAYCAL_RUN_LIVE_EMAIL=1 to send a live test email.');
    }

    require_once dirname(__DIR__, 2) . '/config.php';

    if (Environment::smtpServer() === '' || Environment::smtpPort() === 0) {
      $this->markTestSkipped('SMTP is not configured in environment.');
    }

    if (Environment::emailReplyTo() === '' || Environment::emailPassword() === '') {
      $this->markTestSkipped('SMTP credentials are missing in environment.');
    }
  }

  #[Group('skip')]
  public function testSendsSingleLiveEmail(): void
  {
    $recipient = getenv('PAYCAL_LIVE_EMAIL_RECIPIENT') ?: 'info@paycal.app';

    $htmlBody = <<<HTML
      <html>
        <body style="margin:0; padding:24px; background:#f4f7fb; font-family:Arial, Helvetica, sans-serif; color:#1f2933;">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px; margin:0 auto; background:#ffffff; border:1px solid #d9e2ec; border-radius:12px;">
            <tr>
              <td style="padding:24px; border-bottom:1px solid #d9e2ec;">
                <h1 style="margin:0; font-size:20px;">PayCal Email Test</h1>
                <p style="margin:8px 0 0 0; color:#52606d;">Verifying SPF, DKIM, DMARC, and Message-ID</p>
              </td>
            </tr>
            <tr>
              <td style="padding:24px;">
                <p style="margin:0 0 16px 0;">This is a single live test email sent from EmailSendTest.</p>
                <p style="margin:0; color:#52606d;">If you see this in your inbox with DKIM: PASS and DMARC: PASS, the email stack is healthy.</p>
              </td>
            </tr>
          </table>
        </body>
      </html>
      HTML;

    $textBody = "PayCal Email Test\n\nVerifying SPF, DKIM, DMARC, and Message-ID.\n\nThis is a single live test email sent from EmailSendTest.\n\nIf you see this with DKIM: PASS and DMARC: PASS, the email stack is healthy.";

    $transport = new EmailTransport();
    $sent = $transport->send(
      to: $recipient,
      subject: '[PayCal] Email Stack Test',
      htmlBody: $htmlBody,
      textBody: $textBody,
      from: 'PayCal <info@paycal.app>',
    );

    $this->assertTrue($sent, 'Email send failed: ' . $transport->getLastError());
  }
}
