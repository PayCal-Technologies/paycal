<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * EmailChangeTransaction.php
 *
 * Purpose: Manage email-change transaction lifecycle in Redis.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Handle email-change transaction state in Redis.
 * 
 * Key format: email_change:txn:{txn_id}
 * Fields: user_uuid, old_email, new_email, old_code_hash, new_code_hash,
 *         old_verified, new_verified, expires_at, created_at, last_sent_at,
 *         verify_attempts, resend_count, status
 */
final class EmailChangeTransaction
{
    private string $txnId;
    private string $userUuid;
    private string $oldEmail;
    private string $newEmail;
    private string $oldCodeHash;
    private string $newCodeHash;
    private bool $oldVerified = false;
    private bool $newVerified = false;
    private int $expiresAt;
    private int $createdAt;
    private int $lastSentAt;
    private int $verifyAttempts = 0;
    private int $resendCount = 0;
    private string $status = 'pending'; // pending, verified, committed, cancelled, expired

    /**
     * Create a new email-change transaction.
     */
    public static function create(
        string $userUuid,
        string $oldEmail,
        string $newEmail,
        int $codeTtlMinutes
    ): self {
        $txnId = bin2hex(random_bytes(16));
        $now = time();
        $expiresAt = $now + ($codeTtlMinutes * 60);

        $txn = new self();
        $txn->txnId = $txnId;
        $txn->userUuid = $userUuid;
        $txn->oldEmail = $oldEmail;
        $txn->newEmail = $newEmail;
        $txn->oldCodeHash = '';  // To be set later
        $txn->newCodeHash = '';  // To be set later
        $txn->expiresAt = $expiresAt;
        $txn->createdAt = $now;
        $txn->lastSentAt = $now;
        $txn->status = 'pending';

        return $txn;
    }

    /**
     * Load a transaction from Redis.
     */
    public static function load(string $txnId): ?self {
        $key = Keys::emailChangeTransaction($txnId);
        $data = Database::hgetall($key);

        if (empty($data)) {
            return null;
        }

        $txn = new self();
        $txn->txnId = $txnId;
        $txn->userUuid = (string) ($data['user_uuid'] ?? '');
        $txn->oldEmail = (string) ($data['old_email'] ?? '');
        $txn->newEmail = (string) ($data['new_email'] ?? '');
        $txn->oldCodeHash = (string) ($data['old_code_hash'] ?? '');
        $txn->newCodeHash = (string) ($data['new_code_hash'] ?? '');
        $txn->oldVerified = (bool) ((int) ($data['old_verified'] ?? '0'));
        $txn->newVerified = (bool) ((int) ($data['new_verified'] ?? '0'));
        $txn->expiresAt = (int) ($data['expires_at'] ?? 0);
        $txn->createdAt = (int) ($data['created_at'] ?? 0);
        $txn->lastSentAt = (int) ($data['last_sent_at'] ?? 0);
        $txn->verifyAttempts = (int) ($data['verify_attempts'] ?? '0');
        $txn->resendCount = (int) ($data['resend_count'] ?? '0');
        $txn->status = (string) ($data['status'] ?? 'pending');

        return $txn;
    }

    /**
     * Persist transaction to Redis.
     */
    public function save(): void {
        $key = Keys::emailChangeTransaction($this->txnId);
        Database::hset($key, [
            'user_uuid' => $this->userUuid,
            'old_email' => $this->oldEmail,
            'new_email' => $this->newEmail,
            'old_code_hash' => $this->oldCodeHash,
            'new_code_hash' => $this->newCodeHash,
            'old_verified' => $this->oldVerified ? '1' : '0',
            'new_verified' => $this->newVerified ? '1' : '0',
            'expires_at' => (string) $this->expiresAt,
            'created_at' => (string) $this->createdAt,
            'last_sent_at' => (string) $this->lastSentAt,
            'verify_attempts' => (string) $this->verifyAttempts,
            'resend_count' => (string) $this->resendCount,
            'status' => $this->status,
        ]);

        // Set TTL to expiry time
        $ttl = max(0, $this->expiresAt - time());
        if ($ttl > 0) {
            Database::expire($key, $ttl + 600); // Add 10m buffer
        }
    }

    /**
     * Delete transaction from Redis.
     */
    public function delete(): void {
        $key = Keys::emailChangeTransaction($this->txnId);
        Database::del($key);
    }

    /**
     * Check if transaction has expired.
     */
    public function isExpired(): bool {
        return time() > $this->expiresAt;
    }

    /**
     * Check if both codes have been verified.
     */
    public function bothCodesVerified(): bool {
        return $this->oldVerified && $this->newVerified;
    }

    // Getters & setters
    /**
     * Handles getTxnId operation.
     */
    public function getTxnId(): string { return $this->txnId; }
    /**
     * Handles getUserUuid operation.
     */
    public function getUserUuid(): string { return $this->userUuid; }
    /**
     * Handles getOldEmail operation.
     */
    public function getOldEmail(): string { return $this->oldEmail; }
    /**
     * Handles getNewEmail operation.
     */
    public function getNewEmail(): string { return $this->newEmail; }
    /**
     * Handles getOldCodeHash operation.
     */
    public function getOldCodeHash(): string { return $this->oldCodeHash; }
    /**
     * Handles setOldCodeHash operation.
     */
    public function setOldCodeHash(string $hash): void { $this->oldCodeHash = $hash; }
    /**
     * Handles getNewCodeHash operation.
     */
    public function getNewCodeHash(): string { return $this->newCodeHash; }
    /**
     * Handles setNewCodeHash operation.
     */
    public function setNewCodeHash(string $hash): void { $this->newCodeHash = $hash; }
    /**
     * Handles isOldVerified operation.
     */
    public function isOldVerified(): bool { return $this->oldVerified; }
    /**
     * Handles setOldVerified operation.
     */
    public function setOldVerified(bool $verified): void { $this->oldVerified = $verified; }
    /**
     * Handles isNewVerified operation.
     */
    public function isNewVerified(): bool { return $this->newVerified; }
    /**
     * Handles setNewVerified operation.
     */
    public function setNewVerified(bool $verified): void { $this->newVerified = $verified; }
    /**
     * Handles getExpiresAt operation.
     */
    public function getExpiresAt(): int { return $this->expiresAt; }
    /**
     * Handles getCreatedAt operation.
     */
    public function getCreatedAt(): int { return $this->createdAt; }
    /**
     * Handles getLastSentAt operation.
     */
    public function getLastSentAt(): int { return $this->lastSentAt; }
    /**
     * Handles setLastSentAt operation.
     */
    public function setLastSentAt(int $timestamp): void { $this->lastSentAt = $timestamp; }
    /**
     * Handles getVerifyAttempts operation.
     */
    public function getVerifyAttempts(): int { return $this->verifyAttempts; }
    /**
     * Handles incrementVerifyAttempts operation.
     */
    public function incrementVerifyAttempts(): void { $this->verifyAttempts++; }
    /**
     * Handles getResendCount operation.
     */
    public function getResendCount(): int { return $this->resendCount; }
    /**
     * Handles incrementResendCount operation.
     */
    public function incrementResendCount(): void { $this->resendCount++; }
    /**
     * Handles getStatus operation.
     */
    public function getStatus(): string { return $this->status; }
    /**
     * Handles setStatus operation.
     */
    public function setStatus(string $status): void { $this->status = $status; }
}

