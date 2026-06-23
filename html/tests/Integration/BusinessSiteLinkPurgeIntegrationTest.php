<?php declare(strict_types=1);

namespace Tests\Integration;

use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\BusinessSitesGridRenderer;
use PayCal\Domain\BusinessWorkspaceCache;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\Enums\SiteStatus;
use PayCal\Domain\SitesService;
use PayCal\Domain\UserRepository;
use PHPUnit\Framework\TestCase;

final class BusinessSiteLinkPurgeIntegrationTest extends TestCase
{
  private BusinessDiscoveryService $service;
  private string $ownerUUID;
  private string $ownerEmail;
  private string $businessId = '';
  private string $siteId = '';

  protected function setUp(): void
  {
    parent::setUp();

    $this->service = new BusinessDiscoveryService();
    $suffix = bin2hex(random_bytes(6));
    $this->ownerUUID = 'site-link-owner-' . $suffix;
    $this->ownerEmail = 'site-link-owner-' . $suffix . '@example.com';
    $this->siteId = 'SORPHAN' . strtoupper(substr($suffix, 0, 4));

    $this->seedUser($this->ownerUUID, $this->ownerEmail);

    Database::hset(Keys::USER_SUBSCRIPTION . ':' . $this->ownerUUID, [
      'tier' => 'business',
      'status' => 'active',
    ]);

    $create = $this->service->createBusiness($this->ownerUUID, 'Site Link Purge Org ' . $suffix, [
      'business_type' => 'shared',
    ]);
    $this->assertTrue($create['success'], (string) ($create['message'] ?? 'Business creation failed.'));
    $this->businessId = (string) ($create['data']['business_id'] ?? '');
    $this->assertNotSame('', $this->businessId);
  }

  protected function tearDown(): void
  {
    if ($this->businessId !== '') {
      Database::unlink(Keys::BUSINESS_SITE . ':' . $this->businessId);
      Database::unlink(Keys::BUSINESS_SITE_SETTINGS . ':' . $this->businessId . ':' . $this->ownerUUID . ':' . $this->siteId);
    }

    if ($this->ownerUUID !== '' && $this->siteId !== '') {
      Database::unlink(Keys::SITE . ':' . $this->ownerUUID . ':' . $this->siteId);
    }

    if ($this->ownerUUID !== '') {
      $this->cleanupUser($this->ownerUUID, $this->ownerEmail);
    }

    parent::tearDown();
  }

  private function seedUser(string $userUUID, string $email): void
  {
    Database::hset(Keys::USER . ':' . $userUUID, [
      'user_uuid' => $userUUID,
      'email' => $email,
      'full_name' => 'Integration Test User',
      'email_verified' => '1',
      'auth_level' => AuthLevel::USER->value,
    ]);

    UserRepository::setUserEmail($userUUID, $email);
  }

  private function cleanupUser(string $userUUID, string $email): void
  {
    Database::unlink(Keys::USER . ':' . $userUUID);
    Database::unlink(Keys::USER_SUBSCRIPTION . ':' . $userUUID);
    Database::unlink(Keys::EMAIL . ':' . $email);
    Database::unlink(Keys::EMAIL . $email);
  }

  public function testListBusinessSitesPrunesOrphanSiteRefs(): void
  {
    $siteRef = $this->ownerUUID . ':' . $this->siteId;
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $siteRef);

    $result = $this->service->listBusinessSites($this->ownerUUID, $this->businessId);

    $this->assertTrue($result['success']);
    $this->assertSame([], $result['data']['sites']);
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_SITE . ':' . $this->businessId, $siteRef));
  }

  public function testArchiveLinkedPersonalSiteLeavesActiveBusinessGridAfterCacheRefresh(): void
  {
    $siteRef = $this->ownerUUID . ':' . $this->siteId;
    Database::hset(Keys::SITE . ':' . $this->ownerUUID . ':' . $this->siteId, [
      'site_name' => 'Edmonton Oil',
      'wage' => '45',
      'living_out_allowance' => '0',
      'travel_hours' => '0',
      'province' => 'AB',
      'status' => SiteStatus::ACTIVE->value,
      'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_LINKED,
      'id' => $this->siteId,
    ]);
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $siteRef);

    $warm = $this->service->listBusinessSites($this->ownerUUID, $this->businessId);
    $this->assertTrue($warm['success']);
    $this->assertCount(1, $warm['data']['sites']);
    $this->assertNotNull(BusinessWorkspaceCache::getSitesRaw($this->businessId));

    $archiveResult = (new SitesService())->delete($this->ownerUUID, $this->siteId);
    $this->assertTrue($archiveResult['success']);
    $this->assertSame(SiteStatus::ARCHIVED->value, Database::hget(Keys::SITE . ':' . $this->ownerUUID . ':' . $this->siteId, 'status'));
    $this->assertSame(1, Database::sismember(Keys::BUSINESS_SITE . ':' . $this->businessId, $siteRef));

    $renderer = new BusinessSitesGridRenderer();
    $activeGrid = $renderer->renderForBusiness($this->ownerUUID, $this->businessId, [
      'status' => SiteStatus::ACTIVE->value,
    ]);
    $archivedGrid = $renderer->renderForBusiness($this->ownerUUID, $this->businessId, [
      'status' => SiteStatus::ARCHIVED->value,
    ]);

    $this->assertTrue($activeGrid['success']);
    $this->assertStringNotContainsString('Edmonton Oil', $activeGrid['html']);

    $this->assertTrue($archivedGrid['success']);
    $this->assertStringContainsString('Edmonton Oil', $archivedGrid['html']);
  }

  public function testArchiveBusinessSiteKeepsLinkAndMovesToArchivedGrid(): void
  {
    $siteRef = $this->ownerUUID . ':' . $this->siteId;
    Database::hset(Keys::SITE . ':' . $this->ownerUUID . ':' . $this->siteId, [
      'site_name' => 'Archive From Business Editor',
      'wage' => '45',
      'living_out_allowance' => '0',
      'travel_hours' => '0',
      'province' => 'AB',
      'status' => SiteStatus::ACTIVE->value,
      'ownership_scope' => BusinessDiscoveryService::BUSINESS_SITE_OWNERSHIP_BUSINESS,
      'business_managed' => '1',
      'id' => $this->siteId,
    ]);
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $siteRef);

    $archiveResult = $this->service->archiveBusinessSite(
      $this->ownerUUID,
      $this->businessId,
      $this->ownerUUID,
      $this->siteId,
    );

    $this->assertTrue($archiveResult['success'], (string) ($archiveResult['message'] ?? 'Archive failed.'));
    $this->assertSame(SiteStatus::ARCHIVED->value, Database::hget(Keys::SITE . ':' . $this->ownerUUID . ':' . $this->siteId, 'status'));
    $this->assertSame(SiteStatus::ARCHIVED->value, Database::hget(Keys::BUSINESS_SITE_SETTINGS . ':' . $this->businessId . ':' . $siteRef, 'site_status'));
    $this->assertSame(1, Database::sismember(Keys::BUSINESS_SITE . ':' . $this->businessId, $siteRef));

    $renderer = new BusinessSitesGridRenderer();
    $activeGrid = $renderer->renderForBusiness($this->ownerUUID, $this->businessId, [
      'status' => SiteStatus::ACTIVE->value,
    ]);
    $archivedGrid = $renderer->renderForBusiness($this->ownerUUID, $this->businessId, [
      'status' => SiteStatus::ARCHIVED->value,
    ]);

    $this->assertTrue($activeGrid['success']);
    $this->assertStringNotContainsString('Archive From Business Editor', $activeGrid['html']);
    $this->assertTrue($archivedGrid['success']);
    $this->assertStringContainsString('Archive From Business Editor', $archivedGrid['html']);
  }

  public function testPermanentDeleteRemovesBusinessSiteLinks(): void
  {
    $siteRef = $this->ownerUUID . ':' . $this->siteId;
    Database::hset(Keys::SITE . ':' . $this->ownerUUID . ':' . $this->siteId, [
      'site_name' => 'Purge Test Site',
      'wage' => '45',
      'living_out_allowance' => '0',
      'travel_hours' => '0',
      'province' => 'AB',
      'status' => 'archived',
      'id' => $this->siteId,
    ]);
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $siteRef);

    $deleteResult = (new SitesService())->permanentDelete($this->ownerUUID, $this->siteId);

    $this->assertTrue($deleteResult['success']);
    $this->assertFalse(Database::exists(Keys::SITE . ':' . $this->ownerUUID . ':' . $this->siteId));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_SITE . ':' . $this->businessId, $siteRef));
  }

  public function testPermanentDeleteBusinessSiteRemovesBusinessSiteLinks(): void
  {
    $siteRef = $this->ownerUUID . ':' . $this->siteId;
    $settingsKey = Keys::BUSINESS_SITE_SETTINGS . ':' . $this->businessId . ':' . $siteRef;
    Database::hset(Keys::SITE . ':' . $this->ownerUUID . ':' . $this->siteId, [
      'site_name' => 'Business Editor Purge Site',
      'wage' => '45',
      'living_out_allowance' => '0',
      'travel_hours' => '0',
      'province' => 'AB',
      'status' => SiteStatus::ARCHIVED->value,
      'id' => $this->siteId,
    ]);
    Database::sadd(Keys::BUSINESS_SITE . ':' . $this->businessId, $siteRef);
    Database::hset($settingsKey, [
      'site_status' => SiteStatus::ARCHIVED->value,
      'budget_amount' => '5000',
    ]);

    $deleteResult = $this->service->permanentDeleteBusinessSite(
      $this->ownerUUID,
      $this->businessId,
      $this->ownerUUID,
      $this->siteId,
    );

    $this->assertTrue($deleteResult['success'], (string) ($deleteResult['message'] ?? 'Permanent delete failed.'));
    $this->assertFalse(Database::exists(Keys::SITE . ':' . $this->ownerUUID . ':' . $this->siteId));
    $this->assertSame(0, Database::sismember(Keys::BUSINESS_SITE . ':' . $this->businessId, $siteRef));
    $this->assertFalse(Database::exists($settingsKey));
  }
}
