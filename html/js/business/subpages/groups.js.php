<?php namespace PayCal\Domain; ?>

  // Subpage module: groups (data-business-subpage="groups")

  const isBusinessGroupsSubPage = () => resolveBusinessSubPage() === 'groups';

  const syncBusinessGroupsGridElementRefs = () => {
    elements.businessGroupsGridContainer = document.getElementById('business-groups-grid');
    elements.businessGroupsStatus = document.getElementById('business_groups_sr_status');
  };

  const openBusinessGroupDialog = (dialog) => {
    openHtmlDialog(dialog);
  };

  const closeBusinessGroupDialog = (dialog) => {
    closeHtmlDialog(dialog);
  };

  const businessGroupsGridEndpoint = (orgId, status = 'active') => (
    `/api/v1/businesses/${encodeURIComponent(orgId)}/groups/grid?status=${encodeURIComponent(status)}`
  );

  const resolveBusinessGroupsGridStatus = (status = state.groupsGridStatus || 'active') => {
    return normalizeActiveArchivedStatus(status);
  };

  const getBusinessGroupEditorElements = () => ({
    workspace: document.getElementById('business-workspace'),
    dialog: document.getElementById('modal_business_group'),
    form: document.getElementById('business_groups_form'),
    title: document.getElementById('modal_business_group_title'),
    status: document.getElementById('business_groups_status_message'),
    groupIdInput: document.getElementById('business_groups_group_id'),
    nameInput: document.getElementById('business_groups_name'),
    descriptionInput: document.getElementById('business_groups_description'),
    submitButton: document.getElementById('business_groups_submit'),
    deleteButton: document.getElementById('business_groups_delete'),
  });

  const getBusinessGroupConfirmElements = () => ({
    dialog: document.getElementById('modal_business_group_confirm'),
    title: document.getElementById('modal_business_group_confirm_title'),
    message: document.getElementById('modal_business_group_confirm_message'),
    confirmButton: document.getElementById('business_group_confirm_yes'),
    cancelButton: document.getElementById('business_group_confirm_cancel'),
    closeButtons: document.querySelectorAll('[data-dialog-close="modal_business_group_confirm"]'),
  });

  const businessGroupConfirmController = createConfirmDialogController({
    getElements: getBusinessGroupConfirmElements,
    openDialog: openBusinessGroupDialog,
    closeDialog: closeBusinessGroupDialog,
  });

  const closeBusinessGroupConfirm = (confirmed = false) => businessGroupConfirmController.close(confirmed);

  const confirmBusinessGroupAction = (options = {}) => businessGroupConfirmController.confirm(options);

  const announceBusinessGroupsGridStatus = (message) => {
    setPlainStatusText(elements.businessGroupsStatus, message);
  };

  const focusBusinessGroupsSearch = () => {
    const container = elements.businessGroupsGridContainer;
    if (!(container instanceof HTMLElement)) {
      return false;
    }

    const activeDialog = document.querySelector('dialog[open]');
    if (activeDialog instanceof HTMLDialogElement) {
      return false;
    }

    const activeElement = document.activeElement;
    if (activeElement instanceof HTMLInputElement
      || activeElement instanceof HTMLTextAreaElement
      || activeElement instanceof HTMLSelectElement
      || (activeElement instanceof Element && activeElement.closest('[contenteditable="true"]') instanceof HTMLElement)) {
      return false;
    }

    const searchInput = container.querySelector('.datagrid_search');
    if (!(searchInput instanceof HTMLInputElement)) {
      return false;
    }

    searchInput.focus({ preventScroll: true });
    return true;
  };

  const scheduleBusinessGroupsSearchFocus = () => {
    window.setTimeout(focusBusinessGroupsSearch, 0);
  };

  const setBusinessGroupEditorStatus = (message) => {
    const { status } = getBusinessGroupEditorElements();
    setPlainStatusText(status, message);
  };

  const setBusinessGroupEditorBusy = (busy) => {
    const { submitButton, deleteButton } = getBusinessGroupEditorElements();
    const isBusy = busy === true;
    if (submitButton instanceof HTMLButtonElement) {
      submitButton.disabled = isBusy;
      submitButton.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    }
    if (deleteButton instanceof HTMLButtonElement && !deleteButton.hidden) {
      deleteButton.disabled = isBusy;
      deleteButton.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    }
  };

  const syncBusinessGroupsStatusControls = (status = state.groupsGridStatus || 'active') => {
    syncActiveArchivedStatusControls({
      status,
      tabSelector: '[data-business-groups-status]',
      tabDatasetKey: 'businessGroupsStatus',
      noteSelector: '[data-for-business-groups-status]',
      noteDatasetKey: 'forBusinessGroupsStatus',
      createSelector: '[data-action="create-business-group"]',
      resolveStatus: resolveBusinessGroupsGridStatus,
    });
  };

  const closeBusinessGroupEditor = () => {
    const { dialog, form, groupIdInput } = getBusinessGroupEditorElements();
    if (form instanceof HTMLFormElement) {
      form.reset();
    }
    if (groupIdInput instanceof HTMLInputElement) {
      groupIdInput.value = '';
    }
    closeBusinessGroupDialog(dialog);
    document.body.classList.remove('business_group_modal_open');
    setBusinessGroupEditorStatus('');
  };

  const openBusinessGroupEditor = (group = null) => {
    const {
      dialog,
      title,
      groupIdInput,
      nameInput,
      descriptionInput,
      submitButton,
      deleteButton,
    } = getBusinessGroupEditorElements();
    if (!(dialog instanceof HTMLDialogElement)) {
      return;
    }

    const groupId = String(group?.groupId || '').trim();
    if (title instanceof HTMLElement) {
      title.textContent = groupId === ''
        ? (T.businessGroupsCreate || 'Create group')
        : (T.businessGroupsEditorTitle || 'Edit group');
    }
    if (groupIdInput instanceof HTMLInputElement) {
      groupIdInput.value = groupId;
    }
    if (nameInput instanceof HTMLInputElement) {
      nameInput.value = String(group?.name || '');
    }
    if (descriptionInput instanceof HTMLInputElement) {
      descriptionInput.value = String(group?.description || '');
    }
    if (descriptionInput instanceof HTMLTextAreaElement) {
      descriptionInput.value = String(group?.description || '');
    }
    if (submitButton instanceof HTMLButtonElement) {
      submitButton.textContent = groupId === ''
        ? (T.businessGroupsCreate || 'Create group')
        : (T.businessGroupsSaveChanges || 'Save changes');
      submitButton.disabled = false;
      submitButton.setAttribute('aria-busy', 'false');
    }
    if (deleteButton instanceof HTMLButtonElement) {
      const groupStatus = resolveBusinessGroupsGridStatus(group?.status || '');
      const canShowDelete = groupId !== '';
      deleteButton.hidden = !canShowDelete;
      deleteButton.disabled = !canShowDelete;
      deleteButton.dataset.groupStatus = groupStatus;
      deleteButton.title = groupStatus === 'archived'
        ? ''
        : (T.businessGroupsDeleteActiveDenied || 'Archive the group before deleting it.');
      deleteButton.setAttribute('aria-busy', 'false');
    }

    setBusinessGroupEditorStatus('');
    openBusinessGroupDialog(dialog);
    document.body.classList.add('business_group_modal_open');
    if (nameInput instanceof HTMLInputElement) {
      nameInput.focus();
      nameInput.select();
    }
  };

  const activeBusinessGroupsGrid = () => {
    const container = elements.businessGroupsGridContainer;
    if (!(container instanceof HTMLElement)) {
      return null;
    }
    return container.querySelector('.datagrid[data-grid^="business-groups-"]');
  };

  const readBusinessGroupFromRow = (row) => {
    if (!(row instanceof HTMLElement)) {
      return null;
    }
    const groupId = String(row.dataset.id || '').trim();
    if (groupId === '') {
      return null;
    }

    const name = String(row.querySelector('.business_groups_name_text')?.textContent || '').trim();
    const description = String(row.querySelector('.business_groups_description_text')?.textContent || '').trim();
    const gridId = String(row.closest('.datagrid[data-grid]')?.dataset?.grid || '');
    return {
      groupId,
      name,
      description,
      status: gridId.includes('archived') ? 'archived' : resolveBusinessGroupsGridStatus(),
    };
  };

  const handleBusinessGroupRowAction = async (action, row, actionButton = null) => {
    const group = readBusinessGroupFromRow(row);
    const businessId = String(resolveWorkspaceBusinessId() || '').trim();
    if (!group || businessId === '') {
      return;
    }

    const isRestore = action === 'restore-group';
    const message = isRestore
      ? (T.businessGroupsRestoreConfirm || `Restore "${group.name}"?`)
      : (T.businessGroupsArchiveConfirm || `Archive "${group.name}"?`);
    const actionLabel = isRestore
      ? (T.businessGroupsRestoreAction || 'Restore')
      : (T.businessGroupsArchiveAction || 'Archive');
    const confirmed = await confirmBusinessGroupAction({
      title: `${actionLabel} group?`,
      message: message.replace('{name}', group.name),
      confirmText: actionLabel,
      confirmClass: 'btn btn_primary',
    });
    if (!confirmed) {
      return;
    }

    const endpointAction = isRestore ? 'restore' : 'archive';
    try {
      if (actionButton instanceof HTMLButtonElement) {
        actionButton.disabled = true;
        actionButton.setAttribute('aria-busy', 'true');
      }
      await postForm(`/api/v1/businesses/${encodeURIComponent(businessId)}/groups/${encodeURIComponent(group.groupId)}/${endpointAction}`, {});
      await loadBusinessGroupsGrid(businessId, state.groupsGridStatus || 'active');
    } catch (error) {
      PW.error(error);
      const fallback = isRestore
        ? (T.businessGroupsRestoreFailed || 'Unable to restore group.')
        : (T.businessGroupsArchiveFailed || 'Unable to archive group.');
      PC.showToast(fallback, 'error', 7000, true);
    } finally {
      if (actionButton instanceof HTMLButtonElement) {
        actionButton.disabled = false;
        actionButton.setAttribute('aria-busy', 'false');
      }
    }
  };

  const canHydrateBusinessGroupsGridFromSsr = (status = 'active') => {
    const container = elements.businessGroupsGridContainer;
    if (!(container instanceof HTMLElement)) {
      return false;
    }
    const normalizedStatus = resolveBusinessGroupsGridStatus(status);
    const ssrStatus = String(container.dataset.ssrGroupsGrid || '').trim();
    return ssrStatus === normalizedStatus
      && container.querySelector(`.datagrid[data-grid="business-groups-${normalizedStatus}"]`) instanceof HTMLElement;
  };

  const ensureBusinessGroupsGridManager = (orgId, status = 'active') => {
    const normalizedStatus = resolveBusinessGroupsGridStatus(status);
    if (
      state.groupsGridActiveManager
      && state.groupsGridOrgId === orgId
      && state.groupsGridManagerStatus === normalizedStatus
    ) {
      return state.groupsGridActiveManager;
    }

    if (state.groupsGridActiveManager && typeof state.groupsGridActiveManager.destroy === 'function') {
      state.groupsGridActiveManager.destroy();
    }

    state.groupsGridActiveManager = createDataGrid({
      id: 'business-groups-grid',
      endpoint: businessGroupsGridEndpoint(orgId, normalizedStatus),
    });
    state.groupsGridOrgId = orgId;
    state.groupsGridManagerStatus = normalizedStatus;
    state.groupsGridStatus = normalizedStatus;

    return state.groupsGridActiveManager;
  };

  const loadBusinessGroupsGrid = async (businessId = '', status = state.groupsGridStatus || 'active') => {
    if (!isBusinessGroupsSubPage()) {
      return;
    }

    bindBusinessGroupsPage();
    syncBusinessGroupsGridElementRefs();

    const orgId = String(businessId || resolveWorkspaceBusinessId() || '').trim();
    if (orgId === '' || !(elements.businessGroupsGridContainer instanceof HTMLElement)) {
      return;
    }

    const normalizedStatus = resolveBusinessGroupsGridStatus(status);
    state.groupsGridStatus = normalizedStatus;
    syncBusinessGroupsStatusControls(normalizedStatus);
    const hydrateFromSsr = canHydrateBusinessGroupsGridFromSsr(normalizedStatus);
    if (!hydrateFromSsr) {
      setDatagridMessage(elements.businessGroupsGridContainer, T.loading, true);
      announceBusinessGroupsGridStatus(T.businessGroupsLoading || 'Loading groups...');
    }

    try {
      const manager = ensureBusinessGroupsGridManager(orgId, normalizedStatus);
      if (!manager) {
        throw new Error(T.businessGroupsGridInitFailed || 'Unable to initialize groups grid.');
      }

      if (hydrateFromSsr) {
        delete elements.businessGroupsGridContainer.dataset.ssrGroupsGrid;
        announceBusinessGroupsGridStatus(T.businessGroupsLoaded || 'Groups loaded.');
        scheduleBusinessGroupsSearchFocus();
        return;
      }

      await manager.reload();
      scheduleBusinessGroupsSearchFocus();
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message
        ? error.message
        : (T.businessGroupsLoadFailed || 'Unable to load groups.');
      setDatagridMessage(elements.businessGroupsGridContainer, message);
      announceBusinessGroupsGridStatus(message);
      PC.showToast(message, 'error', 7000, true);
    }
  };

  const bindBusinessGroupsPage = () => {
    if (!isBusinessGroupsSubPage()) {
      return;
    }

    syncBusinessGroupsGridElementRefs();
    const { workspace, form } = getBusinessGroupEditorElements();
    const container = elements.businessGroupsGridContainer;
    if (!(workspace instanceof HTMLElement) || !(form instanceof HTMLFormElement) || !(container instanceof HTMLElement)) {
      return;
    }
    if (workspace.dataset.groupsPageBound === '1') {
      return;
    }
    workspace.dataset.groupsPageBound = '1';

    workspace.addEventListener('click', (event) => {
      const target = event.target instanceof Element ? event.target : null;
      const createButton = target?.closest('[data-action="create-business-group"]');
      if (createButton instanceof HTMLElement && workspace.contains(createButton)) {
        event.preventDefault();
        if (resolveBusinessGroupsGridStatus() === 'active') {
          openBusinessGroupEditor();
        }
      }
    });

    container.addEventListener('click', async (event) => {
      const target = event.target instanceof Element ? event.target : null;
      if (!target) {
        return;
      }

      const createButton = target.closest('[data-action="create-business-group"]');
      if (createButton instanceof HTMLElement) {
        event.preventDefault();
        if (resolveBusinessGroupsGridStatus() === 'active') {
          openBusinessGroupEditor();
        }
        return;
      }

      const actionButton = target.closest('.datagrid_action[data-action]');
      if (actionButton instanceof HTMLElement) {
        event.preventDefault();
        const row = actionButton.closest('.datagrid_row');
        await handleBusinessGroupRowAction(String(actionButton.dataset.action || ''), row, actionButton);
        return;
      }

      if (target.closest('.datagrid_action, .datagrid_sort, .datagrid_search, .datagrid_pager, .datagrid_pagination, .datagrid_column_toggle, .datagrid_controls')) {
        return;
      }

      const row = target.closest('.datagrid_row');
      if (!(row instanceof HTMLElement) || row.classList.contains('datagrid_row_empty')) {
        return;
      }
      const group = readBusinessGroupFromRow(row);
      if (group) {
        openBusinessGroupEditor(group);
      }
    });

    bindDataGridKeyboardNavigation({
      root: container,
      autofocusSearch: true,
      onActivate: (row) => {
        const group = readBusinessGroupFromRow(row);
        if (!group) {
          return false;
        }

        openBusinessGroupEditor(group);
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

    document.querySelectorAll('[data-business-groups-status]').forEach((tab) => {
      if (!(tab instanceof HTMLButtonElement)) {
        return;
      }
      tab.addEventListener('click', () => {
        const status = resolveBusinessGroupsGridStatus(tab.dataset.businessGroupsStatus || 'active');
        state.groupsGridStatus = status;
        syncBusinessGroupsStatusControls(status);
        const orgId = String(resolveWorkspaceBusinessId() || '').trim();
        if (orgId !== '') {
          loadBusinessGroupsGrid(orgId, status).catch((error) => PW.error(error));
        }
      });
    });

    document.querySelectorAll('[data-dialog-close="modal_business_group"]').forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        closeBusinessGroupEditor();
      });
    });

    const { dialog } = getBusinessGroupEditorElements();
    if (dialog instanceof HTMLDialogElement) {
      dialog.addEventListener('close', () => {
        document.body.classList.remove('business_group_modal_open');
      });
    }

    businessGroupConfirmController.bind();

    const { deleteButton } = getBusinessGroupEditorElements();
    if (deleteButton instanceof HTMLButtonElement) {
      deleteButton.addEventListener('click', async () => {
        const { groupIdInput, nameInput } = getBusinessGroupEditorElements();
        const businessId = String(resolveWorkspaceBusinessId() || '').trim();
        const groupId = String(groupIdInput?.value || '').trim();
        const name = String(nameInput?.value || '').trim() || 'this group';
        if (businessId === '' || groupId === '') {
          return;
        }

        if (resolveBusinessGroupsGridStatus(deleteButton.dataset.groupStatus || '') !== 'archived') {
          const message = T.businessGroupsDeleteActiveDenied || 'Archive the group before deleting it.';
          setBusinessGroupEditorStatus(message);
          PC.showToast(message, 'error', 5000, true);
          return;
        }

        const message = String(T.businessGroupsDeleteConfirm || 'Permanently delete "{name}"?').replace('{name}', name);
        const deleteLabel = T.businessGroupsDeleteAction || 'Delete group';
        const confirmed = await confirmBusinessGroupAction({
          title: `${deleteLabel}?`,
          message,
          confirmText: deleteLabel,
          confirmClass: 'btn btn_danger',
        });
        if (!confirmed) {
          return;
        }

        try {
          setBusinessGroupEditorBusy(true);
          await postForm(`/api/v1/businesses/${encodeURIComponent(businessId)}/groups/${encodeURIComponent(groupId)}/delete`, {});
          closeBusinessGroupEditor();
          PC.showToast(T.businessGroupsDeleted || 'Group deleted.', 'save', 5000, true);
          await loadBusinessGroupsGrid(businessId, state.groupsGridStatus || 'archived');
        } catch (error) {
          PW.error(error);
          PC.showToast(T.businessGroupsDeleteFailed || 'Unable to delete group.', 'error', 7000, true);
        } finally {
          setBusinessGroupEditorBusy(false);
        }
      });
    }

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      const { groupIdInput, nameInput, descriptionInput } = getBusinessGroupEditorElements();
      const businessId = String(resolveWorkspaceBusinessId() || '').trim();
      const name = String(nameInput?.value || '').trim();
      const groupId = String(groupIdInput?.value || '').trim();
      if (businessId === '') {
        return;
      }
      if (name.length < 2) {
        setBusinessGroupEditorStatus(T.businessGroupsNameRequired || 'Group name is required.');
        return;
      }

      try {
        setBusinessGroupEditorBusy(true);
        await postForm(`/api/v1/businesses/${encodeURIComponent(businessId)}/groups`, {
          group_id: groupId,
          name,
          description: String(descriptionInput?.value || '').trim(),
        });
        closeBusinessGroupEditor();
        if (groupId === '') {
          state.groupsGridStatus = 'active';
        }
        await loadBusinessGroupsGrid(businessId, state.groupsGridStatus || 'active');
      } catch (error) {
        PW.error(error);
        setBusinessGroupEditorStatus(T.businessGroupsSaveFailed || 'Unable to save group.');
      } finally {
        setBusinessGroupEditorBusy(false);
      }
    });

    document.addEventListener('paycal:datagrid-reloaded', (event) => {
      const detail = event?.detail || {};
      if (String(detail.gridId || '') !== 'business-groups-grid') {
        return;
      }
      const rowCount = Number(detail.rowCount || 0);
      announceBusinessGroupsGridStatus(`Business groups grid updated. ${rowCount} result${rowCount === 1 ? '' : 's'}.`);
    });

    if (workspace.dataset.createOnLoad === '1') {
      openBusinessGroupEditor();
    }
  };

  const initBusinessGroupsPage = () => {
    if (!isBusinessGroupsSubPage()) {
      return;
    }

    bindBusinessGroupsPage();
    const orgId = String(resolveWorkspaceBusinessId() || '').trim();
    if (orgId !== '') {
      loadBusinessGroupsGrid(orgId, state.groupsGridStatus || 'active').catch((error) => PW.error(error));
    }
  };

  if (isBusinessGroupsSubPage()) {
    initBusinessGroupsPage();
  }
