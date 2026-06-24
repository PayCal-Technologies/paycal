<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <!-- Businesses Editor Dialog -->
  <dialog id="businesses_editor_dialog" class="dialog businesses_dialog" aria-modal="true" aria-labelledby="businesses_editor_title" aria-describedby="businesses_editor_aria">
    <div class="visually_hidden">
      <span id="businesses_editor_aria"><?php echo businesses_index_i18n_html('BUSINESSES_EDITOR_ARIA'); ?></span>
    </div>
    <form id="businesses_editor_form" method="dialog">
      <input type="hidden" id="businesses_editor_business_id" value="">

      <section class="modal_header businesses_dialog_header">
        <h2 id="businesses_editor_title" class="modal_title businesses_dialog_title"><?php echo businesses_index_i18n_html('BUSINESSES'); ?></h2>
          <div class="tablist_container" role="tablist" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_TAB_ARIA'); ?>">
          <button id="businesses_tab_details" type="button" class="tab_button" role="tab" aria-selected="false" aria-controls="businesses_tab_details_panel"><?php echo businesses_index_i18n_html('BUSINESSES_TAB_DETAILS'); ?></button>
          <button id="businesses_tab_members" type="button" class="tab_button tab_active" role="tab" aria-selected="true" aria-controls="businesses_tab_members_panel"><?php echo businesses_index_i18n_html('MEMBERS'); ?></button>
        </div>
        <div class="businesses_dialog_header_spacer" aria-hidden="true"></div>
      </section>

      <section class="modal_content businesses_dialog_content businesses_members_panel_hidden" id="businesses_tab_details_panel" role="tabpanel" aria-labelledby="businesses_tab_details">
        <div class="businesses_editor_grid">
          <section class="businesses_editor_card businesses_editor_card_full businesses_editor_panel businesses_owner_summary_card" title="The member with full business control and decision-making authority." data-hover-help="The member with full business control and decision-making authority.">
            <div class="businesses_section_header">
              <h3><?php echo businesses_index_i18n_html('BUSINESSES_CURRENT_OWNER_TITLE'); ?></h3>
            </div>
              <p class="help_text">The member with full business control and decision-making authority.</p>
            <div id="businesses_owner_summary" class="businesses_owner_summary_grid" role="status" aria-live="polite" aria-atomic="true"></div>
          </section>

          <section class="businesses_editor_card businesses_editor_card_full businesses_editor_panel" title="Membership type, role, and status for this workspace." data-hover-help="Membership type, role, and status for this workspace.">
            <h3><?php echo businesses_index_i18n_html('BUSINESSES_EDITOR_MEMBERSHIP_H3'); ?></h3>
            <div class="businesses_field_grid">
                <label for="businesses_editor_type"><?php echo businesses_index_i18n_html('BUSINESSES_TYPE'); ?></label>
                <select id="businesses_editor_type">
                  <option value="personal"><?php echo businesses_index_i18n_html('BUSINESSES_TYPE_PERSONAL'); ?></option>
                  <option value="shared"><?php echo businesses_index_i18n_html('BUSINESSES_TYPE_SHARED'); ?></option>
                </select>

                <label for="businesses_editor_role"><?php echo businesses_index_i18n_html('BUSINESSES_ROLE'); ?></label>
                <select id="businesses_editor_role">
                  <option value="owner"><?php echo businesses_index_i18n_html('BUSINESSES_ROLE_OWNER'); ?></option>
                  <option value="coordinator"><?php echo businesses_index_i18n_html('BUSINESSES_ROLE_COORDINATOR'); ?></option>
                  <option value="contributor"><?php echo businesses_index_i18n_html('BUSINESSES_ROLE_CONTRIBUTOR'); ?></option>
                  <option value="viewer"><?php echo businesses_index_i18n_html('BUSINESSES_ROLE_VIEWER'); ?></option>
                  <option value="member"><?php echo businesses_index_i18n_html('BUSINESSES_ROLE_MEMBER'); ?></option>
                </select>

                <label for="businesses_editor_status"><?php echo businesses_index_i18n_html('STATUS'); ?></label>
                <select id="businesses_editor_status">
                  <option value="active"><?php echo businesses_index_i18n_html('BUSINESSES_STATUS_ACTIVE'); ?></option>
                  <option value="pending"><?php echo businesses_index_i18n_html('BUSINESSES_PENDING'); ?></option>
                </select>

                <p class="help_text businesses_destructive_hint" role="note">Type, role, and status changes can be destructive. You will be asked to confirm sensitive transitions before they are saved.</p>
            </div>
          </section>

          <section class="businesses_editor_card businesses_editor_card_full businesses_editor_panel businesses_danger_zone_panel" title="High-risk ownership transfer controls. Use only when you intend to hand off owner authority." data-hover-help="High-risk ownership transfer controls. Use only when you intend to hand off owner authority.">
            <div class="businesses_section_header">
              <h3><?php echo businesses_index_i18n_html('BUSINESSES_DANGER_ZONE'); ?></h3>
            </div>
            <p id="businesses_transfer_notice" class="help_text"></p>
            <div class="businesses_inline_form">
              <input
                id="businesses_transfer_target"
                type="search"
                list="businesses_transfer_target_list"
                autocomplete="off"
                maxlength="180"
                placeholder="Select member..."
                aria-label="Search current member name for ownership transfer"
              >
              <datalist id="businesses_transfer_target_list"></datalist>
              <input id="businesses_transfer_target_uuid" type="hidden" value="">
              <button id="businesses_transfer_button" type="button" class="btn btn_delete"><?php echo businesses_index_i18n_html('BUSINESSES_TRANSFER_OWNERSHIP'); ?></button>
            </div>
            <div id="businesses_transfer_selected_member" class="businesses_transfer_selected_member businesses_empty" role="status" aria-live="polite" aria-atomic="true"></div>
            <div id="businesses_transfer_confirmation_container" class="businesses_transfer_confirmation_container businesses_empty">
              <label for="businesses_transfer_confirmation" class="businesses_transfer_confirmation_label">
                Type to confirm
              </label>
              <input
                id="businesses_transfer_confirmation"
                type="text"
                maxlength="22"
                autocomplete="off"
                placeholder="Type 'TRANSFER BUSINESS'"
                aria-label="Type TRANSFER BUSINESS to confirm ownership transfer"
              >
              <p class="help_text" id="businesses_transfer_confirmation_status"></p>
            </div>
            <p class="help_text businesses_danger_zone_disclaimer">Transfer ownership only to an active current member you trust. This immediately moves owner control and may be blocked by strict domain policy.</p>
          </section>

        </div>
      </section>

      <section class="modal_content businesses_dialog_content businesses_members_panel_hidden is-visible" id="businesses_tab_members_panel" role="tabpanel" aria-labelledby="businesses_tab_members">
        <div class="businesses_members_content f_column">
          
          <!-- Access Requests Section -->
          <section class="businesses_members_section" id="businesses_members_requests_section">
            <div class="businesses_section_header">
              <h3><?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_REQUESTS_H3'); ?></h3>
            </div>
            <div class="visually_hidden">
              <p id="businesses_access_requests_sr_instructions"><?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_REQUESTS_SR'); ?></p>
              <p id="businesses_access_requests_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
            </div>
            <div id="businesses_members_requests_list" class="businesses_requests_stack businesses_empty" role="region" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_REQUESTS_ARIA'); ?>" aria-describedby="businesses_access_requests_sr_instructions businesses_access_requests_sr_status">
              <p><?php echo businesses_index_i18n_html('BUSINESSES_NO_PENDING_REQUESTS'); ?></p>
            </div>
          </section>

          <!-- Member List Section -->
          <section class="businesses_members_section" id="businesses_members_invite_section">
            <div class="businesses_section_header">
              <h3><?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_H3'); ?></h3>
              <div class="members_list_controls">
                <select id="businesses_members_role_filter" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_FILTER_ARIA'); ?>">
                  <option value=""><?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_FILTER_ALL'); ?></option>
                  <option value="coordinator"><?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_ROLE_COORDINATOR'); ?></option>
                  <option value="contributor"><?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_ROLE_CONTRIBUTOR'); ?></option>
                  <option value="viewer"><?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_ROLE_VIEWER'); ?></option>
                  <option value="member"><?php echo businesses_index_i18n_html('BUSINESSES_ROLE_MEMBER'); ?></option>
                  <option value="owner"><?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_ROLE_OWNER'); ?></option>
                </select>
              </div>
            </div>
            <div class="visually_hidden">
              <p id="businesses_members_grid_sr_instructions"><?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_GRID_SR'); ?></p>
              <p id="businesses_members_grid_sr_context"><?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_GRID_CONTEXT'); ?></p>
              <p id="businesses_members_grid_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
            </div>
            <div id="businesses-members-grid" class="datagrid_container" role="region" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_MEMBERS_GRID_ARIA'); ?>" aria-describedby="businesses_members_grid_sr_instructions businesses_members_grid_sr_context businesses_members_grid_sr_status">
              <div class="datagrid_body"></div>
            </div>
          </section>

          <!-- Send Invite Section -->
          <section class="businesses_members_section">
            <div class="businesses_section_header">
              <h3><?php echo businesses_index_i18n_html('BUSINESSES_SEND_INVITE'); ?></h3>
            </div>
            <form id="businesses_members_invite_form" class="businesses_members_invite_form">
              <div class="form_group">
                <label for="businesses_members_invite_email"><?php echo businesses_index_i18n_html('BUSINESSES_INVITE_EMAIL_LABEL'); ?></label>
                <input type="email" id="businesses_members_invite_email" name="email" maxlength="160" autocomplete="email" placeholder="<?php echo businesses_index_i18n_html('BUSINESSES_INVITE_EMAIL_PLACEHOLDER'); ?>" required>
              </div>
              <div class="form_actions">
                <button type="submit" class="btn btn_primary"><?php echo businesses_index_i18n_html('BUSINESSES_SEND_INVITE'); ?></button>
              </div>
              <div id="businesses_members_invite_status" class="form_status_message" role="status" aria-live="polite"></div>
            </form>

            <div class="businesses_members_invites_grid">
              <div class="businesses_members_invites_block">
                <div class="businesses_section_header businesses_members_subsection_header">
                  <h4><?php echo businesses_index_i18n_html('BUSINESSES_INVITES_PENDING_H4'); ?></h4>
                </div>
                <div class="visually_hidden">
                  <p id="businesses_invites_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
                </div>
                <div id="businesses_members_invites_list" class="businesses_stack businesses_members_invites_listbody businesses_empty" role="region" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_INVITES_PENDING_ARIA'); ?>" aria-describedby="businesses_invites_sr_status"><?php echo businesses_index_i18n_html('BUSINESSES_INVITES_LOADING'); ?></div>
              </div>

              <div class="businesses_members_invites_block">
                <div class="businesses_section_header businesses_members_subsection_header">
                  <h4><?php echo businesses_index_i18n_html('BUSINESSES_INVITES_HISTORY_H4'); ?></h4>
                </div>
                <div id="businesses-invite-history-grid-host" class="datagrid_container businesses_members_invites_history_grid" role="region" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_INVITES_HISTORY_ARIA'); ?>">
                  <div class="datagrid_body"><div class="datagrid_empty"><?php echo businesses_index_i18n_html('BUSINESSES_INVITES_HISTORY_LOADING'); ?></div></div>
                </div>
              </div>
            </div>

            <div class="businesses_members_import_block businesses_members_import_card" id="businesses_members_import_section">
              <h4><?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_H4'); ?></h4>
              <p class="help_text businesses_members_import_intro"><?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_HELP'); ?></p>

              <div class="businesses_members_import_stepflow" role="list" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_WORKFLOW_ARIA'); ?>">
                <span class="businesses_members_import_stepflow_item" role="listitem"><?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_STEP_PARSE'); ?></span>
                <span class="businesses_members_import_stepflow_sep" aria-hidden="true">&gt;&gt;</span>
                <span class="businesses_members_import_stepflow_item" role="listitem"><?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_STEP_VERIFY'); ?></span>
                <span class="businesses_members_import_stepflow_sep" aria-hidden="true">&gt;&gt;</span>
                <span class="businesses_members_import_stepflow_item" role="listitem"><?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_STEP_IMPORT'); ?></span>
              </div>

              <div class="businesses_members_import_layout">
                <section class="businesses_members_import_column businesses_members_import_column_left" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_INPUT_ARIA'); ?>">
                  <div class="form_group businesses_members_import_textarea_group">
                    <label for="businesses_members_import_emails"><?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_EMAILS_LABEL'); ?></label>
                    <textarea
                      id="businesses_members_import_emails"
                      name="emails"
                      rows="10"
                      maxlength="20000"
                      placeholder="<?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_EMAILS_PLACEHOLDER'); ?>"
                    ></textarea>
                  </div>
                </section>

                <section class="businesses_members_import_column businesses_members_import_column_right" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_OUTPUT_ARIA'); ?>">
                  <h5 class="businesses_members_import_output_title"><?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_OUTPUT_H5'); ?></h5>
                  <div id="businesses_members_import_summary" class="businesses_members_import_summary_card businesses_empty" role="region" aria-label="<?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_SUMMARY_ARIA'); ?>"></div>
                  <div id="businesses_members_import_status" class="form_status_message businesses_members_import_status" role="status" aria-live="polite"></div>
                </section>
              </div>

              <div class="form_actions businesses_members_import_actions businesses_members_import_actions_row">
                <button id="businesses_members_import_prepare" type="button" class="btn btn_secondary"><?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_BTN_PARSE'); ?></button>
                <button id="businesses_members_import_send_code" type="button" class="btn btn_primary" disabled><?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_BTN_SEND_CODE'); ?></button>
                <input id="businesses_members_import_code" class="businesses_members_import_code_input" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="<?php echo businesses_index_i18n_html('UNVERIFIED_CODE_PLACEHOLDER'); ?>" disabled>
                <button id="businesses_members_import_verify" type="button" class="btn btn_secondary" disabled><?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_BTN_VERIFY'); ?></button>
                <button id="businesses_members_import_commit" type="button" class="btn btn_primary" disabled><?php echo businesses_index_i18n_html('BUSINESSES_IMPORT_BTN_COMMIT'); ?></button>
              </div>
            </div>
          </section>

        </div>
      </section>

      <section class="modal_footer businesses_dialog_footer">
        <button id="businesses_bootstrap_dek_button" type="button" class="btn btn_secondary">Create Business DEKs</button>
        <button id="businesses_save_button" type="button" class="btn btn_primary"><?php echo businesses_index_i18n_html('UPDATE'); ?></button>
        <button id="businesses_close_button" type="button" class="btn btn_secondary"><?php echo businesses_index_i18n_html('CLOSE'); ?></button>
      </section>

      <div id="businesses_dialog_live_toast" class="businesses_live_toast" role="status" aria-live="polite" aria-atomic="true"></div>
    </form>
  </dialog>
