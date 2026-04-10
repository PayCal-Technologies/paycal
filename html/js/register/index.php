<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

Javascript::renderDocBlock();

?>

import PC from "<?php echo Environment::appURL('js/'); ?>";


/**
 * Registration Page Event Listeners
 */
window.addEventListener('DOMContentLoaded', () => {

  const registerForm = PC.getElement('register_form');
  const password = PC.getElement('password');
  const confirmPassword = PC.getElement('confirm_password');

  if (!registerForm || !password || !confirmPassword) {
    return;
  }

  registerForm.addEventListener('submit', (e) => {
    if (password.value !== confirmPassword.value) {
      confirm('Passwords do not match.');
      e.preventDefault();
    }
  });
});



