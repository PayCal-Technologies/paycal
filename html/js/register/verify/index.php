<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../../config.php';

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

Javascript::renderDocBlock();

?>


/**
 * Event Listeners specifically for the Registration Verfication Page
 */
window.addEventListener('DOMContentLoaded', () => {
  const verificationInput = document.getElementById('verification_code');
  if (!verificationInput) {
    return;
  }

  const verificationSet = <?php echo json_encode(\PayCal\Domain\SystemConfig::PC_VERIFICATION_SET); ?>;
  const disallowedChars = new RegExp(`[^${verificationSet}]`, 'gi');
  const maxLength = <?php echo \PayCal\Domain\SystemConfig::PC_VERIFICATION_LENGTH; ?>;

  const formatVerificationCode = () => {
    const splitAt = Math.ceil(maxLength / 2);
    const normalized = verificationInput.value.toUpperCase().replace(disallowedChars, '').slice(0, maxLength);
    if (normalized.length > splitAt) {
      verificationInput.value = `${normalized.slice(0, splitAt)}-${normalized.slice(splitAt)}`;
      return;
    }

    verificationInput.value = normalized;
  };

  verificationInput.addEventListener('input', formatVerificationCode);
  verificationInput.addEventListener('keyup', formatVerificationCode);
  verificationInput.addEventListener('blur', formatVerificationCode);
});



