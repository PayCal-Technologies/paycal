<?php declare(strict_types=1);

namespace PayCal\Domain;

/** @var User $user */
$profileSubscription = SubscriptionRepository::get($user->user_uuid);
$billingProvider = BillingProvider::current();
$isStripeBilling = $billingProvider === BillingProvider::STRIPE;
$hasActivePremium = SubscriptionRepository::isPremiumActive($user->user_uuid);
$hasActiveBusiness = SubscriptionRepository::isBusinessActive($user->user_uuid);
$hasPaidSubscription = $hasActivePremium || $hasActiveBusiness;
$billingHint = $hasActiveBusiness ? 'business' : ($hasActivePremium ? 'premium' : 'free');
$billingMemberships = BusinessMemberRepository::forUser($user->user_uuid);
$billingActiveMembership = null;
foreach ($billingMemberships as $membership) {
  if ((string) ($membership['status'] ?? '') === 'active') {
    $billingActiveMembership = $membership;
    break;
  }
}
$billingBusinessId = is_array($billingActiveMembership) ? (string) ($billingActiveMembership['org_id'] ?? '') : '';
$billingBusinessRole = is_array($billingActiveMembership) ? (string) ($billingActiveMembership['role'] ?? '') : '';
$billingBusinessMemberCount = $billingBusinessId !== '' ? BusinessMemberRepository::count($billingBusinessId, 'active') : 0;
$billingBusinessSummary = $billingBusinessMemberCount > 0
  ? $billingBusinessMemberCount . ' active member' . ($billingBusinessMemberCount === 1 ? '' : 's')
  : 'No group members yet';
$billingBusinessRoleLabel = $billingBusinessRole !== '' ? ucfirst($billingBusinessRole) : 'Member';
$billingBusinessListingStatus = 'Not listed yet';
if ($billingBusinessId !== '') {
  $billingBusinessData = Database::hgetall(Keys::BUSINESS . ':' . $billingBusinessId);
  $visibility = strtolower(trim((string) ($billingBusinessData['visibility'] ?? '')));
  $moderation = strtolower(trim((string) ($billingBusinessData['moderation_status'] ?? '')));
  if ($visibility === 'listed' && $moderation === 'approved') {
    $billingBusinessListingStatus = 'Eligible';
  } elseif ($moderation === 'pending' || $moderation === 'needs_review') {
    $billingBusinessListingStatus = 'Pending review';
  }
}
$localeOptions = [
  'en-CA' => settings_index_i18n('PROFILE_LOCALE_EN_CA'),
  'fr-CA' => settings_index_i18n('PROFILE_LOCALE_FR_CA'),
  'en-US' => settings_index_i18n('PROFILE_LOCALE_EN_US'),
  'en-GB' => settings_index_i18n('PROFILE_LOCALE_EN_GB'),
  'fr-FR' => settings_index_i18n('PROFILE_LOCALE_FR_FR'),
  'de-DE' => settings_index_i18n('PROFILE_LOCALE_DE_DE'),
  'es-ES' => settings_index_i18n('PROFILE_LOCALE_ES_ES'),
  'pt-BR' => settings_index_i18n('PROFILE_LOCALE_PT_BR'),
];

require __DIR__ . '/../../business/_partials/i18n.php';
