<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
Javascript::renderModuleContentType('application/javascript');

Javascript::renderDocBlock();

?>

import PC from '<?php echo Render::jsModuleURL(); ?>';
import PW from '<?php echo Render::jsModuleURL('phantomwing'); ?>';

document.addEventListener("DOMContentLoaded", () => {

});
