/**
 * PayCalCore - Accessibility Module (a11y)
 * 
 * Keyboard navigation, focus management, modal handling, dialog focus traps.
 * 
 * IMPORT:
 *   import A11yModule from '/js/core/a11y.js';
 */

import PW from '/js/phantomwing/';

const A11yModule = (state, getElementFn, queryFn, queryAllFn, textToSpeechFn, configObj) => (() => {

  /**
   * Get all focusable elements within a container.
   */
  function getFocusableElements(container) {
    if (!container) return [];
    const selector = [
      'a[href]',
      'button:not([disabled])',
      'input:not([disabled]):not([type="hidden"])',
      'select:not([disabled])',
      'textarea:not([disabled])',
      '[tabindex]:not([tabindex="-1"])'
    ].join(',');

    return queryAllFn(selector, container).filter((el) => {
      if (!(el instanceof HTMLElement)) return false;
      return el.offsetParent !== null || el === document.activeElement;
    });
  }

  /**
   * Trap focus within a container (e.g., modal).
   * Prevents Tab from exiting the container.
   */
  function trapFocusWithin(container, event) {
    if (event.key !== 'Tab') return false;

    const focusableElements = getFocusableElements(container);
    if (focusableElements.length === 0) {
      event.preventDefault();
      return true;
    }

    const first = focusableElements[0];
    const last = focusableElements[focusableElements.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && (active === first || !container.contains(active))) {
      event.preventDefault();
      last.focus();
      return true;
    }

    if (!event.shiftKey && (active === last || !container.contains(active))) {
      event.preventDefault();
      first.focus();
      return true;
    }

    return false;
  }

  /**
   * Add audio feedback on input focus (read field value if enabled).
   */
  function addAudioFocusListener(el, prefix = "", suffix = "") {
    if (!el) return;
    el.addEventListener("focus", (event) => {
      const inputValue = event.target?.value;
      if (state.audio_feedback === "all" && inputValue) {
        try {
          textToSpeechFn(prefix + " " + inputValue.toString() + " " + suffix);
        } catch {}
      }
    });
  }

  /**
   * Open modal dialog with accessibility features.
   * - Sets aria-modal="true"
   * - Auto-focuses first focusable element
   * - Announces opening via text-to-speech
   * - Stores last focused element for restoration
   */
  function openModal(id, text = "") {
    const el = getElementFn(id);
    if (!el) return;
    
    if (el instanceof HTMLDialogElement) {
      ensureDialogChrome(el);
      if (el.open) {
        state.modal_is_active = true;
        return;
      }
      state.lastFocused = document.activeElement;
      el.showModal();
    } else {
      state.lastFocused = document.activeElement;
      el.classList.remove('hidden');
      el.classList.add('display-flex');
    }
    
    state.modal_is_active = true;
    el.setAttribute('aria-modal', 'true');
    
    const firstFocusable = el.querySelector('a[href], input, button, textarea, select, [tabindex]:not([tabindex="-1"])');
    if (firstFocusable) firstFocusable.focus();
    
    if (state.audio_feedback === "all") {
      try {
        textToSpeechFn(configObj.OPENED_DIALOG + ` ${text}`);
      } catch {}
    }
    
    el.setAttribute('aria-hidden', 'false');
  }

  /**
   * Close modal dialog with accessibility features.
   * - Restores last focused element
   * - Announces closing via text-to-speech
   * - Resets aria attributes
   */
  function closeModal(id, text = "") {
    const el = getElementFn(id);
    if (!el) return;
    
    if (el instanceof HTMLDialogElement) {
      if (el.open) el.close();
    } else {
      el.classList.add('hidden');
      el.classList.remove('display-flex');
    }
    
    el.setAttribute('aria-hidden', 'true');
    state.modal_is_active = !!queryFn('dialog[open]');
    
    if (!state.modal_is_active && state.lastFocused && typeof state.lastFocused.focus === 'function') {
      state.lastFocused.focus();
    }
    if (!state.modal_is_active) {
      state.lastFocused = null;
    }
    
    if (state.audio_feedback === "all") {
      try {
        textToSpeechFn(configObj.CLOSED_DIALOG + ` ${text}`);
      } catch {}
    }
  }

  /**
   * Ensure dialog element has proper chrome for accessibility.
   */
  function ensureDialogChrome(el) {
    if (!(el instanceof HTMLDialogElement)) return;

    // Ensure ARIA labels from existing title/description elements
    const safeIdBase = el.id && el.id.trim() !== '' ? el.id : 'dialog';
    const titleEl = el.querySelector('.modal_title, h1, h2, h3');
    if (titleEl && !el.hasAttribute('aria-labelledby')) {
      if (!titleEl.id) titleEl.id = `${safeIdBase}_title`;
      el.setAttribute('aria-labelledby', titleEl.id);
    }
    const descEl = el.querySelector('.modal_content, .modal_aria, p');
    if (descEl && !el.hasAttribute('aria-describedby')) {
      if (!descEl.id) descEl.id = `${safeIdBase}_desc`;
      el.setAttribute('aria-describedby', descEl.id);
    }

    // Inject close button if missing
    if (el.id) {
      const header = el.querySelector('.modal_header');
      if (header && !header.querySelector('.btn_close, .modal_close, [data-dialog-close]')) {
        const closeButton = document.createElement('button');
        closeButton.type = 'button';
        closeButton.className = 'btn btn_close';
        closeButton.setAttribute('data-dialog-close', el.id);
        closeButton.setAttribute('aria-label', 'Close');
        closeButton.textContent = '×';
        header.prepend(closeButton);
      }
    }

    // Add focus trap + Escape listener if not already bound
    if (!el.dataset.focusTrapBound) {
      el.addEventListener('keydown', (event) => {
        if (event.key === 'Tab') {
          trapFocusWithin(el, event);
        } else if (event.key === 'Escape') {
          el.close();
        }
      });
      el.dataset.focusTrapBound = 'true';
    }
  }

  function ensureAllDialogsChrome() {
    queryAllFn('dialog').forEach((dialog) => ensureDialogChrome(dialog));
  }

  return {
    getFocusableElements,
    trapFocusWithin,
    addAudioFocusListener,
    openModal,
    closeModal,
    ensureDialogChrome,
    ensureAllDialogsChrome,
  };
})();

export default A11yModule;
