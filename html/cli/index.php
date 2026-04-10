<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Constants\Keys;

require_once '../config.php';

/**
 * @return array{slug: string, label: string}
 */
function resolveNetblock(string $ip): array
{
  if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
    $octets = explode('.', $ip);
    $a = (int)($octets[0] ?? 0);
    $b = (int)($octets[1] ?? 0);
    $c = (int)($octets[2] ?? 0);
    $cidr = $a . '.' . $b . '.' . $c . '.0/24';

    $classification = 'Public IPv4';
    if (127 === $a) {
      $classification = 'Loopback';
    } elseif (10 === $a || (172 === $a && $b >= 16 && $b <= 31) || (192 === $a && 168 === $b)) {
      $classification = 'Private IPv4';
    } elseif (100 === $a && $b >= 64 && $b <= 127) {
      $classification = 'CGNAT';
    }

    return [
      'slug' => str_replace(['.', '/'], ['_', '-'], $cidr),
      'label' => $classification . ' ' . $cidr,
    ];
  }

  if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
    $packed = inet_pton($ip);
    if (false !== $packed) {
      $cidrPacked = substr($packed, 0, 8) . str_repeat("\0", 8);
      $cidr = inet_ntop($cidrPacked) . '/64';
      $firstByte = ord($packed[0]);

      $classification = 'Public IPv6';
      if ('::1/64' === $cidr) {
        $classification = 'Loopback';
      } elseif ($firstByte >= 0xfc && $firstByte <= 0xfd) {
        $classification = 'Unique Local IPv6';
      }

      return [
        'slug' => str_replace([':', '/'], ['_', '-'], $cidr),
        'label' => $classification . ' ' . $cidr,
      ];
    }
  }

  return [
    'slug' => 'unknown',
    'label' => 'Unknown Netblock',
  ];
}

$ttlSeconds = 3600;
$ip = Security::getClientIPAddress();
if ('' === $ip || 'unknown' === $ip) {
  $ip = '0.0.0.0';
}

$today = date('Y-m-d');
$week = date('o-\\WW');
$month = date('Y-m');
$year = date('Y');

$scraperPrefix = Keys::TELEMETRY . ':scraper';
$totalAttemptsKey = $scraperPrefix . ':attempts:total';
$dayAttemptsKey = $scraperPrefix . ':attempts:day:' . $today;
$weekAttemptsKey = $scraperPrefix . ':attempts:week:' . $week;
$monthAttemptsKey = $scraperPrefix . ':attempts:month:' . $month;
$yearAttemptsKey = $scraperPrefix . ':attempts:year:' . $year;

Database::incr($totalAttemptsKey);
Database::incr($dayAttemptsKey);
Database::expire($dayAttemptsKey, 400 * 24 * 3600);
Database::incr($weekAttemptsKey);
Database::expire($weekAttemptsKey, 800 * 24 * 3600);
Database::incr($monthAttemptsKey);
Database::expire($monthAttemptsKey, 2000 * 24 * 3600);
Database::incr($yearAttemptsKey);
Database::expire($yearAttemptsKey, 4000 * 24 * 3600);

$netblock = resolveNetblock($ip);
$netblockCountKey = $scraperPrefix . ':netblock:count:' . $netblock['slug'];
$netblockLabelsKey = $scraperPrefix . ':netblock:labels';
Database::incr($netblockCountKey);
Database::expire($netblockCountKey, 4000 * 24 * 3600);
Database::hset($netblockLabelsKey, [$netblock['slug'] => $netblock['label']]);
Database::expire($netblockLabelsKey, 4000 * 24 * 3600);

$scoreKey = 'sec:ip:' . $ip . ':score';
$reqKey = 'sec:ip:' . $ip . ':req';
$lastKey = 'sec:ip:' . $ip . ':last';

$userAgent = is_string($_SERVER['HTTP_USER_AGENT'] ?? null) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
$accept = is_string($_SERVER['HTTP_ACCEPT'] ?? null) ? (string) $_SERVER['HTTP_ACCEPT'] : '';
$now = time();

$requestCount = Database::incr($reqKey);
Database::expire($reqKey, 1);

$lastSeenRaw = Database::get($lastKey);
$lastSeen = ctype_digit($lastSeenRaw) ? (int) $lastSeenRaw : 0;
Database::set($lastKey, (string) $now, $ttlSeconds);

$increment = 0;
if (1 === preg_match('/\b(curl|wget)\b/i', $userAgent)) {
  $increment += 2;
}
if ('' === trim($accept)) {
  $increment += 1;
}
if ($requestCount > 10) {
  $increment += 2;
}
if ($lastSeen > 0 && ($now - $lastSeen) <= 1) {
  $increment += 1;
}

if ($increment > 0) {
  Database::getWriteInstance()->client->incrBy($scoreKey, $increment);
}
Database::expire($scoreKey, $ttlSeconds);

$scoreRaw = Database::get($scoreKey);
$score = ctype_digit($scoreRaw) ? (int) $scoreRaw : 0;

if ($score >= 11) {
  // Tarpit: slow drip the fixed message so hostile clients burn wall-clock time.
  usleep(random_int(3_000_000, 10_000_000));
} elseif ($score >= 8) {
  usleep(random_int(1_500_000, 3_000_000));
} elseif ($score >= 5) {
  usleep(random_int(500_000, 1_500_000));
}

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$oscWhiteOnRed = "\033]10;#ffffff\007\033]11;#ff0000\007";
$message = 'Thank you for attempting to scrape our website.';

echo $oscWhiteOnRed;

if ($score >= 11) {
  foreach (str_split($message . "\n") as $character) {
    echo $character;
    if (function_exists('flush')) {
      flush();
    }
    usleep(250_000);
  }
  exit;
}

echo $message . "\n";
