<?php declare(strict_types=1);

return [
  'id' => 'billing-provider',
  'name' => 'Billing Provider (Private Override)',
  'version' => '1.0.0-private',
  'description' => 'Private Stripe-backed billing provider override.',
  'author' => 'PayCal Private',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'billing.provider' => 'stripe',
  ],
  'hooks' => [],
  'bootstrap' => 'bootstrap.php',
];