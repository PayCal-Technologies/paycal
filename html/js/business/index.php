<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Business workspace JS router — loads shared core then workspace body.
 * Subpage behaviour is keyed off data-business-subpage on #business-workspace.
 */
require __DIR__ . '/_bootstrap.php';

?>
import PC from "<?php echo Config\Environment::appURL('js/'); ?>";
import PW from "<?php echo Config\Environment::appURL('js/phantomwing/'); ?>";
import { createDataGrid, bindDataGridKeyboardNavigation } from "/js/datagrid/";
import { initializeBillingSection } from "../core/billing.js";
import {
  buildPayPeriodPreviewState,
  resolvePayPeriodPreviewSelection,
} from "/js/core/pay-period-preview.js";
import { initMemberReportsEarningsView } from "/js/earnings/member-reports-view.js";
import EarningsExport from "/js/earnings/earnings-export.js";
import { formatPhpTemplate } from "/js/core/template.js";
import {
  setPlainStatusText as setCorePlainStatusText,
  setStatusText as setCoreStatusText,
} from "/js/core/status-text.js";

(() => {
  'use strict';

  const Guardian = window.Guardian;
  if (!Guardian || typeof Guardian.setHTML !== 'function') {
    throw new Error('Guardian module is required before business workspace');
  }

<?php
require __DIR__ . '/core/state.js.php';
require __DIR__ . '/core/ui-helpers.js.php';
require __DIR__ . '/core/api.js.php';
require __DIR__ . '/workspace.js.php';
require __DIR__ . '/core/contact-cards.js.php';
require __DIR__ . '/core/display-utils.js.php';
require __DIR__ . '/core/timestamp-popovers.js.php';
require __DIR__ . '/core/business-permissions.js.php';
require __DIR__ . '/core/business-grid.js.php';
require __DIR__ . '/core/current-business-panel.js.php';
require __DIR__ . '/core/person-connections.js.php';
require __DIR__ . '/core/access-lookup.js.php';
require __DIR__ . '/core/searchable-picker.js.php';
require __DIR__ . '/core/personal-settings.js.php';
require __DIR__ . '/core/profile-account.js.php';
require __DIR__ . '/core/profile-billing.js.php';
require __DIR__ . '/core/account-activity.js.php';
require __DIR__ . '/core/audit-grids.js.php';
require __DIR__ . '/core/discovery.js.php';
require __DIR__ . '/core/business-browser.js.php';
require __DIR__ . '/core/membership-consent.js.php';
require __DIR__ . '/core/access-management.js.php';
require __DIR__ . '/core/member-actions.js.php';
require __DIR__ . '/core/member-role-popover.js.php';
require __DIR__ . '/core/datagrid-reload-handlers.js.php';
require __DIR__ . '/core/report-export-utils.js.php';
require __DIR__ . '/core/context-header.js.php';
require __DIR__ . '/subpages/dashboard.js.php';
require __DIR__ . '/subpages/details.js.php';
require __DIR__ . '/subpages/members.js.php';
require __DIR__ . '/subpages/groups.js.php';
require __DIR__ . '/../sites/i18n.php';
require __DIR__ . '/../sites/site-editor-core.php';
require __DIR__ . '/subpages/sites.js.php';
require __DIR__ . '/subpages/payroll.js.php';
require __DIR__ . '/subpages/audit.js.php';
require __DIR__ . '/subpages/reports.js.php';
?>

  initialize().catch((error) => {
    PW.error(error);
    setGridMessage(T.loadBusinessesFailed);
  });
})();
