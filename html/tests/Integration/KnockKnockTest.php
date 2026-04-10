<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversNothing]
#[Group('knockknock')]
#[Group('smoke')]
#[Group('slow')]
/**
 * KnockKnockTest
 */
#[Group('integration')]
final class KnockKnockTest extends TestCase
{
  private const LEGACY_REPORT_PATH = __DIR__ . '/../.last-run-knockknock.json';
  private const REPORT_ROOT = __DIR__ . '/../../../ai-notes/knockknock-reports';

  #[Test]
  public function sweepKnownUrlsForUnauthenticatedLeaks(): void
  {
    $baseURL = $this->baseURL();

    if (!$this->isReachable($baseURL . '/auth/')) {
      $this->markTestSkipped('KnockKnock skipped: cannot reach ' . $baseURL . ' (set KNOCKKNOCK_BASE_URL if needed).');
    }

    $targets = $this->collectTargets();
    $this->assertNotEmpty($targets, 'KnockKnock found no targets to scan.');

    $stats = [
      'base_url' => $baseURL,
      'generated_at' => date(DATE_ATOM),
      'totals' => [
        'targets' => count($targets),
        'requests' => 0,
        'gated' => 0,
        'public_ok' => 0,
        'not_found' => 0,
        'other' => 0,
        'leaks' => 0,
      ],
      'status_codes' => [],
      'category' => [
        'page' => 0,
        'api' => 0,
      ],
      'leaks' => [],
      'samples' => [],
    ];

    foreach ($targets as $target) {
      $url = $baseURL . $target['path'];
      $result = $this->request($url, $target['method']);

      $stats['totals']['requests']++;
      $stats['category'][$target['category']]++;

      $statusKey = (string) $result['status'];
      if (!isset($stats['status_codes'][$statusKey])) {
        $stats['status_codes'][$statusKey] = 0;
      }
      $stats['status_codes'][$statusKey]++;

      $classification = $this->classify($target, $result);

      if (!isset($stats['totals'][$classification])) {
        $stats['totals'][$classification] = 0;
      }
      $stats['totals'][$classification]++;

      if ($classification === 'leaks') {
        $stats['leaks'][] = [
          'method' => $target['method'],
          'path' => $target['path'],
          'status' => $result['status'],
          'location' => $result['location'],
          'reason' => $result['reason'],
          'sample' => $result['sample'],
        ];
      }

      if (count($stats['samples']) < 20) {
        $stats['samples'][] = [
          'method' => $target['method'],
          'path' => $target['path'],
          'category' => $target['category'],
          'status' => $result['status'],
          'classification' => $classification,
        ];
      }
    }

    arsort($stats['status_codes']);

    [$jsonPath, $markdownPath] = $this->writeReports($stats);

    fwrite(
      STDOUT,
      PHP_EOL . sprintf(
        '[KnockKnock] targets=%d requests=%d gated=%d public_ok=%d not_found=%d leaks=%d json=%s md=%s',
        $stats['totals']['targets'],
        $stats['totals']['requests'],
        $stats['totals']['gated'],
        $stats['totals']['public_ok'],
        $stats['totals']['not_found'],
        $stats['totals']['leaks'],
        $jsonPath,
        $markdownPath
      ) . PHP_EOL
    );

    $this->assertSame(
      0,
      $stats['totals']['leaks'],
      'KnockKnock found potential unauthenticated leaks. See ' . $markdownPath
    );
  }

  /**
   * @param array<string, mixed> $stats
   * @return array{0: string, 1: string}
   */
  private function writeReports(array $stats): array
  {
    $dateFolder = date('Y-m-d');
    $reportRoot = rtrim(self::REPORT_ROOT, '/');
    $reportDir = $reportRoot . '/' . $dateFolder;

    if (!is_dir($reportDir) && !@mkdir($reportDir, 0775, true) && !is_dir($reportDir)) {
      throw new RuntimeException('Unable to create private KnockKnock report directory: ' . $reportDir);
    }

    $timestamp = date('Y-m-d_His');
    $jsonPath = $reportDir . '/knockknock-' . $timestamp . '.json';
    $markdownPath = $reportDir . '/knockknock-' . $timestamp . '.md';

    $jsonPayload = json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($jsonPayload === false) {
      $jsonPayload = '{}';
    }

    file_put_contents($jsonPath, $jsonPayload);
    file_put_contents(self::LEGACY_REPORT_PATH, $jsonPayload);
    file_put_contents($markdownPath, $this->renderMarkdownReport($stats, $jsonPath));

    return [$jsonPath, $markdownPath];
  }

  /**
   * @param array<string, mixed> $stats
   */
  private function renderMarkdownReport(array $stats, string $jsonPath): string
  {
    $totals = is_array($stats['totals'] ?? null) ? $stats['totals'] : [];
    $statusCodes = is_array($stats['status_codes'] ?? null) ? $stats['status_codes'] : [];
    $leaks = is_array($stats['leaks'] ?? null) ? $stats['leaks'] : [];

    $lines = [];
    $lines[] = '# KnockKnock Report';
    $lines[] = '';
    $lines[] = '- Generated: ' . (string) ($stats['generated_at'] ?? date(DATE_ATOM));
    $lines[] = '- Base URL: ' . (string) ($stats['base_url'] ?? '');
    $lines[] = '- JSON Report: ' . $jsonPath;
    $lines[] = '';
    $lines[] = '## Totals';
    $lines[] = '';
    $lines[] = '| Metric | Value |';
    $lines[] = '|---|---:|';
    $lines[] = '| Targets | ' . (int) ($totals['targets'] ?? 0) . ' |';
    $lines[] = '| Requests | ' . (int) ($totals['requests'] ?? 0) . ' |';
    $lines[] = '| Gated | ' . (int) ($totals['gated'] ?? 0) . ' |';
    $lines[] = '| Public OK | ' . (int) ($totals['public_ok'] ?? 0) . ' |';
    $lines[] = '| Not Found | ' . (int) ($totals['not_found'] ?? 0) . ' |';
    $lines[] = '| Other | ' . (int) ($totals['other'] ?? 0) . ' |';
    $lines[] = '| Potential Leaks | ' . (int) ($totals['leaks'] ?? 0) . ' |';
    $lines[] = '';
    $lines[] = '## Status Codes';
    $lines[] = '';
    $lines[] = '| Status | Count |';
    $lines[] = '|---|---:|';

    foreach ($statusCodes as $code => $count) {
      $lines[] = '| ' . (string) $code . ' | ' . (int) $count . ' |';
    }

    $lines[] = '';
    $lines[] = '## Potential Leaks';
    $lines[] = '';

    if ($leaks === []) {
      $lines[] = 'No potential leaks detected.';
      $lines[] = '';
      return implode(PHP_EOL, $lines);
    }

    $lines[] = '| Method | Path | Status | Reason |';
    $lines[] = '|---|---|---:|---|';
    foreach ($leaks as $leak) {
      $method = (string) ($leak['method'] ?? '');
      $path = str_replace('|', '\\|', (string) ($leak['path'] ?? ''));
      $status = (int) ($leak['status'] ?? 0);
      $reason = str_replace('|', '\\|', (string) ($leak['reason'] ?? ''));
      $lines[] = '| ' . $method . ' | ' . $path . ' | ' . $status . ' | ' . $reason . ' |';
    }

    $lines[] = '';
    $lines[] = '---';
    $lines[] = 'Generated by KnockKnockTest.';

    return implode(PHP_EOL, $lines) . PHP_EOL;
  }

  private function baseURL(): string
  {
    $configured = getenv('KNOCKKNOCK_BASE_URL');
    $url = is_string($configured) && $configured !== ''
      ? $configured
      : 'https://mac.paycal.local';

    return rtrim($url, '/');
  }

  private function isReachable(string $url): bool
  {
    $result = $this->request($url, 'GET', 6);
    return $result['status'] > 0;
  }

  /**
   * @return array<int, array{method: string, path: string, category: string}>
   */
  private function collectTargets(): array
  {
    $targets = [];

    foreach ($this->discoverPagePaths() as $path) {
      $targets['GET:' . $path] = [
        'method' => 'GET',
        'path' => $path,
        'category' => 'page',
      ];
    }

    foreach ($this->discoverApiTargets() as $target) {
      $key = $target['method'] . ':' . $target['path'];
      $targets[$key] = $target;
    }

    $targets['GET:/api/v1/data/calendar/month/get?month=' . date('Y-m')] = [
      'method' => 'GET',
      'path' => '/api/v1/data/calendar/month/get?month=' . date('Y-m'),
      'category' => 'api',
    ];

    $targets['GET:/api/v1/telemetry/record'] = [
      'method' => 'GET',
      'path' => '/api/v1/telemetry/record',
      'category' => 'api',
    ];

    ksort($targets);

    return array_values($targets);
  }

  /**
   * @return array<int, string>
   */
  private function discoverPagePaths(): array
  {
    $root = dirname(__DIR__, 2);
    $paths = ['/'];

    $excludeRoots = [
      '.github', '.tmp', '_references', 'ai-notes', 'api', 'bootstrap', 'css',
      'cli', 'data', 'dev', 'docs', 'fonts', 'images', 'img', 'js', 'lang', 'logs',
      'scripts', 'src', 'tests', 'vendor', 'ws'
    ];

    $iterator = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
      if (!$fileInfo->isFile() || $fileInfo->getFilename() !== 'index.php') {
        continue;
      }

      $fullPath = str_replace('\\', '/', $fileInfo->getPathname());
      $relative = ltrim(str_replace(str_replace('\\', '/', $root), '', $fullPath), '/');

      if ($relative === 'index.php') {
        continue;
      }

      $segments = explode('/', $relative);
      $rootSegment = $segments[0] ?? '';
      if (in_array($rootSegment, $excludeRoots, true)) {
        continue;
      }

      $dir = dirname($relative);
      if ($dir === '.' || $dir === '') {
        continue;
      }

      $paths[] = '/' . trim($dir, '/') . '/';
    }

    foreach (glob($root . '/*.php') ?: [] as $phpFile) {
      $basename = basename($phpFile);
      if (in_array($basename, ['config.php', 'header.php', 'footer.php', 'index.php'], true)) {
        continue;
      }

      $paths[] = '/' . $basename;
    }

    $paths = array_values(array_unique($paths));
    sort($paths);

    return $paths;
  }

  /**
   * @return array<int, array{method: string, path: string, category: string}>
   */
  private function discoverApiTargets(): array
  {
    $controllerFiles = glob(dirname(__DIR__, 2) . '/src/Controllers/*.php') ?: [];
    $targets = [];

    foreach ($controllerFiles as $file) {
      $contents = (string) file_get_contents($file);
      if ($contents === '') {
        continue;
      }

      if (!preg_match_all("/#\[Route\((['\"])(.*?)\\1\s*,\s*\[(.*?)\]\)\]/s", $contents, $matches, PREG_SET_ORDER)) {
        continue;
      }

      foreach ($matches as $match) {
        $rawPath = trim((string) ($match[2] ?? ''), '/');
        if ($rawPath === '') {
          continue;
        }

        $resolvedPath = $this->resolveRoutePlaceholders($rawPath);
        preg_match_all("/['\"]([A-Za-z]+)['\"]/", (string) ($match[3] ?? ''), $methodMatches);
        $methods = array_map('strtoupper', $methodMatches[1] ?? []);
        if ($methods === []) {
          $methods = ['GET'];
        }

        foreach ($methods as $method) {
          $probeMethod = in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true)
            ? $method
            : 'GET';

          $targets[] = [
            'method' => $probeMethod,
            'path' => '/api/v1/' . $resolvedPath,
            'category' => 'api',
          ];
        }
      }
    }

    return $targets;
  }

  private function resolveRoutePlaceholders(string $path): string
  {
    return preg_replace_callback('/\{([^}]+)\}/', static function (array $matches): string {
      $name = strtolower((string) ($matches[1] ?? 'id'));

      return match ($name) {
        'year' => date('Y'),
        'month' => '01',
        'day' => '01',
        'teamid' => 'T000000001',
        default => '1',
      };
    }, $path) ?? $path;
  }

  /**
   * @return array{status: int, location: string, sample: string, reason: string}
   */
  private function request(string $url, string $method = 'GET', int $timeout = 10): array
  {
    $headers = [
      'Accept: application/json, text/html;q=0.9, */*;q=0.1',
      'User-Agent: KnockKnock/1.0',
      'Connection: close',
    ];

    $options = [
      'http' => [
        'method' => $method,
        'ignore_errors' => true,
        'timeout' => $timeout,
        'follow_location' => 0,
        'max_redirects' => 0,
        'header' => implode("\r\n", $headers),
      ],
      'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
      ],
    ];

    if ($method !== 'GET' && $method !== 'HEAD') {
      $options['http']['content'] = '{}';
      $options['http']['header'] .= "\r\nContent-Type: application/json";
    }

    $context = stream_context_create($options);
    $body = @file_get_contents($url, false, $context);
    $responseHeaders = $http_response_header ?? [];

    $status = 0;
    $location = '';

    foreach ($responseHeaders as $header) {
      if (preg_match('/^HTTP\/\S+\s+(\d{3})/i', $header, $match)) {
        $status = (int) $match[1];
      }
      if (stripos($header, 'Location:') === 0) {
        $location = trim(substr($header, strlen('Location:')));
      }
    }

    $sample = $body !== false
      ? trim(substr(preg_replace('/\s+/', ' ', (string) $body) ?? '', 0, 220))
      : '';

    return [
      'status' => $status,
      'location' => $location,
      'sample' => $sample,
      'reason' => $body === false ? 'request_failed_or_empty' : 'ok',
    ];
  }

  /**
   * @param array{method: string, path: string, category: string} $target
   * @param array{status: int, location: string, sample: string, reason: string} $result
   */
  private function classify(array $target, array &$result): string
  {
    $status = $result['status'];
    $path = $target['path'];
    $location = strtolower($result['location']);
    $sampleLower = strtolower($result['sample']);

    $isRedirectToSignin = in_array($status, [301, 302, 303, 307, 308], true)
      && str_contains($location, '/signin');

    $isUnauthorized = in_array($status, [401, 403], true);
    $isNotFound = $status === 404;
    $looksLikeSigninPage = $status === 200
      && (str_contains($sampleLower, '/signin') || str_contains($sampleLower, 'name="password"'));

    $publicPagePrefixes = [
      '/', '/about/', '/auth/', '/blog/', '/contact/', '/faq/', '/help/', '/media/', '/policies/', '/transparency/', '/verify/'
    ];

    $publicPageExact = [
      '/robots.txt', '/manifest.json', '/cookies.txt', '/favicon.ico', '/favicon-16x16.png', '/favicon-32x32.png'
    ];

    $publicAPIPrefixes = [
      '/api/v1/auth/recovery/cancel',
      '/api/v1/health',
      '/api/v1/security/csp/report',
      '/api/v1/system/kek/salt',
      '/api/v1/telemetry/record',
    ];

    if ($isNotFound) {
      return 'not_found';
    }

    if ($isRedirectToSignin || $isUnauthorized || $looksLikeSigninPage) {
      return 'gated';
    }

    if ($target['category'] === 'page' && $status >= 200 && $status < 400) {
      if (in_array($path, $publicPageExact, true)) {
        return 'public_ok';
      }

      foreach ($publicPagePrefixes as $prefix) {
        if ($prefix === '/') {
          if ($path === '/') {
            return 'public_ok';
          }
          continue;
        }

        if (str_starts_with($path, $prefix)) {
          return 'public_ok';
        }
      }
    }

    if ($target['category'] === 'api') {
      foreach ($publicAPIPrefixes as $prefix) {
        if (str_starts_with($path, $prefix) && $status >= 200 && $status < 400) {
          return 'public_ok';
        }
      }
    }

    if ($status >= 200 && $status < 300) {
      $result['reason'] = 'unexpected_2xx_for_unauthenticated_request';
      return 'leaks';
    }

    return 'other';
  }
}
