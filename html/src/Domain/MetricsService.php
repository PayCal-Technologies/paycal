<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Telemetry\TelemetryAccessToken;
use PayCal\Infrastructure\Telemetry\TelemetryRepository;

/**
 * MetricsService.php
 *
 * Read-side metrics aggregation service for admin and diagnostics surfaces.
 *
 * Why this exists:
 * - Consolidate expensive metric collection behind bounded helper methods.
 * - Keep telemetry queries cache-backed to protect Redis under dashboard load.
 * - Enforce privacy boundaries by returning aggregate data only.
 */

/**
 * Aggregates operational metrics from Redis and runtime telemetry sources.
 *
 * Internal guarantees:
 * - Heavy reads are cached with explicit TTL constants.
 * - Returned payloads are normalized arrays suitable for API serialization.
 * - Namespace scans and metric extraction remain capped to avoid unbounded work.
 */

class MetricsService
{
  // Cache TTL constants (in seconds)
  /** @phpstan-ignore-next-line */
  private const CACHE_TTL_REDIS_INFO = 60;        // 1 minute
  /** @phpstan-ignore-next-line */
  private const CACHE_TTL_KEY_DIST = 300;         // 5 minutes
  /** @phpstan-ignore-next-line */
  private const CACHE_TTL_BUSINESS = 60;          // 1 minute
  /** @phpstan-ignore-next-line */
  private const CACHE_TTL_SESSION = 60;           // 1 minute
  /** @phpstan-ignore-next-line */
  private const CACHE_TTL_TELEMETRY = 60;         // 1 minute
  /** @phpstan-ignore-next-line */
  private const CACHE_TTL_SCRAPER = 60;           // 1 minute
  /** @phpstan-ignore-next-line */
  private const CACHE_TTL_CONTACT = 60;           // 1 minute
  /** @phpstan-ignore-next-line */
  private const CACHE_TTL_BILLING = 60;           // 1 minute

  /**
   * Maximum allowed namespace prefixes for key distribution
   */
  private const MAX_NAMESPACES = 10;

  /**
   * Session duration buckets (must match Authentication.php bucketing)
   */
  private const SESSION_DURATION_BUCKETS = ['0-5min', '5-30min', '30-60min', '60min+'];

  /**
   * Get Redis server health metrics from INFO command.
   * 
   * Purpose: Detect memory leaks, monitor capacity, track connection health
   * Scope Limit: INFO command built-in metrics only (no custom data inspection)
   * Privacy Boundary: Server-level stats only, no key content access
   * Query Limit: Cached for 60 seconds (max 1 req/min), admin-only
   * Volume Cap: Fixed set of ~15 INFO fields returned
   * 
   * @return array<string, mixed> Parsed INFO command output
   */
  public static function getRedisInfo(): array
  {
    // Guard: Check cache first (rate limiting)
    $cacheKey = Keys::CACHE . ':metrics:redis_info';
    $cached = Database::get($cacheKey);
    if ('' !== $cached) {
      return json_decode($cached, true) ?: [];
    }

    $info = Database::info();
    
    // Guard: Extract only whitelisted fields (prevent unbounded data return)
    $metrics = [
      'used_memory_mb' => round(($info['used_memory'] ?? 0) / 1048576, 2),
      'used_memory_peak_mb' => round(($info['used_memory_peak'] ?? 0) / 1048576, 2),
      'connected_clients' => (int)($info['connected_clients'] ?? 0),
      'total_connections_received' => (int)($info['total_connections_received'] ?? 0),
      'total_commands_processed' => (int)($info['total_commands_processed'] ?? 0),
      'keyspace_hits' => (int)($info['keyspace_hits'] ?? 0),
      'keyspace_misses' => (int)($info['keyspace_misses'] ?? 0),
      'evicted_keys' => (int)($info['evicted_keys'] ?? 0),
      'expired_keys' => (int)($info['expired_keys'] ?? 0),
      'uptime_in_seconds' => (int)($info['uptime_in_seconds'] ?? 0),
      'uptime_in_days' => (int)($info['uptime_in_days'] ?? 0),
      'redis_version' => (string)($info['redis_version'] ?? 'unknown'),
      'hit_rate_percent' => self::calculateHitRate($info),
    ];

    // Guard: Cache result to prevent INFO command spam
    Database::set($cacheKey, json_encode($metrics), self::CACHE_TTL_REDIS_INFO);

    return $metrics;
  }

  /**
   * Calculate Redis cache hit rate percentage.
   * 
   * @param array<string, mixed> $info Redis INFO array
   * @return float Hit rate as percentage (0-100)
   */
  private static function calculateHitRate(array $info): float
  {
    $hits = (int)($info['keyspace_hits'] ?? 0);
    $misses = (int)($info['keyspace_misses'] ?? 0);
    $total = $hits + $misses;

    if ($total === 0) {
      return 0.0;
    }

    return round(($hits / $total) * 100, 2);
  }

  /**
   * Get key distribution across Redis namespaces.
   * 
   * Purpose: Monitor data distribution for capacity planning
   * Scope Limit: Count-only, no key content or pattern matching beyond prefix
   * Privacy Boundary: Aggregate counts, no individual key inspection
   * Query Limit: Admin-only, cached for 300 seconds (max 1 req/5min)
   * Volume Cap: Max 10 namespace prefixes (enforced by hardcoded array)
   * 
   * @return array<string, int> Namespace prefix => key count
   */
  public static function getKeyDistribution(): array
  {
    // Guard: Check cache first
    $cacheKey = Keys::CACHE . ':metrics:key_distribution';
    $cached = Database::get($cacheKey);
    if ($cached !== null) {
      return json_decode($cached, true) ?: [];
    }

    // Guard: Hardcoded prefix whitelist (prevents unbounded scanning)
    $allowedPrefixes = [
      'user',
      'session',
      'work',
      'site',
      'telemetry',
      'lock',
      'nonce',
      'temp',
      'encryption',
      'cache',
    ];

    // Guard: Enforce max namespace limit
    if (count($allowedPrefixes) > self::MAX_NAMESPACES) {
      throw new \RuntimeException(
        'Key distribution exceeds maximum allowed namespaces (' . self::MAX_NAMESPACES . ')'
      );
    }

    $distribution = [];
    
    foreach ($allowedPrefixes as $prefix) {
      // Guard: Count only, no key content access
      $keys = Database::scanKeys($prefix . ':*');
      $distribution[$prefix] = count($keys);
    }

    // Guard: Cache result
    Database::set($cacheKey, json_encode($distribution), self::CACHE_TTL_KEY_DIST);

    return $distribution;
  }

  /**
   * Get aggregate business metrics.
   * 
   * Purpose: Monitor platform usage patterns for capacity planning
   * Scope Limit: Aggregate statistics only (mean, median, max)
   * Privacy Boundary: No individual user identification, no user-specific data returned
   * Query Limit: Admin-only, cached for 600 seconds (max 1 req/10min)
   * Volume Cap: Max 8 statistical values returned
   * 
   * @return array<string, int|float> Business statistics
   */
  public static function getBusinessMetrics(): array
  {
    // Guard: Check cache first
    $cacheKey = Keys::CACHE . ':metrics:business';
    $cached = Database::get($cacheKey);
    if ('' !== $cached) {
      return json_decode($cached, true) ?: [];
    }

    // Guard: Calculate aggregates without exposing individual users
    $userKeys = Database::scanKeys(Keys::USER . ':*');
    $totalUsers = count($userKeys);
    
    $workEntryCounts = [];
    foreach ($userKeys as $userKey) {
      $userUUID = str_replace(Keys::USER . ':', '', $userKey);
      $workKeys = Database::scanKeys(Keys::WORK . ':' . $userUUID . ':*');
      $workEntryCounts[] = count($workKeys);
      // Guard: No user UUID stored in metrics return value
    }

    $siteKeys = Database::scanKeys(Keys::SITE . ':*');
    $archivedSites = 0;
    foreach ($siteKeys as $siteKey) {
      $siteData = Database::hgetall($siteKey);
      if (($siteData['archived'] ?? 0) == 1) {
        $archivedSites++;
      }
    }

    $metrics = [
      'total_users' => $totalUsers,
      'total_work_entries' => array_sum($workEntryCounts),
      'avg_work_entries_per_user' => $totalUsers > 0 
        ? round(array_sum($workEntryCounts) / $totalUsers, 2) 
        : 0.0,
      'median_work_entries' => self::calculateMedian($workEntryCounts),
      'max_work_entries_single_user' => max($workEntryCounts ?: [0]),
      'total_sites' => count($siteKeys),
      'active_sites' => count($siteKeys) - $archivedSites,
      'archived_sites' => $archivedSites,
    ];

    // Guard: Verify no PII fields in return value (contract test enforces this)
    assert(!isset($metrics['user_uuid']), 'Business metrics must not contain user UUIDs');
    assert(count($metrics) <= 8, 'Business metrics must not exceed 8 values');

    // Guard: Cache result
    Database::set($cacheKey, json_encode($metrics), self::CACHE_TTL_BUSINESS);

    return $metrics;
  }

  /**
   * Calculate median value from array.
   * 
   * @param array<int|float> $values
   * @return float
   */
  private static function calculateMedian(array $values): float
  {
    if (empty($values)) {
      return 0.0;
    }

    sort($values);
    $count = count($values);
    $middle = (int)floor($count / 2);

    if ($count % 2 === 0) {
      // Even count: average of two middle values
      return ($values[$middle - 1] + $values[$middle]) / 2.0;
    }

    // Odd count: middle value
    return (float)$values[$middle];
  }

  /**
   * Get current session metrics snapshot.
   * 
   * Purpose: Monitor session health and authentication patterns
   * Scope Limit: Current active sessions + today's login/logout events
   * Privacy Boundary: Counts only, no session hashes or user identifiers
   * Query Limit: Admin-only, cached for 60 seconds (max 1 req/min)
   * Volume Cap: Fixed 7 values returned (1 active + 2 today + 4 duration buckets)
   * 
   * @return array<string, int> Session statistics
   */
  public static function getSessionMetrics(): array
  {
    // Guard: Check cache first
    $cacheKey = Keys::CACHE . ':metrics:sessions';
    $cached = Database::get($cacheKey);
    if ('' !== $cached) {
      return json_decode($cached, true) ?: [];
    }

    $today = date('Y-m-d');
    
    // Guard: Count only, no session content access
    $activeSessions = count(Database::scanKeys(Keys::SESSION . ':*'));

    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'bucket');
    $query = TelemetryRepository::fetchSessionLifecycleMetrics($token, $today, self::SESSION_DURATION_BUCKETS);
    if (!$query['allowed']) {
      throw new \RuntimeException('Session telemetry query denied: ' . $query['reason']);
    }

    $telemetry = $query['metrics'];
    $loginsToday = (int) ($telemetry['logins_today'] ?? 0);
    $logoutsToday = (int) ($telemetry['logouts_today'] ?? 0);

    $durationDistribution = [];
    foreach (self::SESSION_DURATION_BUCKETS as $bucket) {
      $durationDistribution[$bucket] = (int) ($telemetry['duration:' . $bucket] ?? 0);
    }

    $metrics = [
      'active_sessions' => $activeSessions,
      'logins_today' => $loginsToday,
      'logouts_today' => $logoutsToday,
      'duration_0_5min' => $durationDistribution['0-5min'],
      'duration_5_30min' => $durationDistribution['5-30min'],
      'duration_30_60min' => $durationDistribution['30-60min'],
      'duration_60min_plus' => $durationDistribution['60min+'],
    ];

    // Guard: Verify exactly 7 values (contract test enforces this)
    assert(count($metrics) === 7, 'Session metrics must return exactly 7 values');

    // Guard: Cache result
    Database::set($cacheKey, json_encode($metrics), self::CACHE_TTL_SESSION);

    return $metrics;
  }

  /**
   * Get recent telemetry event counts.
   * 
   * Purpose: Monitor frontend events and error rates
   * Scope Limit: Today's events only, whitelisted event types
   * Privacy Boundary: Event type counts only, no session/user identifiers
   * Query Limit: Admin-only, cached for 60 seconds (max 1 req/min)
   * Volume Cap: Max 20 event types returned
   * 
   * @return array<string, int> Event type => count for today
   */
  public static function getTelemetryEvents(): array
  {
    // Guard: Check cache first
    $cacheKey = Keys::CACHE . ':metrics:telemetry_events';
    $cached = Database::get($cacheKey);
    if ($cached !== null) {
      return json_decode($cached, true) ?: [];
    }

    $today = date('Y-m-d');
    
    // Guard: Hardcoded event type whitelist (prevents unbounded key scanning)
    $allowedEventTypes = [
      'calendar.load.success',
      'calendar.load.failure',
      'encryption.dek.wrap.success',
      'encryption.dek.wrap.failure',
      'encryption.dek.unwrap.success',
      'encryption.dek.unwrap.failure',
      'passkey.register.success',
      'passkey.register.failure',
      'passkey.login.success',
      'passkey.login.failure',
      'work.save.success',
      'work.save.failure',
      'work.delete.success',
      'work.delete.failure',
      'site.create.success',
      'site.create.failure',
    ];

    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'bucket');
    $query = TelemetryRepository::fetchWhitelistedEventCounts($token, $allowedEventTypes, $today);
    if (!$query['allowed']) {
      throw new \RuntimeException('Telemetry query denied: ' . $query['reason']);
    }
    $events = $query['events'];

    // Guard: Cache result
    Database::set($cacheKey, json_encode($events), self::CACHE_TTL_TELEMETRY);

    return $events;
  }

  /**
   * Get Stripe billing webhook telemetry summary.
   *
   * Purpose: Provide admin-safe aggregate visibility into webhook processing health.
   * Scope Limit: Fixed allowlist of counters and event types for the current day only.
   * Privacy Boundary: Aggregate counts only, no payload bodies, user IDs, or customer IDs.
   * Query Limit: Admin-only, cached for 60 seconds.
   *
   * @return array<string, mixed>
   */
  public static function getBillingWebhookMetrics(): array
  {
    $cacheKey = Keys::CACHE . ':metrics:billing_webhooks';
    $cached = Database::get($cacheKey);
    if ('' !== $cached) {
      return json_decode($cached, true) ?: [];
    }

    $today = date('Y-m-d');
    $outcomeMetrics = [
      'processed',
      'duplicate',
      'queued',
      'verification_failed',
      'event_rejected',
      'payload_empty',
      'signature_missing',
      'secret_key_missing',
      'webhook_secret_missing',
      'event_id_missing',
      'queue_encode_failed',
      'queue_enqueue_failed',
      'queue_requeued',
      'queue_payload_invalid',
      'queue_dead_lettered',
    ];

    $eventTypes = [
      'checkout.session.completed' => 'checkout_session_completed',
      'customer.subscription.created' => 'customer_subscription_created',
      'customer.subscription.updated' => 'customer_subscription_updated',
      'customer.subscription.deleted' => 'customer_subscription_deleted',
      'invoice.payment_failed' => 'invoice_payment_failed',
      'invoice.paid' => 'invoice_paid',
    ];

    $outcomes = [];
    foreach ($outcomeMetrics as $metric) {
      $outcomes[$metric] = self::readDailyMetric('billing:webhook:' . $metric, $today);
    }

    $eventSummary = [];
    foreach ($eventTypes as $label => $metricLabel) {
      $eventSummary[$label] = [
        'processed' => self::readDailyMetric('billing:webhook:processed:' . $metricLabel, $today),
        'duplicate' => self::readDailyMetric('billing:webhook:duplicate:' . $metricLabel, $today),
      ];
    }

    $recentDays = [];
    for ($daysAgo = 6; $daysAgo >= 0; $daysAgo -= 1) {
      $day = date('Y-m-d', strtotime('-' . $daysAgo . ' days'));
      $recentDays[] = [
        'date' => $day,
        'processed' => self::readDailyMetric('billing:webhook:processed', $day),
        'duplicate' => self::readDailyMetric('billing:webhook:duplicate', $day),
        'verification_failed' => self::readDailyMetric('billing:webhook:verification_failed', $day),
        'event_rejected' => self::readDailyMetric('billing:webhook:event_rejected', $day),
      ];
    }

    $recentThirtyDays = [];
    $rollingThirtyTotals = [
      'processed' => 0,
      'duplicate' => 0,
      'verification_failed' => 0,
      'event_rejected' => 0,
    ];

    for ($daysAgo = 29; $daysAgo >= 0; $daysAgo -= 1) {
      $day = date('Y-m-d', strtotime('-' . $daysAgo . ' days'));
      $row = [
        'date' => $day,
        'processed' => self::readDailyMetric('billing:webhook:processed', $day),
        'duplicate' => self::readDailyMetric('billing:webhook:duplicate', $day),
        'verification_failed' => self::readDailyMetric('billing:webhook:verification_failed', $day),
        'event_rejected' => self::readDailyMetric('billing:webhook:event_rejected', $day),
      ];

      $rollingThirtyTotals['processed'] += $row['processed'];
      $rollingThirtyTotals['duplicate'] += $row['duplicate'];
      $rollingThirtyTotals['verification_failed'] += $row['verification_failed'];
      $rollingThirtyTotals['event_rejected'] += $row['event_rejected'];
      $recentThirtyDays[] = $row;
    }

    $metrics = [
      'date' => $today,
      'outcomes' => $outcomes,
      'event_types' => $eventSummary,
      'recent_days' => $recentDays,
      'recent_30_days' => $recentThirtyDays,
      'rolling_30_totals' => $rollingThirtyTotals,
    ];

    Database::set($cacheKey, json_encode($metrics), self::CACHE_TTL_BILLING);

    return $metrics;
  }

  /**
   * Handles readDailyMetric operation.
   */
  private static function readDailyMetric(string $metricSuffix, string $day): int
  {
    return (int) (Database::get(Keys::TELEMETRY . ':' . $metricSuffix . ':' . $day) ?: 0);
  }

  /**
   * Get scraper-defense attempt metrics and netblock leaderboard.
   *
   * Purpose: Measure hostile automated request volume without exposing raw IPs
   * Scope Limit: Aggregate counters + netblock groups only
   * Privacy Boundary: No individual IP addresses returned
   * Query Limit: Admin-only, cached for 60 seconds
   * Volume Cap: Top 10 netblocks only
   *
   * @return array<string, mixed>
   */
  public static function getScraperDefenseMetrics(): array
  {
    $cacheKey = Keys::CACHE . ':metrics:scraper_defense';
    $cached = Database::get($cacheKey);
    if ('' !== $cached) {
      return json_decode($cached, true) ?: [];
    }

    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'bucket');
    $query = TelemetryRepository::fetchScraperDefenseMetrics($token);
    if (!$query['allowed']) {
      throw new \RuntimeException('Scraper telemetry query denied: ' . $query['reason']);
    }
    $metrics = $query['metrics'];

    Database::set($cacheKey, json_encode($metrics), self::CACHE_TTL_SCRAPER);

    return $metrics;
  }

  /**
   * Get contact support submission metrics and logging health.
   *
   * @return array<string, mixed>
   */
  public static function getContactSupportMetrics(): array
  {
    $cacheKey = Keys::CACHE . ':metrics:contact_support';
    $cached = Database::get($cacheKey);
    if ('' !== $cached) {
      return json_decode($cached, true) ?: [];
    }

    $token = new TelemetryAccessToken(TelemetryPolicy::STREAM_PRODUCT, 'product', 'default', 'bucket');
    $query = TelemetryRepository::fetchContactSupportMetrics($token);
    if (!$query['allowed']) {
      throw new \RuntimeException('Contact telemetry query denied: ' . $query['reason']);
    }

    $metrics = $query['metrics'];

    Database::set($cacheKey, json_encode($metrics), self::CACHE_TTL_CONTACT);

    return $metrics;
  }

}

