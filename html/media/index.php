<?php

declare(strict_types=1);

use PayCal\Domain\Render;
use PayCal\Domain\Strings;

require_once '../config.php';

$currentPage = 'PAGE_MEDIA';

\PayCal\Observability\Lens::boot('media');

require_once HTML.'/header.php';

echo PHP_EOL.'<link rel="stylesheet" href="' . Render::cssURL('transparency') . '">'.PHP_EOL;
echo PHP_EOL.'<link rel="stylesheet" href="' . Render::cssURL('media') . '">'.PHP_EOL;

$youtubeEmbedURL = 'https://www.youtube-nocookie.com/embed/V7Ch0bdFNX0?rel=0';
$mediaI18nKeys = [
  'MEDIA_PAGE_ARIA',
  'MEDIA_PAGE_CALLOUT_BODY',
  'MEDIA_PAGE_CALLOUT_LABEL',
  'MEDIA_PAGE_DECK',
  'MEDIA_PAGE_FEATURED_TITLE',
  'MEDIA_PAGE_PLAYER_ARIA',
  'MEDIA_PAGE_TITLE',
  'MEDIA_PAGE_VIDEO_TITLE',
];
$mediaI18n = [];
foreach ($mediaI18nKeys as $mediaI18nKey) {
  $mediaI18n[$mediaI18nKey] = htmlspecialchars(Strings::i18n($mediaI18nKey), ENT_QUOTES, 'UTF-8');
}

echo <<<HTML
<article class="article doc-article" aria-labelledby="media-page-title" aria-describedby="media-page-deck">
  <header class="doc-article-header">
    <h1 id="media-page-title">{$mediaI18n['MEDIA_PAGE_TITLE']}</h1>
    <p id="media-page-deck" class="deck">{$mediaI18n['MEDIA_PAGE_DECK']}</p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight" aria-labelledby="media-featured-title">
      <h2 id="media-featured-title">{$mediaI18n['MEDIA_PAGE_FEATURED_TITLE']}</h2>
      <p><strong>{$mediaI18n['MEDIA_PAGE_CALLOUT_LABEL']}:</strong> {$mediaI18n['MEDIA_PAGE_CALLOUT_BODY']}</p>
      <div class="media-embed" role="region" aria-labelledby="media-player-title">
        <h3 id="media-player-title" class="visually_hidden">{$mediaI18n['MEDIA_PAGE_PLAYER_ARIA']}</h3>
        <iframe
          class="media-embed-frame"
          src="{$youtubeEmbedURL}"
          title="{$mediaI18n['MEDIA_PAGE_VIDEO_TITLE']}"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          referrerpolicy="strict-origin-when-cross-origin"
          loading="lazy"
          allowfullscreen>
        </iframe>
      </div>
    </section>
  </div>
</article>
HTML;

require_once HTML.'/footer.php';
