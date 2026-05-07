<?php
require_once __DIR__ . '/../html/config.php';

use PayCal\Domain\Database;

echo "[*] Loading SVG strings from strings/en.txt\n";

$file = __DIR__ . '/../strings/en.txt';
if (!file_exists($file)) {
    echo "[ERROR] File not found: $file\n";
    exit(1);
}

$lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$data = [];

foreach ($lines as $line) {
    if ($line === "" || $line[0] === "#") continue;
    // Split on first whitespace
    $parts = preg_split('/\s+/', $line, 2);
    if (count($parts) === 2) {
        [$key, $value] = $parts;
        // Only load SVG entries
        if (strpos($key, '_SVG') !== false || strpos($key, '_HTML') !== false) {
            $data[$key] = $value;
        }
    }
}

echo "[✔] Parsed " . count($data) . " HTML/SVG entries\n";

// Load into Redis
$redisKey = "PAYCAL_SYSTEM:html:en";
echo "[*] Storing in Redis key: $redisKey\n";

foreach ($data as $key => $value) {
    Database::hset($redisKey, [$key => $value]);
}

echo "[✔] Loaded all HTML/SVG strings into Redis\n";

// Verify TEAM_SVG
$teamSvg = Database::hget($redisKey, "TEAM_SVG");
echo "\n[Check] TEAM_SVG first 100 chars:\n";
echo substr($teamSvg, 0, 100) . "...\n";

if (strpos($teamSvg, '2.99-1.34') !== false) {
    echo "[WARNING] TEAM_SVG still has malformed spacing!\n";
} else if (strpos($teamSvg, '1.66 0 3 -1.34') !== false) {
    echo "[✔] TEAM_SVG has correct spacing!\n";
} else {
    echo "[?] TEAM_SVG format unclear\n";
}

?>
