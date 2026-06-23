<?php declare(strict_types=1);

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
.business-moderation {
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
  max-width: 100%;
}

.business-moderation > :first-child {
  margin-top: 0;
}

.business-moderation__header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 1rem;
}

.business-moderation__header h1 {
  margin: 0 0 0.35rem 0;
}

.business-moderation__header p {
  margin: 0;
  max-width: 52rem;
  line-height: 1.45;
  opacity: 0.9;
}

.business-moderation__badge {
  margin: 0;
  padding: 0.35rem 0.7rem;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 700;
  white-space: nowrap;
  border: 1px solid color-mix(in srgb, currentColor 25%, transparent);
}

.business-moderation__badge--idle {
  opacity: 0.75;
}

.business-moderation__badge--active {
  color: var(--color-warning, #d29922);
  background: color-mix(in srgb, var(--color-warning, #d29922) 14%, transparent);
}

.business-moderation-feedback {
  display: none;
  align-items: flex-start;
  gap: 0.85rem;
  padding: 0.95rem 1.1rem;
  border-radius: 10px;
  border: 1px solid transparent;
}

.business-moderation-feedback.is-visible {
  display: flex;
}

.business-moderation-feedback--success {
  border-color: color-mix(in srgb, var(--color-success, #2da44e) 55%, transparent);
  background: color-mix(in srgb, var(--color-success, #2da44e) 14%, transparent);
}

.business-moderation-feedback--error {
  border-color: color-mix(in srgb, var(--color-danger, #cf222e) 55%, transparent);
  background: color-mix(in srgb, var(--color-danger, #cf222e) 14%, transparent);
}

.business-moderation-feedback__icon {
  flex: 0 0 auto;
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  display: grid;
  place-items: center;
  font-size: 1.1rem;
  font-weight: 700;
}

.business-moderation-feedback--success .business-moderation-feedback__icon {
  background: color-mix(in srgb, var(--color-success, #2da44e) 22%, transparent);
  color: var(--color-success, #2da44e);
}

.business-moderation-feedback--error .business-moderation-feedback__icon {
  background: color-mix(in srgb, var(--color-danger, #cf222e) 22%, transparent);
  color: var(--color-danger, #cf222e);
}

.business-moderation-feedback__body {
  min-width: 0;
}

.business-moderation-feedback__title {
  margin: 0 0 0.25rem 0;
  font-size: 1rem;
  font-weight: 700;
}

.business-moderation-feedback__detail {
  margin: 0;
  line-height: 1.45;
  opacity: 0.92;
}

.business-moderation-queue__head {
  display: flex;
  flex-wrap: wrap;
  align-items: baseline;
  justify-content: space-between;
  gap: 0.5rem 1rem;
  margin-bottom: 0.75rem;
}

.business-moderation-queue__head h2 {
  margin: 0;
}

.business-moderation-queue__meta {
  margin: 0;
  font-size: 0.92rem;
  opacity: 0.8;
}

.business-moderation-empty {
  margin: 0;
  padding: 1.5rem 1rem;
  text-align: center;
  border-radius: 8px;
  border: 1px dashed color-mix(in srgb, currentColor 22%, transparent);
  background: color-mix(in srgb, currentColor 4%, transparent);
}

.business-moderation-empty__title {
  margin: 0 0 0.35rem 0;
  font-size: 1.05rem;
  font-weight: 700;
}

.business-moderation-empty__text {
  margin: 0;
  opacity: 0.82;
  line-height: 1.45;
}

.business-moderation-table-wrap {
  overflow-x: auto;
}

.business-moderation-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.92rem;
}

.business-moderation-table th,
.business-moderation-table td {
  padding: 0.65rem 0.55rem;
  text-align: left;
  vertical-align: top;
  border-bottom: 1px solid color-mix(in srgb, currentColor 12%, transparent);
}

.business-moderation-table th {
  font-size: 0.78rem;
  letter-spacing: 0.03em;
  text-transform: uppercase;
  opacity: 0.75;
  white-space: nowrap;
}

.business-moderation-table tbody tr:hover {
  background: color-mix(in srgb, currentColor 4%, transparent);
}

.business-moderation-table__business {
  font-weight: 700;
  min-width: 9rem;
}

.business-moderation-table__mono {
  font-family: var(--font-mono, ui-monospace, monospace);
  font-size: 0.82rem;
  word-break: break-all;
}

.business-moderation-table__reasons {
  max-width: 12rem;
  word-break: break-word;
  opacity: 0.88;
}

.business-moderation-badge {
  display: inline-block;
  padding: 0.15rem 0.5rem;
  border-radius: 999px;
  font-size: 0.78rem;
  font-weight: 700;
  line-height: 1.35;
  border: 1px solid transparent;
  white-space: nowrap;
}

.business-moderation-badge--muted {
  opacity: 0.72;
  border-color: color-mix(in srgb, currentColor 18%, transparent);
}

.business-moderation-badge--success {
  color: var(--color-success, #2da44e);
  border-color: color-mix(in srgb, var(--color-success, #2da44e) 40%, transparent);
  background: color-mix(in srgb, var(--color-success, #2da44e) 12%, transparent);
}

.business-moderation-badge--warn {
  color: var(--color-warning, #d29922);
  border-color: color-mix(in srgb, var(--color-warning, #d29922) 40%, transparent);
  background: color-mix(in srgb, var(--color-warning, #d29922) 12%, transparent);
}

.business-moderation-badge--danger {
  color: var(--color-danger, #cf222e);
  border-color: color-mix(in srgb, var(--color-danger, #cf222e) 40%, transparent);
  background: color-mix(in srgb, var(--color-danger, #cf222e) 12%, transparent);
}

.business-moderation-badge--info {
  color: var(--color-info, #1f8bff);
  border-color: color-mix(in srgb, var(--color-info, #1f8bff) 40%, transparent);
  background: color-mix(in srgb, var(--color-info, #1f8bff) 12%, transparent);
}

.business-moderation-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  min-width: 14rem;
}

.business-moderation-actions .btn {
  min-width: 4.6rem;
}

.business-moderation-actions .btn_primary {
  font-weight: 700;
}

@media (max-width: 1100px) {
  .business-moderation__header {
    flex-direction: column;
    align-items: flex-start;
  }
}
