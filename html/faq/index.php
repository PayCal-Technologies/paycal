<?php declare(strict_types=1);

/**
 * FAQ — permanent redirect to paycaltech.com/faq/.
 *
 * The PayCal FAQ has moved to the corporate site at paycaltech.com.
 * All requests are forwarded with 301 to preserve existing bookmarks and
 * search-engine indexing.
 *
 * PHP version 8.4.16
 */

header('Location: https://paycaltech.com/faq/', true, 301);
exit;
$pageTitle = 'Frequently Asked Questions - [PayCal]';
$pageLabel = 'Frequently Asked Questions';

require_once '../config.php';

\PayCal\Observability\Lens::boot('faq');
if (\PayCal\Domain\InputSanitizer::getString('lens') === '1') {
  \PayCal\Observability\Lens::add('FAQ Backend Snapshot', [
    'page' => $currentPage,
    'language' => (string) USER_LANGUAGE,
    'template' => 'faq/'.USER_LANGUAGE,
  ]);
}

// --- Load Page ---
require_once HTML.'/header.php';

require_once __DIR__.'/'.USER_LANGUAGE.'/index.php';

require_once HTML.'/footer.php';
