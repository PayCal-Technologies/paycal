<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../config.php';

Authentication::abortIfUnauthenticated();

function wsReadCapabilityTokenFromRequest(): string
{
  $tokenFromQuery = wsGetStringQuery('capability_token');
  if ($tokenFromQuery !== '') {
    return $tokenFromQuery;
  }

  $headerRaw = $_SERVER['HTTP_X_PAYCAL_CAPABILITY'] ?? '';
  if (!is_scalar($headerRaw)) {
    return '';
  }

  return trim((string) $headerRaw);
}

/**
 * @return array{ok: bool, code: string, message: string}
 */
function wsConsumeCapabilityToken(string $action): array
{
  $token = wsReadCapabilityTokenFromRequest();

  return CapabilityTokenService::consume(
    $token,
    $action,
    User::currentUUID(),
    Authentication::getCookie()
  );
}

function wsTestsRunLockKey(string $userUuid): string
{
  return 'ws:test-suite:lock:' . $userUuid;
}

function wsCanStartTestsRun(string $userUuid, int $minIntervalSeconds = 15): bool
{
  $lockKey = wsTestsRunLockKey($userUuid);
  if (Database::exists($lockKey)) {
    return false;
  }

  $lastRunKey = 'ws:test-suite:last-run:' . $userUuid;
  $lastRun = (int) Database::get($lastRunKey);
  if ($lastRun > 0 && (time() - $lastRun) < $minIntervalSeconds) {
    return false;
  }

  return true;
}

function wsAcquireTestsRunLock(string $userUuid, int $ttlSeconds = 1800): void
{
  Database::set(wsTestsRunLockKey($userUuid), (string) time(), $ttlSeconds);
}

function wsReleaseTestsRunLock(string $userUuid): void
{
  Database::unlink(wsTestsRunLockKey($userUuid));
  Database::set('ws:test-suite:last-run:' . $userUuid, (string) time(), 300);
}

/** @param array<string, mixed> $payload */
function wsRespond(array $payload, int $statusCode = 200): void
{
  if (!headers_sent()) {
    http_response_code($statusCode);
  }

  echo json_encode($payload, JSON_UNESCAPED_SLASHES);
}

/** @param array<string, mixed> $payload */
function wsEmitEvent(string $event, array $payload): void
{
  echo 'event: ' . $event . "\n";
  echo 'data: ' . json_encode($payload, JSON_UNESCAPED_SLASHES) . "\n\n";
  @flush();
}

function wsGetStringQuery(string $key): string
{
  $value = $_GET[$key] ?? '';
  if (!is_string($value)) {
    return '';
  }

  return trim((string) InputSanitizer::sanitizeString($value));
}

$channel = wsGetStringQuery('channel');

if ($channel !== 'test_suite_stream' && headers_sent() === false) {
  header('Content-Type: application/json; charset=UTF-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
}

if ($channel === 'test_suite_stream') {
  if (!User::isAdmin()) {
    if (headers_sent() === false) {
      http_response_code(403);
      header('Content-Type: text/event-stream');
      header('Cache-Control: no-cache, no-store, must-revalidate');
      header('Connection: keep-alive');
    }
    wsEmitEvent('error', [
      'success' => false,
      'error' => 'Forbidden.',
    ]);

    return;
  }

  $capabilityDecision = wsConsumeCapabilityToken('admin.tests.run');
  if (!$capabilityDecision['ok']) {
    if (headers_sent() === false) {
      http_response_code(403);
      header('Content-Type: text/event-stream');
      header('Cache-Control: no-cache, no-store, must-revalidate');
      header('Connection: keep-alive');
    }
    wsEmitEvent('error', [
      'success' => false,
      'error' => 'Capability token rejected.',
      'capability_code' => $capabilityDecision['code'],
    ]);

    return;
  }

  $actorUUID = User::currentUUID();
  if (!wsCanStartTestsRun($actorUUID)) {
    if (headers_sent() === false) {
      http_response_code(429);
      header('Content-Type: text/event-stream');
      header('Cache-Control: no-cache, no-store, must-revalidate');
      header('Connection: keep-alive');
    }
    wsEmitEvent('error', [
      'success' => false,
      'error' => 'A test run is already in progress or was started too recently.',
    ]);

    return;
  }

  wsAcquireTestsRunLock($actorUUID);

  if (headers_sent() === false) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no');
  }

  @ini_set('output_buffering', 'off');
  @ini_set('zlib.output_compression', '0');
  @set_time_limit(0);

  while (ob_get_level() > 0) {
    @ob_end_flush();
  }
  ob_implicit_flush(true);

  try {
    $startTime = microtime(true);
    $workspaceRoot = dirname(HTML);

    $phpBinary = PHP_BINARY;
    if (strpos($phpBinary, 'php-fpm') !== false) {
      $phpBinary = str_replace(['sbin/php-fpm', 'php-fpm'], ['bin/php', 'php'], $phpBinary);
    }

    $descriptorSpec = [
      0 => ['pipe', 'r'],
      1 => ['pipe', 'w'],
      2 => ['pipe', 'w'],
    ];

    $command = sprintf('%s ./vendor/bin/phpunit --colors=never', escapeshellarg($phpBinary));
    $process = proc_open($command, $descriptorSpec, $pipes, $workspaceRoot);

    if (!is_resource($process)) {
      throw new \RuntimeException('Unable to start PHPUnit process');
    }

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);

    $fullOutput = '';
    $stdoutBuffer = '';
    $stderrBuffer = '';
    $cancelled = false;

    wsEmitEvent('start', ['message' => 'PHPUnit run started']);

    while (true) {
      if (connection_aborted()) {
        $cancelled = true;
        @proc_terminate($process);
        break;
      }

      $status = proc_get_status($process);

      $stdoutChunk = stream_get_contents($pipes[1]);
      if ($stdoutChunk !== false && $stdoutChunk !== '') {
        $fullOutput .= $stdoutChunk;
        $stdoutBuffer .= $stdoutChunk;

        while (($pos = strpos($stdoutBuffer, "\n")) !== false) {
          $line = substr($stdoutBuffer, 0, $pos);
          $stdoutBuffer = substr($stdoutBuffer, $pos + 1);
          wsEmitEvent('line', ['text' => rtrim($line, "\r")]);
        }
      }

      $stderrChunk = stream_get_contents($pipes[2]);
      if ($stderrChunk !== false && $stderrChunk !== '') {
        $fullOutput .= $stderrChunk;
        $stderrBuffer .= $stderrChunk;

        while (($pos = strpos($stderrBuffer, "\n")) !== false) {
          $line = substr($stderrBuffer, 0, $pos);
          $stderrBuffer = substr($stderrBuffer, $pos + 1);
          wsEmitEvent('line', ['text' => rtrim($line, "\r")]);
        }
      }

      if (!$status['running']) {
        break;
      }

      usleep(50000);
    }

    if ($stdoutBuffer !== '') {
      wsEmitEvent('line', ['text' => rtrim($stdoutBuffer, "\r")]);
    }
    if ($stderrBuffer !== '') {
      wsEmitEvent('line', ['text' => rtrim($stderrBuffer, "\r")]);
    }

    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    $duration = microtime(true) - $startTime;
    $success = !$cancelled
      && $exitCode === 0
      && strpos($fullOutput, 'FAILED') === false
      && strpos($fullOutput, 'ERRORS!') === false
      && strpos($fullOutput, ' Error') === false;

    $testCount = 0;
    $assertionCount = 0;
    $failures = 0;

    if (preg_match('/Tests: (\d+)/', $fullOutput, $matches) || preg_match('/(\d+) tests?/', $fullOutput, $matches)) {
      $testCount = (int) $matches[1];
    }
    if (preg_match('/Assertions: (\d+)/', $fullOutput, $matches) || preg_match('/(\d+) assertions?/', $fullOutput, $matches)) {
      $assertionCount = (int) $matches[1];
    }
    if (preg_match('/Failures: (\d+)/', $fullOutput, $matches) || preg_match('/(\d+) failures?/', $fullOutput, $matches)) {
      $failures = (int) $matches[1];
    }

    $result = [
      'success' => $success,
      'timestamp' => date('Y-m-d H:i:s'),
      'testCount' => $testCount,
      'assertionCount' => $assertionCount,
      'failures' => $failures,
      'duration' => round($duration, 2),
      'exitCode' => $exitCode,
      'cancelled' => $cancelled,
      'output' => $fullOutput,
    ];

    $lastRunPath = HTML . '/tests/.last-run.json';
    $lastRunDir = dirname($lastRunPath);
    if (!is_dir($lastRunDir)) {
      @mkdir($lastRunDir, 0755, true);
    }
    @file_put_contents($lastRunPath, json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    if (!$cancelled) {
      wsEmitEvent('done', $result);
    }
  } catch (\Throwable $e) {
    wsEmitEvent('error', [
      'success' => false,
      'error' => $e->getMessage(),
    ]);
  } finally {
    wsReleaseTestsRunLock($actorUUID);
  }

  return;
}

if ($channel === 'organization_audit') {
  $organizationId = wsGetStringQuery('organization_id');
  $sinceEventId = wsGetStringQuery('since_event_id');

  if ($organizationId === '') {
    wsRespond([
      'status' => 'error',
      'message' => 'organization_id is required.',
      'channel' => 'organization_audit',
      'timestamp_utc' => gmdate('Y-m-d\TH:i:s\Z'),
    ], 400);

    return;
  }

  $service = new OrganizationDiscoveryService();
  $result = $service->listAuditTimeline(User::currentUUID(), $organizationId);
  if (!$result['success']) {
    wsRespond([
      'status' => 'error',
      'message' => (string) $result['message'],
      'channel' => 'organization_audit',
      'organization_id' => $organizationId,
      'timestamp_utc' => gmdate('Y-m-d\TH:i:s\Z'),
    ], 403);

    return;
  }

  $allEvents = is_array($result['data']['events'] ?? null)
    ? $result['data']['events']
    : [];

  $events = $allEvents;
  if ($sinceEventId !== '') {
    $events = [];
    foreach ($allEvents as $event) {
      if (!is_array($event)) {
        continue;
      }

      $eventIdRaw = $event['event_id'] ?? '';
      $eventId = is_string($eventIdRaw) ? $eventIdRaw : '';
      if ($eventId === $sinceEventId) {
        break;
      }

      $events[] = $event;
    }
  }

  $latestEventId = '';
  if ($allEvents !== [] && is_array($allEvents[0])) {
    $latestEventIdRaw = $allEvents[0]['event_id'] ?? '';
    $latestEventId = is_string($latestEventIdRaw) ? $latestEventIdRaw : '';
  }

  wsRespond([
    'status' => 'success',
    'service' => 'ws-organization-audit',
    'channel' => 'organization_audit',
    'organization_id' => $organizationId,
    'latest_event_id' => $latestEventId,
    'events' => $events,
    'timestamp_utc' => gmdate('Y-m-d\TH:i:s\Z'),
  ]);

  return;
}

if ($channel === 'organization_requests_live') {
  $actorUUID = User::currentUUID();
  $sinceSignature = wsGetStringQuery('since_signature');

  $service = new OrganizationDiscoveryService();
  $organizationsResult = $service->listForUser($actorUUID);
  if (!$organizationsResult['success']) {
    wsRespond([
      'status' => 'error',
      'message' => (string) $organizationsResult['message'],
      'channel' => 'organization_requests_live',
      'timestamp_utc' => gmdate('Y-m-d\\TH:i:s\\Z'),
    ], 403);

    return;
  }

  $organizations = is_array($organizationsResult['data']['organizations'] ?? null)
    ? $organizationsResult['data']['organizations']
    : [];

  $pendingRequests = [];
  foreach ($organizations as $organization) {
    if (!is_array($organization)) {
      continue;
    }

    $organizationIdRaw = $organization['organization_id'] ?? '';
    $organizationId = is_scalar($organizationIdRaw) ? (string) $organizationIdRaw : '';
    if ($organizationId === '') {
      continue;
    }

    $organizationNameRaw = $organization['name'] ?? '';
    $organizationName = is_scalar($organizationNameRaw) ? (string) $organizationNameRaw : '';

    $requestsResult = $service->listAccessRequests($actorUUID, $organizationId);
    if (!$requestsResult['success']) {
      continue;
    }

    $requests = is_array($requestsResult['data']['requests'] ?? null)
      ? $requestsResult['data']['requests']
      : [];

    foreach ($requests as $request) {
      if (!is_array($request)) {
        continue;
      }

      $statusRaw = $request['status'] ?? 'pending';
      $status = is_scalar($statusRaw) ? (string) $statusRaw : 'pending';
      if ($status !== 'pending') {
        continue;
      }

      $requestIdRaw = $request['request_id'] ?? '';
      $requestId = is_scalar($requestIdRaw) ? (string) $requestIdRaw : '';
      if ($requestId === '') {
        continue;
      }

      $createdAtRaw = $request['created_at'] ?? '';
      $createdAt = is_scalar($createdAtRaw) ? (string) $createdAtRaw : '';

      $requesterEmailRaw = $request['requester_contact_email'] ?? '';
      $requesterEmail = is_scalar($requesterEmailRaw) ? (string) $requesterEmailRaw : '';

      $requesterUUIDRaw = $request['requester_uuid'] ?? '';
      $requesterUUID = is_scalar($requesterUUIDRaw) ? (string) $requesterUUIDRaw : '';

      $pendingRequests[] = [
        'request_id' => $requestId,
        'organization_id' => $organizationId,
        'organization_name' => $organizationName,
        'requester_contact_email' => $requesterEmail,
        'requester_uuid' => $requesterUUID,
        'status' => $status,
        'created_at' => $createdAt,
      ];
    }
  }

  usort($pendingRequests, static function (array $a, array $b): int {
    return strcmp((string) $b['created_at'], (string) $a['created_at']);
  });

  $signatureSeed = [];
  foreach ($pendingRequests as $request) {
    $signatureSeed[] = implode('|', [
      (string) $request['request_id'],
      (string) $request['organization_id'],
      (string) $request['status'],
      (string) $request['created_at'],
    ]);
  }

  $latestSignature = hash('sha256', implode("\n", $signatureSeed));
  $unchanged = ($sinceSignature !== '' && hash_equals($sinceSignature, $latestSignature));

  wsRespond([
    'status' => 'success',
    'service' => 'ws-organization-requests-live',
    'channel' => 'organization_requests_live',
    'pending_count' => count($pendingRequests),
    'latest_signature' => $latestSignature,
    'unchanged' => $unchanged,
    'pending_requests' => $pendingRequests,
    'timestamp_utc' => gmdate('Y-m-d\\TH:i:s\\Z'),
  ]);

  return;
}

if ($channel === 'organization_discovery') {
  $actorUUID = User::currentUUID();
  $sinceSignature = wsGetStringQuery('since_signature');

  $service = new OrganizationDiscoveryService();
  $result = $service->discoveryForUser($actorUUID);
  if (!$result['success']) {
    wsRespond([
      'status' => 'error',
      'message' => (string) $result['message'],
      'channel' => 'organization_discovery',
      'timestamp_utc' => gmdate('Y-m-d\\TH:i:s\\Z'),
    ], 403);

    return;
  }

  $data = $result['data'];
  $userSites = is_array($data['user_sites'] ?? null) ? $data['user_sites'] : [];
  $matchCandidates = is_array($data['match_candidates'] ?? null) ? $data['match_candidates'] : [];
  $signatureSeed = json_encode([
    'user_sites' => $userSites,
    'match_candidates' => $matchCandidates,
  ], JSON_UNESCAPED_SLASHES) ?: '';
  $latestSignature = hash('sha256', $signatureSeed);
  $unchanged = ($sinceSignature !== '' && hash_equals($sinceSignature, $latestSignature));

  wsRespond([
    'status' => 'success',
    'service' => 'ws-organization-discovery',
    'channel' => 'organization_discovery',
    'latest_signature' => $latestSignature,
    'unchanged' => $unchanged,
    'user_organizations' => is_array($data['user_organizations'] ?? null) ? $data['user_organizations'] : [],
    'user_sites' => $userSites,
    'match_candidates' => $matchCandidates,
    'timestamp_utc' => gmdate('Y-m-d\\TH:i:s\\Z'),
  ]);

  return;
}

if ($channel === 'organization_notifications') {
  $actorUUID = User::currentUUID();
  $summary = (new OrganizationNotificationService())->summarizeUnreadForUser($actorUUID);

  wsRespond([
    'status' => 'success',
    'service' => 'ws-organization-notifications',
    'channel' => 'organization_notifications',
    'total_unread' => (int) $summary['total_unread'],
    'unread_by_org' => $summary['by_org'],
    'timestamp_utc' => gmdate('Y-m-d\\TH:i:s\\Z'),
  ]);

  return;
}

if (!User::isAdmin()) {
  wsRespond([
    'status' => 'forbidden',
    'error' => 'Admin access required.',
  ], 403);

  return;
}

$heartbeatExtra = [];
try {
  $redisInfoData = MetricsService::getRedisInfo();
  $hitRateRaw = $redisInfoData['hit_rate_percent'] ?? null;
  $heartbeatExtra['redis_hit_rate'] = is_numeric($hitRateRaw) ? round((float) $hitRateRaw, 1) : null;
} catch (\Throwable) {
  $heartbeatExtra['redis_hit_rate'] = null;
}
try {
  $heartbeatExtra['active_sessions'] = count(Database::scanKeys('session:*'));
} catch (\Throwable) {
  $heartbeatExtra['active_sessions'] = null;
}

wsRespond(array_merge([
  'status' => 'ok',
  'service' => 'ws-heartbeat',
  'timestamp_utc' => gmdate('Y-m-d\TH:i:s\Z'),
], $heartbeatExtra));
