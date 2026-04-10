<?php declare(strict_types=1);

return [
  'id' => 'organization-signals',
  'name' => 'Organization Signals (Basic)',
  'version' => '1.0.0',
  'description' => 'Basic owner signal fanout when organization access requests are created.',
  'author' => 'PayCal Core',
  'license' => 'Proprietary',
  'core_compat' => '>=1.0.0',
  'enabled' => true,
  'capabilities' => [
    'organization.signal.owner_inbox' => 'basic',
    'organization.audit.listener' => true,
  ],
  'hooks' => [
    'organization.audit_event',
  ],
  'bootstrap' => 'bootstrap.php',
];
