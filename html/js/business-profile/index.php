<?php declare(strict_types=1);

/**
 * Profile/account business JS — personal org, billing, free-tier connect.
 * Loaded on Settings → Account (formerly /profile/).
 */
require __DIR__ . '/../business/_bootstrap.php';

?>
import PC from "<?php echo \PayCal\Domain\Config\Environment::appURL('js/'); ?>";
import PW from "<?php echo \PayCal\Domain\Config\Environment::appURL('js/phantomwing/'); ?>";
import { createDataGrid } from "/js/datagrid/";
import { initializeBillingSection } from "../core/billing.js";

(() => {
  'use strict';
  window.PAYCAL_BUSINESS_JS_MODE = 'profile';

  const Guardian = window.Guardian;
  if (!Guardian || typeof Guardian.setHTML !== 'function') {
    throw new Error('Guardian module is required before business profile shim');
  }

<?php
require __DIR__ . '/../business/core/state.js.php';
require __DIR__ . '/../business/workspace.js.php';
require __DIR__ . '/../business/core/context-header.js.php';
?>

  initialize().catch((error) => {
    PW.error(error);
    const message = error instanceof Error && error.message ? error.message : T.unavailable;
    PC.showToast(message, 'error', 7000, true);
  });
})();
