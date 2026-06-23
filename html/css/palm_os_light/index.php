<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* PALM OS LIGHT */
:root {
  --color-bg: #f5faf5;
  --color-surface: #ffffff;
  --color-surface-muted: #e5f0e7;
  --color-surface-strong: #d3e2d6;
  --input-bg: #ffffff;
  --color-text: #111c15;
  --color-text-muted: #2b3f30;
  --color-text-disabled: #111c15;
  --color-primary: #3b653e;
  --color-primary-hover: #2f5132;
  --color-primary-active: #253f27;
  --color-primary-soft: rgba(59, 101, 62, 0.14);
  --color-on-primary: #ffffff;
  --color-border: #111c15;
  --color-border-soft: #748779;
  --color-focus-ring: #3b653e;
  --button-bg: #d3e2d6;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #111c15;
  --button-text-hover: #111c15;
  --button-text-active: #111c15;
  --button-border: #66796a;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: #ffffff;
  --button-primary-text-hover: #ffffff;
  --button-primary-text-active: #ffffff;
  --panel-bg: #ffffff;
  --panel-text: var(--color-text);
  --panel-border: #66796a;
  --panel-head-bg: #e5f0e7;
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
