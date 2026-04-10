<?php declare(strict_types=1);

/*
 * Token scaffold defaults.
 * Loaded before theme files; theme definitions override these defaults.
 */
?>
:root {
  /* Semantic foundation defaults */
  --color-bg: #0f1418;
  --color-bg-soft: #141b20;
  --color-bg-elevated: #1b242b;
  --color-bg-overlay: rgba(8, 12, 16, 0.72);

  --color-surface: #1c252c;
  --color-surface-muted: #1a2228;
  --color-surface-strong: #26323a;
  --input-bg:                            var(--color-surface);

  --color-border: #3a4a55;
  --color-border-soft: #2e3b44;
  --color-border-strong: #566a79;

  --color-text: #e7edf1;
  --color-text-muted: #b6c2ca;
  --color-text-inverse: #13191d;
  --color-text-disabled: #6c7b86;

  --color-primary: #00bcd4;
  --color-primary-hover: #00acc1;
  --color-primary-active: #0097a7;
  --color-primary-soft: rgba(0, 188, 212, 0.20);
  --color-on-primary: #122026;

  --color-success: #2e7d32;
  --color-warning: #ef6c00;
  --color-danger: #c62828;
  --color-info: #0288d1;

  --color-on-success: #f0fff3;
  --color-on-warning: #1b1300;
  --color-on-danger: #fff3f3;
  --color-on-info: #f1f9ff;

  --color-hover: rgba(255, 255, 255, 0.10);
  --color-active: rgba(255, 255, 255, 0.16);
  --color-focus-ring: #80deea;
  --color-selection: rgba(0, 188, 212, 0.35);
  --color-highlight: rgba(255, 232, 122, 0.22);
  --color-disabled-bg: rgba(255, 255, 255, 0.06);

  /* Radius roles (themes may override these per visual language) */
  --radius-button: calc(var(--border-radius, 8px) * 2);
  --radius-control: var(--border-radius, 8px);
  --radius-panel: var(--border-radius, 8px);
  --radius-dialog: var(--border-radius, 8px);
  --radius-cell: var(--border-radius, 8px);
  --radius-article: var(--border-radius, 8px);

  --elevation-1-bg: #1b242b;
  --elevation-2-bg: #202b33;
  --elevation-3-bg: #27343d;
  --overlay-backdrop: rgba(0, 0, 0, 0.58);

  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.18);
  --shadow-md: 0 6px 16px rgba(0, 0, 0, 0.28);
  --shadow-lg: 0 16px 38px rgba(0, 0, 0, 0.36);

  /* Component token defaults */
  --button-bg: var(--color-surface-strong);
  --button-bg-hover: color-mix(in srgb, var(--button-bg) 72%, white);
  --button-bg-active: color-mix(in srgb, var(--button-bg) 80%, black);
  --button-text: var(--color-text);
  --button-text-hover: var(--button-text);
  --button-text-active: var(--button-text);
  --button-border: var(--color-border);
  --button-border-active: var(--color-primary-hover);

  --button-primary-bg: var(--color-primary);
  --button-primary-bg-hover: var(--button-primary-bg);
  --button-primary-bg-active: var(--button-primary-bg);
  --button-primary-text: var(--color-on-primary);
  --button-primary-text-hover: var(--button-primary-text);
  --button-primary-text-active: var(--button-primary-text);
  --button-secondary-bg: var(--color-surface);
  --button-secondary-text: var(--color-text-muted);
  --button-danger-text: var(--color-danger);
  --btn-back: var(--button-bg);
  --btn-back-hover: var(--button-bg-hover);
  --btn-selected-back: color-mix(in srgb, var(--color-primary) 18%, var(--color-surface-strong));
  --btn-selected-fore: var(--color-text);
  --fore: var(--color-text);
  --fore-muted: var(--color-text-muted);
  --back-light: color-mix(in srgb, var(--color-surface) 88%, var(--color-bg));

  --panel-bg: var(--color-surface);
  --panel-text: var(--color-text);
  --panel-border: var(--color-border);
  --panel-head-bg: var(--color-surface-strong);
  --panel-head-text: var(--color-primary);

  --work-entry-back: var(--color-surface);
  --work-entry-fore: var(--color-text);

  --dialog-bg: var(--elevation-2-bg);
  --dialog-text: var(--color-text);
  --dialog-border: var(--color-border-soft);
  --dialog-shadow: var(--shadow-md);
  --dialog-overlay: var(--overlay-backdrop);
  --dialog-back: var(--dialog-bg);
  --dialog-fore: var(--dialog-text);
  --modal-head-back: var(--panel-head-bg);
  --modal-head-fore: var(--panel-head-text);

  --calendar-bg: var(--color-bg);
  --calendar-border: var(--color-border);
  --calendar-day-bg: var(--color-surface);
  --calendar-day-hover: color-mix(in srgb, var(--color-primary) 24%, var(--calendar-day-bg));
  --calendar-day-today: color-mix(in srgb, var(--color-primary) 16%, var(--calendar-day-bg));
  --calendar-day-selected: color-mix(in srgb, var(--color-primary) 28%, var(--calendar-day-bg));
  --calendar-event-bg: var(--color-primary-soft);
  --calendar-event-text: var(--color-text);
  --calendar-range-bg: color-mix(in srgb, var(--color-primary) 20%, transparent);

  /* Status bar component tokens (info/working/paste) */
  --status-info-bg: #1f8bff;
  --status-info-text: #031a33;
  --status-info-border: #006edc;
  --status-info-icon-bg: #031a33;
  --status-info-icon-text: #1f8bff;

  /* Status bar component tokens (success/save/copy) */
  --status-success-bg: #00c853;
  --status-success-text: #05210f;
  --status-success-border: #00aa44;
  --status-success-icon-bg: #05210f;
  --status-success-icon-text: #00c853;

  /* Status bar component tokens (error/delete) */
  --status-error-bg: #ff3b30;
  --status-error-text: #2b0806;
  --status-error-border: #d91e18;
  --status-error-icon-bg: #2b0806;
  --status-error-icon-text: #ff3b30;

  /* Countdown/timer state tokens */
  --countdown-warning: #ffb020;
  --countdown-expired: #ff4d4f;

  /* Verification banner tokens */
  --verification-banner-bg-start: #fef3c7;
  --verification-banner-bg-end: #fde68a;
  --verification-banner-border: #f59e0b;
  --verification-banner-icon-bg: #fbbf24;
  --verification-banner-icon-text: #78350f;
  --verification-banner-heading-text: #92400e;
  --verification-banner-body-text: #78350f;

  /* Resend / verification action button tokens */
  --resend-btn-bg: #f59e0b;
  --resend-btn-bg-hover: #d97706;
  --resend-btn-bg-disabled: #9ca3af;
  --resend-btn-bg-success: #10b981;
  --resend-btn-text: #ffffff;

  /* Verification reminder inline form tokens */
  --verification-reminder-input-bg: #fefaf0;
  --verification-reminder-input-text: #4a3000;
  --verification-reminder-input-border: var(--verification-banner-border);
  --verification-reminder-input-focus-border: #ff9800;
  --verification-reminder-input-focus-shadow: rgba(255, 193, 7, 0.1);
  --verification-reminder-placeholder: #9ca3af;
  --verification-reminder-link: #ff9800;
  --verification-reminder-link-hover: #e68900;

  /* Input interaction state component tokens */
  --input-focus-border: var(--color-focus-ring);
  --input-disabled-bg: var(--color-disabled-bg);
  --input-disabled-text: var(--color-text-disabled);
  --input-disabled-border: var(--color-border);

  /* Button disabled state tokens */
  --button-disabled-bg: var(--color-disabled-bg);
  --button-disabled-text: var(--color-text-disabled);
  --button-disabled-border: var(--color-border);

  /* Form and dialog spacing tokens */
  --form-gap: 1.25rem;
  --form-field-gap: 0.5rem;
  --dialog-padding: 1.25rem;
  --dialog-gap: 1rem;
  --datagrid-cell-padding: 0.5rem 0.75rem;
}
