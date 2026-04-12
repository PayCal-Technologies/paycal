<div id='status' class='status centered' aria-live='polite' role='status'>
  &nbsp;
</div>


<!-- SYSTEM LIMITS PANELS -->
  <section class='f_column panel w25 mar_sm pad_md' aria-label='__ADMIN_DASHBOARD_USER_MANAGEMENT_ARIA__'>
    <h2>__USERS__</h2>
    <ul id="user_list" class="user_list">
      __USER_LIST_HTML__
    </ul>
  </section>


  __SYSTEM_LIMITS_HTML__


<section class='admin_panel' aria-label='__ADMIN_DASHBOARD_SYSTEM_SETTINGS_ARIA__'>
  <h2 class='admin_panel_title'>__ADMIN_DASHBOARD_REGISTRATION_SETTINGS__</h2>
  <div class='admin_row'>
    <div class='admin_label'>__ADMIN_DASHBOARD_INVITE_CODE__</div>
    <div class='admin_control'>
      <input type="text" id="invite_code" value="__CURRENT_INVITE_CODE__" />
      <button class='btn btn_primary' id='update_invite_code'>__UPDATE__</button>
    </div>
  </div>
</section>

<section class='flex f_row panel w100 mar_sm pad_md admin-platform-metrics-panel' aria-label='__ADMIN_DASHBOARD_PLATFORM_METRICS_ARIA__'>
  <section class='f_column w100 pad_md'>
    <h2>__ADMIN_DASHBOARD_PLATFORM_METRICS_TITLE__</h2>
    <p class='text-muted'>__ADMIN_DASHBOARD_PLATFORM_METRICS_SUBTITLE__</p>
    
    <div class='admin-card-grid'>
      <div class='admin-card'>
        <div class='admin-card-header'>
          <h3>__ADMIN_DASHBOARD_METRICS_DASHBOARD_TITLE__</h3>
        </div>
        <div class='admin-card-body'>
          <p>__ADMIN_DASHBOARD_METRICS_DASHBOARD_BODY__</p>
        </div>
        <div class='admin-card-footer'>
          <a href='/admin/metrics' class='btn btn_primary'>__ADMIN_DASHBOARD_VIEW_DASHBOARD__</a>
        </div>
      </div>
      
      <div class='admin-card'>
        <div class='admin-card-header'>
          <h3>Operations Documentation</h3>
        </div>
        <div class='admin-card-body'>
          <p>Access operational guides, runbooks, and troubleshooting documentation including webhook queue management.</p>
        </div>
        <div class='admin-card-footer'>
          <a href='/admin/documentation' class='btn btn_primary'>View Documentation</a>
        </div>
      </div>
      
      <div class='admin-card'>
        <div class='admin-card-header'>
          <h3>__ADMIN_DASHBOARD_TRANSPARENCY_PAGE_TITLE__</h3>
        </div>
        <div class='admin-card-body'>
          <p>__ADMIN_DASHBOARD_TRANSPARENCY_PAGE_BODY__</p>
        </div>
        <div class='admin-card-footer'>
          <a href='/transparency/metrics' target='_blank' class='btn btn_secondary'>__ADMIN_DASHBOARD_VIEW_PUBLIC_PAGE__</a>
        </div>
      </div>

      __STRIPE_HEALTH_HTML__
    </div>
  </section>
</section>

<section class='flex f_row panel w100 mar_sm pad_md' aria-label='__ADMIN_DASHBOARD_GENERAL_HEALTH_ARIA__'>
  <section class='f_column w100 pad_md'>
    <h2>__ADMIN_DASHBOARD_GENERAL_HEALTH_TITLE__</h2>
    <p class='text-muted'>__ADMIN_DASHBOARD_GENERAL_HEALTH_SUBTITLE__</p>
    <div class='admin-card-grid'>
      __CONTACT_HEALTH_HTML__
    </div>
  </section>
</section>

<section class='flex f_row panel w100 mar_sm pad_md' aria-label='__ADMIN_DASHBOARD_TESTING_TOOLS_ARIA__'>
  <section class='f_column w100 pad_md'>
    <h2>__ADMIN_DASHBOARD_TESTING_TOOLS_TITLE__</h2>
    <p class='text-muted'>__ADMIN_DASHBOARD_TESTING_TOOLS_SUBTITLE__</p>
    
    <div class='admin-card'>
      <div class='admin-card-header'>
        <h3>__ADMIN_DASHBOARD_ORPHANED_WORK_TEST_TITLE__</h3>
      </div>
      <div class='admin-card-body'>
        <p>__ADMIN_DASHBOARD_ORPHANED_WORK_TEST_BODY__</p>
      </div>
      <div class='admin-card-footer'>
        <button class='btn btn_warning' id='btn_create_orphaned_work'>__ADMIN_DASHBOARD_GENERATE_TEST_DATA__</button>
        <span id='orphaned_work_result' class='test_result'></span>
      </div>
    </div>
  </section>
</section>

<!-- MODAL EDIT USER -->
<dialog
  id="modal_edit_user"
  role="dialog"
  aria-modal="true"
  aria-labelledby="modal_edit_user_title"
  aria-describedby="modal_edit_user_desc"
>
  <div class="modal_aria visually_hidden">
    <span id="modal_edit_user_aria">__ADMIN_EDIT_USER_MODAL_ARIA__</span>
  </div>
  <div class="modal_aria visually_hidden">
    <span id="modal_edit_user_desc">__ADMIN_EDIT_USER_MODAL_DESC__</span>
  </div>
  <div class="modal_meta visually_hidden">
    <span id="modal_edit_user_meta">__ADMIN_EDIT_USER_MODAL_META__</span>
  </div>
  <form
    action="__SITE__api/admin/user/update"
    method="POST"
    id="edit_user_form"
    name="edit_user"
  >
    <!-- Screen-reader-friendly username shim for password managers -->
    <input
      class="visually_hidden"
      type="text"
      name="username"
      value="NOTUSED"
      autocomplete="username"
      tabindex="-1"
      aria-hidden="true"
    >

    <input id="edit_user_uuid" type="hidden" name="user_uuid" value="">

    <section class="modal_header centered">
      <h1 id="modal_edit_user_title" class="modal_title">
        __ADMIN_EDIT_USER_TITLE__
      </h1>
      <!-- Explicit close control -->
      <button
        type="button"
        class="modal_close"
        aria-label="__ADMIN_EDIT_USER_CLOSE_ARIA__"
        data-action="close"
        data-dialog-close="modal_edit_user"
      >
        ×
      </button>
    </section>

    <section class="modal_content pad_md">
      <div class="form_grid-2">
        <!-- Left column -->
        <div class="form_col well">
          <label for="edit_full_name">
            Full Name <span aria-hidden="true">*</span>
          </label>
          <input
            type="text"
            id="edit_full_name"
            name="full_name"
            required
            aria-required="true"
            autocomplete="name"
          >

          <label for="edit_email">
            Email <span aria-hidden="true">*</span>
          </label>
          <input
            type="email"
            id="edit_email"
            name="email"
            required
            aria-required="true"
            autocomplete="email"
          >

          <label for="edit_phone">__ADMIN_USER_PHONE__</label>
          <input
            type="tel"
            id="edit_phone"
            name="phone"
            autocomplete="tel"
          >

        </div>

        <!-- Right column -->
        <div class="form_col well">
          <label for="edit_auth_level">__ADMIN_USER_AUTH_LEVEL__</label>
          <select
            id="edit_auth_level"
            name="auth_level"
          >
            <option value="guest">__ADMIN_USER_GUEST__</option>
            <option value="unverified">__ADMIN_USER_UNVERIFIED__</option>
            <option value="verified">__ADMIN_USER_VERIFIED__</option>
            <option value="admin">__ADMIN__</option>
          </select>

          <label for="edit_notes">__ADMIN_USER_NOTES__</label>
          <textarea
            id="edit_notes"
            name="notes"
            rows="10"
          ></textarea>
        </div>

        <section class="edit_user_dashboard_grid" aria-label="__ADMIN_USER_ACTIVITY_SECURITY_ARIA__">
          <section class="panel well edit_user_session" aria-label="__ADMIN_USER_SESSION_REGISTRATION_ARIA__">
            <h2 class="edit_user_session_title">__ADMIN_USER_ACTIVITY__</h2>
            <div class="edit_user_session_row">
              <span class="edit_user_session_label">__ADMIN_USER_REGISTERED__:</span>
              <span id="edit_registered_at" class="edit_user_session_value">__ADMIN_USER_UNKNOWN__</span>
            </div>
            <div class="edit_user_session_row">
              <span class="edit_user_session_label">Registered IP:</span>
              <span id="edit_registered_ip" class="edit_user_session_value">Unknown</span>
            </div>
            <div class="edit_user_session_row">
              <span class="edit_user_session_label">Last Login:</span>
              <span id="edit_last_login_at" class="edit_user_session_value">No login on record</span>
            </div>
            <div class="edit_user_session_row">
              <span class="edit_user_session_label">Last Login IP:</span>
              <span id="edit_last_login_ip" class="edit_user_session_value">Unknown</span>
            </div>
            <div class="edit_user_session_row edit_user_session_row_sep">
              <span class="edit_user_session_label">Seen:</span>
              <span id="edit_last_session_at" class="edit_user_session_value">No session on record</span>
            </div>
            <div class="edit_user_session_row">
              <span class="edit_user_session_label">IP:</span>
              <span id="edit_last_session_ip" class="edit_user_session_value">Unknown</span>
            </div>
            <div class="edit_user_session_row">
              <span class="edit_user_session_label">Session Hash:</span>
              <span id="edit_last_session_hash" class="edit_user_session_value">Unknown</span>
            </div>
          </section>

          <section class="panel well edit_user_security_dashboard" aria-label="__ADMIN_USER_SECURITY_DASHBOARD_ARIA__">
            <h2 class="edit_user_session_title">__ADMIN_USER_SECURITY_DASHBOARD__</h2>
            <div class="edit_user_session_row">
              <span class="edit_user_session_label">Auth Method:</span>
              <span id="edit_last_auth_method" class="edit_user_session_value">Unknown</span>
            </div>
            <div class="edit_user_session_row">
              <span class="edit_user_session_label">Passkeys:</span>
              <span id="edit_credential_count" class="edit_user_session_value">0</span>
            </div>
            <div class="edit_user_session_row">
              <span class="edit_user_session_label">Last Passkey Use:</span>
              <span id="edit_last_passkey_used_at" class="edit_user_session_value">Unknown</span>
            </div>
            <div class="edit_user_session_row edit_user_session_row_sep">
              <span class="edit_user_session_label">Account Flags:</span>
              <span id="edit_account_state_flags" class="edit_user_session_value">Unknown</span>
            </div>
          </section>
        </section>

        <!-- Footer row -->
        <section class="modal_footer form_footer">
          <div class="form_footer_left well">
            <button
              type="submit"
              class="btn btn_primary"
            >
              __UPDATE__
            </button>

            <button
              type="button"
              class="btn btn_cancel"
              data-action="close"
              data-dialog-close="modal_edit_user"
            >
              Close
            </button>

            <button
              type="button"
              class="btn btn_delete"
              id="edit_user_delete_trigger"
            >
              Delete
            </button>

            <div
              id="delete_user_confirm_pill"
              class="delete_user_confirm_pill hidden"
              aria-live="polite"
            >
              <span class="delete_user_confirm_text">Are you sure?</span>
              <button
                type="button"
                class="btn btn_warning delete_user_confirm_yes"
                id="edit_user_delete_yes"
              >
                Yes
              </button>
              <button
                type="button"
                class="btn btn_cancel delete_user_confirm_no"
                id="edit_user_delete_no"
              >
                No
              </button>
            </div>
          </div>
        </section>
      </div>
    </section>
  </form>
</dialog>

<script defer src="/js/admin/" type="module" nonce="__CSP_NONCE__"></script>
