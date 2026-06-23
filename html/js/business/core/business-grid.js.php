<?php namespace PayCal\Domain; ?>

  const gridReasonLabels = {
    loaded: () => T.gridReasonLoaded,
    failed: () => T.gridReasonFailed,
    search: () => T.gridReasonSearch,
    sort: () => T.gridReasonSort,
    page: () => T.gridReasonPage,
  };

  const formatDatagridOrderLabel = (sort, direction) => (
    sort ? `${sort} ${direction || 'asc'}` : T.datagridOrderDefault
  );

  const formatDatagridSearchLabel = (search) => (
    search ? formatPhpTemplate(T.datagridSearchQuery, [search]) : T.datagridSearchNone
  );

  const announceGridStatus = (changeReason = 'loaded') => {
    if (!elements.gridStatus) {
      return;
    }

    const reasonLabel = (gridReasonLabels[changeReason] || gridReasonLabels.loaded)();
    const grid = elements.gridContainer?.querySelector('.datagrid[data-grid="businesses"]');
    if (!grid) {
      elements.gridStatus.textContent = formatPhpTemplate(T.gridStatusNone, [reasonLabel]);
      return;
    }

    const rowCount = grid.querySelectorAll('.datagrid_row').length;
    const search = formatDatagridSearchLabel(state.grid.search);
    const sortInfo = formatDatagridOrderLabel(state.grid.sort, state.grid.direction);
    const page = state.grid.page || 1;

    elements.gridStatus.textContent = formatPhpTemplate(T.gridStatusDetail, [
      reasonLabel,
      rowCount,
      rowCount === 1 ? '' : 's',
      sortInfo,
      search,
      page,
    ]);
  };

  const setGridMessage = (message) => {
    if (!elements.gridBody) {
      return;
    }

    Guardian.setHTML(elements.gridBody, `<div class="datagrid_empty">${message}</div>`);
    announceGridStatus('loaded');
  };

  const getBusinessUnreadCount = (businessId) => {
    const org = findBusiness(businessId);
    if (!org) {
      return 0;
    }

    const raw = Number(org.notification_unread_count || 0);
    if (!Number.isFinite(raw) || raw <= 0) {
      return 0;
    }

    return Math.floor(raw);
  };

  const markBusinessNotificationsRead = async (businessId) => {
    const orgId = String(businessId || '').trim();
    if (orgId === '') {
      return;
    }

    try {
      const payload = await postForm(`/api/v1/businesses/${encodeURIComponent(orgId)}/notifications/read`, {});
      const unreadByOrg = payload && payload.unread_by_org && typeof payload.unread_by_org === 'object'
        ? payload.unread_by_org
        : {};

      state.businesses = state.businesses.map((business) => {
        const id = String(business?.business_id || '');
        const unread = Math.max(0, Number(unreadByOrg[id] || 0));
        return {
          ...business,
          notification_unread_count: String(unread),
          has_unread_notifications: unread > 0 ? '1' : '0',
        };
      });

      decorateGridRowsForPremiumLocks();
      window.dispatchEvent(new CustomEvent('paycal:notifications-updated'));
    } catch (_error) {
      // Non-blocking best-effort update.
    }
  };

  const applyUnreadByBusinessMap = (unreadByOrg) => {
    if (!unreadByOrg || typeof unreadByOrg !== 'object') {
      return;
    }

    state.businesses = state.businesses.map((business) => {
      const orgId = String(business?.business_id || '');
      const unread = Math.max(0, Number(unreadByOrg[orgId] || 0));
      return {
        ...business,
        notification_unread_count: String(unread),
        has_unread_notifications: unread > 0 ? '1' : '0',
      };
    });

    decorateGridRowsForPremiumLocks();
    window.dispatchEvent(new CustomEvent('paycal:notifications-updated'));
  };

  const legacyWsHttpBase = '/ws/';

  const fetchBusinessNotificationSnapshot = async () => {
    const params = new URLSearchParams({
      channel: 'business_notifications',
    });

    if (state.notificationsSignature !== '') {
      params.set('since_signature', state.notificationsSignature);
    }

    const response = await fetch(`${legacyWsHttpBase}?${params.toString()}`, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: buildHeaders(),
    });

    if (!response.ok) {
      throw new Error(`Business notifications channel failed (${response.status}).`);
    }

    const payload = await response.json();
    if (!payload || (payload.status !== 'success' && payload.status !== 'ok')) {
      throw new Error(String(payload?.message || 'Business notifications payload invalid.'));
    }

    return payload;
  };

  const syncBusinessNotificationDots = async () => {
    if (state.businesses.length === 0) {
      return;
    }

    const payload = await fetchBusinessNotificationSnapshot();
    state.notificationsSignature = String(payload.latest_signature || '');
    if (!payload.unchanged) {
      applyUnreadByBusinessMap(payload.unread_by_org || {});
    }
  };

  const stopBusinessNotificationPolling = () => {
    if (state.notificationsIntervalId !== null) {
      window.clearInterval(state.notificationsIntervalId);
      state.notificationsIntervalId = null;
    }
  };

  const startBusinessNotificationPolling = () => {
    stopBusinessNotificationPolling();
    state.notificationsSignature = '';

    syncBusinessNotificationDots().catch((error) => PW.error(error));

    state.notificationsIntervalId = window.setInterval(() => {
      syncBusinessNotificationDots().catch((error) => PW.error(error));
    }, 15000);
  };

  const resetInlineDeleteConfirm = () => {
    if (state.inlineDeleteConfirmTimerId !== null) {
      window.clearTimeout(state.inlineDeleteConfirmTimerId);
      state.inlineDeleteConfirmTimerId = null;
    }

    state.inlineDeleteConfirmOrgId = '';

    if (!elements.gridContainer) {
      return;
    }

    elements.gridContainer.querySelectorAll('.businesses_delete_pill').forEach((button) => {
      if (!(button instanceof HTMLButtonElement)) {
        return;
      }

      button.dataset.confirm = '0';
      button.classList.remove('businesses_delete_pill_confirm');
      button.textContent = '<?php echo addslashes(org_js_index_i18n('REMOVE')); ?>';
    });
  };

  const armInlineDeleteConfirm = (button, businessId) => {
    resetInlineDeleteConfirm();

    button.dataset.confirm = '1';
    button.classList.add('businesses_delete_pill_confirm');
    button.textContent = '<?php echo addslashes(org_js_index_i18n('CONFIRM_DELETE')); ?>';

    state.inlineDeleteConfirmOrgId = businessId;
    state.inlineDeleteConfirmTimerId = window.setTimeout(() => {
      resetInlineDeleteConfirm();
    }, 5000);
  };

  const decorateGridRowsForPremiumLocks = () => {
    if (!elements.gridContainer) {
      return;
    }

    elements.gridContainer.querySelectorAll('.datagrid_row').forEach((row) => {
      if (!(row instanceof HTMLElement)) {
        return;
      }

      row.classList.remove('businesses_row_premium_locked');

      const existingChip = row.querySelector('.businesses_premium_chip');
      if (existingChip) {
        existingChip.remove();
      }

      const removeButton = row.querySelector('.datagrid_action[data-action="remove"]');
      if (removeButton instanceof HTMLButtonElement) {
        removeButton.classList.add('businesses_delete_pill');
        removeButton.classList.remove('businesses_delete_pill_confirm');
        removeButton.dataset.confirm = '0';
        removeButton.textContent = '<?php echo addslashes(org_js_index_i18n('REMOVE')); ?>';

        const businessId = String(row.dataset.id || '');
        if (isPersonalBusinessById(businessId)) {
          removeButton.remove();
        }
      }

      const businessId = String(row.dataset.id || '');
      if (businessId === '') {
        return;
      }

      const firstCell = row.querySelector('.datagrid_item');
      if (!(firstCell instanceof HTMLElement)) {
        return;
      }

      const existingDot = firstCell.querySelector('.businesses_notification_dot');
      if (existingDot) {
        existingDot.remove();
      }

      const unreadCount = getBusinessUnreadCount(businessId);
      if (unreadCount > 0) {
        const dot = document.createElement('span');
        dot.className = 'businesses_notification_dot';
        dot.setAttribute('aria-label', `Unread notifications: ${String(unreadCount)}`);
        dot.title = unreadCount > 99 ? '99+ unread notifications' : `${String(unreadCount)} unread notification${unreadCount === 1 ? '' : 's'}`;
        firstCell.appendChild(dot);
      }

      const business = findBusiness(businessId);
      if (!business || canUsePremiumOrgFeatures(business)) {
        return;
      }

      row.classList.add('businesses_row_premium_locked');

      const chip = document.createElement('span');
      chip.className = 'businesses_premium_chip';
      chip.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PREMIUM_LOCKED')); ?>';
      firstCell.appendChild(chip);
    });
  };

  const syncGridDataset = (grid) => {
    if (!grid) {
      return;
    }

    grid.dataset.search = state.grid.search;
    grid.dataset.sort = state.grid.sort;
    grid.dataset.direction = state.grid.direction;
    grid.dataset.page = state.grid.page;
    syncGridSortA11y(grid);
  };

  const syncGridSortA11y = (grid) => {
    if (!(grid instanceof HTMLElement)) {
      return;
    }

    const activeColumn = String(state.grid.sort || '').trim();
    const activeDirection = String(state.grid.direction || 'asc').toLowerCase();

    grid.querySelectorAll('.datagrid_sort[data-column]').forEach((button) => {
      if (!(button instanceof HTMLElement)) {
        return;
      }

      const column = String(button.dataset.column || '').trim();
      const isActive = column !== '' && column === activeColumn;
      const ariaSort = isActive ? (activeDirection === 'desc' ? 'descending' : 'ascending') : 'none';

      button.setAttribute('aria-sort', ariaSort);

      const headerCell = button.closest('th, [role="columnheader"]');
      if (headerCell instanceof HTMLElement) {
        headerCell.setAttribute('aria-sort', ariaSort);
      }
    });
  };

  const loadGrid = async (overrides = {}) => {
    if (!elements.gridBody) {
      return;
    }

    state.grid = {
      ...state.grid,
      ...overrides,
    };

    const params = new URLSearchParams({
      search: state.grid.search,
      sort: state.grid.sort,
      direction: state.grid.direction,
      page: state.grid.page,
    });

    const changeReason = Object.prototype.hasOwnProperty.call(overrides, 'search')
      ? 'search'
      : Object.prototype.hasOwnProperty.call(overrides, 'sort')
        ? 'sort'
        : Object.prototype.hasOwnProperty.call(overrides, 'page')
          ? 'page'
          : 'loaded';

    try {
      const payload = await apiRequest(`/api/v1/businesses/lists?${params.toString()}`);
      const html = typeof payload.html === 'string' ? payload.html : '';

      if (html === '') {
        resetInlineDeleteConfirm();
        setGridMessage(T.none);
        announceGridStatus(changeReason);
        return;
      }

      resetInlineDeleteConfirm();
      Guardian.setHTML(elements.gridBody, html);
      const grid = elements.gridContainer?.querySelector('.datagrid[data-grid="businesses"]');
      syncGridDataset(grid);
      decorateGridRowsForPremiumLocks();
      announceGridStatus(changeReason);
    } catch (error) {
      PW.error(error);
      setGridMessage(T.loadBusinessesFailed);
      announceGridStatus('failed');
    }
  };

  const loadBusinesses = async () => {
    const payload = await apiRequest('/api/v1/businesses');
    state.businesses = Array.isArray(payload.businesses) ? payload.businesses : [];
    syncCreateButtonState();
    renderCurrentBusinessPanel();

    if (isBusinessWorkspacePage()) {
      syncBusinessWorkspaceElementRefs();
      applySingleBusinessOverviewMode();
      const business = resolveControlCenterBusiness();
      if (business) {
        updateBusinessContextHeader();
      }
    }

    return state.businesses;
  };
