<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

$userUUID = User::currentUUID();
$hasActivePremiumSubscription = $userUUID !== '' && SubscriptionGate::hasActivePremium($userUUID);
$hasActiveBusinessSubscription = $userUUID !== '' && SubscriptionGate::hasActiveBusiness($userUUID);
$isFreeProfile = !User::isAdmin() && !$hasActiveBusinessSubscription;
$showDevAdminPanels = User::isAdmin() && AdminSurface::isEnabled() && in_array(Environment::appEnv(), ['dev', 'mac'], true);
$businessPermissionRoles = [
  ['key' => 'owner', 'label' => businesses_index_i18n('BUSINESSES_ROLE_OWNER')],
  ['key' => 'coordinator', 'label' => businesses_index_i18n('BUSINESSES_ROLE_COORDINATOR')],
  ['key' => 'contributor', 'label' => businesses_index_i18n('BUSINESSES_ROLE_CONTRIBUTOR')],
  ['key' => 'viewer', 'label' => businesses_index_i18n('BUSINESSES_ROLE_VIEWER')],
  ['key' => 'member', 'label' => businesses_index_i18n('BUSINESSES_ROLE_MEMBER')],
];
$businessPermissionScopeOrg = businesses_index_i18n('BUSINESSES_PERM_SCOPE_ORG');
$businessPermissionScopeSelf = businesses_index_i18n('BUSINESSES_PERM_SCOPE_SELF');
$businessPermissionMatrix = [
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_OWNERSHIP_TRANSFER'), 'owner' => '✓', 'coordinator' => '-', 'contributor' => '-', 'viewer' => '-', 'member' => '-'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_ORG_SETTINGS_READ'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '✓', 'viewer' => '✓', 'member' => '✓'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_ORG_SETTINGS_WRITE'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '-', 'viewer' => '-', 'member' => '-'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_ACCESS_MANAGEMENT'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '-', 'viewer' => '-', 'member' => '-'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_AUDIT_READ'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '✓', 'viewer' => '✓', 'member' => '✓'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_ORG_DETAILS_READ'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '✓', 'viewer' => '✓', 'member' => '✓'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_PAY_PERIOD_READ'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '✓', 'viewer' => '✓', 'member' => '✓'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_PAY_PERIOD_WRITE'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '✓', 'viewer' => '-', 'member' => '-'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_WAGE_READ'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '✓', 'viewer' => '✓', 'member' => '✓'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_WAGE_WRITE'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '-', 'viewer' => '-', 'member' => '-'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_SITES_READ'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '✓', 'viewer' => '✓', 'member' => '✓'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_SITES_WRITE'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '✓', 'viewer' => '-', 'member' => '-'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_WORK_READ'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '✓', 'viewer' => '✓', 'member' => '✓'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_WORK_WRITE'), 'owner' => '✓', 'coordinator' => '✓', 'contributor' => '✓', 'viewer' => '-', 'member' => '✓'],
  ['feature' => businesses_index_i18n('BUSINESSES_PERM_WORK_SCOPE'), 'owner' => $businessPermissionScopeOrg, 'coordinator' => $businessPermissionScopeOrg, 'contributor' => $businessPermissionScopeOrg, 'viewer' => '-', 'member' => $businessPermissionScopeSelf],
];
$businessesCsrfNonce = User::current()->generateFormNonce('businesses');
$workspaceBusiness = BusinessNav::primaryWorkspaceBusinessForUser($userUUID);
$workspaceBusinessId = is_array($workspaceBusiness)
  ? trim((string) ($workspaceBusiness['business_id'] ?? ''))
  : '';
$workspaceBusinessIdAttr = $workspaceBusinessId !== ''
  ? ' data-business-id="' . htmlspecialchars($workspaceBusinessId, ENT_QUOTES, 'UTF-8') . '"'
  : '';
