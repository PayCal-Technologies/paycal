/**
 * Content View Switcher — Text View and PDF View
 *
 * PURPOSE:
 *   Injects "Text" and "PDF" view-mode icon buttons into .doc-article-header
 *   on public content pages (help, transparency, about, blog, media, policies).
 *
 * TEXT VIEW:
 *   Opens a <dialog> with a structured plain-text extraction of the article.
 *   Useful for screen readers, offline copying, or low-bandwidth reading.
 *
 * PDF VIEW:
 *   Calls window.print(). The @media print rules in content-views CSS strip
 *   navigation chrome so the browser's "Save as PDF" produces clean output.
 *
 * LOADED VIA:
 *   footer.php emits Render::jsScript('content-views') for doc-page types.
 *   This file is a static asset; no PHP rendering required.
 *
 * SECURITY:
 *   No inline styles or inline event handlers.
 *   All presentation handled through CSS classes defined in content-views CSS.
 *   Dialog element created once and reused across invocations.
 */

'use strict';

/**
 * Walk a DOM subtree and produce structured plain-text lines.
 *
 * @param {Element} root
 * @returns {string}
 */
function extractText(root) {
  const lines = [];

  function walk(node) {
    if (node.nodeType === Node.TEXT_NODE) {
      const t = node.textContent.trim();
      if (t) lines.push(t);
      return;
    }
    if (node.nodeType !== Node.ELEMENT_NODE) return;

    const tag = node.tagName.toLowerCase();
    if (tag === 'script' || tag === 'style') return;

    switch (tag) {
      case 'h1': {
        const text = node.textContent.trim().toUpperCase();
        lines.push('');
        lines.push('='.repeat(Math.min(text.length + 4, 72)));
        lines.push('  ' + text);
        lines.push('='.repeat(Math.min(text.length + 4, 72)));
        return;
      }
      case 'h2': {
        const text = node.textContent.trim();
        lines.push('');
        lines.push(text);
        lines.push('-'.repeat(Math.min(text.length, 72)));
        return;
      }
      case 'h3': {
        lines.push('');
        lines.push('### ' + node.textContent.trim());
        return;
      }
      case 'h4':
      case 'h5':
      case 'h6': {
        lines.push('');
        lines.push('#### ' + node.textContent.trim());
        return;
      }
      case 'p': {
        const text = node.textContent.trim();
        if (text) {
          lines.push('');
          lines.push(text);
        }
        return;
      }
      case 'li': {
        lines.push('  \u2022 ' + node.textContent.trim());
        return;
      }
      case 'br': {
        lines.push('');
        return;
      }
      case 'hr': {
        lines.push('-'.repeat(72));
        return;
      }
      case 'tr': {
        const cells = [];
        node.querySelectorAll('th, td').forEach(cell => cells.push(cell.textContent.trim()));
        if (cells.length) lines.push(cells.join(' | '));
        return;
      }
      default: {
        for (const child of node.childNodes) walk(child);
      }
    }
  }

  walk(root);
  return lines.join('\n').replace(/\n{3,}/g, '\n\n').trim();
}

/**
 * Build the full plain-text content for the text-view dialog.
 *
 * @param {Element} article
 * @returns {string}
 */
function buildViewContent(article) {
  const parts = [];

  const header = article.querySelector('.doc-article-header');
  if (header) {
    const h1 = header.querySelector('h1');
    if (h1) {
      const text = h1.textContent.trim().toUpperCase();
      parts.push('='.repeat(Math.min(text.length + 4, 72)));
      parts.push('  ' + text);
      parts.push('='.repeat(Math.min(text.length + 4, 72)));
    }
    const deck = header.querySelector('.deck');
    if (deck) {
      parts.push('');
      parts.push(deck.textContent.trim());
    }
    const meta = header.querySelector('.doc-article-meta');
    if (meta) {
      parts.push('');
      parts.push(meta.textContent.trim());
    }
  }

  const body = article.querySelector('.doc-article-body') || article;
  const bodyText = extractText(body);
  if (bodyText) {
    parts.push('');
    parts.push(bodyText);
  }

  parts.push('');
  parts.push('-'.repeat(72));
  parts.push(window.location.href);

  return parts.join('\n');
}

/**
 * Open (or reuse) the text-view dialog for the given article element.
 *
 * @param {Element} article
 */
function openTextView(article) {
  let dialog = /** @type {HTMLDialogElement|null} */ (document.getElementById('doc-text-view-dialog'));

  if (!dialog) {
    dialog = /** @type {HTMLDialogElement} */ (document.createElement('dialog'));
    dialog.id = 'doc-text-view-dialog';
    dialog.className = 'doc-text-view-dialog';
    dialog.setAttribute('aria-label', 'Plain text view of article');

    const toolbar = document.createElement('div');
    toolbar.className = 'doc-text-view-toolbar';

    const titleEl = document.createElement('span');
    titleEl.className = 'doc-text-view-title';
    titleEl.textContent = 'Text View';

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'doc-text-view-close';
    closeBtn.textContent = '\u2715 Close';
    closeBtn.setAttribute('aria-label', 'Close text view');
    closeBtn.addEventListener('click', () => dialog.close());

    toolbar.appendChild(titleEl);
    toolbar.appendChild(closeBtn);

    const pre = document.createElement('pre');
    pre.className = 'doc-text-view-pre';
    pre.id = 'doc-text-view-pre';

    dialog.appendChild(toolbar);
    dialog.appendChild(pre);
    document.body.appendChild(dialog);

    dialog.addEventListener('close', () => {
      const opener = /** @type {HTMLElement|null} */ (document.querySelector('.doc-view-btn[data-view="text"]'));
      if (opener) opener.focus();
    });

    // Close on backdrop click (click outside the dialog content)
    dialog.addEventListener('click', (e) => {
      if (e.target === dialog) dialog.close();
    });
  }

  const pre = document.getElementById('doc-text-view-pre');
  if (pre) pre.textContent = buildViewContent(article);

  dialog.showModal();
}

/**
 * Inject the view-switcher toolbar into a doc-article-header element.
 *
 * @param {Element} article
 */
function injectSwitcher(article) {
  const header = article.querySelector('.doc-article-header');
  if (!header || header.querySelector('.doc-view-switcher')) return;

  const switcher = document.createElement('div');
  switcher.className = 'doc-view-switcher';
  switcher.setAttribute('role', 'group');
  switcher.setAttribute('aria-label', 'Content view options');

  // Text view button (lines icon)
  const textBtn = document.createElement('button');
  textBtn.type = 'button';
  textBtn.className = 'doc-view-btn';
  textBtn.setAttribute('data-view', 'text');
  textBtn.setAttribute('title', 'Text view');
  textBtn.setAttribute('aria-label', 'Open plain text view');
  textBtn.innerHTML =
    '<svg width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true" focusable="false">' +
    '<rect x="1" y="2.5" width="13" height="1.4" rx="0.5" fill="currentColor"/>' +
    '<rect x="1" y="6"   width="11" height="1.4" rx="0.5" fill="currentColor"/>' +
    '<rect x="1" y="9.5" width="12" height="1.4" rx="0.5" fill="currentColor"/>' +
    '<rect x="1" y="13"  width="8"  height="1.4" rx="0.5" fill="currentColor"/>' +
    '</svg>' +
    '<span class="doc-view-label">Text</span>';
  textBtn.addEventListener('click', () => openTextView(article));

  // PDF / print button (printer icon)
  const pdfBtn = document.createElement('button');
  pdfBtn.type = 'button';
  pdfBtn.className = 'doc-view-btn';
  pdfBtn.setAttribute('data-view', 'pdf');
  pdfBtn.setAttribute('title', 'Print or save as PDF');
  pdfBtn.setAttribute('aria-label', 'Print or save as PDF');
  pdfBtn.innerHTML =
    '<svg width="15" height="15" viewBox="0 0 15 15" fill="none" aria-hidden="true" focusable="false">' +
    '<rect x="3"   y="1"   width="9" height="5"   rx="0.5" stroke="currentColor" stroke-width="1.1" fill="none"/>' +
    '<rect x="1"   y="5.5" width="13" height="5"  rx="1"   stroke="currentColor" stroke-width="1.1" fill="none"/>' +
    '<rect x="3.5" y="9.5" width="8"  height="4.5" rx="0.5" stroke="currentColor" stroke-width="1.1" fill="none"/>' +
    '<circle cx="11.5" cy="8" r="0.8" fill="currentColor"/>' +
    '</svg>' +
    '<span class="doc-view-label">PDF</span>';
  pdfBtn.addEventListener('click', () => window.print());

  switcher.appendChild(textBtn);
  switcher.appendChild(pdfBtn);
  header.appendChild(switcher);
}

function init() {
  document.querySelectorAll('.doc-article').forEach(injectSwitcher);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', init);
} else {
  init();
}
