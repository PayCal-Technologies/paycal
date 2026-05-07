<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';

Authentication::abortIfUnauthenticated();
Authentication::isAdminOrDie();
CORS::handleORIGIN();
CORS::renderContentType('application/javascript');
Javascript::renderDocBlock();

?>

const canvas = document.getElementById('ast_graph_canvas');
const stats = document.getElementById('ast_stats');
const info = document.getElementById('ast_node_info');
const graphStatus = document.getElementById('ast_graph_status');
const i18nNode = document.getElementById('ast_i18n');
const input = document.getElementById('ast_search');
const datalist = document.getElementById('ast_search_list');
const btnGenerate = document.getElementById('ast_generate_btn');
const btnFocus = document.getElementById('ast_focus_btn');
const btnReset = document.getElementById('ast_reset_btn');
const btnView2d = document.getElementById('ast_view_2d_btn');
const btnView3d = document.getElementById('ast_view_3d_btn');
const focusModeToggle = document.getElementById('ast_focus_mode');
const focusHopsSelect = document.getElementById('ast_focus_hops');
const violationsOnlyToggle = document.getElementById('ast_violations_only');
const cycleClearButton = document.getElementById('ast_cycle_clear_btn');
const nodeOverlay = document.getElementById('ast_node_overlay');
const nodeOverlayHead = document.getElementById('ast_node_overlay_head');
const filterCheckboxes = Array.from(document.querySelectorAll('.ast_filter_checkbox'));
const metricsNodes = document.getElementById('ast_metric_nodes');
const metricsEdges = document.getElementById('ast_metric_edges');
const metricsCycles = document.getElementById('ast_metric_cycles');
const metricsDeadNodes = document.getElementById('ast_metric_dead_nodes');
const metricsLayerViolations = document.getElementById('ast_metric_layer_violations');
const metricsFanOut = document.getElementById('ast_metric_fan_out_hotspots');
const metricsFanIn = document.getElementById('ast_metric_fan_in_hotspots');
const metricsInstability = document.getElementById('ast_metric_instability_hotspots');
const metricsDelta = document.getElementById('ast_metrics_delta');

let localized = {};
const rawMessages = i18nNode?.getAttribute('data-messages') || '{}';
try {
  localized = JSON.parse(rawMessages);
} catch {
  localized = {};
}

const msg = (key, fallback) => {
  const value = localized[key];
  return typeof value === 'string' && value !== '' ? value : fallback;
};

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

function resolveApiErrorMessage(payload) {
  const code = String(payload?.error_code || payload?.data?.error_code || '').trim();
  if (code === '') {
    const fallbackMessage = String(payload?.message || '').trim();
    if (fallbackMessage !== '') {
      return fallbackMessage;
    }

    return msg('api_generic_error', 'AST request failed.');
  }

  const mapped = msg(`api_${code}`, '');
  if (mapped !== '') {
    return mapped;
  }

  const fallbackMessage = String(payload?.message || '').trim();
  return fallbackMessage !== '' ? fallbackMessage : msg('api_generic_error', 'AST request failed.');
}

if (!canvas || !stats || !info || !graphStatus || !input || !datalist || !btnGenerate || !btnFocus || !btnReset || !btnView2d || !btnView3d || !focusModeToggle || !focusHopsSelect || !violationsOnlyToggle || !cycleClearButton || !nodeOverlay || !nodeOverlayHead) {
  throw new Error(msg('ui_missing_elements', 'AST UI missing elements'));
}

const ctx = canvas.getContext('2d');
if (!ctx) throw new Error(msg('no_canvas_context', 'No canvas context'));

let graph = { nodes: [], edges: [] };
let idx = new Map();
let selected = null;
let deadNodeIds = new Set();
let layerViolationByNode = new Map();
let cycleGroupByNode = new Map();
let cycleMembersById = new Map();
let violationEdgeKeys = new Set();
let violationNodeIds = new Set();
let nodeDegreeById = new Map();
let transform = { x: 30, y: 30, scale: 1 };
let currentView = '2d';
let focusIsolationEnabled = true;
let focusHopDepth = 2;
let violationsOnlyEnabled = false;
let highlightedCycleId = null;
let focusSetCache = null;
let focusSetCacheKey = '';
let dragging = false;
let drag = { sx: 0, sy: 0, ox: 0, oy: 0 };
let lastViewToggleAt = 0;
const sphere = { rotX: 0.2, rotY: 0.2, zoom: 1, radius: 180, cameraDistance: 650 };
const VIEW_TOGGLE_COOLDOWN_MS = 1000;
const HIT_RADIUS_SCALE = 2;
const PICK_RADIUS_2D_PX = 14;
const PICK_RADIUS_3D_PADDING_PX = 12;
const PICK_FALLBACK_RADIUS_PX = 30;
const NODE_RADIUS_2D_PX = 7;
const NODE_RADIUS_2D_SELECTED_PX = 11;
const NODE_RADIUS_3D_SCALE = 2;
const AST_ENDPOINTS = [
  '/api/v1/admin/ast',
  '/admin/ast/data.php',
  '/admin/ast/data/',
];

async function astRequest(action, options = {}) {
  const queryParams = options?.queryParams && typeof options.queryParams === 'object' ? options.queryParams : null;
  const method = String(options.method || 'GET').toUpperCase();
  const init = {
    credentials: 'same-origin',
    ...options,
    method,
  };
  delete init.queryParams;

  for (const base of AST_ENDPOINTS) {
    const params = new URLSearchParams();
    params.set('action', action);
    if (queryParams !== null) {
      for (const [k, v] of Object.entries(queryParams)) {
        if (v === null || v === undefined) {
          continue;
        }
        params.set(k, String(v));
      }
    }
    const sep = base.includes('?') ? '&' : '?';
    const url = `${base}${sep}${params.toString()}`;
    try {
      const response = await fetch(url, init);
      if (response.status === 404) {
        const ct = response.headers.get('content-type') || '';
        if (ct.includes('application/json')) {
          let payload = null;
          try {
            payload = await response.json();
          } catch {
            payload = null;
          }
          throw new Error(resolveApiErrorMessage(payload));
        }
        continue;
      }

      if (!response.ok) {
        const ct = response.headers.get('content-type') || '';
        if (ct.includes('application/json')) {
          let payload = null;
          try {
            payload = await response.json();
          } catch {
            payload = null;
          }
          throw new Error(resolveApiErrorMessage(payload));
        }

        throw new Error(msg('api_generic_error', 'AST request failed.'));
      }

      return response;
    } catch (error) {
      if (error instanceof TypeError) {
        continue;
      }

      throw error;
    }
  }

  throw new Error(msg('endpoint_unavailable', 'AST endpoint is unavailable'));
}

function resize() {
  const container = canvas.parentElement;
  canvas.width = container?.clientWidth || window.innerWidth;
  canvas.height = container?.clientHeight || Math.floor(window.innerHeight - 140);
  render();
}

function centerGraph() {
  if (!graph.nodes.length) return;
  let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
  for (const n of graph.nodes) {
    if (n.x < minX) minX = n.x;
    if (n.y < minY) minY = n.y;
    if (n.x > maxX) maxX = n.x;
    if (n.y > maxY) maxY = n.y;
  }
  const gw = maxX - minX || 1, gh = maxY - minY || 1;
  const pad = 40;
  const sx = (canvas.width - pad * 2) / gw;
  const sy = (canvas.height - pad * 2) / gh;
  const s = Math.min(sx, sy, 3);
  transform.scale = s;
  transform.x = (canvas.width - gw * s) / 2 - minX * s;
  transform.y = (canvas.height - gh * s) / 2 - minY * s;
}

function initializeSphereLayout() {
  const count = graph.nodes.length;
  if (!count) {
    return;
  }

  for (let i = 0; i < count; i++) {
    // Fibonacci sphere distribution for even node placement.
    const t = i + 0.5;
    const phi = Math.acos(1 - 2 * t / count);
    const theta = Math.PI * (1 + Math.sqrt(5)) * t;
    const x3 = Math.sin(phi) * Math.cos(theta) * sphere.radius;
    const y3 = Math.cos(phi) * sphere.radius;
    const z3 = Math.sin(phi) * Math.sin(theta) * sphere.radius;
    graph.nodes[i].x3 = x3;
    graph.nodes[i].y3 = y3;
    graph.nodes[i].z3 = z3;
  }
}

function rotatePoint3D(x, y, z) {
  const cosY = Math.cos(sphere.rotY);
  const sinY = Math.sin(sphere.rotY);
  const x1 = x * cosY - z * sinY;
  const z1 = x * sinY + z * cosY;

  const cosX = Math.cos(sphere.rotX);
  const sinX = Math.sin(sphere.rotX);
  const y2 = y * cosX - z1 * sinX;
  const z2 = y * sinX + z1 * cosX;

  return { x: x1, y: y2, z: z2 };
}

function projectNode3D(node) {
  const rotated = rotatePoint3D(node.x3 || 0, node.y3 || 0, node.z3 || 0);
  const perspective = sphere.cameraDistance / (sphere.cameraDistance + rotated.z + sphere.radius);
  const scale = perspective * sphere.zoom;
  const x = canvas.width / 2 + rotated.x * scale;
  const y = canvas.height / 2 + rotated.y * scale;
  const radius = Math.max(5, Math.min(16, (4.2 * scale + 2.2) * NODE_RADIUS_3D_SCALE));

  return { x, y, radius, z: rotated.z };
}

function updateViewButtons() {
  const is2d = currentView === '2d';
  btnView2d.classList.toggle('is-active', is2d);
  btnView3d.classList.toggle('is-active', !is2d);
  btnView2d.setAttribute('aria-pressed', is2d ? 'true' : 'false');
  btnView3d.setAttribute('aria-pressed', is2d ? 'false' : 'true');
}

function setViewMode(mode) {
  if (mode !== '2d' && mode !== '3d') {
    return;
  }
  currentView = mode;
  updateViewButtons();
  graphStatus.textContent = mode === '3d'
    ? msg('view_switched_3d', 'Switched to 3D sphere view.')
    : msg('view_switched_2d', 'Switched to 2D map view.');
  render();
}

function trySetViewMode(mode) {
  const now = Date.now();
  if (now - lastViewToggleAt < VIEW_TOGGLE_COOLDOWN_MS) {
    return false;
  }

  if (mode !== '2d' && mode !== '3d') {
    return false;
  }

  if (mode === currentView) {
    return true;
  }

  lastViewToggleAt = now;
  setViewMode(mode);
  return true;
}

function setOverlayCollapsed(collapsed, announce = false) {
  const wasCollapsed = nodeOverlay.classList.contains('is-collapsed');
  if (collapsed && !wasCollapsed) {
    captureOverlayExpandedGeometry();
  }

  info.hidden = collapsed;
  nodeOverlay.classList.toggle('is-collapsed', collapsed);
  if (collapsed) {
    nodeOverlay.style.width = '';
    nodeOverlay.style.height = '';
    nodeOverlay.style.left = '';
    nodeOverlay.style.top = '';
    nodeOverlay.style.right = '.9rem';
    nodeOverlay.style.bottom = '.9rem';
  } else if (wasCollapsed) {
    if (overlayExpandedGeometry !== null) {
      nodeOverlay.style.right = 'auto';
      nodeOverlay.style.bottom = 'auto';
      nodeOverlay.style.left = `${overlayExpandedGeometry.left}px`;
      nodeOverlay.style.top = `${overlayExpandedGeometry.top}px`;
      nodeOverlay.style.width = `${overlayExpandedGeometry.width}px`;
      nodeOverlay.style.height = `${overlayExpandedGeometry.height}px`;
    } else {
      nodeOverlay.style.width = '';
      nodeOverlay.style.height = '';
      nodeOverlay.style.left = '';
      nodeOverlay.style.top = '';
      nodeOverlay.style.right = '.9rem';
      nodeOverlay.style.bottom = '.9rem';
    }
  }
  nodeOverlayHead.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
  nodeOverlayHead.setAttribute('data-state', collapsed ? 'closed' : 'open');
  nodeOverlayHead.setAttribute('title', collapsed
    ? msg('overlay_expand', 'Show details')
    : msg('overlay_collapse', 'Hide details'));

  if (announce) {
    graphStatus.textContent = collapsed
      ? msg('overlay_collapsed_status', 'Node details panel collapsed.')
      : msg('overlay_expanded_status', 'Node details panel expanded.');
  }

  if (!collapsed && wasCollapsed) {
    requestAnimationFrame(() => {
      clampOverlayToWrap();
    });
  }
}

let overlayPointerState = null;
let overlayExpandedGeometry = null;
const OVERLAY_RESIZE_HOTSPOT_PX = 22;

function captureOverlayExpandedGeometry() {
  const wrap = nodeOverlay.parentElement;
  if (!wrap) {
    return;
  }

  const wrapRect = wrap.getBoundingClientRect();
  const overlayRect = nodeOverlay.getBoundingClientRect();
  overlayExpandedGeometry = {
    left: overlayRect.left - wrapRect.left,
    top: overlayRect.top - wrapRect.top,
    width: overlayRect.width,
    height: overlayRect.height,
  };
}

function clampOverlayToWrap() {
  const wrap = nodeOverlay.parentElement;
  if (!wrap) {
    return;
  }

  const maxLeft = Math.max(0, wrap.clientWidth - nodeOverlay.offsetWidth);
  const maxTop = Math.max(0, wrap.clientHeight - nodeOverlay.offsetHeight);

  if (nodeOverlay.style.left === '' || nodeOverlay.style.top === '') {
    return;
  }

  const currentLeft = Number.parseFloat(nodeOverlay.style.left);
  const currentTop = Number.parseFloat(nodeOverlay.style.top);
  const nextLeft = Number.isFinite(currentLeft) ? Math.min(maxLeft, Math.max(0, currentLeft)) : 0;
  const nextTop = Number.isFinite(currentTop) ? Math.min(maxTop, Math.max(0, currentTop)) : 0;
  nodeOverlay.style.left = `${nextLeft}px`;
  nodeOverlay.style.top = `${nextTop}px`;
}

function beginOverlayPointer(event) {
  if (event.button !== 0) {
    return;
  }

  const overlayRect = nodeOverlay.getBoundingClientRect();
  const inResizeHotspot = event.clientX >= (overlayRect.right - OVERLAY_RESIZE_HOTSPOT_PX)
    && event.clientY >= (overlayRect.bottom - OVERLAY_RESIZE_HOTSPOT_PX);
  if (inResizeHotspot) {
    return;
  }

  const wrap = nodeOverlay.parentElement;
  if (!wrap) {
    return;
  }

  event.preventDefault();
  event.stopPropagation();

  const wrapRect = wrap.getBoundingClientRect();
  const startedOnHeader = event.target instanceof Element
    && event.target.closest('#ast_node_overlay_head') !== null;
  overlayPointerState = {
    pointerId: event.pointerId,
    startX: event.clientX,
    startY: event.clientY,
    startLeft: overlayRect.left - wrapRect.left,
    startTop: overlayRect.top - wrapRect.top,
    wrapRect,
    dragging: false,
    canDrag: true,
    canToggle: startedOnHeader,
  };

  nodeOverlay.style.right = 'auto';
  nodeOverlay.style.bottom = 'auto';
  nodeOverlay.style.left = `${overlayPointerState.startLeft}px`;
  nodeOverlay.style.top = `${overlayPointerState.startTop}px`;
  nodeOverlay.setPointerCapture(event.pointerId);
}

function updateOverlayPointer(event) {
  if (!overlayPointerState || event.pointerId !== overlayPointerState.pointerId) {
    return;
  }

  event.preventDefault();
  if (!overlayPointerState.canDrag) {
    return;
  }

  const deltaX = event.clientX - overlayPointerState.startX;
  const deltaY = event.clientY - overlayPointerState.startY;
  if (!overlayPointerState.dragging && Math.hypot(deltaX, deltaY) < 4) {
    return;
  }

  overlayPointerState.dragging = true;
  nodeOverlay.classList.add('is-dragging');

  const maxLeft = Math.max(0, overlayPointerState.wrapRect.width - nodeOverlay.offsetWidth);
  const maxTop = Math.max(0, overlayPointerState.wrapRect.height - nodeOverlay.offsetHeight);
  const nextLeft = Math.min(maxLeft, Math.max(0, overlayPointerState.startLeft + deltaX));
  const nextTop = Math.min(maxTop, Math.max(0, overlayPointerState.startTop + deltaY));
  nodeOverlay.style.left = `${nextLeft}px`;
  nodeOverlay.style.top = `${nextTop}px`;
}

function endOverlayPointer(event) {
  if (!overlayPointerState || event.pointerId !== overlayPointerState.pointerId) {
    return;
  }

  nodeOverlay.classList.remove('is-dragging');
  if (nodeOverlay.hasPointerCapture(event.pointerId)) {
    nodeOverlay.releasePointerCapture(event.pointerId);
  }

  const didDrag = overlayPointerState.dragging;
  const canToggle = overlayPointerState.canToggle;
  const wasCancelled = event.type === 'pointercancel';
  overlayPointerState = null;

  if (!didDrag && !wasCancelled && canToggle) {
    setOverlayCollapsed(!info.hidden, true);
  }
}

function setupOverlayPanelInteraction() {
  nodeOverlay.addEventListener('pointerdown', beginOverlayPointer);
  nodeOverlay.addEventListener('pointermove', updateOverlayPointer);
  nodeOverlay.addEventListener('pointerup', endOverlayPointer);
  nodeOverlay.addEventListener('pointercancel', endOverlayPointer);
  nodeOverlayHead.addEventListener('keydown', (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') {
      return;
    }

    event.preventDefault();
    setOverlayCollapsed(!info.hidden, true);
  });
}

function ws(x, y) {
  return { x: x * transform.scale + transform.x, y: y * transform.scale + transform.y };
}

function sw(x, y) {
  return { x: (x - transform.x) / transform.scale, y: (y - transform.y) / transform.scale };
}

function loadGraph() {
  canvas.setAttribute('aria-busy', 'true');
  graphStatus.textContent = msg('graph_loading_status', 'AST dependency graph is loading.');
  astRequest('graph')
    .then(r => {
      return r.json();
    })
    .then(data => {
      const graphData = data?.data || {};
      if (graphData.empty || (!graphData.nodes?.length && !graphData.edges?.length)) {
        graphStatus.textContent = msg('graph_empty_status', 'No graph data found. Click "Generate" to build the AST dependency graph.');
        stats.textContent = msg('graph_empty_stats', 'No graph yet');
        canvas.setAttribute('aria-busy', 'false');
        return;
      }
      const nodes = (graphData.nodes || []).slice(0, 1400);
      const set = new Set(nodes.map(n => n.id));
      const edges = (graphData.edges || []).filter(e => set.has(e.from) && set.has(e.to)).slice(0, 7000);

      graph.nodes = nodes.map((n, i) => ({ ...n, x: (i % 70) * 18 + Math.random() * 5, y: Math.floor(i / 70) * 18 + Math.random() * 5, vx: 0, vy: 0 }));
      graph.edges = edges;
      idx = new Map(graph.nodes.map(n => [n.id, n]));
      nodeDegreeById = new Map(graph.nodes.map(n => [n.id, 0]));
      for (const edge of graph.edges) {
        nodeDegreeById.set(edge.from, (nodeDegreeById.get(edge.from) || 0) + 1);
        nodeDegreeById.set(edge.to, (nodeDegreeById.get(edge.to) || 0) + 1);
      }
      computeCycleGroups();
      initializeSphereLayout();
      loadNodeDiagnostics();

      layout(140);
      centerGraph();
      stats.textContent = msg('graph_stats_counts', 'Nodes: {nodes} | Edges: {edges}')
        .replace('{nodes}', String(graph.nodes.length))
        .replace('{edges}', String(graph.edges.length));
      graphStatus.textContent = msg('graph_loaded_status', 'AST dependency graph loaded with {nodes} nodes and {edges} edges.')
        .replace('{nodes}', String(graph.nodes.length))
        .replace('{edges}', String(graph.edges.length));
      populateDatalist();
      render();
      canvas.setAttribute('aria-busy', 'false');
    })
    .catch(e => {
      stats.textContent = msg('graph_load_failed', 'Failed to load graph');
      info.textContent = e.message;
      graphStatus.textContent = msg('graph_load_failed_status', 'AST dependency graph failed to load.') + ' ' + e.message;
      canvas.setAttribute('aria-busy', 'false');
    });
}

function computeCycleGroups() {
  cycleGroupByNode = new Map();
  cycleMembersById = new Map();

  const adjacency = new Map();
  for (const n of graph.nodes) {
    adjacency.set(n.id, []);
  }

  for (const e of graph.edges) {
    if (!adjacency.has(e.from)) {
      adjacency.set(e.from, []);
    }
    adjacency.get(e.from)?.push(e.to);
  }

  let index = 0;
  let groupId = 0;
  const stack = [];
  const onStack = new Set();
  const indices = new Map();
  const low = new Map();

  const strongConnect = (nodeId) => {
    indices.set(nodeId, index);
    low.set(nodeId, index);
    index += 1;
    stack.push(nodeId);
    onStack.add(nodeId);

    const neighbors = adjacency.get(nodeId) || [];
    for (const next of neighbors) {
      if (!indices.has(next)) {
        strongConnect(next);
        low.set(nodeId, Math.min(low.get(nodeId) || 0, low.get(next) || 0));
      } else if (onStack.has(next)) {
        low.set(nodeId, Math.min(low.get(nodeId) || 0, indices.get(next) || 0));
      }
    }

    if ((low.get(nodeId) || 0) !== (indices.get(nodeId) || 0)) {
      return;
    }

    const component = [];
    while (stack.length > 0) {
      const popped = stack.pop();
      if (typeof popped !== 'string') {
        break;
      }
      onStack.delete(popped);
      component.push(popped);
      if (popped === nodeId) {
        break;
      }
    }

    if (component.length > 1) {
      groupId += 1;
      cycleMembersById.set(groupId, new Set(component));
      for (const member of component) {
        cycleGroupByNode.set(member, { id: groupId, size: component.length });
      }
    }
  };

  for (const n of graph.nodes) {
    if (!indices.has(n.id)) {
      strongConnect(n.id);
    }
  }
}

function loadNodeDiagnostics() {
  Promise.all([
    astRequest('dead', { queryParams: { limit: 2000 } }).then(r => r.json()).catch(() => null),
    astRequest('violations', { queryParams: { limit: 2000 } }).then(r => r.json()).catch(() => null),
  ]).then(([deadPayload, violationsPayload]) => {
    const deadRows = deadPayload?.data?.result;
    deadNodeIds = new Set(Array.isArray(deadRows) ? deadRows.map(r => r?.id).filter(v => typeof v === 'string') : []);

    const violations = new Map();
    const violationEdges = new Set();
    const violationNodes = new Set();
    const violationRows = violationsPayload?.data?.result;
    if (Array.isArray(violationRows)) {
      for (const row of violationRows) {
        const from = row?.from;
        const to = row?.to;
        const edgeType = typeof row?.type === 'string' ? row.type : 'depends';
        if (typeof from === 'string') {
          const current = violations.get(from) || { asSource: 0, asTarget: 0 };
          current.asSource += 1;
          violations.set(from, current);
          violationNodes.add(from);
        }
        if (typeof to === 'string') {
          const current = violations.get(to) || { asSource: 0, asTarget: 0 };
          current.asTarget += 1;
          violations.set(to, current);
          violationNodes.add(to);
        }
        if (typeof from === 'string' && typeof to === 'string') {
          violationEdges.add(`${from}|${to}|${edgeType}`);
        }
      }
    }
    layerViolationByNode = violations;
    violationEdgeKeys = violationEdges;
    violationNodeIds = violationNodes;

    if (selected && idx.has(selected.id)) {
      showNodeDetails(selected);
    }
    render();
  }).catch(() => {
    deadNodeIds = new Set();
    layerViolationByNode = new Map();
    violationEdgeKeys = new Set();
    violationNodeIds = new Set();
  });
}

function setMetricValue(node, value) {
  if (!node) {
    return;
  }
  node.textContent = String(value);
}

function renderCommitMetrics(payload) {
  const capture = payload?.commit_metrics || null;
  const liveMetrics = payload?.live_metrics || {};
  const metrics = capture?.metrics || liveMetrics || {};
  const risk = capture?.risk || {};

  setMetricValue(metricsNodes, metrics.nodes ?? '-');
  setMetricValue(metricsEdges, metrics.edges ?? '-');
  setMetricValue(metricsCycles, metrics.cycle_groups ?? '-');
  setMetricValue(metricsDeadNodes, metrics.dead_nodes ?? '-');
  setMetricValue(metricsLayerViolations, risk.layer_violations ?? '-');
  setMetricValue(metricsFanOut, risk.fan_out_hotspots ?? '-');
  setMetricValue(metricsFanIn, risk.fan_in_hotspots ?? '-');
  setMetricValue(metricsInstability, risk.instability_hotspots ?? '-');

  if (!metricsDelta) {
    return;
  }

  const delta = capture?.delta || null;
  if (!delta) {
    metricsDelta.textContent = msg('metrics_not_available', 'No commit capture found yet. Commit once to begin delta tracking.');
    return;
  }

  const rows = [
    `nodes: ${delta.nodes >= 0 ? '+' : ''}${delta.nodes}`,
    `edges: ${delta.edges >= 0 ? '+' : ''}${delta.edges}`,
    `cycle_groups: ${delta.cycle_groups >= 0 ? '+' : ''}${delta.cycle_groups}`,
    `dead_nodes: ${delta.dead_nodes >= 0 ? '+' : ''}${delta.dead_nodes}`,
    `layer_violations: ${delta.layer_violations >= 0 ? '+' : ''}${delta.layer_violations}`,
    `fan_out_hotspots: ${delta.fan_out_hotspots >= 0 ? '+' : ''}${delta.fan_out_hotspots}`,
    `fan_in_hotspots: ${delta.fan_in_hotspots >= 0 ? '+' : ''}${delta.fan_in_hotspots}`,
    `instability_hotspots: ${delta.instability_hotspots >= 0 ? '+' : ''}${delta.instability_hotspots}`,
  ];
  metricsDelta.textContent = rows.join('\n');
}

function loadCommitMetrics() {
  astRequest('metrics')
    .then(r => {
      return r.json();
    })
    .then(data => {
      renderCommitMetrics(data?.data || {});
    })
    .catch(() => {
      if (metricsDelta) {
        metricsDelta.textContent = msg('metrics_not_available', 'No commit capture found yet. Commit once to begin delta tracking.');
      }
    });
}

function generateGraph() {
  canvas.setAttribute('aria-busy', 'true');
  graphStatus.textContent = msg('graph_generating_status', 'Generating AST dependency graph from source files.');
  stats.textContent = msg('graph_generating_stats', 'Generating graph...');
  btnGenerate.disabled = true;

  astRequest('generate', {
    method: 'POST',
  })
    .then(r => {
      return r.json();
    })
    .then(() => {
      graphStatus.textContent = msg('graph_generated_status', 'AST dependency graph generated. Reloading graph.');
      loadGraph();
    })
    .catch(e => {
      stats.textContent = msg('graph_generation_failed', 'Graph generation failed');
      info.textContent = e.message;
      graphStatus.textContent = msg('graph_generation_failed_status', 'AST graph generation failed.') + ' ' + e.message;
    })
    .finally(() => {
      btnGenerate.disabled = false;
      canvas.setAttribute('aria-busy', 'false');
    });
}

function populateDatalist() {
  datalist.textContent = '';
  for (const n of graph.nodes) {
    if (!isNodeVisibleByFilter(n)) {
      continue;
    }
    const opt = document.createElement('option');
    opt.value = n.id;
    datalist.appendChild(opt);
  }
}

function filterDatalist(query) {
  const q = query.toLowerCase();
  datalist.textContent = '';
  const matches = graph.nodes.filter(n => isNodeVisibleByFilter(n) && n.id.toLowerCase().includes(q)).slice(0, 50);
  for (const n of matches) {
    const opt = document.createElement('option');
    opt.value = n.id;
    datalist.appendChild(opt);
  }
}

function showNodeDetails(n) {
  const inEdges = graph.edges.filter(e => e.to === n.id);
  const outEdges = graph.edges.filter(e => e.from === n.id);
  const nodeId = escapeHtml(n.id);
  const nodeType = escapeHtml(n.type);
  const nodeFile = typeof n.file === 'string' && n.file !== '' ? n.file : msg('details_not_available', 'n/a');
  const inCount = inEdges.length;
  const outCount = outEdges.length;
  const degree = inCount + outCount;
  const instability = degree > 0 ? (outCount / degree).toFixed(2) : '0.00';
  const structuralFlags = [];

  if (inCount === 0 && outCount === 0) {
    structuralFlags.push(msg('details_flag_isolated', 'isolated'));
  } else {
    if (inCount === 0) {
      structuralFlags.push(msg('details_flag_root', 'root'));
    }
    if (outCount === 0) {
      structuralFlags.push(msg('details_flag_leaf', 'leaf'));
    }
  }
  if (structuralFlags.length === 0) {
    structuralFlags.push(msg('details_flag_internal', 'internal'));
  }

  const deadFlag = deadNodeIds.has(n.id) ? msg('details_yes', 'yes') : msg('details_no', 'no');
  const violationStats = layerViolationByNode.get(n.id) || { asSource: 0, asTarget: 0 };
  const cycle = cycleGroupByNode.get(n.id) || null;
  const incomingGroups = groupedIds(inEdges.map(e => e.from));
  const outgoingGroups = groupedIds(outEdges.map(e => e.to));
  const issues = [];

  if ((violationStats.asSource + violationStats.asTarget) > 0) {
    issues.push(`${msg('details_attr_layer_violations', 'Layer Violations')}: ${msg('details_attr_layer_source', 'source')} ${violationStats.asSource}, ${msg('details_attr_layer_target', 'target')} ${violationStats.asTarget}`);
  }
  if (cycle) {
    issues.push(`${msg('details_attr_cycle', 'Cycle Group')}: ${msg('details_attr_cycle_id', 'id')} ${cycle.id} (${msg('details_attr_cycle_size', 'size')} ${cycle.size})`);
  }
  if (deadFlag === msg('details_yes', 'yes')) {
    issues.push(`${msg('details_attr_dead_node', 'Dead Node')}: ${deadFlag}`);
  }

  let html = `<strong>${nodeId}</strong> &mdash; ${msg('details_type', 'type')}: ${nodeType} | ${msg('details_incoming', 'incoming')}: ${inCount} | ${msg('details_outgoing', 'outgoing')}: ${outCount}`;
  html += `<section class="ast_detail_section"><h4>${msg('details_issues_heading', 'Issues')}</h4>`;
  if (issues.length === 0) {
    html += `<p class="ast_empty">${msg('details_no_issues', 'No issues detected.')}</p>`;
  } else {
    html += '<ul class="ast_issue_list">';
    for (const issue of issues) {
      html += `<li>${escapeHtml(issue)}</li>`;
    }
    html += '</ul>';
  }
  if (cycle) {
    const isCycleHighlighted = highlightedCycleId === cycle.id;
    html += `<div class="ast_detail_inline_actions"><button id="ast_cycle_toggle_btn" class="btn btn_secondary" type="button" data-cycle-id="${cycle.id}">${isCycleHighlighted ? msg('details_clear_cycle', 'Clear cycle') : msg('details_highlight_cycle', 'Highlight cycle')}</button></div>`;
  }
  html += '</section>';

  html += `<section class="ast_detail_section"><h4>${msg('details_metrics_heading', 'Metrics')}</h4><table class="ast_detail_attr_table">`;
  html += `<tr><th>${msg('details_attr_file', 'File')}</th><td>${escapeHtml(nodeFile)}</td></tr>`;
  html += `<tr><th>${msg('details_attr_degree', 'Degree')}</th><td>${degree}</td></tr>`;
  html += `<tr><th>${msg('details_attr_instability', 'Instability')}</th><td>${instability}</td></tr>`;
  html += `<tr><th>${msg('details_attr_cycle', 'Cycle Group')}</th><td>${cycle ? `${msg('details_attr_cycle_id', 'id')} ${cycle.id} (${msg('details_attr_cycle_size', 'size')} ${cycle.size})` : msg('details_none', 'None')}</td></tr>`;
  html += '</table></section>';

  html += `<section class="ast_detail_section"><h4>${msg('details_structure_heading', 'Structure')}</h4><table class="ast_detail_attr_table">`;
  html += `<tr><th>${msg('details_attr_structural', 'Structural')}</th><td>${escapeHtml(structuralFlags.join(', '))}</td></tr>`;
  html += `<tr><th>${msg('details_attr_dead_node', 'Dead Node')}</th><td>${deadFlag}</td></tr>`;
  html += `<tr><th>${msg('details_attr_layer_violations', 'Layer Violations')}</th><td>${msg('details_attr_layer_source', 'source')}: ${violationStats.asSource}, ${msg('details_attr_layer_target', 'target')}: ${violationStats.asTarget}</td></tr>`;
  html += '</table></section>';

  html += '<div class="ast_detail_grid">';

  html += `<div><strong>${msg('details_incoming_heading', 'Incoming')}</strong><table>`;
  html += `<tr><th>${msg('details_from', 'From')}</th></tr>`;
  if (inEdges.length === 0) {
    html += `<tr><td class="ast_empty">${msg('details_none', 'None')}</td></tr>`;
  }
  for (const [group, ids] of incomingGroups) {
    html += `<tr><td><strong>${escapeHtml(group)} (${ids.length})</strong></td></tr>`;
    for (const id of ids) {
      html += `<tr><td>${escapeHtml(shortLabel(id))}</td></tr>`;
    }
  }
  html += '</table></div>';

  html += `<div><strong>${msg('details_outgoing_heading', 'Outgoing')}</strong><table>`;
  html += `<tr><th>${msg('details_to', 'To')}</th></tr>`;
  if (outEdges.length === 0) {
    html += `<tr><td class="ast_empty">${msg('details_none', 'None')}</td></tr>`;
  }
  for (const [group, ids] of outgoingGroups) {
    html += `<tr><td><strong>${escapeHtml(group)} (${ids.length})</strong></td></tr>`;
    for (const id of ids) {
      html += `<tr><td>${escapeHtml(shortLabel(id))}</td></tr>`;
    }
  }
  html += '</table></div>';

  html += '</div>';
  window.Guardian.setHTML(info, html);
  const cycleToggleButton = document.getElementById('ast_cycle_toggle_btn');
  if (cycleToggleButton instanceof HTMLButtonElement) {
    cycleToggleButton.addEventListener('click', () => {
      if (!cycle) {
        return;
      }
      highlightedCycleId = highlightedCycleId === cycle.id ? null : cycle.id;
      cycleClearButton.hidden = highlightedCycleId === null;
      showNodeDetails(n);
      populateDatalist();
      render();
    });
  }
  cycleClearButton.hidden = highlightedCycleId === null;

  graphStatus.textContent = msg('details_selected_status', 'Selected {id}. {incoming} incoming, {outgoing} outgoing.')
    .replace('{id}', n.id)
    .replace('{incoming}', String(inCount))
    .replace('{outgoing}', String(outCount));
}

function layout(iterations) {
  for (let it = 0; it < iterations; it++) {
    for (let i = 0; i < graph.nodes.length; i++) {
      const a = graph.nodes[i];
      a.vx *= 0.88;
      a.vy *= 0.88;
      for (let j = i + 1; j < Math.min(graph.nodes.length, i + 40); j++) {
        const b = graph.nodes[j];
        const dx = a.x - b.x;
        const dy = a.y - b.y;
        const d2 = dx * dx + dy * dy + 0.01;
        const f = 45 / d2;
        a.vx += dx * f; a.vy += dy * f;
        b.vx -= dx * f; b.vy -= dy * f;
      }
    }

    for (const e of graph.edges) {
      const a = idx.get(e.from), b = idx.get(e.to);
      if (!a || !b) continue;
      const dx = b.x - a.x, dy = b.y - a.y;
      const d = Math.sqrt(dx * dx + dy * dy) + 0.001;
      const force = (d - 20) * 0.01;
      const fx = (dx / d) * force, fy = (dy / d) * force;
      a.vx += fx; a.vy += fy; b.vx -= fx; b.vy -= fy;
    }

    for (const n of graph.nodes) { n.x += n.vx; n.y += n.vy; }
  }
}

const TYPE_COLORS = {
  controller: '#1565c0',
  model:      '#2e7d32',
  service:    '#e65100',
  middleware: '#6a1b9a',
  other:      '#546e7a',
  selected:   '#d81b60',
  violation:  '#e53935',
  cycle:      '#fb8c00',
};

const FILTERABLE_TYPES = ['controller', 'model', 'service', 'middleware', 'other'];
const activeTypeFilters = new Set(FILTERABLE_TYPES);

function classifyNode(id) {
  const lo = id.toLowerCase();
  if (lo.includes('controller'))  return 'controller';
  if (lo.includes('model') || lo.includes('entity')) return 'model';
  if (lo.includes('service') || lo.includes('handler') || lo.includes('provider')) return 'service';
  if (lo.includes('middleware') || lo.includes('guard') || lo.includes('filter')) return 'middleware';
  return 'other';
}

function shortLabel(id) {
  const parts = id.split('\\');
  return parts[parts.length - 1] || id;
}

function namespaceGroup(id) {
  const parts = id.split('\\').filter(Boolean);
  if (parts.length >= 2) {
    return `${parts[0]}\\${parts[1]}`;
  }
  if (parts.length === 1) {
    return parts[0];
  }
  return msg('details_not_available', 'n/a');
}

function groupedIds(ids) {
  const groups = new Map();
  for (const id of ids) {
    const group = namespaceGroup(id);
    if (!groups.has(group)) {
      groups.set(group, []);
    }
    groups.get(group)?.push(id);
  }

  const entries = Array.from(groups.entries()).sort((a, b) => a[0].localeCompare(b[0]));
  for (const entry of entries) {
    entry[1].sort((a, b) => a.localeCompare(b));
  }

  return entries;
}

function degreeRadiusBonus(nodeId) {
  const degree = nodeDegreeById.get(nodeId) || 0;
  return Math.min(4.2, Math.sqrt(Math.max(0, degree)) * 0.35);
}

function isEdgeConnectedToSelected(edge) {
  if (!selected) {
    return false;
  }

  return edge.from === selected.id || edge.to === selected.id;
}

function edgeKey(edge) {
  return `${edge.from}|${edge.to}|${edge.type}`;
}

function isViolationEdge(edge) {
  return violationEdgeKeys.has(edgeKey(edge));
}

function isEdgeInHighlightedCycle(edge) {
  if (highlightedCycleId === null) {
    return false;
  }

  const fromCycle = cycleGroupByNode.get(edge.from);
  const toCycle = cycleGroupByNode.get(edge.to);
  return fromCycle?.id === highlightedCycleId && toCycle?.id === highlightedCycleId;
}

function buildAdjacency() {
  const out = new Map();
  const inn = new Map();

  for (const node of graph.nodes) {
    out.set(node.id, []);
    inn.set(node.id, []);
  }

  for (const edge of graph.edges) {
    if (!out.has(edge.from)) {
      out.set(edge.from, []);
    }
    if (!inn.has(edge.to)) {
      inn.set(edge.to, []);
    }
    out.get(edge.from)?.push(edge.to);
    inn.get(edge.to)?.push(edge.from);
  }

  return { out, inn };
}

function focusedNodeSet() {
  if (!focusIsolationEnabled || !selected) {
    return null;
  }

  const cacheKey = `${selected.id}|${focusHopDepth}|${graph.nodes.length}|${graph.edges.length}`;
  if (focusSetCache !== null && focusSetCacheKey === cacheKey) {
    return focusSetCache;
  }

  const { out, inn } = buildAdjacency();
  const seen = new Set([selected.id]);
  const queue = [{ id: selected.id, depth: 0 }];

  while (queue.length > 0) {
    const current = queue.shift();
    if (!current) {
      continue;
    }
    if (current.depth >= focusHopDepth) {
      continue;
    }

    const neighbors = [...(out.get(current.id) || []), ...(inn.get(current.id) || [])];
    for (const next of neighbors) {
      if (seen.has(next)) {
        continue;
      }
      seen.add(next);
      queue.push({ id: next, depth: current.depth + 1 });
    }
  }

  focusSetCache = seen;
  focusSetCacheKey = cacheKey;
  return focusSetCache;
}

function isNodeVisibleByFilter(node) {
  if (!activeTypeFilters.has(classifyNode(node.id))) {
    return false;
  }

  if (selected && selected.id === node.id) {
    return true;
  }

  if (highlightedCycleId !== null) {
    const cycleInfo = cycleGroupByNode.get(node.id);
    if (!cycleInfo || cycleInfo.id !== highlightedCycleId) {
      return false;
    }
  }

  if (violationsOnlyEnabled && !violationNodeIds.has(node.id)) {
    return false;
  }

  const focusSet = focusedNodeSet();
  if (focusSet && !focusSet.has(node.id)) {
    return false;
  }

  return true;
}

function syncActiveTypeFiltersFromControls() {
  activeTypeFilters.clear();
  for (const checkbox of filterCheckboxes) {
    if (!(checkbox instanceof HTMLInputElement)) {
      continue;
    }
    const filter = String(checkbox.dataset.astFilter || '').trim();
    if (checkbox.checked && FILTERABLE_TYPES.includes(filter)) {
      activeTypeFilters.add(filter);
    }
  }

  if (activeTypeFilters.size === 0) {
    for (const checkbox of filterCheckboxes) {
      if (checkbox instanceof HTMLInputElement) {
        checkbox.checked = true;
        const filter = String(checkbox.dataset.astFilter || '').trim();
        if (FILTERABLE_TYPES.includes(filter)) {
          activeTypeFilters.add(filter);
        }
      }
    }
  }

  if (selected && !isNodeVisibleByFilter(selected)) {
    selected = null;
    info.textContent = msg('node_info_default', 'Select a node.');
  }

  render();
}

function render() {
  if (currentView === '3d') {
    render3D();
    return;
  }

  render2D();
}

function render2D() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  for (const e of graph.edges) {
    const a = idx.get(e.from), b = idx.get(e.to);
    if (!a || !b) continue;
    if (!isNodeVisibleByFilter(a) || !isNodeVisibleByFilter(b)) continue;

    const connectedToSelection = isEdgeConnectedToSelected(e);
    const violationEdge = isViolationEdge(e);
    const highlightedCycleEdge = isEdgeInHighlightedCycle(e);
    const cycleEdge = highlightedCycleEdge || (cycleGroupByNode.get(e.from)?.id && cycleGroupByNode.get(e.from)?.id === cycleGroupByNode.get(e.to)?.id);

    if (selected && connectedToSelection) {
      ctx.strokeStyle = TYPE_COLORS.selected;
      ctx.lineWidth = 2.35;
      ctx.globalAlpha = 0.95;
    } else if (violationEdge) {
      ctx.strokeStyle = TYPE_COLORS.violation;
      ctx.lineWidth = 1.9;
      ctx.globalAlpha = selected ? 0.65 : 0.9;
    } else if (highlightedCycleEdge) {
      ctx.strokeStyle = TYPE_COLORS.cycle;
      ctx.lineWidth = 1.9;
      ctx.globalAlpha = 0.9;
    } else if (cycleEdge) {
      ctx.strokeStyle = TYPE_COLORS.cycle;
      ctx.lineWidth = 1.35;
      ctx.globalAlpha = selected ? 0.2 : 0.45;
    } else {
      ctx.strokeStyle = '#9aa0a6';
      ctx.lineWidth = 1;
      ctx.globalAlpha = selected ? 0.1 : 0.2;
    }

    const p = ws(a.x, a.y), q = ws(b.x, b.y);
    ctx.beginPath();
    ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y);
    ctx.stroke();
  }

  ctx.globalAlpha = 1;
  ctx.lineWidth = 1;
  const showLabels = transform.scale > 0.25;
  for (const n of graph.nodes) {
    if (!isNodeVisibleByFilter(n)) {
      continue;
    }
    const p = ws(n.x, n.y);
    const isSel = selected && selected.id === n.id;
    const cat = classifyNode(n.id);
    const radius = (isSel ? NODE_RADIUS_2D_SELECTED_PX : NODE_RADIUS_2D_PX) + degreeRadiusBonus(n.id);
    ctx.beginPath();
    ctx.fillStyle = isSel ? TYPE_COLORS.selected : TYPE_COLORS[cat];
    ctx.arc(p.x, p.y, radius, 0, Math.PI * 2);
    ctx.fill();

    if (showLabels) {
      ctx.fillStyle = '#fff';
      ctx.font = `bold ${Math.max(8, Math.min(11, 9 * transform.scale))}px sans-serif`;
      ctx.fillText(shortLabel(n.id), p.x + 6, p.y - 6);
    }
  }

  if (selected && isNodeVisibleByFilter(selected)) {
    const p = ws(selected.x, selected.y);
    ctx.fillStyle = '#fff';
    ctx.font = '12px sans-serif';
    ctx.fillText(selected.id, p.x + 8, p.y - 8);
  }
}

function render3D() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  const projected = new Map();
  for (const n of graph.nodes) {
    if (isNodeVisibleByFilter(n)) {
      projected.set(n.id, projectNode3D(n));
    }
  }

  ctx.strokeStyle = '#9aa0a6';
  ctx.lineWidth = 1;
  for (const e of graph.edges) {
    const a = projected.get(e.from);
    const b = projected.get(e.to);
    if (!a || !b) {
      continue;
    }

    const depth = Math.max(0.08, Math.min(0.55, (a.z + b.z + sphere.radius * 2) / (sphere.radius * 4)));
    const connectedToSelection = isEdgeConnectedToSelected(e);
    const violationEdge = isViolationEdge(e);
    const highlightedCycleEdge = isEdgeInHighlightedCycle(e);
    const cycleEdge = highlightedCycleEdge || (cycleGroupByNode.get(e.from)?.id && cycleGroupByNode.get(e.from)?.id === cycleGroupByNode.get(e.to)?.id);

    if (connectedToSelection) {
      ctx.strokeStyle = TYPE_COLORS.selected;
      ctx.lineWidth = 2.3;
      ctx.globalAlpha = Math.min(1, depth + 0.4);
    } else if (violationEdge) {
      ctx.strokeStyle = TYPE_COLORS.violation;
      ctx.lineWidth = 1.9;
      ctx.globalAlpha = selected ? Math.max(0.18, depth * 0.75) : Math.min(1, depth + 0.35);
    } else if (highlightedCycleEdge) {
      ctx.strokeStyle = TYPE_COLORS.cycle;
      ctx.lineWidth = 1.9;
      ctx.globalAlpha = Math.min(1, depth + 0.3);
    } else if (cycleEdge) {
      ctx.strokeStyle = TYPE_COLORS.cycle;
      ctx.lineWidth = 1.3;
      ctx.globalAlpha = selected ? Math.max(0.07, depth * 0.35) : Math.max(0.2, depth * 0.6);
    } else {
      ctx.strokeStyle = '#9aa0a6';
      ctx.lineWidth = 1;
      ctx.globalAlpha = selected ? Math.max(0.05, depth * 0.45) : depth;
    }
    ctx.beginPath();
    ctx.moveTo(a.x, a.y);
    ctx.lineTo(b.x, b.y);
    ctx.stroke();
  }

  const sortedNodes = graph.nodes.filter(n => isNodeVisibleByFilter(n)).sort((a, b) => {
    const pa = projected.get(a.id);
    const pb = projected.get(b.id);
    return (pa?.z || 0) - (pb?.z || 0);
  });

  for (const n of sortedNodes) {
    const p = projected.get(n.id);
    if (!p) {
      continue;
    }

    const isSel = selected && selected.id === n.id;
    const cat = classifyNode(n.id);
    const alpha = Math.max(0.45, Math.min(1, (p.z + sphere.radius) / (sphere.radius * 2) + 0.2));
    const radius = Math.max(4.6, (isSel ? p.radius + 1.2 : p.radius) + degreeRadiusBonus(n.id) * 0.6);
    ctx.globalAlpha = alpha;
    ctx.beginPath();
    ctx.fillStyle = isSel ? TYPE_COLORS.selected : TYPE_COLORS[cat];
    ctx.arc(p.x, p.y, radius, 0, Math.PI * 2);
    ctx.fill();

    if (sphere.zoom > 0.85 && radius > 4.3) {
      ctx.globalAlpha = Math.min(1, alpha + 0.1);
      ctx.fillStyle = '#fff';
      ctx.font = '10px sans-serif';
      ctx.fillText(shortLabel(n.id), p.x + radius + 2, p.y - radius);
    }
  }

  if (selected && isNodeVisibleByFilter(selected)) {
    const p = projected.get(selected.id);
    if (p) {
      ctx.globalAlpha = 1;
      ctx.fillStyle = '#fff';
      ctx.font = '12px sans-serif';
      ctx.fillText(selected.id, p.x + p.radius + 6, p.y - p.radius - 6);
    }
  }

  ctx.globalAlpha = 1;
  ctx.lineWidth = 1;
}

function pick(ex, ey) {
  if (currentView === '3d') {
    return pick3D(ex, ey);
  }

  return pick2D(ex, ey);
}

function isPointInsideBounds(x, y, bounds) {
  return x >= bounds.left && x <= bounds.right && y >= bounds.top && y <= bounds.bottom;
}

function pick2D(ex, ey) {
  const showLabels = transform.scale > 0.25;
  if (showLabels) {
    const fontSize = Math.max(8, Math.min(11, 9 * transform.scale));
    ctx.save();
    ctx.font = `bold ${fontSize}px sans-serif`;
    for (const n of graph.nodes) {
      if (!isNodeVisibleByFilter(n)) {
        continue;
      }
      const p = ws(n.x, n.y);
      const labelX = p.x + 6;
      const labelBaselineY = p.y - 6;
      const labelWidth = ctx.measureText(shortLabel(n.id)).width;
      const labelBounds = {
        left: labelX - 2,
        right: labelX + labelWidth + 2,
        top: labelBaselineY - fontSize - 2,
        bottom: labelBaselineY + 3,
      };
      if (isPointInsideBounds(ex, ey, labelBounds)) {
        ctx.restore();
        return n;
      }
    }
    ctx.restore();
  }

  let best = null;
  let dmin = Infinity;
  const hitRadius = Math.max(PICK_RADIUS_2D_PX, 10 / Math.max(0.35, transform.scale)) * HIT_RADIUS_SCALE;

  for (const n of graph.nodes) {
    if (!isNodeVisibleByFilter(n)) {
      continue;
    }
    const p = ws(n.x, n.y);
    const d = Math.hypot(p.x - ex, p.y - ey);
    if (d < dmin && d <= hitRadius) {
      best = n;
      dmin = d;
    }
  }

  if (best !== null) {
    return best;
  }

  for (const n of graph.nodes) {
    if (!isNodeVisibleByFilter(n)) {
      continue;
    }
    const p = ws(n.x, n.y);
    const d = Math.hypot(p.x - ex, p.y - ey);
    if (d < dmin && d <= PICK_FALLBACK_RADIUS_PX * HIT_RADIUS_SCALE) {
      best = n;
      dmin = d;
    }
  }

  return best;
}

function pick3D(ex, ey) {
  if (sphere.zoom > 0.85) {
    ctx.save();
    ctx.font = '10px sans-serif';
    for (const n of graph.nodes) {
      if (!isNodeVisibleByFilter(n)) {
        continue;
      }
      const p = projectNode3D(n);
      if (p.radius <= 4.3) {
        continue;
      }
      const labelX = p.x + p.radius + 2;
      const labelBaselineY = p.y - p.radius;
      const labelWidth = ctx.measureText(shortLabel(n.id)).width;
      const labelBounds = {
        left: labelX - 2,
        right: labelX + labelWidth + 2,
        top: labelBaselineY - 12,
        bottom: labelBaselineY + 3,
      };
      if (isPointInsideBounds(ex, ey, labelBounds)) {
        ctx.restore();
        return n;
      }
    }
    ctx.restore();
  }

  let best = null;
  let dmin = Infinity;
  for (const n of graph.nodes) {
    if (!isNodeVisibleByFilter(n)) {
      continue;
    }
    const p = projectNode3D(n);
    const dx = p.x - ex;
    const dy = p.y - ey;
    const d = Math.hypot(dx, dy);
    if (d < dmin && d <= (p.radius + PICK_RADIUS_3D_PADDING_PX) * HIT_RADIUS_SCALE) {
      best = n;
      dmin = d;
    }
  }

  if (best !== null) {
    return best;
  }

  for (const n of graph.nodes) {
    if (!isNodeVisibleByFilter(n)) {
      continue;
    }
    const p = projectNode3D(n);
    const d = Math.hypot(p.x - ex, p.y - ey);
    if (d < dmin && d <= PICK_FALLBACK_RADIUS_PX * HIT_RADIUS_SCALE) {
      best = n;
      dmin = d;
    }
  }

  return best;
}

function nearestToCanvasCenter() {
  if (!graph.nodes.length) {
    return null;
  }

  if (currentView === '3d') {
    let best3d = null;
    let bestDistance3d = Infinity;
    const cx = canvas.width / 2;
    const cy = canvas.height / 2;
    for (const n of graph.nodes) {
      if (!isNodeVisibleByFilter(n)) {
        continue;
      }
      const p = projectNode3D(n);
      const distance = Math.hypot(p.x - cx, p.y - cy);
      if (distance < bestDistance3d) {
        best3d = n;
        bestDistance3d = distance;
      }
    }

    return best3d;
  }

  const center = sw(canvas.width / 2, canvas.height / 2);
  let best = null;
  let bestDistance = Infinity;

  for (const n of graph.nodes) {
    if (!isNodeVisibleByFilter(n)) {
      continue;
    }
    const dx = n.x - center.x;
    const dy = n.y - center.y;
    const distance = Math.hypot(dx, dy);
    if (distance < bestDistance) {
      best = n;
      bestDistance = distance;
    }
  }

  return best;
}

canvas.addEventListener('mousedown', e => {
  dragging = true;
  drag = { sx: e.clientX, sy: e.clientY, ox: transform.x, oy: transform.y, moved: false };
});
window.addEventListener('mouseup', () => dragging = false);
window.addEventListener('mousemove', e => {
  if (dragging) {
    const dx = e.clientX - drag.sx, dy = e.clientY - drag.sy;
    if (Math.abs(dx) > 3 || Math.abs(dy) > 3) drag.moved = true;
    if (currentView === '3d') {
      sphere.rotY = sphere.rotY + dx * 0.004;
      sphere.rotX = Math.max(-1.3, Math.min(1.3, sphere.rotX + dy * 0.004));
      drag.sx = e.clientX;
      drag.sy = e.clientY;
    } else {
      transform.x = drag.ox + dx;
      transform.y = drag.oy + dy;
    }
    render();
    return;
  }

  const rect = canvas.getBoundingClientRect();
  const cx = e.clientX - rect.left;
  const cy = e.clientY - rect.top;
  const hovered = pick(cx, cy);
  canvas.style.cursor = hovered ? 'pointer' : 'default';
});
canvas.addEventListener('wheel', e => {
  e.preventDefault();
  if (currentView === '3d') {
    sphere.zoom = Math.max(0.5, Math.min(2.8, sphere.zoom * (e.deltaY < 0 ? 1.08 : 0.92)));
  } else {
    const w = sw(e.offsetX, e.offsetY);
    transform.scale = Math.max(0.25, Math.min(4, transform.scale * (e.deltaY < 0 ? 1.1 : 0.9)));
    const s = ws(w.x, w.y);
    transform.x += e.offsetX - s.x;
    transform.y += e.offsetY - s.y;
  }
  render();
}, { passive: false });

canvas.addEventListener('click', e => {
  if (drag && drag.moved) return;
  const rect = canvas.getBoundingClientRect();
  const cx = e.clientX - rect.left, cy = e.clientY - rect.top;
  const n = pick(cx, cy);
  if (!n) return;
  selected = n;
  showNodeDetails(n);
  render();
});

canvas.addEventListener('keydown', (event) => {
  const panStep = 24;
  const zoomStep = 1.1;
  const minScale = 0.25;
  const maxScale = 4;
  const minSphereZoom = 0.5;
  const maxSphereZoom = 2.8;
  let handled = false;
  let skipRender = false;

  if (event.key === 'ArrowLeft') {
    transform.x += panStep;
    graphStatus.textContent = msg('kbd_pan_left', 'Panned left.');
    handled = true;
  } else if (event.key === 'ArrowRight') {
    transform.x -= panStep;
    graphStatus.textContent = msg('kbd_pan_right', 'Panned right.');
    handled = true;
  } else if (event.key === 'ArrowUp') {
    transform.y += panStep;
    graphStatus.textContent = msg('kbd_pan_up', 'Panned up.');
    handled = true;
  } else if (event.key === 'ArrowDown') {
    transform.y -= panStep;
    graphStatus.textContent = msg('kbd_pan_down', 'Panned down.');
    handled = true;
  } else if (event.key === '+' || event.key === '=' || event.key === 'NumpadAdd') {
    if (currentView === '3d') {
      const nextZoom = Math.min(maxSphereZoom, sphere.zoom * zoomStep);
      sphere.zoom = nextZoom;
      graphStatus.textContent = msg('kbd_zoom_in', 'Zoomed in to {scale} percent.')
        .replace('{scale}', String(Math.round(nextZoom * 100)));
    } else {
      const nextScale = Math.min(maxScale, transform.scale * zoomStep);
      transform.scale = nextScale;
      graphStatus.textContent = msg('kbd_zoom_in', 'Zoomed in to {scale} percent.')
        .replace('{scale}', String(Math.round(nextScale * 100)));
    }
    handled = true;
  } else if (event.key === '-' || event.key === 'NumpadSubtract') {
    if (currentView === '3d') {
      const nextZoom = Math.max(minSphereZoom, sphere.zoom / zoomStep);
      sphere.zoom = nextZoom;
      graphStatus.textContent = msg('kbd_zoom_out', 'Zoomed out to {scale} percent.')
        .replace('{scale}', String(Math.round(nextZoom * 100)));
    } else {
      const nextScale = Math.max(minScale, transform.scale / zoomStep);
      transform.scale = nextScale;
      graphStatus.textContent = msg('kbd_zoom_out', 'Zoomed out to {scale} percent.')
        .replace('{scale}', String(Math.round(nextScale * 100)));
    }
    handled = true;
  } else if (event.key === '*' || event.code === 'NumpadMultiply') {
    if (event.repeat) {
      event.preventDefault();
      return;
    }

    if (!trySetViewMode(currentView === '2d' ? '3d' : '2d')) {
      event.preventDefault();
      return;
    }

    graphStatus.textContent = msg('kbd_toggle_view', 'Switched view mode to {mode}.')
      .replace('{mode}', currentView === '3d' ? '3D' : '2D');
    handled = true;
    skipRender = true;
  } else if (event.key === 'Home' || event.key === '0') {
    transform = { x: 30, y: 30, scale: 1 };
    graphStatus.textContent = msg('kbd_reset_view', 'Viewport reset.');
    handled = true;
  } else if (event.key === 'Enter') {
    const nearest = nearestToCanvasCenter();
    if (nearest) {
      selected = nearest;
      showNodeDetails(nearest);
      graphStatus.textContent = msg('kbd_select_center', 'Selected center-nearest node: {id}.')
        .replace('{id}', nearest.id);
    } else {
      graphStatus.textContent = msg('kbd_select_center_none', 'No node found near center.');
    }
    handled = true;
  }

  if (handled) {
    event.preventDefault();
    if (!skipRender) {
      render();
    }
  }
});

btnFocus.addEventListener('click', () => {
  const q = String(input.value || '').trim().toLowerCase();
  if (!q) return;
  const n = graph.nodes.find(x => isNodeVisibleByFilter(x) && x.id.toLowerCase().includes(q));
  if (!n) {
    info.textContent = msg('focus_no_node', 'No node found: {query}').replace('{query}', q);
    graphStatus.textContent = msg('focus_no_node_status', 'No AST node matched search query {query}.').replace('{query}', q);
    return;
  }
  selected = n;
  if (currentView === '3d') {
    const x3 = n.x3 || 0;
    const y3 = n.y3 || 0;
    const z3 = n.z3 || 0;
    sphere.rotY = -Math.atan2(x3, z3 === 0 ? 0.0001 : z3);
    sphere.rotX = Math.atan2(y3, Math.hypot(x3, z3));
  } else {
    transform.x = canvas.width / 2 - n.x * transform.scale;
    transform.y = canvas.height / 2 - n.y * transform.scale;
  }
  showNodeDetails(n);
  render();
});

input.addEventListener('input', () => {
  const q = String(input.value || '').trim();
  if (q.length < 1) {
    populateDatalist();
    return;
  }
  filterDatalist(q);
  const exact = graph.nodes.find(n => isNodeVisibleByFilter(n) && n.id === q);
  if (exact) {
    selected = exact;
    if (currentView === '3d') {
      const x3 = exact.x3 || 0;
      const y3 = exact.y3 || 0;
      const z3 = exact.z3 || 0;
      sphere.rotY = -Math.atan2(x3, z3 === 0 ? 0.0001 : z3);
      sphere.rotX = Math.atan2(y3, Math.hypot(x3, z3));
    } else {
      transform.x = canvas.width / 2 - exact.x * transform.scale;
      transform.y = canvas.height / 2 - exact.y * transform.scale;
    }
    showNodeDetails(exact);
    render();
  }
});

input.addEventListener('keydown', (event) => {
  if (event.key === 'Enter') {
    event.preventDefault();
    btnFocus.click();
  }
});

btnReset.addEventListener('click', () => {
  if (currentView === '3d') {
    sphere.rotX = 0.2;
    sphere.rotY = 0.2;
    sphere.zoom = 1;
  } else {
    transform = { x: 30, y: 30, scale: 1 };
  }
  selected = null;
  highlightedCycleId = null;
  cycleClearButton.hidden = true;
  info.textContent = msg('node_info_default', 'Select a node.');
  graphStatus.textContent = msg('reset_status', 'AST graph selection cleared and viewport reset.');
  render();
});

btnView2d.addEventListener('click', () => {
  trySetViewMode('2d');
});

btnView3d.addEventListener('click', () => {
  trySetViewMode('3d');
});

for (const checkbox of filterCheckboxes) {
  checkbox.addEventListener('change', () => {
    syncActiveTypeFiltersFromControls();
    populateDatalist();
  });
}

focusModeToggle.addEventListener('change', () => {
  focusIsolationEnabled = focusModeToggle.checked;
  populateDatalist();
  render();
});

focusHopsSelect.addEventListener('change', () => {
  const parsed = Number.parseInt(focusHopsSelect.value, 10);
  focusHopDepth = Number.isFinite(parsed) ? Math.max(1, Math.min(3, parsed)) : 2;
  populateDatalist();
  render();
});

violationsOnlyToggle.addEventListener('change', () => {
  violationsOnlyEnabled = violationsOnlyToggle.checked;
  populateDatalist();
  render();
});

cycleClearButton.addEventListener('click', () => {
  highlightedCycleId = null;
  cycleClearButton.hidden = true;
  if (selected) {
    showNodeDetails(selected);
  }
  populateDatalist();
  render();
});

btnGenerate.addEventListener('click', generateGraph);

window.addEventListener('resize', () => {
  resize();
  clampOverlayToWrap();
});
focusIsolationEnabled = focusModeToggle.checked;
focusHopDepth = Math.max(1, Math.min(3, Number.parseInt(focusHopsSelect.value, 10) || 2));
violationsOnlyEnabled = violationsOnlyToggle.checked;
syncActiveTypeFiltersFromControls();
setupOverlayPanelInteraction();
updateViewButtons();
setOverlayCollapsed(false);
resize();
clampOverlayToWrap();
loadCommitMetrics();
loadGraph();
