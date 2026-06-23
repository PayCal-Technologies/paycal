<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * Canonical structured diagnostic event emitted by Argus.
 */
final class DiagnosticEvent
{
  private const NAME_PATTERN = '/^[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*){1,7}$/';

  /**
   * @param array<string, scalar|null> $context
   */
  public function __construct(
    public readonly string $name,
    public readonly string $module,
    public readonly DiagnosticSeverity $severity,
    public readonly array $context,
    public readonly string $timestamp,
    public readonly string $correlationId = '',
    public readonly string $traceId = '',
    public readonly string $spanId = '',
  ) {
  }

  /**
   * @param array<string, scalar|null> $context
   */
  public static function create(
    string $name,
    DiagnosticSeverity $severity,
    array $context = [],
    string $correlationId = '',
    string $traceId = '',
    string $spanId = '',
  ): ?self {
    $normalizedName = strtolower(trim($name));
    if ($normalizedName === '' || 1 !== preg_match(self::NAME_PATTERN, $normalizedName)) {
      return null;
    }

    $segments = explode('.', $normalizedName);
    $module = $segments[0];
    if ($module === '') {
      return null;
    }

    return new self(
      $normalizedName,
      $module,
      $severity,
      $context,
      self::nowIso8601(),
      $correlationId,
      $traceId,
      $spanId,
    );
  }

  /**
   * @return array<string, mixed>
   */
  public function toArray(): array
  {
    $payload = [
      'name' => $this->name,
      'module' => $this->module,
      'severity' => $this->severity->value,
      'context' => $this->context,
      'timestamp' => $this->timestamp,
      'correlation_id' => $this->correlationId,
    ];

    if ($this->traceId !== '') {
      $payload['trace_id'] = $this->traceId;
    }
    if ($this->spanId !== '') {
      $payload['span_id'] = $this->spanId;
    }

    return $payload;
  }

  /**
   * Return a microsecond-precision UTC timestamp for event payloads.
   */
  private static function nowIso8601(): string
  {
    $dt = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', microtime(true)));

    return $dt instanceof \DateTimeImmutable
      ? $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s.u\Z')
      : gmdate('Y-m-d\TH:i:s\Z');
  }
}
