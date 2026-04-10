<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;
use PayCal\Domain\Config\Environment;

/**
 * Database.php
 *
 * Purpose: Central Redis access gateway for read/write clients, scan helpers,
 * transactional operations, and replica-aware consistency behavior.
 *
 * Developer notes:
 * - This class is the persistence boundary for most of the application and has
 *   wide blast radius.
 * - Read/replica fallback behavior, scan defaults, and transaction helpers are
 *   operational contracts, not convenience details.
 * - Avoid bypassing this gateway with ad hoc Redis client usage unless the
 *   path is extremely performance-sensitive and intentionally reviewed.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */




/**
 * Redis persistence gateway.
 *
 * Responsibilities:
 * - Create and reuse configured Redis client instances.
 * - Expose safe convenience helpers for common storage patterns.
 * - Centralize read/write consistency and fallback semantics.
 */
class Database
{
  private const DEFAULT_SCAN_COUNT = 100;
  private const DEFAULT_KEY_SCAN_COUNT = 10000;
  private const MAX_SCAN_ITERATIONS = 10000;

  private static ?Redis $readInstance = null;

  private static ?Redis $writeInstance = null;

  /**
   * Initializes a new instance.
   */
  private function __construct()   { }
  /**
   * Prevents cloning or customizes clone behavior.
   */
  private function __clone(): void { }
  /**
   * Rehydrates the object after unserialization.
   */
  public function __wakeup(): void { }


  /**
   * Consistency policy: Read from replica with primary fallback.
   * Handles replica lag gracefully and tracks fallback events.
   *
   * @param callable $readOp    Function to execute on read replica
   * @param callable $fallbackOp Function to execute on primary if replica returns empty
   * @return mixed Result from read operation
   */
  private static function readWithFallback(callable $readOp, callable $fallbackOp): mixed
  {
    $result = $readOp(self::getReadInstance());

    // Empty result may indicate replica lag - fallback to primary
    $isEmpty = (is_array($result) && [] === $result) || (is_string($result) && '' === $result) || (is_bool($result) && !$result);

    if ($isEmpty) {
      try {
        $result = $fallbackOp(self::getWriteInstance());

        // Track replica misses for monitoring replication health
        if (class_exists('PayCal\\Observability\\Lens')) {
          \PayCal\Observability\Lens::increment('redis.replica.fallback');
        }
      } catch (\Throwable $e) {
        // Suppress fallback errors, return original empty result
      }
    }

    return $result;
  }


  /**
   * Sets a field in a Database hash.
   * @param string $key    Database hash key
   * @param array<string, string>  $fields Associative array of field => value
   */
  public static function hset(string $key, array $fields): void
  {
    if ([] === $fields) {
      return;
    }

    $normalized = [];
    foreach ($fields as $field => $value) {
      $normalized[(string) $field] = (string) $value;
    }

    self::getWriteInstance()->hmset($key, $normalized);
  }


  /**
   * Deletes one or more Database keys matching the given pattern.
   * @param string $pattern Pattern to delete
   */
  public static function del(string $pattern): int|false
  {
    $keys = self::getReadInstance()->client->keys($pattern);
    $deletedCount = 0;
    if (!empty($keys)) {
      foreach ($keys as $key) {
        $deleted = self::getWriteInstance()->client->del($key);
        if ($deleted) {
          $deletedCount++;
        }
      }
      return $deletedCount;
    }
    return false;
  }


  /**
   * Returns the shared Database instance, initializing it if needed.
   * @throws \RuntimeException on failure to connect or authenticate
   */
  public static function getInstance(): Redis
  {
    $instance = self::getWriteInstance();

    return $instance;
  }


  /**
   * Returns the shared read-only Database instance.
   */
  public static function getReadInstance(): Redis
  {
    if (is_null(self::$readInstance)) {
      $server = Environment::redisServer();
      $port = Environment::redisReadPort();
      $db = Environment::redisDb();
      if (class_exists('PayCal\\Domain\\Log')) {
        \PayCal\Domain\Log::debug('[REDIS] Connecting (read) to ' . $server . ':' . $port . ' db=' . $db);
      }
      self::$readInstance = new Redis($server, $port);
    }
    $dbNum = self::$readInstance->client->getDbNum();
    if (class_exists('PayCal\\Domain\\Log')) {
      \PayCal\Domain\Log::debug('[REDIS] getReadInstance dbNum=' . $dbNum);
    }
    return self::$readInstance;
  }


  /**
   * Returns the shared write Database instance.
   */
  public static function getWriteInstance(): Redis
  {
    if (is_null(self::$writeInstance)) {
      self::$writeInstance = new Redis(Environment::redisServer(), Environment::redisWritePort());
    }
    $dbNum = self::$writeInstance->client->getDbNum();

    return self::$writeInstance;
  }


  /**
   * Retrieves a value from the underlying instance by key.
   * Always returns a string-cast value.
   * @param string $key the lookup key
   * @return string the stored value or empty string if missing
   */
  public static function get(string $key): string
  {
    $raw = self::getReadInstance()->client->get($key);
    $value = is_scalar($raw) ? (string) $raw : '';

    return $value;
  }


  /**
   * Stores a value in the underlying instance by key, with optional expiry.
   * @param string   $key           the storage key
   * @param string   $value         the value to store
   * @param null|int $expireSeconds optional TTL in seconds
   */
  public static function set(string $key, string $value, ?int $expireSeconds = null): void
  {
    if (null !== $expireSeconds && $expireSeconds > 0) {
      self::getWriteInstance()->client->setex($key, $expireSeconds, $value);

      return;
    }
    self::getWriteInstance()->client->set($key, $value);
  }


  /**
   * Retrieves a specific field from a Database hash.
   * @param string $key   Database hash key
   * @param string $field Field name within the hash
   *
   * @return string Retrieved value or empty string
   */
  public static function hget(string $key, string $field): string
  {
    $result = self::readWithFallback(
      fn($redis) => $redis->hget($key, $field),
      fn($redis) => $redis->hget($key, $field)
    );

    return is_scalar($result) ? (string) $result : '';
  }


  /**
   * Retrieves all field-value pairs from a Database hash.
   * @param string $key Database hash key
   * @return array<string> Associative array of all fields
   */
  public static function hgetall(string $key): array
  {
    $result = self::readWithFallback(
      fn($redis) => $redis->hgetall($key),
      fn($redis) => $redis->hgetall($key)
    );

    if (!is_array($result)) {
      return [];
    }

    $normalized = [];
    foreach ($result as $field => $value) {
      $normalized[(string) $field] = is_scalar($value) ? (string) $value : '';
    }

    return $normalized;
  }


  /**
   * Retrieves all field names from a Database hash.
   * @param string $key Database hash key
   * @return array<string> Array of field names
   */
  public static function hkeys(string $key): array
  {
    $result = (array) self::getReadInstance()->hkeys($key);

    return $result;
  }


  /**
   * Iterates over Database keys matching a pattern using SCAN.
   * @param string $pattern   Pattern to match (e.g. "user:*")
   * @param int    $scanCount Number of keys per batch
   * @return array<string> Matching Database keys
   */
  public static function scanKeys(string $pattern, int $scanCount = self::DEFAULT_KEY_SCAN_COUNT): array
  {
    $redis   = self::getReadInstance();
    $rCursor = null;
    $keys    = [];
    $iterations = 0;

    do {
      $scanResult = $redis->scan($rCursor, $pattern, $scanCount);
      ++$iterations;

      foreach ($scanResult as $key) {
        $keys[] = $key;
      }

      if ($iterations >= self::MAX_SCAN_ITERATIONS) {
        if (class_exists('PayCal\\Domain\\Log')) {
          \PayCal\Domain\Log::warn(
            '[REDIS][SCAN] Iteration guard triggered pattern=' . $pattern
            . ' scan_count=' . (string) $scanCount
            . ' cursor=' . (string) $rCursor
            . ' iterations=' . (string) $iterations
          );
        }
        break;
      }

    } while ($rCursor !== 0);

    sort($keys, SORT_STRING);

    return $keys;
  }


  /**
   * Sets an expiry time on a key.
   * @param string $key     Key name
   * @param int    $seconds Expiry time in seconds
   * @return bool True if successful
   */
  public static function expire(string $key, int $seconds): bool
  {
    return (bool) self::getWriteInstance()->expire($key, $seconds);
  }


  /**
   * Checks if a Database key exists.
   * @param string $key Key name
   * @return bool True if the key exists
   */
  public static function exists(string $key): bool
  {
    return (bool) self::readWithFallback(
      fn($redis) => $redis->exists($key),
      fn($redis) => $redis->exists($key)
    );
  }

  /**
   * Get the time-to-live (TTL) of a Database key in seconds.
   * @param string $key Key name
   * @return int TTL in seconds, -1 if no TTL set, -2 if key doesn't exist
   */
  public static function ttl(string $key): int
  {
    return (int) self::getReadInstance()->ttl($key);
  }


  /**
   * Execute multiple Redis commands in a pipeline.
   *
   * @param callable $callback Receives the raw Redis instance to queue commands.
   *                           Example usage:
   *                           Database::multi(function($r) {
   *                           $r->hSet("key1","field","value");
   *                           $r->hDel("key2","field1","field2");
   *                           });
   *
   * @return array<mixed> Returns an array of responses from exec()
   */
  public static function multi(callable $callback): array
  {
    $redis = self::getWriteInstance()->client;
    $redis->multi(\Redis::PIPELINE);
    $callback($redis);

    return (array) $redis->exec();
  }

  /**
   * Execute a block of Redis commands in a single MULTI/EXEC transaction.
   * All queued commands run atomically; partial failure is not possible
   * within a connected session.
   *
   * Usage:
   *   Database::transaction(function (\Redis $r) use ($key, $value): void {
   *     $r->hMSet($key, $value);
   *     $r->sAdd($indexKey, $memberId);
   *   });
   *
   * @param callable(\Redis): void $callback Commands to queue
   * @return array<mixed> Results from exec()
   */
  public static function transaction(callable $callback): array
  {
    $redis = self::getWriteInstance()->client;
    $redis->multi(\Redis::MULTI);
    $callback($redis);

    return (array) $redis->exec();
  }


  /**
   * Returns all members of a Database set.
   * @param string $key Database set key
   * @return array<string> Members of the set
   */
  public static function smembers(string $key): array
  {
    return (array) self::getReadInstance()->client->sMembers($key);
  }


  /**
   * Add one or more members to a set.
   * @param string $key       Set key
   * @param mixed  ...$values Values to add
   * @return int Number of members added to the set
   */
  public static function sadd(string $key, ...$values): int
  {
    return self::getWriteInstance()->client->sAdd($key, ...$values);
  }


  /**
   * Removes the specified key using UNLINK (non-blocking delete).
   * @param string $key Key to unlink
   * @return int Number of keys unlinked (0 or 1)
   */
  public static function unlink(string $key): int
  {
    return self::getWriteInstance()->client->unlink($key);
  }


  /**
   * Removes the expiration from a key.
   * @param string $key Key name
   * @return bool True if successful
   */
  public static function persist(string $key): bool
  {
    return (bool) self::getWriteInstance()->client->persist($key);
  }


  /**
   * Checks if a field exists in a Database hash.
   * @param string $key    Database hash key
   * @param string $string Field name
   * @return bool True if the field exists
   */
  public static function hexists(string $key, string $string): bool
  {
    return (bool) self::readWithFallback(
      fn($redis) => $redis->client->hExists($key, $string),
      fn($redis) => $redis->client->hExists($key, $string)
    );
  }


  /**
   * Deletes one or more fields from a hash.
   * @param string $key       Database hash key
   * @param string ...$fields Field name(s) to delete
   * @return int Number of fields deleted
   */
  public static function hdel(string $key, string ...$fields): int
  {
    if (empty($fields)) {
      return 0;
    }

    return (int) self::getWriteInstance()->client->hDel($key, ...$fields);
  }


  /**
   * Increments the value of a key by 1 (or initializes to 1 if not set).
   * @param string $key the Database key to increment
   * @return int the new value after increment
   */
  public static function incr(string $key): int
  {
    return (int) self::getWriteInstance()->client->incr($key);
  }


  /**
   * Push one value onto the left side of a Redis list.
   *
   * @param string $key List key
   * @param string $value Value to push
   * @return int New list length
   */
  public static function lpush(string $key, string $value): int
  {
    return (int) self::getWriteInstance()->client->lPush($key, $value);
  }


  /**
   * Pop one value from the right side of a Redis list.
   *
   * @param string $key List key
   * @return null|string Popped value or null when list is empty
   */
  public static function rpop(string $key): ?string
  {
    $value = self::getWriteInstance()->client->rPop($key);
    if ($value === false || !is_scalar($value)) {
      return null;
    }

    return (string) $value;
  }


  /**
   * Trim a Redis list to an inclusive index range.
   *
   * @param string $key List key
   * @param int $start Inclusive start index
   * @param int $stop Inclusive stop index
   * @return bool True on success
   */
  public static function ltrim(string $key, int $start, int $stop): bool
  {
    return (bool) self::getWriteInstance()->client->lTrim($key, $start, $stop);
  }


  /**
   * Publish a message to a Redis pub/sub channel.
   *
   * @param string $channel Pub/sub channel name
   * @param string $message JSON/text payload
   * @return int Number of subscribers that received the message
   */
  public static function publish(string $channel, string $message): int
  {
    $normalizedChannel = trim($channel);
    if ($normalizedChannel === '') {
      return 0;
    }

    $result = self::getWriteInstance()->client->publish($normalizedChannel, $message);

    return is_int($result) ? $result : (int) $result;
  }


  /**
   * Get the length of a Redis list.
   *
   * @param string $key List key
   * @return int Current list length
   */
  public static function llen(string $key): int
  {
    return (int) self::getReadInstance()->client->lLen($key);
  }


  /**
   * Renames a Database key.
   * @param string $oldkey The current key name
   * @param string $newkey The new key name
   * @return bool True if successful
   */
  public static function rename(string $oldkey, string $newkey): bool
  {
    return (bool) self::getWriteInstance()->client->rename($oldkey, $newkey);
  }


  /**
   * Remove members from a set.
   * @param string          $key     Set key
   * @param string|string[] $members Member(s) to remove
   * @return int Number of elements removed
   */
  public static function srem(string $key, ...$members): int
  {
    return (int) self::getWriteInstance()->client->sRem($key, ...$members);
  }


  /**
   * Get cardinality (size) of a set.
   * @param string $key Set key
   * @return null|int Number of elements in set, or null if error
   */
  public static function scard(string $key): ?int
  {
    $result = self::getReadInstance()->client->sCard($key);

    return (false !== $result && is_int($result)) ? $result : null;
  }


  /**
   * Check if member exists in a set.
   * @param string $key    Set key
   * @param string $member Member to check
   * @return int 1 if member exists, 0 if not
   */
  public static function sismember(string $key, string $member): int
  {
    return (int) self::getReadInstance()->client->sIsMember($key, $member);
  }


  // ////////////////////////////////////////////////////////////////////////////
  // CUSTOM METHODS

  /**
   * Resets fields and sets dummy values for all keys matching a pattern using pipelining.
   *
   * @param string               $pattern        Key pattern to match (e.g. "work:*")
   * @param array<string>        $fieldsToRemove Fields to delete from each hash
   * @param array<string,string> $dummyValues    Fields/values to HSET after deletion
   * @param int                  $scanCount      Number of keys per SCAN batch (passed to scanKeys)
   */
  public static function resetWorkKeysPipelineSafe(
    string $pattern,
    array $fieldsToRemove,
    array $dummyValues,
    int $scanCount = self::DEFAULT_SCAN_COUNT
  ): void {
    // Get all matching keys safely using your scanKeys helper
    $keys = self::scanKeys($pattern, $scanCount);

    if (empty($keys)) {
      return;
    }

    // Execute pipeline on all keys
    self::multi(function ($r) use ($keys, $fieldsToRemove, $dummyValues): void {
      foreach ($keys as $key) {
        Database::unlink($key);

        // delete unwanted fields
        $r->hDel($key, ...$fieldsToRemove);

        // set dummy values
        $args = [$key];
        foreach ($dummyValues as $field => $value) {
          $args[] = $field;
          $args[] = $value;
        }
        $r->hSet(...$args);
      }
    });
  }


  // ////////////////////////////////////////////////////////////////////////////
  // BACKEND-NEUTRAL METHODS
  //
  // These methods provide a higher-level abstraction that decouples domain
  // logic from Redis-specific operations. This prepares for future multi-backend
  // support (Redis + Postgres) without requiring changes to domain code.

  /**
   * Fetch a complete record by key.
   * Backend-neutral alternative to hgetall().
   *
   * @param string $key Record key
   * @return array<string, string> Record data
   */
  public static function fetchRecord(string $key): array
  {
    return self::hgetall($key);
  }

  /**
   * Store a complete record by key.
   * Backend-neutral alternative to hset().
   *
   * @param string               $key  Record key
   * @param array<string,string> $data Record data
   */
  public static function storeRecord(string $key, array $data): void
  {
    self::hset($key, $data);
  }

  /**
   * Check if a record exists.
   * Backend-neutral alternative to exists().
   *
   * @param string $key Record key
   * @return bool True if record exists
   */
  public static function recordExists(string $key): bool
  {
    return self::exists($key);
  }

  /**
   * Get a single field from a record.
   * Backend-neutral alternative to hget().
   *
   * @param string $key   Record key
   * @param string $field Field name
   * @return string Field value or empty string
   */
  public static function getField(string $key, string $field): string
  {
    return self::hget($key, $field);
  }

  /**
   * Set a single field in a record.
   * Backend-neutral alternative to hset().
   *
   * @param string $key   Record key
   * @param string $field Field name
   * @param string $value Field value
   */
  public static function setField(string $key, string $field, string $value): void
  {
    self::hset($key, [$field => $value]);
  }

  /**
   * Delete a record.
   * Backend-neutral alternative to unlink().
   *
   * @param string $key Record key
   * @return int Number of records deleted
   */
  public static function deleteRecord(string $key): int
  {
    return self::unlink($key);
  }

  /**
   * Check if a field exists in a record.
   * Backend-neutral alternative to hexists().
   *
   * @param string $key   Record key
   * @param string $field Field name
   * @return bool True if field exists
   */
  public static function hasField(string $key, string $field): bool
  {
    return self::hexists($key, $field);
  }

  /**
   * Get Redis server INFO statistics.
   * Used for health monitoring and metrics collection.
   * 
   * @return array<string, mixed> Parsed INFO command output
   */
  public static function info(): array
  {
    return self::getReadInstance()->info();
  }

}


