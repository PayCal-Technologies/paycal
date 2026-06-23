<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* TERMINAL DARK */
:root {
  --color-bg: #050805;
  --color-bg-soft: #0b120b;
  --color-bg-elevated: #101b10;
  --color-bg-overlay: rgba(0, 0, 0, 0.78);
  --color-surface: #0b120b;
  --color-surface-muted: #101b10;
  --color-surface-strong: #172817;
  --input-bg: #050805;
  --color-border: #d8ffd8;
  --color-border-soft: #4f7a4f;
  --color-border-strong: #8fd58f;
  --color-text: #e8ffe8;
  --color-text-muted: #b9eab9;
  --color-text-inverse: #031003;
  --color-text-disabled: #ffffff;
  --color-primary: #63ff63;
  --color-primary-hover: #9cff9c;
  --color-primary-active: #38d838;
  --color-primary-soft: rgba(99, 255, 99, 0.16);
  --color-on-primary: #031003;
  --color-success: #63ff63;
  --color-warning: #e8ff72;
  --color-danger: #ff8d8d;
  --color-info: #9cff9c;
  --color-hover: rgba(99, 255, 99, 0.10);
  --color-active: rgba(99, 255, 99, 0.18);
  --color-focus-ring: #9cff9c;
  --color-selection: rgba(99, 255, 99, 0.28);
  --color-highlight: rgba(156, 255, 156, 0.18);
  --color-disabled-bg: rgba(255, 255, 255, 0.06);
  --elevation-1-bg: #0b120b;
  --elevation-2-bg: #101b10;
  --elevation-3-bg: #172817;
  --overlay-backdrop: rgba(0, 0, 0, 0.70);
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.36);
  --shadow-md: 0 8px 22px rgba(0, 0, 0, 0.50);
  --shadow-lg: 0 18px 44px rgba(0, 0, 0, 0.62);
  --button-bg: #172817;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 22%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 34%, var(--button-bg));
  --button-text: #e8ffe8;
  --button-border: #5f8f5f;
  --button-border-active: #9cff9c;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: var(--color-on-primary);
  --button-secondary-bg: #1e341e;
  --button-secondary-text: #e8ffe8;
  --button-danger-text: #ff8d8d;
  --panel-bg: #0b120b;
  --panel-text: #e8ffe8;
  --panel-border: #5f8f5f;
  --panel-head-bg: #101b10;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #101b10;
  --dialog-text: #e8ffe8;
  --dialog-border: #5f8f5f;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #050805;
  --calendar-border: #5f8f5f;
  --calendar-day-bg: #0b120b;
  --calendar-day-hover: #1d3a1d;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 14%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 22%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(99, 255, 99, 0.14);
  --heading-accent-color: var(--color-primary);
  --theme-signature-color: var(--heading-accent-color);
  --border-size: 1px;
  --border-radius: 3px;
  --radius-button: var(--border-radius);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
  --radius-dialog: var(--border-radius);
  --radius-cell: var(--border-radius);
  --radius-article: var(--border-radius);
  --work-back: #0b120b;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(99, 255, 99, 0.30);
  --button-text-hover: #e8ffe8;
}
