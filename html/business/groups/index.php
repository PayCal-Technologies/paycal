<?php declare(strict_types=1);

namespace PayCal\Domain;

$currentPage = 'PAGE_BUSINESS_GROUPS';

require_once dirname(__DIR__) . '/_layout.php';
BusinessNav::requireCoordinatorAccess();

$groupsGridRenderer = new BusinessGroupsGridRenderer();
$businessGroupsGridBodyHtml = $groupsGridRenderer->loadingSkeleton();
$groupsGridRenderSuccess = false;
$groupsGridStatusMessage = '';
if ($workspaceBusinessId !== '') {
  $groupsGridResult = $groupsGridRenderer->renderForBusiness($userUUID, $workspaceBusinessId, [
    'status' => 'active',
  ]);
  $businessGroupsGridBodyHtml = (string) ($groupsGridResult['html'] ?? $groupsGridRenderer->loadingSkeleton());
  $groupsGridRenderSuccess = (bool) ($groupsGridResult['success'] ?? false);
  $groupsGridStatusMessage = (string) ($groupsGridResult['message'] ?? '');
} else {
  $businessGroupsGridBodyHtml = $groupsGridRenderer->emptyMessage(businesses_index_i18n('BUSINESSES_SELECT_FIRST'));
  $groupsGridStatusMessage = businesses_index_i18n('BUSINESSES_SELECT_FIRST');
}

$createOnLoad = InputSanitizer::getString('create') === '1';
?>

<div id="business-workspace" class="business_workspace business_groups" data-business-subpage="groups"<?php echo $workspaceBusinessIdAttr; ?> data-create-on-load="<?php echo $createOnLoad ? '1' : '0'; ?>">

  <h1 class="visually_hidden"><?php echo Strings::i18n('BUSINESS_NAV_GROUPS'); ?></h1>

  <section class="panel business_groups_panel" aria-label="<?php echo Strings::i18n('BUSINESS_GROUPS_TITLE'); ?>">
    <div class="business_status_legend_row">
      <div class="business_status_action_group" role="group" aria-label="<?php echo Strings::i18n('BUSINESS_GROUPS_STATUS_TABS_ARIA'); ?>">
        <ul class="tabs business_groups_status_tabs">
          <li>
            <button type="button" class="tab active" data-business-groups-status="active" aria-pressed="true">
              <?php echo Strings::i18n('BUSINESS_GROUPS_TAB_ACTIVE'); ?>
            </button>
          </li>
          <li>
            <button type="button" class="tab" data-business-groups-status="archived" aria-pressed="false">
              <?php echo Strings::i18n('BUSINESS_GROUPS_TAB_ARCHIVED'); ?>
            </button>
          </li>
        </ul>
        <button
          type="button"
          class="btn btn_primary business_status_add_button"
          data-action="create-business-group"
          aria-label="<?php echo Strings::i18n('BUSINESS_GROUPS_ADD_ARIA'); ?>"
        ><?php echo Strings::i18n('BUSINESS_STATUS_ADD_SHORT'); ?></button>
      </div>

    </div>

    <div id="business_groups_active_panel">
      <div class="visually_hidden">
        <p id="business_groups_sr_instructions"><?php echo Strings::i18n('BUSINESS_GROUPS_GRID_SR'); ?></p>
        <p id="business_groups_grid_sr_context"><?php echo Strings::i18n('BUSINESS_GROUPS_GRID_SR_CONTEXT'); ?></p>
        <p id="business_groups_sr_status" role="status" aria-live="polite" aria-atomic="true"><?php echo htmlspecialchars($groupsGridStatusMessage, ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <div
        id="business-groups-grid"
        class="datagrid_container business_groups_datagrid"
        role="region"
        aria-label="<?php echo Strings::i18n('BUSINESS_GROUPS_GRID_ARIA'); ?>"
        aria-describedby="business_groups_sr_instructions business_groups_grid_sr_context business_groups_sr_status"
        <?php if ($groupsGridRenderSuccess) { ?>data-ssr-groups-grid="active"<?php } ?>
      >
        <div class="datagrid_body"><?php echo $businessGroupsGridBodyHtml; ?></div>
      </div>
    </div>
  </section>

  <dialog id="modal_business_group" class="dialog business_group_editor_dialog" aria-modal="true" aria-labelledby="modal_business_group_title" aria-describedby="modal_business_group_aria">
    <div class="modal_aria visually_hidden">
      <span id="modal_business_group_aria"><?php echo Strings::i18n('BUSINESS_GROUPS_EDITOR_ARIA'); ?></span>
    </div>
    <form id="business_groups_form" class="business_group_editor_form" method="dialog">
      <input type="hidden" id="business_groups_group_id" value="">
      <section class="modal_header">
        <h2 id="modal_business_group_title" class="modal_title"><?php echo Strings::i18n('BUSINESS_GROUPS_EDITOR_TITLE'); ?></h2>
        <button type="button" class="btn_close" data-dialog-close="modal_business_group" aria-label="<?php echo Strings::i18n('CLOSE'); ?>">&times;</button>
      </section>
      <section class="modal_content business_group_editor_content">
        <div class="business_group_editor_col business_group_editor_col_main">
          <h3 class="business_group_editor_col_heading"><?php echo Strings::i18n('BUSINESS_GROUPS_EDITOR_DETAILS'); ?></h3>
          <label class="business_groups_field" for="business_groups_name">
            <span><?php echo Strings::i18n('BUSINESS_GROUPS_NAME'); ?></span>
            <input id="business_groups_name" name="name" type="text" maxlength="80" autocomplete="off" required>
          </label>
          <label class="business_groups_field" for="business_groups_description">
            <span><?php echo Strings::i18n('BUSINESS_GROUPS_DESCRIPTION_OPTIONAL'); ?></span>
            <textarea id="business_groups_description" name="description" maxlength="300" rows="4"></textarea>
          </label>
        </div>
      </section>
      <section class="modal_footer">
        <p id="business_groups_status_message" class="status_message centered" role="status" aria-live="polite"></p>
        <div class="business_groups_form_actions">
          <button type="button" class="btn btn_danger" id="business_groups_delete" hidden><?php echo Strings::i18n('BUSINESS_GROUPS_DELETE_ACTION'); ?></button>
          <button type="submit" class="btn btn_primary" id="business_groups_submit"><?php echo Strings::i18n('BUSINESS_GROUPS_CREATE'); ?></button>
          <button type="button" class="btn btn_secondary" id="business_groups_cancel" data-dialog-close="modal_business_group"><?php echo Strings::i18n('CANCEL'); ?></button>
        </div>
      </section>
    </form>
  </dialog>

  <dialog id="modal_business_group_confirm" class="dialog business_group_editor_dialog business_group_confirm_dialog" aria-modal="true" aria-labelledby="modal_business_group_confirm_title" aria-describedby="modal_business_group_confirm_message">
    <section class="modal_header">
      <h2 id="modal_business_group_confirm_title" class="modal_title"><?php echo Strings::i18n('BUSINESS_GROUPS_ARCHIVE_ACTION'); ?></h2>
      <button type="button" class="btn_close" data-dialog-close="modal_business_group_confirm" aria-label="<?php echo Strings::i18n('CLOSE'); ?>">&times;</button>
    </section>
    <section class="modal_content business_group_confirm_content">
      <p id="modal_business_group_confirm_message"></p>
    </section>
    <section class="modal_footer">
      <div class="business_groups_form_actions">
        <button type="button" class="btn btn_primary" id="business_group_confirm_yes"><?php echo Strings::i18n('BUSINESS_GROUPS_ARCHIVE_ACTION'); ?></button>
        <button type="button" class="btn btn_secondary" id="business_group_confirm_cancel" data-dialog-close="modal_business_group_confirm"><?php echo Strings::i18n('CANCEL'); ?></button>
      </div>
    </section>
  </dialog>
</div>

<?php
require __DIR__ . '/../_partials/footer_shared.php';
require_once \PayCal\Domain\Config\Environment::appHome() . 'html/footer.php';
