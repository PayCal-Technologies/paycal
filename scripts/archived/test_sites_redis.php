

// Stub PayCal\Log if not present
namespace PayCal {
    if (!class_exists('PayCal\\Log')) {
        class Log {
            public static function debug($msg) { echo "[PayCal\\Log::debug] $msg\n"; }
            public static function info($msg) { echo "[PayCal\\Log::info] $msg\n"; }
            public static function error($msg) { echo "[PayCal\\Log::error] $msg\n"; }
        }
    }
}

namespace {
// Test script to directly invoke Sites class and check Redis key access

require_once __DIR__ . '/bootstrap/constants.php';
require_once __DIR__ . '/Classes/Database.php';
require_once __DIR__ . '/Classes/Redis.php';
require_once __DIR__ . '/Classes/Sites.php';

use PayCal\Sites;

// Set test UUID (replace with your actual test UUID if needed)
$userUUID = 'Ub9127d01';

// Enable verbose debugging (if not already enabled)
if (!function_exists('PC_log')) {
    function PC_log($msg) { echo "[PC_LOG] $msg\n"; }
}

// Facade to test getSites and log Redis connection info
function testSitesRedisAccess($userUUID) {
    echo "--- Redis Connection Info ---\n";
    $redis = PayCal\Database::getReadInstance();
    $client = $redis->client;
    echo "Host: 127.0.0.1\n";
    echo "Port: 6379\n";
    echo "DB: 0\n";
    echo "User: paycal\n";
    $info = $client->info();
    echo "Redis Server Info: ".json_encode($info['server'] ?? $info)."\n";

    // Step 1: Scan for site keys
    $pattern = 'site:' . $userUUID . ':*';
    echo "[DEBUG] Scanning for keys with pattern: $pattern\n";
    $siteKeys = $client->keys($pattern);
    echo "[DEBUG] Found keys: ".json_encode($siteKeys)."\n";
    if (empty($siteKeys)) {
        echo "[DEBUG] No site keys found for user $userUUID\n";
    } else {
        // Step 2: Fetch and print each hash
        foreach ($siteKeys as $key) {
            echo "[DEBUG] Fetching hash for key: $key\n";
            $hash = $client->hGetAll($key);
            echo "[DEBUG] Hash for $key: ".json_encode($hash)."\n";
        }
    }

    // Step 3: Call Sites::getSites and trace
    echo "--- Testing Sites::getSites ---\n";
    $sites = Sites::getInstance()->getSites($userUUID, 'all');
    $count = 0;
    foreach ($sites as $siteID => $siteData) {
        echo "[DEBUG] Sites::getSites yielded SiteID: $siteID\n";
        print_r($siteData);
        $count++;
    }
    if ($count === 0) {
        echo "[DEBUG] Sites::getSites yielded no sites for user $userUUID\n";
    } else {
        echo "[DEBUG] Sites::getSites yielded total: $count\n";
    }
}

testSitesRedisAccess($userUUID);
