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
