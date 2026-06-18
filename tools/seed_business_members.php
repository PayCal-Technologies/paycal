#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * seed_business_members.php — Attach Fake Users to a Development Business
 *
 * Purpose: Seeds all 10 fake users (from seed_fake_users.php) as active members
 *          of a target business. Mirrors BusinessDiscoveryService::setRelationship()
 *          write semantics: relationship hash + forward SET + reverse SET.
 *          Safe to re-run — existing relationships are overwritten idempotently.
 *
 * Business role mapping (system auth_level → business role):
 *   ADMIN   → coordinator  (elevated: settings + access + audit)
 *   MANAGER → coordinator  (first manager) / contributor (second manager)
 *   AUDITOR → viewer       (read-only, suitable for compliance auditor)
 *   USER    → member       (baseline self-scoped access)
 *
 * Usage:
 *   php tools/seed_business_members.php <business_id>
 *   php tools/seed_business_members.php <business_id> --dry-run
 *   php tools/seed_business_members.php <business_id> --drop
 *
 * Example:
 *   php tools/seed_business_members.php ORG498fa7662f3ac4b591
 *
 * Why here: Lives alongside seed_fake_users.php in tools/. Dev/QA only.
 *
 * PHP version 8.4
 */

require_once __DIR__ . '/../html/config.php';

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

$businessId = $argv[1] ?? '';
$dryRun = in_array('--dry-run', $argv, true);
$drop = in_array('--drop', $argv, true);

if ($businessId === '') {
  fwrite(STDERR, "Usage: php tools/seed_business_members.php <business_id> [--dry-run|--drop]\n");
  fwrite(STDERR, "Example: php tools/seed_business_members.php ORG498fa7662f3ac4b591\n");
  exit(1);
}

$businessData = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
if (empty($businessData)) {
  fwrite(STDERR, "Error: business '{$businessId}' not found in Redis.\n");
  exit(1);
}

$businessName = (string) ($businessData['name'] ?? $businessId);

/** @var array<int, array<string, string>> */
$members = [
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

if ($dryRun) {
  echo "── Dry-run: would attach to business '{$businessName}' ({$businessId}) ────────\n";
  foreach ($members as $m) {
    printf("  %-22s  %-12s  %s\n", $m['name'], $m['role'], $m['scopes']);
  }
  exit(0);
}

if ($drop) {
  echo "── Removing fake users from business '{$businessName}' ({$businessId}) ────────\n";
  foreach ($members as $m) {
    $relKey = Keys::BUSINESS_RELATIONSHIP . ':' . $businessId . ':' . $m['uuid'];
    Database::unlink($relKey);
    Database::srem(Keys::BUSINESS_MEMBERS . ':' . $businessId, $m['uuid']);
    Database::srem(Keys::BUSINESS_RELATIONSHIPS . ':' . $businessId, $m['uuid']);
    Database::srem(Keys::BUSINESS_PENDING . ':' . $businessId, $m['uuid']);
    Database::srem(Keys::BUSINESS_USER . ':' . $m['uuid'], $businessId);
    Database::srem(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $m['uuid'], $businessId);
    echo "  removed  {$m['name']}\n";
  }
  echo "Done.\n";
  exit(0);
}

echo "── Attaching fake users to business '{$businessName}' ({$businessId}) ─────────\n";

$timestamp = date('c');
$ownerUUID = (string) ($businessData['owner_uuid'] ?? '');

foreach ($members as $m) {
  $uuid = $m['uuid'];
  $relKey = Keys::BUSINESS_RELATIONSHIP . ':' . $businessId . ':' . $uuid;
  $existed = Database::exists($relKey);

  Database::hset($relKey, [
    'business_id' => $businessId,
    'user_uuid' => $uuid,
    'role' => $m['role'],
    'status' => 'active',
    'scopes' => $m['scopes'],
    'invited_by' => $ownerUUID,
    'created_at' => $timestamp,
    'accepted_at' => $timestamp,
    'updated_at' => $timestamp,
  ]);

  Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $businessId, $uuid);
  Database::sadd(Keys::BUSINESS_RELATIONSHIPS . ':' . $businessId, $uuid);
  Database::sadd(Keys::BUSINESS_USER . ':' . $uuid, $businessId);
  Database::sadd(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $uuid, $businessId);

  $status = $existed ? 'updated' : 'added  ';
  printf("  %s  %-22s  %-12s  %s\n", $status, $m['name'], $m['role'], $m['scopes']);
}

echo "\nAttached " . count($members) . " members to '{$businessName}'.\n";
