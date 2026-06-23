<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* SOLARIS LIGHT */
:root {
  --color-bg: #fbfaf3;
  --color-bg-soft: #f2efe2;
  --color-bg-elevated: #e6dfc8;
  --color-bg-overlay: rgba(39, 26, 5, 0.24);
  --color-surface: #fffdf6;
  --color-surface-muted: #f2efe2;
  --color-surface-strong: #e6dfc8;
  --input-bg: #fffdf6;
  --color-border: #211b12;
  --color-border-soft: #8e7c55;
  --color-border-strong: #675936;
  --color-text: #211b12;
  --color-text-muted: #40351f;
  --color-text-inverse: #ffffff;
  --color-text-disabled: #211b12;
  --color-primary: #8f4f00;
  --color-primary-hover: #723f00;
  --color-primary-active: #5c3300;
  --color-primary-soft: rgba(143, 79, 0, 0.14);
  --color-on-primary: #ffffff;
  --color-success: #176a3a;
  --color-warning: #7a4a00;
  --color-danger: #a91b1b;
  --color-info: #075985;
  --color-hover: rgba(143, 79, 0, 0.08);
  --color-active: rgba(143, 79, 0, 0.14);
  --color-focus-ring: #8f4f00;
  --color-selection: rgba(143, 79, 0, 0.20);
  --color-highlight: rgba(143, 79, 0, 0.12);
  --color-disabled-bg: rgba(33, 27, 18, 0.06);
  --elevation-1-bg: #fffdf6;
  --elevation-2-bg: #f2efe2;
  --elevation-3-bg: #e6dfc8;
  --overlay-backdrop: rgba(39, 26, 5, 0.38);
  --shadow-sm: 0 1px 2px rgba(39, 26, 5, 0.10);
  --shadow-md: 0 6px 16px rgba(39, 26, 5, 0.14);
  --shadow-lg: 0 14px 32px rgba(39, 26, 5, 0.18);
  --button-bg: #e6dfc8;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #211b12;
  --button-border: #7c6b45;
  --button-border-active: #8f4f00;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: #ffffff;
  --button-secondary-bg: #f2efe2;
  --button-secondary-text: #40351f;
  --button-danger-text: #a91b1b;
  --panel-bg: #fffdf6;
  --panel-text: #211b12;
  --panel-border: #7c6b45;
  --panel-head-bg: #f2efe2;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #fffdf6;
  --dialog-text: #211b12;
  --dialog-border: #7c6b45;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #fbfaf3;
  --calendar-border: #7c6b45;
  --calendar-day-bg: #fffdf6;
  --calendar-day-hover: #eadbb7;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 12%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 18%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(143, 79, 0, 0.12);
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
  --work-back: #fffdf6;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(143, 79, 0, 0.24);
  --button-text-hover: #211b12;
}
