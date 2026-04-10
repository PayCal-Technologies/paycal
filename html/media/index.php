<?php

declare(strict_types=1);

use PayCal\Domain\Render;

require_once '../config.php';

$currentPage = 'PAGE_MEDIA';

\PayCal\Observability\Lens::boot('media');

require_once HTML.'/header.php';

echo PHP_EOL.'<link rel="stylesheet" href="' . Render::cssURL('transparency') . '">'.PHP_EOL;
echo PHP_EOL.'<link rel="stylesheet" href="' . Render::cssURL('media') . '">'.PHP_EOL;

$youtubeEmbedURL = 'https://www.youtube-nocookie.com/embed/V7Ch0bdFNX0?rel=0';

echo <<<HTML
<article class="article doc-article" aria-label="PayCal media library">
  <header class="doc-article-header">
    <h1>Media</h1>
    <p class="deck">Watch the latest PayCal media in one place.</p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight" aria-labelledby="media-featured-title">
      <h2 id="media-featured-title">Featured Media</h2>
      <p><strong>Callout:</strong> A concise overview of the PayCal mission and product promise.</p>
      <div class="media-embed" role="region" aria-label="PayCal featured media player">
        <iframe
          class="media-embed-frame"
          src="{$youtubeEmbedURL}"
          title="PayCal s Digital Fortress"
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
