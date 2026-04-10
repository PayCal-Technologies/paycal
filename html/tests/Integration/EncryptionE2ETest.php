<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PayCal\Domain\Database;
use PayCal\Domain\WorkEntry;
use PHPUnit\Framework\Attributes\Group;

require_once __DIR__ . '/../../tests/bootstrap.php';

/**
 * EncryptionE2ETest
 */
#[Group('integration')]
#[Group('crypto')]
final class EncryptionE2ETest extends TestCase
{
    public function testServerSideAesGcmEnvelopeRoundtrip(): void
  {
    // Arrange
    $userUUID = 'Ue2euser01';
    $workDate = date('Y-m-d');
    $siteID = 'S111111111';

    // Create the site first (required by WorkEntry::updateWorkEntry)
    $siteKey = D_SITE.":{$userUUID}:{$siteID}";
    Database::hset($siteKey, [
        'site_name' => 'E2E Test Site',
        'wage' => '25.00',
        'status' => 'active',
    ]);

    $entry = [
        'd' => $workDate,
        's' => $siteID,
        'h' => 9,
        'l' => 0,
        't' => 0,
    ];

    // Use a randomly generated 32-byte key to simulate derived AES-256-GCM key
    $key = random_bytes(32);
    $nonce = random_bytes(12);

    $plaintext = json_encode($entry);

    $tag = '';
    $ciphertext_raw = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    $combined = $ciphertext_raw.$tag; // WebCrypto returns ciphertext with tag appended

    $envelope = json_encode([
        'version' => 1,
        'ciphertext' => base64_encode($combined),
        'nonce' => base64_encode($nonce),
        'aad' => $siteID,
    ]);
    $blob = base64_encode($envelope);

    $workDetails = [
        'd' => $workDate,
        's' => $siteID,
        'h' => 9,
        'l' => 0,
        't' => 0,
        'encrypted_blob' => $blob,
    ];

    $workEntryKey = D_WORK.":{$userUUID}:{$workDate}:{$siteID}";
    Database::del($workEntryKey);

    // Act: store via domain method
    $ok = WorkEntry::updateWorkEntry($workDetails, $userUUID);

    // Assert stored
    $this->assertTrue($ok, 'WorkEntry::updateWorkEntry should return true');
    $stored = Database::hgetall($workEntryKey);
    $this->assertArrayHasKey('encrypted_blob', $stored);

    // Now simulate client-side decryption using the same key
    $storedBlob = $stored['encrypted_blob'];
    $decodedEnvelope = json_decode(base64_decode($storedBlob, true), true);
    $this->assertIsArray($decodedEnvelope);

    $combinedB64 = $decodedEnvelope['ciphertext'];
    $nonceB64 = $decodedEnvelope['nonce'];
    $cipherCombined = base64_decode($combinedB64, true);
    $nonceBytes = base64_decode($nonceB64, true);

    // Separate tag (last 16 bytes) from ciphertext
    $tagLen = 16;
    $ctLen = strlen($cipherCombined) - $tagLen;
    $ciphertext = substr($cipherCombined, 0, $ctLen);
    $tagExtracted = substr($cipherCombined, $ctLen, $tagLen);

    $decrypted = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonceBytes, $tagExtracted);
    $this->assertNotFalse($decrypted, 'Decryption should succeed');

    $decoded = json_decode($decrypted, true);
    $this->assertIsArray($decoded);
    $this->assertSame($entry['h'], $decoded['h']);

    // Cleanup
    Database::del($workEntryKey);
    Database::del($siteKey);
  }
}
