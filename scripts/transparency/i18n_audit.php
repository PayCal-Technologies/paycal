<?php
declare(strict_types=1);

/**
 * PURPOSE:
 *   Audit transparency pages for i18n key coverage across supported locales.
 *
 * USAGE:
 *   php scripts/transparency/i18n_audit.php
 *
 * OUTPUT:
 *   Writes a TSV report to tmp/transparency_i18n_audit.tsv.
 *
 * WHY THIS LIVES HERE:
 *   This is a reusable maintenance report for transparency localization and
 *   should be versioned under scripts instead of tmp.
 */

$root = dirname(__DIR__, 2);
$transparencyDir = $root . '/html/transparency';
$langs = ['de', 'en', 'es', 'fr', 'hi', 'it', 'nl', 'pt', 'tl', 'tr'];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($transparencyDir));
$keySet = [];

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $path = $file->getPathname();
    if (substr($path, -4) !== '.php') {
        continue;
    }

    $source = file_get_contents($path);
    if ($source === false) {
        continue;
    }

    if (preg_match_all("/Strings::i18n\(\s*'([^']+)'\s*\)/", $source, $m1)) {
        foreach ($m1[1] as $key) {
            $keySet[$key] = true;
        }
    }

    if (preg_match_all('/\$i18nKeys\s*=\s*\[(.*?)\];/s', $source, $m2)) {
        foreach ($m2[1] as $listBody) {
            if (preg_match_all("/'([^']+)'/", $listBody, $m3)) {
                foreach ($m3[1] as $key) {
                    $keySet[$key] = true;
                }
            }
        }
    }
}

$allKeys = array_keys($keySet);
sort($allKeys);

$rows = ["lang\ttotal_keys\tmissing_count\tmissing_keys"];

foreach ($langs as $lang) {
    $catalog = [];
    $catalogPath = $root . '/strings/' . $lang . '.txt';

    if (is_file($catalogPath)) {
        $lines = file($catalogPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines)) {
            foreach ($lines as $line) {
                if ($line === '' || $line[0] === '#') {
                    continue;
                }

                $parts = preg_split('/\s+/', $line, 2);
                if (is_array($parts) && count($parts) === 2) {
                    $catalog[$parts[0]] = true;
                }
            }
        }
    }

    $missing = [];
    foreach ($allKeys as $key) {
        if (!array_key_exists($key, $catalog)) {
            $missing[] = $key;
        }
    }

    $rows[] = $lang . "\t" . count($allKeys) . "\t" . count($missing) . "\t" . implode(',', $missing);
}

$reportPath = $root . '/tmp/transparency_i18n_audit.tsv';
file_put_contents($reportPath, implode(PHP_EOL, $rows) . PHP_EOL);

echo 'keys=' . count($allKeys) . PHP_EOL;
echo 'report=' . $reportPath . PHP_EOL;
