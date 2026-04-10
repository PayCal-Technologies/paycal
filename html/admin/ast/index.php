<?php declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

use PayCal\Domain\Authentication;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\User;
use PayCal\Domain\Config\Environment;
use PayCal\Observability\Lens;

$currentPage = 'PAGE_ADMIN';
$pageTitle = 'AST Graph - [PayCal]';
$pageLabel = 'AST Graph';

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/ast/');

Lens::boot('admin-ast');
if (InputSanitizer::getString('lens') === '1') {
  Lens::add('Admin AST Page', ['path' => '/admin/ast/']);
}

require_once HTML . '/header.php';
?>

<section class="panel w100 mar_sm pad_md" aria-label="AST Graph">
  <h1>AST Dependency Graph</h1>
  <p>Loads graph data via <code>/admin/ast/data/?action=graph</code> from <code>tmp/ast/dependency-graph.json</code>.</p>
</section>

<section class="panel w100 mar_sm pad_md" aria-label="AST Controls">
  <label for="ast_search">Search</label>
  <input id="ast_search" type="text" placeholder="class or method">
  <button id="ast_focus_btn" class="btn btn_primary" type="button">Focus</button>
  <button id="ast_reset_btn" class="btn" type="button">Reset</button>
  <div id="ast_stats" class="mar_sm" role="status" aria-live="polite" aria-atomic="true">Loading...</div>
</section>

<section class="panel w100 mar_sm pad_md" aria-label="AST Canvas">
  <div class="visually_hidden">
    <p id="ast_graph_title">AST dependency graph visualization</p>
    <p id="ast_graph_desc">Interactive dependency map showing relationships between classes and methods. Use Search plus Focus to jump to a node, click a node to inspect incoming and outgoing edges, and use Reset to clear selection and viewport pan or zoom.</p>
    <p id="ast_graph_status" role="status" aria-live="polite" aria-atomic="true">AST dependency graph is loading.</p>
  </div>
  <canvas id="ast_graph_canvas" role="img" tabindex="0" aria-labelledby="ast_graph_title" aria-describedby="ast_graph_desc ast_graph_status"></canvas>
  <div id="ast_node_info" class="mar_sm" role="status" aria-live="polite" aria-atomic="true">Select a node.</div>
</section>

<script defer src="<?php echo rtrim(Environment::appPublicURL(), '/'); ?>/js/admin-ast/" type="module" nonce="<?php echo User::nonce(); ?>"></script>

<?php
require_once HTML . '/footer.php';
