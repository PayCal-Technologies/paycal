<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* WINDOWS 11 LIGHT */
:root {
  --color-bg: #f5f8fd;
  --color-bg-soft: #eef4fb;
  --color-bg-elevated: #e4edf8;
  --color-bg-overlay: rgba(10, 22, 40, 0.22);
  --color-surface: #ffffff;
  --color-surface-muted: #eef4fb;
  --color-surface-strong: #dce8f6;
  --input-bg: #ffffff;
  --color-border: #101827;
  --color-border-soft: #8296b1;
  --color-border-strong: #4e6684;
  --color-text: #101827;
  --color-text-muted: #26384f;
  --color-text-inverse: #ffffff;
  --color-text-disabled: #101827;
  --color-primary: #005fb8;
  --color-primary-hover: #004f9a;
  --color-primary-active: #003f7a;
  --color-primary-soft: rgba(0, 95, 184, 0.14);
  --color-on-primary: #ffffff;
  --color-success: #176a3a;
  --color-warning: #8a5200;
  --color-danger: #b91c1c;
  --color-info: #005fb8;
  --color-hover: rgba(0, 95, 184, 0.08);
  --color-active: rgba(0, 95, 184, 0.14);
  --color-focus-ring: #005fb8;
  --color-selection: rgba(0, 95, 184, 0.20);
  --color-highlight: rgba(0, 95, 184, 0.12);
  --color-disabled-bg: rgba(16, 24, 39, 0.06);
  --elevation-1-bg: #ffffff;
  --elevation-2-bg: #eef4fb;
  --elevation-3-bg: #dce8f6;
  --overlay-backdrop: rgba(10, 22, 40, 0.38);
  --shadow-sm: 0 1px 2px rgba(10, 22, 40, 0.10);
  --shadow-md: 0 6px 16px rgba(10, 22, 40, 0.14);
  --shadow-lg: 0 14px 32px rgba(10, 22, 40, 0.18);
  --button-bg: #e4edf8;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #101827;
  --button-border: #667e9d;
  --button-border-active: #005fb8;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: #ffffff;
  --button-secondary-bg: #dce8f6;
  --button-secondary-text: #26384f;
  --button-danger-text: #b91c1c;
  --panel-bg: #ffffff;
  --panel-text: #101827;
  --panel-border: #667e9d;
  --panel-head-bg: #eef4fb;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #ffffff;
  --dialog-text: #101827;
  --dialog-border: #667e9d;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #f5f8fd;
  --calendar-border: #667e9d;
  --calendar-day-bg: #ffffff;
  --calendar-day-hover: #d6e7fa;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 12%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 18%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(0, 95, 184, 0.12);
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
  --work-back: #ffffff;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(0, 95, 184, 0.24);
  --button-text-hover: #101827;
}
