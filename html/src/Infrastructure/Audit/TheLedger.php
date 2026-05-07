<?php declare(strict_types=1);

namespace PayCal\Infrastructure\Audit;

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\SystemAuditBlockchainGateway;
use PayCal\Infrastructure\Queue\SystemAuditBlockchainQueueGateway;

/**
 * TheLedger
 *
 * Purpose: Maintain an append-only, cryptographically chained ledger for
 * system audit events and periodically anchor chain heads to a blockchain
 * publication gateway.
 *
 * Inspiration: The Watcher from the Marvel Cinematic Universe.
 * Modeled attributes: impartial observation, continuity guardianship,
 * and immutable record preservation across event timelines.
 */
final class TheLedger
{
  public const HASH_ALGORITHM = 'sha3-512';
  private const SCHEMA_VERSION = 'v1';
  private const GENESIS_PREVIOUS_HASH = 'GENESIS';
  private const ANCHOR_EVERY_BLOCKS = 128;

  /**
   * Append an event to the immutable chain.
   *
   * @param array<string, scalar> $details
   * @return array<string, scalar>
   */
  public static function append(
    string $eventId,
    string $eventType,
    string $actorUUID,
    array $details,
    string $createdAt
  ): array {
    $sequence = Database::incr(Keys::systemAuditLedgerSequenceCounter());
    $previousHash = self::previousHashForSequence($sequence);

    $normalizedDetails = self::normalizeArray($details);
    $eventMaterial = [
      'event_id' => $eventId,
      'event_type' => $eventType,
      'actor_uuid' => $actorUUID,
      'created_at' => $createdAt,
      'details' => $normalizedDetails,
    ];

    $eventJson = self::canonicalJson($eventMaterial);
    $eventHash = hash(self::HASH_ALGORITHM, $eventJson);
    $blockHash = hash(
      self::HASH_ALGORITHM,
      self::SCHEMA_VERSION . '|' . (string) $sequence . '|' . $previousHash . '|' . $eventHash
    );

    $block = [
      'schema_version' => self::SCHEMA_VERSION,
      'sequence' => (string) $sequence,
      'event_id' => $eventId,
      'event_type' => $eventType,
      'actor_uuid' => $actorUUID,
      'created_at' => $createdAt,
      'details' => self::canonicalJson($normalizedDetails),
      'event_hash' => $eventHash,
      'previous_hash' => $previousHash,
      'block_hash' => $blockHash,
      'hash_algorithm' => self::HASH_ALGORITHM,
      'anchor_status' => 'not_required',
    ];

    $anchorDecision = self::anchorDecisionForSequence($sequence, $blockHash);
    $block['anchor_status'] = (string) $anchorDecision['status'];
    $block['anchor_provider'] = (string) $anchorDecision['provider'];
    $block['anchor_reference'] = (string) $anchorDecision['reference'];

    $blockKey = Keys::systemAuditLedgerBlock($sequence);
    Database::hset($blockKey, $block);
    Database::set(Keys::systemAuditLedgerEventSequence($eventId), (string) $sequence);
    Database::set(Keys::systemAuditLedgerHeadSequence(), (string) $sequence);
    Database::set(Keys::systemAuditLedgerHeadHash(), $blockHash);
    Database::lpush(Keys::systemAuditLedgerOrder(), (string) $sequence);

    return [
      'sequence' => $sequence,
      'block_hash' => $blockHash,
      'previous_hash' => $previousHash,
      'anchor_status' => $block['anchor_status'],
      'anchor_provider' => $block['anchor_provider'],
      'anchor_reference' => $block['anchor_reference'],
    ];
  }

  /**
   * Verify the complete immutable chain from genesis through the current head.
   *
   * @return array<string, scalar>
   */
  public static function verify(): array
  {
    $headSequence = (int) Database::get(Keys::systemAuditLedgerHeadSequence());
    if ($headSequence <= 0) {
      return [
        'ok' => true,
        'checked_blocks' => 0,
        'head_sequence' => 0,
        'head_hash' => '',
        'reason' => 'empty_chain',
      ];
    }

    $expectedPreviousHash = self::GENESIS_PREVIOUS_HASH;

    for ($sequence = 1; $sequence <= $headSequence; $sequence++) {
      $block = Database::hgetall(Keys::systemAuditLedgerBlock($sequence));
      if ([] === $block) {
        return [
          'ok' => false,
          'checked_blocks' => $sequence - 1,
          'head_sequence' => $headSequence,
          'head_hash' => Database::get(Keys::systemAuditLedgerHeadHash()),
          'first_invalid_sequence' => $sequence,
          'reason' => 'missing_block',
        ];
      }

      if ((string) ($block['previous_hash'] ?? '') !== $expectedPreviousHash) {
        return [
          'ok' => false,
          'checked_blocks' => $sequence - 1,
          'head_sequence' => $headSequence,
          'head_hash' => Database::get(Keys::systemAuditLedgerHeadHash()),
          'first_invalid_sequence' => $sequence,
          'reason' => 'previous_hash_mismatch',
        ];
      }

      $detailsJson = (string) ($block['details'] ?? '{}');
      $decodedDetails = json_decode($detailsJson, true);
      if (!is_array($decodedDetails)) {
        return [
          'ok' => false,
          'checked_blocks' => $sequence - 1,
          'head_sequence' => $headSequence,
          'head_hash' => Database::get(Keys::systemAuditLedgerHeadHash()),
          'first_invalid_sequence' => $sequence,
          'reason' => 'invalid_details_json',
        ];
      }

      $eventMaterial = [
        'event_id' => (string) ($block['event_id'] ?? ''),
        'event_type' => (string) ($block['event_type'] ?? ''),
        'actor_uuid' => (string) ($block['actor_uuid'] ?? ''),
        'created_at' => (string) ($block['created_at'] ?? ''),
        'details' => self::normalizeArray($decodedDetails),
      ];

      $recomputedEventHash = hash(self::HASH_ALGORITHM, self::canonicalJson($eventMaterial));
      if (!hash_equals((string) ($block['event_hash'] ?? ''), $recomputedEventHash)) {
        return [
          'ok' => false,
          'checked_blocks' => $sequence - 1,
          'head_sequence' => $headSequence,
          'head_hash' => Database::get(Keys::systemAuditLedgerHeadHash()),
          'first_invalid_sequence' => $sequence,
          'reason' => 'event_hash_mismatch',
        ];
      }

      $recomputedBlockHash = hash(
        self::HASH_ALGORITHM,
        ((string) ($block['schema_version'] ?? self::SCHEMA_VERSION)) . '|' . (string) $sequence . '|' . $expectedPreviousHash . '|' . $recomputedEventHash
      );

      if (!hash_equals((string) ($block['block_hash'] ?? ''), $recomputedBlockHash)) {
        return [
          'ok' => false,
          'checked_blocks' => $sequence - 1,
          'head_sequence' => $headSequence,
          'head_hash' => Database::get(Keys::systemAuditLedgerHeadHash()),
          'first_invalid_sequence' => $sequence,
          'reason' => 'block_hash_mismatch',
        ];
      }

      $expectedPreviousHash = $recomputedBlockHash;
    }

    $headHash = Database::get(Keys::systemAuditLedgerHeadHash());

    return [
      'ok' => hash_equals($expectedPreviousHash, $headHash),
      'checked_blocks' => $headSequence,
      'head_sequence' => $headSequence,
      'head_hash' => $headHash,
      'reason' => hash_equals($expectedPreviousHash, $headHash) ? 'verified' : 'head_hash_mismatch',
    ];
  }

  /**
   * Resolve immutable proof data for one system audit event ID.
   *
   * @return array<string, scalar>
   */
  public static function proofForEvent(string $eventId): array
  {
    $sequence = (int) Database::get(Keys::systemAuditLedgerEventSequence($eventId));
    if ($sequence <= 0) {
      return [
        'has_proof' => false,
      ];
    }

    $block = Database::hgetall(Keys::systemAuditLedgerBlock($sequence));
    if ([] === $block) {
      return [
        'has_proof' => false,
      ];
    }

    return [
      'has_proof' => true,
      'sequence' => $sequence,
      'block_hash' => (string) ($block['block_hash'] ?? ''),
      'previous_hash' => (string) ($block['previous_hash'] ?? ''),
      'anchor_status' => (string) ($block['anchor_status'] ?? 'unknown'),
      'anchor_provider' => (string) ($block['anchor_provider'] ?? 'none'),
      'anchor_reference' => (string) ($block['anchor_reference'] ?? ''),
    ];
  }

  /**
   * @param array<array-key, mixed> $details
   * @return array<string, string>
   */
  private static function normalizeArray(array $details): array
  {
    $normalized = [];
    foreach ($details as $key => $value) {
      $normalizedKey = (string) $key;
      $normalizedValue = is_scalar($value) || $value === null
        ? (string) $value
        : (json_encode($value, JSON_UNESCAPED_SLASHES) ?: '');
      $normalized[$normalizedKey] = $normalizedValue;
    }

    ksort($normalized, SORT_STRING);

    return $normalized;
  }

  /**
   * @param array<mixed, mixed> $payload
   */
  private static function canonicalJson(array $payload): string
  {
    ksort($payload, SORT_STRING);

    return json_encode($payload, JSON_UNESCAPED_SLASHES) ?: '{}';
  }

  private static function previousHashForSequence(int $sequence): string
  {
    if ($sequence <= 1) {
      return self::GENESIS_PREVIOUS_HASH;
    }

    $previous = Database::hget(Keys::systemAuditLedgerBlock($sequence - 1), 'block_hash');

    return $previous !== '' ? $previous : self::GENESIS_PREVIOUS_HASH;
  }

  /**
   * @return array{status: string, provider: string, reference: string}
   */
  private static function anchorDecisionForSequence(int $sequence, string $blockHash): array
  {
    if (($sequence % self::ANCHOR_EVERY_BLOCKS) !== 0) {
      return [
        'status' => 'not_required',
        'provider' => 'none',
        'reference' => '',
      ];
    }

    $anchorId = 'SAA' . substr(hash('sha256', (string) $sequence . '|' . $blockHash . '|' . microtime(true)), 0, 24);

    $gateway = self::resolveBlockchainGateway();
    $publication = $gateway->publish($anchorId, $blockHash, $sequence);

    $anchorRecord = [
      'anchor_id' => $anchorId,
      'sequence' => (string) $sequence,
      'block_hash' => $blockHash,
      'provider' => (string) ($publication['provider'] ?? $gateway->provider()),
      'status' => (string) ($publication['status'] ?? 'queued'),
      'reference' => (string) ($publication['anchor_payload_hash'] ?? ''),
      'created_at' => date('c'),
      'published_at' => (string) ($publication['queued_at'] ?? date('c')),
      'hash_algorithm' => self::HASH_ALGORITHM,
    ];

    Database::hset(Keys::systemAuditAnchor($anchorId), $anchorRecord);
    Database::sadd(Keys::systemAuditAnchorIndex(), $anchorId);

    return [
      'status' => $anchorRecord['status'],
      'provider' => $anchorRecord['provider'],
      'reference' => $anchorRecord['reference'],
    ];
  }

  private static function resolveBlockchainGateway(): SystemAuditBlockchainGateway
  {
    $mode = strtolower(trim((string) (getenv('SYSTEM_AUDIT_BLOCKCHAIN_MODE') ?: 'queue')));

    if ($mode !== 'disabled' && $mode !== 'none' && $mode !== 'off') {
      return new SystemAuditBlockchainQueueGateway();
    }

    return new class implements SystemAuditBlockchainGateway {
      public function publish(string $anchorId, string $blockHash, int $sequence): array
      {
        return [
          'status' => 'disabled',
          'provider' => $this->provider(),
          'queued_at' => date('c'),
          'anchor_payload_hash' => hash('sha3-512', $anchorId . '|' . $blockHash . '|' . (string) $sequence),
        ];
      }

      public function provider(): string
      {
        return 'disabled';
      }
    };
  }
}
