<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* C64 DARK */
:root {
  --color-bg: #101247;
  --color-surface: #171a63;
  --color-surface-muted: #20257a;
  --color-surface-strong: #29308f;
  --input-bg: #101247;
  --color-text: #eef1ff;
  --color-text-muted: #cbd4ff;
  --color-text-disabled: #eef1ff;
  --color-primary: #a8b8ff;
  --color-primary-hover: #c2ccff;
  --color-primary-active: #8fa3ff;
  --color-primary-soft: rgba(168, 184, 255, 0.18);
  --color-on-primary: #070a2b;
  --color-border: #eef1ff;
  --color-border-soft: #6978c7;
  --color-focus-ring: #c2ccff;
  --button-bg: #29308f;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 28%, var(--button-bg));
  --button-text: #ffffff;
  --button-text-hover: #ffffff;
  --button-text-active: #ffffff;
  --button-border: #8190dc;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: var(--color-on-primary);
  --button-primary-text-hover: var(--color-on-primary);
  --button-primary-text-active: var(--color-on-primary);
  --panel-bg: var(--color-surface);
  --panel-text: var(--color-text);
  --panel-border: #8190dc;
  --panel-head-bg: #20257a;
  --panel-head-text: #eef1ff;
  --dialog-bg: #20257a;
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
