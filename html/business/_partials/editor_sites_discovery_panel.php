<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Site discovery panel — mounted on Business Sites page (Phase 5).
 */
?>
  <section class="panel businesses_panel_sites_discovery" title="<?php echo businesses_index_i18n_html('BUSINESSES_DISCOVERY_HELP'); ?>" data-hover-help="<?php echo businesses_index_i18n_html('BUSINESSES_DISCOVERY_HELP'); ?>">
    <div class="businesses_section_header">
      <h3><?php echo businesses_index_i18n_html('BUSINESSES_DISCOVERY'); ?></h3>
      <button id="businesses_discovery_run" type="button" class="btn btn_secondary"><?php echo businesses_index_i18n_html('BUSINESSES_RUN_DISCOVERY'); ?></button>
    </div>
    <p class="help_text"><?php echo businesses_index_i18n_html('BUSINESSES_DISCOVERY_HELP'); ?></p>
    <div class="visually_hidden">
      <p id="businesses_discovery_sr_instructions"><?php echo businesses_index_i18n_html('BUSINESSES_DISCOVERY_SR'); ?></p>
      <p id="businesses_discovery_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
    </div>
    <div id="businesses_discovery_results" class="businesses_stack businesses_empty" role="region" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_DISCOVERY_RESULTS_ARIA'); ?>" aria-describedby="businesses_discovery_sr_instructions businesses_discovery_sr_status"><?php echo businesses_index_i18n_html('BUSINESSES_NO_DISCOVERY'); ?></div>
  </section>
