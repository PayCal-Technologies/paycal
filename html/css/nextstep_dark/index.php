<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* NEXTSTEP DARK */
:root {
  --color-bg: #141414;
  --color-bg-soft: #1f1f1f;
  --color-bg-elevated: #292929;
  --color-bg-overlay: rgba(0, 0, 0, 0.72);
  --color-surface: #202020;
  --color-surface-muted: #2a2a2a;
  --color-surface-strong: #383838;
  --input-bg: #151515;
  --color-border: #f4f4f4;
  --color-border-soft: #7c7c7c;
  --color-border-strong: #b8b8b8;
  --color-text: #f4f4f4;
  --color-text-muted: #d2d2d2;
  --color-text-inverse: #111111;
  --color-text-disabled: #ffffff;
  --color-primary: #ffb020;
  --color-primary-hover: #ffc44d;
  --color-primary-active: #d99000;
  --color-primary-soft: rgba(255, 176, 32, 0.18);
  --color-on-primary: #111111;
  --color-success: #88e08f;
  --color-warning: #ffcf66;
  --color-danger: #ff8d8d;
  --color-info: #8fc7ff;
  --color-hover: rgba(255, 176, 32, 0.10);
  --color-active: rgba(255, 176, 32, 0.18);
  --color-focus-ring: #ffc44d;
  --color-selection: rgba(255, 176, 32, 0.28);
  --color-highlight: rgba(255, 196, 77, 0.18);
  --color-disabled-bg: rgba(255, 255, 255, 0.06);
  --elevation-1-bg: #202020;
  --elevation-2-bg: #2a2a2a;
  --elevation-3-bg: #383838;
  --overlay-backdrop: rgba(0, 0, 0, 0.64);
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.34);
  --shadow-md: 0 8px 22px rgba(0, 0, 0, 0.46);
  --shadow-lg: 0 18px 44px rgba(0, 0, 0, 0.58);
  --button-bg: #383838;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 20%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 30%, var(--button-bg));
  --button-text: #ffffff;
  --button-border: #8a8a8a;
  --button-border-active: #ffc44d;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: var(--color-on-primary);
  --button-secondary-bg: #454545;
  --button-secondary-text: #f4f4f4;
  --button-danger-text: #ff8d8d;
  --panel-bg: #202020;
  --panel-text: #f4f4f4;
  --panel-border: #8a8a8a;
  --panel-head-bg: #2a2a2a;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #2a2a2a;
  --dialog-text: #f4f4f4;
  --dialog-border: #8a8a8a;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #141414;
  --calendar-border: #8a8a8a;
  --calendar-day-bg: #202020;
  --calendar-day-hover: #4a3a1e;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 16%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 24%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(255, 176, 32, 0.16);
  --heading-accent-color: var(--color-primary);
  --theme-signature-color: var(--heading-accent-color);
  --border-size: 1px;
  --border-radius: 4px;
  --radius-button: var(--border-radius);
  --radius-control: var(--border-radius);
  --radius-panel: var(--border-radius);
  --radius-dialog: var(--border-radius);
  --radius-cell: var(--border-radius);
  --radius-article: var(--border-radius);
  --work-back: #202020;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(255, 176, 32, 0.30);
  --button-text-hover: #ffffff;
}
