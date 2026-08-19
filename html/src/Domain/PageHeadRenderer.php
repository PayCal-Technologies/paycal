<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

/**
 * Builds focused head fragments for PayCal pages.
 */
final class PageHeadRenderer
{
  private const ROBOTS_DIRECTIVE = 'index, follow, noai, noimageai, noodp, noydir, maximage-preview: large';

  /** sha256 of inline JSON in renderSpeculationRules() — stable while rules JSON is unchanged. */
  public const SPECULATION_RULES_INLINE_HASH = "'sha256-NIDsJMLCZm+WEjdq/r2s8xoviAH1K9nw5rmW2ievj5A='";

  /** @var array<string, string> */
  private const PAGE_FILE_MAP = [
    'PAGE_ABOUT' => 'content',
    'PAGE_CONTACT' => 'contact',
    'PAGE_EARNINGS' => 'earnings',
    'PAGE_REPORTS' => 'earnings',
    'PAGE_FAQ' => 'content',
    'PAGE_HELP' => 'help',
    'PAGE_INDEX' => 'calendar',
    'PAGE_CONNECTIONS' => 'business',
    'PAGE_POLICIES' => 'content',
    'PAGE_ADMIN' => 'admin',
    'PAGE_SETTINGS' => 'settings',
    'PAGE_SETTINGS_ACCOUNT' => 'settings',
    'PAGE_SETTINGS_SUBSCRIPTION' => 'settings',
    'PAGE_SETTINGS_CALENDAR' => 'settings',
    'PAGE_SETTINGS_APPEARANCE' => 'settings',
    'PAGE_SETTINGS_ACCESSIBILITY' => 'settings',
    'PAGE_SETTINGS_SECURITY' => 'settings',
    'PAGE_SETTINGS_DATA' => 'settings',
    'PAGE_SETTINGS_DIAGNOSTICS' => 'settings',
    'PAGE_SITES' => 'sites',
    'PAGE_BUSINESSES' => 'business',
    'PAGE_BUSINESS_DASHBOARD' => 'business',
    'PAGE_BUSINESS_DETAILS' => 'business',
    'PAGE_BUSINESS_MEMBERS' => 'business',
    'PAGE_BUSINESS_GROUPS' => 'business',
    'PAGE_BUSINESS_SITES' => 'business',
    'PAGE_BUSINESS_PAYROLL' => 'business',
    'PAGE_BUSINESS_AUDIT' => 'business',
    'PAGE_BUSINESS_REPORTS' => 'business',
    'PAGE_FORECAST' => 'forecast',
    'PAGE_PROFILE' => 'profile',
    'PAGE_TESTS' => 'admin',
    'PAGE_TRANSPARENCY' => 'transparency',
    'PAGE_STATUS' => 'transparency',
    'PAGE_PRICING' => 'pricing',
    'PAGE_PAYPERIODS' => 'payperiods',
    'PAGE_AUTH' => 'auth',
    'PAGE_SIGNIN' => 'auth',
    'PAGE_REGISTER' => 'auth',
  ];

  /**
   * Page file for.
   */
  public static function pageFileFor(string $currentPage): string
  {
    return self::PAGE_FILE_MAP[$currentPage] ?? 'content';
  }

  /**
   * @param array{
   *   pageLanguage: string,
   *   metaDescription: string,
   *   metaDescriptionLong: string,
   *   pageTitle: string,
   * } $context
   */
  public static function renderIdentityMeta(array $context): string
  {
    $metaDescription = htmlspecialchars((string) $context['metaDescription'], ENT_QUOTES, 'UTF-8');
    $baseHref = self::attr(Environment::appURL('/'));
    $csrfToken = self::attr(User::nonce());
    $today = self::today();
    $year = self::year();

    return <<<HTML
  <base href="{$baseHref}">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="description" content="{$metaDescription}">
  <meta name="rating" content="general">
  <meta name="copyright" content="Copyright (C) {$year} PayCal Technologies Inc. All rights reserved.">
  <meta name="authors" content="PayCal Technologies Inc.">
  <meta name="publisher" content="PayCal Technologies Inc.">
  <meta name="date" content="{$today}">
  <meta name="referrer" content="strict-origin-when-cross-origin">
  <meta name="csrf-param" content="authenticity_token">
  <meta name="csrf-token" content="{$csrfToken}">
  <meta name="theme-color" content="#060606">
  <meta name="application-name" content="PayCal">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="PayCal">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black">
  <meta name="msapplication-TileColor" content="#060606">
  <meta name="msapplication-TileImage" content="/mstile-150x150.png">
  <meta http-equiv="cleartype" content="on">
  <meta http-equiv="Accept-CH" content="Width, Viewport-Width, Downlink, Sec-CH-UA, Sec-CH-UA-Platform">

HTML;
  }

  /**
   * @param array{
   *   pageLanguage: string,
   *   canonicalPath: string,
   * } $context
   */
  public static function renderRelations(array $context): string
  {
    $canonicalUrl = htmlspecialchars(Environment::publicMetadataURL((string) $context['canonicalPath']), ENT_QUOTES, 'UTF-8');
    $publicBase = htmlspecialchars(Environment::publicMetadataBaseURL(), ENT_QUOTES, 'UTF-8');
    $alternateLang = '';

    if (User::current()->language !== 'en') {
      $lang = htmlspecialchars((string) User::current()->language, ENT_QUOTES, 'UTF-8');
      $langHref = htmlspecialchars(Environment::appURL(User::current()->language), ENT_QUOTES, 'UTF-8');
      $alternateLang = "  <link rel=\"alternate\" hreflang=\"{$lang}\" href=\"{$langHref}\">\n";
    }

    return <<<HTML
  <link rel="profile" href="http://gmpg.org/xfn/11">
  <link rel="alternate" hreflang="en" href="{$publicBase}/">
{$alternateLang}  <link rel="alternate" hreflang="x-default" href="{$publicBase}/">
  <link rel="canonical" href="{$canonicalUrl}">
  <link rel="manifest" href="/manifest.json">
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
  <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
  <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">

HTML;
  }

  public static function renderRobotsMeta(): string
  {
    $robots = htmlspecialchars(self::ROBOTS_DIRECTIVE, ENT_QUOTES, 'UTF-8');

    return <<<HTML
  <meta name="robots" content="{$robots}">

HTML;
  }

  /**
   * @param array{
   *   metaDescription: string,
   *   canonicalPath: string,
   *   socialTitle?: string,
   *   ogDescription?: string,
   *   twitterTitle?: string,
   *   twitterDescription?: string,
   * } $context
   */
  public static function renderSocialMeta(array $context): string
  {
    $metaTitleRaw = (string) ($context['socialTitle'] ?? Strings::headerI18n('META_TITLE'));
    $ogDescriptionRaw = (string) ($context['ogDescription'] ?? $context['metaDescription']);
    $twitterTitleRaw = (string) ($context['twitterTitle'] ?? $metaTitleRaw);
    $twitterDescriptionRaw = (string) ($context['twitterDescription'] ?? $ogDescriptionRaw);
    $metaTitle = htmlspecialchars($metaTitleRaw, ENT_QUOTES, 'UTF-8');
    $ogDescription = htmlspecialchars($ogDescriptionRaw, ENT_QUOTES, 'UTF-8');
    $twitterTitle = htmlspecialchars($twitterTitleRaw, ENT_QUOTES, 'UTF-8');
    $twitterDescription = htmlspecialchars($twitterDescriptionRaw, ENT_QUOTES, 'UTF-8');
    $canonicalUrl = htmlspecialchars(Environment::publicMetadataURL((string) $context['canonicalPath']), ENT_QUOTES, 'UTF-8');
    $publicBase = htmlspecialchars(Environment::publicMetadataBaseURL(), ENT_QUOTES, 'UTF-8');
    $socialImage = htmlspecialchars(Environment::publicMetadataURL('favicon.ico'), ENT_QUOTES, 'UTF-8');

    return <<<HTML
  <meta property="og:locale" content="en_CA">
  <meta property="og:site_name" content="PayCal">
  <meta property="og:title" content="{$metaTitle}">
  <meta property="og:description" content="{$ogDescription}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{$canonicalUrl}">
  <meta name="twitter:title" content="{$twitterTitle}">
  <meta name="twitter:description" content="{$twitterDescription}">
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:site" content="@paycal_app">
  <meta name="twitter:url" content="{$canonicalUrl}">
  <meta name="twitter:domain" content="paycal.app">
  <meta name="twitter:image" content="{$socialImage}">
  <link rel="me" href="https://mastodon.social/@paycal">

HTML;
  }

  /**
   * @param array{
   *   pageLanguage: string,
   *   metaDescriptionLong: string,
   *   dcTitle?: string,
   *   dcDescription?: string,
   * } $context
   */
  public static function renderDublinCoreMeta(array $context): string
  {
    $pageLanguage = htmlspecialchars((string) $context['pageLanguage'], ENT_QUOTES, 'UTF-8');
    $metaTitle = htmlspecialchars((string) ($context['dcTitle'] ?? Strings::headerI18n('META_TITLE')), ENT_QUOTES, 'UTF-8');
    $metaDescriptionLong = htmlspecialchars((string) ($context['dcDescription'] ?? $context['metaDescriptionLong']), ENT_QUOTES, 'UTF-8');
    $englishTag = htmlspecialchars(Strings::headerI18n('ENGLISH'), ENT_QUOTES, 'UTF-8');
    $today = self::today();

    return <<<HTML
  <link rel="schema.DC" href="https://purl.org/dc/elements/1.1/">
  <link rel="schema.DCTERMS" href="https://purl.org/dc/terms/">
  <meta name="dc.title" lang="en" content="{$metaTitle}">
  <meta name="dc.description" content="{$metaDescriptionLong}">
  <meta name="dc.rights" content="URI:/policies/">
  <meta name="dc.creator" content="PayCal Technologies Inc.">
  <meta name="dc.publisher" content="PayCal Technologies Inc.">
  <meta name="dc.date" content="{$today}">
  <meta name="dc.language" content="{$pageLanguage}">
  <meta name="dc.language" scheme="DCTERMS.URI" content="{$englishTag}">

HTML;
  }

  /**
   * @param array{loadWebVitalsDiagnostics?: bool} $context
   */
  public static function renderResourceHints(array $context = []): string
  {
    $origin = htmlspecialchars(Environment::appBaseURL(), ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
  <link rel="preconnect" href="{$origin}">

HTML;

    $fontUrl = htmlspecialchars(Environment::appURL('fonts/open-dyslexic-400.woff2'), ENT_QUOTES, 'UTF-8');
    $html .= "  <link rel=\"preload\" href=\"{$fontUrl}\" as=\"font\" type=\"font/woff2\" crossorigin>\n";

    return $html;
  }

  private static function cssVersion(string $baseVersion, string $cssEndpoint): string
  {
    $path = dirname(__DIR__, 2) . '/css/' . trim($cssEndpoint, '/') . '/index.php';
    $mtime = is_file($path) ? (string) filemtime($path) : '';

    return $mtime !== '' ? $baseVersion . '-' . $mtime : $baseVersion;
  }

  /**
   * @param array{
   *   cspNonce: string,
   *   cssVersion: string,
   *   currentPage: string,
   *   pageFile: string,
   *   isAuthenticated: bool,
   *   loadPhantomWing: bool,
   *   isDocPdfView: bool,
   * } $context
   */
  public static function renderStylesheets(array $context): string
  {
    $cspNonce = htmlspecialchars((string) $context['cspNonce'], ENT_QUOTES, 'UTF-8');
    $baseCssVersion = (string) $context['cssVersion'];
    $cssVersion = htmlspecialchars(self::cssVersion($baseCssVersion, ''), ENT_QUOTES, 'UTF-8');
    $navigationCssVersion = htmlspecialchars(self::cssVersion($baseCssVersion, 'navigation'), ENT_QUOTES, 'UTF-8');
    $utilitiesCssVersion = htmlspecialchars(self::cssVersion($baseCssVersion, 'utilities'), ENT_QUOTES, 'UTF-8');
    $datagridCssVersion = htmlspecialchars(self::cssVersion($baseCssVersion, 'datagrid'), ENT_QUOTES, 'UTF-8');
    $pageFileRaw = (string) $context['pageFile'];
    $pageFile = htmlspecialchars($pageFileRaw, ENT_QUOTES, 'UTF-8');
    $pageCssVersion = htmlspecialchars(self::cssVersion($baseCssVersion, $pageFileRaw), ENT_QUOTES, 'UTF-8');
    $responsiveCssVersion = htmlspecialchars(self::cssVersion($baseCssVersion, 'responsive'), ENT_QUOTES, 'UTF-8');
    $cssBase = htmlspecialchars(Environment::appURL('css/'), ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
  <link rel="stylesheet" fetchpriority="high" href="{$cssBase}?v={$cssVersion}" nonce="{$cspNonce}">
  <link rel="stylesheet" href="{$cssBase}navigation/?v={$navigationCssVersion}" nonce="{$cspNonce}">
  <link rel="stylesheet" href="{$cssBase}utilities/?v={$utilitiesCssVersion}" nonce="{$cspNonce}">
  <link rel="stylesheet" href="{$cssBase}datagrid/?v={$datagridCssVersion}" nonce="{$cspNonce}">
  <link rel="stylesheet" href="{$cssBase}{$pageFile}/?v={$pageCssVersion}" nonce="{$cspNonce}">

HTML;

    if ($context['loadPhantomWing']) {
      $phantomWingCssVersion = htmlspecialchars(self::cssVersion($baseCssVersion, 'phantomwing'), ENT_QUOTES, 'UTF-8');
      $html .= "  <link rel=\"stylesheet\" href=\"{$cssBase}phantomwing/?v={$phantomWingCssVersion}\" nonce=\"{$cspNonce}\">\n";
    }

    $html .= <<<HTML
  <link rel="stylesheet" href="{$cssBase}responsive/?v={$responsiveCssVersion}" nonce="{$cspNonce}">

HTML;

    if (in_array((string) $context['currentPage'], ['PAGE_HELP', 'PAGE_TRANSPARENCY', 'PAGE_STATUS', 'PAGE_ABOUT', 'PAGE_POLICIES', 'PAGE_BLOG', 'PAGE_MEDIA'], true)) {
      $contentViewsCssVersion = htmlspecialchars(self::cssVersion($baseCssVersion, 'content-views'), ENT_QUOTES, 'UTF-8');
      $html .= "  <link rel=\"stylesheet\" href=\"{$cssBase}content-views/?v={$contentViewsCssVersion}\" nonce=\"{$cspNonce}\">\n";
    }

    return $html;
  }

  /**
   * @param array{
   *   cspNonce: string,
   *   jsonLdDocument: string,
   *   isAuthenticated: bool,
   *   loadPhantomWing: bool,
   *   loadWebVitalsDiagnostics: bool,
   *   isDocPdfView: bool,
   * } $context
   */
  public static function renderScripts(array $context): string
  {
    $cspNonce = htmlspecialchars((string) $context['cspNonce'], ENT_QUOTES, 'UTF-8');
    $jsonLd = (string) $context['jsonLdDocument'];
    $cacheVersion = rawurlencode(Environment::appVersion());
    $guardian = htmlspecialchars(Environment::appURL('js/guardian.js') . '?v=' . $cacheVersion, ENT_QUOTES, 'UTF-8');
    $jsBase = htmlspecialchars(Environment::appURL('js/') . '?v=' . $cacheVersion, ENT_QUOTES, 'UTF-8');

    $html = Render::template('header-application-json-linked-data', [
      '__CSP_NONCE__' => (string) $context['cspNonce'],
      '__JSON_LD__' => $jsonLd,
    ]);

    $html .= self::renderSpeculationRules((string) $context['cspNonce']);

    $html .= <<<HTML
  <script src="{$guardian}" nonce="{$cspNonce}"></script>

HTML;

    if ($context['isDocPdfView']) {
      $printTrigger = htmlspecialchars(Environment::appURL('js/print-trigger.js'), ENT_QUOTES, 'UTF-8');
      $html .= "  <script src=\"{$printTrigger}\" nonce=\"{$cspNonce}\"></script>\n";
    }

    if ($context['isAuthenticated']) {
      $html .= "  <script type=\"module\" src=\"{$jsBase}\" nonce=\"{$cspNonce}\"></script>\n";
      if ($context['loadPhantomWing']) {
        $phantomWing = htmlspecialchars(Environment::appURL('js/phantomwing/') . '?v=' . $cacheVersion, ENT_QUOTES, 'UTF-8');
        $html .= "  <script type=\"module\" src=\"{$phantomWing}\" nonce=\"{$cspNonce}\"></script>\n";
      }
      $encryption = htmlspecialchars(Environment::appURL('js/encryption/') . '?v=' . $cacheVersion, ENT_QUOTES, 'UTF-8');
      $workIntegrity = htmlspecialchars(Environment::appURL('js/work-integrity/') . '?v=' . $cacheVersion, ENT_QUOTES, 'UTF-8');
      $html .= "  <script type=\"module\" src=\"{$encryption}\" nonce=\"{$cspNonce}\"></script>\n";
      $html .= "  <script type=\"module\" src=\"{$workIntegrity}\" nonce=\"{$cspNonce}\"></script>\n";
      if ($context['loadWebVitalsDiagnostics']) {
        $webVitalsDiagnostics = htmlspecialchars(
          Environment::appURL('js/dev/web-vitals-diagnostics/') . '?v=' . $cacheVersion,
          ENT_QUOTES,
          'UTF-8'
        );
        $html .= "  <script type=\"module\" src=\"{$webVitalsDiagnostics}\" nonce=\"{$cspNonce}\"></script>\n";
      }
    }

    return $html;
  }

  public static function renderSpeculationRules(string $cspNonce = ''): string
  {
    $json = self::speculationRulesJson();
    if ($json === '') {
      return '';
    }

    $nonceAttr = $cspNonce !== ''
      ? ' nonce="' . self::attr($cspNonce) . '"'
      : '';

    return <<<HTML
  <script type="speculationrules"{$nonceAttr}>
{$json}
  </script>

HTML;
  }

  /**
   * Inline script body hashed for script-src (excludes the script element wrapper).
   */
  public static function speculationRulesInlineBody(): string
  {
    $json = self::speculationRulesJson();
    return $json === '' ? '' : "\n{$json}\n  ";
  }

  private static function speculationRulesJson(): string
  {
    $rules = [
      'prefetch' => [[
        'source' => 'document',
        'where' => [
          'and' => [
            ['href_matches' => '/*'],
            ['not' => ['href_matches' => '/api/*']],
            ['not' => ['href_matches' => '/ws/*']],
            ['not' => ['href_matches' => '/cli/*']],
            ['not' => ['href_matches' => '/internal/*']],
            ['not' => ['href_matches' => '/admin/*']],
            ['not' => ['href_matches' => '/signout*']],
            ['not' => ['href_matches' => '/signout-esc*']],
            ['not' => ['href_matches' => '/auth/recover*']],
            ['not' => ['href_matches' => '/verify*']],
            ['not' => ['selector_matches' => '[download], [target], [rel~=nofollow], [data-no-speculation]']],
          ],
        ],
        'eagerness' => 'eager',
        'tag' => 'paycal-document-prefetch',
      ]],
    ];
    $json = json_encode($rules, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    return is_string($json) ? $json : '';
  }

  private static function attr(string $value): string
  {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }

  private static function today(): string
  {
    return date('Y-m-d');
  }

  private static function year(): string
  {
    return date('Y');
  }
}
