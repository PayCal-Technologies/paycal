<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * RedisSyncService.php
 *
 * Local-to-dev Redis synchronization helper for development workflows.
 *
 * Why this exists:
 * - Keep local datasets aligned with shared dev data without manual command chains.
 * - Track cadence and lock state so overlapping sync runs do not conflict.
 * - Provide an explicit online/offline mode when remote Redis is unavailable.
 */

/**
 * Coordinates scheduled or manual Redis sync cycles.
 *
 * Internal guarantees:
 * - Lock file enforces single-run execution unless force mode is requested.
 * - Sync interval and last-run state are persisted across process restarts.
 * - Operational logs are emitted for visibility into mode, flow, and failures.
 */
final class RedisSyncService
{
  private const SYNC_INTERVAL = 300; // 5 minutes in seconds
  private const STATE_FILE = '/tmp/redis-php-sync-state';
  private const LOCK_FILE = '/tmp/redis-php-sync.lock';
  private const LOG_FILE = '/var/www/paycal/dev/logs/redis-sync-php.log';

  private const DEV_HOST = 'paycal.app';
  private const DEV_PORT = 6379;
  private const LOCAL_PORT = 6379;

  /**
   * Check if sync should run based on last run time.
   */
  public static function shouldRun(): bool
  {
    if (!file_exists(self::STATE_FILE)) {
      return true;
    }

    $lastRun = (int) file_get_contents(self::STATE_FILE);
    $elapsed = time() - $lastRun;

    return $elapsed >= self::SYNC_INTERVAL;
  }

  /**
   * Run sync operation.
   * @return array<string, mixed>
   */
  public static function sync(bool $force = false): array
  {
    // Check if already running
    if (file_exists(self::LOCK_FILE) && !$force) {
      return [
          'success' => false,
          'message' => 'Sync already running',
          'timestamp' => time(),
      ];
    }

    // Check if should run
    if (!$force && !self::shouldRun()) {
      $nextRun = (int) file_get_contents(self::STATE_FILE) + self::SYNC_INTERVAL;
      $remaining = $nextRun - time();

      return [
          'success' => false,
          'message' => 'Sync not due yet',
          'next_run_in' => $remaining,
          'timestamp' => time(),
      ];
    }

    // Create lock
    file_put_contents(self::LOCK_FILE, (string) getmypid());

    try {
      self::log('INFO', 'Starting Redis sync...');

      // Check connectivity
      if (self::checkConnectivity()) {
        self::log('INFO', 'Dev server is online');

        // Check for local changes
        if (self::hasLocalChanges()) {
          self::log('WARN', 'Local changes detected, pushing first...');
          self::pushToDev();
          sleep(3);
          self::pullFromDev();
        } else {
          self::log('INFO', 'No local changes, pulling from dev...');
          self::pullFromDev();
        }

        file_put_contents(self::STATE_FILE, (string) time());

        $result = [
            'success' => true,
            'message' => 'Sync completed successfully',
            'mode' => 'online',
            'timestamp' => time(),
        ];
      } else {
        self::log('WARN', 'Dev server offline - running in standalone mode');

        $result = [
            'success' => true,
            'message' => 'Running in standalone mode',
            'mode' => 'offline',
            'timestamp' => time(),
        ];
      }

      return $result;
    } finally {
      // Remove lock
      if (file_exists(self::LOCK_FILE)) {
        unlink(self::LOCK_FILE);
      }
    }
  }

  /**
   * Get sync status.
   * @return array<string, mixed>
   */
  public static function getStatus(): array
  {
    $isOnline = self::checkConnectivity();
    $lastSync = file_exists(self::STATE_FILE)
      ? (int) file_get_contents(self::STATE_FILE)
      : 0;

    $nextSync = $lastSync > 0 ? $lastSync + self::SYNC_INTERVAL : time();
    $isRunning = file_exists(self::LOCK_FILE);

    return [
        'online' => $isOnline,
        'last_sync' => $lastSync,
        'last_sync_ago' => $lastSync > 0 ? time() - $lastSync : null,
        'next_sync' => $nextSync,
        'next_sync_in' => max(0, $nextSync - time()),
        'is_running' => $isRunning,
        'interval' => self::SYNC_INTERVAL,
    ];
  }

  /**
   * Check if dev server is reachable.
   */
  private static function checkConnectivity(): bool
  {
    $host = self::DEV_HOST;
    $timeout = 5;

    $cmd = sprintf(
      'ssh -o ConnectTimeout=%d -o BatchMode=yes %s "exit" 2>/dev/null',
      $timeout,
      escapeshellarg($host)
    );

    exec($cmd, $output, $returnCode);

    return 0 === $returnCode;
  }

  /**
   * Get last save timestamp from Redis.
   */
  private static function getLastSaveTime(bool $isLocal = true): int
  {
    if ($isLocal) {
      try {
        $redis = new Redis('127.0.0.1', self::LOCAL_PORT);

        return (int) $redis->client->lastsave();
      } catch (\Throwable $e) {
        self::log('ERROR', 'Failed to get local LASTSAVE: '.$e->getMessage());

        return 0;
      }
    } else {
      try {
        $redis = new Redis(self::DEV_HOST, self::DEV_PORT);

        return (int) $redis->client->lastsave();
      } catch (\Throwable $e) {
        self::log('ERROR', 'Failed to get remote LASTSAVE: '.$e->getMessage());

        return 0;
      }
    }
  }

  /**
   * Check if local Redis has changes since last sync.
   */
  private static function hasLocalChanges(): bool
  {
    if (!file_exists(self::STATE_FILE)) {
      return true; // No state, assume changes
    }

    $lastSync = (int) file_get_contents(self::STATE_FILE);
    $currentSave = self::getLastSaveTime(true);

    return $currentSave > $lastSync;
  }

  /**
   * Normalize Redis CONFIG GET dir response into a usable directory string.
   *
   * @param mixed  $config
   * @param string $default
   */
  private static function redisDirFromConfig(mixed $config, string $default): string
  {
    if (is_array($config)) {
      if (isset($config['dir']) && is_string($config['dir']) && '' !== $config['dir']) {
        return $config['dir'];
      }

      if (isset($config[1]) && is_string($config[1]) && '' !== $config[1]) {
        return $config[1];
      }
    }

    return $default;
  }

  /**
   * Push local changes to dev server.
   */
  private static function pushToDev(): bool
  {
    self::log('INFO', 'Pushing local Redis changes to dev server...');

    try {
      // Trigger local save
      $redis = new Redis('127.0.0.1', self::LOCAL_PORT);
      $redis->client->save();

      // Get Redis directory
      $config = $redis->client->config('GET', 'dir');
         $redisDir = self::redisDirFromConfig($config, '/usr/local/var/db/redis');
      $dumpFile = $redisDir.'/dump.rdb';

      if (!file_exists($dumpFile)) {
        self::log('ERROR', 'Local dump file not found: '.$dumpFile);

        return false;
      }

      // Transfer to dev
      $tempDump = '/tmp/redis-php-push-'.time().'.rdb';
      copy($dumpFile, $tempDump);

      $scpCmd = sprintf(
        'scp -q %s %s:/tmp/redis-mac-sync.rdb',
        escapeshellarg($tempDump),
        escapeshellarg(self::DEV_HOST)
      );

      exec($scpCmd, $output, $returnCode);

      if (0 !== $returnCode) {
        self::log('ERROR', 'Failed to transfer dump to dev');
        unlink($tempDump);

        return false;
      }

      // Replace on dev server
      $replaceCmd = sprintf(
        'ssh %s "sudo systemctl stop redis-server && '
        .'sudo cp /tmp/redis-mac-sync.rdb /var/lib/redis/dump.rdb && '
        .'sudo chown redis:redis /var/lib/redis/dump.rdb && '
        .'sudo systemctl start redis-server && '
        .'rm /tmp/redis-mac-sync.rdb"',
        escapeshellarg(self::DEV_HOST)
      );

      exec($replaceCmd, $output, $returnCode);
      unlink($tempDump);

      if (0 !== $returnCode) {
        self::log('ERROR', 'Failed to replace dump on dev server');

        return false;
      }

      self::log('SUCCESS', 'Pushed local changes to dev server');

      return true;
    } catch (\Throwable $e) {
      self::log('ERROR', 'Push failed: '.$e->getMessage());

      return false;
    }
  }

  /**
   * Pull data from dev server.
   */
  private static function pullFromDev(): bool
  {
    self::log('INFO', 'Pulling Redis data from dev server...');

    try {
      // Trigger save on dev via PHP Redis client, then download the dump via scp
      try {
        $redisDev = new Redis(self::DEV_HOST, self::DEV_PORT);
        $redisDev->client->save();
      } catch (\Throwable $e) {
        self::log('ERROR', 'Failed to trigger save on dev: '.$e->getMessage());

        return false;
      }

      sleep(1);

      // Download dev dump
      $tempDump = '/tmp/redis-php-pull-'.time().'.rdb';

      try {
        $config = $redisDev->client->config('GET', 'dir');
           $redisDir = self::redisDirFromConfig($config, '/var/lib/redis');
      } catch (\Throwable $e) {
        $redisDir = '/var/lib/redis';
      }

      $remoteDump = $redisDir.'/dump.rdb';

      $downloadCmd = sprintf(
        'scp -q %s:%s %s',
        escapeshellarg(self::DEV_HOST),
        escapeshellarg($remoteDump),
        escapeshellarg($tempDump)
      );

      exec($downloadCmd, $output, $returnCode);

      if (0 !== $returnCode || !file_exists($tempDump)) {
        self::log('ERROR', 'Failed to download dump from dev');

        return false;
      }

      // Shutdown local Redis
      $redis = new Redis('127.0.0.1', self::LOCAL_PORT);
      $config = $redis->client->config('GET', 'dir');
      $redisDir = self::redisDirFromConfig($config, '/usr/local/var/db/redis');

      // Shutdown without save
      try {
        $redis->client->save(); // Save current state first as backup
        sleep(1);
      } catch (\Throwable $e) {
        // Continue
      }

      // Replace dump file
      $localDump = $redisDir.'/dump.rdb';
      copy($tempDump, $localDump);
      unlink($tempDump);

      // Restart Redis
      exec('brew services restart redis >/dev/null 2>&1 || redis-server --daemonize yes');
      sleep(2);

      self::log('SUCCESS', 'Pulled data from dev server');

      return true;
    } catch (\Throwable $e) {
      self::log('ERROR', 'Pull failed: '.$e->getMessage());

      return false;
    }
  }

  /**
   * Log message.
   */
  private static function log(string $level, string $message): void
  {
    $timestamp = date('Y-m-d H:i:s');
    $logLine = sprintf("[%s] [%s] %s\n", $timestamp, $level, $message);

    file_put_contents(self::LOG_FILE, $logLine, FILE_APPEND);

    // Also log to error_log if ERROR
    if ('ERROR' === $level) {
      error_log($message);
    }
  }
}
