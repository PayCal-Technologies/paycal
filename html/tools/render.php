<?php declare(strict_types=1);

use PayCal\Domain\OvertimeRules;
use PayCal\Domain\PublicPayTools;
use PayCal\Domain\Render;
use PayCal\Domain\Strings;

/**
 * Shared markup for the public /tools pages.
 */

function tools_i18n(string $key): string
{
  static $cache = [];
  if (!array_key_exists($key, $cache)) {
    $cache[$key] = Strings::i18n($key);
  }

  return $cache[$key];
}

function tools_h(string $value): string
{
  return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function tools_place_name(string $code, string $english): string
{
  $value = tools_i18n('TOOLS_PLACE_' . $code);

  return $value === 'TOOLS_PLACE_' . $code ? $english : $value;
}

function tools_money(int $cents): string
{
  $sign = $cents < 0 ? '-' : '';

  return $sign . '$' . number_format(abs($cents) / 100, 2, '.', ',');
}

function tools_shell(string $titleKey, string $descriptionKey): void
{
  global $currentPage, $pageTitle, $pageLabel, $pageMetaDescription, $pageMetaDescriptionLong;

  $currentPage = 'PAGE_TOOLS';
  $pageTitle = tools_i18n($titleKey) . ' - [' . tools_i18n('SITE_NAME') . ']';
  $pageLabel = tools_i18n($titleKey);
  $pageMetaDescription = tools_i18n($descriptionKey);
  $pageMetaDescriptionLong = tools_i18n($descriptionKey);
}

function tools_cta(): string
{
  $label = tools_h(tools_i18n('TOOLS_SIGNUP_CTA'));
  $note = tools_h(tools_i18n('TOOLS_SIGNUP_NOTE'));

  return '<section class="doc-section highlight"><h2>' . tools_h(tools_i18n('TOOLS_SIGNUP_TITLE')) . '</h2>'
    . '<p>' . $note . '</p>'
    . '<p><a class="doc-read-more" href="/auth/signup/?tier=free">' . $label . '</a></p></section>';
}

function tools_disclaimer(): string
{
  return '<p>' . tools_h(tools_i18n('TOOLS_DISCLAIMER')) . '</p>';
}

function tools_render_index(): void
{
  global $currentPage, $pageTitle, $pageLabel, $pageMetaDescription, $pageMetaDescriptionLong;
  tools_shell('TOOLS_INDEX_TITLE', 'TOOLS_INDEX_DESCRIPTION');
  require HTML . '/header.php';

  echo '<article class="article doc-article">';
  echo '<nav class="doc-breadcrumb" aria-label="' . tools_h(tools_i18n('HELP_BREADCRUMB_ARIA')) . '">';
  echo '<a href="/">' . tools_h(tools_i18n('HOME')) . '</a><span class="separator">/</span>';
  echo '<span class="current">' . tools_h(tools_i18n('TOOLS_INDEX_TITLE')) . '</span></nav>';
  echo '<header class="doc-article-header"><h1>' . tools_h(tools_i18n('TOOLS_INDEX_TITLE')) . '</h1>';
  echo '<p class="deck">' . tools_h(tools_i18n('TOOLS_INDEX_DECK')) . '</p></header>';
  echo '<div class="doc-article-body">';
  echo '<section class="doc-section"><h2>' . tools_h(tools_i18n('TOOLS_NEXT_CHEQUE_TITLE')) . '</h2>';
  echo '<p>' . tools_h(tools_i18n('TOOLS_NEXT_CHEQUE_DECK')) . '</p>';
  echo '<p><a class="doc-read-more" href="/tools/next-cheque/">' . tools_h(tools_i18n('TOOLS_OPEN_CALCULATOR')) . '</a></p></section>';
  echo '<section class="doc-section"><h2>' . tools_h(tools_i18n('TOOLS_PAY_RIGHT_HEADING')) . '</h2>';
  echo '<ul>';
  foreach (PublicPayTools::SLUGS as $slug => $code) {
    $page = PublicPayTools::jurisdiction($code);
    echo '<li><a href="/tools/pay-right/' . tools_h($slug) . '/">'
      . tools_h(tools_i18n('TOOLS_PAY_RIGHT_LINK_PREFIX') . ' ' . tools_place_name($code, (string) $page['name']))
      . '</a></li>';
  }
  echo '</ul></section>';
  $federal = OvertimeRules::forCode('CA');
  echo '<section class="doc-section"><h2>' . tools_h(tools_i18n('TOOLS_FEDERAL_TITLE')) . '</h2>';
  echo '<p>' . tools_h(OvertimeRules::summary('CA')) . '</p>';
  echo '<p><a href="' . tools_h((string) $federal['source_url']) . '" rel="noopener noreferrer">'
    . tools_h((string) $federal['source_title']) . '</a></p></section>';
  echo tools_cta();
  echo tools_disclaimer();
  echo '</div></article>';
  tools_styles();
  require HTML . '/footer.php';
}

function tools_render_next_cheque(): void
{
  global $currentPage, $pageTitle, $pageLabel, $pageMetaDescription, $pageMetaDescriptionLong;
  tools_shell('TOOLS_NEXT_CHEQUE_TITLE', 'TOOLS_NEXT_CHEQUE_DESCRIPTION');
  require HTML . '/header.php';

  $submitted = isset($_GET['wage']);
  $result = null;
  $inputError = false;
  if ($submitted) {
    try { $result = PublicPayTools::nextCheque($_GET); } catch (\InvalidArgumentException) { $inputError = true; http_response_code(400); }
  }
  $requestedProvince = strtoupper(\PayCal\Domain\PayRuleData::text($_GET['province'] ?? 'AB'));
  $selectedProvince = is_array($result) ? (string) $result['province'] : (in_array($requestedProvince, PublicPayTools::SLUGS, true) ? $requestedProvince : 'AB');
  $selectedYear = is_array($result) ? (int) $result['year'] : 2026;
  $selectedPeriods = is_array($result) ? (int) $result['periods'] : 26;

  echo '<article class="article doc-article">';
  echo '<nav class="doc-breadcrumb" aria-label="' . tools_h(tools_i18n('HELP_BREADCRUMB_ARIA')) . '">';
  echo '<a href="/">' . tools_h(tools_i18n('HOME')) . '</a><span class="separator">/</span>';
  echo '<a href="/tools/">' . tools_h(tools_i18n('TOOLS_INDEX_TITLE')) . '</a><span class="separator">/</span>';
  echo '<span class="current">' . tools_h(tools_i18n('TOOLS_NEXT_CHEQUE_TITLE')) . '</span></nav>';
  echo '<header class="doc-article-header"><h1>' . tools_h(tools_i18n('TOOLS_NEXT_CHEQUE_TITLE')) . '</h1>';
  echo '<p class="deck">' . tools_h(tools_i18n('TOOLS_NEXT_CHEQUE_DECK')) . '</p></header>';
  echo '<div class="doc-article-body">';
  echo '<form class="doc-section" method="get" action="/tools/next-cheque/">';
  echo '<p><label for="tools-province">' . tools_h(tools_i18n('TOOLS_PROVINCE')) . '</label> ';
  echo '<select id="tools-province" name="province">';
  foreach (PublicPayTools::SLUGS as $code) {
    $page = PublicPayTools::jurisdiction($code);
    $selected = $code === $selectedProvince ? ' selected' : '';
    echo '<option value="' . tools_h($code) . '"' . $selected . '>' . tools_h(tools_place_name($code, (string) $page['name'])) . '</option>';
  }
  echo '</select></p>';
  $payDateValue = is_array($result) ? (string) $result['pay_date'] : date('Y-m-d');
  if ($inputError) { echo '<p role="alert">' . tools_h(tools_i18n('TOOLS_INPUT_ERROR')) . '</p>'; }
  echo '<p><label for="tools-pay-date">' . tools_h(tools_i18n('TOOLS_PAY_DATE')) . '</label> <input type="date" id="tools-pay-date" name="pay_date" min="2020-01-01" max="2026-12-31" value="' . tools_h($payDateValue) . '" required></p>';
  echo '<p><label for="tools-periods">' . tools_h(tools_i18n('TOOLS_PAY_PERIODS')) . '</label> ';
  echo '<select id="tools-periods" name="periods">';
  foreach ([52 => 'TOOLS_PERIOD_WEEKLY', 26 => 'TOOLS_PERIOD_BIWEEKLY', 24 => 'TOOLS_PERIOD_SEMIMONTHLY', 12 => 'TOOLS_PERIOD_MONTHLY'] as $count => $key) {
    $selected = $count === $selectedPeriods ? ' selected' : '';
    echo '<option value="' . $count . '"' . $selected . '>' . tools_h(tools_i18n($key)) . '</option>';
  }
  echo '</select></p>';
  $fields = [
    'wage' => 'TOOLS_HOURLY_WAGE',
    'regular_hours' => 'TOOLS_REGULAR_HOURS',
    'overtime_hours' => 'TOOLS_OVERTIME_HOURS',
    'overtime_rate' => 'TOOLS_OVERTIME_RATE',
    'doubletime_hours' => 'TOOLS_DOUBLETIME_HOURS',
    'travel_hours' => 'TOOLS_TRAVEL_HOURS',
    'allowance' => 'TOOLS_ALLOWANCE',
    'years_of_service' => 'TOOLS_YEARS_OF_SERVICE',
  ];
  foreach ($fields as $name => $labelKey) {
    $value = \PayCal\Domain\PayRuleData::text($_GET[$name] ?? '');
    echo '<p><label for="tools-' . tools_h($name) . '">' . tools_h(tools_i18n($labelKey)) . '</label> ';
    echo '<input type="number" min="0" step="0.01" max="100000" id="tools-' . tools_h($name) . '" name="' . tools_h($name) . '" inputmode="decimal" value="' . tools_h($value) . '"></p>';
  }
  echo '<p><button type="submit">' . tools_h(tools_i18n('TOOLS_CALCULATE')) . '</button></p>';
  echo '</form>';

  if (is_array($result)) {
    echo '<section class="doc-section" aria-live="polite">';
    echo '<h2>' . tools_h(tools_i18n('TOOLS_RESULT_TITLE')) . '</h2>';
    echo '<p>' . tools_h((string) $result['overtime_summary']) . '</p>';
    echo '<dl>';
    foreach ([
      'regular_pay_cents' => 'TOOLS_REGULAR_PAY',
      'overtime_pay_cents' => 'TOOLS_OVERTIME_PAY',
      'overtime_rate_cents' => 'TOOLS_OVERTIME_RATE_USED',
      'doubletime_pay_cents' => 'TOOLS_DOUBLETIME_PAY',
      'travel_pay_cents' => 'TOOLS_TRAVEL_PAY',
      'allowance_cents' => 'TOOLS_ALLOWANCE',
      'gross_cents' => 'TOOLS_GROSS',
      'tax_cents' => 'TOOLS_ESTIMATED_TAX',
      'net_cents' => 'TOOLS_ESTIMATED_NET',
      'vacation_pay_cents' => 'TOOLS_VACATION_ACCRUAL',
    ] as $field => $labelKey) {
      echo '<dt>' . tools_h(tools_i18n($labelKey)) . '</dt><dd>' . tools_h(tools_money((int) $result[$field])) . '</dd>';
    }
    echo '</dl>';
    $vacationNote = (bool) $result['vacation_verified']
      ? tools_i18n('TOOLS_VACATION_VERIFIED')
      : tools_i18n('TOOLS_VACATION_UNVERIFIED');
    echo '<p>' . tools_h($vacationNote) . ' ' . tools_h((string) $result['vacation_label']) . '.</p>';
    echo '<p>' . tools_h(tools_i18n('TOOLS_TAX_NOTE')) . '</p>';
    echo '</section>';
  }

  echo tools_cta();
  echo tools_disclaimer();
  echo '</div></article>';
  tools_styles();
  require HTML . '/footer.php';
}

function tools_render_jurisdiction(string $slug): void
{
  $code = PublicPayTools::codeForSlug($slug);
  if ($code === null) {
    http_response_code(404);
    echo 'Not found';
    return;
  }

  $page = PublicPayTools::jurisdiction($code, 2026);
  $name = tools_place_name($code, (string) $page['name']);
  tools_shell('TOOLS_PAY_RIGHT_TITLE', 'TOOLS_PAY_RIGHT_DESCRIPTION');
  global $currentPage, $pageTitle, $pageLabel, $pageMetaDescription, $pageMetaDescriptionLong;
  $pageTitle = tools_i18n('TOOLS_PAY_RIGHT_LINK_PREFIX') . ' ' . $name . ' - [' . tools_i18n('SITE_NAME') . ']';
  $pageLabel = tools_i18n('TOOLS_PAY_RIGHT_LINK_PREFIX') . ' ' . $name;
  $pageMetaDescription = tools_i18n('TOOLS_PAY_RIGHT_DESCRIPTION') . ' ' . $name . '.';
  $pageMetaDescriptionLong = $pageMetaDescription;
  require HTML . '/header.php';

  $overtime = $page['overtime'];
  $vacation = $page['vacation'];
  $holiday = $page['holiday'];

  echo '<article class="article doc-article">';
  echo '<nav class="doc-breadcrumb" aria-label="' . tools_h(tools_i18n('HELP_BREADCRUMB_ARIA')) . '">';
  echo '<a href="/">' . tools_h(tools_i18n('HOME')) . '</a><span class="separator">/</span>';
  echo '<a href="/tools/">' . tools_h(tools_i18n('TOOLS_INDEX_TITLE')) . '</a><span class="separator">/</span>';
  echo '<span class="current">' . tools_h($name) . '</span></nav>';
  echo '<header class="doc-article-header"><h1>' . tools_h(tools_i18n('TOOLS_PAY_RIGHT_LINK_PREFIX') . ' ' . $name) . '</h1>';
  echo '<p class="deck">' . tools_h((string) $page['overtime_summary']) . '</p></header>';
  echo '<div class="doc-article-body">';

  echo '<section class="doc-section"><h2>' . tools_h(tools_i18n('TOOLS_OVERTIME_HEADING')) . '</h2>';
  echo '<p>' . tools_h((string) ($overtime['exceptions'] ?? '')) . '</p>';
  echo '<p>' . tools_h(tools_i18n('TOOLS_REVIEWED')) . ' 2026-10-20</p>';
  if (!empty($overtime['effective_date'])) { echo '<p>' . tools_h(tools_i18n('TOOLS_EFFECTIVE')) . ' ' . tools_h((string) $overtime['effective_date']) . '</p>'; }
  echo '<p><a href="' . tools_h((string) ($overtime['standards_url'] ?? $overtime['source_url'] ?? '')) . '" rel="noopener noreferrer">'
    . tools_h((string) ($overtime['source_title'] ?? $name)) . '</a></p></section>';

  echo '<section class="doc-section"><h2>' . tools_h(tools_i18n('TOOLS_VACATION_HEADING')) . '</h2>';
  echo '<p>' . tools_h((string) ($vacation['notes'] ?? '')) . '</p><ul>';
  foreach ($page['vacation_tiers'] as $tier) {
    if (!is_array($tier)) {
      continue;
    }
    echo '<li>' . tools_h((string) $tier['label']) . ' ' . tools_h(tools_i18n('TOOLS_FROM_YEARS')) . ' '
      . tools_h((string) $tier['min_years']) . '</li>';
  }
  echo '</ul>';
  $vacationState = !empty($vacation['verified']) ? tools_i18n('TOOLS_SOURCE_VERIFIED') : tools_i18n('TOOLS_SOURCE_UNVERIFIED');
  echo '<p>' . tools_h($vacationState) . '</p>';
  echo '<p>' . tools_h(tools_i18n('TOOLS_EFFECTIVE')) . ' ' . tools_h((string) ($vacation['effective_date'] ?? '')) . '</p>';
  echo '<p><a href="' . tools_h((string) ($vacation['source_url'] ?? '')) . '" rel="noopener noreferrer">'
    . tools_h((string) ($vacation['source_title'] ?? '')) . '</a></p></section>';

  echo '<section class="doc-section"><h2>' . tools_h(tools_i18n('TOOLS_HOLIDAY_HEADING')) . '</h2>';
  echo '<p>' . tools_h((string) ($holiday['eligibility'] ?? '')) . '</p>';
  if (!empty($holiday['dates_verified']) && $page['holidays'] !== []) {
    echo '<h3>' . tools_h(tools_i18n('TOOLS_HOLIDAYS_2026')) . '</h3><ul>';
    foreach ($page['holidays'] as $day) {
      echo '<li>' . tools_h((string) $day['date']) . ', ' . tools_h((string) $day['name']) . '</li>';
    }
    echo '</ul>';
  } else {
    echo '<p>' . tools_h(tools_i18n('TOOLS_HOLIDAYS_UNVERIFIED')) . '</p><ul>';
    foreach ($holiday['names'] ?? [] as $holidayName) {
      echo '<li>' . tools_h((string) $holidayName) . '</li>';
    }
    echo '</ul>';
  }
  echo '<p><a href="' . tools_h((string) ($holiday['source_url'] ?? '')) . '" rel="noopener noreferrer">'
    . tools_h((string) ($holiday['source_title'] ?? '')) . '</a></p></section>';

  echo '<p><a href="/tools/next-cheque/?province=' . tools_h($code) . '">' . tools_h(tools_i18n('TOOLS_OPEN_CALCULATOR')) . '</a></p>';
  echo tools_cta();
  echo tools_disclaimer();
  echo '</div></article>';
  tools_styles();
  require HTML . '/footer.php';
}

function tools_styles(): void
{
  echo PHP_EOL . '<link rel="stylesheet" href="' . Render::cssURL('help') . '">' . PHP_EOL;
}
