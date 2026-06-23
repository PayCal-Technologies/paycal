<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <dialog id="businesses_definitions_dialog" class="dialog businesses_definitions_dialog" aria-modal="true" aria-labelledby="businesses_definitions_title" aria-describedby="businesses_definitions_aria">
    <form method="dialog">
      <section class="modal_header businesses_definitions_dialog_header">
        <h2 id="businesses_definitions_title" class="modal_title"><?php echo businesses_index_i18n('BUSINESSES_DEFINITIONS_TITLE'); ?></h2>
        <button type="button" id="businesses_definitions_close" class="btn_close" aria-label="<?php echo businesses_index_i18n('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content f_column businesses_definitions_dialog_content">
        <p id="businesses_definitions_aria" class="help_text"><?php echo businesses_index_i18n('BUSINESSES_DEFINITIONS_ARIA'); ?></p>
        <div class="businesses_definitions_grid">
          <article class="businesses_definition_card" aria-labelledby="businesses_definition_type_title">
            <h3 id="businesses_definition_type_title"><?php echo businesses_index_i18n('BUSINESSES_DEFINITION_TYPE'); ?></h3>
            <dl class="businesses_definition_list">
              <dt><?php echo businesses_index_i18n('BUSINESSES_TYPE_PERSONAL'); ?></dt>
              <dd><?php echo businesses_index_i18n('BUSINESSES_DEF_TYPE_PERSONAL_DD'); ?></dd>
              <dt><?php echo businesses_index_i18n('BUSINESSES_TYPE_SHARED'); ?></dt>
              <dd><?php echo businesses_index_i18n('BUSINESSES_DEF_TYPE_SHARED_DD'); ?></dd>
            </dl>
          </article>

          <article class="businesses_definition_card" aria-labelledby="businesses_definition_role_title">
            <h3 id="businesses_definition_role_title"><?php echo businesses_index_i18n('BUSINESSES_ROLE'); ?></h3>
            <dl class="businesses_definition_list">
              <dt><?php echo businesses_index_i18n('BUSINESSES_ROLE_OWNER'); ?></dt>
              <dd><?php echo businesses_index_i18n('BUSINESSES_DEF_ROLE_OWNER_DD'); ?></dd>
              <dt><?php echo businesses_index_i18n('BUSINESSES_ROLE_COORDINATOR'); ?></dt>
              <dd><?php echo businesses_index_i18n('BUSINESSES_DEF_ROLE_COORDINATOR_DD'); ?></dd>
              <dt><?php echo businesses_index_i18n('BUSINESSES_ROLE_CONTRIBUTOR'); ?></dt>
              <dd><?php echo businesses_index_i18n('BUSINESSES_DEF_ROLE_CONTRIBUTOR_DD'); ?></dd>
              <dt><?php echo businesses_index_i18n('BUSINESSES_ROLE_VIEWER'); ?></dt>
              <dd><?php echo businesses_index_i18n('BUSINESSES_DEF_ROLE_VIEWER_DD'); ?></dd>
              <dt><?php echo businesses_index_i18n('BUSINESSES_ROLE_MEMBER'); ?></dt>
              <dd><?php echo businesses_index_i18n('BUSINESSES_DEF_ROLE_MEMBER_DD'); ?></dd>
            </dl>
          </article>

          <article class="businesses_definition_card" aria-labelledby="businesses_definition_status_title">
            <h3 id="businesses_definition_status_title"><?php echo businesses_index_i18n('STATUS'); ?></h3>
            <dl class="businesses_definition_list">
              <dt><?php echo businesses_index_i18n('BUSINESSES_STATUS_ACTIVE'); ?></dt>
              <dd><?php echo businesses_index_i18n('BUSINESSES_DEF_STATUS_ACTIVE_DD'); ?></dd>
              <dt><?php echo businesses_index_i18n('BUSINESSES_PENDING'); ?></dt>
              <dd><?php echo businesses_index_i18n('BUSINESSES_DEF_STATUS_PENDING_DD'); ?></dd>
            </dl>
          </article>
        </div>
      </section>
    </form>
  </dialog>

  <dialog id="businesses_current_details_dialog" class="dialog businesses_current_details_dialog" aria-modal="true" aria-labelledby="businesses_current_details_title" aria-describedby="businesses_current_details_aria">
    <form method="dialog">
      <section class="modal_header">
        <h2 id="businesses_current_details_title" class="modal_title"><?php echo businesses_index_i18n('BUSINESSES_CURRENT_DETAILS_TITLE'); ?></h2>
        <button type="button" class="btn_close" data-dialog-close="businesses_current_details_dialog" aria-label="<?php echo businesses_index_i18n('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content f_column businesses_current_details_content">
        <p id="businesses_current_details_aria" class="help_text"><?php echo businesses_index_i18n('BUSINESSES_CURRENT_DETAILS_ARIA'); ?></p>
        <div id="businesses_current_details_body" class="businesses_current_details_body"></div>
      </section>
      <section class="modal_footer">
        <button type="button" class="btn btn_secondary" data-dialog-close="businesses_current_details_dialog"><?php echo businesses_index_i18n('CLOSE'); ?></button>
      </section>
    </form>
  </dialog>

  <dialog id="businesses_membership_consent_dialog" class="dialog businesses_membership_consent_dialog" aria-modal="true" aria-labelledby="businesses_membership_consent_title" aria-describedby="businesses_membership_consent_desc">
    <form id="businesses_membership_consent_form" method="dialog">
      <section class="modal_header">
        <h2 id="businesses_membership_consent_title" class="modal_title"><?php echo businesses_index_i18n('BUSINESSES_MEMBERSHIP_CONSENT_TITLE'); ?></h2>
        <button type="button" id="businesses_membership_consent_close" class="btn_close" aria-label="<?php echo businesses_index_i18n('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content f_column businesses_membership_consent_content">
        <p id="businesses_membership_consent_desc" class="help_text"><?php echo businesses_index_i18n('BUSINESSES_MEMBERSHIP_CONSENT_DESC'); ?></p>
        <p id="businesses_membership_consent_action" class="businesses_membership_consent_action"></p>
        <section class="businesses_membership_consent_matrix" aria-labelledby="businesses_membership_consent_matrix_title">
          <h3 id="businesses_membership_consent_matrix_title" class="section_title_sm"><?php echo businesses_index_i18n('BUSINESSES_HIERARCHY_PERMISSIONS_TITLE'); ?></h3>
          <dl class="businesses_current_details_list">
            <dt>Protected work data</dt>
            <dd>Business-scoped hours, earnings, reports, and exports for business-managed sites only.</dd>
            <dt>Actor</dt>
            <dd>You, after signing in and accepting this membership action.</dd>
            <dt>Membership</dt>
            <dd>Must remain active before the business can read protected work data.</dd>
            <dt>Credential and envelope</dt>
            <dd>Each read must match your consent, active key wrap, and business encryption envelope.</dd>
            <dt>Current consent</dt>
            <dd id="businesses_membership_consent_current_ack">Not granted.</dd>
            <dt>Consent version</dt>
            <dd id="businesses_membership_consent_current_version">v1</dd>
            <dt>Protected sharing</dt>
            <dd id="businesses_membership_consent_current_sharing">Disabled until you consent.</dd>
            <dt>Revoke</dt>
            <dd>You can leave or revoke membership; future business reads must then fail closed.</dd>
          </dl>
        </section>
        <label for="businesses_membership_consent_disclaimer" class="form_label"><?php echo businesses_index_i18n('BUSINESSES_MEMBERSHIP_CONSENT_DISCLAIMER_LABEL'); ?></label>
        <textarea id="businesses_membership_consent_disclaimer" rows="3" maxlength="600" placeholder="<?php echo businesses_index_i18n('BUSINESSES_MEMBERSHIP_CONSENT_DISCLAIMER_PLACEHOLDER'); ?>"></textarea>
        <label for="businesses_membership_consent_ack" class="businesses_membership_consent_ack_label">
          <input id="businesses_membership_consent_ack" type="checkbox" value="1">
          <span><?php echo businesses_index_i18n('BUSINESSES_MEMBERSHIP_CONSENT_ACK_TEXT'); ?></span>
        </label>
        <p id="businesses_membership_consent_error" class="form_error hidden" role="status" aria-live="polite"></p>
      </section>
      <section class="modal_footer">
        <div class="flex f_center f_space_around">
          <button type="submit" id="businesses_membership_consent_confirm" class="btn btn_primary"><?php echo businesses_index_i18n('CONTINUE'); ?></button>
          <button type="button" id="businesses_membership_consent_cancel" class="btn btn_secondary"><?php echo businesses_index_i18n('CANCEL'); ?></button>
        </div>
      </section>
    </form>
  </dialog>
