<?php
require_once __DIR__ . '/dev/html/config.php';

use PayCal\Domain\Database;

// HTML strings
echo "[*] Clearing system:html namespace\n";
Database::unlink("system:html");

$htmlFile = __DIR__ . '/dev/strings/html.txt';
echo "[*] Loading from: $htmlFile\n";

if (file_exists($htmlFile)) {
    $lines = file($htmlFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "[*] Read " . count($lines) . " lines from html.txt\n";
    
    $htmlData = [];
    foreach ($lines as $line) {
        if ($line === "" || $line[0] === "#") continue;
        $parts = preg_split('/\s+/', $line, 2);
        if (count($parts) === 2) {
            [$key, $value] = $parts;
            // Strip h_ prefix if present
            if (stripos($key, 'h_') === 0) {
                $key = substr($key, 2);
            }
            $htmlData[$key] = $value;
        }
    }
    echo "[✔] Parsed " . count($htmlData) . " HTML entries\n";
    
    $langs = ['en'];
    foreach ($langs as $lang) {
      $hashKey = "PAYCAL_SYSTEM:html:$lang";
      echo "[*] Loading to Redis key: $hashKey\n";
      Database::hset($hashKey, $htmlData);
    }
    echo "[✔] Imported HTML strings\n";
    
} else {
    echo "[!] HTML strings file not found: $htmlFile\n";
}
