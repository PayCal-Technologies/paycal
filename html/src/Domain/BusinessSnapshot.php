<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Versioned org metadata cache: roster, roles, and fast counters.
 */
final class BusinessSnapshot
{
  public const SCHEMA_VERSION = 1;

  /**
   * @param list<array<string, mixed>> $relationships
   * @param list<array<string, mixed>> $members
   */
  public function __construct(
    public readonly string $snapshot_version,
    public readonly string $business_id,
    public readonly int $member_count,
    public readonly int $site_count,
    public readonly array $relationships,
    public readonly array $members,
    public readonly ?int $pending_invites = null,
    public readonly ?int $pending_requests = null,
    public readonly string $generated_at = '',
  ) {
  }

  /**
   * @return array<string, mixed>
   */
  public function toArray(): array
  {
    return [
      'schema' => self::SCHEMA_VERSION,
      'snapshot_version' => $this->snapshot_version,
      'business_id' => $this->business_id,
      'member_count' => $this->member_count,
      'site_count' => $this->site_count,
      'relationships' => $this->relationships,
      'members' => $this->members,
      'pending_invites' => $this->pending_invites,
      'pending_requests' => $this->pending_requests,
      'generated_at' => $this->generated_at !== '' ? $this->generated_at : date('c'),
    ];
  }

  /**
   * @param array<string, mixed> $data
   */
  public static function fromArray(array $data): ?self
  {
    $schema = $data['schema'] ?? null;
    if (!is_scalar($schema) || (int) $schema !== self::SCHEMA_VERSION) {
      return null;
    }

    $businessId = trim(is_scalar($data['business_id'] ?? null) ? (string) $data['business_id'] : '');
    $version = trim(is_scalar($data['snapshot_version'] ?? null) ? (string) $data['snapshot_version'] : '');
    if ($version === '' && is_scalar($data['manifest_version'] ?? null)) {
      $version = trim((string) $data['manifest_version']);
    }
    if ($businessId === '' || $version === '') {
      return null;
    }

    $relationshipsRaw = $data['relationships'] ?? [];
    $membersRaw = $data['members'] ?? [];
    if (!is_array($relationshipsRaw) || !is_array($membersRaw)) {
      return null;
    }

    /** @var list<array<string, mixed>> $relationships */
    $relationships = array_values(array_filter($relationshipsRaw, 'is_array'));
    /** @var list<array<string, mixed>> $members */
    $members = array_values(array_filter($membersRaw, 'is_array'));

    $pendingInvites = $data['pending_invites'] ?? null;
    $pendingRequests = $data['pending_requests'] ?? null;

    return new self(
      snapshot_version: $version,
      business_id: $businessId,
      member_count: max(0, self::intField($data, 'member_count', count($members))),
      site_count: max(0, self::intField($data, 'site_count', 0)),
      relationships: $relationships,
      members: $members,
      pending_invites: is_scalar($pendingInvites) ? max(0, (int) $pendingInvites) : null,
      pending_requests: is_scalar($pendingRequests) ? max(0, (int) $pendingRequests) : null,
      generated_at: trim(is_scalar($data['generated_at'] ?? null) ? (string) $data['generated_at'] : ''),
    );
  }

  /**
   * @param array<string, mixed> $data
   */
  private static function intField(array $data, string $key, int $default): int
  {
    $value = $data[$key] ?? null;

    return is_numeric($value) ? (int) $value : $default;
  }
}
