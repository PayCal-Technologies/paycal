<?php declare(strict_types=1);

namespace PayCal\Observability;

/**
 * GraphAnalyzer.php
 *
 * Purpose: Structural analysis helpers for dependency graphs produced by the internal observability tooling.
 *
 * PHP version 8.4.16
 *
 * LICENSE: Part of PayCal.app, licensed under a proprietary license.
 * Unauthorized copying, modification, distribution or use is prohibited.
 *
 * @category   Observability
 * @package    PayCal\Observability
 * @author     Chris Simmons <cshaiku@gmail.com>
 * @copyright  2026 PayCal Technologies Inc.
 * @license    Proprietary License - See LICENSE.txt for full terms
 */

/**
 * Class GraphAnalyzer
 *
 * Provides structural analysis helpers for dependency graphs produced by the
 * internal observability tooling.
 */

final class GraphAnalyzer
{
  /** @var array<string, array{id:string,type:string,file?:string}> */
  private array $nodesById = [];

  /** @var list<array{from:string,to:string,type:string}> */
  private array $edges = [];

  /** @var array<string, list<array{from:string,to:string,type:string}>> */
  private array $out = [];

  /** @var array<string, list<array{from:string,to:string,type:string}>> */
  private array $in = [];

  /** @var array<string, int> */
  private array $inDegree = [];

  /** @var array<string, int> */
  private array $outDegree = [];

  /**
    * Hydrate the analyzer with a graph payload.
    *
   * @param array{nodes?:list<array{id:string,type:string,file?:string}>,edges?:list<array{from:string,to:string,type:string}>} $graph
   */
  public function __construct(array $graph)
  {
    foreach (($graph['nodes'] ?? []) as $node) {
      $id = (string) $node['id'];
      $this->nodesById[$id] = $node;
      $this->inDegree[$id] = 0;
      $this->outDegree[$id] = 0;
    }

    foreach (($graph['edges'] ?? []) as $edge) {
      $from = (string) $edge['from'];
      $to = (string) $edge['to'];
      $type = (string) $edge['type'];

      // Keep graph resilient when edges reference external nodes.
      if (!isset($this->nodesById[$from])) {
        $this->nodesById[$from] = ['id' => $from, 'type' => 'unknown'];
        $this->inDegree[$from] = 0;
        $this->outDegree[$from] = 0;
      }
      if (!isset($this->nodesById[$to])) {
        $this->nodesById[$to] = ['id' => $to, 'type' => 'unknown'];
        $this->inDegree[$to] = 0;
        $this->outDegree[$to] = 0;
      }

      $normalized = ['from' => $from, 'to' => $to, 'type' => $type];
      $this->edges[] = $normalized;
      $this->out[$from][] = $normalized;
      $this->in[$to][] = $normalized;
      $this->outDegree[$from]++;
      $this->inDegree[$to]++;
    }
  }

  /**
    * Summarize headline graph metrics.
    *
   * @return array{nodes:int,edges:int,node_types:array<string,int>,edge_types:array<string,int>,largest_hub:array{id:string,degree:int},average_outgoing_per_class:float,cycle_groups:int,dead_nodes:int}
   */
  public function metrics(): array
  {
    $nodeTypes = [];
    foreach ($this->nodesById as $node) {
      $type = (string) $node['type'];
      $nodeTypes[$type] = ($nodeTypes[$type] ?? 0) + 1;
    }

    $edgeTypes = [];
    foreach ($this->edges as $edge) {
      $type = (string) $edge['type'];
      $edgeTypes[$type] = ($edgeTypes[$type] ?? 0) + 1;
    }

    $largestHubId = '';
    $largestHubDegree = -1;
    foreach ($this->nodesById as $id => $_node) {
      $degree = ($this->inDegree[$id] ?? 0) + ($this->outDegree[$id] ?? 0);
      if ($degree > $largestHubDegree) {
        $largestHubDegree = $degree;
        $largestHubId = $id;
      }
    }

    $classTotal = 0;
    $classCount = 0;
    foreach ($this->nodesById as $id => $node) {
      if ($node['type'] !== 'class') {
        continue;
      }
      $classCount++;
      $classTotal += $this->outDegree[$id] ?? 0;
    }

    $deadNodes = 0;
    foreach ($this->nodesById as $id => $_node) {
      if (($this->inDegree[$id] ?? 0) === 0) {
        $deadNodes++;
      }
    }

    return [
      'nodes' => count($this->nodesById),
      'edges' => count($this->edges),
      'node_types' => $nodeTypes,
      'edge_types' => $edgeTypes,
      'largest_hub' => ['id' => $largestHubId, 'degree' => max(0, $largestHubDegree)],
      'average_outgoing_per_class' => $classCount > 0 ? round($classTotal / $classCount, 2) : 0.0,
      'cycle_groups' => count($this->findCycleGroups()),
      'dead_nodes' => $deadNodes,
    ];
  }

  /**
    * Rank files by their total dependency activity.
    *
    * @return list<array{file:string,score:int}>
   */
  public function heatmapByFile(int $limit = 20): array
  {
    /** @var array<string, int> $scoreByFile */
    $scoreByFile = [];

    foreach ($this->nodesById as $id => $node) {
      $file = (string) ($node['file'] ?? 'unknown');
      $scoreByFile[$file] = ($scoreByFile[$file] ?? 0) + ($this->inDegree[$id] ?? 0) + ($this->outDegree[$id] ?? 0);
    }

    arsort($scoreByFile);
    $rows = [];
    foreach (array_slice($scoreByFile, 0, max(1, $limit), true) as $file => $score) {
      $rows[] = ['file' => $file, 'score' => $score];
    }

    return $rows;
  }

  /**
    * Return graph nodes with no inbound edges.
    *
   * @return list<array{id:string,type:string,file?:string,incoming:int,outgoing:int}>
   */
  public function deadNodes(int $limit = 200, ?string $type = null): array
  {
    $rows = [];
    foreach ($this->nodesById as $id => $node) {
      if ($type !== null && $node['type'] !== $type) {
        continue;
      }
      $incoming = $this->inDegree[$id] ?? 0;
      if ($incoming !== 0) {
        continue;
      }
      $row = [
        'id' => $id,
        'type' => (string) $node['type'],
        'incoming' => $incoming,
        'outgoing' => $this->outDegree[$id] ?? 0,
      ];
      if (isset($node['file'])) {
        $row['file'] = $node['file'];
      }
      $rows[] = $row;
    }

    usort($rows, static fn(array $a, array $b): int => ($b['outgoing'] <=> $a['outgoing']) ?: strcmp($a['id'], $b['id']));

    return array_slice($rows, 0, max(1, $limit));
  }

  /**
    * Search graph nodes by identifier.
    *
   * @return array{query:string,mode:string,total:int,items:list<array{id:string,type:string,file?:string,incoming:int,outgoing:int}>}
   */
  public function search(string $query, string $mode = 'contains', int $limit = 100): array
  {
    $q = mb_strtolower($query);
    $items = [];

    foreach ($this->nodesById as $id => $node) {
      $idLc = mb_strtolower($id);
      $match = match ($mode) {
        'exact' => $idLc === $q,
        'prefix' => str_starts_with($idLc, $q),
        default => str_contains($idLc, $q),
      };

      if (!$match) {
        continue;
      }

      $item = [
        'id' => $id,
        'type' => (string) $node['type'],
        'incoming' => $this->inDegree[$id] ?? 0,
        'outgoing' => $this->outDegree[$id] ?? 0,
      ];
      if (isset($node['file'])) {
        $item['file'] = $node['file'];
      }
      $items[] = $item;
    }

    usort($items, static function (array $a, array $b): int {
      $aDegree = (int) $a['incoming'] + (int) $a['outgoing'];
      $bDegree = (int) $b['incoming'] + (int) $b['outgoing'];
      return ($bDegree <=> $aDegree) ?: strcmp((string) $a['id'], (string) $b['id']);
    });

    return [
      'query' => $query,
      'mode' => $mode,
      'total' => count($items),
      'items' => array_slice($items, 0, max(1, $limit)),
    ];
  }

  /**
    * Extract a constrained subgraph around a root node.
    *
   * @param list<string> $edgeTypes
   * @return array{root:string,depth:int,direction:string,nodes:list<array{id:string,type:string,file?:string}>,edges:list<array{from:string,to:string,type:string}>}
   */
  public function subgraph(string $root, int $depth = 1, string $direction = 'both', array $edgeTypes = []): array
  {
    if (!isset($this->nodesById[$root])) {
      return ['root' => $root, 'depth' => $depth, 'direction' => $direction, 'nodes' => [], 'edges' => []];
    }

    $depth = max(1, min(6, $depth));
    $allowedEdge = array_flip($edgeTypes);

    /** @var array<string, int> $seen */
    $seen = [$root => 0];
    $queue = [[$root, 0]];

    while ($queue !== []) {
      [$cur, $d] = array_shift($queue);
      if ($d >= $depth) {
        continue;
      }

      $neighbors = [];
      if ($direction === 'out' || $direction === 'both') {
        foreach ($this->out[$cur] ?? [] as $edge) {
          if ($allowedEdge !== [] && !isset($allowedEdge[$edge['type']])) {
            continue;
          }
          $neighbors[] = $edge['to'];
        }
      }
      if ($direction === 'in' || $direction === 'both') {
        foreach ($this->in[$cur] ?? [] as $edge) {
          if ($allowedEdge !== [] && !isset($allowedEdge[$edge['type']])) {
            continue;
          }
          $neighbors[] = $edge['from'];
        }
      }

      foreach ($neighbors as $next) {
        if (isset($seen[$next])) {
          continue;
        }
        $seen[$next] = $d + 1;
        $queue[] = [$next, $d + 1];
      }
    }

    $nodeSet = array_fill_keys(array_keys($seen), true);
    $nodes = [];
    foreach (array_keys($nodeSet) as $id) {
      $node = $this->nodesById[$id];
      $nodeRow = ['id' => $id, 'type' => (string) $node['type']];
      if (isset($node['file'])) {
        $nodeRow['file'] = $node['file'];
      }
      $nodes[] = $nodeRow;
    }

    $edges = [];
    foreach ($this->edges as $edge) {
      if (!isset($nodeSet[$edge['from']]) || !isset($nodeSet[$edge['to']])) {
        continue;
      }
      if ($allowedEdge !== [] && !isset($allowedEdge[$edge['type']])) {
        continue;
      }
      $edges[] = $edge;
    }

    return [
      'root' => $root,
      'depth' => $depth,
      'direction' => $direction,
      'nodes' => $nodes,
      'edges' => $edges,
    ];
  }

  /**
    * Find a path between two nodes using breadth-first traversal.
    *
   * @return array{from:string,to:string,max_depth:int,path:list<string>}
   */
  public function trace(string $from, string $to, int $maxDepth = 8): array
  {
    if (!isset($this->nodesById[$from]) || !isset($this->nodesById[$to])) {
      return ['from' => $from, 'to' => $to, 'max_depth' => $maxDepth, 'path' => []];
    }

    $maxDepth = max(1, min(20, $maxDepth));
    $queue = [[$from, [$from]]];
    $seen = [$from => true];

    while ($queue !== []) {
      [$cur, $path] = array_shift($queue);
      if (count($path) > $maxDepth + 1) {
        continue;
      }
      if ($cur === $to) {
        return ['from' => $from, 'to' => $to, 'max_depth' => $maxDepth, 'path' => $path];
      }

      foreach ($this->out[$cur] ?? [] as $edge) {
        $next = $edge['to'];
        if (isset($seen[$next])) {
          continue;
        }
        $seen[$next] = true;
        $nextPath = $path;
        $nextPath[] = $next;
        $queue[] = [$next, $nextPath];
      }
    }

    return ['from' => $from, 'to' => $to, 'max_depth' => $maxDepth, 'path' => []];
  }

  /**
    * Calculate downstream or upstream reachability for a node.
    *
   * @return array{node:string,direction:string,depth:int,total:int,nodes:list<string>}
   */
  public function blastRadius(string $nodeId, string $direction = 'out', int $depth = 6): array
  {
    if (!isset($this->nodesById[$nodeId])) {
      return ['node' => $nodeId, 'direction' => $direction, 'depth' => $depth, 'total' => 0, 'nodes' => []];
    }

    $depth = max(1, min(20, $depth));
    $seen = [$nodeId => 0];
    $queue = [[$nodeId, 0]];

    while ($queue !== []) {
      [$cur, $d] = array_shift($queue);
      if ($d >= $depth) {
        continue;
      }

      $neighbors = [];
      if ($direction === 'out' || $direction === 'both') {
        foreach ($this->out[$cur] ?? [] as $edge) {
          $neighbors[] = $edge['to'];
        }
      }
      if ($direction === 'in' || $direction === 'both') {
        foreach ($this->in[$cur] ?? [] as $edge) {
          $neighbors[] = $edge['from'];
        }
      }

      foreach ($neighbors as $next) {
        if (isset($seen[$next])) {
          continue;
        }
        $seen[$next] = $d + 1;
        $queue[] = [$next, $d + 1];
      }
    }

    $nodes = array_keys($seen);
    sort($nodes);

    return [
      'node' => $nodeId,
      'direction' => $direction,
      'depth' => $depth,
      'total' => max(0, count($nodes) - 1),
      'nodes' => array_values(array_filter($nodes, static fn(string $id): bool => $id !== $nodeId)),
    ];
  }

  /**
    * Report dependency edges that cross disallowed architecture layers.
    *
   * @return list<array{from:string,to:string,from_layer:string,to_layer:string,type:string}>
   */
  public function layerViolations(int $limit = 200): array
  {
    $allowed = [
      'controller' => ['service'],
      'service' => ['repository'],
      'repository' => ['db'],
      'db' => [],
    ];

    $rows = [];
    foreach ($this->edges as $edge) {
      $fromLayer = $this->inferLayer($edge['from']);
      $toLayer = $this->inferLayer($edge['to']);

      if ($fromLayer === 'unknown' || $toLayer === 'unknown') {
        continue;
      }

      $allowedTargets = $allowed[$fromLayer] ?? [];
      if (in_array($toLayer, $allowedTargets, true)) {
        continue;
      }

      $rows[] = [
        'from' => $edge['from'],
        'to' => $edge['to'],
        'from_layer' => $fromLayer,
        'to_layer' => $toLayer,
        'type' => $edge['type'],
      ];
    }

    return array_slice($rows, 0, max(1, $limit));
  }

  /**
    * Find strongly connected components in the graph.
    *
   * @return list<list<string>>
   */
  private function findCycleGroups(): array
  {
    $index = 0;
    $stack = [];
    $onStack = [];
    $indices = [];
    $low = [];
    $scc = [];

    $visit = function (string $v) use (&$visit, &$index, &$stack, &$onStack, &$indices, &$low, &$scc): void {
      $indices[$v] = $index;
      $low[$v] = $index;
      $index++;
      $stack[] = $v;
      $onStack[$v] = true;

      foreach ($this->out[$v] ?? [] as $edge) {
        $w = $edge['to'];
        if (!array_key_exists($w, $indices)) {
          $visit($w);
          $low[$v] = min($low[$v], $low[$w]);
        } elseif (($onStack[$w] ?? false) === true) {
          $low[$v] = min($low[$v], $indices[$w]);
        }
      }

      if ($low[$v] === $indices[$v]) {
        $component = [];
        do {
          $w = array_pop($stack);
          if (!is_string($w)) {
            break;
          }
          $onStack[$w] = false;
          $component[] = $w;
        } while ($w !== $v);

        if (count($component) > 1) {
          $scc[] = $component;
        }
      }
    };

    foreach (array_keys($this->nodesById) as $v) {
      if (!array_key_exists($v, $indices)) {
        $visit($v);
      }
    }

    return $scc;
  }

  /**
   * Infer a coarse architectural layer from a node identifier.
   *
   * @param string $nodeId Node identifier
   *
   * @return string Inferred architectural layer name
   */
  private function inferLayer(string $nodeId): string
  {
    $n = strtolower($nodeId);

    if (str_contains($n, 'controller')) {
      return 'controller';
    }
    if (str_contains($n, 'service')) {
      return 'service';
    }
    if (str_contains($n, 'repository')) {
      return 'repository';
    }
    if (str_contains($n, 'database') || str_contains($n, 'redis') || str_contains($n, 'pdo')) {
      return 'db';
    }

    return 'unknown';
  }
}
