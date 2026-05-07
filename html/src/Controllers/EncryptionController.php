<?php declare(strict_types=1);

namespace PayCal\Controllers;

use Throwable;
use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Authentication;
use PayCal\Domain\ClientCapabilities;
use PayCal\Domain\CryptoVersions;
use PayCal\Domain\Database;
use PayCal\Domain\Config\EncryptionConfig;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Domain\Log;
use PayCal\Domain\Response;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Telemetry\TelemetryAccessToken;
use PayCal\Infrastructure\Telemetry\TelemetryRepository;
use PayCal\Domain\User;

/**
 * EncryptionController.php
 *
 * Purpose: Encryption capability and telemetry controller for client crypto
 * readiness, envelope-version support, and observability-safe reporting.
 *
 * Developer notes:
 * - This controller sits at the boundary between browser crypto capabilities
 *   and server-side policy/telemetry.
 * - Capability reporting should remain lightweight and rate-limited because it
 *   can be emitted by many clients automatically.
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
 * Encryption capability API surface.
 *
 * Responsibilities:
 * - Accept and persist client capability reports.
 * - Surface active crypto-version/configuration metadata to the client.
 * - Record bounded telemetry around encryption readiness and failures.
 */
class EncryptionController
{
  private const TELEMETRY_RATE_TTL_SECONDS = 60;
  private const TELEMETRY_EVENT_LIST_MAX_ITEMS = 1000;

  /**
   * Reports client cryptographic capabilities to the server.
   * Called by the client after detecting WebCrypto support.
   * Stores the capability report for future reference.
   *
   * Expected POST body:
   * {
   *   "webCryptoSupported": bool,
   *   "aesGcmSupported": bool,
   *   "pbkdf2Supported": bool,
   *   "userAgent": string
   * }
   */
  #[Route('encryption/capabilities', ['POST'])]
  /**
   * Handles reportCapabilities operation.
   */
  public function reportCapabilities(): void
  {
    // Check if encryption is enabled
    if (!\PayCal\Domain\Config\EncryptionConfig::isEnabled()) {
      \PayCal\Domain\Response::error('[EncryptionC] Encryption is disabled.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_SERVICE_UNAVAILABLE);
      return;
    }

    if (!Authentication::validateAndTouchSession()) {
      \PayCal\Domain\Response::error('[EncryptionC] User not authenticated.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    $userId = User::currentUUID();

    // Get JSON body
    $raw = file_get_contents('php://input');
    if ($raw === false) {
      \PayCal\Domain\Response::error('[EncryptionC] Failed to read input.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_BAD_REQUEST);

      return;
    }
    if (strlen($raw) > SystemConfig::MAX_STRING_LENGTH) {
      throw new \RuntimeException('Input exceeds maximum allowed length of ' . SystemConfig::MAX_STRING_LENGTH . ' bytes');
    }
    $body = json_decode($raw, true);

    if (!is_array($body)) {
      \PayCal\Domain\Response::error('[EncryptionC] Invalid request body.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    // Build capability report with current timestamp
    $userAgentRaw = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    $userAgent = is_scalar($userAgentRaw) ? (string) $userAgentRaw : 'Unknown';
    $report = [
        'webCryptoSupported' => (bool) ($body['webCryptoSupported'] ?? false),
        'aesGcmSupported' => (bool) ($body['aesGcmSupported'] ?? false),
        'pbkdf2Supported' => (bool) ($body['pbkdf2Supported'] ?? false),
      'userAgent' => $userAgent,
        'timestamp' => (int) ceil(microtime(true) * 1000),
    ];

    // Validate and store
    try {
      ClientCapabilities::store($userId, $report);

      Response::success(
        '[EncryptionC] Capabilities recorded.',
        [
              'stored' => true,
              'supportsEncryption' => ClientCapabilities::supportsEncryption($userId),
          ],
        \PayCal\Domain\Enums\HttpStatus::HTTP_OK
      );
    } catch (Throwable $e) {
      Log::error('[EncryptionC] Failed to store capabilities: '.$e->getMessage());
      \PayCal\Domain\Response::error(
        '[EncryptionC] Failed to record capabilities.',
        [],
        \PayCal\Domain\Enums\HttpStatus::HTTP_INTERNAL_SERVER_ERROR
      );
    }
  }

  /**
   * Returns encryption version information.
   * Always available (no auth required), useful for clients during initialization.
   *
   * Returns:
   * {
   *   "algorithm": {
   *     "version": int,
   *     "name": string
   *   },
   *   "kdf": {
   *     "version": int,
   *     "name": string,
   *     "iterations": int,
   *     "hashAlgo": string
   *   },
   *   "envelope": {
   *     "version": int
   *   }
   * }
   */
  #[Route('encryption/versions', ['GET'])]
  /**
   * Handles getVersionInfo operation.
   */
  public function getVersionInfo(): void
  {
    // Require authentication to access version information
    if (!Authentication::validateAndTouchSession()) {
      Response::error('[EncryptionC] User not authenticated.', [], HttpStatus::HTTP_UNAUTHORIZED);
      return;
    }

    Response::success(
      '[EncryptionC] Version information.',
      CryptoVersions::getVersionInfo()
    );
  }

  /**
   * Returns encryption configuration for this server instance.
   * Helps clients understand what encryption capabilities are enabled.
   * Requires authentication.
   *
   * Returns:
   * {
   *   "crypto_enabled": bool,
   *   "crypto_required": bool
   * }
   */
  #[Route('encryption/config', ['GET'])]
  /**
   * Handles getConfig operation.
   */
  public function getConfig(): void
  {
    // Always allow config endpoint - it returns the enabled status
    if (!Authentication::validateAndTouchSession()) {
      \PayCal\Domain\Response::error('[EncryptionC] User not authenticated.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    $userId = User::currentUUID();

    Response::success(
      '[EncryptionC] Encryption configuration.',
      \PayCal\Domain\Config\EncryptionConfig::getConfig()
    );
  }

  /**
   * Receives client telemetry events related to encryption/decryption.
   * Expected JSON body: { type: string, site?: string, error?: string, blobLength?: int }
   * Increments Redis counters and logs the event for debugging.
   */
  #[Route('encryption/telemetry', ['POST'])]
  /**
   * Handles reportTelemetry operation.
   */
  public function reportTelemetry(): void
  {
    // Check if encryption is enabled; silently accept when disabled so callers
    // (e.g. PhantomWing diagnostics) do not receive a 503 on feature-flagged environments.
    if (!\PayCal\Domain\Config\EncryptionConfig::isEnabled()) {
      \PayCal\Domain\Response::success('[EncryptionC] Telemetry accepted.', [], \PayCal\Domain\Enums\HttpStatus::HTTP_OK);
      return;
    }

    if (!Authentication::validateAndTouchSession()) {
      \PayCal\Domain\Response::error('[EncryptionC] User not authenticated.', [], HttpStatus::HTTP_UNAUTHORIZED);

      return;
    }

    $userId = User::currentUUID();

    $raw = file_get_contents('php://input');
    if ($raw === false) {
      \PayCal\Domain\Response::error('[EncryptionC] Failed to read input.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }
    $body = json_decode($raw, true);
    if (!is_array($body) || !isset($body['type'])) {
      \PayCal\Domain\Response::error('[EncryptionC] Invalid telemetry payload.', [], HttpStatus::HTTP_BAD_REQUEST);

      return;
    }

    $typeRaw = $body['type'];
    $type = is_scalar($typeRaw) ? (string) $typeRaw : '';
    $siteRaw = $body['site'] ?? null;
    $site = is_scalar($siteRaw) ? (string) $siteRaw : 'unknown';
    $errorRaw = $body['error'] ?? null;
    $error = is_scalar($errorRaw) ? (string) $errorRaw : '';
    $blobLengthRaw = $body['blobLength'] ?? null;
    $blobLength = is_scalar($blobLengthRaw) ? (int) $blobLengthRaw : 0;

    try {
      $v = SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;

      // Rate limiting per-user per-minute
      $rateKey = "telemetry:encryption:{$v}:rate:{$userId}";
      $count = \PayCal\Domain\Database::incr($rateKey);
      if (1 === $count) {
        \PayCal\Domain\Database::expire($rateKey, self::TELEMETRY_RATE_TTL_SECONDS);
      }
      $threshold = SystemConfig::ENCRYPTION_KEY_CACHE_TTL;
      if ($count > $threshold) {
        // drop silently but record that we suppressed events so spikes aren't masked
        try {
          \PayCal\Domain\Database::incr("telemetry:encryption:{$v}:rate_exceeded");
        } catch (Throwable $e) {
        }
        Log::info('[EncryptionC] Telemetry rate exceeded', "user={$userId} count={$count}");
        Response::success('[EncryptionC] Telemetry accepted (rate-limited).', [], HttpStatus::HTTP_OK);

        return;
      }

      // Increment a per-event counter
      \PayCal\Domain\Database::incr("telemetry:encryption:{$v}:client:{$type}");
      // Also increment a per-user counter
      \PayCal\Domain\Database::incr("telemetry:encryption:{$v}:user:{$userId}:{$type}");

      // Atomically push and cap list
      $payload = json_encode(['t' => time(), 'u' => $userId, 's' => $site, 'e' => $error, 'b' => $blobLength]);
      $listKey = "telemetry:encryption:{$v}:events";
      \PayCal\Domain\Database::multi(function ($r) use ($listKey, $payload): void {
        $r->lPush($listKey, $payload);
        $r->lTrim($listKey, 0, self::TELEMETRY_EVENT_LIST_MAX_ITEMS - 1);
      });

      Log::info("[EncryptionC] Telemetry: {$type}", "user={$userId} site={$site} blobLength={$blobLength}");

      Response::success('[EncryptionC] Telemetry recorded.', [], HttpStatus::HTTP_OK);
    } catch (Throwable $e) {
      Log::error('[EncryptionC] Telemetry failed: '.$e->getMessage());
      \PayCal\Domain\Response::error('[EncryptionC] Telemetry failed.', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  /**
   * Aggregation endpoint for encryption telemetry counters.
   * GET /api/v1/system/encryption/telemetry.
   */
  #[Route('system/encryption/telemetry', ['GET'])]
  /**
   * Handles getTelemetrySummary operation.
   */
  public function getTelemetrySummary(): void
  {
    if (!Authentication::validateAndTouchSession() || !\PayCal\Domain\User::isAdmin()) {
      \PayCal\Domain\Response::error('[EncryptionC] Admin access required.', [], HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    $v = SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
    $types = ['encryption-success', 'encryption-failure', 'decryption-success', 'decryption-failure'];

    $joinStreamRaw = $_GET['join_stream'] ?? 'security';
    $joinStream = is_scalar($joinStreamRaw) ? strtolower(trim((string) $joinStreamRaw)) : 'security';
    if ($joinStream === '') {
      $joinStream = 'security';
    }

    $token = new TelemetryAccessToken('security', 'security-admin', 'forensics', 'bucket');
    $joinDecision = TelemetryRepository::guardCrossStreamJoin($token, 'security', $joinStream);
    if (!$joinDecision['allowed']) {
      Response::error('[EncryptionC] Telemetry query denied.', ['reason' => $joinDecision['reason']], HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    $query = TelemetryRepository::fetchEncryptionClientCounters($token, $v, $types);
    if (!$query['allowed']) {
      Response::error('[EncryptionC] Telemetry query denied.', ['reason' => $query['reason']], HttpStatus::HTTP_FORBIDDEN);

      return;
    }

    $summary = ['schema' => $v];
    foreach ($query['counters'] as $eventType => $count) {
      $summary[$eventType] = $count;
    }

    // Compute decryption success rate when we have data
    $success = (int) ($summary['decryption-success'] ?? 0);
    $failure = (int) ($summary['decryption-failure'] ?? 0);
    $denom = $success + $failure;
    $summary['decryption-success-rate'] = $denom > 0 ? ($success / $denom) : null;

    // Expose the configured rollout threshold for easy comparison in admin UI
    $summary['decryption-min-success-rate'] = 0.95;

    Response::success('[EncryptionC] Telemetry summary.', $summary, HttpStatus::HTTP_OK);
  }
}

