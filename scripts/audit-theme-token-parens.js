#!/usr/bin/env node

import fs from 'fs';
import path from 'path';

const cssRoot = path.join(process.cwd(), 'html', 'css');
const themes = fs
  .readdirSync(cssRoot)
  .filter((name) => /_(dark|light)$/.test(name))
  .sort();

let totalErrors = 0;

for (const theme of themes) {
  const file = path.join(cssRoot, theme, 'index.php');
  const lines = fs.readFileSync(file, 'utf8').split('\n');
  const errors = [];

  for (let i = 0; i < lines.length; i += 1) {
    const line = lines[i];
    const m = line.match(/^\s*--[a-z0-9-]+:\s*(.+);\s*$/i);
    if (!m) continue;

    const value = m[1];
    let balance = 0;
    for (const ch of value) {
      if (ch === '(') balance += 1;
      if (ch === ')') balance -= 1;
      if (balance < 0) break;
    }

    if (balance !== 0) {
      errors.push(`${i + 1}: paren-balance=${balance} :: ${line.trim()}`);
    }
  }

  if (errors.length === 0) {
    console.log(`PASS ${theme}`);
  } else {
    totalErrors += errors.length;
    console.log(`FAIL ${theme} (${errors.length})`);
    for (const err of errors) {
      console.log(`  ${err}`);
    }
  }
}

console.log(`TOTAL_ERRORS ${totalErrors}`);
