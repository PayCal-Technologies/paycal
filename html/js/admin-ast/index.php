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
const input = document.getElementById('ast_search');
const btnFocus = document.getElementById('ast_focus_btn');
const btnReset = document.getElementById('ast_reset_btn');

if (!canvas || !stats || !info || !graphStatus || !input || !btnFocus || !btnReset) {
  throw new Error('AST UI missing elements');
}

const ctx = canvas.getContext('2d');
if (!ctx) throw new Error('No canvas context');

let graph = { nodes: [], edges: [] };
let idx = new Map();
let selected = null;
let transform = { x: 30, y: 30, scale: 1 };
let dragging = false;
let drag = { sx: 0, sy: 0, ox: 0, oy: 0 };

function resize() {
  canvas.width = Math.max(900, canvas.parentElement?.clientWidth || 900);
  canvas.height = Math.max(520, Math.floor(window.innerHeight * 0.65));
  render();
}

function ws(x, y) {
  return { x: x * transform.scale + transform.x, y: y * transform.scale + transform.y };
}

function sw(x, y) {
  return { x: (x - transform.x) / transform.scale, y: (y - transform.y) / transform.scale };
}

function loadGraph() {
  graphStatus.textContent = 'AST dependency graph is loading.';
  fetch('/admin/ast/data/?action=graph', { credentials: 'same-origin' })
    .then(r => {
      if (!r.ok) throw new Error('Graph JSON not found. Generate graph JSON in tmp/ast/dependency-graph.json');
      return r.json();
    })
    .then(data => {
      const graphData = data?.data || {};
      const nodes = (graphData.nodes || []).slice(0, 1400);
      const set = new Set(nodes.map(n => n.id));
      const edges = (graphData.edges || []).filter(e => set.has(e.from) && set.has(e.to)).slice(0, 7000);

      graph.nodes = nodes.map((n, i) => ({ ...n, x: (i % 70) * 18 + Math.random() * 5, y: Math.floor(i / 70) * 18 + Math.random() * 5, vx: 0, vy: 0 }));
      graph.edges = edges;
      idx = new Map(graph.nodes.map(n => [n.id, n]));

      layout(140);
      stats.textContent = `Nodes: ${graph.nodes.length} | Edges: ${graph.edges.length}`;
      graphStatus.textContent = `AST dependency graph loaded with ${graph.nodes.length} nodes and ${graph.edges.length} edges.`;
      render();
    })
    .catch(e => {
      stats.textContent = 'Failed to load graph';
      info.textContent = e.message;
      graphStatus.textContent = `AST dependency graph failed to load. ${e.message}`;
    });
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

function render() {
  ctx.clearRect(0, 0, canvas.width, canvas.height);
  ctx.strokeStyle = '#9aa0a6';
  ctx.globalAlpha = 0.2;
  ctx.beginPath();
  for (const e of graph.edges) {
    const a = idx.get(e.from), b = idx.get(e.to);
    if (!a || !b) continue;
    const p = ws(a.x, a.y), q = ws(b.x, b.y);
    ctx.moveTo(p.x, p.y); ctx.lineTo(q.x, q.y);
  }
  ctx.stroke();

  ctx.globalAlpha = 1;
  for (const n of graph.nodes) {
    const p = ws(n.x, n.y);
    ctx.beginPath();
    ctx.fillStyle = selected && selected.id === n.id ? '#d81b60' : '#1565c0';
    ctx.arc(p.x, p.y, selected && selected.id === n.id ? 5.5 : 3.5, 0, Math.PI * 2);
    ctx.fill();
  }

  if (selected) {
    const p = ws(selected.x, selected.y);
    ctx.fillStyle = '#111';
    ctx.font = '12px sans-serif';
    ctx.fillText(selected.id, p.x + 8, p.y - 8);
  }
}

function pick(ex, ey) {
  const p = sw(ex, ey);
  let best = null, dmin = Infinity;
  for (const n of graph.nodes) {
    const dx = n.x - p.x, dy = n.y - p.y;
    const d = Math.hypot(dx, dy);
    if (d < dmin && d < 7 / transform.scale) { best = n; dmin = d; }
  }
  return best;
}

canvas.addEventListener('mousedown', e => {
  dragging = true;
  drag = { sx: e.clientX, sy: e.clientY, ox: transform.x, oy: transform.y };
});
window.addEventListener('mouseup', () => dragging = false);
window.addEventListener('mousemove', e => {
  if (!dragging) return;
  transform.x = drag.ox + (e.clientX - drag.sx);
  transform.y = drag.oy + (e.clientY - drag.sy);
  render();
});
canvas.addEventListener('wheel', e => {
  e.preventDefault();
  const w = sw(e.offsetX, e.offsetY);
  transform.scale = Math.max(0.25, Math.min(4, transform.scale * (e.deltaY < 0 ? 1.1 : 0.9)));
  const s = ws(w.x, w.y);
  transform.x += e.offsetX - s.x;
  transform.y += e.offsetY - s.y;
  render();
}, { passive: false });

canvas.addEventListener('click', e => {
  const n = pick(e.offsetX, e.offsetY);
  if (!n) return;
  selected = n;
  const out = graph.edges.filter(x => x.from === n.id).length;
  const incoming = graph.edges.filter(x => x.to === n.id).length;
  info.textContent = `${n.id} | type=${n.type} | outgoing=${out} | incoming=${incoming}`;
  graphStatus.textContent = `Selected node ${n.id}. ${incoming} incoming and ${out} outgoing edges.`;
  render();
});

btnFocus.addEventListener('click', () => {
  const q = String(input.value || '').trim().toLowerCase();
  if (!q) return;
  const n = graph.nodes.find(x => x.id.toLowerCase().includes(q));
  if (!n) {
    info.textContent = `No node found: ${q}`;
    graphStatus.textContent = `No AST node matched search query ${q}.`;
    return;
  }
  selected = n;
  transform.x = canvas.width / 2 - n.x * transform.scale;
  transform.y = canvas.height / 2 - n.y * transform.scale;
  const out = graph.edges.filter(x => x.from === n.id).length;
  const incoming = graph.edges.filter(x => x.to === n.id).length;
  info.textContent = `${n.id} | type=${n.type} | outgoing=${out} | incoming=${incoming}`;
  graphStatus.textContent = `Focused node ${n.id}. ${incoming} incoming and ${out} outgoing edges.`;
  render();
});

btnReset.addEventListener('click', () => {
  transform = { x: 30, y: 30, scale: 1 };
  selected = null;
  info.textContent = 'Select a node.';
  graphStatus.textContent = 'AST graph selection cleared and viewport reset.';
  render();
});

window.addEventListener('resize', resize);
resize();
loadGraph();
