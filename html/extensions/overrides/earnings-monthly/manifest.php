<?php declare(strict_types=1);

return [
  'id' => 'earnings-monthly',
  'name' => 'Earnings Monthly (Private Override)',
  'version' => '1.0.0-private',
  'description' => 'Restores private monthly earnings rendering behavior.',
  'author' => 'PayCal Private',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'earnings.monthly.render' => 'private',
  ],
  'hooks' => [
    'earnings.monthly.render',
  ],
  'bootstrap' => 'bootstrap.php',
];
