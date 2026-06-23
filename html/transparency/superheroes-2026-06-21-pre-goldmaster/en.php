<?php
/**
 * Archived Transparency: Superheroes System Map before GoldMaster.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

$currentPage = 'PAGE_TRANSPARENCY';
$pageTitle = 'Superheroes System Map - Previous Version - [PayCal]';
$pageLabel = 'Superheroes System Map';
require_once HTML.'/header.php';
?>
<article class="article doc-article">
  <nav class="doc-breadcrumb" aria-label="Breadcrumb">
    <a href="<?php echo transparency_href('/transparency/'); ?>">Transparency Hub</a>
    <span class="separator">/</span>
    <a href="<?php echo transparency_href('/transparency/superheroes/'); ?>">Superheroes System Map</a>
    <span class="separator">/</span>
    <span class="current">Previous version</span>
  </nav>

  <header class="doc-article-header">
    <h1>Superheroes System Map - Previous Version</h1>
    <p class="deck">
      This archived version records the superhero map before GoldMaster was added
      on June 21, 2026.
    </p>
    <p class="doc-article-meta">Published: <time datetime="2026-04-02">2026-04-02</time> &middot; Archived: <time datetime="2026-06-21">2026-06-21</time></p>
  </header>

  <div class="doc-article-body">
    <section class="doc-section highlight">
      <h2>Archived System Breakdown</h2>
      <table class="doc-table" aria-label="Archived superhero system map">
        <thead>
          <tr>
            <th>Superhero</th>
            <th>Primary role</th>
            <th>Use case</th>
            <th>Implementation</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>ShadowTalon</td>
            <td>Server-side fault and safety response shaping.</td>
            <td>Protects responses when backend faults occur.</td>
            <td><code>html/src/Domain/ShadowTalon.php</code></td>
          </tr>
          <tr>
            <td>Guardian</td>
            <td>Client-side sanitizer and content defense.</td>
            <td>Filters unsafe inline markup and browser-delivered content.</td>
            <td><code>html/js/guardian.js</code></td>
          </tr>
          <tr>
            <td>Phantom Wing</td>
            <td>Opt-in diagnostics and local debug controls.</td>
            <td>Supports console, detailed diagnostics, and network insight toggles.</td>
            <td><code>html/js/phantomwing/index.php</code></td>
          </tr>
          <tr>
            <td>Lens</td>
            <td>DEV-only event, counter, timer, and API payload diagnostics.</td>
            <td>Supports debug panels and <code>?lens=1</code> inspection.</td>
            <td><code>html/src/Observability/Lens.php</code></td>
          </tr>
          <tr>
            <td>EmailGarum</td>
            <td>Email rendering and transactional message safety.</td>
            <td>Keeps account email templates consistent and auditable.</td>
            <td><code>html/src/Domain/EmailGarum.php</code></td>
          </tr>
          <tr>
            <td>Echo</td>
            <td>Assistive status messaging and aria-live feedback.</td>
            <td>Improves screen-reader feedback across interactive flows.</td>
            <td><code>html/src/Domain/AriaEcho.php</code></td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="doc-section">
      <h2>Current Version</h2>
      <p>
        The current article adds GoldMaster as the canonical-reference system for
        future PayCal code, UI, tests, and architecture.
      </p>
      <p><a class="doc-read-more" href="<?php echo transparency_href('/transparency/superheroes/'); ?>">Read current version</a></p>
    </section>
  </div>
</article>
<?php
require_once HTML.'/footer.php';
