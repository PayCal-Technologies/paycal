#!/usr/bin/env node
'use strict';
const fs   = require('fs');
const path = require('path');

// --- WCAG color helpers ---
function hexToRgb(hex) {
  hex = hex.replace(/^#/, '');
  if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
  if (hex.length !== 6) return null;
  return [parseInt(hex.slice(0,2),16), parseInt(hex.slice(2,4),16), parseInt(hex.slice(4,6),16)];
}
function lin(c) { c /= 255; return c <= 0.04045 ? c/12.92 : Math.pow((c+0.055)/1.055, 2.4); }
function lum(rgb) { return 0.2126*lin(rgb[0]) + 0.7152*lin(rgb[1]) + 0.0722*lin(rgb[2]); }
function contrast(fg, bg) {
  const l1 = lum(fg), l2 = lum(bg);
  return (Math.max(l1,l2)+0.05) / (Math.min(l1,l2)+0.05);
}
function parseColor(val) {
  if (!val) return null;
  const m = val.match(/#([0-9a-fA-F]{3,6})/);
  if (m) return hexToRgb(m[1]);
  const rgba = val.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
  if (rgba) return [parseInt(rgba[1]),parseInt(rgba[2]),parseInt(rgba[3])];
  return null;
}
function toHex(rgb) {
  return '#' + rgb.map(c => c.toString(16).padStart(2,'0')).join('');
}
function grade(r) {
  if (r === null) return '⚪ n/a';
  if (r >= 7.0)  return '🟢 AAA';
  if (r >= 4.5)  return '🟡 AA';
  return '🔴 FAIL';
}

// --- token map builders ---
function buildTokenMap(filePath) {
  if (!fs.existsSync(filePath)) return {};
  const text = fs.readFileSync(filePath, 'utf8');
  const map = {};
  for (const m of text.matchAll(/--([a-z0-9_-]+)\s*:\s*([^;]+);/gi)) {
    map['--' + m[1].trim()] = m[2].trim();
  }
  return map;
}
const baseTokens = buildTokenMap('html/css/tokens/index.php');
function mergeMap(themeFile) {
  return { ...baseTokens, ...buildTokenMap(themeFile) };
}

function resolveToken(key, map, depth) {
  if (depth === undefined) depth = 0;
  if (depth > 6) return null;
  const val = map[key];
  if (!val) return null;
  const hex = parseColor(val);
  if (hex) return { hex, raw: val };
  const vm = val.match(/^var\((--[a-z0-9_-]+)(?:,\s*([^)]+))?\)/i);
  if (vm) {
    const r = resolveToken(vm[1], map, depth+1);
    if (r) return r;
    if (vm[2]) {
      const fb = parseColor(vm[2]);
      if (fb) return { hex: fb, raw: vm[2] };
    }
  }
  return null;
}

// --- scan ---
const cssDir = 'html/css';
const skip = new Set(['calendar','contact','tokens']);
const themeDirs = fs.readdirSync(cssDir)
  .filter(d => {
    if (skip.has(d)) return false;
    const p = path.join(cssDir, d);
    return fs.statSync(p).isDirectory() && fs.existsSync(path.join(p,'index.php'));
  }).sort();

const rows = [];
for (const dir of themeDirs) {
  const file = path.join(cssDir, dir, 'index.php');
  const map  = mergeMap(file);

  const ownBack = buildTokenMap(file)['--work-entry-back'];
  const ownFore = buildTokenMap(file)['--work-entry-fore'];
  const source  = ownBack ? 'theme' : 'base fallback';

  const backRaw = map['--work-entry-back'] || '(base: var(--color-surface))';
  const foreRaw = map['--work-entry-fore'] || '(base: var(--color-text))';

  const backR = resolveToken('--work-entry-back', map);
  const foreR = resolveToken('--work-entry-fore', map);
  const ratio = (backR && foreR) ? contrast(foreR.hex, backR.hex) : null;

  rows.push({
    theme:   dir,
    source,
    backRaw: backRaw.length > 32 ? backRaw.slice(0,30)+'…' : backRaw,
    foreRaw: foreRaw.length > 32 ? foreRaw.slice(0,30)+'…' : foreRaw,
    backHex: backR ? toHex(backR.hex) : '?',
    foreHex: foreR ? toHex(foreR.hex) : '?',
    ratio,
    ratioStr: ratio !== null ? ratio.toFixed(2)+':1' : '—',
    grade: grade(ratio),
  });
}

// --- markdown output ---
const lines = [];
lines.push('# Work-Entry Token Contrast Report');
lines.push('');
lines.push(`Generated: ${new Date().toISOString().slice(0,10)}  `);
lines.push(`Themes scanned: ${rows.length}  `);
lines.push('WCAG AA minimum: **4.5:1** | AAA: **7.0:1**');
lines.push('');
lines.push('> **Token source** — `theme` = explicit tokens in the theme file; `base fallback` = resolved through `tokens/index.php` defaults (`--color-surface` / `--color-text`).');
lines.push('');
lines.push('| Theme | Source | `--work-entry-back` (raw) | Resolved BG | `--work-entry-fore` (raw) | Resolved FG | Ratio | Grade |');
lines.push('|---|---|---|:---:|---|:---:|---:|:---:|');
for (const r of rows) {
  lines.push(`| ${r.theme} | ${r.source} | \`${r.backRaw}\` | \`${r.backHex}\` | \`${r.foreRaw}\` | \`${r.foreHex}\` | ${r.ratioStr} | ${r.grade} |`);
}

const aaa  = rows.filter(r => r.grade.includes('AAA')).length;
const aa   = rows.filter(r => r.grade.includes('AA') && !r.grade.includes('AAA')).length;
const fail = rows.filter(r => r.grade.includes('FAIL')).length;
const unrec= rows.filter(r => r.grade.includes('n/a')).length;

lines.push('');
lines.push('## Summary');
lines.push('');
lines.push('| Result | Count |');
lines.push('|---|---:|');
lines.push(`| 🟢 AAA (≥ 7.0:1) | ${aaa} |`);
lines.push(`| 🟡 AA (4.5 – 6.9:1) | ${aa} |`);
lines.push(`| 🔴 Fail (< 4.5:1) | ${fail} |`);
lines.push(`| ⚪ Unresolved (var chain unresolvable) | ${unrec} |`);
lines.push(`| **Total** | **${rows.length}** |`);

const attn = rows.filter(r => r.grade.includes('FAIL') || r.grade.includes('n/a'));
if (attn.length > 0) {
  lines.push('');
  lines.push('## Needs Attention');
  lines.push('');
  for (const r of attn) {
    lines.push(`- **${r.theme}**: ${r.grade} — ratio ${r.ratioStr} — BG \`${r.backHex}\` FG \`${r.foreHex}\``);
  }
}

const out = 'docs/WCAG_WORK_ENTRY_TOKEN_REPORT.md';
fs.writeFileSync(out, lines.join('\n') + '\n');
console.log(`Written: ${out}`);
console.log(`Themes: ${rows.length} | AAA: ${aaa} | AA: ${aa} | Fail: ${fail} | Unresolved: ${unrec}`);
