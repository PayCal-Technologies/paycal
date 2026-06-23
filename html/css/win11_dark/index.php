<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* WINDOWS 11 DARK */
:root {
  --color-bg: #0f1117;
  --color-bg-soft: #151923;
  --color-bg-elevated: #1b2130;
  --color-bg-overlay: rgba(4, 8, 16, 0.70);
  --color-surface: #191f2b;
  --color-surface-muted: #202838;
  --color-surface-strong: #283247;
  --input-bg: #101722;
  --color-border: #d8e6ff;
  --color-border-soft: #5f718d;
  --color-border-strong: #9bb2d2;
  --color-text: #f3f7ff;
  --color-text-muted: #c8d6ea;
  --color-text-inverse: #07111f;
  --color-text-disabled: #ffffff;
  --color-primary: #61cdff;
  --color-primary-hover: #8ddeff;
  --color-primary-active: #38aee6;
  --color-primary-soft: rgba(97, 205, 255, 0.18);
  --color-on-primary: #06111f;
  --color-success: #8ff3b0;
  --color-warning: #ffd166;
  --color-danger: #ff8d8d;
  --color-info: #61cdff;
  --color-hover: rgba(97, 205, 255, 0.10);
  --color-active: rgba(97, 205, 255, 0.18);
  --color-focus-ring: #a8e7ff;
  --color-selection: rgba(97, 205, 255, 0.28);
  --color-highlight: rgba(168, 231, 255, 0.18);
  --color-disabled-bg: rgba(255, 255, 255, 0.06);
  --elevation-1-bg: #151b27;
  --elevation-2-bg: #191f2b;
  --elevation-3-bg: #202838;
  --overlay-backdrop: rgba(4, 8, 16, 0.62);
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.30);
  --shadow-md: 0 8px 22px rgba(0, 0, 0, 0.40);
  --shadow-lg: 0 18px 44px rgba(0, 0, 0, 0.52);
  --button-bg: #202838;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 22%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 34%, var(--button-bg));
  --button-text: #ffffff;
  --button-border: #6d7f99;
  --button-border-active: #a8e7ff;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: var(--color-on-primary);
  --button-secondary-bg: #283247;
  --button-secondary-text: #e8f0ff;
  --button-danger-text: #ff8d8d;
  --panel-bg: #191f2b;
  --panel-text: #f3f7ff;
  --panel-border: #6d7f99;
  --panel-head-bg: #202838;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #202838;
  --dialog-text: #f3f7ff;
  --dialog-border: #6d7f99;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #0f1117;
  --calendar-border: #6d7f99;
  --calendar-day-bg: #151b27;
  --calendar-day-hover: #263854;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 16%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 24%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(97, 205, 255, 0.16);
  --heading-accent-color: var(--color-primary);
  --theme-signature-color: var(--heading-accent-color);
  --border-size: 1px;
  --border-radius: 10px;
  --radius-button: calc(var(--border-radius) * 2);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
  --radius-dialog: var(--border-radius);
  --radius-cell: var(--border-radius);
  --radius-article: var(--border-radius);
  --work-back: #151b27;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(97, 205, 255, 0.34);
  --button-text-hover: #ffffff;
}
