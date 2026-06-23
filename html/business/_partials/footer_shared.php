<?php declare(strict_types=1);

namespace PayCal\Domain;

?>
  <input type="hidden" id="businesses_csrf_token" value="<?php echo $businessesCsrfNonce; ?>">

  <div id="businesses_live_toast" class="businesses_live_toast" role="status" aria-live="polite" aria-atomic="true"></div>

<?php
echo PHP_EOL.Render::jsScript('business');
