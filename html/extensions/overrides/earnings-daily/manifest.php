<?php declare(strict_types=1);

return [
  'id' => 'earnings-daily',
  'name' => 'Earnings Daily (Private Override)',
  'version' => '1.0.0-private',
  'description' => 'Restores private daily earnings payload shape and calculations.',
  'author' => 'PayCal Private',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'earnings.daily.render' => 'private',
  ],
  'hooks' => [
    'earnings.daily.render',
  ],
  'bootstrap' => 'bootstrap.php',
];
