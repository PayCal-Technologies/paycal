<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* PALM OS DARK */
:root {
  --color-bg: #151f1a;
  --color-surface: #1d2924;
  --color-surface-muted: #27372f;
  --color-surface-strong: #33473d;
  --input-bg: #151f1a;
  --color-text: #edf8f0;
  --color-text-muted: #cbded0;
  --color-text-disabled: #edf8f0;
  --color-primary: #b7dfad;
  --color-primary-hover: #d0efc8;
  --color-primary-active: #98c98d;
  --color-primary-soft: rgba(183, 223, 173, 0.17);
  --color-on-primary: #071407;
  --color-border: #edf8f0;
  --color-border-soft: #6e8676;
  --color-focus-ring: #d0efc8;
  --button-bg: #33473d;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 28%, var(--button-bg));
  --button-text: #ffffff;
  --button-text-hover: #ffffff;
  --button-text-active: #ffffff;
  --button-border: #7d927f;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: var(--color-on-primary);
  --button-primary-text-hover: var(--color-on-primary);
  --button-primary-text-active: var(--color-on-primary);
  --panel-bg: var(--color-surface);
  --panel-text: var(--color-text);
  --panel-border: #7d927f;
  --panel-head-bg: #27372f;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #27372f;
  --dialog-text: var(--color-text);
  --calendar-day-bg: var(--color-surface);
  --work-entry-back: var(--color-surface);
  --work-entry-fore: var(--color-text);
  --btn-selected-back: var(--color-primary);
  --btn-selected-fore: var(--color-on-primary);
  --border-radius: 4px;
  --radius-button: var(--border-radius);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
}
