/**
 * Sidebar Navigation — Click Toggle
 * States: collapsed | pinned
 *
 * collapsed : icon strip visible, blank rail toggles sidebar
 * pinned    : full sidebar, push model (content shifts), manual collapse only
 *
 * Proximity hover: auto-reveals sidebar when cursor nears the sidebar edge.
 * Controlled by PROXIMITY_STORAGE_KEY ('0' = off, '1' = on, default on).
 * Trigger distance controlled by PROXIMITY_PX_STORAGE_KEY (px, default 200, range 0-600).
 * Call NavToggle.setProximityEnabled(bool) / setProximityPx(number) from the settings UI.
 * Overlay auto-collapse timeout is controlled by overlay_sidebar_timeout_seconds in PayCalCore.config
 * (0 = never). Call NavToggle.setOverlaySidebarTimeout(number) from the settings UI.
 */
export default (() => {
  const STORAGE_KEY              = 'paycal_nav_state';       // '0' = collapsed, '1' = pinned
  const PROXIMITY_STORAGE_KEY    = 'paycal_nav_proximity';   // '0' = off, '1' = on (default on)
  const PROXIMITY_PX_STORAGE_KEY = 'paycal_nav_proximity_px'; // integer px (default 200)
  const OVERLAY_STORAGE_KEY      = 'paycal_nav_overlay';     // '1' = overlay, '0' = push (default push)
  const DEFAULT_LABEL_EXPAND = '';
  const DEFAULT_LABEL_COLLAPSE = '';
  const DEFAULT_ANNOUNCE_EXPANDED = '';
  const DEFAULT_ANNOUNCE_COLLAPSED = '';
  const COMPACT_DRAWER_MAX_PX = 768;

  let nav, primaryNav, status, main, toggle, skipLink;
  let state       = 'collapsed';
  let focusOrigin = null;
  let responsiveFrame = null;
  let hoverOpened = false;       // true only when proximity-hover opened the sidebar
  let proximityFrame = null;     // rAF handle for mousemove throttling
  let proximityEnabled = true;   // runtime flag; synced from localStorage on init
  let proximityPx = 200;         // trigger distance in px; synced from localStorage on init
  let overlayMode = false;       // runtime flag; synced from localStorage on init
  let overlaySidebarTimeoutSeconds = 5; // synced from PayCalCore.config on init
  let overlayIdleTimer = null;   // auto-collapse after pointer idle in overlay mode
  let overlayIdleMoveFrame = null;

  function isCompactDrawerViewport() {
    return window.matchMedia(`(max-width: ${COMPACT_DRAWER_MAX_PX}px)`).matches;
  }

  function syncResponsiveState() {
    const compact = isCompactDrawerViewport();
    document.body.toggleAttribute('data-nav-viewport-compact', compact);
    document.body.setAttribute('data-nav-top-density', 'full');

    if (!isSidebarMode()) return;

    if (compact) {
      if (state === 'pinned') {
        collapse(false);
      }
      return;
    }

    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved === '1' && state === 'collapsed') {
      pin();
    }
  }

  function queueResponsiveSync() {
    if (responsiveFrame !== null) {
      cancelAnimationFrame(responsiveFrame);
    }

    responsiveFrame = requestAnimationFrame(() => {
      responsiveFrame = null;
      syncResponsiveState();
    });
  }

  function isSidebarMode() {
    const pos = document.body.getAttribute('data-nav-primary-position');
    return pos === 'left' || pos === 'right';
  }

  function getOverlaySidebarTimeoutSeconds() {
    const seconds = Number(overlaySidebarTimeoutSeconds);
    return Number.isFinite(seconds) && seconds > 0 ? Math.min(30, Math.round(seconds)) : 0;
  }

  function clearOverlayIdleTimer() {
    if (overlayIdleTimer !== null) {
      clearTimeout(overlayIdleTimer);
      overlayIdleTimer = null;
    }
  }

  function shouldApplyOverlayTimeout() {
    return overlayMode && state === 'pinned' && getOverlaySidebarTimeoutSeconds() > 0;
  }

  function collapseFromOverlayTimeout() {
    if (!shouldApplyOverlayTimeout()) return;
    clearOverlayIdleTimer();
    collapse(false, hoverOpened);
  }

  function scheduleOverlayIdleCollapse() {
    clearOverlayIdleTimer();
    if (!shouldApplyOverlayTimeout()) return;

    overlayIdleTimer = setTimeout(() => {
      overlayIdleTimer = null;
      collapseFromOverlayTimeout();
    }, getOverlaySidebarTimeoutSeconds() * 1000);
  }

  function resetOverlayIdleTimer() {
    if (!shouldApplyOverlayTimeout()) {
      clearOverlayIdleTimer();
      return;
    }
    scheduleOverlayIdleCollapse();
  }

  function announce(msg) {
    if (!status) return;
    status.textContent = '';
    setTimeout(() => { status.textContent = msg; }, 10);
  }

  function getLabel(name, fallback) {
    return toggle?.dataset?.[name] || fallback;
  }

  function persistState(collapsed) {
    if (isCompactDrawerViewport()) return;

    const value = collapsed ? '0' : '1';
    localStorage.setItem(STORAGE_KEY, value);
    const navState = collapsed ? 'collapsed' : 'pinned';
    document.body.setAttribute('data-nav-initial-state', navState);

    // Use the settings form nonce only; other forms may have different nonce scopes.
    const csrf = document.querySelector('#account_style_form input[name="csrf_token"]');
    if (!(csrf instanceof HTMLInputElement) || !csrf.value) {
      return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrf.value);
    formData.append('nav_state_primary', navState);
    // Best-effort server persistence for initial render state; localStorage remains immediate fallback.
    fetch('/api/v1/settings/style/update/', {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    }).catch(() => {});
  }

  function setCollapsedInteractivity(collapsed) {
    const focusables = nav.querySelectorAll('a, button, [tabindex]');

    focusables.forEach((element) => {
      if (collapsed) {
        if (!element.hasAttribute('data-nav-tabindex')) {
          element.setAttribute('data-nav-tabindex', element.getAttribute('tabindex') ?? '');
        }
        element.setAttribute('tabindex', '-1');
      } else if (element.hasAttribute('data-nav-tabindex')) {
        const previousTabIndex = element.getAttribute('data-nav-tabindex');
        if (previousTabIndex === '') {
          element.removeAttribute('tabindex');
        } else {
          element.setAttribute('tabindex', previousTabIndex);
        }
        element.removeAttribute('data-nav-tabindex');
      }
    });
  }

  function syncAccessibleState() {
    const collapsed = state === 'collapsed';
    const expandLabel = getLabel('labelExpand', DEFAULT_LABEL_EXPAND);
    const collapseLabel = getLabel('labelCollapse', DEFAULT_LABEL_COLLAPSE);

    if (toggle) {
      toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
      toggle.setAttribute('aria-label', collapsed ? expandLabel : collapseLabel);
      toggle.textContent = collapsed ? expandLabel : collapseLabel;
    }

    if (primaryNav) {
      if (collapsed) {
        primaryNav.setAttribute('aria-hidden', 'true');
      } else {
        primaryNav.removeAttribute('aria-hidden');
      }
    }
  }

  function applyBodyClass(newState) {
    document.body.classList.remove('nav-collapsed', 'nav-peek', 'nav-pinned');
    document.body.classList.add('nav-' + newState);
    state = newState;
    syncAccessibleState();
  }

  function pin(fromHover = false) {
    if (!fromHover) hoverOpened = false;
    applyBodyClass('pinned');
    setCollapsedInteractivity(false);
    announce(getLabel('announceExpanded', DEFAULT_ANNOUNCE_EXPANDED));
    // Don't persist hover-only opens; the saved state should reflect the user's
    // deliberate choice so the next page load starts collapsed as expected.
    if (!fromHover) persistState(false);
    resetOverlayIdleTimer();
  }

  function collapse(returnFocus = false, fromHover = false) {
    clearOverlayIdleTimer();
    if (!fromHover) hoverOpened = false;
    // Blur before disabling collapsed items so focus never lands on hidden links.
    if (nav.contains(document.activeElement)) document.activeElement.blur();
    applyBodyClass('collapsed');
    setCollapsedInteractivity(true);
    announce(getLabel('announceCollapsed', DEFAULT_ANNOUNCE_COLLAPSED));
    // Don't double-write if hover is just cleaning up its own temporary open.
    if (!fromHover) persistState(true);
    if (returnFocus) {
      const target = focusOrigin && document.body.contains(focusOrigin)
        ? focusOrigin
        : main;
      focusOrigin = null;
      target?.focus();
    }
  }

  function isInteractiveSurface(target) {
    if (!(target instanceof Element)) return false;

    return Boolean(target.closest(
      'a, button, input, select, textarea, label, summary, [role="button"], [role="link"], [role="menuitem"], [role="tab"], [contenteditable="true"], dialog, [aria-modal="true"], [data-nav-ignore-collapse]'
    ));
  }

  return {
    init() {
      nav  = document.getElementById('page_header');
      primaryNav = document.getElementById('primary_navigation') ?? nav?.querySelector('.nav_menu--primary');
      main = document.getElementById('main') ?? document.querySelector('main');
      toggle = document.getElementById('sidebar_toggle_control');
      skipLink = document.getElementById('skip_to_content');
      if (!nav) return;

      const currentNavPosition = document.body.getAttribute('data-nav-primary-position');
      if (currentNavPosition !== 'left' && currentNavPosition !== 'right') {
        document.body.setAttribute('data-nav-primary-position', 'left');
      }

      queueResponsiveSync();
      window.addEventListener('resize', queueResponsiveSync, { passive: true });
      window.addEventListener('load', queueResponsiveSync, { once: true });

      if (!isSidebarMode()) return;

      // Polite live region
      status = document.createElement('div');
      status.id = 'sidebar_status';
      status.setAttribute('aria-live', 'polite');
      status.className = 'sr-only';
      document.body.appendChild(status);

      if (toggle) {
        toggle.addEventListener('click', () => {
          if (state === 'collapsed') {
            focusOrigin = toggle;
            pin();
            setTimeout(() => {
              nav.querySelector('a, button')?.focus();
            }, 50);
            return;
          }

          focusOrigin = toggle;
          collapse(false);
          toggle.focus();
        });
      }

      if (skipLink && main) {
        skipLink.addEventListener('click', () => {
          requestAnimationFrame(() => {
            main.focus();
          });
        });
      }

      nav.addEventListener('click', (event) => {
        if (event.target.closest('a, button, input, select, textarea, label')) return;

        if (state === 'pinned') {
          focusOrigin = null;
          collapse(false);
          return;
        }

        pin();
      });

      document.addEventListener('click', (event) => {
        if (state !== 'pinned') return;
        const target = event.target;
        if (!(target instanceof Element)) return;
        if (target.closest('#page_header')) return;
        if (isInteractiveSurface(target)) return;

        focusOrigin = null;
        collapse(false);
      });

      // Keyboard shortcut: bare backtick (`)
      document.addEventListener('keydown', (e) => {
        const el = e.target;
        if (!(el instanceof Element)) return;
        if (document.querySelector('dialog[open]')) return;
        if (el.isContentEditable) return;
        if (el.closest('input, textarea, select, [contenteditable="true"], [role="textbox"]')) return;
        if (el.closest?.('[role="dialog"], [aria-modal="true"]')) return;
        if (e.ctrlKey || e.metaKey || e.altKey || e.shiftKey) return;
        if (e.code !== 'Backquote') return;
        e.preventDefault();

        if (state === 'collapsed') {
          focusOrigin = document.activeElement;
          pin();
          setTimeout(() => {
            nav.querySelector('a, button')?.focus();
          }, 50);
          return;
        }
        if (state === 'pinned') {
          // Preserve the pre-open origin; only fall back to current if unset.
          if (!focusOrigin) focusOrigin = document.activeElement;
          collapse(true);
        }
      });

      // ESC collapses
      document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || state === 'collapsed') return;
        focusOrigin = null;
        collapse(true);
      });

      // Restore saved preference (default: pinned)
      const serverState = document.body.getAttribute('data-nav-initial-state');
      const saved = localStorage.getItem(STORAGE_KEY) ?? (serverState === 'pinned' ? '1' : '0');
      if (saved === '0') {
        applyBodyClass('collapsed');
        setCollapsedInteractivity(true);
        persistState(true);
      } else {
        applyBodyClass('pinned');
        setCollapsedInteractivity(false);
        persistState(false);
      }

      if (isCompactDrawerViewport()) {
        applyBodyClass('collapsed');
        setCollapsedInteractivity(true);
      }

      syncAccessibleState();

      // Load proximity preference ('1' = on by default).
      proximityEnabled = (localStorage.getItem(PROXIMITY_STORAGE_KEY) ?? '1') !== '0';

      // Load proximity distance preference (default 200 px, clamped 0–600).
      const storedPx = parseInt(localStorage.getItem(PROXIMITY_PX_STORAGE_KEY) ?? '200', 10);
      proximityPx = Number.isFinite(storedPx) ? Math.min(600, Math.max(0, storedPx)) : 200;

      // Load overlay preference ('0' = push model by default).
      overlayMode = (localStorage.getItem(OVERLAY_STORAGE_KEY) ?? '0') === '1';
      document.body.classList.toggle('nav-overlay-mode', overlayMode);

      const configTimeout = typeof window !== 'undefined'
        ? window.PayCalCore?.config?.overlay_sidebar_timeout_seconds
        : undefined;
      const parsedTimeout = Number(configTimeout);
      overlaySidebarTimeoutSeconds = Number.isFinite(parsedTimeout) ? parsedTimeout : 5;

      // Overlay idle timeout: collapse expanded overlay sidebar after pointer inactivity.
      document.addEventListener('mousemove', () => {
        if (!shouldApplyOverlayTimeout()) return;
        if (overlayIdleMoveFrame !== null) return;
        overlayIdleMoveFrame = requestAnimationFrame(() => {
          overlayIdleMoveFrame = null;
          resetOverlayIdleTimer();
        });
      }, { passive: true });

      document.addEventListener('mouseleave', () => {
        if (!shouldApplyOverlayTimeout()) return;
        collapseFromOverlayTimeout();
      });

      if (shouldApplyOverlayTimeout()) {
        scheduleOverlayIdleCollapse();
      }

      // Proximity hover: auto-reveal when mouse is within proximityPx of sidebar edge.
      // Only collapses on mouse-leave if *this* feature opened the sidebar.
      // Gated by proximityEnabled — toggled at runtime via setProximityEnabled().
      document.addEventListener('mousemove', (e) => {
        if (!proximityEnabled || isCompactDrawerViewport()) return;
        if (proximityFrame !== null) return; // throttle to one rAF per move batch
        proximityFrame = requestAnimationFrame(() => {
          proximityFrame = null;
          if (!isSidebarMode()) return;

          const rect = nav.getBoundingClientRect();
          const pos  = document.body.getAttribute('data-nav-primary-position');
          const near = pos === 'right'
            ? e.clientX >= rect.left - proximityPx
            : e.clientX <= rect.right + proximityPx;

          if (near && state === 'collapsed') {
            hoverOpened = true;
            pin(true);
          } else if (!near && state === 'pinned' && hoverOpened) {
            hoverOpened = false;
            collapse(false, true);
          }
        });
      }, { passive: true });

      // Remove pre-hydration collapsed shim after persisted state is applied.
      requestAnimationFrame(() => {
        document.body.classList.add('nav-ready');
      });
    },

    /**
     * Enable or disable proximity hover reveal.
     * Called by the settings UI toggle; persists to localStorage.
     * @param {boolean} enabled
     */
    setProximityEnabled(enabled) {
      proximityEnabled = Boolean(enabled);
      localStorage.setItem(PROXIMITY_STORAGE_KEY, proximityEnabled ? '1' : '0');
      // If disabling while hover had opened the sidebar, collapse it.
      if (!proximityEnabled && hoverOpened) {
        hoverOpened = false;
        collapse(false, true);
      }
    },

    /** Returns current proximity enabled state (for settings UI to read on load). */
    isProximityEnabled() {
      return proximityEnabled;
    },

    /**
     * Set proximity trigger distance in pixels.
     * Called by the settings slider; persists to localStorage.
     * @param {number} px  integer 0–600
     */
    setProximityPx(px) {
      proximityPx = Math.min(600, Math.max(0, Math.round(Number(px) || 0)));
      localStorage.setItem(PROXIMITY_PX_STORAGE_KEY, String(proximityPx));
    },

    /** Returns current proximity trigger distance in px (for settings UI). */
    getProximityPx() {
      return proximityPx;
    },

    /**
     * Enable or disable overlay mode (sidebar floats over content vs. pushes it).
     * Called by the settings UI toggle; persists to localStorage.
     * Default: false (push model).
     * @param {boolean} enabled
     */
    setOverlayMode(enabled) {
      overlayMode = Boolean(enabled);
      localStorage.setItem(OVERLAY_STORAGE_KEY, overlayMode ? '1' : '0');
      document.body.classList.toggle('nav-overlay-mode', overlayMode);
      if (overlayMode && state === 'pinned') {
        resetOverlayIdleTimer();
      } else {
        clearOverlayIdleTimer();
      }
    },

    /** Returns current overlay mode state (for settings UI to read on load). */
    isOverlayMode() {
      return overlayMode;
    },

    /**
     * Set overlay sidebar auto-collapse timeout in seconds (0 = never).
     * Called by the settings UI after server persistence.
     * @param {number} seconds integer 0–30
     */
    setOverlaySidebarTimeout(seconds) {
      const parsed = Number(seconds);
      overlaySidebarTimeoutSeconds = Number.isFinite(parsed) ? Math.min(30, Math.max(0, Math.round(parsed))) : 5;
      if (shouldApplyOverlayTimeout()) {
        resetOverlayIdleTimer();
      } else {
        clearOverlayIdleTimer();
      }
    },

    /** Returns current overlay sidebar timeout in seconds (for settings UI). */
    getOverlaySidebarTimeout() {
      return getOverlaySidebarTimeoutSeconds();
    },
  };
})();
