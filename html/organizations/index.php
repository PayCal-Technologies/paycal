<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Observability\Lens;

/**
 * Organizations Page.
 *
 * Organization-centered management surface for shared organizations and member access.
 */
$currentPage = 'PAGE_ORGANIZATIONS';

require_once '../config.php';

if (function_exists('organizations_index_i18n') === false) {
  function organizations_index_i18n(string $key): string
  {
    static $cache = [];
    if (array_key_exists($key, $cache) === false) {
      $cache[$key] = Strings::i18n($key);
    }

    return $cache[$key];
  }
}

Authentication::redirectHomeIfUnauthenticated();

/** @var User $user */
$user = User::current();
$userUUID = User::currentUUID();
$hasActivePremiumSubscription = $userUUID !== '' && SubscriptionGate::hasActivePremium($userUUID);
$isFreeProfile = !User::isAdmin() && !$hasActivePremiumSubscription;

Lens::boot('organizations');

$pageTitle = organizations_index_i18n('ORGANIZATIONS') . ' - [' . organizations_index_i18n('SITE_NAME') . ']';
$pageLabel = organizations_index_i18n('ORGANIZATIONS');
$pageLanguage = (string) User::current()->language;
$organizationsCsrfNonce = User::current()->generateFormNonce('organizations');
$graceDaysMin = (int) SystemLimits::get('editing_grace_days_min');
$graceDaysMax = (int) SystemLimits::get('editing_grace_days_max');

if (InputSanitizer::getString('lens') === '1') {
  $organizationIds = Database::smembers(\PayCal\Domain\Constants\Keys::ORGANIZATION_USER . ':' . $userUUID);

  Lens::add('Organizations Backend Snapshot', [
    'page' => $currentPage,
    'organization_count' => count($organizationIds),
    'is_admin' => User::isAdmin(),
    'is_manager' => User::isManager(),
  ]);
}

require_once \PayCal\Domain\Config\Environment::appHome().'html/header.php';
?>

  <h1 class="visually_hidden"><?php echo organizations_index_i18n('ORGANIZATIONS'); ?></h1>

<?php if (!$isFreeProfile) { ?>
  <section class="panel organizations_top_caution" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_ROLLOUT_NOTICE_ARIA'); ?>">
    <p class="organizations_top_caution_title"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLLOUT_NOTICE_TITLE'); ?></p>
    <p><strong><?php echo organizations_index_i18n('ORGANIZATIONS_ROLLOUT_NOTICE_STRONG'); ?></strong> <?php echo organizations_index_i18n('ORGANIZATIONS_ROLLOUT_NOTICE_SUFFIX'); ?></p>
  </section>
<?php } ?>

<?php if (!$isFreeProfile) { ?>
  <div class="organizations_top_columns">
    <!-- Organizations Grid Panel -->
    <div class="f_column w100 organizations_top_column_primary">
      <section class="w100 panel organizations_header_panel" title="<?php echo organizations_index_i18n('ORGANIZATIONS_HEADER_PANEL_HELP'); ?>" data-hover-help="<?php echo organizations_index_i18n('ORGANIZATIONS_HEADER_PANEL_HELP'); ?>">
        <div class="grid_header flex f_between f_center_y">
          <h2 class="f_grow"><?php echo organizations_index_i18n('ORGANIZATIONS'); ?></h2>
          <div class="organizations_header_actions">
            <button id="organizations_create_button" type="button" class="btn btn_primary organizations_create_btn">
              <?php echo organizations_index_i18n('ORGANIZATIONS_CREATE'); ?>
            </button>
            <button
              id="organizations_definitions_help_button"
              type="button"
              class="organizations_help_button"
              aria-haspopup="dialog"
              aria-controls="organizations_definitions_dialog"
              aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_OPEN_DEFINITIONS_BTN'); ?>"
              title="<?php echo organizations_index_i18n('ORGANIZATIONS_DEFINITIONS_TITLE'); ?>"
            >
              ?
            </button>
          </div>
        </div>
      </section>

      <section class="f_column panel organizations_grid_panel" title="<?php echo organizations_index_i18n('ORGANIZATIONS_GRID_PANEL_HELP'); ?>" data-hover-help="<?php echo organizations_index_i18n('ORGANIZATIONS_GRID_PANEL_HELP'); ?>">
        <div class="visually_hidden">
          <p id="organizations_grid_sr_instructions"><?php echo organizations_index_i18n('ORGANIZATIONS_GRID_SR_INSTRUCTIONS'); ?></p>
          <p id="organizations_grid_sr_context"><?php echo organizations_index_i18n('ORGANIZATIONS_GRID_SR_CONTEXT'); ?></p>
          <p id="organizations_grid_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
        </div>
          <div id="organizations-grid" class="datagrid_container" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_GRID_ARIA'); ?>" aria-describedby="organizations_grid_sr_instructions organizations_grid_sr_context organizations_grid_sr_status">
          <div class="datagrid_body">
            <div class="organizations_grid_placeholder" aria-hidden="true">
              <p><?php echo organizations_index_i18n('ORGANIZATIONS_GRID_PLACEHOLDER'); ?></p>
            </div>
          </div>
        </div>
      </section>

    </div>

    <section class="panel organizations_hub_panel organizations_top_column_live organizations_live_requests_panel" id="organizations-live-requests-panel" title="<?php echo organizations_index_i18n('ORGANIZATIONS_LIVE_REQUESTS_PANEL_HELP'); ?>" data-hover-help="<?php echo organizations_index_i18n('ORGANIZATIONS_LIVE_REQUESTS_PANEL_HELP'); ?>">
      <div class="organizations_section_header">
        <h2 id="organizations_live_requests_title"><?php echo organizations_index_i18n('ORGANIZATIONS_LIVE_REQUESTS_TITLE'); ?></h2>
      </div>
      <div class="visually_hidden">
        <p id="organizations_live_requests_sr_instructions"><?php echo organizations_index_i18n('ORGANIZATIONS_LIVE_REQUESTS_SR'); ?></p>
        <p id="organizations_live_requests_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
      </div>
      <div id="organizations_live_requests_list" class="organizations_stack organizations_empty" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_LIVE_REQUESTS_ARIA'); ?>" aria-describedby="organizations_live_requests_sr_instructions organizations_live_requests_sr_status">
        <p><?php echo organizations_index_i18n('ORGANIZATIONS_NO_PENDING_REQUESTS'); ?></p>
      </div>
    </section>
  </div>
  <section class="panel organizations_definitions_panel" aria-labelledby="organizations_hierarchy_guide_title">
    <div class="organizations_section_header">
      <h2 id="organizations_hierarchy_guide_title">Hierarchy, Permissions, and Consequences</h2>
    </div>
    <p class="help_text organizations_hierarchy_intro">This defines the operating model for shared organizations.</p>
    <div class="organizations_hierarchy_consequence_strip" role="note" aria-label="Hierarchy permissions and consequences">
      <section class="organizations_hierarchy_section_panel" aria-labelledby="organizations_hierarchy_ownership_title">
        <h3 id="organizations_hierarchy_ownership_title" class="organizations_hierarchy_section_title">Ownership</h3>
        <ul class="organizations_hierarchy_list">
          <li>Ownership transfer is immediate and exclusive.</li>
          <li>The previous owner is automatically demoted to manager.</li>
          <li>Only the current owner can transfer ownership.</li>
        </ul>
      </section>
      <section class="organizations_hierarchy_section_panel" aria-labelledby="organizations_hierarchy_roles_title">
        <h3 id="organizations_hierarchy_roles_title" class="organizations_hierarchy_section_title organizations_hierarchy_section_title_roles">Roles</h3>
        <ul class="organizations_hierarchy_list organizations_hierarchy_roles_list">
          <li><strong>Owner</strong><br>Full system control (settings, access, audit, ownership).</li>
          <li><strong>Manager</strong><br>Full operational control, including access management, but no ownership authority.</li>
          <li><strong>Contributor</strong><br>Can create and edit organization-level work, but cannot modify financial data.</li>
          <li><strong>Member</strong><br>Can create and edit only their own work (self scope).</li>
          <li><strong>Viewer</strong><br>Read-only access to non-sensitive operational and financial data.</li>
        </ul>
      </section>
      <section class="organizations_hierarchy_section_panel" aria-labelledby="organizations_hierarchy_permissions_title">
        <h3 id="organizations_hierarchy_permissions_title" class="organizations_hierarchy_section_title">Permissions and Scope</h3>
        <ul class="organizations_hierarchy_list">
          <li>Permissions are role-based with scope constraints.</li>
          <li>Work actions require both capability (write access) and scope validation (org or self).</li>
          <li>Financial write access (wages, pay periods) is restricted to owner and manager.</li>
        </ul>
      </section>
      <section class="organizations_hierarchy_section_panel" aria-labelledby="organizations_hierarchy_consequences_title">
        <h3 id="organizations_hierarchy_consequences_title" class="organizations_hierarchy_section_title">Consequences</h3>
        <ul class="organizations_hierarchy_list">
          <li>Permission changes are applied immediately.</li>
          <li>Updates to role, status, or ownership take effect in real time.</li>
          <li>No action is permitted beyond the user's current role and scope.</li>
        </ul>
      </section>
    </div>
    <div class="organizations_hierarchy_table_wrap">
      <table class="organizations_hierarchy_table">
        <thead>
          <tr>
            <th scope="col">Permission / Feature</th>
            <th scope="col"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_OWNER'); ?></th>
            <th scope="col"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_COORDINATOR'); ?></th>
            <th scope="col"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_CONTRIBUTOR'); ?></th>
            <th scope="col"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_VIEWER'); ?></th>
            <th scope="col"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_MEMBER'); ?></th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <th scope="row">Ownership transfer</th>
            <td>✓</td><td>-</td><td>-</td><td>-</td><td>-</td>
          </tr>
          <tr>
            <th scope="row">Org settings read</th>
            <td>✓</td><td>✓</td><td>✓</td><td>✓</td><td>✓</td>
          </tr>
          <tr>
            <th scope="row">Org settings write</th>
            <td>✓</td><td>✓</td><td>-</td><td>-</td><td>-</td>
          </tr>
          <tr>
            <th scope="row">Access management</th>
            <td>✓</td><td>✓</td><td>-</td><td>-</td><td>-</td>
          </tr>
          <tr>
            <th scope="row">Audit timeline read</th>
            <td>✓</td><td>✓</td><td>✓</td><td>✓</td><td>✓</td>
          </tr>
          <tr>
            <th scope="row">Org Details read</th>
            <td>✓</td><td>✓</td><td>✓</td><td>✓</td><td>✓</td>
          </tr>
          <tr>
            <th scope="row">Pay period read</th>
            <td>✓</td><td>✓</td><td>✓</td><td>✓</td><td>✓</td>
          </tr>
          <tr>
            <th scope="row">Pay period write</th>
            <td>✓</td><td>✓</td><td>✓</td><td>-</td><td>-</td>
          </tr>
          <tr>
            <th scope="row">Wage read</th>
            <td>✓</td><td>✓</td><td>✓</td><td>✓</td><td>✓</td>
          </tr>
          <tr>
            <th scope="row">Wage write</th>
            <td>✓</td><td>✓</td><td>-</td><td>-</td><td>-</td>
          </tr>
          <tr>
            <th scope="row">Sites read</th>
            <td>✓</td><td>✓</td><td>✓</td><td>✓</td><td>✓</td>
          </tr>
          <tr>
            <th scope="row">Sites write</th>
            <td>✓</td><td>✓</td><td>✓</td><td>-</td><td>-</td>
          </tr>
          <tr>
            <th scope="row">Work read</th>
            <td>✓</td><td>✓</td><td>✓</td><td>✓</td><td>✓</td>
          </tr>
          <tr>
            <th scope="row">Work write</th>
            <td>✓</td><td>✓</td><td>✓</td><td>-</td><td>✓</td>
          </tr>
          <tr>
            <th scope="row">Work scope</th>
            <td>Org</td><td>Org</td><td>Org</td><td>-</td><td>Self</td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

  <div class="organizations_separator" role="separator" aria-hidden="true"></div>
<?php } ?>

<?php if ($isFreeProfile) { ?>
  <section id="organizations_current_panel" class="panel organizations_current_panel hidden" title="<?php echo organizations_index_i18n('ORGANIZATIONS_CURRENT_PANEL_HELP'); ?>" data-hover-help="<?php echo organizations_index_i18n('ORGANIZATIONS_CURRENT_PANEL_HELP'); ?>">
    <div class="organizations_section_header">
      <h2><?php echo organizations_index_i18n('ORGANIZATIONS_CURRENT_ORG_TITLE'); ?></h2>
      <button id="organizations_current_info_link" type="button" class="btn btn_secondary organizations_current_info_link"><?php echo organizations_index_i18n('INFO'); ?></button>
    </div>
    <p id="organizations_current_summary" class="organizations_hub_callout"><?php echo organizations_index_i18n('ORGANIZATIONS_CURRENT_ORG_CALLOUT'); ?></p>
    <div id="organizations_current_meta" class="organizations_current_meta"></div>
    <div class="organizations_current_guidance">
      <p class="help_text"><?php echo organizations_index_i18n('ORGANIZATIONS_CURRENT_ORG_ACCESS_HELP'); ?></p>
      <p class="help_text"><?php echo organizations_index_i18n('ORGANIZATIONS_CURRENT_ORG_REVOKE_HELP'); ?></p>
    </div>
    <div class="organizations_actions_row">
      <button id="organizations_current_revoke_button" type="button" class="btn btn_delete"><?php echo organizations_index_i18n('ORGANIZATIONS_REVOKE_ACCESS_BTN'); ?></button>
    </div>
    <p id="organizations_current_status" class="help_text" role="status" aria-live="polite" aria-atomic="true"></p>
  </section>

  <section id="organizations_free_audit_panel" class="panel organizations_free_audit_panel hidden" title="<?php echo organizations_index_i18n('ORGANIZATIONS_FREE_AUDIT_PANEL_HELP'); ?>" data-hover-help="<?php echo organizations_index_i18n('ORGANIZATIONS_FREE_AUDIT_PANEL_HELP'); ?>">
    <div class="organizations_section_header">
      <h2><?php echo organizations_index_i18n('ORGANIZATIONS_EVENT_LOG_TITLE'); ?></h2>
    </div>
    <p class="help_text"><?php echo organizations_index_i18n('ORGANIZATIONS_EVENT_LOG_HELP'); ?></p>
    <div class="visually_hidden">
      <p id="organizations_free_audit_sr_instructions"><?php echo organizations_index_i18n('ORGANIZATIONS_FREE_AUDIT_SR'); ?></p>
      <p id="organizations_free_audit_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
    </div>
    <div id="organizations-free-audit-grid-host" class="datagrid_container organizations_audit_grid" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_FREE_AUDIT_ARIA'); ?>" aria-describedby="organizations_free_audit_sr_instructions organizations_free_audit_sr_status">
      <div class="datagrid_body"><div class="datagrid_empty"><?php echo organizations_index_i18n('ORGANIZATIONS_FREE_AUDIT_EMPTY'); ?></div></div>
    </div>
  </section>
<?php } ?>

  <!-- Request / Member Hub -->
  <section class="panel organizations_hub_panel" id="organizations-hub" title="<?php echo organizations_index_i18n('ORGANIZATIONS_HUB_PANEL_HELP'); ?>" data-hover-help="<?php echo organizations_index_i18n('ORGANIZATIONS_HUB_PANEL_HELP'); ?>">
<?php if ($isFreeProfile) { ?>
  <p class="organizations_hub_callout"><?php echo organizations_index_i18n('ORGANIZATIONS_HUB_CALLOUT_FREE'); ?></p>
  <section class="panel organizations_browser_panel" title="<?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_PANEL_HELP'); ?>" data-hover-help="<?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_PANEL_HELP'); ?>">
    <div class="organizations_section_header">
      <h2><?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_TITLE'); ?></h2>
    </div>
    <p class="help_text"><?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_HELP'); ?></p>
    <form id="organizations_browser_search_form" class="organizations_inline_form organizations_browser_search_form" method="dialog">
      <input id="organizations_browser_search_input" type="search" maxlength="200" autocomplete="off" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_SEARCH_PLACEHOLDER'); ?>" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_SEARCH_ARIA'); ?>">
      <button id="organizations_browser_search_button" type="submit" class="btn btn_secondary"><?php echo organizations_index_i18n('SEARCH'); ?></button>
    </form>
    <div class="visually_hidden">
      <p id="organizations_browser_grid_sr_instructions"><?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_SR'); ?></p>
      <p id="organizations_browser_grid_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
    </div>
    <div class="organizations_browser_grid_layout">
      <section class="organizations_browser_grid_block">
        <h3><?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_SEARCH_RESULTS'); ?></h3>
        <div id="organizations-browser-grid" class="datagrid_container" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_RESULTS_ARIA'); ?>" aria-describedby="organizations_browser_grid_sr_instructions organizations_browser_grid_sr_status">
          <div class="datagrid_body"><div class="datagrid_empty"><?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_SEARCH_EMPTY'); ?></div></div>
        </div>
      </section>
      <section class="organizations_browser_grid_block">
        <h3><?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_RECENT'); ?></h3>
        <div id="organizations-browser-recent-grid" class="datagrid_container" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_RECENT_ARIA'); ?>" aria-describedby="organizations_browser_grid_sr_instructions organizations_browser_grid_sr_status">
          <div class="datagrid_body"><div class="datagrid_empty"><?php echo organizations_index_i18n('ORGANIZATIONS_BROWSER_RECENT_EMPTY'); ?></div></div>
        </div>
      </section>
    </div>
    <p id="organizations_browser_panel_status" class="help_text" role="status" aria-live="polite" aria-atomic="true"></p>
  </section>
<?php } else { ?>
    <section class="panel organizations_premium_connect_panel" aria-label="Premium organization connection guidance">
      <p class="organizations_hub_callout"><?php echo organizations_index_i18n('ORGANIZATIONS_HUB_CALLOUT_PREMIUM'); ?></p>
    </section>
<?php } ?>

    <section class="panel organizations_request_panel" title="<?php echo organizations_index_i18n('ORGANIZATIONS_REQUEST_PANEL_HELP'); ?>" data-hover-help="<?php echo organizations_index_i18n('ORGANIZATIONS_REQUEST_PANEL_HELP'); ?>">
      <div class="organizations_section_header">
        <div>
          <h2><?php echo organizations_index_i18n('ORGANIZATIONS_REQUEST_ACCESS_TITLE'); ?></h2>
        </div>
      </div>
      <p class="help_text"><?php echo organizations_index_i18n('ORGANIZATIONS_REQUEST_ACCESS_HELP'); ?></p>
      <form id="organizations_request_join_form" class="organizations_inline_form" method="dialog">
        <div class="organizations_request_access_controls">
          <input id="organizations_request_email" type="search" maxlength="200" autocomplete="off" list="organizations_access_lookup_request" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_REQUEST_EMAIL_PLACEHOLDER'); ?>">
          <datalist id="organizations_access_lookup_request"></datalist>
          <div class="organizations_access_level_pillbox" role="group" aria-label="Access Level">
            <button id="organizations_request_access_readonly" type="button" class="pill pill_selected" data-access-level="readonly">Read-Only</button>
            <button id="organizations_request_access_full" type="button" class="pill" data-access-level="full">Full Access</button>
          </div>
        </div>
        <button id="organizations_request_join_button" type="submit" class="btn btn_primary"><?php echo organizations_index_i18n('ORGANIZATIONS_REQUEST_JOIN_BTN'); ?></button>
      </form>
      <p id="organizations_discovery_panel_status" class="help_text" role="status" aria-live="polite" aria-atomic="true"></p>
    </section>
  </section>

  <input type="hidden" id="organizations_csrf_token" value="<?php echo $organizationsCsrfNonce; ?>">

  <!-- Create Organization Dialog -->
  <dialog id="organizations_create_dialog" class="dialog" aria-labelledby="organizations_create_title" aria-describedby="organizations_create_aria">
    <div class="visually_hidden">
      <span id="organizations_create_aria"><?php echo organizations_index_i18n('ORGANIZATIONS_CREATE_ARIA'); ?></span>
    </div>
    <form id="organizations_create_form" method="dialog">
      <section class="modal_header">
        <h2 id="organizations_create_title" class="modal_title"><?php echo organizations_index_i18n('ORGANIZATIONS_CREATE'); ?></h2>
        <button type="button" class="btn_close" data-dialog-close="organizations_create_dialog" aria-label="<?php echo organizations_index_i18n('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content f_column">
        <div class="form_group">
          <label for="organizations_create_name" class="form_label"><?php echo organizations_index_i18n('ORGANIZATIONS_NAME'); ?></label>
          <input
            id="organizations_create_name"
            type="text"
            name="organization_name"
            value=""
            placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_CREATE_NAME_PLACEHOLDER'); ?>"
            maxlength="80"
            required
            autofocus
            aria-required="true"
            aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_NAME'); ?>"
          >
          <small id="organizations_create_name_error" class="form_error hidden"></small>
        </div>
        <div id="organizations_create_status" class="status_message" role="status" aria-live="polite"></div>
      </section>
      <section class="modal_footer">
        <div class="flex f_center f_space_around">
          <button type="submit" id="organizations_create_submit" class="btn btn_primary">
            <?php echo organizations_index_i18n('CREATE'); ?>
          </button>
          <button type="button" id="organizations_create_cancel" class="btn btn_secondary" data-dialog-close="organizations_create_dialog">
            <?php echo organizations_index_i18n('CANCEL'); ?>
          </button>
        </div>
      </section>
    </form>
  </dialog>

  <dialog id="organizations_definitions_dialog" class="dialog organizations_definitions_dialog" aria-labelledby="organizations_definitions_title" aria-describedby="organizations_definitions_aria">
    <form method="dialog">
      <section class="modal_header organizations_definitions_dialog_header">
        <h2 id="organizations_definitions_title" class="modal_title"><?php echo organizations_index_i18n('ORGANIZATIONS_DEFINITIONS_TITLE'); ?></h2>
        <button type="button" id="organizations_definitions_close" class="btn_close" aria-label="<?php echo organizations_index_i18n('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content f_column organizations_definitions_dialog_content">
        <p id="organizations_definitions_aria" class="help_text"><?php echo organizations_index_i18n('ORGANIZATIONS_DEFINITIONS_ARIA'); ?></p>
        <div class="organizations_definitions_grid">
          <article class="organizations_definition_card" aria-labelledby="organizations_definition_type_title">
            <h3 id="organizations_definition_type_title"><?php echo organizations_index_i18n('ORGANIZATIONS_DEFINITION_TYPE'); ?></h3>
            <dl class="organizations_definition_list">
              <dt><?php echo organizations_index_i18n('ORGANIZATIONS_TYPE_PERSONAL'); ?></dt>
              <dd><?php echo organizations_index_i18n('ORGANIZATIONS_DEF_TYPE_PERSONAL_DD'); ?></dd>
              <dt><?php echo organizations_index_i18n('ORGANIZATIONS_TYPE_SHARED'); ?></dt>
              <dd><?php echo organizations_index_i18n('ORGANIZATIONS_DEF_TYPE_SHARED_DD'); ?></dd>
            </dl>
          </article>

          <article class="organizations_definition_card" aria-labelledby="organizations_definition_role_title">
            <h3 id="organizations_definition_role_title"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE'); ?></h3>
            <dl class="organizations_definition_list">
              <dt><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_OWNER'); ?></dt>
              <dd><?php echo organizations_index_i18n('ORGANIZATIONS_DEF_ROLE_OWNER_DD'); ?></dd>
              <dt><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_COORDINATOR'); ?></dt>
              <dd><?php echo organizations_index_i18n('ORGANIZATIONS_DEF_ROLE_COORDINATOR_DD'); ?></dd>
              <dt><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_CONTRIBUTOR'); ?></dt>
              <dd><?php echo organizations_index_i18n('ORGANIZATIONS_DEF_ROLE_CONTRIBUTOR_DD'); ?></dd>
              <dt><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_VIEWER'); ?></dt>
              <dd><?php echo organizations_index_i18n('ORGANIZATIONS_DEF_ROLE_VIEWER_DD'); ?></dd>
              <dt><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_MEMBER'); ?></dt>
              <dd><?php echo organizations_index_i18n('ORGANIZATIONS_DEF_ROLE_MEMBER_DD'); ?></dd>
            </dl>
          </article>

          <article class="organizations_definition_card" aria-labelledby="organizations_definition_status_title">
            <h3 id="organizations_definition_status_title"><?php echo organizations_index_i18n('STATUS'); ?></h3>
            <dl class="organizations_definition_list">
              <dt><?php echo organizations_index_i18n('ORGANIZATIONS_STATUS_ACTIVE'); ?></dt>
              <dd><?php echo organizations_index_i18n('ORGANIZATIONS_DEF_STATUS_ACTIVE_DD'); ?></dd>
              <dt><?php echo organizations_index_i18n('ORGANIZATIONS_PENDING'); ?></dt>
              <dd><?php echo organizations_index_i18n('ORGANIZATIONS_DEF_STATUS_PENDING_DD'); ?></dd>
            </dl>
          </article>
        </div>
      </section>
    </form>
  </dialog>

  <dialog id="organizations_current_details_dialog" class="dialog organizations_current_details_dialog" aria-labelledby="organizations_current_details_title" aria-describedby="organizations_current_details_aria">
    <form method="dialog">
      <section class="modal_header">
        <h2 id="organizations_current_details_title" class="modal_title"><?php echo organizations_index_i18n('ORGANIZATIONS_CURRENT_DETAILS_TITLE'); ?></h2>
        <button type="button" class="btn_close" data-dialog-close="organizations_current_details_dialog" aria-label="<?php echo organizations_index_i18n('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content f_column organizations_current_details_content">
        <p id="organizations_current_details_aria" class="help_text"><?php echo organizations_index_i18n('ORGANIZATIONS_CURRENT_DETAILS_ARIA'); ?></p>
        <div id="organizations_current_details_body" class="organizations_current_details_body"></div>
      </section>
      <section class="modal_footer">
        <button type="button" class="btn btn_secondary" data-dialog-close="organizations_current_details_dialog"><?php echo organizations_index_i18n('CLOSE'); ?></button>
      </section>
    </form>
  </dialog>

  <dialog id="organizations_membership_consent_dialog" class="dialog organizations_membership_consent_dialog" aria-labelledby="organizations_membership_consent_title" aria-describedby="organizations_membership_consent_desc">
    <form id="organizations_membership_consent_form" method="dialog">
      <section class="modal_header">
        <h2 id="organizations_membership_consent_title" class="modal_title"><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERSHIP_CONSENT_TITLE'); ?></h2>
        <button type="button" id="organizations_membership_consent_close" class="btn_close" aria-label="<?php echo organizations_index_i18n('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content f_column organizations_membership_consent_content">
        <p id="organizations_membership_consent_desc" class="help_text"><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERSHIP_CONSENT_DESC'); ?></p>
        <p id="organizations_membership_consent_action" class="organizations_membership_consent_action"></p>
        <label for="organizations_membership_consent_disclaimer" class="form_label"><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERSHIP_CONSENT_DISCLAIMER_LABEL'); ?></label>
        <textarea id="organizations_membership_consent_disclaimer" rows="3" maxlength="600" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERSHIP_CONSENT_DISCLAIMER_PLACEHOLDER'); ?>"></textarea>
        <label for="organizations_membership_consent_ack" class="organizations_membership_consent_ack_label">
          <input id="organizations_membership_consent_ack" type="checkbox" value="1">
          <span><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERSHIP_CONSENT_ACK_TEXT'); ?></span>
        </label>
        <p id="organizations_membership_consent_error" class="form_error hidden" role="status" aria-live="polite"></p>
      </section>
      <section class="modal_footer">
        <div class="flex f_center f_space_around">
          <button type="submit" id="organizations_membership_consent_confirm" class="btn btn_primary"><?php echo organizations_index_i18n('CONTINUE'); ?></button>
          <button type="button" id="organizations_membership_consent_cancel" class="btn btn_secondary"><?php echo organizations_index_i18n('CANCEL'); ?></button>
        </div>
      </section>
    </form>
  </dialog>

  <!-- Organizations Editor Dialog -->
  <dialog id="organizations_editor_dialog" class="dialog organizations_dialog" aria-labelledby="organizations_editor_title" aria-describedby="organizations_editor_aria">
    <div class="visually_hidden">
      <span id="organizations_editor_aria"><?php echo organizations_index_i18n('ORGANIZATIONS_EDITOR_ARIA'); ?></span>
    </div>
    <form id="organizations_editor_form" method="dialog">
      <input type="hidden" id="organizations_editor_org_id" value="">

      <section class="modal_header organizations_dialog_header">
        <h2 id="organizations_editor_title" class="modal_title organizations_dialog_title"><?php echo organizations_index_i18n('ORGANIZATIONS'); ?></h2>
          <div class="tablist_container" role="tablist" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_TAB_ARIA'); ?>">
          <button id="organizations_tab_details" type="button" class="tab_button tab_active" role="tab" aria-selected="true" aria-controls="organizations_tab_details_panel"><?php echo organizations_index_i18n('ORGANIZATIONS_TAB_DETAILS'); ?></button>
          <button id="organizations_tab_members" type="button" class="tab_button" role="tab" aria-selected="false" aria-controls="organizations_tab_members_panel"><?php echo organizations_index_i18n('MEMBERS'); ?></button>
        </div>
        <div class="organizations_dialog_header_spacer" aria-hidden="true"></div>
      </section>

      <section class="modal_content organizations_dialog_content" id="organizations_tab_details_panel" role="tabpanel" aria-labelledby="organizations_tab_details">
        <div class="organizations_editor_grid">
          <section class="organizations_editor_card organizations_editor_card_full organizations_editor_panel organizations_owner_summary_card" title="The member with full organizational control and decision-making authority." data-hover-help="The member with full organizational control and decision-making authority.">
            <div class="organizations_section_header">
              <h3><?php echo organizations_index_i18n('ORGANIZATIONS_CURRENT_OWNER_TITLE'); ?></h3>
            </div>
              <p class="help_text">The member with full organizational control and decision-making authority.</p>
            <div id="organizations_owner_summary" class="organizations_owner_summary_grid" role="status" aria-live="polite" aria-atomic="true"></div>
          </section>

          <section id="organizations_audit_control_test_panel" class="organizations_editor_card organizations_editor_card_full organizations_editor_panel" hidden title="Generate one controlled audit failure for Redis, The Watcher, and GCS alert-evidence validation." data-hover-help="Generate one controlled audit failure for Redis, The Watcher, and GCS alert-evidence validation.">
            <div class="organizations_section_header">
              <h3>Audit Control Test</h3>
            </div>
            <p class="help_text">Use this once to generate a controlled organization error event. It records the test in Redis, appends it to The Watcher, and uploads a chained GCS alert artifact. Restricted to owners and authorized managers.</p>
            <div class="organizations_field_grid">
              <label for="organizations_audit_control_test_summary">Test summary</label>
              <input id="organizations_audit_control_test_summary" type="text" maxlength="240" value="Manual organization audit control test" placeholder="Describe why this controlled error was generated">
            </div>
            <div class="organizations_actions_row">
              <button id="organizations_audit_control_test_button" type="button" class="btn btn_delete">Generate Test Error</button>
            </div>
            <p id="organizations_audit_control_test_status" class="help_text" role="status" aria-live="polite" aria-atomic="true"></p>
          </section>

          <section class="organizations_editor_card organizations_editor_card_full organizations_editor_panel" title="Core organization profile including legal details, role defaults, contact info, and address." data-hover-help="Core organization profile including legal details, role defaults, contact info, and address.">
            <h3><?php echo organizations_index_i18n('ORGANIZATIONS_EDITOR_DETAILS_H3'); ?></h3>
            <p class="help_text"><?php echo organizations_index_i18n('ORGANIZATIONS_EDITOR_DETAILS_NOTICE'); ?></p>
            <div class="organizations_details_panel">
              <div class="organizations_details_columns">
                <section class="organizations_details_column">
                <div class="organizations_field_grid">
                <label for="organizations_editor_name"><?php echo organizations_index_i18n('ORGANIZATIONS_NAME'); ?></label>
                <input id="organizations_editor_name" type="text" maxlength="80" required>

                <label for="organizations_editor_legal_name"><?php echo organizations_index_i18n('ORGANIZATIONS_LEGAL_NAME'); ?></label>
                <input id="organizations_editor_legal_name" type="text" maxlength="140" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_LEGAL_NAME_PLACEHOLDER'); ?>">

                <label for="organizations_editor_type"><?php echo organizations_index_i18n('ORGANIZATIONS_TYPE'); ?></label>
                <select id="organizations_editor_type">
                  <option value="personal"><?php echo organizations_index_i18n('ORGANIZATIONS_TYPE_PERSONAL'); ?></option>
                  <option value="shared"><?php echo organizations_index_i18n('ORGANIZATIONS_TYPE_SHARED'); ?></option>
                </select>

                <label for="organizations_editor_role"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE'); ?></label>
                <select id="organizations_editor_role">
                  <option value="owner"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_OWNER'); ?></option>
                  <option value="coordinator"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_COORDINATOR'); ?></option>
                  <option value="contributor"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_CONTRIBUTOR'); ?></option>
                  <option value="viewer"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_VIEWER'); ?></option>
                  <option value="member"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_MEMBER'); ?></option>
                </select>

                <label for="organizations_editor_status"><?php echo organizations_index_i18n('STATUS'); ?></label>
                <select id="organizations_editor_status">
                  <option value="active"><?php echo organizations_index_i18n('ORGANIZATIONS_STATUS_ACTIVE'); ?></option>
                  <option value="pending"><?php echo organizations_index_i18n('ORGANIZATIONS_PENDING'); ?></option>
                </select>

                <p class="help_text organizations_destructive_hint" role="note">Type, role, and status changes can be destructive. You will be asked to confirm sensitive transitions before they are saved.</p>

                <label for="organizations_editor_industry"><?php echo organizations_index_i18n('ORGANIZATIONS_INDUSTRY'); ?></label>
                <input id="organizations_editor_industry" type="text" maxlength="80" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_INDUSTRY_PLACEHOLDER'); ?>">

                <label for="organizations_editor_registration_number"><?php echo organizations_index_i18n('ORGANIZATIONS_REG_NUMBER'); ?></label>
                <input id="organizations_editor_registration_number" type="text" maxlength="64" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_REG_NUMBER_PLACEHOLDER'); ?>">

                <label for="organizations_editor_tax_id"><?php echo organizations_index_i18n('ORGANIZATIONS_TAX_ID'); ?></label>
                <input id="organizations_editor_tax_id" type="text" maxlength="64" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_TAX_ID_PLACEHOLDER'); ?>">

                <label for="organizations_editor_employee_count"><?php echo organizations_index_i18n('ORGANIZATIONS_EMPLOYEE_COUNT'); ?></label>
                <input id="organizations_editor_employee_count" type="text" maxlength="16" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_EMPLOYEE_COUNT_PLACEHOLDER'); ?>">

                <label for="organizations_editor_founded_year"><?php echo organizations_index_i18n('ORGANIZATIONS_FOUNDED_YEAR'); ?></label>
                <input id="organizations_editor_founded_year" type="text" maxlength="8" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_FOUNDED_YEAR_PLACEHOLDER'); ?>">

                <label for="organizations_editor_default_wage"><?php echo organizations_index_i18n('ORGANIZATIONS_DEFAULT_WAGE'); ?></label>
                <input id="organizations_editor_default_wage" type="text" maxlength="32" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_DEFAULT_WAGE_PLACEHOLDER'); ?>">

                <label for="organizations_editor_timezone_search"><?php echo organizations_index_i18n('ORGANIZATIONS_TIMEZONE'); ?></label>
                <div class="timezone_finder" id="organizations_editor_timezone_finder" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="organizations_editor_timezone_listbox">
                  <input class="timezone_finder_search" id="organizations_editor_timezone_search" type="text" autocomplete="off" spellcheck="false" placeholder="<?php echo organizations_index_i18n('PROFILE_TIMEZONE_SEARCH_PLACEHOLDER'); ?>" aria-autocomplete="list" aria-controls="organizations_editor_timezone_listbox" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_TIMEZONE'); ?>">
                  <input id="organizations_editor_timezone" type="hidden">
                  <ul id="organizations_editor_timezone_listbox" class="timezone_finder_list" role="listbox" hidden></ul>
                </div>

                <label for="organizations_editor_currency_search"><?php echo organizations_index_i18n('ORGANIZATIONS_CURRENCY'); ?></label>
                <div class="currency_finder" id="organizations_editor_currency_finder" role="combobox" aria-expanded="false" aria-haspopup="listbox" aria-owns="organizations_editor_currency_listbox">
                  <input class="currency_finder_search" id="organizations_editor_currency_search" type="text" autocomplete="off" spellcheck="false" placeholder="<?php echo organizations_index_i18n('PROFILE_CURRENCY_SEARCH_PLACEHOLDER'); ?>" aria-autocomplete="list" aria-controls="organizations_editor_currency_listbox" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_CURRENCY'); ?>">
                  <input id="organizations_editor_currency" type="hidden">
                  <ul id="organizations_editor_currency_listbox" class="currency_finder_list" role="listbox" hidden></ul>
                </div>
                </div>
                </section>

                <section class="organizations_details_column">
                <div class="organizations_field_grid">
                <label for="organizations_editor_contact_email"><?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_EMAIL'); ?></label>
                <input id="organizations_editor_contact_email" type="email" maxlength="160" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_EMAIL_PLACEHOLDER'); ?>">

                <label for="organizations_editor_contact_phone"><?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_PHONE'); ?></label>
                <input id="organizations_editor_contact_phone" type="tel" maxlength="14" inputmode="numeric" pattern="\([0-9]{3}\) [0-9]{3}-[0-9]{4}" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_PHONE_PLACEHOLDER'); ?>">

                <label for="organizations_editor_website"><?php echo organizations_index_i18n('ORGANIZATIONS_WEBSITE'); ?></label>
                <input id="organizations_editor_website" type="url" maxlength="180" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_WEBSITE_PLACEHOLDER'); ?>">

                <label for="organizations_editor_address_line1"><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_LINE_1'); ?></label>
                <input id="organizations_editor_address_line1" type="text" maxlength="120" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_LINE_1_PLACEHOLDER'); ?>">

                <label for="organizations_editor_address_line2"><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_LINE_2'); ?></label>
                <input id="organizations_editor_address_line2" type="text" maxlength="120" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_LINE_2_PLACEHOLDER'); ?>">

                <label for="organizations_editor_address_city"><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_CITY'); ?></label>
                <input id="organizations_editor_address_city" type="text" maxlength="80" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_CITY_PLACEHOLDER'); ?>">

                <label for="organizations_editor_address_region"><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_REGION'); ?></label>
                <input id="organizations_editor_address_region" type="text" maxlength="80" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_REGION_PLACEHOLDER'); ?>">

                <label for="organizations_editor_address_postal"><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_POSTAL'); ?></label>
                <input id="organizations_editor_address_postal" type="text" maxlength="20" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_POSTAL_PLACEHOLDER'); ?>">

                <label for="organizations_editor_address_country"><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_COUNTRY'); ?></label>
                <input id="organizations_editor_address_country" type="text" maxlength="64" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_COUNTRY_PLACEHOLDER'); ?>">

                <label for="organizations_editor_support_hours"><?php echo organizations_index_i18n('ORGANIZATIONS_SUPPORT_HOURS'); ?></label>
                <input id="organizations_editor_support_hours" type="text" maxlength="120" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_SUPPORT_HOURS_PLACEHOLDER'); ?>">

                <label for="organizations_editor_org_notes"><?php echo organizations_index_i18n('ORGANIZATIONS_ORG_NOTES'); ?></label>
                <textarea id="organizations_editor_org_notes" rows="4" maxlength="1200" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_ORG_NOTES_PLACEHOLDER'); ?>"></textarea>

                <label for="organizations_editor_enforce_contact_domain"><?php echo organizations_index_i18n('ORGANIZATIONS_ENFORCE_CONTACT_DOMAIN'); ?></label>
                <div class="organizations_domain_policy_toggle">
                  <input id="organizations_editor_enforce_contact_domain" type="checkbox" value="1">
                  <span><?php echo organizations_index_i18n('ORGANIZATIONS_ENFORCE_CONTACT_DOMAIN_TEXT'); ?></span>
                </div>

                <label for="organizations_editor_allowed_contact_domains"><?php echo organizations_index_i18n('ORGANIZATIONS_ALLOWED_CONTACT_DOMAINS'); ?></label>
                <input id="organizations_editor_allowed_contact_domains" type="text" maxlength="300" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_ALLOWED_CONTACT_DOMAINS_PLACEHOLDER'); ?>">
                </div>

                </section>
              </div>

              <p id="organizations_editor_domain_policy_status" class="help_text organizations_domain_policy_status" role="status" aria-live="polite" aria-atomic="true"></p>
            </div>

          </section>

          <section class="organizations_editor_card organizations_editor_card_full organizations_editor_panel organizations_panel_contact_directory" title="Key contacts for organization operations, HR, payroll, and support." data-hover-help="Key contacts for organization operations, HR, payroll, and support.">
            <div class="organizations_section_header organizations_contact_directory_header">
              <h3><?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_DIRECTORY_H3'); ?></h3>
              <button id="organizations_contact_card_add" type="button" class="btn btn_secondary"><?php echo organizations_index_i18n('ORGANIZATIONS_ADD_CONTACT_CARD_BTN'); ?></button>
            </div>
              <p class="help_text">Key contacts for organization operations, HR, and support.</p>
            <div class="organizations_contact_directory_grid">
              <div class="organizations_contact_card">
                <img id="organizations_editor_contact_ceo_avatar_preview" class="organizations_contact_card_avatar" src="" alt="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_ALT'); ?>" loading="lazy">
                <input id="organizations_editor_contact_ceo_image_url" class="organizations_contact_image_input" data-preview-id="organizations_editor_contact_ceo_avatar_preview" type="hidden" maxlength="20000" value="">
                <input id="organizations_editor_contact_ceo_name" class="organizations_contact_body_input" name="name" autocomplete="name" type="text" maxlength="100" placeholder="<?php echo organizations_index_i18n('NAME'); ?>">
                <input id="organizations_editor_contact_ceo_email" class="organizations_contact_body_input" name="email" autocomplete="email" type="email" maxlength="160" placeholder="<?php echo organizations_index_i18n('EMAIL'); ?>">
                <input id="organizations_editor_contact_ceo_phone" class="organizations_contact_body_input" name="phone" autocomplete="tel" type="tel" maxlength="32" placeholder="<?php echo organizations_index_i18n('PHONE'); ?>">
                <input id="organizations_editor_contact_ceo_role" class="organizations_contact_role_input" type="text" maxlength="80" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ROLE_PH'); ?>">
                <div class="organizations_contact_card_menu">
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ACTIONS_ARIA'); ?>">...</button>
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_delete" data-card-type="fixed" data-confirming="false" hidden><?php echo organizations_index_i18n('DELETE'); ?></button>
                </div>
              </div>
              <div class="organizations_contact_card">
                <img id="organizations_editor_contact_coo_avatar_preview" class="organizations_contact_card_avatar" src="" alt="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_ALT'); ?>" loading="lazy">
                <input id="organizations_editor_contact_coo_image_url" class="organizations_contact_image_input" data-preview-id="organizations_editor_contact_coo_avatar_preview" type="hidden" maxlength="20000" value="">
                <input id="organizations_editor_contact_coo_name" class="organizations_contact_body_input" name="name" autocomplete="name" type="text" maxlength="100" placeholder="<?php echo organizations_index_i18n('NAME'); ?>">
                <input id="organizations_editor_contact_coo_email" class="organizations_contact_body_input" name="email" autocomplete="email" type="email" maxlength="160" placeholder="<?php echo organizations_index_i18n('EMAIL'); ?>">
                <input id="organizations_editor_contact_coo_phone" class="organizations_contact_body_input" name="phone" autocomplete="tel" type="tel" maxlength="32" placeholder="<?php echo organizations_index_i18n('PHONE'); ?>">
                <input id="organizations_editor_contact_coo_role" class="organizations_contact_role_input" type="text" maxlength="80" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ROLE_PH'); ?>">
                <div class="organizations_contact_card_menu">
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ACTIONS_ARIA'); ?>">...</button>
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_delete" data-card-type="fixed" data-confirming="false" hidden><?php echo organizations_index_i18n('DELETE'); ?></button>
                </div>
              </div>
              <div class="organizations_contact_card">
                <img id="organizations_editor_contact_cto_avatar_preview" class="organizations_contact_card_avatar" src="" alt="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_ALT'); ?>" loading="lazy">
                <input id="organizations_editor_contact_cto_image_url" class="organizations_contact_image_input" data-preview-id="organizations_editor_contact_cto_avatar_preview" type="hidden" maxlength="20000" value="">
                <input id="organizations_editor_contact_cto_name" class="organizations_contact_body_input" name="name" autocomplete="name" type="text" maxlength="100" placeholder="<?php echo organizations_index_i18n('NAME'); ?>">
                <input id="organizations_editor_contact_cto_email" class="organizations_contact_body_input" name="email" autocomplete="email" type="email" maxlength="160" placeholder="<?php echo organizations_index_i18n('EMAIL'); ?>">
                <input id="organizations_editor_contact_cto_phone" class="organizations_contact_body_input" name="phone" autocomplete="tel" type="tel" maxlength="32" placeholder="<?php echo organizations_index_i18n('PHONE'); ?>">
                <input id="organizations_editor_contact_cto_role" class="organizations_contact_role_input" type="text" maxlength="80" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ROLE_PH'); ?>">
                <div class="organizations_contact_card_menu">
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ACTIONS_ARIA'); ?>">...</button>
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_delete" data-card-type="fixed" data-confirming="false" hidden><?php echo organizations_index_i18n('DELETE'); ?></button>
                </div>
              </div>
              <div class="organizations_contact_card">
                <img id="organizations_editor_contact_payroll_avatar_preview" class="organizations_contact_card_avatar" src="" alt="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_ALT'); ?>" loading="lazy">
                <input id="organizations_editor_contact_payroll_image_url" class="organizations_contact_image_input" data-preview-id="organizations_editor_contact_payroll_avatar_preview" type="hidden" maxlength="20000" value="">
                <input id="organizations_editor_contact_payroll_name" class="organizations_contact_body_input" name="name" autocomplete="name" type="text" maxlength="100" placeholder="<?php echo organizations_index_i18n('NAME'); ?>">
                <input id="organizations_editor_contact_payroll_email" class="organizations_contact_body_input" name="email" autocomplete="email" type="email" maxlength="160" placeholder="<?php echo organizations_index_i18n('EMAIL'); ?>">
                <input id="organizations_editor_contact_payroll_phone" class="organizations_contact_body_input" name="phone" autocomplete="tel" type="tel" maxlength="32" placeholder="<?php echo organizations_index_i18n('PHONE'); ?>">
                <input id="organizations_editor_contact_payroll_role" class="organizations_contact_role_input" type="text" maxlength="80" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ROLE_PH'); ?>">
                <div class="organizations_contact_card_menu">
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ACTIONS_ARIA'); ?>">...</button>
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_delete" data-card-type="fixed" data-confirming="false" hidden><?php echo organizations_index_i18n('DELETE'); ?></button>
                </div>
              </div>
              <div class="organizations_contact_card">
                <img id="organizations_editor_contact_hr_avatar_preview" class="organizations_contact_card_avatar" src="" alt="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_ALT'); ?>" loading="lazy">
                <input id="organizations_editor_contact_hr_image_url" class="organizations_contact_image_input" data-preview-id="organizations_editor_contact_hr_avatar_preview" type="hidden" maxlength="20000" value="">
                <input id="organizations_editor_contact_hr_name" class="organizations_contact_body_input" name="name" autocomplete="name" type="text" maxlength="100" placeholder="<?php echo organizations_index_i18n('NAME'); ?>">
                <input id="organizations_editor_contact_hr_email" class="organizations_contact_body_input" name="email" autocomplete="email" type="email" maxlength="160" placeholder="<?php echo organizations_index_i18n('EMAIL'); ?>">
                <input id="organizations_editor_contact_hr_phone" class="organizations_contact_body_input" name="phone" autocomplete="tel" type="tel" maxlength="32" placeholder="<?php echo organizations_index_i18n('PHONE'); ?>">
                <input id="organizations_editor_contact_hr_role" class="organizations_contact_role_input" type="text" maxlength="80" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ROLE_PH'); ?>">
                <div class="organizations_contact_card_menu">
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ACTIONS_ARIA'); ?>">...</button>
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_delete" data-card-type="fixed" data-confirming="false" hidden><?php echo organizations_index_i18n('DELETE'); ?></button>
                </div>
              </div>
              <div class="organizations_contact_card">
                <img id="organizations_editor_contact_operations_avatar_preview" class="organizations_contact_card_avatar" src="" alt="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_ALT'); ?>" loading="lazy">
                <input id="organizations_editor_contact_operations_image_url" class="organizations_contact_image_input" data-preview-id="organizations_editor_contact_operations_avatar_preview" type="hidden" maxlength="20000" value="">
                <input id="organizations_editor_contact_operations_name" class="organizations_contact_body_input" name="name" autocomplete="name" type="text" maxlength="100" placeholder="<?php echo organizations_index_i18n('NAME'); ?>">
                <input id="organizations_editor_contact_operations_email" class="organizations_contact_body_input" name="email" autocomplete="email" type="email" maxlength="160" placeholder="<?php echo organizations_index_i18n('EMAIL'); ?>">
                <input id="organizations_editor_contact_operations_phone" class="organizations_contact_body_input" name="phone" autocomplete="tel" type="tel" maxlength="32" placeholder="<?php echo organizations_index_i18n('PHONE'); ?>">
                <input id="organizations_editor_contact_operations_role" class="organizations_contact_role_input" type="text" maxlength="80" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ROLE_PH'); ?>">
                <div class="organizations_contact_card_menu">
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ACTIONS_ARIA'); ?>">...</button>
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_delete" data-card-type="fixed" data-confirming="false" hidden><?php echo organizations_index_i18n('DELETE'); ?></button>
                </div>
              </div>
              <div class="organizations_contact_card">
                <img id="organizations_editor_contact_manager_avatar_preview" class="organizations_contact_card_avatar" src="" alt="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_ALT'); ?>" loading="lazy">
                <input id="organizations_editor_contact_manager_image_url" class="organizations_contact_image_input" data-preview-id="organizations_editor_contact_manager_avatar_preview" type="hidden" maxlength="20000" value="">
                <input id="organizations_editor_contact_manager_name" class="organizations_contact_body_input" name="name" autocomplete="name" type="text" maxlength="100" placeholder="<?php echo organizations_index_i18n('NAME'); ?>">
                <input id="organizations_editor_contact_manager_email" class="organizations_contact_body_input" name="email" autocomplete="email" type="email" maxlength="160" placeholder="<?php echo organizations_index_i18n('EMAIL'); ?>">
                <input id="organizations_editor_contact_manager_phone" class="organizations_contact_body_input" name="phone" autocomplete="tel" type="tel" maxlength="32" placeholder="<?php echo organizations_index_i18n('PHONE'); ?>">
                <input id="organizations_editor_contact_manager_role" class="organizations_contact_role_input" type="text" maxlength="80" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ROLE_PH'); ?>">
                <div class="organizations_contact_card_menu">
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ACTIONS_ARIA'); ?>">...</button>
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_delete" data-card-type="fixed" data-confirming="false" hidden><?php echo organizations_index_i18n('DELETE'); ?></button>
                </div>
              </div>
              <div class="organizations_contact_card">
                <img id="organizations_editor_contact_support_avatar_preview" class="organizations_contact_card_avatar" src="" alt="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_ALT'); ?>" loading="lazy">
                <input id="organizations_editor_contact_support_image_url" class="organizations_contact_image_input" data-preview-id="organizations_editor_contact_support_avatar_preview" type="hidden" maxlength="20000" value="">
                <input id="organizations_editor_contact_support_name" class="organizations_contact_body_input" name="name" autocomplete="name" type="text" maxlength="100" placeholder="<?php echo organizations_index_i18n('NAME'); ?>">
                <input id="organizations_editor_contact_support_email" class="organizations_contact_body_input" name="email" autocomplete="email" type="email" maxlength="160" placeholder="<?php echo organizations_index_i18n('EMAIL'); ?>">
                <input id="organizations_editor_contact_support_phone" class="organizations_contact_body_input" name="phone" autocomplete="tel" type="tel" maxlength="32" placeholder="<?php echo organizations_index_i18n('PHONE'); ?>">
                <input id="organizations_editor_contact_support_role" class="organizations_contact_role_input" type="text" maxlength="80" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ROLE_PH'); ?>">
                <div class="organizations_contact_card_menu">
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_ACTIONS_ARIA'); ?>">...</button>
                  <button type="button" class="btn btn_secondary organizations_contact_card_menu_delete" data-card-type="fixed" data-confirming="false" hidden><?php echo organizations_index_i18n('DELETE'); ?></button>
                </div>
              </div>
            </div>
            <div id="organizations_contact_directory_custom_cards" class="organizations_contact_directory_grid organizations_contact_directory_custom_grid"></div>
            <input id="organizations_editor_contact_custom_json" type="hidden" value="">

            <div id="organizations_contact_image_popover" class="organizations_contact_image_popover hidden" role="dialog" aria-modal="false" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_POPOVER_ARIA'); ?>">
              <p class="organizations_contact_image_popover_title"><?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_POPOVER_TITLE'); ?></p>
              <div id="organizations_contact_image_dropzone" class="organizations_contact_image_dropzone" tabindex="0" role="button" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_DROP_ARIA'); ?>">
                <?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_DROP'); ?>
              </div>
              <input id="organizations_contact_image_file" type="file" accept="image/*" class="visually_hidden">
              <div class="organizations_contact_image_popover_actions">
                <button id="organizations_contact_image_clear" type="button" class="btn btn_secondary"><?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_IMAGE_REMOVE'); ?></button>
                <button id="organizations_contact_image_cancel" type="button" class="btn btn_secondary"><?php echo organizations_index_i18n('CLOSE'); ?></button>
              </div>
            </div>
          </section>

          <section class="organizations_editor_card organizations_editor_card_full organizations_editor_panel organizations_panel_pay_period" title="Configure pay frequency, period length, grace window, and preview schedule." data-hover-help="Configure pay frequency, period length, grace window, and preview schedule.">
            <div class="organizations_section_header">
              <h3><?php echo organizations_index_i18n('ORGANIZATIONS_PP_TITLE'); ?></h3>
            </div>
              <p class="help_text">Configure pay frequency, grace period, and pay dates.</p>
            <div class="organizations_pp_control_strip organizations_editor_pp_controls">
              <div class="organizations_pp_control">
                <label for="organizations_editor_pay_frequency"><?php echo organizations_index_i18n('PROFILE_PAY_FREQUENCY_LABEL'); ?></label>
                <select id="organizations_editor_pay_frequency">
                  <option value="weekly"><?php echo organizations_index_i18n('PROFILE_PAY_FREQUENCY_WEEKLY'); ?></option>
                  <option value="biweekly"><?php echo organizations_index_i18n('PROFILE_PAY_FREQUENCY_BIWEEKLY'); ?></option>
                  <option value="semimonthly"><?php echo organizations_index_i18n('PROFILE_PAY_FREQUENCY_SEMIMONTHLY'); ?></option>
                  <option value="monthly"><?php echo organizations_index_i18n('MONTHLY'); ?></option>
                </select>
              </div>

              <div class="organizations_pp_control">
                <label for="organizations_editor_pay_period_length"><?php echo organizations_index_i18n('LENGTH'); ?></label>
                <input id="organizations_editor_pay_period_length" type="number" min="7" max="31" readonly>
              </div>

              <div class="organizations_pp_control">
                <span class="organizations_pp_control_label"><?php echo organizations_index_i18n('PROFILE_PAY_GRACE_LABEL'); ?></span>
                <div id="organizations_editor_editing_grace_days" class="radio_group organizations_grace_radio_group" role="radiogroup" aria-label="<?php echo organizations_index_i18n('PROFILE_PAY_GRACE_LABEL'); ?>">
                  <input type="radio" class="radio" id="organizations_editor_grace_0" name="organizations_editor_editing_grace_days" value="0" checked>
                  <label for="organizations_editor_grace_0"><?php echo organizations_index_i18n('NONE'); ?></label>
                  <input type="radio" class="radio" id="organizations_editor_grace_1" name="organizations_editor_editing_grace_days" value="1">
                  <label for="organizations_editor_grace_1"><?php echo organizations_index_i18n('PROFILE_PAY_GRACE_1_DAY'); ?></label>
                  <input type="radio" class="radio" id="organizations_editor_grace_2" name="organizations_editor_editing_grace_days" value="2">
                  <label for="organizations_editor_grace_2"><?php echo organizations_index_i18n('PROFILE_PAY_GRACE_2_DAYS'); ?></label>
                  <input type="radio" class="radio" id="organizations_editor_grace_3" name="organizations_editor_editing_grace_days" value="3">
                  <label for="organizations_editor_grace_3"><?php echo organizations_index_i18n('PROFILE_PAY_GRACE_3_DAYS'); ?></label>
                </div>
              </div>
            </div>
            <input id="organizations_editor_pay_anchor" type="hidden" value="<?php echo organizations_index_i18n('ORGANIZATIONS_DEFAULT_PAY_ANCHOR'); ?>">
            <input id="organizations_editor_pay_period_start" type="hidden" value="">
            <div class="visually_hidden">
              <p id="organizations_editor_payperiod_sr_instructions"><?php echo organizations_index_i18n('ORGANIZATIONS_PP_SR'); ?></p>
              <p id="organizations_editor_payperiod_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
            </div>
            <div id="organizations_editor_preview" class="organizations_preview_box pay_period_preview_compact" aria-live="polite" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_PP_PREVIEW_ARIA'); ?>" aria-describedby="organizations_editor_payperiod_sr_instructions organizations_editor_payperiod_sr_status"></div>
          </section>

          <section class="organizations_editor_card organizations_editor_card_full organizations_editor_panel organizations_panel_sites_discovery" title="Run discovery to surface related accounts and organization links." data-hover-help="Run discovery to surface related accounts and organization links.">
            <div class="organizations_section_header">
              <h3><?php echo organizations_index_i18n('ORGANIZATIONS_DISCOVERY'); ?></h3>
            </div>
            <p class="help_text"><?php echo organizations_index_i18n('ORGANIZATIONS_DISCOVERY_HELP'); ?></p>
            <div class="visually_hidden">
              <p id="organizations_discovery_sr_instructions"><?php echo organizations_index_i18n('ORGANIZATIONS_DISCOVERY_SR'); ?></p>
              <p id="organizations_discovery_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
            </div>
            <div id="organizations_discovery_results" class="organizations_stack organizations_empty" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_DISCOVERY_RESULTS_ARIA'); ?>" aria-describedby="organizations_discovery_sr_instructions organizations_discovery_sr_status"><?php echo organizations_index_i18n('ORGANIZATIONS_NO_DISCOVERY'); ?></div>
          </section>

          <section class="organizations_editor_card organizations_editor_card_full organizations_editor_panel organizations_danger_zone_panel" title="High-risk ownership transfer controls. Use only when you intend to hand off owner authority." data-hover-help="High-risk ownership transfer controls. Use only when you intend to hand off owner authority.">
            <div class="organizations_section_header">
              <h3>DANGER ZONE</h3>
            </div>
            <p id="organizations_transfer_notice" class="help_text"></p>
            <div class="organizations_inline_form">
              <input
                id="organizations_transfer_target"
                type="search"
                list="organizations_transfer_target_list"
                autocomplete="off"
                maxlength="180"
                placeholder="Select member..."
                aria-label="Search current member name for ownership transfer"
              >
              <datalist id="organizations_transfer_target_list"></datalist>
              <input id="organizations_transfer_target_uuid" type="hidden" value="">
              <button id="organizations_transfer_button" type="button" class="btn btn_delete"><?php echo organizations_index_i18n('ORGANIZATIONS_TRANSFER_OWNERSHIP'); ?></button>
            </div>
            <div id="organizations_transfer_selected_member" class="organizations_transfer_selected_member organizations_empty" role="status" aria-live="polite" aria-atomic="true"></div>
            <div id="organizations_transfer_confirmation_container" class="organizations_transfer_confirmation_container organizations_empty">
              <label for="organizations_transfer_confirmation" class="organizations_transfer_confirmation_label">
                Type to confirm
              </label>
              <input
                id="organizations_transfer_confirmation"
                type="text"
                maxlength="22"
                autocomplete="off"
                placeholder="Type 'TRANSFER ORGANIZATION'"
                aria-label="Type TRANSFER ORGANIZATION to confirm ownership transfer"
              >
              <p class="help_text" id="organizations_transfer_confirmation_status"></p>
            </div>
            <p class="help_text organizations_danger_zone_disclaimer">Transfer ownership only to an active current member you trust. This immediately moves owner control and may be blocked by strict domain policy.</p>
          </section>

          <section class="organizations_editor_card organizations_editor_card_full organizations_editor_panel organizations_panel_audit_timeline" title="Complete history of organization-level changes and access events." data-hover-help="Complete history of organization-level changes and access events.">
            <div class="organizations_section_header">
              <h3><?php echo organizations_index_i18n('ORGANIZATIONS_AUDIT_TIMELINE'); ?></h3>
              <button id="organizations_audit_reload" type="button" class="btn btn_secondary"><?php echo organizations_index_i18n('REFRESH'); ?></button>
            </div>
              <p class="help_text">Complete record of all changes made to organization settings.</p>
            <div class="visually_hidden">
              <p id="organizations_audit_sr_instructions"><?php echo organizations_index_i18n('ORGANIZATIONS_AUDIT_SR'); ?></p>
              <p id="organizations_audit_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
            </div>
            <div id="organizations-audit-grid-host" class="datagrid_container organizations_audit_grid" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_AUDIT_ARIA'); ?>" aria-describedby="organizations_audit_sr_instructions organizations_audit_sr_status">
              <div class="datagrid_body"><div class="datagrid_empty"><?php echo organizations_index_i18n('ORGANIZATIONS_NO_AUDIT'); ?></div></div>
            </div>
          </section>

          <section class="organizations_editor_card organizations_editor_card_full organizations_editor_panel organizations_panel_org_details" title="Public organization details visible to all members with access." data-hover-help="Public organization details visible to all members with access.">
            <h3>Org Details</h3>
            <p class="help_text">Public organization information visible to members.</p>
            <div class="organizations_details_panel">
              <div class="organizations_details_columns">
                <section class="organizations_details_column">
                <div class="organizations_field_grid">
                <label><?php echo organizations_index_i18n('ORGANIZATIONS_NAME'); ?></label>
                <div class="organizations_readonly_field" id="organizations_detail_name"></div>

                <label><?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_EMAIL'); ?></label>
                <div class="organizations_readonly_field" id="organizations_detail_contact_email"></div>

                <label><?php echo organizations_index_i18n('ORGANIZATIONS_CONTACT_PHONE'); ?></label>
                <div class="organizations_readonly_field" id="organizations_detail_contact_phone"></div>

                <label><?php echo organizations_index_i18n('ORGANIZATIONS_WEBSITE'); ?></label>
                <div class="organizations_readonly_field" id="organizations_detail_website"></div>

                <label><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_LINE_1'); ?></label>
                <div class="organizations_readonly_field" id="organizations_detail_address_line1"></div>

                <label><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_LINE_2'); ?></label>
                <div class="organizations_readonly_field" id="organizations_detail_address_line2"></div>
                </div>
                </section>

                <section class="organizations_details_column">
                <div class="organizations_field_grid">
                <label><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_CITY'); ?></label>
                <div class="organizations_readonly_field" id="organizations_detail_address_city"></div>

                <label><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_REGION'); ?></label>
                <div class="organizations_readonly_field" id="organizations_detail_address_region"></div>

                <label><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_POSTAL'); ?></label>
                <div class="organizations_readonly_field" id="organizations_detail_address_postal"></div>

                <label><?php echo organizations_index_i18n('ORGANIZATIONS_ADDRESS_COUNTRY'); ?></label>
                <div class="organizations_readonly_field" id="organizations_detail_address_country"></div>
                </div>
                </section>
              </div>
            </div>
          </section>
        </div>
      </section>

      <section class="modal_content organizations_dialog_content organizations_members_panel_hidden" id="organizations_tab_members_panel" role="tabpanel" aria-labelledby="organizations_tab_members">
        <div class="organizations_members_content f_column">
          
          <!-- Access Requests Section -->
          <section class="organizations_members_section" id="organizations_members_requests_section">
            <div class="organizations_section_header">
              <h3><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_REQUESTS_H3'); ?></h3>
            </div>
            <div class="visually_hidden">
              <p id="organizations_access_requests_sr_instructions"><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_REQUESTS_SR'); ?></p>
              <p id="organizations_access_requests_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
            </div>
            <div id="organizations_members_requests_list" class="organizations_requests_stack organizations_empty" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_REQUESTS_ARIA'); ?>" aria-describedby="organizations_access_requests_sr_instructions organizations_access_requests_sr_status">
              <p><?php echo organizations_index_i18n('ORGANIZATIONS_NO_PENDING_REQUESTS'); ?></p>
            </div>
          </section>

          <!-- Member List Section -->
          <section class="organizations_members_section" id="organizations_members_invite_section">
            <div class="organizations_section_header">
              <h3><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_H3'); ?></h3>
              <div class="members_list_controls">
                <select id="organizations_members_role_filter" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_FILTER_ARIA'); ?>">
                  <option value=""><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_FILTER_ALL'); ?></option>
                  <option value="coordinator"><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_ROLE_COORDINATOR'); ?></option>
                  <option value="contributor"><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_ROLE_CONTRIBUTOR'); ?></option>
                  <option value="viewer"><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_ROLE_VIEWER'); ?></option>
                  <option value="member"><?php echo organizations_index_i18n('ORGANIZATIONS_ROLE_MEMBER'); ?></option>
                  <option value="owner"><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_ROLE_OWNER'); ?></option>
                </select>
              </div>
            </div>
            <div class="visually_hidden">
              <p id="organizations_members_grid_sr_instructions"><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_GRID_SR'); ?></p>
              <p id="organizations_members_grid_sr_context"><?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_GRID_CONTEXT'); ?></p>
              <p id="organizations_members_grid_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
            </div>
            <div id="organizations-members-grid" class="datagrid_container" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_MEMBERS_GRID_ARIA'); ?>" aria-describedby="organizations_members_grid_sr_instructions organizations_members_grid_sr_context organizations_members_grid_sr_status">
              <div class="datagrid_body"></div>
            </div>
          </section>

          <!-- Send Invite Section -->
          <section class="organizations_members_section">
            <div class="organizations_section_header">
              <h3><?php echo organizations_index_i18n('ORGANIZATIONS_SEND_INVITE'); ?></h3>
            </div>
            <form id="organizations_members_invite_form" class="organizations_members_invite_form">
              <div class="form_group">
                <label for="organizations_members_invite_email"><?php echo organizations_index_i18n('ORGANIZATIONS_INVITE_EMAIL_LABEL'); ?></label>
                <input type="email" id="organizations_members_invite_email" name="email" maxlength="160" autocomplete="email" placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_INVITE_EMAIL_PLACEHOLDER'); ?>" required>
              </div>
              <div class="form_actions">
                <button type="submit" class="btn btn_primary"><?php echo organizations_index_i18n('ORGANIZATIONS_SEND_INVITE'); ?></button>
              </div>
              <div id="organizations_members_invite_status" class="form_status_message" role="status" aria-live="polite"></div>
            </form>

            <div class="organizations_members_invites_grid">
              <div class="organizations_members_invites_block">
                <div class="organizations_section_header organizations_members_subsection_header">
                  <h4><?php echo organizations_index_i18n('ORGANIZATIONS_INVITES_PENDING_H4'); ?></h4>
                </div>
                <div class="visually_hidden">
                  <p id="organizations_invites_sr_status" role="status" aria-live="polite" aria-atomic="true"></p>
                </div>
                <div id="organizations_members_invites_list" class="organizations_stack organizations_members_invites_listbody organizations_empty" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_INVITES_PENDING_ARIA'); ?>" aria-describedby="organizations_invites_sr_status"><?php echo organizations_index_i18n('ORGANIZATIONS_INVITES_LOADING'); ?></div>
              </div>

              <div class="organizations_members_invites_block">
                <div class="organizations_section_header organizations_members_subsection_header">
                  <h4><?php echo organizations_index_i18n('ORGANIZATIONS_INVITES_HISTORY_H4'); ?></h4>
                </div>
                <div id="organizations-invite-history-grid-host" class="datagrid_container organizations_members_invites_history_grid" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_INVITES_HISTORY_ARIA'); ?>">
                  <div class="datagrid_body"><div class="datagrid_empty"><?php echo organizations_index_i18n('ORGANIZATIONS_INVITES_HISTORY_LOADING'); ?></div></div>
                </div>
              </div>
            </div>

            <div class="organizations_members_import_block organizations_members_import_card" id="organizations_members_import_section">
              <h4><?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_H4'); ?></h4>
              <p class="help_text organizations_members_import_intro"><?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_HELP'); ?></p>

              <div class="organizations_members_import_stepflow" role="list" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_WORKFLOW_ARIA'); ?>">
                <span class="organizations_members_import_stepflow_item" role="listitem"><?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_STEP_PARSE'); ?></span>
                <span class="organizations_members_import_stepflow_sep" aria-hidden="true">&gt;&gt;</span>
                <span class="organizations_members_import_stepflow_item" role="listitem"><?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_STEP_VERIFY'); ?></span>
                <span class="organizations_members_import_stepflow_sep" aria-hidden="true">&gt;&gt;</span>
                <span class="organizations_members_import_stepflow_item" role="listitem"><?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_STEP_IMPORT'); ?></span>
              </div>

              <div class="organizations_members_import_layout">
                <section class="organizations_members_import_column organizations_members_import_column_left" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_INPUT_ARIA'); ?>">
                  <div class="form_group organizations_members_import_textarea_group">
                    <label for="organizations_members_import_emails"><?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_EMAILS_LABEL'); ?></label>
                    <textarea
                      id="organizations_members_import_emails"
                      name="emails"
                      rows="10"
                      maxlength="20000"
                      placeholder="<?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_EMAILS_PLACEHOLDER'); ?>"
                    ></textarea>
                  </div>
                </section>

                <section class="organizations_members_import_column organizations_members_import_column_right" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_OUTPUT_ARIA'); ?>">
                  <h5 class="organizations_members_import_output_title"><?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_OUTPUT_H5'); ?></h5>
                  <div id="organizations_members_import_summary" class="organizations_members_import_summary_card organizations_empty" role="region" aria-label="<?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_SUMMARY_ARIA'); ?>"></div>
                  <div id="organizations_members_import_status" class="form_status_message organizations_members_import_status" role="status" aria-live="polite"></div>
                </section>
              </div>

              <div class="form_actions organizations_members_import_actions organizations_members_import_actions_row">
                <button id="organizations_members_import_prepare" type="button" class="btn btn_secondary"><?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_BTN_PARSE'); ?></button>
                <button id="organizations_members_import_send_code" type="button" class="btn btn_primary" disabled><?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_BTN_SEND_CODE'); ?></button>
                <input id="organizations_members_import_code" class="organizations_members_import_code_input" type="text" inputmode="numeric" maxlength="6" autocomplete="one-time-code" placeholder="<?php echo organizations_index_i18n('UNVERIFIED_CODE_PLACEHOLDER'); ?>" disabled>
                <button id="organizations_members_import_verify" type="button" class="btn btn_secondary" disabled><?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_BTN_VERIFY'); ?></button>
                <button id="organizations_members_import_commit" type="button" class="btn btn_primary" disabled><?php echo organizations_index_i18n('ORGANIZATIONS_IMPORT_BTN_COMMIT'); ?></button>
              </div>
            </div>
          </section>

        </div>
      </section>

      <section class="modal_footer organizations_dialog_footer">
        <button id="organizations_bootstrap_dek_button" type="button" class="btn btn_secondary">Create Org DEKs</button>
        <button id="organizations_save_button" type="button" class="btn btn_primary"><?php echo organizations_index_i18n('UPDATE'); ?></button>
        <button id="organizations_close_button" type="button" class="btn btn_secondary"><?php echo organizations_index_i18n('CLOSE'); ?></button>
      </section>

      <div id="organizations_dialog_live_toast" class="organizations_live_toast" role="status" aria-live="polite" aria-atomic="true"></div>
    </form>
  </dialog>

  <div id="organizations_live_toast" class="organizations_live_toast" role="status" aria-live="polite" aria-atomic="true"></div>

<?php

echo PHP_EOL.Render::jsScript('organizations');

require_once \PayCal\Domain\Config\Environment::appHome().'html/footer.php';
