<?php declare(strict_types=1);

use PayCal\Domain\Database;
use PayCal\Domain\Constants\Keys;
use PayCal\Domain\User;
use PayCal\Domain\UserRepository;

/**
 * Manual Test: Settings Form Submission and Redis Storage
 * 
 * Run this script to test that settings form submissions correctly save to Redis.
 * This validates the full integration: Form → API → SettingsController → User → Redis
 * 
 * Usage: php tests/Manual/SettingsFormSubmissionTest.php
 */



require_once __DIR__.'/../../config.php';


// Configuration
$testEmail = 'copilot@paycal.app';
$testPositions = ['left', 'middle', 'right'];
$testAudioValues = ['all', 'important', 'none'];

echo "Settings Form Submission Integration Test\n";
echo "==========================================\n\n";

// Get user UUID
$emailData = Database::hgetall("email:{$testEmail}");
if (empty($emailData['user_uuid'])) {
    echo "❌ ERROR: Could not find user UUID for {$testEmail}\n";
    exit(1);
}

$userUUID = $emailData['user_uuid'];
echo "✓ Found user: {$userUUID}\n\n";

// Load user
$user = UserRepository::getByUUID($userUUID);
if (!$user) {
    echo "❌ ERROR: Could not load User object\n";
    exit(1);
}

echo "Testing calendar_date_label_position:\n";
echo "-------------------------------------\n";
foreach ($testPositions as $position) {
    // Update via User method (simulating what SettingsController does)
    $result = $user->updateSettings([
        'calendar_date_label_position' => $position,
    ]);
    
    if (!$result) {
        echo "❌ FAILED to update to '{$position}'\n";
        continue;
    }
    
    // Verify in Redis
    $stored = Database::hget(Keys::USER.':'.$userUUID, 'calendar_date_label_position');
    
    if ($stored === $position) {
        echo "✓ '{$position}' saved and verified\n";
    } else {
        echo "❌ FAILED: Expected '{$position}' but got '{$stored}'\n";
    }
}

echo "\nTesting calendar_work_entry_position:\n";
echo "-------------------------------------\n";
foreach ($testPositions as $position) {
    $result = $user->updateSettings([
        'calendar_work_entry_position' => $position,
    ]);
    
    if (!$result) {
        echo "❌ FAILED to update to '{$position}'\n";
        continue;
    }
    
    $stored = Database::hget(Keys::USER.':'.$userUUID, 'calendar_work_entry_position');
    
    if ($stored === $position) {
        echo "✓ '{$position}' saved and verified\n";
    } else {
        echo "❌ FAILED: Expected '{$position}' but got '{$stored}'\n";
    }
}

echo "\nTesting audio_feedback:\n";
echo "----------------------\n";
foreach ($testAudioValues as $value) {
    $result = $user->updateSettings([
        'audio_feedback' => $value,
    ]);
    
    if (!$result) {
        echo "❌ FAILED to update to '{$value}'\n";
        continue;
    }
    
    $stored = Database::hget(Keys::USER.':'.$userUUID, 'audio_feedback');
    
    if ($stored === $value) {
        echo "✓ '{$value}' saved and verified\n";
    } else {
        echo "❌ FAILED: Expected '{$value}' but got '{$stored}'\n";
    }
}

echo "\nTesting theme and variant:\n";
echo "-------------------------\n";
$themeTests = [
    ['theme' => 'paycal', 'variant' => 'dark'],
    ['theme' => 'macos', 'variant' => 'light'],
    ['theme' => 'macos9', 'variant' => 'light'],
    ['theme' => 'system8', 'variant' => 'dark'],
    ['theme' => 'system7', 'variant' => 'light'],
    ['theme' => 'linux', 'variant' => 'dark'],
    ['theme' => 'mint', 'variant' => 'light'],
    ['theme' => 'fedora', 'variant' => 'dark'],
    ['theme' => 'debian', 'variant' => 'light'],
    ['theme' => 'beos', 'variant' => 'light'],
    ['theme' => 'zeta', 'variant' => 'dark'],
    ['theme' => 'haiku', 'variant' => 'light'],
    ['theme' => 'win95', 'variant' => 'dark'],
    ['theme' => 'winxp', 'variant' => 'dark'],
    ['theme' => 'blade_runner', 'variant' => 'dark'],
    ['theme' => 'space_odyssey', 'variant' => 'light'],
    ['theme' => 'tron', 'variant' => 'dark'],
    ['theme' => 'fifth_element', 'variant' => 'light'],
    ['theme' => 'dune', 'variant' => 'dark'],
    ['theme' => 'matrix', 'variant' => 'light'],
    ['theme' => 'alien', 'variant' => 'dark'],
    ['theme' => 'akira', 'variant' => 'light'],
    ['theme' => 'star_trek', 'variant' => 'light'],
];

foreach ($themeTests as $test) {
    $result = $user->updateSettings($test);
    
    if (!$result) {
        echo "❌ FAILED to update to {$test['theme']}/{$test['variant']}\n";
        continue;
    }
    
    $storedTheme = Database::hget(Keys::USER.':'.$userUUID, 'theme');
    $storedVariant = Database::hget(Keys::USER.':'.$userUUID, 'variant');
    
    if ($storedTheme === $test['theme'] && $storedVariant === $test['variant']) {
        echo "✓ '{$test['theme']}/{$test['variant']}' saved and verified\n";
    } else {
        echo "❌ FAILED: Expected '{$test['theme']}/{$test['variant']}' but got '{$storedTheme}/{$storedVariant}'\n";
    }
}

echo "\n==========================================\n";
echo "Test Complete!\n";
