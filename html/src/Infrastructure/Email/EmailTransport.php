<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Email;
use PayCal\Domain\Config\Environment;

/**
 * EmailTransport.php
 *
 * SMTP transport adapter used by domain mail workflows.
 *
 * Why this exists:
 * - Isolate SMTP protocol mechanics from higher-level template/message builders.
 * - Provide a deterministic send contract independent of external libraries.
 * - Surface operational failures via `getLastError()` without throwing to callers.
 */

/**
 * Minimal multipart SMTP transport.
 *
 * Internal guarantees:
 * - Validates recipient addresses before attempting protocol writes.
 * - Executes connect/handshake/auth/send sequence with explicit error boundaries.
 * - Cleans up socket state on all failure and exception paths.
 */
final class EmailTransport
{
  private const SOCKET_TIMEOUT = 30;
  private const READ_BUFFER_SIZE = 2048;

  /** @var resource|null */
  private $socket = null;

  private ?string $lastError = null;

  /**
   * Send an email via SMTP
   *
   * @param string               $to          Recipient email address
   * @param string               $subject     Email subject
   * @param string               $htmlBody    HTML email body
   * @param string               $textBody    Plain text alternative body
   * @param string               $from        Sender address (defaults to info@paycal.app)
   * @param array<string>        $bcc         BCC recipients
   * @param array<string,string> $headers     Custom headers (e.g., Reply-To, List-Unsubscribe)
   *
   * @return bool True on success, false on failure
   */
  public function send(
    string $to,
    string $subject,
    string $htmlBody,
    string $textBody = '',
    string $from = 'PayCal <info@paycal.app>',
    array $bcc = [],
    array $headers = []
  ): bool {
    try {
      // Validate recipient
      if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $this->lastError = "Invalid recipient email: {$to}";

        return false;
      }

      // Establish SMTP connection
      if (!$this->connect()) {
        return false;
      }

      // SMTP handshake
      if (!$this->handshake()) {
        $this->disconnect();

        return false;
      }

      // Authenticate
      if (!$this->authenticate()) {
        $this->disconnect();

        return false;
      }

      // Set sender
      if (!$this->setSender($from)) {
        $this->disconnect();

        return false;
      }

      // Set recipients (to + bcc)
      $recipients = [$to];
      foreach ($bcc as $bccEmail) {
        if (filter_var($bccEmail, FILTER_VALIDATE_EMAIL)) {
          $recipients[] = $bccEmail;
        }
      }

      foreach ($recipients as $recipient) {
        if (!$this->addRecipient($recipient)) {
          $this->disconnect();

          return false;
        }
      }

      // Send DATA command
      if (!$this->command("DATA\r\n", 354)) {
        $this->lastError = 'Failed to initiate DATA command';
        $this->disconnect();

        return false;
      }

      // Build and send email content
      $emailContent = $this->buildMessage($from, $to, $subject, $htmlBody, $textBody, $headers);
      if (!$this->write($emailContent)) {
        $this->lastError = 'Failed to write email content';
        $this->disconnect();

        return false;
      }

      // End DATA with .
      if (!$this->command(".\r\n", 250)) {
        $this->lastError = 'Failed to terminate DATA command';
        $this->disconnect();

        return false;
      }

      // Graceful disconnect
      $this->command("QUIT\r\n", 221);
      $this->disconnect();

      return true;
    } catch (\Throwable $e) {
      $this->lastError = "Email send exception: {$e->getMessage()}";
      $this->disconnect();

      return false;
    }
  }

  /**
   * Get last error message
   */
  public function getLastError(): string
  {
    return $this->lastError ?? '';
  }

  /**
   * Establish SMTP connection with STARTTLS upgrade
   */
  private function connect(): bool
  {
    $server = Environment::smtpServer();
    $port = Environment::smtpPort();

    if (empty($server) || $port === 0) {
      $this->lastError = 'SMTP server or port not configured';

      return false;
    }

    // Open TCP connection
    $socket = @stream_socket_client(
      "tcp://{$server}:{$port}",
      $errno,
      $errstr,
      self::SOCKET_TIMEOUT
    );

    if (!is_resource($socket)) {
      $this->lastError = "Failed to connect to {$server}:{$port} - {$errstr}";

      return false;
    }

    $this->socket = $socket;

    // Read greeting
    $response = $this->read();
    if (!str_starts_with($response, '220')) {
      $this->lastError = "Unexpected SMTP greeting: {$response}";

      return false;
    }

    return true;
  }

  /**
   * SMTP handshake with STARTTLS upgrade
   */
  private function handshake(): bool
  {
    // Send EHLO
    if (!$this->command("EHLO paycal.app\r\n", 250)) {
      $this->lastError = 'EHLO command failed';

      return false;
    }

    // Upgrade to TLS via STARTTLS
    if (!$this->command("STARTTLS\r\n", 220)) {
      $this->lastError = 'STARTTLS command failed';

      return false;
    }

    // Enable TLS encryption
    if (!is_resource($this->socket)) {
      $this->lastError = 'Socket is not a valid resource';

      return false;
    }

    $cryptoEnabled = @stream_socket_enable_crypto(
      $this->socket,
      true,
      STREAM_CRYPTO_METHOD_TLS_CLIENT
    );

    if ($cryptoEnabled !== true) {
      $this->lastError = 'Failed to enable TLS encryption';

      return false;
    }

    // Re-send EHLO after TLS upgrade
    // @phpstan-ignore-next-line booleanNot.alwaysFalse - command can fail if socket issues occur post-TLS
    if (!$this->command("EHLO paycal.app\r\n", 250)) {
      $this->lastError = 'Post-TLS EHLO command failed';

      return false;
    }

    return true;
  }

  /**
   * Authenticate with SMTP server using AUTH LOGIN
   */
  private function authenticate(): bool
  {
    $usernames = [];
    $replyTo = Environment::emailReplyTo();
    $contact = Environment::emailContact();

    if (!empty($replyTo)) {
      $usernames[] = $replyTo;
    }

    if (!empty($contact) && $contact !== $replyTo) {
      $usernames[] = $contact;
    }

    if (empty($usernames)) {
      $this->lastError = 'SMTP credentials not configured';

      return false;
    }

    $password = Environment::emailPassword();

    if (empty($password)) {
      $this->lastError = 'SMTP credentials not configured';

      return false;
    }

    foreach ($usernames as $username) {
      if (!$this->command("AUTH LOGIN\r\n", 334)) {
        // Reset state and continue trying alternate username candidate.
        $this->command("RSET\r\n", 250);
        $this->command("EHLO paycal.app\r\n", 250);
        continue;
      }

      if (!$this->command(base64_encode($username)."\r\n", 334)) {
        $this->command("RSET\r\n", 250);
        $this->command("EHLO paycal.app\r\n", 250);
        continue;
      }

      if ($this->command(base64_encode($password)."\r\n", 235)) {
        return true;
      }

      $this->command("RSET\r\n", 250);
      $this->command("EHLO paycal.app\r\n", 250);
    }

    $this->lastError = 'Password authentication failed';

    return false;
  }

  /**
   * Set email sender
   */
  private function setSender(string $from): bool
  {
    // Extract email from "Name <email@domain.com>" format
    $email = $this->extractEmail($from);

    if (!$this->command("MAIL FROM: <{$email}>\r\n", 250)) {
      $this->lastError = "Failed to set sender: {$email}";

      return false;
    }

    return true;
  }

  /**
   * Add email recipient
   */
  private function addRecipient(string $recipient): bool
  {
    if (!$this->command("RCPT TO: <{$recipient}>\r\n", 250)) {
      $this->lastError = "Failed to add recipient: {$recipient}";

      return false;
    }

    return true;
  }

  /**
   * Build email message content
   *
   * @param array<string, string> $headers
   */
  private function buildMessage(
    string $from,
    string $to,
    string $subject,
    string $htmlBody,
    string $textBody,
    array $headers
  ): string {
    $eol = "\r\n";
    $boundary = uniqid('----PayCal_MIME_Boundary_');

    // Build headers
    $message = "Date: ".date('D, d M Y H:i:s O')."$eol";
    $message .= "Message-ID: <".uniqid('paycal.', true)."@paycal.app>$eol";
    $message .= "From: {$from}{$eol}";
    $message .= "To: {$to}{$eol}";
    $message .= "Subject: {$subject}{$eol}";
    $message .= "MIME-Version: 1.0{$eol}";

    // Add custom headers
    foreach ($headers as $name => $value) {
      $message .= "{$name}: {$value}{$eol}";
    }

    // Multipart message if text body is provided
    if (!empty($textBody)) {
      $message .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"{$eol}";
      $message .= $eol;

      // Plain text part
      $message .= "--{$boundary}{$eol}";
      $message .= "Content-Type: text/plain; charset=utf-8{$eol}";
      $message .= "Content-Transfer-Encoding: base64{$eol}";
      $message .= $eol;
      $message .= chunk_split(base64_encode($textBody), 76, "\r\n");

      // HTML part
      $message .= "--{$boundary}{$eol}";
      $message .= "Content-Type: text/html; charset=utf-8{$eol}";
      $message .= "Content-Transfer-Encoding: base64{$eol}";
      $message .= $eol;
      $message .= chunk_split(base64_encode($htmlBody), 76, "\r\n");

      $message .= "--{$boundary}--{$eol}";
    } else {
      // HTML only
      $message .= "Content-Type: text/html; charset=utf-8{$eol}";
      $message .= "Content-Transfer-Encoding: base64{$eol}";
      $message .= $eol;
      $message .= chunk_split(base64_encode($htmlBody), 76, "\r\n");
    }

    return $message;
  }

  /**
   * Send SMTP command and verify response code
   */
  private function command(string $command, int $expectedCode): bool
  {
    if (!$this->write($command)) {
      return false;
    }

    $response = $this->read();

    return str_starts_with($response, (string) $expectedCode);
  }

  /**
   * Write data to socket
   */
  private function write(string $data): bool
  {
    if (!is_resource($this->socket)) {
      return false;
    }

    return fwrite($this->socket, $data) !== false;
  }

  /**
   * Read response from socket (handles multiline responses)
   */
  private function read(): string
  {
    if (!is_resource($this->socket)) {
      return '';
    }

    $data = '';

    // Read until we hit a final response line (code followed by space)
    while (!feof($this->socket)) {
      $line = fgets($this->socket, self::READ_BUFFER_SIZE);
      if ($line === false) {
        break;
      }

      $data .= $line;

      // Check if this is the final line (format: "250 OK" vs "250-Extended")
      if (preg_match('/^\d{3} /', $line)) {
        break;
      }
    }

    return trim($data);
  }

  /**
   * Extract email address from "Name <email>" format
   */
  private function extractEmail(string $address): string
  {
    if (preg_match('/<(.+?)>/', $address, $matches)) {
      return $matches[1];
    }

    return $address;
  }

  /**
   * Close socket connection
   */
  private function disconnect(): void
  {
    if (is_resource($this->socket)) {
      @fclose($this->socket);
      $this->socket = null;
    }
  }
}
