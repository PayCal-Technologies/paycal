<?php declare(strict_types=1);

namespace PayCal\Domain;

/*
 * HTML Header
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Functions
 * @package    PayCal
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 *
 */

// PREVENT DIRECT LOADING

if (!isset($currentPage))
  $currentPage = '';

if (!isset($pageLanguage) || $pageLanguage === '') {
  $pageLanguage = defined('USER_LANGUAGE') ? (string) USER_LANGUAGE : 'en-CA';
}

// METRICS
$startTime = -microtime(true);
$startMemory = memory_get_usage();

/** AUTHENTICATION GUARD - Redirect unauthenticated users */
$hash = Authentication::getCookie();
$isAuthenticated = $hash !== '';

/** Public pages that don't require authentication */
$publicPages = ['PAGE_SIGNIN', 'PAGE_REGISTER', 'PAGE_CONTACT', 'PAGE_AUTH', 'PAGE_ABOUT', 'PAGE_HELP', 'PAGE_TRANSPARENCY', 'PAGE_POLICIES', 'PAGE_BLOG', 'PAGE_MEDIA'];

if (!$isAuthenticated && !in_array($currentPage, $publicPages, true)) {
  Security::sendCoreSecurityHeaders();
  header("Content-Security-Policy: default-src 'self' https: data: blob:; object-src 'none'; frame-ancestors 'none'; base-uri 'self'");
  header('Location: '.Authentication::unauthenticatedRedirectURL());
  exit;
}

/** All code below assumes authenticated user OR public page */
$siteName = Strings::headerI18n('SITE_NAME');
if (!isset($pageTitle) || $pageTitle === '') {
  $pageTitle = match ($currentPage) {
    'PAGE_ABOUT' => Strings::headerI18n('ABOUT_US').' - ['.$siteName.']',
    'PAGE_BLOG' => Strings::headerI18n('BLOG').' - ['.$siteName.']',
    'PAGE_CONTACT' => Strings::headerI18n('CONTACT').' - ['.$siteName.']',
    'PAGE_EARNINGS' => Strings::headerI18n('EARNINGS').' - ['.$siteName.']',
    'PAGE_FAQ' => Strings::headerI18n('FAQ_TITLE').' - ['.$siteName.']',
    'PAGE_HELP' => Strings::headerI18n('HELP').' - ['.$siteName.']',
    'PAGE_MEDIA' => 'Media - ['.$siteName.']',
    'PAGE_INDEX' => Strings::headerI18n('CALENDAR').' - ['.$siteName.']',
    'PAGE_POLICIES' => Strings::headerI18n('POLICIES').' - ['.$siteName.']',
    'PAGE_SETTINGS' => Strings::headerI18n('SETTINGS').' - ['.$siteName.']',
    'PAGE_SIGNIN' => Strings::headerI18n('AUTH_TAB_SIGNIN').' - ['.Strings::headerI18n('SITE_NAME').']',
    'PAGE_REGISTER' => Strings::headerI18n('REGISTER').' - ['.Strings::headerI18n('SITE_NAME').']',
    'PAGE_AUTH' => Strings::headerI18n('AUTH_TAB_SIGNIN').' - ['.Strings::headerI18n('SITE_NAME').']',
    'PAGE_SITES' => Strings::headerI18n('SITES').' - ['.$siteName.']',
    'PAGE_ORGANIZATIONS' => Strings::headerI18n('ORGANIZATIONS').' - ['.$siteName.']',
    'PAGE_PROFILE' => Strings::headerI18n('PROFILE').' - ['.$siteName.']',
    'PAGE_ADMIN' => Strings::headerI18n('ADMIN').' - ['.$siteName.']',
    'PAGE_TESTS' => Strings::headerI18n('TESTS').' - ['.$siteName.']',
    'PAGE_TRANSPARENCY' => Strings::headerI18n('TRANSPARENCY').' - ['.$siteName.']',
    default => $siteName,
  };
}

$metaDescription = Strings::headerI18n('META_DESCRIPTION');
$metaDescriptionLong = Strings::headerI18n('META_DESCRIPTION_LONG');
$requestUriRaw = $_SERVER['REQUEST_URI'] ?? '/';
$requestUriForStructuredData = is_scalar($requestUriRaw) ? (string) $requestUriRaw : '/';
$requestPathRaw = parse_url($requestUriForStructuredData, PHP_URL_PATH);
$requestPathForStructuredData = is_string($requestPathRaw) ? $requestPathRaw : '/';
if ($requestPathForStructuredData === '') {
  $requestPathForStructuredData = '/';
}

$pageStructuredDataUrl = Environment::appURL(
  $requestPathForStructuredData === '/' ? '/' : ltrim($requestPathForStructuredData, '/')
);
$pageLanguageTag = str_replace('_', '-', $pageLanguage);
$structuredDataLanguageTag = match ($pageLanguageTag) {
  'en' => 'en-CA',
  'fr' => 'fr-CA',
  default => $pageLanguageTag,
};
$websiteStructuredDataId = Environment::appURL('/') . '#website';
$organizationStructuredDataId = Environment::appURL('/') . '#organization';
$softwareStructuredDataId = Environment::appURL('/') . '#software';
$webPageStructuredDataId = $pageStructuredDataUrl . '#webpage';
$isAuthStructuredDataPage = in_array($currentPage, ['PAGE_AUTH', 'PAGE_SIGNIN', 'PAGE_REGISTER'], true);
$authStructuredDataDescription = match ($currentPage) {
  'PAGE_REGISTER' => 'Create a PayCal account to start tracking work hours and payroll-ready records.',
  default => 'Sign in to PayCal to access your payroll records and account tools.',
};

$websiteStructuredData = [
  '@type' => 'WebSite',
  '@id' => $websiteStructuredDataId,
  'url' => Environment::appURL('/'),
  'name' => $siteName,
  'description' => $metaDescription,
  'inLanguage' => $structuredDataLanguageTag,
  'publisher' => ['@id' => $organizationStructuredDataId],
];

$organizationStructuredData = [
  '@type' => 'Organization',
  '@id' => $organizationStructuredDataId,
  'name' => 'PayCal Technologies Inc.',
  'url' => Environment::appURL('/'),
  'logo' => [
    '@type' => 'ImageObject',
    'url' => Environment::appURL('apple-touch-icon.png'),
  ],
  'email' => 'info@paycal.app',
  'foundingDate' => '2023-09-01',
  'contactPoint' => [[
    '@type' => 'ContactPoint',
    'contactType' => 'customer support',
    'email' => 'info@paycal.app',
    'url' => Environment::appURL('contact/'),
  ]],
  'sameAs' => [
    'https://github.com/PayCal-Technologies/paycal',
    'https://mastodon.social/@paycal',
  ],
];

$webPageStructuredData = [
  '@type' => 'WebPage',
  '@id' => $webPageStructuredDataId,
  'url' => $pageStructuredDataUrl,
  'name' => $pageTitle,
  'description' => $isAuthStructuredDataPage ? $authStructuredDataDescription : $metaDescription,
  'isPartOf' => ['@id' => $websiteStructuredDataId],
  'about' => ['@id' => $organizationStructuredDataId],
  'inLanguage' => $structuredDataLanguageTag,
];

$structuredDataGraph = [
  $websiteStructuredData,
  $organizationStructuredData,
  $webPageStructuredData,
];

if (!$isAuthStructuredDataPage) {
  $structuredDataGraph[] = [
    '@type' => 'SoftwareApplication',
    '@id' => $softwareStructuredDataId,
    'name' => $siteName,
    'url' => Environment::appURL('/'),
    'applicationCategory' => 'BusinessApplication',
    'operatingSystem' => 'Web',
    'browserRequirements' => 'Requires JavaScript and a modern web browser.',
    'description' => $metaDescriptionLong,
    'inLanguage' => $structuredDataLanguageTag,
    'publisher' => ['@id' => $organizationStructuredDataId],
    'offers' => [
      '@type' => 'Offer',
      'price' => '0.00',
      'priceCurrency' => 'USD',
    ],
  ];

  $structuredDataGraph[2]['mainEntity'] = ['@id' => $softwareStructuredDataId];
}

$jsonLdDocument = json_encode(
  ['@context' => 'https://schema.org', '@graph' => $structuredDataGraph],
  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE
);

if ($jsonLdDocument === false) {
  $jsonLdDocument = '{}';
}

/*
 * Force live updates: disable browser/proxy caching for page responses.
 */
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
Security::sendCoreSecurityHeaders();

// CORS
$allowedOrigins = ['https://paycal.app', 'https://www.paycal.app'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($origin, $allowedOrigins, true)) {
  header("Access-Control-Allow-Origin: {$origin}");
  header('Vary: Origin');
  header('Access-Control-Allow-Credentials: true');
}

/*
 * CONTENT SECURITY POLICY (CSP)
 * Single policy, all environments
 */

$domainRaw = $_ENV['APP_DOMAIN'] ?? '';
$domain = is_scalar($domainRaw) ? (string) $domainRaw : '';
$originScheme = parse_url((string) \PayCal\Domain\Environment::appPublicURL(), PHP_URL_SCHEME) ?: 'https';
$origin = $originScheme.'://'.$domain;
$cspNonce = User::nonce();
$_SERVER['CSP_NONCE'] = $cspNonce;
$cspReportUrl = Environment::appURL('api/' . Environment::apiVersion() . '/security/csp/report');

$csp = [
  'default-src' => ["'none'"],
  'base-uri' => ["'self'", $origin],
  'connect-src' => ["'self'", $origin, '*.google-analytics.com', '*.analytics.google.com', '*.googletagmanager.com'],
  'frame-src' => ["'self'", 'https://www.youtube.com', 'https://www.youtube-nocookie.com'],
  'font-src' => ["'self'", $origin],
  'form-action' => ["'self'", $origin],
  'img-src' => ["'self'", 'data:', 'www.googletagmanager.com'],
  'media-src' => ["'self'", $origin],
  'manifest-src' => ["'self'", $origin],
  'object-src' => ["'none'"],
  'script-src' => array_merge([
    "'nonce-{$cspNonce}'",
    "'strict-dynamic'",
    "'self'",
    $origin,
    'www.googletagmanager.com',
  ], Environment::devAllowInlineScripts() ? ["'unsafe-inline'"] : []),
  'style-src' => ["'self'", $origin],
  'style-src-elem' => ["'self'", $origin],
  'frame-ancestors' => ["'none'"],
  'report-uri' => [$cspReportUrl],
  'report-to' => ['csp-endpoint'],
];

$policy = '';

foreach ($csp as $directive => $values) {
  $policy .= $directive.' '.implode(' ', $values).'; ';
}

// CSP disabled when DEV_SECURITY_DISABLED=true in .env
$trustedTypesPolicy = trim($policy . " require-trusted-types-for 'script'; trusted-types default paycal;");
if (!\PayCal\Domain\Environment::devSecurityDisabled())
  header('Content-Security-Policy: '.$trustedTypesPolicy);

header('Report-To: {"group":"csp-endpoint","max_age":10886400,"endpoints":[{"url":"' . $cspReportUrl . '"}]}');

header('Accept-CH: Sec-CH-UA-Platform');

$platformToken = PlatformToken::detect();

// SEO-RELATED HEADER
header('X-Robots-Tag: index, follow, noai, noimageai, noodp, noydir, maximage-preview: large');

/*
 * PHPSTAN ANALYSIS REMEDIATION
 * These might be removed or set to ignore later.
 * @var non-falsy-string $currentPage
 * @var non-falsy-string $pageLanguage
 * @var non-falsy-string $pageTitle
 */

?><!DOCTYPE html><!-- Hello there. -->
<html lang="<?php echo htmlspecialchars((string) $pageLanguage, ENT_QUOTES, 'UTF-8'); ?>" dir="ltr" prefix="og: http://ogp.me/ns#" data-os="<?php echo htmlspecialchars($platformToken, ENT_QUOTES, 'UTF-8'); ?>" data-a11y-animated-images="system" data-a11y-link-underlines="true" data-a11y-dyslexia-typography="<?php echo htmlspecialchars((string) (User::current()->dyslexia_typography ?? UserPreferenceDefaults::DEFAULT_DYSLEXIA_TYPOGRAPHY), ENT_QUOTES, 'UTF-8'); ?>">

<!--
  _______   ______    ___________          _______   __  ___  __   __       __       ______
 /  _____| /  __  \  |           |        /       | |  |/  / |  | |  |     |  |     |      \
|  |  __  |  |  |  | \___|  |___/        |  /____/  |     /  |  | |  |     |  |     \___   |
|  | |_ | |  |  |  |     |  |             \   \     |    <   |  | |  |     |  |         /  /
|  |__| | |  |__|  |     |  |          ___/    |    |     \  |  | |  \____ |  \____    |__|
 \______|  \______/      |__|         |_______/     |__|\__\ |__| |_______||_______|    __
                                                                                       |__|
<?php echo Strings::headerI18n('WE_ARE_HIRING'); ?>

-->

<head>

  <!-- Identity -->
  <base href="<?php echo Environment::appURL('/'); ?>">
  <meta charset="UTF-8">
  <meta name="contentType"                                 content="text/html; charset=utf-8">
  <meta name="viewport"                                    content="width=device-width, initial-scale=1">
  <meta name="description"                                 content="<?php echo Strings::headerI18n('META_DESCRIPTION'); ?>">
  <meta name="keywords"                                    content="<?php echo Strings::headerI18n('META_KEYWORDS'); ?>">
  <meta name="page-subject"                                content="<?php echo Strings::headerI18n('META_KEYWORDS'); ?>">
  <meta name="subjects"                                    content="<?php echo Strings::headerI18n('META_KEYWORDS'); ?>">
  <meta name="topics"                                      content="<?php echo Strings::headerI18n('META_KEYWORDS'); ?>">
  <meta name="taxonomyTerms"                               content="<?php echo Strings::headerI18n('META_KEYWORDS'); ?>">
  <meta name="rating"                                      content="general">
  <meta name="copyright"                                   content="Copyright (C) <?php echo date('Y'); ?> PayCal Technologies Inc. All rights reserved.">
  <meta name="generator"                                   content="Our heart, soul and love">
  <meta name="authors"                                     content="PayCal Technologies Inc.">
  <meta name="publisher"                                   content="PayCal Technologies Inc.">
  <meta name="date"                                        content="<?php echo date('Y-m-d'); ?>">
  <meta name="unix_date"                                   content="<?php echo time(); ?>">
  <meta name="expected-hostname"                           content="paycal.app">
  <meta name="ISOCODE"                                     content="CAN">
  <meta name="applicable-device"                           content="pc, mobile">
  <meta name="MobileOptimized"                             content="320">
  <meta name="HandheldFriendly"                            content="true">
  <meta name="distribution"                                content="Global">
  <meta name="coverage"                                    content="Worldwide">
  <meta name="referrer"                                      content="strict-origin-when-cross-origin">
  <meta name="csrf-param"                                  content="authenticity_token">
  <meta name="csrf-token"                                  content="<?php echo User::nonce(); ?>">

  <!-- Relations -->
  <link rel="profile"                                      href="http://gmpg.org/xfn/11">
  <link rel="alternate" hreflang="en"                      href="<?php echo Environment::appBaseURL(); ?>">
<?php if (User::current()->language !== 'en') {
  $user = User::current();
  $lang = $user->language;

  echo '  <link rel="alternate" hreflang="' . $lang .'"                      href="'.Environment::appURL(User::current()->language).'">'.PHP_EOL;
} ?>
  <link rel="alternate" hreflang="x-default"               href="<?php echo Environment::appBaseURL(); ?>">
  <link rel="canonical"                                    href="<?php echo Environment::appBaseURL(); ?>">
  <link rel="manifest" href="/manifest.json">

  <!-- Robots, whrrrr -->
  <meta name="robots"                                      content="index, follow, noai, noimageai, noodp, noydir, maximage-preview: large">
  <meta name="googlebot"                                   content="index, follow, noai, noimageai, noodp, noydir, maximage-preview: large">
  <meta name="bingbot"                                     content="index, follow, noai, noimageai, noodp, noydir, maximage-preview: large">

  <!-- Googley Oogley https://developer.chrome.com/en/articles/user-agent-client-hints/ -->
  <meta http-equiv="Accept-CH"                             content="Width, Viewport-Width, Downlink, Sec-CH-UA, Sec-CH-UA-Platform">

  <!-- OS -->
  <meta name="mobile-web-app-capable"                      content="yes">
  <meta name="apple-mobile-web-app-title"                  content="PayCal">
  <meta name="apple-mobile-web-app-capable"                content="yes">
  <meta name="apple-mobile-web-app-status-bar-style"       content="black">
  <meta name="theme-color"                                 content="#060606">
  <meta name="application-name"                            content="<?php echo Environment::appBaseURL(); ?>">
  <meta name="application-name"                            content="<?php echo Environment::appBaseURL(); ?>">
  <meta name="msapplication-config"                        content="#none">
  <meta name="msapplication-TileColor"                     content="#060606">
  <meta name="msapplication-TileImage"                     content="/mstile-150x150.png">
  <meta name="msapplication-tap-highlight"                 content="no">
  <meta name="norton-safeweb-site-verification"            content="D8S4VWZR6KMGWAOEJW87M-6R7CTNNH1AYHZ566CJIIEO39EBTVNU4L3SLGY-5AAYWP7YNIJEYL985B3YFVEQCT-N6V24LQVVJWTQ7ASIUOLDX9N7JDTZ2ELVCIV3PPUR">
  <!--[if IE]> <meta http-equiv="X-UA-Compatible"          content="IE=Edge,chrome=1"><![endif]-->
  <meta http-equiv="cleartype"                             content="on">

  <link rel="icon" type="image/x-icon"                     href="/favicon.ico">
  <link rel="icon" type="image/png"                        href="/favicon-16x16.png" sizes="16x16">
  <link rel="icon" type="image/png"                        href="/favicon-32x32.png" sizes="32x32">
  <link rel="apple-touch-icon"                             href="/apple-touch-icon.png" sizes="180x180">

  <!-- Social -->
  <meta property="og:locale"                               content="en_CA">
  <meta property="inLanguage"                              content="en-CA">
  <meta property="og:site_name"                            content="PayCal">
  <meta property="og:title"                                content="<?php echo Strings::headerI18n('META_TITLE'); ?>">
  <meta property="og:description"                          content="<?php echo Strings::headerI18n('META_DESCRIPTION'); ?>">
  <meta property="og:type"                                 content="website">
  <meta property="og:url"                                  content="<?php echo Environment::appBaseURL(); ?>">
  <meta property="al:web:url"                              content="<?php echo Environment::appBaseURL(); ?>">
  <meta property="ia:markup_url"                           content="<?php echo Environment::appBaseURL(); ?>">
  <meta name="twitter:title"                               content="<?php echo Strings::headerI18n('META_TITLE'); ?>">
  <meta name="twitter:description"                         content="<?php echo Strings::headerI18n('META_DESCRIPTION'); ?>">
  <meta name="twitter:card"                                content="summary_large_image">
  <meta name="twitter:site"                                content="@paycal_app">
  <meta name="twitter:url"                                 content="https://paycal.app/">
  <meta name="twitter:domain"                              content="paycal.app">
  <meta name="twitter:image"                               content="<?php echo Environment::appURL('favicon.ico'); ?>">
  <link rel="me"                                           href="https://mastodon.social/@paycal">

  <!-- Dublin Core -->
  <link rel="schema.DC"                                    href="https://purl.org/dc/elements/1.1/">
  <link rel="schema.DCTERMS"                               href="https://purl.org/dc/terms/">
  <meta name="dc.title" lang="en"                          content="<?php echo Strings::headerI18n('META_TITLE'); ?>">
  <meta name="dc.subject" lang="en"                        content="<?php echo Strings::headerI18n('META_KEYWORDS'); ?>">
  <meta name="dc.description"                              content="<?php echo Strings::headerI18n('META_DESCRIPTION_LONG'); ?>">
  <meta name="dc.rights"                                   content="URI:/policies/">
  <meta name="dc.creator"                                  content="PayCal Technologies Inc.">
  <meta name="dc.publisher"                                content="PayCal Technologies Inc.">
  <meta name="dc.date"                                     content="<?php echo date('Y-m-d'); ?>">
  <meta name="dc.language"                                 content="<?php echo $pageLanguage; ?>">
  <meta name="dc.coverage"                                 content="World">
  <meta name="dc.type"                                     content="Text">
  <meta name="dc.format"                                   content="text/html">
  <meta name="dc.language" scheme="DCTERMS.URI"            content="<?php echo Strings::headerI18n('ENGLISH'); ?>">

  <!-- Start your engines -->
  <title><?php echo $pageTitle; ?></title>

  <!-- Ready -->
  <?php
  $pageFileMap = [
    'PAGE_ABOUT' => 'content',
    'PAGE_CONTACT' => 'contact',
    'PAGE_EARNINGS' => 'earnings',
    'PAGE_FAQ' => 'content',
    'PAGE_HELP' => 'help',
    'PAGE_INDEX' => 'calendar',
    'PAGE_POLICIES' => 'content',
    'PAGE_ADMIN' => 'admin',
    'PAGE_SETTINGS' => 'settings',
    'PAGE_SITES' => 'sites',
    'PAGE_ORGANIZATIONS' => 'organizations',
    'PAGE_PROFILE' => 'profile',
    'PAGE_TESTS' => 'admin',
    'PAGE_TRANSPARENCY' => 'transparency',
    'PAGE_PAYPERIODS' => 'payperiods',
    'PAGE_AUTH' => 'auth',
  ];
  $pageFile = $pageFileMap[$currentPage] ?? 'content';
  $cssVersion = (string) time();
  $navCssVersion = $cssVersion;
  $cssNonce = User::nonce();
  ?>
  <link rel="stylesheet" fetchpriority="high" href="<?php echo Environment::appURL('css/'); ?>?v=<?php echo $cssVersion; ?>" nonce="<?php echo $cssNonce; ?>">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/navigation/'); ?>?v=<?php echo $navCssVersion; ?>" nonce="<?php echo $cssNonce; ?>">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/utilities/'); ?>?v=<?php echo $cssVersion; ?>" nonce="<?php echo $cssNonce; ?>">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/datagrid/'); ?>?v=<?php echo $cssVersion; ?>" nonce="<?php echo $cssNonce; ?>">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/' . $pageFile . '/'); ?>?v=<?php echo $cssVersion; ?>" nonce="<?php echo $cssNonce; ?>">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/phantomwing/'); ?>?v=<?php echo $cssVersion; ?>" nonce="<?php echo $cssNonce; ?>">
  <link rel="stylesheet" href="<?php echo Environment::appURL('css/responsive/'); ?>?v=<?php echo $cssVersion; ?>" nonce="<?php echo $cssNonce; ?>">

  <link rel="dns-prefetch"                                 href="<?php echo Environment::appBaseURL(); ?>">
  <link rel="preconnect"                                   href="<?php echo Environment::appBaseURL(); ?>">
  <link rel="dns-prefetch"                                 href="<?php echo Environment::appURL('earnings/'); ?>">
  <link rel="preconnect"                                   href="<?php echo Environment::appURL('earnings/'); ?>">
  <link rel="dns-prefetch"                                 href="<?php echo Environment::appURL('sites/'); ?>">
  <link rel="dns-prefetch"                                 href="<?php echo Environment::appURL('sites/'); ?>">
  <link rel="dns-prefetch"                                 href="<?php echo Environment::appURL('settings/#account_prefs'); ?>">
  <link rel="dns-prefetch"                                 href="<?php echo Environment::appURL('about/'); ?>">
  <link rel="dns-prefetch"                                 href="<?php echo Environment::appURL('help/'); ?>">
  <link rel="dns-prefetch"                                 href="<?php echo Environment::appURL('policies/'); ?>">

  <!-- Set -->
<?php
  $hajRenders = [
    '__CSP_NONCE__' => $cspNonce,
    '__JSON_LD__' => $jsonLdDocument,
  ];
  echo Render::template('header-application-json-linked-data', $hajRenders);
?>

  <script src="<?php echo Environment::appURL('js/guardian.js'); ?>" nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>"></script>

<?php if ($isAuthenticated) { ?>
  <script type="module" src="<?php echo Environment::appURL('js/'); ?>" nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>"></script>
  <script type="module" src="<?php echo Environment::appURL('js/phantomwing/'); ?>" nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>"></script>
  <script type="module" src="<?php echo Environment::appURL('js/encryption/'); ?>" nonce="<?php echo htmlspecialchars($cspNonce, ENT_QUOTES, 'UTF-8'); ?>"></script>
<?php } ?>

</head>

<!-- Go -->
<?php
$allowedNavPositions = ['left', 'right'];
$userForNav = User::current();
$navPrimaryPosition = strtolower((string) ($userForNav->nav_position_primary ?? UserPreferenceDefaults::DEFAULT_NAV_POSITION_PRIMARY));

if (!in_array($navPrimaryPosition, $allowedNavPositions, true)) {
  $navPrimaryPosition = UserPreferenceDefaults::DEFAULT_NAV_POSITION_PRIMARY;
}

$navInitialStateRaw = strtolower((string) ($userForNav->nav_state_primary ?? ''));
$navInitialState = in_array($navInitialStateRaw, ['collapsed', 'pinned'], true)
  ? $navInitialStateRaw
  : 'collapsed';

$isSidePrimaryNav = in_array($navPrimaryPosition, ['left', 'right'], true);
$activeLanguageForNav = Language::resolveFromQuery('l');
$languageNavHtml = Render::languageNav($activeLanguageForNav);
?>
<body role="document" aria-label="<?php echo Strings::headerI18n('SITE_NAME'); ?>" data-nav-primary-position="<?php echo htmlspecialchars($navPrimaryPosition, ENT_QUOTES, 'UTF-8'); ?>" data-nav-initial-state="<?php echo htmlspecialchars($navInitialState, ENT_QUOTES, 'UTF-8'); ?>">

<div id="status" class="status centered" aria-live="polite" role="status"></div>

<noscript><div role='status' aria-live='polite'><?php echo Strings::headerI18n('NEED_TO_ENABLE_JAVASCRIPT'); ?></div></noscript>

<a id="skip_to_content" class="skip_link" href="#main" accesskey="0" aria-keyshortcuts="Alt+0"><?php echo Strings::headerI18n('SKIP_TO_CONTENT'); ?></a>

<?php if ($isSidePrimaryNav) { ?>
<button
  id="sidebar_toggle_control"
  class="sidebar_toggle_accessible"
  type="button"
  aria-controls="primary_navigation"
  aria-expanded="true"
  aria-label="<?php echo Strings::headerI18n('COLLAPSE_SIDEBAR'); ?>"
  data-label-expand="<?php echo htmlspecialchars(Strings::headerI18n('EXPAND_SIDEBAR'), ENT_QUOTES, 'UTF-8'); ?>"
  data-label-collapse="<?php echo htmlspecialchars(Strings::headerI18n('COLLAPSE_SIDEBAR'), ENT_QUOTES, 'UTF-8'); ?>"
  data-announce-expanded="<?php echo htmlspecialchars(Strings::headerI18n('SIDEBAR_EXPANDED'), ENT_QUOTES, 'UTF-8'); ?>"
  data-announce-collapsed="<?php echo htmlspecialchars(Strings::headerI18n('SIDEBAR_COLLAPSED'), ENT_QUOTES, 'UTF-8'); ?>"
>
  <?php echo Strings::headerI18n('COLLAPSE_SIDEBAR'); ?>
</button>
<?php } ?>

<?php if ($isAuthenticated) { ?>
<?php
  $renders = [
      '__KEYBOARD_SHORTCUTS__'              => Strings::headerI18n('KEYBOARD_SHORTCUTS'),
      '__OPEN_CALENDAR_WITH__'              => Strings::headerI18n('OPEN_CALENDAR_WITH'),
      '__KEYBOARD_SHORTCUTS_MODAL_ARIA__'   => Strings::headerI18n('KEYBOARD_SHORTCUTS_MODAL_ARIA'),
      '__KEYBOARD_SHORTCUTS_MODAL_META__'   => Strings::headerI18n('KEYBOARD_SHORTCUTS_MODAL_META'),
      '__KEYBOARD_SHORTCUTS_SYSTEM_ARIA__'  => Strings::headerI18n('KEYBOARD_SHORTCUTS_SYSTEM_ARIA'),
      '__KEYBOARD_SHORTCUTS_SYSTEM_TITLE__' => Strings::headerI18n('KEYBOARD_SHORTCUTS_SYSTEM_TITLE'),
      '__KEYBOARD_SHORTCUTS_TOGGLE_SIDEBAR__' => Strings::headerI18n('KEYBOARD_SHORTCUTS_TOGGLE_SIDEBAR'),
      '__KEYBOARD_SHORTCUTS_CALENDAR_ARIA__' => Strings::headerI18n('KEYBOARD_SHORTCUTS_CALENDAR_ARIA'),
      '__CALENDAR__'                        => Strings::headerI18n('CALENDAR'),
      '__KEYBOARD_SHORTCUTS_SAFEGUARDS_ARIA__' => Strings::headerI18n('KEYBOARD_SHORTCUTS_SAFEGUARDS_ARIA'),
      '__KEYBOARD_SHORTCUTS_SAFEGUARDS_TITLE__' => Strings::headerI18n('KEYBOARD_SHORTCUTS_SAFEGUARDS_TITLE'),
      '__KEYBOARD_SHORTCUTS_SAFEGUARDS_TEXT__' => Strings::headerI18n('KEYBOARD_SHORTCUTS_SAFEGUARDS_TEXT'),
      '__ALT_C__'                           => Strings::headerI18n('ALT_C'),
      '__OPEN_CALENDAR__'                   => Strings::headerI18n('OPEN_CALENDAR'),
      '__OPEN_EARNINGS_WITH__'              => Strings::headerI18n('OPEN_EARNINGS_WITH'),
      '__ALT_R__'                           => Strings::headerI18n('ALT_R'),
      '__OPEN_EARNINGS__'                   => Strings::headerI18n('OPEN_EARNINGS'),
      '__HELP__'                            => Strings::headerI18n('HELP'),
      '__TRANSPARENCY__'                    => Strings::headerI18n('TRANSPARENCY'),
      '__OPEN_ACCOUNT_WITH__'               => Strings::headerI18n('OPEN_ACCOUNT_WITH'),
      '__ALT_A__'                           => Strings::headerI18n('ALT_A'),
      '__OPEN_ACCOUNT__'                    => Strings::headerI18n('OPEN_ACCOUNT'),
      '__OPEN_ABOUT_WITH__'                 => Strings::headerI18n('OPEN_ABOUT_WITH'),
      '__ALT_B__'                           => Strings::headerI18n('ALT_B'),
      '__OPEN_ABOUT__'                      => Strings::headerI18n('OPEN_ABOUT'),
      '__OPEN_POLICIES_WITH__'              => Strings::headerI18n('OPEN_POLICIES_WITH'),
      '__ALT_P__'                           => Strings::headerI18n('ALT_P'),
      '__OPEN_POLICIES__'                   => Strings::headerI18n('OPEN_POLICIES'),
      '__OPEN_SHORTCUTS_WITH__'             => Strings::headerI18n('OPEN_SHORTCUTS_WITH'),
      '__QUESTION_MARK_KEY__'               => Strings::headerI18n('QUESTION_MARK_KEY'),
      '__OPEN_NUMBERED_TAB_WITH__'          => Strings::headerI18n('OPEN_NUMBERED_TAB_WITH'),
      '__OPEN_NUMBERED_TAB__'               => Strings::headerI18n('OPEN_NUMBERED_TAB'),
      '__NUMBERED__'                        => Strings::headerI18n('NUMBERED'),
      '__OPEN_SHORTCUTS__'                  => Strings::headerI18n('OPEN_SHORTCUTS'),
      '__OPEN_DIALOG_WITH__'                => Strings::headerI18n('OPEN_DIALOG_WITH'),
      '__ENTER_KEY__'                       => Strings::headerI18n('ENTER_KEY'),
      '__OPEN_DIALOG__'                     => Strings::headerI18n('OPEN_DIALOG'),
      '__CLOSE__'                           => Strings::headerI18n('CLOSE'),
      '__CLOSE_DIALOG_WITH__'               => Strings::headerI18n('CLOSE_DIALOG_WITH'),
      '__ESCAPE_KEY__'                      => Strings::headerI18n('ESCAPE_KEY'),
      '__CLOSE_DIALOG__'                    => Strings::headerI18n('CLOSE_DIALOG'),
      '__TOGGLE_CALENDAR_SCREENMODE_WITH__' => Strings::headerI18n('TOGGLE_CALENDAR_SCREENMODE_WITH'),
      '__TOGGLE_CALENDAR_SCREENMODE__'      => Strings::headerI18n('TOGGLE_CALENDAR_SCREENMODE'),
      '__TILDE_KEY__'                       => Strings::headerI18n('TILDE_KEY'),
      '__OPEN_DATE_PICKER_WITH__'           => Strings::headerI18n('OPEN_DATE_PICKER_WITH'),
      '__ALT_BACKSLASH__'                   => Strings::headerI18n('ALT_BACKSLASH'),
      '__OPEN_DATE_PICKER__'                => Strings::headerI18n('OPEN_DATE_PICKER'),
      '__NAVIGATE_WITH__'                   => Strings::headerI18n('NAVIGATE_WITH'),
      '__TAB_KEY__'                         => Strings::headerI18n('TAB_KEY'),
      '__ARROW_KEYS__'                      => Strings::headerI18n('ARROW_KEYS'),
      '__HOME_KEY__'                        => Strings::headerI18n('HOME_KEY'),
      '__END_KEY__'                         => Strings::headerI18n('END_KEY'),
      '__NAVIGATE__'                        => Strings::headerI18n('NAVIGATE'),
      '__NEXT_PREV_CALENDAR_MONTH_WITH__'   => Strings::headerI18n('NEXT_PREV_CALENDAR_MONTH_WITH'),
      '__NEXT_PREV_BRACKETS__'              => Strings::headerI18n('NEXT_PREV_BRACKETS'),
      '__NEXT_PREV_PAGEKEYS__'              => Strings::headerI18n('NEXT_PREV_PAGEKEYS'),
      '__NEXT_PREV_CALENDAR_MONTH__'        => Strings::headerI18n('NEXT_PREV_CALENDAR_MONTH'),
      '__COPY_WORK_WITH__'                  => Strings::headerI18n('COPY_WORK_WITH'),
      '__COPY_WORK__'                       => Strings::headerI18n('COPY_WORK'),
      '__CTRL_C__'                          => Strings::headerI18n('CTRL_C'),
      '__PASTE_WORK_WITH__'                 => Strings::headerI18n('PASTE_WORK_WITH'),
      '__PASTE_WORK__'                      => Strings::headerI18n('PASTE_WORK'),
      '__CTRL_V__'                          => Strings::headerI18n('CTRL_V'),
      '__DELETE_WORK_WITH__'                => Strings::headerI18n('DELETE_WORK_WITH'),
      '__DELETE_KEY__'                      => Strings::headerI18n('DELETE_KEY'),
      '__DELETE_WORK__'                     => Strings::headerI18n('DELETE_WORK'),
      '__KEYBOARD_SHORTCUTS_GOT_IT__'       => Strings::headerI18n('KEYBOARD_SHORTCUTS_GOT_IT'),
      '__HELP_PAGE_TEASER__'                => Strings::html('HELP_PAGE_TEASER'),
  ];

echo Render::template('keyboard-shortcuts', $renders);
?>

<dialog id="modal_signout" aria-labelledby="modal_signout_title" aria-describedby="modal_signout_aria modal_signout_meta">
  <div class="modal_aria visually_hidden">
    <span id="modal_signout_aria"><?php echo Strings::headerI18n('SIGN_OUT_DIALOG_DESCRIPTION'); ?></span>
  </div>
  <div class="modal_meta visually_hidden">
    <span id="modal_signout_meta"><?php echo Strings::headerI18n('SIGN_OUT_CONFIRMATION_AND_CONTROLS'); ?></span>
  </div>
  <form id="signout_form" name="signout_form" method="POST" action="<?php echo Environment::appURL('signout/'); ?>" aria-label="<?php echo Strings::headerI18n('SIGN_OUT'); ?>">
    <input class="visually_hidden" type="text" name="username" value="NOTUSED" autocomplete="username" hidden tabindex="-1" aria-hidden="true">
    <section class="modal_header">
      <button type="button" class="btn btn_close" data-dialog-close="modal_signout" aria-label="<?php echo Strings::headerI18n('CLOSE'); ?>">&times;</button>
      <h1 id="modal_signout_title" class="modal_title centered"><?php echo Strings::headerI18n('SIGN_OUT'); ?></h1>
    </section>
    <section class="modal_content f_column">
      <p><?php echo Strings::headerI18n('SIGN_OUT_MESSAGE'); ?></p>
    </section>
    <section class="modal_footer">
      <div class="modal_controls flex centered">
        <button id="signout_submit" class="btn btn_primary f_just_center mar_sm"><?php echo Strings::headerI18n('SIGN_OUT'); ?></button>
        <button id="signout_cancel_btn" class="btn btn_cancel f_just_center mar_sm"><?php echo Strings::headerI18n('CANCEL'); ?></button>
      </div>
    </section>
  </form>
</dialog>

<?php if (User::isAdmin()) { ?>
<div id="dashboard">
  <div id="dashboardHeader" aria-roledescription="draggable">
    <span><?php echo Strings::headerI18n('DASHBOARD'); ?></span>
    <button id="dashboardCloseButton" aria-label="<?php echo Strings::headerI18n('DASHBOARD_CLOSE_ARIA'); ?>">✕</button>
  </div>

  <div id="dashboardBody">
    <section id="user_identity_section" class="dashboard-section" aria-label="<?php echo Strings::headerI18n('DASHBOARD_SESSION_IDENTITY'); ?>">
      <h2 class="dashboard-section-title"><?php echo Strings::headerI18n('DASHBOARD_SESSION_IDENTITY'); ?></h2>
      <div id="user_identity_content" class="dashboard-section-content dashboard-identity-grid">
        <span class="dashboard-identity-label">Name</span>
        <span class="dashboard-identity-value"><?php echo htmlspecialchars(User::current()->full_name, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="dashboard-identity-label">UUID</span>
        <span class="dashboard-identity-value dashboard-identity-mono"><?php echo htmlspecialchars(User::currentUUID(), ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="dashboard-identity-label">Auth Level</span>
        <span class="dashboard-identity-value"><?php echo htmlspecialchars(User::current()->auth_level->value, ENT_QUOTES, 'UTF-8'); ?></span>
        <span class="dashboard-identity-label">Email</span>
        <span class="dashboard-identity-value"><?php echo User::current()->email_verified ? 'verified' : 'unverified'; ?></span>
        <span class="dashboard-identity-label">Theme</span>
        <span class="dashboard-identity-value dashboard-identity-mono"><?php echo htmlspecialchars(User::current()->theme . ' / ' . User::current()->variant, ENT_QUOTES, 'UTF-8'); ?></span>
      </div>
    </section>

    <section id="lens_metrics_section" class="dashboard-section" aria-label="<?php echo Strings::headerI18n('DASHBOARD_LENS_METRICS'); ?>">
      <h2 class="dashboard-section-title"><?php echo Strings::headerI18n('DASHBOARD_LENS_METRICS'); ?></h2>
      <div id="lens_metrics_content" class="dashboard-section-content">
        <p class="dashboard-empty"><?php echo Strings::headerI18n('DASHBOARD_LOADING_BACKEND_METRICS'); ?></p>
      </div>
    </section>

    <section id="ws_heartbeat_section" class="dashboard-section" aria-label="<?php echo Strings::headerI18n('DASHBOARD_WEB_STATUS_HEARTBEAT'); ?>">
      <h2 class="dashboard-section-title"><?php echo Strings::headerI18n('DASHBOARD_WEB_STATUS'); ?></h2>
      <div id="ws_heartbeat_content" class="dashboard-section-content">
        <p class="dashboard-empty"><?php echo Strings::headerI18n('DASHBOARD_HEARTBEAT_IDLE'); ?></p>
      </div>
    </section>

    <section id="pw_metrics_section" class="pw-metrics-section" aria-label="<?php echo Strings::headerI18n('DASHBOARD_PHANTOM_WING_METRICS'); ?>">
      <h2 class="pw-metrics-title"><?php echo Strings::headerI18n('DASHBOARD_PHANTOM_WING_METRICS'); ?></h2>
      <div id="pw_metrics_content" class="pw-metrics-content">
        <p class="pw-metrics-empty"><?php echo Strings::headerI18n('DASHBOARD_NO_METRICS_YET'); ?></p>
      </div>
    </section>
  </div>

  <div id="dashboardResizeGrip" role="separator" aria-label="Resize dashboard"></div>
</div>
<?php } // end isAdmin ?>
<?php } // end $isAuthenticated ?>

<?php if ($isAuthenticated) {
  /** PRIMARY NAVIGATION BAR - Build navigation pages */
  $userUUIDForNav = User::currentUUID();
  $hasPremiumSubscriptionForNav = $userUUIDForNav !== '' && SubscriptionGate::hasActivePremium($userUUIDForNav);
  $pages = [Page::INDEX, Page::EARNINGS, Page::SITES, Page::ORGANIZATIONS, Page::PROFILE];

  $sideNavIcons = [
    'settings' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M19.14 12.94c.04-.31.06-.62.06-.94s-.02-.63-.07-.94l2.03-1.58a.5.5 0 0 0 .12-.64l-1.92-3.32a.5.5 0 0 0-.6-.22l-2.39.96a7.1 7.1 0 0 0-1.63-.94l-.36-2.54a.5.5 0 0 0-.49-.42h-3.84a.5.5 0 0 0-.49.42l-.36 2.54c-.58.23-1.12.54-1.63.94l-2.39-.96a.5.5 0 0 0-.6.22L2.7 8.84a.5.5 0 0 0 .12.64l2.03 1.58c-.05.31-.07.63-.07.95s.02.63.07.94L2.82 14.53a.5.5 0 0 0-.12.64l1.92 3.32c.13.22.39.31.6.22l2.39-.96c.5.4 1.05.72 1.63.95l.36 2.54c.04.24.25.42.49.42h3.84c.24 0 .45-.18.49-.42l.36-2.54c.58-.23 1.13-.55 1.63-.95l2.39.96c.22.09.47 0 .6-.22l1.92-3.32a.5.5 0 0 0-.12-.64l-2.03-1.57zM12 15.5A3.5 3.5 0 1 1 12 8.5a3.5 3.5 0 0 1 0 7z"/></svg>',
    'shortcuts' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 17.2a1.2 1.2 0 1 1 0-2.4 1.2 1.2 0 0 1 0 2.4zm2.07-7.43-.87.62c-.63.45-.98.9-.98 1.81v.3h-2v-.39c0-1.37.51-2.26 1.62-3.05l1.1-.79c.5-.36.78-.88.78-1.45 0-1.02-.78-1.74-1.9-1.74-1.2 0-1.95.75-2.01 1.95H7.79c.1-2.23 1.62-3.88 4.05-3.88 2.35 0 3.95 1.46 3.95 3.57 0 1.2-.57 2.3-1.72 3.12z"/></svg>',
    'admin' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2 3 6v6c0 5 3.84 9.74 9 11 5.16-1.26 9-6 9-11V6l-9-4zm0 10a3 3 0 1 1 0-6 3 3 0 0 1 0 6zm0 8c-2.04-.64-3.8-2.02-5-3.83.12-1.66 3.33-2.57 5-2.57s4.88.91 5 2.57C15.8 17.98 14.04 19.36 12 20z"/></svg>',
    'metrics' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M3 3h2v18H3V3zm16 8h2v10h-2V11zM7 13h2v8H7v-8zm4-5h2v13h-2V8zm4-6h2v19h-2V2z"/></svg>',
    'redis' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M12 2 3 6v4l9 4 9-4V6l-9-4zm0 7L5.06 6.52 12 3.44l6.94 3.08L12 9zm-9 5 9 4 9-4v4l-9 4-9-4v-4z"/></svg>',
    'ast' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M10 4h4v3h-4V4zm-6 0h4v3H4V4zm12 0h4v3h-4V4zM4 17h4v3H4v-3zm6 0h4v3h-4v-3zm6 0h4v3h-4v-3zM6 8h2v3h2v2H8v3H6v-3H4v-2h2V8zm8 1h6v2h-6V9zm-4 0h2v7h-2V9zm4 4h6v2h-6v-2z"/></svg>',
    'tests' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M7 2h10v2h-1v3.1l3.6 6.2A4 4 0 0 1 16.1 20H7.9a4 4 0 0 1-3.5-6.7L8 7.1V4H7V2zm3 2v3.63L6.13 14.2A2 2 0 0 0 7.9 18h8.2a2 2 0 0 0 1.77-3.8L14 7.63V4h-4z"/></svg>',
    'signout' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor" aria-hidden="true" focusable="false"><path d="M10 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h5v-2H5V5h5V3zm5.59 4.59L14.17 9l2.58 2H8v2h8.75l-2.58 2 1.42 1.41L21 12l-5.41-4.41z"/></svg>',
  ];

  $requestUriRaw = $_SERVER['REQUEST_URI'] ?? '/';
  $requestUri = is_scalar($requestUriRaw) ? (string) $requestUriRaw : '/';
  $requestPathRaw = parse_url($requestUri, PHP_URL_PATH);
  $requestPath = is_string($requestPathRaw) ? $requestPathRaw : '/';
  $requestPath = rtrim($requestPath, '/');
  if ($requestPath === '') {
    $requestPath = '/';
  }

  $adminSurfaceEnabled = \PayCal\Domain\AdminSurface::isEnabled();
  $adminNavItems = $adminSurfaceEnabled ? \PayCal\Domain\AdminSurface::navLinks() : [];
  $settingsActive = ($currentPage === 'PAGE_SETTINGS') || str_starts_with($requestPath, '/settings');
  $adminSectionActive = false;
  if ($adminSurfaceEnabled) {
    foreach ($adminNavItems as $adminNavItem) {
      if (\PayCal\Domain\AdminSurface::navItemIsActive($adminNavItem, $requestPath)) {
        $adminSectionActive = true;
        break;
      }
    }
  }
} // end $isAuthenticated ?>

<header id="page_header" class="nav_component nav_component--header<?php echo !$isAuthenticated ? ' nav_component--public' : ''; ?>" role="banner" aria-label="<?php echo Strings::headerI18n('HEADER'); ?>">
  <nav id="primary_navigation" class="nav_menu nav_menu--primary" role="navigation" aria-label="<?php echo Strings::headerI18n('PRIMARY'); ?>">
    <ul aria-label="<?php echo Strings::headerI18n('PAGES'); ?>">
<?php if (!$isAuthenticated) { ?>
  <li class="pages"><a href="/" aria-label="<?php echo htmlspecialchars(Strings::headerI18n('PAYCAL'), ENT_QUOTES, 'UTF-8'); ?>"><span class="nav_icon pages nav_brand_mark" aria-hidden="true"><img class="nav_brand_mark_base" src="/img/paycal-shield.png?v=<?php echo rawurlencode(Environment::appVersion()); ?>" alt="" width="34" height="34" decoding="async"><span class="nav_brand_mark_tint"></span></span><span class="nav_label"><?php echo Strings::html('PAYCAL_HTML_PUBLIC'); ?></span></a></li>
<?php } else {
    $navLinks = Render::buildNavLinks($pages, $hasPremiumSubscriptionForNav);
    echo Render::renderNavLinks($navLinks, $currentPage, 'pages');
?>
          <li class="pages<?php echo $settingsActive ? ' active' : ''; ?>"><a href="/settings/" data-nav-shortcut="e" aria-keyshortcuts="e" accesskey="e"<?php echo $settingsActive ? " aria-current='page'" : ''; ?>><span class="nav_icon nav_icon--side"><?php echo $sideNavIcons['settings']; ?></span><span class="nav_label"><?php echo Strings::headerI18n('SETTINGS_HTML'); ?></span></a></li>
        <?php if ($adminSurfaceEnabled && User::isAdmin() && $adminNavItems !== []) { ?>
          <li class="pages nav_admin_group<?php echo $adminSectionActive ? ' active' : ''; ?>">
            <a
              href="#"
              class="nav_admin_toggle"
              role="button"
              data-admin-popover-toggle="admin-nav-popover"
              aria-haspopup="menu"
              aria-controls="admin-nav-popover"
              aria-expanded="false"
            >
              <span class="nav_icon nav_icon--side"><?php echo $sideNavIcons['admin']; ?></span>
              <span class="nav_label"><?php echo Strings::headerI18n('ADMIN'); ?></span>
            </a>
            <div id="admin-nav-popover" class="nav_admin_popover" role="menu" aria-label="<?php echo Strings::headerI18n('ADMIN_TOOLS_ARIA'); ?>" hidden>
              <?php foreach ($adminNavItems as $adminNavItem) {
                $isActive = \PayCal\Domain\AdminSurface::navItemIsActive($adminNavItem, $requestPath);
                $iconKey = $adminNavItem['icon'];
                $iconSvg = $sideNavIcons[$iconKey] ?? $sideNavIcons['admin'];
                $label = Strings::headerI18n($adminNavItem['label_key']);
              ?>
              <a class="nav_admin_item<?php echo $isActive ? ' active' : ''; ?>" role="menuitem" href="<?php echo htmlspecialchars($adminNavItem['href'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isActive ? " aria-current='page'" : ''; ?>><span class="nav_icon nav_icon--side"><?php echo $iconSvg; ?></span><span class="nav_label"><?php echo $label; ?></span></a>
              <?php } ?>
            </div>
          </li>
        <?php } ?>
          <li class="pages"><a href="/help/" data-help-trigger="true" data-nav-shortcut="h" aria-keyshortcuts="h" accesskey="h"><span class="nav_icon nav_icon--side"><?php echo $sideNavIcons['shortcuts']; ?></span><span class="nav_label"><?php echo Strings::headerI18n('KEYBOARD'); ?></span></a></li>
<?php } // end $isAuthenticated nav ?>
<?php if (!$isAuthenticated) {
  echo $languageNavHtml;
} ?>
<?php if ($isAuthenticated) { ?>
      <li class="pages nav_signout"><a href="/signout/" id="call_signout_modal"><span class="nav_icon nav_icon--side"><?php echo $sideNavIcons['signout']; ?></span><span class="nav_label"><?php echo Strings::headerI18n('SIGN_OUT'); ?></span></a></li>
<?php } ?>
    </ul>
  </nav>
</header>

<main id="main" role="main" tabindex="-1" aria-label="<?php if (isset($pageLabel)) {
    echo $pageLabel;
  } ?>"><?php echo Authentication::getVerificationReminderHtml(); ?>
