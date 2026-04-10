<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;
use PayCal\Domain\Enums\HttpStatus;
use PayCal\Observability\GraphAnalyzer;
use PayCal\Observability\Lens;

require_once __DIR__ . '/../../../config.php';

Authentication::abortIfUnauthenticated();
Authentication::isAdminOrDie();
CORS::handleORIGIN();

Lens::boot('admin-ast-data');

$graph = loadGraph();

$analyzer = new GraphAnalyzer($graph);
$action = getParam('action', 'summary');

switch ($action) {
  case 'graph':
    Response::success('AST graph', [
      'nodes' => $graph['nodes'],
      'edges' => $graph['edges'],
    ]);

  case 'summary':
    Response::success('AST summary', [
      'metrics' => $analyzer->metrics(),
      'top_heatmap' => $analyzer->heatmapByFile(getInt('limit', 20, 1, 200)),
      'dead_nodes' => $analyzer->deadNodes(getInt('limit', 50, 1, 500), nullableString(getParam('type', ''))),
      'layer_violations' => $analyzer->layerViolations(getInt('limit', 50, 1, 500)),
      'available_actions' => ['graph', 'summary', 'search', 'subgraph', 'trace', 'blast', 'dead', 'violations', 'heatmap', 'export'],
    ]);

  case 'search':
    $query = getParam('q', '');
    if ($query === '') {
      Response::error('Missing q parameter');
    }
    Response::success('AST search', [
      'result' => $analyzer->search($query, getParam('mode', 'contains'), getInt('limit', 100, 1, 500)),
    ]);

  case 'subgraph':
    $root = getParam('root', '');
    if ($root === '') {
      Response::error('Missing root parameter');
    }
    $edgeTypes = getList('edge_type');
    Response::success('AST subgraph', [
      'result' => $analyzer->subgraph($root, getInt('depth', 2, 1, 6), getParam('direction', 'both'), $edgeTypes),
    ]);

  case 'trace':
    $from = getParam('from', '');
    $to = getParam('to', '');
    if ($from === '' || $to === '') {
      Response::error('Missing from/to parameter');
    }
    Response::success('AST trace', [
      'result' => $analyzer->trace($from, $to, getInt('depth', 8, 1, 20)),
    ]);

  case 'blast':
    $node = getParam('node', '');
    if ($node === '') {
      Response::error('Missing node parameter');
    }
    Response::success('AST blast radius', [
      'result' => $analyzer->blastRadius($node, getParam('direction', 'out'), getInt('depth', 6, 1, 20)),
    ]);

  case 'dead':
    Response::success('AST dead nodes', [
      'result' => $analyzer->deadNodes(getInt('limit', 200, 1, 1000), nullableString(getParam('type', ''))),
    ]);

  case 'violations':
    Response::success('AST layer violations', [
      'result' => $analyzer->layerViolations(getInt('limit', 200, 1, 1000)),
    ]);

  case 'heatmap':
    Response::success('AST file heatmap', [
      'result' => $analyzer->heatmapByFile(getInt('limit', 50, 1, 1000)),
    ]);

  case 'export':
    Response::success('AST export', [
      'metrics' => $analyzer->metrics(),
      'dead_nodes' => $analyzer->deadNodes(getInt('dead_limit', 400, 1, 2000)),
      'layer_violations' => $analyzer->layerViolations(getInt('violation_limit', 400, 1, 2000)),
      'file_heatmap' => $analyzer->heatmapByFile(getInt('heatmap_limit', 200, 1, 2000)),
      'top_user_symbols' => $analyzer->search('user', 'contains', 100),
      'top_calendar_symbols' => $analyzer->search('calendar', 'contains', 100),
    ]);

  default:
    Response::error('Unknown action', ['action' => $action], HttpStatus::HTTP_BAD_REQUEST);
}

/**
 * Keep symbol-like query values intact (e.g. Namespace\\Class::method).
 */
function getParam(string $key, string $default = ''): string
{
  if (!isset($_GET[$key]) || !is_string($_GET[$key])) {
    return $default;
  }

  $value = InputSanitizer::stripControls(trim($_GET[$key]));
  if ($value === '') {
    return $default;
  }

  return mb_substr($value, 0, 300);
}

/**
 * @return list<string>
 */
function getList(string $key): array
{
  if (!isset($_GET[$key])) {
    return [];
  }

  $raw = $_GET[$key];
  $values = [];

  if (is_array($raw)) {
    foreach ($raw as $item) {
      if (!is_string($item)) {
        continue;
      }
      $v = InputSanitizer::stripControls(trim($item));
      if ($v !== '') {
        $values[] = mb_substr($v, 0, 80);
      }
    }
    return array_values(array_unique($values));
  }

  if (is_string($raw)) {
    foreach (explode(',', $raw) as $item) {
      $v = InputSanitizer::stripControls(trim($item));
      if ($v !== '') {
        $values[] = mb_substr($v, 0, 80);
      }
    }
  }

  return array_values(array_unique($values));
}

function getInt(string $key, int $default, int $min, int $max): int
{
  $raw = getParam($key, (string) $default);
  if (!preg_match('/^-?\d+$/', $raw)) {
    return $default;
  }

  $value = (int) $raw;
  if ($value < $min) {
    return $min;
  }
  if ($value > $max) {
    return $max;
  }

  return $value;
}

function nullableString(string $value): ?string
{
  $trimmed = trim($value);
  return $trimmed === '' ? null : $trimmed;
}

function resolveGraphPath(): ?string
{
  $tmpRoot = rtrim(Environment::appHome(), '/') . '/tmp';
  $astDir = $tmpRoot . '/ast';
  $candidate = $astDir . '/dependency-graph.json';

  $resolvedTmpRoot = realpath($tmpRoot);
  $resolvedAstDir = realpath($astDir);
  if ($resolvedTmpRoot === false || $resolvedAstDir === false) {
    return null;
  }

  if (!str_starts_with($resolvedAstDir, $resolvedTmpRoot . DIRECTORY_SEPARATOR) && $resolvedAstDir !== $resolvedTmpRoot) {
    return null;
  }

  return $candidate;
}

/**
 * @return array{nodes: list<array{id: string, type: string, file?: string}>, edges: list<array{from: string, to: string, type: string}>}
 */
function loadGraph(): array
{
  $graphPath = resolveGraphPath();
  if ($graphPath === null || !is_file($graphPath)) {
    failRequest(
      'Graph JSON not found. Generate graph JSON in tmp/ast/dependency-graph.json',
      ['path' => 'tmp/ast/dependency-graph.json'],
      HttpStatus::HTTP_NOT_FOUND
    );
  }

  $raw = file_get_contents($graphPath);
  if (!is_string($raw)) {
    failRequest('Failed to read graph JSON', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
  }

  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    failRequest('Graph JSON is invalid', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
  }

  $nodes = [];
  $edges = [];

  $nodesRaw = $decoded['nodes'] ?? [];
  if (is_array($nodesRaw)) {
    foreach ($nodesRaw as $node) {
      if (!is_array($node)) {
        continue;
      }
      $id = $node['id'] ?? null;
      $type = $node['type'] ?? null;
      if (!is_string($id) || !is_string($type)) {
        continue;
      }
      $normalized = ['id' => $id, 'type' => $type];
      if (isset($node['file']) && is_string($node['file'])) {
        $normalized['file'] = $node['file'];
      }
      $nodes[] = $normalized;
    }
  }

  $edgesRaw = $decoded['edges'] ?? [];
  if (is_array($edgesRaw)) {
    foreach ($edgesRaw as $edge) {
      if (!is_array($edge)) {
        continue;
      }
      $from = $edge['from'] ?? null;
      $to = $edge['to'] ?? null;
      $type = $edge['type'] ?? null;
      if (!is_string($from) || !is_string($to) || !is_string($type)) {
        continue;
      }
      $edges[] = ['from' => $from, 'to' => $to, 'type' => $type];
    }
  }

  return ['nodes' => $nodes, 'edges' => $edges];
}

/**
 * @param array<string, mixed> $meta
 */
function failRequest(string $message, array $meta, int $status): never
{
  Response::error($message, $meta, $status);
  throw new \RuntimeException($message);
}
