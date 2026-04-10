<?php
/**
 * Public Transparency: Tax Methodology
 *
 * PURPOSE: Detailed tax formula documentation and examples.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_TAX_METHODOLOGY_PAGE_TITLE',
  'TRANSPARENCY_TAX_METHODOLOGY_DECK',
  'TRANSPARENCY_TAX_SCOPE_COMMITMENT_TITLE',
  'TRANSPARENCY_TAX_VERIFICATION_METADATA_TITLE',
  'TRANSPARENCY_TAX_ROUTE_METADATA_TITLE',
  'TRANSPARENCY_TAX_KNOWN_LIMITATIONS_TITLE',
  'TRANSPARENCY_TAX_FEDERAL_PROVINCIAL_TITLE',
  'TRANSPARENCY_TAX_NET_PAY_TITLE',
  'TRANSPARENCY_TAX_EMPLOYMENT_INSURANCE_TITLE',
  'TRANSPARENCY_TAX_ACCURACY_VALIDATION_TITLE',
  'TRANSPARENCY_TAX_IMPORTANT_NOTES_TITLE',
  'TRANSPARENCY_TAX_OAS_CLAWBACK_TITLE',
  'TRANSPARENCY_TAX_FEDERAL_BRACKETS_ARIA',
  'TRANSPARENCY_TAX_TABLE_INCOME_RANGE',
  'TRANSPARENCY_TAX_TABLE_RATE',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_TAX_METHODOLOGY_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_TAX_METHODOLOGY_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
    <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
      <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
      <span class="separator">/</span>
      <span class="current"><?php echo $i18n['TRANSPARENCY_TAX_METHODOLOGY_PAGE_TITLE']; ?></span>
    </nav>
    <header class="doc-article-header">
      <h1><?php echo $i18n['TRANSPARENCY_TAX_METHODOLOGY_PAGE_TITLE']; ?></h1>
      <p class="deck"><?php echo $i18n['TRANSPARENCY_TAX_METHODOLOGY_DECK']; ?></p>
<p class="doc-article-meta">Published: <time datetime="2026-03-09">2026-03-09</time></p>
    </header>

    <div class="doc-article-body">
      <section class="doc-section highlight">
        <h2><?php echo $i18n['TRANSPARENCY_TAX_SCOPE_COMMITMENT_TITLE']; ?></h2>
        <p>All calculations on this page follow current CRA guidance for the relevant tax year and are maintained as reference estimates.</p>
        <p>For platform telemetry and privacy boundaries, see <a href="<?php echo transparency_href('/transparency/metrics/'); ?>">Platform Metrics Transparency</a>.</p>
      </section>

      <section class="doc-section highlight">
        <h2><?php echo $i18n['TRANSPARENCY_TAX_VERIFICATION_METADATA_TITLE']; ?></h2>
        <div class="doc-two-column">
          <div>
            <h3><?php echo $i18n['TRANSPARENCY_TAX_ROUTE_METADATA_TITLE']; ?></h3>
            <ul class="doc-fact-list">
              <li><strong>Route:</strong> <code>/transparency/taxes/</code></li>
              <li><strong>Last verified:</strong> <time datetime="2026-03-23">2026-03-23</time></li>
              <li><strong>Next review due:</strong> <time datetime="2027-01-01">2027-01-01</time> (aligned to CRA annual parameter publication)</li>
              <li><strong>Verification scope:</strong> manual review of published CRA parameter tables for the current tax year against values on this page.</li>
            </ul>
          </div>
          <div>
            <h3><?php echo $i18n['TRANSPARENCY_TAX_KNOWN_LIMITATIONS_TITLE']; ?></h3>
            <ul class="doc-fact-list">
              <li>Tax rates and brackets are updated annually; interim CRA announcements between annual publication cycles may not be reflected immediately.</li>
              <li>Provincial rates are listed as reference only; some provinces publish mid-year amendments that may not be captured until the next annual review.</li>
            </ul>
          </div>
        </div>
        <p>This metadata is updated each January when CRA publishes updated parameters for the new tax year and as needed for material changes.</p>
      </section>
          <li>Basic Exemption: $3,500</li>
          <li>YMPE: $68,500</li>
          <li>Contribution Rate: 5.95%</li>
          <li>Maximum Annual Contribution: $3,867.50</li>
        </ul>
        <p><strong>Formula:</strong></p>
        <pre class="doc-code">CPP = max(0, min(AnnualIncome, 68500) - 3500) * 0.0595</pre>
        <p><strong>Example:</strong> For $50,000 annual income, CPP is <code>($50,000 - $3,500) * 5.95% = $2,766.75</code>.</p>
      </section>

      <section class="doc-section">
        <h2><?php echo $i18n['TRANSPARENCY_TAX_OAS_CLAWBACK_TITLE']; ?></h2>
        <p><strong>2025/2026 Parameters:</strong></p>
        <ul>
          <li>Clawback Threshold: $87,282</li>
          <li>Clawback Rate: 15%</li>
        </ul>
        <p><strong>Formula:</strong></p>
        <pre class="doc-code">OASClawback = max(0, AnnualIncome - 87282) * 0.15</pre>
        <p><strong>Example:</strong> For $120,000 annual income, OAS clawback is <code>($120,000 - $87,282) * 15% = $4,907.70</code>.</p>
      </section>

      <section class="doc-section">
        <h2><?php echo $i18n['TRANSPARENCY_TAX_EMPLOYMENT_INSURANCE_TITLE']; ?></h2>
        <p><strong>2025/2026 Parameters:</strong></p>
        <ul>
          <li>Maximum Insurable Earnings: $63,200</li>
          <li>Employee Rate (AB reference): 1.58%</li>
          <li>Maximum Annual Contribution: $998.56</li>
        </ul>
        <p><strong>Formula:</strong></p>
        <pre class="doc-code">EI = min(AnnualIncome, 63200) * 0.0158</pre>
        <p><strong>Example:</strong> For $75,000 annual income, EI is capped at <code>$998.56</code>.</p>
      </section>

      <section class="doc-section">
        <h2><?php echo $i18n['TRANSPARENCY_TAX_FEDERAL_PROVINCIAL_TITLE']; ?></h2>
        <p>Income tax is progressive. Each bracket rate applies only to income within that bracket.</p>
        <p><strong>2025/2026 Federal Brackets:</strong></p>
        <table class="doc-table" aria-label="<?php echo $i18n['TRANSPARENCY_TAX_FEDERAL_BRACKETS_ARIA']; ?>">
          <thead>
            <tr><th><?php echo $i18n['TRANSPARENCY_TAX_TABLE_INCOME_RANGE']; ?></th><th><?php echo $i18n['TRANSPARENCY_TAX_TABLE_RATE']; ?></th></tr>
          </thead>
          <tbody>
            <tr><td>$0 - $55,867</td><td>15%</td></tr>
            <tr><td>$55,867 - $111,733</td><td>20.5%</td></tr>
            <tr><td>$111,733 - $173,205</td><td>26%</td></tr>
            <tr><td>$173,205 - $246,752</td><td>29%</td></tr>
            <tr><td>Over $246,752</td><td>33%</td></tr>
          </tbody>
        </table>
        <p><strong>Provincial rates:</strong> Applied based on user location and updated with annual indexation.</p>
      </section>

      <section class="doc-section">
        <h2><?php echo $i18n['TRANSPARENCY_TAX_NET_PAY_TITLE']; ?></h2>
        <p><strong>Formula:</strong> <code>NetPay = GrossIncome - TotalDeductions</code></p>
        <p><strong>Total deductions include:</strong></p>
        <ul>
          <li>Federal income tax</li>
          <li>Provincial income tax</li>
          <li>CPP</li>
          <li>EI</li>
          <li>OAS clawback when applicable</li>
        </ul>
        <p><strong>Example:</strong> At $65,000 annual income (AB example), estimated net pay is about <code>$48,292</code> after estimated deductions.</p>
      </section>

      <section class="doc-section success">
        <h2><?php echo $i18n['TRANSPARENCY_TAX_ACCURACY_VALIDATION_TITLE']; ?></h2>
        <ul class="doc-fact-list">
          <li>Automated coverage across CPP, OAS, EI, and bracket transitions.</li>
          <li>Annual parameter updates aligned with CRA publications.</li>
          <li>Integration scenarios to validate combined deduction outcomes.</li>
        </ul>
      </section>

      <section class="doc-section highlight">
        <h2><?php echo $i18n['TRANSPARENCY_TAX_IMPORTANT_NOTES_TITLE']; ?></h2>
        <ul>
          <li>These values are estimates, not official tax assessments.</li>
          <li>Personal credits, deductions, and special circumstances can change actual liability.</li>
          <li>Consult a certified tax professional for filing or planning decisions.</li>
        </ul>
      </section>
    </div>
  </article>
<?php
require_once HTML.'/footer.php';
