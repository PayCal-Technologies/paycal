<?php declare(strict_types=1);

use PayCal\Domain\AdminSurface;
use PayCal\Domain\Authentication;
use PayCal\Domain\Config\Environment;
use PayCal\Domain\GoldMasterCatalog;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Render;

require_once __DIR__ . '/../../config.php';

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/goldmaster/');

$currentPage = 'PAGE_ADMIN';
$pageTitle = 'GoldMaster - [PayCal]';
$pageLabel = 'GoldMaster';

$selectedCategory = InputSanitizer::sanitizeString(InputSanitizer::getString('category') ?? '');
$selectedMaster = InputSanitizer::sanitizeString(InputSanitizer::getString('master') ?? '');
$selected = GoldMasterCatalog::find($selectedCategory, $selectedMaster);
$categories = GoldMasterCatalog::categories();
$grouped = GoldMasterCatalog::groupedExamples();
$showFile = (InputSanitizer::sanitizeString(InputSanitizer::getString('view') ?? '') === 'file');
$fileContents = $selected !== null ? GoldMasterCatalog::fileContents($selected) : '';
$openDialogOnLoad = $selectedCategory !== '' || $selectedMaster !== '' || $showFile;

require_once HTML . '/header.php';

$h = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$safeHref = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
$queryHref = static function (array $params): string {
  return '/admin/goldmaster/?' . http_build_query($params);
};
$renderList = static function (array $items) use ($h): string {
  if ($items === []) {
    return '<p class="text-muted">None listed.</p>';
  }

  $html = '<ul class="goldmaster-list">';
  foreach ($items as $item) {
    if (!is_scalar($item)) {
      continue;
    }
    $html .= '<li>' . $h((string) $item) . '</li>';
  }
  $html .= '</ul>';

  return $html;
};

$cspNonceRaw = $_SERVER['CSP_NONCE'] ?? '';
$cspNonce = is_scalar($cspNonceRaw) ? (string) $cspNonceRaw : '';
echo PHP_EOL . '<link rel="stylesheet" href="' . htmlspecialchars(Render::cssURL('admin'), ENT_QUOTES, 'UTF-8') . '" nonce="' . $h($cspNonce) . '">' . PHP_EOL;
?>
<section class="panel w100 pad_md goldmaster-admin-page" aria-labelledby="goldmaster_admin_title">
  <div class="admin-feedback-header">
    <div>
      <h1 id="goldmaster_admin_title">GoldMaster</h1>
      <p class="text-muted">Canonical examples for humans and AI agents.</p>
    </div>
    <div class="goldmaster-admin-actions">
      <button type="button" class="btn btn_primary" data-goldmaster-open>Open GoldMaster</button>
      <a class="btn btn_secondary" href="/transparency/goldmaster/" target="_blank" rel="noopener noreferrer">Transparency article</a>
      <a class="btn btn_secondary" href="/admin/">Back to Admin</a>
    </div>
  </div>

  <div class="goldmaster-admin-grid">
    <article class="admin-card panel">
      <div class="admin-card-header">
        <h2>Reference Catalog</h2>
      </div>
      <div class="admin-card-body">
        <p>GoldMaster keeps PayCal consistent. These curated examples define the preferred patterns for future work.</p>
      </div>
      <div class="admin-card-footer">
        <button type="button" class="btn btn_primary" data-goldmaster-open>Browse Examples</button>
      </div>
    </article>

    <article class="admin-card panel">
      <div class="admin-card-header">
        <h2>Status</h2>
      </div>
      <div class="admin-card-body">
        <p><strong><?= $h((string) count(GoldMasterCatalog::examples())) ?></strong> examples indexed from static metadata.</p>
        <p class="text-muted">Read-only file previews. No code execution. No database dependency.</p>
      </div>
    </article>
  </div>
</section>

<dialog id="goldmaster_dialog" data-dialog-invoker-bridge class="dialog goldmaster-dialog" data-dialog-close-tts="GoldMaster" aria-modal="true" aria-labelledby="goldmaster_dialog_title" aria-describedby="goldmaster_dialog_desc" <?= $openDialogOnLoad ? 'data-open-on-load="1"' : '' ?>>
  <section class="modal_header">
    <div>
      <h2 id="goldmaster_dialog_title" class="modal_title">GoldMaster</h2>
      <p id="goldmaster_dialog_desc" class="goldmaster-subtitle">Canonical examples for humans and AI agents</p>
    </div>
    <button type="button" class="btn btn_close" data-dialog-close="goldmaster_dialog" commandfor="goldmaster_dialog" command="close" aria-label="Close GoldMaster">&times;</button>
  </section>

  <section class="modal_content goldmaster-modal-content">
    <section class="goldmaster-intro" aria-label="GoldMaster guidance">
      GoldMaster keeps PayCal consistent. These curated examples define the preferred patterns for future work.
    </section>

    <section class="goldmaster-dialog-grid">
      <aside class="goldmaster-panel goldmaster-categories" aria-label="Golden master categories">
        <h3>Categories</h3>
        <nav class="goldmaster-category-list" aria-label="Category list">
          <?php foreach ($categories as $category) {
            $categoryKey = (string) $category['key'];
            $isActive = $selected !== null && $selected['category'] === $categoryKey;
            $firstExample = $grouped[$categoryKey][0] ?? null;
            $href = $firstExample === null
              ? '#'
              : $queryHref(['category' => $categoryKey, 'master' => $firstExample['id']]);
          ?>
            <a
              class="goldmaster-category-card<?= $isActive ? ' is-active' : '' ?><?= $firstExample === null ? ' is-empty' : '' ?>"
              href="<?= $safeHref($href) ?>"
              aria-current="<?= $isActive ? 'true' : 'false' ?>"
            >
              <span class="goldmaster-category-label"><?= $h((string) $category['label']) ?></span>
              <span class="goldmaster-category-count"><?= $h((string) $category['count']) ?></span>
              <span class="goldmaster-category-description"><?= $h((string) $category['description']) ?></span>
            </a>
          <?php } ?>
        </nav>
      </aside>

      <main class="goldmaster-panel goldmaster-main" aria-label="Selected golden master">
        <?php if ($selected === null) { ?>
          <div class="goldmaster-empty">
            <h3>No golden master selected.</h3>
            <p>Choose a category or create the first canonical example.</p>
          </div>
        <?php } else { ?>
          <div class="goldmaster-detail-header">
            <div>
              <span class="goldmaster-eyebrow"><?= $h((string) $selected['category_label']) ?></span>
              <h3><?= $h((string) $selected['name']) ?></h3>
            </div>
            <span class="goldmaster-status goldmaster-status-<?= $h((string) $selected['status_key']) ?>">
              <?= $h((string) $selected['status']) ?>
            </span>
          </div>

          <dl class="goldmaster-detail-list">
            <div>
              <dt>File path</dt>
              <dd><code data-goldmaster-path><?= $h((string) $selected['file_path']) ?></code></dd>
            </div>
            <div>
              <dt>Purpose</dt>
              <dd><?= $h((string) $selected['purpose']) ?></dd>
            </div>
            <div>
              <dt>Pattern type</dt>
              <dd><?= $h((string) $selected['pattern_type']) ?></dd>
            </div>
            <div>
              <dt>Notes</dt>
              <dd><?= $h((string) $selected['notes']) ?></dd>
            </div>
          </dl>

          <div class="goldmaster-two-column">
            <section>
              <h4>Use this when...</h4>
              <?= $renderList($selected['use_when']) ?>
            </section>
            <section>
              <h4>Do not use this when...</h4>
              <?= $renderList($selected['do_not_use_when']) ?>
            </section>
          </div>

          <section
            id="goldmaster_file_preview"
            class="goldmaster-file-preview"
            data-goldmaster-file-preview
            aria-label="Read-only file preview"
            <?= $showFile ? '' : 'hidden' ?>
          >
            <h4>Read-only file preview</h4>
            <pre tabindex="0"><code><?= $h($fileContents) ?></code></pre>
          </section>
        <?php } ?>
      </main>

      <aside class="goldmaster-panel goldmaster-meta" aria-label="Golden master metadata">
        <h3>Metadata</h3>
        <?php if ($selected === null) { ?>
          <p class="text-muted">No metadata selected.</p>
        <?php } else { ?>
          <dl class="goldmaster-meta-list">
            <div>
              <dt>Last reviewed</dt>
              <dd><?= $h((string) $selected['last_reviewed']) ?></dd>
            </div>
            <div>
              <dt>Owner</dt>
              <dd><?= $h((string) $selected['owner']) ?></dd>
            </div>
            <div>
              <dt>Metadata</dt>
              <dd><code><?= $h((string) $selected['metadata_path']) ?></code></dd>
            </div>
          </dl>

          <h4>Related production files</h4>
          <?= $renderList($selected['related_production_files']) ?>

          <h4>Related tests</h4>
          <?= $renderList($selected['related_tests']) ?>
        <?php } ?>
      </aside>
    </section>
  </section>

  <section class="modal_footer">
    <?php if ($selected !== null) { ?>
      <button
        type="button"
        class="btn btn_secondary"
        data-goldmaster-view-file
        aria-controls="goldmaster_file_preview"
        aria-expanded="<?= $showFile ? 'true' : 'false' ?>"
      ><?= $showFile ? 'Hide file' : 'View file' ?></button>
      <button type="button" class="btn btn_primary" data-goldmaster-copy>Copy path</button>
    <?php } ?>
    <button type="button" class="btn btn_secondary" data-dialog-close="goldmaster_dialog" commandfor="goldmaster_dialog" command="close">Close</button>
    <span class="goldmaster-copy-status" data-goldmaster-copy-status aria-live="polite"></span>
  </section>
</dialog>

<script
  type="module"
  src="<?= $h(Environment::appURL('js/admin/goldmaster.js') . '?v=' . rawurlencode(Environment::appVersion()) . '&m=' . rawurlencode((string) @filemtime(__DIR__ . '/../../js/admin/goldmaster.js'))) ?>"
  nonce="<?= $h($cspNonce) ?>"
></script>
<?php require_once HTML . '/footer.php'; ?>
