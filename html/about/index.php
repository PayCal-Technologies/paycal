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
$i18n = [];
foreach (['ABOUT_OVERVIEW_ARIA', 'ABOUT_PAGE_TITLE', 'ABOUT_PAGE_DECK'] as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

\PayCal\Observability\Lens::boot('about');
if (\PayCal\Domain\InputSanitizer::getString('lens') === '1') {
  \PayCal\Observability\Lens::add('About Backend Snapshot', [
    'page' => $currentPage,
    'language' => (string) USER_LANGUAGE,
    'template' => 'about/'.USER_LANGUAGE,
  ]);
}

require_once HTML.'/header.php';

echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('transparency') . "\">".PHP_EOL;

$aboutOverviewAria = htmlspecialchars($i18n['ABOUT_OVERVIEW_ARIA'], ENT_QUOTES, 'UTF-8');
$aboutPageTitle = htmlspecialchars($i18n['ABOUT_PAGE_TITLE'], ENT_QUOTES, 'UTF-8');
$aboutPageDeck = htmlspecialchars($i18n['ABOUT_PAGE_DECK'], ENT_QUOTES, 'UTF-8');

echo <<<HTML
<article class="article doc-article" aria-label="{$aboutOverviewAria}">
  <header class="doc-article-header">
    <h1>{$aboutPageTitle}</h1>
    <p class="deck">{$aboutPageDeck}</p>
  </header>
</article>
HTML;

require_once __DIR__.'/'.USER_LANGUAGE.'/index.php';

require_once HTML.'/footer.php';
