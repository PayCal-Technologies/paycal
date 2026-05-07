<?php
// Seed mock earnings data in Redis using test: namespace for 2023
// Usage: php seed_mock_earnings_test_namespace.php

require_once __DIR__ . '/../dev/html/tests/bootstrap.php';
require_once __DIR__ . '/../dev/html/Classes/Database.php';
require_once __DIR__ . '/../dev/html/Classes/WorkEntry.php';
require_once __DIR__ . '/../dev/html/Classes/Work.php';

putenv('REDIS_PREFIX=test:'); // Ensure test namespace

$userUUID = 'test-user-uuid-001';
$siteID = 'test-site-001';
$siteName = 'Test Site';

$dates = [
    '2023-01-01', '2023-01-02', '2023-01-03', '2023-07-01', '2023-07-02', '2023-12-31'
];

foreach ($dates as $date) {
    $workDetails = [
        'd' => $date,
        's' => $siteID,
        'siteName' => $siteName,
        'h' => 8.0, // hours
        'r' => 8.0, // regular hours
        'o' => 0.0, // overtime hours
        't' => 0.0, // travel hours
        'w' => '25.50', // wage
        'notes' => 'Mock test entry',
    ];
    WorkEntry::updateWorkEntry($workDetails, $userUUID);
    echo "Seeded work entry for $date\n";
}

// Confirm keys written
$pattern = 'test:work:' . $userUUID . ':2023-*';
$keys = Database::scanKeys($pattern);
echo "\nSeeded keys:\n";
foreach ($keys as $key) {
    echo $key . "\n";
}
