<?php declare(strict_types=1);

return [
  'id' => 'earnings-ytd',
  'name' => 'Earnings YTD Summary (Basic)',
  'version' => '1.0.0',
  'description' => 'Minimal Year-to-Date earnings summary for public core baseline.',
  'author' => 'PayCal Core',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'earnings.ytd.render' => 'basic',
    'earnings.ytd.rows' => 5,
  ],
  'hooks' => [
    'earnings.ytd.render',
  ],
  'bootstrap' => 'bootstrap.php',
];
