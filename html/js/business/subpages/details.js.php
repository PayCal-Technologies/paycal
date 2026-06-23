
  // Subpage module: details (data-business-subpage="details")
  // Entry: openDetailsPage, saveBusinessDetailsSettings, collectBusinessDetailsPayload

  const BUSINESS_DETAILS_LENS_PREFIX = '[PayCal Lens][business/details]';

  const isBusinessDetailsSubPage = () => resolveBusinessSubPage() === 'details';

  const resolveBusinessDetailsLensBootOptions = () => {
    const fromWindow = window.__PAYCAL_LENS_PERF__?.['business/details'];
    if (fromWindow && typeof fromWindow === 'object') {
      return { ranked: true, enabled: true, ...fromWindow };
    }

    const workspace = document.getElementById('business-workspace');
    if (workspace instanceof HTMLElement && workspace.dataset.lensPerfBoot) {
      try {
        const parsed = JSON.parse(workspace.dataset.lensPerfBoot);
        if (parsed && typeof parsed === 'object') {
          return { ranked: true, enabled: true, ...parsed };
        }
      } catch (error) {
        PW.warn(BUSINESS_DETAILS_LENS_PREFIX, 'Invalid data-lens-perf-boot JSON', error);
      }
    }

    if (workspace instanceof HTMLElement && workspace.dataset.lensPageDebug) {
      return { ranked: true, enabled: true, scope: 'business/details' };
    }

    return { ranked: true, enabled: false };
  };

  const resolveBusinessDetailsLensPerf = () => {
    if (!isBusinessDetailsSubPage()) {
      return null;
    }

    const bootOptions = resolveBusinessDetailsLensBootOptions();
    const shouldEnable = bootOptions.enabled !== false;
    if (!shouldEnable) {
      return null;
    }

    if (typeof window.PayCalLensPerformance?.create !== 'function') {
      PW.warn(BUSINESS_DETAILS_LENS_PREFIX, 'PayCalLensPerformance.create unavailable - perf summary disabled');
      return null;
    }

    const existing = window.PayCalLensDetailsPerf;
    if (!existing) {
      window.PayCalLensDetailsPerf = window.PayCalLensPerformance.create('business/details', bootOptions);
      if (window.PayCalLensDetailsPerf?.isEnabled()) {
        window.PayCalLensDetailsPerf.markSsrPainted();
      }
      return window.PayCalLensDetailsPerf;
    }

    if (shouldEnable && !existing.isEnabled()) {
      window.PayCalLensDetailsPerf = window.PayCalLensPerformance.create('business/details', bootOptions);
      if (window.PayCalLensDetailsPerf?.isEnabled()) {
        window.PayCalLensDetailsPerf.markSsrPainted();
      }
    }

    return window.PayCalLensDetailsPerf;
  };

  let businessDetailsLensPerfSummaryEmitted = false;

  const finalizeBusinessDetailsLensPerfSummary = (title = 'Performance Summary') => {
    const perf = resolveBusinessDetailsLensPerf();
    if (!perf?.isEnabled() || businessDetailsLensPerfSummaryEmitted) {
      return;
    }

    businessDetailsLensPerfSummaryEmitted = true;
    perf.markHydrationComplete();
    perf.summarize(title);
  };

  const businessDetailsDiagnosticSnapshot = (detail = {}) => {
    const workspace = document.getElementById('business-workspace');
    const workspaceBusinessId = workspace instanceof HTMLElement
      ? String(workspace.dataset.businessId || '').trim()
      : '';
    const selectedBusinessId = String(state.selectedBusinessId || '').trim();
    const payload = typeof collectBusinessDetailsPayload === 'function'
      ? collectBusinessDetailsPayload()
      : {};
    const nameLength = String(payload.name || '').length;

    return {
      subpage: resolveBusinessSubPage(),
      workspace_present: workspace instanceof HTMLElement,
      workspace_business_id_present: workspaceBusinessId !== '',
      workspace_business_id_length: workspaceBusinessId.length,
      selected_business_id_present: selectedBusinessId !== '',
      selected_business_id_length: selectedBusinessId.length,
      selected_business_found: selectedBusinessId !== '' && Boolean(findBusiness(selectedBusinessId)),
      hidden_business_id_present: elements.businessIdField instanceof HTMLInputElement && String(elements.businessIdField.value || '').trim() !== '',
      name_input_present: elements.name instanceof HTMLInputElement,
      timezone_input_present: elements.timezone instanceof HTMLInputElement,
      currency_input_present: elements.currency instanceof HTMLInputElement,
      business_details_status_present: elements.businessDetailsStatus instanceof HTMLElement,
      live_toast_present: elements.liveToast instanceof HTMLElement || elements.dialogLiveToast instanceof HTMLElement,
      editor_hydrating: Boolean(state.editorHydrating),
      business_details_save_in_flight: Boolean(state.businessDetailsSaveInFlight),
      business_details_pending_source: String(state.businessDetailsSavePendingSource || ''),
      business_details_signature_present: String(state.businessDetailsLastSavedSignature || '') !== '',
      payload_key_count: Object.keys(payload).length,
      payload_name_length: nameLength,
      payload_timezone_present: String(payload.timezone || '').trim() !== '',
      payload_currency_present: String(payload.currency || '').trim() !== '',
      ...detail,
    };
  };

  const pushBusinessDetailsDiagnostic = (eventName, detail = {}, level = 'log') => {
    if (!isBusinessDetailsSubPage()) {
      return;
    }

    const payload = businessDetailsDiagnosticSnapshot(detail);
    const diagnostics = window.PayCalBusinessDetailsDiagnostics || {
      events: [],
    };
    const record = {
      at: new Date().toISOString(),
      event: String(eventName || 'event'),
      payload,
    };

    diagnostics.events.push(record);
    diagnostics.events = diagnostics.events.slice(-80);
    diagnostics.last = record;
    diagnostics.snapshot = () => businessDetailsDiagnosticSnapshot();
    diagnostics.phantomWingState = () => (typeof PW.getState === 'function' ? PW.getState() : null);
    window.PayCalBusinessDetailsDiagnostics = diagnostics;

    const message = `[Business Details][autosave] ${eventName}`;
    if (level === 'error') {
      PW.error(message, payload);
    } else if (level === 'warn') {
      PW.warn(message, payload);
    } else {
      PW.log(message, payload);
    }
    PW.report('business_details_autosave', String(eventName || 'event').replace(/[^a-z0-9_]+/gi, '_').toLowerCase(), payload);

    const perf = resolveBusinessDetailsLensPerf();
    if (perf?.isEnabled()) {
      perf.mark(`diagnostic:${eventName}`, { ranked: false, ...payload });
    }
  };

  const logBusinessDetailsLensPageDebug = () => {
    const workspace = document.getElementById('business-workspace');
    if (!(workspace instanceof HTMLElement) || !workspace.dataset.lensPageDebug) {
      return;
    }

    try {
      const debug = JSON.parse(workspace.dataset.lensPageDebug);
      console.groupCollapsed(BUSINESS_DETAILS_LENS_PREFIX + ' page debug');
      console.log(BUSINESS_DETAILS_LENS_PREFIX, 'Lens mode requested:', debug.lens_requested);
      console.log(BUSINESS_DETAILS_LENS_PREFIX, 'Lens enabled:', debug.lens_enabled);
      console.dir(debug.snapshot);
      if (debug.lens_meta && Object.keys(debug.lens_meta).length) {
        console.log(BUSINESS_DETAILS_LENS_PREFIX, 'Lens meta:', debug.lens_meta);
      }
      if (Array.isArray(debug.lens_events) && debug.lens_events.length) {
        console.group(BUSINESS_DETAILS_LENS_PREFIX + ' Lens events');
        debug.lens_events.forEach((event) => {
          console.group((event.label || 'event') + ' (' + (event.type || 'data') + ')');
          console.dir(event.payload);
          console.groupEnd();
        });
        console.groupEnd();
      }
      if (debug.lens_counters && Object.keys(debug.lens_counters).length) {
        console.log(BUSINESS_DETAILS_LENS_PREFIX, 'Lens counters:', debug.lens_counters);
      }
      console.groupEnd();
    } catch (error) {
      PW.warn(BUSINESS_DETAILS_LENS_PREFIX, 'Invalid data-lens-page-debug JSON', error);
    }
  };

  const logBusinessDetailsLensDebug = () => {
    if (!isBusinessDetailsSubPage()) {
      return;
    }

    const workspace = document.getElementById('business-workspace');
    if (!(workspace instanceof HTMLElement)) {
      PW.warn(BUSINESS_DETAILS_LENS_PREFIX, 'Missing #business-workspace');
      return;
    }

    const fieldCount = document.querySelectorAll('#business-workspace input[id^="businesses_editor_"], #business-workspace textarea[id^="businesses_editor_"], #business-workspace select[id^="businesses_editor_"]').length;
    console.groupCollapsed(BUSINESS_DETAILS_LENS_PREFIX + ' DOM init');
    console.log(BUSINESS_DETAILS_LENS_PREFIX, 'subpage', workspace.dataset.businessSubpage || '(missing)');
    console.log(BUSINESS_DETAILS_LENS_PREFIX, 'data-business-id present', String(workspace.dataset.businessId || '').trim() !== '');
    console.log(BUSINESS_DETAILS_LENS_PREFIX, 'data-lens-mode', workspace.dataset.lensMode || '0');
    console.log(BUSINESS_DETAILS_LENS_PREFIX, 'details field count', fieldCount);
    console.log(BUSINESS_DETAILS_LENS_PREFIX, 'business status present', elements.businessDetailsStatus instanceof HTMLElement);
    console.log(BUSINESS_DETAILS_LENS_PREFIX, 'diagnostics handle', 'window.PayCalBusinessDetailsDiagnostics');
    console.groupEnd();

    pushBusinessDetailsDiagnostic('dom-init', { details_field_count: fieldCount });
  };

  const initDetailsContactPanel = () => {
    if (resolveBusinessSubPage() !== 'details') {
      return;
    }

    bindBusinessDetailsContactPanelEvents();
    pushBusinessDetailsDiagnostic('contact-panel-bound', {
      custom_cards_present: elements.customCardsContainer instanceof HTMLElement,
      contact_popover_present: elements.contactImagePopover instanceof HTMLElement,
    });
  };

  if (isBusinessDetailsSubPage()) {
    logBusinessDetailsLensPageDebug();
    logBusinessDetailsLensDebug();
  }
