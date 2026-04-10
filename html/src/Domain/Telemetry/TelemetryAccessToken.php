<?php declare(strict_types=1);

namespace PayCal\Domain\Telemetry;

use PayCal\Domain\TelemetryPolicy;

/**
 * Class TelemetryAccessToken
 *
 * Immutable telemetry authorization context describing a caller's stream,
 * role, retention scope, and aggregation level.
 */
final readonly class TelemetryAccessToken
{
  /**
   * Initializes a telemetry access token.
   */
  public function __construct(
    private string $stream,
    private string $role,
    private string $retentionScope,
    private string $aggregationLevel
  ) {
  }

  /** Returns the normalized telemetry stream name. */
  public function stream(): string
  {
    return strtolower(trim($this->stream));
  }

  /** Returns the normalized telemetry role name. */
  public function role(): string
  {
    return strtolower(trim($this->role));
  }

  /** Returns the normalized retention scope. */
  public function retentionScope(): string
  {
    return strtolower(trim($this->retentionScope));
  }

  /** Returns the normalized aggregation level. */
  public function aggregationLevel(): string
  {
    return strtolower(trim($this->aggregationLevel));
  }

  /** Checks whether this token allows access to a target stream. */
  public function allowsStream(string $stream): bool
  {
    return TelemetryPolicy::canAccess($stream, $this->role());
  }
}
