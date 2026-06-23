<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* VAPORWAVE DARK */
:root {
  --color-bg: #140b24;
  --color-bg-soft: #1f1235;
  --color-bg-elevated: #2a1748;
  --color-bg-overlay: rgba(10, 4, 22, 0.72);
  --color-surface: #1f1235;
  --color-surface-muted: #2a1748;
  --color-surface-strong: #3a2062;
  --input-bg: #140b24;
  --color-border: #fff0ff;
  --color-border-soft: #8069a6;
  --color-border-strong: #c6a9f6;
  --color-text: #fff0ff;
  --color-text-muted: #decdf8;
  --color-text-inverse: #15041d;
  --color-text-disabled: #ffffff;
  --color-primary: #ff6ad5;
  --color-primary-hover: #ff93e2;
  --color-primary-active: #df47b7;
  --color-primary-soft: rgba(255, 106, 213, 0.18);
  --color-on-primary: #190015;
  --color-success: #6fffd2;
  --color-warning: #ffd166;
  --color-danger: #ff8d8d;
  --color-info: #71f4ff;
  --color-hover: rgba(255, 106, 213, 0.10);
  --color-active: rgba(255, 106, 213, 0.18);
  --color-focus-ring: #71f4ff;
  --color-selection: rgba(255, 106, 213, 0.28);
  --color-highlight: rgba(113, 244, 255, 0.18);
  --color-disabled-bg: rgba(255, 255, 255, 0.06);
  --elevation-1-bg: #1f1235;
  --elevation-2-bg: #2a1748;
  --elevation-3-bg: #3a2062;
  --overlay-backdrop: rgba(10, 4, 22, 0.64);
  --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.32);
  --shadow-md: 0 8px 22px rgba(0, 0, 0, 0.44);
  --shadow-lg: 0 18px 44px rgba(0, 0, 0, 0.56);
  --button-bg: #3a2062;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 22%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 34%, var(--button-bg));
  --button-text: #ffffff;
  --button-border: #9377bf;
  --button-border-active: #ff93e2;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: var(--color-on-primary);
  --button-secondary-bg: #492779;
  --button-secondary-text: #fff0ff;
  --button-danger-text: #ff8d8d;
  --panel-bg: #1f1235;
  --panel-text: #fff0ff;
  --panel-border: #9377bf;
  --panel-head-bg: #2a1748;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #2a1748;
  --dialog-text: #fff0ff;
  --dialog-border: #9377bf;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #140b24;
  --calendar-border: #9377bf;
  --calendar-day-bg: #1f1235;
  --calendar-day-hover: #533070;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 16%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 24%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(255, 106, 213, 0.16);
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
  --work-back: #1f1235;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(255, 106, 213, 0.30);
  --button-text-hover: #ffffff;
}
