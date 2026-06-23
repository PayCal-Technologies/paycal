<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/**
 * Resolve active org memberships with group financial access and selected org.
 *
 * Expects optional $preferredOrgId (workspace business id). Sets:
 * $activeOrgs, $hasOrgMembership, $selectedOrgId, $allMemberships
 */
$userUUID = $userUUID ?? User::currentUUID();
$preferredOrgId = trim((string) ($preferredOrgId ?? ''));
$allMemberships = BusinessMemberRepository::forUser($userUUID);

$hasTeamFinancialAccess = static function (array $membership): bool {
  $scopesRaw = $membership['scopes'] ?? [];
  $scopeSet = [];
  if (is_array($scopesRaw)) {
    foreach ($scopesRaw as $scope) {
      if (!is_string($scope)) {
        continue;
      }
      $scopeSet[strtolower(trim($scope))] = true;
    }
  }

  if (isset($scopeSet['all']) || isset($scopeSet['work.read']) || isset($scopeSet['wage.read']) || isset($scopeSet['financial_payload'])) {
    return true;
  }

  $role = strtolower(trim((string) ($membership['role'] ?? '')));

  return in_array($role, ['owner', 'coordinator', 'contributor', 'manager'], true);
};

/** @var list<array{org_id: string, name: string}> */
$activeOrgs = [];
foreach ($allMemberships as $m) {
  if ($m['status'] !== 'active') {
    continue;
  }
  if (!$hasTeamFinancialAccess($m)) {
    continue;
  }
  $orgName = (string) (Database::hget(Keys::BUSINESS . ':' . $m['org_id'], 'name') ?: $m['org_id']);
  $activeOrgs[] = ['org_id' => $m['org_id'], 'name' => $orgName];
}

$hasOrgMembership = count($activeOrgs) > 0;

$selectedOrgId = '';
$requestedOrg = InputSanitizer::getString('org');
if ($requestedOrg !== '') {
  foreach ($activeOrgs as $o) {
    if ($o['org_id'] === $requestedOrg) {
      $selectedOrgId = $o['org_id'];
      break;
    }
  }
}

if ($selectedOrgId === '' && $preferredOrgId !== '') {
  foreach ($activeOrgs as $o) {
    if ($o['org_id'] === $preferredOrgId) {
      $selectedOrgId = $o['org_id'];
      break;
    }
  }
}

if ($selectedOrgId === '' && isset($activeOrgs[0])) {
  $selectedOrgId = $activeOrgs[0]['org_id'];
}
