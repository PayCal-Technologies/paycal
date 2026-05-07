#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Source Code Dump Generator
 *
 * Generates a single file containing all project source code
 * with file separators for AI analysis and audit discussions.
 */

$projectRoot = dirname(__DIR__);
$outputDate = date('Y-m-d');
$outputFile = '/var/www/paycal/paycal-source-dump-as-at-' . $outputDate . '.txt';

// File extensions to include in the analysis dump.
$includeExtensions = ['php', 'js', 'css'];

// Directories to exclude
$excludeDirs = [
    'vendor',
    'node_modules',
    '.git',
    'coverage',
    'logs',
    'keys',
    'data',
    '.vscode',
    '.idea',
    'tmp',
    'temp',
    'cache'
];

// Files to exclude
$excludeFiles = [
    '.env',
    '.env.local',
    '.env.production',
    'cookies.txt',
    'composer.lock',
    'package-lock.json',
    '.DS_Store',
    'phpunit',
    'junit.xml'
];

// Specific paths to exclude
$excludePaths = [
    'vendor/',
    'html/data/',
    'html/logs/',
    'logs/',
];

/**
 * @return array{stdout: string, stderr: string, exitCode: int, durationSeconds: float}
 */
function runCommand(string $command, string $cwd): array
{
    $descriptorSpec = [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $start = microtime(true);
    $process = proc_open($command, $descriptorSpec, $pipes, $cwd);

    if (!is_resource($process)) {
        return [
            'stdout' => '',
            'stderr' => 'Failed to start process',
            'exitCode' => 1,
            'durationSeconds' => 0.0,
        ];
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    $duration = microtime(true) - $start;

    return [
        'stdout' => $stdout === false ? '' : $stdout,
        'stderr' => $stderr === false ? '' : $stderr,
        'exitCode' => $exitCode,
        'durationSeconds' => round($duration, 2),
    ];
}

function normalizeWhitespace(string $text): string
{
    return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
}

function summarizeGateOutput(string $tool, string $combinedOutput): string
{
    if ($tool === 'phpstan') {
        if (preg_match('/\[OK\]\s+No errors/i', $combinedOutput) === 1) {
            return 'No errors reported.';
        }

        if (preg_match('/\[ERROR\]\s+Found\s+([0-9,]+)\s+errors?/i', $combinedOutput, $matches) === 1) {
            return 'Found ' . $matches[1] . ' errors.';
        }
    }

    if ($tool === 'phpunit') {
        if (preg_match('/OK\s*\(([^\)]+)\)/i', $combinedOutput, $matches) === 1) {
            return 'OK (' . $matches[1] . ').';
        }

        if (preg_match('/Tests:\s*([0-9,]+),\s*Assertions:\s*([0-9,]+)/i', $combinedOutput, $matches) === 1) {
            return 'Tests: ' . $matches[1] . ', Assertions: ' . $matches[2] . '.';
        }
    }

    $lines = preg_split('/\R/', trim($combinedOutput)) ?: [];
    foreach ($lines as $line) {
        $clean = normalizeWhitespace($line);
        if ($clean !== '') {
            return $clean;
        }
    }

    return 'No summary available.';
}

/**
 * @return array{name: string, command: string, status: string, exitCode: int, durationSeconds: float, summary: string, excerpt: string}
 */
function runGateCheck(string $name, string $command, string $cwd): array
{
    $result = runCommand($command, $cwd);
    $combined = trim($result['stdout'] . "\n" . $result['stderr']);
    $status = $result['exitCode'] === 0 ? 'PASS' : 'FAIL';

    $lines = preg_split('/\R/', $combined) ?: [];
    $excerptLines = array_slice(array_values(array_filter(array_map('trim', $lines), static fn(string $line): bool => $line !== '')), 0, 12);
    $excerpt = $excerptLines === [] ? 'No output.' : implode("\n", $excerptLines);

    return [
        'name' => $name,
        'command' => $command,
        'status' => $status,
        'exitCode' => $result['exitCode'],
        'durationSeconds' => $result['durationSeconds'],
        'summary' => summarizeGateOutput($name, $combined),
        'excerpt' => $excerpt,
    ];
}

/**
 * @return list<array{name: string, command: string, status: string, exitCode: int, durationSeconds: float, summary: string, excerpt: string}>
 */
function runSecurityGates(string $projectRoot): array
{
    $gates = [];

    if (is_executable($projectRoot . '/vendor/bin/phpstan') && is_file($projectRoot . '/phpstan.neon')) {
        $gates[] = runGateCheck(
            'phpstan',
            './vendor/bin/phpstan analyse --configuration=phpstan.neon --level=9 --memory-limit=1G --no-progress',
            $projectRoot
        );
    } else {
        $gates[] = [
            'name' => 'phpstan',
            'command' => './vendor/bin/phpstan analyse --configuration=phpstan.neon --level=9 --memory-limit=1G --no-progress',
            'status' => 'SKIP',
            'exitCode' => 0,
            'durationSeconds' => 0.0,
            'summary' => 'Skipped: vendor/bin/phpstan or phpstan.neon not found.',
            'excerpt' => 'No output.',
        ];
    }

    if (is_executable($projectRoot . '/vendor/bin/phpunit')) {
        $phpunitConfig = is_file($projectRoot . '/phpunit.xml') ? ' --configuration phpunit.xml' : '';
        $gates[] = runGateCheck(
            'phpunit',
            './vendor/bin/phpunit' . $phpunitConfig . ' --colors=never',
            $projectRoot
        );
    } else {
        $gates[] = [
            'name' => 'phpunit',
            'command' => './vendor/bin/phpunit --configuration phpunit.xml --colors=never',
            'status' => 'SKIP',
            'exitCode' => 0,
            'durationSeconds' => 0.0,
            'summary' => 'Skipped: vendor/bin/phpunit not found.',
            'excerpt' => 'No output.',
        ];
    }

    return $gates;
}

function readCurrentVersion(string $projectRoot): string
{
    $versionJsonPath = $projectRoot . '/version.json';
    if (is_file($versionJsonPath)) {
        $raw = file_get_contents($versionJsonPath);
        if ($raw !== false) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && isset($decoded['version']) && is_string($decoded['version']) && $decoded['version'] !== '') {
                return $decoded['version'];
            }
        }
    }

    $versionFilePath = $projectRoot . '/VERSION';
    if (is_file($versionFilePath)) {
        $rawVersion = file_get_contents($versionFilePath);
        if ($rawVersion !== false) {
            return trim($rawVersion);
        }
    }

    return 'unknown';
}

/**
 * @return list<string>
 */
function readRecentCommits(string $projectRoot, int $count = 5): array
{
    $result = runCommand('git --no-pager log -n ' . $count . ' --pretty=format:%h\ %s', $projectRoot);
    if ($result['exitCode'] !== 0) {
        return ['Unable to read git history in this environment.'];
    }

    $lines = preg_split('/\R/', trim($result['stdout'])) ?: [];
    $commits = [];
    foreach ($lines as $line) {
        $clean = trim($line);
        if ($clean !== '') {
            $commits[] = $clean;
        }
    }

    return $commits === [] ? ['No commits found.'] : $commits;
}

function isTestFile(string $relativePath): bool
{
    $filename = basename($relativePath);
    $normalizedPath = str_replace('\\', '/', $relativePath);

    if (str_contains($normalizedPath, '/tests/')) {
        return true;
    }

    return preg_match('/(Test\.php|\.test\.php|\.spec\.php|\.test\.js|\.spec\.js)$/i', $filename) === 1;
}

function normalizeStem(string $relativePath): string
{
    $filename = basename($relativePath);
    $stem = pathinfo($filename, PATHINFO_FILENAME);
    $stem = preg_replace('/(Test|Tests|\.test|\.spec)$/i', '', $stem) ?? $stem;

    return strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $stem) ?? $stem);
}

/**
 * @return array<string, string>
 */
function readCanonicalWorkstreamDefinitions(string $projectRoot): array
{
    $sourcePath = $projectRoot . '/html/transparency/security-audit/index.php';
    if (!is_file($sourcePath)) {
        return [];
    }

    $raw = file_get_contents($sourcePath);
    if ($raw === false) {
        return [];
    }

    $definitions = [];
    if (preg_match_all('/Workstream\s+([A-I]):<\/strong>\s*(.*?)<\/li>/i', $raw, $matches, PREG_SET_ORDER) !== false) {
        foreach ($matches as $match) {
            $letter = strtoupper(trim($match[1]));
            $desc = trim(strip_tags(html_entity_decode($match[2], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            if ($letter !== '' && $desc !== '') {
                $definitions[$letter] = $desc;
            }
        }
    }

    return $definitions;
}

/**
 * @return array<string, string>
 */
function defaultWorkstreamDefinitions(): array
{
    return [
        'A' => 'Correlation governance centralized through broker-evaluated, deny-safe envelopes.',
        'B' => 'Telemetry query governance enforced with stream-scoped tokens and cross-stream join denial.',
        'C' => 'Privileged-role controls hardened with singleton superadmin and mutation guard enforcement.',
        'D' => 'Runtime decrypted-data lifecycle controls completed, including lifecycle zeroization and DOM sensitivity scrub behavior.',
        'E' => 'CSP nonce + strict-dynamic policy and violation ingestion enforcement in production routes.',
        'F' => 'One-shot capability token gates for high-risk admin mutations with denial regression coverage.',
        'G' => 'Browser credential-bridge removal for passkey flows and deterministic credential-derived KEK governance.',
        'H' => 'Runtime integrity monitor state machine (SAFE/DEGRADED/LOCKED/TERMINATED) with telemetry hooks.',
        'I' => 'Guardian sanitizer selector/style hardening with updated coverage anchors.',
    ];
}

/**
 * @return array<string, array{description: string, pathContains: list<string>, contentRegex: list<string>}>
 */
function getWorkstreamRules(array $canonicalDefinitions): array
{
    $defs = $canonicalDefinitions;
    foreach (defaultWorkstreamDefinitions() as $letter => $fallbackDescription) {
        if (!isset($defs[$letter]) || $defs[$letter] === '') {
            $defs[$letter] = $fallbackDescription;
        }
    }

    return [
        'A' => [
            'description' => $defs['A'],
            'pathContains' => [
                'html/src/domain/security/correlation',
                'html/tests/unit/correlation',
            ],
            'contentRegex' => [
                '/\bCorrelationBroker\b/',
                '/\bMetadataCorrelationPolicy\b/',
                '/deny-safe|defaultDecision\s*:\s*[\'\"]deny[\'\"]|canCorrelateMetadata/i',
            ],
        ],
        'B' => [
            'description' => $defs['B'],
            'pathContains' => [
                'html/src/domain/telemetry',
                'html/tests/contract/telemetrypolicy',
            ],
            'contentRegex' => [
                '/stream[-_ ]scoped|stream[_ ]token|security_stream|product_stream/i',
                '/cross-stream|join\s+denial|authorize\w*stream/i',
                '/\bTelemetryRepository\b|\bTelemetryPolicy\b/',
            ],
        ],
        'C' => [
            'description' => $defs['C'],
            'pathContains' => [
                'html/src/controllers/admincontroller.php',
                'html/src/domain/userrepository.php',
                'html/tests/integration/admincontrollerintegrationtest.php',
            ],
            'contentRegex' => [
                '/\bSUPERADMIN\b/',
                '/demoteOtherSuperAdmins|only superadmin may modify privileged auth levels/i',
                '/privileged\s*role|mutation guard/i',
            ],
        ],
        'D' => [
            'description' => $defs['D'],
            'pathContains' => [
                'html/js/calendar',
                'html/tests/playwright/smoke-ui',
            ],
            'contentRegex' => [
                '/zeroization|zeroize|clearSensitive|clear.*crypto|dom sensitivity scrub/i',
                '/bindCryptoLifecycleZeroization|lifecycle/i',
                '/decrypted-data|decryptedEntries|unlock(ed)?-state/i',
            ],
        ],
        'E' => [
            'description' => $defs['E'],
            'pathContains' => [
                'html/header.php',
                'html/src/domain/layout.php',
            ],
            'contentRegex' => [
                '/strict-dynamic|csp nonce|content-security-policy/i',
                '/csp[-_ ]violation|violation ingestion/i',
                '/nonce=|script-src/i',
            ],
        ],
        'F' => [
            'description' => $defs['F'],
            'pathContains' => [
                'html/src/domain/capabilitytokenservice.php',
                'html/tests/unit/capabilitytokenservice',
            ],
            'contentRegex' => [
                '/one-shot capability token|capability token/i',
                '/issue.*capability|consume.*capability/i',
                '/high-risk admin mutation|denial regression/i',
            ],
        ],
        'G' => [
            'description' => $defs['G'],
            'pathContains' => [
                'html/src/controllers/encryptioncontroller.php',
                'html/js/calendar/crypto-worker.js',
            ],
            'contentRegex' => [
                '/passkey|webauthn|credential[-_ ]bridge|credential_id/i',
                '/deterministic credential-derived kek|hkdf|kek/i',
                '/pbkdf2|dek/i',
            ],
        ],
        'H' => [
            'description' => $defs['H'],
            'pathContains' => [
                'html/js/runtime-integrity',
                'html/src/domain/shadowtalon.php',
            ],
            'contentRegex' => [
                '/runtime integrity monitor|shadowtalon/i',
                '/\bSAFE\b|\bDEGRADED\b|\bLOCKED\b|\bTERMINATED\b/',
                '/integrity state machine|telemetry hooks/i',
            ],
        ],
        'I' => [
            'description' => $defs['I'],
            'pathContains' => [
                'html/js/guardian.js',
                'html/tests/unit/guardiansanitizer',
                'docs/guardian_metadata_correlation_policy.md',
            ],
            'contentRegex' => [
                '/\bGuardian\b/',
                '/sanitizer|blocked selectors?|style hardening/i',
                '/setHTML|insertHTML|stripInlineStyle|nonce compliance/i',
            ],
        ],
    ];
}

/**
 * @param list<string> $testFiles
 * @return array<string, list<string>>
 */
function buildTestIndex(array $testFiles): array
{
    $index = [];
    foreach ($testFiles as $testFile) {
        $stem = normalizeStem($testFile);
        if ($stem === '') {
            continue;
        }

        if (!array_key_exists($stem, $index)) {
            $index[$stem] = [];
        }

        $index[$stem][] = $testFile;
    }

    foreach ($index as $stem => $paths) {
        sort($paths);
        $index[$stem] = array_values(array_unique($paths));
    }

    return $index;
}

/**
 * @param array<string, array{description: string, pathContains: list<string>, contentRegex: list<string>}> $rules
 * @return array{tags: list<string>, reasons: list<string>}
 */
function detectWorkstreams(string $relativePath, string $content, array $rules): array
{
    $tags = [];
    $reasons = [];
    $lowerPath = strtolower($relativePath);

    foreach ($rules as $letter => $rule) {
        $matched = false;
        $hitReasons = [];

        foreach ($rule['pathContains'] as $pathNeedle) {
            $needle = strtolower($pathNeedle);
            if ($needle !== '' && str_contains($lowerPath, $needle)) {
                $matched = true;
                $hitReasons[] = 'path:' . $pathNeedle;
                break;
            }
        }

        foreach ($rule['contentRegex'] as $regex) {
            if (preg_match($regex, $content) === 1) {
                $matched = true;
                $hitReasons[] = 'content:' . $regex;
                break;
            }
        }

        if ($matched) {
            $label = $letter . ' ' . $rule['description'];
            $tags[] = $label;
            $reasons[] = $letter . ' => ' . implode(' | ', array_values(array_unique($hitReasons)));
        }
    }

    if ($tags === []) {
        return [
            'tags' => ['UNMAPPED'],
            'reasons' => ['no rule matched'],
        ];
    }

    return [
        'tags' => array_values(array_unique($tags)),
        'reasons' => array_values(array_unique($reasons)),
    ];
}

/**
 * @param list<string> $files
 * @return array<string, list<string>>
 */
function buildSecurityInterestIndex(array $files, string $projectRoot): array
{
    $patterns = [
        'AES-256-GCM' => '/AES-256-GCM/i',
        'HKDF' => '/\bHKDF\b/i',
        'PBKDF2' => '/\bPBKDF2\b/i',
        'WebAuthn/Passkey' => '/WebAuthn|passkey/i',
        'Zeroization/Clear Memory' => '/zeroization|zeroize|clearSensitive|wipe|memory_clear/i',
        'Recovery Proof' => '/recovery\s+proof|proof\s+of\s+recovery/i',
    ];

    $index = [];
    foreach ($patterns as $label => $regex) {
        $index[$label] = [];
    }

    foreach ($files as $relativePath) {
        $fullPath = $projectRoot . '/' . $relativePath;
        $content = file_get_contents($fullPath);
        if ($content === false) {
            continue;
        }

        foreach ($patterns as $label => $regex) {
            if (preg_match($regex, $content) === 1) {
                $index[$label][] = $relativePath;
            }
        }
    }

    foreach ($index as $label => $paths) {
        sort($paths);
        $index[$label] = array_values(array_unique($paths));
    }

    return $index;
}

/**
 * @param list<string> $controllerFiles
 * @param list<string> $symbols
 * @return array<string, list<string>>
 */
function buildControllerReferenceMap(array $controllerFiles, string $projectRoot, array $symbols): array
{
    $map = [];
    foreach ($symbols as $symbol) {
        $map[$symbol] = [];
    }

    foreach ($controllerFiles as $relativePath) {
        $fullPath = $projectRoot . '/' . $relativePath;
        $content = file_get_contents($fullPath);
        if ($content === false) {
            continue;
        }

        foreach ($symbols as $symbol) {
            if (stripos($content, $symbol) !== false) {
                $map[$symbol][] = $relativePath;
            }
        }
    }

    foreach ($map as $symbol => $paths) {
        sort($paths);
        $map[$symbol] = array_values(array_unique($paths));
    }

    return $map;
}

echo "PayCal Source Code Dump Generator\n";
echo "==================================\n\n";
echo "Project Root: $projectRoot\n";
echo "Output File: $outputFile\n\n";

// Collect all files
$files = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($projectRoot, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $relativePath = str_replace($projectRoot . '/', '', $file->getPathname());
    
    // Check if file is in excluded directory
    $skip = false;
    foreach ($excludeDirs as $excludeDir) {
        if (str_starts_with($relativePath, $excludeDir . '/') || 
            str_contains($relativePath, '/' . $excludeDir . '/')) {
            $skip = true;
            break;
        }
    }
    
    if ($skip) {
        continue;
    }
    
    // Check if file is in excluded path
    foreach ($excludePaths as $excludePath) {
        if (str_starts_with($relativePath, $excludePath)) {
            $skip = true;
            break;
        }
    }
    
    if ($skip) {
        continue;
    }
    
    // Check if filename should be excluded
    $filename = basename($relativePath);
    if (in_array($filename, $excludeFiles, true)) {
        continue;
    }
    
    // Check file extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!in_array($ext, $includeExtensions, true)) {
        continue;
    }
    
    $files[] = $relativePath;
}

// Sort files for consistent output
sort($files);

echo "Found " . count($files) . " source files\n";
echo "Running security gates (PHPStan level 9 + PHPUnit)...\n";
$securityGates = runSecurityGates($projectRoot);
echo "Collecting metadata indexes...\n";

$currentVersion = readCurrentVersion($projectRoot);
$canonicalWorkstreamDefinitions = readCanonicalWorkstreamDefinitions($projectRoot);
$workstreamRules = getWorkstreamRules($canonicalWorkstreamDefinitions);
$recentCommits = readRecentCommits($projectRoot, 5);
$securityInterestIndex = buildSecurityInterestIndex($files, $projectRoot);
echo "Generating sectioned dump (CSS last)...\n\n";

/**
 * @param string $relativePath
 */
function isPhpBackedJavascriptFile(string $relativePath): bool
{
    return str_ends_with($relativePath, '.php') && str_starts_with($relativePath, 'html/js/');
}

/**
 * @return array{classes: list<string>, interfaces: list<string>, traits: list<string>, enums: list<string>}
 */
function extractPhpSymbols(string $content): array
{
    $symbols = [
        'classes' => [],
        'interfaces' => [],
        'traits' => [],
        'enums' => [],
    ];

    $tokens = token_get_all($content);
    $count = count($tokens);

    for ($i = 0; $i < $count; $i++) {
        $token = $tokens[$i];
        if (!is_array($token)) {
            continue;
        }

        $kind = $token[0];
        if (!in_array($kind, [T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM], true)) {
            continue;
        }

        // Skip anonymous classes.
        if ($kind === T_CLASS) {
            $prev = $i - 1;
            while ($prev >= 0) {
                $prevToken = $tokens[$prev];
                if (is_array($prevToken) && in_array($prevToken[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
                    $prev--;
                    continue;
                }

                if (is_array($prevToken) && $prevToken[0] === T_NEW) {
                    continue 2;
                }
                break;
            }
        }

        $name = null;
        for ($j = $i + 1; $j < $count; $j++) {
            $next = $tokens[$j];
            if (is_array($next) && $next[0] === T_STRING) {
                $name = $next[1];
                break;
            }

            if (is_array($next) && in_array($next[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_FINAL, T_ABSTRACT, T_READONLY], true)) {
                continue;
            }

            break;
        }

        if ($name === null) {
            continue;
        }

        if ($kind === T_CLASS) {
            $symbols['classes'][] = $name;
        } elseif ($kind === T_INTERFACE) {
            $symbols['interfaces'][] = $name;
        } elseif ($kind === T_TRAIT) {
            $symbols['traits'][] = $name;
        } else {
            $symbols['enums'][] = $name;
        }
    }

    foreach ($symbols as $key => $values) {
        $symbols[$key] = array_values(array_unique($values));
    }

    return $symbols;
}

/**
 * @param array<string, list<string>> $testIndex
 * @param array<string, bool> $emittedTests
 */
function appendFilesSectionWithMetadata(
    string $title,
    array $paths,
    string $projectRoot,
    string &$output,
    int &$processedCount,
    array $testIndex,
    array &$emittedTests,
    array $workstreamRules
): void {
    $output .= "================================================================================\n";
    $output .= "SECTION: $title\n";
    $output .= "Files: " . count($paths) . "\n";
    $output .= "================================================================================\n\n";

    foreach ($paths as $relativePath) {
        $fullPath = $projectRoot . '/' . $relativePath;
        $content = file_get_contents($fullPath);
        if ($content === false) {
            echo "Warning: Could not read $relativePath\n";
            continue;
        }

        $workstreamMatch = detectWorkstreams($relativePath, $content, $workstreamRules);

        $output .= str_repeat('=', 80) . "\n";
        $output .= "FILE: $relativePath\n";
        $output .= 'WORKSTREAMS: ' . implode(' | ', $workstreamMatch['tags']) . "\n";
        $output .= 'WORKSTREAM_MATCH_REASONS: ' . implode(' || ', $workstreamMatch['reasons']) . "\n";

        if (str_ends_with($relativePath, '.php')) {
            $symbols = extractPhpSymbols($content);
            $symbolParts = [];
            if ($symbols['classes'] !== []) {
                $symbolParts[] = 'classes=' . implode(',', $symbols['classes']);
            }
            if ($symbols['interfaces'] !== []) {
                $symbolParts[] = 'interfaces=' . implode(',', $symbols['interfaces']);
            }
            if ($symbols['traits'] !== []) {
                $symbolParts[] = 'traits=' . implode(',', $symbols['traits']);
            }
            if ($symbols['enums'] !== []) {
                $symbolParts[] = 'enums=' . implode(',', $symbols['enums']);
            }

            $output .= 'SYMBOLS: ' . ($symbolParts === [] ? 'none' : implode(' | ', $symbolParts)) . "\n";
        }

        $output .= str_repeat('=', 80) . "\n";
        $output .= $content;

        if (!str_ends_with($content, "\n")) {
            $output .= "\n";
        }

        $output .= "\n";

        $stem = normalizeStem($relativePath);
        $relatedTests = $stem !== '' && array_key_exists($stem, $testIndex) ? $testIndex[$stem] : [];
        foreach ($relatedTests as $testPath) {
            if (($emittedTests[$testPath] ?? false) === true) {
                continue;
            }

            $testContent = file_get_contents($projectRoot . '/' . $testPath);
            if ($testContent === false) {
                continue;
            }

            $output .= str_repeat('-', 80) . "\n";
            $output .= "RELATED TEST: $testPath\n";
            $output .= str_repeat('-', 80) . "\n";
            $output .= $testContent;
            if (!str_ends_with($testContent, "\n")) {
                $output .= "\n";
            }
            $output .= "\n";

            $emittedTests[$testPath] = true;
        }

        $output .= "\n";
        $processedCount++;

        if ($processedCount % 50 === 0) {
            echo "Processed $processedCount files...\n";
        }
    }
}

/**
 * @param list<string> $paths
 * @param array<string, bool> $emittedTests
 */
function appendRemainingTestsSection(string $title, array $paths, string $projectRoot, string &$output, array $emittedTests): void
{
    $remaining = [];
    foreach ($paths as $path) {
        if (($emittedTests[$path] ?? false) !== true) {
            $remaining[] = $path;
        }
    }

    sort($remaining);

    $output .= "================================================================================\n";
    $output .= "SECTION: $title\n";
    $output .= "Files: " . count($remaining) . "\n";
    $output .= "================================================================================\n\n";

    foreach ($remaining as $relativePath) {
        $content = file_get_contents($projectRoot . '/' . $relativePath);
        if ($content === false) {
            continue;
        }

        $output .= str_repeat('=', 80) . "\n";
        $output .= "FILE: $relativePath\n";
        $output .= str_repeat('=', 80) . "\n";
        $output .= $content;
        if (!str_ends_with($content, "\n")) {
            $output .= "\n";
        }
        $output .= "\n\n";
    }
}

$phpBackedJsFiles = [];
$phpFiles = [];
$jsFiles = [];
$cssFiles = [];
$testFiles = [];
$controllerFiles = [];

foreach ($files as $path) {
    if (str_starts_with($path, 'html/src/Controllers/') && str_ends_with($path, '.php')) {
        $controllerFiles[] = $path;
    }

    if (isTestFile($path)) {
        $testFiles[] = $path;
        continue;
    }

    if (str_ends_with($path, '.css')) {
        $cssFiles[] = $path;
        continue;
    }

    if (str_ends_with($path, '.js')) {
        $jsFiles[] = $path;
        continue;
    }

    if (isPhpBackedJavascriptFile($path)) {
        $phpBackedJsFiles[] = $path;
        continue;
    }

    if (str_ends_with($path, '.php')) {
        $phpFiles[] = $path;
    }
}

$testIndex = buildTestIndex($testFiles);
$controllerReferenceMap = buildControllerReferenceMap(
    $controllerFiles,
    $projectRoot,
    ['CorrelationBroker', 'Guardian']
);

// Start output buffer
$output = '';

// Add header
$output .= "================================================================================\n";
$output .= "PAYCAL SOURCE CODE DUMP\n";
$output .= "================================================================================\n";
$output .= "Generated: " . date('Y-m-d H:i:s T') . "\n";
$output .= "Version: " . $currentVersion . "\n";
$output .= "Total Files: " . count($files) . "\n";
$output .= "- PHP Files: " . count($phpFiles) . "\n";
$output .= "- PHP-backed JavaScript Files: " . count($phpBackedJsFiles) . "\n";
$output .= "- JavaScript Files: " . count($jsFiles) . "\n";
$output .= "- CSS Files (last section): " . count($cssFiles) . "\n";
$output .= "- Test Files (linked inline or residual section): " . count($testFiles) . "\n";
$output .= "Purpose: Structured audit artifact for architecture and security analysis\n";
$output .= "Workstream Mapping: ACTIVE (A-I)\n";
$output .= "Workstream Mapping Source: html/transparency/security-audit/index.php\n";
$output .= "================================================================================\n\n";

$output .= "================================================================================\n";
$output .= "WORKSTREAM DEFINITIONS (CANONICAL A-I)\n";
$output .= "================================================================================\n\n";
foreach ($workstreamRules as $letter => $rule) {
    $output .= $letter . ': ' . $rule['description'] . "\n";
}
$output .= "\n";

$output .= "================================================================================\n";
$output .= "STATE OF PROJECT (AUTOMATED GATES)\n";
$output .= "================================================================================\n\n";
foreach ($securityGates as $gate) {
    $output .= strtoupper($gate['name']) . ': ' . $gate['status'] . "\n";
    $output .= 'Command: ' . $gate['command'] . "\n";
    $output .= 'Exit Code: ' . $gate['exitCode'] . "\n";
    $output .= 'Duration (s): ' . $gate['durationSeconds'] . "\n";
    $output .= 'Summary: ' . $gate['summary'] . "\n";
    $output .= 'Output Excerpt: ' . $gate['excerpt'] . "\n\n";
}

$output .= "================================================================================\n";
$output .= "DELTA CONTEXT\n";
$output .= "================================================================================\n\n";
$output .= "Recent commits (last 5):\n";
foreach ($recentCommits as $commitLine) {
    $output .= '- ' . $commitLine . "\n";
}
$output .= "\n";

$output .= "================================================================================\n";
$output .= "SECURITY INTEREST INDEX\n";
$output .= "================================================================================\n\n";
foreach ($securityInterestIndex as $label => $paths) {
    $output .= $label . ': ' . count($paths) . " file(s)\n";
    foreach ($paths as $path) {
        $output .= '- ' . $path . "\n";
    }
    $output .= "\n";
}

$output .= "================================================================================\n";
$output .= "CONTROLLER REFERENCE TREE\n";
$output .= "================================================================================\n\n";
foreach ($controllerReferenceMap as $symbol => $paths) {
    $output .= $symbol . ': ' . count($paths) . " controller reference(s)\n";
    foreach ($paths as $path) {
        $output .= '- ' . $path . "\n";
    }
    $output .= "\n";
}

// Add project notes
$output .= "================================================================================\n";
$output .= "PROJECT NOTES\n";
$output .= "================================================================================\n\n";

$output .= "PROJECT: PayCal\n";
$output .= "DESCRIPTION: Accessibility-first payroll platform for transparent earnings,\n";
$output .= "             predictable tax calculations, and secure data handling.\n\n";

$output .= "ARCHITECTURE:\n";
$output .= "- Backend: PHP 8.5+ with Redis for caching/sessions\n";
$output .= "- Frontend: Vanilla JavaScript (ES6+), no frameworks\n";
$output .= "- Database: MySQL/MariaDB\n";
$output .= "- Security: Zero-knowledge encryption, CSRF protection, rate limiting\n";
$output .= "- Testing: PHPUnit with unit/integration/slow/stress test groups\n";
$output .= "- Static Analysis: PHPStan Level 9 target\n\n";

$output .= "KEY DIRECTORIES:\n";
$output .= "- html/src/Controllers/ - Page controllers and API endpoints\n";
$output .= "- html/src/Domain/ - Business logic and domain models\n";
$output .= "- html/src/Observability/ - Logging, metrics, debugging\n";
$output .= "- html/js/ - Client-side JavaScript modules\n";
$output .= "- html/css/ - Stylesheets\n";
$output .= "- html/tests/ - PHPUnit test suite\n";
$output .= "- scripts/ - Build and maintenance scripts\n";
$output .= "- docs/ - Project documentation and changelogs\n\n";

$output .= "CURRENT VERSION: " . $currentVersion . "\n\n";

$output .= "================================================================================\n\n\n";

// Process files in explicit order; CSS is always emitted last.
$processedCount = 0;
$emittedTests = [];
appendFilesSectionWithMetadata('PHP SOURCE', $phpFiles, $projectRoot, $output, $processedCount, $testIndex, $emittedTests, $workstreamRules);
appendFilesSectionWithMetadata('PHP-BACKED JAVASCRIPT SOURCE', $phpBackedJsFiles, $projectRoot, $output, $processedCount, $testIndex, $emittedTests, $workstreamRules);
appendFilesSectionWithMetadata('JAVASCRIPT SOURCE', $jsFiles, $projectRoot, $output, $processedCount, $testIndex, $emittedTests, $workstreamRules);
appendFilesSectionWithMetadata('CSS SOURCE (LAST)', $cssFiles, $projectRoot, $output, $processedCount, $testIndex, $emittedTests, $workstreamRules);
appendRemainingTestsSection('TEST FILES (UNLINKED)', $testFiles, $projectRoot, $output, $emittedTests);

echo "Processed $processedCount files total\n";
echo "Writing output file...\n";

// Write output
$result = file_put_contents($outputFile, $output);

if ($result === false) {
    echo "ERROR: Failed to write output file!\n";
    exit(1);
}

$sizeMB = round(strlen($output) / 1024 / 1024, 2);
echo "\nSUCCESS!\n";
echo "Output: $outputFile\n";
echo "Size: $sizeMB MB\n";
echo "Files: $processedCount\n";
echo "\nReady for analysis with Google Gemini.\n";

exec("open -R " . escapeshellarg($outputFile));
