<?php declare(strict_types=1);

namespace PayCal\Observability;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Render;
use PayCal\Domain\User;

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

    /**
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     */
    $json = json_encode(self::$payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

    if ($json === false) {
      return;
    }

    // If force-rendered, show visible panel; otherwise just console output
    if (self::$forceRender) {
      echo self::renderVisiblePanel($json); // @psalm-suppress TaintedHtml TaintedTextWithQuotes -- dev-only, gated by devSecurityDisabled(); $json encoded with JSON_HEX_TAG
    } else {
      echo self::renderConsoleScript($json); // @psalm-suppress TaintedHtml TaintedTextWithQuotes -- dev-only, gated by devSecurityDisabled(); $json encoded with JSON_HEX_TAG
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
   * Emit scoped page diagnostics to the browser console (dev only).
   *
   * Unlike render(), this does not require ?lens=1 and is intended for
   * page-specific troubleshooting (e.g. /business/reports/).
   *
   * @param string $scope Short route label used in the console prefix.
   * @param array<string, mixed> $snapshot Request-scoped values to log.
   */
  /**
   * @param array<string, mixed> $snapshot
   */
  public static function pageConsoleDebugScript(string $scope, array $snapshot): string
  {
    $prefix = '[PayCal Lens][' . $scope . ']';
    $payload = [
      'scope' => $scope,
      'snapshot' => self::normalize($snapshot),
      'lens_enabled' => self::$enabled,
      'lens_requested' => self::isLensRequested(),
    ];

    if (self::$enabled) {
      $payload['lens_meta'] = self::normalize(self::$payload['meta']);
      $payload['lens_events'] = self::normalize(self::$payload['events']);
      $payload['lens_counters'] = self::normalize(self::$payload['counters']);
    }

    /**
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     */
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    if ($json === false) {
      return '';
    }

    $escapedPrefix = json_encode($prefix, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

    return <<<HTML
<script>
(function(){
  const prefix = {$escapedPrefix};
  const debug = {$json};
  console.groupCollapsed(prefix + " page debug");
  console.log(prefix, "Lens mode requested:", debug.lens_requested);
  console.log(prefix, "Lens enabled:", debug.lens_enabled);
  console.dir(debug.snapshot);
  if (debug.lens_meta && Object.keys(debug.lens_meta).length) {
    console.log(prefix, "Lens meta:", debug.lens_meta);
  }
  if (Array.isArray(debug.lens_events) && debug.lens_events.length) {
    console.group(prefix + " Lens events");
    debug.lens_events.forEach((event) => {
      console.group((event.label || "event") + " (" + (event.type || "data") + ")");
      console.dir(event.payload);
      console.groupEnd();
    });
    console.groupEnd();
  }
  if (debug.lens_counters && Object.keys(debug.lens_counters).length) {
    console.log(prefix, "Lens counters:", debug.lens_counters);
  }
  console.groupEnd();
})();
</script>
HTML;
  }

  /**
   * @param array<string, mixed> $snapshot
   */
  public static function renderPageConsoleDebug(string $scope, array $snapshot): void
  {
    if (!self::isDev()) {
      return;
    }

    if (headers_sent()) {
      return;
    }

    if (!self::isHtmlResponse()) {
      return;
    }

    $script = self::pageConsoleDebugScript($scope, $snapshot);
    if ($script !== '') {
      echo $script; // @psalm-suppress TaintedHtml -- dev-only scoped console debug
    }
  }

  /**
   * Build page-scoped Lens boot options for client performance tracking.
   *
   * @param array<string, mixed> $options
   * @return array<string, mixed>
   */
  public static function pagePerformanceBootOptions(string $scope, array $options = []): array
  {
    if (!Environment::isLocalMac()) {
      return [];
    }

    $bootOptions = self::normalize([
      'scope' => $scope,
      'enabled' => true,
      'ranked' => true,
      'page_load_origin_ms' => round(microtime(true) * 1000, 3),
      ...$options,
    ]);

    return is_array($bootOptions) ? $bootOptions : [];
  }

  /**
   * Emit data-* attributes with Lens perf boot + page debug payloads (mac dev, CSP-safe).
   *
   * @param array<string, mixed> $snapshot
   * @param array<string, mixed> $perfOptions
   */
  public static function workspaceLensDataAttributes(string $scope, array $snapshot, array $perfOptions = []): string
  {
    if (!Environment::isLocalMac()) {
      return '';
    }

    $attrs = '';
    $perfPayload = self::pagePerformanceBootOptions($scope, $perfOptions);
    if ($perfPayload !== []) {
      $perfJson = json_encode($perfPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
      if ($perfJson !== false) {
        $attrs .= ' data-lens-perf-boot="' . htmlspecialchars($perfJson, ENT_QUOTES, 'UTF-8') . '"';
      }
    }

    $debugPayload = [
      'scope' => $scope,
      'snapshot' => self::normalize($snapshot),
      'lens_enabled' => self::$enabled,
      'lens_requested' => self::isLensRequested(),
    ];

    if (self::$enabled) {
      $debugPayload['lens_meta'] = self::normalize(self::$payload['meta']);
      $debugPayload['lens_events'] = self::normalize(self::$payload['events']);
      $debugPayload['lens_counters'] = self::normalize(self::$payload['counters']);
    }

    $debugJson = json_encode($debugPayload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($debugJson !== false) {
      $attrs .= ' data-lens-page-debug="' . htmlspecialchars($debugJson, ENT_QUOTES, 'UTF-8') . '"';
    }

    return $attrs;
  }

  /**
   * Emit a reusable client-side performance helper for page load instrumentation.
   *
   * @param string $scope Short route label used in the console prefix.
   * @param array<string, mixed> $options Page-scoped boot options (SSR hints, fetch patterns).
   */
  public static function pagePerformanceClientScript(string $scope, array $options = []): string
  {
    if (!self::isDev()) {
      return '';
    }

    $payload = self::normalize([
      'scope' => $scope,
      'enabled' => true,
      'page_load_origin_ms' => round(microtime(true) * 1000, 3),
      ...$options,
    ]);

    /**
     * @psalm-taint-escape html
     * @psalm-taint-escape has_quotes
     */
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);
    if ($json === false) {
      return '';
    }

    $escapedScope = json_encode($scope, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

    return <<<HTML
<script>
(function(){
  if (typeof window.PayCalLensPerformance === "undefined") {
    window.PayCalLensPerformance = {
      create(scope, options) {
        const opts = options || {};
        const enabled = opts.enabled !== false;
        const prefix = "[PayCal Lens][" + scope + "]";
        const records = [];
        const active = new Map();
        const pageOriginMs = typeof opts.page_load_origin_ms === "number" ? opts.page_load_origin_ms : null;
        const moduleStartMs = performance.now();

        const now = () => performance.now();

        const pushRecord = (label, durationMs, meta) => {
          records.push({
            label: String(label || "unknown"),
            duration_ms: Math.max(0, durationMs),
            type: "timer",
            ranked: !(meta && meta.ranked === false),
            meta: meta || null,
          });
        };

        const api = {
          prefix,
          isEnabled() {
            return enabled;
          },
          mark(label, meta) {
            if (!enabled) {
              return;
            }
            pushRecord(label, 0, meta);
          },
          start(label) {
            if (!enabled) {
              return;
            }
            active.set(String(label), now());
          },
          end(label, meta) {
            if (!enabled) {
              return;
            }
            const key = String(label);
            const started = active.get(key);
            if (typeof started !== "number") {
              return;
            }
            pushRecord(key, now() - started, meta);
            active.delete(key);
          },
          async measure(label, fn, meta) {
            if (!enabled) {
              return fn();
            }
            api.start(label);
            try {
              return await fn();
            } finally {
              api.end(label, meta);
            }
          },
          measureSync(label, fn, meta) {
            if (!enabled) {
              return fn();
            }
            api.start(label);
            try {
              return fn();
            } finally {
              api.end(label, meta);
            }
          },
          markSsrPainted() {
            if (!enabled) {
              return;
            }
            pushRecord("SSR DOM painted", moduleStartMs, {
              grid_present: !!document.getElementById("businesses-members-grid"),
              ranked: false,
            });
          },
          markHydrationComplete() {
            if (!enabled) {
              return;
            }
            const hydrationMs = now() - moduleStartMs;
            pushRecord("initialize (total)", hydrationMs, {
              page_origin_ms: pageOriginMs,
            });
            pushRecord("SSR painted → JS hydration complete", hydrationMs, {
              page_origin_ms: pageOriginMs,
              ranked: false,
            });
          },
          summarize(title) {
            if (!enabled) {
              return [];
            }

            const timers = records
              .filter((record) => record.type === "timer" && record.duration_ms > 0 && record.ranked !== false)
              .sort((a, b) => b.duration_ms - a.duration_ms);
            const top3 = timers.slice(0, 3);
            const heading = prefix + " " + (title || "Performance Summary");

            console.groupCollapsed(heading);
            if (top3.length === 0) {
              console.log(prefix, "No ranked timings recorded yet.");
            } else {
              top3.forEach((record, index) => {
                console.log("  " + (index + 1) + ". " + record.label + " — " + Math.round(record.duration_ms) + "ms");
              });
            }
            console.log(
              "Top 3 slowest paths:",
              top3.map((record) => record.label + " (" + Math.round(record.duration_ms) + "ms)").join(", ") || "n/a",
            );
            if (timers.length) {
              console.table(timers.map((record) => ({
                path: record.label,
                ms: Math.round(record.duration_ms),
              })));
            }
            console.groupEnd();

            return top3;
          },
          records() {
            return records.slice();
          },
        };

        return api;
      },
    };
  }

  window.__PAYCAL_LENS_PERF__ = window.__PAYCAL_LENS_PERF__ || {};
  window.__PAYCAL_LENS_PERF__[{$escapedScope}] = {$json};
})();
</script>
HTML;
  }

  /**
   * @param array<string, mixed> $options
   */
  public static function renderPagePerformanceBoot(string $scope, array $options = []): void
  {
    if (!Environment::isLocalMac()) {
      return;
    }

    if (headers_sent()) {
      return;
    }

    if (!self::isHtmlResponse()) {
      return;
    }

    // CSP-safe factory — classic (non-module) script so PayCalLensPerformance exists before business JS modules run.
    $cacheVersion = \PayCal\Domain\Config\Environment::appVersion();
    $cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
    $cspNonceCandidate = is_string($cspNonceRaw) ? trim($cspNonceRaw) : '';
    $isValidNonce = $cspNonceCandidate !== ''
      && strlen($cspNonceCandidate) >= 16
      && preg_match('/^[A-Za-z0-9+\/_\-]+=*$/', $cspNonceCandidate) === 1;
    $cspNonce = $isValidNonce ? $cspNonceCandidate : User::nonce();
    echo '    <script src="'
      . \PayCal\Domain\Config\Environment::appURL('js/lens/performance.js')
      . '?v=' . $cacheVersion
      . '" nonce="' . $cspNonce . '"></script>' . PHP_EOL; // @psalm-suppress TaintedHtml -- dev-only external script

    if (!self::isDev()) {
      return;
    }

    $script = self::pagePerformanceClientScript($scope, $options);
    if ($script !== '') {
      echo $script; // @psalm-suppress TaintedHtml -- dev-only scoped performance boot
    }
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

