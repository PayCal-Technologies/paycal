<?php declare(strict_types=1);

header('Clear-Site-Data: "cache", "cookies", "storage", "executionContexts"');
http_response_code(204);

exit;
