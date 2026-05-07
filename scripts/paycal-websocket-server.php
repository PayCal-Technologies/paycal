#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * paycal-websocket-server.php
 *
 * Purpose:
 * - Run the native PayCal WebSocket daemon for browser clients that need a
 *   real RFC 6455 transport instead of SSE over HTTP.
 *
 * Usage context:
 * - Started locally by scripts/paycal-websocket-up.sh and stopped by
 *   scripts/paycal-websocket-down.sh.
 * - Intended for the native macOS development stack behind nginx.
 *
 * Why this file exists here:
 * - The app already had /ws/ HTTP and SSE channels. This daemon makes exact
 *   /ws a true WebSocket endpoint while preserving the legacy /ws/ PHP routes.
 */

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Enums\AuthLevel;

require_once __DIR__ . '/../html/config.php';

final class PayCalWebSocketServer
{
  private const DEFAULT_HOST = '127.0.0.1';
  private const DEFAULT_PORT = 8081;
  private const SELECT_TIMEOUT_SECONDS = 1;
  private const MAX_FRAME_BYTES = 1048576;
  private const AUDIT_SNAPSHOT_LIMIT = 50;

  /** @var resource */
  private $server;

  /**
   * @var array<int, array{socket: resource, buffer: string, handshake: bool, user_uuid: string, is_admin: bool, latest_audit_event_id: string, subscriptions: array<string, bool>}>
   */
  private array $clients = [];

  private float $lastAuditPollAt = 0.0;

  public function __construct(
    private readonly string $host,
    private readonly int $port
  ) {
  }

  public static function fromEnvironment(): self
  {
    $host = getenv('PAYCAL_WS_HOST');
    $port = getenv('PAYCAL_WS_PORT');

    $resolvedHost = is_string($host) && trim($host) !== '' ? trim($host) : self::DEFAULT_HOST;
    $resolvedPort = is_string($port) && ctype_digit($port) ? (int) $port : self::DEFAULT_PORT;

    return new self($resolvedHost, $resolvedPort);
  }

  public function run(): void
  {
    $endpoint = sprintf('tcp://%s:%d', $this->host, $this->port);
    $server = @stream_socket_server($endpoint, $errno, $errstr);
    if (!is_resource($server)) {
      throw new \RuntimeException(sprintf('Unable to bind WebSocket server on %s: %s (%d)', $endpoint, $errstr, $errno));
    }

    stream_set_blocking($server, false);
    $this->server = $server;
    $this->log(sprintf('listening on %s', $endpoint));

    while (true) {
      $read = [$this->server];
      foreach ($this->clients as $client) {
        $read[] = $client['socket'];
      }

      $write = null;
      $except = null;
      @stream_select($read, $write, $except, self::SELECT_TIMEOUT_SECONDS);

      foreach ($read as $socket) {
        if ($socket === $this->server) {
          $this->acceptClient();
          continue;
        }

        $clientId = $this->findClientIdBySocket($socket);
        if ($clientId === null) {
          continue;
        }

        $this->readClient($clientId);
      }

      $this->pollAuditEvents();
    }
  }

  private function acceptClient(): void
  {
    $socket = @stream_socket_accept($this->server, 0);
    if (!is_resource($socket)) {
      return;
    }

    stream_set_blocking($socket, false);
    $this->clients[(int) $socket] = [
      'socket' => $socket,
      'buffer' => '',
      'handshake' => false,
      'user_uuid' => '',
      'is_admin' => false,
      'latest_audit_event_id' => '',
      'subscriptions' => [],
    ];
  }

  private function readClient(int $clientId): void
  {
    $client = $this->clients[$clientId] ?? null;
    if ($client === null) {
      return;
    }

    $chunk = @fread($client['socket'], 8192);
    if ($chunk === '' || $chunk === false) {
      if (feof($client['socket'])) {
        $this->disconnect($clientId);
      }
      return;
    }

    $this->clients[$clientId]['buffer'] .= $chunk;

    if (!$this->clients[$clientId]['handshake']) {
      $this->handleHandshake($clientId);
      return;
    }

    while (true) {
      $frame = $this->tryExtractFrame($this->clients[$clientId]['buffer']);
      if ($frame === null) {
        break;
      }

      $opcode = $frame['opcode'];
      $payload = $frame['payload'];

      if ($opcode === 0x8) {
        $this->disconnect($clientId);
        return;
      }

      if ($opcode === 0x9) {
        $this->writeFrame($clientId, $payload, 0xA);
        continue;
      }

      if ($opcode !== 0x1) {
        continue;
      }

      $this->handleClientMessage($clientId, $payload);
    }
  }

  private function handleHandshake(int $clientId): void
  {
    $buffer = $this->clients[$clientId]['buffer'];
    $delimiterPos = strpos($buffer, "\r\n\r\n");
    if ($delimiterPos === false) {
      return;
    }

    $request = substr($buffer, 0, $delimiterPos + 4);
    $this->clients[$clientId]['buffer'] = substr($buffer, $delimiterPos + 4);

    $lines = preg_split("/\r\n/", trim($request)) ?: [];
    $requestLine = array_shift($lines);
    if (!is_string($requestLine) || !preg_match('/^GET\s+(\S+)\s+HTTP\/1\.[01]$/', $requestLine, $matches)) {
      $this->rejectHandshake($clientId, 400, 'Bad Request');
      return;
    }

    $path = $matches[1];
    if ($path !== '/ws') {
      $this->rejectHandshake($clientId, 404, 'Not Found');
      return;
    }

    $headers = [];
    foreach ($lines as $line) {
      $parts = explode(':', $line, 2);
      if (count($parts) !== 2) {
        continue;
      }
      $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
    }

    $upgrade = strtolower((string) ($headers['upgrade'] ?? ''));
    $connection = strtolower((string) ($headers['connection'] ?? ''));
    $key = (string) ($headers['sec-websocket-key'] ?? '');
    if ($upgrade !== 'websocket' || !str_contains($connection, 'upgrade') || $key === '') {
      $this->rejectHandshake($clientId, 400, 'Invalid WebSocket Handshake');
      return;
    }

    $auth = $this->authenticateFromCookieHeader((string) ($headers['cookie'] ?? ''));
    if ($auth === null) {
      $this->rejectHandshake($clientId, 401, 'Unauthorized');
      return;
    }

    $accept = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
    $response = implode("\r\n", [
      'HTTP/1.1 101 Switching Protocols',
      'Upgrade: websocket',
      'Connection: Upgrade',
      'Sec-WebSocket-Accept: ' . $accept,
      '',
      '',
    ]);

    fwrite($this->clients[$clientId]['socket'], $response);
    $this->clients[$clientId]['handshake'] = true;
    $this->clients[$clientId]['user_uuid'] = $auth['user_uuid'];
    $this->clients[$clientId]['is_admin'] = $auth['is_admin'];

    $this->sendJson($clientId, [
      'type' => 'connected',
      'user_uuid' => $auth['user_uuid'],
      'timestamp' => date('c'),
    ]);
  }

  /**
   * @return null|array{user_uuid: string, is_admin: bool, user: User}
   */
  private function authenticateFromCookieHeader(string $cookieHeader): ?array
  {
    $sessionHash = '';
    foreach (explode(';', $cookieHeader) as $fragment) {
      $fragment = trim($fragment);
      if (!str_starts_with($fragment, 'PAYCAL_AUTH=')) {
        continue;
      }
      $sessionHash = trim((string) rawurldecode(substr($fragment, strlen('PAYCAL_AUTH='))));
      break;
    }

    if ($sessionHash === '') {
      return null;
    }

    $userUuid = Authentication::getUserUUIDFromSession($sessionHash);
    if ($userUuid === null || $userUuid === '') {
      return null;
    }

    Authentication::touchSession($sessionHash);

    $user = UserRepository::getByUUID($userUuid);
    if ($user === null) {
      return null;
    }

    return [
      'user_uuid' => $userUuid,
      'is_admin' => $user->auth_level->atLeast(AuthLevel::ADMIN),
      'user' => $user,
    ];
  }

  private function handleClientMessage(int $clientId, string $payload): void
  {
    $decoded = json_decode($payload, true);
    if (!is_array($decoded)) {
      $this->sendJson($clientId, [
        'type' => 'error',
        'message' => 'Invalid JSON payload.',
      ]);
      return;
    }

    $action = trim((string) ($decoded['action'] ?? ''));
    if ($action === 'ping') {
      $this->sendJson($clientId, ['type' => 'pong', 'timestamp' => date('c')]);
      return;
    }

    if ($action === 'subscribe' && ($decoded['channel'] ?? '') === 'system_audit') {
      $this->subscribeClientToSystemAudit($clientId);
      return;
    }

    $this->sendJson($clientId, [
      'type' => 'error',
      'message' => 'Unsupported action.',
    ]);
  }

  private function subscribeClientToSystemAudit(int $clientId): void
  {
    $client = $this->clients[$clientId] ?? null;
    if ($client === null) {
      return;
    }

    $user = UserRepository::getByUUID($client['user_uuid']);
    if ($user === null || !$client['is_admin']) {
      $this->sendJson($clientId, [
        'type' => 'error',
        'message' => 'Admin access required.',
      ]);
      return;
    }

    try {
      SystemAuditPolicy::assertCanRead($user);
    } catch (AuditAccessDeniedException $e) {
      $this->sendJson($clientId, [
        'type' => 'error',
        'message' => $e->getMessage(),
      ]);
      return;
    }

    SystemAuditRepository::recordReadAccess($client['user_uuid'], 'websocket_subscribe_system_audit');

    $events = SystemAuditRepository::recent(self::AUDIT_SNAPSHOT_LIMIT);
    $latestEventId = '';
    if ($events !== [] && is_array($events[0])) {
      $latestEventId = (string) ($events[0]['event_id'] ?? '');
    }

    $this->clients[$clientId]['subscriptions']['system_audit'] = true;
    $this->clients[$clientId]['latest_audit_event_id'] = $latestEventId;

    $this->sendJson($clientId, [
      'type' => 'audit_snapshot',
      'events' => $events,
      'timestamp' => date('c'),
    ]);
  }

  private function pollAuditEvents(): void
  {
    $now = microtime(true);
    if (($now - $this->lastAuditPollAt) < 1.0) {
      return;
    }

    $this->lastAuditPollAt = $now;
    $events = SystemAuditRepository::recent(self::AUDIT_SNAPSHOT_LIMIT);

    foreach ($this->clients as $clientId => $client) {
      if (($client['subscriptions']['system_audit'] ?? false) !== true) {
        continue;
      }

      $latestSeen = $client['latest_audit_event_id'];
      $newEvents = [];
      foreach ($events as $event) {
        if (!is_array($event)) {
          continue;
        }

        $eventId = (string) ($event['event_id'] ?? '');
        if ($eventId === '') {
          continue;
        }

        if ($latestSeen !== '' && $eventId === $latestSeen) {
          break;
        }

        $newEvents[] = $event;
      }

      if ($newEvents === []) {
        continue;
      }

      $ordered = array_reverse($newEvents);
      foreach ($ordered as $event) {
        $this->sendJson($clientId, [
          'type' => 'audit_event',
          'event' => $event,
          'timestamp' => date('c'),
        ]);
      }

      $newLatest = (string) ($events[0]['event_id'] ?? '');
      $this->clients[$clientId]['latest_audit_event_id'] = $newLatest;
    }
  }

  private function rejectHandshake(int $clientId, int $statusCode, string $reason): void
  {
    $socket = $this->clients[$clientId]['socket'];
    fwrite($socket, sprintf("HTTP/1.1 %d %s\r\nConnection: close\r\nContent-Length: 0\r\n\r\n", $statusCode, $reason));
    $this->disconnect($clientId);
  }

  /**
   * @param array<string, mixed> $payload
   */
  private function sendJson(int $clientId, array $payload): void
  {
    if (!isset($this->clients[$clientId])) {
      return;
    }

    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
      return;
    }

    $this->writeFrame($clientId, $json, 0x1);
  }

  private function writeFrame(int $clientId, string $payload, int $opcode): void
  {
    $socket = $this->clients[$clientId]['socket'] ?? null;
    if (!is_resource($socket)) {
      return;
    }

    $length = strlen($payload);
    $header = chr(0x80 | ($opcode & 0x0F));
    if ($length < 126) {
      $header .= chr($length);
    } elseif ($length <= 65535) {
      $header .= chr(126) . pack('n', $length);
    } else {
      $header .= chr(127) . pack('NN', 0, $length);
    }

    @fwrite($socket, $header . $payload);
  }

  /**
   * @param string $buffer
   * @return null|array{opcode: int, payload: string}
   */
  private function tryExtractFrame(string &$buffer): ?array
  {
    $bufferLength = strlen($buffer);
    if ($bufferLength < 2) {
      return null;
    }

    $first = ord($buffer[0]);
    $second = ord($buffer[1]);
    $opcode = $first & 0x0F;
    $masked = ($second & 0x80) !== 0;
    $payloadLength = $second & 0x7F;
    $offset = 2;

    if ($payloadLength === 126) {
      if ($bufferLength < 4) {
        return null;
      }
      $payloadLength = unpack('n', substr($buffer, 2, 2))[1];
      $offset = 4;
    } elseif ($payloadLength === 127) {
      if ($bufferLength < 10) {
        return null;
      }
      $parts = unpack('Nhigh/Nlow', substr($buffer, 2, 8));
      $payloadLength = (int) $parts['low'];
      $offset = 10;
    }

    if ($payloadLength > self::MAX_FRAME_BYTES) {
      throw new \RuntimeException('WebSocket frame exceeds maximum size.');
    }

    $maskLength = $masked ? 4 : 0;
    if ($bufferLength < ($offset + $maskLength + $payloadLength)) {
      return null;
    }

    $mask = $masked ? substr($buffer, $offset, 4) : '';
    $offset += $maskLength;
    $payload = substr($buffer, $offset, $payloadLength);
    $buffer = substr($buffer, $offset + $payloadLength);

    if ($masked && $mask !== '') {
      $unmasked = '';
      for ($i = 0; $i < $payloadLength; $i += 1) {
        $unmasked .= $payload[$i] ^ $mask[$i % 4];
      }
      $payload = $unmasked;
    }

    return [
      'opcode' => $opcode,
      'payload' => $payload,
    ];
  }

  /**
   * @param resource $socket
   */
  private function findClientIdBySocket($socket): ?int
  {
    $target = (int) $socket;
    return isset($this->clients[$target]) ? $target : null;
  }

  private function disconnect(int $clientId): void
  {
    $socket = $this->clients[$clientId]['socket'] ?? null;
    if (is_resource($socket)) {
      @fclose($socket);
    }
    unset($this->clients[$clientId]);
  }

  private function log(string $message): void
  {
    fwrite(STDERR, sprintf("[%s] %s\n", date('c'), $message));
  }
}

$server = PayCalWebSocketServer::fromEnvironment();
$server->run();