<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>

/* Shared content styles for public layout pages loaded via /css/content/. */
.content-prose a,
.blog_content a {
  text-decoration-thickness: 0.09em;
  text-underline-offset: 0.14em;
}
