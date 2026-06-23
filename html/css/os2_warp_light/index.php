<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* OS/2 WARP LIGHT */
:root {
  --color-bg: #f2f6ff;
  --color-surface: #ffffff;
  --color-surface-muted: #e3ebfb;
  --color-surface-strong: #cfdbf2;
  --input-bg: #ffffff;
  --color-text: #11182a;
  --color-text-muted: #293957;
  --color-text-disabled: #11182a;
  --color-primary: #284f91;
  --color-primary-hover: #1f3f76;
  --color-primary-active: #183360;
  --color-primary-soft: rgba(40, 79, 145, 0.14);
  --color-on-primary: #ffffff;
  --color-border: #11182a;
  --color-border-soft: #7484a3;
  --color-focus-ring: #284f91;
  --button-bg: #cfdbf2;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #11182a;
  --button-text-hover: #11182a;
  --button-text-active: #11182a;
  --button-border: #647492;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: #ffffff;
  --button-primary-text-hover: #ffffff;
  --button-primary-text-active: #ffffff;
  --panel-bg: #ffffff;
  --panel-text: var(--color-text);
  --panel-border: #647492;
  --panel-head-bg: #e3ebfb;
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
