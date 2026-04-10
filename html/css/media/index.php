<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>

.media-embed {
  display: block;
  width: 100%;
  max-width: 100%;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  border-radius: 0.35rem;
  border: 1px solid var(--panel-border);
  background: #000;
}

.media-embed-frame {
  display: block;
  width: 100%;
  height: 100%;
  border: 0;
}