<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\CalendarController;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Domain\Sites;
use PayCal\Domain\Enums\SiteStatus;
use PHPUnit\Framework\TestCase;

/**
 * CalendarControllerIntegrationTest
 *
 * Integration tests for calendar endpoints.
 */
final class CalendarControllerIntegrationTest extends TestCase
{
    private string $testUserUUID;
    private string $testSessionHash;
    private bool $originalOrgSharedEncryptionEnabled;
    private bool $originalOrgSharedEncryptionWriteEnabled;
    /** @var array<string, mixed> */
    private array $originalCookie = [];
    /** @var array<string, mixed> */
    private array $originalServer = [];
    /** @var array<string, mixed> */
    private array $originalPost = [];
    /** @var array<string, mixed> */
    private array $originalGet = [];

    private function nonceKey(string $nonce): string
    {
        return Keys::USER . ':' . $this->testUserUUID . ':csrf:calendar:' . $nonce;
    }

    private function clearCalendarNonceKeys(): void
    {
        foreach (Database::scanKeys(Keys::USER . ':' . $this->testUserUUID . ':csrf:calendar:*') as $key) {
            Database::unlink((string) $key);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(string $output): array
    {
        $response = json_decode($output, true);
        $this->assertIsArray($response, 'Controller response must decode into an array');

        return $response;
    }

    /**
     * @return array<int, string>
     */
    private function seedPayPeriodScheduleForDate(string $userUUID, string $dateYmd): array
    {
        $baseKey = Keys::PAY_PERIOD . ':schedule:' . $userUUID;
        $indexKey = $baseKey . ':index';
        $metaKey = $baseKey . ':meta';
        $periodKey = $baseKey . ':' . $dateYmd;

        $start = new \DateTimeImmutable($dateYmd, new \DateTimeZone('America/Edmonton'));
        $endExclusive = $start->modify('+14 days');

        Database::hset($periodKey, [
            'start' => $start->format('Y-m-d'),
            'end_exclusive' => $endExclusive->format('Y-m-d'),
            'end_inclusive' => $endExclusive->modify('-1 day')->format('Y-m-d'),
            'frequency' => 'biweekly',
            'anchor' => 'Monday',
            'epoch' => $start->format('Y-m-d'),
            'timezone' => 'America/Edmonton',
        ]);

        Database::hset($metaKey, [
            'version' => '1',
            'frequency' => 'biweekly',
            'anchor' => 'Monday',
            'timezone' => 'America/Edmonton',
            'epoch' => $start->format('Y-m-d'),
            'historical_start' => $start->format('Y-m-d'),
            'generated_at' => $start->format('c'),
        ]);

        Database::getInstance()->client->zAdd($indexKey, (float) $start->getTimestamp(), $start->format('Y-m-d'));

        return [$periodKey, $metaKey, $indexKey];
    }

    private function orgWriteDeniedCounterKey(string $reason): string
    {
        $schema = SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;

        return "telemetry:encryption:{$schema}:org:work_write_denied:{$reason}";
    }

    private function clearOrgWriteDeniedTelemetryCounters(): void
    {
        $schema = SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
        foreach (Database::scanKeys("telemetry:encryption:{$schema}:org:work_write_denied:*") as $key) {
            Database::unlink((string) $key);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalCookie = $_COOKIE ?? [];
        $this->originalServer = $_SERVER ?? [];
        $this->originalPost = $_POST ?? [];
        $this->originalGet = $_GET ?? [];

        $this->testUserUUID = 'test-user-' . bin2hex(random_bytes(8));
        $testEmail = 'test-' . bin2hex(random_bytes(4)) . '@example.com';

        $this->originalOrgSharedEncryptionEnabled = (bool) SystemConfig::get('org_shared_encryption_enabled');
        $this->originalOrgSharedEncryptionWriteEnabled = (bool) SystemConfig::get('org_shared_encryption_enable_write');
        $this->clearOrgWriteDeniedTelemetryCounters();

        Database::hset(Keys::USER . ':' . $this->testUserUUID, [
            'user_uuid' => $this->testUserUUID,
            'email' => $testEmail,
            'full_name' => 'Test User',
            'email_verified' => '1',
            'auth_level' => (string) AuthLevel::USER->value,
        ]);

        $this->testSessionHash = hash('sha256', bin2hex(random_bytes(32)));
        Database::hset(Keys::SESSION . ':' . $this->testSessionHash, [
            'user_uuid' => $this->testUserUUID,
            'created_at' => date('c'),
        ]);
        Database::expire(Keys::SESSION . ':' . $this->testSessionHash, 3600);

        $_COOKIE['PAYCAL_AUTH'] = $this->testSessionHash;
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void
    {
        Database::unlink(Keys::USER . ':' . $this->testUserUUID);
        Database::unlink(Keys::SESSION . ':' . $this->testSessionHash);
        $this->clearCalendarNonceKeys();

        unset($_COOKIE['PAYCAL_AUTH']);
        unset($_SERVER['REQUEST_METHOD']);

        $_COOKIE = $this->originalCookie;
        $_SERVER = $this->originalServer;
        $_POST = $this->originalPost;
        $_GET = $this->originalGet;

        SystemConfig::set('org_shared_encryption_enabled', $this->originalOrgSharedEncryptionEnabled);
        SystemConfig::set('org_shared_encryption_enable_write', $this->originalOrgSharedEncryptionWriteEnabled);
        $this->clearOrgWriteDeniedTelemetryCounters();

        parent::tearDown();
    }

    private function issueCalendarNonce(CalendarController $controller): string
    {
        ob_start();
        $controller->getNonce();
        $output = ob_get_clean();
        $response = $this->decodeJsonResponse((string) $output);
        $this->assertTrue((bool) ($response['success'] ?? false));
        $this->assertArrayHasKey('data', $response);
        $this->assertIsArray($response['data']);

        return (string) (($response['data']['nonce'] ?? ''));
    }

    /**
     * Test getNonce endpoint generates valid nonce
     */
    public function testGetNonceGeneratesValidNonce(): void
    {
        $controller = new CalendarController();

        ob_start();
        $controller->getNonce();
        $output = ob_get_clean();

        $response = $this->decodeJsonResponse((string) $output);

        $this->assertTrue($response['success'] ?? false);
        $this->assertArrayHasKey('nonce', $response['data'] ?? []);
        
        $nonce = $response['data']['nonce'] ?? '';
        $this->assertNotEmpty($nonce);
        $this->assertIsString($nonce);
        $this->assertGreaterThan(8, strlen($nonce), 'Nonce should be sufficiently long');
    }

    /**
     * Test nonce is persisted in Redis
     */
    public function testNonceIsPersistedInRedis(): void
    {
        $controller = new CalendarController();

        ob_start();
        $controller->getNonce();
        $output = ob_get_clean();

        $response = $this->decodeJsonResponse((string) $output);
        $nonce = $response['data']['nonce'] ?? '';

        $nonceKey = $this->nonceKey((string) $nonce);
        $this->assertTrue(Database::exists($nonceKey));
    }

    /**
     * Test nonce has TTL set
     */
    public function testNonceHasTTL(): void
    {
        $controller = new CalendarController();

        ob_start();
        $controller->getNonce();
        $output = ob_get_clean();

        $response = $this->decodeJsonResponse((string) $output);
        $nonce = (string) (($response['data']['nonce'] ?? ''));
        $nonceKey = $this->nonceKey($nonce);
        $ttl = Database::ttl($nonceKey);

        $this->assertNotSame(-2, $ttl, 'Nonce key must exist in Redis');
        $this->assertNotSame(-1, $ttl, 'Nonce key must have an expiry');
        $this->assertGreaterThan(0, $ttl, 'Nonce should have TTL set');
        $this->assertLessThanOrEqual(3600, $ttl, 'Nonce TTL should not exceed 1 hour');
    }

    /**
     * Test multiple getNonce calls return different nonces
     */
    public function testMultipleGetNonceCallsReturnDifferentNonces(): void
    {
        $controller = new CalendarController();

        // First nonce
        $nonce1 = $this->issueCalendarNonce($controller);

        // Wait a tiny bit to ensure different timestamp
        usleep(1000);

        // Second nonce
        $nonce2 = $this->issueCalendarNonce($controller);

        $this->assertNotEmpty($nonce1);
        $this->assertNotEmpty($nonce2);
        $this->assertNotSame($nonce1, $nonce2, 'Each getNonce call should generate a new nonce');
    }

    public function testGetCalendarRejectsUnknownCorrelationContext(): void
    {
        $_GET = [
            'year' => '2026',
            'month' => '3',
            'correlation_context' => 'unapproved-correlation-context',
        ];

        $controller = new CalendarController();
        ob_start();
        $controller->getCalendar();
        $output = ob_get_clean();

        $response = $this->decodeJsonResponse((string) $output);

        $this->assertSame('error', $response['status'] ?? null);
        $this->assertStringContainsString('Correlation context denied', (string) ($response['message'] ?? ''));
        $this->assertSame('metadata_correlation_denied', $response['reason'] ?? null);
        $this->assertSame('unapproved-correlation-context', $response['context'] ?? null);
        $this->assertIsArray($response['decision'] ?? null);
        $this->assertSame('metadata_correlation_denied', $response['decision']['reason'] ?? null);
        $deniedPairs = $response['decision']['denied_pairs'] ?? null;
        $this->assertIsArray($deniedPairs);
        $this->assertContains('site_metadata:financial_payload', $deniedPairs);
    }

    public function testGetMonthDataRejectsUnknownCorrelationContext(): void
    {
        $_GET = [
            'month' => '2026-03',
            'correlation_context' => 'unapproved-correlation-context',
        ];

        $controller = new CalendarController();
        ob_start();
        $controller->getMonthData();
        $output = ob_get_clean();

        $response = $this->decodeJsonResponse((string) $output);

        $this->assertSame('error', $response['status'] ?? null);
        $this->assertStringContainsString('Correlation context denied', (string) ($response['message'] ?? ''));
        $this->assertSame('metadata_correlation_denied', $response['reason'] ?? null);
        $this->assertSame('unapproved-correlation-context', $response['context'] ?? null);
        $this->assertIsArray($response['decision'] ?? null);
        $this->assertSame('metadata_correlation_denied', $response['decision']['reason'] ?? null);
        $deniedPairs = $response['decision']['denied_pairs'] ?? null;
        $this->assertIsArray($deniedPairs);
        $this->assertContains('site_metadata:financial_payload', $deniedPairs);
    }

    public function testUpdateCalendarOrganizationModeRequiresOrganizationId(): void
    {
        SystemConfig::set('org_shared_encryption_enabled', true);
        SystemConfig::set('org_shared_encryption_enable_write', true);

        $controller = new CalendarController();
        $nonce = $this->issueCalendarNonce($controller);
        $this->assertNotSame('', $nonce);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $nonce,
            'd' => date('Y-m-d'),
            'mode' => 'organization',
            'entries' => '[]',
        ];

        ob_start();
        $controller->updateCalendar();
        $output = ob_get_clean();
        $response = $this->decodeJsonResponse((string) $output);

        $this->assertSame('error', $response['status'] ?? null);
        $this->assertStringContainsString('organization_id is required', (string) ($response['message'] ?? ''));
        $counter = Database::get($this->orgWriteDeniedCounterKey('missing_org_id'));
        $this->assertNotNull($counter);
        $this->assertSame('1', (string) $counter);
    }

    public function testUpdateCalendarOrganizationModeWritesDisabledEmitsTelemetry(): void
    {
        SystemConfig::set('org_shared_encryption_enabled', false);
        SystemConfig::set('org_shared_encryption_enable_write', false);

        $controller = new CalendarController();
        $nonce = $this->issueCalendarNonce($controller);
        $this->assertNotSame('', $nonce);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $nonce,
            'd' => date('Y-m-d'),
            'mode' => 'organization',
            'organization_id' => 'org-disabled-' . bin2hex(random_bytes(4)),
            'target_user_uuid' => $this->testUserUUID,
            'entries' => '[]',
        ];

        ob_start();
        $controller->updateCalendar();
        $output = ob_get_clean();
        $response = $this->decodeJsonResponse((string) $output);

        $this->assertSame('error', $response['status'] ?? null);
        $this->assertStringContainsString('Organization mode writes are disabled', (string) ($response['message'] ?? ''));
        $counter = Database::get($this->orgWriteDeniedCounterKey('writes_disabled'));
        $this->assertNotNull($counter);
        $this->assertSame('1', (string) $counter);
    }

    public function testUpdateCalendarOrganizationModeRejectsDelegatedWriteWithoutScope(): void
    {
        SystemConfig::set('org_shared_encryption_enabled', true);
        SystemConfig::set('org_shared_encryption_enable_write', true);

        $controller = new CalendarController();
        $nonce = $this->issueCalendarNonce($controller);
        $this->assertNotSame('', $nonce);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $nonce,
            'd' => date('Y-m-d'),
            'mode' => 'organization',
            'organization_id' => 'org-test-no-scope',
            'target_user_uuid' => 'other-user-' . bin2hex(random_bytes(4)),
            'entries' => '[]',
        ];

        ob_start();
        $controller->updateCalendar();
        $output = ob_get_clean();
        $response = $this->decodeJsonResponse((string) $output);

        $this->assertSame('error', $response['status'] ?? null);
        $this->assertStringContainsString('Insufficient organization scope for delegated work mutation', (string) ($response['message'] ?? ''));
        $counter = Database::get($this->orgWriteDeniedCounterKey('insufficient_scope'));
        $this->assertNotNull($counter);
        $this->assertSame('1', (string) $counter);
    }

    public function testUpdateCalendarOrganizationModeDelegatedWriteSucceeds(): void
    {
        SystemConfig::set('org_shared_encryption_enabled', true);
        SystemConfig::set('org_shared_encryption_enable_write', true);

        $orgId = 'org-write-' . bin2hex(random_bytes(4));
        $targetUserUUID = 'target-user-' . bin2hex(random_bytes(6));
        $targetSiteId = 'S' . strtoupper(substr(bin2hex(random_bytes(5)), 0, 9));
        $day = date('Y-m-d');
        $payPeriodKeys = [];

        Database::hset(Keys::USER . ':' . $targetUserUUID, [
            'user_uuid' => $targetUserUUID,
            'email' => 'target-' . bin2hex(random_bytes(3)) . '@example.com',
            'full_name' => 'Target User',
            'email_verified' => '1',
            'auth_level' => (string) AuthLevel::USER->value,
            'timezone' => 'America/Edmonton',
            'pay_anchor' => 'Monday',
            'pay_period_start' => '2024-01-01',
            'pay_period_length' => '14',
            'pay_frequency' => 'biweekly',
        ]);

        Database::hset(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $this->testUserUUID, [
            'organization_id' => $orgId,
            'user_uuid' => $this->testUserUUID,
            'role' => 'member',
            'status' => OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
            'scopes' => 'work.write,work.read',
        ]);
        Database::hset(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $targetUserUUID, [
            'organization_id' => $orgId,
            'user_uuid' => $targetUserUUID,
            'role' => 'member',
            'status' => OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
            'scopes' => 'work.read',
        ]);

        Sites::updateSites([
            $targetSiteId => [
                'site_name' => 'Delegated Target Site',
                'wage' => '30.00',
                'living_out_allowance' => '0',
                'travel_hours' => '0',
                'province' => 'AB',
                'status' => SiteStatus::ACTIVE->value,
            ],
        ], $targetUserUUID);

        $payPeriodKeys = $this->seedPayPeriodScheduleForDate($targetUserUUID, $day);

        $envelope = [
            'ciphertext' => base64_encode('ciphertext-placeholder'),
            'nonce' => base64_encode(str_repeat('n', 12)),
            'aad' => 'site-aad',
            'meta' => [
                'encryption_mode' => 'organization',
                'org_id' => $orgId,
                'segment' => OrganizationDiscoveryService::ORG_DEK_SEGMENT_CURRENT_PERIOD,
                'key_version' => 'v1',
            ],
        ];

        try {
            $controller = new CalendarController();
            $nonce = $this->issueCalendarNonce($controller);
            $this->assertNotSame('', $nonce);

            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = [
                'csrf_token' => $nonce,
                'd' => $day,
                'mode' => 'organization',
                'organization_id' => $orgId,
                'target_user_uuid' => $targetUserUUID,
                'entries' => (string) json_encode([
                    [
                        'site_id' => $targetSiteId,
                        'encrypted_blob' => base64_encode((string) json_encode($envelope)),
                    ],
                ]),
            ];

            ob_start();
            $controller->updateCalendar();
            $output = ob_get_clean();
            $response = $this->decodeJsonResponse((string) $output);

            $this->assertSame('success', $response['status'] ?? null);

            $workKey = Keys::WORK . ':' . $targetUserUUID . ':' . $day . ':' . $targetSiteId;
            $stored = Database::hgetall($workKey);
            $this->assertNotSame([], $stored);
            $this->assertSame(base64_encode((string) json_encode($envelope)), (string) ($stored['encrypted_blob'] ?? ''));
        } finally {
            Database::unlink(Keys::USER . ':' . $targetUserUUID);
            Database::unlink(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $this->testUserUUID);
            Database::unlink(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $targetUserUUID);
            Database::unlink(Keys::SITE . ':' . $targetUserUUID . ':' . $targetSiteId);
            Database::unlink(Keys::WORK . ':' . $targetUserUUID . ':' . $day . ':' . $targetSiteId);
            foreach ($payPeriodKeys as $key) {
                Database::unlink($key);
            }
        }
    }
}
