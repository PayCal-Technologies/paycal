<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* VAPORWAVE LIGHT */
:root {
  --color-bg: #fff7fd;
  --color-bg-soft: #f9e8f7;
  --color-bg-elevated: #edd8f8;
  --color-bg-overlay: rgba(40, 10, 58, 0.24);
  --color-surface: #ffffff;
  --color-surface-muted: #f9e8f7;
  --color-surface-strong: #edd8f8;
  --input-bg: #ffffff;
  --color-border: #24112d;
  --color-border-soft: #9475a7;
  --color-border-strong: #6b4b7d;
  --color-text: #24112d;
  --color-text-muted: #412750;
  --color-text-inverse: #ffffff;
  --color-text-disabled: #24112d;
  --color-primary: #a3137d;
  --color-primary-hover: #841064;
  --color-primary-active: #690c50;
  --color-primary-soft: rgba(163, 19, 125, 0.13);
  --color-on-primary: #ffffff;
  --color-success: #176a3a;
  --color-warning: #7a4a00;
  --color-danger: #a91b1b;
  --color-info: #006c9e;
  --color-hover: rgba(163, 19, 125, 0.08);
  --color-active: rgba(163, 19, 125, 0.14);
  --color-focus-ring: #006c9e;
  --color-selection: rgba(163, 19, 125, 0.20);
  --color-highlight: rgba(0, 108, 158, 0.12);
  --color-disabled-bg: rgba(36, 17, 45, 0.06);
  --elevation-1-bg: #ffffff;
  --elevation-2-bg: #f9e8f7;
  --elevation-3-bg: #edd8f8;
  --overlay-backdrop: rgba(40, 10, 58, 0.38);
  --shadow-sm: 0 1px 2px rgba(40, 10, 58, 0.10);
  --shadow-md: 0 6px 16px rgba(40, 10, 58, 0.14);
  --shadow-lg: 0 14px 32px rgba(40, 10, 58, 0.18);
  --button-bg: #edd8f8;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #24112d;
  --button-border: #84639a;
  --button-border-active: #a3137d;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: #ffffff;
  --button-secondary-bg: #f9e8f7;
  --button-secondary-text: #412750;
  --button-danger-text: #a91b1b;
  --panel-bg: #ffffff;
  --panel-text: #24112d;
  --panel-border: #84639a;
  --panel-head-bg: #f9e8f7;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #ffffff;
  --dialog-text: #24112d;
  --dialog-border: #84639a;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #fff7fd;
  --calendar-border: #84639a;
  --calendar-day-bg: #ffffff;
  --calendar-day-hover: #f3d7f0;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 12%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 18%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(163, 19, 125, 0.12);
  --heading-accent-color: var(--color-primary);
  --theme-signature-color: var(--heading-accent-color);
  --border-size: 1px;
  --border-radius: 8px;
  --radius-button: calc(var(--border-radius) * 2);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
  --radius-dialog: var(--border-radius);
  --radius-cell: var(--border-radius);
  --radius-article: var(--border-radius);
  --work-back: #ffffff;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(163, 19, 125, 0.24);
  --button-text-hover: #24112d;
}
