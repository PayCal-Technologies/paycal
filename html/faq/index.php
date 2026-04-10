<?php

declare(strict_types=1);

/**
 * FAQ page controller.
 */
$currentPage = 'PAGE_FAQ';
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
