<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - Forecast Page Styles
 *
 * Crew Forecasting + Workforce Cost Intelligence module.
 * All monetary display components. No inline styles.
 */

/* ── Page wrapper ──────────────────────────────────────── */

.forecast-page {
  gap: var(--gap-md, 1rem);
}

/* ── Panels ────────────────────────────────────────────── */

.forecast-panel {
  width: 100%;
}

.forecast-panel__title {
  margin: 0 0 0.5rem;
  font-size: 1.1rem;
}

.forecast-panel__crew-summary {
  font-weight: 400;
  font-size: 0.9rem;
  color: var(--text-muted, var(--color-text-muted));
}

.forecast-panel__intro {
  margin: 0 0 1rem;
  font-size: 0.9rem;
  color: var(--text-muted, var(--color-text-muted));
}

/* ── Parameter form ────────────────────────────────────── */

.forecast-form {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr));
  gap: var(--gap-sm, 0.5rem) var(--gap-md, 1rem);
  align-items: start;
}

.forecast-form__row {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.forecast-form__label {
  font-size: 0.875rem;
  font-weight: 600;
}

.forecast-form__optional {
  font-weight: 400;
  font-size: 0.8rem;
  color: var(--text-muted, var(--color-text-muted));
}

.forecast-form__input,
.forecast-form__select {
  padding: 0.4rem 0.6rem;
  border: 1px solid var(--border, rgba(255, 255, 255, 0.2));
  border-radius: var(--radius-sm, 4px);
  background: var(--surface, #1a1a1a);
  color: var(--text, var(--color-text));
  font-size: 0.9rem;
  width: 100%;
}

.forecast-form__input:focus,
.forecast-form__select:focus {
  outline: 2px solid var(--primary, var(--color-primary));
  outline-offset: 1px;
}

.forecast-form__hint {
  font-size: 0.775rem;
  color: var(--text-muted, var(--color-text-muted));
}

.forecast-form__actions {
  grid-column: 1 / -1;
  padding-top: 0.5rem;
}

.forecast-form__submit {
  padding: 0.55rem 1.4rem;
}

/* ── Results datagrid ──────────────────────────────────── */

.forecast-results-grid {
  --datagrid_cols: 6;
  --grid-template-columns: minmax(130px, 1.5fr) repeat(3, minmax(80px, 1fr)) repeat(2, minmax(110px, 1.2fr));
  margin: 0 0 0.75rem;
}

.forecast-gross {
  font-weight: 600;
}

.forecast-net {
  font-weight: 700;
  color: var(--primary, var(--color-primary));
}

.forecast-ot {
  color: var(--warning, var(--color-warning, #f59e0b));
}

.forecast-row--shutdown .datagrid_item {
  border-top: 2px solid var(--primary, var(--color-primary));
  font-style: italic;
}

/* ── Disclaimer ────────────────────────────────────────── */

.forecast-disclaimer {
  margin: 0.5rem 0 0;
  font-size: 0.8rem;
  color: var(--text-muted, var(--color-text-muted));
  font-style: italic;
  max-width: 68rem;
}

/* ── Upgrade notice ────────────────────────────────────── */

.forecast-upgrade-notice {
  background: var(--elevated-surface, var(--surface));
  border: 1px solid var(--primary, var(--color-primary));
}

.forecast-upgrade-notice a {
  color: var(--primary, var(--color-primary));
  font-weight: 600;
  text-decoration: underline;
}

/* ── Responsive ─────────────────────────────────────────── */

@media (max-width: 640px) {
  .forecast-form {
    grid-template-columns: 1fr;
  }

  .forecast-results-grid {
    --datagrid_cols: 3;
    --grid-template-columns: minmax(110px, 1.5fr) minmax(100px, 1fr) minmax(100px, 1fr);
    overflow-x: auto;
    white-space: nowrap;
  }
}
