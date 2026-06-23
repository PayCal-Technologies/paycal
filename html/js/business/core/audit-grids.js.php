  // Core module: business audit grids and realtime polling

  const auditGridEndpoint = (orgId) => {
    return `/api/v1/businesses/${encodeURIComponent(orgId)}/audit/grid`;
  };

  const freeAuditGridEndpoint = (orgId) => {
    return `/api/v1/businesses/${encodeURIComponent(orgId)}/audit/member/grid`;
  };

  const ensureAuditGridManager = (orgId) => {
    if (
      state.auditGridManager
      && state.auditGridOrgId === orgId
    ) {
      return state.auditGridManager;
    }

    if (state.auditGridManager && typeof state.auditGridManager.destroy === 'function') {
      state.auditGridManager.destroy();
    }

    state.auditGridManager = createDataGrid({
      id: 'businesses-audit-grid',
      containerId: 'businesses-audit-grid-host',
      endpoint: auditGridEndpoint(orgId),
    });
    state.auditGridOrgId = orgId;

    return state.auditGridManager;
  };

  const ensureFreeAuditGridManager = (orgId) => {
    if (
      state.freeAuditGridManager
      && state.freeAuditGridOrgId === orgId
    ) {
      return state.freeAuditGridManager;
    }

    if (state.freeAuditGridManager && typeof state.freeAuditGridManager.destroy === 'function') {
      state.freeAuditGridManager.destroy();
    }

    state.freeAuditGridManager = createDataGrid({
      id: 'businesses-free-audit-grid',
      containerId: 'businesses-free-audit-grid-host',
      endpoint: freeAuditGridEndpoint(orgId),
    });
    state.freeAuditGridOrgId = orgId;

    return state.freeAuditGridManager;
  };

  const SIGNAL_EVENT_LABELS = {
    'access.requested': T.signalAccessRequestReceived,
    'access.request.approved': T.signalAccessRequestApproved,
    'access.request.rejected': T.signalAccessRequestRejected,
    'invite.accepted': T.signalInviteAccepted,
    'invite.sent': T.signalInviteSent,
    'invite.revoked': T.signalInviteRevoked,
    'connection.revoked': T.signalAccessRevoked,
    'connection.withdrawn': T.signalMemberLeftBusiness,
    'ownership.transferred': T.signalOwnershipTransferred,
    'settings.updated': T.signalSettingsUpdated,
    'site.linked': T.signalSiteLinked,
  };

  const isSignalEventType = (eventType) => {
    return Object.prototype.hasOwnProperty.call(SIGNAL_EVENT_LABELS, String(eventType || ''));
  };

  const fetchBusinessAuditEvents = async (businessId) => {
    const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/audit`);
    return Array.isArray(payload.events) ? payload.events : [];
  };

  const fetchRealtimeAuditDelta = async (businessId, sinceEventId = '') => {
    const params = new URLSearchParams({
      channel: 'business_audit',
      business_id: String(businessId || ''),
    });
    if (sinceEventId !== '') {
      params.set('since_event_id', sinceEventId);
    }

    const response = await fetch(`${legacyWsHttpBase}?${params.toString()}`, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: buildHeaders(),
    });

    if (!response.ok) {
      throw new Error(`Realtime audit channel failed (${response.status}).`);
    }

    const payload = await response.json();
    if (!payload || (payload.status !== 'success' && payload.status !== 'ok')) {
      throw new Error(String(payload?.message || 'Realtime audit payload invalid.'));
    }

    return {
      events: Array.isArray(payload.events) ? payload.events : [],
      latestEventId: typeof payload.latest_event_id === 'string' ? payload.latest_event_id : '',
    };
  };

  const notifyAuditSignals = (events) => {
    const signals = (Array.isArray(events) ? events : []).filter((event) => {
      const type = String(event?.event_type || '');
      const actor = String(event?.actor_uuid || '');
      return isSignalEventType(type) && actor !== '' && actor !== currentUserUUID;
    });

    if (signals.length === 0) {
      return;
    }

    const first = signals[0];
    const type = String(first?.event_type || '');
    const label = SIGNAL_EVENT_LABELS[type] || 'Business security signal';
    const suffix = signals.length > 1 ? ` (+${signals.length - 1} more)` : '';
    PC.showToast(`${label}${suffix}`, 'save', 4500, true);
  };

  const shouldRefreshAccessPanels = (events) => {
    const refreshTypes = new Set([
      'invite.accepted',
      'invite.revoked',
      'invite.sent',
      'access.request.approved',
      'access.request.rejected',
      'connection.revoked',
      'connection.withdrawn',
      'ownership.transferred',
      'access.requested',
    ]);

    return (Array.isArray(events) ? events : []).some((event) => {
      const type = String(event?.event_type || '');
      return refreshTypes.has(type);
    });
  };

  const stopRealtimeAuditPolling = () => {
    if (state.auditRealtimeIntervalId !== null) {
      window.clearInterval(state.auditRealtimeIntervalId);
      state.auditRealtimeIntervalId = null;
    }
  };

  const loadBusinessAudit = async (businessId) => {
    const manager = ensureAuditGridManager(businessId);
    if (!manager) {
      throw new Error('Unable to initialize audit grid manager.');
    }

    try {
      await manager.reload();
    } catch (error) {
      PW.error(error);
      setDatagridMessage(elements.auditGridContainer, T.loadAuditFailed);
      announceAuditStatus(T.auditLoadFailed);
    }
  };

  const pollRealtimeAudit = async (businessId) => {
    if (businessId === '' || state.selectedBusinessId !== businessId) {
      return;
    }

    try {
      if (!state.auditRealtimeReady) {
        const bootstrap = await fetchRealtimeAuditDelta(businessId, '');
        state.auditRealtimeTopEventId = bootstrap.latestEventId;
        state.auditRealtimeReady = true;
        await loadBusinessAudit(businessId);
        return;
      }

      const delta = await fetchRealtimeAuditDelta(businessId, state.auditRealtimeTopEventId);
      if (delta.events.length === 0) {
        return;
      }

      state.auditRealtimeTopEventId = delta.latestEventId || state.auditRealtimeTopEventId;
      notifyAuditSignals(delta.events);

      await loadBusinessAudit(businessId);

      if (shouldRefreshAccessPanels(delta.events)) {
        loadBusinessInvites(businessId).catch((error) => PW.error(error));
        loadBusinessInviteHistoryGrid(businessId).catch((error) => PW.error(error));
        loadBusinessConnections(businessId).catch((error) => PW.error(error));
      }
    } catch (error) {
      PW.warn('Realtime channel fallback to timeline poll', error);

      const events = await fetchBusinessAuditEvents(businessId);
      const topEventId = events.length > 0 ? String(events[0].event_id || '') : '';
      if (!state.auditRealtimeReady) {
        state.auditRealtimeTopEventId = topEventId;
        state.auditRealtimeReady = true;
        await loadBusinessAudit(businessId);
        return;
      }
      if (topEventId === '' || topEventId === state.auditRealtimeTopEventId) {
        return;
      }
      const newEvents = [];
      for (const event of events) {
        const eventId = String(event?.event_id || '');
        if (eventId === state.auditRealtimeTopEventId) {
          break;
        }
        newEvents.push(event);
      }

      state.auditRealtimeTopEventId = topEventId;
      notifyAuditSignals(newEvents);
      await loadBusinessAudit(businessId);

      if (shouldRefreshAccessPanels(newEvents)) {
        loadBusinessInvites(businessId).catch((error) => PW.error(error));
        loadBusinessInviteHistoryGrid(businessId).catch((error) => PW.error(error));
        loadBusinessConnections(businessId).catch((error) => PW.error(error));
      }
    }
  };

  const startRealtimeAuditPolling = (businessId) => {
    stopRealtimeAuditPolling();
    state.auditRealtimeReady = false;
    state.auditRealtimeTopEventId = '';

    if (businessId === '') {
      return;
    }

    pollRealtimeAudit(businessId).catch((error) => PW.error(error));
    state.auditRealtimeIntervalId = window.setInterval(() => {
      pollRealtimeAudit(businessId).catch((error) => PW.error(error));
    }, 5000);
  };

  const loadFreeProfileAudit = async (businessId) => {
    const orgId = String(businessId || '').trim();
    if (orgId === '' || !(elements.freeAuditGridContainer instanceof HTMLElement)) {
      return;
    }

    const manager = ensureFreeAuditGridManager(orgId);
    if (!manager) {
      throw new Error('Unable to initialize free profile audit grid manager.');
    }

    setDatagridMessage(elements.freeAuditGridContainer, T.loading, true);
    announceFreeAuditStatus(T.freeAuditLoading);

    try {
      await manager.reload();
      announceFreeAuditStatus(T.freeAuditLoaded);
    } catch (error) {
      PW.error(error);
      setDatagridMessage(elements.freeAuditGridContainer, T.loadAuditFailed);
      announceFreeAuditStatus(T.freeAuditLoadFailed);
    }
  };
