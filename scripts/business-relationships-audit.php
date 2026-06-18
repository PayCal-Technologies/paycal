#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * Audit and optionally repair derived Redis indexes for business relationships.
 *
 * Canonical truth:
 *   business:relationship:{businessId}:{userUUID}
 *
 * Derived indexes repaired by --fix:
 *   business:members:{businessId}
 *   business:user:{userUUID}
 *   business:relationships:{businessId}
 *   business:relationships:user:{userUUID}
 *   business:pending:{businessId}
 *
 * Usage:
 *   php scripts/business-relationships-audit.php [--business <businessId>] [--fix] [--json]
 */

require_once __DIR__ . '/../html/config.php';

use PayCal\Domain\BusinessDiscoveryService;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\Database;

/**
 * @param list<string> $argv
 */
function main(array $argv): int
{
  array_shift($argv);
  $options = parseOptions($argv);

  if (isset($options['help'])) {
    usage();
    return 0;
  }

  $fix = isset($options['fix']);
  $json = isset($options['json']);
  $businessId = trim((string) ($options['business'] ?? ''));

  $result = auditBusinessRelationships($businessId, $fix);
  $exitCode = auditExitCode($result, $fix);

  if ($json) {
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    return $exitCode;
  }

  printHumanReport($result, $fix);

  return $exitCode;
}

/**
 * @param list<string> $args
 * @return array<string, string|bool>
 */
function parseOptions(array $args): array
{
  $options = [];
  for ($i = 0, $count = count($args); $i < $count; ++$i) {
    $arg = $args[$i];
    if ($arg === '--fix') {
      $options['fix'] = true;
      continue;
    }
    if ($arg === '--json') {
      $options['json'] = true;
      continue;
    }
    if ($arg === '--help' || $arg === '-h') {
      $options['help'] = true;
      continue;
    }
    if ($arg === '--business') {
      $options['business'] = (string) ($args[++$i] ?? '');
      continue;
    }
    if (str_starts_with($arg, '--business=')) {
      $options['business'] = substr($arg, strlen('--business='));
      continue;
    }

    fwrite(STDERR, "Unknown option: {$arg}\n");
    usage();
    exit(2);
  }

  return $options;
}

function usage(): void
{
  fwrite(STDERR, "Usage: php scripts/business-relationships-audit.php [--business <businessId>] [--fix] [--json]\n");
  fwrite(STDERR, "\nExit codes:\n");
  fwrite(STDERR, "  0  Clean audit, or --fix repaired known repairable drift\n");
  fwrite(STDERR, "  1  Report-only drift found\n");
  fwrite(STDERR, "  2  Owner invariant violation\n");
  fwrite(STDERR, "  3  Unknown/other drift found\n");
  fwrite(STDERR, "  4  Script or Redis failure\n");
}

/**
 * @return array{
 *   checked_relationships: int,
 *   checked_businesses: int,
 *   drift_count: int,
 *   fixed_count: int,
 *   owner_violation_count: int,
 *   bucket_counts: array<string, int>,
 *   drifts: list<array<string, string>>,
 *   owner_violations: list<array<string, mixed>>
 * }
 */
function auditBusinessRelationships(string $businessId, bool $fix): array
{
  $relationshipKeys = $businessId === ''
    ? Database::scanKeys(Keys::BUSINESS_RELATIONSHIP . ':*')
    : Database::scanKeys(Keys::BUSINESS_RELATIONSHIP . ':' . $businessId . ':*');

  $drifts = [];
  $fixedCount = 0;
  $businesses = [];
  $activeOwners = [];

  foreach ($relationshipKeys as $relationshipKeyRaw) {
    $relationshipKey = (string) $relationshipKeyRaw;
    $relationship = Database::hgetall($relationshipKey);
    if ($relationship === []) {
      continue;
    }

    $businessIdValue = trim((string) ($relationship['business_id'] ?? ''));
    $userUUID = trim((string) ($relationship['user_uuid'] ?? ''));
    if ($businessIdValue === '' || $userUUID === '') {
      $parsed = parseRelationshipKey($relationshipKey);
      $businessIdValue = $businessIdValue !== '' ? $businessIdValue : $parsed['business_id'];
      $userUUID = $userUUID !== '' ? $userUUID : $parsed['user_uuid'];
    }

    if ($businessIdValue === '' || $userUUID === '') {
      $drifts[] = drift($businessIdValue, $userUUID, 'relationship_hash_identity', 'business_id and user_uuid present', 'missing identity fields', 'manual_review');
      continue;
    }

    $businesses[$businessIdValue] = true;
    $status = strtolower(trim((string) ($relationship['status'] ?? '')));
    $role = strtolower(trim((string) ($relationship['role'] ?? '')));

    if ($status === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE && $role === 'owner') {
      $activeOwners[$businessIdValue][$userUUID] = true;
    }

    $expectActiveIndexes = $status === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE;
    $expectRelationshipIndexes = in_array($status, [
      BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE,
      BusinessDiscoveryService::MEMBERSHIP_STATE_CONSENTED,
      BusinessDiscoveryService::MEMBERSHIP_STATE_PENDING,
    ], true);
    $expectPendingIndex = $status === BusinessDiscoveryService::MEMBERSHIP_STATE_PENDING;

    $fixedCount += checkSetMembership(
      Keys::BUSINESS_MEMBERS . ':' . $businessIdValue,
      $userUUID,
      $expectActiveIndexes,
      $fix,
      $drifts,
      $businessIdValue,
      $userUUID,
      'business:members',
      $status,
    );
    $fixedCount += checkSetMembership(
      Keys::BUSINESS_USER . ':' . $userUUID,
      $businessIdValue,
      $expectActiveIndexes,
      $fix,
      $drifts,
      $businessIdValue,
      $userUUID,
      'business:user',
      $status,
    );
    $fixedCount += checkSetMembership(
      Keys::BUSINESS_RELATIONSHIPS . ':' . $businessIdValue,
      $userUUID,
      $expectRelationshipIndexes,
      $fix,
      $drifts,
      $businessIdValue,
      $userUUID,
      'business:relationships',
      $status,
    );
    $fixedCount += checkSetMembership(
      Keys::BUSINESS_RELATIONSHIPS_USER . ':' . $userUUID,
      $businessIdValue,
      $expectRelationshipIndexes,
      $fix,
      $drifts,
      $businessIdValue,
      $userUUID,
      'business:relationships:user',
      $status,
    );
    $fixedCount += checkSetMembership(
      Keys::BUSINESS_PENDING . ':' . $businessIdValue,
      $userUUID,
      $expectPendingIndex,
      $fix,
      $drifts,
      $businessIdValue,
      $userUUID,
      'business:pending',
      $status,
    );
  }

  $fixedCount += auditStaleIndexSets($businessId, $fix, $drifts);
  $ownerViolations = auditOwnerInvariant(array_keys($businesses), $activeOwners);
  $bucketCounts = bucketCounts($drifts);
  $bucketCounts['owner_violation'] = count($ownerViolations);

  return [
    'checked_relationships' => count($relationshipKeys),
    'checked_businesses' => count($businesses),
    'drift_count' => count($drifts),
    'fixed_count' => $fixedCount,
    'owner_violation_count' => count($ownerViolations),
    'bucket_counts' => $bucketCounts,
    'drifts' => $drifts,
    'owner_violations' => $ownerViolations,
  ];
}

/**
 * @return array{business_id: string, user_uuid: string}
 */
function parseRelationshipKey(string $key): array
{
  $prefix = Keys::BUSINESS_RELATIONSHIP . ':';
  if (!str_starts_with($key, $prefix)) {
    return ['business_id' => '', 'user_uuid' => ''];
  }

  $tail = substr($key, strlen($prefix));
  $parts = explode(':', $tail, 2);

  return [
    'business_id' => trim((string) ($parts[0] ?? '')),
    'user_uuid' => trim((string) ($parts[1] ?? '')),
  ];
}

/**
 * @param list<array<string, string>> $drifts
 */
function checkSetMembership(
  string $setKey,
  string $member,
  bool $expected,
  bool $fix,
  array &$drifts,
  string $businessId,
  string $userUUID,
  string $check,
  string $status,
): int {
  $present = Database::sismember($setKey, $member) === 1;
  if ($present === $expected) {
    return 0;
  }

  $action = $expected ? 'add' : 'remove';
  if ($fix) {
    if ($expected) {
      Database::sadd($setKey, $member);
    } else {
      Database::srem($setKey, $member);
    }
  }

  $drifts[] = drift(
    $businessId,
    $userUUID,
    $check,
    $expected ? 'present' : 'absent',
    $present ? 'present' : 'absent',
    $fix ? $action : 'report_only',
    classifyDrift($status, $check, $expected, $present)
  );

  return $fix ? 1 : 0;
}

/**
 * @param list<array<string, string>> $drifts
 */
function auditStaleIndexSets(string $businessId, bool $fix, array &$drifts): int
{
  $fixed = 0;
  $businessPatterns = $businessId === ''
    ? [Keys::BUSINESS_MEMBERS . ':*', Keys::BUSINESS_RELATIONSHIPS . ':*', Keys::BUSINESS_PENDING . ':*']
    : [Keys::BUSINESS_MEMBERS . ':' . $businessId, Keys::BUSINESS_RELATIONSHIPS . ':' . $businessId, Keys::BUSINESS_PENDING . ':' . $businessId];

  foreach ($businessPatterns as $pattern) {
    foreach (Database::scanKeys($pattern) as $setKeyRaw) {
      $setKey = (string) $setKeyRaw;
      if (str_starts_with($setKey, Keys::BUSINESS_RELATIONSHIPS_USER . ':')) {
        continue;
      }
      $prefix = explode(':', $setKey, 3)[0] . ':' . explode(':', $setKey, 3)[1] . ':';
      $indexedBusinessId = substr($setKey, strlen($prefix));
      foreach (Database::smembers($setKey) as $userUUIDRaw) {
        $userUUID = trim((string) $userUUIDRaw);
        if ($userUUID === '') {
          continue;
        }
        if (Database::hgetall(Keys::BUSINESS_RELATIONSHIP . ':' . $indexedBusinessId . ':' . $userUUID) !== []) {
          continue;
        }
        if ($fix) {
          Database::srem($setKey, $userUUID);
          ++$fixed;
        }
        $drifts[] = drift($indexedBusinessId, $userUUID, $setKey, 'canonical relationship exists', 'stale index member', $fix ? 'remove' : 'report_only', 'stale_index_without_relationship');
      }
    }
  }

  $userPatterns = $businessId === ''
    ? [Keys::BUSINESS_USER . ':*', Keys::BUSINESS_RELATIONSHIPS_USER . ':*']
    : [];
  foreach ($userPatterns as $pattern) {
    foreach (Database::scanKeys($pattern) as $setKeyRaw) {
      $setKey = (string) $setKeyRaw;
      $userUUID = str_starts_with($setKey, Keys::BUSINESS_RELATIONSHIPS_USER . ':')
        ? substr($setKey, strlen(Keys::BUSINESS_RELATIONSHIPS_USER . ':'))
        : substr($setKey, strlen(Keys::BUSINESS_USER . ':'));
      foreach (Database::smembers($setKey) as $indexedBusinessIdRaw) {
        $indexedBusinessId = trim((string) $indexedBusinessIdRaw);
        if ($indexedBusinessId === '') {
          continue;
        }
        if (Database::hgetall(Keys::BUSINESS_RELATIONSHIP . ':' . $indexedBusinessId . ':' . $userUUID) !== []) {
          continue;
        }
        if ($fix) {
          Database::srem($setKey, $indexedBusinessId);
          ++$fixed;
        }
        $drifts[] = drift($indexedBusinessId, $userUUID, $setKey, 'canonical relationship exists', 'stale reverse index member', $fix ? 'remove' : 'report_only', 'stale_index_without_relationship');
      }
    }
  }

  return $fixed;
}

/**
 * @param list<string> $businessIds
 * @param array<string, array<string, bool>> $activeOwners
 * @return list<array<string, mixed>>
 */
function auditOwnerInvariant(array $businessIds, array $activeOwners): array
{
  $violations = [];
  foreach ($businessIds as $businessId) {
    $business = Database::hgetall(Keys::BUSINESS . ':' . $businessId);
    if ($business === []) {
      continue;
    }

    $status = strtolower(trim((string) ($business['status'] ?? 'active')));
    if ($status !== BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE) {
      continue;
    }

    $owners = array_keys($activeOwners[$businessId] ?? []);
    if (count($owners) !== 1) {
      $violations[] = [
        'business_id' => $businessId,
        'expected' => 'exactly_one_active_owner',
        'actual_count' => count($owners),
        'owners' => $owners,
      ];
    }
  }

  return $violations;
}

/**
 * @return array<string, string>
 */
function drift(string $businessId, string $userUUID, string $check, string $expected, string $actual, string $action, string $bucket = 'other'): array
{
  return [
    'business_id' => $businessId,
    'user_uuid' => $userUUID,
    'check' => $check,
    'expected' => $expected,
    'actual' => $actual,
    'action' => $action,
    'bucket' => $bucket,
  ];
}

function classifyDrift(string $status, string $check, bool $expected, bool $present): string
{
  $terminalStatuses = [
    BusinessDiscoveryService::MEMBERSHIP_STATE_REVOKED => true,
    BusinessDiscoveryService::MEMBERSHIP_STATE_REJECTED => true,
    BusinessDiscoveryService::MEMBERSHIP_STATE_EXPIRED => true,
    'withdrawn' => true,
  ];

  if ($status === BusinessDiscoveryService::MEMBERSHIP_STATE_ACTIVE && $expected && !$present) {
    if ($check === 'business:members') {
      return 'active_missing_member_index';
    }
    if ($check === 'business:user') {
      return 'active_missing_user_index';
    }
  }

  if ($status === BusinessDiscoveryService::MEMBERSHIP_STATE_PENDING && !$expected && $present) {
    if ($check === 'business:members' || $check === 'business:user') {
      return 'pending_in_active_member_index';
    }
  }

  if ($status === BusinessDiscoveryService::MEMBERSHIP_STATE_CONSENTED && !$expected && $present) {
    if ($check === 'business:members' || $check === 'business:user') {
      return 'consented_in_active_member_index';
    }
  }

  if (isset($terminalStatuses[$status]) && !$expected && $present) {
    if ($check === 'business:relationships' || $check === 'business:relationships:user') {
      return 'terminal_in_relationship_index';
    }
    if ($check === 'business:members' || $check === 'business:user') {
      return 'terminal_in_member_index';
    }
    if ($check === 'business:pending') {
      return 'terminal_in_pending_index';
    }
  }

  if ($expected && !$present && ($check === 'business:relationships' || $check === 'business:relationships:user')) {
    return 'relationship_lookup_missing';
  }

  return 'other';
}

/**
 * @param list<array<string, string>> $drifts
 * @return array<string, int>
 */
function bucketCounts(array $drifts): array
{
  $counts = [
    'active_missing_member_index' => 0,
    'active_missing_user_index' => 0,
    'pending_in_active_member_index' => 0,
    'terminal_in_relationship_index' => 0,
    'terminal_in_member_index' => 0,
    'terminal_in_pending_index' => 0,
    'consented_in_active_member_index' => 0,
    'relationship_lookup_missing' => 0,
    'owner_violation' => 0,
    'stale_index_without_relationship' => 0,
    'other' => 0,
  ];

  foreach ($drifts as $drift) {
    $bucket = (string) ($drift['bucket'] ?? 'other');
    if (!array_key_exists($bucket, $counts)) {
      $bucket = 'other';
    }
    ++$counts[$bucket];
  }

  return $counts;
}

/**
 * Exit-code policy:
 *   0 = clean audit, or --fix repaired all known repairable drift
 *   1 = report-only drift found
 *   2 = owner invariant violation
 *   3 = unknown/other drift found
 *   4 = script/Redis failure, handled at process boundary
 *
 * @param array<string, mixed> $result
 */
function auditExitCode(array $result, bool $fix): int
{
  if ((int) ($result['owner_violation_count'] ?? 0) > 0) {
    return 2;
  }

  $bucketCounts = is_array($result['bucket_counts'] ?? null) ? $result['bucket_counts'] : [];
  if ((int) ($bucketCounts['other'] ?? 0) > 0) {
    return 3;
  }

  if (!$fix && (int) ($result['drift_count'] ?? 0) > 0) {
    return 1;
  }

  return 0;
}

/**
 * @param array<string, mixed> $result
 */
function printHumanReport(array $result, bool $fix): void
{
  echo "PayCal business relationship audit\n";
  echo "Mode: " . ($fix ? 'FIX' : 'REPORT') . "\n";
  echo "Relationships checked: " . (int) $result['checked_relationships'] . "\n";
  echo "Businesses checked: " . (int) $result['checked_businesses'] . "\n";
  echo "Drift findings: " . (int) $result['drift_count'] . "\n";
  echo "Repairs applied: " . (int) $result['fixed_count'] . "\n";
  echo "Owner invariant violations: " . (int) $result['owner_violation_count'] . "\n";

  $bucketCounts = is_array($result['bucket_counts'] ?? null) ? $result['bucket_counts'] : [];
  if ($bucketCounts !== []) {
    echo "\nBuckets:\n";
    foreach ($bucketCounts as $bucket => $count) {
      echo '- ' . (string) $bucket . ': ' . (int) $count . "\n";
    }
  }

  $drifts = is_array($result['drifts'] ?? null) ? $result['drifts'] : [];
  if ($drifts !== []) {
    echo "\nDrift:\n";
    foreach ($drifts as $row) {
      if (!is_array($row)) {
        continue;
      }
      echo '- '
        . (string) ($row['business_id'] ?? '')
        . ' / '
        . (string) ($row['user_uuid'] ?? '')
        . ' / '
        . (string) ($row['check'] ?? '')
        . ': expected '
        . (string) ($row['expected'] ?? '')
        . ', actual '
        . (string) ($row['actual'] ?? '')
        . ', action '
        . (string) ($row['action'] ?? '')
        . "\n";
    }
  }

  $ownerViolations = is_array($result['owner_violations'] ?? null) ? $result['owner_violations'] : [];
  if ($ownerViolations !== []) {
    echo "\nOwner invariant violations:\n";
    foreach ($ownerViolations as $row) {
      if (!is_array($row)) {
        continue;
      }
      echo '- '
        . (string) ($row['business_id'] ?? '')
        . ': expected exactly one active owner, actual '
        . (string) ($row['actual_count'] ?? '')
        . "\n";
    }
  }
}

try {
  exit(main($argv));
} catch (Throwable $error) {
  fwrite(STDERR, '[fatal] ' . $error->getMessage() . "\n");
  exit(4);
}
