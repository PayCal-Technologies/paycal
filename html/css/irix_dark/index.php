<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* IRIX DARK */
:root {
  --color-bg: #111827;
  --color-surface: #171f33;
  --color-surface-muted: #202a43;
  --color-surface-strong: #2b3858;
  --input-bg: #111827;
  --color-text: #eef5ff;
  --color-text-muted: #c9d8ec;
  --color-text-disabled: #eef5ff;
  --color-primary: #69d9ca;
  --color-primary-hover: #8ce7dc;
  --color-primary-active: #42bcae;
  --color-primary-soft: rgba(105, 217, 202, 0.17);
  --color-on-primary: #061816;
  --color-border: #eef5ff;
  --color-border-soft: #667c9d;
  --color-focus-ring: #8ce7dc;
  --button-bg: #2b3858;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 28%, var(--button-bg));
  --button-text: #ffffff;
  --button-text-hover: #ffffff;
  --button-text-active: #ffffff;
  --button-border: #788bad;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: var(--color-on-primary);
  --button-primary-text-hover: var(--color-on-primary);
  --button-primary-text-active: var(--color-on-primary);
  --panel-bg: var(--color-surface);
  --panel-text: var(--color-text);
  --panel-border: #788bad;
  --panel-head-bg: #202a43;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #202a43;
  --dialog-text: var(--color-text);
  --calendar-day-bg: var(--color-surface);
  --work-entry-back: var(--color-surface);
  --work-entry-fore: var(--color-text);
  --btn-selected-back: var(--color-primary);
  --btn-selected-fore: var(--color-on-primary);
  --border-radius: 7px;
  --radius-button: var(--border-radius);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
}
