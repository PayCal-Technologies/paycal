<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Response;
use PayCal\Domain\Security;
use PayCal\Domain\SecurityLog;

/**
 * SecurityController
 *
 * Security endpoints for browser-surface telemetry ingestion.
 */
final class SecurityController
{
  /**
   * Handles clipScalar operation.
   */
  private static function clipScalar(mixed $value, int $max = 512): string
  {
    if (!is_scalar($value)) {
      return '';
    }

    $text = trim((string) $value);
    if ($text === '') {
      return '';
    }

    return mb_substr($text, 0, $max);
  }

  /**
   * Accept CSP violation reports from browser report-to/report-uri channels.
   */
  #[Route('security/csp/report', ['POST'])]
  /**
   * Handles ingestCspReport operation.
   */
  public function ingestCspReport(): void
  {
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
      Response::success('[Security] CSP report accepted.', ['accepted' => true], HttpStatus::HTTP_OK);
      return;
    }

    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) {
      Response::success('[Security] CSP report accepted.', ['accepted' => true], HttpStatus::HTTP_OK);
      return;
    }

    /** @var array<string, mixed> $decoded */
    $report = $decoded['csp-report'] ?? $decoded;
    if (!is_array($report)) {
      $report = [];
    }

    SecurityLog::log('csp_violation', [
      'blocked_uri' => self::clipScalar($report['blocked-uri'] ?? ''),
      'document_uri' => self::clipScalar($report['document-uri'] ?? ''),
      'violated_directive' => self::clipScalar($report['violated-directive'] ?? ''),
      'effective_directive' => self::clipScalar($report['effective-directive'] ?? ''),
      'source_file' => self::clipScalar($report['source-file'] ?? ''),
      'line_number' => self::clipScalar($report['line-number'] ?? ''),
      'column_number' => self::clipScalar($report['column-number'] ?? ''),
      'disposition' => self::clipScalar($report['disposition'] ?? ''),
      'status_code' => self::clipScalar($report['status-code'] ?? ''),
      'sample' => self::clipScalar($report['script-sample'] ?? '', 256),
      'ip' => Security::getClientIPAddress(),
    ]);

    Response::success('[Security] CSP report accepted.', ['accepted' => true], HttpStatus::HTTP_OK);
  }
}


