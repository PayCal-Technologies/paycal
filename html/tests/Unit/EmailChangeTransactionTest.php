<?php declare(strict_types=1);

namespace Tests\Unit;

use PayCal\Domain\EmailChangeTransaction;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PHPUnit\Framework\TestCase;

/**
 * EmailChangeTransactionTest
 *
 * Unit tests for EmailChangeTransaction domain class.
 */
final class EmailChangeTransactionTest extends TestCase
{
    private string $testUserUUID = 'test-user-uuid';
    private string $oldEmail = 'old@example.com';
    private string $newEmail = 'new@example.com';
    /** @var array<int, string> */
    private array $createdTxnIds = [];

    private function createTransaction(int $codeTtlMinutes = 15): EmailChangeTransaction
    {
        $txn = EmailChangeTransaction::create(
            $this->testUserUUID,
            $this->oldEmail,
            $this->newEmail,
            $codeTtlMinutes
        );
        $txn->setOldCodeHash(hash('sha256', 'oldcode'));
        $txn->setNewCodeHash(hash('sha256', 'newcode'));
        $this->createdTxnIds[] = $txn->getTxnId();

        return $txn;
    }

    protected function tearDown(): void
    {
        foreach ($this->createdTxnIds as $txnId) {
            Database::unlink(Keys::emailChangeTransaction($txnId));
        }
        parent::tearDown();
    }

    /**
     * Test create() initializes transaction with correct fields
     */
    public function testCreateInitializesTransaction(): void
    {
        $startedAt = time();
        $txn = $this->createTransaction(15);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $txn->getTxnId());
        $this->assertSame($this->testUserUUID, $txn->getUserUuid());
        $this->assertSame($this->oldEmail, $txn->getOldEmail());
        $this->assertSame($this->newEmail, $txn->getNewEmail());
        $this->assertSame(hash('sha256', 'oldcode'), $txn->getOldCodeHash());
        $this->assertSame(hash('sha256', 'newcode'), $txn->getNewCodeHash());
        $this->assertFalse($txn->isOldVerified());
        $this->assertFalse($txn->isNewVerified());
        $this->assertSame('pending', $txn->getStatus());
        $this->assertGreaterThanOrEqual($startedAt + (15 * 60) - 2, $txn->getExpiresAt());
    }

    /**
     * Test save() persists transaction to Redis
     */
    public function testSavePersistsToRedis(): void
    {
        $txn = $this->createTransaction(15);

        $txn->save();

        // Verify Redis contains the transaction data
        $redisData = Database::hgetall(Keys::emailChangeTransaction($txn->getTxnId()));
        $this->assertIsArray($redisData);
        $this->assertArrayHasKey('user_uuid', $redisData);
        $this->assertSame($this->testUserUUID, $redisData['user_uuid']);
        $this->assertArrayHasKey('old_email', $redisData);
        $this->assertSame($this->oldEmail, $redisData['old_email']);
        $this->assertSame(hash('sha256', 'oldcode'), $redisData['old_code_hash']);
    }

    /**
     * Test load() retrieves transaction from Redis
     */
    public function testLoadRetrievesFromRedis(): void
    {
        $originalTxn = $this->createTransaction(15);
        $originalTxn->save();

        // Now load it
        $loadedTxn = EmailChangeTransaction::load($originalTxn->getTxnId());
        $this->assertNotNull($loadedTxn);
        $this->assertSame($originalTxn->getTxnId(), $loadedTxn->getTxnId());
        $this->assertSame($this->testUserUUID, $loadedTxn->getUserUuid());
        $this->assertSame($this->oldEmail, $loadedTxn->getOldEmail());
        $this->assertSame($this->newEmail, $loadedTxn->getNewEmail());
    }

    /**
     * Test load() returns null for nonexistent transaction
     */
    public function testLoadReturnsNullForMissingTxn(): void
    {
        $txn = EmailChangeTransaction::load('nonexistent-txn');
        $this->assertNull($txn);
    }

    /**
     * Test delete() removes transaction from Redis
     */
    public function testDeleteRemovesFromRedis(): void
    {
        $txn = $this->createTransaction(15);
        $txn->save();

        // Verify it exists
        $loaded = EmailChangeTransaction::load($txn->getTxnId());
        $this->assertNotNull($loaded);

        // Delete it
        $txn->delete();

        // Verify it's gone
        $deleted = EmailChangeTransaction::load($txn->getTxnId());
        $this->assertNull($deleted);
    }

    /**
     * Test isExpired() returns true for expired transaction
     */
    public function testIsExpiredReturnsTrueForExpiredTxn(): void
    {
        $txn = $this->createTransaction(-1);

        $this->assertTrue($txn->isExpired());
    }

    /**
     * Test isExpired() returns false for fresh transaction
     */
    public function testIsExpiredReturnsFalseForFreshTxn(): void
    {
        $txn = $this->createTransaction(60);

        $this->assertFalse($txn->isExpired());
    }

    /**
     * Test bothCodesVerified() returns false when neither verified
     */
    public function testBothCodesVerifiedReturnsFalseWhenNeitherVerified(): void
    {
        $txn = $this->createTransaction(15);

        $this->assertFalse($txn->bothCodesVerified());
    }

    /**
     * Test bothCodesVerified() returns false when only one verified
     */
    public function testBothCodesVerifiedReturnsFalseWhenOneVerified(): void
    {
        $txn = $this->createTransaction(15);

        // Mark old as verified
        $txn->setOldVerified(true);

        $this->assertFalse($txn->bothCodesVerified());
    }

    /**
     * Test bothCodesVerified() returns true when both verified
     */
    public function testBothCodesVerifiedReturnsTrueWhenBothVerified(): void
    {
        $txn = $this->createTransaction(15);

        // Mark both as verified
        $txn->setOldVerified(true);
        $txn->setNewVerified(true);

        $this->assertTrue($txn->bothCodesVerified());
    }

    /**
     * Test setters update transaction state
     */
    public function testSettersUpdateState(): void
    {
        $txn = $this->createTransaction(15);

        // Test setting verified flags
        $this->assertFalse($txn->isOldVerified());
        $txn->setOldVerified(true);
        $this->assertTrue($txn->isOldVerified());

        $this->assertFalse($txn->isNewVerified());
        $txn->setNewVerified(true);
        $this->assertTrue($txn->isNewVerified());

        // Test setting status
        $this->assertSame('pending', $txn->getStatus());
        $txn->setStatus('committed');
        $this->assertSame('committed', $txn->getStatus());

        $txn->setStatus('cancelled');
        $this->assertSame('cancelled', $txn->getStatus());
    }

    /**
     * Test resend count tracking
     */
    public function testResendCountTracking(): void
    {
        $txn = $this->createTransaction(15);

        $this->assertSame(0, $txn->getResendCount());

        $txn->incrementResendCount();
        $this->assertSame(1, $txn->getResendCount());

        $txn->incrementResendCount();
        $this->assertSame(2, $txn->getResendCount());
    }

    /**
     * Test verify attempt tracking
     */
    public function testVerifyAttemptTracking(): void
    {
        $txn = $this->createTransaction(15);

        $this->assertSame(0, $txn->getVerifyAttempts());

        $txn->incrementVerifyAttempts();
        $this->assertSame(1, $txn->getVerifyAttempts());

        $txn->incrementVerifyAttempts();
        $this->assertSame(2, $txn->getVerifyAttempts());
    }
}
