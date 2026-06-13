<?php declare(strict_types=1);

return [
  'id' => 'business-signals',
  'name' => 'Business Signals (Basic)',
  'version' => '1.0.0',
  'description' => 'Basic owner signal fanout when business access requests are created.',
  'author' => 'PayCal Core',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'business.signal.owner_inbox' => 'basic',
    'business.audit.listener' => true,
  ],
  'hooks' => [
    'business.audit_event',
  ],
  'bootstrap' => 'bootstrap.php',
];
