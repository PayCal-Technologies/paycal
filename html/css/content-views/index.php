<?php declare(strict_types=1);

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - Content View Switcher CSS
 *
 * Styles for the Text and PDF view-mode link buttons injected server-side
 * by ContentView::renderSwitcher() into .doc-article-header, and the
 * @media print rules that produce clean PDF output via window.print().
 *
 * Buttons are <a> elements so they work with or without JavaScript.
 * The text view and PDF view are server-side endpoints (?view=text, ?view=pdf).
 */

/* ------------------------------------------------------------------ */
/* View-switcher toolbar (server-side rendered into .doc-article-header) */
/* ------------------------------------------------------------------ */

.doc-article-header {
  position: relative;
}

.doc-view-switcher {
  display: flex;
  gap: 0.35rem;
  align-items: center;
  position: absolute;
  top: 1.1rem;
  right: 1.25rem;
}

.doc-view-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.28rem 0.6rem;
  border: 1px solid var(--panel-border);
  border-radius: 3px;
  background: var(--panel-bg);
  color: var(--text-muted, var(--text-color));
  font-size: 0.78rem;
  font-family: var(--sans-serif);
  line-height: 1;
  text-decoration: none;
  white-space: nowrap;
  cursor: pointer;
  transition: color 0.15s, border-color 0.15s, background 0.15s;
}

.doc-view-btn:hover {
  color: var(--color-primary);
  border-color: var(--color-primary);
  background: color-mix(in srgb, var(--color-primary) 6%, var(--panel-bg));
}

.doc-view-btn:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 1px;
  color: var(--color-primary);
  border-color: var(--color-primary);
}

.doc-view-btn svg {
  flex-shrink: 0;
  vertical-align: middle;
}

.doc-view-label {
  display: inline;
}

@media (max-width: 600px) {
  .doc-view-switcher {
    position: static;
    justify-content: flex-end;
    padding: 0.5rem 0 0 0;
  }
}

/* ------------------------------------------------------------------ */
/* Print / PDF output — triggered by window.print() on ?view=pdf       */
/* Hides navigation chrome; renders only .doc-article content.         */
/* ------------------------------------------------------------------ */

@media print {
  #page_header,
  #page_footer,
  .doc-view-switcher,
  .doc-breadcrumb,
  .nav_component,
  #modal_session_timeout,
  #lens_footer_segment,
  .phantomwing_container,
  [class*="phantomwing"],
  .sidebar_toggle_accessible,
  dialog {
    display: none !important;
  }

  body {
    background: white;
    color: black;
  }

  .doc-article {
    width: 100%;
    max-width: none;
    border: none;
    box-shadow: none;
    margin: 0;
    background: white;
  }

  .doc-article-header {
    background: none;
    border-bottom: 2px solid #333;
    padding: 0.75rem 0;
  }

  .doc-article-header h1 {
    color: black;
    font-size: 1.75rem;
  }

  .doc-article-header .deck {
    color: #333;
  }

  .doc-article-body {
    padding: 1rem 0;
    color: black;
    font-size: 0.95rem;
  }

  .doc-section {
    border-left: 2px solid #555;
    background: none;
    box-shadow: none;
    break-inside: avoid;
  }

  .doc-section h2,
  .doc-section h3 {
    color: black;
  }

  .doc-panel-grid {
    display: block;
  }

  .doc-panel-grid .doc-section {
    margin-bottom: 1.25rem;
  }

  a[href]::after {
    content: " (" attr(href) ")";
    font-size: 0.78em;
    color: #555;
    word-break: break-all;
  }

  .doc-read-more::after {
    content: none;
  }
}

/* ------------------------------------------------------------------ */
/* View-switcher toolbar                                                */
/* ------------------------------------------------------------------ */

.doc-article-header {
  position: relative;
}

.doc-view-switcher {
  display: flex;
  gap: 0.35rem;
  align-items: center;
  position: absolute;
  top: 1.1rem;
  right: 1.25rem;
}

.doc-view-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.28rem 0.6rem;
  border: 1px solid var(--panel-border);
  border-radius: 3px;
  background: var(--panel-bg);
  color: var(--text-muted, var(--text-color));
  font-size: 0.78rem;
  font-family: var(--sans-serif);
  line-height: 1;
  cursor: pointer;
  white-space: nowrap;
  transition: color 0.15s, border-color 0.15s, background 0.15s;
}

.doc-view-btn:hover {
  color: var(--color-primary);
  border-color: var(--color-primary);
  background: color-mix(in srgb, var(--color-primary) 6%, var(--panel-bg));
}

.doc-view-btn:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 1px;
  color: var(--color-primary);
  border-color: var(--color-primary);
}

.doc-view-btn svg {
  flex-shrink: 0;
  vertical-align: middle;
}

/* ------------------------------------------------------------------ */
/* Text-view dialog — full-screen plain-text reader                    */
/* ------------------------------------------------------------------ */

.doc-text-view-dialog {
  position: fixed;
  inset: 0;
  width: 100dvw;
  max-width: 100dvw;
  height: 100dvh;
  max-height: 100dvh;
  margin: 0;
  padding: 0;
  border: none;
  background: var(--panel-bg);
  color: var(--text-color);
  display: flex;
  flex-direction: column;
  z-index: 9000;
  box-sizing: border-box;
}

.doc-text-view-dialog::backdrop {
  background: rgba(0, 0, 0, 0.82);
}

.doc-text-view-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.7rem 1.25rem;
  border-bottom: 1px solid var(--panel-border);
  background: color-mix(in srgb, var(--panel-bg) 90%, black 10%);
  flex-shrink: 0;
}

.doc-text-view-title {
  font-family: var(--sans-serif);
  font-size: 0.8rem;
  font-weight: 600;
  color: var(--text-muted, var(--text-color));
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.doc-text-view-close {
  padding: 0.3rem 0.8rem;
  border: 1px solid var(--panel-border);
  border-radius: 3px;
  background: transparent;
  color: var(--text-color);
  font-size: 0.85rem;
  font-family: var(--sans-serif);
  cursor: pointer;
  transition: background 0.15s;
}

.doc-text-view-close:hover {
  background: color-mix(in srgb, var(--panel-border) 35%, transparent);
}

.doc-text-view-close:focus-visible {
  outline: 2px solid var(--color-primary);
  outline-offset: 1px;
}

.doc-text-view-pre {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem 2rem;
  margin: 0;
  font-family: var(--monospace, monospace);
  font-size: 0.88rem;
  line-height: 1.7;
  color: var(--text-color);
  background: var(--panel-bg);
  white-space: pre-wrap;
  word-break: break-word;
  tab-size: 2;
}

@media (max-width: 600px) {
  .doc-view-switcher {
    position: static;
    justify-content: flex-end;
    padding: 0.5rem 0 0 0;
  }

  .doc-text-view-pre {
    padding: 1rem;
    font-size: 0.82rem;
  }
}

/* ------------------------------------------------------------------ */
/* Print / PDF output — called by window.print()                       */
/* Hides navigation chrome; renders only .doc-article content.         */
/* ------------------------------------------------------------------ */

@media print {
  #page_header,
  #page_footer,
  .doc-view-switcher,
  .doc-breadcrumb,
  .nav_component,
  #modal_session_timeout,
  #lens_footer_segment,
  .phantomwing_container,
  [class*="phantomwing"] {
    display: none !important;
  }

  body {
    background: white;
    color: black;
  }

  .doc-article {
    width: 100%;
    max-width: none;
    border: none;
    box-shadow: none;
    margin: 0;
    background: white;
  }

  .doc-article-header {
    background: none;
    border-bottom: 2px solid #333;
    padding: 0.75rem 0;
  }

  .doc-article-header h1 {
    color: black;
    font-size: 1.75rem;
  }

  .doc-article-header .deck {
    color: #333;
  }

  .doc-article-body {
    padding: 1rem 0;
    color: black;
    font-size: 0.95rem;
  }

  .doc-section {
    border-left: 2px solid #555;
    background: none;
    box-shadow: none;
    break-inside: avoid;
  }

  .doc-section h2,
  .doc-section h3 {
    color: black;
  }

  .doc-panel-grid {
    display: block;
  }

  .doc-panel-grid .doc-section {
    margin-bottom: 1.25rem;
  }

  a[href]::after {
    content: " (" attr(href) ")";
    font-size: 0.78em;
    color: #555;
    word-break: break-all;
  }

  .doc-read-more::after {
    content: none;
  }
}
