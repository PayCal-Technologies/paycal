import fs from 'node:fs';
import path from 'node:path';

const ROOT = process.cwd();
const JS_DIR = path.join(ROOT, 'html', 'js');
const HEADER_FILE = path.join(ROOT, 'html', 'header.php');
const PAGE_HEAD_RENDERER_FILE = path.join(ROOT, 'html', 'src', 'Domain', 'PageHeadRenderer.php');

const ALLOWED_SINK_FILES = new Set([
  path.join('html', 'js', 'guardian.js'),
  path.join('html', 'js', 'core', 'index.php'),
]);

const SINK_PATTERNS = [
  { name: 'innerHTML assignment', regex: /\binnerHTML\s*\+?=/g },
  { name: 'outerHTML assignment', regex: /\bouterHTML\s*=/g },
  { name: 'insertAdjacentHTML call', regex: /\binsertAdjacentHTML\s*\(/g },
];

function walk(dir) {
  const out = [];
  const entries = fs.readdirSync(dir, { withFileTypes: true });
  for (const entry of entries) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) {
      out.push(...walk(full));
    } else if (entry.isFile()) {
      if (entry.name.endsWith('.php') || entry.name.endsWith('.js')) {
        out.push(full);
      }
    }
  }
  return out;
}

function rel(filePath) {
  return path.relative(ROOT, filePath).replace(/\\/g, '/');
}

function lineFromIndex(content, index) {
  return content.slice(0, index).split('\n').length;
}

function checkGuardianBootstrap() {
  const header = fs.readFileSync(HEADER_FILE, 'utf8');

  if (header.includes('js/guardian.js')) {
    return;
  }

  const delegatesToPageHeadRenderer = header.includes('PageHeadRenderer::renderScripts');
  if (delegatesToPageHeadRenderer && fs.existsSync(PAGE_HEAD_RENDERER_FILE)) {
    const pageHeadRenderer = fs.readFileSync(PAGE_HEAD_RENDERER_FILE, 'utf8');
    if (pageHeadRenderer.includes('guardian.js')) {
      return;
    }
    throw new Error('Guardian bootstrap missing in html/src/Domain/PageHeadRenderer.php');
  }

  throw new Error('Guardian bootstrap missing in html/header.php');
}

function main() {
  checkGuardianBootstrap();

  const files = walk(JS_DIR);
  const violations = [];

  for (const file of files) {
    const relative = rel(file);
    const content = fs.readFileSync(file, 'utf8');

    if (ALLOWED_SINK_FILES.has(relative)) {
      continue;
    }

    for (const pattern of SINK_PATTERNS) {
      let match;
      while ((match = pattern.regex.exec(content)) !== null) {
        violations.push({
          file: relative,
          line: lineFromIndex(content, match.index),
          type: pattern.name,
        });
      }
      pattern.regex.lastIndex = 0;
    }
  }

  if (violations.length > 0) {
    console.error('JavaScript security check failed. Disallowed sinks detected:');
    for (const v of violations) {
      console.error(`- ${v.file}:${v.line} (${v.type})`);
    }
    process.exit(1);
  }

  console.log('JavaScript security check passed: no disallowed HTML sinks found.');
}

main();
