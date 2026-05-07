<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

?>

.contact-page {
  width: min(92vw, 88rem);
  max-width: none;
  margin: 0 auto;
  padding: 0;
}

.contact-page .doc-article-header,
.contact-page .doc-article-body {
  max-width: none;
  margin: 0 auto;
}

.contact-page .doc-article-body {
  display: grid;
  gap: var(--pad-md);
}

.contact-page .doc-article-header {
  margin-bottom: 1.25rem;
}

.contact-page .doc-section {
  padding: clamp(0.9rem, 2vw, 1.5rem);
}

.contact-form {
  display: grid;
  gap: var(--pad-md);
}

.contact-form-section {
  border: 1px solid var(--panel-border, var(--color-border, #ccc));
  border-radius: 0.5rem;
  background: var(--color-surface, #fff);
  padding: var(--pad-md);
}

.contact-form-top-grid,
.contact-form-bottom-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1.1rem 1.25rem;
}

.contact-field--notes {
  grid-column: 1 / -1;
}

.contact-status-slot {
  min-height: var(--pad-md);
}

.contact-details-panel {
  grid-column: 1 / -1;
  justify-self: stretch;
  width: 100%;
  box-sizing: border-box;
  display: grid;
  gap: 0.75rem;
  border: 1px solid var(--panel-border, var(--color-border, #ccc));
  border-radius: 0.5rem;
  background: var(--color-surface, #fff);
  padding: var(--pad-md);
}

.contact-details-split {
  display: grid;
  grid-template-columns: 1.3fr 1fr;
  gap: 1rem;
  align-items: start;
}

.contact-guide-block,
.contact-optional-block {
  min-width: 0;
  align-self: start;
}

.contact-guide-block .contact-help-section-title,
.contact-optional-block .contact-help-section-title {
  margin-top: 0;
}

.contact-field {
  display: grid;
  gap: 0.5rem;
}

.contact-field--full {
  grid-column: 1 / -1;
}

.contact-field input,
.contact-field textarea {
  width: 100%;
  padding: 0.65rem 0.7rem;
  background-color: var(--input-bg);
}

.contact-field-error {
  margin: 0;
  font-size: var(--font-sm, 0.9rem);
  color: var(--status-error-text, #7a1010);
}

.contact-status {
  margin: 0 0 1rem 0;
  border: 1px solid var(--panel-border, var(--color-border, #7a7a7a));
  border-left-width: 4px;
  border-radius: var(--border-radius, 0.5rem);
  padding: 0.85rem 1rem;
  background: var(--color-surface-strong, #f6f6f6);
  color: var(--color-text, #1d1d1d);
}

.contact-status[hidden] {
  display: none;
}

.contact-status--info {
  border-left-color: #1762cc;
}

.contact-status--success {
  border-left-color: #1a8f4c;
}

.contact-status--error {
  border-left-color: #be2d2d;
}

.contact-actions {
  display: flex;
  justify-content: center;
  width: 100%;
  padding-top: var(--pad-md);
  margin-bottom: var(--pad-lg, 2rem);
}

.contact-form-footer {
  grid-column: 1 / -1;
  margin-bottom: 0;
  border-top: 1px solid var(--panel-border, var(--color-border, #ccc));
  padding-top: 1rem;
}

.contact-cooldown-hint {
  grid-column: 1 / -1;
  margin: 0;
  text-align: center;
  font-size: var(--font-sm, 0.9rem);
  color: var(--color-text-muted, #666);
}

.contact-send-button--cooldown {
  opacity: 0.85;
}

.contact-send-button--cooldown[disabled] {
  cursor: not-allowed;
}

.contact-field select {
  width: 100%;
  padding: 0.65rem 0.7rem;
  border: 1px solid var(--panel-border, var(--color-border, #ccc));
  border-radius: 0.3rem;
  background-color: var(--color-surface, #fff);
  color: var(--color-text, #1d1d1d);
  font-family: inherit;
  font-size: inherit;
}

.contact-field select:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.contact-field-inline-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.25rem;
}

.contact-field-count {
  margin: 0;
  font-size: var(--font-sm, 0.9rem);
  color: var(--color-text-muted, #666);
}

.contact-field-count--warn {
  color: #d4a500;
  font-weight: 600;
}

.contact-help-section {
  margin-top: 0.5rem;
  padding: 0;
  border: 1px solid var(--panel-border, var(--color-border, #ccc));
  border-radius: 0.3rem;
  background: var(--color-surface, #fff);
}

.contact-help-section > summary {
  padding: 0.75rem 1rem;
  cursor: pointer;
  font-weight: 500;
  color: var(--color-text, #1d1d1d);
  user-select: none;
}

.contact-help-section > summary:hover {
  background: var(--color-surface-strong, #f6f6f6);
}

.contact-help-section[open] > summary {
  border-bottom: 1px solid var(--panel-border, var(--color-border, #ccc));
}

.contact-help-content {
  padding: 0.75rem 1rem;
}

.contact-help-desc {
  margin: 0 0 0.75rem 0;
  font-size: var(--font-sm, 0.9rem);
  color: var(--color-text-muted, #666);
}

.contact-help-tips {
  margin: 0 0 0.75rem 0;
  padding-left: 1.5rem;
  font-size: var(--font-sm, 0.9rem);
  color: var(--color-text, #1d1d1d);
}

.contact-help-tips li {
  margin: 0.4rem 0;
  line-height: 1.4;
}

.contact-help-tips strong {
  color: var(--color-text, #1d1d1d);
}

.contact-help-divider {
  height: 1px;
  margin: 0.75rem 0;
  background: var(--panel-border, var(--color-border, #ccc));
}

.contact-sla-info {
  margin: 0;
  font-size: var(--font-sm, 0.9rem);
  color: var(--color-text-muted, #666);
  line-height: 1.5;
}

.contact-sla-info--center {
  margin-top: 0;
  text-align: center;
}

.contact-support-callout {
  display: grid;
  gap: var(--pad-md);
  border: 1px solid var(--panel-border, var(--color-border, #ccc));
  border-radius: 0.5rem;
  background: var(--color-surface, #fff);
}

@media (max-width: 900px) {
  .contact-form-top-grid,
  .contact-form-bottom-grid {
    grid-template-columns: 1fr;
  }

  .contact-field--notes,
  .contact-details-panel,
  .contact-form-footer {
    grid-column: 1 / -1;
  }

  .contact-details-split {
    grid-template-columns: 1fr;
  }
}

.contact-success-card {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 1.25rem;
  align-items: center;
  margin: 1.5rem 0 1rem 0;
  padding: 1.5rem 1.25rem;
  border: 1px solid #1a8f4c;
  border-radius: 0.5rem;
  background: linear-gradient(135deg, rgba(26, 143, 76, 0.08) 0%, rgba(26, 143, 76, 0.02) 100%);
}

.contact-success-card[hidden] {
  display: none;
}

.contact-success-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  background: #1a8f4c;
  color: #fff;
  font-size: 1.5rem;
  font-weight: bold;
  flex-shrink: 0;
}

.contact-success-content {
  grid-column: 2 / 3;
}

.contact-success-title {
  margin: 0 0 0.25rem 0;
  font-size: 1.1rem;
  font-weight: 600;
  color: #1a8f4c;
}

.contact-success-time {
  margin: 0 0 0.5rem 0;
  font-size: var(--font-sm, 0.9rem);
  color: var(--color-text-muted, #666);
}

.contact-success-note {
  margin: 0;
  font-size: var(--font-sm, 0.85rem);
  color: var(--color-text-muted, #666);
  line-height: 1.4;
}

.contact-success-cooldown {
  margin: 0.5rem 0 0;
  font-size: var(--font-sm, 0.9rem);
  color: var(--color-text-muted, #666);
}

#contact_send_another {
  grid-column: 3 / 4;
  align-self: center;
  flex-shrink: 0;
}

.contact-help-chips {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.contact-chip {
  display: flex;
  align-items: center;
  cursor: pointer;
  user-select: none;
  gap: 0.6rem;
}

.contact-chip-input {
  appearance: none;
  -webkit-appearance: none;
  width: 20px;
  height: 20px;
  cursor: pointer;
  border: 2px solid var(--panel-border, #5a6f7d);
  border-radius: 3px;
  background: transparent;
  margin: 0;
  padding: 0;
  flex-shrink: 0;
  transition: all 0.2s ease;
}

.contact-chip-input:hover {
  border-color: var(--color-primary, #2c5aa0);
  box-shadow: 0 0 0 2px rgba(0, 188, 212, 0.1);
}

.contact-chip-input:focus {
  outline: none;
  border-color: var(--color-primary, #2c5aa0);
  box-shadow: 0 0 0 3px rgba(0, 188, 212, 0.2);
}

.contact-chip-input:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.contact-chip-input:checked {
  background: var(--color-primary, #2c5aa0);
  border-color: var(--color-primary, #2c5aa0);
}

.contact-chip-input:checked::after {
  content: '';
  display: block;
  width: 100%;
  height: 100%;
  background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='white' d='M13.78 4.22a.75.75 0 010 1.06l-7.5 7.5a.75.75 0 11-1.06-1.06l7.5-7.5a.75.75 0 011.06 0z'/%3E%3Cpath fill='white' d='M2.22 9.22a.75.75 0 010-1.06l5-5a.75.75 0 111.06 1.06l-5 5a.75.75 0 01-1.06 0z'/%3E%3C/svg%3E") no-repeat center;
  background-size: 12px;
}

.contact-chip-label {
  font-size: var(--font-sm, 0.9rem);
  color: var(--color-text, #f5f5f7);
  margin: 0;
  font-weight: 500;
}

.contact-help-section-title {
  margin: 0 0 0.75rem 0;
  font-size: var(--font-base, 1rem);
  font-weight: 600;
  color: var(--color-text, #1d1d1d);
}

.contact-help-section-title--diagnostics {
  margin-top: 0.75rem;
  margin-bottom: 0.75rem;
  font-size: 0.95rem;
}

.contact-context-sublabel {
  margin: 0 0 0.75rem 0;
  font-size: var(--font-sm, 0.9rem);
  color: var(--color-text-muted, #666);
}

.contact-email-reply-info {
  margin: 0.75rem 0 0 0;
  font-size: var(--font-sm, 0.9rem);
  color: var(--color-text-muted, #666);
}

@media only screen and (max-width: 900px) {
  .contact-form-grid {
    grid-template-columns: 1fr;
  }

  .contact-success-card {
    grid-template-columns: auto 1fr;
    gap: 1rem;
  }

  #contact_send_another {
    grid-column: 1 / -1;
    align-self: auto;
    margin-top: 0.5rem;
  }
}
