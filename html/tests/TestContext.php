<?php declare(strict_types=1);

/**
 * TestContext - Process-Isolated Test Configuration.
 *
 * Replaces global constants with environment-based test context.
 * Ensures test isolation across spawned PHP processes by using
 * environment variables instead of shared global state.
 *
 * Problem Solved:
 * - Global constants like USER_UUID are shared across all processes
 * - Spawned child scripts inherit parent's constant definitions
 * - This causes UUID collisions and test interference
 *
 * Solution:
 * - Generate deterministic test identities per test run
 * - Store in environment variables
 * - Pass to child processes via environment
 *
 * Usage:
 *   // In test bootstrap
 *   TestContext::init();
 *
 *   // In tests
 *   $userId = TestContext::get('user_uuid');
 *
 *   // For child processes
 *   $cmd = TestContext::wrapCommand('php script.php');
 */
class TestContext
{
  private static array $context = [];

  private static bool $initialized = false;

  /**
   * Initialize test context with deterministic values.
   * Called once during test bootstrap.
   */
  public static function init(): void
  {
    if (self::$initialized) {
      return;
    }

    // Generate deterministic test UUID based on timestamp + process ID
    $seed = getenv('TEST_SEED') ?: (string) (time() . posix_getpid()); // @phpstan-ignore-line
    $userUuid = self::generateDeterministicUuid('user', $seed);
    $sessionUuid = self::generateDeterministicUuid('session', $seed);

    self::$context = [
      'user_uuid' => $userUuid,
      'session_uuid' => $sessionUuid,
      'year' => (int) date('Y'),
      'data_url' => '/api/data',
      'verification_set' => 'ABCDEFGHJKLMNPQRTUWXYZ346789',
    ];

    // Export to environment for child processes
    foreach (self::$context as $key => $value) {
      putenv('TEST_' . strtoupper($key) . '=' . $value);
    }

    self::$initialized = true;
  }

  /**
   * Get a test context value.
   *
   * @param string $key Context key
   * @return mixed Context value
   */
  public static function get(string $key): mixed
  {
    if (!self::$initialized) {
      self::init();
    }

    // Try context first
    if (isset(self::$context[$key])) {
      return self::$context[$key];
    }

    // Fallback to environment variable
    $envKey = 'TEST_' . strtoupper($key);
    $value = getenv($envKey);

    return $value !== false ? $value : null;
  }

  /**
   * Set a test context value.
   *
   * @param string $key   Context key
   * @param mixed  $value Context value
   */
  public static function set(string $key, mixed $value): void
  {
    self::$context[$key] = $value;

    // Also set in environment
    $envKey = 'TEST_' . strtoupper($key);
    putenv($envKey . '=' . $value);
  }

  /**
   * Generate a deterministic UUID from a label and seed.
   *
   * @param string $label Identifier label (e.g., 'user', 'session')
   * @param string $seed  Seed value for determinism
   * @return string UUID-format string
   */
  private static function generateDeterministicUuid(string $label, string $seed): string
  {
    $hash = hash('sha256', $label . ':' . $seed);

    // Format as UUID v4
    return sprintf(
      '%s-%s-%s-%s-%s',
      substr($hash, 0, 8),
      substr($hash, 8, 4),
      '4' . substr($hash, 13, 3),
      dechex(hexdec(substr($hash, 16, 1)) & 0x3 | 0x8) . substr($hash, 17, 3),
      substr($hash, 20, 12)
    );
  }

  /**
   * Wrap a command with test environment variables.
   *
   * @param string $command The command to wrap
   * @return string Command with environment variables prepended
   */
  public static function wrapCommand(string $command): string
  {
    $envVars = [];
    foreach (self::$context as $key => $value) {
      $envKey = 'TEST_' . strtoupper($key);
      $envVars[] = escapeshellarg($envKey . '=' . $value);
    }

    return 'env ' . implode(' ', $envVars) . ' ' . $command;
  }

  /**
   * Get all test context as an array.
   *
   * @return array<string, mixed>
   */
  public static function all(): array
  {
    if (!self::$initialized) {
      self::init();
    }

    return self::$context;
  }

  /**
   * Reset test context (for testing only).
   */
  public static function reset(): void
  {
    self::$context = [];
    self::$initialized = false;
  }
}
