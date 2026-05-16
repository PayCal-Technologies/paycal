<?php
/**
 * Language Coverage Status
 *
 * PURPOSE: Public-facing reference documenting PayCal's multilingual coverage
 * across every layer of the product — UI strings, Help Center, Transparency
 * Center articles, and transactional email. Demonstrates the breadth and
 * depth of localization work and sets honest expectations about what is
 * complete versus planned.
 *
 * URL: /language-coverage/
 */

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$currentPage  = 'PAGE_TRANSPARENCY';
$pageTitle    = 'Language Coverage - [PayCal]';

require_once HTML . '/header.php';
?>
<article class="article doc-article">

  <nav class="doc-breadcrumb" aria-label="<?php echo \PayCal\Domain\Strings::i18n('BREADCRUMB'); ?>">
    <a href="/transparency/">Transparency Hub</a>
    <span class="separator">/</span>
    <span class="current">Language Coverage</span>
  </nav>

  <header class="doc-article-header">
    <h1>Language Coverage</h1>
    <p class="deck">
      Every layer of PayCal — UI strings, Help Center, Transparency articles, and email — is
      tracked here by language. What you see below is not merely a translated interface. It is
      multilingual institutional infrastructure.
    </p>
    <p class="doc-article-meta">
      Last updated: <time datetime="2026-05-12">2026-05-12</time>
      &nbsp;&bull;&nbsp; 10 languages &nbsp;&bull;&nbsp; 2,785 UI keys
    </p>
  </header>

  <div class="doc-article-body">

    <section class="doc-section highlight">
      <h2>Why This Exists</h2>
      <p>
        Most products treat localization as a feature to ship once and forget. We treat it as a
        commitment that is either current or it is not. This page makes that commitment visible.
      </p>
      <p>Maintaining full-depth multilingual coverage across every product layer:</p>
      <ul class="doc-list">
        <li><strong>Demonstrates seriousness.</strong> Translated UI is a feature. Translated institutional content — transparency reports, security disclosures, audit methodologies — is a signal of organizational maturity.</li>
        <li><strong>Creates trust.</strong> Users who read in their own language understand what we are telling them. That is the only way disclosure means anything.</li>
        <li><strong>Improves SEO.</strong> Every transparency article in nine languages is independently indexable, searchable content in those language markets.</li>
        <li><strong>Helps contributors and translators later.</strong> A complete, versioned record of what is covered makes onboarding future human reviewers tractable.</li>
        <li><strong>Reinforces international credibility.</strong> Security disclosures, SOC 2 readiness documentation, and audit methodology published in ten languages positions PayCal as operating at a standard that most companies with far larger teams do not reach.</li>
      </ul>
    </section>

    <section class="doc-section">
      <h2>Coverage by Layer</h2>

      <table class="doc-table" aria-label="Language coverage across all product layers">
        <thead>
          <tr>
            <th scope="col">Language</th>
            <th scope="col">UI</th>
            <th scope="col">Help</th>
            <th scope="col">Transparency</th>
            <th scope="col">Emails</th>
            <th scope="col">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><strong>English</strong> <span class="doc-badge info">Source</span></td>
            <td>2,785 keys</td>
            <td>361 keys</td>
            <td>18 articles</td>
            <td>All templates</td>
            <td><span class="doc-badge info">Source</span></td>
          </tr>
          <tr>
            <td><strong>French</strong> <code>fr</code></td>
            <td>&#10003; 2,785 keys</td>
            <td>&#10003; 361 keys</td>
            <td>&#10003; 18 / 18</td>
            <td><span class="doc-article-meta">Planned</span></td>
            <td><span class="doc-badge low">Active</span></td>
          </tr>
          <tr>
            <td><strong>German</strong> <code>de</code></td>
            <td>&#10003; 2,785 keys</td>
            <td>&#10003; 361 keys</td>
            <td>&#10003; 18 / 18</td>
            <td><span class="doc-article-meta">Planned</span></td>
            <td><span class="doc-badge low">Active</span></td>
          </tr>
          <tr>
            <td><strong>Spanish</strong> <code>es</code></td>
            <td>&#10003; 2,785 keys</td>
            <td>&#10003; 361 keys</td>
            <td>&#10003; 18 / 18</td>
            <td><span class="doc-article-meta">Planned</span></td>
            <td><span class="doc-badge low">Active</span></td>
          </tr>
          <tr>
            <td><strong>Italian</strong> <code>it</code></td>
            <td>&#10003; 2,785 keys</td>
            <td>&#10003; 361 keys</td>
            <td>&#10003; 18 / 18</td>
            <td><span class="doc-article-meta">Planned</span></td>
            <td><span class="doc-badge low">Active</span></td>
          </tr>
          <tr>
            <td><strong>Dutch</strong> <code>nl</code></td>
            <td>&#10003; 2,785 keys</td>
            <td>&#10003; 361 keys</td>
            <td>&#10003; 18 / 18</td>
            <td><span class="doc-article-meta">Planned</span></td>
            <td><span class="doc-badge low">Active</span></td>
          </tr>
          <tr>
            <td><strong>Portuguese</strong> <code>pt</code></td>
            <td>&#10003; 2,785 keys</td>
            <td>&#10003; 361 keys</td>
            <td>&#10003; 18 / 18</td>
            <td><span class="doc-article-meta">Planned</span></td>
            <td><span class="doc-badge low">Active</span></td>
          </tr>
          <tr>
            <td><strong>Hindi</strong> <code>hi</code></td>
            <td>&#10003; 2,785 keys</td>
            <td>&#10003; 361 keys</td>
            <td>&#10003; 18 / 18</td>
            <td><span class="doc-article-meta">Planned</span></td>
            <td><span class="doc-badge low">Active</span></td>
          </tr>
          <tr>
            <td><strong>Filipino</strong> <code>tl</code></td>
            <td>&#10003; 2,785 keys</td>
            <td>&#10003; 361 keys</td>
            <td>&#10003; 18 / 18</td>
            <td><span class="doc-article-meta">Planned</span></td>
            <td><span class="doc-badge low">Active</span></td>
          </tr>
          <tr>
            <td><strong>Turkish</strong> <code>tr</code></td>
            <td>&#10003; 2,785 keys</td>
            <td>&#10003; 361 keys</td>
            <td>&#10003; 18 / 18</td>
            <td><span class="doc-article-meta">Planned</span></td>
            <td><span class="doc-badge low">Active</span></td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>What Each Column Means</h2>

      <h3>UI — Interface Strings</h3>
      <p>
        Every visible label, button, tooltip, error message, keyboard shortcut hint, modal title,
        navigation item, and accessibility announcement in the application is driven by a string
        key loaded from <code>strings/{lang}.txt</code>. The English master contains 2,785 keys
        spanning 60+ top-level namespaces. All nine non-English languages maintain exact key
        parity with the English master.
      </p>
      <p>
        Key namespaces include: <code>TRANSPARENCY_*</code> (611 keys), <code>HELP_*</code> (361),
        <code>ORGANIZATIONS_*</code> (359), <code>ADMIN_*</code> (178), <code>SETTINGS_*</code> (149),
        <code>PROFILE_*</code> (109), <code>AUTH_*</code> (67), <code>EMAIL_*</code> (65), and more.
      </p>

      <h3>Help — Help Center</h3>
      <p>
        The Help Center contains structured articles covering getting started, pay period setup,
        work hours and wages, WebAuthn passkey registration, tax bracket methodology, browser
        requirements, and troubleshooting. All 361 <code>HELP_*</code> string keys are localized
        across all nine non-English languages.
      </p>

      <h3>Transparency — Transparency Center</h3>
      <p>
        The <a href="/transparency/">Transparency Hub</a> contains 18 full-length articles
        covering security audits, Redis infrastructure, WebAuthn implementation, SOC 2 readiness,
        accessibility compliance, dependency management, load testing, email architecture, metrics
        collection, and more.
      </p>
      <p>
        Each article is a standalone PHP template with the full article body translated — prose,
        tables, section headings, finding descriptions, and timelines. Technical content (code
        blocks, class names, commit hashes, specification references, severity badges) is preserved
        verbatim in all languages, because precision in those details cannot be approximated by
        translation.
      </p>
      <p>Current article count: <strong>18 articles</strong> &times; <strong>10 languages</strong> = <strong>180 localized article files</strong>.</p>

      <h3>Emails — Transactional Email</h3>
      <p>
        Transactional emails (email verification, account recovery, passkey enrollment confirmation,
        organization invitations) currently use English-only templates. Localized email delivery
        is planned as a future layer once the HTML and plain-text template rendering pipeline is
        extended to accept a language parameter.
      </p>
      <p>
        The <code>EMAIL_*</code> string keys (65 keys covering every email type, subject line, and
        body block) are already translated in all nine non-English language files. The gap is
        plumbing, not translation work.
      </p>

    </section>

    <section class="doc-section success">
      <h2>Scale in Numbers</h2>
      <table class="doc-table" aria-label="i18n scale summary">
        <thead>
          <tr>
            <th scope="col">Metric</th>
            <th scope="col">Count</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Total languages</td>
            <td><strong>10</strong> (English source + 9 active)</td>
          </tr>
          <tr>
            <td>UI string keys per language</td>
            <td><strong>2,785</strong></td>
          </tr>
          <tr>
            <td>Total string entries (all 10 locales)</td>
            <td><strong>~27,850</strong></td>
          </tr>
          <tr>
            <td>Transparency articles with full 10-language coverage</td>
            <td><strong>18</strong></td>
          </tr>
          <tr>
            <td>Transparency article PHP files</td>
            <td><strong>180</strong> (18 &times; 10)</td>
          </tr>
          <tr>
            <td>Help Center keys localized</td>
            <td><strong>361 keys &times; 9 languages</strong></td>
          </tr>
          <tr>
            <td>Emails localized</td>
            <td>Planned (string keys ready)</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Architecture</h2>
      <p>
        The two main i18n layers are intentionally independent:
      </p>
      <ul class="doc-list">
        <li>
          <strong>String system (<code>strings/*.txt</code>).</strong> Every rendered UI string
          flows through a single <code>Strings::i18n($key)</code> call. The active language is
          resolved from the authenticated user record and set as a PHP constant at request time.
          All locales are loaded on demand, not bundled into the page. This means adding a new
          key to the English master and propagating it to all nine language files is the complete
          and only step required to localize any new UI surface.
        </li>
        <li>
          <strong>Transparency article templates.</strong> Each transparency article has a
          per-language PHP template file (e.g., <code>transparency/soc2/fr.php</code>) containing
          the full translated HTML body. Language routing is handled by each article&apos;s
          <code>index.php</code>, which resolves the requested language via <code>?l=xx</code>
          query parameter or user session language and falls back to English.
        </li>
      </ul>
      <p>
        Because these layers are decoupled, a new transparency article can be written in English
        first and translated independently, without touching any of the UI string infrastructure.
        Conversely, adding a new UI string key does not require touching any article templates.
      </p>
    </section>

    <section class="doc-section">
      <h2>Roadmap</h2>
      <table class="doc-table" aria-label="Language coverage roadmap">
        <thead>
          <tr>
            <th scope="col">Layer</th>
            <th scope="col">Current State</th>
            <th scope="col">Next Step</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>UI strings</td>
            <td>10 languages, full key parity</td>
            <td>Maintain parity on new key additions</td>
          </tr>
          <tr>
            <td>Help Center</td>
            <td>10 languages, full key parity</td>
            <td>Expand with new help topics as features ship</td>
          </tr>
          <tr>
            <td>Transparency articles</td>
            <td>18 articles &times; 10 languages</td>
            <td>Translate new articles as they are published</td>
          </tr>
          <tr>
            <td>Transactional email</td>
            <td>English only; all string keys pre-translated</td>
            <td>Wire language parameter into email template renderer</td>
          </tr>
          <tr>
            <td>Additional languages</td>
            <td>10 active</td>
            <td>No current additions planned; architecture supports any <a href="https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes">ISO 639-1</a> code</td>
          </tr>
        </tbody>
      </table>
    </section>

  </div>
</article>
<?php
require_once HTML . '/footer.php';
