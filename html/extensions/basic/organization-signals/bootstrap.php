<?php declare(strict_types=1);

namespace PayCal\Extensions\Basic\OrganizationSignals;

use PayCal\Domain\Extensions\HookBus;

// Package bootstrap: register baseline organization audit-event listener.
require_once __DIR__ . '/hooks.php';

HookBus::register(
  'organization.audit_event',
  [Hooks::class, 'onOrganizationAuditEvent'],
  100,
  'extension:organization-signals:basic'
);
