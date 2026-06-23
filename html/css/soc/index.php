<?php declare(strict_types=1);

/**
 * css/soc/index.php
 *
 * Purpose: Stylesheet for the /soc Auditor Portal — the primary external-facing
 * SOC 2 evidence interface for approved auditors. Reuses existing admin/soc2
 * token conventions (.soc2-admin, .panel, .badge, .datagrid) and extends them
 * with portal-specific layout and ledger visualization styles.
 *
 * Consumed by: html/soc/index.php
 * Token base: var(--panel-bg), var(--panel-text), var(--panel-border-color),
 *             var(--color-primary), var(--color-danger), var(--color-success)
 */

header('Content-Type: text/css; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/* ── Auditor Portal layout ───────────────────────────────────────────────── */

.soc-portal {
  display: grid;
  gap: 0.8rem;
}

.soc-portal__header {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
  gap: 1rem;
  align-items: start;
}

@media (max-width: 680px) {
  .soc-portal__header {
    grid-template-columns: 1fr;
  }
}

.soc-portal__header h1 {
  margin: 0 0 0.35rem;
  font-size: 1.4rem;
}

.soc-portal__header p {
  margin: 0;
  opacity: 0.88;
  max-width: 76ch;
}

.soc-portal__header-meta {
  font-size: 0.83rem;
  opacity: 0.75;
  margin: 0.4rem 0 0;
}

.soc-portal__actions {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.5rem;
}

.soc-portal__actions .btn {
  width: auto;
  min-width: 0;
}

.soc-portal__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

@media (max-width: 840px) {
  .soc-portal__grid {
    grid-template-columns: 1fr;
  }
}

.soc-portal__section-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  flex-wrap: wrap;
  margin-bottom: 0.75rem;
}

.soc-portal__section-row h2 {
  margin: 0;
  font-size: 1.05rem;
}

.grid-span-full {
  grid-column: 1 / -1;
}

/* ── Validity gate checklist ─────────────────────────────────────────────── */

.soc-gate-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.45rem;
}

.soc-gate-item {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.9rem;
  padding: 0.35rem 0.5rem;
  border-radius: 6px;
}

.soc-gate-item.is-pass {
  background: color-mix(in srgb, #1f7a38 10%, transparent);
}

.soc-gate-item.is-warn {
  background: color-mix(in srgb, #9a6b00 10%, transparent);
}

.soc-gate-item.is-fail {
  background: color-mix(in srgb, #8a1d1d 10%, transparent);
}

.soc-gate-icon {
  flex: 0 0 auto;
  font-size: 0.9rem;
  line-height: 1.4;
}

.soc-gate-icon--pass { color: #1f7a38; }
.soc-gate-icon--warn { color: #9a6b00; }
.soc-gate-icon--fail { color: #8a1d1d; }

.soc-gate-text {
  flex: 1 1 auto;
  line-height: 1.4;
}

.soc-gate-detail {
  font-size: 0.78rem;
  opacity: 0.75;
  margin-top: 0.1rem;
}

/* ── Control summary table ───────────────────────────────────────────────── */

.soc-control-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.88rem;
}

.soc-control-table th {
  text-align: left;
  padding: 0.45rem 0.6rem;
  border-bottom: 2px solid color-mix(in srgb, var(--panel-text, #222) 18%, transparent);
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  opacity: 0.8;
}

.soc-control-table td {
  padding: 0.5rem 0.6rem;
  border-bottom: 1px solid color-mix(in srgb, var(--panel-text, #222) 10%, transparent);
  vertical-align: top;
}

.soc-control-table tr:last-child td {
  border-bottom: none;
}

.soc-control-table .soc-ctrl-id {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
  font-size: 0.82rem;
  white-space: nowrap;
}

.soc-ctrl-title {
  font-family: inherit;
  font-size: 0.78rem;
  opacity: 0.75;
}

.soc-control-table .soc-ctrl-notes {
  font-size: 0.82rem;
  opacity: 0.8;
}

/* ── Status badges ───────────────────────────────────────────────────────── */

.soc-badge {
  display: inline-block;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.03em;
  padding: 0.15em 0.55em;
  border-radius: 4px;
  white-space: nowrap;
}

.soc-badge--pass {
  background: color-mix(in srgb, #1f7a38 18%, transparent);
  color: #1f7a38;
  border: 1px solid color-mix(in srgb, #1f7a38 40%, transparent);
}

.soc-portal .soc-artifacts-table .soc-badge--pass {
  animation: socFinalityPulse 4s ease-in-out infinite;
}

.soc-badge--warn {
  background: color-mix(in srgb, #9a6b00 16%, transparent);
  color: #9a6b00;
  border: 1px solid color-mix(in srgb, #9a6b00 40%, transparent);
}

.soc-badge--fail {
  background: color-mix(in srgb, #8a1d1d 16%, transparent);
  color: #8a1d1d;
  border: 1px solid color-mix(in srgb, #8a1d1d 40%, transparent);
}

.soc-badge--info {
  background: color-mix(in srgb, #005f8f 14%, transparent);
  color: #005f8f;
  border: 1px solid color-mix(in srgb, #005f8f 36%, transparent);
}

.soc-badge--neutral {
  background: color-mix(in srgb, var(--panel-text, #444) 10%, transparent);
  border: 1px solid color-mix(in srgb, var(--panel-text, #444) 22%, transparent);
}

/* ── TheLedger chain visualization ──────────────────────────────────────── */

.soc-ledger-chain {
  display: grid;
  gap: 0.5rem;
}

.soc-ledger-block {
  border: 1px solid color-mix(in srgb, var(--panel-text, #444) 14%, transparent);
  border-radius: 8px;
  padding: 0.6rem 0.75rem;
  font-size: 0.85rem;
}

.soc-ledger-block.is-verified {
  border-color: color-mix(in srgb, #1f7a38 50%, transparent);
  background: color-mix(in srgb, #1f7a38 6%, transparent);
  animation: socLedgerIntegrityGlow 900ms ease-out both;
}

.soc-ledger-block.is-empty {
  opacity: 0.6;
}

.soc-ledger-block__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-bottom: 0.3rem;
}

.soc-ledger-block__seq {
  font-weight: 600;
  font-size: 0.82rem;
}

.soc-ledger-block__hash {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
  font-size: 0.72rem;
  opacity: 0.7;
  word-break: break-all;
  margin: 0.2rem 0 0;
}

.soc-ledger-block__meta {
  font-size: 0.78rem;
  opacity: 0.7;
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.soc-ledger-empty {
  font-size: 0.88rem;
  opacity: 0.65;
  padding: 0.75rem 0;
  text-align: center;
}

/* ── Artifacts table ─────────────────────────────────────────────────────── */

.soc-artifacts-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
}

.soc-artifacts-table th {
  text-align: left;
  padding: 0.4rem 0.6rem;
  border-bottom: 2px solid color-mix(in srgb, var(--panel-text, #222) 16%, transparent);
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  opacity: 0.78;
}

.soc-artifacts-table td {
  padding: 0.45rem 0.6rem;
  border-bottom: 1px solid color-mix(in srgb, var(--panel-text, #222) 9%, transparent);
  vertical-align: middle;
}

.soc-artifacts-table tr:last-child td {
  border-bottom: none;
}

.soc-artifact-name {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
  font-size: 0.8rem;
  word-break: break-all;
}

.soc-artifact-updated {
  white-space: nowrap;
  font-size: 0.8rem;
  opacity: 0.75;
}

.soc-artifact-size {
  white-space: nowrap;
  font-size: 0.8rem;
  opacity: 0.65;
}

.soc-anchor-intro {
  font-size: 0.8rem;
  margin-bottom: 0.75rem;
  opacity: 0.75;
}

.soc-tx-pending {
  opacity: 0.45;
}

/* ── Backup evidence strip ───────────────────────────────────────────────── */

.soc-backup-strip {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
  gap: 0.5rem;
  margin-top: 0.6rem;
}

.soc-backup-kpi {
  border: 1px solid color-mix(in srgb, var(--panel-text, #444) 14%, transparent);
  border-radius: 8px;
  padding: 0.55rem 0.65rem;
}

.soc-backup-kpi.is-ok {
  border-color: color-mix(in srgb, #1f7a38 50%, transparent);
  background: color-mix(in srgb, #1f7a38 7%, transparent);
}

.soc-backup-kpi__label {
  font-size: 0.75rem;
  opacity: 0.78;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  margin-bottom: 0.2rem;
}

.soc-backup-kpi__value {
  font-size: 1.1rem;
  font-weight: 600;
}

.soc-backup-kpi__value--filename {
  font-size: 0.75rem;
  word-break: break-all;
}

.soc-backup-kpi__sub {
  font-size: 0.75rem;
  opacity: 0.7;
  margin-top: 0.1rem;
  word-break: break-word;
}

/* ── Footer strip ────────────────────────────────────────────────────────── */

.soc-portal__footer {
  font-size: 0.82rem;
  opacity: 0.75;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.soc-disclaimer {
  font-size: 0.8rem;
  opacity: 0.65;
  border-top: 1px solid color-mix(in srgb, var(--panel-text, #444) 12%, transparent);
  padding-top: 0.5rem;
  margin-top: 0.25rem;
}

@keyframes socLedgerIntegrityGlow {
  0%, 100% {
    box-shadow: none;
  }
  45% {
    box-shadow: inset 0 0 0 1px color-mix(in srgb, #1f7a38 65%, transparent), 0 0 18px color-mix(in srgb, #1f7a38 22%, transparent);
  }
}

@keyframes socFinalityPulse {
  0%, 100% {
    opacity: 0.72;
  }
  50% {
    opacity: 1;
  }
}
