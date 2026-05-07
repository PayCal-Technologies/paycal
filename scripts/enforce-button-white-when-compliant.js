#!/usr/bin/env node

import fs from 'fs';
import path from 'path';

const rootDir = process.cwd();
const cssRoot = path.join(rootDir, 'html', 'css');
const minContrastRaw = Number.parseFloat(process.env.PAYCAL_CONTRAST_MIN ?? '4.75');
const minContrast = Number.isFinite(minContrastRaw) ? minContrastRaw : 4.75;
const args = new Set(process.argv.slice(2));
const darkOnly = args.has('--dark-only');
const primaryOnly = args.has('--primary-only');

const buttonPairs = [
  { fg: '--button-text', bg: '--button-bg' },
  { fg: '--button-text-hover', bg: '--button-bg-hover' },
  { fg: '--button-text-active', bg: '--button-bg-active' },
  { fg: '--button-primary-text', bg: '--button-primary-bg' },
  { fg: '--button-primary-text-hover', bg: '--button-primary-bg-hover' },
  { fg: '--button-primary-text-active', bg: '--button-primary-bg-active' },
];

function listThemeFiles() {
  return fs
    .readdirSync(cssRoot, { withFileTypes: true })
    .filter((entry) => entry.isDirectory() && /_(dark|light)$/.test(entry.name))
    .map((entry) => ({
      theme: entry.name,
      filePath: path.join(cssRoot, entry.name, 'index.php'),
    }))
    .filter((entry) => fs.existsSync(entry.filePath))
    .sort((a, b) => a.theme.localeCompare(b.theme));
}

function parseTokenMap(filePath) {
  const source = fs.readFileSync(filePath, 'utf8');
  const map = new Map();
  const tokenRegex = /^\s*(--[a-z0-9-]+)\s*:\s*([^;]+);/gim;
  let match;
  while ((match = tokenRegex.exec(source)) !== null) {
    map.set(match[1].trim(), match[2].trim());
  }
  return map;
}

let baseTokenMap = null;
function getBaseTokenMap() {
  if (baseTokenMap) return baseTokenMap;
  const tokensPath = path.join(cssRoot, 'tokens', 'index.php');
  baseTokenMap = fs.existsSync(tokensPath) ? parseTokenMap(tokensPath) : new Map();
  return baseTokenMap;
}

function mergedTokenMap(themeMap) {
  const base = getBaseTokenMap();
  const merged = new Map(base);
  for (const [k, v] of themeMap) {
    merged.set(k, v);
  }
  return merged;
}

function splitTopLevelComma(input) {
  let depth = 0;
  for (let i = 0; i < input.length; i += 1) {
    const ch = input[i];
    if (ch === '(') depth += 1;
    if (ch === ')') depth = Math.max(0, depth - 1);
    if (ch === ',' && depth === 0) {
      return [input.slice(0, i), input.slice(i + 1)];
    }
  }
  return [input, null];
}

function splitTopLevelCommas(input) {
  let depth = 0;
  const parts = [];
  let start = 0;
  for (let i = 0; i < input.length; i += 1) {
    const ch = input[i];
    if (ch === '(') depth += 1;
    if (ch === ')') depth = Math.max(0, depth - 1);
    if (ch === ',' && depth === 0) {
      parts.push(input.slice(start, i));
      start = i + 1;
    }
  }
  parts.push(input.slice(start));
  return parts;
}

function clamp255(value) {
  if (!Number.isFinite(value)) return 0;
  return Math.max(0, Math.min(255, Math.round(value)));
}

function hexToRgb(hex) {
  const cleaned = hex.replace('#', '').trim();
  if (cleaned.length === 3) {
    return {
      r: parseInt(cleaned[0] + cleaned[0], 16),
      g: parseInt(cleaned[1] + cleaned[1], 16),
      b: parseInt(cleaned[2] + cleaned[2], 16),
    };
  }
  if (cleaned.length === 6 || cleaned.length === 8) {
    return {
      r: parseInt(cleaned.slice(0, 2), 16),
      g: parseInt(cleaned.slice(2, 4), 16),
      b: parseInt(cleaned.slice(4, 6), 16),
    };
  }
  return null;
}

function hslToRgb(hslArgs) {
  const normalized = hslArgs.replace(/\//g, ' ').replace(/,/g, ' ').trim();
  const parts = normalized.split(/\s+/).filter(Boolean);
  if (parts.length < 3) return null;

  const h = Number(parts[0].replace(/deg$/i, ''));
  const s = Number(parts[1].replace(/%$/, '')) / 100;
  const l = Number(parts[2].replace(/%$/, '')) / 100;
  if (!Number.isFinite(h) || !Number.isFinite(s) || !Number.isFinite(l)) return null;

  const hue = ((h % 360) + 360) % 360;
  const chroma = (1 - Math.abs(2 * l - 1)) * s;
  const hPrime = hue / 60;
  const x = chroma * (1 - Math.abs((hPrime % 2) - 1));

  let r1 = 0;
  let g1 = 0;
  let b1 = 0;

  if (hPrime >= 0 && hPrime < 1) {
    r1 = chroma;
    g1 = x;
  } else if (hPrime >= 1 && hPrime < 2) {
    r1 = x;
    g1 = chroma;
  } else if (hPrime >= 2 && hPrime < 3) {
    g1 = chroma;
    b1 = x;
  } else if (hPrime >= 3 && hPrime < 4) {
    g1 = x;
    b1 = chroma;
  } else if (hPrime >= 4 && hPrime < 5) {
    r1 = x;
    b1 = chroma;
  } else {
    r1 = chroma;
    b1 = x;
  }

  const m = l - chroma / 2;
  return {
    r: clamp255((r1 + m) * 255),
    g: clamp255((g1 + m) * 255),
    b: clamp255((b1 + m) * 255),
  };
}

function resolveColor(value, tokenMap, depth = 0) {
  if (!value || depth > 14) return null;
  const trimmed = value.trim();

  if (/^var\(/i.test(trimmed)) {
    const inner = trimmed.replace(/^var\(/i, '').replace(/\)\s*$/, '');
    const [tokenRef, fallback] = splitTopLevelComma(inner);
    const tokenName = tokenRef ? tokenRef.trim() : '';

    if (tokenName.startsWith('--') && tokenMap.has(tokenName)) {
      const resolved = resolveColor(tokenMap.get(tokenName), tokenMap, depth + 1);
      if (resolved) return resolved;
    }
    if (fallback) return resolveColor(fallback, tokenMap, depth + 1);
    return null;
  }

  if (/^#[0-9a-f]{3}$/i.test(trimmed) || /^#[0-9a-f]{6}$/i.test(trimmed) || /^#[0-9a-f]{8}$/i.test(trimmed)) {
    return hexToRgb(trimmed);
  }

  const rgbMatch = trimmed.match(/^rgba?\(([^)]+)\)$/i);
  if (rgbMatch) {
    const parts = rgbMatch[1].split(',').map((p) => p.trim());
    if (parts.length >= 3) {
      return {
        r: clamp255(Number(parts[0])),
        g: clamp255(Number(parts[1])),
        b: clamp255(Number(parts[2])),
      };
    }
  }

  const hslMatch = trimmed.match(/^hsla?\(([^)]+)\)$/i);
  if (hslMatch) {
    return hslToRgb(hslMatch[1]);
  }

  const colorMixMatch = trimmed.match(/^color-mix\(\s*in\s+srgb\s*,(.+)\)$/i);
  if (colorMixMatch) {
    const parts = splitTopLevelCommas(colorMixMatch[1]);
    if (parts.length >= 2) {
      const a = parts[0].trim();
      const b = parts[1].trim();
      const aPct = a.match(/^(.*?)\s+([\d.]+)%\s*$/);
      const bPct = b.match(/^(.*?)\s+([\d.]+)%\s*$/);

      let p1 = aPct ? parseFloat(aPct[2]) / 100 : null;
      let p2 = bPct ? parseFloat(bPct[2]) / 100 : null;
      const c1Raw = aPct ? aPct[1].trim() : a;
      const c2Raw = bPct ? bPct[1].trim() : b;

      if (p1 === null && p2 !== null) p1 = 1 - p2;
      if (p2 === null && p1 !== null) p2 = 1 - p1;
      if (p1 === null && p2 === null) {
        p1 = 0.5;
        p2 = 0.5;
      }

      const c1 = resolveColor(c1Raw, tokenMap, depth + 1);
      const c2 = resolveColor(c2Raw, tokenMap, depth + 1);
      if (c1 && c2) {
        return {
          r: clamp255(c1.r * p1 + c2.r * p2),
          g: clamp255(c1.g * p1 + c2.g * p2),
          b: clamp255(c1.b * p1 + c2.b * p2),
        };
      }
    }
  }

  if (/^white$/i.test(trimmed)) return { r: 255, g: 255, b: 255 };
  if (/^black$/i.test(trimmed)) return { r: 0, g: 0, b: 0 };
  return null;
}

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

function escapeRegex(input) {
  return input.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function setTokenValueInSource(source, token, value) {
  const tokenRegex = new RegExp(`(^\\s*${escapeRegex(token)}\\s*:\\s*)([^;]+)(;)`, 'm');
  if (tokenRegex.test(source)) {
    return source.replace(tokenRegex, `$1${value}$3`);
  }

  const rootStart = source.indexOf(':root');
  if (rootStart === -1) return source;
  const rootOpen = source.indexOf('{', rootStart);
  if (rootOpen === -1) return source;
  const rootClose = source.indexOf('}', rootOpen);
  if (rootClose === -1) return source;

  const insert = `\n  ${token}: ${value};`;
  return `${source.slice(0, rootClose)}${insert}\n${source.slice(rootClose)}`;
}

function isPureWhite(color) {
  return color && color.r === 255 && color.g === 255 && color.b === 255;
}

function main() {
  const white = { r: 255, g: 255, b: 255 };
  const selectedPairs = primaryOnly
    ? buttonPairs.filter((pair) => pair.fg.startsWith('--button-primary-text'))
    : buttonPairs;

  let themesUpdated = 0;
  let tokenUpdates = 0;
  let eligibleNonWhitePairs = 0;
  let whiteAlreadyPairs = 0;
  let whiteIneligiblePairs = 0;
  let unresolvedPairs = 0;

  for (const entry of listThemeFiles()) {
    if (darkOnly && !entry.theme.endsWith('_dark')) {
      continue;
    }

    const originalSource = fs.readFileSync(entry.filePath, 'utf8');
    let source = originalSource;
    const themeMap = parseTokenMap(entry.filePath);
    const merged = mergedTokenMap(themeMap);

    for (const pair of selectedPairs) {
      const fgRaw = merged.get(pair.fg);
      const bgRaw = merged.get(pair.bg);
      const fgColor = fgRaw ? resolveColor(fgRaw, merged) : null;
      const bgColor = bgRaw ? resolveColor(bgRaw, merged) : null;

      if (!fgColor || !bgColor) {
        unresolvedPairs += 1;
        continue;
      }

      const whiteRatio = contrastRatio(white, bgColor);
      if (whiteRatio >= minContrast) {
        if (isPureWhite(fgColor)) {
          whiteAlreadyPairs += 1;
          continue;
        }
        eligibleNonWhitePairs += 1;
        source = setTokenValueInSource(source, pair.fg, '#ffffff');
        themeMap.set(pair.fg, '#ffffff');
        merged.set(pair.fg, '#ffffff');
        tokenUpdates += 1;
      } else {
        whiteIneligiblePairs += 1;
      }
    }

    if (source !== originalSource) {
      fs.writeFileSync(entry.filePath, source, 'utf8');
      themesUpdated += 1;
    }
  }

  console.log(`Min contrast target: ${minContrast.toFixed(2)}:1`);
  console.log(`Mode: ${darkOnly ? 'dark-only ' : 'all-themes '}${primaryOnly ? 'primary-only' : 'all-button-text'}`.trim());
  console.log(`Theme files updated: ${themesUpdated}`);
  console.log(`Button text tokens set to #ffffff: ${tokenUpdates}`);
  console.log(`Pairs already pure white and compliant: ${whiteAlreadyPairs}`);
  console.log(`Pairs newly switched to pure white: ${eligibleNonWhitePairs}`);
  console.log(`Pairs not eligible for white (contrast): ${whiteIneligiblePairs}`);
  console.log(`Pairs unresolved: ${unresolvedPairs}`);
}

main();
