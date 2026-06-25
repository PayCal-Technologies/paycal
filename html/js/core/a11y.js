/**
 * PayCalCore - Accessibility Module (a11y)
 * 
 * Keyboard navigation, focus management, modal handling, dialog focus traps.
 * 
 * IMPORT:
 *   import A11yModule from '/js/core/a11y.js';
 */

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
   * Post-open a11y side effects shared by openModal() and the Invoker bridge.
   * Guarded so command+click paths cannot double-announce.
   */
  function applyModalOpenEffects(el, openTtsText = "") {
    if (!(el instanceof HTMLElement)) return;
    if (el.dataset.paycalOpenEffectsApplied === '1') return;
    el.dataset.paycalOpenEffectsApplied = '1';
    queueMicrotask(() => {
      delete el.dataset.paycalOpenEffectsApplied;
    });

    state.modal_is_active = true;
    el.setAttribute('aria-modal', 'true');
    el.setAttribute('aria-hidden', 'false');

    const firstFocusable = el.querySelector('a[href], input, button, textarea, select, [tabindex]:not([tabindex="-1"])');
    if (firstFocusable) firstFocusable.focus();

    if (state.audio_feedback === "all") {
      try {
        textToSpeechFn(configObj.OPENED_DIALOG + ` ${openTtsText}`);
      } catch {}
    }
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
      applyModalOpenEffects(el, text);
      return;
    }

    state.lastFocused = document.activeElement;
    el.classList.remove('hidden');
    el.classList.add('display-flex');
    applyModalOpenEffects(el, text);
  }

  /**
   * Post-close a11y side effects shared by closeModal() and the Invoker bridge.
   * Guarded so cancel+close or delegated+native paths cannot double-announce.
   */
  function applyModalCloseEffects(el, text = "") {
    if (!(el instanceof HTMLElement)) return;
    if (el.dataset.paycalCloseEffectsApplied === '1') return;
    el.dataset.paycalCloseEffectsApplied = '1';
    queueMicrotask(() => {
      delete el.dataset.paycalCloseEffectsApplied;
    });

    const active = document.activeElement;
    const focusWasInside = active instanceof HTMLElement && el.contains(active);
    if (focusWasInside) {
      active.blur();
    }

    if (!(document.activeElement instanceof HTMLElement) || !el.contains(document.activeElement)) {
      el.setAttribute('aria-hidden', 'true');
    }
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
   * Close modal dialog with accessibility features.
   * - Restores last focused element
   * - Announces closing via text-to-speech
   * - Resets aria attributes
   */
  function closeModal(id, text = "") {
    const el = getElementFn(id);
    if (!el) return;

    if (el instanceof HTMLDialogElement) {
      if (!el.open) return;

      const active = document.activeElement;
      if (active instanceof HTMLElement && el.contains(active)) {
        active.blur();
      }

      el.dataset.paycalCloseViaModal = '1';
      el.close();
      delete el.dataset.paycalCloseViaModal;
      applyModalCloseEffects(el, text);
      return;
    }

    const active = document.activeElement;
    const focusWasInside = active instanceof HTMLElement && el.contains(active);
    if (focusWasInside) {
      active.blur();
    }

    el.classList.add('hidden');
    el.classList.remove('display-flex');
    applyModalCloseEffects(el, text);
  }

  /**
   * Golden Invoker Commands + PayCal a11y bridge
   * -------------------------------------------
   * Dialog: data-dialog-invoker-bridge, optional data-dialog-open-tts / data-dialog-close-tts.
   *
   * Open control (when no JS prep is needed before showModal):
   *   commandfor="{dialogId}" command="show-modal"
   *   data-dialog-open="{dialogId}"  (legacy fallback for delegation → openModal)
   *
   * Close control:
   *   commandfor="{dialogId}" command="close"
   *   data-dialog-close="{dialogId}"  (legacy fallback for delegation → closeModal)
   *
   * Invoker-capable browsers: command="show-modal" opens natively; this bridge runs
   * applyModalOpenEffects (ensureDialogChrome, lastFocused, TTS, focus, aria, modal_is_active)
   * via the dialog command event and/or capture-phase open-control clicks. command="close"
   * (or Escape → cancel/close) closes natively; close/cancel runs applyModalCloseEffects.
   * Delegated [data-dialog-open]/[data-dialog-close] clicks are skipped when invoker is
   * active so openModal()/closeModal() do not double-fire.
   *
   * Legacy browsers: [data-dialog-open] / [data-dialog-close] delegation as before.
   * Open paths that need prep (form reset, fetched content, calendar pickers) still use
   * openModal(); only use command="show-modal" when no JS prep is required before showModal().
   */
  function bindDialogInvokerBridge(dialog) {
    if (!(dialog instanceof HTMLDialogElement)) return;
    if (dialog.dataset.invokerBridgeBound === '1') return;
    dialog.dataset.invokerBridgeBound = '1';

    const getCloseLabel = () => dialog.getAttribute('data-dialog-close-tts') || '';
    const getOpenLabel = () => dialog.getAttribute('data-dialog-open-tts') || '';

    const scheduleOpenEffects = (wasOpen) => {
      queueMicrotask(() => {
        if (!dialog.open) return;
        if (wasOpen) {
          state.modal_is_active = true;
          return;
        }
        applyModalOpenEffects(dialog, getOpenLabel());
      });
    };

    const prepareInvokerOpen = (source) => {
      ensureDialogChrome(dialog);
      const wasOpen = dialog.open;
      if (!wasOpen) {
        state.lastFocused = source instanceof HTMLElement ? source : document.activeElement;
      }
      return wasOpen;
    };

    const handleNativeClose = () => {
      if (dialog.dataset.paycalCloseViaModal === '1') return;
      applyModalCloseEffects(dialog, getCloseLabel());
    };

    dialog.addEventListener('close', handleNativeClose);
    dialog.addEventListener('cancel', handleNativeClose);

    dialog.addEventListener('command', (event) => {
      if (event.command !== 'show-modal') return;
      const wasOpen = prepareInvokerOpen(event.source);
      scheduleOpenEffects(wasOpen);
    });

    if (dialog.id) {
      const openSelector = `button[commandfor="${dialog.id}"][command="show-modal"]`;
      queryAllFn(openSelector).forEach((control) => {
        if (control.dataset.invokerOpenBound === '1') return;
        control.dataset.invokerOpenBound = '1';

        control.addEventListener('click', () => {
          const wasOpen = prepareInvokerOpen(control);
          scheduleOpenEffects(wasOpen);
        }, { capture: true });
      });
    }
  }

  function bindAllDialogInvokerBridges() {
    queryAllFn('dialog[data-dialog-invoker-bridge]').forEach((dialog) => {
      bindDialogInvokerBridge(dialog);
    });
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
        if (el.hasAttribute('data-dialog-invoker-bridge')) {
          closeButton.setAttribute('commandfor', el.id);
          closeButton.setAttribute('command', 'close');
        }
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
    applyModalOpenEffects,
    applyModalCloseEffects,
    bindDialogInvokerBridge,
    bindAllDialogInvokerBridges,
    ensureDialogChrome,
    ensureAllDialogsChrome,
  };
})();

export default A11yModule;
