<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';

// Prevent included theme/token files from emitting header warnings into CSS output.
if (ob_get_level() === 0) {
  ob_start();
}

if (headers_sent() === false) {
  header('Content-type: text/css');
}
// This endpoint is user-preference aware (theme, density, text scale), so cache by cookie context.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, pragma: no-cache, expires: -1');
header('Vary: Cookie');

$user = User::current();

$textSizingRaw = strtolower(trim((string) ($user->text ?? UserPreferenceDefaults::DEFAULT_TEXT)));
$densityRaw = strtolower(trim((string) ($user->density ?? UserPreferenceDefaults::DEFAULT_DENSITY)));

$normalizeAdjustment = static function (string $value, array $legacyMap): int {
  if ($value === '') {
    return 0;
  }

  if (isset($legacyMap[$value])) {
    return $legacyMap[$value];
  }

  if (preg_match('/^-?\d+$/', $value) === 1) {
    return max(-5, min(5, (int) $value));
  }

  return 0;
};

$resolveCssLength = static function (string $value, array $semanticMap, string $default): string {
  if (isset($semanticMap[$value])) {
    return $semanticMap[$value];
  }

  if (preg_match('/^-?(?:\\d+|\\d*\\.\\d+)(px|rem|em|%|vh|vw|dvh|dvw)$/', $value) === 1) {
    return $value;
  }

  if (preg_match('/^-?(?:\\d+|\\d*\\.\\d+)$/', $value) === 1) {
    return $value.'rem';
  }

  return $default;
};

$textAdjustment = $normalizeAdjustment($textSizingRaw, [
  'small' => -2,
  'medium' => 0,
  'large' => 2,
  'x-large' => 5,
]);

$densityAdjustment = $normalizeAdjustment($densityRaw, [
  'tight' => -5,
  'compact' => -5,
  'comfy' => 0,
  'spacious' => 5,
]);

$textOverrideRaw = trim((string) \PayCal\Domain\Config\SystemConfig::get(\PayCal\Domain\Config\SystemConfig::FONT_SIZE_ADJUSTMENT_OVERRIDE));
$densityOverrideRaw = trim((string) \PayCal\Domain\Config\SystemConfig::get(\PayCal\Domain\Config\SystemConfig::DENSITY_ADJUSTMENT_OVERRIDE));
$textOverride = $normalizeAdjustment(strtolower($textOverrideRaw), []);
$densityOverride = $normalizeAdjustment(strtolower($densityOverrideRaw), []);

$combinedTextAdjustment = max(-5, min(5, $textAdjustment + $textOverride));
$combinedDensityAdjustment = max(-5, min(5, $densityAdjustment + $densityOverride));

$resolveCssLengthOrNumber = static function (mixed $raw, string $default): string {
  if (!is_scalar($raw) && $raw !== null) {
    return $default;
  }

  $value = strtolower(trim((string) ($raw ?? '')));
  if ($value === '') {
    return $default;
  }

  if (preg_match('/^-?(?:\\d+|\\d*\\.\\d+)$/', $value) === 1) {
    return $value;
  }

  if (preg_match('/^-?(?:\\d+|\\d*\\.\\d+)(px|rem|em|%|vh|vw|dvh|dvw)$/', $value) === 1) {
    return $value;
  }

  return $default;
};

$resolveFontWeight = static function (mixed $raw, string $default): string {
  if (!is_scalar($raw) && $raw !== null) {
    return $default;
  }

  $value = trim((string) ($raw ?? ''));
  if (preg_match('/^[1-9]00$/', $value) === 1) {
    return $value;
  }

  if (in_array(strtolower($value), ['normal', 'bold', 'bolder', 'lighter'], true)) {
    return strtolower($value);
  }

  return $default;
};

$sanitizeFontFamily = static function (mixed $raw, string $default): string {
  if (!is_scalar($raw) && $raw !== null) {
    return $default;
  }

  $value = trim((string) ($raw ?? ''));
  if ($value === '' || strlen($value) > 220) {
    return $default;
  }

  if (preg_match('/^[a-zA-Z0-9\",\'\-\s]+$/', $value) !== 1) {
    return $default;
  }

  return $value;
};

$lineHeight = $resolveCssLengthOrNumber(\PayCal\Domain\Config\SystemConfig::get('font_line_height'), '1.5');
$fontXs = $resolveCssLength( strtolower(trim((string) \PayCal\Domain\Config\SystemConfig::get('font_size_xs'))), [], '0.50rem');
$fontSm = $resolveCssLength( strtolower(trim((string) \PayCal\Domain\Config\SystemConfig::get('font_size_sm'))), [], '1.00rem');
$fontMd = $resolveCssLength( strtolower(trim((string) \PayCal\Domain\Config\SystemConfig::get('font_size_md'))), [], '1.10rem');
$fontLg = $resolveCssLength( strtolower(trim((string) \PayCal\Domain\Config\SystemConfig::get('font_size_lg'))), [], '1.30rem');
$fontXl = $resolveCssLength( strtolower(trim((string) \PayCal\Domain\Config\SystemConfig::get('font_size_xl'))), [], '1.70rem');
$fontWeight = $resolveFontWeight(\PayCal\Domain\Config\SystemConfig::get('font_weight_base'), '500');
$sansSerif = $sanitizeFontFamily(
  \PayCal\Domain\Config\SystemConfig::get('font_family_sans'),
  'Roboto, "Open Sans", Lato, Nunito, Verdana, Helvetica, Arial, sans-serif'
);
$serif = $sanitizeFontFamily(
  \PayCal\Domain\Config\SystemConfig::get('font_family_serif'),
  'Merriweather, Garamond, "Times New Roman", serif'
);
$monospace = $sanitizeFontFamily(
  \PayCal\Domain\Config\SystemConfig::get('font_family_monospace'),
  '"Courier New", Courier, monospace'
);

$navBarStickiness = "static";

?>@charset "UTF-8";

/*
 * Copyright <?php echo date('Y'); ?> PayCal
 * Common CSS - Shared across all pages
 */

/* VARIABLES */
:root {
  --text-base:                           1.125rem;
  --text-adjustment-px:                  <?php echo $combinedTextAdjustment; ?>px;
  --text:                                clamp(0.75rem, calc(var(--text-base) + var(--text-adjustment-px)), 1.5rem);
  --line-height:                         <?php echo $lineHeight; ?>;
  --density-base:                        1rem;
  --density-adjustment-px:               <?php echo $combinedDensityAdjustment; ?>px;
  --spacing:                             clamp(0.60rem, calc(var(--density-base) + var(--density-adjustment-px)), 1.5rem);
  --font-xs:                             <?php echo $fontXs; ?>;
  --font-sm:                             max(1rem, <?php echo $fontSm; ?>);
  --font-md:                             <?php echo $fontMd; ?>;
  --font-lg:                             <?php echo $fontLg; ?>;
  --font-xl:                             <?php echo $fontXl; ?>;
  --font-weight:                         <?php echo $fontWeight; ?>;
  --sans-serif:                          <?php echo $sansSerif; ?>;
  --serif:                               <?php echo $serif; ?>;
  --monospace:                           <?php echo $monospace; ?>;
  --mar:                                 var(--spacing);
  --mar-xs:                              calc(var(--mar) / 4);
  --mar-sm:                              calc(var(--mar) / 2);
  --mar-md:                              var(--mar);
  --mar-lg:                              calc(var(--mar) * 2);
  --pad:                                 var(--spacing);
  --pad-xs:                              calc(var(--pad) / 4);
  --pad-sm:                              calc(var(--pad) / 2);
  --pad-md:                              var(--pad);
  --pad-lg:                              calc(var(--pad) * 2);
  --gap-xs:                              var(--pad-xs);
  --gap-sm:                              var(--pad-sm);
  --gap-md:                              var(--pad-md);
  --gap-lg:                              var(--pad-lg);
  --chrome-height:                       4rem;
  --profile-menu-width:                  8rem;
  --blur-size:                           2px;
  --dialog-backdrop-blur:                var(--blur-size);
  --dialog-backdrop-bg:                  rgba(0, 0, 0, 0.60);
  --dialog-margin:                       0px;
  --dialog-padding:                      calc(var(--pad-sm)) calc(var(--pad-md));
  --dialog-spread:                       100dvh;
  --dialog-shadow:                       rgba(0, 0, 0, 0.90);
  --dialog-frame-color:                  var(--dialog-border, var(--panel-border-color, var(--color-text)));
  --dialog-edge-top-size:                8px;
  --dialog-edge-right-size:              8px;
  --dialog-edge-bottom-size:             8px;
  --dialog-edge-left-size:               8px;
  --dialog-max-width:                    52rem;
  --dialog-max-height:                   84dvh;
  --work-back:                           rgba(  8,  16,  12, 1.00);
  -webkit-text-size-adjust:              100%;
  -webkit-user-select:                   text;
  --svg-icon-width:                      16px;
  --svg-icon-height:                     16px;
  --zero-transition:                     00.05s;
  --short-transition:                    00.10s;
  --medium-transition:                   03.00s;
  --long-transition:                     05.00s;
  --header-position:                     <?php echo $navBarStickiness; ?>;
}

@font-face {
  font-family: 'Atkinson Hyperlegible';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url('/fonts/atkinson-hyperlegible-400.ttf') format('truetype');
}

@font-face {
  font-family: 'Atkinson Hyperlegible';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url('/fonts/atkinson-hyperlegible-700.ttf') format('truetype');
}

@font-face {
  font-family: 'Lexend';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url('/fonts/lexend-400.ttf') format('truetype');
}

@font-face {
  font-family: 'Lexend';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url('/fonts/lexend-700.ttf') format('truetype');
}

@font-face {
  font-family: 'OpenDyslexic';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url('/fonts/open-dyslexic-400.woff2') format('woff2');
}

@font-face {
  font-family: 'OpenDyslexic';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url('/fonts/open-dyslexic-700.woff2') format('woff2');
}

@font-face {
  font-family: 'Atkinson Hyperlegible Mono';
  font-style: normal;
  font-weight: 400;
  font-display: swap;
  src: url('/fonts/atkinson-hyperlegible-mono-400.ttf') format('truetype');
}

@font-face {
  font-family: 'Atkinson Hyperlegible Mono';
  font-style: normal;
  font-weight: 700;
  font-display: swap;
  src: url('/fonts/atkinson-hyperlegible-mono-700.ttf') format('truetype');
}

html[data-a11y-dyslexia-typography="on"] {
  --sans-serif: "Atkinson Hyperlegible", "Lexend", "OpenDyslexic", "Segoe UI", Verdana, Arial, sans-serif;
  --monospace: "Atkinson Hyperlegible Mono", Menlo, Monaco, "Courier New", Courier, monospace;
}

html[data-a11y-dyslexia-typography="on"] body,
html[data-a11y-dyslexia-typography="on"] button,
html[data-a11y-dyslexia-typography="on"] input,
html[data-a11y-dyslexia-typography="on"] select,
html[data-a11y-dyslexia-typography="on"] textarea,
html[data-a11y-dyslexia-typography="on"] label,
html[data-a11y-dyslexia-typography="on"] p,
html[data-a11y-dyslexia-typography="on"] li,
html[data-a11y-dyslexia-typography="on"] dd,
html[data-a11y-dyslexia-typography="on"] dt,
html[data-a11y-dyslexia-typography="on"] a {
  line-height: 1.6;
  letter-spacing: 0.12em;
  word-spacing: 0.16em;
}

<?php

$allowedThemes = [
  "macos", "macos9", "system8", "system7",
  "linux", "mint", "fedora", "debian",
  "beos", "zeta", "haiku",
  "win10", "win95", "win98", "winxp",
  "blade_runner", "space_odyssey", "tron", "fifth_element", "dune", "matrix", "alien", "akira",
  "star_trek", "star_wars", "paycal_blue", "paycal_black", "paycal_red", "paycal_green", "paycal_white", "paycal", "retro", "bluejeans", "garden", "arcade"
];

$allowedVariants = ["light", "dark"];
$defaultTheme    = "paycal_blue";
$defaultVariant  = "dark";

$base = in_array($user->theme ?? '', $allowedThemes, true) ? $user->theme : $defaultTheme;
// Back-compat: "paycal" was renamed to "paycal_black"; redirect silently.
if ($base === 'paycal') {
  $base = 'paycal_black';
}
$variant = $defaultVariant;
if (isset($user->variant) && in_array($user->variant, $allowedVariants, true)) {
  $variant = $user->variant;
}
$resolvedTheme = "{$base}_{$variant}";

require_once __DIR__ . "/../tokens/index.php";
require_once __DIR__ . "/../{$resolvedTheme}/index.php";

?>

/* BASE HTML */
*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
  border: 0;
}

html { text-rendering: optimizelegibility; scrollbar-gutter: stable; }

html, body {
  width: 100%;
  max-width: 100%;
  min-height: 100%;
  margin: 0;
  padding: 0;
  font-family: var(--sans-serif);
  font-size: var(--text, 1.125rem);
  background-color: var(--color-bg);
  color: var(--color-text);
  overflow-x: hidden;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

body { font-weight: var(--font-weight); }

/* Keep html as the primary scroll container to avoid browser-specific root scroll locks. */
html { overflow-y: auto; }
body { overflow-y: visible; }

body:has(dialog[open]) { overflow: hidden; }
*::-webkit-scrollbar { width: 0; height: 0; }
footer, header, main, nav { display: block; }

#page_header {
  display: block;
  position: var(--header-position);
  top: 0;
  left: 0;
  right: 0;
  z-index: 10000;
  width: 100%;
  height: 3rem;
  margin: 0 0 3rem 0;
  padding: 0;
  background-color: var(--panel-head-bg);
  color: var(--panel-head-text);
}

#page_header nav.nav_menu li {
  flex: 1;
  text-align: center;
  height: 3rem;
  padding: 0;
  z-index: 1000;
}

#page_header nav.nav_menu ul li.pages   { min-width: 12%; }
#speaker_icon, [data-help-trigger="true"] { border: none; }

#main {
  width: 100%;
  padding: var(--pad-lg);
  margin: 3rem 0 0 0;
  background-color: var(--color-bg);
  color: var(--color-text);
}

#page_footer {
  margin: 0;
  padding: clamp(0.6rem, 1.3vw, 1rem);
}

.ledge {
  position: relative;
  border-top: 3px solid var(--panel-border);
  box-shadow: inset 0 18px 24px -10px rgba(0, 0, 0, 0.9), 0 -12px 20px -8px rgba(0, 0, 0, 0.85);
}

a {
  padding: var(--pad-xs);
  line-height: var(--line-height);
  color: var(--color-text);
  text-decoration: none;
}

a:visited, a:active {
  color: var(--color-text);
  text-decoration: none;
}

a:hover, a:active {
  background-color: var(--color-text);
  color: var(--color-text-inverse);
  transition: background-color var(--short-transition) ease;
}

a em {
  border-bottom: 1px dotted var(--color-text);
  font-style: normal;
  font-weight: 500;
}

a mark {
  border-bottom: 1px dotted var(--color-text);
  font-style: normal;
  font-weight: 500;
  background-color: rgba(0, 0, 0, 0.97);
}

a:hover .nav_icon svg path,
a:hover .nav_icon svg circle,
a:hover .nav_icon svg rect,
a:hover:not(.nav_language_link) svg path,
a:hover:not(.nav_language_link) svg circle,
a:hover:not(.nav_language_link) svg rect {
  fill: var(--color-text-inverse);
  transition: background-color var(--short-transition) ease;
}

a:hover .nav_icon svg * {
  fill: var(--color-text-inverse);
}

abbr[title] {
  border-bottom: none;
  text-decoration: none;
}

h1, h2, h3, h4, h5, h6 {
  margin: var(--mar-md) 0;
  font-family: var(--serif);
  font-weight: 700;
  color: var(--theme-signature-color, var(--heading-accent-color, var(--color-text)));
  letter-spacing: 0.1rem;
}

/* Mobile-first responsive heading scaling */
h1 { font-size: clamp(1.25rem, 3.5vw, 2.5rem); }
h2 { font-size: clamp(1.1rem, 2.8vw, 2rem); }
h3 { font-size: clamp(1rem, 2.4vw, 1.75rem); }
h4 { font-size: clamp(0.95rem, 2vw, 1.5rem); }
h5 { font-size: clamp(0.9rem, 1.8vw, 1.25rem); }
h6 { font-size: 1rem; }

p {
  font-family: var(--sans-serif);
  font-size: 1rem;
  font-weight: var(--font-weight);
}

kbd {
  display: inline-block;
  margin: 0;
  padding: 0;
  border: var(--border-size) outset var(--btn-selected-fore);
  background-color: var(--btn-selected-back);
  color: var(--btn-selected-fore);
}

ul:where([role="list"]) {
  list-style-type: "";
}

.svg-icon {
  width: var(--svg-icon-width);
  height: var(--svg-icon-height);
}

svg {
  display: inline-block;
}


/* NAVIGATION */
.skip_link {
  position: absolute;
  top: -100px;
  left: 0;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(1px 1px 1px 1px);
  clip: rect(1px, 1px, 1px, 1px);
  white-space: nowrap;
}

.skip_link:focus {
  position: fixed;
  top: 0;
  left: 0;
  z-index: 99999;
  width: auto;
  height: auto;
  clip: auto;
  overflow: auto;
  padding: 0.75rem 1.25rem;
  border-bottom-right-radius: var(--border-radius, 4px);
  background-color: var(--color-primary, #0073e6);
  color: var(--color-on-primary, #fff);
  font-size: var(--font-md, 1rem);
  font-weight: bold;
  text-decoration: none;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
  outline: 3px solid transparent;
}

.nav_menu {
  display: flex;
  width: 100%;
  margin: 0;
  align-items: center;
  height: 3rem;
  overflow: visible;
}

.nav_menu ul {
  display: flex;
  flex-grow: 1;
  height: 3rem;
  margin: 0;
  padding: 0;
  overflow: visible;
}

.nav_menu h1, .nav_menu div {
  flex-grow: 1;
  align-items: center;
  height: 3rem;
  padding: var(--pad-md);
}

.nav_menu a {
  display: flex;
  flex-grow: 1;
  justify-content: center;
  align-items: center;
  padding: 0;
  text-align: center;
  color: var(--nav-menu-fore);
  background-color: var(--nav-menu-back);
  text-decoration: none;
  letter-spacing: 0.05rem;
}

/* Reserve hover border space in footer nav links to prevent 1-2px layout shift. */
#page_footer .nav_menu li a {
  border: var(--border-size) double transparent;
  border-radius: var(--border-radius);
}

.nav_menu a:active { border-top: calc(var(--border-size)) inset var(--panel-border); }

.nav_menu li:hover a {
  border: var(--border-size) double var(--color-text);
  border-top-left-radius: var(--border-radius);
  border-top-right-radius: var(--border-radius);
  background-color: var(--color-text);
  color: var(--color-text-inverse);
  transition: background-color var(--short-transition) ease;
}

#page_footer .nav_menu li:hover a {
  border-radius: var(--border-radius);
}

.nav_menu li.active a {
  border-top: var(--border-size) inset var(--panel-border);
  border-left: calc(var(--border-size)) inset var(--panel-border);
  border-right: calc(var(--border-size)) inset var(--panel-border);
  border-radius: 0;
  border-top-left-radius: var(--border-radius);
  border-top-right-radius: var(--border-radius);
  background-color: var(--color-bg);
  color: var(--color-text);
  font-weight: 700;
}

span.active svg { fill: var(--color-text); stroke: var(--color-text); }

.nav_menu li.active:hover a, .nav_menu li:focus a {
  background-color: var(--color-text);
  color: var(--color-text-inverse);
  transition: background-color var(--short-transition) ease;
}

header>.nav_menu>ul>li:not(.active) { border-bottom: 1px solid var(--panel-border); }

.nav_icon {
  font-size: var(--font-md);
  color: var(--color-text);
  border: 0;
}

.nav_icon svg {
  shape-rendering: geometricPrecision;
}

.nav_icon svg path,
.nav_icon svg circle,
.nav_icon svg rect,
a:not(.nav_language_link) svg path,
a:not(.nav_language_link) svg circle,
a:not(.nav_language_link) svg rect {
  fill: var(--color-text);
}


/* SYSTEM TRAY */
#system_tray {
  display: flex;
  justify-content: center;
  background-color: var(--system-tray-back);
}

.tray_widget {
  position: relative;
  align-content: center;
  justify-content: center;
  background-color: transparent;
}

.tray_widget:hover, .tray_widget:focus {
  cursor: pointer;
  background-color: var(--color-text);
  color: var(--color-text-inverse);
  transition: background-color var(--short-transition) ease;
}

#current_time.is-countdown {
  color: var(--color-primary);
}

#current_time.is-expiring {
  color: var(--countdown-warning);
}

#current_time.is-expired {
  color: var(--countdown-expired);
}

.tray_time_popover {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  z-index: 11050;
  min-width: 16rem;
  max-width: 22rem;
  padding: 0.7rem 0.8rem;
  border: 1px solid var(--panel-border);
  border-radius: 10px;
  background: color-mix(in srgb, var(--panel-bg) 88%, transparent);
  backdrop-filter: blur(8px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.3);
  color: var(--panel-text);
  opacity: 0;
  transform: translateY(4px) scale(0.98);
  pointer-events: none;
  transition: opacity 120ms ease, transform 120ms ease;
}

.tray_time_popover.is-open {
  opacity: 1;
  transform: translateY(0) scale(1);
}

.tray_time_popover_title {
  margin: 0 0 0.35rem 0;
  font-weight: 700;
  font-size: var(--font-sm);
  color: var(--panel-text);
}

.tray_time_popover_row {
  display: flex;
  justify-content: space-between;
  gap: 0.8rem;
  margin-top: 0.2rem;
  font-size: var(--font-sm);
}

.tray_time_popover_row strong {
  font-weight: 600;
}

.audio_enabled { display: block; color: var(--color-fore); }
#capslock_icon, #speaker_icon { display: flex; }
#capslock_icon.hidden, #speaker_icon.hidden { visibility: hidden; }

.initials {
  display: inline-block;
  width: 32px;
  height: 32px;
  font-family: var(--sans-serif);
  font-size: 16px;
  font-weight: bold;
  text-align: center;
  line-height: 32px;
  color: var(--panel-head-text);
}

.nav_menu ul li {
  display: flex;
  flex: 1;
  list-style-type: none;
}

.nav_menu ul li button {
  flex: 1;
}


/* DIALOGS */
dialog {
  display: none;
  position: fixed;
  top: max(1rem, 6dvh);
  left: 50%;
  transform: translateX(-50%);
  width: min(calc(100vw - 1rem), var(--dialog-max-width));
  max-width: calc(100vw - 1rem);
  max-height: var(--dialog-max-height);
  margin: 0;
  padding: 0;
  grid-template-columns: var(--dialog-edge-left-size) minmax(0, 1fr) var(--dialog-edge-right-size);
  grid-template-rows: var(--dialog-edge-top-size) auto auto auto var(--dialog-edge-bottom-size);
  grid-auto-rows: min-content;
  justify-items: stretch;
  align-items: stretch;
  border: 0;
  border-radius: var(--radius-dialog, var(--border-radius));
  background-color: var(--dialog-frame-color);
  color: var(--dialog-text);
  font-size: var(--font-md);
  box-shadow: 0 0.25rem 0.25rem rgba(0, 0, 0, 0.75);
  overflow-x: hidden;
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

dialog > * {
  grid-column: 2;
}

/* Keep frame edge rows reserved; place dialog content in rows 2-4. */
dialog > .modal_header {
  grid-row: 2;
}

dialog > .modal_content {
  grid-row: 3;
}

dialog > .modal_footer {
  grid-row: 4;
}

/* Some dialogs wrap sections in a form; map that form to content rows. */
dialog > form {
  grid-column: 2;
  grid-row: 2 / span 3;
  display: grid;
  grid-template-rows: auto minmax(0, 1fr) auto;
  min-height: 0;
}

dialog > form > .modal_header {
  grid-row: 1;
}

dialog > form > .modal_content {
  grid-row: 2;
}

dialog > form > .modal_footer {
  grid-row: 3;
}

dialog[open] { display: grid; }
.dialog_wide {
  width: min(calc(100vw - 1rem), 80vw);
  max-width: calc(100vw - 1rem);
  left: 50%;
  transform: translateX(-50%);
}

#modal_help {
  --dialog-max-width: 64rem;
}

#modal_help .modal_content {
  gap: var(--gap-md);
  align-items: stretch;
}

#modal_help .keyboard_shortcuts_layout {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(22rem, 1fr));
  gap: var(--gap-md);
}

#modal_help .keyboard_shortcuts_panel {
  min-width: 0;
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-panel, var(--border-radius));
  background: color-mix(in srgb, var(--dialog-back) 86%, var(--panel-bg) 14%);
  overflow: hidden;
}

#modal_help .keyboard_shortcuts_panel_title {
  margin: 0;
  padding: 0.9rem 1rem;
  font-size: 1.05rem;
  border-bottom: 1px solid var(--panel-border);
  background: color-mix(in srgb, var(--panel-bg) 72%, var(--dialog-back) 28%);
}

#modal_help .keyboard_shortcuts_table {
  display: grid;
}

#modal_help .keyboard_shortcuts_row {
  display: grid;
  grid-template-columns: minmax(12rem, 1fr) auto;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid color-mix(in srgb, var(--panel-border) 68%, transparent);
}

#modal_help .keyboard_shortcuts_row:last-child {
  border-bottom: 0;
}

#modal_help .keyboard_shortcuts_label {
  font-weight: 600;
  min-width: 0;
  white-space: normal;
  word-break: normal;
  overflow-wrap: break-word;
}

#modal_help .keyboard_shortcuts_keys {
  display: inline-flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.35rem;
  text-align: right;
  min-width: 0;
  max-width: 100%;
  flex-wrap: wrap;
  color: var(--text-muted, var(--fore_soft));
}

#modal_help .keyboard_shortcuts_keys:focus-visible {
  outline: 2px solid var(--focus, var(--color-focus-ring, #0096d6));
  outline-offset: 2px;
  border-radius: 0.45rem;
}

#modal_help .keyboard_shortcuts_keys kbd {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.4rem;
  padding: 0.15rem 0.4rem;
  border-radius: 0.38rem;
  border: 1px solid color-mix(in srgb, var(--panel-border) 88%, transparent 12%);
  background: color-mix(in srgb, var(--dialog-back) 78%, var(--panel-bg) 22%);
  color: var(--text-muted, var(--fore_soft));
  font-size: 0.84rem;
  font-weight: 400;
  line-height: 1.2;
}

#modal_help .keyboard_shortcuts_or,
#modal_help .keyboard_shortcuts_sep {
  color: var(--text-muted, var(--fore_soft));
  font-size: 0.84rem;
}

#modal_help .keyboard_shortcuts_help_text {
  max-width: 22rem;
  text-align: right;
  font-size: 0.88rem;
  line-height: 1.35;
}

#modal_help .keyboard_shortcuts_safeguards {
  display: block;
  grid-column: 1 / -1;
  width: 100%;
  margin-top: var(--gap-sm);
  border-top: 1px solid color-mix(in srgb, var(--panel-border) 68%, transparent);
}

#modal_help .keyboard_shortcuts_safeguards .keyboard_shortcuts_panel_title {
  margin-bottom: 0.4rem;
  border: 0;
  background: transparent;
  padding: 0;
}

#modal_help .keyboard_shortcuts_safeguards .keyboard_shortcuts_help_text {
  margin: 0;
  max-width: none;
  text-align: left;
}

#modal_help .modal_footer {
  justify-content: center;
}

#modal_help .modal_footer .modal_controls {
  align-items: center;
  gap: var(--gap-sm);
}

#modal_help .modal_footer form {
  margin: 0;
}

#modal_help .modal_footer p {
  margin: 0;
  max-width: 32rem;
}

dialog::backdrop {
  background: var(--dialog-backdrop-bg);
  backdrop-filter: blur(var(--dialog-backdrop-blur));
  overscroll-behavior: contain;
}

.modal_header {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
  align-items: center;
  column-gap: 0.65rem;
  margin: 0 0 var(--mar-sm) 0;
  padding: var(--pad-sm) var(--pad-md);
  min-height: 2.75rem;
  font-size: var(--font-lg);
  background-color: var(--modal-head-back);
  color: var(--modal-head-fore);
}

.modal_header h1 { color: var(--modal-head-fore); }

.modal_title {
  margin: 0;
  padding: 0;
  font-size: var(--font-lg);
  grid-column: 1;
  justify-self: start;
  width: auto;
  padding-left: var(--pad-md);
  text-align: left !important;
}

.modal_header .modal_tab_list,
.modal_header .tablist_container,
.modal_header [role="tablist"] {
  grid-column: 2;
  justify-self: center;
  display: inline-flex;
  align-items: center;
  gap: 0;
  flex-wrap: nowrap;
  margin: 0;
  padding: 0;
  border: var(--border-size) solid var(--panel-border);
  border-bottom: 0;
  border-radius: var(--border-radius) var(--border-radius) 0 0;
  background-color: var(--panel-bg);
  box-shadow: none;
  overflow: hidden;
}

.modal_header .modal_header_spacer {
  grid-column: 3;
  justify-self: stretch;
  min-width: 0;
}

.modal_header [role="tab"] {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--pad-sm) var(--pad-md);
  border: var(--border-size) solid transparent;
  border-bottom: 0;
  background: var(--modal-head-back);
  color: var(--text-muted, #999);
  font-size: var(--font-md);
  font-weight: 700;
  letter-spacing: 0.03rem;
  border-radius: 0;
  white-space: nowrap;
  min-width: max-content;
  cursor: pointer;
  transition: background-color var(--short-transition) ease, color var(--short-transition) ease, border-color var(--short-transition) ease;
}

.modal_header [role="tab"][aria-selected="true"] {
  border-color: var(--panel-border);
  background-color: var(--panel-bg);
  color: var(--panel-text, var(--color-text, #fff));
  border-bottom-color: var(--panel-bg);
}

.modal_header [role="tab"]:hover {
  background-color: color-mix(in srgb, var(--modal-head-back) 70%, var(--panel-bg) 30%);
  color: var(--panel-text, var(--color-text, #fff));
}

.modal_close {
  margin: 0;
  padding: var(--pad-xs) var(--pad-sm);
  font-size: var(--font-xl);
  background-color: transparent;
  cursor: pointer;
}

.modal_header .btn_close,
.modal_header .modal_close,
.modal_header [data-dialog-close] {
  grid-column: 3;
  justify-self: end;
  position: static !important;
  left: auto !important;
  top: auto !important;
  transform: none !important;
  width: 32px;
  height: 32px;
  min-width: 32px;
  padding: 0;
  border-radius: 16px;
  border: 1px solid transparent;
  background-color: var(--button-bg);
  color: var(--button-text);
  font-size: 1rem;
  font-weight: 700;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
}

.modal_header .btn_close:hover,
.modal_header .btn_close:focus,
.modal_header .btn_close:active,
.modal_header .modal_close:hover,
.modal_header .modal_close:focus,
.modal_header .modal_close:active,
.modal_header [data-dialog-close]:hover,
.modal_header [data-dialog-close]:focus,
.modal_header [data-dialog-close]:active {
  background-color: var(--color-warning);
  color: var(--color-on-warning, #1b1300);
  border-color: transparent;
  filter: none;
}

.modal_close:hover { transition: background-color var(--short-transition) ease; }

.modal_content {
  display: flex;
  padding: var(--pad-md);
  background-color: var(--dialog-back);
  color: var(--dialog-fore);
}

.modal_content h2 {
  color: var(--panel-text);
}

.modal_footer .btn,
.modal_footer button:not(#profile_button) {
  font-size: var(--font-sm);
}

.site_create_fields {
  display: flex;
  flex-wrap: nowrap;
  gap: var(--gap-md);
  align-items: flex-end;
}

.site_create_fields .form_group {
  display: flex;
  flex: 1 1 0;
  flex-direction: column;
  min-width: 0;
}

.modal_footer {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: var(--gap-md);
  padding: var(--pad-md);
  background-color: var(--dialog-back);
  border-top: var(--border-size) solid var(--border-inset-color);
}

.section_header {
  flex-grow: 1;
  width: 100%;
  margin: var(--mar-md);
}

#change_email_status_message {
  color: var(--dialog-fore);
}


/* CONTROLS */
label {
  font-size: var(--text, 1.125rem);
  cursor: pointer;
}

input, select, button {
  display: inline-block;
  border: var(--border-size) double transparent;
  border-color: var(--button-border);
  border-radius: var(--radius-control, var(--border-radius));
  font-size: var(--text, 1.125rem);
  font-weight: var(--font-weight);
  line-height: var(--line-height);
  text-align: center;
  white-space: nowrap;
  background-color: var(--button-bg);
  color: var(--button-text);
  cursor: pointer;
  -webkit-user-select: text;
}

input, select, textarea {
  margin: var(--mar-sm) 0;
  padding: var(--pad-sm);
  box-shadow: inset 0 0.1rem 0.1rem 1px rgba(0, 0, 0, 0.50);
}

.btn, button {
  padding: var(--pad-sm);
  flex-grow: 1;
  background-image: none;
  border: var(--border-size) double transparent;
  border-color: var(--button-border);
  border-radius: var(--radius-button, var(--border-radius));
  transition: background-color var(--zero-transition) linear, color var(--zero-transition) linear, border-color var(--zero-transition) linear;
}

.btn:hover {
  border-color: var(--button-border-active);
  background-color: var(--button-bg-hover);
  color: var(--button-text-hover, var(--button-text));
  text-decoration: none;
}

.btn:focus-visible {
  border-color: var(--button-border-active);
  outline: var(--border-size) solid var(--color-focus-ring);
  outline-offset: 2px;
}

.btn:active {
  border-color: var(--button-border-active);
  background-color: var(--button-bg-active);
  color: var(--button-text-active, var(--button-text));
  text-decoration: none;
}

.btn_primary {
  background-color: var(--button-primary-bg);
  color: var(--button-primary-text);
}

.btn_primary:hover {
  background-color: var(--button-primary-bg-hover, var(--button-primary-bg));
  color: var(--button-primary-text-hover, var(--button-primary-text));
}

.btn_primary:active {
  background-color: var(--button-primary-bg-active, var(--button-primary-bg));
  color: var(--button-primary-text-active, var(--button-primary-text));
}

input[type=text], input[type=password],input[type=email], input[type=tel], input[type=date] {
  width: 100%;
  background-color: var(--button-bg);
  cursor: text;
}

select {
  width: 100%;
}

input:hover,
select:hover,
input:focus,
select:focus,
input:active,
select:active,
.btn_primary:focus-visible {
  border-color: var(--button-border-active);
}

input:disabled {
  border: var(--border-size) double transparent;
  border-color: var(--button-border);
  background-color: var(--input-bg, var(--button-bg));
  color: var(--color-text-disabled, var(--button-text));
  cursor: not-allowed;
  opacity: 1;
  box-shadow: none;
}

.btn_cancel {
  border-color: transparent;
  color: var(--button-secondary-text);
}

.btn_cancel:hover, .btn_cancel:focus,.btn_cancel:active {
  background-color: var(--color-warning);
  transition: background-color var(--short-transition) ease;
}

.btn_close {
  margin: 0;
  padding: var(--pad-xs) var(--pad-sm);
  line-height: 1;
}

.btn_close:hover, .btn_close:focus, .btn_close:active {
  background-color: var(--panel-text);
  color: var(--panel-bg);
}

.btn_delete {
  border: none;
  background-color: transparent;
  color: var(--button-danger-text);
}

.btn_delete:hover, .btn_delete:focus,.btn_delete:active {
  background-color: transparent;
  color: var(--button-danger-text);
  transition: background-color var(--short-transition) ease;
}

.btn_change {
  background-color: var(--button-secondary-bg);
  color: var(--button-secondary-text);
}

.btn_change:hover, .btn_change:focus,.btn_change:active {
  background-color: var(--button-secondary-text);
  color: var(--button-secondary-bg);
  transition: background-color var(--short-transition) ease;
}

.btn_add {
  padding: 0 6px;
  border: var(--border-size) double var(--button-secondary-bg);
  border-color: var(--button-border);
  background-color: var(--button-secondary-bg);
  color: var(--button-secondary-text);
  width: auto;
  flex-grow: 0;
  align-self: flex-end;
}

.btn_add:hover, .btn_add:focus,.btn_add:active .btn_toggle:hover,.btn_toggle:focus, .btn_toggle:active {
  background-color: var(--button-secondary-text);
  color: var(--button-secondary-bg);
  transition: background-color var(--short-transition) ease;
}

.item_pair {
  display: flex;
  justify-content: flex-end;
  align-items: baseline;
  width: 100%;
}

.item_label, .item_value {
  flex: 1;
  padding: var(--pad-sm);
}

.item_label {
  text-align: right;
  text-wrap: balance;
}

label:focus {
  outline: 3px solid white;
  outline-offset: -3px;
  background-color: var(--color-text);
}

.list_row {
  display: flex;
  background: var(--panel-bg);
}

.list_head {
  flex: 1;
  margin: 0 0 var(--mar-sm) 0;
  border-bottom: var(--border-bottom);
  text-align: center;
}

.list_item {
  flex: 1;
  margin: 0;
  text-align: center;
  align-content: center;
}

.list_control {
  min-width: 2rem;
  max-width: 2rem;
  width: 2rem;
  font-size: var(--font-lg);
}

.list_row > .list_item,
.list_row > select,
.list_row > input {
  flex-grow: 1;
  width: auto;
}

.radio_group {
  display: flex;
  margin: 0;
  padding: 0;
  width: 100%;
}

.radio_group .radio:hover,.radio:focus + label {
  border: 1px solid red;
  background-color: var(--btn-selected-fore);
  color: var(--btn-selected-fore);
  transition: background-color var(--short-transition) ease;
}

.radio_group .radio {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  border: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
}

.radio_group .radio + label {
  flex: 1;
  padding: var(--pad-xs);
  text-align: center;
  transition: var(--zero-transition) all linear;
}

.radio_group .radio:active + label,input[type="radio"]:checked + label {
  border-bottom: var(--border-bottom);
  border-color: var(--button-border-active);
  background-color: var(--btn-selected-back);
  color: var(--btn-selected-fore);
}


/* WORK ENTRY FIELD TAGS */
.work_entry_tags {
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap-md);
  align-items: baseline;
  margin: var(--mar-sm);
}

.work_entry_field {
  position: absolute;
  left: -9999px;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  border: 0;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
}

.work_entry_field + label {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: var(--pad-xs) var(--pad-md);
  border: 0;
  border-radius: var(--settings-selected-radius, var(--radius-control, var(--border-radius)));
  background-color: transparent;
  color: var(--button-text, inherit);
  cursor: pointer;
  transition: var(--short-transition) all ease;
  font-size: var(--font-sm);
  white-space: nowrap;
  font-weight: 500;
}

.work_entry_field:hover + label {
  background-color: var(--color-hover, rgba(255, 255, 255, 0.08));
  color: var(--button-text, inherit);
}

.work_entry_field:checked + label {
  border-bottom: var(--border-bottom);
  border-color: var(--button-border-active);
  background-color: var(--btn-selected-back, var(--button-bg-active));
  color: var(--btn-selected-fore, var(--button-text));
}

.work_entry_field:focus + label {
  outline: none;
}

.radio_group:focus-within {
  outline: var(--border-size) double var(--button-primary-text);
  border-radius: var(--radius-control, var(--border-radius));
}


/* COSMETICS */
.align_right     { text-align: right; }
.beta            { border: 3px dashed purple; border-radius: 10px; }
.border_bottom   { border-bottom: var(--border-bottom); }
.border_inset    { border-bottom: var(--border-size) double var(--border-inset-color); }
.border_top      { border-top: var(--border-size) double var(--panel-border); }
.centered        { margin-left: auto; margin-right: auto; text-align: center; }
.ellipsis        { text-overflow: ellipsis; }
.emoji           { font-size: var(--font-lg); }
.icon            { width: 1.5rem; height: 1.5rem; filter: brightness(100%) saturate(100%); }
.no_bullets      { list-style-type: none; }
.spotcolor       { color: var(--theme-signature-color, var(--heading-accent-color, var(--color-text))); }
.ta_start        { text-align: start; }
.ta_end          { text-align: end;   }
.trim_right      { border-right: var(--border-size) double var(--color-text);  background-color: var(--color-surface-strong); }
.visually_hidden { display: none; }
.hidden { display: none; }
.display-flex { display: flex; }
.display-block { display: block; }
.display-inline-block { display: inline-block; }
.visibility-hidden { visibility: hidden; }
.visibility-visible { visibility: visible; }


.panel {
  width: 100%;
  max-width: 100%;
  padding: var(--pad-md);
  margin: 0 auto var(--mar-md) auto;
  border-radius: var(--radius-panel, var(--border-radius));
  background-color: var(--panel-bg);
  color: var(--panel-text);
  border: 1px solid color-mix(in srgb, var(--panel-border) 70%, var(--panel-bg));
  box-shadow: inset 0 1px 0 color-mix(in srgb, var(--panel-text) 16%, white), 0 4px 12px color-mix(in srgb, var(--panel-text) 12%, black);
  backdrop-filter: blur(10px);
}

.row {
  display: flex;
  max-width: 100%;
  margin: 0;
  padding: 0;
}

.status {
  display: none;
  position: fixed;
  top: auto;
  bottom: calc(env(safe-area-inset-bottom, 0px) + var(--pad-lg));
  left: 50%;
  right: auto;
  width: fit-content;
  max-width: min(50vw, 42rem);
  margin: 0;
  height: auto;
  min-height: 3rem;
  z-index: 11000;
  font-size: var(--font-md);
  border-radius: var(--radius-panel, var(--border-radius));
  background-color: var(--panel-text);
  color: var(--panel-bg);
  transition: all var(--long-transition) ease-out;
  backdrop-filter: blur(10px);
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.4);
  padding: 0;
  overflow: hidden;
  transform: translateX(-50%);
}

.status.visible {
  display: flex;
  position: fixed;
  align-items: stretch;
  justify-content: flex-start;
  opacity: 1;
  visibility: visible;
}

/* Status type themes */
.status.info,
.status.paste {
  background-color: var(--status-info-bg);
  color: var(--status-info-text);
  border-left: 3px solid var(--status-info-border);
}

.status.success,
.status.save,
.status.copy {
  background-color: var(--status-success-bg);
  color: var(--status-success-text);
  border-left: 3px solid var(--status-success-border);
}

.status.error,
.status.delete {
  background-color: var(--status-error-bg);
  color: var(--status-error-text);
  border-left: 3px solid var(--status-error-border);
}

.status.working {
  background-color: var(--status-info-bg);
  color: var(--status-info-text);
  border-left: 3px solid var(--status-info-border);
}

.status.update {
  background-color: var(--status-info-bg);
  color: var(--status-info-text);
  border-left: 3px solid var(--status-info-border);
}

.status .status-icon-box {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
  padding: 0 12px;
  font-weight: 700;
  flex-shrink: 0;
}

.status.info .status-icon-box,
.status.paste .status-icon-box {
  background-color: var(--status-info-icon-bg);
  color: var(--status-info-icon-text);
}

.status.success .status-icon-box,
.status.save .status-icon-box,
.status.copy .status-icon-box {
  background-color: var(--status-success-icon-bg);
  color: var(--status-success-icon-text);
}

.status.error .status-icon-box,
.status.delete .status-icon-box {
  background-color: var(--status-error-icon-bg);
  color: var(--status-error-icon-text);
}

.status.working .status-icon-box,
.status.update .status-icon-box {
  background-color: var(--status-info-icon-bg);
  color: var(--status-info-icon-text);
}

.status .status-icon-box img {
  display: block;
  object-fit: contain;
}

.status .status-message-text {
  display: inline-flex;
  align-items: center;
  padding: 10px 14px;
  flex-grow: 1;
  color: inherit;
}

::-webkit-calendar-picker-indicator {
  background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="15" viewBox="0 0 24 24"><path fill="%23bbbbbb" d="M20 3h-1V1h-2v2H7V1H5v2H4c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 18H4V8h16v13z"/></svg>');
}

details {
  margin: var(--mar-xs) 0 0 0;
  padding: var(--pad-xs) 0;
  border-top: 1px double var(--border-inset-color);
}

details summary:hover {
  cursor: pointer;
}

/* ============================================================================
   BREADCRUMB TICKET STUB STYLE
   Ticket stub design: |text>text>text> with notches via clip-path
   Fallback for browsers without clip-path support
   ========================================================================== */

.doc-breadcrumb {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0;
  padding-block: var(--pad-sm);
  padding-inline: var(--pad-md);
  margin: 0 0 var(--mar-md) 0;
  background-color: transparent;
  border-bottom: 1px solid var(--border-color, #e0e0e0);
}

/* Individual breadcrumb items */
.doc-breadcrumb a,
.doc-breadcrumb .current {
  display: inline-flex;
  align-items: center;
  padding: 0.5rem 1rem;
  margin-inline-end: 0;
  background-color: var(--panel-bg, #f5f5f5);
  color: inherit;
  text-decoration: none;
  position: relative;
  /* Fallback for browsers without clip-path: simple border */
  border: 1px solid var(--border-color, #cccccc);
  border-radius: 0;
  /* Modern browsers: ticket stub notch via clip-path */
  clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 50%, calc(100% - 12px) 100%, 0 100%, 12px 50%);
  margin-inline-start: -1px; /* Overlap borders for seamless connection */
  z-index: 1;
  transition: background-color 0.2s ease, color 0.2s ease;
}

.doc-breadcrumb a:hover,
.doc-breadcrumb a:focus {
  background-color: var(--panel-hover-bg, #efefef);
  color: var(--color-primary, #0066cc);
  outline: none;
}

.doc-breadcrumb a:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: -2px;
}

/* First breadcrumb item: no left notch, but show right notch with border */
.doc-breadcrumb a:first-of-type,
.doc-breadcrumb .current:first-of-type {
  clip-path: polygon(0 0, calc(100% - 12px) 0, 100% 50%, calc(100% - 12px) 100%, 0 100%);
  margin-inline-start: 0;
  box-shadow: 
    inset -3px 0 0 var(--border-color, #cccccc),
    inset 0 -3px 0 var(--border-color, #cccccc),
    inset 0 3px 0 var(--border-color, #cccccc),
    inset -10px -6px 0 -8px var(--border-color, #cccccc),
    inset -10px 6px 0 -8px var(--border-color, #cccccc);
}

/* Last breadcrumb item: current page indicator */
.doc-breadcrumb .current {
  background-color: var(--color-primary, #0066cc);
  color: var(--button-primary-text, white);
  font-weight: 500;
  cursor: default;
  pointer-events: none;
  /* Reduced right notch for current item */
  clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%, 12px 50%);
}

/* Separators - hidden in modern browsers (clip-path creates visual separators) */
.doc-breadcrumb .separator,
.blog_breadcrumbs [aria-hidden="true"] {
  display: none;
}

/* Fallback styles for browsers without clip-path support */
@supports not (clip-path: polygon(0 0, 1px 1px)) {
  .doc-breadcrumb a,
  .doc-breadcrumb .current {
    clip-path: none;
    margin-inline-start: 0;
    padding: 0.5rem 1rem;
    border-radius: 2px;
    margin-inline-end: 8px;
  }

  .doc-breadcrumb a:first-of-type,
  .doc-breadcrumb .current:first-of-type {
    clip-path: none;
    margin-inline-start: 0;
  }

  .doc-breadcrumb .current {
    clip-path: none;
  }

  .doc-breadcrumb .separator {
    display: inline-block;
    margin: 0 4px;
    color: var(--text-muted, #888888);
    content: ">";
    opacity: 0.6;
  }

  .blog_breadcrumbs [aria-hidden="true"] {
    display: inline-block;
    margin: 0 4px;
    color: var(--text-muted, #888888);
    opacity: 0.6;
  }
}

/* ============================================================================
   BLOG BREADCRUMBS - Enhanced with ticket stub style
   ========================================================================== */

.blog_breadcrumbs {
  display: flex;
  gap: 0;
  align-items: center;
  font-size: 0.9rem;
  color: var(--text-muted);
  flex-wrap: wrap;
  padding-block: var(--pad-sm);
  padding-inline: var(--pad-md);
  margin: 0 0 var(--mar-md) 0;
  background-color: transparent;
  border-bottom: 1px solid var(--border-color, #e0e0e0);
}

.blog_breadcrumbs a,
.blog_breadcrumbs .current {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 0.8rem;
  text-decoration: none;
  position: relative;
  background-color: var(--panel-bg, #f5f5f5);
  color: inherit;
  border: 1px solid var(--border-color, #dddddd);
  /* Ticket stub notch effect */
  clip-path: polygon(0 0, calc(100% - 10px) 0, 100% 50%, calc(100% - 10px) 100%, 0 100%, 10px 50%);
  margin-inline-start: -1px;
  z-index: 1;
  font-size: 0.85rem;
  transition: background-color 0.2s ease;
}

.blog_breadcrumbs a:first-of-type {
  clip-path: polygon(0 0, calc(100% - 10px) 0, 100% 50%, calc(100% - 10px) 100%, 0 100%);
  margin-inline-start: 0;
  box-shadow: 
    inset -3px 0 0 var(--border-color, #dddddd),
    inset 0 -3px 0 var(--border-color, #dddddd),
    inset 0 3px 0 var(--border-color, #dddddd),
    inset -8px -5px 0 -7px var(--border-color, #dddddd),
    inset -8px 5px 0 -7px var(--border-color, #dddddd);
}

.blog_breadcrumbs a:hover {
  background-color: var(--panel-hover-bg, #efefef);
  text-decoration: none;
}

.blog_breadcrumbs .current {
  background-color: var(--color-primary, #0066cc);
  color: white;
  font-weight: 500;
  cursor: default;
  border-color: var(--color-primary, #0066cc);
  clip-path: polygon(0 0, 100% 0, 100% 100%, 0 100%, 10px 50%);
}

.blog_breadcrumbs .separator {
  display: none;
}

/* TOOLTIPS */
.tooltip {
  position: relative;
  display: inline-block;
  cursor: pointer;
}

.tooltip .tooltiptext {
  position: absolute;
  z-index: 1;
  bottom: 100%;
  left: 50%;
  visibility: hidden;
  width: max-content;
  max-width: min(28ch, calc(100vw - 2rem));
  transform: translateX(-50%);
  margin-left: 0;
  padding: var(--pad-sm);
  border-radius: var(--border-radius);
  text-align: left;
  background-color: var(--dialog-bg);
  color: var(--dialog-text);
  opacity: 0;
  transition: opacity 0.25s;
}

.tooltip .tooltiptext::after {
  position: absolute;
  top: 100%;
  left: 50%;
  content: "";
  margin-left: -5px;
  border-width: 1px;
  border-style: solid;
  border-color: #555 transparent transparent transparent;
}

.tooltip:hover .tooltiptext {
  visibility: visible;
  opacity: 1;
}

/* DASHBOARD PANEL */
#dashboard {
  position: fixed;
  top: 0;
  left: 0;

  width: 440px;
  height: 500px;
  min-height: 180px;

  background: var(--dialog-back);
  color: var(--dialog-text);

  border: 1px solid var(--panel-border);
  border-radius: var(--border-radius-lg, 0.75rem);

  box-shadow: 0 25px 70px var(--overlay-backdrop, rgba(0,0,0,0.58));
  backdrop-filter: blur(16px);

  overflow: hidden;
  user-select: none;

  will-change: transform, width, height;

  display: none;
  flex-direction: column;
  z-index: 9998;
}

#dashboard.active {
  display: flex;
}

#dashboard.dashboard-prep {
  visibility: hidden;
}

#dashboardHeader {
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 14px;

  background: var(--modal-head-back);
  border-bottom: 1px solid var(--panel-border);

  cursor: grab;
}

#dashboardHeader:active {
  cursor: grabbing;
}

#dashboardHeader span {
  color: var(--modal-head-fore);
  font-size: var(--font-md);
  font-weight: 600;
}

#dashboardCloseButton {
  border: var(--border-size) solid var(--danger-border);
  background: var(--danger-back);
  color: var(--danger-fore);
  border-radius: var(--border-radius);
  padding: var(--pad-xs) var(--pad-sm);
  min-width: 2.5rem;
  height: 2rem;
  line-height: 1;
  cursor: pointer;
  font-size: var(--font-lg);
  font-weight: bold;
  transition: background-color 0.15s ease, border-color 0.15s ease;
}

#dashboardCloseButton:hover {
  background: var(--danger-hover);
  border-color: var(--danger-hover-border);
}

#dashboardCloseButton:active {
  transform: scale(0.95);
}

#dashboardBody {
  padding: 16px;
  overflow: auto;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: var(--gap-lg);
}

.dashboard-section {
  display: flex;
  flex-direction: column;
  gap: var(--gap-md);
}

.dashboard-section-title {
  margin: 0;
  font-size: var(--font-lg);
  color: var(--panel-head-fore);
}

.dashboard-section-content {
  display: flex;
  flex-direction: column;
  gap: var(--gap-sm);
}

.dashboard-empty {
  margin: 0;
  color: var(--color-text-muted);
  font-size: var(--font-sm);
}

.lens-metrics-grid {
  display: grid;
  grid-template-columns: minmax(10rem, 16rem) 1fr;
  gap: var(--gap-sm) var(--gap-md);
  align-items: baseline;
}

.lens-metrics-grid dt {
  color: var(--color-text-muted);
  font-size: var(--font-sm);
}

.lens-metrics-grid dd {
  margin: 0;
  color: var(--panel-text);
  font-family: var(--monospace);
  font-size: var(--font-sm);
}

.lens-client-metrics {
  margin-top: var(--mar-sm);
  padding-top: var(--pad-sm);
  border-top: var(--border-size) solid var(--panel-border);
}

.lens-subtitle {
  margin: 0 0 var(--mar-sm) 0;
  font-size: var(--font-md);
  color: var(--panel-head-fore);
}

#dashboardResizeGrip {
  position: absolute;
  right: 0;
  bottom: 0;

  width: 18px;
  height: 18px;

  cursor: se-resize;
  opacity: 0.35;
  transition: opacity 0.15s ease;

  background:
    linear-gradient(135deg, transparent 50%, var(--panel-border) 50%),
    linear-gradient(135deg, transparent 62%, var(--panel-head-text) 62%);
  background-size: 100% 100%;
}

#dashboardResizeGrip:hover {
  opacity: 0.85;
}

.dashboard-identity-grid {
  display: grid;
  grid-template-columns: minmax(6rem, 8rem) 1fr;
  gap: var(--gap-xs) var(--gap-md);
  align-items: baseline;
}

.dashboard-identity-label {
  color: var(--color-text-muted);
  font-size: var(--font-sm);
}

.dashboard-identity-value {
  color: var(--panel-text);
  font-size: var(--font-sm);
  word-break: break-all;
}

.dashboard-identity-mono {
  font-family: var(--monospace);
  font-size: calc(var(--font-sm) * 0.85);
}

.dashboard-heartbeat-grid {
  display: grid;
  grid-template-columns: minmax(7rem, 10rem) 1fr;
  gap: var(--gap-xs) var(--gap-md);
  align-items: baseline;
}

.dashboard-heartbeat-label {
  color: var(--color-text-muted);
  font-size: var(--font-sm);
}

.dashboard-heartbeat-value {
  color: var(--panel-text);
  font-size: var(--font-sm);
  font-family: var(--monospace);
}

/* Shared utility classes moved to /css/utilities/ */


/* FOOTER */
footer {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: var(--chrome-height);
  height: auto;
  background-color: var(--color-surface-strong);
  color: var(--color-text-muted);
  margin: 0;
  padding: clamp(0.6rem, 1.3vw, 1rem);
}

footer nav {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-evenly;
  width: 100%;
  margin: 0;
  padding: 0;
  list-style: none;
  gap: var(--gap-lg, 2rem);
}

footer nav:not(.nav_menu) a {
  color: var(--color-text);
  text-decoration: none;
}

footer nav:not(.nav_menu) a:hover,
footer nav:not(.nav_menu) a:focus {
  background-color: var(--panel-text);
  color: var(--panel-bg);
  border-radius: 4px;
  padding: 0.25rem 0.5rem;
  transition: background-color 0.2s ease;
}

footer div { font-size: var(--font-md); }

/* ACCESSIBILITY TWEAKS */
@media (prefers-contrast: more) {
  :root {
    --font-weight: 700;
  }

  b {
    font-weight: 900;
  }

  input, button, select, .btn {
    font-weight: 550;
  }

  h1, h2, h3, h4 {
    font-weight: 700;
  }
}

@media (prefers-contrast: less) {
  :root {
    --font-weight: 100;
  }

  b {
    font-weight: 300;
  }
}

/* EMAIL VERIFICATION STATUS BANNER */
.verification-status-banner {
  background: linear-gradient(135deg, var(--verification-banner-bg-start) 0%, var(--verification-banner-bg-end) 100%);
  border: 2px solid var(--verification-banner-border);
  border-radius: 8px;
  padding: 16px 20px;
  margin-bottom: 24px;
  animation: verification-slide-down 0.3s ease-out;
}

.verification-banner-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.banner-icon {
  flex-shrink: 0;
  width: 48px;
  height: 48px;
  background: var(--verification-banner-icon-bg);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.banner-icon svg {
  color: var(--verification-banner-icon-text);
}

.banner-text {
  flex: 1;
}

.banner-text h4 {
  color: var(--verification-banner-heading-text);
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 4px;
}

.banner-text p {
  color: var(--verification-banner-body-text);
  font-size: 14px;
  margin: 0;
}

.banner-actions {
  flex-shrink: 0;
}

.resend-verification-btn {
  background: var(--resend-btn-bg);
  color: var(--resend-btn-text);
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s;
}

.resend-verification-btn:hover {
  background: var(--resend-btn-bg-hover);
}

.resend-verification-btn:disabled {
  background: var(--resend-btn-bg-disabled);
  cursor: not-allowed;
}

.resend-verification-btn[aria-busy='true'] {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
}

.resend-verification-btn[aria-busy='true']::after {
  content: '';
  width: 0.9rem;
  height: 0.9rem;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: resendVerificationSpin 700ms linear infinite;
}

.resend-verification-btn.is-success {
  background: var(--resend-btn-bg-success);
}

@keyframes resendVerificationSpin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes verification-slide-down {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }

  to {
    opacity: 1;
    transform: translateY(0);
  }
}

noscript div[role="status"] {
  background-color: #fff3cd;
  border: 1px solid #ffeeba;
  color: #856404;
  padding: 1rem;
  margin: 5rem;
  font-size: 1.2rem;
  text-align: center;
}

textarea {
  border-color: var(--button-border);
  border-radius: var(--border-radius);
  font-size: var(--font-md);
  font-weight: var(--font-weight);
  line-height: var(--line-height);
  background-color: var(--button-bg);
  color: var(--button-text);
}

/* Verification Reminder Banner */
.verification-reminder {
  background-color: #fff3cd;
  border: 2px solid #ffc107;
  border-radius: 8px;
  padding: 1.5rem;
  margin: 0 auto 1.5rem;
  text-align: center;
  max-width: 600px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
}

.verification-reminder__content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.verification-reminder__content strong {
  color: #856404;
  font-size: 1.1rem;
}

.verification-reminder__content span {
  color: #856404;
  font-size: 0.95rem;
}

.verification-reminder__form {
  display: flex;
  gap: 0.75rem;
  justify-content: center;
  flex-wrap: wrap;
  width: 100%;
}

.verification-reminder__input {
  padding: 0.5rem 1rem;
  border: 1px solid var(--verification-reminder-input-border);
  border-radius: 4px;
  font-size: 1rem;
  min-width: 250px;
  max-width: 100%;
  background-color: var(--verification-reminder-input-bg);
  color: var(--verification-reminder-input-text);
}

.verification-reminder__input:focus {
  outline: none;
  border-color: var(--verification-reminder-input-focus-border);
  background-color: var(--verification-reminder-input-bg);
  box-shadow: 0 0 0 3px var(--verification-reminder-input-focus-shadow);
}

.verification-reminder__input::placeholder {
  color: var(--verification-reminder-placeholder);
}

.verification-reminder__resend {
  width: 100%;
}

.verification-reminder__link {
  color: var(--verification-reminder-link);
  text-decoration: underline;
  cursor: pointer;
  font-size: 0.95rem;
  transition: color 0.2s ease;
}

.verification-reminder__link:hover {
  color: var(--verification-reminder-link-hover);
  text-decoration: none;
}

.verification-reminder__link.is-disabled {
  opacity: 0.6;
  pointer-events: none;
  text-decoration: none;
}

.verification-reminder__cooldown-hint {
  margin: 0.35rem 0 0;
  font-size: 0.85rem;
  color: #6c4f00;
}

/* Calendar Verification Lock Styles */
#calendar-v2-root.calendar_verification_locked {
  position: relative;
}

#calendar-v2-root.calendar_verification_locked #calendar-grid {
  pointer-events: none;
  filter: grayscale(0.1);
  opacity: 0.55;
}

.calendar_verification_lock_message {
  position: absolute;
  inset: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-align: center;
  background: rgba(255, 255, 255, 0.92);
  border: 2px solid #d6b14d;
  border-radius: 12px;
  color: #5c3f00;
  font-weight: 600;
  padding: 16px;
  z-index: 20;
  font-size: 1.1rem;
  line-height: 1.6;
}

/* ========================================
   WCAG 2.4.7 Focus Visible Styles
   Applies to all interactive elements
   ======================================== */

/* Links */
a:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

/* Buttons - all variants */
button:focus-visible,
input[type="button"]:focus-visible,
input[type="submit"]:focus-visible,
input[type="reset"]:focus-visible,
.btn:focus-visible,
.btn_primary:focus-visible,
.btn_secondary:focus-visible,
.btn_cancel:focus-visible,
.btn_close:focus-visible,
.btn_delete:focus-visible,
.btn_change:focus-visible,
.btn_add:focus-visible,
.btn_toggle:focus-visible,
[role="button"]:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

/* Form inputs */
input[type="text"]:focus-visible,
input[type="password"]:focus-visible,
input[type="email"]:focus-visible,
input[type="tel"]:focus-visible,
input[type="date"]:focus-visible,
input[type="number"]:focus-visible,
input[type="search"]:focus-visible,
input[type="url"]:focus-visible,
textarea:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

/* Select dropdowns */
select:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

/* Radio and checkbox inputs */
input[type="radio"]:focus-visible,
input[type="checkbox"]:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

/* Menu items and list items acting as controls */
[role="menuitem"]:focus-visible,
[role="tab"]:focus-visible,
[role="option"]:focus-visible,
.menu_item:focus-visible,
.popover_item:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

/* Image map areas */
area:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: -2px;
}

/* Generic tabbable elements */
[tabindex]:focus-visible:not([tabindex="-1"]):not(.datagrid_month_cell) {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

/* ---- REDUCED MOTION ---- */
/*
 * Respect the OS-level "Reduce Motion" preference.
 * Collapses all transitions and key-frame animations app-wide so motion-
 * sensitive users are not affected by decorative movement.
 */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 1ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 1ms !important;
    scroll-behavior: auto !important;
  }
}
