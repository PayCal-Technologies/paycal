#!/usr/bin/env php
<?php declare(strict_types=1);

/**
 * capture-ast-metrics.php
 *
 * Purpose:
 *   Capture AST graph metrics at commit time, compute deltas versus the prior
 *   capture, and persist a local history for architecture trend tracking.
 *
 * Usage:
 *   php scripts/ast/capture-ast-metrics.php [--source=<label>]
 *
 * Notes:
 *   - This script is non-blocking by design; if graph data is missing it exits 0.
 *   - Metrics are written under tmp/ast (local runtime artifacts, not committed).
 */

namespace PayCal\Scripts\Ast;

use PayCal\Observability\GraphAnalyzer;

require_once __DIR__ . '/../../html/config.php';

const FAN_OUT_THRESHOLD = 10;
const FAN_IN_THRESHOLD = 8;
const INSTABILITY_THRESHOLD = 0.7;

main($argv);

/**
 * @param list<string> $argv
 */
function main(array $argv): void
{
  $source = readSourceArg($argv);

  $repoRoot = realpath(__DIR__ . '/../../');
  if (!is_string($repoRoot) || $repoRoot === '') {
    fwrite(STDERR, "[ast-metrics] Unable to resolve repository root.\n");
    return;
  }

  $astDir = $repoRoot . '/tmp/ast';
  $graphPath = $astDir . '/dependency-graph.json';
  $latestPath = $astDir . '/metrics-latest.json';
  $historyPath = $astDir . '/metrics-history.jsonl';

  if (!is_file($graphPath)) {
    fwrite(STDOUT, "[ast-metrics] Graph not found at tmp/ast/dependency-graph.json; skipping capture.\n");
    return;
  }

  $graphRaw = file_get_contents($graphPath);
  if (!is_string($graphRaw) || $graphRaw === '') {
    fwrite(STDOUT, "[ast-metrics] Graph file is empty/unreadable; skipping capture.\n");
    return;
  }

  $decoded = json_decode($graphRaw, true);
  if (!is_array($decoded)) {
    fwrite(STDOUT, "[ast-metrics] Graph JSON is invalid; skipping capture.\n");
    return;
  }

  $graph = normalizeGraph($decoded);
  $analyzer = new GraphAnalyzer($graph);

  $baseMetrics = $analyzer->metrics();
  $risks = computeStructuralRisks($graph);

  $previous = loadPreviousCapture($latestPath);

  $capture = [
    'captured_at' => gmdate('c'),
    'source' => $source,
    'git' => [
      'branch' => trim((string) shell_exec('git rev-parse --abbrev-ref HEAD 2>/dev/null')),
      'head' => trim((string) shell_exec('git rev-parse HEAD 2>/dev/null')),
    ],
    'metrics' => $baseMetrics,
    'risk' => $risks,
    'delta' => computeDelta($previous, $baseMetrics, $risks),
  ];

  if (!is_dir($astDir) && !mkdir($astDir, 0775, true) && !is_dir($astDir)) {
    fwrite(STDERR, "[ast-metrics] Failed to create tmp/ast directory.\n");
    return;
  }

  file_put_contents($latestPath, json_encode($capture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
  file_put_contents($historyPath, json_encode($capture, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND);

  fwrite(
    STDOUT,
    sprintf(
      "[ast-metrics] captured: nodes=%d edges=%d cycles=%d layer_violations=%d (delta_edges=%+d)\n",
      (int) ($baseMetrics['nodes'] ?? 0),
      (int) ($baseMetrics['edges'] ?? 0),
      (int) ($baseMetrics['cycle_groups'] ?? 0),
      (int) ($risks['layer_violations'] ?? 0),
      (int) ($capture['delta']['edges'] ?? 0)
    )
  );
}

/**
 * @param list<string> $argv
 */
function readSourceArg(array $argv): string
{
  foreach ($argv as $arg) {
    if (str_starts_with($arg, '--source=')) {
      $value = trim(substr($arg, 9));
      return $value !== '' ? $value : 'manual';
    }
  }

  return 'manual';
}

/**
 * @param array<mixed> $payload
 * @return array{nodes:list<array{id:string,type:string,file?:string}>,edges:list<array{from:string,to:string,type:string}>}
 */
function normalizeGraph(array $payload): array
{
  $nodes = [];
  $edges = [];

  $rawNodes = $payload['nodes'] ?? [];
  if (is_array($rawNodes)) {
    foreach ($rawNodes as $node) {
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

  $rawEdges = $payload['edges'] ?? [];
  if (is_array($rawEdges)) {
    foreach ($rawEdges as $edge) {
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
 * @param array{nodes:list<array{id:string,type:string,file?:string}>,edges:list<array{from:string,to:string,type:string}>} $graph
 * @return array{fan_out_hotspots:int,fan_in_hotspots:int,instability_hotspots:int,layer_violations:int}
 */
function computeStructuralRisks(array $graph): array
{
  /** @var array<string, int> $inDegree */
  $inDegree = [];
  /** @var array<string, int> $outDegree */
  $outDegree = [];

  foreach ($graph['nodes'] as $node) {
    $id = $node['id'];
    $inDegree[$id] = 0;
    $outDegree[$id] = 0;
  }

  $layerViolations = 0;
  foreach ($graph['edges'] as $edge) {
    $from = $edge['from'];
    $to = $edge['to'];
    $outDegree[$from] = ($outDegree[$from] ?? 0) + 1;
    $inDegree[$to] = ($inDegree[$to] ?? 0) + 1;

    if (str_contains(mb_strtolower($from), 'controller') && str_contains(mb_strtolower($to), 'controller')) {
      $layerViolations++;
    }
  }

  $fanOutHotspots = 0;
  $fanInHotspots = 0;
  $instabilityHotspots = 0;

  $allIds = array_keys($inDegree + $outDegree);
  foreach ($allIds as $id) {
    $incoming = $inDegree[$id] ?? 0;
    $outgoing = $outDegree[$id] ?? 0;

    if ($outgoing > FAN_OUT_THRESHOLD) {
      $fanOutHotspots++;
    }
    if ($incoming > FAN_IN_THRESHOLD) {
      $fanInHotspots++;
    }

    $total = $incoming + $outgoing;
    if ($total > 0) {
      $instability = $outgoing / $total;
      if ($instability > INSTABILITY_THRESHOLD) {
        $instabilityHotspots++;
      }
    }
  }

  return [
    'fan_out_hotspots' => $fanOutHotspots,
    'fan_in_hotspots' => $fanInHotspots,
    'instability_hotspots' => $instabilityHotspots,
    'layer_violations' => $layerViolations,
  ];
}

/**
 * @return array<string, mixed>|null
 */
function loadPreviousCapture(string $latestPath): ?array
{
  if (!is_file($latestPath)) {
    return null;
  }

  $raw = file_get_contents($latestPath);
  if (!is_string($raw) || trim($raw) === '') {
    return null;
  }

  $decoded = json_decode($raw, true);
  return is_array($decoded) ? $decoded : null;
}

/**
 * @param array<string, mixed>|null $previous
 * @param array<string, mixed> $metrics
 * @param array<string, int> $risks
 * @return array<string, int>
 */
function computeDelta(?array $previous, array $metrics, array $risks): array
{
  $prevMetrics = is_array($previous['metrics'] ?? null) ? $previous['metrics'] : [];
  $prevRisks = is_array($previous['risk'] ?? null) ? $previous['risk'] : [];

  return [
    'nodes' => ((int) ($metrics['nodes'] ?? 0)) - ((int) ($prevMetrics['nodes'] ?? 0)),
    'edges' => ((int) ($metrics['edges'] ?? 0)) - ((int) ($prevMetrics['edges'] ?? 0)),
    'cycle_groups' => ((int) ($metrics['cycle_groups'] ?? 0)) - ((int) ($prevMetrics['cycle_groups'] ?? 0)),
    'dead_nodes' => ((int) ($metrics['dead_nodes'] ?? 0)) - ((int) ($prevMetrics['dead_nodes'] ?? 0)),
    'layer_violations' => ((int) ($risks['layer_violations'] ?? 0)) - ((int) ($prevRisks['layer_violations'] ?? 0)),
    'fan_out_hotspots' => ((int) ($risks['fan_out_hotspots'] ?? 0)) - ((int) ($prevRisks['fan_out_hotspots'] ?? 0)),
    'fan_in_hotspots' => ((int) ($risks['fan_in_hotspots'] ?? 0)) - ((int) ($prevRisks['fan_in_hotspots'] ?? 0)),
    'instability_hotspots' => ((int) ($risks['instability_hotspots'] ?? 0)) - ((int) ($prevRisks['instability_hotspots'] ?? 0)),
  ];
}
