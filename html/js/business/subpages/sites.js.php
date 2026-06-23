<?php namespace PayCal\Domain; ?>

  // Subpage module: sites (data-business-subpage="sites")
  // Entry: openSitesPage, loadBusinessSitesGrid, discovery polling

  const isBusinessSitesSubPage = () => resolveBusinessSubPage() === 'sites';

  let businessSiteEditor = null;

  const resolveSiteOwnershipBadge = (site, business) => {
    const siteOwnerUUID = String(site?.site_owner_uuid || '').trim();
    const businessOwnerUUID = String(business?.owner_uuid || '').trim();

    if (siteOwnerUUID === currentUserUUID) {
      return {
        label: T.sitesOwnershipPersonal,
        className: 'businesses_site_ownership_personal',
      };
    }

    if (siteOwnerUUID !== '' && siteOwnerUUID === businessOwnerUUID) {
      return {
        label: T.sitesOwnershipBusiness,
        className: 'businesses_site_ownership_business',
      };
    }

    return {
      label: T.sitesOwnershipShared,
      className: 'businesses_site_ownership_shared',
    };
  };

  const renderBusinessSites = (sites, business) => {
    if (!(elements.businessSitesList instanceof HTMLElement)) {
      return;
    }

    const rows = [];
    sites.forEach((site) => {
      if (!site || typeof site !== 'object') {
        return;
      }

      const siteName = String(site.site_name || site.name || T.unknown);
      const siteStatus = String(site.settings?.site_status || site.site_status || 'active').toLowerCase();
      const ownership = resolveSiteOwnershipBadge(site, business);
      const archivedBadge = siteStatus === 'archived'
        ? `<span class="businesses_site_status_badge">${safeText(T.sitesStatusArchived)}</span>`
        : '';

      rows.push(`
        <div class="businesses_stack_row">
          <div class="businesses_stack_text">
            <strong>${safeText(siteName)}</strong>
            <span>
              <span class="businesses_site_ownership_badge ${ownership.className}">${safeText(ownership.label)}</span>
              ${archivedBadge}
            </span>
          </div>
        </div>
      `);
    });

    if (rows.length === 0) {
      setStackMessage(elements.businessSitesList, T.noBusinessSites);
      announceBusinessSitesStatus(T.noBusinessSites);
      return;
    }

    elements.businessSitesList.classList.remove('businesses_empty');
    Guardian.setHTML(elements.businessSitesList, rows.join(''));
    announceBusinessSitesStatus(formatPhpTemplate(T.businessSitesLinkedLoaded, [
      rows.length,
      rows.length === 1 ? '' : 's',
    ]));
  };

  const loadBusinessSites = async (businessId) => {
    if (!(elements.businessSitesList instanceof HTMLElement)) {
      return;
    }

    const business = findBusiness(businessId);
    if (business && !canUsePremiumOrgFeatures(business)) {
      setStackMessage(elements.businessSitesList, T.premiumAdminLockedDetailed);
      announceBusinessSitesStatus(T.businessSitesPremiumLocked);
      return;
    }

    setStackMessage(elements.businessSitesList, T.loading);
    announceBusinessSitesStatus(T.businessSitesLoadingLinked);

    try {
      const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/sites`);
      const sites = Array.isArray(payload?.sites) ? payload.sites : [];
      state.contextSiteCount = sites.length;
      updateBusinessContextHeader();
      renderBusinessSites(sites, business);
    } catch (error) {
      PW.error(error);
      setStackMessage(elements.businessSitesList, T.businessSitesLoadFailed);
      announceBusinessSitesStatus(T.businessSitesLoadFailed);
    }
  };

  const openSitesPage = async (businessId) => {
    const business = findBusiness(businessId);
    if (!business) {
      announceBusinessSitesStatus(T.businessSitesNoBusiness);
      return;
    }

    stopRealtimeAuditPolling();
    state.selectedBusinessId = businessId;
    setEditorMeta(business);
    closeContactImagePopover();

    markBusinessNotificationsRead(businessId).catch(() => {});

    if (elements.discoveryResults instanceof HTMLElement) {
      setStackMessage(elements.discoveryResults, T.noDiscovery);
      announceDiscoveryStatus(T.discoveryAutoRefresh);
    }

    try {
      const loads = [loadBusinessSitesGrid(businessId)];
      if (elements.discoveryResults instanceof HTMLElement) {
        loads.push(handleDiscovery(false));
      }

      await Promise.all(loads);

      if (elements.discoveryResults instanceof HTMLElement) {
        startDiscoveryPolling();
      }
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.loadDefaultsFailed, 'error', 7000, true);
    }
  };

  const getBusinessSiteEditor = () => {
    if (!businessSiteEditor) {
      businessSiteEditor = initSiteEditor({
        mode: 'business',
        canWrite: () => {
          const orgId = String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
          return canWriteBusinessSites(findBusiness(orgId));
        },
        getBusinessId: () => String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim(),
        reloadGrids: async () => {
          const orgId = String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
          if (orgId !== '') {
            await loadBusinessSitesGrid(orgId, state.sitesGridStatus || 'active');
          }
        },
        getGridStatus: () => state.sitesGridStatus || 'active',
      });
    }

    return businessSiteEditor;
  };

  const syncBusinessSitesGridElementRefs = () => {
    elements.businessSitesGridContainer = document.getElementById('businesses-sites-grid');
    elements.businessSitesStatus = document.getElementById('businesses_sites_sr_status');
  };

  const businessSitesGridEndpoint = (orgId, status = 'active') => (
    `/api/v1/businesses/${encodeURIComponent(orgId)}/sites/grid?status=${encodeURIComponent(status)}`
  );

  const syncBusinessSitesStatusControls = (status = state.sitesGridStatus || 'active') => {
    syncActiveArchivedStatusControls({
      status,
      tabSelector: '[data-business-sites-status]',
      tabDatasetKey: 'businessSitesStatus',
      noteSelector: '[data-for-business-sites-status]',
      noteDatasetKey: 'forBusinessSitesStatus',
      createSelector: '[data-action="create-business-site"]',
      resolveStatus: resolveBusinessSitesGridStatus,
    });
  };

  const businessSitesGridEventTarget = (event) => (
    event.target instanceof Element ? event.target : null
  );

  const isBusinessSitesGridControlTarget = (target) => {
    if (!(target instanceof Element)) {
      return true;
    }

    return !!target.closest(
      '.datagrid_action, .datagrid_sort, .datagrid_search, .datagrid_pager, .datagrid_pagination, .datagrid_column_toggle, .datagrid_controls',
    );
  };

  const selectBusinessSitesGridRow = (rowElement) => {
    const container = elements.businessSitesGridContainer;
    if (container instanceof HTMLElement) {
      container.querySelectorAll('.datagrid_row').forEach((candidate) => candidate.classList.remove('is-selected'));
    }
    if (rowElement instanceof HTMLElement) {
      rowElement.classList.add('is-selected');
    }
  };

  const handleBusinessSitesRowClick = (rowId, rowElement) => {
    const normalizedRowId = String(rowId || '').trim();
    if (normalizedRowId === '' || !(rowElement instanceof HTMLElement)) {
      return;
    }

    selectBusinessSitesGridRow(rowElement);
    getBusinessSiteEditor().openEditSiteDialog(normalizedRowId).catch((error) => PW.error(error));
  };

  const handleBusinessSitesGridControlClick = (event) => {
    const target = businessSitesGridEventTarget(event);
    const container = elements.businessSitesGridContainer;
    if (!target || !(container instanceof HTMLElement) || !container.contains(target)) {
      return;
    }

    const createBtn = target.closest('[data-action="create-business-site"]');
    if (createBtn instanceof HTMLElement) {
      event.preventDefault();
      if (resolveBusinessSitesGridStatus() === 'active') {
        getBusinessSiteEditor().openCreateSiteDialog('active');
      }
      return;
    }

    const actionBtn = target.closest('.datagrid_action');
    if (!(actionBtn instanceof HTMLElement)) {
      return;
    }

    event.preventDefault();
    const action = String(actionBtn.dataset.action || '');
    const row = actionBtn.closest('.datagrid_row');
    const rowId = row instanceof HTMLElement ? String(row.dataset.id || actionBtn.dataset.id || '').trim() : '';
    if (rowId === '') {
      return;
    }

    const siteName = siteEditorResolveGridSiteName(row);
    const ownerUUID = String(rowId.split(':')[0] || '');
    const siteId = String(rowId.split(':')[1] || rowId);
    if (action === 'restore-site') {
      restoreBusinessSiteFromGrid(ownerUUID, siteId, siteName).catch((error) => PW.error(error));
      return;
    }

    if (action === 'archive-site' || action === 'delete') {
      getBusinessSiteEditor().openDeleteSiteDialog(siteId, siteName, 'active', ownerUUID);
    }
  };

  const restoreBusinessSiteFromGrid = async (ownerUUID, siteId, siteName) => {
    const businessId = String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
    const normalizedOwner = String(ownerUUID || '').trim();
    const normalizedSiteId = String(siteId || '').trim();
    if (businessId === '' || normalizedOwner === '' || normalizedSiteId === '') {
      return;
    }

    const message = String(T.businessSitesRestoreConfirm || 'Restore "{name}"?').replace('{name}', siteName || 'this site');
    const restoreLabel = T.businessSitesRestoreAction || 'Restore';
    const confirmed = await getBusinessSiteEditor().confirmAction({
      title: `${restoreLabel} site?`,
      message,
      confirmText: restoreLabel,
      confirmClass: 'btn btn_primary',
    });
    if (!confirmed) {
      return;
    }

    try {
      await postForm(
        `/api/v1/businesses/${encodeURIComponent(businessId)}/sites/${encodeURIComponent(normalizedOwner)}/${encodeURIComponent(normalizedSiteId)}/restore`,
        {},
      );
      PC.showToast(T.businessSitesRestored || 'Site restored.', 'save', 5000, true);
      await loadBusinessSitesGrid(businessId, state.sitesGridStatus || 'archived');
    } catch (error) {
      PW.error(error);
      PC.showToast(T.businessSitesRestoreFailed || 'Unable to restore site.', 'error', 7000, true);
    }
  };

  const handleBusinessSitesGridRowActivate = (event) => {
    const target = businessSitesGridEventTarget(event);
    const container = elements.businessSitesGridContainer;
    if (!target || !(container instanceof HTMLElement) || !container.contains(target)) {
      return;
    }

    if (isBusinessSitesGridControlTarget(target)) {
      return;
    }

    const row = target.closest('.datagrid_row');
    if (!(row instanceof HTMLElement) || row.classList.contains('datagrid_row_empty')) {
      return;
    }

    const rowId = String(row.dataset.id || '').trim();
    if (rowId === '') {
      return;
    }

    event.preventDefault();
    handleBusinessSitesRowClick(rowId, row);
  };

  const resolveBusinessSitesGridStatus = (status = state.sitesGridStatus || 'active') => {
    return normalizeActiveArchivedStatus(status);
  };

  const canHydrateBusinessSitesGridFromSsr = (status = 'active') => {
    const container = elements.businessSitesGridContainer;
    if (!(container instanceof HTMLElement)) {
      return false;
    }

    const normalizedStatus = resolveBusinessSitesGridStatus(status);
    const ssrStatus = String(container.dataset.ssrSitesGrid || '').trim();
    if (ssrStatus === '' || ssrStatus !== normalizedStatus) {
      return false;
    }

    return container.querySelector(`.datagrid[data-grid="business-sites-${normalizedStatus}"]`) instanceof HTMLElement;
  };

  const announceBusinessSitesGridFromDom = (status = 'active') => {
    const container = elements.businessSitesGridContainer;
    if (!(container instanceof HTMLElement)) {
      return;
    }

    const normalizedStatus = resolveBusinessSitesGridStatus(status);
    const rowCount = container.querySelectorAll(
      `.datagrid[data-grid="business-sites-${normalizedStatus}"] .datagrid_row:not(.datagrid_row_empty)`,
    ).length;
    announceBusinessSitesGridStatus(
      `Business sites grid ready. ${rowCount} result${rowCount === 1 ? '' : 's'}.`,
    );
    state.contextSiteCount = rowCount;
    if (typeof updateBusinessContextHeader === 'function') {
      updateBusinessContextHeader();
    }
  };

  const ensureBusinessSitesGridManager = (orgId, status = 'active') => {
    const normalizedStatus = String(status || 'active').trim() || 'active';
    if (
      state.sitesGridActiveManager
      && state.sitesGridOrgId === orgId
      && state.sitesGridManagerStatus === normalizedStatus
    ) {
      return state.sitesGridActiveManager;
    }

    if (state.sitesGridActiveManager && typeof state.sitesGridActiveManager.destroy === 'function') {
      state.sitesGridActiveManager.destroy();
    }

    state.sitesGridActiveManager = createDataGrid({
      id: 'businesses-sites-grid',
      endpoint: businessSitesGridEndpoint(orgId, normalizedStatus),
      onRowClick: handleBusinessSitesRowClick,
    });
    state.sitesGridOrgId = orgId;
    state.sitesGridManagerStatus = normalizedStatus;
    state.sitesGridStatus = normalizedStatus;

    return state.sitesGridActiveManager;
  };

  const announceBusinessSitesGridStatus = (message) => {
    setPlainStatusText(elements.businessSitesStatus, message);
  };

  const bindBusinessSitesGridInteractions = () => {
    syncBusinessSitesGridElementRefs();
    const container = elements.businessSitesGridContainer;
    if (!(container instanceof HTMLElement)) {
      return;
    }
    if (container.dataset.sitesGridInteractionsBound === '1') {
      return;
    }
    container.dataset.sitesGridInteractionsBound = '1';

    getBusinessSiteEditor();

    const workspace = document.getElementById('business-workspace');
    if (workspace instanceof HTMLElement && workspace.dataset.sitesCreateBound !== '1') {
      workspace.dataset.sitesCreateBound = '1';
      workspace.addEventListener('click', (event) => {
        const target = businessSitesGridEventTarget(event);
        const createBtn = target?.closest('[data-action="create-business-site"]');
        if (createBtn instanceof HTMLElement && workspace.contains(createBtn)) {
          event.preventDefault();
          if (resolveBusinessSitesGridStatus() === 'active') {
            getBusinessSiteEditor().openCreateSiteDialog('active');
          }
        }
      });
    }

    container.addEventListener('click', (event) => {
      handleBusinessSitesGridControlClick(event);
    });

    bindDataGridKeyboardNavigation({
      root: container,
      autofocusSearch: true,
      onActivate: (row) => {
        const rowId = row instanceof HTMLElement ? String(row.dataset.id || '').trim() : '';
        if (rowId === '' || !(row instanceof HTMLElement)) {
          return false;
        }

        handleBusinessSitesRowClick(rowId, row);
        return true;
      },
      onContextAction: (row) => {
        const action = row instanceof HTMLElement
          ? row.querySelector('.datagrid_action:not([disabled])')
          : null;
        if (action instanceof HTMLElement) {
          action.focus({ preventScroll: true });
          return true;
        }

        return false;
      },
    });

    document.querySelectorAll('[data-business-sites-status]').forEach((tab) => {
      if (!(tab instanceof HTMLButtonElement)) {
        return;
      }

      tab.addEventListener('click', () => {
        const status = resolveBusinessSitesGridStatus(tab.dataset.businessSitesStatus || 'active');
        state.sitesGridStatus = status;
        syncBusinessSitesStatusControls(status);

        const orgId = String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
        if (orgId !== '') {
          loadBusinessSitesGrid(orgId, status).catch((error) => PW.error(error));
        }
      });
    });

    document.addEventListener('paycal:datagrid-reloaded', (event) => {
      const detail = event?.detail || {};
      if (String(detail.gridId || '') !== 'businesses-sites-grid') {
        return;
      }

      const rowCount = Number(detail.rowCount || 0);
      const stateDetail = detail.state || {};
      const order = stateDetail.sort ? `${stateDetail.sort} ${stateDetail.direction || 'asc'}` : 'default order';
      const search = stateDetail.search ? `search ${stateDetail.search}` : 'no search filter';
      const page = stateDetail.page || 1;
      announceBusinessSitesGridStatus(`Business sites grid updated. ${rowCount} result${rowCount === 1 ? '' : 's'}. ${order}. ${search}. Page ${page}.`);
      state.contextSiteCount = rowCount;
      updateBusinessContextHeader();
    });
  };

  if (isBusinessSitesSubPage()) {
    bindBusinessSitesGridInteractions();
  }

  const loadBusinessSitesGrid = async (businessId = '', status = state.sitesGridStatus || 'active') => {
    if (resolveBusinessSubPage() !== 'sites') {
      return;
    }

    bindBusinessSitesGridInteractions();
    syncBusinessSitesGridElementRefs();

    const orgId = String(businessId || resolveWorkspaceBusinessId() || '').trim();
    if (orgId === '') {
      if (elements.businessSitesGridContainer instanceof HTMLElement) {
        setDatagridMessage(elements.businessSitesGridContainer, T.selectFirst);
      }
      announceBusinessSitesGridStatus(T.noBusinessSelected);
      return;
    }

    if (!(elements.businessSitesGridContainer instanceof HTMLElement)) {
      return;
    }

    const business = findBusiness(orgId);
    if (business && !canUsePremiumOrgFeatures(business)) {
      setDatagridMessage(elements.businessSitesGridContainer, T.premiumAdminLockedDetailed);
      announceBusinessSitesGridStatus(T.premiumAdminLockedDetailed);
      return;
    }

    if (business) {
      state.selectedBusinessId = orgId;
      setEditorMeta(business);
    }

    const normalizedStatus = resolveBusinessSitesGridStatus(status);
    state.sitesGridStatus = normalizedStatus;
    syncBusinessSitesStatusControls(normalizedStatus);
    const hydrateFromSsr = canHydrateBusinessSitesGridFromSsr(normalizedStatus);

    if (!hydrateFromSsr) {
      setDatagridMessage(elements.businessSitesGridContainer, T.loading, true);
      announceBusinessSitesGridStatus(T.businessSitesLoading);
    }

    try {
      const manager = ensureBusinessSitesGridManager(orgId, normalizedStatus);
      if (!manager) {
        throw new Error(T.businessSitesGridInitFailed);
      }

      if (hydrateFromSsr) {
        delete elements.businessSitesGridContainer.dataset.ssrSitesGrid;
        announceBusinessSitesGridFromDom(normalizedStatus);
        return;
      }

      await manager.reload();
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message
        ? error.message
        : T.businessSitesLoadFailed;
      setDatagridMessage(elements.businessSitesGridContainer, message);
      announceBusinessSitesGridStatus(message);
      PC.showToast(message, 'error', 7000, true);
    }
  };
