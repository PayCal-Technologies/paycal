import fs from 'node:fs';
import path from 'node:path';

const ROOT = process.cwd();
const SEARCH_ROOTS = [
  path.join(ROOT, 'html'),
  path.join(ROOT, 'templates'),
];

const SINK_PATTERNS = [
  { name: 'innerHTML assignment', regex: /\binnerHTML\s*\+?=/g },
  { name: 'outerHTML assignment', regex: /\bouterHTML\s*=/g },
  { name: 'insertAdjacentHTML call', regex: /\binsertAdjacentHTML\s*\(/g },
];

function walk(dir) {
  if (!fs.existsSync(dir)) {
    return [];
  }

  const files = [];
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      files.push(...walk(full));
    } else if (entry.isFile() && entry.name.endsWith('.php')) {
      files.push(full);
    }
  }

  return files;
}

function rel(filePath) {
  return path.relative(ROOT, filePath).replace(/\\/g, '/');
}

function lineFromIndex(content, index) {
  return content.slice(0, index).split('\n').length;
}

function hasJavaScriptSurface(content, filePath) {
  return filePath.endsWith('.js.php') || /<script\b/i.test(content);
}

const violations = [];

for (const root of SEARCH_ROOTS) {
  for (const file of walk(root)) {
    const content = fs.readFileSync(file, 'utf8');
    if (!hasJavaScriptSurface(content, file)) {
      continue;
    }

    for (const pattern of SINK_PATTERNS) {
      let match;
      while ((match = pattern.regex.exec(content)) !== null) {
        violations.push({
          file: rel(file),
          line: lineFromIndex(content, match.index),
          type: pattern.name,
        });
      }
      pattern.regex.lastIndex = 0;
    }
  }
}

if (violations.length > 0) {
  console.error('PHP JavaScript lint failed. Disallowed sinks detected:');
  for (const violation of violations) {
    console.error(`- ${violation.file}:${violation.line} (${violation.type})`);
  }
  process.exit(1);
}

console.log('PHP JavaScript lint passed: no disallowed embedded sinks found.');
