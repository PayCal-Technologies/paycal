const SignalPanel = (() => {
  const state = {
    initialized: false,
    panel: null,
    previousFocus: null,
    activeTab: 'feedback',
    selectedCategory: 'bug',
    clientErrors: [],
    apiStatuses: [],
    heartbeatIntervalId: null,
    heartbeatInFlight: false,
  };

  const focusableSelector = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
  ].join(',');

  const sanitizeMessage = (value) => String(value || '')
    .replace(/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/gi, '[email]')
    .replace(/\b[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\b/gi, '[uuid]')
    .replace(/\b\d+(?:\.\d{1,2})?\b/g, '[number]')
    .slice(0, 300);

  const pushLimited = (list, item, limit) => {
    list.push(item);
    while (list.length > limit) list.shift();
  };

  const patchFetchForStatuses = () => {
    if (window.__paycalSignalFetchPatched || typeof window.fetch !== 'function') return;
    window.__paycalSignalFetchPatched = true;
    const originalFetch = window.fetch.bind(window);
    window.fetch = async (...args) => {
      const started = performance.now();
      try {
        const response = await originalFetch(...args);
        const url = typeof args[0] === 'string'
          ? args[0]
          : String(args[0]?.url || '');
        pushLimited(state.apiStatuses, {
          path: safePath(url),
          status: response.status,
          ok: response.ok,
          ms: Math.round(performance.now() - started),
          at: new Date().toISOString(),
        }, 12);
        return response;
      } catch (error) {
        pushLimited(state.apiStatuses, {
          path: safePath(typeof args[0] === 'string' ? args[0] : String(args[0]?.url || '')),
          status: 0,
          ok: false,
          ms: Math.round(performance.now() - started),
          at: new Date().toISOString(),
        }, 12);
        throw error;
      }
    };
  };

  const safePath = (url) => {
    try {
      return new URL(String(url || ''), window.location.origin).pathname || '/';
    } catch {
      return '/';
    }
  };

  const bindErrorCapture = () => {
    window.addEventListener('error', (event) => {
      pushLimited(state.clientErrors, {
        type: 'error',
        message: sanitizeMessage(event.message || event.error?.message || 'client error'),
        source: safePath(event.filename || ''),
        line: Number(event.lineno || 0),
        at: new Date().toISOString(),
      }, 8);
    });
    window.addEventListener('unhandledrejection', (event) => {
      pushLimited(state.clientErrors, {
        type: 'rejection',
        message: sanitizeMessage(event.reason?.message || event.reason || 'promise rejection'),
        source: 'promise',
        line: 0,
        at: new Date().toISOString(),
      }, 8);
    });
  };

  const init = ({ core, PW } = {}) => {
    if (state.initialized) return;
    state.panel = document.getElementById('signal_panel');
    if (!state.panel) return;
    state.initialized = true;

    state.core = core || null;
    state.PW = PW || null;

    patchFetchForStatuses();
    bindErrorCapture();
    bindTabs();
    bindForm();
    bindGlobalKeys();
    bindOpeners();
    bindDragResize();
    refreshContext();
    centerPanel();
  };

  const bindOpeners = () => {
    document.querySelectorAll('[data-signal-open]').forEach((button) => {
      button.addEventListener('click', () => open('feedback', button instanceof HTMLElement ? button : null));
    });
    state.panel.querySelectorAll('[data-signal-close]').forEach((button) => {
      button.addEventListener('click', close);
    });
    state.panel.querySelector('[data-signal-console-refresh]')?.addEventListener('click', renderConsole);
    state.panel.querySelector('[data-signal-console-copy]')?.addEventListener('click', copyConsole);
  };

  const bindGlobalKeys = () => {
    document.addEventListener('keydown', (event) => {
      const isEscape = event.key === 'Escape' || event.code === 'Escape';
      if (event.shiftKey && isEscape) {
        event.preventDefault();
        isOpen() ? close() : open('feedback');
        return;
      }
      if (isEscape && isOpen()) {
        event.preventDefault();
        close();
        return;
      }
      if (event.key === 'Tab' && isOpen()) {
        trapFocus(event);
      }
    }, true);
  };

  const bindTabs = () => {
    state.panel.querySelectorAll('[data-signal-tab]').forEach((tab) => {
      tab.addEventListener('click', () => activateTab(tab.dataset.signalTab || 'feedback'));
      tab.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
        event.preventDefault();
        const tabs = Array.from(state.panel.querySelectorAll('[data-signal-tab]'));
        const index = tabs.indexOf(tab);
        const delta = event.key === 'ArrowRight' ? 1 : -1;
        const next = tabs[(index + delta + tabs.length) % tabs.length];
        next?.focus();
        next?.click();
      });
    });

    state.panel.querySelectorAll('[data-signal-category] .signal_chip').forEach((chip) => {
      chip.addEventListener('click', () => {
        state.selectedCategory = String(chip.dataset.value || 'bug');
        state.panel.querySelectorAll('[data-signal-category] .signal_chip').forEach((item) => item.classList.remove('active'));
        chip.classList.add('active');
      });
    });
  };

  const bindForm = () => {
    const form = document.getElementById('signal_feedback_panel');
    if (!(form instanceof HTMLFormElement)) return;
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      await submitFeedback(form);
    });
  };

  const isOpen = () => state.panel && !state.panel.hidden;

  const open = (tab = 'feedback', opener = null) => {
    if (!state.panel) return;
    state.previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    refreshContext();
    state.panel.hidden = false;
    activateTab(tab);
    if (tab === 'feedback' && opener instanceof HTMLElement) {
      applyFeedbackPrefill(opener);
    }
    startHeartbeatIfNeeded();
    requestAnimationFrame(() => {
      const first = state.panel.querySelector('#signal_feedback_topic, [data-signal-tab], button, input, textarea');
      first?.focus();
    });
  };

  const applyFeedbackPrefill = (opener) => {
    const topic = state.panel.querySelector('#signal_feedback_topic');
    const notes = state.panel.querySelector('#signal_feedback_notes');
    const tags = state.panel.querySelector('#signal_feedback_tags');

    if (topic instanceof HTMLInputElement && opener.dataset.signalTopic && topic.value.trim() === '') {
      topic.value = opener.dataset.signalTopic;
    }
    if (notes instanceof HTMLTextAreaElement && opener.dataset.signalNotes && notes.value.trim() === '') {
      notes.value = opener.dataset.signalNotes;
    }
    if (tags instanceof HTMLInputElement && opener.dataset.signalTags && tags.value.trim() === '') {
      tags.value = opener.dataset.signalTags;
    }
  };

  const close = () => {
    if (!state.panel) return;
    state.panel.hidden = true;
    stopHeartbeat();
    if (state.previousFocus instanceof HTMLElement && document.contains(state.previousFocus)) {
      state.previousFocus.focus();
    }
  };

  const activateTab = (tab) => {
    if (!state.panel) return;
    const target = state.panel.querySelector(`[data-signal-page="${tab}"]`);
    const trigger = state.panel.querySelector(`[data-signal-tab="${tab}"]`);
    if (!target || !trigger) return;
    state.activeTab = tab;
    state.panel.querySelectorAll('[data-signal-tab]').forEach((item) => {
      const active = item === trigger;
      item.classList.toggle('active', active);
      item.setAttribute('aria-selected', active ? 'true' : 'false');
    });
    state.panel.querySelectorAll('[data-signal-page]').forEach((page) => {
      const active = page === target;
      page.classList.toggle('active', active);
      page.hidden = !active;
    });
    if (tab === 'diagnostics') {
      renderDiagnostics();
      startHeartbeatIfNeeded();
    }
    if (tab === 'console') {
      renderConsole();
    }
  };

  const trapFocus = (event) => {
    const items = Array.from(state.panel.querySelectorAll(focusableSelector))
      .filter((item) => item instanceof HTMLElement && item.getClientRects().length > 0);
    if (items.length === 0) return;
    const first = items[0];
    const last = items[items.length - 1];
    if (event.shiftKey && document.activeElement === first) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
      event.preventDefault();
      first.focus();
    }
  };

  const refreshContext = () => {
    if (!state.panel) return;
    const path = safePath(window.location.href);
    const title = document.title.replace(/\s+-\s+\[PayCal\]\s*$/, '') || 'PayCal';
    const role = state.panel.dataset.userRole || 'user';
    setContext('path', path);
    setContext('title', title);
    setContext('role', role);
  };

  const setContext = (name, value) => {
    const node = state.panel.querySelector(`[data-signal-context="${name}"]`);
    if (node) node.textContent = String(value || '');
  };

  const buildContext = () => {
    const body = document.body;
    const viewport = {
      width: window.innerWidth,
      height: window.innerHeight,
      device_pixel_ratio: window.devicePixelRatio || 1,
    };
    return {
      page_path: safePath(window.location.href),
      page_title: document.title,
      section: inferSection(),
      viewport,
      browser: navigator.userAgentData?.brands || navigator.userAgent,
      platform: navigator.userAgentData?.platform || navigator.platform || '',
      language: state.panel.dataset.language || navigator.language || '',
      theme: state.panel.dataset.theme || body?.dataset?.theme || '',
      text_preferences: {
        toast_font_size: body?.dataset?.toastFontSize || '',
        reduced_motion: window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ? 'reduce' : 'no-preference',
      },
      user_role: state.panel.dataset.userRole || '',
      business_id: findContextId('businessId'),
      site_id: findContextId('siteId'),
      team_id: findContextId('teamId'),
      feature_flags: window.PayCalFeatureFlags || {},
    };
  };

  const inferSection = () => {
    const first = safePath(window.location.href).split('/').filter(Boolean)[0] || 'home';
    return first === 'businesses' ? 'business' : first;
  };

  const findContextId = (name) => {
    const attr = `data-${name.replace(/[A-Z]/g, (m) => `-${m.toLowerCase()}`)}`;
    const node = document.querySelector(`[${attr}]`);
    return node instanceof HTMLElement ? String(node.getAttribute(attr) || '') : '';
  };

  const buildDiagnostics = () => {
    const nav = performance.getEntriesByType?.('navigation')?.[0];
    const perf = nav ? {
      dom_complete_ms: Math.round(nav.domComplete || 0),
      load_event_ms: Math.round(nav.loadEventEnd || 0),
      transfer_size: Math.round(nav.transferSize || 0),
    } : {};
    return {
      client_errors: state.clientErrors.slice(-5),
      api_statuses: state.apiStatuses.slice(-10).map((item) => ({
        path: item.path,
        status: item.status,
        ok: item.ok,
        ms: item.ms,
      })),
      performance: perf,
      phantomwing_summary: typeof state.PW?.exportErrorData === 'function'
        ? state.PW.exportErrorData().summary
        : {},
    };
  };

  const submitFeedback = async (form) => {
    const status = state.panel.querySelector('[data-signal-status]');
    setStatus(status, 'Submitting...', '');
    const formData = new FormData(form);
    const includeDiagnostics = formData.get('include_diagnostics') === 'on';
    const payload = {
      topic: String(formData.get('topic') || ''),
      notes: String(formData.get('notes') || ''),
      category: state.selectedCategory,
      tags: String(formData.get('tags') || '').split(',').map((tag) => tag.trim()).filter(Boolean),
      pain_points: formData.getAll('pain_points'),
      severity: String(formData.get('severity') || 'medium'),
      page_path: safePath(window.location.href),
      page_title: document.title,
      context: buildContext(),
      diagnostics: includeDiagnostics ? buildDiagnostics() : {},
    };

    try {
      const response = await fetch(`${state.core?.config?.pc_api || '/api/v1'}/feedback`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
      });
      const json = await response.json().catch(() => ({}));
      if (!response.ok || json.status === 'error') {
        throw new Error(json.message || `Feedback submit failed (${response.status})`);
      }
      form.reset();
      state.selectedCategory = 'bug';
      state.panel.querySelectorAll('[data-signal-category] .signal_chip').forEach((chip) => {
        chip.classList.toggle('active', chip.dataset.value === 'bug');
      });
      setStatus(status, 'Feedback submitted. Thank you for helping improve PayCal.', 'success');
    } catch (error) {
      setStatus(status, error.message || 'Unable to submit feedback right now.', 'error');
    }
  };

  const setStatus = (node, message, type) => {
    if (!node) return;
    node.textContent = message;
    node.classList.toggle('is-success', type === 'success');
    node.classList.toggle('is-error', type === 'error');
  };

  const renderDiagnostics = () => {
    if (typeof state.PW?.injectErrorPanel === 'function') {
      state.PW.injectErrorPanel();
    }
  };

  const startHeartbeatIfNeeded = () => {
    if (state.activeTab !== 'diagnostics' || !document.getElementById('ws_heartbeat_content')) return;
    if (state.heartbeatIntervalId !== null) return;
    pollHeartbeat();
    state.heartbeatIntervalId = window.setInterval(pollHeartbeat, 5000);
  };

  const stopHeartbeat = () => {
    if (state.heartbeatIntervalId === null) return;
    window.clearInterval(state.heartbeatIntervalId);
    state.heartbeatIntervalId = null;
  };

  const pollHeartbeat = async () => {
    const target = document.getElementById('ws_heartbeat_content');
    if (!target || state.heartbeatInFlight) return;
    state.heartbeatInFlight = true;
    try {
      const response = await fetch('/ws/', {
        method: 'GET',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!response.ok) throw new Error(`Heartbeat HTTP ${response.status}`);
      const payload = await response.json();
      target.textContent = `${payload.status || 'unknown'} (${payload.service || 'ws'}) at ${new Date().toLocaleTimeString()}`;
    } catch {
      target.textContent = 'Heartbeat error. Retrying in 5s.';
    } finally {
      state.heartbeatInFlight = false;
    }
  };

  const renderConsole = () => {
    const output = document.getElementById('signal_console_output');
    if (!output) return;
    const payload = {
      generated_at: new Date().toISOString(),
      debug_settings: window.PAYCAL_DEBUG_SETTINGS || {},
      phantomwing: typeof state.PW?.exportErrorData === 'function' ? state.PW.exportErrorData() : {},
      api_statuses: state.apiStatuses,
      client_errors: state.clientErrors,
    };
    output.textContent = JSON.stringify(payload, null, 2);
  };

  const copyConsole = async () => {
    renderConsole();
    const output = document.getElementById('signal_console_output');
    if (!output || !navigator.clipboard) return;
    await navigator.clipboard.writeText(output.textContent || '');
  };

  const centerPanel = () => {
    const panel = state.panel;
    if (!panel) return;
    panel.classList.add('signal_panel_prep');
    panel.hidden = false;
    const rect = panel.getBoundingClientRect();
    panel.style.setProperty('--signal-panel-left', `${Math.max(0, (window.innerWidth - rect.width) / 2)}px`);
    panel.style.setProperty('--signal-panel-top', `${Math.max(0, (window.innerHeight - rect.height) / 5)}px`);
    panel.hidden = true;
    panel.classList.remove('signal_panel_prep');
  };

  const bindDragResize = () => {
    const panel = state.panel;
    const handle = panel.querySelector('[data-signal-drag-handle]');
    const resize = document.getElementById('signal_panel_resize');
    let mode = '';
    let startX = 0;
    let startY = 0;
    let startLeft = 0;
    let startTop = 0;
    let startWidth = 0;
    let startHeight = 0;

    const pointerDown = (event, nextMode) => {
      if (event.target.closest('button, input, select, textarea, a')) return;
      mode = nextMode;
      const rect = panel.getBoundingClientRect();
      startX = event.clientX;
      startY = event.clientY;
      startLeft = rect.left;
      startTop = rect.top;
      startWidth = rect.width;
      startHeight = rect.height;
      panel.setPointerCapture(event.pointerId);
      event.preventDefault();
    };

    handle?.addEventListener('pointerdown', (event) => pointerDown(event, 'drag'));
    resize?.addEventListener('pointerdown', (event) => pointerDown(event, 'resize'));
    panel.addEventListener('pointermove', (event) => {
      if (mode === '') return;
      const dx = event.clientX - startX;
      const dy = event.clientY - startY;
      if (mode === 'drag') {
        const rect = panel.getBoundingClientRect();
        const left = Math.max(0, Math.min(startLeft + dx, window.innerWidth - rect.width));
        const top = Math.max(0, Math.min(startTop + dy, window.innerHeight - rect.height));
        panel.style.setProperty('--signal-panel-left', `${left}px`);
        panel.style.setProperty('--signal-panel-top', `${top}px`);
      }
      if (mode === 'resize') {
        panel.style.setProperty('--signal-panel-width', `${Math.max(300, startWidth + dx)}px`);
        panel.style.setProperty('--signal-panel-height', `${Math.max(420, startHeight + dy)}px`);
      }
    });
    panel.addEventListener('pointerup', (event) => {
      mode = '';
      try { panel.releasePointerCapture(event.pointerId); } catch {}
    });
  };

  return { init, open, close };
})();

export default SignalPanel;
