<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* SOLARPUNK LIGHT */
:root {
  --color-bg: #f8fbef;
  --color-surface: #ffffff;
  --color-surface-muted: #edf4dd;
  --color-surface-strong: #dce9c8;
  --input-bg: #ffffff;
  --color-text: #17200f;
  --color-text-muted: #344626;
  --color-text-disabled: #17200f;
  --color-primary: #596600;
  --color-primary-hover: #465000;
  --color-primary-active: #363e00;
  --color-primary-soft: rgba(89, 102, 0, 0.14);
  --color-on-primary: #ffffff;
  --color-border: #17200f;
  --color-border-soft: #7f8e6e;
  --color-focus-ring: #596600;
  --button-bg: #dce9c8;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #17200f;
  --button-text-hover: #17200f;
  --button-text-active: #17200f;
  --button-border: #6f8060;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: #ffffff;
  --button-primary-text-hover: #ffffff;
  --button-primary-text-active: #ffffff;
  --panel-bg: #ffffff;
  --panel-text: var(--color-text);
  --panel-border: #6f8060;
  --panel-head-bg: #edf4dd;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #ffffff;
  --dialog-text: var(--color-text);
  --calendar-day-bg: #ffffff;
  --work-entry-back: #ffffff;
  --work-entry-fore: var(--color-text);
  --btn-selected-back: var(--color-primary);
  --btn-selected-fore: #ffffff;
  --border-radius: 8px;
  --radius-button: calc(var(--border-radius) * 2);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
}
