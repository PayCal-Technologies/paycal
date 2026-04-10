<?php declare(strict_types=1);

/**
 * PHPUnit Bootstrap File.
 *
 * Loads all necessary components for running integration and manual tests.
 * This file is NOT used by automated unit tests (phpunit.xml uses vendor/autoload.php).
 *
 * What this file does:
 * 1. Loads Composer autoload (PSR-4 class resolution)
 * 2. Loads test configuration (system limits, test data, Redis prefixes)
 * 3. Initializes TestContext for process-isolated test execution
 * 4. Makes all PayCal domain classes and test fixtures available
 */

// Load Composer PSR-4 autoload first (all classes)
require_once __DIR__.'/../../vendor/autoload.php';

// Load TestContext for process isolation
require_once __DIR__.'/TestContext.php';

// Load test-specific configuration and constants
require_once __DIR__.'/TestConfig.php';

// Initialize test context for this test run
TestContext::init();

