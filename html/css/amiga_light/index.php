<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* AMIGA LIGHT */
:root {
  --color-bg: #fbf8ff;
  --color-bg-soft: #f0e8fb;
  --color-bg-elevated: #e4d6f6;
  --color-bg-overlay: rgba(32, 18, 52, 0.24);
  --color-surface: #ffffff;
  --color-surface-muted: #f0e8fb;
  --color-surface-strong: #e4d6f6;
  --input-bg: #ffffff;
  --color-border: #1f1630;
  --color-border-soft: #8f78ad;
  --color-border-strong: #684d88;
  --color-text: #1f1630;
  --color-text-muted: #3c2d54;
  --color-text-inverse: #ffffff;
  --color-text-disabled: #1f1630;
  --color-primary: #b21869;
  --color-primary-hover: #901253;
  --color-primary-active: #781044;
  --color-primary-soft: rgba(178, 24, 105, 0.13);
  --color-on-primary: #ffffff;
  --color-success: #176a3a;
  --color-warning: #8a5200;
  --color-danger: #a91b1b;
  --color-info: #006c9e;
  --color-hover: rgba(178, 24, 105, 0.08);
  --color-active: rgba(178, 24, 105, 0.14);
  --color-focus-ring: #006c9e;
  --color-selection: rgba(178, 24, 105, 0.20);
  --color-highlight: rgba(0, 108, 158, 0.12);
  --color-disabled-bg: rgba(31, 22, 48, 0.06);
  --elevation-1-bg: #ffffff;
  --elevation-2-bg: #f0e8fb;
  --elevation-3-bg: #e4d6f6;
  --overlay-backdrop: rgba(32, 18, 52, 0.38);
  --shadow-sm: 0 1px 2px rgba(32, 18, 52, 0.10);
  --shadow-md: 0 6px 16px rgba(32, 18, 52, 0.14);
  --shadow-lg: 0 14px 32px rgba(32, 18, 52, 0.18);
  --button-bg: #e4d6f6;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #1f1630;
  --button-border: #80679d;
  --button-border-active: #b21869;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: #ffffff;
  --button-secondary-bg: #eadff8;
  --button-secondary-text: #3c2d54;
  --button-danger-text: #a91b1b;
  --panel-bg: #ffffff;
  --panel-text: #1f1630;
  --panel-border: #80679d;
  --panel-head-bg: #f0e8fb;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #ffffff;
  --dialog-text: #1f1630;
  --dialog-border: #80679d;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #fbf8ff;
  --calendar-border: #80679d;
  --calendar-day-bg: #ffffff;
  --calendar-day-hover: #eadff8;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 12%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 18%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(178, 24, 105, 0.12);
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
  --work-back: #ffffff;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(178, 24, 105, 0.24);
  --button-text-hover: #1f1630;
}
