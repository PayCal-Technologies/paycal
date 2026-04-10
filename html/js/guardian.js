/*
 * Guardian
 *
 * TrustedHTML-oriented DOM write guard for PayCal frontend modules.
 *
 * Why this exists:
 * - Centralize safe HTML insertion patterns used by calendar, core UI, and diagnostics panels.
 * - Strip active content and inline handlers before any markup is inserted into the document.
 * - Use Trusted Types policies when available, with a safe string fallback for older browsers.
 *
 * Internal contracts:
 * - Exposes `window.Guardian` exactly once and never overwrites an existing implementation.
 * - `setHTML`, `insertHTML`, and `replaceOuterHTML` always sanitize first.
 * - Sanitizer removes blocked tags and JS URL/event-handler vectors.
 */

(function bootstrapPayCalGuardian() {
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    return;
  }

  const escapeTextForHTML = (value) => {
    const span = document.createElement('span');
    span.textContent = value == null ? '' : String(value);
    return span.innerHTML;
  };

  if (window.Guardian) {
    if (typeof window.Guardian.sanitizedText !== 'function') {
      window.Guardian.sanitizedText = escapeTextForHTML;
    }
    return;
  }

  const hasTrustedTypes = typeof window.trustedTypes !== 'undefined'
    && typeof window.trustedTypes.createPolicy === 'function';

  const toStringValue = (value) => value == null ? '' : String(value);

  // Formal anti-correlation policy surface used by diagnostics and telemetry callers.
  const metadataCorrelationPolicy = Object.freeze({
    version: '2026-03-23',
    defaultDecision: 'deny',
    allowedContexts: Object.freeze([
      'security-incident',
      'fraud-investigation',
      'regulatory-legal-hold',
    ]),
    prohibitedPairs: Object.freeze([
      Object.freeze(['profile_pii', 'productivity_events']),
      Object.freeze(['profile_pii', 'telemetry_content']),
      Object.freeze(['account_identity', 'work_hours_detail']),
    ]),
    requiredControls: Object.freeze([
      'documented_ticket',
      'least_privilege_approval',
      'time_bounded_access',
      'audit_log_entry',
    ]),
  });

  function getMetadataCorrelationPolicy() {
    return metadataCorrelationPolicy;
  }

  function canCorrelateMetadata(context) {
    const key = toStringValue(context).trim().toLowerCase();
    return metadataCorrelationPolicy.allowedContexts.includes(key);
  }

  function sanitizeHTML(input) {
    const raw = toStringValue(input);
    if (raw === '') return '';

    const template = document.createElement('template');
    template.innerHTML = raw;

    const blockedSelector = 'script, iframe, object, embed, link[rel="import"], meta[http-equiv="refresh"], svg script, math script, foreignObject';
    template.content.querySelectorAll(blockedSelector).forEach((el) => el.remove());

    const walker = document.createTreeWalker(template.content, NodeFilter.SHOW_ELEMENT);
    let node = walker.currentNode;

    while (node) {
      if (node.attributes) {
        Array.from(node.attributes).forEach((attr) => {
          const name = attr.name.toLowerCase();
          const value = (attr.value || '').trim();

          if (name.startsWith('on')) {
            node.removeAttribute(attr.name);
            return;
          }

          if (name === 'style') {
            node.removeAttribute(attr.name);
            return;
          }

          if (name === 'srcdoc') {
            node.removeAttribute(attr.name);
            return;
          }

          if ((name === 'href' || name === 'src' || name === 'xlink:href' || name === 'formaction')
            && /^javascript:/i.test(value)) {
            node.removeAttribute(attr.name);
          }
        });
      }
      node = walker.nextNode();
    }

    return template.innerHTML;
  }

  // Compatibility helper: return escaped text-safe markup for legacy callers.
  function sanitizedText(input) {
    return escapeTextForHTML(toStringValue(input));
  }

  let defaultPolicy = null;
  let paycalPolicy = null;

  if (hasTrustedTypes) {
    try {
      defaultPolicy = window.trustedTypes.createPolicy('default', {
        createHTML: (markup) => markup,
        createScriptURL: (url) => toStringValue(url)
      });
    } catch {}

    try {
      paycalPolicy = window.trustedTypes.createPolicy('paycal', {
        createHTML: (markup) => markup,
        createScriptURL: (url) => toStringValue(url)
      });
    } catch {}
  }

  function toTrustedHTML(markup) {
    const sanitized = sanitizeHTML(markup);

    if (paycalPolicy && typeof paycalPolicy.createHTML === 'function') {
      try {
        return paycalPolicy.createHTML(sanitized);
      } catch {}
    }

    if (defaultPolicy && typeof defaultPolicy.createHTML === 'function') {
      try {
        return defaultPolicy.createHTML(sanitized);
      } catch {}
    }

    return sanitized;
  }

  function setHTML(el, markup) {
    if (!el) return;
    el.innerHTML = toTrustedHTML(markup);
  }

  function insertHTML(el, position, markup) {
    if (!el) return;
    el.insertAdjacentHTML(position, toTrustedHTML(markup));
  }

  function replaceOuterHTML(el, markup) {
    if (!el) return;
    el.outerHTML = toTrustedHTML(markup);
  }

  window.Guardian = {
    sanitizedText,
    sanitizeHTML,
    toTrustedHTML,
    setHTML,
    insertHTML,
    replaceOuterHTML,
    getMetadataCorrelationPolicy,
    canCorrelateMetadata,
  };
})();

(function initPublicLanguageSwitcherToggle() {
  if (typeof window === 'undefined' || typeof document === 'undefined') {
    return;
  }

  const SWITCHER_SELECTOR = '#page_header .nav_language_switcher';
  const BUTTON_SELECTOR = '#page_header .nav_language_current';

  const closeAll = () => {
    document.querySelectorAll(SWITCHER_SELECTOR + '.is-open').forEach((switcher) => {
      switcher.classList.remove('is-open');
      const button = switcher.querySelector('.nav_language_current');
      if (button instanceof HTMLElement) {
        button.setAttribute('aria-expanded', 'false');
      }
    });
  };

  const onToggleClick = (event) => {
    const button = event.currentTarget;
    if (!(button instanceof HTMLElement)) {
      return;
    }

    const switcher = button.closest('.nav_language_switcher');
    if (!(switcher instanceof HTMLElement)) {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    const willOpen = !switcher.classList.contains('is-open');
    closeAll();
    if (willOpen) {
      switcher.classList.add('is-open');
      button.setAttribute('aria-expanded', 'true');
    }
  };

  const init = () => {
    document.querySelectorAll(BUTTON_SELECTOR).forEach((button) => {
      button.removeEventListener('click', onToggleClick);
      button.addEventListener('click', onToggleClick);
    });
  };

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (target instanceof Element && target.closest(SWITCHER_SELECTOR)) {
      return;
    }
    closeAll();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      closeAll();
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
