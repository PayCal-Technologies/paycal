<?php declare(strict_types=1);

/**
 * Blog — permanent redirect to paycaltech.com/blog/.
 *
 * The PayCal blog has moved to the corporate site at paycaltech.com.
 * All requests are forwarded with 301 to preserve existing bookmarks and
 * search-engine indexing.  The path segment after /blog/ is passed through
 * after validation so individual article URLs continue to resolve correctly.
 *
 * PHP version 8.4.16
 */

$subPath = '';
$rawUri = $_SERVER['REQUEST_URI'] ?? '';
if (is_string($rawUri)) {
    $rawPath = parse_url($rawUri, PHP_URL_PATH);
    if (is_string($rawPath) && str_starts_with($rawPath, '/blog/')) {
        $candidate = substr($rawPath, 6); // strip leading '/blog/'
        // Allow only slug-safe characters: lowercase/uppercase letters, digits,
        // hyphens, underscores, forward slashes, and dots.
        if (preg_match('/^[a-zA-Z0-9_\-\/\.]*$/', $candidate)) {
            $subPath = $candidate;
        }
    }
}

header('Location: https://paycaltech.com/blog/' . $subPath, true, 301);
exit;
