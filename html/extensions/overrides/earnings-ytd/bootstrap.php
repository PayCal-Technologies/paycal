<?php declare(strict_types=1);

namespace PayCal\Extensions\Overrides\EarningsYtd;

use PayCal\Domain\Extensions\HookBus;

// Package bootstrap: register private YTD renderer with higher precedence.
require_once __DIR__ . '/hooks.php';

HookBus::register(
  'earnings.ytd.render',
  [Hooks::class, 'render'],
  10,
  'extension:earnings-ytd:override'
);
