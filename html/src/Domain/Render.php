<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Render.php
 *
 * Purpose: Presentation helper for template resolution, layout assembly,
 * navigation markup, and page-level asset inclusion.
 *
 * Developer notes:
 * - This class is a view-composition utility, not a controller or policy
 *   layer. Avoid pushing authentication or business rules into render helpers.
 * - Template lookup intentionally supports both app-home and repo-relative
 *   paths; keep that fallback behavior when moving templates.
 * - Rendering helpers must remain CSP-aware. Do not introduce inline-script or
 *   inline-style shortcuts here.
 * - Navigation builders feed shared shells used across many pages, so output
 *   structure changes can have broad frontend impact.
 *
 * @category   Domain
 * @package    PayCal\Domain
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Shared rendering and shell-composition utility.
 *
 * Responsibilities:
 * - Resolve PHP templates and inject bounded variable sets.
 * - Compose public and authenticated application shells.
 * - Build navigation/link markup consumed by header and sidebar surfaces.
 * - Emit predictable asset/script tags for page-level resources.
 */
class Render
{
  /**
   * Render mode: strict = throw exceptions, lenient = log warnings
   */
  private static bool $strictMode = false;

  /**
   * Set template rendering mode (strict vs lenient).
   *
   * @param bool $strict if true, throw exceptions on missing/unused variables; if false, log warnings
   * @return void
   */
  public static function setStrictMode(bool $strict): void
  {
    self::$strictMode = $strict;
  }

  /**
   * Renders a PHP template, injecting variables and returning the output.
   *
   * @param string                $templateName template file to parse
   * @param array<string, string> $pairs        variables to make available in the template (key-value pairs)
   *
   * @return string rendered template output
   */
  public static function template(string $templateName, array $pairs = []): string
  {
    Log::info("[Render:template] {$templateName}");

    $templateFileName = $templateName . '.php';
    $templatePaths = [
      \PayCal\Domain\Config\Environment::appHome() . 'templates/' . $templateFileName,
      dirname(__DIR__, 3) . '/templates/' . $templateFileName,
    ];

    $templateFile = null;
    foreach ($templatePaths as $candidate) {
      if (is_file($candidate)) {
        $templateFile = $candidate;
        break;
      }
    }

    if ($templateFile === null) {
      Log::error("Template {$templateName} not found.");
      return "<!-- Template {$templateName} not found -->";
    }

    $buffer = (string) file_get_contents($templateFile);

    // Extract all template placeholders (__PLACEHOLDER_NAME__)
    preg_match_all('/__([A-Z_][A-Z0-9_]*)__/', $buffer, $matches);
    $requiredPlaceholders = array_map(static fn(string $m): string => "__{$m}__", $matches[1]);
    $requiredPlaceholders = array_unique($requiredPlaceholders);

    // Validate all required placeholders are provided
    $providedKeys = array_keys($pairs);
    $missingPlaceholders = array_diff($requiredPlaceholders, $providedKeys);

    if (!empty($missingPlaceholders)) {
      $missing = implode(', ', $missingPlaceholders);
      $message = "Template '{$templateName}' missing required variables: {$missing}";

      if (self::$strictMode) {
        throw new \RuntimeException($message);
      } else {
        Log::warn($message);
      }
    }

    // Warn about unused variables (provided but not in template)
    $unusedVariables = array_diff($providedKeys, $requiredPlaceholders);
    if (!empty($unusedVariables)) {
      $unused = implode(', ', $unusedVariables);
      Log::warn("[Render:template] Template '{$templateName}' unused variables: {$unused}");
    }

    foreach ($pairs as $name => $value) {
      $buffer = strtr((string) $buffer, [$name => $value]);
    }

    $buffer = self::resolveInlineI18nExpressions($buffer);

    return $buffer;
  }

  /**
   * Resolve inline i18n PHP echo expressions in token templates.
   *
   * Render::template treats files as token sources rather than executable PHP,
   * so inline i18n calls must be converted to literal strings here.
   */
  private static function resolveInlineI18nExpressions(string $buffer): string
  {
    $plainPattern = '/<\?php\s+echo\s+(?:\\\\?PayCal\\\\Domain\\\\)?Strings::i18n\(\s*[\'\"]([A-Z0-9_]+)[\'\"]\s*\)\s*;\s*\?>/';
    $escapedPattern = '/<\?php\s+echo\s+htmlspecialchars\(\s*(?:\\\\?PayCal\\\\Domain\\\\)?Strings::i18n\(\s*[\'\"]([A-Z0-9_]+)[\'\"]\s*\)\s*,\s*ENT_QUOTES\s*,\s*[\'\"]UTF-8[\'\"]\s*\)\s*;\s*\?>/';
    $plainHtmlPattern = '/<\?php\s+echo\s+(?:\\\\?PayCal\\\\Domain\\\\)?Strings::html\(\s*[\'\"]([A-Z0-9_]+)[\'\"]\s*\)\s*;\s*\?>/';
    $escapedHtmlPattern = '/<\?php\s+echo\s+htmlspecialchars\(\s*(?:\\\\?PayCal\\\\Domain\\\\)?Strings::html\(\s*[\'\"]([A-Z0-9_]+)[\'\"]\s*\)\s*,\s*ENT_QUOTES\s*,\s*[\'\"]UTF-8[\'\"]\s*\)\s*;\s*\?>/';

    $buffer = preg_replace_callback(
      $escapedPattern,
      static fn(array $matches): string => htmlspecialchars(Strings::i18n((string) $matches[1]), ENT_QUOTES, 'UTF-8'),
      $buffer
    ) ?? $buffer;

    $buffer = preg_replace_callback(
      $plainPattern,
      static fn(array $matches): string => Strings::i18n((string) $matches[1]),
      $buffer
    ) ?? $buffer;

    $buffer = preg_replace_callback(
      $escapedHtmlPattern,
      static fn(array $matches): string => htmlspecialchars(Strings::html((string) $matches[1]), ENT_QUOTES, 'UTF-8'),
      $buffer
    ) ?? $buffer;

    $buffer = preg_replace_callback(
      $plainHtmlPattern,
      static fn(array $matches): string => Strings::html((string) $matches[1]),
      $buffer
    ) ?? $buffer;

    return $buffer;
  }

  /**
   * Renders content within a page layout.
   * 
   * Layouts:
   * - 'public': Minimal navigation, no authentication resources
   * - 'authenticated': Full navigation, encryption scripts, session management
   * 
   * @param string $layoutType 'public' or 'authenticated'
   * @param string $content Main page content HTML
   * @param array<string, string> $options Layout options (pageTitle, metaDescription, currentPage)
   * @return string Complete HTML page
   */
  public static function layout(string $layoutType, string $content, array $options = []): string
  {
    if ($layoutType === 'public') {
      $pageTitle = $options['pageTitle'] ?? '';
      $metaDescription = $options['metaDescription'] ?? '';
      
      return Layout::renderPublic($content, $pageTitle, $metaDescription);
    }

    if ($layoutType === 'authenticated') {
      $currentPage = $options['currentPage'] ?? '';
      
      return Layout::renderAuthenticated($content, $currentPage);
    }

    throw new \InvalidArgumentException("Unknown layout type: {$layoutType}");
  }

  /**
   * Retrieves an HTML constant from Redis based on the key and language code.
   *
   * @param string      $key  The HTML key (e.g., 'EDIT_USER).
   * @param null|string $lang The language code (e.g., 'en', 'de'). Defaults to USER_LANGUAGE or 'en'.
   *
   * @return string the HTML string, or the resolved key if not found
   */
  public static function html(string $key, ?string $lang = null): string
  {
    if ($lang === null) {
      if (defined('PAYCAL_PAGE_LANGUAGE_OVERRIDE')) {
        $lang = (string) PAYCAL_PAGE_LANGUAGE_OVERRIDE;
      } else {
        $lang = \PayCal\Domain\User::current()->language;
      }
    }

    return Strings::html($key, $lang);
  }

  /**
   * Retrieves a localized string from file-backed i18n catalogs.
   *
   * @param string      $key  The i18n key (e.g., 'SETTINGS').
   * @param null|string $lang The language code (e.g., 'en', 'de'). Defaults to USER_LANGUAGE or 'en'.
   *
   * @return string the translated string, or the key if not found
   */
  public static function language(string $key, ?string $lang = null): string
  {
    if ($lang === null) {
      if (defined('PAYCAL_PAGE_LANGUAGE_OVERRIDE')) {
        $lang = (string) PAYCAL_PAGE_LANGUAGE_OVERRIDE;
      } else {
        $lang = \PayCal\Domain\User::current()->language;
      }
    }

    return Strings::i18n($key, $lang);
  }

  /**
   * Builds an array of navigation link definitions for rendering.
   * @param array<int, Page> $pages list of NavPage enum values to include in the navigation
   * @return array<int, array<string, string>> Structured navigation data ready for rendering.
   *
   * Each element of the returned array contains:
   * - page        : string  Constant identifier (e.g. "PAGE_INDEX")
   * - name        : string  Human-readable label
   * - href        : string  Absolute or relative link URL
   * - arialabel   : string  Accessible label text
   * - access_key  : string  Shortcut key (one character)
   * - icon        : string  Inline SVG or icon markup
   */
  public static function buildNavLinks(array $pages, bool $isPremiumMember = false): array
  {
    $nav = [];
    $i18n = [];
    $i18nKeys = [
      'PAYCAL',
      'EARNINGS_HTML',
      'EARNINGS',
      'SITES_HTML',
      'SITES',
      'PROFILE_HTML',
      'PROFILE',
      'ORGANIZATIONS_HTML',
      'ORGANIZATIONS',
    ];
    foreach ($i18nKeys as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    $paycalName = (string) Strings::html('PAYCAL_HTML');
    $paycalAriaLabel = (string) $i18n['PAYCAL'];
    if ($isPremiumMember) {
      $paycalName .= ' Premium';
      $paycalAriaLabel .= ' Premium';
    }

    foreach ($pages as $page) {
      $nav[] = match ($page) {
        Page::INDEX => [
            'page' => Page::INDEX->value,
          'name' => $paycalName,
            'href' => '/', // Public URL
          'arialabel' => $paycalAriaLabel,
            'access_key' => (string) 'C',
            'icon' => self::paycalBrandIconMarkup(),
        ],
        Page::EARNINGS => [
            'page' => Page::EARNINGS->value,
            'name' => (string) $i18n['EARNINGS_HTML'],
            'href' => '/earnings/', // Public URL
            'arialabel' => (string) $i18n['EARNINGS'],
            'access_key' => (string) 'R',
            'icon' => (string) Strings::html('MONEY_SVG'),
        ],
        Page::SITES => [
            'page' => Page::SITES->value,
            'name' => (string) $i18n['SITES_HTML'],
            'href' => '/sites/', // Public URL
            'arialabel' => (string) $i18n['SITES'],
            'access_key' => (string) 'S',
            'icon' => (string) Strings::html('SITES_SVG'),
        ],
        Page::PROFILE => [
            'page' => Page::PROFILE->value,
            'name' => (string) $i18n['PROFILE_HTML'],
            'href' => '/profile/', // Public URL
            'arialabel' => (string) $i18n['PROFILE'],
            'access_key' => (string) 'f',
            'icon' => (string) Strings::html('PROFILE_SVG'),
        ],
        Page::ORGANIZATIONS => [
          'page' => Page::ORGANIZATIONS->value,
          'name' => (string) $i18n['ORGANIZATIONS_HTML'],
          'href' => '/organizations', // Public URL
          'arialabel' => (string) $i18n['ORGANIZATIONS'],
          'access_key' => (string) 'O',
          'icon' => (string) Strings::html('ORGANIZATIONS_SVG'),
        ],
        // Admin nav is extension-driven (admin.nav.links capability popover).
        // Core does not render it as a standard nav link.
        Page::ADMIN => throw new \LogicException('Page::ADMIN must not be passed to buildNavLinks; admin nav is extension-driven via the admin.nav.links capability.'),
      };
    }

    return $nav;
  }

  /**
   * Returns markup for the PayCal brand icon used in navigation.
   */
  private static function paycalBrandIconMarkup(): string
  {
    $assetVersion = rawurlencode(Environment::appVersion());

    return "<span class='nav_brand_mark'><img class='nav_brand_mark_base nav_brand_mark_base--app' src='/img/paycal-shield.png?v={$assetVersion}' alt='' width='24' height='24' decoding='async'><span class='nav_brand_mark_tint'></span></span>";
  }

  /**
   * Generates HTML navigation links from an array of link objects.
   *
   * @param array<array<string, string>> $links      array of navigation objects
   * @param string                       $activePage Optional. The current active page identifier.
   * @param string                       $extraCSS   Optional. Additional CSS classes.
   *
   * @return string rendered HTML list of navigation links
   */
  public static function renderNavLinks(array $links, string $activePage = '', string $extraCSS = ''): string
  {
    $buffer = '';

    foreach ($links as $link) {
      $css = $extraCSS;
      $ariaCurrent = '';

      $linkPage = (string) ($link['page'] ?? '');
      $name = (string) ($link['name'] ?? '');
      $href = (string) ($link['href'] ?? '#');
      $ariaLabel = (string) ($link['arialabel'] ?? $name);
      $accessKey = (string) ($link['access_key'] ?? '');
      $iconContent = (string) ($link['icon'] ?? '');

      if ($activePage === $linkPage) {
        $css .= ' active';
        $ariaCurrent = "aria-current='page'";
      }

      $icon = '';
      if ('' !== $iconContent) {
        $icon = "<span class='nav_icon"
              .('' !== $css ? ' '.$css : '')
              ."'>".$iconContent.'</span>';
      }

      $renders = [
          '__SCSS__' => $css,
          '__HREF__' => $href,
          '__ARIALABEL__' => $ariaLabel,
          '__ARIA_CURRENT__' => $ariaCurrent,
          '__ACCESS_KEY__' => $accessKey,
          '__SICON__' => $icon,
          '__NAME__' => $name,
      ];

      $buffer .= Render::template('nav-link-item', $renders);
    }

    return $buffer;
  }

  /**
   * Build right-aligned language switcher markup for header navigation.
   *
    * Displays current language flag and exposes other languages in a popover list.
    * Keeps current route/query and replaces only `l`.
   */
  public static function languageNav(string $activeLanguage): string
  {
    $activeLanguage = strtolower($activeLanguage);
    if (!Language::isSupported($activeLanguage)) {
      $activeLanguage = Language::DEFAULT;
    }

    $requestUriRaw = $_SERVER['REQUEST_URI'] ?? '/';
    $requestUri = is_scalar($requestUriRaw) ? (string) $requestUriRaw : '/';
    $pathRaw = parse_url($requestUri, PHP_URL_PATH);
    $path = is_string($pathRaw) && $pathRaw !== '' ? $pathRaw : '/';

    $queryRaw = parse_url($requestUri, PHP_URL_QUERY);
    $queryParams = [];
    if (is_string($queryRaw) && $queryRaw !== '') {
      parse_str($queryRaw, $queryParams);
    }

    $flags = [
      'en' => '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false"><rect width="20" height="20" fill="#012169"/><path d="M0 2 2 0l18 18-2 2L0 2Zm18 0 2 2L2 20 0 18 18 0Z" fill="#fff"/><path d="M0 3.5 3.5 0 20 16.5 16.5 20 0 3.5Zm16.5 0L20 3.5 3.5 20 0 16.5 16.5 0Z" fill="#C8102E"/><rect x="8" width="4" height="20" fill="#fff"/><rect y="8" width="20" height="4" fill="#fff"/><rect x="8.8" width="2.4" height="20" fill="#C8102E"/><rect y="8.8" width="20" height="2.4" fill="#C8102E"/></svg>',
      'de' => '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false"><rect width="20" height="6.67" y="0" fill="#000"/><rect width="20" height="6.67" y="6.67" fill="#DD0000"/><rect width="20" height="6.66" y="13.34" fill="#FFCE00"/></svg>',
      'fr' => '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false"><rect width="6.67" height="20" x="0" fill="#0055A4"/><rect width="6.66" height="20" x="6.67" fill="#fff"/><rect width="6.67" height="20" x="13.33" fill="#EF4135"/></svg>',
      'es' => '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false"><rect width="20" height="20" fill="#AA151B"/><rect y="5" width="20" height="10" fill="#F1BF00"/></svg>',
      'it' => '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false"><rect width="6.67" height="20" x="0" fill="#009246"/><rect width="6.66" height="20" x="6.67" fill="#fff"/><rect width="6.67" height="20" x="13.33" fill="#CE2B37"/></svg>',
      'nl' => '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false"><rect width="20" height="6.67" y="0" fill="#AE1C28"/><rect width="20" height="6.67" y="6.67" fill="#fff"/><rect width="20" height="6.66" y="13.34" fill="#21468B"/></svg>',
      'pt' => '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false"><rect width="8" height="20" x="0" fill="#046A38"/><rect width="12" height="20" x="8" fill="#DA291C"/><circle cx="8" cy="10" r="2.2" fill="#F8D247"/></svg>',
      'hi' => '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false"><rect width="20" height="6.67" y="0" fill="#FF9933"/><rect width="20" height="6.67" y="6.67" fill="#fff"/><rect width="20" height="6.66" y="13.34" fill="#138808"/><circle cx="10" cy="10" r="1.6" fill="#000080"/></svg>',
      'tl' => '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false"><rect width="20" height="10" y="0" fill="#0038A8"/><rect width="20" height="10" y="10" fill="#CE1126"/><polygon points="0,0 9,10 0,20" fill="#fff"/><circle cx="2.4" cy="10" r="1.1" fill="#FCD116"/></svg>',
      'tr' => '<svg viewBox="0 0 20 20" width="20" height="20" aria-hidden="true" focusable="false"><rect width="20" height="20" fill="#E30A17"/><circle cx="8" cy="10" r="4" fill="#fff"/><circle cx="9" cy="10" r="3.2" fill="#E30A17"/><polygon points="12.8,10 16,8.8 14,11.6 14,8.4 16,11.2" fill="#fff"/></svg>',
    ];

    $languageNames = [
      'en' => 'English',
      'de' => 'Deutsch',
      'fr' => 'Francais',
      'es' => 'Espanol',
      'it' => 'Italiano',
      'nl' => 'Nederlands',
      'pt' => 'Portugues',
      'hi' => 'Hindi',
      'tl' => 'Tagalog',
      'tr' => 'Turkce',
    ];

    $items = '';
    foreach (Language::getCodes() as $langCode) {
      if ($langCode === $activeLanguage) {
        continue;
      }

      $queryParams['l'] = $langCode;
      $query = http_build_query($queryParams);
      $href = $path . ($query !== '' ? ('?' . $query) : '');
      $label = $languageNames[$langCode] ?? Language::getDisplayName($langCode);
      $flagSvg = $flags[$langCode] ?? '';

      $items .= '<li class="pages nav_language_item">'
        . '<a class="nav_language_link" role="menuitem" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" aria-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '" title="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">'
        . '<span class="nav_language_flag">' . $flagSvg . '</span>'
        . '<span class="nav_language_name">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>'
        . '</a>'
        . '</li>';
    }

    if ($items === '') {
      return '';
    }

    $activeLabel = $languageNames[$activeLanguage] ?? Language::getDisplayName($activeLanguage);
    $activeFlagSvg = $flags[$activeLanguage] ?? '';

    return '<li class="pages nav_language_switcher" aria-label="Language switcher">'
      . '<button type="button" class="nav_language_current" aria-haspopup="menu" aria-expanded="false" data-language-toggle="true" aria-label="Language: '
      . htmlspecialchars($activeLabel, ENT_QUOTES, 'UTF-8')
      . '" title="'
      . htmlspecialchars($activeLabel, ENT_QUOTES, 'UTF-8')
      . '">'
      . '<span class="nav_language_flag">' . $activeFlagSvg . '</span>'
      . '<span class="nav_language_name">' . htmlspecialchars($activeLabel, ENT_QUOTES, 'UTF-8') . '</span>'
      . '</button>'
      . '<ul class="nav_language_list" role="menu" aria-label="Languages">'
      . $items
      . '</ul>'
      . '</li>';
  }

  /**
   * Generates an HTML <select> element populated with the user's work sites.
   *
   * Iterates over the available sites of the given type ("active", "inactive", or "all"),
   * renders each site as an <option> using a sub-template, and returns the assembled
   * <select> HTML element using the main wrapper template.
   *
   * @param string $type the type of sites to include: "active", "inactive", or "all"
   *
   * @return string the rendered HTML string for the site selection dropdown
   *
   * @todo Replace 'all' merge logic with a dedicated Sites::get_all_site_types() method when available.
   */
  public static function siteSelect(string $type = 'active'): string
  {
    $i18n = [];
    foreach (['i_SITES_LIST'] as $key) {
      $i18n[$key] = Strings::i18n($key);
    }

    $userUuid = \PayCal\Domain\User::current()->user_uuid;
    if ('all' === $type) {
      $sites = Sites::getAllSiteTypes($userUuid);
    } else {
      $sites = (array) Sites::getInstance()->GetSites($userUuid, $type);
    }

    $optionsHtml = '';
    foreach ($sites as $siteID => $siteData) {
      $siteName = is_string($siteData['site_name'] ?? null) ? $siteData['site_name'] : '';
      $optionsHtml .= Render::template('site-select-option', [
          '__SITE_ID__' => $siteID,
          '__SITE_NAME__' => $siteName,
      ]);
    }

    $siteSelectHtml = [
      '__ARIA_LABEL__' => $i18n['i_SITES_LIST'],
        '__OPTIONS_HTML__' => $optionsHtml,
    ];

    return Render::template('site-select', $siteSelectHtml);
  }

  /**
   * Render a standardized dialog shell with optional form wrapper.
   *
   * @param array<string, string|array<string, string>|null> $options dialog configuration
   *
   * Supported options:
   * - id (string, required): dialog id
   * - title (string, required): visible heading text
   * - titleId (string, optional): heading id, defaults to "{id}_title"
   * - ariaDescribedBy (string, optional): dialog aria-describedby value
   * - dialogClass (string, optional): extra dialog class names
   * - dialogAttributes (array<string, string>, optional): extra dialog attributes
   * - formAttributes (array<string, string>, optional): if set, content is wrapped in a form
   * - formInnerHtml (string, optional): HTML placed at top of form wrapper
   * - contentHtml (string, optional): inner content for .modal_content
   * - footerHtml (string, optional): inner content for .modal_footer
   * - closeLabel (string, optional): close button aria-label
   * - titleClass (string, optional): title class list
   *
   * @return string fully rendered dialog HTML
   */
  public static function dialog(array $options): string
  {
    $idRaw = $options['id'] ?? '';
    $id = trim(is_string($idRaw) ? $idRaw : '');
    if ($id === '') {
      throw new \InvalidArgumentException('Dialog id is required.');
    }

    $titleRaw = $options['title'] ?? '';
    $title = is_string($titleRaw) ? $titleRaw : '';
    if ($title === '') {
      throw new \InvalidArgumentException("Dialog '{$id}' title is required.");
    }

    $titleIdRaw = $options['titleId'] ?? ($id . '_title');
    $titleId = is_string($titleIdRaw) ? $titleIdRaw : ($id . '_title');
    $i18n = [];
    foreach (['CLOSE'] as $key) {
      $i18n[$key] = Strings::i18n($key);
    }
    $closeLabelRaw = $options['closeLabel'] ?? $i18n['CLOSE'];
    $closeLabel = is_string($closeLabelRaw) ? $closeLabelRaw : $i18n['CLOSE'];
    $titleClassRaw = $options['titleClass'] ?? 'modal_title centered';
    $titleClass = is_string($titleClassRaw) ? $titleClassRaw : 'modal_title centered';
    $dialogClassRaw = $options['dialogClass'] ?? '';
    $dialogClass = trim(is_string($dialogClassRaw) ? $dialogClassRaw : '');
    $ariaDescribedByRaw = $options['ariaDescribedBy'] ?? '';
    $ariaDescribedBy = trim(is_string($ariaDescribedByRaw) ? $ariaDescribedByRaw : '');
    $contentHtmlRaw = $options['contentHtml'] ?? '';
    $contentHtml = is_string($contentHtmlRaw) ? $contentHtmlRaw : '';
    $footerHtmlRaw = $options['footerHtml'] ?? '';
    $footerHtml = is_string($footerHtmlRaw) ? $footerHtmlRaw : '';
    $formInnerHtmlRaw = $options['formInnerHtml'] ?? '';
    $formInnerHtml = is_string($formInnerHtmlRaw) ? $formInnerHtmlRaw : '';

    $dialogAttributesRaw = $options['dialogAttributes'] ?? [];
    $formAttributesRaw = $options['formAttributes'] ?? null;

    if (!is_array($dialogAttributesRaw)) {
      throw new \InvalidArgumentException("Dialog '{$id}' dialogAttributes must be an array.");
    }
    if ($formAttributesRaw !== null && !is_array($formAttributesRaw)) {
      throw new \InvalidArgumentException("Dialog '{$id}' formAttributes must be an array when set.");
    }

    /** @var array<string, string> $dialogAttributes */
    $dialogAttributes = [];
    foreach ($dialogAttributesRaw as $k => $v) {
      $dialogAttributes[(string) $k] = (string) $v;
    }

    /** @var array<string, string>|null $formAttributes */
    $formAttributes = null;
    if (is_array($formAttributesRaw)) {
      $formAttributes = [];
      foreach ($formAttributesRaw as $k => $v) {
        $formAttributes[(string) $k] = (string) $v;
      }
    }

    /** @var array<string, string> $dialogAttrs */
    $dialogAttrs = [
      'id' => $id,
      'aria-labelledby' => $titleId,
    ];
    if ($ariaDescribedBy !== '') {
      $dialogAttrs['aria-describedby'] = $ariaDescribedBy;
    }
    if ($dialogClass !== '') {
      $dialogAttrs['class'] = $dialogClass;
    }
    $dialogAttrs = array_merge($dialogAttrs, $dialogAttributes);

    $headerHtml = '<section class="modal_header">'
      . '<button type="button" class="btn btn_close" data-dialog-close="'
      . htmlspecialchars($id, ENT_QUOTES, 'UTF-8')
      . '" aria-label="'
      . htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8')
      . '">&times;</button>'
      . '<h1 id="'
      . htmlspecialchars($titleId, ENT_QUOTES, 'UTF-8')
      . '" class="'
      . htmlspecialchars($titleClass, ENT_QUOTES, 'UTF-8')
      . '">'
      . htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
      . '</h1>'
      . '</section>';

    $bodyHtml = $headerHtml
      . '<section class="modal_content f_column">' . $contentHtml . '</section>'
      . '<section class="modal_footer">' . $footerHtml . '</section>';

    if (is_array($formAttributes)) {
      $bodyHtml = '<form'
        . self::buildHtmlAttributes($formAttributes)
        . '>'
        . $formInnerHtml
        . $bodyHtml
        . '</form>';
    }

    return '<dialog'
      . self::buildHtmlAttributes($dialogAttrs)
      . '>'
      . $bodyHtml
      . '</dialog>';
  }

  /**
   * Build an HTML attribute string from key/value pairs.
   *
   * @param array<string, string> $attributes attribute map
   *
   * @return string serialized attributes prefixed with spaces
   */
  private static function buildHtmlAttributes(array $attributes): string
  {
    $parts = [];
    foreach ($attributes as $name => $value) {
      $key = trim($name);
      if ($key === '') {
        continue;
      }
      $parts[] = sprintf(
        ' %s="%s"',
        htmlspecialchars($key, ENT_QUOTES, 'UTF-8'),
        htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
      );
    }

    return implode('', $parts);
  }

  /**
   * Generate a CSS file URL with cache busting.
   * Returns the full URL to a CSS file with version query parameter.
   *
   * @param string $name CSS file name (without .css extension, relative to /css directory)
   *
   * @return string Complete CSS file URL ready for use in link tags
   */
  public static function cssURL(string $name = ''): string
  {
    $name = trim($name, '/');
    $cssBase = 'css/';
    
    if ('' === $name) {
      return \PayCal\Domain\Config\Environment::appURL($cssBase);
    }
    
    // Page-specific CSS now uses folder-based structure: /css/{page}/
    // Each folder contains an index.php that outputs CSS with proper headers
    // Cache busting via unix epoch timestamp for aggressive revalidation
    $cacheVersion = (string) time();

    return \PayCal\Domain\Config\Environment::appURL($cssBase . $name . '/') . '?v=' . $cacheVersion;
  }

  /**
   * Generate a deferred, module script tag with cache busting and CSP nonce.
   * Creates a <script> tag with:
   * - defer attribute for non-blocking load
   * - type='module' for ES6 module support
   * - Cache version query param for busting stale scripts
   * - CSP nonce for inline script enforcement compliance.
   *
   * @param string $name Script name/path (relative to JS_URL directory)
   *
   * @return string Complete HTML script tag ready for output
   */
  public static function jsScript(string $name = '-'): string
  {
    $name = trim($name, '/');
    $jsBase = 'js';
    if ($name === '-') {
      $path = $jsBase . '/';
    } else {
      // If the name matches a PHP-backed folder, do NOT append .js
      if (in_array($name, ['encryption', 'calendar', 'earnings', 'sites', 'organizations', 'settings', 'register', 'signin', 'admin', 'help', 'core', 'datagrid', 'payperiods', 'contact', 'tests', 'dev'])) {
        $path = $jsBase . '/' . $name . '/';
      } else {
        // Otherwise, treat as a JS file
        if (!str_ends_with($name, '.js')) {
          $name .= '.js';
        }
        $path = $jsBase . '/' . $name;
      }
    }
    $cacheVersion = \PayCal\Domain\Config\Environment::appVersion();
    $cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
    $cspNonceCandidate = is_string($cspNonceRaw) ? trim($cspNonceRaw) : '';
    $isValidNonce = $cspNonceCandidate !== ''
      && strlen($cspNonceCandidate) >= 16
      && preg_match('/^[A-Za-z0-9+\/_\-]+=*$/', $cspNonceCandidate) === 1;
    $cspNonce = $isValidNonce ? $cspNonceCandidate : User::nonce();
    return '    <script type="module" src="'
      . \PayCal\Domain\Config\Environment::appURL($path)
      . '?v=' . $cacheVersion
      . '" nonce="' . $cspNonce . '"></script>' . PHP_EOL;
  }

  /**
   * Build an SRI hash for a static asset under html/.
   * Returns empty string if the file is missing or unreadable.
   */
  public static function sriHash(string $relativePath): string
  {
    $normalizedPath = ltrim(trim($relativePath), '/');
    if ($normalizedPath === '') {
      return '';
    }

    $fullPath = rtrim(\PayCal\Domain\Config\Environment::appHome(), '/') . '/html/' . $normalizedPath;
    if (!is_file($fullPath)) {
      return '';
    }

    $binaryHash = hash_file('sha384', $fullPath, true);
    if ($binaryHash === false) {
      return '';
    }

    return 'sha384-' . base64_encode($binaryHash);
  }

  /**
   * Render SRI attributes suitable for insertion into a script/link tag.
   */
  public static function sriAttribute(string $relativePath): string
  {
    $hash = self::sriHash($relativePath);
    if ($hash === '') {
      throw new \RuntimeException('Missing or unreadable static asset for SRI: ' . $relativePath);
    }

    return ' integrity="' . htmlspecialchars($hash, ENT_QUOTES, 'UTF-8') . '" crossorigin="anonymous"';
  }
}
