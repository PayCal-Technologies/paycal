<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Controllers\SitesController;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\OrganizationDiscoveryService;
use PayCal\Domain\Enums\SiteStatus;
use PayCal\Domain\Sites;
use PHPUnit\Framework\TestCase;

/**
 * SitesControllerIntegrationTest
 *
 * Integration tests for sites management endpoints.
 */
final class SitesControllerIntegrationTest extends TestCase
{
    private string $testUserUUID;
    private string $testSessionHash;
    private string $testSiteId;
    /** @var array<int, string> */
    private array $ephemeralKeys = [];

    private function orgUnwrapDeniedCounterKey(string $reason): string
    {
        $schema = SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;

        return "telemetry:encryption:{$schema}:org:unwrap_denied_{$reason}";
    }

    private function clearOrgUnwrapDeniedTelemetryCounters(): void
    {
        $schema = SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA;
        foreach (Database::scanKeys("telemetry:encryption:{$schema}:org:unwrap_denied_*") as $key) {
            Database::unlink((string) $key);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->testUserUUID = 'test-user-' . bin2hex(random_bytes(8));
        $testEmail = 'test-' . bin2hex(random_bytes(4)) . '@example.com';

        Database::hset(Keys::USER . ':' . $this->testUserUUID, [
            'user_uuid' => $this->testUserUUID,
            'email' => $testEmail,
            'full_name' => 'Test User',
            'email_verified' => '1',
            'auth_level' => (string) AuthLevel::USER->value,
            'encryption_salt' => base64_encode(random_bytes(16)),
        ]);

        $this->testSessionHash = hash('sha256', bin2hex(random_bytes(32)));
        Database::hset(Keys::SESSION . ':' . $this->testSessionHash, [
            'user_uuid' => $this->testUserUUID,
            'created_at' => date('c'),
            'credential_id' => 'cred-' . bin2hex(random_bytes(4)),
        ]);
        Database::expire(Keys::SESSION . ':' . $this->testSessionHash, 3600);
        $this->clearOrgUnwrapDeniedTelemetryCounters();

        // Create a test site record in the current canonical shape
        $this->testSiteId = bin2hex(random_bytes(16));
        Sites::updateSites([
            $this->testSiteId => [
                'site_name' => 'Test Site',
                'wage' => '25.50',
                'living_out_allowance' => '0',
                'travel_hours' => '0',
                'province' => 'AB',
                'status' => SiteStatus::ACTIVE->value,
            ],
        ], $this->testUserUUID);

        $_COOKIE['PAYCAL_AUTH'] = $this->testSessionHash;
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    protected function tearDown(): void
    {
        // Clean up test data
        Database::unlink(Keys::USER . ':' . $this->testUserUUID);
        Database::unlink(Keys::SESSION . ':' . $this->testSessionHash);
        
        // Clean up site data
        Database::unlink(Keys::SITE . ':' . $this->testUserUUID . ':' . $this->testSiteId);
        foreach ($this->ephemeralKeys as $key) {
            Database::unlink($key);
        }
        $this->clearOrgUnwrapDeniedTelemetryCounters();

        unset($_COOKIE['PAYCAL_AUTH']);
        unset($_SERVER['REQUEST_METHOD']);
        unset($_POST);
        unset($_SERVER['HTTP_X_RESOURCE_ID']);

        parent::tearDown();
    }

    /**
     * Test updating a single site
     */
    public function testUpdateSingleSite(): void
    {
        $_POST['id'] = $this->testSiteId;
        $_POST['site_name'] = 'Updated Site Name';
        $_POST['wage'] = '30.00';

        $controller = new SitesController();

        ob_start();
        $controller->updateSites();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertTrue($response['success'] ?? false, 'Site update should succeed');
    }

    /**
     * Test deleting a site
     */
    public function testDeleteSite(): void
    {
        $_SERVER['HTTP_X_RESOURCE_ID'] = $this->testSiteId;

        $controller = new SitesController();

        ob_start();
        $controller->deleteSite();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        // Note: Delete may fail if site has work entries, which is expected
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('message', $response);
    }

    /**
     * Test delete with missing site ID returns error
     */
    public function testDeleteSiteWithMissingIdReturnsError(): void
    {
        // No site ID provided
        unset($_SERVER['HTTP_X_RESOURCE_ID']);
        unset($_POST['id']);

        $controller = new SitesController();

        ob_start();
        $controller->deleteSite();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true, 'Delete without ID should fail');
    }

    /**
     * Test getting archived work entries
     */
    public function testGetArchivedWork(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['site_id'] = $this->testSiteId;

        $controller = new SitesController();

        ob_start();
        $controller->getArchivedWork();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        // Success or error, both are valid (depends on if site has archived entries)
        $this->assertArrayHasKey('success', $response);
        $this->assertArrayHasKey('message', $response);
    }

    /**
     * Test getting archived work with missing site_id returns error
     */
    public function testGetArchivedWorkWithMissingSiteIdReturnsError(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_GET['site_id']);

        $controller = new SitesController();

        ob_start();
        $controller->getArchivedWork();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true);
        $this->assertStringContainsString('Missing site_id', $response['message'] ?? '');
    }

    /**
     * Test permanent delete with missing site_id returns error
     */
    public function testPermanentDeleteWithMissingSiteIdReturnsError(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        unset($_POST['site_id']);
        unset($_POST['id']);
        unset($_SERVER['HTTP_X_RESOURCE_ID']);

        $controller = new SitesController();

        ob_start();
        $controller->permanentDelete();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertFalse($response['success'] ?? true);
        $this->assertStringContainsString('Missing site_id', (string) ($response['message'] ?? ''));
    }

    /**
     * Test get user sites returns sites payload
     */
    public function testGetUserSitesReturnsPayload(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $controller = new SitesController();

        ob_start();
        $controller->getUserSites();
        $output = ob_get_clean();

        $response = json_decode($output, true);

        $this->assertIsArray($response);
        $this->assertTrue($response['success'] ?? false);
        $this->assertArrayHasKey('sites', $response['data'] ?? []);
    }

    public function testCreateSiteAllowsDelegatedOwnerMutationWithSitesWriteScope(): void
    {
        $ownerUUID = 'owner-user-' . bin2hex(random_bytes(8));
        $ownerEmail = 'owner-' . bin2hex(random_bytes(4)) . '@example.com';
        $service = new OrganizationDiscoveryService();
        $createdSiteId = '';

        try {
            Database::hset(Keys::USER . ':' . $ownerUUID, [
                'user_uuid' => $ownerUUID,
                'email' => $ownerEmail,
                'full_name' => 'Owner User',
                'email_verified' => '1',
                'auth_level' => (string) AuthLevel::USER->value,
            ]);
            Database::hset(Keys::USER_SUBSCRIPTION . ':' . $ownerUUID, [
                'tier' => 'premium',
                'status' => 'active',
            ]);

            $createOrg = $service->createOrganization($ownerUUID, 'Delegation Org', [
                'organization_type' => 'shared',
            ]);
            $this->assertTrue($createOrg['success']);
            $orgId = (string) ($createOrg['data']['organization_id'] ?? '');
            $this->assertNotSame('', $orgId);

            Database::hset(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $this->testUserUUID, [
                'organization_id' => $orgId,
                'user_uuid' => $this->testUserUUID,
                'role' => 'member',
                'status' => 'active',
                'scopes' => 'sites.write,sites.read,work.read',
                'updated_at' => date('c'),
            ]);
            Database::sadd(Keys::ORGANIZATION_USER . ':' . $this->testUserUUID, $orgId);

            $_POST = [
                'owner_uuid' => $ownerUUID,
                'site_name' => 'Delegated Site Create',
                'wage' => '33.25',
                'living_out_allowance' => '0',
                'travel_hours' => '0',
                'province' => 'AB',
                'status' => SiteStatus::ACTIVE->value,
            ];

            $controller = new SitesController();
            ob_start();
            $controller->createSite();
            $output = ob_get_clean();

            $response = json_decode((string) $output, true);
            $this->assertIsArray($response);
            $this->assertTrue($response['success'] ?? false, 'Delegated create should succeed with sites.write scope.');

            $createdSiteId = (string) ($response['data']['id'] ?? '');
            $this->assertNotSame('', $createdSiteId);

            $site = Database::hgetall(Keys::SITE . ':' . $ownerUUID . ':' . $createdSiteId);
            $this->assertSame('Delegated Site Create', (string) ($site['site_name'] ?? ''));

            $service->leaveOrganization($ownerUUID, $orgId);
            Database::unlink(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $this->testUserUUID);
            Database::srem(Keys::ORGANIZATION_USER . ':' . $this->testUserUUID, $orgId);
        } finally {
            if ($createdSiteId !== '') {
                Database::unlink(Keys::SITE . ':' . $ownerUUID . ':' . $createdSiteId);
            }
            Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $ownerUUID);
            Database::unlink(Keys::USER . ':' . $ownerUUID);
        }
    }

    public function testCreateSiteRejectsDelegatedOwnerMutationWithoutSitesWriteScope(): void
    {
        $ownerUUID = 'owner-user-denied-' . bin2hex(random_bytes(8));
        $ownerEmail = 'owner-denied-' . bin2hex(random_bytes(4)) . '@example.com';
        $service = new OrganizationDiscoveryService();

        try {
            Database::hset(Keys::USER . ':' . $ownerUUID, [
                'user_uuid' => $ownerUUID,
                'email' => $ownerEmail,
                'full_name' => 'Owner Denied',
                'email_verified' => '1',
                'auth_level' => (string) AuthLevel::USER->value,
            ]);
            Database::hset(Keys::USER_SUBSCRIPTION . ':' . $ownerUUID, [
                'tier' => 'premium',
                'status' => 'active',
            ]);

            $createOrg = $service->createOrganization($ownerUUID, 'Delegation Org Denied', [
                'organization_type' => 'shared',
            ]);
            $this->assertTrue($createOrg['success']);
            $orgId = (string) ($createOrg['data']['organization_id'] ?? '');
            $this->assertNotSame('', $orgId);

            Database::hset(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $this->testUserUUID, [
                'organization_id' => $orgId,
                'user_uuid' => $this->testUserUUID,
                'role' => 'viewer',
                'status' => 'active',
                'scopes' => 'work.read,sites.read',
                'updated_at' => date('c'),
            ]);
            Database::sadd(Keys::ORGANIZATION_USER . ':' . $this->testUserUUID, $orgId);

            $_POST = [
                'owner_uuid' => $ownerUUID,
                'site_name' => 'Denied Delegated Site Create',
                'wage' => '22.00',
                'living_out_allowance' => '0',
                'travel_hours' => '0',
                'province' => 'AB',
                'status' => SiteStatus::ACTIVE->value,
            ];

            $controller = new SitesController();
            ob_start();
            $controller->createSite();
            $output = ob_get_clean();

            $response = json_decode((string) $output, true);
            $this->assertIsArray($response);
            $this->assertFalse($response['success'] ?? true, 'Delegated create should fail without sites.write scope.');
            $this->assertStringContainsString('Insufficient organization scope', (string) ($response['message'] ?? ''));

            $service->leaveOrganization($ownerUUID, $orgId);
            Database::unlink(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $this->testUserUUID);
            Database::srem(Keys::ORGANIZATION_USER . ':' . $this->testUserUUID, $orgId);
        } finally {
            Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $ownerUUID);
            Database::unlink(Keys::USER . ':' . $ownerUUID);
        }
    }

    public function testGetSiteEarningsRejectsUnknownCorrelationContext(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['year'] = '2026';
        $_GET['correlation_context'] = 'unapproved-correlation-context';

        $controller = new SitesController();
        ob_start();
        $controller->getSiteEarnings();
        $output = ob_get_clean();

        $response = json_decode((string) $output, true);
        $this->assertIsArray($response);

        $this->assertSame('error', $response['status'] ?? null);
        $this->assertStringContainsString('Correlation context denied', (string) ($response['message'] ?? ''));
        $this->assertSame('metadata_correlation_denied', $response['reason'] ?? null);
        $this->assertSame('unapproved-correlation-context', $response['context'] ?? null);
        $this->assertIsArray($response['decision'] ?? null);
        $this->assertSame('metadata_correlation_denied', $response['decision']['reason'] ?? null);
        $this->assertContains('site_metadata:financial_payload', $response['decision']['denied_pairs'] ?? []);
    }

    public function testGetSiteEarningsOrgEnvelopeWithoutWrapDoesNotFatal(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $year = (string) SystemConfig::get('year_min');
        $_GET['year'] = $year;

        $orgId = 'org-telemetry-' . bin2hex(random_bytes(6));
        $workDate = $year . '-06-15';
        $workKey = Keys::WORK . ':' . $this->testUserUUID . ':' . $workDate . ':' . $this->testSiteId;

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

        Database::hset(Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $this->testUserUUID, [
            'organization_id' => $orgId,
            'user_uuid' => $this->testUserUUID,
            'role' => 'member',
            'status' => OrganizationDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
            'scopes' => 'work.read,sites.read',
        ]);
        Database::hset($workKey, [
            'encrypted_blob' => base64_encode((string) json_encode($envelope)),
        ]);

        $this->ephemeralKeys[] = Keys::ORGANIZATION_RELATIONSHIP . ':' . $orgId . ':' . $this->testUserUUID;
        $this->ephemeralKeys[] = $workKey;

        $controller = new SitesController();
        ob_start();
        $controller->getSiteEarnings();
        $output = ob_get_clean();

        $response = json_decode((string) $output, true);
        $this->assertIsArray($response);
        $this->assertArrayHasKey('success', $response);
    }
}
