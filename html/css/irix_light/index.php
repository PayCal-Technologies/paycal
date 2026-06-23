<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* IRIX LIGHT */
:root {
  --color-bg: #f3f8fb;
  --color-surface: #ffffff;
  --color-surface-muted: #e4eef4;
  --color-surface-strong: #d2e1ea;
  --input-bg: #ffffff;
  --color-text: #101b24;
  --color-text-muted: #283d4d;
  --color-text-disabled: #101b24;
  --color-primary: #006b68;
  --color-primary-hover: #005551;
  --color-primary-active: #00423f;
  --color-primary-soft: rgba(0, 107, 104, 0.13);
  --color-on-primary: #ffffff;
  --color-border: #101b24;
  --color-border-soft: #718995;
  --color-focus-ring: #006b68;
  --button-bg: #d2e1ea;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #101b24;
  --button-text-hover: #101b24;
  --button-text-active: #101b24;
  --button-border: #607a87;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: #ffffff;
  --button-primary-text-hover: #ffffff;
  --button-primary-text-active: #ffffff;
  --panel-bg: #ffffff;
  --panel-text: var(--color-text);
  --panel-border: #607a87;
  --panel-head-bg: #e4eef4;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #ffffff;
  --dialog-text: var(--color-text);
  --calendar-day-bg: #ffffff;
  --work-entry-back: #ffffff;
  --work-entry-fore: var(--color-text);
  --btn-selected-back: var(--color-primary);
  --btn-selected-fore: #ffffff;
  --border-radius: 7px;
  --radius-button: var(--border-radius);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
}
