<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* OPENSTEP LIGHT */
:root {
  --color-bg: #f2f3f3;
  --color-surface: #ffffff;
  --color-surface-muted: #e3e5e7;
  --color-surface-strong: #d1d5d9;
  --input-bg: #ffffff;
  --color-text: #14171a;
  --color-text-muted: #323940;
  --color-text-disabled: #14171a;
  --color-primary: #415a7a;
  --color-primary-hover: #324660;
  --color-primary-active: #27384d;
  --color-primary-soft: rgba(65, 90, 122, 0.14);
  --color-on-primary: #ffffff;
  --color-border: #14171a;
  --color-border-soft: #7c858e;
  --color-focus-ring: #415a7a;
  --button-bg: #d1d5d9;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #14171a;
  --button-text-hover: #14171a;
  --button-text-active: #14171a;
  --button-border: #6e7780;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: #ffffff;
  --button-primary-text-hover: #ffffff;
  --button-primary-text-active: #ffffff;
  --panel-bg: #ffffff;
  --panel-text: var(--color-text);
  --panel-border: #6e7780;
  --panel-head-bg: #e3e5e7;
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
