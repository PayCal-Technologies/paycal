<?php declare(strict_types=1);

use PayCal\Domain\InputSanitizer;

require_once '../../config.php';

\PayCal\Observability\Lens::boot('blog-article-legacy-route');

$slugRaw = InputSanitizer::getString('slug') ?? '';
$slug = strtolower(trim($slugRaw));
if ($slug !== '' && preg_match('/^[a-z0-9-]+$/', $slug)) {
  header('Location: /blog/' . rawurlencode($slug) . '/', true, 301);
  exit;
}

header('Location: /blog/', true, 302);
exit;
