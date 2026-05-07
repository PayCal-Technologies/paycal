#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const root = path.resolve(__dirname, '..');
const cssDir = path.join(root, 'html', 'css');

const themeFiles = fs
  .readdirSync(cssDir)
  .filter((d) => /_(light|dark)$/.test(d))
  .map((d) => path.join(cssDir, d, 'index.php'))
  .filter((f) => fs.existsSync(f));

const legacyVars = new Set([
  'body-back',
  'body-fore',
  'panel-back',
  'panel-fore',
  'panel-border-color',
  'btn-back',
  'btn-fore',
  'btn-border-colors',
  'btn-border-colors-active',
  'btn-primary-back',
  'btn-primary-fore',
  'btn-secondary-back',
  'btn-secondary-fore',
  'chrome-back',
  'chrome-fore',
  'header-back',
  'header-fore',
  'footer-back',
  'footer-fore',
  'spot-back',
  'spot-fore',
  'context-menu-back',
  'context-menu-fore',
  'warning',
  'cal-day-back',
  'cal-day-hover-back',
  'cal-day-border',
  'btn-selected-back',
  'btn-selected-fore',
  'panel-head-back',
  'panel-head-fore',
  'dialog-back',
  'dialog-fore',
]);

const semanticTokens = new Set([
  'color-bg',
  'color-bg-soft',
  'color-bg-elevated',
  'color-bg-overlay',
  'color-surface',
  'color-surface-muted',
  'color-surface-strong',
  'color-border',
  'color-border-soft',
  'color-border-strong',
  'color-text',
  'color-text-muted',
  'color-text-inverse',
  'color-text-disabled',
  'color-primary',
  'color-primary-hover',
  'color-primary-active',
  'color-primary-soft',
  'color-on-primary',
  'color-success',
  'color-warning',
  'color-danger',
  'color-info',
  'color-hover',
  'color-active',
  'color-focus-ring',
  'color-selection',
  'color-highlight',
  'color-disabled-bg',
  'elevation-1-bg',
  'elevation-2-bg',
  'elevation-3-bg',
  'overlay-backdrop',
  'shadow-sm',
  'shadow-md',
  'shadow-lg',
  'button-bg',
  'button-bg-hover',
  'button-bg-active',
  'button-text',
  'button-border',
  'button-border-active',
  'button-primary-bg',
  'button-primary-text',
  'button-secondary-bg',
  'button-secondary-text',
  'button-danger-text',
  'panel-bg',
  'panel-text',
  'panel-border',
  'panel-head-bg',
  'panel-head-text',
  'dialog-bg',
  'dialog-text',
  'dialog-border',
  'dialog-shadow',
  'dialog-overlay',
  'calendar-bg',
  'calendar-border',
  'calendar-day-bg',
  'calendar-day-hover',
  'calendar-day-today',
  'calendar-day-selected',
  'calendar-event-bg',
  'calendar-event-text',
  'calendar-range-bg',
]);

const legacyRegex = /var\(--(?:body-back|body-fore|panel-back|panel-fore|panel-border-color|btn-back|btn-fore|btn-border-colors|btn-border-colors-active|btn-primary-back|btn-primary-fore|btn-secondary-back|btn-secondary-fore|chrome-back|chrome-fore|header-back|header-fore|footer-back|footer-fore|spot-back|spot-fore|context-menu-back|context-menu-fore|warning|cal-day-back|cal-day-hover-back|cal-day-border|btn-selected-back|btn-selected-fore|panel-head-back|panel-head-fore|dialog-back|dialog-fore)\b/;

function replaceVarFunction(expr, varName, replacement) {
  const needle = `var(--${varName}`;
  let cursor = 0;
  let out = expr;

  while (true) {
    const start = out.indexOf(needle, cursor);
    if (start === -1) break;

    let i = start + 4; // index at opening parenthesis
    let depth = 1;
    while (i < out.length && depth > 0) {
      i += 1;
      const ch = out[i];
      if (ch === '(') depth += 1;
      if (ch === ')') depth -= 1;
    }

    if (depth !== 0) break;

    const end = i;
    out = `${out.slice(0, start)}${replacement}${out.slice(end + 1)}`;
    cursor = start + replacement.length;
  }

  return out;
}

function resolveLegacy(expr, declMap, depth = 0) {
  if (depth > 15 || !expr.includes('var(--')) return expr;

  let out = expr;
  let changed = false;

  for (const name of legacyVars) {
    const raw = declMap.get(name);
    if (!raw) continue;
    const replacement = resolveLegacy(raw, declMap, depth + 1).trim() || raw.trim();
    const next = replaceVarFunction(out, name, replacement);
    if (next !== out) {
      out = next;
      changed = true;
    }
  }

  return changed ? resolveLegacy(out, declMap, depth + 1) : out;
}

let changedFiles = 0;
let changedDecls = 0;

for (const filePath of themeFiles) {
  const source = fs.readFileSync(filePath, 'utf8');
  const lines = source.split('\n');
  const declMap = new Map();

  for (const line of lines) {
    const m = line.match(/^\s*--([a-z0-9-]+):\s*(.+);\s*$/i);
    if (m) declMap.set(m[1], m[2]);
  }

  let fileChanged = false;
  const out = lines.map((line) => {
    const m = line.match(/^(\s*)--([a-z0-9-]+):(\s*)(.+);(\s*)$/i);
    if (!m) return line;

    const indent = m[1];
    const name = m[2];
    const spacer = m[3];
    const value = m[4];
    const trailing = m[5];

    if (!semanticTokens.has(name)) return line;
    if (!legacyRegex.test(value)) return line;

    const nextValue = resolveLegacy(value, declMap)
      .replace(/\s{2,}/g, ' ')
      .replace(/\s+,/g, ',')
      .replace(/,\s+/g, ', ')
      .trim();

    if (nextValue === value.trim()) return line;

    fileChanged = true;
    changedDecls += 1;
    return `${indent}--${name}:${spacer}${nextValue};${trailing}`;
  });

  if (fileChanged) {
    fs.writeFileSync(filePath, out.join('\n'));
    changedFiles += 1;
  }
}

console.log(`Themes scanned: ${themeFiles.length}`);
console.log(`Themes changed: ${changedFiles}`);
console.log(`Semantic declarations normalized: ${changedDecls}`);
