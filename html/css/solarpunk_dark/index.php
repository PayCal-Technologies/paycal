<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* SOLARPUNK DARK */
:root {
  --color-bg: #152114;
  --color-surface: #1d2c18;
  --color-surface-muted: #283a20;
  --color-surface-strong: #354b2a;
  --input-bg: #152114;
  --color-text: #f3ffe8;
  --color-text-muted: #d3e7c1;
  --color-text-disabled: #f3ffe8;
  --color-primary: #d8d36a;
  --color-primary-hover: #ece789;
  --color-primary-active: #bdb84c;
  --color-primary-soft: rgba(216, 211, 106, 0.17);
  --color-on-primary: #151500;
  --color-border: #f3ffe8;
  --color-border-soft: #7e916d;
  --color-focus-ring: #ece789;
  --button-bg: #354b2a;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 28%, var(--button-bg));
  --button-text: #ffffff;
  --button-text-hover: #ffffff;
  --button-text-active: #ffffff;
  --button-border: #8d9f79;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: var(--color-on-primary);
  --button-primary-text-hover: var(--color-on-primary);
  --button-primary-text-active: var(--color-on-primary);
  --panel-bg: var(--color-surface);
  --panel-text: var(--color-text);
  --panel-border: #8d9f79;
  --panel-head-bg: #283a20;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #283a20;
  --dialog-text: var(--color-text);
  --calendar-day-bg: var(--color-surface);
  --work-entry-back: var(--color-surface);
  --work-entry-fore: var(--color-text);
  --btn-selected-back: var(--color-primary);
  --btn-selected-fore: var(--color-on-primary);
  --border-radius: 8px;
  --radius-button: calc(var(--border-radius) * 2);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
}
