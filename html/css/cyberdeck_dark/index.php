<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* CYBERDECK DARK */
:root {
  --color-bg: #0c1118;
  --color-surface: #111820;
  --color-surface-muted: #192330;
  --color-surface-strong: #243244;
  --input-bg: #0c1118;
  --color-text: #eef8ff;
  --color-text-muted: #c7ddeb;
  --color-text-disabled: #eef8ff;
  --color-primary: #ffbf66;
  --color-primary-hover: #ffd18c;
  --color-primary-active: #e59f3f;
  --color-primary-soft: rgba(255, 191, 102, 0.17);
  --color-on-primary: #170d00;
  --color-border: #eef8ff;
  --color-border-soft: #657a8c;
  --color-focus-ring: #ffd18c;
  --button-bg: #243244;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 28%, var(--button-bg));
  --button-text: #ffffff;
  --button-text-hover: #ffffff;
  --button-text-active: #ffffff;
  --button-border: #768b9d;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: var(--color-on-primary);
  --button-primary-text-hover: var(--color-on-primary);
  --button-primary-text-active: var(--color-on-primary);
  --panel-bg: var(--color-surface);
  --panel-text: var(--color-text);
  --panel-border: #768b9d;
  --panel-head-bg: #192330;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #192330;
  --dialog-text: var(--color-text);
  --calendar-day-bg: var(--color-surface);
  --work-entry-back: var(--color-surface);
  --work-entry-fore: var(--color-text);
  --btn-selected-back: var(--color-primary);
  --btn-selected-fore: var(--color-on-primary);
  --border-radius: 6px;
  --radius-button: var(--border-radius);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
}
