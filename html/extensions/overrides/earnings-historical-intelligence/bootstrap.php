<?php declare(strict_types=1);

namespace PayCal\Extensions\Overrides\EarningsHistoricalIntelligence;

use PayCal\Domain\Extensions\HookBus;

require_once __DIR__ . '/hooks.php';

HookBus::register(
  'earnings.historical.render',
  [Hooks::class, 'render'],
  10,
  'extension:earnings-historical-intelligence:override'
);
