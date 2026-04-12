<?php declare(strict_types=1);

return [
  'id' => 'earnings-piegraphs',
  'name' => 'Earnings Pie Graphs (Private Override)',
  'version' => '1.0.0-private',
  'description' => 'Adds private YTD and monthly earnings composition pie charts.',
  'author' => 'PayCal Private',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'earnings.piegraphs.render' => 'private',
  ],
  'hooks' => [
    'earnings.piegraphs.render',
  ],
  'bootstrap' => 'bootstrap.php',
];
