<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* OPENSTEP DARK */
:root {
  --color-bg: #17191c;
  --color-surface: #24282d;
  --color-surface-muted: #30363d;
  --color-surface-strong: #3d454e;
  --input-bg: #17191c;
  --color-text: #f4f4f0;
  --color-text-muted: #d3d7db;
  --color-text-disabled: #f4f4f0;
  --color-primary: #b7c9e8;
  --color-primary-hover: #d1def2;
  --color-primary-active: #99afd5;
  --color-primary-soft: rgba(183, 201, 232, 0.17);
  --color-on-primary: #101419;
  --color-border: #f4f4f0;
  --color-border-soft: #77818d;
  --color-focus-ring: #d1def2;
  --button-bg: #3d454e;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 28%, var(--button-bg));
  --button-text: #ffffff;
  --button-text-hover: #ffffff;
  --button-text-active: #ffffff;
  --button-border: #87919e;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: var(--color-on-primary);
  --button-primary-text-hover: var(--color-on-primary);
  --button-primary-text-active: var(--color-on-primary);
  --panel-bg: var(--color-surface);
  --panel-text: var(--color-text);
  --panel-border: #87919e;
  --panel-head-bg: #30363d;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #30363d;
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
