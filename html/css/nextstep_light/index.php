<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* NEXTSTEP LIGHT */
:root {
  --color-bg: #efefef;
  --color-bg-soft: #e2e2e2;
  --color-bg-elevated: #d6d6d6;
  --color-bg-overlay: rgba(0, 0, 0, 0.24);
  --color-surface: #f7f7f7;
  --color-surface-muted: #e7e7e7;
  --color-surface-strong: #d2d2d2;
  --input-bg: #ffffff;
  --color-border: #111111;
  --color-border-soft: #777777;
  --color-border-strong: #444444;
  --color-text: #111111;
  --color-text-muted: #333333;
  --color-text-inverse: #ffffff;
  --color-text-disabled: #111111;
  --color-primary: #9b4b00;
  --color-primary-hover: #713700;
  --color-primary-active: #5a2c00;
  --color-primary-soft: rgba(155, 75, 0, 0.14);
  --color-on-primary: #ffffff;
  --color-success: #176a3a;
  --color-warning: #7a4a00;
  --color-danger: #a91b1b;
  --color-info: #075985;
  --color-hover: rgba(155, 75, 0, 0.08);
  --color-active: rgba(155, 75, 0, 0.14);
  --color-focus-ring: #9b4b00;
  --color-selection: rgba(155, 75, 0, 0.20);
  --color-highlight: rgba(155, 75, 0, 0.12);
  --color-disabled-bg: rgba(17, 17, 17, 0.06);
  --elevation-1-bg: #f7f7f7;
  --elevation-2-bg: #e7e7e7;
  --elevation-3-bg: #d2d2d2;
  --overlay-backdrop: rgba(0, 0, 0, 0.38);
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.12);
  --shadow-md: 0 6px 16px rgba(0, 0, 0, 0.16);
  --shadow-lg: 0 14px 32px rgba(0, 0, 0, 0.20);
  --button-bg: #d6d6d6;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #111111;
  --button-border: #666666;
  --button-border-active: #9b4b00;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: #ffffff;
  --button-secondary-bg: #e2e2e2;
  --button-secondary-text: #333333;
  --button-danger-text: #a91b1b;
  --panel-bg: #f7f7f7;
  --panel-text: #111111;
  --panel-border: #666666;
  --panel-head-bg: #e2e2e2;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #f7f7f7;
  --dialog-text: #111111;
  --dialog-border: #666666;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #efefef;
  --calendar-border: #666666;
  --calendar-day-bg: #ffffff;
  --calendar-day-hover: #ecd7bd;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 12%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 18%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(155, 75, 0, 0.12);
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
  --work-back: #ffffff;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(155, 75, 0, 0.24);
  --button-text-hover: #111111;
}
