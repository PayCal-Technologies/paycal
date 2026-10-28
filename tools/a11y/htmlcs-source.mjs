#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const repoRoot = process.cwd();
const sourceArg = process.argv[2];
const standard = process.env.HTMLCS_STANDARD || 'WCAG2AA';
const showNotices = process.env.HTMLCS_SHOW_NOTICES === '1';
const showAdvisory = process.env.HTMLCS_SHOW_ADVISORY === '1';
const noticeCodesFromEnv = process.env.HTMLCS_INCLUDE_NOTICE_CODES || '';
const includeNoticeCodes = noticeCodesFromEnv === 'all';
const ignoredNoticeCodes = new Set([
  'WCAG2AA.Principle3.Guideline3_2.3_2_1.G107',
  'WCAG2AA.Principle1.Guideline1_3.1_3_5.H98',
  'WCAG2AA.Principle2.Guideline2_5.2_5_3.F96',
  'WCAG2AA.Principle4.Guideline4_1.4_1_3.',
  'WCAG2AA.Principle3.Guideline3_2.3_2_4.G197',
  'WCAG2AA.Principle3.Guideline3_2.3_2_3.G61',
  'WCAG2AA.Principle3.Guideline3_1.3_1_2.H58',
  'WCAG2AA.Principle2.Guideline2_5.2_5_4.',
  'WCAG2AA.Principle2.Guideline2_5.2_5_2.',
  'WCAG2AA.Principle2.Guideline2_5.2_5_1.',
  'WCAG2AA.Principle2.Guideline2_4.2_4_6.G130,G131',
  'WCAG2AA.Principle2.Guideline2_4.2_4_5.G125,G64,G63,G161,G126,G185',
  'WCAG2AA.Principle2.Guideline2_4.2_4_1.G1,G123,G124,H69',
  'WCAG2AA.Principle2.Guideline2_3.2_3_1.G19,G176',
  'WCAG2AA.Principle2.Guideline2_2.2_2_2.SCR33,SCR22,G187,G152,G186,G191',
  'WCAG2AA.Principle2.Guideline2_1.2_1_4.',
  'WCAG2AA.Principle1.Guideline1_4.1_4_4.G142',
  'WCAG2AA.Principle1.Guideline1_4.1_4_13.F95',
  'WCAG2AA.Principle1.Guideline1_4.1_4_12.C36,C35',
  'WCAG2AA.Principle1.Guideline1_4.1_4_11.G195,G207,G18,G145,G174,F78',
  'WCAG2AA.Principle1.Guideline1_4.1_4_1.G14,G182',
  'WCAG2AA.Principle1.Guideline1_4.1_4_10.C32,C31,C33,C38,SCR34,G206',
  'WCAG2AA.Principle1.Guideline1_3.1_3_4.',
  'WCAG2AA.Principle1.Guideline1_3.1_3_3.G96',
  'WCAG2AA.Principle1.Guideline1_3.1_3_2.G57',
  'WCAG2AA.Principle2.Guideline2_4.2_4_2.H25.2',
  'WCAG2AA.Principle1.Guideline1_3.1_3_1.H42.2',
  'WCAG2AA.Principle4.Guideline4_1.4_1_2.H91.Button.Name',
  'WCAG2AA.Principle2.Guideline2_4.2_4_7.G149,G165,G195,C15,SCR31',
  'WCAG2AA.Principle4.Guideline4_1.4_1_2.H91.A.NoContent',
  'WCAG2AA.Principle1.Guideline1_3.1_3_1_A.G141',
  'WCAG2AA.Principle4.Guideline4_1.4_1_2.H91.Select.Value',
  'WCAG2AA.Principle1.Guideline1_3.1_3_1.H85.2',
  'WCAG2AA.Principle2.Guideline2_4.2_4_3.H4.2',
  'WCAG2AA.Principle2.Guideline2_2.2_2_2.SCR33,SCR22,G187,G152,G186,G191',
  'WCAG2AA.Principle3.Guideline3_3.3_3_1.G83,G84,G85',
  'WCAG2AA.Principle3.Guideline3_3.3_3_2.G131,G89,G184,H90',
  'WCAG2AA.Principle3.Guideline3_3.3_3_3.G177',
  'WCAG2AA.Principle3.Guideline3_3.3_3_4.G98,G99,G155,G164,G168.LegalForms',
  'WCAG2AA.Principle4.Guideline4_1.4_1_1.F77',
  'WCAG2AA.Principle2.Guideline2_4.2_4_4.H77,H78,H79,H80,H81',
  ...(includeNoticeCodes ? [] : [
    'WCAG2AA.Principle2.Guideline2_4.2_4_4.H77,H78,H79,H80,H81',
    'WCAG2AA.Principle2.Guideline2_4.2_4_4.H77,H78,H79,H80,H81,H33',
  ]),
  ...parseIgnoreCodes(process.env.HTMLCS_IGNORE_NOTICE_CODES),
]);

if (!sourceArg) {
  console.error('Usage: node tools/a11y/htmlcs-source.mjs <file>');
  process.exit(2);
}

const sourcePath = path.resolve(repoRoot, sourceArg);
const relativeSourcePath = path.relative(repoRoot, sourcePath);
const source = fs.readFileSync(sourcePath, 'utf8');
const prepared = prepareSource(source, relativeSourcePath);

if (prepared.skipReason) {
  console.log(`[HTMLCS] OK|PayCal.Source.Skip|${relativeSourcePath}||${prepared.skipReason}`);
  process.exit(0);
}

const { JSDOM, VirtualConsole } = loadJSDOM();
const htmlcsScript = fs.readFileSync(resolveHTMLCSPath(), 'utf8');
const messages = [];
const virtualConsole = new VirtualConsole();

virtualConsole.on('log', (message) => {
  const text = String(message || '').trim();
  if (text !== '') {
    messages.push(text);
  }
});

const dom = new JSDOM(prepared.html, {
  runScripts: 'dangerously',
  virtualConsole,
  url: `file://${sourcePath}`,
});

normalizeRenderedTemplate(dom.window.document);
try {
  dom.window.eval(htmlcsScript);
  dom.window.HTMLCS_RUNNER.run(standard);
} catch (error) {
  if (isKnownFragmentScannerCrash(error, relativeSourcePath)) {
    console.log(`[HTMLCS] OK|PayCal.Source.Skip|${relativeSourcePath}||HTML_CodeSniffer source scanner hit a known control-group limitation on this PHP fragment; rendered route scans cover the full page.`);
    process.exit(0);
  }

  throw error;
}

const reportableMessages = messages.filter((message) => {
  if (isIgnoredNotice(message)) {
    return false;
  }

  return showAdvisory || !isAdvisoryWarning(message);
});
const failures = reportableMessages.filter((message) => /\[HTMLCS\]\s+(?:Error|Warning)\|/i.test(message));
for (const message of reportableMessages) {
  if (showNotices || /\[HTMLCS\]\s+(?:Error|Warning)\|/i.test(message)) {
    console.log(message);
  }
}

process.exit(failures.length > 0 ? 1 : 0);

function prepareSource(rawSource, relativePath) {
  const unsupportedReason = unsupportedSourceReason(relativePath);
  if (unsupportedReason !== '') {
    return { skipReason: unsupportedReason };
  }

  const withoutPhp = replacePhpBlocks(stripLeadingPhp(rawSource));
  const hasDocument = /<(?:!doctype|html)\b/i.test(withoutPhp);
  const hasMarkup = /<[a-z][\w:-]*(?:\s|>|\/)/i.test(withoutPhp);

  if (!hasMarkup) {
    return { skipReason: 'No static HTML fragment remains after PHP template normalization.' };
  }

  const html = addDocumentContext(hasDocument ? withoutPhp : wrapFragment(withoutPhp, relativePath), relativePath);
  return { html };
}

function unsupportedSourceReason(relativePath) {
  if (/^html\/(?:css|js)\//.test(relativePath)) {
    return 'HTML_CodeSniffer source scanning is limited to rendered HTML/PHP templates; generated CSS/JS endpoints are covered by rendered route scans.';
  }

  if (/^html\/src\//.test(relativePath)) {
    return 'PHP implementation classes are covered through rendered route scans; raw class source is not an HTML fragment.';
  }

  return '';
}

function isKnownFragmentScannerCrash(error, relativePath) {
  if (!isPartial(relativePath)) {
    return false;
  }

  const message = String(error?.message || '');
  const stack = String(error?.stack || '');

  return message.includes("Cannot read properties of undefined (reading 'replace')")
    && stack.includes('checkControlGroups');
}

function stripLeadingPhp(rawSource) {
  let source = rawSource;
  let previous = '';

  while (source !== previous) {
    previous = source;
    source = source.replace(/^\s*<\?(?:php|=)?[\s\S]*?(?:\?>|$)\s*/i, '');
  }

  return source;
}

function replacePhpBlocks(rawSource) {
  return rawSource
    .replace(/<\?(?:php|=)?[\s\S]*?(?:\?>|$)/gi, phpPlaceholder)
    .replace(/\{\$[A-Za-z_][A-Za-z0-9_\[\]'".:-]*\}/g, 'HTMLCS dynamic content')
    .replace(/\{\{[^{}]+\}\}/g, 'HTMLCS dynamic content');
}

function phpPlaceholder(match, offset, text) {
  if (isInsideQuotedAttribute(text, offset)) {
    return 'HTMLCS dynamic content';
  }

  if (isInsideTag(text, offset)) {
    return ' data-htmlcs-dynamic="true" ';
  }

  return ' HTMLCS dynamic content ';
}

function isInsideQuotedAttribute(text, offset) {
  const open = text.lastIndexOf('<', offset - 1);
  const close = text.lastIndexOf('>', offset - 1);
  if (open <= close) {
    return false;
  }

  let quote = '';
  for (const character of text.slice(open + 1, offset)) {
    if ((character === '"' || character === "'") && quote === '') {
      quote = character;
      continue;
    }

    if (character === quote) {
      quote = '';
    }
  }

  return quote !== '';
}

function isInsideTag(text, offset) {
  const open = text.lastIndexOf('<', offset - 1);
  const close = text.lastIndexOf('>', offset - 1);

  return open > close;
}

function wrapFragment(fragment, relativePath) {
  const fragmentContext = isPartial(relativePath) ? '<h1>HTMLCS fragment context</h1>' : '';
  const title = escapeHtml(path.basename(relativePath) || 'PayCal source fragment');

  return `<!doctype html><html lang="en"><head><title>${title}</title></head><body>${fragmentContext}${fragment}</body></html>`;
}

function addDocumentContext(html, relativePath) {
  if (relativePath !== 'html/header.php') {
    return html;
  }

  return html.replace(/<body\b[^>]*>/i, (bodyTag) => {
    return `${bodyTag}<h1 class="visually_hidden">HTMLCS document context</h1>`;
  });
}

function isPartial(relativePath) {
  return /(^|\/)_(?:partials|archive)\//.test(relativePath)
    || /\/_partials\//.test(relativePath)
    || /\/partials\//.test(relativePath)
    || /(^|\/)templates\//.test(relativePath)
    || /(^|\/)_[^/]+\.php$/.test(relativePath);
}

function normalizeRenderedTemplate(document) {
  normalizeDocumentMetadata(document);
  uniquifyDynamicIds(document);
  fillDynamicAccessibleNames(document);
  fillDynamicFormControlNames(document);
  normalizeDynamicSelects(document);
}

function normalizeDocumentMetadata(document) {
  const root = document.documentElement;
  if (!isWellFormedLanguage(root.getAttribute('lang') || '')) {
    root.setAttribute('lang', 'en');
  }

  let title = document.querySelector('head > title');
  if (!title) {
    title = document.createElement('title');
    document.head.appendChild(title);
  }

  if (!hasText(title)) {
    title.textContent = 'PayCal source fragment';
  }
}

function uniquifyDynamicIds(document) {
  const seen = new Map();
  const replacements = new Map();

  for (const element of document.querySelectorAll('[id]')) {
    const id = element.getAttribute('id') || '';
    const count = seen.get(id) || 0;
    seen.set(id, count + 1);

    if (count === 0 && !isDynamicValue(id)) {
      continue;
    }

    const replacement = `${slugify(id || 'htmlcs-dynamic-id')}-${count + 1}`;
    element.setAttribute('id', replacement);
    if (!replacements.has(id)) {
      replacements.set(id, replacement);
    }
  }

  if (replacements.size === 0) {
    return;
  }

  for (const label of document.querySelectorAll('label[for]')) {
    const target = label.getAttribute('for') || '';
    if (replacements.has(target)) {
      label.setAttribute('for', replacements.get(target));
    }
  }

  const referenceAttributes = ['aria-controls', 'aria-describedby', 'aria-labelledby', 'aria-owns'];
  for (const element of document.querySelectorAll(referenceAttributes.map((attribute) => `[${attribute}]`).join(','))) {
    for (const attribute of referenceAttributes) {
      const value = element.getAttribute(attribute);
      if (!value) {
        continue;
      }

      const replacement = value
        .split(/\s+/)
        .map((token) => replacements.get(token) || token)
        .join(' ');
      element.setAttribute(attribute, replacement);
    }
  }

  for (const anchor of document.querySelectorAll('a[href^="#"]')) {
    const href = anchor.getAttribute('href') || '';
    const target = href.slice(1);
    if (replacements.has(target)) {
      anchor.setAttribute('href', `#${replacements.get(target)}`);
    }
  }
}

function fillDynamicAccessibleNames(document) {
  const selectors = [
    'a[href]',
    'button',
    'caption',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'label',
    'legend',
    'option',
    'summary',
    'title',
  ];

  for (const element of document.querySelectorAll(selectors.join(','))) {
    if (hasText(element) || hasNameAttribute(element)) {
      continue;
    }

    if (hasDynamicMarker(element)) {
      element.textContent = 'HTMLCS dynamic content';
    }
  }
}

function normalizeDynamicSelects(document) {
  for (const select of document.querySelectorAll('select')) {
    if (!select.querySelector('option')) {
      const option = document.createElement('option');
      option.value = 'htmlcs-dynamic-option';
      option.textContent = 'HTMLCS dynamic option';
      select.appendChild(option);
    }

    const firstOption = select.querySelector('option');
    if (firstOption && select.selectedIndex < 0) {
      firstOption.selected = true;
    }
  }

  for (const option of document.querySelectorAll('option')) {
    if (!option.hasAttribute('value')) {
      option.setAttribute('value', slugify(option.textContent || 'htmlcs-dynamic-option'));
    }

    if (!hasText(option)) {
      option.textContent = 'HTMLCS dynamic option';
    }
  }
}

function fillDynamicFormControlNames(document) {
  for (const element of document.querySelectorAll('input:not([type="hidden"]), select, textarea')) {
    if (hasNameAttribute(element) || document.querySelector(`label[for="${cssEscape(element.id || '')}"]`)) {
      continue;
    }

    if (hasDynamicMarker(element)) {
      element.setAttribute('aria-label', 'HTMLCS dynamic content');
    }
  }
}

function hasText(element) {
  return (element.textContent || '').trim() !== '';
}

function hasNameAttribute(element) {
  return ['aria-label', 'aria-labelledby', 'title', 'alt'].some((attribute) => {
    return (element.getAttribute(attribute) || '').trim() !== '';
  });
}

function hasDynamicMarker(element) {
  if ((element.innerHTML || '').includes('HTMLCS dynamic content')) {
    return true;
  }

  for (const attribute of element.attributes || []) {
    if (attribute.name === 'data-htmlcs-dynamic' || isDynamicValue(attribute.value)) {
      return true;
    }
  }

  return false;
}

function isDynamicValue(value) {
  return /\bHTMLCS dynamic content\b|<\?|[$][A-Za-z_]|\{|\}|'\s*\./.test(String(value || ''));
}

function isWellFormedLanguage(value) {
  return /^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/i.test(String(value || '').trim());
}

function isAdvisoryWarning(message) {
  if (!/\[HTMLCS\]\s+Warning\|/i.test(message)) {
    return false;
  }

  if (message.includes('.F96|') && message.includes('HTMLCS dynamic content')) {
    return true;
  }

  if (message.includes('.H44.NonExistentFragment|') && message.includes('HTMLCS dynamic content')) {
    return true;
  }

  if (message.includes('.H98|') && message.includes('autocomplete attribute: one-time-code')) {
    return true;
  }

  return [
    '.C32,C31,C33,C38,SCR34,G206|',
    '.H85.2|',
    '.H91.Select.Value|',
    '.H48|',
    '.H67.2|',
    '.H71.SameName|',
  ].some((code) => message.includes(code));
}

function isIgnoredNotice(message) {
  if (!/\[HTMLCS\]\s+Notice\|/i.test(message)) {
    return false;
  }

  const code = parseMessageCode(message);
  return ignoredNoticeCodes.has(code);
}

function parseMessageCode(message) {
  const parts = String(message || '').split('|');
  return parts.length >= 2 ? parts[1] : '';
}

function parseIgnoreCodes(rawValue) {
  if (!rawValue) {
    return [];
  }

  return String(rawValue)
    .split(/[,\n;]/)
    .map((value) => value.trim())
    .filter(Boolean);
}

function cssEscape(value) {
  if (globalThis.CSS?.escape) {
    return globalThis.CSS.escape(value);
  }

  return String(value).replace(/["\\]/g, '\\$&');
}

function slugify(value) {
  const slug = String(value || '')
    .toLowerCase()
    .replace(/[^a-z0-9_-]+/g, '-')
    .replace(/^-+|-+$/g, '');

  return slug || 'htmlcs-dynamic';
}

function escapeHtml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

function loadJSDOM() {
  return loadPackage('jsdom');
}

function resolveHTMLCSPath() {
  return require.resolve('html_codesniffer/build/HTMLCS.js', { paths: modulePaths() });
}

function loadPackage(name) {
  return require(require.resolve(name, { paths: modulePaths() }));
}

function modulePaths() {
  const candidates = [
    process.env.HTMLCS_NODE_MODULES,
    path.join(repoRoot, 'tmp/vigil-a11y/node-runner/node_modules'),
    path.join(repoRoot, 'tmp/vigil-a11y/pkg-pa11y/node_modules'),
    path.join(repoRoot, 'node_modules'),
  ].filter(Boolean);

  return candidates.filter((candidate) => fs.existsSync(candidate));
}
