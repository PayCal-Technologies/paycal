<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* C64 LIGHT */
:root {
  --color-bg: #f2f4ff;
  --color-surface: #ffffff;
  --color-surface-muted: #e2e7ff;
  --color-surface-strong: #cbd5ff;
  --input-bg: #ffffff;
  --color-text: #11143b;
  --color-text-muted: #29306b;
  --color-text-disabled: #11143b;
  --color-primary: #263a9a;
  --color-primary-hover: #1b2e7f;
  --color-primary-active: #152465;
  --color-primary-soft: rgba(38, 58, 154, 0.14);
  --color-on-primary: #ffffff;
  --color-border: #11143b;
  --color-border-soft: #7782c2;
  --color-focus-ring: #263a9a;
  --button-bg: #cbd5ff;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #11143b;
  --button-text-hover: #11143b;
  --button-text-active: #11143b;
  --button-border: #6672b4;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: #ffffff;
  --button-primary-text-hover: #ffffff;
  --button-primary-text-active: #ffffff;
  --panel-bg: #ffffff;
  --panel-text: var(--color-text);
  --panel-border: #6672b4;
  --panel-head-bg: #e2e7ff;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #ffffff;
  --dialog-text: var(--color-text);
  --calendar-day-bg: #ffffff;
  --work-entry-back: #ffffff;
  --work-entry-fore: var(--color-text);
  --btn-selected-back: var(--color-primary);
  --btn-selected-fore: #ffffff;
  --border-radius: 4px;
  --radius-button: var(--border-radius);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
}
