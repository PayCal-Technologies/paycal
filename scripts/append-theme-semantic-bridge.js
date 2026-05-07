#!/usr/bin/env node

import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const rootDir = path.resolve(__dirname, '..');
const cssDir = path.join(rootDir, 'html', 'css');
const manifestPath = path.join(rootDir, 'docs', 'data-shapes', 'theme-token-converted-themes.txt');

const bridge = `

  /* Semantic/component bridge for full-sweep migration */
  --color-bg:                            var(--body-back);
  --color-bg-soft:                       color-mix(in srgb, var(--body-back) 86%, var(--chrome-back));
  --color-bg-elevated:                   color-mix(in srgb, var(--panel-back, var(--body-back)) 90%, var(--chrome-back));
  --color-bg-overlay:                    rgba(0, 0, 0, 0.40);

  --color-surface:                       var(--panel-back, var(--chrome-back));
  --color-surface-muted:                 color-mix(in srgb, var(--panel-back, var(--chrome-back)) 90%, var(--chrome-back));
  --color-surface-strong:                var(--chrome-back);

  --color-border:                        var(--panel-border-color, var(--btn-border-colors));
  --color-border-soft:                   color-mix(in srgb, var(--panel-border-color, var(--btn-border-colors)) 72%, var(--panel-back, var(--chrome-back)));
  --color-border-strong:                 color-mix(in srgb, var(--panel-border-color, var(--btn-border-colors)) 84%, black);

  --color-text:                          var(--panel-fore, var(--body-fore));
  --color-text-muted:                    var(--footer-fore, var(--chrome-fore));
  --color-text-inverse:                  var(--btn-primary-fore, #FFFFFF);
  --color-text-disabled:                 color-mix(in srgb, var(--panel-fore, var(--body-fore)) 52%, var(--panel-back, var(--chrome-back)));

  --color-primary:                       var(--btn-primary-back, var(--spot-back, var(--spot-fore)));
  --color-primary-hover:                 var(--btn-selected-back, var(--btn-primary-back, var(--spot-back, var(--spot-fore))));
  --color-primary-active:                color-mix(in srgb, var(--btn-primary-back, var(--spot-back, var(--spot-fore))) 82%, black);
  --color-primary-soft:                  color-mix(in srgb, var(--btn-primary-back, var(--spot-back, var(--spot-fore))) 18%, var(--panel-back, var(--chrome-back)));
  --color-on-primary:                    var(--btn-primary-fore, var(--panel-fore, var(--body-fore)));

  --color-success:                       #2E7D32;
  --color-warning:                       #EF6C00;
  --color-danger:                        var(--warning, #C62828);
  --color-info:                          #0288D1;

  --color-hover:                         rgba(127, 127, 127, 0.10);
  --color-active:                        rgba(127, 127, 127, 0.16);
  --color-focus-ring:                    var(--btn-primary-back, var(--spot-back, var(--spot-fore)));
  --color-selection:                     color-mix(in srgb, var(--btn-primary-back, var(--spot-back, var(--spot-fore))) 24%, var(--body-back));
  --color-highlight:                     rgba(255, 232, 122, 0.22);
  --color-disabled-bg:                   rgba(127, 127, 127, 0.08);

  --elevation-1-bg:                      color-mix(in srgb, var(--panel-back, var(--chrome-back)) 92%, var(--chrome-back));
  --elevation-2-bg:                      color-mix(in srgb, var(--panel-back, var(--chrome-back)) 84%, var(--chrome-back));
  --elevation-3-bg:                      color-mix(in srgb, var(--panel-back, var(--chrome-back)) 76%, var(--chrome-back));
  --overlay-backdrop:                    rgba(0, 0, 0, 0.58);

  --shadow-sm:                           var(--cal-day-shadow, 0 1px 2px rgba(0, 0, 0, 0.18));
  --shadow-md:                           0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg:                           0 16px 38px rgba(0, 0, 0, 0.36);

  --button-bg:                           var(--btn-back);
  --button-bg-hover:                     var(--cal-day-hover-back, var(--btn-selected-back, var(--btn-back)));
  --button-bg-active:                    var(--btn-selected-back, var(--btn-primary-back, var(--btn-back)));
  --button-text:                         var(--btn-fore);
  --button-border:                       var(--btn-border-colors);
  --button-border-active:                var(--btn-border-colors-active, var(--btn-border-colors));
  --button-primary-bg:                   var(--btn-primary-back, var(--spot-back, var(--spot-fore)));
  --button-primary-text:                 var(--btn-primary-fore, var(--btn-fore));
  --button-secondary-bg:                 var(--btn-secondary-back, var(--btn-back));
  --button-secondary-text:               var(--btn-secondary-fore, var(--btn-fore));
  --button-danger-text:                  var(--warning, #C62828);

  --panel-bg:                            var(--panel-back, var(--chrome-back));
  --panel-text:                          var(--panel-fore, var(--body-fore));
  --panel-border:                        var(--panel-border-color, var(--btn-border-colors));
  --panel-head-bg:                       var(--panel-head-back, var(--chrome-back));
  --panel-head-text:                     var(--panel-head-fore, var(--panel-fore, var(--body-fore)));

  --dialog-bg:                           var(--dialog-back, var(--panel-back, var(--chrome-back)));
  --dialog-text:                         var(--dialog-fore, var(--panel-fore, var(--body-fore)));
  --dialog-border:                       var(--panel-border-color, var(--btn-border-colors));
  --dialog-shadow:                       var(--dialog-shadow, var(--shadow-md));
  --dialog-overlay:                      var(--overlay-backdrop);

  --calendar-bg:                         var(--body-back);
  --calendar-border:                     var(--panel-border-color, var(--btn-border-colors));
  --calendar-day-bg:                     var(--cal-day-back, var(--panel-back, var(--chrome-back)));
  --calendar-day-hover:                  var(--cal-day-hover-back, var(--btn-selected-back, var(--cal-day-back, var(--panel-back, var(--chrome-back)))));
  --calendar-day-today:                  color-mix(in srgb, var(--btn-primary-back, var(--spot-back, var(--spot-fore))) 12%, var(--cal-day-back, var(--panel-back, var(--chrome-back))));
  --calendar-day-selected:               color-mix(in srgb, var(--btn-primary-back, var(--spot-back, var(--spot-fore))) 22%, var(--cal-day-back, var(--panel-back, var(--chrome-back))));
  --calendar-event-bg:                   var(--color-primary-soft);
  --calendar-event-text:                 var(--work-entry-fore, var(--panel-fore, var(--body-fore)));
  --calendar-range-bg:                   color-mix(in srgb, var(--btn-primary-back, var(--spot-back, var(--spot-fore))) 18%, var(--body-back));`;

function getThemeFiles() {
  return fs.readdirSync(cssDir)
    .filter((entry) => /_(light|dark)$/.test(entry))
    .map((entry) => path.join(cssDir, entry, 'index.php'))
    .filter((filePath) => fs.existsSync(filePath))
    .sort();
}

function updateThemeFile(filePath) {
  const original = fs.readFileSync(filePath, 'utf8');
  if (original.includes('--color-bg:')) {
    return false;
  }

  const updated = original.replace(/\n\}\s*$/, `${bridge}\n}\n`);
  if (updated === original) {
    throw new Error(`Failed to insert semantic bridge into ${filePath}`);
  }

  fs.writeFileSync(filePath, updated);
  return true;
}

function writeManifest(files) {
  const lines = ['# Converted theme files validated by token contract checker.'];
  for (const filePath of files) {
    lines.push(path.relative(rootDir, filePath).replace(/\\/g, '/'));
  }
  fs.writeFileSync(manifestPath, `${lines.join('\n')}\n`);
}

const themeFiles = getThemeFiles();
let updatedCount = 0;
for (const filePath of themeFiles) {
  if (updateThemeFile(filePath)) {
    updatedCount += 1;
  }
}

writeManifest(themeFiles);

console.log(`Theme files discovered: ${themeFiles.length}`);
console.log(`Theme files updated: ${updatedCount}`);
console.log(`Manifest refreshed: ${path.relative(rootDir, manifestPath)}`);