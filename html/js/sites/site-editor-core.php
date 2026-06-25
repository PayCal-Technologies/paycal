<?php declare(strict_types=1);

namespace PayCal\Domain;

/**
 * Shared site editor dialog logic for personal /sites and business /business/sites.
 */
?>
  const SITE_EDITOR_FORM_HEADERS = Object.freeze({
    'Content-Type': 'application/x-www-form-urlencoded',
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
  });

  let siteEditorColorPickSave = false;
  let siteEditorDeleteSiteId = null;
  let siteEditorDeleteSiteName = null;
  let siteEditorDeleteSiteStatus = 'active';
  let siteEditorDeleteOwnerUUID = '';
  let siteEditorHooksBound = false;

  const SITE_EDITOR_DIALOG_IDS = new Set([
    'modal_create_site',
    'modal_edit_site',
    'modal_confirm_delete_site',
    'modal_archived_work',
    'modal_finality_delete',
  ]);

  const siteEditorGetElement = (id) => document.getElementById(id);

  const siteEditorOpenDialog = (dialog) => {
    if (!(dialog instanceof HTMLDialogElement)) {
      return;
    }

    dialog.setAttribute('aria-hidden', 'false');
    if (!dialog.open) {
      dialog.showModal();
    }
  };

  const siteEditorCloseDialog = (dialog) => {
    if (!(dialog instanceof HTMLDialogElement)) {
      return;
    }

    const active = document.activeElement;
    if (active instanceof HTMLElement && dialog.contains(active)) {
      active.blur();
    }
    if (dialog.open) {
      dialog.close();
    }
    dialog.setAttribute('aria-hidden', 'true');
  };

  const siteEditorConfirmAction = ({
    title = '',
    message = '',
    confirmText = '',
    confirmClass = 'btn btn_primary',
  } = {}) => new Promise((resolve) => {
    const modal = siteEditorGetElement('modal_confirm_delete_site');
    const titleEl = siteEditorGetElement('modal_confirm_delete_site_title');
    const messageEl = siteEditorGetElement('confirm_delete_site_message');
    const confirmBtn = siteEditorGetElement('confirm_delete_site_yes');
    const cancelBtn = siteEditorGetElement('confirm_delete_site_no');
    const ariaEl = siteEditorGetElement('confirm_delete_site_aria');
    if (!(modal instanceof HTMLDialogElement) || !(confirmBtn instanceof HTMLButtonElement)) {
      resolve(false);
      return;
    }

    siteEditorDeleteSiteId = null;
    siteEditorDeleteSiteName = null;
    siteEditorDeleteOwnerUUID = '';
    siteEditorDeleteSiteStatus = 'active';

    if (titleEl instanceof HTMLElement) {
      titleEl.textContent = String(title || '');
    }
    if (ariaEl instanceof HTMLElement) {
      ariaEl.textContent = String(title || '');
    }
    if (messageEl instanceof HTMLElement) {
      messageEl.textContent = String(message || '');
    }
    confirmBtn.textContent = String(confirmText || title || 'Confirm');
    confirmBtn.className = confirmClass;
    confirmBtn.disabled = false;
    if (cancelBtn instanceof HTMLButtonElement) {
      cancelBtn.textContent = SITES_T.CANCEL;
    }

    let settled = false;
    const cleanup = () => {
      confirmBtn.removeEventListener('click', onConfirm);
      cancelBtn?.removeEventListener('click', onCancel);
      modal.removeEventListener('cancel', onCancel);
      modal.removeEventListener('close', onClose);
    };
    const settle = (confirmed) => {
      if (settled) {
        return;
      }
      settled = true;
      cleanup();
      if (modal.open) {
        siteEditorCloseDialog(modal);
      }
      resolve(confirmed === true);
    };
    const onConfirm = () => settle(true);
    const onCancel = (event) => {
      if (event instanceof Event) {
        event.preventDefault();
      }
      settle(false);
    };
    const onClose = () => settle(false);

    confirmBtn.addEventListener('click', onConfirm);
    cancelBtn?.addEventListener('click', onCancel);
    modal.addEventListener('cancel', onCancel);
    modal.addEventListener('close', onClose);
    siteEditorOpenDialog(modal);
    window.requestAnimationFrame(() => confirmBtn.focus());
  });

  const siteEditorResolveGridSiteName = (row) => {
    if (!(row instanceof HTMLElement)) {
      return '';
    }

    const nameEl = row.querySelector('.business_sites_site_name_text');
    if (nameEl instanceof HTMLElement) {
      return String(nameEl.textContent || '').trim();
    }

    const siteNameCol = row.querySelector('.datagrid_col_site_name');
    if (siteNameCol instanceof HTMLElement) {
      return String(siteNameCol.textContent || '').trim();
    }

    return String(row.querySelector('.datagrid_item')?.textContent || '').trim();
  };

  const siteEditorSetFieldInvalidState = (input, isInvalid) => {
    if (!(input instanceof HTMLElement)) {
      return;
    }
    input.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
  };

  const siteEditorSetFormStatus = (statusElementId, message) => {
    const statusEl = siteEditorGetElement(statusElementId);
    if (statusEl instanceof HTMLElement) {
      statusEl.textContent = String(message || '');
    }
  };

  const siteEditorSetDeleteButtonState = (siteStatus, canDelete) => {
    const deleteBtn = siteEditorGetElement('edit_site_delete');
    if (!(deleteBtn instanceof HTMLButtonElement)) {
      return;
    }

    const isArchived = String(siteStatus || '').trim().toLowerCase() === 'archived';
    deleteBtn.hidden = !isArchived;
    deleteBtn.disabled = !isArchived || !canDelete;
  };

  const siteEditorSetFieldError = (input, errorElementId, message) => {
    const text = String(message || '').trim();
    siteEditorSetFieldInvalidState(input, text.length > 0);
    const errorEl = siteEditorGetElement(errorElementId);
    if (errorEl instanceof HTMLElement) {
      errorEl.textContent = text;
    }
  };

  const siteEditorValidateRequiredSiteFields = (nameInput, wageInput, statusElementId, nameErrorId, wageErrorId) => {
    const nameValue = String(nameInput?.value || '').trim();
    const wageValue = String(wageInput?.value || '').trim();

    siteEditorSetFieldError(nameInput, nameErrorId, '');
    siteEditorSetFieldError(wageInput, wageErrorId, '');
    siteEditorSetFormStatus(statusElementId, '');

    if (!nameValue || !wageValue) {
      if (!nameValue) {
        siteEditorSetFieldError(nameInput, nameErrorId, SITES_T.SITES_ERROR_ENTER_SITE_NAME);
      }
      if (!wageValue) {
        siteEditorSetFieldError(wageInput, wageErrorId, SITES_T.SITES_ERROR_ENTER_HOURLY_WAGE);
      }
      siteEditorSetFormStatus(statusElementId, SITES_T.SITES_ERROR_ENTER_NAME_AND_WAGE);
      (nameValue ? wageInput : nameInput)?.focus();
      return false;
    }

    const wageNum = parseFloat(wageValue);
    if (Number.isNaN(wageNum) || wageNum <= 0) {
      siteEditorSetFieldError(wageInput, wageErrorId, SITES_T.SITES_ERROR_WAGE_MUST_BE_POSITIVE);
      siteEditorSetFormStatus(statusElementId, SITES_T.SITES_ERROR_WAGE_ZERO_EXPLANATION);
      wageInput?.focus();
      return false;
    }

    return true;
  };

  const siteEditorApplyColorSelection = (hex, label) => {
    const colorHidden = siteEditorGetElement('edit_site_color_input');
    if (colorHidden instanceof HTMLInputElement) {
      colorHidden.value = hex;
    }
    const colorName = siteEditorGetElement('edit_site_color_name');
    if (colorName instanceof HTMLElement) {
      colorName.textContent = label || '';
    }
    document.querySelectorAll('.site_color_swatch').forEach((swatch) => {
      if (!(swatch instanceof HTMLElement)) {
        return;
      }
      const selected = String(swatch.dataset.hex || '').toLowerCase() === hex.toLowerCase();
      swatch.classList.toggle('is-selected', selected);
      swatch.setAttribute('aria-pressed', selected ? 'true' : 'false');
    });
  };

  const siteEditorBindColorSwatches = () => {
    const swatchesContainer = siteEditorGetElement('edit_site_color_swatches');
    const colorHiddenInput = siteEditorGetElement('edit_site_color_input');
    if (!(swatchesContainer instanceof HTMLElement) || !(colorHiddenInput instanceof HTMLInputElement)) {
      return;
    }
    if (swatchesContainer.dataset.siteEditorColorBound === '1') {
      return;
    }
    swatchesContainer.dataset.siteEditorColorBound = '1';

    swatchesContainer.addEventListener('click', (event) => {
      const sw = event.target instanceof Element ? event.target.closest('.site_color_swatch') : null;
      if (!(sw instanceof HTMLElement) || !sw.dataset.hex) {
        return;
      }
      siteEditorApplyColorSelection(String(sw.dataset.hex), String(sw.dataset.label || ''));
      const editForm = siteEditorGetElement('edit_site_form');
      if (editForm instanceof HTMLFormElement) {
        siteEditorColorPickSave = true;
        editForm.requestSubmit();
      }
    });
  };

  const siteEditorBindDialogCloseButtons = () => {
    SITE_EDITOR_DIALOG_IDS.forEach((dialogId) => {
      const dialog = siteEditorGetElement(dialogId);
      if (!(dialog instanceof HTMLDialogElement) || dialog.dataset.siteEditorCloseCleanupBound === '1') {
        return;
      }
      dialog.dataset.siteEditorCloseCleanupBound = '1';
      dialog.addEventListener('close', () => {
        if (dialogId === 'modal_create_site') {
          const form = siteEditorGetElement('create_site_form');
          if (form instanceof HTMLFormElement) {
            form.reset();
          }
          siteEditorSetFormStatus('create_site_form_status', '');
        } else if (dialogId === 'modal_edit_site') {
          siteEditorSetFormStatus('edit_site_form_status', '');
        } else if (dialogId === 'modal_confirm_delete_site') {
          siteEditorDeleteSiteId = null;
          siteEditorDeleteSiteName = null;
          siteEditorDeleteOwnerUUID = '';
          siteEditorDeleteSiteStatus = 'active';
          const confirmBtn = siteEditorGetElement('confirm_delete_site_yes');
          if (confirmBtn instanceof HTMLButtonElement) {
            confirmBtn.disabled = false;
          }
        }
      });
    });
  };

  const siteEditorPopulatePlanningFields = (businessName, settings, businessId, ownerUUID) => {
    const orgPlanningEl = siteEditorGetElement('edit_site_org_planning');
    const orgEmptyEl = siteEditorGetElement('edit_site_org_planning_empty');
    const s = settings || {};

    const planOrgId = siteEditorGetElement('edit_site_plan_org_id');
    if (planOrgId instanceof HTMLInputElement) {
      planOrgId.value = businessId;
    }
    const planOwner = siteEditorGetElement('edit_site_plan_owner_uuid');
    if (planOwner instanceof HTMLInputElement) {
      planOwner.value = ownerUUID;
    }
    const orgNameEl = siteEditorGetElement('edit_site_org_planning_org_name');
    if (orgNameEl instanceof HTMLElement) {
      orgNameEl.textContent = businessName || '';
    }
    const budgetEl = siteEditorGetElement('edit_site_plan_budget');
    if (budgetEl instanceof HTMLInputElement) {
      budgetEl.value = s.budget_amount || '';
    }
    const warnEl = siteEditorGetElement('edit_site_plan_warn');
    if (warnEl instanceof HTMLInputElement) {
      warnEl.value = s.warn_threshold || '80';
    }
    const critEl = siteEditorGetElement('edit_site_plan_critical');
    if (critEl instanceof HTMLInputElement) {
      critEl.value = s.critical_threshold || '95';
    }
    const planStatusEl = siteEditorGetElement('edit_site_plan_status_select');
    if (planStatusEl instanceof HTMLSelectElement) {
      planStatusEl.value = s.site_status || 'active';
    }
    const clientInput = siteEditorGetElement('edit_site_client_input');
    if (clientInput instanceof HTMLInputElement) {
      clientInput.value = s.client_name || '';
    }
    const costCodeInput = siteEditorGetElement('edit_site_cost_code_input');
    if (costCodeInput instanceof HTMLInputElement) {
      costCodeInput.value = s.cost_code || '';
    }
    const startDateInput = siteEditorGetElement('edit_site_start_date_input');
    if (startDateInput instanceof HTMLInputElement) {
      startDateInput.value = s.start_date || '';
    }
    const endDateInput = siteEditorGetElement('edit_site_end_date_input');
    if (endDateInput instanceof HTMLInputElement) {
      endDateInput.value = s.end_date || '';
    }

    if (orgPlanningEl instanceof HTMLElement) {
      orgPlanningEl.removeAttribute('hidden');
    }
    if (orgEmptyEl instanceof HTMLElement) {
      orgEmptyEl.hidden = true;
    }
  };

  const siteEditorHidePlanningFields = (message = '') => {
    const orgPlanningEl = siteEditorGetElement('edit_site_org_planning');
    const orgEmptyEl = siteEditorGetElement('edit_site_org_planning_empty');
    if (orgPlanningEl instanceof HTMLElement) {
      orgPlanningEl.setAttribute('hidden', '');
    }
    if (orgEmptyEl instanceof HTMLElement) {
      if (!orgEmptyEl.dataset.defaultText) {
        orgEmptyEl.dataset.defaultText = String(orgEmptyEl.textContent || '').trim();
      }
      orgEmptyEl.textContent = String(message || orgEmptyEl.dataset.defaultText || '');
      orgEmptyEl.hidden = false;
    }

    ['edit_site_plan_org_id', 'edit_site_plan_owner_uuid'].forEach((id) => {
      const field = siteEditorGetElement(id);
      if (field instanceof HTMLInputElement) {
        field.value = '';
      }
    });
  };

  const siteEditorPopulateSiteFields = (site, ownerUUID) => {
    const siteId = String(site?.id || site?.site_id || '');
    const idInput = siteEditorGetElement('edit_site_id');
    if (idInput instanceof HTMLInputElement) {
      idInput.value = siteId;
    }
    const ownerInput = siteEditorGetElement('edit_site_owner_uuid');
    if (ownerInput instanceof HTMLInputElement) {
      ownerInput.value = ownerUUID || '';
    }

    const setValue = (id, value) => {
      const el = siteEditorGetElement(id);
      if (el instanceof HTMLInputElement || el instanceof HTMLSelectElement) {
        el.value = value;
      }
    };
    const setChecked = (id, value) => {
      const el = siteEditorGetElement(id);
      if (el instanceof HTMLInputElement) {
        el.checked = String(value || '') === '1';
      }
    };

    setValue('edit_site_name_input', site?.site_name || '');
    setValue('edit_site_wage_input', site?.wage || '');
    setValue('edit_site_loa_input', site?.living_out_allowance || '');
    setValue('edit_site_travel_input', site?.travel_hours || '');
    setValue('edit_site_province_select', site?.province || '');
    setValue('edit_site_status_select', site?.status || 'active');
    setValue('edit_site_default_hours_input', site?.default_hours || '');
    setChecked('edit_site_is_on_reserve_input', site?.is_on_reserve || '0');
    setValue('edit_site_reserve_name_input', site?.reserve_name || '');

    const savedColor = String(site?.site_color || '').toLowerCase() || '#6aa6ff';
    siteEditorApplyColorSelection(savedColor, '');
    const matchingSwatch = document.querySelector(`.site_color_swatch[data-hex="${savedColor}"], .site_color_swatch[data-hex="${savedColor.toUpperCase()}"]`);
    if (matchingSwatch instanceof HTMLElement) {
      siteEditorApplyColorSelection(savedColor, matchingSwatch.getAttribute('aria-label') || '');
    }
  };

  const siteEditorSetSiteFieldsReadOnly = (readOnly) => {
    const form = siteEditorGetElement('edit_site_form');
    if (!(form instanceof HTMLFormElement)) {
      return;
    }
    form.querySelectorAll('.edit_site_col_basic input, .edit_site_col_basic select, .edit_site_col_basic textarea, .edit_site_col_basic button').forEach((field) => {
      if (!(field instanceof HTMLElement)) {
        return;
      }
      if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement) {
        field.disabled = readOnly;
      }
      if (field instanceof HTMLButtonElement) {
        field.disabled = readOnly;
      }
    });
  };

  const siteEditorSetPlanningFieldsReadOnly = (readOnly) => {
    const planningEl = siteEditorGetElement('edit_site_org_planning');
    if (!(planningEl instanceof HTMLElement)) {
      return;
    }
    planningEl.querySelectorAll('input, select, textarea, button').forEach((field) => {
      if (field instanceof HTMLInputElement || field instanceof HTMLSelectElement || field instanceof HTMLTextAreaElement || field instanceof HTMLButtonElement) {
        field.disabled = readOnly;
      }
    });
  };

  const siteEditorSetEditorAccess = (canWriteSite, canWritePlanning) => {
    const form = siteEditorGetElement('edit_site_form');
    if (!(form instanceof HTMLFormElement)) {
      return;
    }

    form.dataset.canWriteSite = canWriteSite ? '1' : '0';
    form.dataset.canWritePlanning = canWritePlanning ? '1' : '0';
    siteEditorSetSiteFieldsReadOnly(!canWriteSite);
    siteEditorSetPlanningFieldsReadOnly(!canWritePlanning);

    const submitBtn = siteEditorGetElement('edit_site_submit');
    if (submitBtn instanceof HTMLButtonElement) {
      submitBtn.disabled = !canWriteSite && !canWritePlanning;
    }
  };

  const siteEditorSetReadOnly = (readOnly) => {
    siteEditorSetEditorAccess(!readOnly, !readOnly);
  };

  const initSiteEditor = (config) => {
    const {
      mode = 'personal',
      canWrite = () => true,
      getBusinessId = () => '',
      reloadGrids = async () => {},
      getGridStatus = () => 'active',
      apiBase = '/api/v1',
    } = config || {};

    if (!siteEditorHooksBound) {
      siteEditorHooksBound = true;
      siteEditorBindDialogCloseButtons();
      siteEditorBindColorSwatches();

      const createForm = siteEditorGetElement('create_site_form');
      createForm?.addEventListener('submit', (event) => {
        handleSiteEditorCreateSubmit(event).catch((error) => PW.error(error));
      });

      const editForm = siteEditorGetElement('edit_site_form');
      editForm?.addEventListener('submit', (event) => {
        handleSiteEditorEditSubmit(event).catch((error) => PW.error(error));
      });

      const deleteYesBtn = siteEditorGetElement('confirm_delete_site_yes');
      deleteYesBtn?.addEventListener('click', () => {
        handleSiteEditorConfirmDelete().catch((error) => PW.error(error));
      });

      const unlinkBtn = siteEditorGetElement('edit_site_unlink_business');
      unlinkBtn?.addEventListener('click', () => {
        handleSiteEditorUnlinkFromBusiness().catch((error) => PW.error(error));
      });

      const editDeleteBtn = siteEditorGetElement('edit_site_delete');
      editDeleteBtn?.addEventListener('click', () => {
        const siteId = siteEditorGetElement('edit_site_id')?.value || '';
        const siteName = siteEditorGetElement('edit_site_name_input')?.value || SITES_T.SITES_THIS_SITE;
        const ownerUUID = siteEditorGetElement('edit_site_owner_uuid')?.value || '';
        if (siteId !== '') {
          const editModal = siteEditorGetElement('modal_edit_site');
          if (editModal instanceof HTMLDialogElement && editModal.open) {
            siteEditorCloseDialog(editModal);
            window.requestAnimationFrame(() => openDeleteSiteDialog(siteId, siteName, 'archived', ownerUUID));
            return;
          }
          openDeleteSiteDialog(siteId, siteName, 'archived', ownerUUID);
        }
      });
    }

    const openCreateSiteDialog = (status = 'active') => {
      if (!canWrite()) {
        PC.showToast(T.premiumAdminLockedDetailed || 'You do not have permission to create sites.', 'error', 7000, true);
        return;
      }

      const modal = siteEditorGetElement('modal_create_site');
      const form = siteEditorGetElement('create_site_form');
      const statusInput = siteEditorGetElement('create_site_status');
      if (!(modal instanceof HTMLDialogElement) || !(form instanceof HTMLFormElement)) {
        return;
      }

      form.reset();
      if (statusInput instanceof HTMLInputElement) {
        statusInput.value = status;
      }
      siteEditorSetFormStatus('create_site_form_status', '');
      siteEditorOpenDialog(modal);
    };

    const openEditSiteDialog = async (rowId) => {
      const modal = siteEditorGetElement('modal_edit_site');
      if (!(modal instanceof HTMLDialogElement)) {
        return;
      }

      if (mode === 'business') {
        const businessId = String(getBusinessId() || '').trim();
        const parts = String(rowId || '').split(':');
        if (businessId === '' || parts.length !== 2) {
          return;
        }
        const [siteOwnerUUID, siteId] = parts;

        try {
          const response = await fetch(
            `${apiBase}/businesses/${encodeURIComponent(businessId)}/sites/${encodeURIComponent(siteOwnerUUID)}/${encodeURIComponent(siteId)}/editor`,
            { method: 'GET', credentials: 'include', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } },
          );
          const payload = await response.json();
          const data = payload?.data && typeof payload.data === 'object' ? payload.data : payload;
          if (payload?.status !== 'success' && !data?.site) {
            throw new Error(payload?.message || SITES_T.SITES_ERROR_LOADING);
          }

          siteEditorPopulateSiteFields(data.site, data.site_owner_uuid || siteOwnerUUID);
          const canWriteSite = !!data.can_write_site && canWrite();
          const canViewPlanning = !!data.can_view_planning;
          const canWritePlanning = canViewPlanning && !!data.can_write_planning;
          if (canViewPlanning) {
            siteEditorPopulatePlanningFields(
              data.business?.name || '',
              data.settings || {},
              businessId,
              data.site_owner_uuid || siteOwnerUUID,
            );
          } else {
            siteEditorHidePlanningFields(SITES_T.SITES_BUSINESS_PLANNING_LOCKED || '');
          }

          siteEditorSetEditorAccess(canWriteSite, canWritePlanning);
          siteEditorSetDeleteButtonState(data.site?.status || getGridStatus(), canWriteSite);
          if (!canWriteSite && !canWritePlanning) {
            siteEditorSetFormStatus('edit_site_form_status', SITES_T.SITES_READ_ONLY_ACCESS);
          } else {
            siteEditorSetFormStatus('edit_site_form_status', '');
          }

          siteEditorOpenDialog(modal);
          const nameInput = siteEditorGetElement('edit_site_name_input');
          if (nameInput instanceof HTMLInputElement && canWriteSite) {
            window.requestAnimationFrame(() => nameInput.focus());
          }
        } catch (error) {
          PW.error(error);
          PC.showToast(PC.resolveThrownMessage(error, SITES_T.SITES_ERROR_LOADING), 'error', 7000, true);
        }
        return;
      }

      try {
        const response = await fetch(`${apiBase}/sites/get?id=${encodeURIComponent(String(rowId || ''))}`, {
          method: 'GET',
          credentials: 'include',
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const payload = await response.json();
        if (payload?.status !== 'success') {
          throw new Error(payload?.message || SITES_T.SITES_ERROR_LOADING);
        }

        siteEditorPopulateSiteFields(payload.site, '');
        const orgCtx = payload.org_context;
        const orgPlanningEl = siteEditorGetElement('edit_site_org_planning');
        if (orgPlanningEl instanceof HTMLElement) {
          if (orgCtx && orgCtx.org_id) {
            const canViewPlanning = orgCtx.can_view_planning !== false;
            const canWritePlanning = canViewPlanning && orgCtx.can_write_planning !== false;
            if (canViewPlanning) {
              siteEditorPopulatePlanningFields(orgCtx.org_name || '', orgCtx.settings || {}, orgCtx.org_id, orgCtx.owner_uuid || '');
            } else {
              siteEditorHidePlanningFields(SITES_T.SITES_BUSINESS_PLANNING_LOCKED || '');
            }
            siteEditorSetEditorAccess(true, canWritePlanning);
          } else {
            siteEditorHidePlanningFields();
            siteEditorSetEditorAccess(true, false);
          }
        }

        siteEditorSetDeleteButtonState(payload.site?.status || getGridStatus(), true);
        siteEditorOpenDialog(modal);
      } catch (error) {
        PW.error(error);
        PC.showToast(SITES_T.SITES_ERROR_LOADING, 'error', 7000, true);
      }
    };

    const openDeleteSiteDialog = (siteId, siteName, siteStatus = 'active', siteOwnerUUID = '') => {
      if (!canWrite()) {
        return;
      }

      const modal = siteEditorGetElement('modal_confirm_delete_site');
      const titleEl = siteEditorGetElement('modal_confirm_delete_site_title');
      const messageEl = siteEditorGetElement('confirm_delete_site_message');
      const confirmBtn = siteEditorGetElement('confirm_delete_site_yes');
      const cancelBtn = siteEditorGetElement('confirm_delete_site_no');
      const ariaEl = siteEditorGetElement('confirm_delete_site_aria');
      if (!(modal instanceof HTMLDialogElement)) {
        return;
      }

      const escapedName = siteName || SITES_T.SITES_THIS_SITE;
      if (siteStatus === 'archived') {
        if (titleEl instanceof HTMLElement) {
          titleEl.textContent = SITES_T.SITES_FINALITY_DELETE_TITLE;
        }
        if (ariaEl instanceof HTMLElement) {
          ariaEl.textContent = SITES_T.SITES_FINALITY_DELETE_ARIA;
        }
        Guardian.setHTML(
          messageEl,
          sitesFormatMessage(SITES_T.SITES_DELETE_PERMANENT_BODY, { name: escapedName }),
        );
        if (confirmBtn instanceof HTMLButtonElement) {
          confirmBtn.textContent = SITES_T.SITES_FINALITY_DELETE_CONFIRM;
          confirmBtn.className = 'btn btn_danger';
        }
      } else {
        if (titleEl instanceof HTMLElement) {
          titleEl.textContent = SITES_T.SITES_CONFIRM_ARCHIVE_TITLE;
        }
        if (ariaEl instanceof HTMLElement) {
          ariaEl.textContent = SITES_T.SITES_CONFIRM_ARCHIVE_ARIA;
        }
        Guardian.setHTML(
          messageEl,
          sitesFormatMessage(SITES_T.SITES_ARCHIVE_CONFIRM_BODY, { name: escapedName }),
        );
        if (confirmBtn instanceof HTMLButtonElement) {
          confirmBtn.textContent = SITES_T.SITES_ARCHIVE_SITE;
          confirmBtn.className = 'btn btn_primary';
        }
      }

      if (cancelBtn instanceof HTMLButtonElement) {
        cancelBtn.textContent = SITES_T.CANCEL;
      }

      siteEditorDeleteSiteId = siteId;
      siteEditorDeleteSiteName = siteName;
      siteEditorDeleteSiteStatus = siteStatus;
      siteEditorDeleteOwnerUUID = siteOwnerUUID;
      siteEditorOpenDialog(modal);
    };

    async function handleSiteEditorCreateSubmit(event) {
      event.preventDefault();
      const form = event.target;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      const nameInput = siteEditorGetElement('site_name_input');
      const wageInput = siteEditorGetElement('site_wage_input');
      if (!siteEditorValidateRequiredSiteFields(nameInput, wageInput, 'create_site_form_status', 'site_name_error', 'site_wage_error')) {
        return;
      }

      const formData = new FormData(form);
      const businessId = String(getBusinessId() || '').trim();
      const endpoint = mode === 'business' && businessId !== ''
        ? `${apiBase}/businesses/${encodeURIComponent(businessId)}/sites/create`
        : `${apiBase}/sites/create`;

      try {
        const response = await fetch(endpoint, {
          method: 'POST',
          credentials: 'include',
          headers: SITE_EDITOR_FORM_HEADERS,
          body: new URLSearchParams(formData),
        });
        const payload = await response.json();
        if (payload?.status !== 'success') {
          throw new Error(payload?.message || SITES_T.SITES_ERROR_CREATING);
        }

          siteEditorCloseDialog(siteEditorGetElement('modal_create_site'));
        PC.showToast(mode === 'business' ? (T.businessSitesCreated || SITES_T.SITES_CREATED_SUCCESS) : SITES_T.SITES_CREATED_SUCCESS, 'save', 5000, true);
        form.reset();
        await reloadGrids();
      } catch (error) {
        PW.error(error);
        siteEditorSetFormStatus('create_site_form_status', PC.resolveThrownMessage(error, SITES_T.SITES_ERROR_CREATE_RETRY));
        PC.showToast(SITES_T.SITES_ERROR_CREATING, 'error', 7000, true);
      }
    }

    async function handleSiteEditorEditSubmit(event) {
      event.preventDefault();

      const form = event.target;
      if (!(form instanceof HTMLFormElement)) {
        return;
      }

      const canWriteSite = form.dataset.canWriteSite !== '0';
      const canWritePlanning = form.dataset.canWritePlanning === '1';
      if (!canWriteSite && !canWritePlanning) {
        return;
      }

      const nameInput = siteEditorGetElement('edit_site_name_input');
      const wageInput = siteEditorGetElement('edit_site_wage_input');
      if (canWriteSite && !siteEditorValidateRequiredSiteFields(nameInput, wageInput, 'edit_site_form_status', 'edit_site_name_error', 'edit_site_wage_error')) {
        return;
      }

      try {
        if (canWriteSite) {
          const formData = new FormData(form);
          const response = await fetch(`${apiBase}/sites/update`, {
            method: 'POST',
            credentials: 'include',
            headers: SITE_EDITOR_FORM_HEADERS,
            body: new URLSearchParams(formData),
          });
          const payload = await response.json();
          if (payload?.status !== 'success') {
            throw new Error(payload?.message || SITES_T.SITES_ERROR_UPDATING);
          }
        }

        const planningEl = siteEditorGetElement('edit_site_org_planning');
        const planOrgId = siteEditorGetElement('edit_site_plan_org_id')?.value || getBusinessId();
        const planOwner = siteEditorGetElement('edit_site_plan_owner_uuid')?.value || siteEditorGetElement('edit_site_owner_uuid')?.value || '';
        const planSiteId = siteEditorGetElement('edit_site_id')?.value || '';
        if (canWritePlanning && planningEl instanceof HTMLElement && !planningEl.hasAttribute('hidden') && planOrgId && planOwner && planSiteId) {
          const planBody = new URLSearchParams({
            budget_amount: siteEditorGetElement('edit_site_plan_budget')?.value || '',
            budget_type: 'annual',
            warn_threshold: siteEditorGetElement('edit_site_plan_warn')?.value || '80',
            critical_threshold: siteEditorGetElement('edit_site_plan_critical')?.value || '95',
            site_status: siteEditorGetElement('edit_site_plan_status_select')?.value || 'active',
            client_name: siteEditorGetElement('edit_site_client_input')?.value || '',
            cost_code: siteEditorGetElement('edit_site_cost_code_input')?.value || '',
            start_date: siteEditorGetElement('edit_site_start_date_input')?.value || '',
            end_date: siteEditorGetElement('edit_site_end_date_input')?.value || '',
          });
          const planResponse = await fetch(
            `${apiBase}/businesses/${encodeURIComponent(planOrgId)}/sites/${encodeURIComponent(planOwner)}/${encodeURIComponent(planSiteId)}/settings/update`,
            { method: 'POST', credentials: 'include', headers: SITE_EDITOR_FORM_HEADERS, body: planBody },
          );
          const planPayload = await planResponse.json();
          if (planPayload?.status !== 'success') {
            throw new Error(planPayload?.message || SITES_T.SITES_ERROR_UPDATING);
          }
        }

        if (!siteEditorColorPickSave) {
          siteEditorCloseDialog(siteEditorGetElement('modal_edit_site'));
        }
        siteEditorColorPickSave = false;
        siteEditorSetFormStatus('edit_site_form_status', '');
        PC.showToast(SITES_T.SITES_UPDATED_SUCCESS, 'save', 5000, true);

        await reloadGrids();
      } catch (error) {
        PW.error(error);
        siteEditorSetFormStatus('edit_site_form_status', PC.resolveThrownMessage(error, SITES_T.SITES_ERROR_UPDATE_RETRY));
        PC.showToast(SITES_T.SITES_ERROR_UPDATING, 'error', 7000, true);
      }
    }

    async function handleSiteEditorConfirmDelete() {
      if (!siteEditorDeleteSiteId || !canWrite()) {
        return;
      }

      const confirmBtn = siteEditorGetElement('confirm_delete_site_yes');
      if (confirmBtn instanceof HTMLButtonElement) {
        confirmBtn.disabled = true;
      }

      try {
        const body = new URLSearchParams({ id: siteEditorDeleteSiteId });
        let url = siteEditorDeleteSiteStatus === 'archived'
          ? `${apiBase}/sites/permanent-delete`
          : `${apiBase}/sites/delete`;

        if (mode === 'business') {
          const businessId = String(getBusinessId() || '').trim();
          const ownerUUID = String(siteEditorDeleteOwnerUUID || '').trim();
          const siteId = String(siteEditorDeleteSiteId || '').trim();
          if (businessId === '' || ownerUUID === '' || siteId === '') {
            throw new Error(SITES_T.SITES_ERROR_DELETING);
          }
          url = siteEditorDeleteSiteStatus === 'archived'
            ? `${apiBase}/businesses/${encodeURIComponent(businessId)}/sites/${encodeURIComponent(ownerUUID)}/${encodeURIComponent(siteId)}/permanent-delete`
            : `${apiBase}/businesses/${encodeURIComponent(businessId)}/sites/${encodeURIComponent(ownerUUID)}/${encodeURIComponent(siteId)}/archive`;
        } else if (siteEditorDeleteOwnerUUID) {
          body.set('owner_uuid', siteEditorDeleteOwnerUUID);
        }

        const response = await fetch(url, {
          method: 'POST',
          credentials: 'include',
          headers: SITE_EDITOR_FORM_HEADERS,
          body,
        });
        const payload = await response.json();
        if (payload?.status !== 'success') {
          throw new Error(payload?.message || SITES_T.SITES_ERROR_DELETING);
        }

        siteEditorCloseDialog(siteEditorGetElement('modal_confirm_delete_site'));
        PC.showToast(siteEditorDeleteSiteStatus === 'archived' ? SITES_T.SITES_PERMANENTLY_DELETED_SHORT : SITES_T.SITES_ARCHIVED_SHORT, 'save', 5000, true);
        await reloadGrids();
      } catch (error) {
        PW.error(error);
        PC.showToast(SITES_T.SITES_ERROR_DELETING, 'error', 7000, true);
        if (confirmBtn instanceof HTMLButtonElement) {
          confirmBtn.disabled = false;
        }
      }
    }

    async function handleSiteEditorUnlinkFromBusiness() {
      const businessId = String(getBusinessId() || '').trim();
      const siteOwnerUUID = siteEditorGetElement('edit_site_owner_uuid')?.value || '';
      const siteId = siteEditorGetElement('edit_site_id')?.value || '';
      if (!canWrite() || businessId === '' || siteOwnerUUID === '' || siteId === '') {
        return;
      }

      const siteName = siteEditorGetElement('edit_site_name_input')?.value || SITES_T.SITES_THIS_SITE;
      const unlinkLabel = T.businessSitesUnlink || SITES_T.BUSINESS_SITES_UNLINK || 'Unlink';
      const confirmed = await siteEditorConfirmAction({
        title: `${unlinkLabel} site?`,
        message: sitesFormatMessage(SITES_T.BUSINESS_SITES_UNLINK_CONFIRM_NAMED, { name: siteName }),
        confirmText: unlinkLabel,
        confirmClass: 'btn btn_primary',
      });
      if (!confirmed) {
        return;
      }

      await postForm(
        `${apiBase}/businesses/${encodeURIComponent(businessId)}/sites/${encodeURIComponent(siteOwnerUUID)}/${encodeURIComponent(siteId)}/unlink`,
        {},
      );
      siteEditorCloseDialog(siteEditorGetElement('modal_edit_site'));
      PC.showToast(T.businessSitesUnlinked || SITES_T.BUSINESS_SITES_UNLINKED, 'save', 5000, true);
      await reloadGrids();
    }

    const handleBusinessSiteGridClick = (event, container) => {
      if (!(container instanceof HTMLElement)) {
        return;
      }

      const createBtn = event.target.closest('[data-action="create-business-site"], [data-action="create-site"]');
      if (createBtn instanceof HTMLElement && container.contains(createBtn)) {
        event.preventDefault();
        openCreateSiteDialog(getGridStatus());
        return;
      }

      const actionBtn = event.target.closest('.datagrid_action');
      if (actionBtn instanceof HTMLElement && container.contains(actionBtn)) {
        event.preventDefault();
        const action = String(actionBtn.dataset.action || '');
        const row = actionBtn.closest('.datagrid_row');
        const rowId = row instanceof HTMLElement ? String(row.dataset.id || actionBtn.dataset.id || '').trim() : '';
        const siteName = siteEditorResolveGridSiteName(row);

        if ((action === 'archive-site' || action === 'delete') && rowId !== '') {
          const ownerUUID = mode === 'business' ? String(rowId.split(':')[0] || '') : '';
          const siteId = mode === 'business' ? String(rowId.split(':')[1] || rowId) : rowId;
          openDeleteSiteDialog(siteId, siteName, 'active', ownerUUID);
        }
        if (action === 'restore-site' && rowId !== '' && mode === 'business') {
          const businessId = String(getBusinessId() || '').trim();
          const ownerUUID = String(rowId.split(':')[0] || '');
          const siteId = String(rowId.split(':')[1] || rowId);
          if (businessId !== '' && ownerUUID !== '' && siteId !== '') {
            const message = String(SITES_T.SITES_RESTORE_CONFIRM || 'Restore "{name}"?').replace('{name}', siteName || SITES_T.SITES_THIS_SITE);
            const restoreLabel = SITES_T.SITES_RESTORE_ACTION || 'Restore';
            siteEditorConfirmAction({
              title: `${restoreLabel} site?`,
              message,
              confirmText: restoreLabel,
              confirmClass: 'btn btn_primary',
            }).then((confirmed) => {
              if (!confirmed) {
                return;
              }
              fetch(
                `${apiBase}/businesses/${encodeURIComponent(businessId)}/sites/${encodeURIComponent(ownerUUID)}/${encodeURIComponent(siteId)}/restore`,
                { method: 'POST', credentials: 'include', headers: SITE_EDITOR_FORM_HEADERS, body: new URLSearchParams() },
              )
                .then((response) => response.json())
                .then(async (payload) => {
                  if (payload?.status !== 'success') {
                    throw new Error(payload?.message || SITES_T.SITES_RESTORE_FAILED);
                  }
                  PC.showToast(SITES_T.SITES_RESTORED || 'Site restored.', 'save', 5000, true);
                  await reloadGrids();
                })
                .catch((error) => {
                  PW.error(error);
                  PC.showToast(SITES_T.SITES_RESTORE_FAILED || 'Unable to restore site.', 'error', 7000, true);
                });
            });
          }
        }
        return;
      }

      if (event.target.closest('.datagrid_action, .datagrid_sort, .datagrid_search, .datagrid_pager, .datagrid_pagination, button, a, input, select, textarea, label')) {
        return;
      }

      const row = event.target.closest('.datagrid_row');
      if (!(row instanceof HTMLElement) || !container.contains(row)) {
        return;
      }

      const rowId = String(row.dataset.id || '').trim();
      if (rowId === '') {
        return;
      }

      container.querySelectorAll('.datagrid_row').forEach((candidate) => candidate.classList.remove('is-selected'));
      row.classList.add('is-selected');
      openEditSiteDialog(rowId).catch((error) => PW.error(error));
    };

    return {
      openCreateSiteDialog,
      openEditSiteDialog,
      openDeleteSiteDialog,
      confirmAction: siteEditorConfirmAction,
      handleBusinessSiteGridClick,
    };
  };
