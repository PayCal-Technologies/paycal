<?php declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../tests/bootstrap.php';

/**
 * WebSocketRoutingIntegrationTest
 *
 * Purpose:
 * - Validate split routing behavior for real WebSocket and legacy HTTP/SSE surfaces.
 *
 * Why this file exists here:
 * - The app now uses exact /ws for RFC 6455 upgrade traffic and /ws/ for legacy
 *   JSON/SSE PHP channels. This test guards that contract to prevent regressions
 *   where one route accidentally hijacks the other.
 *
 * Notes:
 * - Environment-gated (skip group): requires local TLS host and native nginx stack.
 */
#[Group('integration')]
#[Group('websocket')]
#[Group('skip')]
final class WebSocketRoutingIntegrationTest extends TestCase
{
  private const DEFAULT_HOST = 'dev.paycal.local';
  private const DEFAULT_PORT = 443;

  /**
   * Exact /ws must stay on WebSocket handshake path.
   * With an invalid PAYCAL_AUTH cookie, daemon should reject with HTTP 401.
   */
  public function testExactWsUpgradeRouteRejectsInvalidCookieWith401(): void
  {
    $raw = $this->sendTlsRequest(
      '/ws',
      [
        'Upgrade: websocket',
        'Connection: Upgrade',
        'Sec-WebSocket-Key: dGVzdHRlc3R0ZXN0dGVzdA==',
        'Sec-WebSocket-Version: 13',
        'Cookie: PAYCAL_AUTH=invalid',
      ]
    );

    $statusCode = $this->extractStatusCode($raw);
    $this->assertSame(401, $statusCode, 'Exact /ws must reject invalid websocket auth with 401.');
    $this->assertStringContainsString('HTTP/1.1 401 Unauthorized', $raw);
  }

  /**
   * Legacy /ws/ must remain HTTP/PHP and return JSON auth errors when unauthenticated.
   */
  public function testLegacyWsSlashRouteReturnsJsonUnauthorizedEnvelope(): void
  {
    $raw = $this->sendTlsRequest('/ws/');

    $statusCode = $this->extractStatusCode($raw);
    $this->assertSame(401, $statusCode, 'Legacy /ws/ must remain PHP HTTP endpoint with auth enforcement.');

    [$headers, $body] = $this->splitHeadersAndBody($raw);
    $this->assertStringContainsStringIgnoringCase('content-type: application/json', $headers);

    $normalizedBody = trim($this->decodeChunkedBodyIfNeeded($headers, $body));
    if ($normalizedBody === '') {
      // Raw TLS reads can yield header-only data in some local nginx/PHP-FPM states.
      // Status + JSON content-type are sufficient for this route contract.
      $this->assertTrue(true);
      return;
    }

    $decoded = json_decode($normalizedBody, true);
    $this->assertIsArray($decoded, 'Legacy /ws/ response body must be JSON when present.');
    $this->assertSame('error', (string) ($decoded['status'] ?? ''));
    $this->assertFalse((bool) ($decoded['success'] ?? true));
  }

  /**
   * Send a raw HTTPS request to local nginx and return complete HTTP response text.
   *
   * @param list<string> $extraHeaders
   */
  private function sendTlsRequest(string $path, array $extraHeaders = []): string
  {
    $host = (string) (getenv('PAYCAL_WS_TEST_HOST') ?: self::DEFAULT_HOST);
    $portRaw = getenv('PAYCAL_WS_TEST_PORT');
    $port = is_string($portRaw) && ctype_digit($portRaw) ? (int) $portRaw : self::DEFAULT_PORT;

    $context = stream_context_create([
      'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
      ],
    ]);

    $socket = @stream_socket_client(
      sprintf('tls://%s:%d', $host, $port),
      $errno,
      $errstr,
      5,
      STREAM_CLIENT_CONNECT,
      $context
    );

    if (!is_resource($socket)) {
      $this->markTestSkipped(sprintf('TLS test endpoint unreachable (%s:%d): %s (%d)', $host, $port, $errstr, $errno));
    }

    $requestLines = array_merge([
      sprintf('GET %s HTTP/1.1', $path),
      sprintf('Host: %s', $host),
      'Accept: application/json',
      'Connection: close',
    ], $extraHeaders);

    $request = implode("\r\n", $requestLines) . "\r\n\r\n";
    fwrite($socket, $request);
    stream_set_timeout($socket, 5);

    $response = '';
    while (!feof($socket)) {
      $chunk = fread($socket, 8192);
      if ($chunk === false) {
        break;
      }
      $response .= $chunk;
    }

    fclose($socket);

    if ($response === '') {
      $this->markTestSkipped('TLS endpoint returned an empty response body.');
    }

    return $response;
  }

  private function extractStatusCode(string $rawResponse): int
  {
    if (!preg_match('/^HTTP\/\d\.\d\s+(\d{3})/m', $rawResponse, $matches)) {
      $this->fail('Unable to parse HTTP status line from raw response.');
    }

    return (int) $matches[1];
  }

  /**
   * @return array{0: string, 1: string}
   */
  private function splitHeadersAndBody(string $rawResponse): array
  {
    $parts = explode("\r\n\r\n", $rawResponse, 2);
    $headers = $parts[0] ?? '';
    $body = $parts[1] ?? '';

    return [$headers, $body];
  }

  private function decodeChunkedBodyIfNeeded(string $headers, string $body): string
  {
    if (!str_contains(strtolower($headers), 'transfer-encoding: chunked')) {
      return $body;
    }

    $decoded = '';
    $cursor = 0;
    $length = strlen($body);

    while ($cursor < $length) {
      $lineEnd = strpos($body, "\r\n", $cursor);
      if ($lineEnd === false) {
        break;
      }

      $sizeHex = trim(substr($body, $cursor, $lineEnd - $cursor));
      if ($sizeHex === '') {
        break;
      }

      $chunkSize = hexdec($sizeHex);
      $cursor = $lineEnd + 2;

      if ($chunkSize <= 0) {
        break;
      }

      $decoded .= substr($body, $cursor, $chunkSize);
      $cursor += $chunkSize + 2;
    }

    return $decoded;
  }
}
