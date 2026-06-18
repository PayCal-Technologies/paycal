<?php declare(strict_types=1);

/**
 * GcsEvidenceUploader.php
 *
 * Purpose: Upload SOC 2 audit-ledger evidence artifacts to Google Cloud Storage
 *          using a service-account JSON key.  No external Composer dependencies —
 *          all HTTP is done via cURL (fallback: stream context).
 *
 * Usage:
 *   require __DIR__ . '/GcsEvidenceUploader.php';
 *   $uploader = GcsEvidenceUploader::fromEnv();
 *
 *   // Normal run: one combined artifact (verification + head)
 *   $chainTip = GcsEvidenceUploader::loadChainTip($redisGet);
 *   $result   = $uploader->uploadVerificationArtifact($report, $timestampIso8601, $chainTip);
 *   if ($result['uploaded']) {
 *     GcsEvidenceUploader::persistChainTip($redisSet, $result['object_path'], $result['object_hash']);
 *   }
 *
 *   // Failure run: alert artifact
 *   $result = $uploader->uploadAlertArtifact($report, $webhook, $timestampIso8601, $chainTip);
 *
 * Design decisions:
 *   - ONE object per verifier run (combined verification result + head snapshot).
 *     Head snapshot fields (head_sequence, head_hash) are already present in the
 *     verification result, so a separate head object would be redundant.
 *   - CHAINED objects: each artifact records the previous artifact's GCS path and
 *     SHA-256 hash, creating a verifiable chain of evidence that auditors can walk.
 *   - RETRY: up to 3 attempts per upload with backoff; failure logged, never fatal.
 *   - Chain tip (previous path + hash) is stored externally by the caller (Redis key
 *     system:audit:gcs:chain_tip) so the uploader itself remains stateless.
 *
 * Environment variables:
 *   GCS_SOC2_KEY_FILE          - absolute path to service-account JSON key file
 *   GCS_SOC2_BUCKET            - GCS bucket name (default: paycal-soc2-evidence)
 *   GCS_SOC2_ENVIRONMENT       - server identity: "local" | "dev" | "prod" (default: prod)
 *   GCS_SOC2_TIMEOUT_SECONDS   - HTTP timeout per attempt (default: 10)
 *
 * Authentication:
 *   Uses a GCP service account JSON key to obtain a short-lived OAuth2 access token
 *   via the Google token endpoint (RS256 JWT self-signed assertion).
 *   The service account must have roles/storage.objectCreator on the bucket only.
 *   No delete or admin permissions should be granted.
 *
 * Batching strategy (SOC 2 free-tier capacity):
 *   Upload once per scheduled verifier run (hourly by recommendation).
 *   Do NOT upload one object per raw event.
 *   At hourly cadence: ~720 operations/month → well within GCS free-tier (5,000 ops/month).
 *   Storage estimate: ~1–10 MB/month → well within free-tier (5 GiB).
 *
 * GCS object layout:
 *   soc2/audit-ledger/verification/YYYY/MM/verification-<ISO8601>.json
 *   soc2/audit-ledger/alerts/YYYY/MM/alert-<ISO8601>.json
 *
 * Why this file exists:
 *   SOC 2 requires off-host, tamper-resistant evidence retention.  GCS Bucket Lock
 *   provides WORM semantics — objects cannot be deleted or overwritten once the
 *   retention lock is enforced.  Evidence objects form a cryptographic chain: each
 *   references the SHA-256 of the previous object, producing an auditor-walkable
 *   evidence trail independent of (but corroborating) the on-chain TheLedger hash.
 *
 * @category   CLI / SOC2 Evidence
 * @package    copilot-scripts
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

final class GcsEvidenceUploader
{
  private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';
  private const STORAGE_UPLOAD_BASE = 'https://storage.googleapis.com/upload/storage/v1/b/';
  private const SCOPE = 'https://www.googleapis.com/auth/devstorage.read_write';
  private const TOKEN_TTL_SECONDS = 3600;
  private const DEFAULT_BUCKET = 'paycal-soc2-evidence';
  private const DEFAULT_LOCAL_KEY_FILE = '/var/www/secure/gen-lang-client-0022749706-f5ba00ac5b12.json';
  private const DEFAULT_TIMEOUT = 10;
  private const UPLOAD_MAX_ATTEMPTS = 10;

  /** @var array<string, scalar>|null Cached decoded service-account key */
  private ?array $serviceAccountKey = null;

  /** Cached short-lived access token */
  private string $accessToken = '';

  /** Unix time when the cached token expires */
  private int $tokenExpiresAt = 0;

  private function __construct(
    private readonly string $keyFilePath,
    private readonly string $bucket,
    private readonly string $environment,
    private readonly int $timeoutSeconds,
  ) {
  }

  /**
   * Build from environment variables.
   *
   * @throws \RuntimeException if GCS_SOC2_KEY_FILE is not set or unreadable
   */
  public static function fromEnv(): self
  {
    $keyFile = self::resolveKeyFilePath();
    $bucket  = trim((string) (getenv('GCS_SOC2_BUCKET')   ?: self::DEFAULT_BUCKET));
    $env     = trim((string) (getenv('GCS_SOC2_ENVIRONMENT') ?: 'prod'));
    $timeoutRaw = getenv('GCS_SOC2_TIMEOUT_SECONDS');
    $timeout = (is_string($timeoutRaw) && ctype_digit($timeoutRaw)) ? max(1, (int) $timeoutRaw) : self::DEFAULT_TIMEOUT;

    if ($keyFile === '') {
      throw new \RuntimeException('GCS service account key file is not configured. Set GCS_SOC2_KEY_FILE or provide the local secure key file.');
    }

    return new self($keyFile, $bucket, $env, $timeout);
  }

  /**
   * Returns false (not configured) when GCS_SOC2_KEY_FILE is empty, without throwing.
   * Use this to check before calling fromEnv() in scripts that make GCS optional.
   */
  public static function isConfigured(): bool
  {
    return self::resolveKeyFilePath() !== '';
  }

  private static function resolveKeyFilePath(): string
  {
    $fromEnv = trim((string) (getenv('GCS_SOC2_KEY_FILE') ?: ''));
    if ($fromEnv !== '') {
      return $fromEnv;
    }

    return is_readable(self::DEFAULT_LOCAL_KEY_FILE) ? self::DEFAULT_LOCAL_KEY_FILE : '';
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Chain-tip helpers (static; caller manages Redis persistence)
  // ─────────────────────────────────────────────────────────────────────────

  /**
   * Decode a chain-tip JSON string from Redis into an array.
   * Returns ['object_path' => '', 'object_hash' => ''] when nothing is stored yet
   * (genesis state — the first object will have no previous reference).
   *
   * @return array{object_path: string, object_hash: string}
   */
  public static function loadChainTip(string $raw): array
  {
    if ($raw === '') {
      return ['object_path' => '', 'object_hash' => ''];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      return ['object_path' => '', 'object_hash' => ''];
    }
    return [
      'object_path' => (string) ($decoded['object_path'] ?? ''),
      'object_hash' => (string) ($decoded['object_hash'] ?? ''),
    ];
  }

  /**
   * Serialize the new chain tip into JSON for Redis storage.
   * Call this after a successful upload with the result's object_path and object_hash.
   */
  public static function persistChainTip(string $objectPath, string $objectHash): string
  {
    return (string) json_encode(['object_path' => $objectPath, 'object_hash' => $objectHash], JSON_UNESCAPED_SLASHES);
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Public upload methods
  // ─────────────────────────────────────────────────────────────────────────

  /**
   * Upload a single combined verification + head artifact for one verifier run.
   *
   * Combines what were previously two separate objects (verification report and head
   * snapshot) into one.  The verification result already contains head_sequence and
   * head_hash, so a separate head object is redundant.
   *
   * The artifact includes a 'chain' block referencing the previous artifact's GCS
   * object path and SHA-256 hash, creating a verifiable chain of evidence objects.
   *
   * @param array<string, scalar> $verificationResult  Result from verifyImmutableLedger()
   * @param string $timestampIso8601                   RFC 3339 timestamp for this run
   * @param array{object_path: string, object_hash: string} $previousChainTip  From loadChainTip()
   * @return array{uploaded: bool, object_path: string, object_hash: string, http_code: int, error: string, attempts: int}
   */
  public function uploadVerificationArtifact(
    array $verificationResult,
    string $timestampIso8601,
    array $previousChainTip
  ): array {
    $payload = [
      'type'        => 'ledger_verification',
      'timestamp'   => $timestampIso8601,
      'server'      => $this->environment,
      'environment' => $this->environment,
      'service'     => 'paycal-api',
      'result'      => $verificationResult,
      'chain'       => [
        'previous_object_path' => $previousChainTip['object_path'],
        'previous_object_hash' => $previousChainTip['object_hash'],
        'genesis'              => ($previousChainTip['object_path'] === ''),
      ],
    ];

    $objectPath = $this->buildObjectPath('verification', 'verification', $timestampIso8601);

    return $this->uploadWithRetry($objectPath, $payload);
  }

  /**
   * Upload an alert artifact on integrity failure.
   *
   * Also chained: includes previous artifact reference so the alert slot in the
   * evidence chain is unambiguously correlated to the run that triggered it.
   *
   * @param array<string, scalar> $verificationResult  Full verification result
   * @param array<string, scalar> $webhookResult       Result from sendAuditLedgerFailureWebhook()
   * @param string $timestampIso8601                   RFC 3339 timestamp for this run
   * @param array{object_path: string, object_hash: string} $previousChainTip  From loadChainTip()
   * @return array{uploaded: bool, object_path: string, object_hash: string, http_code: int, error: string, attempts: int}
   */
  public function uploadAlertArtifact(
    array $verificationResult,
    array $webhookResult,
    string $timestampIso8601,
    array $previousChainTip
  ): array {
    $payload = [
      'type'                => 'ledger_alert',
      'timestamp'           => $timestampIso8601,
      'server'              => $this->environment,
      'environment'         => $this->environment,
      'severity'            => 'critical',
      'verification_result' => $verificationResult,
      'webhook'             => [
        'attempted'   => (bool)   ($webhookResult['attempted'] ?? false),
        'status_code' => (int)    ($webhookResult['http_code'] ?? 0),
        'delivered'   => (bool)   ($webhookResult['delivered'] ?? false),
        'error'       => (string) ($webhookResult['error'] ?? ''),
      ],
      'chain' => [
        'previous_object_path' => $previousChainTip['object_path'],
        'previous_object_hash' => $previousChainTip['object_hash'],
        'genesis'              => ($previousChainTip['object_path'] === ''),
      ],
    ];

    $objectPath = $this->buildObjectPath('alerts', 'alert', $timestampIso8601);

    return $this->uploadWithRetry($objectPath, $payload);
  }

  /**
   * Return configured GCS bucket name.
   */
  public function getBucket(): string
  {
    return $this->bucket;
  }

  /**
   * Return configured environment label.
   */
  public function getEnvironment(): string
  {
    return $this->environment;
  }

  /**
   * List objects in the configured GCS bucket under a prefix.
   *
   * @return array{
   *   ok: bool,
   *   http_code: int,
   *   error: string,
    *   error_code: string,
    *   error_detail: string,
   *   next_page_token: string,
   *   prefixes: array<int, string>,
   *   items: array<int, array{
   *     name: string,
   *     size: string,
   *     updated: string,
   *     time_created: string,
   *     content_type: string,
   *     md5_hash: string,
   *     crc32c: string,
   *     generation: string,
   *     metageneration: string
   *   }>
   * }
   */
  public function listObjectsByPrefix(string $prefix, int $maxResults = 200, string $pageToken = '', string $delimiter = ''): array
  {
    $trimmedPrefix = ltrim(trim($prefix), '/');
    $boundedMaxResults = max(1, min($maxResults, 1000));
    $trimmedPageToken = trim($pageToken);
    $trimmedDelimiter = trim($delimiter);

    try {
      $token = $this->getAccessToken();
    } catch (\Throwable $e) {
      return [
        'ok' => false,
        'http_code' => 0,
        'error' => 'token_error',
        'error_code' => 'token_error',
        'error_detail' => $e->getMessage(),
        'next_page_token' => '',
        'prefixes' => [],
        'items' => [],
      ];
    }

    $query = [
      'prefix' => $trimmedPrefix,
      'maxResults' => (string) $boundedMaxResults,
      'fields' => 'items(name,size,updated,timeCreated,contentType,md5Hash,crc32c,generation,metageneration),prefixes,nextPageToken',
    ];
    if ($trimmedPageToken !== '') {
      $query['pageToken'] = $trimmedPageToken;
    }
    if ($trimmedDelimiter !== '') {
      $query['delimiter'] = $trimmedDelimiter;
    }

    $url = 'https://storage.googleapis.com/storage/v1/b/'
      . rawurlencode($this->bucket)
      . '/o?'
      . http_build_query($query);

    $response = $this->requestJson(
      'GET',
      $url,
      ['Authorization: Bearer ' . $token],
      ''
    );

    $lastHttpCode = (int) ($response['http_code'] ?? 0);
    if (!((bool) ($response['ok'] ?? false))) {
      $errorCode = (string) ($response['error_code'] ?? 'gcs_list_failed');
      $errorDetail = (string) ($response['error_detail'] ?? (string) ($response['error'] ?? ''));
      return [
        'ok' => false,
        'http_code' => $lastHttpCode,
        'error' => $errorCode,
        'error_code' => $errorCode,
        'error_detail' => $errorDetail,
        'next_page_token' => '',
        'prefixes' => [],
        'items' => [],
      ];
    }

    $decoded = $response['decoded'];
    if (!is_array($decoded)) {
      return [
        'ok' => false,
        'http_code' => $lastHttpCode,
        'error' => 'invalid_gcs_list_response',
        'error_code' => 'invalid_gcs_list_response',
        'error_detail' => '',
        'next_page_token' => '',
        'prefixes' => [],
        'items' => [],
      ];
    }

    $allItems = [];
    $items = $decoded['items'] ?? [];
    if (is_array($items)) {
      foreach ($items as $item) {
        if (!is_array($item)) {
          continue;
        }

        $allItems[] = [
          'name' => (string) ($item['name'] ?? ''),
          'size' => (string) ($item['size'] ?? '0'),
          'updated' => (string) ($item['updated'] ?? ''),
          'time_created' => (string) ($item['timeCreated'] ?? ''),
          'content_type' => (string) ($item['contentType'] ?? ''),
          'md5_hash' => (string) ($item['md5Hash'] ?? ''),
          'crc32c' => (string) ($item['crc32c'] ?? ''),
          'generation' => (string) ($item['generation'] ?? ''),
          'metageneration' => (string) ($item['metageneration'] ?? ''),
        ];
      }
    }

    $prefixes = [];
    $prefixEntries = $decoded['prefixes'] ?? [];
    if (is_array($prefixEntries)) {
      foreach ($prefixEntries as $prefixEntry) {
        if (!is_scalar($prefixEntry)) {
          continue;
        }

        $prefixes[] = (string) $prefixEntry;
      }
    }

    $nextToken = $decoded['nextPageToken'] ?? '';
    $nextPageToken = is_scalar($nextToken) ? (string) $nextToken : '';

    return [
      'ok' => true,
      'http_code' => $lastHttpCode,
      'error' => '',
      'error_code' => '',
      'error_detail' => '',
      'next_page_token' => $nextPageToken,
      'prefixes' => $prefixes,
      'items' => $allItems,
    ];
  }

  /**
   * Download a single object body from GCS by object path.
   *
   * @return array{ok: bool, http_code: int, error: string, error_code: string, error_detail: string, content_type: string, body: string}
   */
  public function downloadObject(string $objectPath): array
  {
    $trimmedObjectPath = ltrim(trim($objectPath), '/');
    if ($trimmedObjectPath === '') {
      return [
        'ok' => false,
        'http_code' => 0,
        'error' => 'empty_object_path',
        'error_code' => 'empty_object_path',
        'error_detail' => '',
        'content_type' => '',
        'body' => '',
      ];
    }

    try {
      $token = $this->getAccessToken();
    } catch (\Throwable $e) {
      return [
        'ok' => false,
        'http_code' => 0,
        'error' => 'token_error',
        'error_code' => 'token_error',
        'error_detail' => $e->getMessage(),
        'content_type' => '',
        'body' => '',
      ];
    }

    $url = 'https://storage.googleapis.com/storage/v1/b/'
      . rawurlencode($this->bucket)
      . '/o/'
      . rawurlencode($trimmedObjectPath)
      . '?alt=media';

    $response = $this->requestRaw(
      'GET',
      $url,
      ['Authorization: Bearer ' . $token],
      ''
    );

    return [
      'ok' => (bool) ($response['ok'] ?? false),
      'http_code' => (int) ($response['http_code'] ?? 0),
      'error' => (string) ($response['error'] ?? ''),
      'error_code' => (string) ($response['error_code'] ?? ''),
      'error_detail' => (string) ($response['error_detail'] ?? ''),
      'content_type' => (string) ($response['content_type'] ?? ''),
      'body' => (string) ($response['body'] ?? ''),
    ];
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Internal: object path builder
  // ─────────────────────────────────────────────────────────────────────────

  /**
   * Build a deterministic GCS object path.
   *
   * Pattern: soc2/audit-ledger/{category}/YYYY/MM/{prefix}-{urlsafe-iso8601}.json
   */
  private function buildObjectPath(string $category, string $prefix, string $timestampIso8601): string
  {
    // Extract YYYY/MM from the ISO8601 string (first 7 chars: "2026-04")
    $ym   = substr($timestampIso8601, 0, 7); // "2026-04"
    $year = substr($ym, 0, 4);
    $mon  = substr($ym, 5, 2);

    // Make the timestamp safe for GCS object names (replace colons and + with -)
    $safeTs = preg_replace('/[:\+]/', '-', $timestampIso8601) ?? $timestampIso8601;

    return "soc2/audit-ledger/{$category}/{$year}/{$mon}/{$prefix}-{$safeTs}.json";
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Internal: upload to GCS (with retry)
  // ─────────────────────────────────────────────────────────────────────────

  /**
   * Serialize payload to JSON, SHA-256 hash the canonical body, then attempt
   * upload up to UPLOAD_MAX_ATTEMPTS times.  Returns the object_hash so the
   * caller can persist the chain tip after success.
   *
   * @param array<string, mixed> $payload
    * @return array{uploaded: bool, object_path: string, object_hash: string, http_code: int, error: string, error_code: string, error_detail: string, attempts: int}
   */
  private function uploadWithRetry(string $objectPath, array $payload): array
  {
    $body = (string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    // SHA-256 of the canonical JSON body — used as chain reference by the next artifact.
    $objectHash = hash('sha256', $body);

    try {
      $token = $this->getAccessToken();
    } catch (\Throwable $e) {
      return [
        'uploaded'    => false,
        'object_path' => $objectPath,
        'object_hash' => $objectHash,
        'http_code'   => 0,
        'error'       => 'token_error',
        'error_code'  => 'token_error',
        'error_detail' => $e->getMessage(),
        'attempts'    => 0,
      ];
    }

    // GCS JSON API: simple upload (object < 5 MB, no resumable needed)
    $encodedPath = rawurlencode($objectPath);
    $url = self::STORAGE_UPLOAD_BASE
         . rawurlencode($this->bucket)
         . '/o?uploadType=media&name='
         . $encodedPath;

    $headers = [
      'Authorization: Bearer ' . $token,
      'Content-Type: application/json',
      'Content-Length: ' . strlen($body),
    ];

    $lastResult = [
      'uploaded' => false,
      'http_code' => 0,
      'error' => 'no_attempts',
      'error_code' => 'no_attempts',
      'error_detail' => '',
    ];
    for ($attempt = 1; $attempt <= self::UPLOAD_MAX_ATTEMPTS; $attempt++) {
      if (function_exists('curl_init')) {
        $lastResult = $this->uploadViaCurl($url, $headers, $body, $objectPath);
      } else {
        $lastResult = $this->uploadViaStream($url, $headers, $body, $objectPath);
      }

      if ($lastResult['uploaded']) {
        return array_merge($lastResult, ['object_hash' => $objectHash, 'attempts' => $attempt]);
      }

      // Transient failure: wait briefly before retry (skip sleep on final attempt)
      // Backoff: 0.5 s × attempt, capped at 2 s (attempts 1–3: 0.5/1.0/1.5 s; 4+: 2 s)
      if ($attempt < self::UPLOAD_MAX_ATTEMPTS) {
        usleep(min(500_000 * $attempt, 2_000_000));
      }
    }

    return array_merge($lastResult, ['object_hash' => $objectHash, 'attempts' => self::UPLOAD_MAX_ATTEMPTS]);
  }

  /**
   * @param list<string> $headers
   * @return array{uploaded: bool, object_path: string, http_code: int, error: string, error_code: string, error_detail: string}
   */
  private function uploadViaCurl(string $url, array $headers, string $body, string $objectPath): array
  {
    $ch = curl_init($url);
    if ($ch === false) {
      return [
        'uploaded' => false,
        'object_path' => $objectPath,
        'http_code' => 0,
        'error' => 'curl_init_failed',
        'error_code' => 'curl_init_failed',
        'error_detail' => '',
      ];
    }

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);

    $response = curl_exec($ch);
    $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    if (PHP_VERSION_ID < 80500) {
      curl_close($ch);
    }

    $uploaded = ($response !== false && $httpCode >= 200 && $httpCode < 300);
    $errorCode = '';
    $errorDetail = '';

    if (!$uploaded) {
      if ($response === false) {
        $errorCode = 'network_error';
        $errorDetail = $curlError;
      } else {
        $errorCode = 'http_error';
        $errorDetail = (string) $response;
      }
    }

    return [
      'uploaded'    => $uploaded,
      'object_path' => $objectPath,
      'http_code'   => $httpCode,
      'error'       => $errorCode,
      'error_code'  => $errorCode,
      'error_detail' => $errorDetail,
    ];
  }

  /**
   * @param list<string> $headers
    * @return array{uploaded: bool, object_path: string, http_code: int, error: string, error_code: string, error_detail: string}
   */
  private function uploadViaStream(string $url, array $headers, string $body, string $objectPath): array
  {
    $context = stream_context_create([
      'http' => [
        'method'        => 'POST',
        'header'        => implode("\r\n", $headers),
        'content'       => $body,
        'timeout'       => $this->timeoutSeconds,
        'ignore_errors' => true,
      ],
      'ssl' => [
        'verify_peer' => true,
      ],
    ]);

    $response    = @file_get_contents($url, false, $context);
    $httpCode    = 0;
    $responseHeaders = [];

    if (function_exists('http_get_last_response_headers')) {
      $raw = http_get_last_response_headers();
      if (is_array($raw)) {
        $responseHeaders = $raw;
      }
    }

    if (isset($responseHeaders[0]) && is_string($responseHeaders[0])) {
      if (preg_match('/^HTTP\/\d+\.\d+\s+(\d{3})/', $responseHeaders[0], $m)) {
        $httpCode = (int) $m[1];
      }
    }

    $uploaded = ($response !== false && $httpCode >= 200 && $httpCode < 300);

    return [
      'uploaded'    => $uploaded,
      'object_path' => $objectPath,
      'http_code'   => $httpCode,
      'error'       => $uploaded ? '' : 'stream_upload_failed',
      'error_code'  => $uploaded ? '' : 'stream_upload_failed',
      'error_detail' => '',
    ];
  }

  // ─────────────────────────────────────────────────────────────────────────
  // Internal: OAuth2 token via service-account JWT assertion
  // ─────────────────────────────────────────────────────────────────────────

  /**
   * Return a valid access token, fetching a new one only when expired.
   *
   * @throws \RuntimeException on key-file or token-exchange failures
   */
  private function getAccessToken(): string
  {
    if ($this->accessToken !== '' && time() < $this->tokenExpiresAt - 60) {
      return $this->accessToken;
    }

    $key = $this->loadServiceAccountKey();

    $jwt   = $this->buildJwt($key);
    $token = $this->exchangeJwtForToken($jwt);

    $this->accessToken    = $token;
    $this->tokenExpiresAt = time() + self::TOKEN_TTL_SECONDS;

    return $this->accessToken;
  }

  /**
   * @return array<string, scalar>
   * @throws \RuntimeException
   */
  private function loadServiceAccountKey(): array
  {
    if ($this->serviceAccountKey !== null) {
      return $this->serviceAccountKey;
    }

    if (!is_readable($this->keyFilePath)) {
      throw new \RuntimeException('GCS service account key file not readable: ' . $this->keyFilePath);
    }

    $raw = file_get_contents($this->keyFilePath);
    if ($raw === false) {
      throw new \RuntimeException('Failed to read GCS service account key file.');
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
      throw new \RuntimeException('GCS service account key file is not valid JSON.');
    }

    foreach (['client_email', 'private_key'] as $required) {
      if (empty($decoded[$required])) {
        throw new \RuntimeException('GCS service account key file missing field: ' . $required);
      }
    }

    $this->serviceAccountKey = $decoded;

    return $this->serviceAccountKey;
  }

  /**
   * Build a signed RS256 JWT assertion for the Google token endpoint.
   *
   * @param array<string, scalar> $key
   * @throws \RuntimeException
   */
  private function buildJwt(array $key): string
  {
    $now = time();

    $header  = self::base64UrlEncode((string) json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claims  = self::base64UrlEncode((string) json_encode([
      'iss'   => (string) $key['client_email'],
      'scope' => self::SCOPE,
      'aud'   => self::TOKEN_ENDPOINT,
      'iat'   => $now,
      'exp'   => $now + self::TOKEN_TTL_SECONDS,
    ]));

    $signingInput = $header . '.' . $claims;

    $privateKey = openssl_pkey_get_private((string) $key['private_key']);
    if ($privateKey === false) {
      throw new \RuntimeException('Failed to load GCS private key from service account file.');
    }

    $signature = '';
    if (!openssl_sign($signingInput, $signature, $privateKey, 'SHA256')) {
      throw new \RuntimeException('Failed to sign GCS JWT assertion.');
    }

    return $signingInput . '.' . self::base64UrlEncode($signature);
  }

  /**
   * Exchange a signed JWT for a short-lived GCS access token.
   *
   * @throws \RuntimeException
   */
  private function exchangeJwtForToken(string $jwt): string
  {
    $body = http_build_query([
      'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
      'assertion'  => $jwt,
    ]);

    $headers = [
      'Content-Type: application/x-www-form-urlencoded',
      'Content-Length: ' . strlen($body),
    ];

    $responseBody = '';
    $httpCode     = 0;

    if (function_exists('curl_init')) {
      $ch = curl_init(self::TOKEN_ENDPOINT);
      if ($ch === false) {
        throw new \RuntimeException('curl_init failed for token endpoint.');
      }
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);
      $responseBody = (string) curl_exec($ch);
      $httpCode     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
      if (PHP_VERSION_ID < 80500) {
        curl_close($ch);
      }
    } else {
      $context      = stream_context_create([
        'http' => [
          'method'        => 'POST',
          'header'        => implode("\r\n", $headers),
          'content'       => $body,
          'timeout'       => $this->timeoutSeconds,
          'ignore_errors' => true,
        ],
      ]);
      $raw          = @file_get_contents(self::TOKEN_ENDPOINT, false, $context);
      $responseBody = $raw !== false ? $raw : '';
      $httpCode     = 200; // stream context doesn't expose code; validate via JSON below
    }

    if ($httpCode !== 0 && ($httpCode < 200 || $httpCode >= 300)) {
      throw new \RuntimeException('GCS token exchange returned HTTP ' . $httpCode . ': ' . $responseBody);
    }

    $decoded = json_decode($responseBody, true);
    if (!is_array($decoded) || empty($decoded['access_token'])) {
      throw new \RuntimeException('GCS token exchange response did not contain access_token: ' . $responseBody);
    }

    return (string) $decoded['access_token'];
  }

  /**
   * @param list<string> $headers
    * @return array{ok: bool, http_code: int, body: string, error: string, error_code: string, error_detail: string, decoded: mixed}
   */
  private function requestJson(string $method, string $url, array $headers, string $body = ''): array
  {
    $response = $this->requestRaw($method, $url, $headers, $body);
    $decoded = json_decode((string) ($response['body'] ?? ''), true);

    return [
      'ok' => (bool) ($response['ok'] ?? false),
      'http_code' => (int) ($response['http_code'] ?? 0),
      'body' => (string) ($response['body'] ?? ''),
      'error' => (string) ($response['error'] ?? ''),
      'error_code' => (string) ($response['error_code'] ?? ''),
      'error_detail' => (string) ($response['error_detail'] ?? ''),
      'decoded' => $decoded,
    ];
  }

  /**
   * @param list<string> $headers
   * @return array{ok: bool, http_code: int, body: string, error: string, error_code: string, error_detail: string, content_type: string}
   */
  private function requestRaw(string $method, string $url, array $headers, string $body = ''): array
  {
    $httpCode = 0;
    $responseBody = '';
    $errorCode = '';
    $errorDetail = '';
    $contentType = '';

    if (function_exists('curl_init')) {
      $ch = curl_init($url);
      if ($ch === false) {
        return [
          'ok' => false,
          'http_code' => 0,
          'body' => '',
          'error' => 'curl_init_failed',
          'error_code' => 'curl_init_failed',
          'error_detail' => '',
          'content_type' => '',
        ];
      }

      curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSeconds);

      if ($body !== '') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
      }

      $raw = curl_exec($ch);
      $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $contentType = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
      $curlError = curl_error($ch);
      if (PHP_VERSION_ID < 80500) {
        curl_close($ch);
      }

      if ($raw === false) {
        $errorCode = 'network_error';
        $errorDetail = $curlError;
      } else {
        $responseBody = (string) $raw;
      }
    } else {
      $context = stream_context_create([
        'http' => [
          'method' => $method,
          'header' => implode("\r\n", $headers),
          'content' => $body,
          'timeout' => $this->timeoutSeconds,
          'ignore_errors' => true,
        ],
        'ssl' => [
          'verify_peer' => true,
        ],
      ]);

      $raw = @file_get_contents($url, false, $context);
      if ($raw !== false) {
        $responseBody = $raw;
      }

      $responseHeaders = [];
      if (function_exists('http_get_last_response_headers')) {
        $rawHeaders = http_get_last_response_headers();
        if (is_array($rawHeaders)) {
          $responseHeaders = $rawHeaders;
        }
      }

      if (isset($responseHeaders[0]) && is_string($responseHeaders[0])) {
        if (preg_match('/^HTTP\/\d+\.\d+\s+(\d{3})/', $responseHeaders[0], $m)) {
          $httpCode = (int) $m[1];
        }
      }

      foreach ($responseHeaders as $headerLine) {
        if (!is_string($headerLine)) {
          continue;
        }

        if (stripos($headerLine, 'Content-Type:') === 0) {
          $contentType = trim(substr($headerLine, strlen('Content-Type:')));
          break;
        }
      }
    }

    $ok = $errorCode === '' && $httpCode >= 200 && $httpCode < 300;

    if (!$ok && $errorCode === '' && $responseBody === '') {
      $errorCode = 'empty_response';
    }

    if (!$ok && $errorCode === '' && $responseBody !== '') {
      $errorCode = 'http_error';
      $errorDetail = $responseBody;
    }

    return [
      'ok' => $ok,
      'http_code' => $httpCode,
      'body' => $responseBody,
      'error' => $errorCode,
      'error_code' => $errorCode,
      'error_detail' => $errorDetail,
      'content_type' => $contentType,
    ];
  }

  private static function base64UrlEncode(string $input): string
  {
    return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
  }
}
