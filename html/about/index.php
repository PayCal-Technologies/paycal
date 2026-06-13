<?php declare(strict_types=1);

/**
 * About — permanent redirect to paycaltech.com/about/.
 *
 * The PayCal About page has moved to the corporate site at paycaltech.com.
 * All requests are forwarded with 301 to preserve existing bookmarks and
 * search-engine indexing.
 *
 * PHP version 8.4.16
 */

header('Location: https://paycaltech.com/about/', true, 301);
exit;
