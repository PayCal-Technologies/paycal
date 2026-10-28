<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

/** Prepare first, then atomically replace a day and its maintained indexes. */
final class CalendarReplacementService
{
  /** Build the conflict revision key for a calendar day. */
  public static function revisionKey(string $userUUID, string $date): string
  {
    return 'calendar:replacement:revision:' . $userUUID . ':' . $date;
  }

  /** Read the current revision without accepting non string storage values. */
  public static function currentRevision(string $userUUID, string $date): string
  {
    $value = Database::getWriteInstance()->client->get(self::revisionKey($userUUID, $date));
    if ($value === false) {
      return '0';
    }
    if (!is_string($value)) {
      throw new \RuntimeException('Invalid calendar revision.');
    }
    return $value;
  }

  /** Resolve a work key to its calendar revision key. */
  private static function revisionForWorkKey(string $key): ?string
  {
    return preg_match('/\Awork:(?:archived:)?([^:]+):(\d{4}-\d{2}-\d{2}):S[0-9a-fA-F]{9}\z/', $key, $m) === 1
      ? self::revisionKey($m[1], $m[2]) : null;
  }

  /**
   * Make ordinary writes participate in replacement conflict detection.
   * @param array<string, scalar|null> $fields
   */
  public static function writeHashIfWork(string $key, array $fields): bool
  {
    $revision = self::revisionForWorkKey($key);
    if ($revision === null) {
      return false;
    }
    $args = [$key, $revision];
    foreach ($fields as $field => $value) {
      $args[] = (string) $field;
      $args[] = (string) $value;
    }
    $script = <<<'LUA'
local t = redis.call('TYPE', KEYS[1]).ok
local r = redis.call('TYPE', KEYS[2]).ok
if (t ~= 'none' and t ~= 'hash') or (r ~= 'none' and r ~= 'string') then return -1 end
local n = redis.call('GET', KEYS[2]) or '0'
if (n ~= '0' and not string.match(n, '^[1-9]%d*$')) or tonumber(n) >= 9000000000000000 then return -1 end
redis.call('HSET', KEYS[1], unpack(ARGV))
redis.call('INCR', KEYS[2])
return 1
LUA;
    if (Database::getWriteInstance()->client->eval($script, $args, 2) !== 1) {
      throw new \RuntimeException('Work write conflict metadata invalid.');
    }
    return true;
  }

  /** Delete a work entry and advance its revision atomically. */
  public static function deleteIfWork(string $key): ?int
  {
    $revision = self::revisionForWorkKey($key);
    if ($revision === null) {
      return null;
    }
    $script = <<<'LUA'
local t = redis.call('TYPE', KEYS[2]).ok
if t ~= 'none' and t ~= 'string' then return -1 end
local n = redis.call('GET', KEYS[2]) or '0'
if (n ~= '0' and not string.match(n, '^[1-9]%d*$')) or tonumber(n) >= 9000000000000000 then return -1 end
local removed = redis.call('DEL', KEYS[1])
if removed > 0 then redis.call('INCR', KEYS[2]) end
return removed
LUA;
    $result = Database::getWriteInstance()->client->eval($script, [$key, $revision], 2);
    if (!is_int($result) || $result < 0) {
      throw new \RuntimeException('Work delete conflict metadata invalid.');
    }
    return $result;
  }

  /** Archive and restore must invalidate replacements captured before the move. */
  public static function renameIfWork(string $source, string $destination): ?bool
  {
    $revisions = array_values(array_unique(array_filter([
      self::revisionForWorkKey($source), self::revisionForWorkKey($destination),
    ])));
    if ($revisions === []) {
      return null;
    }
    $script = <<<'LUA'
for i = 3, #KEYS do
  local t = redis.call('TYPE', KEYS[i]).ok
  if t ~= 'none' and t ~= 'string' then return -1 end
  local n = redis.call('GET', KEYS[i]) or '0'
  if (n ~= '0' and not string.match(n, '^[1-9]%d*$')) or tonumber(n) >= 9000000000000000 then return -1 end
end
if redis.call('EXISTS', KEYS[1]) == 0 then return 0 end
redis.call('RENAME', KEYS[1], KEYS[2])
for i = 3, #KEYS do redis.call('INCR', KEYS[i]) end
return 1
LUA;
    $keys = array_merge([$source, $destination], $revisions);
    $result = Database::getWriteInstance()->client->eval($script, $keys, count($keys));
    if ($result !== 0 && $result !== 1) {
      throw new \RuntimeException('Work rename conflict metadata invalid.');
    }
    return $result === 1;
  }

  /**
   * SCAN MATCH uses physical prefixes; raw commands avoid extension prefix modes.
   * @return list<string>
   */
  private static function dayKeys(\Redis $redis, string $userUUID, string $date): array
  {
    $prefix = $redis->getOption(\Redis::OPT_PREFIX);
    if (!is_string($prefix)) {
      throw new \RuntimeException('Invalid Redis key prefix.');
    }
    $keys = [];
    foreach ([Keys::WORK . ':' . $userUUID . ':' . $date . ':', Keys::WORK . ':archived:' . $userUUID . ':' . $date . ':'] as $start) {
      $cursor = '0';
      $iterations = 0;
      do {
        $result = $redis->rawCommand('SCAN', $cursor, 'MATCH', $prefix . $start . '*', 'COUNT', '1000');
        if (!is_array($result) || count($result) !== 2 || !is_array($result[1]) || !is_scalar($result[0])) {
          throw new \RuntimeException('Calendar key enumeration failed.');
        }
        $cursor = (string) $result[0];
        foreach ($result[1] as $key) {
          if (!is_string($key)) {
            throw new \RuntimeException('Invalid calendar storage key.');
          }
          $key = substr($key, strlen($prefix));
          if (!str_starts_with($key, $start)) {
            throw new \RuntimeException('Unexpected calendar storage key.');
          }
          $keys[$key] = true;
          if (count($keys) > 1000) {
            throw new \RuntimeException('Calendar day exceeds supported size.');
          }
        }
        if (++$iterations > 10000) {
          throw new \RuntimeException('Calendar enumeration exceeds supported work.');
        }
      } while ($cursor !== '0');
    }
    return array_keys($keys);
  }

  /**
   * @param list<array<string, mixed>> $entries
   * @return array{deleted: int, updated: int, refresh_pending: bool}
   */
  public static function replace(string $userUUID, string $date, array $entries, string $revision, \Closure $authorize): array
  {
    if (preg_match('/\A[A-Za-z0-9_-]+\z/', $userUUID) !== 1 || !WorkEntry::validateDate($date) || count($entries) > 1000) {
      throw new \InvalidArgumentException('Invalid calendar replacement.');
    }
    $redis = Database::getWriteInstance()->client;
    $keys = [];
    $positions = [];
    $types = [];
    $expected = [];
    $add = static function (string $key, string $type) use (&$keys, &$positions, &$types, &$expected, $redis): int {
      if (isset($positions[$key])) {
        if ($types[$positions[$key] - 1] !== $type) {
          throw new \RuntimeException('Conflicting calendar key types.');
        }
        return $positions[$key];
      }
      $keys[] = $key;
      $types[] = $type;
      $raw = $redis->dump($key);
      $expected[] = sha1(is_string($raw) ? $raw : '');
      return $positions[$key] = count($keys);
    };
    $rev = $add(self::revisionKey($userUUID, $date), 'string');
    $add(Keys::USER . ':' . $userUUID, 'hash');
    $add(Keys::USER . ':' . $userUUID . ':passkey_wrapped_deks', 'hash');
    $prepared = [];
    foreach ($entries as $entry) {
      if (($entry['date'] ?? '') !== $date) {
        throw new \InvalidArgumentException('Replacement dates do not match.');
      }
      $fields = [];
      foreach ($entry as $field => $value) {
        if (!is_scalar($value) && $value !== null) {
          throw new \InvalidArgumentException('Calendar entry fields must be scalar values.');
        }
        $fields[$field] = $value;
      }
      $row = WorkEntry::prepareWorkEntry($fields, $userUUID);
      if ($row === null) {
        throw new \InvalidArgumentException('Calendar entry preparation failed.');
      }
      $prepared[$row['key']] = $row;
    }
    $old = self::dayKeys($redis, $userUUID, $date);
    if (count($old) > 1000) {
      throw new \RuntimeException('Calendar replacement exceeds supported day size.');
    }
    $remove = [];
    $indexRemove = [];
    $indexAdd = [];
    $indexOps = static function (string $key, array &$ops) use ($add): void {
      if (!class_exists(WorkEntryIndex::class)) {
        return;
      }
      $definition = WorkEntryIndex::mutationKeys($key);
      if ($definition === null) {
        throw new \RuntimeException('Invalid work index key.');
      }
      foreach ($definition['sets'] as $set) {
        $ops[] = [$add($set, 'set'), $key, false];
      }
      $ops[] = [$add($definition['date'], 'zset'), $key, $definition['score']];
    };
    foreach ($old as $key) {
      $remove[] = $add($key, 'hash');
      $indexOps($key, $indexRemove);
    }
    $rows = [];
    $sites = [];
    foreach ($prepared as $key => $row) {
      $flat = [];
      foreach ($row['fields'] as $field => $value) {
        $flat[] = (string) $field;
        $flat[] = (string) $value;
      }
      $rows[] = [$add($key, 'hash'), $flat];
      $indexOps($key, $indexAdd);
      $site = Keys::SITE . ':' . $userUUID . ':' . $row['site_id'];
      $sitePosition = $add($site, 'hash');
      if ($redis->hGet($site, 'status') === 'inactive') {
        $sites[] = $sitePosition;
      }
    }
    $increments = [];
    $invalidate = [];
    if (class_exists(WorkAggregateCache::class)) {
      $cache = WorkAggregateCache::invalidationKeysForDate($userUUID, $date);
      foreach ($cache['increment'] as $key) {
        $increments[] = $add($key, 'string');
      }
      foreach ($cache['delete'] as $key) {
        $invalidate[] = $add($key, 'any');
      }
    }
    if (count($keys) > 4096 || !$authorize()) {
      throw new \RuntimeException('Calendar authorization or transaction size changed.');
    }
    $payload = json_encode(compact('types', 'expected', 'rev', 'revision', 'remove', 'rows', 'sites', 'indexRemove', 'indexAdd', 'increments', 'invalidate'), JSON_THROW_ON_ERROR);
    if (strlen($payload) > 4194304) {
      throw new \RuntimeException('Calendar transaction exceeds supported size.');
    }
    $script = <<<'LUA'
local p = cjson.decode(ARGV[1])
for i, key in ipairs(KEYS) do
  local t = redis.call('TYPE', key).ok
  if p.types[i] ~= 'any' and t ~= 'none' and t ~= p.types[i] then return -2 end
  local raw = redis.call('DUMP', key) or ''
  if redis.sha1hex(raw) ~= p.expected[i] then return -1 end
end
local revision = redis.call('GET', KEYS[p.rev]) or '0'
if revision ~= p.revision then return -1 end
local counters = {p.rev}
for _, i in ipairs(p.increments) do table.insert(counters, i) end
for _, i in ipairs(counters) do
  local n = redis.call('GET', KEYS[i]) or '0'
  if (n ~= '0' and not string.match(n, '^[1-9]%d*$')) or tonumber(n) >= 9000000000000000 then return -2 end
end
-- All validation precedes all mutations. Each following command has known types.
for _, i in ipairs(p.remove) do redis.call('DEL', KEYS[i]) end
for _, op in ipairs(p.indexRemove) do
  if op[3] == false then redis.call('SREM', KEYS[op[1]], op[2])
  else redis.call('ZREM', KEYS[op[1]], op[2]) end
end
for _, row in ipairs(p.rows) do
  redis.call('DEL', KEYS[row[1]])
  redis.call('HSET', KEYS[row[1]], unpack(row[2]))
end
for _, op in ipairs(p.indexAdd) do
  if op[3] == false then redis.call('SADD', KEYS[op[1]], op[2])
  else redis.call('ZADD', KEYS[op[1]], op[3], op[2]) end
end
for _, i in ipairs(p.sites) do redis.call('HSET', KEYS[i], 'status', 'active') end
for _, i in ipairs(p.invalidate) do redis.call('DEL', KEYS[i]) end
for _, i in ipairs(counters) do redis.call('INCR', KEYS[i]) end
return 1
LUA;
    $result = $redis->eval($script, array_merge($keys, [$payload]), count($keys));
    if ($result !== 1) {
      throw new \RuntimeException('Calendar replacement conflict or invalid storage type.');
    }
    $refreshPending = false;
    try {
      \PayCal\Infrastructure\Cache\EarningsCacheService::invalidateForUser($userUUID);
      BusinessWorkspaceCache::invalidateFinancialDataForMember($userUUID, (int) substr($date, 0, 4));
      foreach ($prepared as $key => $row) {
        if (!in_array($key, $old, true)) {
          BusinessDashboardMetrics::recordWorkEntryCreated($userUUID, $date);
        }
      }
    } catch (\Throwable) {
      $refreshPending = true;
      Log::warn('[Calendar] Replacement committed; cache refresh needs retry.');
    }
    return ['deleted' => count($old), 'updated' => count($prepared), 'refresh_pending' => $refreshPending];
  }
}
