<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <dialog id="businesses_route_gate_dialog" class="dialog businesses_route_gate_dialog" aria-modal="true" aria-labelledby="businesses_route_gate_title" aria-describedby="businesses_route_gate_desc">
    <form method="dialog">
      <section class="modal_header">
        <h2 id="businesses_route_gate_title" class="modal_title"><?php echo businesses_index_i18n_html('BUSINESS_ROUTE_GATE_TITLE'); ?></h2>
        <button type="button" id="businesses_route_gate_close_x" class="btn_close" aria-label="<?php echo businesses_index_i18n_html('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content f_column">
        <p id="businesses_route_gate_desc" class="help_text"><?php echo businesses_index_i18n_html('BUSINESS_ROUTE_GATE_HELP'); ?></p>
      </section>
      <section class="modal_footer">
        <div class="flex f_center f_space_around">
          <button type="button" id="businesses_route_gate_billing_btn" class="btn btn_primary"><?php echo businesses_index_i18n_html('BUSINESS_ROUTE_GATE_BILLING_BTN'); ?></button>
          <button type="button" id="businesses_route_gate_close_btn" class="btn btn_secondary"><?php echo businesses_index_i18n_html('CLOSE'); ?></button>
        </div>
      </section>
    </form>
  </dialog>
