<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* CYBERDECK LIGHT */
:root {
  --color-bg: #f6f8fa;
  --color-surface: #ffffff;
  --color-surface-muted: #e7edf2;
  --color-surface-strong: #d6e1ea;
  --input-bg: #ffffff;
  --color-text: #101820;
  --color-text-muted: #293d4c;
  --color-text-disabled: #101820;
  --color-primary: #7a4a00;
  --color-primary-hover: #613a00;
  --color-primary-active: #4d2f00;
  --color-primary-soft: rgba(122, 74, 0, 0.14);
  --color-on-primary: #ffffff;
  --color-border: #101820;
  --color-border-soft: #748795;
  --color-focus-ring: #7a4a00;
  --button-bg: #d6e1ea;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #101820;
  --button-text-hover: #101820;
  --button-text-active: #101820;
  --button-border: #657886;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: #ffffff;
  --button-primary-text-hover: #ffffff;
  --button-primary-text-active: #ffffff;
  --panel-bg: #ffffff;
  --panel-text: var(--color-text);
  --panel-border: #657886;
  --panel-head-bg: #e7edf2;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #ffffff;
  --dialog-text: var(--color-text);
  --calendar-day-bg: #ffffff;
  --work-entry-back: #ffffff;
  --work-entry-fore: var(--color-text);
  --btn-selected-back: var(--color-primary);
  --btn-selected-fore: #ffffff;
  --border-radius: 6px;
  --radius-button: var(--border-radius);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
}
