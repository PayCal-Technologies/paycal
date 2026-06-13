<?php declare(strict_types=1);

namespace PayCal\Extensions\Basic\BusinessSignals;

use PayCal\Domain\Extensions\HookBus;

// Package bootstrap: register baseline business audit-event listener.
require_once __DIR__ . '/hooks.php';

HookBus::register(
  'business.audit_event',
  [Hooks::class, 'onBusinessAuditEvent'],
  100,
  'extension:business-signals:basic'
);
