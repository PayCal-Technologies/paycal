<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* AMIGA DARK */
:root {
  --color-bg: #17151d;
  --color-bg-soft: #211d2b;
  --color-bg-elevated: #2a2437;
  --color-bg-overlay: rgba(9, 7, 14, 0.72);
  --color-surface: #211d2b;
  --color-surface-muted: #2a2437;
  --color-surface-strong: #372f47;
  --input-bg: #17151d;
  --color-border: #f2ecff;
  --color-border-soft: #74668c;
  --color-border-strong: #b4a0d5;
  --color-text: #f7f2ff;
  --color-text-muted: #d8cbed;
  --color-text-inverse: #130f1b;
  --color-text-disabled: #ffffff;
  --color-primary: #ff7ab6;
  --color-primary-hover: #ff9bcc;
  --color-primary-active: #e55c9c;
  --color-primary-soft: rgba(255, 122, 182, 0.18);
  --color-on-primary: #180512;
  --color-success: #61e294;
  --color-warning: #ffd166;
  --color-danger: #ff8c8c;
  --color-info: #8ed8ff;
  --color-hover: rgba(255, 122, 182, 0.10);
  --color-active: rgba(255, 122, 182, 0.18);
  --color-focus-ring: #8ed8ff;
  --color-selection: rgba(255, 122, 182, 0.28);
  --color-highlight: rgba(142, 216, 255, 0.18);
  --color-disabled-bg: rgba(255, 255, 255, 0.06);
  --elevation-1-bg: #211d2b;
  --elevation-2-bg: #2a2437;
  --elevation-3-bg: #372f47;
  --overlay-backdrop: rgba(9, 7, 14, 0.64);
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.32);
  --shadow-md: 0 8px 22px rgba(0, 0, 0, 0.44);
  --shadow-lg: 0 18px 44px rgba(0, 0, 0, 0.56);
  --button-bg: #372f47;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 24%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 36%, var(--button-bg));
  --button-text: #ffffff;
  --button-border: #8d79aa;
  --button-border-active: #ff9bcc;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: var(--color-on-primary);
  --button-secondary-bg: #423656;
  --button-secondary-text: #f7f2ff;
  --button-danger-text: #ff8c8c;
  --panel-bg: #211d2b;
  --panel-text: #f7f2ff;
  --panel-border: #8d79aa;
  --panel-head-bg: #2a2437;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #2a2437;
  --dialog-text: #f7f2ff;
  --dialog-border: #8d79aa;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #17151d;
  --calendar-border: #8d79aa;
  --calendar-day-bg: #211d2b;
  --calendar-day-hover: #49335b;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 16%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 24%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(255, 122, 182, 0.16);
  --heading-accent-color: var(--color-primary);
  --theme-signature-color: var(--heading-accent-color);
  --border-size: 1px;
  --border-radius: 6px;
  --radius-button: calc(var(--border-radius) * 2);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
  --radius-dialog: var(--border-radius);
  --radius-cell: var(--border-radius);
  --radius-article: var(--border-radius);
  --work-back: #211d2b;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(255, 122, 182, 0.34);
  --button-text-hover: #ffffff;
}
