<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

/**
 * robots.txt and sitemap.xml content for public SEO surfaces.
 *
 * robots.txt Disallow is a crawl preference signal only — not access control.
 * Private/authenticated HTML must pair with X-Robots-Tag (or meta robots) noindex
 * via CrawlPolicy::shouldIndexPage(); canonical URLs on marketing pages use
 * Environment::publicMetadataURL() so dev hosts do not leak into search metadata.
 */
final class CrawlPolicy
{
  /**
   * Public marketing and documentation paths (aligned with KnockKnockTest).
   *
   * @var list<string>
   */
  public const PUBLIC_INDEX_PATHS = [
    '/',
    '/about/',
    '/auth/',
    '/blog/',
    '/contact/',
    '/faq/',
    '/help/',
    '/language-coverage/',
    '/media/',
    '/policies/',
    '/premium/',
    '/pricing/',
    '/security/',
    '/soc2/',
    '/status/',
    '/transparency/',
    '/verify/',
  ];

  /**
   * Authenticated, admin, API, and tooling surfaces crawlers should skip.
   *
   * Render assets (/css/, /js/ except dev/admin/tests, /fonts/, /img/) stay
   * crawlable so marketing pages can be rendered by crawlers that fetch assets.
   *
   * @var list<string>
   */
  public const PRIVATE_DISALLOW_PATHS = [
    '/admin/',
    '/api/',
    '/business/',
    '/calendar/',
    '/connections/',
    '/debug/',
    '/dev/',
    '/earnings/',
    '/forecast/',
    '/groups/',
    '/internal/',
    '/payperiods/',
    '/profile/',
    '/reports/',
    '/settings/',
    '/sites/',
    '/tests/',
    '/unverified/',
    '/ws/',
    '/js/dev/',
    '/js/admin/',
    '/js/tests/',
  ];

  /**
   * Query-string and calendar month traps that create crawl noise.
   *
   * Pair with noindex on authenticated calendar/report HTML (header.php).
   *
   * @var list<string>
   */
  public const QUERY_TRAP_DISALLOW_PATTERNS = [
    '/*?user_uuid=',
    '/*?month=',
    '/*?clear_user_view=',
    '/20',
  ];

  /**
   * $currentPage values that may be indexed on production when unauthenticated.
   *
   * @var list<string>
   */
  public const PUBLIC_MARKETING_PAGE_IDS = [
    'PAGE_ABOUT',
    'PAGE_AUTH',
    'PAGE_BLOG',
    'PAGE_CONTACT',
    'PAGE_FAQ',
    'PAGE_HELP',
    'PAGE_LANGUAGE_COVERAGE',
    'PAGE_MEDIA',
    'PAGE_POLICIES',
    'PAGE_PREMIUM',
    'PAGE_PRICING',
    'PAGE_REGISTER',
    'PAGE_SECURITY',
    'PAGE_SIGNIN',
    'PAGE_SOC2',
    'PAGE_STATUS',
    'PAGE_TRANSPARENCY',
    'PAGE_VERIFY',
  ];

  /**
   * AI training crawlers — complements X-Robots-Tag noai / noimageai on pages.
   *
   * @var list<string>
   */
  private const AI_TRAINING_USER_AGENTS = [
    'GPTBot',
    'ChatGPT-User',
    'Google-Extended',
    'anthropic-ai',
    'ClaudeBot',
    'Bytespider',
    'CCBot',
  ];

  /**
   * Whether an HTML page should send indexable robots directives.
   *
   * Authenticated app surfaces and non-production hosts always noindex.
   */
  public static function shouldIndexPage(string $currentPage, bool $isAuthenticated): bool
  {
    if (!Environment::allowsPublicIndexing()) {
      return false;
    }

    if ($isAuthenticated) {
      return false;
    }

    return in_array($currentPage, self::PUBLIC_MARKETING_PAGE_IDS, true);
  }

  public static function renderRobotsTxt(): string
  {
    if (!Environment::allowsPublicIndexing()) {
      return self::renderBlockAllRobotsTxt();
    }

    return self::renderProductionRobotsTxt();
  }

  public static function renderSitemapXml(): string
  {
    if (!Environment::allowsPublicIndexing()) {
      return '';
    }

    $urls = [];
    foreach (self::PUBLIC_INDEX_PATHS as $path) {
      $urls[] = Environment::publicMetadataURL($path);
    }

    $lastmod = gmdate('Y-m-d');
    $entries = [];
    foreach ($urls as $url) {
      $escaped = htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8');
      $entries[] = "  <url>\n    <loc>{$escaped}</loc>\n    <lastmod>{$lastmod}</lastmod>\n  </url>";
    }

    return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
      . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
      . implode("\n", $entries)
      . "\n</urlset>\n";
  }

  private static function renderBlockAllRobotsTxt(): string
  {
    $host = Environment::appDomain() !== '' ? Environment::appDomain() : 'non-production';

    return implode("\n", [
      '# Non-production hosts (' . $host . '): block all crawlers.',
      '# Complements auth gates and X-Robots-Tag noindex on HTML responses.',
      'User-agent: *',
      'Disallow: /',
      '',
    ]);
  }

  private static function renderProductionRobotsTxt(): string
  {
    $lines = [
      '# Production crawl policy for paycal.app.',
      '# Public marketing paths are listed in sitemap.xml; private app surfaces are disallowed below.',
      '# Disallow is not security — pair with X-Robots-Tag noindex on authenticated HTML.',
      '',
      '# --- General crawlers: allow public docs, block authenticated app areas ---',
      'User-agent: *',
    ];

    foreach (self::PRIVATE_DISALLOW_PATHS as $path) {
      $lines[] = 'Disallow: ' . $path;
    }

    foreach (self::QUERY_TRAP_DISALLOW_PATTERNS as $pattern) {
      $lines[] = 'Disallow: ' . $pattern;
    }

    $lines[] = '';
    $lines[] = 'Sitemap: ' . Environment::publicMetadataURL('sitemap.xml');
    $lines[] = '';

    $lines[] = '# --- AI training crawlers (aligns with page-level noai / noimageai) ---';
    foreach (self::AI_TRAINING_USER_AGENTS as $agent) {
      $lines[] = 'User-agent: ' . $agent;
      $lines[] = 'Disallow: /';
      $lines[] = '';
    }

    return implode("\n", $lines);
  }
}
