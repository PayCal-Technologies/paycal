<?php declare(strict_types=1);

namespace PayCal\Extensions\Basic\EarningsYtd;

use PayCal\Domain\Extensions\HookBus;

// Package bootstrap: register baseline YTD renderer listener.
require_once __DIR__ . '/hooks.php';

HookBus::register(
  'earnings.ytd.render',
  [Hooks::class, 'render'],
  100,
  'extension:earnings-ytd:basic'
);
