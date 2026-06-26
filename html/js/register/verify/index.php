<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../../config.php';

CORS::handleORIGIN();
Javascript::renderModuleContentType('application/javascript');

Javascript::renderDocBlock();

?>

import { bindGroupedCodeInput } from '/js/core/paycal-code.js';

/**
 * Event Listeners specifically for the Registration Verfication Page
 */
window.addEventListener('DOMContentLoaded', () => {
  const verificationInput = document.getElementById('verification_code');
  if (!verificationInput) {
    return;
  }

  bindGroupedCodeInput(verificationInput, {
    allowedChars: <?php echo json_encode(\PayCal\Domain\SystemConfig::PC_VERIFICATION_SET); ?>,
    maxLength: <?php echo \PayCal\Domain\SystemConfig::PC_VERIFICATION_LENGTH; ?>,
    splitAt: <?php echo (int) ceil(\PayCal\Domain\SystemConfig::PC_VERIFICATION_LENGTH / 2); ?>,
  });
});


