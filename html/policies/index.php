<?php declare(strict_types=1);

/**
 * Policies — permanent redirect to paycaltech.com/policies/.
 *
 * The PayCal Policies page has moved to the corporate site at paycaltech.com.
 * All requests are forwarded with 301 to preserve existing bookmarks and
 * search-engine indexing.  The anchor fragment (#accessibility, #terms,
 * #privacy) is a client-side concern and is preserved automatically by the
 * browser after redirect.
 *
 * PHP version 8.4.16
 */

$targetOrigin = 'https://paycaltech.com';

header('Location: ' . $targetOrigin . '/policies/', true, 301);
exit;
