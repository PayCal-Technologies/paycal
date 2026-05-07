<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * SystemAuditBlockchainGateway
 *
 * Purpose: Abstraction for publishing immutable audit ledger anchors to an
 * external blockchain-compatible transport.
 */
interface SystemAuditBlockchainGateway
{
  /**
   * Publish an anchor payload and return publication metadata.
   *
   * @return array<string, scalar>
   */
  public function publish(string $anchorId, string $blockHash, int $sequence): array;

  /**
   * Human-readable provider identifier.
   */
  public function provider(): string;
}
