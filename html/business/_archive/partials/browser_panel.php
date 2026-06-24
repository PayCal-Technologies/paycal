<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <section class="businesses_browser_panel">
    <header class="businesses_browser_header">
      <h2 class="businesses_panel_title"><?php echo businesses_index_i18n_html('BUSINESSES_FIND_ANOTHER'); ?></h2>
      <p class="help_text"><?php echo businesses_index_i18n_html('BUSINESSES_FIND_ANOTHER_HELP'); ?></p>
    </header>
    <form id="businesses_browser_search_form" class="businesses_browser_search_form" method="dialog" role="search">
      <label for="businesses_browser_search_input" class="businesses_browser_search_label">Search by business name or owner email.</label>
      <div class="businesses_browser_search_controls">
        <input id="businesses_browser_search_input" type="search" maxlength="200" autocomplete="off" placeholder="<?php echo businesses_index_i18n_html('BUSINESSES_BROWSER_SEARCH_PLACEHOLDER'); ?>">
        <button id="businesses_browser_search_button" type="submit" class="btn btn_secondary"><?php echo businesses_index_i18n_html('SEARCH'); ?></button>
      </div>
    </form>
    <div class="visually_hidden">
      <p id="businesses_browser_grid_sr_instructions"><?php echo businesses_index_i18n_html('BUSINESSES_BROWSER_SR'); ?></p>
      <p id="businesses_browser_grid_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
    </div>
    <section class="businesses_browser_grid_block" aria-labelledby="businesses_browser_results_title">
      <h2 id="businesses_browser_results_title" class="businesses_panel_title"><?php echo businesses_index_i18n_html('BUSINESSES_BROWSER_SEARCH_RESULTS'); ?></h2>
      <div id="businesses-browser-grid" class="datagrid_container" role="region" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_BROWSER_RESULTS_ARIA'); ?>" aria-describedby="businesses_browser_grid_sr_instructions businesses_browser_grid_sr_status">
        <div class="datagrid_body"><div class="datagrid_empty"><?php echo businesses_index_i18n_html('BUSINESSES_BROWSER_SEARCH_EMPTY'); ?></div></div>
      </div>
    </section>
    <p id="businesses_browser_panel_status" class="help_text" role="status" aria-live="polite" aria-atomic="true"></p>
  </section>
