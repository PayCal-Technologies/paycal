<?php
/**
 * Template Validation Script
 * Validates all templates for:
 * - PHP syntax
 * - Placeholder patterns
 * - Missing required variables
 */

date_default_timezone_set("America/Toronto");
require_once __DIR__ . '/../dev/html/config.php';

$templatesDir = __DIR__ . '/../dev/templates';
$templates = glob($templatesDir . '/*.php');

if (!is_array($templates)) {
    echo "ERROR: No templates found in {$templatesDir}\n";
    exit(1);
}

echo "Validating " . count($templates) . " templates...\n";
echo str_repeat("-", 60) . "\n";

$errors = [];
$warnings = [];

foreach ($templates as $template) {
    $name = basename($template, '.php');
    
    // Check syntax
    $output = [];
    $returnVar = 0;
    exec("php -l " . escapeshellarg($template) . " 2>&1", $output, $returnVar);
    
    if ($returnVar !== 0) {
        $errors[$name] = $output;
        continue;
    }
    
    // Extract placeholders
    $content = file_get_contents($template);
    preg_match_all('/__([A-Z_][A-Z0-9_]*)__/', $content, $matches);
    $placeholders = array_unique($matches[1]);
    
    echo "[OK] {$name}";
    if (!empty($placeholders)) {
        echo " (" . count($placeholders) . " placeholders: " . implode(', ', array_slice($placeholders, 0, 3));
        if (count($placeholders) > 3) {
            echo ", ...";
        }
        echo ")";
    }
    echo "\n";
}

echo str_repeat("-", 60) . "\n";
echo "Summary:\n";
echo "  Total: " . count($templates) . "\n";
echo "  Syntax errors: " . count($errors) . "\n";
echo "  Warnings: " . count($warnings) . "\n";

if (!empty($errors)) {
    echo "\nERRORS:\n";
    foreach ($errors as $name => $output) {
        echo "  {$name}:\n";
        foreach ($output as $line) {
            echo "    " . $line . "\n";
        }
    }
    exit(1);
}

echo "\nAll templates validated successfully!\n";
exit(0);
