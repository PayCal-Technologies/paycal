<?php declare(strict_types=1);

require_once __DIR__ . '/../../config.php';

use PayCal\Domain\Authentication;
use PayCal\Domain\AdminSurface;
use PayCal\Domain\InputSanitizer;
use PayCal\Domain\Strings;
use PayCal\Domain\User;
use PayCal\Domain\Config\Environment;
use PayCal\Observability\Lens;

if (function_exists('ast_i18n') === false) {
  /**
   * Resolve localized AST text with an English fallback.
   *
   * @param array<string, scalar> $replacements
   */
  function ast_i18n(string $key, string $fallback, array $replacements = []): string
  {
    static $cache = [];
    if (!array_key_exists($key, $cache)) {
      $translated = Strings::i18n($key);
      if (!is_string($translated) || trim($translated) === '' || $translated === $key) {
        $translated = $fallback;
      }
      $cache[$key] = $translated;
    }

    $text = $cache[$key];
    foreach ($replacements as $name => $value) {
      $text = str_replace('{' . (string) $name . '}', (string) $value, $text);
    }

    return $text;
  }
}

$currentPage = 'PAGE_ADMIN';
$pageTitle = ast_i18n('AST_GRAPH_PAGE_TITLE', 'AST') . ' - [' . ast_i18n('SITE_NAME', 'PayCal') . ']';
$pageLabel = ast_i18n('AST_GRAPH_PAGE_TITLE', 'AST');

Authentication::redirectHomeIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/ast/');

Lens::boot('admin-ast');
if (InputSanitizer::getString('lens') === '1') {
  Lens::add('Admin AST Page', ['path' => '/admin/ast/']);
}

require_once HTML . '/header.php';

$astClientI18n = [
  'loading' => ast_i18n('AST_LOADING', 'Loading...'),
  'node_info_default' => ast_i18n('AST_NODE_INFO_DEFAULT', 'Select a node.'),
  'ui_missing_elements' => ast_i18n('AST_UI_MISSING_ELEMENTS', 'AST UI missing elements'),
  'no_canvas_context' => ast_i18n('AST_NO_CANVAS_CONTEXT', 'No canvas context'),
  'endpoint_unavailable' => ast_i18n('AST_ENDPOINT_UNAVAILABLE', 'AST endpoint is unavailable'),
  'api_generic_error' => ast_i18n('AST_API_GENERIC_ERROR', 'AST request failed.'),
  'api_missing_q_parameter' => ast_i18n('AST_API_MISSING_Q_PARAMETER', 'Search query is required.'),
  'api_missing_root_parameter' => ast_i18n('AST_API_MISSING_ROOT_PARAMETER', 'Root node is required.'),
  'api_missing_from_to_parameter' => ast_i18n('AST_API_MISSING_FROM_TO_PARAMETER', 'Both source and target nodes are required.'),
  'api_missing_node_parameter' => ast_i18n('AST_API_MISSING_NODE_PARAMETER', 'Node is required.'),
  'api_unknown_action' => ast_i18n('AST_API_UNKNOWN_ACTION', 'Unknown AST action requested.'),
  'api_failed_read_graph_json' => ast_i18n('AST_API_FAILED_READ_GRAPH_JSON', 'Unable to read graph data.'),
  'api_graph_json_invalid' => ast_i18n('AST_API_GRAPH_JSON_INVALID', 'Graph data is invalid.'),
  'api_ast_source_directory_not_found' => ast_i18n('AST_API_SOURCE_DIRECTORY_NOT_FOUND', 'AST source directory was not found.'),
  'api_unable_resolve_graph_output_path' => ast_i18n('AST_API_UNABLE_RESOLVE_GRAPH_OUTPUT_PATH', 'Unable to resolve graph output path.'),
  'api_unable_create_graph_output_directory' => ast_i18n('AST_API_UNABLE_CREATE_GRAPH_OUTPUT_DIRECTORY', 'Unable to create graph output directory.'),
  'api_unable_encode_graph_json' => ast_i18n('AST_API_UNABLE_ENCODE_GRAPH_JSON', 'Unable to encode graph data.'),
  'api_unable_write_graph_json' => ast_i18n('AST_API_UNABLE_WRITE_GRAPH_JSON', 'Unable to write graph data.'),
  'search_placeholder' => ast_i18n('AST_SEARCH_PLACEHOLDER', 'Search class or method'),
  'button_generate' => ast_i18n('AST_BUTTON_GENERATE', 'Generate'),
  'button_focus' => ast_i18n('AST_BUTTON_FOCUS', 'Focus'),
  'button_reset' => ast_i18n('AST_BUTTON_RESET', 'Reset'),
  'button_view_2d' => ast_i18n('AST_BUTTON_VIEW_2D', '2D'),
  'button_view_3d' => ast_i18n('AST_BUTTON_VIEW_3D', '3D'),
  'graph_title' => ast_i18n('AST_GRAPH_TITLE', 'AST dependency graph visualization'),
  'graph_desc' => ast_i18n('AST_GRAPH_DESCRIPTION', 'Interactive dependency map showing relationships between classes and methods. Use Search plus Focus to jump to a node, click a node to inspect incoming and outgoing edges, and use Reset to clear selection and viewport pan or zoom. Keyboard: Arrow keys pan, plus or minus zoom, Home resets view, Enter selects the nearest node to the center, and asterisk switches 2D or 3D mode.'),
  'graph_loading_status' => ast_i18n('AST_GRAPH_LOADING_STATUS', 'AST dependency graph is loading.'),
  'graph_empty_status' => ast_i18n('AST_GRAPH_EMPTY_STATUS', 'No graph data found. Click "Generate" to build the AST dependency graph.'),
  'graph_empty_stats' => ast_i18n('AST_GRAPH_EMPTY_STATS', 'No graph yet'),
  'graph_stats_counts' => ast_i18n('AST_GRAPH_STATS_COUNTS', 'Nodes: {nodes} | Edges: {edges}'),
  'graph_loaded_status' => ast_i18n('AST_GRAPH_LOADED_STATUS', 'AST dependency graph loaded with {nodes} nodes and {edges} edges.'),
  'graph_load_failed' => ast_i18n('AST_GRAPH_LOAD_FAILED', 'Failed to load graph'),
  'graph_load_failed_status' => ast_i18n('AST_GRAPH_LOAD_FAILED_STATUS', 'AST dependency graph failed to load.'),
  'graph_generating_status' => ast_i18n('AST_GRAPH_GENERATING_STATUS', 'Generating AST dependency graph from source files.'),
  'graph_generating_stats' => ast_i18n('AST_GRAPH_GENERATING_STATS', 'Generating graph...'),
  'graph_generated_status' => ast_i18n('AST_GRAPH_GENERATED_STATUS', 'AST dependency graph generated. Reloading graph.'),
  'graph_generation_failed' => ast_i18n('AST_GRAPH_GENERATION_FAILED', 'Graph generation failed'),
  'graph_generation_failed_status' => ast_i18n('AST_GRAPH_GENERATION_FAILED_STATUS', 'AST graph generation failed.'),
  'details_type' => ast_i18n('AST_DETAILS_TYPE', 'Type'),
  'details_incoming' => ast_i18n('AST_DETAILS_INCOMING', 'incoming'),
  'details_outgoing' => ast_i18n('AST_DETAILS_OUTGOING', 'outgoing'),
  'details_incoming_heading' => ast_i18n('AST_DETAILS_INCOMING_HEADING', 'Incoming'),
  'details_outgoing_heading' => ast_i18n('AST_DETAILS_OUTGOING_HEADING', 'Outgoing'),
  'details_from' => ast_i18n('AST_DETAILS_FROM', 'From'),
  'details_to' => ast_i18n('AST_DETAILS_TO', 'To'),
  'details_none' => ast_i18n('AST_DETAILS_NONE', 'None'),
  'node_details' => ast_i18n('AST_NODE_DETAILS', 'Node Details'),
  'details_selected_status' => ast_i18n('AST_DETAILS_SELECTED_STATUS', 'Selected {id}. {incoming} incoming, {outgoing} outgoing.'),
  'focus_no_node' => ast_i18n('AST_FOCUS_NO_NODE', 'No node found: {query}'),
  'focus_no_node_status' => ast_i18n('AST_FOCUS_NO_NODE_STATUS', 'No AST node matched search query {query}.'),
  'reset_status' => ast_i18n('AST_RESET_STATUS', 'AST graph selection cleared and viewport reset.'),
  'kbd_pan_left' => ast_i18n('AST_KBD_PAN_LEFT', 'Panned left.'),
  'kbd_pan_right' => ast_i18n('AST_KBD_PAN_RIGHT', 'Panned right.'),
  'kbd_pan_up' => ast_i18n('AST_KBD_PAN_UP', 'Panned up.'),
  'kbd_pan_down' => ast_i18n('AST_KBD_PAN_DOWN', 'Panned down.'),
  'kbd_zoom_in' => ast_i18n('AST_KBD_ZOOM_IN', 'Zoomed in to {scale} percent.'),
  'kbd_zoom_out' => ast_i18n('AST_KBD_ZOOM_OUT', 'Zoomed out to {scale} percent.'),
  'kbd_toggle_view' => ast_i18n('AST_KBD_TOGGLE_VIEW', 'Switched view mode to {mode}.'),
  'kbd_reset_view' => ast_i18n('AST_KBD_RESET_VIEW', 'Viewport reset.'),
  'kbd_select_center' => ast_i18n('AST_KBD_SELECT_CENTER', 'Selected center-nearest node: {id}.'),
  'kbd_select_center_none' => ast_i18n('AST_KBD_SELECT_CENTER_NONE', 'No node found near center.'),
  'view_switched_2d' => ast_i18n('AST_VIEW_SWITCHED_2D', 'Switched to 2D map view.'),
  'view_switched_3d' => ast_i18n('AST_VIEW_SWITCHED_3D', 'Switched to 3D sphere view.'),
  'overlay_collapse' => ast_i18n('AST_OVERLAY_COLLAPSE', 'Hide details'),
  'overlay_expand' => ast_i18n('AST_OVERLAY_EXPAND', 'Show details'),
  'overlay_drag_handle' => ast_i18n('AST_OVERLAY_DRAG_HANDLE', 'Move details panel'),
  'overlay_collapsed_status' => ast_i18n('AST_OVERLAY_COLLAPSED_STATUS', 'Node details panel collapsed.'),
  'overlay_expanded_status' => ast_i18n('AST_OVERLAY_EXPANDED_STATUS', 'Node details panel expanded.'),
  'focus_mode_label' => ast_i18n('AST_FOCUS_MODE_LABEL', 'Focus'),
  'focus_hops_label' => ast_i18n('AST_FOCUS_HOPS_LABEL', 'Hops'),
  'focus_hops_one' => ast_i18n('AST_FOCUS_HOPS_ONE', '1 hop'),
  'focus_hops_two' => ast_i18n('AST_FOCUS_HOPS_TWO', '2 hops'),
  'focus_hops_three' => ast_i18n('AST_FOCUS_HOPS_THREE', '3 hops'),
  'violations_only_label' => ast_i18n('AST_VIOLATIONS_ONLY_LABEL', 'Violations only'),
  'cycle_clear_label' => ast_i18n('AST_CYCLE_CLEAR_LABEL', 'Clear cycle highlight'),
  'details_issues_heading' => ast_i18n('AST_DETAILS_ISSUES_HEADING', 'Issues'),
  'details_metrics_heading' => ast_i18n('AST_DETAILS_METRICS_HEADING', 'Metrics'),
  'details_structure_heading' => ast_i18n('AST_DETAILS_STRUCTURE_HEADING', 'Structure'),
  'details_highlight_cycle' => ast_i18n('AST_DETAILS_HIGHLIGHT_CYCLE', 'Highlight cycle'),
  'details_clear_cycle' => ast_i18n('AST_DETAILS_CLEAR_CYCLE', 'Clear cycle'),
  'details_no_issues' => ast_i18n('AST_DETAILS_NO_ISSUES', 'No issues detected.'),
  'details_yes' => ast_i18n('AST_DETAILS_YES', 'yes'),
  'details_no' => ast_i18n('AST_DETAILS_NO', 'no'),
  'details_not_available' => ast_i18n('AST_DETAILS_NOT_AVAILABLE', 'n/a'),
  'details_flag_isolated' => ast_i18n('AST_DETAILS_FLAG_ISOLATED', 'isolated'),
  'details_flag_root' => ast_i18n('AST_DETAILS_FLAG_ROOT', 'root'),
  'details_flag_leaf' => ast_i18n('AST_DETAILS_FLAG_LEAF', 'leaf'),
  'details_flag_internal' => ast_i18n('AST_DETAILS_FLAG_INTERNAL', 'internal'),
  'details_attr_file' => ast_i18n('AST_DETAILS_ATTR_FILE', 'File'),
  'details_attr_degree' => ast_i18n('AST_DETAILS_ATTR_DEGREE', 'Degree'),
  'details_attr_instability' => ast_i18n('AST_DETAILS_ATTR_INSTABILITY', 'Instability'),
  'details_attr_structural' => ast_i18n('AST_DETAILS_ATTR_STRUCTURAL', 'Structural'),
  'details_attr_dead_node' => ast_i18n('AST_DETAILS_ATTR_DEAD_NODE', 'Dead Node'),
  'details_attr_layer_violations' => ast_i18n('AST_DETAILS_ATTR_LAYER_VIOLATIONS', 'Layer Violations'),
  'details_attr_layer_source' => ast_i18n('AST_DETAILS_ATTR_LAYER_SOURCE', 'source'),
  'details_attr_layer_target' => ast_i18n('AST_DETAILS_ATTR_LAYER_TARGET', 'target'),
  'details_attr_cycle' => ast_i18n('AST_DETAILS_ATTR_CYCLE', 'Cycle Group'),
  'details_attr_cycle_id' => ast_i18n('AST_DETAILS_ATTR_CYCLE_ID', 'id'),
  'details_attr_cycle_size' => ast_i18n('AST_DETAILS_ATTR_CYCLE_SIZE', 'size'),
  'search_label' => ast_i18n('AST_SEARCH_LABEL', 'Search class or method'),
  'actions_group_label' => ast_i18n('AST_ACTIONS_GROUP_LABEL', 'AST graph actions'),
  'color_legend_label' => ast_i18n('AST_COLOR_LEGEND_LABEL', 'Color legend'),
  'color_legend_hint' => ast_i18n('AST_COLOR_LEGEND_HINT', 'Toggle categories to filter visible nodes and relationships.'),
  'color_legend_selected_hint' => ast_i18n('AST_COLOR_LEGEND_SELECTED_HINT', 'Selected node accent color.'),
  'color_controller' => ast_i18n('AST_COLOR_CONTROLLER', 'Controller'),
  'color_model' => ast_i18n('AST_COLOR_MODEL', 'Model or Entity'),
  'color_service' => ast_i18n('AST_COLOR_SERVICE', 'Service or Handler'),
  'color_middleware' => ast_i18n('AST_COLOR_MIDDLEWARE', 'Middleware or Guard'),
  'color_other' => ast_i18n('AST_COLOR_OTHER', 'Other'),
  'color_selected' => ast_i18n('AST_COLOR_SELECTED', 'Selected Node'),
  'metrics_panel_title' => ast_i18n('AST_METRICS_PANEL_TITLE', 'AST Structural Metrics'),
  'metrics_panel_subtitle' => ast_i18n('AST_METRICS_PANEL_SUBTITLE', 'Captured at commit-time and compared against the previous snapshot.'),
  'metrics_nodes' => ast_i18n('AST_METRICS_NODES', 'Nodes'),
  'metrics_edges' => ast_i18n('AST_METRICS_EDGES', 'Edges'),
  'metrics_cycles' => ast_i18n('AST_METRICS_CYCLES', 'Cycle Groups'),
  'metrics_dead_nodes' => ast_i18n('AST_METRICS_DEAD_NODES', 'Dead Nodes'),
  'metrics_layer_violations' => ast_i18n('AST_METRICS_LAYER_VIOLATIONS', 'Layer Violations'),
  'metrics_fan_out_hotspots' => ast_i18n('AST_METRICS_FAN_OUT_HOTSPOTS', 'Fan-out Hotspots'),
  'metrics_fan_in_hotspots' => ast_i18n('AST_METRICS_FAN_IN_HOTSPOTS', 'Fan-in Hotspots'),
  'metrics_instability_hotspots' => ast_i18n('AST_METRICS_INSTABILITY_HOTSPOTS', 'Instability Hotspots'),
  'metrics_delta_title' => ast_i18n('AST_METRICS_DELTA_TITLE', 'Delta vs Previous Capture'),
  'metrics_not_available' => ast_i18n('AST_METRICS_NOT_AVAILABLE', 'No commit capture found yet. Commit once to begin delta tracking.'),
];
?>

<link rel="stylesheet" href="<?php echo rtrim(Environment::appPublicURL(), '/'); ?>/css/admin-ast/">

<section class="w100 ast_page" aria-label="<?= htmlspecialchars(ast_i18n('AST_GRAPH_SECTION_LABEL', 'AST'), ENT_QUOTES, 'UTF-8') ?>">
  <div id="ast_i18n" class="visually_hidden" data-messages="<?= htmlspecialchars((string) json_encode($astClientI18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8') ?>"></div>
  <div class="ast_toolbar">
    <h1><?= htmlspecialchars(ast_i18n('AST_GRAPH_PAGE_TITLE', 'AST'), ENT_QUOTES, 'UTF-8') ?></h1>
    <label for="ast_search" class="visually_hidden"><?= htmlspecialchars(ast_i18n('AST_SEARCH_LABEL', 'Search class or method'), ENT_QUOTES, 'UTF-8') ?></label>
    <input id="ast_search" type="text" placeholder="<?= htmlspecialchars(ast_i18n('AST_SEARCH_PLACEHOLDER', 'Search class or method'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ast_i18n('AST_SEARCH_LABEL', 'Search class or method'), ENT_QUOTES, 'UTF-8') ?>" list="ast_search_list" autocomplete="off">
    <datalist id="ast_search_list"></datalist>
    <div role="group" aria-label="<?= htmlspecialchars(ast_i18n('AST_ACTIONS_GROUP_LABEL', 'AST graph actions'), ENT_QUOTES, 'UTF-8') ?>">
      <button id="ast_generate_btn" class="btn btn_secondary" type="button" aria-controls="ast_graph_canvas"><?= htmlspecialchars(ast_i18n('AST_BUTTON_GENERATE', 'Generate'), ENT_QUOTES, 'UTF-8') ?></button>
      <button id="ast_focus_btn" class="btn btn_primary" type="button" aria-controls="ast_graph_canvas"><?= htmlspecialchars(ast_i18n('AST_BUTTON_FOCUS', 'Focus'), ENT_QUOTES, 'UTF-8') ?></button>
      <button id="ast_reset_btn" class="btn" type="button" aria-controls="ast_graph_canvas"><?= htmlspecialchars(ast_i18n('AST_BUTTON_RESET', 'Reset'), ENT_QUOTES, 'UTF-8') ?></button>
      <span class="ast_view_pill" role="group" aria-label="<?= htmlspecialchars(ast_i18n('AST_VIEW_MODE_LABEL', 'View mode'), ENT_QUOTES, 'UTF-8') ?>">
        <button id="ast_view_2d_btn" class="btn btn_secondary ast_view_btn is-active" type="button" aria-controls="ast_graph_canvas" aria-pressed="true"><?= htmlspecialchars(ast_i18n('AST_BUTTON_VIEW_2D', '2D'), ENT_QUOTES, 'UTF-8') ?></button>
        <button id="ast_view_3d_btn" class="btn btn_secondary ast_view_btn" type="button" aria-controls="ast_graph_canvas" aria-pressed="false"><?= htmlspecialchars(ast_i18n('AST_BUTTON_VIEW_3D', '3D'), ENT_QUOTES, 'UTF-8') ?></button>
      </span>
      <span class="ast_diag_controls" role="group" aria-label="<?= htmlspecialchars(ast_i18n('AST_DIAGNOSTIC_CONTROLS_LABEL', 'Diagnostic controls'), ENT_QUOTES, 'UTF-8') ?>">
        <label class="ast_diag_toggle" for="ast_focus_mode">
          <input id="ast_focus_mode" type="checkbox" checked>
          <?= htmlspecialchars(ast_i18n('AST_FOCUS_MODE_LABEL', 'Focus'), ENT_QUOTES, 'UTF-8') ?>
        </label>
        <label class="ast_diag_toggle" for="ast_focus_hops">
          <?= htmlspecialchars(ast_i18n('AST_FOCUS_HOPS_LABEL', 'Hops'), ENT_QUOTES, 'UTF-8') ?>
          <select id="ast_focus_hops" class="ast_focus_hops_select" aria-label="<?= htmlspecialchars(ast_i18n('AST_FOCUS_HOPS_LABEL', 'Hops'), ENT_QUOTES, 'UTF-8') ?>">
            <option value="1"><?= htmlspecialchars(ast_i18n('AST_FOCUS_HOPS_ONE', '1 hop'), ENT_QUOTES, 'UTF-8') ?></option>
            <option value="2" selected><?= htmlspecialchars(ast_i18n('AST_FOCUS_HOPS_TWO', '2 hops'), ENT_QUOTES, 'UTF-8') ?></option>
            <option value="3"><?= htmlspecialchars(ast_i18n('AST_FOCUS_HOPS_THREE', '3 hops'), ENT_QUOTES, 'UTF-8') ?></option>
          </select>
        </label>
        <label class="ast_diag_toggle" for="ast_violations_only">
          <input id="ast_violations_only" type="checkbox">
          <?= htmlspecialchars(ast_i18n('AST_VIOLATIONS_ONLY_LABEL', 'Violations only'), ENT_QUOTES, 'UTF-8') ?>
        </label>
        <button id="ast_cycle_clear_btn" class="btn btn_secondary ast_cycle_clear_btn" type="button" hidden><?= htmlspecialchars(ast_i18n('AST_CYCLE_CLEAR_LABEL', 'Clear cycle highlight'), ENT_QUOTES, 'UTF-8') ?></button>
      </span>
    </div>
    <details class="ast_color_legend" aria-label="<?= htmlspecialchars(ast_i18n('AST_COLOR_LEGEND_LABEL', 'Color legend'), ENT_QUOTES, 'UTF-8') ?>">
      <summary class="ast_color_legend_summary" title="<?= htmlspecialchars(ast_i18n('AST_COLOR_LEGEND_LABEL', 'Color legend'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars(ast_i18n('AST_COLOR_LEGEND_LABEL', 'Color legend'), ENT_QUOTES, 'UTF-8') ?>">f</summary>
      <div class="ast_color_legend_panel">
        <p class="ast_color_legend_hint"><?= htmlspecialchars(ast_i18n('AST_COLOR_LEGEND_HINT', 'Toggle categories to filter visible nodes and relationships.'), ENT_QUOTES, 'UTF-8') ?></p>
        <ul class="ast_color_legend_list">
          <li>
            <label class="ast_filter_option" for="ast_filter_controller">
              <input id="ast_filter_controller" class="ast_filter_checkbox" type="checkbox" data-ast-filter="controller" checked>
              <span class="ast_color_swatch ast_color_swatch_controller"></span>
              <?= htmlspecialchars(ast_i18n('AST_COLOR_CONTROLLER', 'Controller'), ENT_QUOTES, 'UTF-8') ?>
            </label>
          </li>
          <li>
            <label class="ast_filter_option" for="ast_filter_model">
              <input id="ast_filter_model" class="ast_filter_checkbox" type="checkbox" data-ast-filter="model" checked>
              <span class="ast_color_swatch ast_color_swatch_model"></span>
              <?= htmlspecialchars(ast_i18n('AST_COLOR_MODEL', 'Model or Entity'), ENT_QUOTES, 'UTF-8') ?>
            </label>
          </li>
          <li>
            <label class="ast_filter_option" for="ast_filter_service">
              <input id="ast_filter_service" class="ast_filter_checkbox" type="checkbox" data-ast-filter="service" checked>
              <span class="ast_color_swatch ast_color_swatch_service"></span>
              <?= htmlspecialchars(ast_i18n('AST_COLOR_SERVICE', 'Service or Handler'), ENT_QUOTES, 'UTF-8') ?>
            </label>
          </li>
          <li>
            <label class="ast_filter_option" for="ast_filter_middleware">
              <input id="ast_filter_middleware" class="ast_filter_checkbox" type="checkbox" data-ast-filter="middleware" checked>
              <span class="ast_color_swatch ast_color_swatch_middleware"></span>
              <?= htmlspecialchars(ast_i18n('AST_COLOR_MIDDLEWARE', 'Middleware or Guard'), ENT_QUOTES, 'UTF-8') ?>
            </label>
          </li>
          <li>
            <label class="ast_filter_option" for="ast_filter_other">
              <input id="ast_filter_other" class="ast_filter_checkbox" type="checkbox" data-ast-filter="other" checked>
              <span class="ast_color_swatch ast_color_swatch_other"></span>
              <?= htmlspecialchars(ast_i18n('AST_COLOR_OTHER', 'Other'), ENT_QUOTES, 'UTF-8') ?>
            </label>
          </li>
          <li>
            <span class="ast_filter_option ast_filter_option_static">
              <span class="ast_color_swatch ast_color_swatch_selected"></span>
              <?= htmlspecialchars(ast_i18n('AST_COLOR_SELECTED', 'Selected Node'), ENT_QUOTES, 'UTF-8') ?>
              <small class="ast_filter_selected_hint"><?= htmlspecialchars(ast_i18n('AST_COLOR_LEGEND_SELECTED_HINT', 'Selected node accent color.'), ENT_QUOTES, 'UTF-8') ?></small>
            </span>
          </li>
        </ul>
      </div>
    </details>
    <span id="ast_stats" class="ast_stats" role="status" aria-live="polite" aria-atomic="true"><?= htmlspecialchars(ast_i18n('AST_LOADING', 'Loading...'), ENT_QUOTES, 'UTF-8') ?></span>
  </div>

  <section class="panel ast_metrics_panel" aria-label="<?= htmlspecialchars(ast_i18n('AST_METRICS_PANEL_TITLE', 'AST Structural Metrics'), ENT_QUOTES, 'UTF-8') ?>">
    <header class="ast_metrics_head">
      <h2><?= htmlspecialchars(ast_i18n('AST_METRICS_PANEL_TITLE', 'AST Structural Metrics'), ENT_QUOTES, 'UTF-8') ?></h2>
      <p><?= htmlspecialchars(ast_i18n('AST_METRICS_PANEL_SUBTITLE', 'Captured at commit-time and compared against the previous snapshot.'), ENT_QUOTES, 'UTF-8') ?></p>
    </header>
    <div class="ast_metrics_grid">
      <article class="panel ast_metric_card">
        <h3><?= htmlspecialchars(ast_i18n('AST_METRICS_NODES', 'Nodes'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p id="ast_metric_nodes" class="ast_metric_value">-</p>
      </article>
      <article class="panel ast_metric_card">
        <h3><?= htmlspecialchars(ast_i18n('AST_METRICS_EDGES', 'Edges'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p id="ast_metric_edges" class="ast_metric_value">-</p>
      </article>
      <article class="panel ast_metric_card">
        <h3><?= htmlspecialchars(ast_i18n('AST_METRICS_CYCLES', 'Cycle Groups'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p id="ast_metric_cycles" class="ast_metric_value">-</p>
      </article>
      <article class="panel ast_metric_card">
        <h3><?= htmlspecialchars(ast_i18n('AST_METRICS_DEAD_NODES', 'Dead Nodes'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p id="ast_metric_dead_nodes" class="ast_metric_value">-</p>
      </article>
      <article class="panel ast_metric_card">
        <h3><?= htmlspecialchars(ast_i18n('AST_METRICS_LAYER_VIOLATIONS', 'Layer Violations'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p id="ast_metric_layer_violations" class="ast_metric_value">-</p>
      </article>
      <article class="panel ast_metric_card">
        <h3><?= htmlspecialchars(ast_i18n('AST_METRICS_FAN_OUT_HOTSPOTS', 'Fan-out Hotspots'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p id="ast_metric_fan_out_hotspots" class="ast_metric_value">-</p>
      </article>
      <article class="panel ast_metric_card">
        <h3><?= htmlspecialchars(ast_i18n('AST_METRICS_FAN_IN_HOTSPOTS', 'Fan-in Hotspots'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p id="ast_metric_fan_in_hotspots" class="ast_metric_value">-</p>
      </article>
      <article class="panel ast_metric_card">
        <h3><?= htmlspecialchars(ast_i18n('AST_METRICS_INSTABILITY_HOTSPOTS', 'Instability Hotspots'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p id="ast_metric_instability_hotspots" class="ast_metric_value">-</p>
      </article>
    </div>
    <article class="panel ast_metric_delta_panel" aria-label="<?= htmlspecialchars(ast_i18n('AST_METRICS_DELTA_TITLE', 'Delta vs Previous Capture'), ENT_QUOTES, 'UTF-8') ?>">
      <h3><?= htmlspecialchars(ast_i18n('AST_METRICS_DELTA_TITLE', 'Delta vs Previous Capture'), ENT_QUOTES, 'UTF-8') ?></h3>
      <pre id="ast_metrics_delta" class="ast_metric_delta">-</pre>
    </article>
  </section>

  <div class="ast_canvas_wrap">
    <div class="visually_hidden">
      <p id="ast_graph_title"><?= htmlspecialchars(ast_i18n('AST_GRAPH_TITLE', 'AST dependency graph visualization'), ENT_QUOTES, 'UTF-8') ?></p>
      <p id="ast_graph_desc"><?= htmlspecialchars(ast_i18n('AST_GRAPH_DESCRIPTION', 'Interactive dependency map showing relationships between classes and methods. Use Search plus Focus to jump to a node, click a node to inspect incoming and outgoing edges, and use Reset to clear selection and viewport pan or zoom.'), ENT_QUOTES, 'UTF-8') ?></p>
      <p id="ast_graph_status" role="status" aria-live="polite" aria-atomic="true"><?= htmlspecialchars(ast_i18n('AST_GRAPH_LOADING_STATUS', 'AST dependency graph is loading.'), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <canvas id="ast_graph_canvas" role="img" tabindex="0" aria-labelledby="ast_graph_title" aria-describedby="ast_graph_desc ast_graph_status" aria-keyshortcuts="ArrowUp ArrowDown ArrowLeft ArrowRight + - Home Enter *"></canvas>
    <div id="ast_node_overlay" class="ast_node_info ast_node_info_overlay">
      <div id="ast_node_overlay_head" class="ast_node_overlay_head" role="button" tabindex="0" aria-controls="ast_node_info" aria-expanded="true" data-state="open">
        <span class="ast_node_overlay_chevron" aria-hidden="true">▾</span>
        <strong class="ast_node_overlay_title"><?= htmlspecialchars(ast_i18n('AST_NODE_DETAILS', 'Node Details'), ENT_QUOTES, 'UTF-8') ?></strong>
      </div>
      <div id="ast_node_info" class="pad_md ast_node_info_content" role="status" aria-live="polite" aria-atomic="true"><?= htmlspecialchars(ast_i18n('AST_NODE_INFO_DEFAULT', 'Select a node.'), ENT_QUOTES, 'UTF-8') ?></div>
      <button id="ast_node_overlay_resize_grip" class="ast_node_overlay_resize_grip" type="button" tabindex="-1" aria-hidden="true"></button>
    </div>
  </div>
</section>

<script defer src="<?php echo rtrim(Environment::appPublicURL(), '/'); ?>/js/admin-ast/?v=<?php echo rawurlencode(Environment::appVersion()); ?>&m=<?php echo rawurlencode((string) @filemtime(__DIR__ . '/../../js/admin-ast/index.php')); ?>" type="module" nonce="<?php echo User::nonce(); ?>"></script>

<?php
require_once HTML . '/footer.php';
