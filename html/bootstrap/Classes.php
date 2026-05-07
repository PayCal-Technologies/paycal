<?php declare(strict_types=1);

// Align cwd for GitHub Actions PHPUnit path expectations.
chdir(dirname(__DIR__));

require_once __DIR__.'/../../vendor/autoload.php';
require_once __DIR__.'/class_aliases.php';
require_once __DIR__.'/../tests/TestConfig.php';

// Load application config so Redis credentials and environment are available
// during test runs (required for server-side runs where Redis auth is enforced).
// TestConfig.php is loaded first so its constant guards (e.g. USER_UUID) win
// over the legacy-compat shims in config.php.
require_once __DIR__.'/../config.php';

