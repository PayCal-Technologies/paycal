<?php declare(strict_types=1);

/**
 * One-shot dev consolidation: Edmonton Industrial Consultants only.
 *
 * Usage: php scripts/consolidate_eic_only.php [--dry-run]
 */

require_once __DIR__ . '/../html/config.php';

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

$dryRun = in_array('--dry-run', $argv, true);
$owner = 'Uf06e8b53';
$targetId = 'ORG498fa7662f3ac4b591';
$personalId = 'ORG6d20e649f40a2e8d5b';
$siteRefs = ['Uf06e8b53:S5047F394D', 'Uf06e8b53:SE0498C9B7'];

$fakeMembers = [
  ['uuid' => 'fade000100000000000000000000001', 'name' => 'Tom Jones', 'role' => 'member', 'scopes' => 'work.read,sites.read'],
  ['uuid' => 'fade000200000000000000000000002', 'name' => 'Sally Reid', 'role' => 'member', 'scopes' => 'work.read,sites.read'],
  ['uuid' => 'fade000300000000000000000000003', 'name' => 'Harry Styles', 'role' => 'coordinator', 'scopes' => 'work.read,sites.read,sites.write,org.read,org.write,access.manage'],
  ['uuid' => 'fade000400000000000000000000004', 'name' => 'Jack Mah', 'role' => 'contributor', 'scopes' => 'work.read,sites.read,sites.write,org.read'],
  ['uuid' => 'fade000500000000000000000000005', 'name' => 'Frank Gallagher', 'role' => 'member', 'scopes' => 'work.read,sites.read'],
  ['uuid' => 'fade000600000000000000000000006', 'name' => 'Lucy Chen', 'role' => 'viewer', 'scopes' => 'work.read,org.read'],
  ['uuid' => 'fade000700000000000000000000007', 'name' => 'Morgan Freeman', 'role' => 'coordinator', 'scopes' => 'work.read,sites.read,sites.write,org.read,org.write,access.manage'],
  ['uuid' => 'fade000800000000000000000000008', 'name' => 'Julia Gulia', 'role' => 'member', 'scopes' => 'work.read,sites.read'],
  ['uuid' => 'fade000900000000000000000000009', 'name' => 'Suzanne Summers', 'role' => 'member', 'scopes' => 'work.read,sites.read'],
  ['uuid' => 'fade000a0000000000000000000000a', 'name' => 'Carol Johnson', 'role' => 'member', 'scopes' => 'work.read,sites.read'],
];

function purgeBusiness(string $businessId, string $ownerUuid, bool $dryRun): void
{
  if ($dryRun) {
    echo "[dry-run] would purge business {$businessId}\n";
    return;
  }

  foreach (Database::smembers(Keys::BUSINESS_AUDIT . ':' . $businessId) as $eventId) {
    Database::unlink(Keys::BUSINESS_AUDIT_EVENT . ':' . (string) $eventId);
  }
  Database::unlink(Keys::BUSINESS_AUDIT . ':' . $businessId);

  foreach (Database::smembers(Keys::BUSINESS_MEMBERS . ':' . $businessId) as $memberUuid) {
    $memberUuid = (string) $memberUuid;
    Database::unlink(Keys::BUSINESS_RELATIONSHIP . ':' . $businessId . ':' . $memberUuid);
    Database::srem(Keys::BUSINESS_USER . ':' . $memberUuid, $businessId);
    Database::srem(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $memberUuid, $businessId);
  }

  Database::unlink(Keys::BUSINESS_MEMBERS . ':' . $businessId);
  Database::unlink(Keys::BUSINESS_RELATIONSHIPS . ':' . $businessId);
  Database::unlink(Keys::BUSINESS_PENDING . ':' . $businessId);
  Database::unlink(Keys::BUSINESS_SETTINGS . ':' . $businessId);
  Database::unlink(Keys::BUSINESS_SITE . ':' . $businessId);
  Database::unlink(Keys::BUSINESS . ':' . $businessId);
  Database::srem(Keys::BUSINESS_OWNER . ':' . $ownerUuid, $businessId);
  Database::srem(Keys::BUSINESS_USER . ':' . $ownerUuid, $businessId);
  Database::srem(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $ownerUuid, $businessId);

  foreach (Database::scanKeys('telemetry:org:dek:auto_bootstrap:' . $businessId . ':*') as $key) {
    Database::unlink((string) $key);
  }
  foreach (Database::scanKeys('telemetry:business:dek:auto_bootstrap:' . $businessId . ':*') as $key) {
    Database::unlink((string) $key);
  }
}

echo ($dryRun ? '[dry-run] ' : '') . "Consolidating to {$targetId}\n";

$targetSettings = Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $targetId);
$personalSettings = Database::hgetall(Keys::BUSINESS_SETTINGS . ':' . $personalId);
$mergedSettings = $personalSettings !== [] ? array_merge($targetSettings, $personalSettings) : $targetSettings;
$mergedSettings['last_updated_at'] = date('c');
$mergedSettings['last_updated_by'] = $owner;

$targetBiz = Database::hgetall(Keys::BUSINESS . ':' . $targetId);
$normalizedBiz = [
  'business_id' => $targetId,
  'business_type' => 'shared',
  'name' => 'Edmonton Industrial Consultants',
  'owner_uuid' => $owner,
  'status' => (string) ($targetBiz['status'] ?? 'active'),
  'created_at' => (string) ($targetBiz['created_at'] ?? date('c')),
  'updated_at' => date('c'),
];

if (!$dryRun) {
  Database::hset(Keys::BUSINESS . ':' . $targetId, $normalizedBiz);
  Database::hdel(Keys::BUSINESS . ':' . $targetId, 'organization_id', 'organization_type');
  if ($mergedSettings !== []) {
    Database::hset(Keys::BUSINESS_SETTINGS . ':' . $targetId, $mergedSettings);
  }

  foreach ($siteRefs as $siteRef) {
    Database::sadd(Keys::BUSINESS_SITE . ':' . $targetId, $siteRef);
    [$siteOwner, $siteId] = explode(':', $siteRef, 2);
    Database::hset(Keys::SITE . ':' . $siteOwner . ':' . $siteId, ['business_id' => $targetId]);
  }

  $timestamp = date('c');
  Database::hset(Keys::BUSINESS_RELATIONSHIP . ':' . $targetId . ':' . $owner, [
    'business_id' => $targetId,
    'user_uuid' => $owner,
    'role' => 'owner',
    'status' => 'active',
    'scopes' => 'all',
    'invited_by' => $owner,
    'created_at' => (string) ($targetBiz['created_at'] ?? $timestamp),
    'accepted_at' => (string) ($targetBiz['created_at'] ?? $timestamp),
    'updated_at' => $timestamp,
  ]);
  Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $targetId, $owner);
  Database::sadd(Keys::BUSINESS_RELATIONSHIPS . ':' . $targetId, $owner);
  Database::sadd(Keys::BUSINESS_USER . ':' . $owner, $targetId);
  Database::sadd(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $owner, $targetId);
  Database::sadd(Keys::BUSINESS_OWNER . ':' . $owner, $targetId);

  foreach ($fakeMembers as $member) {
    $uuid = $member['uuid'];
    Database::hset(Keys::BUSINESS_RELATIONSHIP . ':' . $targetId . ':' . $uuid, [
      'business_id' => $targetId,
      'user_uuid' => $uuid,
      'role' => $member['role'],
      'status' => 'active',
      'scopes' => $member['scopes'],
      'invited_by' => $owner,
      'created_at' => $timestamp,
      'accepted_at' => $timestamp,
      'updated_at' => $timestamp,
    ]);
    Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $targetId, $uuid);
    Database::sadd(Keys::BUSINESS_RELATIONSHIPS . ':' . $targetId, $uuid);
    Database::sadd(Keys::BUSINESS_USER . ':' . $uuid, $targetId);
    Database::sadd(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $uuid, $targetId);
  }

  purgeBusiness($personalId, $owner, false);
  echo "Purged personal business {$personalId}\n";
} else {
  echo "[dry-run] would normalize {$targetId}, merge settings, relink sites, attach members, purge {$personalId}\n";
}
