<?php declare(strict_types=1);
require_once __DIR__.'/../../config.php';
if (headers_sent() === false) { header('Content-type: text/css'); }
?>

/* WORKBENCH DARK */
:root {
  --color-bg: #161d2b;
  --color-surface: #202a3d;
  --color-surface-muted: #2a3650;
  --color-surface-strong: #354363;
  --input-bg: #161d2b;
  --color-text: #eef4ff;
  --color-text-muted: #cad8f1;
  --color-text-disabled: #eef4ff;
  --color-primary: #9fbfff;
  --color-primary-hover: #bdd2ff;
  --color-primary-active: #82a7ee;
  --color-primary-soft: rgba(159, 191, 255, 0.17);
  --color-on-primary: #071120;
  --color-border: #eef4ff;
  --color-border-soft: #7183a4;
  --color-focus-ring: #bdd2ff;
  --button-bg: #354363;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 28%, var(--button-bg));
  --button-text: #ffffff;
  --button-text-hover: #ffffff;
  --button-text-active: #ffffff;
  --button-border: #8192b6;
  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--color-primary-hover);
  --button-primary-bg-active: var(--color-primary-active);
  --button-primary-text: var(--color-on-primary);
  --button-primary-text-hover: var(--color-on-primary);
  --button-primary-text-active: var(--color-on-primary);
  --panel-bg: var(--color-surface);
  --panel-text: var(--color-text);
  --panel-border: #8192b6;
  --panel-head-bg: #2a3650;
  --panel-head-text: #eef4ff;
  --dialog-bg: #2a3650;
  --dialog-text: var(--color-text);
  --calendar-day-bg: var(--color-surface);
  --work-entry-back: var(--color-surface);
  --work-entry-fore: var(--color-text);
  --btn-selected-back: var(--color-primary);
  --btn-selected-fore: var(--color-on-primary);
  --border-radius: 5px;
  --radius-button: var(--border-radius);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
}
