<?php declare(strict_types=1);

namespace PayCal\Observability;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\InputSanitizer;

/**
 * Lens.php
 *
 * Request-scoped observability buffer for development diagnostics.
 *
 * Why this exists:
 * - Provide structured breadcrumbs, timers, and counters while developing.
 * - Keep diagnostics in-process so no external collector is required.
 * - Offer predictable payload shape for UI renderers and API debug injection.
 */


/**
 * PayCal Lens.
 *
 * Operational boundaries:
 * - DEV-only activation via environment guards.
 * - No network side effects and no persistent storage writes.
 * - Render path is opt-in (`?lens=1`) unless force-render is explicitly enabled.
 *
 * Internal contracts:
 * - Payload schema remains stable for `_lens` API embedding and dashboard use.
 * - Non-scalar values are normalized to bound payload depth and size.
 * - Timers are explicit start/end pairs keyed by caller-selected labels.
 *
 * Data Schema:
 *
 * self::$payload = [
 *   'meta' => [
 *     'route' => string,
 *     'method' => string,
 *     'env' => string,
 *     'php_version' => string,
 *     'start_time' => float,
 *     'end_time' => float,
 *     'duration_ms' => float,
 *     'peak_memory_bytes' => int,
 *     'included_files' => int,
 *   ],
 *   'events' => [
 *     [
 *       'label' => string,
 *       'type' => string,
 *       'timestamp' => float,
 *       'memory_bytes' => int,
 *       'payload' => mixed (normalized)
 *     ]
 *   ],
 *   'timers' => [
 *     [
 *       'label' => string,
 *       'start' => float,
 *       'end' => float,
 *       'duration_ms' => float
 *     ]
 *   ],
 *   'counters' => array<string,int>
 * ];
 *
 * Usage:
 *   Lens::boot($route);
 *   Lens::add('Payroll Input', $data);
 *   Lens::timeStart('Redis Fetch');
 *   ...
 *   Lens::timeEnd('Redis Fetch');
 *   Lens::render();
 */

final class Lens
{
  private const MAX_DEPTH = 3;

  private static bool $enabled = false;
  
  private static bool $forceRender = false;

  /** @var array{meta: array<string, mixed>, events: array<int, array<string, mixed>>, timers: array<int, array<string, mixed>>, counters: array<string, int>} */
  private static array $payload = [
    'meta' => [],
    'events' => [],
    'timers' => [],
    'counters' => []
  ];

  /** @var array<string, float> */
  private static array $activeTimers = [];

  /**
   * Handles toString operation.
   */
  private static function toString(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Handles toFloat operation.
   */
  private static function toFloat(mixed $value, float $default = 0.0): float
  {
    return is_numeric($value) ? (float) $value : $default;
  }

  /** @return array<string, mixed> */
  private static function assoc(mixed $value): array
  {
    if (!is_array($value)) {
      return [];
    }

    $out = [];
    foreach ($value as $k => $v) {
      $out[(string) $k] = $v;
    }

    return $out;
  }

  /** @return array<int, array<string, mixed>> */
  private static function listAssoc(mixed $value): array
  {
    if (!is_array($value)) {
      return [];
    }

    $out = [];
    foreach ($value as $item) {
      if (!is_array($item)) {
        continue;
      }
      $out[] = self::assoc($item);
    }

    return $out;
  }

  /**
   * Initialize Lens for a request.
   *
   * @param string $route
   * @return void
   */
  public static function boot(string $route): void
  {
    if (!self::isDev()) {
      return;
    }

    self::$enabled = true;

    self::$payload['meta'] = [
      'route' => $route,
      'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
      'env' => Environment::appEnv(),
      'php_version' => PHP_VERSION,
      'start_time' => microtime(true),
      'end_time' => 0.0,
      'duration_ms' => 0.0,
      'peak_memory_bytes' => 0,
      'included_files' => 0,
    ];
  }

  /**
   * Force Lens to render on this request, bypassing the ?lens=1 requirement.
   * 
   * Useful for critical debug pages like signin that need observability.
   *
   * @return void
   */
  public static function forceRender(): void
  {
    self::$forceRender = true;
  }

  /**
   * Get the collected Lens data as an array.
   *
   * @return array<string, mixed>
   */
  public static function data(): array
  {
    if (!self::$enabled) {
      return [];
    }

    return self::$payload;
  }

  /**
   * Log an event or data point.
   *
   * @param string $label
   * @param mixed $value
   * @param string $type
   * @return void
   */
  public static function add(string $label, mixed $value, string $type = 'data'): void
  {
    if (!self::$enabled) {
      return;
    }

    self::$payload['events'][] = [
      'label' => $label,
      'type' => $type,
      'timestamp' => microtime(true),
      'memory_bytes' => memory_get_usage(),
      'payload' => self::normalize($value)
    ];
  }

  /**
   * Start a timer.
   *
   * @param string $label
   * @return void
   */
  public static function timeStart(string $label): void
  {
    if (!self::$enabled) {
      return;
    }

    self::$activeTimers[$label] = microtime(true);
  }

  /**
   * End a timer.
   *
   * @param string $label
   * @return void
   */
  public static function timeEnd(string $label): void
  {
    if (!self::$enabled || !isset(self::$activeTimers[$label])) {
      return;
    }

    $start = self::$activeTimers[$label];
    $end = microtime(true);

    self::$payload['timers'][] = [
      'label' => $label,
      'start' => $start,
      'end' => $end,
      'duration_ms' => ($end - $start) * 1000
    ];

    unset(self::$activeTimers[$label]);
  }

  /**
   * Increment a counter.
   *
   * @param string $key
   * @param int $by
   * @return void
   */
  public static function increment(string $key, int $by = 1): void
  {
    if (!self::$enabled) {
      return;
    }

    $counters = self::$payload['counters'];
    if (!isset($counters[$key])) {
      $counters[$key] = 0;
    }

    $counters[$key] += $by;
    self::$payload['counters'] = $counters;
  }

  /**
   * Finalizes request metrics and emits Lens output when render guards pass.
   *
   * @return void
   */
  public static function render(): void
  {
    // Guard: Framework not enabled for this request
    if (!self::$enabled) {
      return;
    }

    // Guard: Headers already sent (can't inject script)
    if (headers_sent()) {
      return;
    }

    // Guard: Not an HTML response (skip JSON, redirects, etc.)
    if (!self::isHtmlResponse()) {
      return;
    }

    // Guard: Lens not explicitly requested via ?lens=1 (opt-in safety)
    if (!self::isLensRequested()) {
      return;
    }

    $meta = self::$payload['meta'];
    $endTime = microtime(true);
    $startTime = self::toFloat($meta['start_time'] ?? 0.0, 0.0);
    $meta['end_time'] = $endTime;
    $meta['duration_ms'] = ($endTime - $startTime) * 1000;

    $meta['peak_memory_bytes'] = memory_get_peak_usage();
    $meta['included_files'] = count(get_included_files());
    self::$payload['meta'] = $meta;

    $json = json_encode(self::$payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
      return;
    }

    // If force-rendered, show visible panel; otherwise just console output
    if (self::$forceRender) {
      echo self::renderVisiblePanel($json);
    } else {
      echo self::renderConsoleScript($json);
    }
  }

  /**
   * Render a visible debug panel (for signin and other critical pages).
   * Uses NO inline styles to avoid CSP violations.
   *
   * @param string $json
   * @return string
   */
  private static function renderVisiblePanel(string $json): string
  {
    $data = self::assoc(json_decode($json, true));
    $meta = self::assoc($data['meta'] ?? []);
    $timers = self::listAssoc($data['timers'] ?? []);
    $events = self::listAssoc($data['events'] ?? []);
    
    // Render plain visible output with no inline styles
    $html = '';
    $html .= '<hr><section id="lens_debug_panel"><h3>🔎 PayCal Lens Debug</h3>';
    
    $html .= '<p><strong>Route:</strong> ' . htmlspecialchars(self::toString($meta['route'] ?? '')) . '</p>';
    $html .= '<p><strong>Method:</strong> ' . htmlspecialchars(self::toString($meta['method'] ?? '')) . '</p>';
    $html .= '<p><strong>Duration:</strong> ' . round(self::toFloat($meta['duration_ms'] ?? 0), 2) . 'ms</p>';
    $html .= '<p><strong>Peak Memory:</strong> ' . round(self::toFloat($meta['peak_memory_bytes'] ?? 0) / 1024 / 1024, 2) . 'MB</p>';
    
    // Timers
    if ([] !== $timers) {
      $html .= '<h4>⏱ Timers (Performance):</h4>';
      $html .= '<ul>';
      foreach ($timers as $timer) {
        $html .= '<li>' . htmlspecialchars(self::toString($timer['label'] ?? '')) . ': <strong>' . round(self::toFloat($timer['duration_ms'] ?? 0), 2) . 'ms</strong></li>';
      }
      $html .= '</ul>';
    }
    
    // Events
    if ([] !== $events) {
      $html .= '<h4>📦 Events (Last 10):</h4>';
      $html .= '<ul>';
      foreach (array_slice($events, -10) as $event) {
        $html .= '<li>[' . strtoupper(htmlspecialchars(self::toString($event['type'] ?? ''))) . '] ' . htmlspecialchars(self::toString($event['label'] ?? '')) . '</li>';
      }
      $html .= '</ul>';
    }
    
    $html .= '<p><em>📋 Open Browser Console (F12) for complete details and full event payloads.</em></p>';
    $html .= '</section><hr>';
    
    // Also include console script for detailed data
    $html .= self::renderConsoleScript($json);
    
    return $html;
  }

  /**
   * Render the console injection script.
   *
   * @param string $json
   * @return string
   */
  private static function renderConsoleScript(string $json): string
  {
    return <<<HTML
<script>
(function(){
  const lens = $json;

  console.groupCollapsed("🔎 PayCal Lens (DEV)");
  console.log("Route:", lens.meta.route);
  console.log("Method:", lens.meta.method);
  console.log("Duration (ms):", lens.meta.duration_ms.toFixed(2));
  console.log("Peak Memory (bytes):", lens.meta.peak_memory_bytes);
  console.log("Included Files:", lens.meta.included_files);

  if (lens.timers.length) {
    console.group("⏱ Timers");
    console.table(lens.timers.map(t => ({
      label: t.label,
      duration_ms: t.duration_ms.toFixed(2)
    })));
    console.groupEnd();
  }

  if (lens.counters && Object.keys(lens.counters).length) {
    console.group("📊 Counters");
    console.table(lens.counters);
    console.groupEnd();
  }

  if (lens.events.length) {
    console.group("📦 Events");
    lens.events.forEach(e => {
      console.group(e.label + " (" + e.type + ")");
      console.dir(e.payload);
      console.groupEnd();
    });
    console.groupEnd();
  }

  console.groupEnd();
})();
</script>
HTML;
  }

  /**
   * Normalize value for JSON serialization.
   *
   * @param mixed $value
   * @param int $depth
   * @return mixed
   */
  private static function normalize(mixed $value, int $depth = 0): mixed
  {
    if ($depth >= self::MAX_DEPTH) {
      return '[max-depth]';
    }

    if (is_scalar($value) || $value === null) {
      return $value;
    }

    if (is_array($value)) {
      $out = [];
      foreach ($value as $k => $v) {
        $out[$k] = self::normalize($v, $depth + 1);
      }
      return $out;
    }

    if (is_object($value)) {
      $out = ['__class' => get_class($value)];
      foreach (get_object_vars($value) as $k => $v) {
        $out[$k] = self::normalize($v, $depth + 1);
      }
      return $out;
    }

    return '[unsupported]';
  }

  /**
   * Check if response is HTML (text/html).
   *
   * This ensures Lens doesn't inject into:
   * - JSON responses (API endpoints)
   * - Redirects (Location header)
   * - Downloads
   * - CLI requests
   *
   * @return bool
   */
  private static function isHtmlResponse(): bool
  {
    // CLI detection: can't output HTML in CLI
    if (php_sapi_name() === 'cli') {
      return false;
    }

    // Check for redirect response (3xx status)
    $statusCode = http_response_code();
    if ($statusCode >= 300 && $statusCode < 400) {
      return false;
    }

    // Check Content-Type header if available via headers_list()
    if (function_exists('headers_list')) {
      foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type:') === 0) {
          // If Content-Type is set and NOT text/html, skip
          if (stripos($header, 'text/html') === false) {
            return false;
          }
          break;
        }
      }
    }

    // Assume HTML by default if not proven otherwise
    return true;
  }

  /**
   * Check if Lens was explicitly requested via ?lens=1 or forced via forceRender().
   *
   * Prevents console noise on every request.
   * Requires manual opt-in per request, unless forced.
   *
   * Valid query string values: ?lens=1
   *
   * @return bool
   */
  private static function isLensRequested(): bool
  {
    return self::$forceRender || InputSanitizer::getString('lens') === '1';
  }

  /**
   * Check if DEV environment is enabled.
   *
   * Explicit positive logic:
    * - appEnv() MUST be exactly 'mac' (local development only)
   * - DEV_ALLOW_INLINE_SCRIPTS MUST be true
   *
   * Not: appEnv() !== 'prod'
   *
   * @return bool
   */
  private static function isDev(): bool
  {
    // Explicit positive check: environment MUST be mac (local development)
    $isDevEnv = Environment::isLocalMac();

    // Explicit positive check: config MUST allow inline scripts
    $allowInlineScripts = Environment::devAllowInlineScripts() === true;

    return $isDevEnv && $allowInlineScripts;
  }
}

