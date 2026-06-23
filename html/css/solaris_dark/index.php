<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* SOLARIS DARK */
:root {
  --color-bg: #101716;
  --color-bg-soft: #172220;
  --color-bg-elevated: #20302d;
  --color-bg-overlay: rgba(3, 12, 10, 0.72);
  --color-surface: #172220;
  --color-surface-muted: #20302d;
  --color-surface-strong: #2a403b;
  --input-bg: #101716;
  --color-border: #eefcf8;
  --color-border-soft: #68827b;
  --color-border-strong: #a1c7bd;
  --color-text: #eefcf8;
  --color-text-muted: #c9e3dd;
  --color-text-inverse: #071513;
  --color-text-disabled: #ffffff;
  --color-primary: #ffb84d;
  --color-primary-hover: #ffd17c;
  --color-primary-active: #dd9227;
  --color-primary-soft: rgba(255, 184, 77, 0.18);
  --color-on-primary: #1a0f00;
  --color-success: #8ce8ae;
  --color-warning: #ffd17c;
  --color-danger: #ff8d8d;
  --color-info: #83d8ff;
  --color-hover: rgba(255, 184, 77, 0.10);
  --color-active: rgba(255, 184, 77, 0.18);
  --color-focus-ring: #ffd17c;
  --color-selection: rgba(255, 184, 77, 0.28);
  --color-highlight: rgba(255, 209, 124, 0.18);
  --color-disabled-bg: rgba(255, 255, 255, 0.06);
  --elevation-1-bg: #172220;
  --elevation-2-bg: #20302d;
  --elevation-3-bg: #2a403b;
  --overlay-backdrop: rgba(3, 12, 10, 0.64);
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.30);
  --shadow-md: 0 8px 22px rgba(0, 0, 0, 0.42);
  --shadow-lg: 0 18px 44px rgba(0, 0, 0, 0.54);
  --button-bg: #2a403b;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 22%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 34%, var(--button-bg));
  --button-text: #ffffff;
  --button-border: #78948c;
  --button-border-active: #ffd17c;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: var(--color-on-primary);
  --button-secondary-bg: #345047;
  --button-secondary-text: #eefcf8;
  --button-danger-text: #ff8d8d;
  --panel-bg: #172220;
  --panel-text: #eefcf8;
  --panel-border: #78948c;
  --panel-head-bg: #20302d;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #20302d;
  --dialog-text: #eefcf8;
  --dialog-border: #78948c;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #101716;
  --calendar-border: #78948c;
  --calendar-day-bg: #172220;
  --calendar-day-hover: #423823;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 16%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 24%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(255, 184, 77, 0.16);
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
  --work-back: #172220;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(255, 184, 77, 0.30);
  --button-text-hover: #ffffff;
}
