<?php declare(strict_types=1);

/**
 * Public status hub.
 *
 * PURPOSE: Consolidates public-safe PayCal status signals into one concise
 * page: platform availability, billing/pricing, security readiness,
 * transparency, accessibility, and language coverage.
 *
 * URL: /status/
 */

require_once __DIR__ . '/../config.php';

use PayCal\Domain\Config\Environment;

$currentPage  = 'PAGE_STATUS';
$pageTitle    = 'Status - [PayCal]';
$appVersion   = Environment::appVersion();
$lastUpdated  = gmdate('F j, Y');

require_once HTML . '/header.php';
?>
<article class="article doc-article">

  <header class="doc-article-header">
    <h1>PayCal Status</h1>
    <p class="deck">
      Public status signals for PayCal availability, billing, trust, documentation,
      accessibility, and language coverage.
    </p>
    <p class="doc-article-meta">
      Last updated: <time datetime="<?php echo htmlspecialchars(gmdate('Y-m-d'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($lastUpdated, ENT_QUOTES, 'UTF-8'); ?></time>
      &nbsp;&bull;&nbsp; App version <?php echo htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8'); ?>
    </p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight" aria-labelledby="status-summary-heading">
      <h2 id="status-summary-heading">Public Status Summary</h2>
      <table class="doc-table">
        <caption class="visually_hidden">PayCal public status summary by area, status, and public detail.</caption>
        <thead>
          <tr>
            <th scope="col">Area</th>
            <th scope="col">Status</th>
            <th scope="col">Public detail</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Web application</td>
            <td><span class="doc-badge low" aria-label="Status: Available">Available</span></td>
            <td>This page rendered successfully from the PayCal application.</td>
          </tr>
          <tr>
            <td>Billing and pricing</td>
            <td><span class="doc-badge low" aria-label="Status: Active">Active</span></td>
            <td>Public pricing is available. Paid-plan checkout and billing are Stripe-managed.</td>
          </tr>
          <tr>
            <td>Security and SOC 2 readiness</td>
            <td><span class="doc-badge info" aria-label="Status: In progress">In progress</span></td>
            <td>SOC 2 readiness is mapped to CC1-CC9 controls. PayCal is not yet SOC 2 certified.</td>
          </tr>
          <tr>
            <td>Transparency documentation</td>
            <td><span class="doc-badge low" aria-label="Status: Published">Published</span></td>
            <td>Security, metrics, accessibility, and compliance posture articles are publicly available.</td>
          </tr>
          <tr>
            <td>Language coverage</td>
            <td><span class="doc-badge low" aria-label="Status: Active">Active</span></td>
            <td>10 languages are tracked across UI strings, Help, Transparency articles, and email readiness.</td>
          </tr>
        </tbody>
      </table>
    </section>

    <div class="doc-panel-grid doc-panel-grid--responsive-3" role="list" aria-label="Status panels">
      <section class="doc-section" role="listitem" aria-labelledby="status-platform-heading">
        <h2 id="status-platform-heading">Platform</h2>
        <ul class="doc-fact-list">
          <li><strong>Public web:</strong> Available when this page renders.</li>
          <li><strong>Application version:</strong> <?php echo htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8'); ?></li>
          <li><strong>Incident page:</strong> Not separated yet; public status is consolidated here.</li>
        </ul>
      </section>

      <section class="doc-section" role="listitem" aria-labelledby="status-billing-heading">
        <h2 id="status-billing-heading">Billing</h2>
        <ul class="doc-fact-list">
          <li><strong>Plans:</strong> Free, Premium, Business.</li>
          <li><strong>Billing provider:</strong> Stripe for paid-plan checkout and customer billing.</li>
          <li><strong>Public detail:</strong> <a class="doc-read-more" href="/pricing/" aria-label="Open Pricing page">Open Pricing</a></li>
        </ul>
      </section>

      <section class="doc-section" role="listitem" aria-labelledby="status-security-heading">
        <h2 id="status-security-heading">Security</h2>
        <ul class="doc-fact-list">
          <li><strong>SOC 2:</strong> Readiness in progress, not certified.</li>
          <li><strong>Controls:</strong> CC1-CC9 mapped to evidence workflows.</li>
          <li><strong>Public detail:</strong> <a class="doc-read-more" href="/soc2/" aria-label="Open SOC 2 Readiness page">Open SOC 2 Readiness</a></li>
        </ul>
      </section>

      <section class="doc-section" role="listitem" aria-labelledby="status-transparency-heading">
        <h2 id="status-transparency-heading">Transparency</h2>
        <ul class="doc-fact-list">
          <li><strong>Public articles:</strong> 22 Transparency sections covering security, metrics, accessibility, SOC 2, infrastructure, and governance.</li>
          <li><strong>Localization:</strong> 183 localized Transparency article files are present; 18 sections have full 10-language coverage.</li>
          <li><strong>Public detail:</strong> <a class="doc-read-more" href="https://paycaltech.com/transparency/" target="_blank" rel="noopener noreferrer" aria-label="Open Transparency Hub on paycaltech.com">Open Transparency Hub</a></li>
        </ul>
      </section>

      <section class="doc-section" role="listitem" aria-labelledby="status-accessibility-heading">
        <h2 id="status-accessibility-heading">Accessibility</h2>
        <ul class="doc-fact-list">
          <li><strong>Program:</strong> Accessibility is tracked as part of product quality and transparency.</li>
          <li><strong>Scope:</strong> Keyboard, screen-reader, contrast, forms, and route-level regression checks.</li>
          <li><strong>Public detail:</strong> Available through Transparency documentation.</li>
        </ul>
      </section>

      <section class="doc-section" role="listitem" aria-labelledby="status-data-boundary-heading">
        <h2 id="status-data-boundary-heading">Public Data Boundary</h2>
        <ul class="doc-fact-list">
          <li><strong>Shown here:</strong> Product and documentation status signals.</li>
          <li><strong>Not shown here:</strong> User data, private billing records, internal logs, or operational secrets.</li>
          <li><strong>Detailed reports:</strong> SOC 2 materials use an NDA request workflow.</li>
        </ul>
      </section>
    </div>

    <section id="language-coverage" class="doc-section success" aria-labelledby="language-coverage-heading">
      <h2 id="language-coverage-heading">Language Coverage</h2>
      <p>
        PayCal tracks multilingual coverage across UI strings, Help Center content,
        Transparency articles, and transactional email readiness.
      </p>
      <table class="doc-table">
        <caption class="visually_hidden">Language coverage status by content layer, current state, and status.</caption>
        <thead>
          <tr>
            <th scope="col">Layer</th>
            <th scope="col">Current state</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Total languages</td>
            <td>10 languages: English source plus 9 active locales</td>
            <td><span class="doc-badge low" aria-label="Status: Active">Active</span></td>
          </tr>
          <tr>
            <td>UI strings</td>
            <td>4,290 English source keys; active locale files are tracked in <code>strings/*.txt</code></td>
            <td><span class="doc-badge low" aria-label="Status: Active">Active</span></td>
          </tr>
          <tr>
            <td>Help Center</td>
            <td>360 <code>HELP_</code> keys in the English source set</td>
            <td><span class="doc-badge low" aria-label="Status: Active">Active</span></td>
          </tr>
          <tr>
            <td>Transparency articles</td>
            <td>22 article sections; 183 localized article files; 18 sections with full 10-language coverage</td>
            <td><span class="doc-badge low" aria-label="Status: Active">Active</span></td>
          </tr>
          <tr>
            <td>Transactional email</td>
            <td>64 <code>EMAIL_</code> source keys; localized delivery plumbing is planned</td>
            <td><span class="doc-badge info" aria-label="Status: Planned">Planned</span></td>
          </tr>
        </tbody>
      </table>
    </section>

  </div>
</article>
<?php
require_once HTML . '/footer.php';
