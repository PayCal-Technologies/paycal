#!/usr/bin/env node

import fs from 'fs';
import path from 'path';

const rootDir = process.cwd();
const cssRoot = path.join(rootDir, 'html', 'css');
const minContrastRaw = Number.parseFloat(process.env.PAYCAL_CONTRAST_MIN ?? '4.75');
const minContrast = Number.isFinite(minContrastRaw) ? minContrastRaw : 4.75;

const tokenChecks = [
  { key: 'textOnBg', fg: '--color-text', bg: '--color-bg' },
  { key: 'textMutedOnBg', fg: '--color-text-muted', bg: '--color-bg' },
  { key: 'textOnSurface', fg: '--color-text', bg: '--color-surface' },
  { key: 'primaryOnBg', fg: '--color-primary', bg: '--color-bg' },
  { key: 'borderOnBg', fg: '--color-border', bg: '--color-bg' },
  { key: 'focusOnBg', fg: '--color-focus-ring', bg: '--color-bg' },
  { key: 'focusOnSurface', fg: '--color-focus-ring', bg: '--color-surface' },
  { key: 'buttonTextOnButtonBg', fg: '--button-text', bg: '--button-bg' },
  { key: 'buttonTextOnButtonBgHover', fg: '--button-text-hover', bg: '--button-bg-hover' },
  { key: 'buttonTextOnButtonBgActive', fg: '--button-text-active', bg: '--button-bg-active' },
  { key: 'primaryButtonTextOnBg', fg: '--button-primary-text', bg: '--button-primary-bg' },
  { key: 'panelTextOnPanelBg', fg: '--panel-text', bg: '--panel-bg' },
  { key: 'dialogTextOnDialogBg', fg: '--dialog-text', bg: '--dialog-bg' },
  { key: 'panelHeadTextOnPanelHeadBg', fg: '--panel-head-text', bg: '--panel-head-bg' },
  { key: 'workEntryForeOnWorkEntryBack', fg: '--work-entry-fore', bg: '--work-entry-back' },
  { key: 'selectedForeOnSelectedBack', fg: '--btn-selected-fore', bg: '--btn-selected-back' },
  { key: 'disabledInputTextOnInputBg', fg: '--color-text-disabled', bg: '--input-bg' },
];

const textFirstFgTokens = new Set([
  '--color-primary',
  '--color-border',
  '--color-focus-ring',
  '--color-text',
  '--color-text-muted',
  '--color-text-disabled',
  '--button-text',
  '--button-text-hover',
  '--button-text-active',
  '--button-primary-text',
  '--button-primary-text-hover',
  '--button-primary-text-active',
  '--panel-text',
  '--dialog-text',
  '--panel-head-text',
  '--work-entry-fore',
  '--btn-selected-fore',
]);

const backgroundLastResortTokens = new Set([
  '--button-bg',
  '--button-bg-hover',
  '--button-bg-active',
  '--button-primary-bg',
  '--btn-selected-back',
  '--panel-bg',
  '--dialog-bg',
  '--panel-head-bg',
  '--work-entry-back',
  '--input-bg',
]);

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
  for (const [k, v] of themeMap) merged.set(k, v);
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
    r1 = chroma; g1 = x;
  } else if (hPrime >= 1 && hPrime < 2) {
    r1 = x; g1 = chroma;
  } else if (hPrime >= 2 && hPrime < 3) {
    g1 = chroma; b1 = x;
  } else if (hPrime >= 3 && hPrime < 4) {
    g1 = x; b1 = chroma;
  } else if (hPrime >= 4 && hPrime < 5) {
    r1 = x; b1 = chroma;
  } else {
    r1 = chroma; b1 = x;
  }

  const m = l - chroma / 2;
  return {
    r: clamp255((r1 + m) * 255),
    g: clamp255((g1 + m) * 255),
    b: clamp255((b1 + m) * 255),
  };
}

function resolveVarExpression(value, tokenMap, depth = 0) {
  if (!value || depth > 14) return null;
  const trimmed = value.trim();

  if (/^var\(/i.test(trimmed)) {
    const inner = trimmed.replace(/^var\(/i, '').replace(/\)\s*$/, '');
    const [tokenRef, fallback] = splitTopLevelComma(inner);
    const tokenName = tokenRef ? tokenRef.trim() : '';

    if (tokenName.startsWith('--') && tokenMap.has(tokenName)) {
      const resolved = resolveVarExpression(tokenMap.get(tokenName), tokenMap, depth + 1);
      if (resolved) return resolved;
    }
    if (fallback) return resolveVarExpression(fallback, tokenMap, depth + 1);
    return null;
  }

  if (/^#[0-9a-f]{3}$/i.test(trimmed) || /^#[0-9a-f]{6}$/i.test(trimmed) || /^#[0-9a-f]{8}$/i.test(trimmed)) {
    return hexToRgb(trimmed);
  }

  const rgbMatch = trimmed.match(/^rgba?\(([^)]+)\)$/i);
  if (rgbMatch) {
    const parts = rgbMatch[1].split(',').map((p) => p.trim());
    if (parts.length >= 3) {
      return { r: clamp255(Number(parts[0])), g: clamp255(Number(parts[1])), b: clamp255(Number(parts[2])) };
    }
  }

  const hslMatch = trimmed.match(/^hsla?\(([^)]+)\)$/i);
  if (hslMatch) return hslToRgb(hslMatch[1]);

  const mixMatch = trimmed.match(/^color-mix\(\s*in\s+srgb\s*,(.+)\)$/i);
  if (mixMatch) {
    const inner = mixMatch[1];
    const parts = splitTopLevelComma(inner);
    if (parts[0] && parts[1]) {
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
      if (p1 === null && p2 === null) { p1 = 0.5; p2 = 0.5; }
      const c1 = resolveVarExpression(c1Raw, tokenMap, depth + 1);
      const c2 = resolveVarExpression(c2Raw, tokenMap, depth + 1);
      if (c1 && c2) {
        return {
          r: clamp255(c1.r * p1 + c2.r * p2),
          g: clamp255(c1.g * p1 + c2.g * p2),
          b: clamp255(c1.b * p1 + c2.b * p2),
        };
      }
    }
  }

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

function evaluateChecks(tokenMap) {
  return tokenChecks.map((check) => {
    const fgRaw = tokenMap.get(check.fg);
    const bgRaw = tokenMap.get(check.bg);
    const fg = fgRaw ? resolveVarExpression(fgRaw, tokenMap) : null;
    const bg = bgRaw ? resolveVarExpression(bgRaw, tokenMap) : null;
    if (!fg || !bg) return { ...check, status: 'unresolved', ratio: null, fgColor: null, bgColor: null };
    const ratio = contrastRatio(fg, bg);
    return { ...check, status: ratio >= minContrast ? 'pass' : 'fail', ratio, fgColor: fg, bgColor: bg };
  });
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

function rgbToHex(rgb) {
  const h = (value) => clamp255(value).toString(16).padStart(2, '0');
  return `#${h(rgb.r)}${h(rgb.g)}${h(rgb.b)}`;
}

function chooseBestTextColor(backgrounds) {
  const white = { r: 255, g: 255, b: 255 };
  const black = { r: 0, g: 0, b: 0 };
  let whiteMin = Number.POSITIVE_INFINITY;
  let blackMin = Number.POSITIVE_INFINITY;
  for (const bg of backgrounds) {
    whiteMin = Math.min(whiteMin, contrastRatio(white, bg));
    blackMin = Math.min(blackMin, contrastRatio(black, bg));
  }
  return whiteMin >= blackMin ? white : black;
}

function mixColor(a, b, t) {
  return {
    r: clamp255(a.r * (1 - t) + b.r * t),
    g: clamp255(a.g * (1 - t) + b.g * t),
    b: clamp255(a.b * (1 - t) + b.b * t),
  };
}

function tuneBackgroundForContrast(fg, bg) {
  const fgLum = luminance(fg);
  const bgLum = luminance(bg);
  const target = fgLum > bgLum ? { r: 0, g: 0, b: 0 } : { r: 255, g: 255, b: 255 };

  const current = contrastRatio(fg, bg);
  if (current >= minContrast) return bg;
  const extreme = contrastRatio(fg, target);
  if (extreme < minContrast) return target;

  let low = 0;
  let high = 1;
  let best = target;
  for (let i = 0; i < 18; i += 1) {
    const mid = (low + high) / 2;
    const mixed = mixColor(bg, target, mid);
    const ratio = contrastRatio(fg, mixed);
    if (ratio >= minContrast) {
      best = mixed;
      high = mid;
    } else {
      low = mid;
    }
  }
  return best;
}

function main() {
  const themes = listThemeFiles();
  let changedFiles = 0;
  let changedTokens = 0;

  for (const entry of themes) {
    let source = fs.readFileSync(entry.filePath, 'utf8');
    const themeMap = parseTokenMap(entry.filePath);
    let merged = mergedTokenMap(themeMap);

    let checks = evaluateChecks(merged);
    const fgGroups = new Map();

    for (const check of checks) {
      if (check.status !== 'fail' || !check.bgColor) continue;
      if (!textFirstFgTokens.has(check.fg)) continue;
      const arr = fgGroups.get(check.fg) ?? [];
      arr.push(check.bgColor);
      fgGroups.set(check.fg, arr);
    }

    for (const [fgToken, backgrounds] of fgGroups) {
      const best = chooseBestTextColor(backgrounds);
      const hex = rgbToHex(best);
      const currentResolved = merged.get(fgToken) ? resolveVarExpression(merged.get(fgToken), merged) : null;
      if (!currentResolved || contrastRatio(best, backgrounds[0]) !== contrastRatio(currentResolved, backgrounds[0])) {
        source = setTokenValueInSource(source, fgToken, hex);
        themeMap.set(fgToken, hex);
        merged.set(fgToken, hex);
        changedTokens += 1;
      }
    }

    checks = evaluateChecks(merged);

    for (const check of checks) {
      if (check.status !== 'fail' || !check.fgColor || !check.bgColor) continue;
      if (!textFirstFgTokens.has(check.fg)) continue;
      if (!backgroundLastResortTokens.has(check.bg)) continue;

      const tunedBg = tuneBackgroundForContrast(check.fgColor, check.bgColor);
      const hex = rgbToHex(tunedBg);
      source = setTokenValueInSource(source, check.bg, hex);
      themeMap.set(check.bg, hex);
      merged.set(check.bg, hex);
      changedTokens += 1;
    }

    const original = fs.readFileSync(entry.filePath, 'utf8');
    if (source !== original) {
      fs.writeFileSync(entry.filePath, source, 'utf8');
      changedFiles += 1;
    }
  }

  console.log(`Min target: ${minContrast.toFixed(2)}:1`);
  console.log(`Theme files updated: ${changedFiles}`);
  console.log(`Token values updated: ${changedTokens}`);
}

main();
