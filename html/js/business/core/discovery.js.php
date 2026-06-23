  // Core module: business discovery rendering and polling

  const announceDiscoveryStatus = (message) => {
    setStatusText(elements.discoveryStatus, message);
  };

  const stopDiscoveryPolling = () => {
    if (state.discoveryIntervalId !== null) {
      window.clearInterval(state.discoveryIntervalId);
      state.discoveryIntervalId = null;
    }
    state.discoverySignature = '';
  };

  const startDiscoveryPolling = () => {
    if (!(elements.discoveryResults instanceof HTMLElement)) {
      return;
    }

    stopDiscoveryPolling();
  };

  const renderDiscovery = (payload) => {
    if (!elements.discoveryResults) {
      return;
    }

    const userSites = Array.isArray(payload?.user_sites) ? payload.user_sites : [];
    const matchCandidates = Array.isArray(payload?.match_candidates) ? payload.match_candidates : [];
    const rows = [];

    userSites.forEach((site) => {
      if (String(site.business_id || '') !== '') {
        return;
      }

      rows.push(`
        <div class="businesses_stack_row">
          <div class="businesses_stack_text">
            <strong>${String(site.name || T.unknown)}</strong>
            <span>Site without business</span>
          </div>
          <button type="button" class="btn btn_secondary" data-org-action="link-site" data-site-id="${String(site.site_id || '')}" data-site-owner-uuid="${String(site.site_owner_uuid || '')}">${T.linkSite}</button>
        </div>
      `);
    });

    matchCandidates.forEach((candidate) => {
      rows.push(`
        <div class="businesses_stack_row businesses_stack_row_hint">
          <div class="businesses_stack_text">
            <strong>${String(candidate.candidate_type || 'candidate')}</strong>
            <span>${String(candidate.reason || '')}</span>
          </div>
        </div>
      `);
    });

    if (rows.length === 0) {
      setStackMessage(elements.discoveryResults, T.noDiscovery);
      announceDiscoveryStatus(T.discoveryResultsNone);
      return;
    }

    elements.discoveryResults.classList.remove('businesses_empty');
    Guardian.setHTML(elements.discoveryResults, rows.join(''));
    announceDiscoveryStatus(formatPhpTemplate(T.discoveryResultsCount, [
      rows.length,
      rows.length === 1 ? '' : 's',
    ]));
  };

  const fetchDiscoverySnapshot = async () => {
    const params = new URLSearchParams({
      channel: 'business_discovery',
    });

    if (state.discoverySignature !== '') {
      params.set('since_signature', state.discoverySignature);
    }

    const response = await fetch(`${legacyWsHttpBase}?${params.toString()}`, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: buildHeaders(),
    });

    if (!response.ok) {
      throw new Error(`Discovery channel failed (${response.status}).`);
    }

    const payload = await response.json();
    if (!payload || (payload.status !== 'success' && payload.status !== 'ok')) {
      throw new Error(String(payload?.message || 'Discovery payload invalid.'));
    }

    return payload;
  };

  const handleDiscovery = async (showToast = true) => {
    if (blockPremiumActionWhenLocked()) {
      setStackMessage(elements.discoveryResults, T.premiumAdminLockedDetailed);
      announceDiscoveryStatus(T.discoveryPremiumLocked);
      return;
    }

    try {
      if (showToast) {
        PC.showToast(T.discoveryRunning, 'save', 5000, true);
      }
      announceDiscoveryStatus(T.discoveryRunning);
      const payload = await fetchDiscoverySnapshot();
      if (!payload.unchanged) {
        renderDiscovery(payload);
      }
      state.discoverySignature = String(payload.latest_signature || '');
      if (showToast) {
        PC.showToast(T.discoveryComplete, 'save', 5000, true);
      }
    } catch (error) {
      PW.error(error);
      setStackMessage(elements.discoveryResults, T.discoveryUnavailable);
      announceDiscoveryStatus(T.discoveryLoadFailed);
      if (showToast) {
        PC.showToast(error instanceof Error && error.message ? error.message : T.discoveryFailed, 'error', 7000, true);
      }
    }
  };
