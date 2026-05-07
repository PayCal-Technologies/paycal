<?php declare(strict_types=1);

use PayCal\Domain\Config\SystemConfig;

/**
 * TestConfig.php
 *
 * @package PayCal
 */

/**
 * Test Configuration.
 *
 * Defines all constants and fixtures needed for integration and manual tests.
 * This file is NOT loaded by the application itself - only by test suites.
 *
 * Test Structure:
 * - System limits: Work hour thresholds used by Work::regularizeHours()
 * - Test data: Default UUIDs, years, URLs for test fixtures
 * - Redis prefixes: Key namespace constants for Redis operations
 * - Logging paths: Test-specific log file locations
 */

// ============================================================================
// System Limits (used by src/Domain/Work.php in both production and tests)
// ============================================================================
// These define the thresholds for regular vs overtime hours calculation.
// Values come from src/system-limits-master.php authoritative config.

if (!defined('MAX_WEEKLY_REGULAR_HOURS')) {
  define('MAX_WEEKLY_REGULAR_HOURS', 44.0);
}

if (!defined('MAX_DAILY_REGULAR_HOURS')) {
  define('MAX_DAILY_REGULAR_HOURS', 8.0);
}

if (!defined('DEFAULT_WORK_WEEK_LENGTH')) {
  define('DEFAULT_WORK_WEEK_LENGTH', 7);
}

if (!defined('MIN_DAILY_REGULAR_HOURS')) {
  define('MIN_DAILY_REGULAR_HOURS', 0.0);
}

if (!defined('MAX_DAILY_REGULAR_HOURS_ABSOLUTE')) {
  define('MAX_DAILY_REGULAR_HOURS_ABSOLUTE', 24.0);
}

// ============================================================================
// Test Data Fixtures
// ============================================================================
// Default values used across test suites for consistency.

if (!defined('PC_VERIFICATION_SET')) {
  define('PC_VERIFICATION_SET', 'ABCDEFGHJKLMNPQRTUWXYZ346789');
}

if (!defined('USER_UUID')) {
  define('USER_UUID', 'test-user-uuid-001');
}

if (!defined('USER_LANGUAGE')) {
  define('USER_LANGUAGE', 'en');
}

if (!defined('YEAR')) {
  define('YEAR', (int) date('Y'));
}

if (!defined('DATA_URL')) {
  define('DATA_URL', '/api/data');
}

// ============================================================================
// Redis Key Prefixes
// ============================================================================
// These constants are used to construct Redis keys in test fixtures.
// In production, the Keys class provides these prefixes.

if (!defined('D_WORK')) {
  define('D_WORK', 'work');
}

if (!defined('D_EARNING')) {
  define('D_EARNING', 'earning');
}

if (!defined('D_USER')) {
  define('D_USER', 'user');
}

if (!defined('D_SESSION')) {
  define('D_SESSION', 'session');
}

if (!defined('D_SITE')) {
  define('D_SITE', 'site');
}

if (!defined('SITE_ID_PREFIX')) {
  define('SITE_ID_PREFIX', 'S');
}

if (!defined('D_SITE_STRUCTURE')) {
  define('D_SITE_STRUCTURE', 'site_structure');
}

// Team constants now use Keys:: class - removed D_ORGANIZATION_* definitions

// ============================================================================
// Encryption & Cryptography Constants
// ============================================================================
// Constants for encryption telemetry and crypto defaults.

if (!defined('ENCRYPTION_TELEMETRY_SCHEMA')) {
  define('ENCRYPTION_TELEMETRY_SCHEMA', \PayCal\Domain\SystemConfig::ENCRYPTION_TELEMETRY_SCHEMA);
}

if (!defined('ENCRYPTED_BLOB_MAX_BYTES')) {
  define('ENCRYPTED_BLOB_MAX_BYTES', \PayCal\Domain\SystemConfig::ENCRYPTED_BLOB_MAX_BYTES);
}

// ============================================================================
// Logging Paths (Test-Specific)
// ============================================================================
// Where integration and manual tests write logs.
// These should NOT be used in production code.

if (!defined('LOGS')) {
  define('LOGS', __DIR__.'/../../logs/');
}

if (!defined('DEBUG_LOGFILE')) {
  define('DEBUG_LOGFILE', LOGS.'debug.log');
}

if (!defined('INFO_LOGFILE')) {
  define('INFO_LOGFILE', LOGS.'info.log');
}

if (!defined('ACCESS_LOGFILE')) {
  define('ACCESS_LOGFILE', LOGS.'access.log');
}

if (!defined('ERROR_LOGFILE')) {
  define('ERROR_LOGFILE', LOGS.'error.log');
}

if (!defined('TRACE_LOGFILE')) {
  define('TRACE_LOGFILE', LOGS.'trace.log');
}
