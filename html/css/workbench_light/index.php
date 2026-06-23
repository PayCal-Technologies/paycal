<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* WORKBENCH LIGHT */
:root {
  --color-bg: #f1f5fb;
  --color-surface: #ffffff;
  --color-surface-muted: #e5edf8;
  --color-surface-strong: #d4deef;
  --input-bg: #ffffff;
  --color-text: #121a2a;
  --color-text-muted: #2b3a56;
  --color-text-disabled: #121a2a;
  --color-primary: #31558f;
  --color-primary-hover: #254372;
  --color-primary-active: #1d365e;
  --color-primary-soft: rgba(49, 85, 143, 0.14);
  --color-on-primary: #ffffff;
  --color-border: #121a2a;
  --color-border-soft: #7888a4;
  --color-focus-ring: #31558f;
  --button-bg: #d4deef;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #121a2a;
  --button-text-hover: #121a2a;
  --button-text-active: #121a2a;
  --button-border: #687895;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: #ffffff;
  --button-primary-text-hover: #ffffff;
  --button-primary-text-active: #ffffff;
  --panel-bg: #ffffff;
  --panel-text: var(--color-text);
  --panel-border: #687895;
  --panel-head-bg: #e5edf8;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #ffffff;
  --dialog-text: var(--color-text);
  --calendar-day-bg: #ffffff;
  --work-entry-back: #ffffff;
  --work-entry-fore: var(--color-text);
  --btn-selected-back: var(--color-primary);
  --btn-selected-fore: #ffffff;
  --border-radius: 5px;
  --radius-button: var(--border-radius);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
}
