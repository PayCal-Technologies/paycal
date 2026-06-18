#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * seed_famous_actor_members.php — 90 Famous-Actor Fake Users + Business Membership
 *
 * Purpose: Seeds 90 additional fake users (phonetic variations of famous actor names)
 *          with European locale diversity, then attaches them to a target business
 *          workspace. Mirrors seed_fake_users.php user writes and
 *          seed_business_members.php relationship semantics.
 *
 * Prerequisites:
 *   - Local Redis running (html/config.php connection)
 *   - Target business exists (default: Edmonton Industrial Consultants)
 *   - seed_fake_users.php optional but recommended for the original 10 users
 *
 * Default password for all seeded users: FakeUser2026!
 *
 * Business roles (no owners): member, viewer, contributor, coordinator
 *
 * Usage:
 *   php tools/seed_famous_actor_members.php
 *   php tools/seed_famous_actor_members.php ORG498fa7662f3ac4b591
 *   php tools/seed_famous_actor_members.php ORG498fa7662f3ac4b591 --dry-run
 *   php tools/seed_famous_actor_members.php ORG498fa7662f3ac4b591 --drop
 *
 * After seeding users, generate 2026 work history:
 *   php tools/seed_work_history_2026.php ORG498fa7662f3ac4b591
 *
 * PHP version 8.4
 */

require_once __DIR__ . '/../html/config.php';

use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;
use PayCal\Domain\Enums\AuthLevel;
use PayCal\Domain\UserRepository;

const SEED_PASSWORD = 'FakeUser2026!';
const DEFAULT_BUSINESS_ID = 'ORG498fa7662f3ac4b591';
const ACTOR_UUID_START = 0x11; // fade0011 … fade006a (90 users)

/** @var list<array{first: string, last: string, inspiration: string, country: string}> */
$actorBases = require __DIR__ . '/famous_actor_users_data.php';

if (count($actorBases) !== 90) {
  fwrite(STDERR, 'Error: famous_actor_users_data.php must define exactly 90 users (got ' . count($actorBases) . ").\n");
  exit(1);
}

$businessId = $argv[1] ?? DEFAULT_BUSINESS_ID;
if (str_starts_with($businessId, '--')) {
  $businessId = DEFAULT_BUSINESS_ID;
}
$dryRun = in_array('--dry-run', $argv, true);
$drop   = in_array('--drop', $argv, true);

/**
 * European locale profiles keyed by ISO country code.
 *
 * @var array<string, array{address_line1: string, address_city: string, address_postal: string, province: string, timezone: string, currency: string, phone_prefix: string}>
 */
$locales = [
  'GB' => ['address_line1' => '14 Baker Street',       'address_city' => 'London',     'address_postal' => 'NW1 6XE', 'province' => 'ENG', 'timezone' => 'Europe/London',    'currency' => 'GBP', 'phone_prefix' => '+44 20'],
  'FR' => ['address_line1' => '8 Rue de Rivoli',       'address_city' => 'Paris',      'address_postal' => '75004',   'province' => 'IDF', 'timezone' => 'Europe/Paris',     'currency' => 'EUR', 'phone_prefix' => '+33 1'],
  'DE' => ['address_line1' => '22 Unter den Linden',   'address_city' => 'Berlin',     'address_postal' => '10117',   'province' => 'BE',  'timezone' => 'Europe/Berlin',    'currency' => 'EUR', 'phone_prefix' => '+49 30'],
  'ES' => ['address_line1' => '5 Calle Mayor',         'address_city' => 'Madrid',     'address_postal' => '28013',   'province' => 'MD',  'timezone' => 'Europe/Madrid',    'currency' => 'EUR', 'phone_prefix' => '+34 91'],
  'IT' => ['address_line1' => '12 Via del Corso',      'address_city' => 'Rome',       'address_postal' => '00186',   'province' => 'RM',  'timezone' => 'Europe/Rome',      'currency' => 'EUR', 'phone_prefix' => '+39 06'],
  'NL' => ['address_line1' => '48 Damrak',             'address_city' => 'Amsterdam',  'address_postal' => '1012 JS', 'province' => 'NH',  'timezone' => 'Europe/Amsterdam', 'currency' => 'EUR', 'phone_prefix' => '+31 20'],
  'SE' => ['address_line1' => '3 Drottninggatan',      'address_city' => 'Stockholm',  'address_postal' => '111 51',  'province' => 'AB',  'timezone' => 'Europe/Stockholm', 'currency' => 'SEK', 'phone_prefix' => '+46 8'],
  'DK' => ['address_line1' => '15 Nyhavn',             'address_city' => 'Copenhagen', 'address_postal' => '1051',    'province' => '84',  'timezone' => 'Europe/Copenhagen','currency' => 'DKK', 'phone_prefix' => '+45 33'],
  'IE' => ['address_line1' => '21 Grafton Street',     'address_city' => 'Dublin',     'address_postal' => 'D02 X285','province' => 'D',   'timezone' => 'Europe/Dublin',    'currency' => 'EUR', 'phone_prefix' => '+353 1'],
  'BE' => ['address_line1' => '9 Grand Place',         'address_city' => 'Brussels',   'address_postal' => '1000',    'province' => 'BRU', 'timezone' => 'Europe/Brussels',  'currency' => 'EUR', 'phone_prefix' => '+32 2'],
  'AT' => ['address_line1' => '6 Kärntner Straße',     'address_city' => 'Vienna',     'address_postal' => '1010',    'province' => '9',   'timezone' => 'Europe/Vienna',    'currency' => 'EUR', 'phone_prefix' => '+43 1'],
  'US' => ['address_line1' => '220 W 42nd Street',     'address_city' => 'New York',   'address_postal' => '10036',   'province' => 'NY',  'timezone' => 'America/New_York', 'currency' => 'USD', 'phone_prefix' => '+1 212'],
  'CA' => ['address_line1' => '100 King St W',         'address_city' => 'Toronto',    'address_postal' => 'M5X 1A9',  'province' => 'ON',  'timezone' => 'America/Toronto',  'currency' => 'CAD', 'phone_prefix' => '+1 416'],
  'AU' => ['address_line1' => '88 George Street',      'address_city' => 'Sydney',     'address_postal' => '2000',    'province' => 'NSW', 'timezone' => 'Australia/Sydney', 'currency' => 'AUD', 'phone_prefix' => '+61 2'],
  'NZ' => ['address_line1' => '45 Queen Street',       'address_city' => 'Auckland',   'address_postal' => '1010',    'province' => 'AUK', 'timezone' => 'Pacific/Auckland', 'currency' => 'NZD', 'phone_prefix' => '+64 9'],
  'IL' => ['address_line1' => '12 Rothschild Blvd',    'address_city' => 'Tel Aviv',   'address_postal' => '6688101', 'province' => 'TA',  'timezone' => 'Asia/Jerusalem',   'currency' => 'ILS', 'phone_prefix' => '+972 3'],
  'ZA' => ['address_line1' => '33 Long Street',        'address_city' => 'Cape Town',  'address_postal' => '8001',    'province' => 'WC',  'timezone' => 'Africa/Johannesburg','currency' => 'ZAR','phone_prefix' => '+27 21'],
];

$jobTitles = [
  'Field Technician', 'Site Inspector', 'Project Coordinator', 'Safety Officer',
  'Payroll Analyst', 'Equipment Operator', 'Quality Auditor', 'Logistics Clerk',
  'HR Specialist', 'Maintenance Lead', 'Survey Technician', 'Accounts Payable',
  'Warehouse Supervisor', 'Environmental Monitor', 'Training Facilitator',
];

$departments = ['Operations', 'Field Services', 'Finance', 'Compliance', 'Administration', 'Human Resources'];

/** @var list<string> */
$businessRoles = ['member', 'member', 'member', 'viewer', 'contributor', 'coordinator'];

/** @var array<string, array{role: string, scopes: string, auth: AuthLevel}> */
$roleMap = [
  'member'      => ['role' => 'member',      'scopes' => 'work.read,sites.read', 'auth' => AuthLevel::USER],
  'viewer'      => ['role' => 'viewer',      'scopes' => 'work.read,org.read',   'auth' => AuthLevel::AUDITOR],
  'contributor' => ['role' => 'contributor', 'scopes' => 'work.read,sites.read,sites.write,org.read', 'auth' => AuthLevel::MANAGER],
  'coordinator' => ['role' => 'coordinator', 'scopes' => 'work.read,sites.read,sites.write,org.read,org.write,access.manage', 'auth' => AuthLevel::MANAGER],
];

/**
 * @param list<array{first: string, last: string, inspiration: string, country: string}> $bases
 * @return list<array<string, string>>
 */
function buildActorUsers(array $bases, array $locales, array $jobTitles, array $departments, array $businessRoles, array $roleMap): array
{
  $users = [];
  $idx   = ACTOR_UUID_START;

  foreach ($bases as $i => $base) {
    $uuid     = sprintf('fade%04x%019x', $idx, $idx);
    $fullName = $base['first'] . ' ' . $base['last'];
    $slug     = strtolower(preg_replace('/[^a-z0-9]+/i', '.', $base['first'] . '.' . $base['last']) ?? '');
    $slug     = trim($slug, '.');
    $email    = $slug . '@paycal.app';

    $localeKey = $base['country'];
    if (!isset($locales[$localeKey])) {
      $localeKey = 'GB';
    }
    $loc = $locales[$localeKey];

    mt_srand(crc32("phone-{$uuid}"));
    $phoneSuffix = str_pad((string) mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT);
    $phone       = $loc['phone_prefix'] . ' ' . substr($phoneSuffix, 0, 3) . ' ' . substr($phoneSuffix, 3);

    $bizRole = $businessRoles[$i % count($businessRoles)];
    $mapped  = $roleMap[$bizRole];

    $users[] = [
      'uuid'           => $uuid,
      'full_name'      => $fullName,
      'email'          => $email,
      'phone'          => $phone,
      'auth_level'     => $mapped['auth']->value,
      'address_line1'  => $loc['address_line1'],
      'address_city'   => $loc['address_city'],
      'address_postal' => $loc['address_postal'],
      'province'       => $loc['province'],
      'timezone'       => $loc['timezone'],
      'currency'       => $loc['currency'],
      'job_title'      => $jobTitles[$i % count($jobTitles)],
      'department'     => $departments[$i % count($departments)],
      'role'           => $mapped['role'],
      'scopes'         => $mapped['scopes'],
      'inspiration'    => $base['inspiration'],
      'country'        => $base['country'],
    ];

    $idx++;
  }

  return $users;
}

$users = buildActorUsers($actorBases, $locales, $jobTitles, $departments, $businessRoles, $roleMap);

// ── Drop mode ─────────────────────────────────────────────────────────────────

if ($drop) {
  echo "── Dropping famous-actor fake users ───────────────────────────\n";
  foreach ($users as $u) {
    $userKey  = Keys::USER . ':' . $u['uuid'];
    $emailKey = Keys::EMAIL . ':' . $u['email'];
    Database::unlink($userKey);
    Database::unlink($emailKey);

    $relKey = Keys::BUSINESS_RELATIONSHIP . ':' . $businessId . ':' . $u['uuid'];
    Database::unlink($relKey);
    Database::srem(Keys::BUSINESS_MEMBERS . ':' . $businessId, $u['uuid']);
    Database::srem(Keys::BUSINESS_RELATIONSHIPS . ':' . $businessId, $u['uuid']);
    Database::srem(Keys::BUSINESS_PENDING . ':' . $businessId, $u['uuid']);
    Database::srem(Keys::BUSINESS_USER . ':' . $u['uuid'], $businessId);
    Database::srem(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $u['uuid'], $businessId);

    echo "  removed  {$u['full_name']}\n";
  }
  echo "Done.\n";
  exit(0);
}

// ── Dry-run ───────────────────────────────────────────────────────────────────

if ($dryRun) {
  echo "── Dry-run: would seed 90 famous-actor users ────────────────────\n";
  foreach ($users as $u) {
    printf(
      "  %-24s  %-12s  %-3s  %-28s  %s\n",
      $u['full_name'],
      $u['role'],
      $u['country'],
      $u['email'],
      $u['inspiration']
    );
  }
  echo "\nBusiness target: {$businessId}\n";
  echo "Default password: " . SEED_PASSWORD . "\n";
  exit(0);
}

// ── Validate business ─────────────────────────────────────────────────────────

$businessData = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
if (empty($businessData)) {
  fwrite(STDERR, "Error: business '{$businessId}' not found in Redis.\n");
  exit(1);
}

$businessName = (string) ($businessData['name'] ?? $businessId);
$ownerUUID    = (string) ($businessData['owner_uuid'] ?? '');
$timestamp    = date('c');
$passwordHash = password_hash(SEED_PASSWORD, PASSWORD_DEFAULT);
$now          = (string) time();

echo "── Seeding 90 famous-actor fake users ───────────────────────────\n";

$created = 0;
foreach ($users as $u) {
  $uuid  = $u['uuid'];
  $email = $u['email'];
  $auth  = AuthLevel::from($u['auth_level']);

  $exists = Database::exists(Keys::USER . ':' . $uuid);

  UserRepository::setUser(
    $uuid,
    $passwordHash,
    $email,
    $auth,
    $u['full_name'],
    '',
    $u['phone']
  );

  Database::hset(Keys::USER . ':' . $uuid, [
    'email_verified'  => '1',
    'province'        => $u['province'],
    'timezone'        => $u['timezone'],
    'currency'        => $u['currency'],
    'address_line1'   => $u['address_line1'],
    'address_city'    => $u['address_city'],
    'address_postal'  => $u['address_postal'],
    'job_title'       => $u['job_title'],
    'department'      => $u['department'],
    'created_at'      => $now,
  ]);

  $status = $exists ? 'overwritten' : 'created';
  if (!$exists) {
    $created++;
  }
  printf("  %-12s  %-24s  %-3s  %s\n", $status, $u['full_name'], $u['country'], $email);
}

echo "\n── Attaching to business '{$businessName}' ({$businessId}) ─────────\n";

$attached = 0;
foreach ($users as $u) {
  $uuid   = $u['uuid'];
  $relKey = Keys::BUSINESS_RELATIONSHIP . ':' . $businessId . ':' . $uuid;
  $hadRel = Database::exists($relKey);

  Database::hset($relKey, [
    'business_id' => $businessId,
    'user_uuid'   => $uuid,
    'role'        => $u['role'],
    'status'      => 'active',
    'scopes'      => $u['scopes'],
    'invited_by'  => $ownerUUID,
    'created_at'  => $timestamp,
    'accepted_at' => $timestamp,
    'updated_at'  => $timestamp,
  ]);

  Database::sadd(Keys::BUSINESS_MEMBERS . ':' . $businessId, $uuid);
  Database::sadd(Keys::BUSINESS_RELATIONSHIPS . ':' . $businessId, $uuid);
  Database::sadd(Keys::BUSINESS_USER . ':' . $uuid, $businessId);
  Database::sadd(Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $uuid, $businessId);

  if (!$hadRel) {
    $attached++;
  }
  printf("  %-7s  %-24s  %-12s\n", $hadRel ? 'updated' : 'added', $u['full_name'], $u['role']);
}

echo "\nSeeded " . count($users) . " users ({$created} new user records).\n";
echo "Attached " . count($users) . " business relationships ({$attached} new).\n";
echo "Default password: " . SEED_PASSWORD . "\n";
echo "\nNext: php tools/seed_work_history_2026.php {$businessId}\n";
