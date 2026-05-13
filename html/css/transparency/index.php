<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - Transparency Page Styles
 * 
 * Extracted from main CSS index.php
 * Date: March 1, 2026
 */

/* TRANSPARENCY PAGE STYLES */

/* Formula notes */
.formula-note {
  margin-top: 10px;
  font-size: 0.9em;
  color: var(--text-muted, #666);
}

/* Diamond bullets for overview panels */
#panel-overview ul {
  margin: 1.5rem 0;
  padding-left: 0;
  list-style: none;
}

#panel-overview ul li {
  position: relative;
  margin-bottom: 0.8rem;
  padding-left: 1.5rem;
}

#panel-overview ul li::before {
  content: "◆";
  position: absolute;
  left: 0;
  color: var(--color-primary);
}

#panel-overview h3 {
  margin-top: 2.5rem;
  margin-bottom: 1.5rem;
}

#panel-overview p {
  margin: 1rem 0;
}

/* Example Code/Calculation Panels - Inset style with spot color accent */
.calculation-example {
  max-width: 50%;
  margin: 2rem 0;
  margin-left: 3rem;
  padding: 15px;
  border: 1px inset var(--panel-border);
  border-top: 3px solid var(--color-primary);
  border-left: 3px solid var(--color-primary);
  border-radius: 4px;
  background: var(--panel-bg);
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
}

.calculation-example ul {
  margin: 1.2rem 0;
  padding-left: 0;
  list-style: none;
}

.calculation-example ul li {
  position: relative;
  margin-bottom: 0.8rem;
  padding-left: 1.5rem;
}

.calculation-example ul li::before {
  content: "◆";
  position: absolute;
  left: 0;
  font-weight: bold;
  color: var(--color-primary);
}

.calculation-example p {
  margin-top: 1.2rem;
}

.net-pay-result {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-primary);
  font-size: 1.1em;
  font-weight: 600;
  color: var(--color-primary);
}

.combined-income-result {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-primary);
  font-size: 1.1em;
  font-weight: 600;
  color: var(--color-primary);
}

.test-results-summary {
  margin-top: 1.5rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-primary);
  font-weight: 600;
  color: var(--color-primary);
}

.calculation-example p:last-child:not(.net-pay-result):not(.combined-income-result) {
  margin-top: 10px;
  padding-top: 10px;
  border-top: 1px solid var(--color-primary);
}

/* Transparency page link styling */
.doc-article a {
  text-decoration: underline;
  text-decoration-style: dashed;
  text-decoration-thickness: 1px;
  text-underline-offset: 3px;
  color: var(--color-primary);
}

.doc-article a[href^="http"]::after {
  content: " ↗";
  font-size: 0.85em;
}

.doc-article a:hover {
  text-decoration-style: solid;
}

/* Public page masthead */
.public-header {
  background: #1a3a6b;
  padding: 1rem 0;
  border-bottom: 3px solid #2c5aa0;
}

.public-header .container {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.public-header .logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  text-decoration: none;
  color: #ffffff;
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: -0.5px;
}

.public-header .logo:hover {
  opacity: 0.9;
}

.public-header .logo-icon {
  font-size: 1.8rem;
}

.public-header .tagline {
  color: rgba(255, 255, 255, 0.85);
  font-size: 0.9rem;
  font-weight: 400;
  margin-left: 0.5rem;
}

/* Article wells */
.doc-article {
  width: min(92vw, 88rem);
  max-width: none;
  margin: 2rem auto;
  background: var(--panel-bg);
  border: 1px solid var(--panel-border);
  border-radius: 0;
  box-shadow: 0 2px 8px color-mix(in srgb, var(--panel-bg) 85%, black);
}

.doc-article-header {
  padding: 2.5rem 3rem 2rem 3rem;
  border-bottom: 3px double var(--panel-border);
  background: linear-gradient(
    to bottom,
    color-mix(in srgb, var(--panel-bg) 92%, var(--color-primary) 8%) 0%,
    var(--panel-bg) 100%
  );
}

.doc-article-header h1 {
  margin: 0 0 0.75rem 0;
  color: var(--color-primary);
  font-size: 2.25rem;
  font-weight: 700;
  letter-spacing: -0.5px;
  line-height: 1.2;
}

.doc-article-header .deck {
  color: var(--text-color);
  font-size: 1.1rem;
  line-height: 1.6;
  margin: 0;
  font-weight: 400;
}

.doc-article-header .doc-article-meta {
  color: var(--text-muted);
  font-size: 0.8rem;
  margin: 0.5rem 0 0 0;
}

.doc-article-body {
  padding: 2rem 3rem 3rem 3rem;
  font-family: var(--sans-serif);
  font-size: 1.05rem;
  line-height: 1.75;
  color: var(--text-color);
}

.doc-section {
  margin: 2.5rem 0;
  padding: 2rem;
  background: color-mix(in srgb, var(--panel-bg) 94%, transparent);
  border-left: 1px dashed var(--color-primary);
  border-radius: 2px;
  box-shadow: inset 0 1px 3px color-mix(in srgb, var(--panel-bg) 90%, black);
}

.doc-section.highlight {
  background: color-mix(in srgb, var(--color-primary) 8%, var(--panel-bg));
  border-left-color: var(--color-primary);
}

.doc-section.success {
  background: color-mix(in srgb, var(--color-primary) 14%, var(--panel-bg));
  border-left-color: var(--color-primary);
}

/* Subject example cutout panel: inset border + tonal color treatment */
.subject-example-cutout {
  margin: 1.5rem 0;
  padding: 1.1rem 1.25rem;
  border: 1px inset color-mix(in srgb, var(--panel-border) 80%, var(--color-primary));
  border-left: 4px inset var(--color-primary);
  border-radius: 3px;
  background: color-mix(in srgb, var(--panel-bg) 88%, var(--color-primary) 12%);
  box-shadow: inset 0 1px 0 color-mix(in srgb, white 25%, transparent), inset 0 -1px 0 color-mix(in srgb, black 18%, transparent);
}

.subject-example-cutout > :first-child {
  margin-top: 0;
}

.subject-example-cutout > :last-child {
  margin-bottom: 0;
}

.subject-example-cutout h3,
.subject-example-cutout h4,
.subject-example-cutout strong {
  color: var(--color-primary);
}

.doc-section h2 {
  margin: 0 0 1.25rem 0;
  color: var(--color-primary);
  font-family: var(--sans-serif);
  font-size: 1.5rem;
  font-weight: 600;
  letter-spacing: -0.3px;
}

.doc-section h3 {
  margin: 1.5rem 0 1rem 0;
  color: var(--color-primary);
  font-family: var(--sans-serif);
  font-size: 1.2rem;
  font-weight: 600;
}

.doc-section p {
  margin: 0.75rem 0;
}

.doc-section ul {
  margin: 0.75rem 0;
  padding-left: 2rem;
}

.doc-section li {
  margin: 0.5rem 0;
}

.doc-table {
  width: 100%;
  border-collapse: collapse;
  margin: 1rem 0;
  font-family: var(--sans-serif);
  font-size: 0.95rem;
  line-height: 1.5;
  background: var(--panel-bg);
  border: 1px solid var(--panel-border);
  display: block;
  overflow-x: auto;
  max-width: 100%;
}

.doc-table th,
.doc-table td {
  border: 1px solid var(--panel-border);
  padding: 0.7rem 0.8rem;
  text-align: left;
  vertical-align: top;
}

.doc-table th {
  background: var(--dialog-bg);
  color: var(--dialog-text);
  font-weight: 700;
}

.doc-code {
  background: color-mix(in srgb, var(--panel-bg) 82%, black);
  color: var(--text-color);
  padding: 1.25rem;
  border-radius: 3px;
  font-family: var(--monospace);
  font-size: 0.9rem;
  line-height: 1.5;
  overflow-x: auto;
  margin: 1.5rem 0;
  border: 1px solid var(--panel-border);
}

.doc-badge {
  display: inline-block;
  padding: 0.3rem 0.75rem;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 600;
  font-family: var(--sans-serif);
  margin-left: 0.5rem;
  vertical-align: middle;
}

.doc-badge.info {
  background: var(--color-primary);
  color: var(--color-on-primary);
}

.doc-badge.high {
  background: #f8d7da;
  color: #721c24;
}

.doc-badge.medium {
  background: #fff3cd;
  color: #856404;
}

.doc-badge.low {
  background: #cce5ff;
  color: #004085;
}

.doc-fact-list {
  margin: 0.75rem 0;
  padding-left: 1.25rem;
}

.doc-fact-list li {
  margin: 0.45rem 0;
}

.doc-read-more {
  display: inline-block;
  margin-top: 0.5rem;
  font-family: var(--sans-serif);
  font-weight: 600;
  text-decoration: none;
  color: var(--color-primary);
}

.doc-read-more:hover {
  opacity: 0.8;
}

.doc-ref-desc {
  display: block;
  font-size: 0.82rem;
  color: var(--color-text-muted);
  margin: 0.15rem 0 0.6rem;
  line-height: 1.35;
}

.a11y-feedback-form {
  display: grid;
  gap: 0.65rem;
  margin: 1rem 0 1.25rem;
  padding: 1rem;
  border: 1px solid var(--panel-border);
  background: color-mix(in srgb, var(--panel-bg) 90%, var(--color-primary) 10%);
}

.a11y-feedback-form label {
  font-family: var(--sans-serif);
  font-size: 0.95rem;
  font-weight: 600;
}

.a11y-feedback-form input,
.a11y-feedback-form textarea {
  width: 100%;
  padding: 0.6rem 0.75rem;
  border: 1px solid var(--panel-border);
  background: var(--panel-bg);
  color: var(--text-color);
  font: inherit;
}

.a11y-feedback-form textarea {
  resize: vertical;
}

.a11y-feedback-form .form-hint {
  margin: 0;
  color: var(--text-muted, var(--text-color));
  font-size: 0.95rem;
}

.doc-two-column {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
  margin: 1rem 0;
}

.doc-two-column h3 {
  margin: 0 0 1rem 0;
  color: var(--color-primary);
  font-family: var(--sans-serif);
  font-size: 1.1rem;
  font-weight: 600;
}

.doc-two-column > div {
  padding: 0;
}

@media (max-width: 720px) {
  .doc-two-column {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}

.doc-panel-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.25rem;
}

.doc-panel-grid .doc-section {
  margin: 0;
  height: 100%;
}

.doc-panel-grid--responsive-3 {
  width: 100%;
  box-sizing: border-box;
  padding: 0;
}

@media (min-width: 760px) {
  .doc-panel-grid--responsive-3 {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1.25rem;
  }
}

@media (min-width: 1240px) {
  .doc-panel-grid--responsive-3 {
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 1.5rem;
  }

  .doc-panel-grid--responsive-3 .doc-section.success {
    grid-column: auto;
  }
}

/* Admin Metrics Dashboard styles */
.metrics-dashboard {
  max-width: 1400px;
  margin: 0 auto;
  padding: 2rem 1.5rem;
  font-family: var(--sans-serif);
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2.5rem;
  padding: 2rem;
  gap: 2rem;
  background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
  border: 1px solid #e9ecef;
  border-radius: 4px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);
}

.dashboard-header h1 {
  margin: 0 0 0.5rem 0;
  color: #1a3a6b;
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: -0.3px;
}

.dashboard-header p {
  margin: 0;
  color: #6c757d;
  font-size: 0.95rem;
}

.dashboard-header .status-section {
  text-align: right;
}

.dashboard-header .timestamp {
  color: #6c757d;
  font-size: 0.85rem;
  margin-top: 0.5rem;
}

.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.metric-card {
  background: #ffffff;
  border: 1px solid #dee2e6;
  border-radius: 4px;
  padding: 1.75rem;
  border-left: 4px solid #2c5aa0;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.metric-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transform: translateY(-1px);
}

.metric-card h2 {
  margin: 0 0 1.25rem 0;
  font-size: 1.15rem;
  color: #1a3a6b;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  letter-spacing: -0.2px;
}

.metric-row {
  display: flex;
  justify-content: space-between;
  padding: 0.75rem 0;
  border-bottom: 1px solid #f1f3f5;
}

.metric-row:last-child {
  border-bottom: none;
}

.metric-label {
  color: #495057;
  font-size: 0.9rem;
  font-weight: 500;
}

.metric-value {
  font-weight: 600;
  color: #212529;
  font-size: 0.95rem;
}

.metric-value.success {
  color: #28a745;
  font-weight: 700;
}

.metric-value.warning {
  color: #ffc107;
  font-weight: 700;
}

.metric-value.danger {
  color: #dc3545;
  font-weight: 700;
}

.status-badge {
  display: inline-block;
  padding: 0.5rem 1.25rem;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.3px;
}

.status-badge.healthy {
  background: #d4edda;
  color: #155724;
}

.status-badge.error {
  background: #f8d7da;
  color: #721c24;
}

.duration-bars {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid #e9ecef;
}

.duration-bars h3 {
  margin: 0 0 1rem 0;
  font-size: 0.95rem;
  color: #1a3a6b;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.duration-bar {
  margin-bottom: 1rem;
}

.duration-bar-label {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.4rem;
  font-size: 0.85rem;
  color: #495057;
  font-weight: 500;
}

.duration-bar-track {
  width: 100%;
  height: 20px;
  background: #e9ecef;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
}

.duration-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #2c5aa0, #4a7fc7);
  transition: width 0.3s ease;
}

.error-message {
  background: #fff3cd;
  border: 1px solid #ffeeda;
  border-left: 4px solid #ffc107;
  border-radius: 4px;
  padding: 1.25rem;
  color: #856404;
  margin-bottom: 1.5rem;
  font-weight: 500;
}

.action-buttons {
  display: flex;
  gap: 1rem;
  margin-top: 2.5rem;
  flex-wrap: wrap;
}

.btn {
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  transition: all 0.2s ease;
  letter-spacing: 0.3px;
}

.btn-primary {
  background: #2c5aa0;
  color: white;
  box-shadow: 0 2px 4px rgba(44, 90, 160, 0.2);
}

.btn-primary:hover {
  background: #1a3a6b;
  box-shadow: 0 4px 8px rgba(44, 90, 160, 0.3);
  transform: translateY(-1px);
}

.btn-secondary {
  background: #6c757d;
  color: white;
  box-shadow: 0 2px 4px rgba(108, 117, 125, 0.2);
}

.btn-secondary:hover {
  background: #5a6268;
  box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
  transform: translateY(-1px);
}

@media (max-width: 720px) {
  .public-header .tagline {
    display: none;
  }

  .doc-article-header,
  .doc-article-body,
  .doc-section {
    padding: 1.25rem;
  }

  .doc-panel-grid {
    gap: 1rem;
  }

  .doc-panel-grid--responsive-3 {
    padding: 0;
  }

  .doc-article-header h1 {
    font-size: 1.8rem;
  }

  .doc-article {
    width: calc(100% - 1.5rem);
    max-width: 100%;
    margin: 1rem auto;
  }
}
