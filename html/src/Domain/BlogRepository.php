<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

/**
 * BlogRepository
 *
 * Loads markdown-backed blog posts with frontmatter metadata and provides
 * filtering, pagination, and safe HTML rendering utilities for blog pages.
 */
final class BlogRepository
{
  private const PAGE_SIZE = 10;
  private const TAG_FILE_EXT = '.tag';

  /**
   * @return array<int, array<string, mixed>>
   */
  public static function allPosts(): array
  {
    $dir = self::postsDirectory();
    if (!is_dir($dir)) {
      return [];
    }

    $files = glob($dir . '/*.md');
    if ($files === false) {
      return [];
    }

    $posts = [];
    foreach ($files as $file) {
      if (!is_file($file)) {
        continue;
      }
      // Skip localized variants (e.g. slug.fr.md, slug.de.md) — only index canonical files.
      $basename = pathinfo($file, PATHINFO_FILENAME); // e.g. "slug" or "slug.fr"
      if (preg_match('/\.[a-z]{2}$/', $basename)) {
        continue;
      }

      $post = self::parsePostFile($file);
      if ($post !== null) {
        $posts[] = $post;
      }
    }

    usort(
      $posts,
      static fn (array $a, array $b): int => self::int($b['dateTimestamp'] ?? 0) <=> self::int($a['dateTimestamp'] ?? 0)
    );

    self::syncTagFiles($posts);

    return $posts;
  }

  /**
   * @param array<int, array<string, mixed>> $posts
   * @return array<int, array<string, mixed>>
   */
  public static function filterPosts(array $posts, string $query = '', string $tag = ''): array
  {
    $query = trim($query);
    $tag = self::normalizeTagKey($tag);

    if ($query === '' && $tag === '') {
      return $posts;
    }

    $queryNeedle = strtolower($query);
    $tagSlugMap = self::slugsForTagKey($tag);

    return array_values(array_filter($posts, static function (array $post) use ($queryNeedle, $tag, $tagSlugMap): bool {
      $tags = is_array($post['tags'] ?? null) ? $post['tags'] : [];
      $tagList = [];
      foreach ($tags as $candidateTag) {
        if (is_string($candidateTag) && $candidateTag !== '') {
          $tagList[] = self::normalizeTagKey($candidateTag);
        }
      }

      if ($tag !== '') {
        $slug = self::str($post['slug'] ?? '');
        if ($tagSlugMap !== [] && !in_array($slug, $tagSlugMap, true)) {
          return false;
        }

        if ($tagSlugMap === [] && !in_array($tag, $tagList, true)) {
          return false;
        }
      }

      if ($queryNeedle === '') {
        return true;
      }

      $haystack = strtolower(
        self::str($post['title'] ?? '')
        . ' '
        . self::str($post['author'] ?? '')
        . ' '
        . self::str($post['snippet'] ?? '')
        . ' '
        . implode(' ', $tagList)
      );

      return str_contains($haystack, $queryNeedle);
    }));
  }

  /**
   * @param array<int, array<string, mixed>> $posts
   * @return array<string, mixed>
   */
  public static function paginate(array $posts, int $page, int $pageSize = self::PAGE_SIZE): array
  {
    $total = count($posts);
    $totalPages = max(1, (int) ceil($total / max(1, $pageSize)));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $pageSize;

    return [
      'items' => array_slice($posts, $offset, $pageSize),
      'page' => $page,
      'pageSize' => $pageSize,
      'total' => $total,
      'totalPages' => $totalPages,
      'hasPrev' => $page > 1,
      'hasNext' => $page < $totalPages,
    ];
  }

  /**
   * @param array<int, array<string, mixed>> $posts
   * @return array<int, string>
   */
  public static function collectTags(array $posts): array
  {
    $fileTags = self::tagsFromTagFiles();
    if ($fileTags !== []) {
      return $fileTags;
    }

    $all = [];
    foreach ($posts as $post) {
      $tags = is_array($post['tags'] ?? null) ? $post['tags'] : [];
      foreach ($tags as $tag) {
        if (is_string($tag) && $tag !== '') {
          $key = self::normalizeTagKey($tag);
          $all[$key] = $key;
        }
      }
    }

    ksort($all);

    return array_values($all);
  }

  /**
   * @param array<int, array<string, mixed>> $posts
   * @return array<string, mixed>|null
   */
  public static function findBySlug(array $posts, string $slug): ?array
  {
    foreach ($posts as $post) {
      if (self::str($post['slug'] ?? '') === $slug) {
        return $post;
      }
    }

    return null;
  }

  /**
   * Return a post with title and contentHtml overridden from a locale-specific
   * markdown file (e.g. slug.fr.md) when one exists. Falls back to the original
   * post unchanged if no translation file is found or if $lang is 'en'.
   *
   * @param array<string, mixed> $post
   * @return array<string, mixed>
   */
  public static function localizedPost(array $post, string $lang): array
  {
    if ($lang === 'en' || $lang === '') {
      return $post;
    }

    $slug = self::str($post['slug'] ?? '');
    if ($slug === '') {
      return $post;
    }

    $localizedFile = self::postsDirectory() . '/' . $slug . '.' . $lang . '.md';
    if (!is_file($localizedFile)) {
      return $post;
    }

    $localized = self::parsePostFile($localizedFile);
    if (!is_array($localized)) {
      return $post;
    }

    // Merge: keep canonical slug/date/tags from the English source; take
    // title, author, and contentHtml from the translated file.
    return array_merge($post, [
      'title'       => $localized['title'],
      'author'      => $localized['author'],
      'contentHtml' => $localized['contentHtml'],
      'snippet'     => $localized['snippet'],
    ]);
  }

  /**
   * @param array<int, array<string, mixed>> $posts
   * @return array<string, mixed>
   */
  public static function adjacentForSlug(array $posts, string $slug): array
  {
    $currentIndex = null;
    foreach ($posts as $i => $post) {
      if (self::str($post['slug'] ?? '') === $slug) {
        $currentIndex = $i;
        break;
      }
    }

    if ($currentIndex === null) {
      return ['previous' => null, 'next' => null];
    }

    return [
      'previous' => $currentIndex > 0 ? $posts[$currentIndex - 1] : null,
      'next' => isset($posts[$currentIndex + 1]) ? $posts[$currentIndex + 1] : null,
    ];
  }

  /**
   * Handles postsDirectory operation.
   */
  private static function postsDirectory(): string
  {
    return rtrim(Environment::appHome(), '/') . '/html/blog/markdown';
  }

  /**
   * Handles tagsDirectory operation.
   */
  private static function tagsDirectory(): string
  {
    return rtrim(Environment::appHome(), '/') . '/html/blog/tags';
  }

  /**
   * @return array<string, mixed>|null
   */
  private static function parsePostFile(string $file): ?array
  {
    $content = file_get_contents($file);
    if (!is_string($content) || trim($content) === '') {
      return null;
    }

    $slug = pathinfo($file, PATHINFO_FILENAME);
    if (!preg_match('/^[a-z0-9.-]+$/', $slug)) {
      return null;
    }

    $metadata = [];
    $markdownBody = $content;

    if (preg_match('/\A---\R(.*?)\R---\R(.*)\z/s', $content, $matches) === 1) {
      $metadata = self::parseFrontmatter((string) $matches[1]);
      $markdownBody = (string) $matches[2];
    }

    $title = (string) ($metadata['title'] ?? self::humanizeSlug($slug));
    $author = (string) ($metadata['author'] ?? 'PayCal Editorial');
    $dateIso = self::normalizeDate((string) ($metadata['date'] ?? ''));
    $dateTimestamp = strtotime($dateIso);
    if ($dateTimestamp === false) {
      $dateTimestamp = filemtime($file);
      if ($dateTimestamp === false) {
        $dateTimestamp = time();
      }
      $dateIso = date('Y-m-d', $dateTimestamp);
    }

    $tags = self::parseTags((string) ($metadata['tags'] ?? ''));
    $plainText = self::markdownToPlainText($markdownBody);

    return [
      'slug' => $slug,
      'title' => $title,
      'author' => $author,
      'dateIso' => $dateIso,
      'dateDisplay' => date('F j, Y', $dateTimestamp),
      'dateTimestamp' => $dateTimestamp,
      'tags' => $tags,
      'snippet' => self::snippet($plainText, 200),
      'contentHtml' => self::renderMarkdown($markdownBody),
      'sourcePath' => $file,
    ];
  }

  /**
   * @return array<string, string>
   */
  private static function parseFrontmatter(string $frontmatter): array
  {
    $result = [];
    $lines = preg_split('/\R/', $frontmatter) ?: [];
    foreach ($lines as $line) {
      if (trim($line) === '') {
        continue;
      }

      $parts = explode(':', $line, 2);
      if (count($parts) !== 2) {
        continue;
      }

      $key = strtolower(trim($parts[0]));
      $value = trim($parts[1]);
      if ($key !== '') {
        $result[$key] = $value;
      }
    }

    return $result;
  }

  /**
   * @return array<int, string>
   */
  private static function parseTags(string $raw): array
  {
    if ($raw === '') {
      return [];
    }

    $parts = array_map('trim', explode(',', $raw));
    $clean = [];
    foreach ($parts as $part) {
      if ($part === '') {
        continue;
      }

      $tag = preg_replace('/[^a-zA-Z0-9_\-\s]/', '', $part);
      if (!is_string($tag)) {
        continue;
      }

      $tag = trim($tag);
      if ($tag !== '') {
        $tagKey = self::normalizeTagKey($tag);
        $clean[$tagKey] = $tagKey;
      }
    }

    return array_values($clean);
  }

  /**
   * Handles normalizeDate operation.
   */
  private static function normalizeDate(string $rawDate): string
  {
    if ($rawDate === '') {
      return date('Y-m-d');
    }

    $ts = strtotime($rawDate);
    if ($ts === false) {
      return date('Y-m-d');
    }

    return date('Y-m-d', $ts);
  }

  /**
   * Handles humanizeSlug operation.
   */
  private static function humanizeSlug(string $slug): string
  {
    return ucwords(str_replace('-', ' ', $slug));
  }

  /**
   * Handles snippet operation.
   */
  private static function snippet(string $text, int $maxChars): string
  {
    $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
    if (strlen($text) <= $maxChars) {
      return $text;
    }

    return rtrim(substr($text, 0, $maxChars - 1)) . '…';
  }

  /**
   * Handles markdownToPlainText operation.
   */
  private static function markdownToPlainText(string $markdown): string
  {
    $text = preg_replace('/```[\s\S]*?```/', ' ', $markdown) ?? $markdown;
    $text = preg_replace('/`([^`]+)`/', '$1', $text) ?? $text;
    $text = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $text) ?? $text;
    $text = preg_replace('/[#>*_\-]/', ' ', $text) ?? $text;

    return trim(preg_replace('/\s+/', ' ', $text) ?? $text);
  }

  /**
   * Handles renderMarkdown operation.
   */
  private static function renderMarkdown(string $markdown): string
  {
    $lines = preg_split('/\R/', $markdown) ?: [];
    $html = '';
    $inUl = false;
    $inOl = false;

    foreach ($lines as $lineRaw) {
      $line = rtrim((string) $lineRaw);

      if (trim($line) === '') {
        if ($inUl) {
          $html .= "</ul>\n";
          $inUl = false;
        }
        if ($inOl) {
          $html .= "</ol>\n";
          $inOl = false;
        }
        continue;
      }

      if (preg_match('/^###\s+(.+)$/', $line, $match) === 1) {
        if ($inUl) {
          $html .= "</ul>\n";
          $inUl = false;
        }
        if ($inOl) {
          $html .= "</ol>\n";
          $inOl = false;
        }
        $html .= '<h3>' . self::renderInline((string) $match[1]) . "</h3>\n";
        continue;
      }

      if (preg_match('/^##\s+(.+)$/', $line, $match) === 1) {
        if ($inUl) {
          $html .= "</ul>\n";
          $inUl = false;
        }
        if ($inOl) {
          $html .= "</ol>\n";
          $inOl = false;
        }
        $html .= '<h2>' . self::renderInline((string) $match[1]) . "</h2>\n";
        continue;
      }

      if (preg_match('/^#\s+(.+)$/', $line, $match) === 1) {
        if ($inUl) {
          $html .= "</ul>\n";
          $inUl = false;
        }
        if ($inOl) {
          $html .= "</ol>\n";
          $inOl = false;
        }
        $html .= '<h1>' . self::renderInline((string) $match[1]) . "</h1>\n";
        continue;
      }

      if (preg_match('/^\d+\.\s+(.+)$/', $line, $match) === 1) {
        if ($inUl) {
          $html .= "</ul>\n";
          $inUl = false;
        }
        if (!$inOl) {
          $html .= "<ol>\n";
          $inOl = true;
        }
        $html .= '<li>' . self::renderInline((string) $match[1]) . "</li>\n";
        continue;
      }

      if (preg_match('/^[\-*]\s+(.+)$/', $line, $match) === 1) {
        if ($inOl) {
          $html .= "</ol>\n";
          $inOl = false;
        }
        if (!$inUl) {
          $html .= "<ul>\n";
          $inUl = true;
        }
        $html .= '<li>' . self::renderInline((string) $match[1]) . "</li>\n";
        continue;
      }

      if ($inUl) {
        $html .= "</ul>\n";
        $inUl = false;
      }
      if ($inOl) {
        $html .= "</ol>\n";
        $inOl = false;
      }

      $html .= '<p>' . self::renderInline($line) . "</p>\n";
    }

    if ($inUl) {
      $html .= "</ul>\n";
    }
    if ($inOl) {
      $html .= "</ol>\n";
    }

    return $html;
  }

  /**
   * Handles renderInline operation.
   */
  private static function renderInline(string $text): string
  {
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

    $safe = preg_replace_callback('/`([^`]+)`/', static function (array $m): string {
      return '<code>' . $m[1] . '</code>';
    }, $safe) ?? $safe;

    $safe = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $safe) ?? $safe;
    $safe = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $safe) ?? $safe;

    $safe = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', static function (array $m): string {
      $label = $m[1];
      $url = trim(htmlspecialchars_decode($m[2], ENT_QUOTES));
      if (!self::isSafeHref($url)) {
        return $label;
      }

      $href = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
      $classAttr = self::isExternalHref($url) ? ' class="external_link"' : '';
      return '<a href="' . $href . '"' . $classAttr . '>' . $label . '</a>';
    }, $safe) ?? $safe;

    return $safe;
  }

  /**
   * Handles isExternalHref operation.
   */
  private static function isExternalHref(string $href): bool
  {
    $scheme = parse_url($href, PHP_URL_SCHEME);
    $host = parse_url($href, PHP_URL_HOST);
    if (!is_string($scheme) || !is_string($host)) {
      return false;
    }

    $scheme = strtolower($scheme);
    if (!in_array($scheme, ['http', 'https'], true)) {
      return false;
    }

    $appHostRaw = parse_url(Environment::appURL(''), PHP_URL_HOST);
    $appHost = is_string($appHostRaw) ? strtolower($appHostRaw) : '';

    return strtolower($host) !== $appHost;
  }

  /**
   * Handles isSafeHref operation.
   */
  private static function isSafeHref(string $href): bool
  {
    if ($href === '') {
      return false;
    }

    if (str_starts_with($href, '/')) {
      return true;
    }

    $scheme = parse_url($href, PHP_URL_SCHEME);
    if (!is_string($scheme)) {
      return false;
    }

    $scheme = strtolower($scheme);

    return in_array($scheme, ['http', 'https'], true);
  }

  /**
   * @param array<int, array<string, mixed>> $posts
   */
  private static function syncTagFiles(array $posts): void
  {
    $dir = self::tagsDirectory();
    if (!is_dir($dir)) {
      @mkdir($dir, 0775, true);
    }

    if (!is_dir($dir)) {
      return;
    }

    $existing = glob($dir . '/*' . self::TAG_FILE_EXT);
    if (is_array($existing)) {
      foreach ($existing as $file) {
        if (is_file($file)) {
          @unlink($file);
        }
      }
    }

    $tagMap = [];
    foreach ($posts as $post) {
      $slug = self::str($post['slug'] ?? '');
      if ($slug === '') {
        continue;
      }

      $filename = $slug . '.md';
      $tags = is_array($post['tags'] ?? null) ? $post['tags'] : [];
      foreach ($tags as $tag) {
        $key = self::normalizeTagKey(self::str($tag));
        if ($key === '') {
          continue;
        }
        if (!isset($tagMap[$key])) {
          $tagMap[$key] = [];
        }
        $tagMap[$key][] = $filename;
      }
    }

    foreach ($tagMap as $tagKey => $files) {
      $files = array_values(array_unique($files));
      sort($files);
      $path = $dir . '/' . $tagKey . self::TAG_FILE_EXT;
      @file_put_contents($path, implode(PHP_EOL, $files) . PHP_EOL);
    }
  }

  /**
   * @return array<int, string>
   */
  private static function tagsFromTagFiles(): array
  {
    $dir = self::tagsDirectory();
    if (!is_dir($dir)) {
      return [];
    }

    $files = glob($dir . '/*' . self::TAG_FILE_EXT);
    if ($files === false || $files === []) {
      return [];
    }

    $tags = [];
    foreach ($files as $file) {
      $base = basename($file, self::TAG_FILE_EXT);
      $key = self::normalizeTagKey($base);
      if ($key !== '') {
        $tags[] = $key;
      }
    }

    sort($tags);

    return array_values(array_unique($tags));
  }

  /**
   * @return array<int, string>
   */
  private static function slugsForTagKey(string $tagKey): array
  {
    $tagKey = self::normalizeTagKey($tagKey);
    if ($tagKey === '') {
      return [];
    }

    $path = self::tagsDirectory() . '/' . $tagKey . self::TAG_FILE_EXT;
    if (!is_file($path)) {
      return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
      return [];
    }

    $slugs = [];
    foreach ($lines as $line) {
      $name = trim((string) $line);
      if ($name === '') {
        continue;
      }
      $slug = pathinfo($name, PATHINFO_FILENAME);
      if (preg_match('/^[a-z0-9-]+$/', $slug)) {
        $slugs[] = $slug;
      }
    }

    return array_values(array_unique($slugs));
  }

  /**
   * Handles normalizeTagKey operation.
   */
  public static function normalizeTagKey(string $tag): string
  {
    $tag = strtolower(trim($tag));
    if ($tag === '') {
      return '';
    }

    $tag = preg_replace('/\s+/', '_', $tag) ?? $tag;
    $tag = preg_replace('/[^a-z0-9_-]/', '', $tag) ?? $tag;

    return trim($tag, '_-');
  }

  /**
   * Handles str operation.
   */
  private static function str(mixed $value, string $default = ''): string
  {
    return is_scalar($value) ? (string) $value : $default;
  }

  /**
   * Handles int operation.
   */
  private static function int(mixed $value, int $default = 0): int
  {
    return is_numeric($value) ? (int) $value : $default;
  }
}


