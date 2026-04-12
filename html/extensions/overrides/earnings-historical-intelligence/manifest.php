<?php declare(strict_types=1);

return [
  'id' => 'earnings-historical-intelligence',
  'name' => 'Earnings Historical Intelligence (Private Override)',
  'version' => '1.0.0-private',
  'description' => 'Restores private historical intelligence panel rendering.',
  'author' => 'PayCal Private',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'earnings.historical.render' => 'private',
  ],
  'hooks' => [
    'earnings.historical.render',
  ],
  'bootstrap' => 'bootstrap.php',
];
