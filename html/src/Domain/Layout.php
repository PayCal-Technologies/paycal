<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

/**
 * Layout.php
 *
 * Purpose: HTML shell/layout composition helper for public and authenticated
 * page wrappers, page metadata, and shared structural markup.
 *
 * Developer notes:
 * - Layout composition affects broad UI surfaces and should remain aligned with
 *   shared navigation and wrapper conventions.
 * - Keep CSP-sensitive concerns in mind; avoid introducing inline asset hacks
 *   here because this class sits on the main page shell path.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Shared page-layout composition utility.
 *
 * Responsibilities:
 * - Build public and authenticated page shells.
 * - Apply shared metadata and structural wrappers consistently.
 * - Support shell-level conventions used across page controllers.
 */
class Layout
{
  /**
   * Render a page with public layout (header + content + footer).
   * 
   * Public layout characteristics:
   * - No authentication required
  * - Minimal footer navigation (About, Help, Contact, Transparency, Policies)
   * - No encryption scripts or session management UI
  * - No authenticated navigation items (Calendar, Earnings, Sites, Organizations)
   * 
   * @param string $content Main page content HTML
   * @param string $pageTitle Browser tab title
   * @param string $metaDescription SEO description
   * @return string Complete HTML page
   */
  public static function renderPublic(
    string $content, 
    string $pageTitle = '', 
    string $metaDescription = ''
  ): string {
    $i18n = [];
    $i18nKeys = [
      'SITE_NAME',
      'META_DESCRIPTION',
      'ABOUT_US',
      'HELP',
      'MEDIA',
      'CONTACT_US',
      'TRANSPARENCY',
      'POLICIES',
    ];
    foreach ($i18nKeys as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    // Default values
    if ($pageTitle === '') {
      $pageTitle = $i18n['SITE_NAME'];
    }
    
    if ($metaDescription === '') {
      $metaDescription = $i18n['META_DESCRIPTION'];
    }

    // Public navigation links (footer only)
    $publicNavLinks = [
      ['page' => 'PAGE_ABOUT',
        'name' => Strings::html('ABOUT_US'),
        'href' => Environment::appURL('about/'),
        'arialabel' => $i18n['ABOUT_US'],
        'access_key' => 'B', 
        'icon' => ''],
      ['page' => 'PAGE_HELP',
        'name' => Strings::html('HELP'),
        'href' => Environment::appURL('help/'),
        'arialabel' => $i18n['HELP'],
        'access_key' => 'h',
        'icon' => ''],
      ['page' => 'PAGE_MEDIA',
        'name' => Strings::html('MEDIA_HTML'),
        'href' => Environment::appURL('media/'),
        'arialabel' => $i18n['MEDIA'],
        'access_key' => 'm',
        'icon' => ''],
      ['page' => 'PAGE_CONTACT',
        'name' => Strings::html('CONTACT_US'),
        'href' => Environment::appURL('contact/'),
        'arialabel' => $i18n['CONTACT_US'],
        'access_key' => 'c',
        'icon' => ''],
      ['page' => 'PAGE_TRANSPARENCY',
        'name' => Strings::html('TRANSPARENCY'),
        'href' => Environment::appURL('transparency/'),
        'arialabel' => $i18n['TRANSPARENCY'],
        'access_key' => 'n',
        'icon' => ''],
      ['page' => 'PAGE_POLICIES',
        'name' => Strings::html('POLICIES'),
        'href' => Environment::appURL('policies/'),
        'arialabel' => $i18n['POLICIES'],
        'access_key' => 'l',
        'icon' => ''],
    ];

    $header = self::renderPublicHeader($pageTitle, $metaDescription);
    $footer = self::renderPublicFooter($publicNavLinks);

    return $header . $content . $footer;
  }

  /**
   * Render public page header (minimal, no auth).
   * 
   * @param string $pageTitle Browser tab title
   * @param string $metaDescription SEO description
   * @return string HTML header
   */
  private static function renderPublicHeader(string $pageTitle, string $metaDescription): string
  {
    $i18n = [];
    $i18nKeys = [
      'SITE_NAME',
      'SKIP_TO_CONTENT',
      'AUDIO_INTRO_BTN',
      'HEADER',
      'PRIMARY',
      'PAGES',
      'MAIN_CONTENT',
    ];
    foreach ($i18nKeys as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    $siteName = $i18n['SITE_NAME'];
    $pageLanguage = 'en-CA';
    $domainRaw = $_ENV['APP_DOMAIN'] ?? '';
    $domain = is_string($domainRaw) ? $domainRaw : '';
    $originScheme = parse_url(Environment::appPublicURL(), PHP_URL_SCHEME) ?: 'https';
    $origin = $originScheme . '://' . $domain;
    $cspNonce = User::nonce();
    $_SERVER['CSP_NONCE'] = $cspNonce;
    $cspReportUrl = Environment::appURL('api/' . Environment::apiVersion() . '/security/csp/report');

    // CSP for public pages (no unsafe-inline needed)
    $csp = [
      'default-src' => ["'none'"],
      'base-uri' => ["'self'", $origin],
      'connect-src' => ["'self'", $origin],
      'frame-src' => ["'self'", 'https://www.youtube.com', 'https://www.youtube-nocookie.com'],
      'font-src' => ["'self'", $origin],
      'form-action' => ["'self'", $origin],
      'img-src' => ["'self'", 'data:'],
      'media-src' => ["'self'", $origin],
      'manifest-src' => ["'self'", $origin],
      'object-src' => ["'none'"],
      'script-src' => ["'nonce-{$cspNonce}'", "'strict-dynamic'", "'self'", $origin],
      'style-src' => ["'self'", $origin],
      'style-src-elem' => ["'self'", $origin],
      'frame-ancestors' => ["'none'"],
      'report-uri' => [$cspReportUrl],
      'report-to' => ['csp-endpoint'],
    ];

    $policy = '';
    foreach ($csp as $directive => $values) {
      $policy .= $directive . ' ' . implode(' ', $values) . '; ';
    }

    if (!Environment::devSecurityDisabled()) {
      header('Content-Security-Policy: ' . trim($policy));
    }

    header('Report-To: {"group":"csp-endpoint","max_age":10886400,"endpoints":[{"url":"' . $cspReportUrl . '"}]}');

    Security::sendCoreSecurityHeaders();
    header('X-Robots-Tag: index, follow, noai, noimageai');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    $cssVersion = (string) time();
    $isAuthenticated = Authentication::getCookie() !== '';
    $headerNavigation = self::renderPublicHeaderNavigation($isAuthenticated);
    $allowedNavPositions = ['left', 'right'];
    $navPrimaryPosition = UserPreferenceDefaults::DEFAULT_NAV_POSITION_PRIMARY;
    $dyslexiaTypography = UserPreferenceDefaults::DEFAULT_DYSLEXIA_TYPOGRAPHY;

    if ($isAuthenticated) {
      $currentUser = User::current();
      $primary = strtolower($currentUser->nav_position_primary);
      $dyslexiaTypographyRaw = strtolower($currentUser->dyslexia_typography);

      if (in_array($primary, $allowedNavPositions, true)) {
        $navPrimaryPosition = $primary;
      }

      if (in_array($dyslexiaTypographyRaw, ['off', 'on'], true)) {
        $dyslexiaTypography = $dyslexiaTypographyRaw;
      }
    }
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en" dir="ltr" prefix="og: http://ogp.me/ns#" data-a11y-animated-images="system" data-a11y-link-underlines="true" data-a11y-dyslexia-typography="{$dyslexiaTypography}">
<head>
  <base href="{$origin}/">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="{$metaDescription}">
  <meta name="robots" content="index, follow, noai, noimageai">
  <meta name="theme-color" content="#060606">
  
  <title>{$pageTitle} | {$siteName}</title>
  
  <link rel="icon" type="image/x-icon" href="/favicon.ico">
  <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
  <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
  
  <link rel="stylesheet" href="/css/?v={$cssVersion}">
  <link rel="stylesheet" href="/css/navigation/?v={$cssVersion}">
  <link rel="stylesheet" href="/css/utilities/?v={$cssVersion}">
  <link rel="stylesheet" href="/css/content/?v={$cssVersion}">
  <link rel="stylesheet" href="/css/transparency/?v={$cssVersion}">
  <link rel="stylesheet" href="/css/responsive/?v={$cssVersion}">
  
  <meta property="og:site_name" content="{$siteName}">
  <meta property="og:title" content="{$pageTitle}">
  <meta property="og:description" content="{$metaDescription}">
  <meta property="og:type" content="website">
  <meta property="og:url" content="{$origin}">
  
</head>
<body role="document" aria-label="{$siteName}" data-nav-primary-position="{$navPrimaryPosition}">

HTML;

    $html .= '  <a id="skip_to_content" class="skip_link" href="#main" accesskey="0" aria-keyshortcuts="Alt+0">'
      . htmlspecialchars($i18n['SKIP_TO_CONTENT'], ENT_QUOTES, 'UTF-8')
      . '</a>'
      . PHP_EOL;

    $html .= '  <button id="page_audio_intro" class="skip_link" type="button">'
      . htmlspecialchars($i18n['AUDIO_INTRO_BTN'], ENT_QUOTES, 'UTF-8')
      . '</button>'
      . PHP_EOL;

    $headerAria = htmlspecialchars($i18n['HEADER'], ENT_QUOTES, 'UTF-8');
    $primaryAria = htmlspecialchars($i18n['PRIMARY'], ENT_QUOTES, 'UTF-8');
    $pagesAria = htmlspecialchars($i18n['PAGES'], ENT_QUOTES, 'UTF-8');
    $mainContentAria = htmlspecialchars($i18n['MAIN_CONTENT'], ENT_QUOTES, 'UTF-8');

    $html .= <<<HTML

    <header id="page_header" class="nav_component nav_component--header" role="banner" aria-label="{$headerAria}">
      <nav class="nav_menu nav_menu--primary" role="navigation" aria-label="{$primaryAria}">
        <ul aria-label="{$pagesAria}">
{$headerNavigation}
        </ul>
      </nav>
    </header>
  
  <main id="main" role="main" tabindex="-1" aria-label="{$mainContentAria}">

HTML;

    return $html;
  }

  /**
   * Render top header navigation for public layout pages.
   *
   * Authenticated visitors get the standard navigation menu.
   * Public visitors get logo-only navigation.
   *
   * @param bool $isAuthenticated Whether the request has an authenticated session
   * @return string HTML list items for top header navigation
   */
  private static function renderPublicHeaderNavigation(bool $isAuthenticated): string
  {
    $i18n = [];
    foreach (['PAYCAL'] as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    if ($isAuthenticated) {
      $pages = [Page::INDEX, Page::EARNINGS, Page::SITES];
      $userUUID = User::currentUUID();
      $hasPremiumSubscription = $userUUID !== '' && SubscriptionGate::hasActivePremium($userUUID);

      $navLinks = Render::buildNavLinks($pages, $hasPremiumSubscription);
      return Render::renderNavLinks($navLinks, '', 'pages');
    }

    $logoOnlyLink = [[
      'page' => 'PAGE_SIGNIN',
      'name' => (string) Strings::html('PAYCAL_HTML'),
      'href' => Environment::appURL('auth/'),
      'arialabel' => (string) $i18n['PAYCAL'],
      'access_key' => 'C',
      'icon' => '',
    ]];

    return Render::renderNavLinks($logoOnlyLink, '', 'pages');
  }

  /**
   * Render public page footer (minimal navigation, no auth scripts).
   * 
   * @param array<array<string, string>> $navLinks Navigation links
   * @return string HTML footer
   */
  private static function renderPublicFooter(array $navLinks): string
  {
    $navHtml = Render::renderNavLinks($navLinks, '');
    $i18n = [];
    foreach (['FOOTER', 'SECONDARY', 'PAGES'] as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    $footerAria = htmlspecialchars($i18n['FOOTER'], ENT_QUOTES, 'UTF-8');
    $secondaryAria = htmlspecialchars($i18n['SECONDARY'], ENT_QUOTES, 'UTF-8');
    $pagesAria = htmlspecialchars($i18n['PAGES'], ENT_QUOTES, 'UTF-8');

    $html = <<<HTML
  </main>
  
  <footer id="page_footer" class="ledge nav_component nav_component--footer" role="contentinfo" aria-label="{$footerAria}">
    <nav class="nav_menu nav_menu--secondary" role="navigation" aria-label="{$secondaryAria}">
      <ul aria-label="{$pagesAria}">
{$navHtml}
      </ul>
    </nav>
  </footer>
</body>
</html>

HTML;

    return $html;
  }

  /**
   * Render a page with authenticated layout (existing header.php/footer.php).
   * 
   * Authenticated layout characteristics:
  * - Full navigation (Calendar, Earnings, Sites, Organizations, Admin)
   * - Encryption scripts and session management
   * - Session timeout warnings
   * - Full CSP with TrustedHTML policies
   * 
   * @param string $content Main page content HTML
   * @param string $currentPage Page constant (e.g., 'PAGE_INDEX')
   * @return string Complete HTML page
   */
  public static function renderAuthenticated(string $content, string $currentPage = ''): string
  {
    ob_start();
    
    // Use existing header.php (relative to this file's location)
    require __DIR__ . '/../../header.php';
    echo $content;
    require __DIR__ . '/../../footer.php';
    
    return ob_get_clean() ?: '';
  }
}
