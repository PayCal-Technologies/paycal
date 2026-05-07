<?php declare(strict_types=1);

namespace PayCal\Controllers;

use PayCal\Domain\Attributes\Route;
use PayCal\Domain\Log;
use PayCal\Domain\MetricsService;
use PayCal\Domain\Response;
use PayCal\Infrastructure\Queue\StripeBillingQueueMonitor;
use PayCal\Domain\User;

/**
 * HealthController.php
 *
 * Purpose: Read-only health and metrics controller for platform diagnostics,
 * queue status, and admin-facing operational snapshots.
 *
 * Developer notes:
 * - Health payloads are operationally sensitive and must remain admin-gated.
 * - Keep this controller read-only; write or repair behavior belongs elsewhere.
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
 * Platform health API surface.
 *
 * Responsibilities:
 * - Aggregate admin-only health metrics from multiple subsystems.
 * - Present queue and reliability snapshots for diagnostics use.
 * - Avoid introducing side effects while collecting status information.
 */
class HealthController
{
  /**
   * Get comprehensive platform health metrics.
   * 
   * Returns combined response with all metrics:
   * - Redis server health (INFO command)
   * - Key distribution across namespaces
   * - Business metrics (users, work entries, sites)
   * - Session metrics (active, logins, duration distribution)
   * - Telemetry events (success/failure rates)
   * 
   * @return array<string, mixed> JSON-serializable health snapshot
   */
  public static function getHealthSnapshot(): array
  {
    // Guard: Admin-only access
    if (!User::isAdmin()) {
      http_response_code(403);
      return ['error' => 'Forbidden: Admin access required'];
    }

    try {
      return [
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'redis' => MetricsService::getRedisInfo(),
        'keys' => MetricsService::getKeyDistribution(),
        'business' => MetricsService::getBusinessMetrics(),
        'sessions' => MetricsService::getSessionMetrics(),
        'telemetry' => MetricsService::getTelemetryEvents(),
        'billing_webhooks' => MetricsService::getBillingWebhookMetrics(),
        'webhook_queue' => StripeBillingQueueMonitor::getQueueHealth(),
        'scraper_defense' => MetricsService::getScraperDefenseMetrics(),
        'contact' => MetricsService::getContactSupportMetrics(),
      ];
    } catch (\Exception $e) {
      http_response_code(500);
      Log::error('[Health] getHealthSnapshot exception: ' . $e->getMessage());
      return [
        'status' => 'error',
        'error' => 'Failed to retrieve health metrics',
      ];
    }
  }

  /**
   * Get basic health check (public endpoint).
   * 
   * Returns minimal status for external monitoring.
   * No authentication required.
   * 
   * Emit basic health status for public monitoring.
   */
  #[Route('health', ['GET'])]
  /**
   * Handles getHealthCheck operation.
   */
  public function getHealthCheck(): void
  {
    try {
      $redis = MetricsService::getRedisInfo();

      Response::json('ok', 'Health check passed.', 200, [
        'redis' => 'connected',
        'uptime_days' => strval($redis['uptime_in_days'] ?? 0),
      ]);
    } catch (\Exception $e) {
      Response::json('error', 'Service unavailable.', 503, [
        'redis' => 'disconnected',
        'error' => 'Service unavailable',
      ]);
    }
  }

  /**
   * Get Redis-specific health metrics.
   * Admin-only endpoint.
   * 
   * @return array<string, mixed> Redis INFO metrics
   */
  public static function getRedisHealth(): array
  {
    // Guard: Admin-only access
    if (!User::isAdmin()) {
      http_response_code(403);
      return ['error' => 'Forbidden: Admin access required'];
    }

    try {
      return MetricsService::getRedisInfo();
    } catch (\Exception $e) {
      http_response_code(500);
      Log::error('[Health] getRedisHealth exception: ' . $e->getMessage());
      return [
        'error' => 'Failed to retrieve Redis metrics',
      ];
    }
  }

  /**
   * Get session lifecycle metrics.
   * Admin-only endpoint.
   * 
  * @return array<string, mixed> Session metrics or error message
   */
  public static function getSessionHealth(): array
  {
    // Guard: Admin-only access
    if (!User::isAdmin()) {
      http_response_code(403);
      return ['error' => 'Forbidden: Admin access required'];
    }

    try {
      return MetricsService::getSessionMetrics();
    } catch (\Exception $e) {
      http_response_code(500);
      Log::error('[Health] getSessionHealth exception: ' . $e->getMessage());
      return [
        'error' => 'Failed to retrieve session metrics',
      ];
    }
  }

  /**
   * Get business metrics.
   * Admin-only endpoint.
   * 
  * @return array<string, mixed> Business statistics or error message
   */
  public static function getBusinessHealth(): array
  {
    // Guard: Admin-only access
    if (!User::isAdmin()) {
      http_response_code(403);
      return ['error' => 'Forbidden: Admin access required'];
    }

    try {
      return MetricsService::getBusinessMetrics();
    } catch (\Exception $e) {
      http_response_code(500);
      Log::error('[Health] getBusinessHealth exception: ' . $e->getMessage());
      return [
        'error' => 'Failed to retrieve business metrics',
      ];
    }
  }

  /**
   * Get Stripe webhook queue health metrics.
   * Admin-only endpoint.
   * 
   * Returns queue depth, dead-letter depth, and any active alerts.
   * 
   * @return array<string, mixed> Queue health status or error message
   */
  public static function getWebhookQueueHealth(): array
  {
    // Guard: Admin-only access
    if (!User::isAdmin()) {
      http_response_code(403);
      return ['error' => 'Forbidden: Admin access required'];
    }

    try {
      $health = StripeBillingQueueMonitor::getQueueHealth();
      return [
        'healthy' => $health['healthy'],
        'queue_depth' => $health['queue_depth'],
        'queue_percent' => $health['queue_percent'],
        'dead_letter_depth' => $health['dead_letter_depth'],
        'dead_letter_percent' => $health['dead_letter_percent'],
        'alerts' => $health['alerts'],
      ];
    } catch (\Exception $e) {
      http_response_code(500);
      Log::error('[Health] getWebhookQueueHealth exception: ' . $e->getMessage());
      return [
        'error' => 'Failed to retrieve webhook queue metrics',
      ];
    }
  }
}


