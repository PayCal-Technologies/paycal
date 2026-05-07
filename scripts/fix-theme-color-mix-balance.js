#!/usr/bin/env node

import fs from 'fs';
import path from 'path';

const root = process.cwd();
const cssRoot = path.join(root, 'html', 'css');

const themeFiles = fs
  .readdirSync(cssRoot)
  .filter((name) => /_(dark|light)$/.test(name))
  .map((name) => path.join(cssRoot, name, 'index.php'))
  .filter((file) => fs.existsSync(file))
  .sort();

function fixColorMixValue(value) {
  if (!value.includes('color-mix(')) return value;

  let out = value;

  // If a token value ends with extra "))", collapse to a single final ')'.
  out = out.replace(/(color-mix\([^;]*?)\)\)\s*$/g, '$1)');

  // Ensure missing final ')' before semicolon is restored.
  const openCount = (out.match(/\(/g) || []).length;
  const closeCount = (out.match(/\)/g) || []).length;
  if (openCount > closeCount) {
    out = out + ')'.repeat(openCount - closeCount);
  }

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
    const m = line.match(/^(\s*--[a-z0-9-]+:\s*)(.+?)(;\s*)$/i);
    if (!m) continue;

    const prefix = m[1];
    const value = m[2];
    const suffix = m[3];

    const fixed = fixColorMixValue(value);
    if (fixed !== value) {
      lines[i] = `${prefix}${fixed}${suffix}`;
      fileChanged = true;
      changedLines += 1;
    }
  }

  if (fileChanged) {
    fs.writeFileSync(file, `${lines.join('\n')}\n`);
    changedFiles += 1;
  }
}

console.log(`Theme files scanned: ${themeFiles.length}`);
console.log(`Theme files changed: ${changedFiles}`);
console.log(`Token lines fixed: ${changedLines}`);
