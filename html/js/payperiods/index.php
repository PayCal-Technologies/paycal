<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

Javascript::renderDocBlock();

?>

import PC from '<?php echo Environment::appURL('js/'); ?>';
import PW from '<?php echo Environment::appURL('js/phantomwing/'); ?>';

document.addEventListener("DOMContentLoaded", () => {

});
