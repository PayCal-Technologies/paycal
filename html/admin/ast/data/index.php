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

$action = getParam('action', 'summary');

if ($action === 'generate') {
  $generated = generateGraphJson();
  Response::success('AST graph generated', [
    'path' => $generated['path'],
    'nodes' => $generated['nodes'],
    'edges' => $generated['edges'],
  ]);
}

$graph = loadGraph();
$analyzer = new GraphAnalyzer($graph);

switch ($action) {
  case 'graph':
    Response::success('AST graph', [
      'nodes' => $graph['nodes'],
      'edges' => $graph['edges'],
    ]);

  case 'metrics':
    Response::success('AST commit metrics', [
      'live_metrics' => $analyzer->metrics(),
      'commit_metrics' => loadCommitMetricsSnapshot(),
    ]);

  case 'summary':
    Response::success('AST summary', [
      'metrics' => $analyzer->metrics(),
      'commit_metrics' => loadCommitMetricsSnapshot(),
      'top_heatmap' => $analyzer->heatmapByFile(getInt('limit', 20, 1, 200)),
      'dead_nodes' => $analyzer->deadNodes(getInt('limit', 50, 1, 500), nullableString(getParam('type', ''))),
      'layer_violations' => $analyzer->layerViolations(getInt('limit', 50, 1, 500)),
      'available_actions' => ['graph', 'metrics', 'summary', 'search', 'subgraph', 'trace', 'blast', 'dead', 'violations', 'heatmap', 'export'],
    ]);

  case 'search':
    $query = getParam('q', '');
    if ($query === '') {
      Response::error('Missing q parameter', ['error_code' => 'missing_q_parameter']);
    }
    Response::success('AST search', [
      'result' => $analyzer->search($query, getParam('mode', 'contains'), getInt('limit', 100, 1, 500)),
    ]);

  case 'subgraph':
    $root = getParam('root', '');
    if ($root === '') {
      Response::error('Missing root parameter', ['error_code' => 'missing_root_parameter']);
    }
    $edgeTypes = getList('edge_type');
    Response::success('AST subgraph', [
      'result' => $analyzer->subgraph($root, getInt('depth', 2, 1, 6), getParam('direction', 'both'), $edgeTypes),
    ]);

  case 'trace':
    $from = getParam('from', '');
    $to = getParam('to', '');
    if ($from === '' || $to === '') {
      Response::error('Missing from/to parameter', ['error_code' => 'missing_from_to_parameter']);
    }
    Response::success('AST trace', [
      'result' => $analyzer->trace($from, $to, getInt('depth', 8, 1, 20)),
    ]);

  case 'blast':
    $node = getParam('node', '');
    if ($node === '') {
      Response::error('Missing node parameter', ['error_code' => 'missing_node_parameter']);
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
    Response::error('Unknown action', ['action' => $action, 'error_code' => 'unknown_action'], HttpStatus::HTTP_BAD_REQUEST);
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
  if ($resolvedTmpRoot === false) {
    return null;
  }

  $resolvedAstDir = realpath($astDir);
  if ($resolvedAstDir === false) {
    // ast/ subdirectory does not exist yet; validate parent only
    if (!str_starts_with($astDir, $resolvedTmpRoot . DIRECTORY_SEPARATOR)) {
      return null;
    }

    return $candidate;
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
    return ['nodes' => [], 'edges' => [], 'empty' => true];
  }

  $raw = file_get_contents($graphPath);
  if (!is_string($raw)) {
    failRequest('Failed to read graph JSON', 'failed_read_graph_json', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
  }

  $decoded = json_decode($raw, true);
  if (!is_array($decoded)) {
    failRequest('Graph JSON is invalid', 'graph_json_invalid', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
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
 * @return array{path: string, nodes: int, edges: int}
 */
function generateGraphJson(): array
{
  $sourceRoot = rtrim(Environment::appHome(), '/') . '/html/src';
  if (!is_dir($sourceRoot)) {
    failRequest('AST source directory not found', 'ast_source_directory_not_found', ['path' => 'html/src'], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
  }

  $classById = [];
  $fileByClass = [];

  foreach (collectPhpFiles($sourceRoot) as $filePath) {
    $content = file_get_contents($filePath);
    if (!is_string($content) || $content === '') {
      continue;
    }

    $namespace = extractNamespace($content);
    foreach (extractClassNames($content) as $className) {
      $fqcn = ltrim(($namespace !== '' ? $namespace . '\\' : '') . $className, '\\');
      if ($fqcn === '') {
        continue;
      }

      $classById[$fqcn] = [
        'id' => $fqcn,
        'type' => 'class',
        'file' => relativePath($filePath),
      ];
      $fileByClass[$fqcn] = $filePath;
    }
  }

  $edges = [];
  foreach ($fileByClass as $fromClass => $filePath) {
    $content = file_get_contents($filePath);
    if (!is_string($content) || $content === '') {
      continue;
    }

    $namespace = extractNamespace($content);
    $uses = extractUseMap($content);

    foreach (extractClassDependencies($content) as $rawSymbol) {
      $target = resolveDependencySymbol($rawSymbol, $namespace, $uses, $classById);
      if ($target === null || $target === $fromClass) {
        continue;
      }
      $edgeKey = $fromClass . '|' . $target . '|depends';
      $edges[$edgeKey] = ['from' => $fromClass, 'to' => $target, 'type' => 'depends'];
    }
  }

  ksort($classById, SORT_STRING);
  ksort($edges, SORT_STRING);

  $graph = [
    'nodes' => array_values($classById),
    'edges' => array_values($edges),
  ];

  $graphPath = resolveGraphPath();
  if ($graphPath === null) {
    failRequest('Unable to resolve graph output path', 'unable_resolve_graph_output_path', ['path' => 'tmp/ast/dependency-graph.json'], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
  }

  $dir = dirname($graphPath);
  if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    failRequest('Unable to create graph output directory', 'unable_create_graph_output_directory', ['path' => relativePath($dir)], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
  }

  $json = json_encode($graph, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
  if (!is_string($json)) {
    failRequest('Unable to encode graph JSON', 'unable_encode_graph_json', [], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
  }

  $bytes = file_put_contents($graphPath, $json . PHP_EOL);
  if ($bytes === false) {
    failRequest('Unable to write graph JSON', 'unable_write_graph_json', ['path' => relativePath($graphPath)], HttpStatus::HTTP_INTERNAL_SERVER_ERROR);
  }

  return [
    'path' => relativePath($graphPath),
    'nodes' => count($graph['nodes']),
    'edges' => count($graph['edges']),
  ];
}

/**
 * @return array<string, mixed>
 */
function loadCommitMetricsSnapshot(): array
{
  $path = rtrim(Environment::appHome(), '/') . '/tmp/ast/metrics-latest.json';
  if (!is_file($path)) {
    return [];
  }

  $raw = file_get_contents($path);
  if (!is_string($raw) || trim($raw) === '') {
    return [];
  }

  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : [];
}

/**
 * @return list<string>
 */
function collectPhpFiles(string $sourceRoot): array
{
  $files = [];
  $iterator = new \RecursiveIteratorIterator(
    new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS)
  );

  /** @var \SplFileInfo $entry */
  foreach ($iterator as $entry) {
    if (!$entry->isFile()) {
      continue;
    }
    $path = $entry->getPathname();
    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'php') {
      continue;
    }
    $files[] = $path;
  }

  sort($files, SORT_STRING);
  return $files;
}

function extractNamespace(string $content): string
{
  if (preg_match('/^\s*namespace\s+([^;]+);/m', $content, $m) === 1) {
    return trim($m[1]);
  }
  return '';
}

/**
 * @return list<string>
 */
function extractClassNames(string $content): array
{
  $tokens = token_get_all($content);
  $names = [];
  $tokenCount = count($tokens);

  for ($i = 0; $i < $tokenCount; $i++) {
    $token = $tokens[$i];
    if (!is_array($token)) {
      continue;
    }

    $tokenId = $token[0];
    $isDeclarationToken = $tokenId === T_CLASS || $tokenId === T_INTERFACE || $tokenId === T_TRAIT || (defined('T_ENUM') && $tokenId === T_ENUM);
    if (!$isDeclarationToken) {
      continue;
    }

    // Ignore anonymous classes: `new class (...) { ... }`.
    if ($tokenId === T_CLASS) {
      $prev = previousMeaningfulToken($tokens, $i);
      if (is_array($prev) && $prev[0] === T_NEW) {
        continue;
      }
    }

    $nameToken = nextStringToken($tokens, $i + 1);
    if (!is_array($nameToken) || $nameToken[0] !== T_STRING) {
      continue;
    }

    $name = trim((string) $nameToken[1]);
    if ($name !== '') {
      $names[] = $name;
    }
  }

  return array_values(array_unique($names));
}

/**
 * @param list<int|string|array{0:int,1:string,2:int}> $tokens
 * @return int|string|array{0:int,1:string,2:int}|null
 */
function previousMeaningfulToken(array $tokens, int $start): int|string|array|null
{
  for ($i = $start - 1; $i >= 0; $i--) {
    $token = $tokens[$i];
    if (is_string($token)) {
      if (trim($token) === '') {
        continue;
      }
      return $token;
    }

    if (!is_array($token)) {
      continue;
    }

    if ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
      continue;
    }

    return $token;
  }

  return null;
}

/**
 * @param list<int|string|array{0:int,1:string,2:int}> $tokens
 * @return array{0:int,1:string,2:int}|null
 */
function nextStringToken(array $tokens, int $start): ?array
{
  $tokenCount = count($tokens);
  for ($i = $start; $i < $tokenCount; $i++) {
    $token = $tokens[$i];
    if (is_string($token)) {
      if (trim($token) === '') {
        continue;
      }
      return null;
    }

    if (!is_array($token)) {
      continue;
    }

    if ($token[0] === T_WHITESPACE || $token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
      continue;
    }

    if ($token[0] === T_STRING) {
      return $token;
    }

    return null;
  }

  return null;
}

/**
 * @return array<string, string>
 */
function extractUseMap(string $content): array
{
  $map = [];
  if (preg_match_all('/^\s*use\s+([^;]+);/m', $content, $m) !== 1 && !isset($m[1])) {
    return $map;
  }

  foreach (($m[1] ?? []) as $rawUse) {
    if (!is_string($rawUse)) {
      continue;
    }

    $normalized = trim($rawUse);
    if ($normalized === '' || stripos($normalized, 'function ') === 0 || stripos($normalized, 'const ') === 0) {
      continue;
    }

    $parts = preg_split('/\s+as\s+/i', $normalized);
    if (!is_array($parts) || $parts === []) {
      continue;
    }

    $fqcn = ltrim(trim((string) $parts[0]), '\\');
    if ($fqcn === '') {
      continue;
    }

    $alias = '';
    if (count($parts) > 1) {
      $alias = trim((string) $parts[1]);
    }
    if ($alias === '') {
      $segments = explode('\\', $fqcn);
      $alias = (string) end($segments);
    }

    if ($alias !== '') {
      $map[$alias] = $fqcn;
    }
  }

  return $map;
}

/**
 * @return list<string>
 */
function extractClassDependencies(string $content): array
{
  $symbols = [];
  if (preg_match_all('/\bnew\s+([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)\b/', $content, $newMatches) === 1 || isset($newMatches[1])) {
    foreach (($newMatches[1] ?? []) as $name) {
      if (is_string($name) && $name !== '') {
        $symbols[] = $name;
      }
    }
  }

  if (preg_match_all('/\b([A-Za-z_\\\\][A-Za-z0-9_\\\\]*)::/', $content, $staticMatches) === 1 || isset($staticMatches[1])) {
    foreach (($staticMatches[1] ?? []) as $name) {
      if (is_string($name) && $name !== '') {
        $symbols[] = $name;
      }
    }
  }

  if (preg_match_all('/\b(?:extends|implements)\s+([A-Za-z_\\\\][A-Za-z0-9_\\\\,\s]*)/i', $content, $inheritMatches) === 1 || isset($inheritMatches[1])) {
    foreach (($inheritMatches[1] ?? []) as $group) {
      if (!is_string($group) || $group === '') {
        continue;
      }
      foreach (preg_split('/\s*,\s*/', trim($group)) as $name) {
        if (is_string($name) && $name !== '') {
          $symbols[] = $name;
        }
      }
    }
  }

  return array_values(array_unique($symbols));
}

/**
 * @param array<string, string> $useMap
 * @param array<string, array{id:string,type:string,file?:string}> $classById
 */
function resolveDependencySymbol(string $symbol, string $namespace, array $useMap, array $classById): ?string
{
  $raw = trim($symbol);
  if ($raw === '' || in_array(strtolower($raw), ['self', 'static', 'parent'], true)) {
    return null;
  }

  $candidates = [];
  $trimmed = ltrim($raw, '\\');
  if ($trimmed !== '') {
    $candidates[] = $trimmed;

    $head = strtok($trimmed, '\\');
    if (is_string($head) && isset($useMap[$head])) {
      $tail = substr($trimmed, strlen($head));
      $candidates[] = $useMap[$head] . $tail;
    }

    if (!str_contains($trimmed, '\\') && isset($useMap[$trimmed])) {
      $candidates[] = $useMap[$trimmed];
    }

    if ($namespace !== '') {
      $candidates[] = $namespace . '\\' . $trimmed;
    }
  }

  foreach ($candidates as $candidate) {
    $id = ltrim((string) $candidate, '\\');
    if ($id !== '' && isset($classById[$id])) {
      return $id;
    }
  }

  return null;
}

function relativePath(string $absolutePath): string
{
  $home = rtrim(Environment::appHome(), '/');
  if (str_starts_with($absolutePath, $home . '/')) {
    return substr($absolutePath, strlen($home) + 1);
  }
  return $absolutePath;
}

/**
 * @param array<string, mixed> $meta
 */
function failRequest(string $message, string $errorCode, array $meta, int $status): never
{
  Response::error($message, array_merge($meta, ['error_code' => $errorCode]), $status);
  throw new \RuntimeException($message);
}
