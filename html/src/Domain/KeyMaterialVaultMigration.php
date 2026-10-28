<?php declare(strict_types=1);

namespace PayCal\Domain;

/** Idempotent migration using compare and set, without exposing wrapper values. */
final class KeyMaterialVaultMigration
{
  /** @return array{legacy: int, sealed: int, migrated: int, conflicts: int} */
  public static function run(\Redis $redis, bool $apply = false): array
  {
    $prefix = $redis->getOption(\Redis::OPT_PREFIX);
    if (!is_string($prefix)) {
      throw new \RuntimeException('Invalid Redis key prefix.');
    }
    $cursor = '0';
    $counts = ['legacy' => 0, 'sealed' => 0, 'migrated' => 0, 'conflicts' => 0];
    do {
      $scan = $redis->rawCommand('SCAN', $cursor, 'MATCH', $prefix . 'user:*', 'COUNT', '500');
      if (!is_array($scan) || count($scan) !== 2 || !is_array($scan[1]) || !is_scalar($scan[0])) {
        throw new \RuntimeException('Key material enumeration failed.');
      }
      $cursor = (string) $scan[0];
      foreach ($scan[1] as $physicalKey) {
        if (!is_string($physicalKey)) {
          throw new \RuntimeException('Invalid key material storage key.');
        }
        $key = substr($physicalKey, strlen($prefix));
        if (preg_match('/\Auser:[^:]+(?::passkey_wrapped_deks)?\z/', $key) !== 1 || $redis->type($key) !== \Redis::REDIS_HASH) {
          continue;
        }
        foreach ($redis->hGetAll($key) as $field => $value) {
          $field = (string) $field;
          if ($value === '' || !KeyMaterialVault::isSensitiveField($key, $field)) {
            continue;
          }
          if (str_starts_with($value, 'pc-km:')) {
            KeyMaterialVault::open($key, $field, $value);
            $counts['sealed']++;
            continue;
          }
          $counts['legacy']++;
          if (!$apply) {
            continue;
          }
          $sealed = KeyMaterialVault::seal($key, $field, $value);
          if (!hash_equals($value, KeyMaterialVault::open($key, $field, $sealed))) {
            throw new \RuntimeException('Key material round trip verification failed.');
          }
          $script = <<<'LUA'
if redis.call('HGET', KEYS[1], ARGV[1]) ~= ARGV[2] then return 0 end
redis.call('HSET', KEYS[1], ARGV[1], ARGV[3])
return 1
LUA;
          $result = $redis->eval($script, [$key, $field, $value, $sealed], 1);
          if ($result === 1) {
            $counts['migrated']++;
          } else {
            $counts['conflicts']++;
          }
        }
      }
    } while ($cursor !== '0');
    return $counts;
  }
}
