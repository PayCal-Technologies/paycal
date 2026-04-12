<?php declare(strict_types=1);

namespace PayCal\Extensions\Overrides\EarningsMonthly;

use PayCal\Domain\Extensions\HookBus;

require_once __DIR__ . '/hooks.php';

HookBus::register(
  'earnings.monthly.render',
  [Hooks::class, 'render'],
  10,
  'extension:earnings-monthly:override'
);
