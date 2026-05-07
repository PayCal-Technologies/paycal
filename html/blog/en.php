<?php declare(strict_types=1);

use PayCal\Infrastructure\Content\BlogRepository;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Render;
use PayCal\Domain\Strings;

$currentPage = 'PAGE_BLOG';

require_once __DIR__ . '/../config.php';

if (function_exists('blog_index_i18n') === false) {
  function blog_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

$pageTitle = blog_index_i18n('BLOG_PAGE_TITLE') . ' - [PayCal]';
$pageLabel = blog_index_i18n('BLOG_PAGE_TITLE');

\PayCal\Observability\Lens::boot('blog-index');

$requestUriRaw = $_SERVER['REQUEST_URI'] ?? '/blog/';
$requestUri = is_scalar($requestUriRaw) ? (string) $requestUriRaw : '/blog/';
$requestPathRaw = parse_url($requestUri, PHP_URL_PATH);
$requestPath = is_string($requestPathRaw) ? trim($requestPathRaw, '/') : 'blog';
$pathSegments = $requestPath === '' ? [] : explode('/', $requestPath);

$mode = 'list';
$slugFromPath = '';
$tagFromPath = '';

if (isset($pathSegments[0]) && $pathSegments[0] === 'blog') {
  if (isset($pathSegments[1]) && $pathSegments[1] !== '') {
    if ($pathSegments[1] === 'tags') {
      $mode = 'list';
      $tagFromPath = isset($pathSegments[2]) ? (string) $pathSegments[2] : '';
    } else {
      $mode = 'article';
      $slugFromPath = (string) $pathSegments[1];
    }
  }
}

$searchRaw = InputSanitizer::getString('q') ?? '';
$search = trim($searchRaw);

$tagRaw = $tagFromPath !== '' ? $tagFromPath : (InputSanitizer::getString('tag') ?? '');
$tag = BlogRepository::normalizeTagKey($tagRaw);

$pageRaw = InputSanitizer::getString('page') ?? '1';
$page = is_numeric($pageRaw) ? (int) $pageRaw : 1;
$page = max(1, $page);

$allPosts = BlogRepository::allPosts();
$langSuffix = (isset($lang) && $lang !== '' && $lang !== 'en') ? '?l=' . rawurlencode($lang) : '';

if ($mode === 'article') {
  $slugCandidate = strtolower(trim($slugFromPath));
  if (!preg_match('/^[a-z0-9-]+$/', $slugCandidate)) {
    $slugCandidate = '';
  }

  $post = $slugCandidate === '' ? null : BlogRepository::findBySlug($allPosts, $slugCandidate);
  if (!is_array($post)) {
    http_response_code(404);
    $pageTitle = blog_index_i18n('BLOG_POST_NOT_FOUND_TITLE') . ' - [PayCal]';
    $pageLabel = blog_index_i18n('BLOG_POST_NOT_FOUND_TITLE');

    require_once HTML . '/header.php';
    echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('blog') . '">' . PHP_EOL;
    ?>
    <article class="article blog_page" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BLOG_POST_NOT_FOUND_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
      <nav class="blog_breadcrumbs" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BREADCRUMB'), ENT_QUOTES, 'UTF-8'); ?>">
        <a href="/"><?php echo htmlspecialchars(blog_index_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
        <span aria-hidden="true">/</span>
        <a href="/blog/<?php echo $langSuffix; ?>"><?php echo htmlspecialchars(blog_index_i18n('BLOG_PAGE_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>
        <span aria-hidden="true">/</span>
        <span><?php echo htmlspecialchars(blog_index_i18n('NOT_FOUND'), ENT_QUOTES, 'UTF-8'); ?></span>
      </nav>
      <h1><?php echo htmlspecialchars(blog_index_i18n('BLOG_POST_NOT_FOUND_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h1>
      <p class="blog_deck"><?php echo htmlspecialchars(blog_index_i18n('BLOG_POST_NOT_FOUND_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
      <p><a href="/blog/<?php echo $langSuffix; ?>"><?php echo htmlspecialchars(blog_index_i18n('BLOG_RETURN_TO_LIST'), ENT_QUOTES, 'UTF-8'); ?></a></p>
    </article>
    <?php
    require_once HTML . '/footer.php';
    exit;
  }

  $adjacent = BlogRepository::adjacentForSlug($allPosts, $slugCandidate);
  $post = BlogRepository::localizedPost($post, $lang);

  $title = is_string($post['title'] ?? null) ? $post['title'] : blog_index_i18n('BLOG_UNTITLED');
  $author = is_string($post['author'] ?? null) ? $post['author'] : blog_index_i18n('BLOG_DEFAULT_AUTHOR');
  $dateDisplay = is_string($post['dateDisplay'] ?? null) ? $post['dateDisplay'] : '';
  $contentHtml = is_string($post['contentHtml'] ?? null) ? $post['contentHtml'] : '';
  $postTags = is_array($post['tags'] ?? null) ? $post['tags'] : [];
  $previousPost = is_array($adjacent['previous'] ?? null) ? $adjacent['previous'] : null;
  $nextPost = is_array($adjacent['next'] ?? null) ? $adjacent['next'] : null;

  $pageTitle = $title . ' - Blog - [PayCal]';
  $pageLabel = $title;

  require_once HTML . '/header.php';
  echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('blog') . '">' . PHP_EOL;
  ?>

  <article class="article blog_page blog_article" aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
    <header class="blog_header">
      <nav class="blog_breadcrumbs" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BREADCRUMB'), ENT_QUOTES, 'UTF-8'); ?>">
        <a href="/"><?php echo htmlspecialchars(blog_index_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
        <span aria-hidden="true">/</span>
        <a href="/blog/<?php echo $langSuffix; ?>"><?php echo htmlspecialchars(blog_index_i18n('BLOG_PAGE_TITLE'), ENT_QUOTES, 'UTF-8'); ?></a>
        <span aria-hidden="true">/</span>
        <span><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></span>
      </nav>
      <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
      <p class="blog_meta"><?php echo htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php if ($postTags !== []) { ?>
      <ul class="blog_tag_list" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BLOG_POST_TAGS_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ($postTags as $articleTag) {
          if (!is_string($articleTag) || $articleTag === '') {
            continue;
          }
          $articleTagHref = '/blog/tags/' . rawurlencode($articleTag) . '/' . $langSuffix;
          ?>
        <li><a href="<?php echo htmlspecialchars($articleTagHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($articleTag, ENT_QUOTES, 'UTF-8'); ?></a></li>
        <?php } ?>
      </ul>
      <?php } ?>
    </header>

    <section class="blog_content" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BLOG_ARTICLE_BODY_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
      <?php echo $contentHtml; ?>
    </section>

    <nav class="blog_article_nav" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BLOG_ARTICLE_NAV_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
      <?php if (is_array($previousPost)) {
        $previousSlug = is_string($previousPost['slug'] ?? null) ? $previousPost['slug'] : '';
        $previousTitle = is_string($previousPost['title'] ?? null) ? $previousPost['title'] : blog_index_i18n('BLOG_PREVIOUS');
        ?>
      <a href="<?php echo htmlspecialchars('/blog/' . rawurlencode($previousSlug) . '/' . $langSuffix, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BLOG_PREVIOUS_ARTICLE_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars(blog_index_i18n('BLOG_PREVIOUS_ARROW_LABEL'), ENT_QUOTES, 'UTF-8'); ?>
        <span><?php echo htmlspecialchars($previousTitle, ENT_QUOTES, 'UTF-8'); ?></span>
      </a>
      <?php } else { ?>
      <span class="disabled"><?php echo htmlspecialchars(blog_index_i18n('BLOG_PREVIOUS_ARROW_LABEL'), ENT_QUOTES, 'UTF-8'); ?></span>
      <?php } ?>

      <a class="blog_list_link" href="/blog/<?php echo $langSuffix; ?>"><?php echo htmlspecialchars(blog_index_i18n('BLOG_LIST'), ENT_QUOTES, 'UTF-8'); ?></a>

      <?php if (is_array($nextPost)) {
        $nextSlug = is_string($nextPost['slug'] ?? null) ? $nextPost['slug'] : '';
        $nextTitle = is_string($nextPost['title'] ?? null) ? $nextPost['title'] : blog_index_i18n('BLOG_NEXT');
        ?>
      <a href="<?php echo htmlspecialchars('/blog/' . rawurlencode($nextSlug) . '/' . $langSuffix, ENT_QUOTES, 'UTF-8'); ?>" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BLOG_NEXT_ARTICLE_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php echo htmlspecialchars(blog_index_i18n('BLOG_NEXT_ARROW_LABEL'), ENT_QUOTES, 'UTF-8'); ?>
        <span><?php echo htmlspecialchars($nextTitle, ENT_QUOTES, 'UTF-8'); ?></span>
      </a>
      <?php } else { ?>
      <span class="disabled"><?php echo htmlspecialchars(blog_index_i18n('BLOG_NEXT_ARROW_LABEL'), ENT_QUOTES, 'UTF-8'); ?></span>
      <?php } ?>
    </nav>
  </article>

  <?php
  require_once HTML . '/footer.php';
  exit;
}

$tags = BlogRepository::collectTags($allPosts);
$filteredPosts = BlogRepository::filterPosts($allPosts, $search, $tag);
$pager = BlogRepository::paginate($filteredPosts, $page, 10);

/** @var array<int, array<string, mixed>> $visiblePosts */
$visiblePosts = is_array($pager['items'] ?? null) ? $pager['items'] : [];
$currentPageNumber = is_int($pager['page'] ?? null) ? $pager['page'] : 1;
$totalPages = is_int($pager['totalPages'] ?? null) ? $pager['totalPages'] : 1;
$totalResults = is_int($pager['total'] ?? null) ? $pager['total'] : count($visiblePosts);
$hasPrev = (bool) ($pager['hasPrev'] ?? false);
$hasNext = (bool) ($pager['hasNext'] ?? false);

$buildQuery = static function (int $pageNumber) use ($search, $tag, $langSuffix, $lang): string {
  $params = ['page' => (string) $pageNumber];
  if ($search !== '') {
    $params['q'] = $search;
  }
  if ($langSuffix !== '') {
    $params['l'] = $lang;
  }
  if ($tag !== '') {
    return '/blog/tags/' . rawurlencode($tag) . '/?' . http_build_query($params);
  }

  return '/blog/?' . http_build_query($params);
};

require_once HTML . '/header.php';

echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('blog') . '">' . PHP_EOL;
?>

<article class="article blog_page" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BLOG_OVERVIEW_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
  <header class="blog_header">
    <nav class="blog_breadcrumbs" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BREADCRUMB'), ENT_QUOTES, 'UTF-8'); ?>">
      <a href="/"><?php echo htmlspecialchars(blog_index_i18n('HOME'), ENT_QUOTES, 'UTF-8'); ?></a>
      <span aria-hidden="true">/</span>
      <span><?php echo htmlspecialchars(blog_index_i18n('BLOG_PAGE_TITLE'), ENT_QUOTES, 'UTF-8'); ?></span>
    </nav>
    <h1><?php echo htmlspecialchars(blog_index_i18n('BLOG_PAGE_TITLE'), ENT_QUOTES, 'UTF-8'); ?></h1>
    <p class="blog_deck"><?php echo htmlspecialchars(blog_index_i18n('BLOG_PAGE_DECK'), ENT_QUOTES, 'UTF-8'); ?></p>
  </header>

  <section class="blog_search" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BLOG_SEARCH_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <form method="get" action="/blog/" class="blog_search_form">
      <label for="blog_search_q"><?php echo htmlspecialchars(blog_index_i18n('SEARCH'), ENT_QUOTES, 'UTF-8'); ?></label>
      <input
        id="blog_search_q"
        type="search"
        name="q"
        value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
        placeholder="<?php echo htmlspecialchars(blog_index_i18n('BLOG_SEARCH_PLACEHOLDER'), ENT_QUOTES, 'UTF-8'); ?>"
      >
      <?php if ($tag !== '') { ?>
      <input type="hidden" name="tag" value="<?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>">
      <?php } ?>
      <?php if ($langSuffix !== '') { ?>
      <input type="hidden" name="l" value="<?php echo htmlspecialchars($lang, ENT_QUOTES, 'UTF-8'); ?>">
      <?php } ?>
      <button type="submit"><?php echo htmlspecialchars(blog_index_i18n('SEARCH'), ENT_QUOTES, 'UTF-8'); ?></button>
      <?php if ($search !== '' || $tag !== '') { ?>
      <a class="blog_reset" href="/blog/<?php echo $langSuffix; ?>"><?php echo htmlspecialchars(blog_index_i18n('BLOG_CLEAR'), ENT_QUOTES, 'UTF-8'); ?></a>
      <?php } ?>
    </form>
  </section>

  <section class="blog_tags" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BLOG_FILTER_TAG_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <a class="blog_tag<?php echo $tag === '' ? ' active' : ''; ?>" href="/blog/<?php echo $langSuffix; ?>"><?php echo htmlspecialchars(blog_index_i18n('ALL'), ENT_QUOTES, 'UTF-8'); ?></a>
    <?php foreach ($tags as $tagName) {
      if ($tagName === '') {
        continue;
      }
      $tagHref = '/blog/tags/' . rawurlencode($tagName) . '/';
      $tagParams = [];
      if ($search !== '') {
        $tagParams['q'] = $search;
      }
      if ($langSuffix !== '') {
        $tagParams['l'] = $lang;
      }
      if ($tagParams !== []) {
        $tagHref .= '?' . http_build_query($tagParams);
      }
      ?>
    <a class="blog_tag<?php echo BlogRepository::normalizeTagKey($tagName) === $tag ? ' active' : ''; ?>" href="<?php echo htmlspecialchars($tagHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($tagName, ENT_QUOTES, 'UTF-8'); ?></a>
    <?php } ?>
  </section>

  <section class="blog_results" aria-live="polite">
    <p class="blog_results_count"><?php echo htmlspecialchars((string) $totalResults, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($totalResults === 1 ? blog_index_i18n('BLOG_ARTICLE_FOUND_SINGULAR') : blog_index_i18n('BLOG_ARTICLE_FOUND_PLURAL'), ENT_QUOTES, 'UTF-8'); ?></p>

    <?php if ($visiblePosts === []) { ?>
    <p class="blog_empty"><?php echo htmlspecialchars(blog_index_i18n('BLOG_EMPTY_RESULTS'), ENT_QUOTES, 'UTF-8'); ?></p>
    <?php } ?>

    <?php foreach ($visiblePosts as $post) {
      $slug = is_string($post['slug'] ?? null) ? $post['slug'] : '';
      $title = is_string($post['title'] ?? null) ? $post['title'] : blog_index_i18n('BLOG_UNTITLED');
      $author = is_string($post['author'] ?? null) ? $post['author'] : blog_index_i18n('BLOG_DEFAULT_AUTHOR');
      $dateDisplay = is_string($post['dateDisplay'] ?? null) ? $post['dateDisplay'] : '';
      $snippet = is_string($post['snippet'] ?? null) ? $post['snippet'] : '';
      $postTags = is_array($post['tags'] ?? null) ? $post['tags'] : [];
      $postHref = '/blog/' . rawurlencode($slug) . '/' . $langSuffix;
      ?>
    <article class="blog_card" aria-label="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
      <h2><a href="<?php echo htmlspecialchars($postHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></a></h2>
      <p class="blog_meta"><?php echo htmlspecialchars($dateDisplay, ENT_QUOTES, 'UTF-8'); ?> · <?php echo htmlspecialchars($author, ENT_QUOTES, 'UTF-8'); ?></p>
      <p class="blog_snippet"><?php echo htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8'); ?></p>
      <?php if ($postTags !== []) { ?>
      <ul class="blog_tag_list" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BLOG_POST_TAGS_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
        <?php foreach ($postTags as $postTag) {
          if (!is_string($postTag) || $postTag === '') {
            continue;
          }
          $postTagHref = '/blog/tags/' . rawurlencode($postTag) . '/' . $langSuffix;
          ?>
        <li><a href="<?php echo htmlspecialchars($postTagHref, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($postTag, ENT_QUOTES, 'UTF-8'); ?></a></li>
        <?php } ?>
      </ul>
      <?php } ?>
    </article>
    <?php } ?>
  </section>

  <?php if ($totalPages > 1) { ?>
  <nav class="blog_pagination" aria-label="<?php echo htmlspecialchars(blog_index_i18n('BLOG_PAGE_NAV_ARIA'), ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($hasPrev) { ?>
    <a href="<?php echo htmlspecialchars($buildQuery($currentPageNumber - 1), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(blog_index_i18n('BLOG_PREVIOUS_PAGE'), ENT_QUOTES, 'UTF-8'); ?></a>
    <?php } else { ?>
    <span class="disabled"><?php echo htmlspecialchars(blog_index_i18n('BLOG_PREVIOUS_PAGE'), ENT_QUOTES, 'UTF-8'); ?></span>
    <?php } ?>

    <span class="blog_page_indicator"><?php echo htmlspecialchars(blog_index_i18n('BLOG_PAGE_LABEL'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) $currentPageNumber, ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars(blog_index_i18n('BLOG_OF_LABEL'), ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars((string) $totalPages, ENT_QUOTES, 'UTF-8'); ?></span>

    <?php if ($hasNext) { ?>
    <a href="<?php echo htmlspecialchars($buildQuery($currentPageNumber + 1), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(blog_index_i18n('BLOG_NEXT_PAGE'), ENT_QUOTES, 'UTF-8'); ?></a>
    <?php } else { ?>
    <span class="disabled"><?php echo htmlspecialchars(blog_index_i18n('BLOG_NEXT_PAGE'), ENT_QUOTES, 'UTF-8'); ?></span>
    <?php } ?>
  </nav>
  <?php } ?>
</article>

<?php
require_once HTML . '/footer.php';
