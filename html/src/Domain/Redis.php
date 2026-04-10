<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;
use PayCal\Domain\Config\Environment;

/**
 * Redis.php
 *
 * Purpose: Thin native Redis client wrapper that applies app-level connection,
 * timeout, auth, and environment defaults.
 *
 * Developer notes:
 * - This wrapper is low-level infrastructure used by Database and other core
 *   storage paths; connection behavior changes here are system-wide.
 * - Keep transport and configuration concerns here, not higher-level business logic.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Native Redis client wrapper.
 *
 * Responsibilities:
 * - Establish configured Redis connections.
 * - Apply runtime connection/read timeout defaults.
 * - Expose the underlying client for higher-level storage gateways.
 */
class Redis
{
    public const OPT_PREFIX = \Redis::OPT_PREFIX;

    /**
     * Native Redis client instance.
     */
    public \Redis $client;

    /**
     * Create Redis connection.
     *
     * @param string $host Redis host
     * @param int    $port Redis port
     */
    public function __construct(string $host = '127.0.0.1', int $port = 6379)
    {
        $rawTimeout = getenv('REDIS_CONNECT_TIMEOUT');
        $connectTimeout = is_string($rawTimeout) ? (float) $rawTimeout : 2.5;
        if ($connectTimeout <= 0.0) {
            $connectTimeout = 2.5;
        }

        $this->client = new \Redis();
        $this->client->connect($host, $port, $connectTimeout);
        $this->client->setOption(\Redis::OPT_READ_TIMEOUT, $connectTimeout);

        if (Environment::redisAuthEnabled()) {
            $user = Environment::redisUser();
            $password = Environment::redisPassword();

            $authenticated = '' !== $user
                ? $this->client->auth([$user, $password])
                : $this->client->auth($password);

            if (false === $authenticated) {
                throw new \RuntimeException('Redis authentication failed. Check REDIS_AUTH_ENABLED, REDIS_USER, and REDIS_PASSWORD.');
            }
        }

        $prefix = getenv('REDIS_PREFIX');
        if (is_string($prefix) && '' !== $prefix) {
            $this->client->setOption(\Redis::OPT_PREFIX, $prefix);
        }
    }

    /**
     * Delete a key.
     *
     * @param string $key
     * @return int Number of keys deleted
     */
    public function unlink(string $key): int
    {
        Lens::increment('redis_ops');
        return $this->client->unlink($key);
    }

    /**
     * Get string value by key.
     *
     * @param string $key Redis key
     *
     * @return null|string Null if key does not exist
     */
    public function get(string $key): ?string
    {
        Lens::increment('redis_ops');
        $result = $this->client->get($key);
        if (false === $result) {
            return null;
        }

        return is_scalar($result) ? (string) $result : null;
    }

    /**
     * Get single hash field.
     *
     * @param string $key   Redis hash key
     * @param string $field Hash field
     *
     * @return null|string Null if field not found
     */
    public function hget(string $key, string $field): ?string
    {
        Lens::increment('redis_ops');
        $result = $this->client->hGet($key, $field);
        return false === $result ? null : (string) $result;
    }

    /**
     * Get multiple hash fields.
     *
     * @param string            $key    Redis hash key
     * @param array<int,string> $fields Hash fields
     *
     * @return array<string,null|string> Field-value map
     */
    public function hmget(string $key, array $fields): array
    {
        Lens::increment('redis_ops');
        /** @var array<int,null|false|string> $result */
        $result = $this->client->hMGet($key, $fields);
        $mapped = [];
        foreach ($fields as $index => $field) {
            $value = $result[$field] ?? null;
            $mapped[(string) $field] = false !== $value && null !== $value
                ? (string) $value
                : null;
        }
        return $mapped;
    }

    /**
     * Set single hash field.
     *
     * @param string $key   Redis hash key
     * @param string $field Hash field
     * @param string $value Value
     *
     * @return bool True on success
     */
    public function hset(string $key, string $field, string $value): bool
    {
        Lens::increment('redis_ops');
        return false !== $this->client->hSet($key, $field, (string) $value);
    }

    /**
     * Set multiple hash fields in one operation.
     *
     * @param string               $key    Redis hash key
     * @param array<string,string> $fields Field-value map
     *
     * @return bool True on success
     */
    public function hmset(string $key, array $fields): bool
    {
        Lens::increment('redis_ops');
        if ([] === $fields) {
            return true;
        }

        return $this->client->hMSet($key, $fields);
    }

    /**
     * Get all hash fields and values.
     *
     * @param string $key Redis hash key
     *
     * @return array<string,string> Empty array if none
     */
    public function hgetall(string $key): array
    {
        Lens::increment('redis_ops');
        Lens::timeStart('redis_hgetall');
        $result = $this->client->hGetAll($key);
        Lens::timeEnd('redis_hgetall');
        return false === $result ? [] : $result;
    }

    /**
     * Get all hash field names.
     *
     * @param string $key Redis hash key
     *
     * @return array<int,string> Empty array if none
     */
    public function hkeys(string $key): array
    {
        $result = $this->client->hKeys($key);
        return false === $result ? [] : $result;
    }

    /**
     * Check if key exists.
     *
     * @param string $key Redis key
     *
     * @return bool True if exists
     */
    public function exists(string $key): bool
    {
        return $this->client->exists($key) > 0;
    }

    /**
     * Set expiration on key.
     *
     * @param string $key     Redis key
     * @param int    $seconds Time to live in seconds
     *
     * @return bool True on success
     */
    public function expire(string $key, int $seconds): bool
    {
        return $this->client->expire($key, $seconds);
    }

    /**
     * Get TTL for key.
     *
     * @param string $key Redis key
     *
     * @return int TTL in seconds or -1/-2 per Redis semantics
     */
    public function ttl(string $key): int
    {
        return $this->client->ttl($key);
    }

    /**
     * Retrieve keys by pattern.
     *
     * @param string $pattern Search pattern
     *
     * @return array<int,string> Empty array if none
     */
    public function keys(string $pattern): array
    {
        $result = $this->client->keys($pattern);
        return false === $result ? [] : $result;
    }

    /**
     * SCAN wrapper.
     *
     * @param int|null $cursor  Scan cursor (passed by reference)
     * @param string   $pattern Match pattern
     * @param int      $count   Batch size
     *
     * @return array<int,string> Result keys
     */
    public function scan(?int &$cursor, string $pattern, int $count = 10000): array
    {
        $result = $this->client->scan($cursor, $pattern, $count);
        return false === $result ? [] : $result;
    }

    /**
     * Begin pipeline mode.
     *
     * @return \Redis Native client in pipeline state
     */
    public function pipeline(): \Redis
    {
        $this->client->multi(\Redis::PIPELINE);
        return $this->client;
    }

    /**
     * Get Redis server INFO statistics.
     * 
     * @return array<string, mixed> Parsed INFO command output
     */
    public function info(): array
    {
        Lens::increment('redis_ops');
        $result = $this->client->info();
        return is_array($result) ? $result : [];
    }
}
