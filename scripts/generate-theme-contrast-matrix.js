#!/usr/bin/env node

import fs from 'fs';
import path from 'path';

const rootDir = process.cwd();
const cssRoot = path.join(rootDir, 'html', 'css');
const outputPath = path.join(rootDir, 'docs', 'WCAG_THEME_CONTRAST_MATRIX.md');
const contrastTarget = Number.parseFloat(process.env.PAYCAL_CONTRAST_MIN ?? '4.75');
const minContrast = Number.isFinite(contrastTarget) ? contrastTarget : 4.75;

const tokenChecks = [
  { key: 'textOnBg', fg: '--color-text', bg: '--color-bg', min: minContrast, label: 'Text on bg' },
  { key: 'textMutedOnBg', fg: '--color-text-muted', bg: '--color-bg', min: minContrast, label: 'Muted text on bg' },
  { key: 'textOnSurface', fg: '--color-text', bg: '--color-surface', min: minContrast, label: 'Text on surface' },
  { key: 'primaryOnBg', fg: '--color-primary', bg: '--color-bg', min: minContrast, label: 'Primary/icon on bg' },
  { key: 'borderOnBg', fg: '--color-border', bg: '--color-bg', min: minContrast, label: 'Border on bg' },
  { key: 'focusOnBg', fg: '--color-focus-ring', bg: '--color-bg', min: minContrast, label: 'Focus ring on bg' },
  { key: 'focusOnSurface', fg: '--color-focus-ring', bg: '--color-surface', min: minContrast, label: 'Focus ring on surface' },
  { key: 'buttonTextOnButtonBg', fg: '--button-text', bg: '--button-bg', min: minContrast, label: 'Button text on button bg' },
  { key: 'buttonTextOnButtonBgHover', fg: '--button-text-hover', bg: '--button-bg-hover', min: minContrast, label: 'Button text on button hover bg' },
  { key: 'buttonTextOnButtonBgActive', fg: '--button-text-active', bg: '--button-bg-active', min: minContrast, label: 'Button text on button active bg' },
  { key: 'primaryButtonTextOnBg', fg: '--button-primary-text', bg: '--button-primary-bg', min: minContrast, label: 'Primary button text on primary bg' },
  { key: 'primaryButtonTextOnHoverBg', fg: '--button-primary-text-hover', bg: '--button-primary-bg-hover', min: minContrast, label: 'Primary button text on primary hover bg' },
  { key: 'primaryButtonTextOnActiveBg', fg: '--button-primary-text-active', bg: '--button-primary-bg-active', min: minContrast, label: 'Primary button text on primary active bg' },
  { key: 'panelTextOnPanelBg', fg: '--panel-text', bg: '--panel-bg', min: minContrast, label: 'Panel text on panel bg' },
  { key: 'dialogTextOnDialogBg', fg: '--dialog-text', bg: '--dialog-bg', min: minContrast, label: 'Dialog text on dialog bg' },
  { key: 'panelHeadTextOnPanelHeadBg', fg: '--panel-head-text', bg: '--panel-head-bg', min: minContrast, label: 'Panel head text on panel head bg' },
  { key: 'workEntryForeOnWorkEntryBack', fg: '--work-entry-fore', bg: '--work-entry-back', min: minContrast, label: 'Work entry text on work entry bg' },
  { key: 'selectedForeOnSelectedBack', fg: '--btn-selected-fore', bg: '--btn-selected-back', min: minContrast, label: 'Selected control text on selected bg' },
  { key: 'disabledInputTextOnInputBg', fg: '--color-text-disabled', bg: '--input-bg', min: minContrast, label: 'Disabled input text on input bg' },
  { key: 'statusInfoTextOnStatusInfoBg', fg: '--status-info-text', bg: '--status-info-bg', min: minContrast, label: 'Status info text on info bg' },
  { key: 'statusSuccessTextOnStatusSuccessBg', fg: '--status-success-text', bg: '--status-success-bg', min: minContrast, label: 'Status success text on success bg' },
  { key: 'statusErrorTextOnStatusErrorBg', fg: '--status-error-text', bg: '--status-error-bg', min: minContrast, label: 'Status error text on error bg' },
  { key: 'statusInfoIconTextOnStatusInfoIconBg', fg: '--status-info-icon-text', bg: '--status-info-icon-bg', min: minContrast, label: 'Status info icon text on info icon bg' },
  { key: 'statusSuccessIconTextOnStatusSuccessIconBg', fg: '--status-success-icon-text', bg: '--status-success-icon-bg', min: minContrast, label: 'Status success icon text on success icon bg' },
  { key: 'statusErrorIconTextOnStatusErrorIconBg', fg: '--status-error-icon-text', bg: '--status-error-icon-bg', min: minContrast, label: 'Status error icon text on error icon bg' },
];

const harmonyChecks = [
  { key: 'surfaceToPanel', a: '--color-surface', b: '--panel-bg', maxDelta: 0.18, label: 'Surface vs panel gulf' },
  { key: 'surfaceToDialog', a: '--color-surface', b: '--dialog-bg', maxDelta: 0.18, label: 'Surface vs dialog gulf' },
  { key: 'surfaceToCalendarDay', a: '--color-surface', b: '--calendar-day-bg', maxDelta: 0.20, label: 'Surface vs calendar day gulf' },
  { key: 'panelToDialog', a: '--panel-bg', b: '--dialog-bg', maxDelta: 0.12, label: 'Panel vs dialog gulf' },
  { key: 'workToCalendarDay', a: 'var(--work-entry-back, var(--panel-bg))', b: '--calendar-day-bg', maxDelta: 0.18, label: 'Work vs calendar day gulf' },
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

let _baseTokenMap = null;

function getBaseTokenMap() {
  if (_baseTokenMap) return _baseTokenMap;
  const tokensPath = path.join(cssRoot, 'tokens', 'index.php');
  _baseTokenMap = fs.existsSync(tokensPath) ? parseTokenMap(tokensPath) : new Map();
  return _baseTokenMap;
}

function mergedTokenMap(themeMap) {
  // Base tokens fill gaps not defined in the theme file.
  const base = getBaseTokenMap();
  const merged = new Map(base);
  for (const [k, v] of themeMap) {
    merged.set(k, v);
  }
  return merged;
}

function resolveTokenReference(reference, tokenMap) {
  if (!reference) return null;

  if (reference.startsWith('--')) {
    const raw = tokenMap.get(reference);
    return raw ? resolveVarExpression(raw, tokenMap) : null;
  }

  return resolveVarExpression(reference, tokenMap);
}

function resolveColorCandidates(value, tokenMap, depth = 0) {
  if (!value || depth > 12) return [];
  const trimmed = value.trim();

  if (/^var\(/i.test(trimmed)) {
    const inner = trimmed.replace(/^var\(/i, '').replace(/\)\s*$/, '');
    const [tokenRef, fallback] = splitTopLevelComma(inner);
    const tokenName = tokenRef ? tokenRef.trim() : '';

    if (tokenName.startsWith('--') && tokenMap.has(tokenName)) {
      const resolved = resolveColorCandidates(tokenMap.get(tokenName), tokenMap, depth + 1);
      if (resolved.length > 0) return resolved;
    }

    if (fallback) {
      return resolveColorCandidates(fallback, tokenMap, depth + 1);
    }

    return [];
  }

  const namedColor = resolveNamedColor(trimmed);
  if (namedColor) {
    return [namedColor];
  }

  if (/^#[0-9a-f]{3}$/i.test(trimmed) || /^#[0-9a-f]{6}$/i.test(trimmed) || /^#[0-9a-f]{8}$/i.test(trimmed)) {
    return [hexToRgb(trimmed)];
  }

  const rgbMatch = trimmed.match(/^rgba?\(([^)]+)\)$/i);
  if (rgbMatch) {
    const parts = rgbMatch[1].split(',').map((p) => p.trim());
    if (parts.length >= 3) {
      const r = clamp255(Number(parts[0]));
      const g = clamp255(Number(parts[1]));
      const b = clamp255(Number(parts[2]));
      return [{ r, g, b }];
    }
  }

  const hslMatch = trimmed.match(/^hsla?\(([^)]+)\)$/i);
  if (hslMatch) {
    const rgb = hslToRgb(hslMatch[1]);
    if (rgb) {
      return [rgb];
    }
  }

  const colorMixMatch = trimmed.match(/^color-mix\(\s*in\s+srgb\s*,(.+)\)$/i);
  if (colorMixMatch) {
    const inner = colorMixMatch[1];
    const parts = splitTopLevelCommas(inner);
    if (parts.length >= 2) {
      const c1Str = parts[0].trim();
      const c2Str = parts[1].trim();
      const pct1Match = c1Str.match(/^(.*?)\s+([\d.]+)%\s*$/);
      const pct2Match = c2Str.match(/^(.*?)\s+([\d.]+)%\s*$/);
      let p1 = pct1Match ? parseFloat(pct1Match[2]) / 100 : null;
      let p2 = pct2Match ? parseFloat(pct2Match[2]) / 100 : null;
      const color1Str = pct1Match ? pct1Match[1].trim() : c1Str;
      const color2Str = pct2Match ? pct2Match[1].trim() : c2Str;
      if (p1 === null && p2 !== null) p1 = 1 - p2;
      if (p2 === null && p1 !== null) p2 = 1 - p1;
      if (p1 === null && p2 === null) { p1 = 0.5; p2 = 0.5; }
      const c1List = resolveColorCandidates(color1Str, tokenMap, depth + 1);
      const c2List = resolveColorCandidates(color2Str, tokenMap, depth + 1);
      if (c1List.length > 0 && c2List.length > 0) {
        return combineColorCandidates(c1List, c2List, (c1, c2) => ({
          r: clamp255(Math.round(c1.r * p1 + c2.r * p2)),
          g: clamp255(Math.round(c1.g * p1 + c2.g * p2)),
          b: clamp255(Math.round(c1.b * p1 + c2.b * p2)),
        }));
      }
    }
  }

  const gradientMatch = trimmed.match(/^(?:linear|radial)-gradient\((.+)\)$/i);
  if (gradientMatch) {
    const parts = splitTopLevelCommas(gradientMatch[1]);
    const stopParts = isGradientPrelude(parts[0]) ? parts.slice(1) : parts;
    const stops = stopParts
      .map((part) => stripGradientStopPosition(part.trim()))
      .flatMap((part) => resolveColorCandidates(part, tokenMap, depth + 1));
    if (stops.length > 0) {
      return stops;
    }
  }

  return [];
}

function resolveVarExpression(value, tokenMap, depth = 0) {
  const candidates = resolveColorCandidates(value, tokenMap, depth);
  return candidates[0] ?? null;
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

function resolveNamedColor(value) {
  switch (value.toLowerCase()) {
    case 'black':
      return { r: 0, g: 0, b: 0 };
    case 'white':
      return { r: 255, g: 255, b: 255 };
    case 'transparent':
      return { r: 255, g: 255, b: 255 };
    default:
      return null;
  }
}

function combineColorCandidates(aList, bList, mapper) {
  const combined = [];
  for (const a of aList) {
    for (const b of bList) {
      combined.push(mapper(a, b));
    }
  }
  return combined;
}

function isGradientPrelude(value) {
  if (!value) return false;
  const trimmed = value.trim().toLowerCase();
  return trimmed.startsWith('to ') || trimmed.endsWith('deg') || trimmed.endsWith('rad') || trimmed.endsWith('turn') || trimmed.endsWith('grad') || trimmed.startsWith('circle') || trimmed.startsWith('ellipse') || trimmed.startsWith('closest-') || trimmed.startsWith('farthest-') || trimmed.startsWith('at ');
}

function stripGradientStopPosition(value) {
  let result = value.trim();
  while (/\s+(?:-?[\d.]+(?:%|px|rem|em|vh|vw|vmin|vmax)|0)\s*$/i.test(result)) {
    result = result.replace(/\s+(?:-?[\d.]+(?:%|px|rem|em|vh|vw|vmin|vmax)|0)\s*$/i, '').trim();
  }
  return result;
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

  const hueRaw = parts[0].replace(/deg$/i, '');
  const satRaw = parts[1].replace(/%$/, '');
  const litRaw = parts[2].replace(/%$/, '');

  const h = Number(hueRaw);
  const s = Number(satRaw) / 100;
  const l = Number(litRaw) / 100;
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

function channelToLinear(channel) {
  const normalized = channel / 255;
  if (normalized <= 0.03928) {
    return normalized / 12.92;
  }
  return ((normalized + 0.055) / 1.055) ** 2.4;
}

function luminance(rgb) {
  return (0.2126 * channelToLinear(rgb.r)) +
    (0.7152 * channelToLinear(rgb.g)) +
    (0.0722 * channelToLinear(rgb.b));
}

function contrastRatio(fg, bg) {
  const l1 = luminance(fg);
  const l2 = luminance(bg);
  const lighter = Math.max(l1, l2);
  const darker = Math.min(l1, l2);
  return (lighter + 0.05) / (darker + 0.05);
}

function formatDelta(delta) {
  return delta.toFixed(3);
}

function formatRatio(ratio) {
  return `${ratio.toFixed(2)}:1`;
}

function evaluateTheme(theme, filePath) {
  const tokenMap = mergedTokenMap(parseTokenMap(filePath));
  const checks = tokenChecks.map((check) => {
    const fgRaw = tokenMap.get(check.fg);
    const bgRaw = tokenMap.get(check.bg);
    const fgCandidates = fgRaw ? resolveColorCandidates(fgRaw, tokenMap) : [];
    const bgCandidates = bgRaw ? resolveColorCandidates(bgRaw, tokenMap) : [];

    if (fgCandidates.length === 0 || bgCandidates.length === 0) {
      return {
        ...check,
        ratio: null,
        status: 'unresolved',
      };
    }

    const ratio = Math.min(...combineColorCandidates(fgCandidates, bgCandidates, (fg, bg) => contrastRatio(fg, bg)));
    return {
      ...check,
      ratio,
      status: ratio >= check.min ? 'pass' : 'fail',
    };
  });

  const harmony = harmonyChecks.map((check) => {
    const a = resolveTokenReference(check.a, tokenMap);
    const b = resolveTokenReference(check.b, tokenMap);

    if (!a || !b) {
      return {
        ...check,
        delta: null,
        status: 'unresolved',
      };
    }

    const delta = Math.abs(luminance(a) - luminance(b));
    return {
      ...check,
      delta,
      status: delta <= check.maxDelta ? 'pass' : 'fail',
    };
  });

  const failed = checks.filter((c) => c.status === 'fail');
  const unresolved = checks.filter((c) => c.status === 'unresolved');
  const failedHarmony = harmony.filter((c) => c.status === 'fail');
  const unresolvedHarmony = harmony.filter((c) => c.status === 'unresolved');
  const overall = failed.length === 0 && unresolved.length === 0 && failedHarmony.length === 0 && unresolvedHarmony.length === 0 ? 'PASS' : 'REVIEW';

  return {
    theme,
    filePath,
    overall,
    checks,
    harmony,
    failed,
    unresolved,
    failedHarmony,
    unresolvedHarmony,
  };
}

function renderMarkdown(results) {
  const now = new Date().toISOString().slice(0, 10);
  const totalChecks = results.length * tokenChecks.length;
  const totalHarmonyChecks = results.length * harmonyChecks.length;
  const failedChecks = results.reduce((sum, result) => sum + result.failed.length, 0);
  const unresolvedChecks = results.reduce((sum, result) => sum + result.unresolved.length, 0);
  const failedHarmonyChecks = results.reduce((sum, result) => sum + result.failedHarmony.length, 0);
  const unresolvedHarmonyChecks = results.reduce((sum, result) => sum + result.unresolvedHarmony.length, 0);
  const passThemes = results.filter((result) => result.overall === 'PASS').length;

  const lines = [];
  lines.push('# WCAG Theme Contrast Matrix');
  lines.push('');
  lines.push(`Generated: ${now}`);
  lines.push('');
  lines.push('Scope: `html/css/*_dark/index.php` and `html/css/*_light/index.php` tokens.');
  lines.push('');
  lines.push('Thresholds:');
  lines.push(`- All contrast checks use minimum \`${minContrast.toFixed(2)}:1\`.`);
  lines.push('');
  lines.push('Summary:');
  lines.push(`- Theme files scanned: ${results.length}`);
  lines.push(`- Themes with full pass: ${passThemes}`);
  lines.push(`- Total checks: ${totalChecks + totalHarmonyChecks}`);
  lines.push(`- Failed checks: ${failedChecks + failedHarmonyChecks}`);
  lines.push(`- Unresolved checks: ${unresolvedChecks + unresolvedHarmonyChecks}`);
  lines.push('');

  lines.push('## Matrix');
  lines.push('');
  lines.push('| Theme | Text/Bg | TextMuted/Bg | Text/Surface | Primary/Bg | Border/Bg | Focus/Bg | Focus/Surface | Btn/Bg | BtnHover | BtnActive | BtnPrimary/Bg | BtnPrimaryHover | BtnPrimaryActive | Panel/Bg | Dialog/Bg | PanelHead | WorkEntry | Selected | DisabledInput | Harmony | Result |');
  lines.push('|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---:|---|');

  for (const result of results) {
    const byKey = Object.fromEntries(result.checks.map((check) => [check.key, check]));
    const worstHarmony = result.harmony.reduce((worst, current) => {
      if (current.delta === null) return worst;
      if (!worst || worst.delta === null || current.delta > worst.delta) return current;
      return worst;
    }, null);
    lines.push(
      `| ${result.theme} | ${formatCell(byKey.textOnBg)} | ${formatCell(byKey.textMutedOnBg)} | ${formatCell(byKey.textOnSurface)} | ${formatCell(byKey.primaryOnBg)} | ${formatCell(byKey.borderOnBg)} | ${formatCell(byKey.focusOnBg)} | ${formatCell(byKey.focusOnSurface)} | ${formatCell(byKey.buttonTextOnButtonBg)} | ${formatCell(byKey.buttonTextOnButtonBgHover)} | ${formatCell(byKey.buttonTextOnButtonBgActive)} | ${formatCell(byKey.primaryButtonTextOnBg)} | ${formatCell(byKey.primaryButtonTextOnHoverBg)} | ${formatCell(byKey.primaryButtonTextOnActiveBg)} | ${formatCell(byKey.panelTextOnPanelBg)} | ${formatCell(byKey.dialogTextOnDialogBg)} | ${formatCell(byKey.panelHeadTextOnPanelHeadBg)} | ${formatCell(byKey.workEntryForeOnWorkEntryBack)} | ${formatCell(byKey.selectedForeOnSelectedBack)} | ${formatCell(byKey.disabledInputTextOnInputBg)} | ${formatHarmonyCell(worstHarmony)} | ${result.overall} |`
    );
  }

  const themesNeedingReview = results.filter((result) => result.failed.length > 0 || result.unresolved.length > 0 || result.failedHarmony.length > 0 || result.unresolvedHarmony.length > 0);
  lines.push('');
  lines.push('## Review Queue');
  lines.push('');

  if (themesNeedingReview.length === 0) {
    lines.push('- No failing or unresolved checks.');
  } else {
    for (const result of themesNeedingReview) {
      lines.push(`### ${result.theme}`);
      lines.push('');
      for (const check of result.failed) {
        lines.push(`- FAIL ${check.label}: ${formatRatio(check.ratio)} (min ${check.min.toFixed(1)}:1)`);
      }
      for (const check of result.unresolved) {
        lines.push(`- UNRESOLVED ${check.label}: token value could not be resolved to a concrete color.`);
      }
      for (const check of result.failedHarmony) {
        lines.push(`- FAIL ${check.label}: ${formatDelta(check.delta)} luminance delta (max ${check.maxDelta.toFixed(3)})`);
      }
      for (const check of result.unresolvedHarmony) {
        lines.push(`- UNRESOLVED ${check.label}: token value could not be resolved to a concrete color.`);
      }
      lines.push('');
    }
  }

  lines.push('## Command');
  lines.push('');
  lines.push('```bash');
  lines.push('npm run test:a11y:contrast');
  lines.push('```');

  return `${lines.join('\n')}\n`;
}

function formatCell(check) {
  if (!check || check.status === 'unresolved' || check.ratio === null) {
    return 'UNRESOLVED';
  }
  const prefix = check.status === 'pass' ? 'PASS' : 'FAIL';
  return `${prefix} ${formatRatio(check.ratio)}`;
}

function formatHarmonyCell(check) {
  if (!check || check.status === 'unresolved' || check.delta === null) {
    return 'UNRESOLVED';
  }
  const prefix = check.status === 'pass' ? 'PASS' : 'FAIL';
  return `${prefix} ${formatDelta(check.delta)}`;
}

function main() {
  const themeFiles = listThemeFiles();
  const results = themeFiles.map((entry) => evaluateTheme(entry.theme, entry.filePath));
  const markdown = renderMarkdown(results);

  fs.writeFileSync(outputPath, markdown, 'utf8');

  const failedChecks = results.reduce((sum, result) => sum + result.failed.length + result.failedHarmony.length, 0);
  const unresolvedChecks = results.reduce((sum, result) => sum + result.unresolved.length + result.unresolvedHarmony.length, 0);

  console.log(`Theme files scanned: ${results.length}`);
  console.log(`Failed checks: ${failedChecks}`);
  console.log(`Unresolved checks: ${unresolvedChecks}`);
  console.log(`Matrix written: ${path.relative(rootDir, outputPath)}`);

  if (process.env.PAYCAL_CONTRAST_STRICT === '1' && failedChecks > 0) {
    process.exitCode = 1;
  }
}

main();
