<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

Javascript::renderDocBlock();

$manifestPath = __DIR__ . '/../../src/Domain/TaxRateTablesData.json';
$rawManifest = is_file($manifestPath) ? file_get_contents($manifestPath) : false;
$decodedManifest = is_string($rawManifest) ? json_decode($rawManifest, true) : null;

if (!is_array($decodedManifest)) {
  $decodedManifest = [
    'federal' => [],
    'provincial' => [],
  ];
}

/** @var array<string, mixed> $decodedManifest */
?>const TAX_RATE_TABLES = <?php echo json_encode($decodedManifest, JSON_UNESCAPED_SLASHES); ?>;

export default TAX_RATE_TABLES;
