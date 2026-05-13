<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - Help Page Styles
 * 
 * Extracted from main CSS index.php
 * Date: March 1, 2026
 */

/* HELP PAGE STYLES */
.help-body {
  font-family: var(--sans-serif);
  margin: 20px;
}

.help-body h1,
.help-body h2 {
  color: #333;
}

.help-body ul {
  list-style-type: disc;
  margin-left: 20px;
}

.help-body a {
  color: #007bff;
  text-decoration: none;
}

.help-body a:hover {
  text-decoration: underline;
}

.help_article {
  margin: 4rem 0;
  padding-bottom: 3rem;
  border-bottom: 1px solid var(--border-color, #ddd);
}

.help_article:last-child {
  padding-bottom: 0;
  border-bottom: none;
}

.help_article h3 {
  margin: 2.5rem 0 1.5rem 0;
  font-size: var(--font-lg);
}

.help_article h4 {
  margin: 2rem 0 1rem 0;
  font-size: var(--font-md);
}

.help_article h5 {
  margin: 1.5rem 0 0.8rem 0;
  font-size: var(--font-sm);
}

.help_article p {
  margin: 1rem 0;
}

.help_article ol {
  margin: 1.5rem 0;
  padding-left: 1.5rem;
}

.help_article ol li {
  margin-bottom: 0.8rem;
}

.help_article dl {
  margin: 1.5rem 0;
}

.help_article dd {
  margin-bottom: 0.8rem;
}

.help_article table {
  margin: 15px 0;
}

.help-figure {
  display: grid;
  gap: var(--pad-sm);
  margin: 2rem 0;
}

.help-image-button {
  display: block;
  width: 100%;
  padding: 0;
  background: none;
  border: none;
  cursor: zoom-in;
}

.help-image-button:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 4px;
}

.help-image-thumb,
.help-image-full {
  display: block;
  width: 100%;
  height: auto;
  border: 1px solid var(--color-border-soft, var(--border-color, #ddd));
  border-radius: var(--radius-md, 12px);
  box-shadow: var(--shadow-sm, 0 8px 24px rgba(0, 0, 0, 0.12));
}

.help-image-thumb {
  max-width: min(100%, 48rem);
}

.help-figure figcaption {
  color: var(--color-text-muted, inherit);
  font-size: 0.95rem;
}

.help-image-popover {
  inset: 0;
  display: none;
  place-items: center;
  width: 100vw;
  height: 100vh;
  max-width: none;
  max-height: none;
  padding: min(4vw, 1.5rem);
  border: none;
  background: var(--overlay-backdrop, rgba(0, 0, 0, 0.7));
  color: inherit;
}

.help-image-popover[hidden] {
  display: none !important;
}

.help-image-popover:popover-open,
.help-image-popover.is-open {
  display: grid;
}

.help-image-popover::backdrop {
  background: transparent;
}

.help-image-popover-card {
  display: inline-grid;
  place-self: center;
  gap: var(--pad-sm);
  width: fit-content;
  max-width: min(92vw, 68rem);
  max-height: 100%;
  padding: var(--pad-md);
  background: var(--dialog-bg, var(--color-surface, #111));
  color: var(--dialog-text, var(--color-text, #f5f5f5));
  border: 1px solid var(--dialog-border, var(--color-border-soft, #444));
  border-radius: var(--radius-lg, 16px);
  box-shadow: var(--dialog-shadow, var(--shadow-lg, 0 18px 50px rgba(0, 0, 0, 0.35)));
}

.help-image-popover-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--pad-sm);
}

.help-image-popover-header h4 {
  margin: 0;
}

.help-image-full {
  width: 100%;
  max-width: min(92vw, 68rem);
  max-height: calc(100vh - 8rem);
  object-fit: contain;
}

@media (max-width: 640px) {
  .help-image-popover-card {
    width: min(100%, 72rem);
    padding: var(--pad-sm);
  }

  .help-image-popover-header {
    align-items: flex-start;
    flex-direction: column;
  }
}

#help_main {
  min-width: 0;
}

/* Diamond bullets for help article lists */
.help_article ul {
  margin: 1.5rem 0;
  padding-left: 0;
  list-style: none;
}

.help_article ul li {
  position: relative;
  margin-bottom: 0.8rem;
  padding-left: 1.5rem;
}

.help_article ul li::before {
  content: "◆";
  position: absolute;
  left: 0;
  color: var(--color-primary);
}

/* Tax Tables */
.tax-table {
  width: 100%;
  max-width: 50%;
  margin: 2rem 0;
  border-collapse: collapse;
}

.tax-table th,
.tax-table td {
  padding: 0.75rem;
  border-bottom: 1px solid var(--border-color, #ddd);
  text-align: left;
}

.tax-table th {
  background-color: var(--panel-head-bg);
  font-weight: bold;
  color: var(--panel-head-text);
}

/* Cards with metrics for help pages */
.cards .card .card-body .metric {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem 1rem;
}

.cards .card .card-body .metric span {
  flex: 1;
  font-size: var(--font-sm);
}

.cards .card .card-body .metric strong {
  font-size: 0.9rem;
  text-align: right;
}

#help_main > article.doc-article {
  display: block;
}
