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
  .filter((f) => fs.existsSync(f))
  .sort();

const semanticPrefixes = [
  '--color-',
  '--button-',
  '--panel-',
  '--dialog-',
  '--calendar-',
  '--elevation-',
  '--overlay-backdrop',
  '--shadow-',
];

function isSemanticToken(name) {
  return semanticPrefixes.some((prefix) => name.startsWith(prefix));
}

function normalizeValue(value) {
  let out = value.trim();

  // Fix malformed color-mix first argument like "#22303c) 90%" or "var(--x)) 20%".
  out = out.replace(
    /color-mix\(in srgb,\s*(#[0-9A-Fa-f]{3,8}|var\(--[a-z0-9-]+\))\)+\s+([0-9]{1,3}%)/g,
    'color-mix(in srgb, $1 $2'
  );

  // Fix malformed color-mix first argument before comma variant.
  out = out.replace(
    /color-mix\(in srgb,\s*(#[0-9A-Fa-f]{3,8}|var\(--[a-z0-9-]+\))\)+\s*,/g,
    'color-mix(in srgb, $1,'
  );

  // Remove duplicate close parenthesis after var() when not needed.
  out = out.replace(/var\((--[a-z0-9-]+)\)\)+/gi, 'var($1)');

  // Normalize literal values with excess trailing ')' (e.g. "#22303c)", "rgba(...))").
  const hexMatch = out.match(/^(#[0-9A-Fa-f]{3,8})\)+$/);
  if (hexMatch) {
    return hexMatch[1];
  }

  const fnMatch = out.match(/^([a-zA-Z-]+\([^\)]*\))\)+$/);
  if (fnMatch && !out.startsWith('color-mix(')) {
    return fnMatch[1];
  }

  // Clean accidental trailing ")" before semantically complete endings.
  out = out.replace(/\)\s*$/g, (m, offset, str) => {
    // Keep one final ')' for function calls.
    const before = str.slice(0, offset);
    const opens = (before.match(/\(/g) || []).length;
    const closes = (before.match(/\)/g) || []).length;
    return closes >= opens ? '' : m;
  });

  return out;
}

let changedFiles = 0;
let changedLines = 0;

for (const file of themeFiles) {
  const source = fs.readFileSync(file, 'utf8');
  const lines = source.split('\n');
  let fileChanged = false;

  for (let i = 0; i < lines.length; i += 1) {
    const line = lines[i];
    const m = line.match(/^(\s*)--([a-z0-9-]+):(\s*)(.+);(\s*)$/i);
    if (!m) continue;

    const indent = m[1];
    const token = m[2];
    const spacer = m[3];
    const value = m[4];
    const trailing = m[5];

    if (!isSemanticToken(`--${token}`)) continue;

    const normalized = normalizeValue(value);
    if (normalized !== value.trim()) {
      lines[i] = `${indent}--${token}:${spacer}${normalized};${trailing}`;
      fileChanged = true;
      changedLines += 1;
    }
  }

  // Ensure heading accent exists to reinforce theme identity.
  const hasHeadingAccent = lines.some((l) => /^\s*--heading-accent-color\s*:/i.test(l));
  if (!hasHeadingAccent) {
    const insertAt = lines.findIndex((l) => /^\s*--calendar-range-bg\s*:/i.test(l));
    if (insertAt !== -1) {
      lines.splice(insertAt + 1, 0, '  --heading-accent-color:                var(--color-primary);');
      fileChanged = true;
      changedLines += 1;
    }
  }

  if (fileChanged) {
    fs.writeFileSync(file, `${lines.join('\n')}\n`);
    changedFiles += 1;
  }
}

console.log(`Themes scanned: ${themeFiles.length}`);
console.log(`Themes changed: ${changedFiles}`);
console.log(`Token lines normalized/added: ${changedLines}`);
