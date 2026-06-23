<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';

if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>

.reports_page_shell {
  max-width: var(--app-content-width, 100%);
  margin: 0 0 2rem;
}

.reports_page_title {
  margin: 0 0 0.75rem;
}

.reports_page_intro {
  margin: 0 0 1rem;
  opacity: 0.9;
}

.reports_page_links {
  margin: 0;
  padding-left: 1.25rem;
}

.reports_page_links li + li {
  margin-top: 0.5rem;
}
