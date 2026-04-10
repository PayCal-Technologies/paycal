<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../config.php';

$messageRaw = InputSanitizer::getString('message') ?? 'Email verified successfully!';
$message = (string) $messageRaw;

header('Location: /auth/?auth_tab=signin&verification_success=1&signin_message=' . urlencode($message), true, 302);
exit;
