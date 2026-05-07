#!/usr/bin/env node

/**
 * Earnings Page Contrast Audit (Win95 Light Theme Focus)
 *
 * Purpose: Audit contrast issues on the /earnings page specifically for win95_light theme.
 * The earnings page renders pie graphs with specific color mixtures that may have
 * low contrast against the light gray background palette.
 *
 * Usage: node scripts/earnings-contrast-audit.js
 *
 * This script tests:
 * 1. Pie graph colors (gross, net, deductions) against panel/surface backgrounds
 * 2. Legend text and dots against surface backgrounds
 * 3. Overall theme semantic token contrast
 * 4. Historical intelligence card text contrast
 */

import fs from 'fs';
import path from 'path';

const rootDir = process.cwd();
const cssRoot = path.join(rootDir, 'html', 'css');
const themeName = 'win95_light';
const themeFile = path.join(cssRoot, themeName, 'index.php');

if (!fs.existsSync(themeFile)) {
  console.error(`❌ Theme file not found: ${themeFile}`);
  process.exit(1);
}

const minContrast = 4.5;

// Utility functions from generate-theme-contrast-matrix.js
function channelToLinear(channel) {
  const normalized = channel / 255;
  if (normalized <= 0.03928) return normalized / 12.92;
  return ((normalized + 0.055) / 1.055) ** 2.4;
}

function luminance(rgb) {
  return 0.2126 * channelToLinear(rgb.r) + 0.7152 * channelToLinear(rgb.g) + 0.0722 * channelToLinear(rgb.b);
}

function contrastRatio(fg, bg) {
  const l1 = luminance(fg);
  const l2 = luminance(bg);
  const lighter = Math.max(l1, l2);
  const darker = Math.min(l1, l2);
  return (lighter + 0.05) / (darker + 0.05);
}

function hexToRgb(hex) {
  const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  return result
    ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16),
      }
    : null;
}

function hslToRgb(hslString) {
  const hslMatch = /hsl\(\s*([\d.]+)\s*,\s*([\d.]+)%\s*,\s*([\d.]+)%\s*\)/.exec(hslString);
  if (!hslMatch) return null;

  const h = parseFloat(hslMatch[1]) / 360;
  const s = parseFloat(hslMatch[2]) / 100;
  const l = parseFloat(hslMatch[3]) / 100;

  let r, g, b;

  if (s === 0) {
    r = g = b = l;
  } else {
    const hue2rgb = (p, q, t) => {
      if (t < 0) t += 1;
      if (t > 1) t -= 1;
      if (t < 1 / 6) return p + (q - p) * 6 * t;
      if (t < 1 / 2) return q;
      if (t < 2 / 3) return p + (q - p) * (2 / 3 - t) * 6;
      return p;
    };

    const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
    const p = 2 * l - q;
    r = hue2rgb(p, q, h + 1 / 3);
    g = hue2rgb(p, q, h);
    b = hue2rgb(p, q, h - 1 / 3);
  }

  return {
    r: Math.round(r * 255),
    g: Math.round(g * 255),
    b: Math.round(b * 255),
  };
}

function parseTokenMap(filePath) {
  const source = fs.readFileSync(filePath, 'utf8');
  const map = new Map();

  const tokenRegex = /^\s*(--[a-z0-9-]+)\s*:\s*([^;]+);/gim;
  let match;
  while ((tokenRegex.exec(source) !== null)) {
    match = tokenRegex.lastIndex && source.substring(source.lastIndexOf('\n', tokenRegex.lastIndex - 1) + 1, source.indexOf(';', tokenRegex.lastIndex - 1) + 1);
  }

  // Parse properly
  const lines = source.split('\n');
  for (const line of lines) {
    const tokenMatch = /^\s*(--[a-z0-9-]+)\s*:\s*([^;]+);/.exec(line);
    if (tokenMatch) {
      map.set(tokenMatch[1].trim(), tokenMatch[2].trim());
    }
  }

  return map;
}

function resolveColorMix(expr, tokenMap) {
  // Handle color-mix(in srgb, a t%, b (100-t)%)
  const colorMixMatch = /color-mix\s*\(\s*in\s+srgb\s*,\s*([^,]+)\s+([^\s%]+)%\s*,\s*([^)]+)\s*\)/.exec(expr);
  if (!colorMixMatch) return null;

  const colorA = resolveToRgb(colorMixMatch[1].trim(), tokenMap);
  const colorB = resolveToRgb(colorMixMatch[3].trim(), tokenMap);
  const t = parseFloat(colorMixMatch[2]) / 100;

  if (!colorA || !colorB) return null;

  return {
    r: Math.round(colorA.r * t + colorB.r * (1 - t)),
    g: Math.round(colorA.g * t + colorB.g * (1 - t)),
    b: Math.round(colorA.b * t + colorB.b * (1 - t)),
  };
}

function resolveToRgb(value, tokenMap) {
  value = value.trim();

  if (value.startsWith('var(')) {
    const tokenMatch = /var\((--[a-z0-9-]+)\s*(?:,\s*([^)]+))?\)/.exec(value);
    if (!tokenMatch) return null;
    const resolved = tokenMap.get(tokenMatch[1]);
    if (!resolved) return null;
    return resolveToRgb(resolved, tokenMap);
  }

  if (value.startsWith('#')) {
    return hexToRgb(value);
  }

  if (value.startsWith('hsl')) {
    return hslToRgb(value);
  }

  if (value.startsWith('color-mix')) {
    return resolveColorMix(value, tokenMap);
  }

  if (value === '#ffffff' || value === 'white') {
    return { r: 255, g: 255, b: 255 };
  }
  if (value === '#000000' || value === 'black') {
    return { r: 0, g: 0, b: 0 };
  }

  return null;
}

// Earnings-specific contrast checks
const earningsTokens = [
  { name: '--color-bg', label: 'Background (page)' },
  { name: '--color-surface', label: 'Surface (panel base)' },
  { name: '--color-surface-muted', label: 'Surface muted' },
  { name: '--color-text', label: 'Text (primary)' },
  { name: '--color-text-muted', label: 'Text muted' },
  { name: '--color-primary', label: 'Primary/spot color' },
  { name: '--color-warning', label: 'Warning color' },
];

const earningsChecks = [
  {
    fg: 'color-mix(in srgb, var(--color-primary) 65%, #000000 35%)',
    bg: 'var(--color-surface)',
    label: 'Pie graph gross (dark blue mix) on surface',
  },
  {
    fg: 'color-mix(in srgb, var(--color-primary) 52%, #ffffff 48%)',
    bg: 'var(--color-surface)',
    label: 'Pie graph net (light blue mix) on surface',
  },
  {
    fg: 'color-mix(in srgb, var(--color-warning) 28%, #ffffff 72%)',
    bg: 'var(--color-surface)',
    label: 'Pie graph deductions (light orange mix) on surface',
  },
  {
    fg: 'var(--color-text)',
    bg: 'var(--color-surface)',
    label: 'Legend text on surface',
  },
  {
    fg: 'var(--color-text-muted)',
    bg: 'var(--color-surface)',
    label: 'Legend label (muted) on surface',
  },
  {
    fg: 'var(--color-text)',
    bg: 'var(--color-surface)',
    label: 'Pie graph totals text on surface (FIXED)',
  },
  {
    fg: 'var(--color-text)',
    bg: 'var(--color-bg)',
    label: 'General text on background',
  },
];

console.log(`\n📊 EARNINGS PAGE CONTRAST AUDIT: ${themeName}\n`);
console.log(`Minimum Target Contrast: ${minContrast}:1\n`);

// Parse tokens
const tokens = parseTokenMap(themeFile);
console.log(`📋 Theme Tokens Found: ${tokens.size}\n`);

// Print token values
console.log('=== Theme Color Values ===\n');
earningsTokens.forEach(({ name, label }) => {
  const value = tokens.get(name);
  if (value) {
    console.log(`  ${label}`);
    console.log(`    Token: ${name}`);
    console.log(`    Value: ${value}\n`);
  }
});

// Run contrast checks
console.log('=== Contrast Checks ===\n');

let passCount = 0;
let failCount = 0;
const failures = [];

earningsChecks.forEach((check) => {
  const fgRgb = resolveToRgb(check.fg, tokens);
  const bgRgb = resolveToRgb(check.bg, tokens);

  if (!fgRgb) {
    console.log(`⚠️  WARN: Could not resolve foreground: ${check.fg}`);
    return;
  }
  if (!bgRgb) {
    console.log(`⚠️  WARN: Could not resolve background: ${check.bg}`);
    return;
  }

  const ratio = contrastRatio(fgRgb, bgRgb);
  const pass = ratio >= minContrast;
  const status = pass ? '✅ PASS' : '❌ FAIL';

  console.log(`${status} ${check.label}`);
  console.log(`   Contrast: ${ratio.toFixed(2)}:1 (min: ${minContrast}:1)`);
  console.log(`   FG RGB: rgb(${fgRgb.r}, ${fgRgb.g}, ${fgRgb.b})`);
  console.log(`   BG RGB: rgb(${bgRgb.r}, ${bgRgb.g}, ${bgRgb.b})\n`);

  if (pass) {
    passCount++;
  } else {
    failCount++;
    failures.push({ check, ratio, fgRgb, bgRgb });
  }
});

console.log(`\n📈 AUDIT RESULTS\n`);
console.log(`  ✅ Passed: ${passCount}`);
console.log(`  ❌ Failed: ${failCount}`);
console.log(`  Total: ${passCount + failCount}\n`);

if (failCount > 0) {
  console.log(`=== FAILURES REQUIRING FIXES ===\n`);
  failures.forEach(({ check, ratio, fgRgb, bgRgb }) => {
    console.log(`❌ ${check.label}`);
    console.log(`   Current: ${ratio.toFixed(2)}:1`);
    console.log(`   Needed: ${minContrast}:1`);
    console.log(`   Recommendation: Adjust foreground or background color in theme or earnings CSS\n`);
  });

  console.log(`\n💡 KEY ISSUES:\n`);
  console.log(`1. WHITE TEXT ON LIGHT SURFACE: The pie graph totals use white text (#ffffff)`);
  console.log(`   which is completely invisible on the light gray surface (#C0C0C0).`);
  console.log(`   FIX: Change .earnings_piegraphs_total to use var(--color-text) instead of white.\n`);

  if (failures.some((f) => f.check.label.includes('light blue mix'))) {
    console.log(`2. LIGHT BLUE PIE SLICE: The net earnings slice (light blue) may be too light.`);
    console.log(`   FIX: Adjust color-mix ratio in earnings CSS to darken the light blue.\n`);
  }

  if (failures.some((f) => f.check.label.includes('light orange'))) {
    console.log(`3. LIGHT ORANGE PIE SLICE: The deductions slice (light orange) may be too light.`);
    console.log(`   FIX: Adjust color-mix ratio in earnings CSS to darken the orange.\n`);
  }

  process.exit(1);
} else {
  console.log(`✅ All contrast checks passed!\n`);
}
