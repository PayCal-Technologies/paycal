<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Queue;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\SystemAuditBlockchainGateway;
use PayCal\Infrastructure\Audit\TheLedger;

/**
 * SystemAuditBlockchainQueueGateway
 *
 * Purpose: Queue blockchain anchor jobs for asynchronous submission by an
 * external relayer process. This keeps app writes low-latency while preserving
 * immutable publication intent.
 */
final class SystemAuditBlockchainQueueGateway implements SystemAuditBlockchainGateway
{
  /**
   * Handles provider operation.
   */
  public function provider(): string
  {
    return 'queue-v1';
  }

  /**
   * Handles publish operation.
   *
   * @return array<string, scalar>
   */
  public function publish(string $anchorId, string $blockHash, int $sequence): array
  {
    $queuedAt = date('c');

    $payload = [
      'anchor_id' => $anchorId,
      'sequence' => $sequence,
      'block_hash' => $blockHash,
      'hash_algorithm' => TheLedger::HASH_ALGORITHM,
      'queued_at' => $queuedAt,
      'target_networks' => ['bitcoin', 'ethereum'],
      'minimum_confirmations' => [
        'bitcoin' => 6,
        'ethereum' => 64,
      ],
    ];

    Database::lpush(
      Keys::systemAuditBlockchainAnchorQueue(),
      json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}'
    );

    return [
      'status' => 'queued',
      'provider' => $this->provider(),
      'queued_at' => $queuedAt,
      'queue_key' => Keys::systemAuditBlockchainAnchorQueue(),
      'anchor_payload_hash' => hash('sha3-512', json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}'),
    ];
  }
}
