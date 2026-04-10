<?php declare(strict_types=1);

return [
  'id' => 'billing-provider',
  'name' => 'Billing Provider (Basic)',
  'version' => '1.0.0',
  'description' => 'Public core billing mode with a local Premium toggle.',
  'author' => 'PayCal Core',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'billing.provider' => 'public-toggle',
  ],
  'hooks' => [],
  'bootstrap' => 'bootstrap.php',
];