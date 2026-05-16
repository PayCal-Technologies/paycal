/**
 * Sidebar Navigation — Click Toggle
 * States: collapsed | pinned
 *
 * collapsed : icon strip visible, blank rail toggles sidebar
 * pinned    : full sidebar, push model (content shifts), manual collapse only
 *
 * Proximity hover: auto-reveals sidebar when cursor nears the sidebar edge.
 * Controlled by PROXIMITY_STORAGE_KEY ('0' = off, '1' = on, default on).
 * Call NavToggle.setProximityEnabled(bool) to toggle at runtime (settings UI).
 */
export default (() => {
  const STORAGE_KEY           = 'paycal_nav_state';      // '0' = collapsed, '1' = pinned
  const PROXIMITY_STORAGE_KEY = 'paycal_nav_proximity';  // '0' = off, '1' = on (default on)
  const OVERLAY_STORAGE_KEY   = 'paycal_nav_overlay';    // '1' = overlay, '0' = push (default push)
  const DEFAULT_LABEL_EXPAND = '';
  const DEFAULT_LABEL_COLLAPSE = '';
  const DEFAULT_ANNOUNCE_EXPANDED = '';
  const DEFAULT_ANNOUNCE_COLLAPSED = '';

  let nav, primaryNav, status, main, toggle, skipLink;
  let state       = 'collapsed';
  let focusOrigin = null;
  let responsiveFrame = null;
  let hoverOpened = false;       // true only when proximity-hover opened the sidebar
  let proximityFrame = null;     // rAF handle for mousemove throttling
  let proximityEnabled = true;   // runtime flag; synced from localStorage on init
  let overlayMode = false;       // runtime flag; synced from localStorage on init

  function syncResponsiveState() {
    document.body.setAttribute('data-nav-top-density', 'full');
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

  function announce(msg) {
    if (!status) return;
    status.textContent = '';
    setTimeout(() => { status.textContent = msg; }, 10);
  }

  function getLabel(name, fallback) {
    return toggle?.dataset?.[name] || fallback;
  }

  function persistState(collapsed) {
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
  }

  function collapse(returnFocus = false, fromHover = false) {
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

      syncAccessibleState();

      // Load proximity preference ('1' = on by default).
      proximityEnabled = (localStorage.getItem(PROXIMITY_STORAGE_KEY) ?? '1') !== '0';

      // Load overlay preference ('0' = push model by default).
      overlayMode = (localStorage.getItem(OVERLAY_STORAGE_KEY) ?? '0') === '1';
      document.body.classList.toggle('nav-overlay-mode', overlayMode);

      // Proximity hover: auto-reveal when mouse is within 200px of sidebar edge.
      // Only collapses on mouse-leave if *this* feature opened the sidebar.
      // Gated by proximityEnabled — toggled at runtime via setProximityEnabled().
      const PROXIMITY_PX = 200;
      document.addEventListener('mousemove', (e) => {
        if (!proximityEnabled) return;
        if (proximityFrame !== null) return; // throttle to one rAF per move batch
        proximityFrame = requestAnimationFrame(() => {
          proximityFrame = null;
          if (!isSidebarMode()) return;

          const rect = nav.getBoundingClientRect();
          const pos  = document.body.getAttribute('data-nav-primary-position');
          const near = pos === 'right'
            ? e.clientX >= rect.left - PROXIMITY_PX
            : e.clientX <= rect.right + PROXIMITY_PX;

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
     * Enable or disable overlay mode (sidebar floats over content vs. pushes it).
     * Called by the settings UI toggle; persists to localStorage.
     * Default: false (push model).
     * @param {boolean} enabled
     */
    setOverlayMode(enabled) {
      overlayMode = Boolean(enabled);
      localStorage.setItem(OVERLAY_STORAGE_KEY, overlayMode ? '1' : '0');
      document.body.classList.toggle('nav-overlay-mode', overlayMode);
    },

    /** Returns current overlay mode state (for settings UI to read on load). */
    isOverlayMode() {
      return overlayMode;
    },
  };
})();
