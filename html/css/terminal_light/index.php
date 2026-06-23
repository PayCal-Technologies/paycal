<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}

?>

/* TERMINAL LIGHT */
:root {
  --color-bg: #f6fff6;
  --color-bg-soft: #e8f6e8;
  --color-bg-elevated: #d8ead8;
  --color-bg-overlay: rgba(8, 28, 8, 0.22);
  --color-surface: #fbfffb;
  --color-surface-muted: #e8f6e8;
  --color-surface-strong: #d8ead8;
  --input-bg: #fbfffb;
  --color-border: #071407;
  --color-border-soft: #638263;
  --color-border-strong: #355f35;
  --color-text: #071407;
  --color-text-muted: #183318;
  --color-text-inverse: #ffffff;
  --color-text-disabled: #071407;
  --color-primary: #0f6b0f;
  --color-primary-hover: #0b520b;
  --color-primary-active: #083f08;
  --color-primary-soft: rgba(15, 107, 15, 0.13);
  --color-on-primary: #ffffff;
  --color-success: #0f6b0f;
  --color-warning: #756900;
  --color-danger: #a91b1b;
  --color-info: #075985;
  --color-hover: rgba(15, 107, 15, 0.08);
  --color-active: rgba(15, 107, 15, 0.14);
  --color-focus-ring: #0f6b0f;
  --color-selection: rgba(15, 107, 15, 0.20);
  --color-highlight: rgba(15, 107, 15, 0.12);
  --color-disabled-bg: rgba(7, 20, 7, 0.06);
  --elevation-1-bg: #fbfffb;
  --elevation-2-bg: #e8f6e8;
  --elevation-3-bg: #d8ead8;
  --overlay-backdrop: rgba(8, 28, 8, 0.38);
  --shadow-sm: 0 1px 2px rgba(8, 28, 8, 0.10);
  --shadow-md: 0 6px 16px rgba(8, 28, 8, 0.14);
  --shadow-lg: 0 14px 32px rgba(8, 28, 8, 0.18);
  --button-bg: #d8ead8;
  --button-bg-hover: color-mix(in srgb, var(--color-primary) 12%, var(--button-bg));
  --button-bg-active: color-mix(in srgb, var(--color-primary) 18%, var(--button-bg));
  --button-text: #071407;
  --button-border: #557755;
  --button-border-active: #0f6b0f;
  --button-primary-bg: var(--color-primary);
  --button-primary-text: #ffffff;
  --button-secondary-bg: #e8f6e8;
  --button-secondary-text: #183318;
  --button-danger-text: #a91b1b;
  --panel-bg: #fbfffb;
  --panel-text: #071407;
  --panel-border: #557755;
  --panel-head-bg: #e8f6e8;
  --panel-head-text: var(--color-primary);
  --dialog-bg: #fbfffb;
  --dialog-text: #071407;
  --dialog-border: #557755;
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --calendar-bg: #f6fff6;
  --calendar-border: #557755;
  --calendar-day-bg: #fbfffb;
  --calendar-day-hover: #d6efd6;
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 12%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 18%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: rgba(15, 107, 15, 0.12);
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
  --work-back: #fbfffb;
  --work-fore: var(--color-text);
  --cal-day-fore: var(--color-text);
  --cal-day-hover-glow: 0 0 1px 5px rgba(15, 107, 15, 0.24);
  --button-text-hover: #071407;
}
