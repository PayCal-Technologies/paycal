<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;

/**
 * Class AccountRecoveryTransaction
 *
 * Represents a single account-recovery session stored in Redis.  Tracks the
 * progressive states (pending → email-verified → proof-verified → bootstrap-issued →
 * completed/cancelled) and enforces proof-nonce TTLs, replay protection, and
 * single-use bootstrap token semantics.
 */
final class AccountRecoveryTransaction
{
  public const STATUS_PENDING = 'pending';
  public const STATUS_EMAIL_VERIFIED = 'email-verified';
  public const STATUS_PROOF_VERIFIED = 'proof-verified';
  public const STATUS_BOOTSTRAP_ISSUED = 'bootstrap-issued';
  public const STATUS_COMPLETED = 'completed';
  public const STATUS_CANCELLED = 'cancelled';

  private const ACTIVE_STATES = [
    self::STATUS_PENDING,
    self::STATUS_EMAIL_VERIFIED,
    self::STATUS_PROOF_VERIFIED,
    self::STATUS_BOOTSTRAP_ISSUED,
  ];

  /** @param array<string, string> $data */
  private function __construct(private readonly string $txnId, private array $data)
  {
  }

  /**
   * @return array{transaction: self, txnSecret: string, superseded: bool}
   */
  public static function create(string $email, string $userUuid = '', string $fullName = ''): array
  {
    $txnId = bin2hex(random_bytes(16));
    $txnSecret = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    $ttlSeconds = max(600, (int) SystemConfig::get('account_recovery_txn_ttl_minutes') * 60);
    $superseded = false;

    if ($userUuid !== '') {
      $activeKey = Keys::accountRecoveryActiveTransaction($userUuid);
      $existingTxnId = Database::get($activeKey);
      if ($existingTxnId !== '') {
        $existing = self::load($existingTxnId);
        if ($existing !== null && $existing->isActive()) {
          $existing->cancel();
          $superseded = true;
        }
      }
      Database::set($activeKey, $txnId, $ttlSeconds);
    }

    $now = time();
    $record = [
      'txn_id' => $txnId,
      'txn_secret_hash' => hash('sha256', $txnSecret),
      'user_uuid' => $userUuid,
      'email' => $email,
      'full_name' => $fullName,
      'email_code_hash' => '',
      'email_code_expires_at' => '0',
      'txn_client_fingerprint_hash' => '',
      'txn_ip_class' => '',
      'proof_nonce_hash' => '',
      'proof_nonce_expires_at' => '0',
      'bootstrap_expires_at' => '0',
      'replacement_credential_id' => '',
      'verify_attempts' => '0',
      'resend_count' => '0',
      'expires_at' => (string) ($now + $ttlSeconds),
      'status' => self::STATUS_PENDING,
    ];

    Database::hset(Keys::accountRecoveryTransaction($txnId), $record);
    Database::expire(Keys::accountRecoveryTransaction($txnId), $ttlSeconds);

    return [
      'transaction' => new self($txnId, $record),
      'txnSecret' => $txnSecret,
      'superseded' => $superseded,
    ];
  }

  /**
   * Handles load operation.
   */
  public static function load(string $txnId): ?self
  {
    if ($txnId === '') {
      return null;
    }

    $record = Database::hgetall(Keys::accountRecoveryTransaction($txnId));
    if ($record === []) {
      return null;
    }

    return new self($txnId, $record);
  }

  /**
   * Handles id operation.
   */
  public function id(): string
  {
    return $this->txnId;
  }

  /**
   * Handles userUuid operation.
   */
  public function userUuid(): string
  {
    return (string) ($this->data['user_uuid'] ?? '');
  }

  /**
   * Handles verifySecret operation.
   */
  public function verifySecret(string $txnSecret): bool
  {
    return $txnSecret !== '' && hash_equals((string) ($this->data['txn_secret_hash'] ?? ''), hash('sha256', $txnSecret));
  }

  /**
   * Handles status operation.
   */
  public function status(): string
  {
    return (string) ($this->data['status'] ?? '');
  }

  /**
   * Handles isActive operation.
   */
  public function isActive(): bool
  {
    return in_array($this->status(), self::ACTIVE_STATES, true) && !$this->isExpired();
  }

  /**
   * Handles isExpired operation.
   */
  public function isExpired(): bool
  {
    return time() > (int) ($this->data['expires_at'] ?? '0');
  }

  /**
   * Handles storeEmailCode operation.
   */
  public function storeEmailCode(string $code): void
  {
    $expiresAt = time() + max(300, (int) SystemConfig::get('account_recovery_code_ttl_minutes') * 60);
    $this->write([
      'email_code_hash' => hash('sha256', strtoupper(trim($code))),
      'email_code_expires_at' => (string) $expiresAt,
      'resend_count' => (string) (((int) ($this->data['resend_count'] ?? '0')) + 1),
    ]);
  }

  /**
   * Handles canResend operation.
   */
  public function canResend(): bool
  {
    return $this->status() === self::STATUS_PENDING && !$this->isExpired();
  }

  /**
   * Handles verifyEmailCode operation.
   */
  public function verifyEmailCode(string $code, string $fingerprintHash, string $ipClass): bool
  {
    $valid = $this->status() === self::STATUS_PENDING
      && !$this->isExpired()
      && hash_equals((string) ($this->data['email_code_hash'] ?? ''), hash('sha256', strtoupper(trim($code))))
      && time() <= (int) ($this->data['email_code_expires_at'] ?? '0');

    if (!$valid) {
      $this->write(['verify_attempts' => (string) (((int) ($this->data['verify_attempts'] ?? '0')) + 1)]);
      return false;
    }

    $this->write([
      'txn_client_fingerprint_hash' => $fingerprintHash,
      'txn_ip_class' => $ipClass,
      'status' => self::STATUS_EMAIL_VERIFIED,
      'verify_attempts' => '0',
    ]);

    return true;
  }

  /**
   * @return array{proofNonce: string, expiresAt: int}|null
   */
  public function issueProofNonce(string $fingerprintHash, string $ipClass): ?array
  {
    if ($this->status() !== self::STATUS_EMAIL_VERIFIED || !$this->matchesClientBinding($fingerprintHash, $ipClass)) {
      return null;
    }

    $proofNonce = rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    $expiresAt = time() + max(30, (int) SystemConfig::get('account_recovery_proof_nonce_ttl_seconds'));
    $this->write([
      'proof_nonce_hash' => hash('sha256', $proofNonce),
      'proof_nonce_expires_at' => (string) $expiresAt,
    ]);

    return ['proofNonce' => $proofNonce, 'expiresAt' => $expiresAt];
  }

  /**
   * Handles verifyProof operation.
   */
  public function verifyProof(string $proof, string $proofNonce, string $expectedProof, string $fingerprintHash, string $ipClass): bool
  {
    $valid = $this->status() === self::STATUS_EMAIL_VERIFIED
      && !$this->isExpired()
      && $this->matchesClientBinding($fingerprintHash, $ipClass)
      && hash_equals((string) ($this->data['proof_nonce_hash'] ?? ''), hash('sha256', $proofNonce))
      && time() <= (int) ($this->data['proof_nonce_expires_at'] ?? '0')
      && hash_equals($expectedProof, $proof);

    $updates = [
      'proof_nonce_hash' => '',
      'proof_nonce_expires_at' => '0',
    ];

    if ($valid) {
      $updates['status'] = self::STATUS_PROOF_VERIFIED;
    } else {
      $updates['verify_attempts'] = (string) (((int) ($this->data['verify_attempts'] ?? '0')) + 1);
    }

    $this->write($updates);
    return $valid;
  }

  /**
   * Handles issueBootstrap operation.
   */
  public function issueBootstrap(): bool
  {
    if ($this->status() !== self::STATUS_PROOF_VERIFIED || $this->isExpired()) {
      return false;
    }

    $this->write([
      'bootstrap_expires_at' => (string) (time() + max(30, (int) SystemConfig::get('account_recovery_bootstrap_ttl_seconds'))),
      'status' => self::STATUS_BOOTSTRAP_ISSUED,
    ]);
    return true;
  }

  /**
   * Handles bootstrapIsUsable operation.
   */
  public function bootstrapIsUsable(string $fingerprintHash, string $ipClass): bool
  {
    return $this->status() === self::STATUS_BOOTSTRAP_ISSUED
      && !$this->isExpired()
      && $this->matchesClientBinding($fingerprintHash, $ipClass)
      && time() <= (int) ($this->data['bootstrap_expires_at'] ?? '0');
  }

  /**
   * Handles markReplacementPasskeyRegistered operation.
   */
  public function markReplacementPasskeyRegistered(string $credentialId): void
  {
    $this->write(['replacement_credential_id' => $credentialId]);
  }

  /**
   * Handles replacementCredentialId operation.
   */
  public function replacementCredentialId(): string
  {
    return (string) ($this->data['replacement_credential_id'] ?? '');
  }

  /**
   * Handles complete operation.
   */
  public function complete(string $credentialId): void
  {
    $this->write([
      'replacement_credential_id' => $credentialId,
      'status' => self::STATUS_COMPLETED,
    ]);

    if ($this->userUuid() !== '') {
      Database::unlink(Keys::accountRecoveryActiveTransaction($this->userUuid()));
    }
  }

  /**
   * Handles cancel operation.
   */
  public function cancel(): void
  {
    $this->write(['status' => self::STATUS_CANCELLED]);
    if ($this->userUuid() !== '') {
      Database::unlink(Keys::accountRecoveryActiveTransaction($this->userUuid()));
    }
  }

  /**
   * Handles matchesClientBinding operation.
   */
  private function matchesClientBinding(string $fingerprintHash, string $ipClass): bool
  {
    $storedFingerprint = (string) ($this->data['txn_client_fingerprint_hash'] ?? '');
    $storedIpClass = (string) ($this->data['txn_ip_class'] ?? '');
    if ($storedFingerprint === '' && $storedIpClass === '') {
      return true;
    }

    return hash_equals($storedFingerprint, $fingerprintHash) && hash_equals($storedIpClass, $ipClass);
  }

  /** @param array<string, string> $updates */
  private function write(array $updates): void
  {
    Database::hset(Keys::accountRecoveryTransaction($this->txnId), $updates);
    foreach ($updates as $field => $value) {
      $this->data[$field] = $value;
    }
  }
}



