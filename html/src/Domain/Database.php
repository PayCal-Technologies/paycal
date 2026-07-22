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
  private const PIPELINE_BATCH_SIZE = 2000;

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

    if (!$isEmpty) {
      return $result;
    }

    try {
      $result = $fallbackOp(self::getWriteInstance());

      // Track replica misses for monitoring replication health
      if (class_exists('PayCal\\Observability\\Lens')) {
        \PayCal\Observability\Lens::increment('redis.replica.fallback');
      }
    } catch (\Throwable $e) {
      // Suppress fallback errors, return original empty result
    }

    return $result;
  }


  /**   * Sets fields in a hash and applies a TTL atomically (MULTI/EXEC).
   *
   * Use this everywhere a hset is immediately followed by expire on the same
   * key.  Without atomicity, a process crash between the two calls leaves the
   * key alive forever with no expiry.
   *
   * @param string                $key     Hash key
   * @param array<string, string> $fields  Field => value map
   * @param int                   $ttlSeconds TTL in seconds (must be > 0)
   */
  public static function hsetex(string $key, array $fields, int $ttlSeconds): void
  {
    if ([] === $fields || $ttlSeconds <= 0) {
      return;
    }

    $normalized = [];
    foreach ($fields as $field => $value) {
      $normalized[(string) $field] = (string) $value;
    }

    self::transaction(function (\Redis $r) use ($key, $normalized, $ttlSeconds): void {
      $r->hMSet($key, $normalized);
      $r->expire($key, $ttlSeconds);
    });
  }


  /**   * Sets a field in a Database hash.
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
   * Atomically increments a hash field by the given amount (HINCRBY).
   *
   * Use instead of hget()+hset() when incrementing integer counters stored in
   * a hash. The increment is atomic — no read-modify-write race.
   *
   * @param string $key   Hash key
   * @param string $field Hash field to increment
   * @param int    $by    Increment amount (positive or negative)
   * @return int  New value of the field after the increment
   */
  public static function hincrby(string $key, string $field, int $by = 1): int
  {
    $result = self::getWriteInstance()->client->hIncrBy($key, $field, $by);
    return is_int($result) ? $result : 0;
  }


  /**
   * Deletes one or more Database keys matching the given pattern.
   *
   * IMPORTANT: Key enumeration must use the write instance. Using the read
   * replica here created a silent failure window: under replica lag the replica
   * returned an empty key list, so the subsequent DEL on the primary was never
   * issued. Affected callers include destroySession() (logout) and
   * validateCSRFToken() (nonce invalidation), making one-shot keys reusable.
   *
   * Uses SCAN instead of KEYS to avoid blocking the Redis event loop on large
   * keyspaces. KEYS is O(N) and stalls all other clients while it runs.
   *
   * @param string $pattern Pattern to delete
   */
  public static function del(string $pattern): int|false
  {
    $redis        = self::getWriteInstance();
    $cursor       = null;
    $deletedCount = 0;
    $iterations   = 0;

    do {
      $keys = $redis->scan($cursor, $pattern, self::DEFAULT_SCAN_COUNT);
      ++$iterations;

      foreach ($keys as $key) {
        $deleted = $redis->client->del($key);
        if ($deleted) {
          $deletedCount++;
        }
      }

      if ($iterations >= self::MAX_SCAN_ITERATIONS) {
        if (class_exists('PayCal\\Domain\\Log')) {
          \PayCal\Domain\Log::warn(
            '[REDIS][DEL] Iteration guard triggered pattern=' . $pattern
            . ' cursor=' . (string) $cursor
            . ' iterations=' . (string) $iterations
          );
        }
        break;
      }

    } while ($cursor !== 0);

    return $deletedCount > 0 ? $deletedCount : false;
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
      $server = Environment::redisServer();
      $port = Environment::redisWritePort();
      $db = Environment::redisDb();
      if (class_exists('PayCal\\Domain\\Log')) {
        \PayCal\Domain\Log::debug('[REDIS] Connecting (write) to ' . $server . ':' . $port . ' db=' . $db);
      }
      self::$writeInstance = new Redis($server, $port);
    }
    $dbNum = self::$writeInstance->client->getDbNum();
    if (class_exists('PayCal\\Domain\\Log')) {
      \PayCal\Domain\Log::debug('[REDIS] getWriteInstance dbNum=' . $dbNum);
    }
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
   * Atomically read and delete a string key (GETDEL).
   *
   * Use instead of get() + del() for single-use tokens (CSRF nonces, one-time
   * codes).  A separate get+del pair exposes a replay window between the two
   * round trips; GETDEL closes it.
   *
   * @param string $key the storage key
   * @return string the value that was stored, or empty string if key was absent
   */
  public static function getdel(string $key): string
  {
    $result = self::getWriteInstance()->getdel($key);
    return $result ?? '';
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
   * Atomically sets key to value with TTL only if the key does not exist (SET NX EX).
   *
   * Returns true if the key was set (caller claimed it), false if it already
   * existed (caller should treat this as a duplicate / throttled condition).
   *
   * Use this instead of exists()+set() to eliminate the TOCTOU race where two
   * concurrent requests both observe key absent and both write.
   *
   * @param string $key           Storage key
   * @param string $value         Value to store
   * @param int    $expireSeconds TTL in seconds (required — an NX key without TTL would never expire)
   * @return bool True if the key was newly set, false if it already existed
   */
  public static function setnx(string $key, string $value, int $expireSeconds): bool
  {
    $result = self::getWriteInstance()->client->set($key, $value, ['nx', 'ex' => $expireSeconds]);
    // PhpRedis returns true on success, false or null when the key already exists.
    return $result === true;
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
   * Retrieves specific fields from a Database hash (HMGET).
   *
   * Use instead of hgetall() when only a subset of fields is needed — avoids
   * transferring the full hash over the wire for a partial read.
   *
   * Returns an array keyed by field name; missing fields map to empty string.
   *
   * @param string            $key    Database hash key
   * @param array<int,string> $fields Field names to fetch
   * @return array<string,string> Field => value map (missing fields = '')
   */
  public static function hmget(string $key, array $fields): array
  {
    if ([] === $fields) {
      return [];
    }

    $result = self::readWithFallback(
      fn($redis) => $redis->hmget($key, $fields),
      fn($redis) => $redis->hmget($key, $fields)
    );

    if (!is_array($result)) {
      return array_fill_keys($fields, '');
    }

    $normalized = [];
    foreach ($fields as $field) {
      $value = $result[$field] ?? null;
      $normalized[$field] = is_string($value) ? $value : '';
    }

    return $normalized;
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
   * Retrieves all field-value pairs for many hashes in one round trip (pipelined HGETALL).
   *
   * Use instead of calling hgetall() in a loop — N sequential round trips become
   * a single pipelined batch. Missing keys map to an empty array.
   *
   * @param array<int, string> $keys Database hash keys
   * @return array<string, array<string, string>> Key => field/value map (missing keys = [])
   */
  public static function pipelineHgetall(array $keys): array
  {
    $normalizedKeys = [];
    foreach ($keys as $key) {
      if ($key !== '') {
        $normalizedKeys[] = $key;
      }
    }

    if ([] === $normalizedKeys) {
      return [];
    }

    $results = self::pipelineHgetallOn(self::getReadInstance()->client, $normalizedKeys);

    // Preserve the read-replica fallback contract of hgetall(): keys that came
    // back empty may be victims of replica lag, so retry them as one pipelined
    // batch against the primary. No empties (the common case) costs nothing.
    $emptyKeys = [];
    foreach ($results as $key => $fields) {
      if ([] === $fields) {
        $emptyKeys[] = $key;
      }
    }

    if ([] !== $emptyKeys) {
      try {
        $fallback = self::pipelineHgetallOn(self::getWriteInstance()->client, $emptyKeys);
        foreach ($fallback as $key => $fields) {
          if ([] !== $fields) {
            $results[$key] = $fields;
            if (class_exists('PayCal\\Observability\\Lens')) {
              \PayCal\Observability\Lens::increment('redis.replica.fallback');
            }
          }
        }
      } catch (\Throwable $e) {
        // Suppress fallback errors, keep replica results
      }
    }

    return $results;
  }


  /**
   * @param array<int, string> $keys
   * @return array<string, array<string, string>>
   */
  private static function pipelineHgetallOn(\Redis $redis, array $keys): array
  {
    $results = [];

    foreach (array_chunk($keys, self::PIPELINE_BATCH_SIZE) as $chunk) {
      try {
        $redis->multi(\Redis::PIPELINE);
        foreach ($chunk as $key) {
          $redis->hGetAll($key);
        }
        $responses = (array) $redis->exec();
      } catch (\Throwable $e) {
        self::resetQueuedRedisMode($redis);
        throw $e;
      }

      foreach ($chunk as $index => $key) {
        $raw = $responses[$index] ?? [];
        $normalized = [];
        if (is_array($raw)) {
          foreach ($raw as $field => $value) {
            $normalized[(string) $field] = is_scalar($value) ? (string) $value : '';
          }
        }
        $results[$key] = $normalized;
      }
    }

    return $results;
  }


  /**
   * Iterates over Database keys matching a pattern using SCAN.
   * @param string $pattern   Pattern to match (e.g. "user:*")
   * @param int    $scanCount Number of keys per batch
   * @return array<string> Matching Database keys
   */
  public static function scanKeys(string $pattern, int $scanCount = self::DEFAULT_KEY_SCAN_COUNT): array
  {
    return self::scanKeysOnInstance(self::getReadInstance(), $pattern, $scanCount);
  }


  /**
   * SCAN on the write primary — use before unlink/del on enumerated keys.
   *
   * Read-replica enumeration can miss freshly written session/CSRF keys under
   * replication lag, leaving one-shot tokens reusable after logout.
   *
   * @param string $pattern   Pattern to match (e.g. "session:*")
   * @param int    $scanCount Number of keys per batch
   * @return array<string> Matching Database keys
   */
  public static function scanKeysForWrite(string $pattern, int $scanCount = self::DEFAULT_KEY_SCAN_COUNT): array
  {
    return self::scanKeysOnInstance(self::getWriteInstance(), $pattern, $scanCount);
  }


  /**
   * @return array<string>
   */
  private static function scanKeysOnInstance(Redis $redis, string $pattern, int $scanCount): array
  {
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
   * Block until replicas acknowledge the most recent write (WAIT).
   *
   * Call immediately after security-critical hsetex/set writes on paths where
   * the response redirects to a page that reads the same key.  Without WAIT,
   * a replica that has not caught up yet can return stale/empty data, making
   * the user appear unauthenticated immediately after login.
   *
   * Returns the count of replicas that acknowledged within the timeout.  On a
   * single-instance deployment (no replicas) WAIT returns 0 immediately.
   *
   * @param int $numReplicas Minimum replicas to wait for
   * @param int $timeoutMs   Max wait in milliseconds (50 is safe for LAN replication)
   * @return int Replicas that acknowledged
   */
  public static function wait(int $numReplicas = 1, int $timeoutMs = 50): int
  {
    return self::getWriteInstance()->wait($numReplicas, $timeoutMs);
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
    try {
      $redis->multi(\Redis::PIPELINE);
      $callback($redis);

      return (array) $redis->exec();
    } catch (\Throwable $e) {
      self::resetQueuedRedisMode($redis);
      throw $e;
    }
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
    try {
      $redis->multi(\Redis::MULTI);
      $callback($redis);

      return (array) $redis->exec();
    } catch (\Throwable $e) {
      self::resetQueuedRedisMode($redis);
      throw $e;
    }
  }

  /**
   * Best-effort cleanup for shared persistent clients left in MULTI/PIPELINE.
   */
  private static function resetQueuedRedisMode(\Redis $redis): void
  {
    try {
      $redis->discard();
    } catch (\Throwable $discardError) {
      try {
        $redis->exec();
      } catch (\Throwable $execError) {
      }
    }
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
    $result = self::getWriteInstance()->client->unlink($key);

    return is_int($result) ? $result : 0;
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
   * Read a range of values from a Redis list.
   *
   * @param string $key List key
   * @param int $start Inclusive start index
   * @param int $stop Inclusive stop index
   * @return array<int, string>
   */
  public static function lrange(string $key, int $start, int $stop): array
  {
    $raw = self::getReadInstance()->client->lRange($key, $start, $stop);
    if (!is_array($raw)) {
      return [];
    }

    $out = [];
    foreach ($raw as $value) {
      if (is_scalar($value)) {
        $out[] = (string) $value;
      }
    }

    return $out;
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

