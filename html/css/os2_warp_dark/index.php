<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* OS/2 WARP DARK */
:root {
  --color-bg: #0f1730;
  --color-surface: #151f3e;
  --color-surface-muted: #1d2a52;
  --color-surface-strong: #283866;
  --input-bg: #0f1730;
  --color-text: #f0f5ff;
  --color-text-muted: #ccd9f4;
  --color-text-disabled: #f0f5ff;
  --color-primary: #87b2ff;
  --color-primary-hover: #a7c8ff;
  --color-primary-active: #6597ec;
  --color-primary-soft: rgba(135, 178, 255, 0.17);
  --color-on-primary: #061022;
  --color-border: #f0f5ff;
  --color-border-soft: #647ba9;
  --color-focus-ring: #a7c8ff;
  --button-bg: #283866;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 28%, var(--button-bg));
  --button-text: #ffffff;
  --button-text-hover: #ffffff;
  --button-text-active: #ffffff;
  --button-border: #748abe;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: var(--color-on-primary);
  --button-primary-text-hover: var(--color-on-primary);
  --button-primary-text-active: var(--color-on-primary);
  --panel-bg: var(--color-surface);
  --panel-text: var(--color-text);
  --panel-border: #748abe;
  --panel-head-bg: #1d2a52;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #1d2a52;
  --dialog-text: var(--color-text);
  --calendar-day-bg: var(--color-surface);
  --work-entry-back: var(--color-surface);
  --work-entry-fore: var(--color-text);
  --btn-selected-back: var(--color-primary);
  --btn-selected-fore: var(--color-on-primary);
  --border-radius: 5px;
  --radius-button: var(--border-radius);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
}
