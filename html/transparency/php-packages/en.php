<?php
/**
 * Public Transparency: PHP Package Dependency Transparency
 *
 * PURPOSE: Document every PHP package PayCal directly depends on,
 * the rationale for each, CVE audit outcomes, and the philosophy
 * governing third-party dependency decisions.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$i18n = [];
$i18nKeys = [
  'BREADCRUMB',
  'HELP_TOC_TRANSPARENCY_HUB',
  'TRANSPARENCY_PHP_PACKAGES_PAGE_TITLE',
  'TRANSPARENCY_PHP_PACKAGES_DECK',
  'TRANSPARENCY_PHP_PACKAGES_VERIFICATION_METADATA_TITLE',
  'TRANSPARENCY_PHP_PACKAGES_ROUTE_LABEL',
  'TRANSPARENCY_PHP_PACKAGES_LAST_VERIFIED_LABEL',
  'TRANSPARENCY_PHP_PACKAGES_NEXT_REVIEW_LABEL',
  'TRANSPARENCY_PHP_PACKAGES_SCOPE_LABEL',
  'TRANSPARENCY_PHP_PACKAGES_SCOPE_VALUE',
  'TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_TITLE',
  'TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_TEXT_1',
  'TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_FACT_1',
  'TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_FACT_2',
  'TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_FACT_3',
  'TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_FACT_4',
  'TRANSPARENCY_PHP_PACKAGES_RUNTIME_DEPS_TITLE',
  'TRANSPARENCY_PHP_PACKAGES_RUNTIME_DEPS_TEXT_1',
  'TRANSPARENCY_PHP_PACKAGES_TABLE_ARIA',
  'TRANSPARENCY_PHP_PACKAGES_TABLE_PACKAGE',
  'TRANSPARENCY_PHP_PACKAGES_TABLE_VERSION',
  'TRANSPARENCY_PHP_PACKAGES_TABLE_PURPOSE',
  'TRANSPARENCY_PHP_PACKAGES_TABLE_TRANSITIVE',
  'TRANSPARENCY_PHP_PACKAGES_DEV_DEPS_TITLE',
  'TRANSPARENCY_PHP_PACKAGES_DEV_DEPS_TEXT_1',
  'TRANSPARENCY_PHP_PACKAGES_TABLE_DEV_ARIA',
  'TRANSPARENCY_PHP_PACKAGES_CVE_TITLE',
  'TRANSPARENCY_PHP_PACKAGES_CVE_TEXT_1',
  'TRANSPARENCY_PHP_PACKAGES_CVE_TEXT_2',
  'TRANSPARENCY_PHP_PACKAGES_PHPDOTENV_DECISION_TITLE',
  'TRANSPARENCY_PHP_PACKAGES_PHPDOTENV_DECISION_TEXT_1',
  'TRANSPARENCY_PHP_PACKAGES_PHPDOTENV_DECISION_TEXT_2',
  'TRANSPARENCY_PHP_PACKAGES_PHPDOTENV_DECISION_TEXT_3',
  'TRANSPARENCY_PHP_PACKAGES_CICD_GATE_TITLE',
  'TRANSPARENCY_PHP_PACKAGES_CICD_GATE_TEXT_1',
  'TRANSPARENCY_PHP_PACKAGES_HOW_TO_VERIFY_TITLE',
];
foreach ($i18nKeys as $key) {
  $i18n[$key] = \PayCal\Domain\Strings::i18n($key);
}

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = $i18n['TRANSPARENCY_PHP_PACKAGES_PAGE_TITLE'] . ' - [PayCal]';
$pageLabel = $i18n['TRANSPARENCY_PHP_PACKAGES_PAGE_TITLE'];
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="<?php echo $i18n['BREADCRUMB']; ?>">
    <a href="<?php echo transparency_href('/transparency/'); ?>"><?php echo $i18n['HELP_TOC_TRANSPARENCY_HUB']; ?></a>
    <span class="separator">/</span>
    <span class="current"><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PAGE_TITLE']; ?></span>
  </nav>

  <header class="doc-article-header">
    <h1><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PAGE_TITLE']; ?></h1>
    <p class="deck"><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_DECK']; ?></p>
    <p class="doc-article-meta">Published: <time datetime="2026-05-07">2026-05-07</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_VERIFICATION_METADATA_TITLE']; ?></h2>
      <ul class="doc-fact-list">
        <li><strong><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_ROUTE_LABEL']; ?></strong> <code>/transparency/php-packages/</code></li>
        <li><strong><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_LAST_VERIFIED_LABEL']; ?></strong> <time datetime="2026-05-07">2026-05-07</time></li>
        <li><strong><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_NEXT_REVIEW_LABEL']; ?></strong> <time datetime="2026-08-07">2026-08-07</time></li>
        <li><strong><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_SCOPE_LABEL']; ?></strong> <?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_SCOPE_VALUE']; ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_FACT_1']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_FACT_2']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_FACT_3']; ?></li>
        <li><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PHILOSOPHY_FACT_4']; ?></li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_RUNTIME_DEPS_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_RUNTIME_DEPS_TEXT_1']; ?></p>
      <table class="doc-table" aria-label="<?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_TABLE_ARIA']; ?>">
        <thead>
          <tr>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_TABLE_PACKAGE']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_TABLE_VERSION']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_TABLE_PURPOSE']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_TABLE_TRANSITIVE']; ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>lbuchs/webauthn</code></td>
            <td>2.2.0</td>
            <td>Passkey and FIDO2 registration and authentication for passwordless sign-in.</td>
            <td>0</td>
          </tr>
          <tr>
            <td><code>phpoffice/phpspreadsheet</code></td>
            <td>5.7.0</td>
            <td>XLSX generation for earnings export downloads.</td>
            <td>~9 (maennchen/zipstream-php, markbaker/complex, markbaker/matrix, myclabs/deep-copy, psr/simple-cache, symfony/polyfill-*)</td>
          </tr>
          <tr>
            <td><code>stripe/stripe-php</code></td>
            <td>20.1.0</td>
            <td>Stripe billing API: subscription management, webhook verification, customer and payment intent operations.</td>
            <td>0</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_DEV_DEPS_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_DEV_DEPS_TEXT_1']; ?></p>
      <table class="doc-table" aria-label="<?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_TABLE_DEV_ARIA']; ?>">
        <thead>
          <tr>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_TABLE_PACKAGE']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_TABLE_VERSION']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_TABLE_PURPOSE']; ?></th>
            <th scope="col"><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_TABLE_TRANSITIVE']; ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>phpstan/phpstan</code></td>
            <td>2.1.54</td>
            <td>Static analysis at Level 9. Enforced in pre-push hook and CI. No baseline file permitted.</td>
            <td>~8 (nikic/php-parser, react/*, clue/ndjson-react, fidry/cpu-core-counter, etc.)</td>
          </tr>
          <tr>
            <td><code>phpunit/phpunit</code></td>
            <td>13.1.8</td>
            <td>Unit and integration test runner. 1,527 tests across Domain, Controllers, Infrastructure, and Observability layers.</td>
            <td>~15 (sebastian/*, phpunit/php-*, phar-io/*, myclabs/deep-copy, etc.)</td>
          </tr>
          <tr>
            <td><code>friendsofphp/php-cs-fixer</code></td>
            <td>3.95.1</td>
            <td>Code style enforcement. Dry-run check runs on every staged PHP file in the pre-commit hook and in CI.</td>
            <td>~12 (symfony/console, symfony/finder, symfony/event-dispatcher, ergebnis/agent-detector, etc.)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_CVE_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_CVE_TEXT_1']; ?></p>
      <p><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_CVE_TEXT_2']; ?></p>
    </section>

    <section class="doc-section highlight">
      <h2><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PHPDOTENV_DECISION_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PHPDOTENV_DECISION_TEXT_1']; ?></p>
      <p><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PHPDOTENV_DECISION_TEXT_2']; ?></p>
      <p><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_PHPDOTENV_DECISION_TEXT_3']; ?></p>
    </section>

    <section class="doc-section success">
      <h2><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_CICD_GATE_TITLE']; ?></h2>
      <p><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_CICD_GATE_TEXT_1']; ?></p>
      <ul class="doc-fact-list">
        <li><code>.github/workflows/phpstan.yml</code>: PHPStan Level 9 on PHP 8.4 and 8.5, includes <code>format:check</code>.</li>
        <li><code>.github/workflows/phpunit.yml</code>: fast gate (PHP 8.4) → full validation (PHP 8.5) → deep verification (scheduled).</li>
        <li>Pre-commit hook: PHPStan baseline check, AST snapshot, quick tests (1,055 tests).</li>
        <li>Pre-push hook: full PHPStan Level 9 + quick tests. <code>composer audit --locked</code> advisory check.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2><?php echo $i18n['TRANSPARENCY_PHP_PACKAGES_HOW_TO_VERIFY_TITLE']; ?></h2>
      <pre class="doc-code"># List all direct dependencies
composer show --direct

# Check for known security advisories
composer audit --locked

# Review the full dependency graph
composer show

# Inspect first-party .env loader (replaced vlucas/phpdotenv)
cat html/src/Infrastructure/Env/Dotenv.php</pre>
      <p>Related transparency pages: <a href="<?php echo transparency_href('/transparency/dependency-ci/'); ?>">Dependency and CI/CD Governance</a> and <a href="<?php echo transparency_href('/transparency/framework-backend/'); ?>">Framework and Backend Change Ledger</a>.</p>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
