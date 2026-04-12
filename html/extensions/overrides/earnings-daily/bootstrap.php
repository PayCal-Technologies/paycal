<?php declare(strict_types=1);

namespace PayCal\Extensions\Overrides\EarningsDaily;

use PayCal\Domain\Extensions\HookBus;

require_once __DIR__ . '/hooks.php';

HookBus::register(
  'earnings.daily.render',
  [Hooks::class, 'render'],
  10,
  'extension:earnings-daily:override'
);
