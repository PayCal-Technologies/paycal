<?php declare(strict_types=1);

use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\BusinessMembersCache;
use PayCal\Domain\BusinessMembersFinancialSummary;
use PayCal\Domain\BusinessMemberReportExportService;
use PayCal\Domain\BusinessMemberReportsService;
use PayCal\Domain\BusinessProtectedDataAccess;
use PayCal\Domain\BusinessWorkspaceCache;
use PayCal\Domain\Config\SystemConfig;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\TeamEarningsSnapshotBuilder;
use PayCal\Infrastructure\Business\BusinessEncryptionService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[Group('unit')]
#[Group('redis-write')]
final class BusinessMemberReportExportServiceTest extends TestCase
{
  private string $suffix = '';
  private string $businessId = '';
  private string $ownerUUID = '';
  private string $memberUUID = '';
  private string $siteId = '';
  private string $consentId = '';
  private string $credentialId = '';
  private string $dekId = '';
  private bool $originalOrgEncryptionEnabled = false;

  protected function setUp(): void
  {
    parent::setUp();

    $this->suffix = bin2hex(random_bytes(4));
    $this->businessId = 'biz_export_' . $this->suffix;
    $this->ownerUUID = 'owner_export_' . $this->suffix;
    $this->memberUUID = 'member_export_' . $this->suffix;
    $this->siteId = 'site_export_' . $this->suffix;
    $this->consentId = 'consent_export_' . $this->suffix;
    $this->credentialId = 'cred_export_' . $this->suffix;
    $this->dekId = 'dek_export_' . $this->suffix;
    $this->originalOrgEncryptionEnabled = (bool) SystemConfig::get('business_shared_encryption_enabled');
    SystemConfig::set('business_shared_encryption_enabled', true);

    $this->seedBusiness();
    $this->seedConsentAndWrap();
    $this->seedWorkEntry();
  }

  protected function tearDown(): void
  {
    $this->clearBusinessAuditEvents();

    foreach (Database::scanKeys('*' . $this->suffix . '*') as $key) {
      Database::unlink((string) $key);
    }

    SystemConfig::set('business_shared_encryption_enabled', $this->originalOrgEncryptionEnabled);
    parent::tearDown();
  }

  #[Test]
  public function protectedMemberXlsxExportBuildsFromServerSideGate(): void
  {
    $result = (new BusinessMemberReportExportService())->exportMemberReport(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      'yearly',
      'xlsx',
      2026,
    );

    $this->assertTrue($result['success']);
    $this->assertSame('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', $result['data']['mime']);
    $this->assertStringStartsWith('PK', $result['data']['bytes']);
    $this->assertSame(1, $result['data']['entry_count']);
  }

  #[Test]
  public function protectedMemberXlsxExportRequiresPremiumReporting(): void
  {
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID, [
      'tier' => 'free',
      'status' => 'active',
    ]);

    $result = (new BusinessMemberReportExportService())->exportMemberReport(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      'yearly',
      'xlsx',
      2026,
    );

    $this->assertFalse($result['success']);
    $this->assertSame('premium_required', $result['reason']);

    $eventTypes = $this->businessExportAuditEventTypes();
    $this->assertContains('business.member.report.export.requested', $eventTypes);
    $this->assertContains('business.member.report.export.denied', $eventTypes);
    $this->assertNotContains('business.member.report.export.started', $eventTypes);
    $this->assertNotContains('business.member.report.export.completed', $eventTypes);
  }

  #[Test]
  public function memberReportsViewAllowsActiveSelfScopedMemberWithConsent(): void
  {
    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberUUID, [
      'role' => 'member',
      'scopes' => 'work.read,work.scope.self',
    ]);

    $result = (new BusinessMemberReportsService())->getMemberBreakdown(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      2026,
    );

    $this->assertTrue($result['success']);
    $this->assertStringContainsString('member_reports_line_graph_', (string) ($result['data']['html'] ?? ''));
  }

  #[Test]
  public function protectedMemberPdfExportBuildsFromServerSideGate(): void
  {
    $result = (new BusinessMemberReportExportService())->exportMemberReport(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      'daily',
      'pdf',
      2026,
    );

    $this->assertTrue($result['success']);
    $this->assertSame('application/pdf', $result['data']['mime']);
    $this->assertStringStartsWith('%PDF', $result['data']['bytes']);
  }

  #[Test]
  public function protectedMemberExportDeniesWhenWrapIsMissing(): void
  {
    $this->clearBusinessAuditEvents();
    Database::unlink(Keys::businessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->memberUUID,
      $this->credentialId,
    ));

    $result = (new BusinessMemberReportExportService())->exportMemberReport(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      'yearly',
      'xlsx',
      2026,
    );

    $this->assertFalse($result['success']);
    $this->assertSame('missing_consent_or_wrap', $result['reason']);

    $eventTypes = $this->businessExportAuditEventTypes();
    $this->assertContains('business.member.report.export.requested', $eventTypes);
    $this->assertContains('business.member.report.export.denied', $eventTypes);
    $this->assertNotContains('business.member.report.export.started', $eventTypes);
    $this->assertNotContains('business.member.report.export.completed', $eventTypes);
    $this->assertNotContains('business.member.report.export.failed', $eventTypes);
  }

  #[Test]
  public function protectedMemberExportAuditsRequestedStartedAndCompleted(): void
  {
    $this->clearBusinessAuditEvents();

    $result = (new BusinessMemberReportExportService())->exportMemberReport(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      'yearly',
      'xlsx',
      2026,
    );

    $this->assertTrue($result['success']);

    $eventTypes = $this->businessExportAuditEventTypes();
    $this->assertContains('business.member.report.export.requested', $eventTypes);
    $this->assertContains('business.member.report.export.started', $eventTypes);
    $this->assertContains('business.member.report.export.completed', $eventTypes);
    $this->assertNotContains('business.member.report.export.denied', $eventTypes);
    $this->assertNotContains('business.member.report.export.failed', $eventTypes);
  }

  #[Test]
  public function revokeAfterCacheDeniesReadsExportsSummariesAndTeamEarnings(): void
  {
    $initialRead = (new BusinessProtectedDataAccess())->readMemberWork(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      2026,
    );
    $this->assertTrue($initialRead['success']);

    $initialExport = (new BusinessMemberReportExportService())->exportMemberReport(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      'yearly',
      'xlsx',
      2026,
    );
    $this->assertTrue($initialExport['success']);

    $this->seedStaleProtectedCaches();

    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberUUID, [
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED,
    ]);
    Database::hset(Keys::businessConsent($this->consentId), [
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED,
    ]);
    Database::hset(Keys::businessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->memberUUID,
      $this->credentialId,
    ), [
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED,
    ]);

    $postRevokeRead = (new BusinessProtectedDataAccess())->readMemberWork(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      2026,
    );
    $this->assertFalse($postRevokeRead['success']);
    $this->assertSame('target_membership_inactive', $postRevokeRead['reason']);

    $postRevokePdf = (new BusinessMemberReportExportService())->exportMemberReport(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      'daily',
      'pdf',
      2026,
    );
    $this->assertFalse($postRevokePdf['success']);
    $this->assertSame('target_membership_inactive', $postRevokePdf['reason']);

    $postRevokeXlsx = (new BusinessMemberReportExportService())->exportMemberReport(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
      'yearly',
      'xlsx',
      2026,
    );
    $this->assertFalse($postRevokeXlsx['success']);
    $this->assertSame('target_membership_inactive', $postRevokeXlsx['reason']);

    $summary = (new BusinessMembersFinancialSummary())->forBusinessMembers(
      $this->businessId,
      [$this->memberUUID],
      2026,
      false,
      false,
      false,
      $this->ownerUUID,
    );
    $this->assertSame(0.0, $summary[$this->memberUUID]['ytd_gross'] ?? null);
    $this->assertSame(0.0, $summary[$this->memberUUID]['total_hours'] ?? null);

    $teamSnapshot = TeamEarningsSnapshotBuilder::build($this->businessId, 2026, $this->ownerUUID);
    $this->assertSame([], $teamSnapshot['teamEarningsRows']);
    $this->assertSame(0.0, $teamSnapshot['teamEarningsTotals']['gross']);
  }

  #[Test]
  public function teamEarningsSnapshotExposesCanonicalSiteAndGroupRollups(): void
  {
    $groupId = 'group_export_' . $this->suffix;
    Database::hset(Keys::businessGroup($this->businessId, $groupId), [
      'group_id' => $groupId,
      'business_id' => $this->businessId,
      'name' => 'Field Group',
      'type' => 'manual',
      'status' => 'active',
      'created_at' => '2026-01-01T00:00:00+00:00',
      'updated_at' => '2026-01-01T00:00:00+00:00',
    ]);
    Database::sadd(Keys::businessGroups($this->businessId), $groupId);
    Database::sadd(Keys::businessGroupMembers($this->businessId, $groupId), $this->memberUUID);

    $teamSnapshot = TeamEarningsSnapshotBuilder::build($this->businessId, 2026, $this->ownerUUID);
    $siteRef = $this->ownerUUID . ':' . $this->siteId;

    $this->assertArrayHasKey('orgSiteRefData', $teamSnapshot);
    $this->assertArrayHasKey($siteRef, $teamSnapshot['orgSiteRefData']);
    $this->assertSame($siteRef, $teamSnapshot['orgSiteRefData'][$siteRef]['site_ref']);
    $this->assertSame('Business Site', $teamSnapshot['orgSiteRefData'][$siteRef]['site_name']);
    $this->assertSame(400.0, $teamSnapshot['orgSiteRefData'][$siteRef]['gross']);
    $this->assertSame(1, $teamSnapshot['orgSiteRefData'][$siteRef]['member_count']);

    $this->assertArrayHasKey('businessGroupData', $teamSnapshot);
    $this->assertArrayHasKey($groupId, $teamSnapshot['businessGroupData']);
    $this->assertSame('Field Group', $teamSnapshot['businessGroupData'][$groupId]['name']);
    $this->assertSame(400.0, $teamSnapshot['businessGroupData'][$groupId]['gross']);
    $this->assertSame(8.0, $teamSnapshot['businessGroupData'][$groupId]['hours']);
    $this->assertSame(1, $teamSnapshot['businessGroupData'][$groupId]['site_count']);
  }

  #[Test]
  public function revokeConnectionPurgesProtectedCaches(): void
  {
    $this->seedStaleProtectedCaches();
    $this->assertNotNull(BusinessWorkspaceCache::getMemberWork($this->businessId));
    $this->assertNotNull(BusinessMembersCache::get($this->businessId, 2026));
    $this->assertNotNull(BusinessWorkspaceCache::getTeamEarnings($this->businessId, 2026));

    $result = (new BusinessDiscoveryService())->revokeConnection(
      $this->ownerUUID,
      $this->businessId,
      $this->memberUUID,
    );

    $this->assertTrue($result['success']);
    $this->assertNull(BusinessWorkspaceCache::getMemberWork($this->businessId));
    $this->assertNull(BusinessMembersCache::get($this->businessId, 2026));
    $this->assertNull(BusinessWorkspaceCache::getTeamEarnings($this->businessId, 2026));
  }

  #[Test]
  public function revokeBusinessDataConsentLeavesMembershipActiveAndRevokesProtectedAccess(): void
  {
    $this->seedStaleProtectedCaches();
    $this->assertNotNull(BusinessWorkspaceCache::getMemberWork($this->businessId));
    $this->assertNotNull(BusinessMembersCache::get($this->businessId, 2026));
    $this->assertNotNull(BusinessWorkspaceCache::getTeamEarnings($this->businessId, 2026));

    $result = (new BusinessDiscoveryService())->revokeBusinessDataConsent(
      $this->memberUUID,
      $this->businessId,
    );

    $this->assertTrue($result['success']);
    $this->assertSame(1, $result['data']['revoked_consent_count'] ?? null);
    $this->assertSame(1, $result['data']['revoked_wrap_count'] ?? null);

    $connection = Database::hgetall(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberUUID);
    $this->assertSame(BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE, $connection['status'] ?? null);

    $consent = Database::hgetall(Keys::businessConsent($this->consentId));
    $this->assertSame(BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED, $consent['status'] ?? null);

    $wrap = Database::hgetall(Keys::businessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->memberUUID,
      $this->credentialId,
    ));
    $this->assertSame(BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED, $wrap['status'] ?? null);
    $this->assertNull(BusinessWorkspaceCache::getMemberWork($this->businessId));
    $this->assertNull(BusinessMembersCache::get($this->businessId, 2026));
    $this->assertNull(BusinessWorkspaceCache::getTeamEarnings($this->businessId, 2026));
  }

  #[Test]
  public function grantBusinessDataConsentRecordsConsentWhenSecureWrappingIsDisabled(): void
  {
    SystemConfig::set('business_shared_encryption_enabled', false);
    $this->removeSeedConsentAndWrap();

    $result = (new BusinessDiscoveryService())->grantBusinessDataConsent(
      $this->memberUUID,
      $this->businessId,
      [
        'consent_acknowledged' => '1',
        'consent_version' => 'v1',
        'disclaimer_text' => 'No consent, no protected reports.',
      ],
    );

    $this->assertTrue($result['success']);
    $this->assertTrue($result['data']['protected_access_ready'] ?? false);
    $this->assertFalse($result['data']['secure_bootstrap_required'] ?? true);

    $consentId = (string) ($result['data']['consent_id'] ?? '');
    $this->assertNotSame('', $consentId);
    $consent = Database::hgetall(Keys::businessConsent($consentId));
    $this->assertSame($this->businessId, $consent['business_id'] ?? null);
    $this->assertSame($this->memberUUID, $consent['user_uuid'] ?? null);
    $this->assertSame(BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE, $consent['status'] ?? null);
  }

  #[Test]
  public function grantBusinessDataConsentBootstrapsCurrentV1WrapWhenSecureWrappingIsEnabled(): void
  {
    $this->removeSeedConsentAndWrap();
    Database::hset(Keys::USER . ':' . $this->memberUUID . ':passkey_wrapped_deks', [
      $this->credentialId => 'wrapped-dek',
    ]);

    $result = (new BusinessDiscoveryService())->grantBusinessDataConsent(
      $this->memberUUID,
      $this->businessId,
      [
        'consent_acknowledged' => '1',
        'consent_version' => 'v1',
        'disclaimer_text' => 'No consent, no protected reports.',
      ],
    );

    $this->assertTrue($result['success']);
    $this->assertTrue($result['data']['protected_access_ready'] ?? false);
    $this->assertTrue($result['data']['secure_bootstrap_required'] ?? false);

    $wrap = Database::hgetall(Keys::businessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      BusinessDiscoveryService::BUSINESS_DEK_VERSION_CURRENT,
      $this->memberUUID,
      $this->credentialId,
    ));
    $this->assertSame(BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE, $wrap['status'] ?? null);
    $this->assertSame(BusinessDiscoveryService::BUSINESS_DEK_VERSION_CURRENT, $wrap['key_version'] ?? null);
    $this->assertStringEndsWith('v1', (string) ($wrap['dek_id'] ?? ''));
    $this->assertStringNotContainsString('vv1', (string) ($wrap['dek_id'] ?? ''));
  }

  private function seedStaleProtectedCaches(): void
  {
    $workKey = Keys::WORK . ':' . $this->memberUUID . ':2026-01-02:' . $this->siteId;
    $entry = Database::hgetall($workKey);

    BusinessWorkspaceCache::putMemberWork($this->businessId, [
      $this->memberUUID => [$workKey => $entry],
    ]);
    BusinessMembersCache::put($this->businessId, 2026, [
      $this->memberUUID => [
        'ytd_gross' => 9999.99,
        'total_hours' => 999.0,
        'reg_hours' => 999.0,
        'ot_hours' => 0.0,
        'trailing_baseline' => 999.0,
      ],
    ]);
    BusinessWorkspaceCache::putTeamEarnings($this->businessId, 2026, [
      'teamEarningsRows' => [[
        'member_uuid' => $this->memberUUID,
        'name' => 'Stale Member',
        'gross' => 9999.99,
      ]],
      'teamEarningsTotals' => ['reg_hours' => 999.0, 'ot_hours' => 0.0, 'gross' => 9999.99, 'net' => 9999.99],
      'teamSiteMatchStats' => ['match_owner_and_site' => 1, 'match_unique_site_id' => 0, 'match_site_name' => 0, 'included_unlinked' => 0],
      'teamSiteDropSamples' => [],
      'teamSiteFallbackWarn' => false,
      'teamUnlinkedOnlyWarn' => false,
      'teamUnlinkedOnlyCount' => 0,
      'orgSiteData' => [],
      'memberLoaTotals' => [],
      'memberWeeklyH' => [],
      'memberDays' => [],
    ]);
  }

  private function removeSeedConsentAndWrap(): void
  {
    Database::unlink(Keys::businessConsent($this->consentId));
    Database::srem(Keys::businessConsentsByBusiness($this->businessId), $this->consentId);
    Database::srem(Keys::businessConsentsByUser($this->memberUUID), $this->consentId);
    Database::unlink(Keys::businessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->memberUUID,
      $this->credentialId,
    ));
  }

  /**
   * @return list<string>
   */
  private function businessExportAuditEventTypes(): array
  {
    $types = [];
    foreach (Database::smembers(Keys::BUSINESS_AUDIT . ':' . $this->businessId) as $eventId) {
      $event = Database::hgetall(Keys::BUSINESS_AUDIT_EVENT . ':' . (string) $eventId);
      $eventType = (string) ($event['event_type'] ?? '');
      if (str_starts_with($eventType, 'business.member.report.export.')) {
        $types[] = $eventType;
      }
    }

    sort($types);

    return $types;
  }

  private function clearBusinessAuditEvents(): void
  {
    $auditKey = Keys::BUSINESS_AUDIT . ':' . $this->businessId;
    foreach (Database::smembers($auditKey) as $eventId) {
      Database::unlink(Keys::BUSINESS_AUDIT_EVENT . ':' . (string) $eventId);
    }
    Database::unlink($auditKey);
  }

  private function seedBusiness(): void
  {
    Database::hset(Keys::USER . ':' . $this->ownerUUID, [
      'user_uuid' => $this->ownerUUID,
      'email' => $this->ownerUUID . '@example.com',
      'full_name' => 'Owner Export',
      'email_verified' => '1',
      'auth_level' => '1',
    ]);
    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID, [
      'tier' => 'business',
      'status' => 'active',
    ]);
    Database::hset(Keys::USER . ':' . $this->memberUUID, [
      'user_uuid' => $this->memberUUID,
      'email' => $this->memberUUID . '@example.com',
      'full_name' => 'Member Export',
      'email_verified' => '1',
      'auth_level' => '1',
      'province' => 'AB',
    ]);

    Database::hset(Keys::BUSINESS . ':' . $this->businessId, [
      'business_id' => $this->businessId,
      'owner_uuid' => $this->ownerUUID,
      'status' => 'active',
    ]);

    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->ownerUUID, [
      'business_id' => $this->businessId,
      'user_uuid' => $this->ownerUUID,
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'role' => 'owner',
    ]);

    Database::hset(Keys::BUSINESS_CONNECTION . ':' . $this->businessId . ':' . $this->memberUUID, [
      'business_id' => $this->businessId,
      'user_uuid' => $this->memberUUID,
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'role' => 'contributor',
      'scopes' => 'work.read,work.scope.business',
    ]);

    Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $this->businessId, $this->memberUUID);
    Database::sadd(Keys::BUSINESS_USER . ':' . $this->memberUUID, $this->businessId);

    Database::hset(Keys::SITE . ':' . $this->ownerUUID . ':' . $this->siteId, [
      'site_id' => $this->siteId,
      'site_name' => 'Business Site',
      'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS,
      'business_managed' => '1',
      'business_id' => $this->businessId,
    ]);

    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $this->ownerUUID . ':' . $this->siteId);
  }

  private function seedConsentAndWrap(): void
  {
    Database::hset(Keys::businessConsent($this->consentId), [
      'consent_id' => $this->consentId,
      'business_id' => $this->businessId,
      'user_uuid' => $this->memberUUID,
      'status' => BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      'accepted_at' => date('c'),
    ]);
    Database::sadd(Keys::businessConsentsByBusiness($this->businessId), $this->consentId);
    Database::sadd(Keys::businessConsentsByUser($this->memberUUID), $this->consentId);

    $store = (new BusinessEncryptionService())->storeBusinessDekWrap(
      $this->businessId,
      BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
      'v1',
      $this->memberUUID,
      $this->credentialId,
      'wrapped-dek',
      $this->consentId,
      'hkdf-passkey-v1',
      $this->dekId,
    );

    $this->assertTrue($store['success']);
  }

  private function seedWorkEntry(): void
  {
    Database::hset(Keys::WORK . ':' . $this->memberUUID . ':2026-01-02:' . $this->siteId, [
      'date' => '2026-01-02',
      'site_id' => $this->siteId,
      'site_name' => 'Business Site',
      'site_owner_uuid' => $this->ownerUUID,
      'wage' => '50',
      'hours' => '8',
      'regular_hours' => '8',
      'overtime_hours' => '0',
      'gross' => '400',
      'net' => '320',
      'encrypted_blob' => $this->encryptedBlob(),
    ]);
  }

  private function encryptedBlob(): string
  {
    return base64_encode((string) json_encode([
      'ciphertext' => base64_encode('ciphertext'),
      'nonce' => base64_encode('nonce'),
      'aad' => 'aad',
      'meta' => [
        'encryption_mode' => 'business',
        'business_id' => $this->businessId,
        'segment' => BusinessDiscoveryService::BUSINESS_DEK_SEGMENT_CURRENT_PERIOD,
        'key_version' => 'v1',
        'dek_id' => $this->dekId,
        'needs_rewrap' => 'false',
      ],
    ]));
  }
}
