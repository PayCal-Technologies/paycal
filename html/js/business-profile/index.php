<?php declare(strict_types=1);

/**
 * Account business JS — personal org, billing, free-tier connect.
 * Loaded on Settings → Account.
 */
require __DIR__ . '/../business/_bootstrap.php';

?>
import PC from "<?php echo \PayCal\Domain\Config\Environment::appURL('js/'); ?>";
import PW from "<?php echo \PayCal\Domain\Config\Environment::appURL('js/phantomwing/'); ?>";
import { createDataGrid } from "/js/datagrid/";
import { initializeBillingSection } from "../core/billing.js";
import {
  buildPayPeriodPreviewState,
  resolvePayPeriodPreviewSelection,
} from "/js/core/pay-period-preview.js";

(() => {
  'use strict';
  window.PAYCAL_BUSINESS_JS_MODE = 'profile';

  const Guardian = window.Guardian;
  if (!Guardian || typeof Guardian.setHTML !== 'function') {
    throw new Error('Guardian module is required before the account business module');
  }

<?php
require __DIR__ . '/../business/core/state.js.php';
require __DIR__ . '/../business/core/ui-helpers.js.php';
require __DIR__ . '/../business/core/api.js.php';
require __DIR__ . '/../business/workspace.js.php';
require __DIR__ . '/../business/core/contact-cards.js.php';
require __DIR__ . '/../business/core/display-utils.js.php';
require __DIR__ . '/../business/core/timestamp-popovers.js.php';
require __DIR__ . '/../business/core/business-permissions.js.php';
require __DIR__ . '/../business/core/business-grid.js.php';
require __DIR__ . '/../business/core/current-business-panel.js.php';
require __DIR__ . '/../business/core/person-connections.js.php';
require __DIR__ . '/../business/core/access-lookup.js.php';
require __DIR__ . '/../business/core/searchable-picker.js.php';
require __DIR__ . '/../business/core/personal-settings.js.php';
require __DIR__ . '/../business/core/profile-account.js.php';
require __DIR__ . '/../business/core/profile-billing.js.php';
require __DIR__ . '/../business/core/account-activity.js.php';
require __DIR__ . '/../business/core/audit-grids.js.php';
require __DIR__ . '/../business/core/discovery.js.php';
require __DIR__ . '/../business/core/business-browser.js.php';
require __DIR__ . '/../business/core/membership-consent.js.php';
require __DIR__ . '/../business/core/access-management.js.php';
require __DIR__ . '/../business/core/member-actions.js.php';
require __DIR__ . '/../business/core/member-role-popover.js.php';
require __DIR__ . '/../business/core/datagrid-reload-handlers.js.php';
require __DIR__ . '/../business/core/context-header.js.php';
?>

  initialize().catch((error) => {
    PW.error(error);
    const message = error instanceof Error && error.message ? error.message : T.unavailable;
    PC.showToast(message, 'error', 7000, true);
  });
})();
