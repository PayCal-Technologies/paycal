<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Assigned / linked sites datagrid — mounted on Business Sites page.
 */
$canCreateBusinessSite = false;
if (!empty($workspaceBusinessId) && !empty($userUUID)) {
  $canCreateBusinessSite = (new BusinessDiscoveryService())->canWriteBusinessSites((string) $workspaceBusinessId, (string) $userUUID);
}
?>
  <section class="panel businesses_sites_assigned_panel">
    <div class="business_status_legend_row">
      <div class="business_status_action_group" role="group" aria-label="<?php echo businesses_index_i18n('BUSINESS_SITES_STATUS_TABS_ARIA'); ?>">
        <ul class="tabs business_sites_status_tabs">
          <li>
            <button type="button" class="tab active" data-business-sites-status="active" aria-pressed="true">
              <?php echo businesses_index_i18n('BUSINESS_SITES_TAB_ACTIVE'); ?>
            </button>
          </li>
          <li>
            <button type="button" class="tab" data-business-sites-status="archived" aria-pressed="false">
              <?php echo businesses_index_i18n('BUSINESS_SITES_TAB_ARCHIVED'); ?>
            </button>
          </li>
        </ul>
        <?php if ($canCreateBusinessSite) { ?>
        <button
          type="button"
          class="btn btn_primary business_status_add_button"
          data-action="create-business-site"
          aria-label="<?php echo businesses_index_i18n('BUSINESS_SITES_ADD_ARIA'); ?>"
        ><?php echo businesses_index_i18n('BUSINESS_STATUS_ADD_SHORT'); ?></button>
        <?php } ?>
      </div>

      <div class="business_sites_ownership_legend" aria-label="<?php echo businesses_index_i18n('BUSINESS_SITES_OWNERSHIP_LEGEND_ARIA'); ?>">
        <span class="business_sites_ownership_legend_item">
          <span class="business_sites_ownership_symbol business_sites_ownership_symbol--business" aria-hidden="true"></span>
          <span><?php echo businesses_index_i18n('BUSINESS_SITES_STATUS_TAG_BUSINESS'); ?></span>
        </span>
        <span class="business_sites_ownership_legend_item">
          <span class="business_sites_ownership_symbol business_sites_ownership_symbol--personal" aria-hidden="true"></span>
          <span><?php echo businesses_index_i18n('BUSINESS_SITES_STATUS_TAG_PERSONAL'); ?></span>
        </span>
        <span class="business_sites_ownership_legend_item">
          <span class="business_sites_ownership_symbol business_sites_ownership_symbol--shared" aria-hidden="true"></span>
          <span><?php echo businesses_index_i18n('BUSINESS_SITES_STATUS_TAG_SHARED'); ?></span>
        </span>
      </div>

    </div>
    <div id="businesses_sites_active_panel">
      <div class="visually_hidden">
        <p id="businesses_sites_sr_instructions"><?php echo businesses_index_i18n('BUSINESS_SITES_ASSIGNED_SR'); ?></p>
        <p id="businesses_sites_grid_sr_context"><?php echo businesses_index_i18n('BUSINESS_SITES_GRID_SR_CONTEXT'); ?></p>
        <p id="businesses_sites_sr_status" role="status" aria-live="polite" aria-atomic="true"><?php echo htmlspecialchars((string) ($businessSitesGridStatusMessage ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
      </div>
      <div
        id="businesses-sites-grid"
        class="datagrid_container business_sites_datagrid"
        role="region"
        aria-label="<?php echo businesses_index_i18n('BUSINESS_SITES_GRID_ARIA'); ?>"
        aria-describedby="businesses_sites_sr_instructions businesses_sites_grid_sr_context businesses_sites_sr_status"
        <?php if (!empty($sitesGridRenderSuccess)) { ?>data-ssr-sites-grid="active"<?php } ?>
      >
        <div class="datagrid_body"><?php echo $businessSitesGridBodyHtml ?? ''; ?></div>
      </div>
    </div>
  </section>
