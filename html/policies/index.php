<?php declare(strict_types=1);

/**
 * Policies — permanent redirect to paycaltech.com/policies/.
 *
 * The PayCal Policies page has moved to the corporate site at paycaltech.com.
 * All requests are forwarded with 301 to preserve existing bookmarks and
 * search-engine indexing.  The anchor fragment (#accessibility, #terms,
 * #privacy) is a client-side concern and is preserved automatically by the
 * browser after redirect.
 *
 * PHP version 8.4.16
 */

header('Location: https://paycaltech.com/policies/', true, 301);
exit;

require_once '../config.php';

\PayCal\Observability\Lens::boot('policies');
if (\PayCal\Domain\InputSanitizer::getString('lens') === '1') {
  \PayCal\Observability\Lens::add('Policies Backend Snapshot', [
    'page' => $currentPage,
    'language' => (string) USER_LANGUAGE,
    'template' => 'policies/'.USER_LANGUAGE,
  ]);
}

// --- Load Page ---
require_once HTML.'/header.php';
echo PHP_EOL."<link rel=\"stylesheet\" href=\"" . Render::cssURL('transparency') . "\">".PHP_EOL;
$policyTemplate = 'policies/' . USER_LANGUAGE;
$policyTemplatePath = \PayCal\Domain\Config\Environment::appHome() . 'templates/' . $policyTemplate . '.php';

if (!is_file($policyTemplatePath)) {
  $policyTemplate = 'policies/en';
}

$i18n = [];
$i18nKeys = [
  'POLICIES_OVERVIEW_ARIA',
  'POLICIES_PAGE_TITLE',
  'POLICIES_PAGE_DECK',
  'POLICIES_LAST_UPDATED_2026_03_20',
  'POLICIES_LINK_ACCESSIBILITY',
  'POLICIES_LINK_TERMS',
  'POLICIES_LINK_PRIVACY',
  'POLICIES_ACCESSIBILITY_ARIA',
  'POLICIES_ACCESSIBILITY_TITLE',
  'POLICIES_ACCESSIBILITY_DECK',
  'POLICIES_GOVERNANCE_ARIA',
  'POLICIES_GOVERNANCE_TITLE',
  'POLICIES_GOVERNANCE_DECK',
  'POLICIES_STRIPE_BILLING_NOTE',
  'POLICIES_LINK_VERIFICATION_GOVERNANCE',
  'POLICIES_LINK_TESTING_GOVERNANCE',
  'POLICIES_LINK_FRAMEWORK_LEDGER',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = Strings::i18n($key);
}

$policyTemplateRenders = [
  '__POLICIES_OVERVIEW_ARIA__' => $i18n['POLICIES_OVERVIEW_ARIA'],
  '__POLICIES_PAGE_TITLE__' => $i18n['POLICIES_PAGE_TITLE'],
  '__POLICIES_PAGE_DECK__' => $i18n['POLICIES_PAGE_DECK'],
  '__POLICIES_LAST_UPDATED_2026_03_20__' => $i18n['POLICIES_LAST_UPDATED_2026_03_20'],
  '__POLICIES_LINK_ACCESSIBILITY__' => $i18n['POLICIES_LINK_ACCESSIBILITY'],
  '__POLICIES_LINK_TERMS__' => $i18n['POLICIES_LINK_TERMS'],
  '__POLICIES_LINK_PRIVACY__' => $i18n['POLICIES_LINK_PRIVACY'],
  '__POLICIES_ACCESSIBILITY_ARIA__' => $i18n['POLICIES_ACCESSIBILITY_ARIA'],
  '__POLICIES_ACCESSIBILITY_TITLE__' => $i18n['POLICIES_ACCESSIBILITY_TITLE'],
  '__POLICIES_ACCESSIBILITY_DECK__' => $i18n['POLICIES_ACCESSIBILITY_DECK'],
];

echo Render::template($policyTemplate, $policyTemplateRenders);

echo '<article class="article doc-article" aria-label="' . htmlspecialchars($i18n['POLICIES_GOVERNANCE_ARIA'], ENT_QUOTES, 'UTF-8') . '">' 
  . '<section class="doc-article-body">'
  . '<section class="doc-section">'
  . '<h2>' . htmlspecialchars($i18n['POLICIES_GOVERNANCE_TITLE'], ENT_QUOTES, 'UTF-8') . '</h2>'
  . '<p>' . htmlspecialchars($i18n['POLICIES_GOVERNANCE_DECK'], ENT_QUOTES, 'UTF-8') . '</p>'
  . '<p>' . htmlspecialchars($i18n['POLICIES_STRIPE_BILLING_NOTE'], ENT_QUOTES, 'UTF-8')
  . ' <a href="https://support.stripe.com/" target="_blank" rel="noopener noreferrer">support.stripe.com</a>.</p>'
  . '<ul class="doc-fact-list">'
  . '<li><a href="/transparency/verification-governance/">' . htmlspecialchars($i18n['POLICIES_LINK_VERIFICATION_GOVERNANCE'], ENT_QUOTES, 'UTF-8') . '</a></li>'
  . '<li><a href="/transparency/testing/">' . htmlspecialchars($i18n['POLICIES_LINK_TESTING_GOVERNANCE'], ENT_QUOTES, 'UTF-8') . '</a></li>'
  . '<li><a href="/transparency/framework-backend/">' . htmlspecialchars($i18n['POLICIES_LINK_FRAMEWORK_LEDGER'], ENT_QUOTES, 'UTF-8') . '</a></li>'
  . '</ul>'
  . '</section>'
  . '</section>'
  . '</article>';

require_once HTML.'/footer.php';
