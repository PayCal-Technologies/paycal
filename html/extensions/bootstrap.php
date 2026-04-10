<?php declare(strict_types=1);

/**
 * Extension runtime bootstrap entrypoint.
 *
 * This file is intentionally thin: Core loads it once during app bootstrap,
 * and the runtime discovers/activates extension manifests and optional
 * bootstrap files.
 */

use PayCal\Domain\Extensions\ExtensionRuntime;

require_once __DIR__ . '/runtime.php';

ExtensionRuntime::boot();
