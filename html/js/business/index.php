<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Business workspace JS router — loads shared core then workspace body.
 * Subpage behaviour is keyed off data-business-subpage on #business-workspace.
 */
require __DIR__ . '/_bootstrap.php';

Javascript::beginSourceMapBundle('business');

?>
import PC from '<?php echo Render::jsModuleURL(); ?>';
import PW from '<?php echo Render::jsModuleURL('phantomwing'); ?>';
import { createDataGrid, bindDataGridKeyboardNavigation } from '<?php echo Render::jsModuleURL('datagrid'); ?>';
import { initializeBillingSection } from '<?php echo Render::jsStaticURL('js/core/billing.js'); ?>';
import {
  buildPayPeriodPreviewState,
  resolvePayPeriodPreviewSelection,
} from '<?php echo Render::jsStaticURL('js/core/pay-period-preview.js'); ?>';
import { initMemberReportsEarningsView } from '<?php echo Render::jsStaticURL('js/earnings/member-reports-view.js'); ?>';
import EarningsExport from '<?php echo Render::jsStaticURL('js/earnings/earnings-export.js'); ?>';
import { formatPhpTemplate } from '<?php echo Render::jsStaticURL('js/core/template.js'); ?>';
import {
  setPlainStatusText as setCorePlainStatusText,
  setStatusText as setCoreStatusText,
} from '<?php echo Render::jsStaticURL('js/core/status-text.js'); ?>';

(() => {
  'use strict';

  const Guardian = window.Guardian;
  if (!Guardian || typeof Guardian.setHTML !== 'function') {
    throw new Error('Guardian module is required before business workspace');
  }

<?php
Javascript::emitJsSegment(__DIR__ . '/core/state.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/ui-helpers.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/api.js.php');
Javascript::emitJsSegment(__DIR__ . '/workspace.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/contact-cards.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/display-utils.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/timestamp-popovers.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/business-permissions.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/business-grid.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/current-business-panel.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/person-connections.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/access-lookup.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/searchable-picker.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/personal-settings.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/profile-account.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/profile-billing.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/account-activity.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/audit-grids.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/discovery.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/business-browser.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/membership-consent.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/access-management.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/member-actions.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/member-role-popover.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/datagrid-reload-handlers.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/report-export-utils.js.php');
Javascript::emitJsSegment(__DIR__ . '/core/context-header.js.php');
Javascript::emitJsSegment(__DIR__ . '/subpages/dashboard.js.php');
Javascript::emitJsSegment(__DIR__ . '/subpages/details.js.php');
Javascript::emitJsSegment(__DIR__ . '/subpages/members.js.php');
Javascript::emitJsSegment(__DIR__ . '/subpages/groups.js.php');
Javascript::emitJsSegment(__DIR__ . '/../sites/i18n.php');
Javascript::emitJsSegment(__DIR__ . '/../sites/site-editor-core.php');
Javascript::emitJsSegment(__DIR__ . '/subpages/sites.js.php');
Javascript::emitJsSegment(__DIR__ . '/subpages/payroll.js.php');
Javascript::emitJsSegment(__DIR__ . '/subpages/audit.js.php');
Javascript::emitJsSegment(__DIR__ . '/subpages/reports.js.php');
?>

  initialize().catch((error) => {
    PW.error(error);
    setGridMessage(T.loadBusinessesFailed);
  });
})();
<?php Javascript::finishSourceMapBundle(); ?>
