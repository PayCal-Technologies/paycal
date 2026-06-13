<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

Javascript::renderDocBlock();

$registerI18n = [
  'AUTH_REGISTER_PASSWORDS_NO_MATCH' => Strings::i18n('AUTH_REGISTER_PASSWORDS_NO_MATCH'),
];

?>
const REGISTER_T = <?php echo json_encode($registerI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

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
      e.preventDefault();
      PC.showToast(REGISTER_T.AUTH_REGISTER_PASSWORDS_NO_MATCH);
    }
  });
});



