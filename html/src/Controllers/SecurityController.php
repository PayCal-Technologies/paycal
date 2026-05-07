<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Response;
use PayCal\Domain\Security;
use PayCal\Infrastructure\Telemetry\SecurityLog;

/**
 * SecurityController.php
 *
 * Purpose: Security-event ingestion controller for browser telemetry and
 * client-reported signals that feed audit and abuse-detection pipelines.
 *
 * Developer notes:
 * - Payload clipping and normalization here protect downstream logging paths.
 * - Keep this controller narrowly focused on intake and bounded shaping.
 *
 * Architectural role:
 * - Entry-point controller for request handling, authorization enforcement,
 *   and response or render shaping at the web boundary.
 * - Domain policy, persistence rules, and side-effect orchestration should
 *   stay in collaborators rather than expanding controller state.
 *
 * @category   Controllers
 * @package    PayCal\Controllers
 * @subpackage HTTP
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 * @version    1.051.001
 */

/**
 * Security telemetry API surface.
 *
 * Responsibilities:
 * - Accept browser-surface security telemetry and normalize bounded payloads.
 * - Forward auditable client-reported security signals into downstream logging.
 * - Keep intake behavior narrowly scoped to ingestion rather than policy decisions.
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


