<?php namespace PayCal\Domain; ?>
  const EDITOR_SENSITIVE_FIELD_IDS = [
    'businesses_editor_type',
    'businesses_editor_role',
    'businesses_editor_status',
  ];

  const CONTACT_AVATAR_PLACEHOLDER_SRC = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22128%22 height=%22128%22 viewBox=%220 0 128 128%22%3E%3Cdefs%3E%3ClinearGradient id=%22g%22 x1=%220%22 y1=%220%22 x2=%221%22 y2=%221%22%3E%3Cstop offset=%220%25%22 stop-color=%22%23343a46%22/%3E%3Cstop offset=%22100%25%22 stop-color=%22%23262c36%22/%3E%3C/linearGradient%3E%3C/defs%3E%3Crect width=%22128%22 height=%22128%22 rx=%2264%22 fill=%22url(%23g)%22/%3E%3Ccircle cx=%2264%22 cy=%2248%22 r=%2222%22 fill=%22%238a95a8%22/%3E%3Cpath d=%22M24 110c4-21 20-33 40-33s36 12 40 33%22 fill=%22%238a95a8%22/%3E%3C/svg%3E';

  const normalizeContactImageDataUrl = (rawValue) => {
    const value = String(rawValue || '').trim();
    const match = value.match(/^data:image\/(png|jpe?g|webp|gif);base64,([a-z0-9+/=\s]+)$/i);
    if (!match) {
      return '';
    }

    const mime = String(match[1] || '').toLowerCase();
    const payload = String(match[2] || '').replace(/\s+/g, '');
    if (payload.length < 256 || payload.length > 19000) {
      return '';
    }

    // Quick decode sanity check to reject malformed values that still match regex.
    try {
      atob(payload);
    } catch (_error) {
      return '';
    }

    return `data:image/${mime};base64,${payload}`;
  };

  const getContactAvatarPreviewSrc = (rawValue) => {
    const normalized = normalizeContactImageDataUrl(rawValue);
    return normalized === '' ? CONTACT_AVATAR_PLACEHOLDER_SRC : normalized;
  };

  const elements = {
    searchForm: document.getElementById('businesses_search_form'),
    searchInput: document.getElementById('businesses_discovery_query'),
    requestJoinForm: document.getElementById('businesses_request_join_form'),
    requestOrgName: document.getElementById('businesses_request_org_name'),
    requestEmail: document.getElementById('businesses_request_email'),
    requestLookupDatalist: document.getElementById('businesses_access_lookup_request'),
    discoveryPanelStatus: document.getElementById('businesses_discovery_panel_status'),
    browserSearchForm: document.getElementById('businesses_browser_search_form'),
    browserSearchInput: document.getElementById('businesses_browser_search_input'),
    browserGrid: document.getElementById('businesses-browser-grid'),
    browserPanelStatus: document.getElementById('businesses_browser_panel_status'),
    browserGridStatus: document.getElementById('businesses_browser_grid_sr_status'),
    currentPanel: document.getElementById('businesses_current_panel'),
    currentSummary: document.getElementById('businesses_current_summary'),
    currentMeta: document.getElementById('businesses_current_meta'),
    currentStatus: document.getElementById('businesses_current_status'),
    currentInfoLink: document.getElementById('businesses_current_info_link'),
    currentRevokeButton: document.getElementById('businesses_current_revoke_button'),
    currentDetailsDialog: document.getElementById('businesses_current_details_dialog'),
    currentDetailsBody: document.getElementById('businesses_current_details_body'),
    membershipConsentDialog: document.getElementById('businesses_membership_consent_dialog'),
    membershipConsentForm: document.getElementById('businesses_membership_consent_form'),
    membershipConsentClose: document.getElementById('businesses_membership_consent_close'),
    membershipConsentCancel: document.getElementById('businesses_membership_consent_cancel'),
    membershipConsentAction: document.getElementById('businesses_membership_consent_action'),
    membershipConsentDisclaimer: document.getElementById('businesses_membership_consent_disclaimer'),
    membershipConsentAcknowledge: document.getElementById('businesses_membership_consent_ack'),
    membershipConsentError: document.getElementById('businesses_membership_consent_error'),
    freeAuditPanel: document.getElementById('businesses_free_audit_panel'),

    freeAuditStatus: document.getElementById('businesses_free_audit_sr_status'),
    freeAuditGridContainer: document.getElementById('businesses-free-audit-grid-host'),
    personalForm: document.getElementById('businesses_personal_form'),
    personalOrgId: document.getElementById('businesses_personal_org_id'),
    personalName: document.getElementById('businesses_personal_name'),
    personalPayFrequency: document.getElementById('businesses_personal_pay_frequency'),
    personalPayAnchor: document.getElementById('businesses_personal_pay_anchor'),
    personalPayPeriodStart: document.getElementById('businesses_personal_pay_period_start'),
    personalPayPeriodLength: document.getElementById('businesses_personal_pay_period_length'),
    personalPayPeriodWarning: document.getElementById('businesses_personal_payperiod_warning'),
    personalEditingGraceDays: document.getElementById('businesses_personal_editing_grace_days'),
    personalEditingGraceDayRadios: document.querySelectorAll('input[name="businesses_personal_editing_grace_days"]'),
    personalDefaultWage: document.getElementById('businesses_personal_default_wage'),
    personalTimezone: document.getElementById('businesses_personal_timezone'),
    personalTimezoneSearch: document.getElementById('businesses_personal_timezone_search'),
    personalCurrency: document.getElementById('businesses_personal_currency'),
    personalCurrencySearch: document.getElementById('businesses_personal_currency_search'),
    personalLanguage: document.getElementById('businesses_personal_language'),
    personalLocale: document.getElementById('businesses_personal_locale'),
    personalI18nPreview: document.getElementById('businesses_i18n_preview'),
    personalPreview: document.getElementById('businesses_personal_preview'),
    accountActivityStatus: document.getElementById('account_activity_status'),
    accountActivityPanel: document.getElementById('panel-account-activity'),
    accountActivityLoginDetails: document.getElementById('account_activity_login_details'),
    accountActivityBrowserDetails: document.getElementById('account_activity_browser_details'),
    accountActivitySessions: document.getElementById('account_activity_sessions'),
    csrfToken: document.getElementById('businesses_csrf_token'),
    gridContainer: document.getElementById('businesses-grid'),
    gridBody: document.getElementById('businesses-grid')?.querySelector('.datagrid_body'),
    gridPanel: document.getElementById('businesses-grid-panel'),
    gridStatus: document.getElementById('businesses_grid_sr_status'),
    execSummaryHeading: document.getElementById('businesses_exec_summary_heading'),
    execSummaryLede: document.getElementById('businesses_exec_summary_lede'),
    execSummaryRole: document.getElementById('businesses_exec_role'),
    execSummaryStatus: document.getElementById('businesses_exec_status'),
    execSummaryIndustry: document.getElementById('businesses_exec_industry'),
    execSummaryPending: document.getElementById('businesses_exec_pending'),
    execSummaryNotices: document.getElementById('businesses_exec_notices'),
    editorInlineMount: document.getElementById('businesses_editor_inline_mount'),
    organizationStatus: document.getElementById('businesses_organization_status'),
    payrollSaveButton: document.getElementById('businesses_payroll_save'),
    payrollStatus: document.getElementById('businesses_payroll_status'),
    createButton: document.getElementById('businesses_create_button'),
    definitionsHelpButton: document.getElementById('businesses_definitions_help_button'),
    definitionsDialog: document.getElementById('businesses_definitions_dialog'),
    definitionsCloseButton: document.getElementById('businesses_definitions_close'),
    createDialog: document.getElementById('businesses_create_dialog'),
    createForm: document.getElementById('businesses_create_form'),
    createName: document.getElementById('businesses_create_name'),
    createNameError: document.getElementById('businesses_create_name_error'),
    createStatus: document.getElementById('businesses_create_status'),
    createSubmit: document.getElementById('businesses_create_submit'),
    dialog: document.getElementById('businesses_editor_dialog'),
    closeButton: document.getElementById('businesses_close_button'),
    bootstrapDekButton: document.getElementById('businesses_bootstrap_dek_button'),
    saveButton: document.getElementById('businesses_save_button'),
    title: document.getElementById('businesses_editor_title'),
    subtitle: document.getElementById('businesses_editor_subtitle'),
    premiumNotice: document.getElementById('businesses_editor_premium_notice'),
    orgId: document.getElementById('businesses_editor_org_id'),
    name: document.getElementById('businesses_editor_name'),
    type: document.getElementById('businesses_editor_type'),
    role: document.getElementById('businesses_editor_role'),
    status: document.getElementById('businesses_editor_status'),
    payFrequency: document.getElementById('businesses_editor_pay_frequency'),
    payAnchor: document.getElementById('businesses_editor_pay_anchor'),
    payPeriodStart: document.getElementById('businesses_editor_pay_period_start'),
    payPeriodLength: document.getElementById('businesses_editor_pay_period_length'),
    editingGraceDays: document.getElementById('businesses_editor_editing_grace_days'),
    editorEditingGraceDayRadios: document.querySelectorAll('input[name="businesses_editor_editing_grace_days"]'),
    payPeriodGridStatus: document.getElementById('businesses_editor_payperiod_sr_status'),
    defaultWage: document.getElementById('businesses_editor_default_wage'),
    timezone: document.getElementById('businesses_editor_timezone'),
    timezoneSearch: document.getElementById('businesses_editor_timezone_search'),
    currency: document.getElementById('businesses_editor_currency'),
    currencySearch: document.getElementById('businesses_editor_currency_search'),
    preview: document.getElementById('businesses_editor_preview'),
    ownerSummary: document.getElementById('businesses_owner_summary'),
    auditControlTestPanel: document.getElementById('businesses_audit_control_test_panel'),
    auditControlTestSummary: document.getElementById('businesses_audit_control_test_summary'),
    auditControlTestButton: document.getElementById('businesses_audit_control_test_button'),
    auditControlTestStatus: document.getElementById('businesses_audit_control_test_status'),
    customCardsContainer: document.getElementById('businesses_contact_directory_custom_cards'),
    customCardsJson: document.getElementById('businesses_editor_contact_custom_json'),
    contactImagePopover: document.getElementById('businesses_contact_image_popover'),
    contactImageDropzone: document.getElementById('businesses_contact_image_dropzone'),
    contactImageFile: document.getElementById('businesses_contact_image_file'),
    contactImageClear: document.getElementById('businesses_contact_image_clear'),
    contactImageCancel: document.getElementById('businesses_contact_image_cancel'),
    inviteEmail: document.getElementById('businesses_invite_email'),
    inviteSend: document.getElementById('businesses_invite_send'),
    invitesReload: document.getElementById('businesses_invites_reload'),
    scopeGrid: document.getElementById('businesses_scope_grid'),
    scopeStatus: document.getElementById('businesses_scope_sr_status'),
    invitesStatus: document.getElementById('businesses_invites_sr_status'),
    invitesList: document.getElementById('businesses_invites_list'),
    membersInvitesList: document.getElementById('businesses_members_invites_list'),
    membersInviteHistoryGridContainer: document.getElementById('businesses-invite-history-grid-host'),
    accessRequestsStatus: document.getElementById('businesses_access_requests_sr_status'),
    accessRequestsList: document.getElementById('businesses_access_requests_list'),
    liveRequestsList: document.getElementById('businesses_live_requests_list'),
    liveRequestsStatus: document.getElementById('businesses_live_requests_sr_status'),
    membersRoleFilter: document.getElementById('businesses_members_role_filter'),
    membersGridContainer: document.getElementById('businesses-members-grid'),
    sitesGridContainer: document.getElementById('businesses-sites-grid'),
    membersGridStatus: document.getElementById('businesses_members_grid_sr_status'),
    membersImportEmails: document.getElementById('businesses_members_import_emails'),
    membersImportPrepare: document.getElementById('businesses_members_import_prepare'),
    membersImportSendCode: document.getElementById('businesses_members_import_send_code'),
    membersImportCode: document.getElementById('businesses_members_import_code'),
    membersImportVerify: document.getElementById('businesses_members_import_verify'),
    membersImportCommit: document.getElementById('businesses_members_import_commit'),
    membersImportSummary: document.getElementById('businesses_members_import_summary'),
    membersImportStatus: document.getElementById('businesses_members_import_status'),
    discoveryRun: document.getElementById('businesses_discovery_run'),
    discoveryStatus: document.getElementById('businesses_discovery_sr_status'),
    discoveryResults: document.getElementById('businesses_discovery_results'),
    businessSitesGridContainer: document.getElementById('businesses-sites-grid'),
    businessSitesStatus: document.getElementById('businesses_sites_sr_status'),
    relationshipsReload: document.getElementById('businesses_relationships_reload'),
    relationshipsList: document.getElementById('businesses_relationships_list'),
    relationshipsStatus: document.getElementById('businesses_relationships_sr_status'),
    transferTarget: document.getElementById('businesses_transfer_target'),
    transferTargetList: document.getElementById('businesses_transfer_target_list'),
    transferTargetUUID: document.getElementById('businesses_transfer_target_uuid'),
    transferSelectedMember: document.getElementById('businesses_transfer_selected_member'),
    transferConfirmationContainer: document.getElementById('businesses_transfer_confirmation_container'),
    transferConfirmation: document.getElementById('businesses_transfer_confirmation'),
    transferConfirmationStatus: document.getElementById('businesses_transfer_confirmation_status'),
    transferButton: document.getElementById('businesses_transfer_button'),
    transferNotice: document.getElementById('businesses_transfer_notice'),
    leaveButton: document.getElementById('businesses_leave_button'),
    auditReload: document.getElementById('businesses_audit_reload'),
    auditStatus: document.getElementById('businesses_audit_sr_status'),
    auditGridContainer: document.getElementById('businesses-audit-grid-host'),
    contextHeaderName: document.getElementById('business_context_name'),
    contextHeaderMembers: document.getElementById('business_context_members'),
    contextHeaderSites: document.getElementById('business_context_sites'),
    contextHeaderPending: document.getElementById('business_context_pending'),
    liveToast: document.getElementById('businesses_live_toast'),
    dialogLiveToast: document.getElementById('businesses_dialog_live_toast'),
  };

  let liveToastTimerId = null;
  const showBusinessesToast = (message, type = 'save', durationMs = 2600, sticky = true) => {
    const text = String(message || '').trim();
    if (text === '') {
      return;
    }

    if (document.getElementById('status') instanceof HTMLElement) {
      PC.showToast(text, type, durationMs, sticky);
      return;
    }

    const toastTarget = (elements.dialog instanceof HTMLDialogElement
      && elements.dialog.open
      && elements.dialogLiveToast instanceof HTMLElement)
      ? elements.dialogLiveToast
      : elements.liveToast;

    if (!(toastTarget instanceof HTMLElement)) {
      return;
    }

    const toneClass = type === 'error'
      ? 'businesses_live_toast_error'
      : 'businesses_live_toast_save';

    toastTarget.classList.remove('businesses_live_toast_error', 'businesses_live_toast_save');
    toastTarget.classList.add(toneClass);
    toastTarget.textContent = text;
    toastTarget.classList.add('businesses_live_toast_show', 'businesses_live_toast_visible');
    if (liveToastTimerId !== null) {
      window.clearTimeout(liveToastTimerId);
    }

    liveToastTimerId = window.setTimeout(() => {
      if (elements.liveToast instanceof HTMLElement) {
        elements.liveToast.classList.remove('businesses_live_toast_show', 'businesses_live_toast_visible');
      }
      if (elements.dialogLiveToast instanceof HTMLElement) {
        elements.dialogLiveToast.classList.remove('businesses_live_toast_show', 'businesses_live_toast_visible');
      }
      liveToastTimerId = null;
    }, Math.max(1200, durationMs));
  };

  const EDITOR_META_FIELD_MAP = {
    businesses_editor_legal_name: 'legal_name',
    businesses_editor_industry: 'industry',
    businesses_editor_registration_number: 'registration_number',
    businesses_editor_tax_id: 'tax_id',
    businesses_editor_employee_count: 'employee_count',
    businesses_editor_founded_year: 'founded_year',
    businesses_editor_contact_email: 'contact_email',
    businesses_editor_contact_phone: 'contact_phone',
    businesses_editor_website: 'website',
    businesses_editor_address_line1: 'address_line1',
    businesses_editor_address_line2: 'address_line2',
    businesses_editor_address_city: 'address_city',
    businesses_editor_address_region: 'address_region',
    businesses_editor_address_postal: 'address_postal',
    businesses_editor_address_country: 'address_country',
    businesses_editor_support_hours: 'support_hours',
    businesses_editor_org_notes: 'org_notes',
    businesses_editor_contact_payroll_name: 'contact_payroll_name',
    businesses_editor_contact_payroll_image_url: 'contact_payroll_image_url',
    businesses_editor_contact_payroll_email: 'contact_payroll_email',
    businesses_editor_contact_payroll_phone: 'contact_payroll_phone',
    businesses_editor_contact_payroll_role: 'contact_payroll_role',
    businesses_editor_contact_hr_name: 'contact_hr_name',
    businesses_editor_contact_hr_image_url: 'contact_hr_image_url',
    businesses_editor_contact_hr_email: 'contact_hr_email',
    businesses_editor_contact_hr_phone: 'contact_hr_phone',
    businesses_editor_contact_hr_role: 'contact_hr_role',
    businesses_editor_contact_ceo_name: 'contact_ceo_name',
    businesses_editor_contact_ceo_image_url: 'contact_ceo_image_url',
    businesses_editor_contact_ceo_email: 'contact_ceo_email',
    businesses_editor_contact_ceo_phone: 'contact_ceo_phone',
    businesses_editor_contact_ceo_role: 'contact_ceo_role',
    businesses_editor_contact_coo_name: 'contact_coo_name',
    businesses_editor_contact_coo_image_url: 'contact_coo_image_url',
    businesses_editor_contact_coo_email: 'contact_coo_email',
    businesses_editor_contact_coo_phone: 'contact_coo_phone',
    businesses_editor_contact_coo_role: 'contact_coo_role',
    businesses_editor_contact_cto_name: 'contact_cto_name',
    businesses_editor_contact_cto_image_url: 'contact_cto_image_url',
    businesses_editor_contact_cto_email: 'contact_cto_email',
    businesses_editor_contact_cto_phone: 'contact_cto_phone',
    businesses_editor_contact_cto_role: 'contact_cto_role',
    businesses_editor_contact_custom_json: 'contact_custom_json',
  };

  const escapeHtml = (value) => String(value || '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');

  const uid = () => `cc_${Date.now().toString(36)}_${Math.random().toString(36).slice(2, 8)}`;

  const normalizeCustomContactCards = (rawValue) => {
    let parsed = [];
    try {
      const maybe = JSON.parse(String(rawValue || '[]'));
      if (Array.isArray(maybe)) {
        parsed = maybe;
      }
    } catch {
      parsed = [];
    }

    return parsed
      .filter((item) => item && typeof item === 'object')
      .map((item) => ({
        id: String(item.id || uid()),
        name: String(item.name || ''),
        email: String(item.email || ''),
        phone: typeof PC.formatPhoneNumberValue === 'function'
          ? PC.formatPhoneNumberValue(String(item.phone || ''))
          : String(item.phone || ''),
        role: String(item.role || ''),
        image_url: String(item.image_url || ''),
      }));
  };

  const syncCustomCardsHiddenInput = () => {
    if (!(elements.customCardsJson instanceof HTMLInputElement)) {
      return;
    }
    elements.customCardsJson.value = JSON.stringify(state.customContactCards);
  };

  const renderCustomContactCards = () => {
    if (!(elements.customCardsContainer instanceof HTMLElement)) {
      return;
    }

    if (state.customContactCards.length === 0) {
      elements.customCardsContainer.textContent = '';
      return;
    }

    const markup = state.customContactCards.map((card) => {
      const previewId = `businesses_editor_contact_custom_${card.id}_avatar_preview`;
      const imageFieldId = `businesses_editor_contact_custom_${card.id}_image_url`;
      const previewSrc = getContactAvatarPreviewSrc(card.image_url);
      return `
        <div class="businesses_contact_card businesses_contact_card_custom" data-custom-card-id="${escapeHtml(card.id)}">
          <button type="button" class="businesses_contact_card_avatar_button" aria-haspopup="dialog" aria-controls="businesses_contact_image_popover" aria-expanded="false" aria-label="<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_IMAGE_POPOVER_ARIA')); ?>">
            <img id="${escapeHtml(previewId)}" class="businesses_contact_card_avatar" src="${escapeHtml(previewSrc)}" alt="" role="presentation" loading="lazy">
          </button>
          <input id="${escapeHtml(imageFieldId)}" class="businesses_contact_image_input" data-custom-field="image_url" data-custom-card-id="${escapeHtml(card.id)}" data-preview-id="${escapeHtml(previewId)}" type="hidden" maxlength="20000" value="${escapeHtml(card.image_url)}">
          <input class="businesses_contact_custom_input businesses_contact_role_input" data-custom-field="role" data-custom-card-id="${escapeHtml(card.id)}" type="text" maxlength="80" placeholder="<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_ROLE_PH')); ?>" value="${escapeHtml(card.role)}">
          <input class="businesses_contact_custom_input businesses_contact_body_input" name="name" autocomplete="name" data-custom-field="name" data-custom-card-id="${escapeHtml(card.id)}" type="text" maxlength="100" placeholder="<?php echo addslashes(org_js_index_i18n('NAME')); ?>" value="${escapeHtml(card.name)}">
          <input class="businesses_contact_custom_input businesses_contact_body_input" name="email" autocomplete="email" data-custom-field="email" data-custom-card-id="${escapeHtml(card.id)}" type="email" maxlength="160" placeholder="<?php echo addslashes(org_js_index_i18n('EMAIL')); ?>" value="${escapeHtml(card.email)}">
          <input class="businesses_contact_custom_input businesses_contact_body_input" name="phone" autocomplete="tel" data-custom-field="phone" data-custom-card-id="${escapeHtml(card.id)}" type="tel" inputmode="numeric" maxlength="14" pattern="\([0-9]{3}\) [0-9]{3}-[0-9]{4}" placeholder="<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_PHONE_PLACEHOLDER')); ?>" value="${escapeHtml(typeof PC.formatPhoneNumberValue === 'function' ? PC.formatPhoneNumberValue(card.phone) : card.phone)}">
          <div class="businesses_contact_card_menu">
            <button type="button" class="btn btn_secondary businesses_contact_card_menu_toggle" aria-haspopup="menu" aria-expanded="false" aria-label="<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_ACTIONS_ARIA')); ?>">...</button>
            <button type="button" class="btn btn_secondary businesses_contact_card_menu_delete" data-card-type="custom" data-custom-card-id="${escapeHtml(card.id)}" data-confirming="false" hidden><?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CLEAR')); ?></button>
          </div>
        </div>
      `;
    }).join('');

    Guardian.setHTML(elements.customCardsContainer, markup);
    applyContactInputAriaLabels(elements.customCardsContainer);
  };

  const applyContactInputAriaLabels = (root = document) => {
    if (!(root instanceof Document || root instanceof HTMLElement)) {
      return;
    }

    const resolveLabel = (input) => {
      if (!(input instanceof HTMLInputElement)) {
        return '';
      }
      if (input.classList.contains('businesses_contact_role_input')) {
        return T.contactRoleLabel;
      }
      if (input.name === 'name') {
        return T.contactNameLabel;
      }
      if (input.name === 'email') {
        return T.contactEmailLabel;
      }
      if (input.name === 'phone') {
        return T.contactPhoneLabel;
      }
      return '';
    };

    root.querySelectorAll('.businesses_contact_body_input, .businesses_contact_role_input').forEach((field) => {
      if (!(field instanceof HTMLInputElement)) {
        return;
      }
      const label = resolveLabel(field);
      if (label !== '') {
        field.setAttribute('aria-label', label);
      }
    });
  };

  const upsertCustomCardField = (cardId, fieldName, fieldValue) => {
    const idx = state.customContactCards.findIndex((card) => card.id === cardId);
    if (idx === -1) {
      return;
    }
    state.customContactCards[idx][fieldName] = String(fieldValue || '');
    syncCustomCardsHiddenInput();
  };

  const contactDeleteTimers = new WeakMap();
  let organizationContactPanelEventsBound = false;

  const resetContactCardDeleteButton = (button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    const timerId = contactDeleteTimers.get(button);
    if (typeof timerId === 'number') {
      window.clearTimeout(timerId);
      contactDeleteTimers.delete(button);
    }

    button.dataset.confirming = 'false';
    button.classList.remove('is_confirming');
    button.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CLEAR')); ?>';
  };

  const setContactCardMenuOpen = (menu, isOpen) => {
    if (!(menu instanceof HTMLElement)) {
      return;
    }

    const toggle = menu.querySelector('.businesses_contact_card_menu_toggle');
    const deleteButton = menu.querySelector('.businesses_contact_card_menu_delete');
    if (toggle instanceof HTMLButtonElement) {
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
    if (deleteButton instanceof HTMLButtonElement) {
      deleteButton.hidden = !isOpen;
      if (!isOpen) {
        resetContactCardDeleteButton(deleteButton);
      }
    }
    menu.classList.toggle('is_open', isOpen);
  };

  const closeAllContactCardMenus = (exceptMenu = null) => {
    document.querySelectorAll('.businesses_contact_card_menu.is_open').forEach((menu) => {
      if (!(menu instanceof HTMLElement)) {
        return;
      }
      if (exceptMenu instanceof HTMLElement && menu === exceptMenu) {
        return;
      }
      setContactCardMenuOpen(menu, false);
    });
  };

  const armContactCardDeleteButton = (button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    button.dataset.confirming = 'true';
    button.classList.add('is_confirming');
    button.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CONFIRM_CLEAR')); ?>';

    const timerId = window.setTimeout(() => {
      resetContactCardDeleteButton(button);
    }, 3800);
    contactDeleteTimers.set(button, timerId);
  };

  const clearFixedContactCard = (card) => {
    if (!(card instanceof HTMLElement)) {
      return false;
    }

    let changed = false;
    card.querySelectorAll('input').forEach((input) => {
      if (!(input instanceof HTMLInputElement)) {
        return;
      }

      if (input.type === 'hidden') {
        if (input.classList.contains('businesses_contact_image_input') && String(input.value || '') !== '') {
          input.value = '';
          syncContactAvatarPreview(input);
          changed = true;
        }
        return;
      }

      if (String(input.value || '') !== '') {
        input.value = '';
        changed = true;
      }
    });

    return changed;
  };

  const clearCustomContactCard = (cardId) => {
    const normalizedId = String(cardId || '').trim();
    if (normalizedId === '') {
      return false;
    }

    const idx = state.customContactCards.findIndex((card) => card.id === normalizedId);
    if (idx === -1) {
      return false;
    }

    const card = state.customContactCards[idx];
    let changed = false;
    ['name', 'email', 'phone', 'role', 'image_url'].forEach((fieldName) => {
      if (String(card[fieldName] || '') !== '') {
        card[fieldName] = '';
        changed = true;
      }
    });

    if (changed) {
      syncCustomCardsHiddenInput();
      renderCustomContactCards();
    }

    return changed;
  };

  const handleContactCardDeleteAction = (button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    if (button.dataset.confirming !== 'true') {
      armContactCardDeleteButton(button);
      return;
    }

    resetContactCardDeleteButton(button);

    const cardType = String(button.dataset.cardType || 'fixed').trim();
    if (cardType === 'custom') {
      const cardId = String(button.dataset.customCardId || '').trim();
      if (cardId === '') {
        return;
      }

      const changed = clearCustomContactCard(cardId);
      scheduleEditorAutoSave(220, 'custom-contact-clear');
      showBusinessesToast(
        changed
          ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CLEARED')); ?>'
          : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_ALREADY_EMPTY')); ?>',
        'save',
        2200,
        true,
      );
      closeAllContactCardMenus();
      return;
    }

    const card = button.closest('.businesses_contact_card');
    if (!(card instanceof HTMLElement)) {
      return;
    }

    const changed = clearFixedContactCard(card);
    scheduleEditorAutoSave(220, 'fixed-contact-clear');
    showBusinessesToast(
      changed
        ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CLEARED')); ?>'
        : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_ALREADY_EMPTY')); ?>',
      'save',
      2200,
      true,
    );
    const menu = button.closest('.businesses_contact_card_menu');
    if (menu instanceof HTMLElement) {
      setContactCardMenuOpen(menu, false);
    }
  };

  const closeContactImagePopover = ({ restoreFocus = false } = {}) => {
    if (elements.contactImagePopover instanceof HTMLElement) {
      elements.contactImagePopover.classList.add('hidden');
      elements.contactImagePopover.hidden = true;
      elements.contactImagePopover.style.removeProperty('--contact-popover-top');
      elements.contactImagePopover.style.removeProperty('--contact-popover-left');
    }

    const trigger = state.contactImagePopoverTrigger;
    if (trigger instanceof HTMLElement) {
      trigger.setAttribute('aria-expanded', 'false');
    }

    state.contactImagePopoverTargetFieldId = '';
    state.contactImagePopoverTrigger = null;

    if (restoreFocus && trigger instanceof HTMLElement && typeof trigger.focus === 'function') {
      trigger.focus();
    }
  };

  const isContactImagePopoverOpen = () => {
    return elements.contactImagePopover instanceof HTMLElement
      && !elements.contactImagePopover.classList.contains('hidden');
  };

  const getContactImagePopoverFocusable = () => {
    if (!(elements.contactImagePopover instanceof HTMLElement)) {
      return [];
    }

    const selector = [
      'a[href]',
      'button:not([disabled])',
      'input:not([disabled]):not([type="hidden"])',
      '[tabindex]:not([tabindex="-1"])',
    ].join(',');

    return Array.from(elements.contactImagePopover.querySelectorAll(selector))
      .filter((el) => el instanceof HTMLElement && (el.offsetParent !== null || el === document.activeElement));
  };

  const trapContactImagePopoverFocus = (event) => {
    if (event.key !== 'Tab' || !(elements.contactImagePopover instanceof HTMLElement)) {
      return false;
    }

    const focusableElements = getContactImagePopoverFocusable();
    if (focusableElements.length === 0) {
      event.preventDefault();
      return true;
    }

    const first = focusableElements[0];
    const last = focusableElements[focusableElements.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && (active === first || !elements.contactImagePopover.contains(active))) {
      event.preventDefault();
      last.focus();
      return true;
    }

    if (!event.shiftKey && (active === last || !elements.contactImagePopover.contains(active))) {
      event.preventDefault();
      first.focus();
      return true;
    }

    return false;
  };

  const syncOrganizationContactElementRefs = () => {
    elements.customCardsContainer = document.getElementById('businesses_contact_directory_custom_cards');
    elements.customCardsJson = document.getElementById('businesses_editor_contact_custom_json');
    elements.contactImagePopover = document.getElementById('businesses_contact_image_popover');
    elements.contactImageDropzone = document.getElementById('businesses_contact_image_dropzone');
    elements.contactImageFile = document.getElementById('businesses_contact_image_file');
    elements.contactImageClear = document.getElementById('businesses_contact_image_clear');
    elements.contactImageCancel = document.getElementById('businesses_contact_image_cancel');
  };

  const mountContactImagePopover = () => {
    syncOrganizationContactElementRefs();
    if (!(elements.contactImagePopover instanceof HTMLElement)) {
      return;
    }

    if (elements.contactImagePopover.parentElement !== document.body) {
      document.body.appendChild(elements.contactImagePopover);
    }

    if (elements.contactImagePopover.classList.contains('hidden')) {
      elements.contactImagePopover.hidden = true;
    }
  };

  const openContactImagePopover = (targetField, anchorElement) => {
    mountContactImagePopover();
    if (!(elements.contactImagePopover instanceof HTMLElement)) {
      return;
    }

    if (isContactImagePopoverOpen()) {
      closeContactImagePopover({ restoreFocus: false });
    }

    state.contactImagePopoverTargetFieldId = targetField.id;
    state.contactImagePopoverTrigger = anchorElement instanceof HTMLElement ? anchorElement : null;

    const rect = anchorElement.getBoundingClientRect();
    const top = Math.min(window.innerHeight - 220, rect.bottom + 8);
    const left = Math.min(window.innerWidth - 380, Math.max(8, rect.left - 12));
    elements.contactImagePopover.style.setProperty('--contact-popover-top', `${Math.max(8, top)}px`);
    elements.contactImagePopover.style.setProperty('--contact-popover-left', `${Math.max(8, left)}px`);
    elements.contactImagePopover.classList.remove('hidden');
    elements.contactImagePopover.hidden = false;

    if (state.contactImagePopoverTrigger instanceof HTMLElement) {
      state.contactImagePopoverTrigger.setAttribute('aria-expanded', 'true');
    }

    const focusTarget = elements.contactImageDropzone instanceof HTMLElement
      ? elements.contactImageDropzone
      : elements.contactImageCancel;
    if (focusTarget instanceof HTMLElement) {
      focusTarget.focus();
    }
  };

  const applyContactImageValue = (rawValue) => {
    const targetField = document.getElementById(state.contactImagePopoverTargetFieldId);
    if (!(targetField instanceof HTMLInputElement)) {
      return;
    }

    const nextValue = String(rawValue || '').trim();
    if (targetField.maxLength > 0 && nextValue.length > targetField.maxLength) {
      showBusinessesToast(T.contactImageTooLong.replace('%d', String(targetField.maxLength)), 'error', 5000, true);
      return;
    }
    targetField.value = nextValue;
    syncContactAvatarPreview(targetField);

    const customCardId = String(targetField.dataset.customCardId || '');
    const customField = String(targetField.dataset.customField || '');
    if (customCardId !== '' && customField === 'image_url') {
      upsertCustomCardField(customCardId, 'image_url', nextValue);
      syncCustomCardsHiddenInput();
    }

    showBusinessesToast(T.contactImageSaving, 'save', 1400, true);
    saveBusinessEditorSettings('contact-image', false)
      .then((saved) => {
        if (!saved) {
          showBusinessesToast(T.contactImageUnchanged, 'save', 2200, true);
        }
      })
      .catch((error) => PW.error(error));
    closeContactImagePopover({ restoreFocus: true });
  };

  const fileToCompactDataUrl = async (file) => {
    if (!(file instanceof File)) {
      return '';
    }

    const sourceDataUrl = await new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(String(reader.result || ''));
      reader.onerror = () => reject(new Error('Image read failed'));
      reader.readAsDataURL(file);
    });

    if (sourceDataUrl === '') {
      return '';
    }

    const image = await new Promise((resolve, reject) => {
      const img = new Image();
      img.onload = () => resolve(img);
      img.onerror = () => reject(new Error('Image load failed'));
      img.src = sourceDataUrl;
    });

    const canvas = document.createElement('canvas');
    const maxAllowedLength = 16000;
    const sizeCandidates = [96, 88, 80, 72, 64, 56];
    const qualityCandidates = [0.62, 0.55, 0.48, 0.42, 0.35];
    const ctx = canvas.getContext('2d');
    if (!ctx) {
      return '';
    }

    const srcW = image.width;
    const srcH = image.height;
    const crop = Math.min(srcW, srcH);
    const sx = Math.floor((srcW - crop) / 2);
    const sy = Math.floor((srcH - crop) / 2);

    let candidate = '';
    for (const size of sizeCandidates) {
      canvas.width = size;
      canvas.height = size;
      ctx.clearRect(0, 0, size, size);
      ctx.drawImage(image, sx, sy, crop, crop, 0, 0, size, size);

      for (const quality of qualityCandidates) {
        candidate = canvas.toDataURL('image/webp', quality);
        if (candidate.length <= maxAllowedLength) {
          return candidate;
        }
      }
    }

    return candidate;
  };

  const getImageFieldForAvatar = (avatar) => {
    if (!(avatar instanceof HTMLImageElement)) {
      return null;
    }

    const avatarId = String(avatar.id || '').trim();
    if (avatarId === '') {
      return null;
    }

    const field = document.querySelector(`.businesses_contact_image_input[data-preview-id="${CSS.escape(avatarId)}"]`);
    return field instanceof HTMLInputElement ? field : null;
  };

  const resolveContactCardAvatar = (target) => {
    if (!(target instanceof Element)) {
      return null;
    }

    const avatar = target.closest('.businesses_contact_card_avatar');
    if (avatar instanceof HTMLImageElement) {
      return avatar;
    }

    const avatarButton = target.closest('.businesses_contact_card_avatar_button');
    if (avatarButton instanceof HTMLButtonElement) {
      const nestedAvatar = avatarButton.querySelector('.businesses_contact_card_avatar');
      return nestedAvatar instanceof HTMLImageElement ? nestedAvatar : null;
    }

    return null;
  };

  const resolveContactCardAvatarAnchor = (avatar) => {
    if (!(avatar instanceof HTMLImageElement)) {
      return null;
    }

    const avatarButton = avatar.closest('.businesses_contact_card_avatar_button');
    return avatarButton instanceof HTMLButtonElement ? avatarButton : avatar;
  };

  const handleContactImageFiles = async (files) => {
    const first = files && files.length > 0 ? files[0] : null;
    if (!(first instanceof File)) {
      return;
    }

    try {
      const dataUrl = await fileToCompactDataUrl(first);
      applyContactImageValue(dataUrl);
    } catch (error) {
      PW.error(error);
      showBusinessesToast(T.contactImageProcessFailed, 'error', 5000, true);
    }
  };

  const syncContactAvatarPreview = (field) => {
    if (!(field instanceof HTMLInputElement) || !field.classList.contains('businesses_contact_image_input')) {
      return;
    }

    const previewId = String(field.dataset.previewId || '').trim();
    if (previewId === '') {
      return;
    }

    const preview = document.getElementById(previewId);
    if (!(preview instanceof HTMLImageElement)) {
      return;
    }

    const nextSrc = getContactAvatarPreviewSrc(field.value);
    preview.src = nextSrc;
    preview.alt = '';
    preview.setAttribute('role', 'presentation');
  };

  const applyPhoneInputFormatting = (input) => {
    if (!(input instanceof HTMLInputElement)) {
      return;
    }

    input.type = 'tel';
    input.autocomplete = input.autocomplete || 'tel';
    input.inputMode = 'numeric';
    input.maxLength = 14;
    input.pattern = '\\([0-9]{3}\\) [0-9]{3}-[0-9]{4}';
    if (input.placeholder === '' || input.placeholder === 'Phone' || input.placeholder === '123-456-7890') {
      input.placeholder = '(123) 456-7890';
    }
    PC.formatPhoneNumber(input);
  };

  const formatPhoneInputsWithin = (root = document) => {
    if (!(root instanceof Document || root instanceof HTMLElement || root instanceof HTMLDialogElement)) {
      return;
    }

    root.querySelectorAll('input[id$="_phone"], .businesses_contact_custom_input[data-custom-field="phone"]').forEach((field) => {
      if (field instanceof HTMLInputElement) {
        applyPhoneInputFormatting(field);
      }
    });
  };

  const EDITOR_AUTOSAVE_SOURCE_IDS = [
    'businesses_editor_name',
    'businesses_editor_type',
    'businesses_editor_role',
    'businesses_editor_status',
    'businesses_editor_pay_frequency',
    'businesses_editor_timezone',
    'businesses_editor_currency',
    ...Object.keys(EDITOR_META_FIELD_MAP),
  ];

  const getEditorFieldValueById = (fieldId) => {
    const field = document.getElementById(fieldId);
    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
      if (field instanceof HTMLInputElement && field.type === 'checkbox') {
        return field.checked ? '1' : '0';
      }
      return String(field.value || '').trim();
    }
    return '';
  };

  const setEditorFieldValueById = (fieldId, value) => {
    const field = document.getElementById(fieldId);
    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
      if (field instanceof HTMLInputElement && field.type === 'checkbox') {
        const normalized = String(value || '').trim().toLowerCase();
        field.checked = normalized === '1' || normalized === 'true' || normalized === 'yes' || normalized === 'on';
      } else {
      field.value = String(value || '');
      }
      if (field instanceof HTMLInputElement) {
        if (fieldId.endsWith('_phone')) {
          applyPhoneInputFormatting(field);
        }
        syncContactAvatarPreview(field);
      }
    }

    if (fieldId === 'businesses_editor_contact_custom_json') {
      state.customContactCards = normalizeCustomContactCards(value);
      syncCustomCardsHiddenInput();
      renderCustomContactCards();
    }
  };

  const getEditorSensitiveValue = (fieldId) => {
    const field = document.getElementById(fieldId);
    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
      return String(field.value || '').trim().toLowerCase();
    }

    return '';
  };

  const getKnownTransferMemberCount = () => {
    return Array.isArray(state.transferCandidates) ? state.transferCandidates.length : 0;
  };

  const normalizeTransferLookupName = (value) => String(value || '').trim().toLowerCase();

  const deriveTransferCandidateDisplay = (member) => {
    const name = decodePossiblyEncodedText(String(member?.full_name || '').trim());
    const fallbackEmail = String(member?.email || '').trim();
    if (name !== '') {
      return name;
    }

    return fallbackEmail;
  };

  const getTransferCandidateByUUID = (userUUID) => {
    const target = String(userUUID || '').trim();
    if (target === '') {
      return null;
    }

    return state.transferCandidates.find((candidate) => candidate.userUUID === target) || null;
  };

  const renderTransferSelectedMember = () => {
    if (!(elements.transferSelectedMember instanceof HTMLElement)) {
      return;
    }

    const candidate = getTransferCandidateByUUID(state.transferSelectedUUID);
    if (!candidate) {
      elements.transferSelectedMember.classList.add('businesses_empty');
      elements.transferSelectedMember.textContent = '';
      return;
    }

    const metaParts = [candidate.email, candidate.roleLabel, candidate.statusLabel].filter((part) => String(part || '').trim() !== '');
    Guardian.setHTML(elements.transferSelectedMember, `
      <div class="businesses_transfer_selected_member_row">
        <div class="businesses_transfer_selected_member_text">
          <strong>${escapeHtml(candidate.displayName)}</strong>
          <span>${escapeHtml(metaParts.join(' | '))}</span>
        </div>
        <button type="button" class="btn btn_secondary businesses_transfer_selected_member_clear" data-transfer-selection-action="deselect">Deselect</button>
      </div>
    `);
    elements.transferSelectedMember.classList.remove('businesses_empty');
  };

  const setTransferInputLocked = (locked) => {
    if (!(elements.transferTarget instanceof HTMLInputElement)) {
      return;
    }

    const canTransfer = !(elements.transferButton instanceof HTMLButtonElement) || !elements.transferButton.disabled;
    elements.transferTarget.disabled = locked || !canTransfer;
  };

  const setTransferButtonVisible = (visible) => {
    if (!(elements.transferButton instanceof HTMLButtonElement)) {
      return;
    }

    if (visible) {
      elements.transferButton.hidden = false;
      elements.transferButton.removeAttribute('hidden');
      return;
    }

    elements.transferButton.hidden = true;
    elements.transferButton.setAttribute('hidden', 'hidden');
  };

  const clearTransferSelection = (clearInput = true) => {
    state.transferSelectedUUID = '';
    if (elements.transferTargetUUID instanceof HTMLInputElement) {
      elements.transferTargetUUID.value = '';
    }
    if (clearInput && elements.transferTarget instanceof HTMLInputElement) {
      elements.transferTarget.value = '';
    }
    if (elements.transferConfirmation instanceof HTMLInputElement) {
      elements.transferConfirmation.value = '';
    }
    if (elements.transferConfirmationContainer instanceof HTMLElement) {
      elements.transferConfirmationContainer.classList.add('businesses_empty');
    }
    setTransferButtonVisible(false);
    setTransferInputLocked(false);
    renderTransferSelectedMember();
    syncTransferConfirmation();
  };

  const applyTransferSelection = (candidate, announce = true) => {
    if (!candidate) {
      clearTransferSelection(false);
      return;
    }

    state.transferSelectedUUID = candidate.userUUID;
    if (elements.transferTarget instanceof HTMLInputElement) {
      elements.transferTarget.value = candidate.displayName;
    }
    if (elements.transferTargetUUID instanceof HTMLInputElement) {
      elements.transferTargetUUID.value = candidate.userUUID;
    }
    if (elements.transferConfirmation instanceof HTMLInputElement) {
      elements.transferConfirmation.value = '';
    }
    if (elements.transferConfirmationContainer instanceof HTMLElement) {
      elements.transferConfirmationContainer.classList.remove('businesses_empty');
    }
    setTransferButtonVisible(false);
    setTransferInputLocked(true);
    renderTransferSelectedMember();
    syncTransferConfirmation();

    if (announce) {
      showBusinessesToast(T.transferMemberChosen.replace('%s', candidate.displayName), 'save', 3200, true);
    }
  };

  const syncTransferTargetFromLookup = () => {
    const field = elements.transferTarget;
    if (!(field instanceof HTMLInputElement)) {
      return;
    }

    const lookupValue = normalizeTransferLookupName(field.value);
    if (!(elements.transferTargetUUID instanceof HTMLInputElement)) {
      return;
    }

    if (lookupValue === '') {
      elements.transferTargetUUID.value = '';
      return;
    }

    const matches = state.transferCandidates.filter((candidate) => candidate.lookupKey === lookupValue);
    if (matches.length === 1) {
      applyTransferSelection(matches[0], true);
      return;
    }

    if (state.transferSelectedUUID === '') {
      elements.transferTargetUUID.value = '';
    }
  };

  const syncTransferConfirmation = () => {
    const confirmInput = elements.transferConfirmation;
    if (!(confirmInput instanceof HTMLInputElement)) {
      return;
    }

    const expectedPhrase = 'TRANSFER BUSINESS';
    const rawTyped = String(confirmInput.value || '');
    const uppercaseTyped = rawTyped.toUpperCase();
    if (rawTyped !== uppercaseTyped) {
      confirmInput.value = uppercaseTyped;
    }
    const normalizedTyped = uppercaseTyped
      .replace(/[^A-Z\s]/g, ' ')
      .replace(/\s+/g, ' ')
      .trim();
    const isMatch = normalizedTyped === expectedPhrase;
    const hasSelection = state.transferSelectedUUID !== '';

    if (elements.transferConfirmationStatus instanceof HTMLElement) {
      if (!hasSelection) {
        elements.transferConfirmationStatus.textContent = '';
      } else if (rawTyped.trim() === '') {
        elements.transferConfirmationStatus.textContent = T.transferConfirmType;
      } else if (isMatch) {
        elements.transferConfirmationStatus.textContent = T.transferConfirmAccepted;
      } else {
        elements.transferConfirmationStatus.textContent = T.transferConfirmMismatch;
      }
    }

    setTransferButtonVisible(hasSelection && isMatch);
  };

  const syncEditorRiskBaselineFromInputs = () => {
    state.editorRiskBaseline.type = getEditorSensitiveValue('businesses_editor_type');
    state.editorRiskBaseline.role = getEditorSensitiveValue('businesses_editor_role');
    state.editorRiskBaseline.status = getEditorSensitiveValue('businesses_editor_status');
  };

  const setEditorSensitiveFieldValue = (fieldId, nextValue) => {
    const field = document.getElementById(fieldId);
    if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
      field.value = String(nextValue || '');
    }
  };

  const promptSensitiveEditorTransition = (fieldId, previousValue, nextValue) => {
    const business = getSelectedBusiness();
    const orgName = decodePossiblyEncodedText(String(business?.name || 'this business'));

    if (fieldId === 'businesses_editor_type') {
      if (
        previousValue === 'shared'
        && nextValue === 'personal'
        && hasActiveBusinessSubscription
        && business
        && isBusinessOwner(business)
      ) {
        const memberCount = getKnownTransferMemberCount();
        const memberWarning = memberCount > 0
          ? (memberCount === 1
            ? T.confirmTypeMemberWarningOne
            : T.confirmTypeMemberWarningMany.replace('%d', String(memberCount)))
          : '';
        return window.confirm(
          T.confirmTypeSharedToPersonal
            .replace('%s', orgName)
            .replace('%s', memberWarning),
        );
      }

      return window.confirm(
        T.confirmTypeGeneric
          .replace('%s', orgName)
          .replace('%s', previousValue || T.valueUnknown)
          .replace('%s', nextValue || T.valueUnknown),
      );
    }

    if (fieldId === 'businesses_editor_role') {
      return window.confirm(
        T.confirmRole
          .replace('%s', previousValue || T.valueUnknown)
          .replace('%s', nextValue || T.valueUnknown),
      );
    }

    if (fieldId === 'businesses_editor_status') {
      if (previousValue === 'active' && nextValue === 'pending') {
        return window.confirm(T.confirmStatusActivePending);
      }

      return window.confirm(
        T.confirmStatusGeneric
          .replace('%s', previousValue || T.valueUnknown)
          .replace('%s', nextValue || T.valueUnknown),
      );
    }

    return true;
  };

  const guardSensitiveEditorFieldChange = (fieldId) => {
    if (state.editorHydrating || !EDITOR_SENSITIVE_FIELD_IDS.includes(fieldId)) {
      return true;
    }

    const baselineKey = fieldId === 'businesses_editor_type'
      ? 'type'
      : fieldId === 'businesses_editor_role'
        ? 'role'
        : 'status';

    const previousValue = String(state.editorRiskBaseline[baselineKey] || '').trim().toLowerCase();
    const nextValue = getEditorSensitiveValue(fieldId);

    if (previousValue === '' || nextValue === '' || previousValue === nextValue) {
      return true;
    }

    const confirmed = promptSensitiveEditorTransition(fieldId, previousValue, nextValue);
    if (!confirmed) {
      setEditorSensitiveFieldValue(fieldId, previousValue);
      if (state.editorAutoSaveTimerId !== null) {
        window.clearTimeout(state.editorAutoSaveTimerId);
        state.editorAutoSaveTimerId = null;
      }
      showBusinessesToast(T.changeCanceled, 'error', 4200, true);
      return false;
    }

    return true;
  };

  const getEditorPayloadFallback = (key) => {
    if (state.editorSettingsCache && Object.prototype.hasOwnProperty.call(state.editorSettingsCache, key)) {
      return String(state.editorSettingsCache[key] ?? '').trim();
    }

    const business = getSelectedBusiness();
    if (business && Object.prototype.hasOwnProperty.call(business, key)) {
      return String(business[key] ?? '').trim();
    }

    return '';
  };

  const collectOrganizationPayload = () => {
    syncCustomCardsHiddenInput();

    const payload = {
      name: elements.name instanceof HTMLInputElement
        ? decodePossiblyEncodedText(elements.name.value).trim()
        : getEditorPayloadFallback('name'),
      timezone: elements.timezone instanceof HTMLInputElement
        ? elements.timezone.value.trim()
        : getEditorPayloadFallback('timezone'),
      currency: elements.currency instanceof HTMLInputElement
        ? elements.currency.value.trim()
        : getEditorPayloadFallback('currency'),
    };

    Object.entries(EDITOR_META_FIELD_MAP).forEach(([fieldId, payloadKey]) => {
      payload[payloadKey] = document.getElementById(fieldId)
        ? getEditorFieldValueById(fieldId)
        : getEditorPayloadFallback(payloadKey);
    });

    return payload;
  };

  const collectPayrollPayload = () => ({
    pay_frequency: elements.payFrequency instanceof HTMLSelectElement
      ? elements.payFrequency.value
      : getEditorPayloadFallback('pay_frequency') || 'biweekly',
    pay_anchor: elements.payAnchor instanceof HTMLInputElement && elements.payAnchor.value !== ''
      ? getEditorPayAnchor()
      : getEditorPayloadFallback('pay_anchor') || getEditorPayAnchor(),
    pay_period_start: elements.payPeriodStart instanceof HTMLInputElement
      ? elements.payPeriodStart.value
      : getEditorPayloadFallback('pay_period_start'),
    pay_period_length: elements.payPeriodLength instanceof HTMLInputElement
      ? elements.payPeriodLength.value
      : getEditorPayloadFallback('pay_period_length') || FREQUENCY_LENGTHS.biweekly,
    editing_grace_days: elements.editorEditingGraceDayRadios.length > 0
      ? getEditorEditingGraceDays()
      : getEditorPayloadFallback('editing_grace_days') || '0',
    default_wage: elements.defaultWage instanceof HTMLInputElement
      ? elements.defaultWage.value.trim()
      : getEditorPayloadFallback('default_wage'),
  });

  const collectBusinessEditorPayload = () => {
    syncCustomCardsHiddenInput();

    const payload = {
      name: elements.name instanceof HTMLInputElement
        ? decodePossiblyEncodedText(elements.name.value).trim()
        : getEditorPayloadFallback('name'),
      business_type: (elements.type instanceof HTMLSelectElement || elements.type instanceof HTMLInputElement)
        ? String(elements.type.value || '').trim().toLowerCase()
        : getEditorPayloadFallback('business_type'),
      role: (elements.role instanceof HTMLSelectElement || elements.role instanceof HTMLInputElement)
        ? String(elements.role.value || '').trim().toLowerCase()
        : getEditorPayloadFallback('role'),
      status: (elements.status instanceof HTMLSelectElement || elements.status instanceof HTMLInputElement)
        ? String(elements.status.value || '').trim().toLowerCase()
        : getEditorPayloadFallback('status'),
      pay_frequency: elements.payFrequency instanceof HTMLSelectElement
        ? elements.payFrequency.value
        : getEditorPayloadFallback('pay_frequency') || 'biweekly',
      pay_anchor: elements.payAnchor instanceof HTMLInputElement && elements.payAnchor.value !== ''
        ? getEditorPayAnchor()
        : getEditorPayloadFallback('pay_anchor') || getEditorPayAnchor(),
      pay_period_start: elements.payPeriodStart instanceof HTMLInputElement
        ? elements.payPeriodStart.value
        : getEditorPayloadFallback('pay_period_start'),
      pay_period_length: elements.payPeriodLength instanceof HTMLInputElement
        ? elements.payPeriodLength.value
        : getEditorPayloadFallback('pay_period_length') || FREQUENCY_LENGTHS.biweekly,
      editing_grace_days: elements.editorEditingGraceDayRadios.length > 0
        ? getEditorEditingGraceDays()
        : getEditorPayloadFallback('editing_grace_days') || '0',
      default_wage: elements.defaultWage instanceof HTMLInputElement
        ? elements.defaultWage.value.trim()
        : getEditorPayloadFallback('default_wage'),
      timezone: elements.timezone instanceof HTMLInputElement
        ? elements.timezone.value.trim()
        : getEditorPayloadFallback('timezone'),
      currency: elements.currency instanceof HTMLInputElement
        ? elements.currency.value.trim()
        : getEditorPayloadFallback('currency'),
    };

    Object.entries(EDITOR_META_FIELD_MAP).forEach(([fieldId, payloadKey]) => {
      payload[payloadKey] = document.getElementById(fieldId)
        ? getEditorFieldValueById(fieldId)
        : getEditorPayloadFallback(payloadKey);
    });

    return payload;
  };

  const buildEditorPayloadSignature = (payload) => {
    return Object.keys(payload)
      .sort()
      .map((key) => `${key}:${String(payload[key] ?? '')}`)
      .join('|');
  };

  const saveBusinessEditorSettings = async (source = 'manual', refreshAfterSave = false) => {
    if (resolveBusinessSubPage() === 'details') {
      return saveOrganizationSettings(source, refreshAfterSave);
    }

    if (resolveBusinessSubPage() === 'payroll') {
      return savePayrollSettings(source, refreshAfterSave);
    }

    if (state.selectedBusinessId === '' || state.editorHydrating) {
      return false;
    }

    const payload = collectBusinessEditorPayload();
    const signature = buildEditorPayloadSignature(payload);
    if (signature === state.editorLastSavedSignature) {
      return false;
    }

    if (state.editorSaveInFlight) {
      state.editorSavePendingSource = source;
      return false;
    }

    state.editorSaveInFlight = true;
    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/settings/update`, payload);
      state.editorLastSavedSignature = signature;
      syncEditorRiskBaselineFromInputs();
      let toastMessage = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_AUTO_SAVE_DETAILS')); ?>';
      if (source === 'manual') {
        toastMessage = T.defaultsSaved;
      } else if (source === 'contact-image') {
        toastMessage = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_IMAGE_SAVED')); ?>';
      } else if (source.startsWith('custom-contact')) {
        toastMessage = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CARD_SAVED')); ?>';
      }
      showBusinessesToast(toastMessage, 'save', source === 'manual' ? 5000 : 2600, true);
      if (refreshAfterSave) {
        await refreshIndex(state.selectedBusinessId, true);
      }
      return true;
    } catch (error) {
      PW.error(error);
      showBusinessesToast(error instanceof Error && error.message ? error.message : T.defaultsSaveFailed, 'error', 7000, true);
      return false;
    } finally {
      state.editorSaveInFlight = false;
      if (state.editorSavePendingSource !== '') {
        const pending = state.editorSavePendingSource;
        state.editorSavePendingSource = '';
        saveBusinessEditorSettings(pending, false).catch((error) => PW.error(error));
      }
    }
  };

  const scheduleEditorAutoSave = (delayMs = 600, source = 'auto') => {
    const subPage = resolveBusinessSubPage();
    if (state.editorHydrating || subPage === 'payroll') {
      return;
    }

    if (state.editorAutoSaveTimerId !== null) {
      window.clearTimeout(state.editorAutoSaveTimerId);
    }

    state.editorAutoSaveTimerId = window.setTimeout(() => {
      saveBusinessEditorSettings(source, false).catch((error) => PW.error(error));
    }, delayMs);
  };

  const isPersonalBusiness = (business) => String(business?.business_type || '').toLowerCase() === T.personal;

  const isBusinessOwner = (business) => {
    const ownerUUID = String(business?.owner_uuid || '');
    const role = String(business?.role || '').toLowerCase();

    return ownerUUID === currentUserUUID || role === T.owner;
  };

  const canEditOwnRoleInEditor = (business) => {
    const role = String(business?.role || '').trim().toLowerCase();
    return role !== 'owner' && role !== 'coordinator';
  };

  const canUsePremiumOrgFeatures = (business) => {
    if (isElevatedStaffUser) {
      return true;
    }

    if (hasActiveBusinessSubscription) {
      return true;
    }

    if (hasActivePremiumSubscription) {
      return false;
    }

    if (!business || typeof business !== 'object') {
      return false;
    }

    if (isBusinessOwner(business) || isPersonalBusiness(business)) {
      return true;
    }

    return false;
  };

  const ACCESS_MANAGE_WARNING = String(T.memberAccessManageDenied || T.manageAccessUnavailable || '').trim();

  const getBusinessScopes = (business) => {
    if (!Array.isArray(business?.scopes)) {
      return [];
    }

    return business.scopes
      .map((scope) => String(scope || '').trim().toLowerCase())
      .filter((scope) => scope !== '');
  };

  const canWriteBusinessSites = (business) => {
    if (!business || typeof business !== 'object') {
      return false;
    }

    if (!canUsePremiumOrgFeatures(business)) {
      return false;
    }

    if (isBusinessOwner(business)) {
      return true;
    }

    const relationshipStatus = String(business.relationship_status || business.status || '').trim().toLowerCase();
    if (relationshipStatus !== 'active') {
      return false;
    }

    const role = String(business.role || '').trim().toLowerCase();
    if (role === 'owner' || role === 'coordinator') {
      return true;
    }

    const scopeSet = new Set(getBusinessScopes(business));
    return scopeSet.has('sites.write') || scopeSet.has('all');
  };

  const canManageBusinessAccess = (business) => {
    if (!business || typeof business !== 'object') {
      return false;
    }

    if (isBusinessOwner(business)) {
      return true;
    }

    const relationshipStatus = String(business.relationship_status || business.status || '').trim().toLowerCase();
    if (relationshipStatus !== 'active') {
      return false;
    }

    const role = String(business.role || '').trim().toLowerCase();
    if (role === 'owner' || role === 'coordinator') {
      return true;
    }

    const scopeSet = new Set(getBusinessScopes(business));
    return scopeSet.has('access.manage') || scopeSet.has('org.settings.write');
  };

  const canManageSelectedBusinessAccess = () => {
    const business = getSelectedBusiness();
    if (!business) {
      return false;
    }

    return canManageBusinessAccess(business);
  };

  const canGenerateAuditControlTest = (business) => {
    if (!business || typeof business !== 'object' || !isDevEnvironment) {
      return false;
    }

    if (!(isAdminUser || isSuperAdminUser)) {
      return false;
    }

    return canManageBusinessAccess(business);
  };

  const GATED_PANEL_SELECTORS = [
    '#businesses_audit_control_test_panel',
    '.businesses_panel_sites_discovery',
    '.businesses_panel_audit_timeline',
    '#businesses_members_requests_section',
    '#businesses_members_invite_section',
    '#businesses_members_import_section',
  ];

  const syncDevGatedPanelBorders = () => {
    GATED_PANEL_SELECTORS.forEach((selector) => {
      document.querySelectorAll(selector).forEach((node) => {
        if (!(node instanceof HTMLElement)) {
          return;
        }
        node.classList.toggle('businesses_dev_gated_panel', isDevEnvironment);
      });
    });
  };

  const setAuditControlTestStatus = (message, tone = 'info') => {
    if (!(elements.auditControlTestStatus instanceof HTMLElement)) {
      return;
    }

    elements.auditControlTestStatus.textContent = String(message || '');
    elements.auditControlTestStatus.classList.remove('error', 'success');
    if (tone === 'error' || tone === 'success') {
      elements.auditControlTestStatus.classList.add(tone);
    }
  };

  const syncAuditControlTestPanel = (business) => {
    if (!(elements.auditControlTestPanel instanceof HTMLElement)) {
      return;
    }

    const allowed = !!business && canGenerateAuditControlTest(business);
    elements.auditControlTestPanel.hidden = !allowed;

    if (elements.auditControlTestButton instanceof HTMLButtonElement) {
      elements.auditControlTestButton.disabled = !allowed;
    }

    if (!allowed) {
      setAuditControlTestStatus('');
    }
  };

  const showAccessManagementDeniedWarning = (message = ACCESS_MANAGE_WARNING) => {
    const warning = String(message || ACCESS_MANAGE_WARNING);
    setCurrentBusinessStatus(warning);
    announceInvitesStatus(warning);
    announceAccessRequestsStatus(warning);
    if (elements.membersGridStatus instanceof HTMLElement) {
      elements.membersGridStatus.textContent = warning;
    }
    const membersInviteStatus = document.getElementById('businesses_members_invite_status');
    if (membersInviteStatus instanceof HTMLElement) {
      membersInviteStatus.textContent = warning;
      membersInviteStatus.classList.remove('success');
      membersInviteStatus.classList.add('error', 'is-visible');
    }
    const membersRequestsStatus = document.getElementById('businesses_access_requests_sr_status');
    if (membersRequestsStatus instanceof HTMLElement) {
      membersRequestsStatus.textContent = warning;
    }
  };

  const getSelectedBusiness = () => findBusiness(state.selectedBusinessId);

  const getOwnedSharedBusiness = () => {
    return state.businesses.find((business) => {
      if (!business || typeof business !== 'object') {
        return false;
      }

      const ownerUUID = String(business.owner_uuid || '');
      const type = String(business.business_type || 'shared').toLowerCase();
      const status = String(business.status || 'active').toLowerCase();

      if (ownerUUID !== currentUserUUID || type !== 'shared') {
        return false;
      }

      return status !== 'archived' && status !== 'deleted' && status !== 'disabled';
    }) || null;
  };

  const hasOwnedSharedBusiness = () => {
    return getOwnedSharedBusiness() !== null;
  };

  const resolveBusinessSubPage = () => {
    const workspace = document.getElementById('business-workspace');
    if (workspace instanceof HTMLElement) {
      return String(workspace.dataset.businessSubpage || '').trim();
    }

    return '';
  };

  const isBusinessWorkspacePage = () => resolveBusinessSubPage() !== '';

  const resolveControlCenterBusiness = () => {
    const ownedShared = getOwnedSharedBusiness();
    if (ownedShared) {
      return ownedShared;
    }

    return state.businesses.find((business) => {
      if (!business || typeof business !== 'object') {
        return false;
      }

      const type = String(business.business_type || 'shared').toLowerCase();
      const relationshipStatus = String(business.relationship_status || business.status || 'active').toLowerCase();
      return type === 'shared' && (relationshipStatus === 'active' || relationshipStatus === 'pending');
    }) || null;
  };

  const setOrganizationStatus = (message, tone = 'info') => {
    if (!(elements.organizationStatus instanceof HTMLElement)) {
      return;
    }

    const text = String(message || '');
    elements.organizationStatus.textContent = text;
    elements.organizationStatus.classList.remove('error', 'success', 'visually_hidden');
    if (text === '') {
      elements.organizationStatus.classList.add('visually_hidden');
    } else if (tone === 'error' || tone === 'success') {
      elements.organizationStatus.classList.add(tone);
    }
  };

  const setPayrollStatus = (message, tone = 'info') => {
    if (!(elements.payrollStatus instanceof HTMLElement)) {
      return;
    }

    elements.payrollStatus.textContent = String(message || '');
    elements.payrollStatus.classList.remove('error', 'success');
    if (tone === 'error' || tone === 'success') {
      elements.payrollStatus.classList.add(tone);
    }
  };

  const savePayrollSettings = async (source = 'manual', refreshAfterSave = false) => {
    if (state.selectedBusinessId === '' || state.editorHydrating) {
      return false;
    }

    if (blockPremiumActionWhenLocked()) {
      return false;
    }

    const payload = collectPayrollPayload();
    const signature = buildEditorPayloadSignature(payload);
    if (signature === state.payrollLastSavedSignature) {
      if (source === 'manual') {
        showBusinessesToast(T.noChangesToUpdate, 'save', 2600, true);
      }
      return false;
    }

    if (elements.payrollSaveButton instanceof HTMLButtonElement) {
      elements.payrollSaveButton.disabled = true;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/settings/update`, payload);
      state.payrollLastSavedSignature = signature;
      state.editorLastSavedSignature = signature;
      showBusinessesToast(T.payrollSaved, 'save', source === 'manual' ? 5000 : 2600, true);
      setPayrollStatus(T.payrollSaved, 'success');
      if (refreshAfterSave) {
        await refreshIndex(state.selectedBusinessId, false);
      } else {
        await loadBusinesses();
        applySingleBusinessOverviewMode();
      }
      return true;
    } catch (error) {
      const message = error instanceof Error && error.message ? error.message : T.payrollSaveFailed;
      setPayrollStatus(message, 'error');
      showBusinessesToast(message, 'error', 7000, true);
      return false;
    } finally {
      if (elements.payrollSaveButton instanceof HTMLButtonElement) {
        elements.payrollSaveButton.disabled = false;
      }
    }
  };

  const saveOrganizationSettings = async (source = 'auto', refreshAfterSave = false) => {
    if (state.selectedBusinessId === '' || state.editorHydrating) {
      return false;
    }

    if (blockPremiumActionWhenLocked()) {
      return false;
    }

    const payload = collectOrganizationPayload();
    const signature = buildEditorPayloadSignature(payload);
    if (signature === state.organizationLastSavedSignature) {
      return false;
    }

    if (payload.name.length < 2) {
      setOrganizationStatus(T.nameMin, 'error');
      showBusinessesToast(T.nameMin, 'error', 7000, true);
      return false;
    }

    if (state.organizationSaveInFlight) {
      state.organizationSavePendingSource = source;
      return false;
    }

    state.organizationSaveInFlight = true;
    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/settings/update`, payload);
      state.organizationLastSavedSignature = signature;
      state.editorLastSavedSignature = signature;
      let toastMessage = T.organizationSaved;
      if (source === 'contact-image') {
        toastMessage = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_IMAGE_SAVED')); ?>';
      } else if (source.startsWith('custom-contact')) {
        toastMessage = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_CARD_SAVED')); ?>';
      } else if (source === 'fixed-contact-clear' || source === 'custom-contact-clear') {
        toastMessage = '';
      }
      if (toastMessage !== '') {
        showBusinessesToast(toastMessage, 'save', 2600, true);
      }
      setOrganizationStatus('');
      if (refreshAfterSave) {
        await refreshIndex(state.selectedBusinessId, false);
      } else {
        await loadBusinesses();
        applySingleBusinessOverviewMode();
      }
      return true;
    } catch (error) {
      const message = error instanceof Error && error.message ? error.message : T.organizationSaveFailed;
      setOrganizationStatus(message, 'error');
      showBusinessesToast(message, 'error', 7000, true);
      return false;
    } finally {
      state.organizationSaveInFlight = false;
      if (state.organizationSavePendingSource !== '') {
        const pending = state.organizationSavePendingSource;
        state.organizationSavePendingSource = '';
        saveOrganizationSettings(pending, false).catch((error) => PW.error(error));
      }
    }
  };

  const openDetailsPage = async (businessId) => {
    const business = findBusiness(businessId);
    if (!business) {
      setOrganizationStatus(T.organizationNoBusiness, 'error');
      return;
    }

    stopDiscoveryPolling();
    stopRealtimeAuditPolling();
    state.selectedBusinessId = businessId;
    setEditorMeta(business);
    closeContactImagePopover();

    try {
      await loadBusinessSettings(businessId);
      state.organizationLastSavedSignature = buildEditorPayloadSignature(collectOrganizationPayload());
      setOrganizationStatus('');
      bindOrganizationContactPanelEvents();
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message ? error.message : T.loadDefaultsFailed;
      setOrganizationStatus(message, 'error');
      PC.showToast(message, 'error', 7000, true);
    }
  };

  const openPayrollPage = async (businessId) => {
    const business = findBusiness(businessId);
    if (!business) {
      setPayrollStatus(T.payrollNoBusiness, 'error');
      return;
    }

    stopDiscoveryPolling();
    stopRealtimeAuditPolling();
    state.selectedBusinessId = businessId;
    setEditorMeta(business);
    closeContactImagePopover();

    try {
      await loadBusinessSettings(businessId);
      state.payrollLastSavedSignature = buildEditorPayloadSignature(collectPayrollPayload());
      setPayrollStatus('');
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message ? error.message : T.loadDefaultsFailed;
      setPayrollStatus(message, 'error');
      PC.showToast(message, 'error', 7000, true);
    }
  };

  const resolveWorkspaceBusinessId = (preferredBusinessId = '') => {
    const preferred = String(preferredBusinessId || '').trim();
    if (preferred !== '') {
      return preferred;
    }

    const selected = String(state.selectedBusinessId || '').trim();
    if (selected !== '') {
      return selected;
    }

    const workspace = document.getElementById('business-workspace');
    if (workspace instanceof HTMLElement) {
      const fromDataset = String(workspace.dataset.businessId || '').trim();
      if (fromDataset !== '') {
        return fromDataset;
      }
    }

    return String(resolveControlCenterBusiness()?.business_id || '').trim();
  };

  const prepareBusinessEditorSession = (businessId) => {
    state.selectedBusinessId = businessId;
    const membersPanelOnOpen = document.getElementById('businesses_tab_members_panel');
    if (membersPanelOnOpen) {
      delete membersPanelOnOpen.dataset.ready;
    }
    if (elements.preview) {
      elements.preview.textContent = T.loadingDetails;
    }
  };

  const resolveMembersLensPerf = () => {
    if (resolveBusinessSubPage() !== 'members') {
      return null;
    }

    if (typeof resolveBusinessMembersLensPerf === 'function') {
      return resolveBusinessMembersLensPerf();
    }

    return window.PayCalLensMembersPerf || null;
  };

  const openMembersPage = async (businessId) => {
    const orgId = String(businessId || '').trim();
    if (orgId === '') {
      return;
    }

    const perf = resolveMembersLensPerf();
    const openPage = async () => {
      stopDiscoveryPolling();
      stopRealtimeAuditPolling();
      closeContactImagePopover();
      markBusinessNotificationsRead(orgId).catch(() => {});

      await loadBusinessMembersGrid(orgId);
    };

    if (perf?.isEnabled()) {
      await perf.measure('openMembersPage', openPage, { ranked: false });
      return;
    }

    await openPage();
  };

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
    announceBusinessSitesStatus(
      T.businessSitesLinkedLoaded
        .replace('%d', String(rows.length))
        .replace('%s', rows.length === 1 ? '' : 's'),
    );
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

  const renderGovernanceRelationshipsList = (relationships) => {
    if (!(elements.relationshipsList instanceof HTMLElement)) {
      return;
    }

    const rows = (Array.isArray(relationships) ? relationships : [])
      .map((relationship) => {
        const displayName = String(
          relationship.display_name
          || relationship.name
          || relationship.email
          || relationship.user_uuid
          || T.unknown,
        ).trim();
        const email = String(relationship.email || '').trim();
        const role = String(relationship.role || 'member').trim() || 'member';
        const status = String(relationship.status || relationship.relationship_status || T.pending).trim() || T.pending;
        const roleLabel = T[String(role || '').toLowerCase()] || toTitleLabel(role, role);

        return `
          <div class="businesses_stack_row businesses_stack_row_hint">
            <div class="businesses_stack_text">
              <strong>${safeText(displayName)}</strong>
              <span>${safeText(roleLabel)} | ${safeText(status)}${email !== '' ? ` | ${safeText(email)}` : ''}</span>
            </div>
          </div>
        `;
      });

    if (rows.length === 0) {
      setStackMessage(elements.relationshipsList, T.governanceRelationshipsEmpty);
      announceRelationshipsStatus(T.relationshipsLoadedNone);
      return;
    }

    elements.relationshipsList.classList.remove('businesses_empty');
    Guardian.setHTML(elements.relationshipsList, rows.join(''));
    announceRelationshipsStatus(
      T.relationshipsLoadedCount
        .replace('%d', String(rows.length))
        .replace('%s', rows.length === 1 ? '' : 's'),
    );
  };

  const resolveReportsLensPerf = () => {
    if (typeof resolveBusinessReportsLensPerf === 'function') {
      return resolveBusinessReportsLensPerf();
    }

    return window.PayCalLensReportsPerf || null;
  };

  const openBusinessReportsPage = async (businessId = '') => {
    if (resolveBusinessSubPage() !== 'reports') {
      return;
    }

    const perf = resolveReportsLensPerf();
    const openPage = async () => {
      const orgId = String(businessId || resolveWorkspaceBusinessId() || '').trim();
      if (orgId !== '') {
        state.selectedBusinessId = orgId;
      }

      stopDiscoveryPolling();
      stopRealtimeAuditPolling();
      closeContactImagePopover();
      applySingleBusinessOverviewMode();

      if (orgId !== '') {
        markBusinessNotificationsRead(orgId).catch(() => {});
      }

      if (typeof syncBusinessReportsPanelFromDom === 'function') {
        if (perf?.isEnabled()) {
          perf.measureSync('syncBusinessReportsPanelFromDom', syncBusinessReportsPanelFromDom);
        } else {
          syncBusinessReportsPanelFromDom();
        }
      }
    };

    try {
      if (perf?.isEnabled()) {
        await perf.measure('openBusinessReportsPage', openPage, { ranked: false });
      } else {
        await openPage();
      }
    } finally {
      if (typeof finalizeBusinessReportsLensPerfSummary === 'function') {
        finalizeBusinessReportsLensPerfSummary('Performance Summary');
      }
    }
  };

  const handleSavePayroll = async () => {
    if (state.selectedBusinessId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    await savePayrollSettings('manual', true);
  };

  const shouldUseSingleBusinessOverview = () => resolveControlCenterBusiness() !== null;

  const formatExecutiveSummaryValue = (value, fallback = '—') => {
    const normalized = String(value || '').trim();
    return normalized !== '' ? normalized : fallback;
  };

  const resolveExecutiveSummaryRoleLabel = (business) => {
    const normalizedRole = String(business?.role || '').trim().toLowerCase();
    if (normalizedRole === '') {
      return '—';
    }

    return T[normalizedRole] || toTitleLabel(normalizedRole, '—');
  };

  const resolveExecutiveSummaryStatusLabel = (business) => {
    const status = String(business?.relationship_status || business?.status || '').trim().toLowerCase();
    if (status === '') {
      return '—';
    }

    if (status === 'active') {
      return T.active || toTitleLabel(status, status);
    }

    if (status === 'pending') {
      return T.pending;
    }

    return toTitleLabel(status, status);
  };

  const canViewCoordinatorBusinessTabs = (business) => {
    if (!business || typeof business !== 'object') {
      return false;
    }

    if (isBusinessOwner(business)) {
      return true;
    }

    const role = String(business.role || '').trim().toLowerCase();
    return role === 'owner' || role === 'coordinator';
  };

  const updateBusinessSubNavVisibility = (business) => {
    if (!isBusinessWorkspacePage()) {
      return;
    }

    document.querySelectorAll('.business_subnav_tab[data-business-tab-min-role]').forEach((tabNode) => {
      if (!(tabNode instanceof HTMLElement)) {
        return;
      }

      const minRole = String(tabNode.dataset.businessTabMinRole || '').trim().toLowerCase();
      const visible = minRole === 'coordinator'
        ? canViewCoordinatorBusinessTabs(business)
        : true;
      tabNode.classList.toggle('hidden', !visible);
    });
  };

  const updateExecutiveSummary = (pendingCount = null) => {
    syncBusinessWorkspaceElementRefs();

    const business = resolveControlCenterBusiness();
    if (!business) {
      return;
    }

    if (pendingCount !== null) {
      state.execSummaryPendingCount = Math.max(0, Number(pendingCount || 0));
    }

    const name = decodePossiblyEncodedText(String(business.name || 'Business'));
    const legalName = decodePossiblyEncodedText(String(business.legal_name || ''));
    const industry = decodePossiblyEncodedText(String(business.industry || ''));
    const unreadNotices = Math.max(0, Number(business.notification_unread_count || 0));
    const pendingRequests = state.execSummaryPendingCount;

    if (elements.execSummaryHeading instanceof HTMLElement) {
      elements.execSummaryHeading.textContent = name;
    }

    if (elements.execSummaryLede instanceof HTMLElement) {
      if (legalName !== '' && legalName.toLowerCase() !== name.toLowerCase()) {
        elements.execSummaryLede.textContent = legalName;
      } else if (industry !== '') {
        elements.execSummaryLede.textContent = industry;
      } else {
        elements.execSummaryLede.textContent = T.execSummaryLede;
      }
    }

    if (elements.execSummaryRole instanceof HTMLElement) {
      elements.execSummaryRole.textContent = resolveExecutiveSummaryRoleLabel(business);
    }

    if (elements.execSummaryStatus instanceof HTMLElement) {
      elements.execSummaryStatus.textContent = resolveExecutiveSummaryStatusLabel(business);
    }

    if (elements.execSummaryIndustry instanceof HTMLElement) {
      elements.execSummaryIndustry.textContent = formatExecutiveSummaryValue(industry, T.execNone);
    }

    if (elements.execSummaryPending instanceof HTMLElement) {
      elements.execSummaryPending.textContent = pendingRequests === null
        ? T.execNone
        : (pendingRequests > 0 ? String(pendingRequests) : T.execNone);
    }

    if (elements.execSummaryNotices instanceof HTMLElement) {
      elements.execSummaryNotices.textContent = unreadNotices > 0
        ? String(unreadNotices)
        : T.execNone;
    }

    updateBusinessContextHeader(pendingCount);
  };

  const applySingleBusinessOverviewMode = () => {
    if (shouldUseSingleBusinessOverview()) {
      updateExecutiveSummary();
    }
  };

  const syncCreateButtonState = () => {
    if (!(elements.createButton instanceof HTMLButtonElement)) {
      return;
    }

    if (String(elements.createButton.dataset.defaultLabel || '').trim() === '') {
      elements.createButton.dataset.defaultLabel = String(elements.createButton.textContent || '').trim();
    }

    const locked = hasOwnedSharedBusiness();
    elements.createButton.disabled = false;
    elements.createButton.setAttribute('aria-disabled', 'false');

    if (locked) {
      elements.createButton.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESS_NAV_DETAILS')); ?>';
      elements.createButton.setAttribute('title', '<?php echo addslashes(org_js_index_i18n('BUSINESS_DETAILS_HELP')); ?>');
    } else {
      elements.createButton.textContent = String(elements.createButton.dataset.defaultLabel || '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CREATE')); ?>');
      elements.createButton.removeAttribute('title');
    }

    if (elements.createStatus instanceof HTMLElement) {
      elements.createStatus.textContent = locked ? T.sharedOrgSingleton : '';
    }
  };

  const isSelectedBusinessPremiumLocked = () => {
    const business = getSelectedBusiness();
    if (!business) {
      return false;
    }

    return !canUsePremiumOrgFeatures(business);
  };

  const updatePremiumNotice = (business) => {
    if (!elements.premiumNotice) {
      return;
    }

    if (!business) {
      elements.premiumNotice.textContent = (isElevatedStaffUser || hasActiveBusinessSubscription)
        ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_PREMIUM_NOTICE_SELECT')); ?>'
        : `${T.premiumAdminLocked} ${T.selfOrgWip}`;
      return;
    }

    if (!canUsePremiumOrgFeatures(business)) {
      elements.premiumNotice.textContent = T.premiumAdminLockedDetailed;
      return;
    }

    const typeLabel = isPersonalBusiness(business) ? T.personal : T.shared;
    const selectedNotice = T.premiumNoticeTypeSelected
      .replace('%s', typeLabel)
      .replace('%s', T.nounBusiness);
    elements.premiumNotice.textContent = `${selectedNotice} ${T.selfOrgWip}`;
  };

  const setPremiumLockedState = (business) => {
    const isLocked = !!business && !canUsePremiumOrgFeatures(business);
    const roleLocked = !!business && !canEditOwnRoleInEditor(business);
    const accessLocked = !!business && !canManageBusinessAccess(business);

    if (elements.saveButton instanceof HTMLButtonElement) {
      elements.saveButton.disabled = isLocked;
    }
    if (elements.bootstrapDekButton instanceof HTMLButtonElement) {
      elements.bootstrapDekButton.disabled = isLocked;
    }
    if (elements.role instanceof HTMLSelectElement || elements.role instanceof HTMLInputElement) {
      elements.role.disabled = isLocked || roleLocked;
      elements.role.classList.toggle('businesses_field_locked', isLocked || roleLocked);
      if (isLocked || roleLocked) {
        elements.role.setAttribute('aria-disabled', 'true');
      } else {
        elements.role.removeAttribute('aria-disabled');
      }
    }
    if (elements.inviteSend instanceof HTMLButtonElement) {
      elements.inviteSend.disabled = isLocked || accessLocked;
    }
    if (elements.invitesReload instanceof HTMLButtonElement) {
      elements.invitesReload.disabled = isLocked || accessLocked;
    }
    if (elements.discoveryRun instanceof HTMLButtonElement) {
      elements.discoveryRun.disabled = isLocked;
    }
    if (elements.relationshipsReload instanceof HTMLButtonElement) {
      elements.relationshipsReload.disabled = isLocked || accessLocked;
    }
    if (elements.leaveButton instanceof HTMLButtonElement) {
      if (isLocked) {
        elements.leaveButton.disabled = true;
      }
    }
    if (elements.scopeGrid instanceof HTMLFieldSetElement) {
      elements.scopeGrid.disabled = isLocked;
    }
    if (elements.inviteEmail instanceof HTMLInputElement) {
      elements.inviteEmail.disabled = isLocked;
    }
    if (elements.membersImportEmails instanceof HTMLTextAreaElement) {
      elements.membersImportEmails.disabled = isLocked || accessLocked;
    }
    if (elements.membersImportPrepare instanceof HTMLButtonElement) {
      elements.membersImportPrepare.disabled = isLocked || accessLocked;
    }
    if (elements.membersImportSendCode instanceof HTMLButtonElement) {
      elements.membersImportSendCode.disabled = isLocked || accessLocked || state.membersImport.importId === '';
    }
    if (elements.membersImportCode instanceof HTMLInputElement) {
      elements.membersImportCode.disabled = isLocked || accessLocked || state.membersImport.challengeId === '';
    }
    if (elements.membersImportVerify instanceof HTMLButtonElement) {
      elements.membersImportVerify.disabled = isLocked || accessLocked || state.membersImport.challengeId === '';
    }
    if (elements.membersImportCommit instanceof HTMLButtonElement) {
      elements.membersImportCommit.disabled = isLocked || accessLocked || !state.membersImport.verified;
    }
    if (elements.membersRoleFilter instanceof HTMLSelectElement) {
      elements.membersRoleFilter.disabled = accessLocked;
    }

    updatePremiumNotice(business);
    syncDevGatedPanelBorders();
  };

  const blockPremiumActionWhenLocked = () => {
    if (!isSelectedBusinessPremiumLocked()) {
      return false;
    }

    PC.showToast(T.premiumAdminLockedDetailed, 'error', 9000, true);
    return true;
  };

  const blockAccessManagementActionWhenLocked = () => {
    if (canManageSelectedBusinessAccess()) {
      return false;
    }

    showAccessManagementDeniedWarning();
    PC.showToast(ACCESS_MANAGE_WARNING, 'error', 9000, true);
    return true;
  };

  const getCsrfToken = () => {
    if (!(elements.csrfToken instanceof HTMLInputElement)) {
      return '';
    }

    return String(elements.csrfToken.value || '');
  };

  const buildHeaders = (extra = {}) => ({
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...extra,
  });

  const extractPayloadData = (payload) => {
    if (payload && typeof payload === 'object') {
      const { status, message, _lens, ...data } = payload;
      return data;
    }

    return {};
  };

  const buildApiError = (message, status = 0, data = {}) => {
    const error = new Error(message);
    error.status = Number(status || 0);
    error.data = data && typeof data === 'object' ? data : {};
    return error;
  };

  const apiRequest = async (url, options = {}) => {
    const { timeoutMs: customTimeoutMs, ...fetchOptions } = options;
    const timeoutMs = Number.isFinite(customTimeoutMs)
      ? Math.max(1000, Number(customTimeoutMs))
      : 30000;
    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), timeoutMs);

    const mergedSignal = fetchOptions.signal || controller.signal;

    let response;
    try {
      response = await fetch(url, {
        credentials: 'same-origin',
        headers: buildHeaders(fetchOptions.headers || {}),
        signal: mergedSignal,
        ...fetchOptions,
      });
    } catch (error) {
      window.clearTimeout(timeoutId);
      if (error instanceof DOMException && error.name === 'AbortError') {
        throw new Error('<?php echo addslashes(org_js_index_i18n('BUSINESSES_REQUEST_TIMEOUT')); ?>');
      }
      throw new Error('<?php echo addslashes(org_js_index_i18n('BUSINESSES_REQUEST_NETWORK_FAILED')); ?>');
    }

    window.clearTimeout(timeoutId);

    const raw = await response.text();
    let payload = null;
    if (raw.trim() !== '') {
      try {
        payload = JSON.parse(raw);
      } catch (_error) {
        if (!response.ok) {
          throw new Error(`Request failed (${response.status}).`);
        }
        payload = {};
      }
    }

    if (!response.ok) {
      const message = payload && typeof payload === 'object' && 'message' in payload
        ? String(payload.message || 'Request failed.')
        : `Request failed (${response.status}).`;
      const data = payload && typeof payload === 'object' && 'data' in payload && payload.data && typeof payload.data === 'object'
        ? payload.data
        : {};
      throw buildApiError(message, response.status, data);
    }

    if (payload && typeof payload === 'object' && 'status' in payload && payload.status !== 'success') {
      const data = payload && typeof payload === 'object' && 'data' in payload && payload.data && typeof payload.data === 'object'
        ? payload.data
        : {};
      throw buildApiError(String(payload.message || 'Request failed.'), response.status, data);
    }

    return extractPayloadData(payload || {});
  };

  const postForm = async (url, values, requestOptions = {}) => {
    const body = new URLSearchParams();

    Object.entries(values).forEach(([key, value]) => {
      if (Array.isArray(value)) {
        value.forEach((item) => body.append(`${key}[]`, String(item)));
        return;
      }

      if (value === null || typeof value === 'undefined') {
        return;
      }

      body.set(key, String(value));
    });

    const csrfToken = getCsrfToken();
    if (csrfToken !== '') {
      body.set('csrf_token', csrfToken);
    }

    return apiRequest(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: body.toString(),
      ...requestOptions,
    });
  };

  const warmBusinessWorkspaceCache = (businessId) => {
    const orgId = String(businessId || '').trim();
    if (orgId === '') {
      return;
    }

    const year = new Date().getFullYear();
    const url = `/api/v1/businesses/${encodeURIComponent(orgId)}/cache/warm?year=${encodeURIComponent(String(year))}`;

    fetch(url, {
      credentials: 'same-origin',
      headers: buildHeaders({ Accept: 'application/json' }),
    }).catch((error) => {
      PW.error(error);
    });
  };

  const getSelectedInviteScopes = () => {
    return Array.from(document.querySelectorAll('#businesses_scope_grid .businesses_scope:checked'))
      .map((input) => input instanceof HTMLInputElement ? input.value.trim() : '')
      .filter((value) => value !== '');
  };

  const scopeStatusTemplates = {
    updated: () => T.scopeStatusUpdated,
    loaded: () => T.scopeStatusLoaded,
    cleared: () => T.scopeStatusCleared,
  };

  const announceScopeSelectionStatus = (reason = 'updated') => {
    if (!elements.scopeStatus) {
      return;
    }

    const count = getSelectedInviteScopes().length;
    if (count === 0) {
      elements.scopeStatus.textContent = reason === 'required'
        ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SCOPE_REQUIRED')); ?>'
        : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SCOPE_NONE_SELECTED')); ?>';
      return;
    }

    const template = (scopeStatusTemplates[reason] || scopeStatusTemplates.updated)();
    elements.scopeStatus.textContent = template
      .replace('%d', String(count))
      .replace('%s', count === 1 ? '' : 's');
  };

  const announceInvitesStatus = (message) => {
    if (elements.invitesStatus) {
      elements.invitesStatus.textContent = message;
    }
  };

  const announceAccessRequestsStatus = (message) => {
    if (elements.accessRequestsStatus) {
      elements.accessRequestsStatus.textContent = message;
    }
  };

  const announceRelationshipsStatus = (message) => {
    if (elements.relationshipsStatus instanceof HTMLElement) {
      elements.relationshipsStatus.textContent = String(message || '');
    }
  };

  const announceLiveRequestsStatus = (message) => {
    if (elements.liveRequestsStatus) {
      elements.liveRequestsStatus.textContent = String(message || '');
    }
  };

  const setLiveRequestsNotificationState = (pendingCount) => {
    const panel = document.getElementById('businesses-live-requests-panel');
    const title = document.getElementById('businesses_live_requests_title');
    if (!(panel instanceof HTMLElement) || !(title instanceof HTMLElement)) {
      return;
    }

    const count = Math.max(0, Number(pendingCount || 0));
    panel.toggleAttribute('data-has-pending-requests', count > 0);

    const existingDot = title.querySelector('.businesses_live_requests_dot');
    if (count <= 0) {
      existingDot?.remove();
      return;
    }

    if (existingDot instanceof HTMLElement) {
      existingDot.setAttribute('aria-label', count > 99
        ? '99 plus pending live requests'
        : `${String(count)} pending live request${count === 1 ? '' : 's'}`
      );
      return;
    }

    const dot = document.createElement('span');
    dot.className = 'businesses_live_requests_dot';
    dot.setAttribute('aria-hidden', 'true');
    dot.title = count > 99
      ? '99+ pending live requests'
      : `${String(count)} pending live request${count === 1 ? '' : 's'}`;
    title.appendChild(dot);
  };

  const announceDiscoveryStatus = (message) => {
    if (elements.discoveryStatus) {
      elements.discoveryStatus.textContent = message;
    }
  };

  const announceBusinessSitesStatus = (message) => {
    if (elements.businessSitesStatus instanceof HTMLElement) {
      elements.businessSitesStatus.textContent = String(message || '');
    }
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
    state.discoveryIntervalId = window.setInterval(() => {
      handleDiscovery(false).catch((error) => PW.error(error));
    }, 60000);
  };

  const announceAuditStatus = (message) => {
    if (elements.auditStatus) {
      elements.auditStatus.textContent = message;
    }
  };

  const announceFreeAuditStatus = (message) => {
    if (elements.freeAuditStatus) {
      elements.freeAuditStatus.textContent = String(message || '');
    }
  };

  const findBusiness = (businessId) => {
    return state.businesses.find((business) => String(business.business_id || '') === String(businessId)) || null;
  };

  const getCurrentRelationshipBusiness = () => {
    const preferred = state.businesses.find((business) => {
      if (!business || isPersonalBusiness(business)) {
        return false;
      }

      const status = String(business.relationship_status || '').toLowerCase();
      return status === 'active';
    });

    if (preferred) {
      return preferred;
    }

    return state.businesses.find((business) => {
      if (!business || isPersonalBusiness(business)) {
        return false;
      }

      const status = String(business.relationship_status || '').toLowerCase();
      return status === 'pending';
    }) || null;
  };

  const setCurrentBusinessStatus = (message) => {
    if (elements.currentStatus instanceof HTMLElement) {
      elements.currentStatus.textContent = String(message || '');
    }
  };

  const formatPhoneDisplayValue = (value) => {
    if (typeof PC.formatPhoneNumberValue === 'function') {
      return PC.formatPhoneNumberValue(String(value || ''));
    }

    return String(value || '');
  };

  const renderCurrentBusinessMeta = (business) => {
    if (!(elements.currentMeta instanceof HTMLElement)) {
      return;
    }

    const role = String(business?.role || 'member').trim() || 'member';
    const relationshipStatus = String(business?.relationship_status || business?.status || 'active').trim() || 'active';
    const scopes = Array.isArray(business?.scopes)
      ? business.scopes.map((scope) => String(scope || '').trim()).filter((scope) => scope !== '').sort((left, right) => left.localeCompare(right))
      : [];
    const scopesText = scopes.length === 0 ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SCOPES_NONE_LISTED')); ?>' : scopes.join(', ');
    const ownerEmail = String(business?.owner_email || '').trim();
    const ownerMarkup = ownerEmail === ''
      ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_UNAVAILABLE')); ?>'
      : `<a href="mailto:${encodeURIComponent(ownerEmail)}">${safeText(ownerEmail)}</a>`;

    Guardian.setHTML(elements.currentMeta, `
      <div class="businesses_current_meta_grid">
        <p><strong>${safeText(T.relationshipLabel)}:</strong> ${safeText(role)}</p>
        <p><strong>${safeText(T.ownerLabel)}:</strong> ${ownerMarkup}</p>
        <p><strong>${safeText(T.statusLabel)}:</strong> ${safeText(relationshipStatus)}</p>
        <p><strong>${safeText(T.scopesLabel)}:</strong> ${safeText(scopesText)}</p>
      </div>
    `);
  };

  const renderCurrentBusinessPanel = () => {
    if (!(elements.currentPanel instanceof HTMLElement)) {
      return;
    }

    const business = getCurrentRelationshipBusiness();
    if (!business) {
      state.currentRelationshipBusinessId = '';
      elements.currentPanel.classList.add('hidden');
      if (elements.freeAuditPanel instanceof HTMLElement) {
        elements.freeAuditPanel.classList.add('hidden');
      }
      if (elements.freeAuditGridContainer instanceof HTMLElement) {
        setDatagridMessage(elements.freeAuditGridContainer, '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_CURRENT_RELATIONSHIP')); ?>');
      }
      announceFreeAuditStatus('<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_CURRENT_SELECTED_AUDIT')); ?>');
      if (elements.currentRevokeButton instanceof HTMLButtonElement) {
        elements.currentRevokeButton.disabled = true;
      }
      return;
    }

    state.currentRelationshipBusinessId = String(business.business_id || '');
    elements.currentPanel.classList.remove('hidden');

    const relationshipLabel = String(business.role || 'member').trim() || 'member';
    const businessName = String(business.name || 'Business').trim() || 'Business';

    if (elements.currentSummary instanceof HTMLElement) {
      Guardian.setHTML(elements.currentSummary, `You have provided <strong>${safeText(businessName)}</strong> access to your work data. They may view your work entries at sites you have worked at.`);
    }

    renderCurrentBusinessMeta(business);
    setCurrentBusinessStatus('');

    if (elements.currentRevokeButton instanceof HTMLButtonElement) {
      elements.currentRevokeButton.disabled = String(business.role || '').toLowerCase() === 'owner';
    }

    if (elements.freeAuditPanel instanceof HTMLElement) {
      elements.freeAuditPanel.classList.remove('hidden');
    }

    loadFreeProfileAudit(state.currentRelationshipBusinessId).catch((error) => PW.error(error));
  };

  const renderCurrentBusinessDetailsDialog = (business) => {
    if (!(elements.currentDetailsBody instanceof HTMLElement)) {
      return;
    }

    const addressLine1 = String(business?.address_line1 || '').trim();
    const addressLine2 = String(business?.address_line2 || '').trim();
    const city = String(business?.address_city || '').trim();
    const province = String(business?.address_region || '').trim();
    const postalCode = String(business?.address_postal || '').trim();
    const country = String(business?.address_country || '').trim();
    const localityLine = [city, province, postalCode].filter((part) => part !== '').join(' ');
    const addressLines = [addressLine1, addressLine2, localityLine, country].filter((line) => line !== '');
    const addressMarkup = addressLines.length > 0
      ? addressLines.map((line) => safeText(line)).join('<br>')
      : '';
    const scopes = Array.isArray(business?.scopes)
      ? business.scopes.map((scope) => String(scope || '').trim()).filter((scope) => scope !== '')
      : [];
    const rows = [
      ['Business', String(business?.name || '')],
      ['Relationship', String(business?.role || '')],
      ['<?php echo addslashes(org_js_index_i18n('BUSINESSES_RELATIONSHIP_STATUS_LABEL')); ?>', String(business?.relationship_status || business?.status || '')],
      ['<?php echo addslashes(org_js_index_i18n('BUSINESSES_OWNER_NAME_LABEL')); ?>', String(business?.owner_name || '')],
      ['<?php echo addslashes(org_js_index_i18n('BUSINESSES_OWNER_EMAIL_LABEL')); ?>', String(business?.owner_email || '')],
      ['<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_EMAIL')); ?>', String(business?.contact_email || '')],
      ['<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_PHONE')); ?>', formatPhoneDisplayValue(String(business?.contact_phone || ''))],
      ['Industry', String(business?.industry || '')],
      ['Website', String(business?.website || '')],
      ['Address', addressMarkup],
      ['<?php echo addslashes(org_js_index_i18n('BUSINESSES_SUPPORT_HOURS')); ?>', String(business?.support_hours || '')],
      ['<?php echo addslashes(org_js_index_i18n('BUSINESSES_EMPLOYEE_COUNT')); ?>', String(business?.employee_count || '')],
      ['<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_SCOPES_LABEL')); ?>', scopes.join(', ')],
    ].filter(([, value]) => String(value || '').trim() !== '');

    Guardian.setHTML(elements.currentDetailsBody, `
      <dl class="businesses_current_details_grid">
        ${rows.map(([label, value]) => `<dt>${safeText(label)}</dt><dd>${label === '<?php echo addslashes(org_js_index_i18n('ADDRESS')); ?>' ? String(value || '') : safeText(value)}</dd>`).join('')}
      </dl>
      <p class="help_text"><?php echo addslashes(org_js_index_i18n('BUSINESSES_REVOKE_DETAILS_HELP')); ?></p>
    `);
  };

  const openCurrentBusinessDetailsDialog = () => {
    const business = getCurrentRelationshipBusiness();
    if (!business || !(elements.currentDetailsDialog instanceof HTMLDialogElement)) {
      return;
    }

    renderCurrentBusinessDetailsDialog(business);
    if (!elements.currentDetailsDialog.open) {
      elements.currentDetailsDialog.showModal();
    }
  };

  const closeCurrentBusinessDetailsDialog = () => {
    if (elements.currentDetailsDialog instanceof HTMLDialogElement && elements.currentDetailsDialog.open) {
      elements.currentDetailsDialog.close();
    }
  };

  const handleRevokeCurrentBusinessAccess = async () => {
    const business = getCurrentRelationshipBusiness();
    const businessId = String(business?.business_id || '');
    if (businessId === '') {
      return;
    }

    const confirmed = window.confirm('<?php echo addslashes(org_js_index_i18n('BUSINESSES_REVOKE_CONFIRM')); ?>');
    if (!confirmed) {
      return;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(businessId)}/leave`, {});
      setCurrentBusinessStatus('<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REVOKED_STATUS')); ?>');
      PC.showToast(T.withdrawn, 'save', 6000, true);
      closeCurrentBusinessDetailsDialog();
      await refreshIndex();
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message ? error.message : T.withdrawFailed;
      setCurrentBusinessStatus(message);
      PC.showToast(message, 'error', 7000, true);
    }
  };

  const setStackMessage = (container, message) => {
    if (!container) {
      return;
    }

    container.textContent = message;
    container.classList.add('businesses_empty');
  };

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
    search ? T.datagridSearchQuery.replace('%s', search) : T.datagridSearchNone
  );

  const announceGridStatus = (changeReason = 'loaded') => {
    if (!elements.gridStatus) {
      return;
    }

    const reasonLabel = (gridReasonLabels[changeReason] || gridReasonLabels.loaded)();
    const grid = elements.gridContainer?.querySelector('.datagrid[data-grid="businesses"]');
    if (!grid) {
      elements.gridStatus.textContent = T.gridStatusNone.replace('%s', reasonLabel);
      return;
    }

    const rowCount = grid.querySelectorAll('.datagrid_row').length;
    const search = formatDatagridSearchLabel(state.grid.search);
    const sortInfo = formatDatagridOrderLabel(state.grid.sort, state.grid.direction);
    const page = state.grid.page || 1;

    elements.gridStatus.textContent = T.gridStatusDetail
      .replace('%s', reasonLabel)
      .replace('%d', String(rowCount))
      .replace('%s', rowCount === 1 ? '' : 's')
      .replace('%s', sortInfo)
      .replace('%s', search)
      .replace('%d', String(page));
  };

  const setGridMessage = (message) => {
    if (!elements.gridBody) {
      return;
    }

    Guardian.setHTML(elements.gridBody, `<div class="datagrid_empty">${message}</div>`);
    announceGridStatus('loaded');
  };

  const buildSkeletonRows = (colCount = 4, rowCount = 4) => {
    const cell = '<span class="sk-line businesses_datagrid_skeleton_cell"></span>';
    const rowClass = colCount === 4
      ? 'businesses_datagrid_skeleton_row'
      : `businesses_datagrid_skeleton_row businesses_datagrid_skeleton_row--${colCount}`;
    const row = `<div class="skeleton ${rowClass}">${cell.repeat(colCount)}</div>`;
    return row.repeat(rowCount);
  };

  const setDatagridMessage = (container, message, isLoading = false) => {
    if (!(container instanceof HTMLElement)) {
      return;
    }

    const body = container.querySelector('.datagrid_body');
    if (!(body instanceof HTMLElement)) {
      return;
    }

    if (isLoading) {
      Guardian.setHTML(body, buildSkeletonRows(4, 4));
    } else {
      Guardian.setHTML(body, `<div class="datagrid_empty">${String(message || '')}</div>`);
    }
  };

  const setDiscoveryPanelStatus = (message) => {
    if (!elements.discoveryPanelStatus) {
      return;
    }

    elements.discoveryPanelStatus.textContent = String(message || '');
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

  const extractLookupEmail = (rawValue) => {
    const value = String(rawValue || '').trim();
    if (value === '') {
      return '';
    }

    const emailMatch = value.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i);
    if (!emailMatch) {
      return '';
    }

    return String(emailMatch[0] || '').trim().toLowerCase();
  };

  const renderAccessLookupOptions = (datalistEl, suggestions) => {
    if (!(datalistEl instanceof HTMLDataListElement)) {
      return;
    }

    Guardian.setHTML(datalistEl, '');

    suggestions.forEach((suggestion) => {
      const email = String(suggestion && suggestion.email ? suggestion.email : '').trim();
      if (email === '') {
        return;
      }

      const ownerName = String(suggestion && suggestion.name ? suggestion.name : '').trim();
      const businessName = String(suggestion && suggestion.business_name ? suggestion.business_name : '').trim();

      let value = ownerName === '' ? email : `${ownerName} <${email}>`;
      if (businessName !== '') {
        value = `${businessName} (${value})`;
      }

      const option = document.createElement('option');
      option.value = value;
      datalistEl.appendChild(option);
    });
  };

  const fetchAccessLookupSuggestions = async (query, options = {}) => {
    const params = new URLSearchParams();
    const trimmed = String(query || '').trim();
    if (trimmed !== '') {
      params.set('q', trimmed);
    }

    if (typeof options.mode === 'string' && options.mode.trim() !== '') {
      params.set('mode', options.mode.trim());
    }

    if (Number.isFinite(options.limit)) {
      params.set('limit', String(Math.max(1, Math.min(25, Number(options.limit)))));
    }

    const qs = params.toString();
    const endpoint = qs === ''
      ? '/api/v1/businesses/access/search'
      : `/api/v1/businesses/access/search?${qs}`;

    const payload = await apiRequest(endpoint, {
      timeoutMs: 12000,
    });

    return Array.isArray(payload.suggestions) ? payload.suggestions : [];
  };

  const safeText = (value) => {
    if (Guardian && typeof Guardian.sanitizedText === 'function') {
      return Guardian.sanitizedText(String(value ?? ''));
    }

    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  };

  const normalizeBrowserSuggestion = (suggestion) => {
    const email = String(suggestion?.email || '').trim().toLowerCase();
    const ownerName = String(suggestion?.name || '').trim();
    const businessName = String(suggestion?.business_name || '').trim();
    const key = `${businessName.toLowerCase()}|${email}`;

    return {
      key,
      email,
      ownerName,
      businessName,
      publicProfile: suggestion && typeof suggestion.public_profile === 'object' && suggestion.public_profile
        ? suggestion.public_profile
        : {},
      searchedAt: Date.now(),
    };
  };

  const setBrowserPanelStatus = (message) => {
    const text = String(message || '');
    if (elements.browserPanelStatus instanceof HTMLElement) {
      elements.browserPanelStatus.textContent = text;
    }
    if (elements.browserGridStatus instanceof HTMLElement) {
      elements.browserGridStatus.textContent = text;
    }
  };

  const renderBrowserGrid = (container, rows, emptyMessage) => {
    if (!(container instanceof HTMLElement)) {
      return;
    }

    const body = container.querySelector('.datagrid_body');
    if (!(body instanceof HTMLElement)) {
      return;
    }

    if (!Array.isArray(rows) || rows.length === 0) {
      setDatagridMessage(container, emptyMessage);
      return;
    }

    const cards = rows.map((row) => {
      const businessText = row.businessName === '' ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_UNKNOWN_NAME')); ?>' : row.businessName;
      const profile = row.publicProfile && typeof row.publicProfile === 'object' ? row.publicProfile : {};

      const city = String(profile.address_city || '').trim();
      const province = String(profile.address_region || '').trim();
      const countryRaw = String(profile.address_country || '').trim();
      const country = countryRaw === '' ? 'Canada' : countryRaw;
      const cityProvince = [city, province]
        .filter((part) => part !== '')
        .join(' ');
      const locationLine = [cityProvince, country]
        .filter((part) => part !== '')
        .join(', ');

      const industry = String(profile.industry || '').trim();
      const rawWebsite = String(profile.website || '').trim();
      const websiteText = rawWebsite.replace(/^https?:\/\//i, '').replace(/\/$/, '');
      const websiteHref = rawWebsite === ''
        ? ''
        : (/^https?:\/\//i.test(rawWebsite) ? rawWebsite : `https://${rawWebsite}`);
      const employeeCountRaw = String(profile.employee_count || '').trim();
      const employeeCount = /^\d+$/.test(employeeCountRaw)
        ? `${employeeCountRaw} ${employeeCountRaw === '1' ? 'employee' : 'employees'}`
        : employeeCountRaw;
      const supportHours = String(profile.support_hours || '').trim();
      const locationDisplay = locationLine === '' ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_LOCATION_UNAVAILABLE')); ?>' : locationLine;
      const ownerEmailDisplay = String(row.email || '').trim() === '' ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_EMAIL_AVAILABLE')); ?>' : String(row.email || '').trim();
      const industryDisplay = industry === '' ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_INDUSTRY_UNAVAILABLE')); ?>' : industry;
      const employeesDisplay = employeeCount === '' ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_EMPLOYEES_UNAVAILABLE')); ?>' : employeeCount;
      const websiteDisplay = websiteHref === ''
        ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_WEBSITE_UNAVAILABLE')); ?>'
        : `<a href="${safeText(websiteHref)}" target="_blank" rel="noopener noreferrer">${safeText(websiteText)}</a>`;
      const supportDisplay = supportHours === '' ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SUPPORT_HOURS_UNAVAILABLE')); ?>' : safeText(supportHours);

      return `
        <article class="businesses_browser_card">
          <section class="businesses_browser_data_grid">
            <p class="businesses_browser_cell businesses_browser_name businesses_browser_span_full">${safeText(businessText)}</p>
            <p class="businesses_browser_cell businesses_browser_location businesses_browser_span_full">${safeText(locationDisplay)}</p>
            <p class="businesses_browser_cell businesses_browser_owner_email businesses_browser_span_full">${safeText(ownerEmailDisplay)}</p>

            <p class="businesses_browser_cell businesses_browser_cell_label businesses_browser_website">${websiteDisplay}</p>
            <p class="businesses_browser_cell businesses_browser_cell_value businesses_browser_employees">${safeText(employeesDisplay)}</p>

            <p class="businesses_browser_cell businesses_browser_cell_label businesses_browser_industry">${safeText(industryDisplay)}</p>
            <p class="businesses_browser_cell businesses_browser_cell_value businesses_browser_support">${supportDisplay}</p>
          </section>
          <section class="businesses_browser_card_footer">
            <button
              type="button"
              class="btn btn_primary btn_sm businesses_browser_row_action"
              data-browser-action="connect"
              data-email="${safeText(row.email)}"
              data-org-name="${safeText(row.businessName)}"
              data-owner-name="${safeText(row.ownerName)}"
            ><?php echo addslashes(org_js_index_i18n('BUSINESSES_REQUEST_JOIN_BTN')); ?></button>
          </section>
        </article>
      `;
    }).join('');

    Guardian.setHTML(body, `<div class="businesses_browser_cards" aria-label="<?php echo addslashes(org_js_index_i18n('BUSINESSES_CARDS_ARIA')); ?>">${cards}</div>`);
  };

  const runBrowserSearch = async (query) => {
    const trimmed = String(query || '').trim();
    if (trimmed.length < ACCESS_LOOKUP_MIN_CHARS) {
      state.browserLastResults = [];
      renderBrowserGrid(elements.browserGrid, [], '<?php echo addslashes(org_js_index_i18n('BUSINESSES_BROWSER_MIN_CHARS')); ?>');
      setBrowserPanelStatus('<?php echo addslashes(org_js_index_i18n('BUSINESSES_BROWSER_MIN_CHARS')); ?>');
      return;
    }

    const suggestions = await fetchAccessLookupSuggestions(trimmed);
    const rows = suggestions
      .map((suggestion) => normalizeBrowserSuggestion(suggestion))
      .filter((row) => row.email !== '');

    state.browserLastResults = rows;

    if (rows.length === 0) {
      const noMatchMessage = T.browserNoMatchQuery.replace('%s', trimmed);
      renderBrowserGrid(elements.browserGrid, [], noMatchMessage);
      setBrowserPanelStatus('');
      return;
    }

    renderBrowserGrid(elements.browserGrid, rows, '<?php echo addslashes(org_js_index_i18n('BUSINESSES_BROWSER_NO_MATCHES')); ?>');

    setBrowserPanelStatus(
      T.browserFoundCount
        .replace('%d', String(rows.length))
        .replace('%s', rows.length === 1 ? '' : 's')
        .replace('%s', trimmed),
    );
  };

  const connectToBusinessFromBrowser = async (email, businessName = '', ownerName = '') => {
    const normalizedEmail = String(email || '').trim().toLowerCase();
    if (normalizedEmail === '') {
      PC.showToast('<?php echo addslashes(org_js_index_i18n('BUSINESSES_BROWSER_SELECT_VALID_CONTACT')); ?>', 'error', 5000, true);
      return;
    }

    await postForm('/api/v1/businesses/access/request', {
      owner_email: normalizedEmail,
    });

    const businessLabel = String(businessName || '').trim();
    const successMessage = businessLabel === ''
      ? `<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REQUEST_SUBMITTED_FOR')); ?>`.replace('%s', normalizedEmail)
      : `<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REQUEST_SUBMITTED_TO')); ?>`.replace('%s', businessLabel);

    if (elements.requestEmail instanceof HTMLInputElement) {
      elements.requestEmail.value = normalizedEmail;
    }

    setDiscoveryPanelStatus(successMessage);
    setBrowserPanelStatus(successMessage);
    PC.showToast(successMessage, 'save', 7000, true);
  };

  const initializeBusinessBrowser = () => {
    if (!(elements.browserGrid instanceof HTMLElement)) {
      return;
    }

    setBrowserPanelStatus('');
  };

  const bindAccessLookupInput = (inputEl, datalistEl) => {
    if (!(inputEl instanceof HTMLInputElement) || !(datalistEl instanceof HTMLDataListElement)) {
      return;
    }

    let debounceId = null;
    let requestSeq = 0;

    const runLookup = async () => {
      const query = String(inputEl.value || '').trim();
      if (query.length < ACCESS_LOOKUP_MIN_CHARS) {
        renderAccessLookupOptions(datalistEl, []);
        return;
      }

      requestSeq += 1;
      const mySeq = requestSeq;

      try {
        const suggestions = await fetchAccessLookupSuggestions(query);
        if (mySeq !== requestSeq) {
          return;
        }

        renderAccessLookupOptions(datalistEl, suggestions);
      } catch (_error) {
        if (mySeq !== requestSeq) {
          return;
        }

        renderAccessLookupOptions(datalistEl, []);
      }
    };

    inputEl.addEventListener('input', () => {
      if (debounceId !== null) {
        window.clearTimeout(debounceId);
      }

      debounceId = window.setTimeout(() => {
        runLookup().catch((error) => PW.error(error));
        debounceId = null;
      }, ACCESS_LOOKUP_DEBOUNCE_MS);
    });

    inputEl.addEventListener('focus', () => {
      const query = String(inputEl.value || '').trim();
      if (query.length >= ACCESS_LOOKUP_MIN_CHARS) {
        runLookup().catch((error) => PW.error(error));
      }
    });
  };

  const getPersonalPayAnchor = () => {
    if (elements.personalPayAnchor instanceof HTMLInputElement || elements.personalPayAnchor instanceof HTMLSelectElement) {
      return String(elements.personalPayAnchor.value || 'Monday');
    }

    return 'Monday';
  };

  const setPersonalPayAnchor = (value) => {
    if (elements.personalPayAnchor instanceof HTMLInputElement || elements.personalPayAnchor instanceof HTMLSelectElement) {
      elements.personalPayAnchor.value = value;
    }
  };

  const getPersonalPayPeriodStart = () => {
    if (elements.personalPayPeriodStart instanceof HTMLInputElement) {
      return String(elements.personalPayPeriodStart.value || '');
    }

    return '';
  };

  const setPersonalPayPeriodStart = (value) => {
    if (elements.personalPayPeriodStart instanceof HTMLInputElement) {
      elements.personalPayPeriodStart.value = value;
    }
  };

  const getPersonalEditingGraceDays = () => {
    const checkedRadio = Array.from(elements.personalEditingGraceDayRadios).find((radio) => radio instanceof HTMLInputElement && radio.checked);
    return checkedRadio instanceof HTMLInputElement ? String(checkedRadio.value || '0') : '0';
  };

  const setPersonalEditingGraceDays = (value) => {
    const normalizedValue = ['0', '1', '2', '3'].includes(String(value)) ? String(value) : '0';
    Array.from(elements.personalEditingGraceDayRadios).forEach((radio) => {
      if (radio instanceof HTMLInputElement) {
        radio.checked = radio.value === normalizedValue;
      }
    });
  };

  const isValidYmdDate = (value) => {
    const text = String(value || '').trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(text)) {
      return false;
    }

    const date = new Date(`${text}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
      return false;
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}` === text;
  };

  const setPersonalPayPeriodWarning = (message) => {
    if (!(elements.personalPayPeriodWarning instanceof HTMLElement)) {
      return;
    }

    const text = String(message || '').trim();
    elements.personalPayPeriodWarning.textContent = text;
    elements.personalPayPeriodWarning.classList.toggle('is-visible', text !== '');
  };

  const getPersonalPayPeriodValidationMessage = () => {
    const frequency = elements.personalPayFrequency instanceof HTMLSelectElement
      ? String(elements.personalPayFrequency.value || '').trim().toLowerCase()
      : 'biweekly';
    const allowedFrequencies = Object.keys(FREQUENCY_LENGTHS);
    if (!allowedFrequencies.includes(frequency)) {
      return T.ppInvalidFrequency;
    }

    const expectedLength = String(FREQUENCY_LENGTHS[frequency] || '14');
    const actualLength = elements.personalPayPeriodLength instanceof HTMLInputElement
      ? String(elements.personalPayPeriodLength.value || '').trim()
      : expectedLength;
    if (actualLength !== expectedLength) {
      return T.ppInvalidLength
        .replace('%s', frequency)
        .replace('%s', expectedLength);
    }

    const anchor = getPersonalPayAnchor();
    if (!(anchor in PAY_PERIOD_WEEKDAY_MAP)) {
      return T.ppInvalidAnchor;
    }

    const graceRaw = getPersonalEditingGraceDays();
    const graceValue = parseInt(graceRaw, 10);
    const allowedGraceValues = Array.from(elements.personalEditingGraceDayRadios)
      .filter((radio) => radio instanceof HTMLInputElement)
      .map((radio) => parseInt(String(radio.value || ''), 10))
      .filter((value) => Number.isFinite(value));
    const graceMin = allowedGraceValues.length > 0 ? Math.min(...allowedGraceValues) : 0;
    const graceMax = allowedGraceValues.length > 0 ? Math.max(...allowedGraceValues) : 3;
    if (!Number.isFinite(graceValue) || graceValue < graceMin || graceValue > graceMax) {
      return T.ppInvalidGrace
        .replace('%d', String(graceMin))
        .replace('%d', String(graceMax));
    }

    const payPeriodStart = getPersonalPayPeriodStart();
    if ((frequency === 'weekly' || frequency === 'biweekly') && payPeriodStart === '') {
      return T.ppSelectStartDate;
    }

    if (payPeriodStart !== '' && !isValidYmdDate(payPeriodStart)) {
      return T.ppInvalidStartDate;
    }

    return '';
  };

  const refreshPersonalPayPeriodValidation = () => {
    const message = getPersonalPayPeriodValidationMessage();
    setPersonalPayPeriodWarning(message);
    return message === '';
  };

  const toTitleLabel = (value, fallback = '') => {
    const source = String(value || '').trim();
    if (source === '') {
      return fallback;
    }

    return source
      .replace(/[_-]+/g, ' ')
      .split(/\s+/)
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase())
      .join(' ');
  };

  const setSelectValueSafe = (selectEl, rawValue, fallback = '') => {
    if (!(selectEl instanceof HTMLSelectElement)) {
      return;
    }

    const normalized = String(rawValue || fallback || '').trim().toLowerCase();
    if (normalized === '') {
      return;
    }

    const hasOption = Array.from(selectEl.options).some((option) => option.value === normalized);
    if (!hasOption) {
      const fallbackLabel = toTitleLabel(normalized, normalized);
      const option = new Option(fallbackLabel, normalized);
      selectEl.add(option);
    }

    selectEl.value = normalized;
  };

  const isPersonalBusinessById = (businessId) => {
    const business = findBusiness(businessId);
    return !!business && isPersonalBusiness(business);
  };

  const getPersonalBusiness = () => {
    return state.businesses.find((business) => isPersonalBusiness(business)) || null;
  };

  const syncPersonalFrequency = () => {
    if (!(elements.personalPayFrequency instanceof HTMLSelectElement)) {
      if (elements.personalPayPeriodLength instanceof HTMLInputElement) {
        elements.personalPayPeriodLength.value = FREQUENCY_LENGTHS.biweekly;
      }
      return FREQUENCY_LENGTHS.biweekly;
    }

    const nextLength = FREQUENCY_LENGTHS[elements.personalPayFrequency.value] || FREQUENCY_LENGTHS.biweekly;
    if (elements.personalPayPeriodLength instanceof HTMLInputElement) {
      elements.personalPayPeriodLength.value = nextLength;
    }

    return nextLength;
  };

  const renderPersonalPreview = () => {
    if (!elements.personalPreview) {
      return;
    }

    const frequency = elements.personalPayFrequency instanceof HTMLSelectElement
      ? elements.personalPayFrequency.value
      : 'biweekly';

    const startRaw = getPersonalPayPeriodStart();
    const anchor = getPersonalPayAnchor();
    const graceDays = getPersonalEditingGraceDays();

    const parseYmd = (ymd) => new Date(`${ymd}T00:00:00`);
    const ppAddDays = (date, days) => new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
    const ppFormatYmd = (date) => {
      const y = date.getFullYear();
      const m = String(date.getMonth() + 1).padStart(2, '0');
      const d = String(date.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    };
    const alignToAnchor = (start, anchorDay) => {
      const target = PAY_PERIOD_WEEKDAY_MAP[anchorDay] ?? 1;
      let cursor = new Date(start.getFullYear(), start.getMonth(), start.getDate());
      while (cursor.getDay() !== target) {
        cursor = ppAddDays(cursor, -1);
      }
      return cursor;
    };
    const nextPeriod = (start, periodFrequency) => {
      if (periodFrequency === 'weekly') {
        return ppAddDays(start, 7);
      }
      if (periodFrequency === 'biweekly') {
        return ppAddDays(start, 14);
      }
      if (periodFrequency === 'semimonthly') {
        if (start.getDate() <= 15) {
          return new Date(start.getFullYear(), start.getMonth(), 16);
        }
        return new Date(start.getFullYear(), start.getMonth() + 1, 1);
      }

      return new Date(start.getFullYear(), start.getMonth() + 1, 1);
    };
    const currentPeriod = (startRaw, periodFrequency, anchorDay) => {
      const now = new Date();
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

      if (periodFrequency === 'weekly') {
        const start = alignToAnchor(today, anchorDay);
        return { start, endExclusive: ppAddDays(start, 7) };
      }
      if (periodFrequency === 'biweekly') {
        // Keep the user-selected cadence, but align to the currently selected anchor day.
        // This makes anchor changes reflect immediately without requiring an extra calendar click.
        const start = alignToAnchor(parseYmd(startRaw), anchorDay);
        return { start, endExclusive: ppAddDays(start, 14) };
      }
      if (periodFrequency === 'semimonthly') {
        if (today.getDate() <= 15) {
          const start = new Date(today.getFullYear(), today.getMonth(), 1);
          return { start, endExclusive: new Date(today.getFullYear(), today.getMonth(), 16) };
        }

        const start = new Date(today.getFullYear(), today.getMonth(), 16);
        return { start, endExclusive: new Date(today.getFullYear(), today.getMonth() + 1, 1) };
      }

      const start = new Date(today.getFullYear(), today.getMonth(), 1);
      return { start, endExclusive: new Date(today.getFullYear(), today.getMonth() + 1, 1) };
    };
    const startOfWeek = (date) => ppAddDays(date, -date.getDay());
    const inRange = (date, start, endExclusive) => date >= start && date < endExclusive;
    const monthLabel = (date) => date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
    const buildRibbonCalendar = (periods, grace, today) => {
      const stripbar = PAY_PERIOD_DAY_NAMES.map((day) => `<span class="pp_day_head">${day}</span>`).join('');
      const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
      const gridStart = startOfWeek(firstOfMonth);
      const badgesPlaced = { p1: false, p2: false };
      let bodyRows = '';

      for (let week = 0; week < 6; week += 1) {
        bodyRows += '<tr>';
        for (let day = 0; day < 7; day += 1) {
          const offset = (week * 7) + day;
          const cellDate = ppAddDays(gridStart, offset);
          const isToday = ppFormatYmd(cellDate) === ppFormatYmd(today);
          const classes = ['pp_day_cell'];
          let badge = '';

          periods.forEach((period, index) => {
            const periodKey = index === 0 ? 'p1' : 'p2';
            const prevDate = ppAddDays(cellDate, -1);
            const nextDate = ppAddDays(cellDate, 1);
            const active = inRange(cellDate, period.start, period.endExclusive);
            const prevActive = inRange(prevDate, period.start, period.endExclusive);
            const nextActive = inRange(nextDate, period.start, period.endExclusive);
            const graceStart = period.endExclusive;
            const graceEndExclusive = ppAddDays(graceStart, grace);
            const graceActive = inRange(cellDate, graceStart, graceEndExclusive);

            if (active) {
              classes.push('pp_in_period', `pp_in_${periodKey}`);
              if (!prevActive) {
                classes.push(`pp_ribbon_start_${periodKey}`);
              }
              if (!nextActive) {
                classes.push(`pp_ribbon_end_${periodKey}`);
              }
              if (!badgesPlaced[periodKey]) {
                badge = `<span class="pp_badge ${periodKey === 'p2' ? 'pp_badge_p2' : ''}">${period.label}</span>`;
                badgesPlaced[periodKey] = true;
              }
            }

            if (graceActive && grace > 0) {
              const graceIndex = Math.min(grace, Math.max(1, Math.floor((cellDate - graceStart) / 86400000) + 1));
              classes.push('pp_grace_day', `pp_grace_${graceIndex}`, `pp_grace_${periodKey}`);
            }
          });

          if (isToday) {
            classes.push('pp_today');
          }

          bodyRows += `<td class="${classes.join(' ')}" data-ymd="${ppFormatYmd(cellDate)}" tabindex="0"><span class="pp_day_number">${String(cellDate.getDate()).padStart(2, '0')}</span>${badge}</td>`;
        }
        bodyRows += '</tr>';
      }

      return `
        <div class="pp_month_label">${monthLabel(today)}</div>
        <div class="pp_stripbar">${stripbar}</div>
        <table class="pp_three_week">
          <tbody>${bodyRows}</tbody>
        </table>
      `;
    };

    // For biweekly with no stored start, seed from the anchor-aligned date closest to today
    // so the calendar renders and the user can click to set their real period start.
    // alignToAnchor is defined above by this point.
    const now2 = new Date();
    const today2 = new Date(now2.getFullYear(), now2.getMonth(), now2.getDate());
    const startValue = startRaw !== '' ? startRaw : ppFormatYmd(alignToAnchor(today2, anchor));

    const period1 = currentPeriod(startValue, frequency, anchor);
    const period2 = {
      start: period1.endExclusive,
      endExclusive: nextPeriod(period1.endExclusive, frequency),
    };
    const endInclusive1 = ppAddDays(period1.endExclusive, -1);
    const endInclusive2 = ppAddDays(period2.endExclusive, -1);
    const periods = [
      { label: 'P1', start: period1.start, endExclusive: period1.endExclusive },
      { label: 'P2', start: period2.start, endExclusive: period2.endExclusive },
    ];
    const today = new Date();
    const graceDaysInt = parseInt(graceDays, 10);
    const safeGraceDays = Number.isNaN(graceDaysInt) ? 0 : graceDaysInt;
    const previewSignature = [
      frequency,
      startValue,
      anchor,
      String(safeGraceDays),
      ppFormatYmd(today),
    ].join('|');

    if (state.personalPreviewSignature === previewSignature) {
      return;
    }

    state.personalPreviewSignature = previewSignature;

    Guardian.setHTML(elements.personalPreview, `
      ${buildRibbonCalendar(periods, safeGraceDays, today)}
      <div class="pp_preview_summary"><span class="pp_preview_summary_item">P1: ${ppFormatYmd(period1.start)} to ${ppFormatYmd(endInclusive1)}</span><span class="pp_preview_summary_item">P2: ${ppFormatYmd(period2.start)} to ${ppFormatYmd(endInclusive2)}</span></div>
    `);
  };

  const schedulePersonalPreviewRender = () => {
    if (state.personalPreviewRafId !== null) {
      return;
    }

    state.personalPreviewRafId = window.requestAnimationFrame(() => {
      state.personalPreviewRafId = null;
      renderPersonalPreview();
    });
  };

  const displayCurrencyValue = (searchEl, code) => {
    if (!(searchEl instanceof HTMLInputElement)) return;
    const entry = (code && CURRENCY_LIST[code]) ? CURRENCY_LIST[code] : null;
    searchEl.value = entry ? `${entry.code} \u2014 ${entry.name}` : (code || '');
  };

  const initCurrencyFinder = (searchId, hiddenId, listboxId, wrapperId) => {
    const searchEl = document.getElementById(searchId);
    const hiddenEl = document.getElementById(hiddenId);
    const listboxEl = document.getElementById(listboxId);
    const wrapperEl = document.getElementById(wrapperId);
    if (!(searchEl instanceof HTMLInputElement) || !(hiddenEl instanceof HTMLInputElement) || !listboxEl || !wrapperEl) return;

    let activeIndex = -1;

    const closeList = () => {
      listboxEl.hidden = true;
      wrapperEl.setAttribute('aria-expanded', 'false');
      activeIndex = -1;
    };

    const setActive = (index) => {
      const items = Array.from(listboxEl.querySelectorAll('.currency_finder_item'));
      items.forEach((item, i) => {
        const on = i === index;
        item.setAttribute('aria-selected', on ? 'true' : 'false');
        item.classList.toggle('currency_finder_item_active', on);
      });
      if (items[index]) items[index].scrollIntoView({ block: 'nearest' });
      activeIndex = index;
    };

    const selectCode = (code) => {
      hiddenEl.value = code;
      displayCurrencyValue(searchEl, code);
      closeList();
      const currency = CURRENCY_LIST[code] || null;
      const label = currency ? `${currency.code} - ${currency.name}` : code;
      PC.showToast(T.currencyUpdatedLabel.replace('%s', label), 'save');
      hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const buildList = (query) => {
      const q = query.toLowerCase().trim();
      const matches = Object.values(CURRENCY_LIST).filter((c) =>
        q === '' ||
        c.code.toLowerCase().includes(q) ||
        c.name.toLowerCase().includes(q) ||
        c.countries.toLowerCase().includes(q)
      ).slice(0, 60);
      if (matches.length === 0) { closeList(); return; }
      const html = matches.map((c, i) =>
        `<li class="currency_finder_item" role="option" id="${listboxId}_item_${i}" data-code="${c.code}" aria-selected="false" tabindex="-1">` +
        `<span class="currency_finder_code">${c.code}</span>` +
        `<span class="currency_finder_symbol">${c.symbol}</span>` +
        `<span class="currency_finder_name">${c.name}</span>` +
        `</li>`
      ).join('');
      Guardian.setHTML(listboxEl, html);
      activeIndex = -1;
      listboxEl.hidden = false;
      wrapperEl.setAttribute('aria-expanded', 'true');
      listboxEl.querySelectorAll('.currency_finder_item').forEach((item) => {
        item.addEventListener('mousedown', (e) => {
          e.preventDefault();
          const code = String(item.getAttribute('data-code') || '');
          if (code) selectCode(code);
        });
      });
    };

    searchEl.addEventListener('input', () => buildList(searchEl.value));
    searchEl.addEventListener('focus', () => {
      const currentCode = hiddenEl.value || '';
      buildList(currentCode && CURRENCY_LIST[currentCode] ? '' : searchEl.value);
    });
    searchEl.addEventListener('blur', () => setTimeout(closeList, 160));
    searchEl.addEventListener('keydown', (e) => {
      const items = Array.from(listboxEl.querySelectorAll('.currency_finder_item'));
      const pageStep = 10;
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActive(Math.min(activeIndex + 1, items.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActive(Math.max(activeIndex - 1, 0));
      } else if (e.key === 'Home') {
        e.preventDefault();
        if (items.length > 0) {
          setActive(0);
        }
      } else if (e.key === 'End') {
        e.preventDefault();
        if (items.length > 0) {
          setActive(items.length - 1);
        }
      } else if (e.key === 'PageDown') {
        e.preventDefault();
        if (items.length > 0) {
          const nextIndex = activeIndex < 0
            ? Math.min(pageStep - 1, items.length - 1)
            : Math.min(activeIndex + pageStep, items.length - 1);
          setActive(nextIndex);
        }
      } else if (e.key === 'PageUp') {
        e.preventDefault();
        if (items.length > 0) {
          const nextIndex = activeIndex < 0
            ? 0
            : Math.max(activeIndex - pageStep, 0);
          setActive(nextIndex);
        }
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activeIndex >= 0 && items[activeIndex]) {
          const code = String(items[activeIndex].getAttribute('data-code') || '');
          if (code) selectCode(code);
        }
      } else if (e.key === 'Escape') {
        closeList();
        displayCurrencyValue(searchEl, hiddenEl.value);
      }
    });
  };

  const displayTimezoneValue = (searchEl, value) => {
    if (!(searchEl instanceof HTMLInputElement)) return;
    const zone = String(value || '');
    if (zone === '') {
      searchEl.value = '';
      return;
    }
    const meta = TIMEZONE_MAP[zone] || null;
    searchEl.value = meta ? meta.label : zone;
  };

  const initTimezoneFinder = (searchId, hiddenId, listboxId, wrapperId) => {
    const searchEl = document.getElementById(searchId);
    const hiddenEl = document.getElementById(hiddenId);
    const listboxEl = document.getElementById(listboxId);
    const wrapperEl = document.getElementById(wrapperId);
    if (!(searchEl instanceof HTMLInputElement) || !(hiddenEl instanceof HTMLInputElement) || !listboxEl || !wrapperEl) return;

    let activeIndex = -1;

    const closeList = () => {
      listboxEl.hidden = true;
      wrapperEl.setAttribute('aria-expanded', 'false');
      activeIndex = -1;
    };

    const setActive = (index) => {
      const items = Array.from(listboxEl.querySelectorAll('.timezone_finder_item'));
      items.forEach((item, i) => {
        const on = i === index;
        item.setAttribute('aria-selected', on ? 'true' : 'false');
        item.classList.toggle('timezone_finder_item_active', on);
      });
      if (items[index]) items[index].scrollIntoView({ block: 'nearest' });
      activeIndex = index;
    };

    const selectZone = (zone) => {
      hiddenEl.value = zone;
      displayTimezoneValue(searchEl, zone);
      closeList();
      const meta = TIMEZONE_MAP[zone] || null;
      const label = meta ? `${zone} [UTC${meta.offsetNow}]` : zone;
      PC.showToast(T.timezoneUpdatedLabel.replace('%s', label), 'save');
      hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const buildList = (query) => {
      const q = query.toLowerCase().trim();
      const matches = TIMEZONE_META.filter((item) => q === '' || item.searchable.includes(q)).slice(0, 80);
      if (matches.length === 0) {
        closeList();
        return;
      }
      const html = matches.map((item, i) =>
        `<li class="timezone_finder_item" role="option" id="${listboxId}_item_${i}" data-zone="${item.zone}" aria-selected="false" tabindex="-1">` +
        `<span class="timezone_finder_name">${item.zone}</span>` +
        `<span class="timezone_finder_offset">[UTC${item.offsetNow}]</span>` +
        `<span class="timezone_finder_abbr">${item.abbreviations.join('/')}</span>` +
        `</li>`
      ).join('');
      Guardian.setHTML(listboxEl, html);
      activeIndex = -1;
      listboxEl.hidden = false;
      wrapperEl.setAttribute('aria-expanded', 'true');
      listboxEl.querySelectorAll('.timezone_finder_item').forEach((item) => {
        item.addEventListener('mousedown', (e) => {
          e.preventDefault();
          const zone = String(item.getAttribute('data-zone') || '');
          if (zone) selectZone(zone);
        });
      });
    };

    searchEl.addEventListener('input', () => buildList(searchEl.value));
    searchEl.addEventListener('focus', () => {
      buildList(searchEl.value);
    });
    searchEl.addEventListener('blur', () => setTimeout(closeList, 160));
    searchEl.addEventListener('keydown', (e) => {
      const items = Array.from(listboxEl.querySelectorAll('.timezone_finder_item'));
      const pageStep = 10;
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActive(Math.min(activeIndex + 1, items.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActive(Math.max(activeIndex - 1, 0));
      } else if (e.key === 'Home') {
        e.preventDefault();
        if (items.length > 0) {
          setActive(0);
        }
      } else if (e.key === 'End') {
        e.preventDefault();
        if (items.length > 0) {
          setActive(items.length - 1);
        }
      } else if (e.key === 'PageDown') {
        e.preventDefault();
        if (items.length > 0) {
          const nextIndex = activeIndex < 0
            ? Math.min(pageStep - 1, items.length - 1)
            : Math.min(activeIndex + pageStep, items.length - 1);
          setActive(nextIndex);
        }
      } else if (e.key === 'PageUp') {
        e.preventDefault();
        if (items.length > 0) {
          const nextIndex = activeIndex < 0
            ? 0
            : Math.max(activeIndex - pageStep, 0);
          setActive(nextIndex);
        }
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activeIndex >= 0 && items[activeIndex]) {
          const zone = String(items[activeIndex].getAttribute('data-zone') || '');
          if (zone) selectZone(zone);
        }
      } else if (e.key === 'Escape') {
        closeList();
        displayTimezoneValue(searchEl, hiddenEl.value);
      }
    });
  };

  const loadPersonalBusinessPanel = async () => {
    const panel = document.getElementById('panel-pay-period');
    let settings = {};
    try {
      const raw = typeof panel?.dataset.userSettings === 'string' ? panel.dataset.userSettings : '';
      if (raw !== '') {
        settings = JSON.parse(raw);
      }
    } catch (_err) {
      PW.error(_err);
    }

    const ownedShared = getOwnedSharedBusiness();
    state.profilePayPeriodManagedByBusiness = ownedShared !== null;
    updateProfilePayPeriodManagedBanner(ownedShared);
    setProfilePayPeriodControlsLocked(state.profilePayPeriodManagedByBusiness);

    if (ownedShared) {
      const businessId = String(ownedShared.business_id || '').trim();
      if (businessId !== '') {
        try {
          const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/settings`);
          const orgSettings = payload && typeof payload.settings === 'object' && payload.settings
            ? payload.settings
            : null;
          if (orgSettings) {
            settings = {
              ...settings,
              pay_frequency: String(orgSettings.pay_frequency || settings.pay_frequency || 'biweekly'),
              pay_anchor: String(orgSettings.pay_anchor || settings.pay_anchor || 'Monday'),
              pay_period_start: String(orgSettings.pay_period_start || settings.pay_period_start || ''),
              pay_period_length: String(orgSettings.pay_period_length || settings.pay_period_length || '14'),
              editing_grace_days: String(orgSettings.editing_grace_days || settings.editing_grace_days || '0'),
              pay_rate: String(orgSettings.default_wage || settings.pay_rate || ''),
              timezone: String(orgSettings.timezone || settings.timezone || 'America/Edmonton'),
              currency: String(orgSettings.currency || settings.currency || 'CAD'),
            };
          }
        } catch (error) {
          PW.error(error);
        }
      }
    }

    applyPersonalPayPeriodSettings(settings);
  };

  const applyPersonalPayPeriodSettings = (settings) => {
    if (!settings || typeof settings !== 'object') {
      settings = {};
    }

    if (elements.personalDefaultWage instanceof HTMLInputElement) {
      elements.personalDefaultWage.value = String(settings.pay_rate || '');
    }
    if (elements.personalTimezone instanceof HTMLInputElement) {
      const tz = String(settings.timezone || '');
      elements.personalTimezone.value = tz;
      displayTimezoneValue(elements.personalTimezoneSearch, tz);
    }
    if (elements.personalCurrency instanceof HTMLInputElement) {
      const cur = String(settings.currency || '');
      elements.personalCurrency.value = cur;
      displayCurrencyValue(elements.personalCurrencySearch, cur);
    }
    if (elements.personalLanguage instanceof HTMLSelectElement) {
      elements.personalLanguage.value = String(settings.language || 'en');
    }
    if (elements.personalLocale instanceof HTMLSelectElement) {
      const locale = String(settings.locale || 'en-CA');
      elements.personalLocale.value = locale;
    }
    if (elements.personalPayFrequency instanceof HTMLSelectElement) {
      elements.personalPayFrequency.value = String(settings.pay_frequency || 'biweekly');
    }
    setPersonalPayAnchor(String(settings.pay_anchor || 'Monday'));
    setPersonalPayPeriodStart(String(settings.pay_period_start || ''));
    if (elements.personalPayPeriodLength instanceof HTMLInputElement) {
      elements.personalPayPeriodLength.value = String(settings.pay_period_length || syncPersonalFrequency());
    }
    setPersonalEditingGraceDays(String(settings.editing_grace_days || '0'));
    state.personalEditingGraceDaysValue = getPersonalEditingGraceDays();

    syncPersonalFrequency();
    refreshPersonalPayPeriodValidation();
    schedulePersonalPreviewRender();
    syncPersonalWageCurrencyAdornment();
    syncProfilePhoneCountryAdornment();
    renderPersonalInternationalizationPreview();

    const bootFormData = buildPersonalSettingsFormData();
    state.personalLastSavedSignature = [
      bootFormData.get('pay_frequency'),
      bootFormData.get('pay_anchor'),
      bootFormData.get('pay_period_start'),
      bootFormData.get('pay_period_length'),
      bootFormData.get('editing_grace_days'),
      bootFormData.get('pay_rate'),
      bootFormData.get('timezone'),
      bootFormData.get('currency'),
      bootFormData.get('language'),
      bootFormData.get('locale'),
    ].join('|');
  };

  const updateProfilePayPeriodManagedBanner = (business) => {
    const panel = document.getElementById('panel-pay-period');
    const banner = document.getElementById('profile_pay_period_managed_banner');
    if (!(panel instanceof HTMLElement) || !(banner instanceof HTMLElement)) {
      return;
    }

    if (!business) {
      panel.classList.remove('is-managed-by-business');
      panel.removeAttribute('data-pay-period-managed');
      banner.hidden = true;
      return;
    }

    const businessName = String(business.name || '').trim() || T.nounBusiness;
    const payrollHref = String(business.payroll_href || panel.dataset.managedPayrollHref || '/business/payroll/').trim() || '/business/payroll/';

    panel.classList.add('is-managed-by-business');
    panel.dataset.payPeriodManaged = 'true';
    panel.dataset.managedBusinessId = String(business.business_id || '');
    panel.dataset.managedBusinessName = businessName;
    panel.dataset.managedPayrollHref = payrollHref;
    banner.hidden = false;

    const lede = banner.querySelector('.profile_pay_period_managed_banner_lede');
    if (lede instanceof HTMLElement) {
      lede.textContent = T.profilePayPeriodManagedBanner.replace('%s', businessName);
    }

    const help = banner.querySelector('.profile_pay_period_managed_banner_help');
    if (help instanceof HTMLElement) {
      help.textContent = T.profilePayPeriodManagedHelp;
    }

    const link = banner.querySelector('.profile_pay_period_managed_banner_action a');
    if (link instanceof HTMLAnchorElement) {
      link.href = payrollHref;
      link.textContent = T.profilePayPeriodManagedLink;
    }
  };

  const setProfilePayPeriodControlsLocked = (locked) => {
    const controls = [
      elements.personalPayFrequency,
      elements.personalPayPeriodLength,
      ...Array.from(elements.personalEditingGraceDayRadios),
    ];

    controls.forEach((control) => {
      if (control instanceof HTMLInputElement || control instanceof HTMLSelectElement) {
        control.disabled = locked;
      }
    });

    if (elements.personalPreview instanceof HTMLElement) {
      elements.personalPreview.classList.toggle('is-read-only', locked);
    }
  };

  const syncPersonalWageCurrencyAdornment = () => {
    if (!(elements.personalDefaultWage instanceof HTMLInputElement)) {
      return;
    }

    const input = elements.personalDefaultWage;
    const parent = input.parentElement;
    if (!(parent instanceof HTMLElement)) {
      return;
    }

    let shell = parent.querySelector('.personal_wage_input_shell');
    if (!(shell instanceof HTMLElement)) {
      shell = document.createElement('div');
      shell.className = 'personal_wage_input_shell';
      parent.insertBefore(shell, input);
      shell.appendChild(input);
    }

    let symbolEl = shell.querySelector('.personal_wage_currency_symbol');
    if (!(symbolEl instanceof HTMLElement)) {
      symbolEl = document.createElement('span');
      symbolEl.className = 'personal_wage_currency_symbol';
      symbolEl.setAttribute('aria-hidden', 'true');
      shell.insertBefore(symbolEl, input);
    }

    const currencyCode = elements.personalCurrency instanceof HTMLInputElement
      ? String(elements.personalCurrency.value || 'CAD').trim().toUpperCase()
      : 'CAD';
    const currencyMeta = CURRENCY_LIST[currencyCode] || null;
    const symbol = currencyMeta && String(currencyMeta.symbol || '').trim() !== ''
      ? String(currencyMeta.symbol)
      : '$';

    symbolEl.textContent = symbol;
  };

  const resolveDialCodeFromLocale = (localeValue) => {
    const locale = String(localeValue || '').trim();
    const dialCodesByLocale = {
      'en-CA': '+1',
      'fr-CA': '+1',
      'en-US': '+1',
      'en-GB': '+44',
      'fr-FR': '+33',
      'de-DE': '+49',
      'es-ES': '+34',
      'pt-BR': '+55',
    };

    return dialCodesByLocale[locale] || '+1';
  };

  const syncProfilePhoneCountryAdornment = () => {
    const phoneInput = document.getElementById('edit_details_phone');
    if (!(phoneInput instanceof HTMLInputElement)) {
      return;
    }

    const parent = phoneInput.parentElement;
    if (!(parent instanceof HTMLElement)) {
      return;
    }

    let shell = parent.querySelector('.personal_phone_input_shell');
    if (!(shell instanceof HTMLElement)) {
      shell = document.createElement('div');
      shell.className = 'personal_phone_input_shell';
      parent.insertBefore(shell, phoneInput);
      shell.appendChild(phoneInput);
    }

    let dialCodeEl = shell.querySelector('.personal_phone_country_code');
    if (!(dialCodeEl instanceof HTMLElement)) {
      dialCodeEl = document.createElement('span');
      dialCodeEl.className = 'personal_phone_country_code';
      dialCodeEl.setAttribute('aria-hidden', 'true');
      shell.insertBefore(dialCodeEl, phoneInput);
    }

    const locale = elements.personalLocale instanceof HTMLSelectElement
      ? String(elements.personalLocale.value || 'en-CA')
      : 'en-CA';
    dialCodeEl.textContent = resolveDialCodeFromLocale(locale);
  };

  const renderPersonalInternationalizationPreview = () => {
    if (!(elements.personalI18nPreview instanceof HTMLElement)) {
      return;
    }

    const locale = elements.personalLocale instanceof HTMLSelectElement
      ? String(elements.personalLocale.value || 'en-CA')
      : 'en-CA';
    const timeZone = elements.personalTimezone instanceof HTMLInputElement
      ? String(elements.personalTimezone.value || 'UTC')
      : 'UTC';
    const currency = elements.personalCurrency instanceof HTMLInputElement
      ? String(elements.personalCurrency.value || 'CAD').toUpperCase()
      : 'CAD';
    const language = elements.personalLanguage instanceof HTMLSelectElement
      ? String(elements.personalLanguage.value || 'en')
      : 'en';
    const languageLabel = elements.personalLanguage instanceof HTMLSelectElement
      ? String(elements.personalLanguage.options[elements.personalLanguage.selectedIndex]?.text || language)
      : language;

    const sampleDate = new Date(Date.UTC(2026, 3, 13, 20, 45, 0));

    let numberSample = '';
    let currencySample = '';
    let dateSample = '';
    try {
      numberSample = new Intl.NumberFormat(locale, {
        useGrouping: true,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(45977.2);
      currencySample = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
      }).format(45977.2);
      dateSample = new Intl.DateTimeFormat(locale, {
        dateStyle: 'full',
        timeStyle: 'short',
        timeZone,
      }).format(sampleDate);
    } catch (_error) {
      numberSample = '45,977.20';
      currencySample = '$45,977.20';
      dateSample = sampleDate.toISOString();
    }

    Guardian.setHTML(elements.personalI18nPreview, `
      <div class="profile_i18n_preview_rows">
        <div><strong>Language:</strong> ${escapeHtml(languageLabel)} (${escapeHtml(language)})</div>
        <div><strong>Locale:</strong> ${escapeHtml(locale)}</div>
        <div><strong>Timezone:</strong> ${escapeHtml(timeZone)}</div>
        <div><strong>Currency:</strong> ${escapeHtml(currency)}</div>
        <div><strong>Number:</strong> ${escapeHtml(numberSample)}</div>
        <div><strong>Money:</strong> ${escapeHtml(currencySample)}</div>
        <div><strong>Date + Time:</strong> ${escapeHtml(dateSample)}</div>
      </div>
    `);
  };

  const buildPersonalSettingsFormData = () => {
    const formData = new FormData();

    const settingsCsrf = String(
      (document.getElementById('settings_csrf_token') instanceof HTMLInputElement
        ? (/** @type {HTMLInputElement} */ (document.getElementById('settings_csrf_token'))).value
        : '') || ''
    ).trim();
    if (settingsCsrf !== '') {
      formData.set('csrf_token', settingsCsrf);
    }

    formData.set('pay_frequency', elements.personalPayFrequency instanceof HTMLSelectElement ? elements.personalPayFrequency.value : 'biweekly');
    formData.set('pay_anchor', getPersonalPayAnchor());
    formData.set('pay_period_start', getPersonalPayPeriodStart());
    formData.set('pay_period_length', String(syncPersonalFrequency()));
    formData.set('editing_grace_days', getPersonalEditingGraceDays());
    formData.set('pay_rate', elements.personalDefaultWage instanceof HTMLInputElement ? elements.personalDefaultWage.value.trim() : '');
    formData.set('timezone', elements.personalTimezone instanceof HTMLInputElement ? elements.personalTimezone.value.trim() : '');
    formData.set('currency', elements.personalCurrency instanceof HTMLInputElement ? elements.personalCurrency.value.trim() : '');
    formData.set('language', elements.personalLanguage instanceof HTMLSelectElement ? elements.personalLanguage.value.trim() : '');
    formData.set('locale', elements.personalLocale instanceof HTMLSelectElement ? elements.personalLocale.value.trim() : '');

    return formData;
  };

  /**
   * Save personal pay/profile settings with request de-duplication.
   *
   * Plain-language behavior:
   * 1) Build the payload from current form values.
   * 2) Skip saving if nothing changed.
   * 3) If a save is already running, queue one final save with the latest values.
   */
  const PAY_PERIOD_SAVE_SOURCES = new Set(['frequency', 'anchor', 'grace', 'calendar-day']);

  const savePersonalBusinessSettings = async (source = 'auto') => {
    const payPeriodManaged = state.profilePayPeriodManagedByBusiness;

    if (payPeriodManaged && PAY_PERIOD_SAVE_SOURCES.has(source)) {
      return;
    }

    const payPeriodValid = refreshPersonalPayPeriodValidation();
    if (PAY_PERIOD_SAVE_SOURCES.has(source) && !payPeriodValid) {
      return;
    }

    const formData = buildPersonalSettingsFormData();
    if (!payPeriodValid || payPeriodManaged) {
      ['pay_frequency', 'pay_anchor', 'pay_period_start', 'pay_period_length', 'editing_grace_days'].forEach((field) => {
        formData.delete(field);
      });
    }

    const previousLanguage = String(document.documentElement.lang || '').trim().toLowerCase();

    // Build a stable signature of the current values for dedup.
    const payloadSignature = [
      formData.get('pay_frequency'),
      formData.get('pay_anchor'),
      formData.get('pay_period_start'),
      formData.get('pay_period_length'),
      formData.get('editing_grace_days'),
      formData.get('pay_rate'),
      formData.get('timezone'),
      formData.get('currency'),
      formData.get('language'),
      formData.get('locale'),
    ].join('|');

    if (state.personalSaveInFlight) {
      state.personalSavePendingSource = source;
      state.personalPendingSignature = payloadSignature;
      return;
    }

    if (state.personalLastSavedSignature === payloadSignature) {
      return;
    }

    state.personalSaveInFlight = true;

    const savingMessage = source === 'calendar-day'
      ? T.payPeriodSavingStart
      : T.profileSettingsSaving;
    PC.showToast(savingMessage, 'save');

    try {
        // Debug: log what we're sending
        const debugPayload = {
          csrf_token: formData.get('csrf_token') ? '***' : 'MISSING',
          pay_frequency: formData.get('pay_frequency'),
          pay_anchor: formData.get('pay_anchor'),
          pay_period_start: formData.get('pay_period_start'),
          pay_period_length: formData.get('pay_period_length'),
          editing_grace_days: formData.get('editing_grace_days'),
          pay_rate: formData.get('pay_rate'),
          timezone: formData.get('timezone'),
          currency: formData.get('currency'),
          language: formData.get('language'),
          locale: formData.get('locale'),
        };
        debugLog('[savePersonalBusinessSettings] Sending to /api/v1/profile/update/', debugPayload);

        const result = await PC.updateResource('profile', formData, { timeoutMs: 45000 });
        debugLog('[savePersonalBusinessSettings] Success response', result);
      state.personalLastSavedSignature = payloadSignature;

      const savedLanguage = String(formData.get('language') || '').trim().toLowerCase();
      if (savedLanguage !== '' && savedLanguage !== previousLanguage) {
        PC.showToast(T.profileSettingsSaved, 'save');
        await PC.delay(1);
        window.location.reload();
        return;
      }

      const successMessage = source === 'calendar-day'
        ? T.payPeriodStartUpdated
        : T.profileSettingsSaved;
      PC.showToast(successMessage, 'save');
    } catch (error) {
      PW.error(error);
      debugLog('[savePersonalBusinessSettings] Error caught:', {
        message: error instanceof Error ? error.message : String(error),
        stack: error instanceof Error ? error.stack : undefined,
      });
      PC.showToast(error instanceof Error && error.message ? error.message : T.defaultsSaveFailed, 'error');
    } finally {
      state.personalSaveInFlight = false;

      if (state.personalSavePendingSource !== '' && state.personalPendingSignature !== state.personalLastSavedSignature) {
        const queuedSource = state.personalSavePendingSource;
        state.personalSavePendingSource = '';
        state.personalPendingSignature = '';
        savePersonalBusinessSettings(queuedSource).catch((error) => PW.error(error));
      } else {
        state.personalSavePendingSource = '';
        state.personalPendingSignature = '';
      }
    }
  };

  /**
   * Debounce helper for autosave.
   * Waits briefly after user input so we do one save for a burst of changes.
   */
  const schedulePersonalAutoSave = (delayMs = 450, source = 'auto') => {
    if (state.personalAutoSaveTimerId !== null) {
      window.clearTimeout(state.personalAutoSaveTimerId);
    }

    state.personalAutoSaveTimerId = window.setTimeout(() => {
      savePersonalBusinessSettings(source).catch((error) => PW.error(error));
      state.personalAutoSaveTimerId = null;
    }, delayMs);
  };

  const handlePersonalGraceDaysChange = () => {
    const nextValue = getPersonalEditingGraceDays();
    if (state.personalEditingGraceDaysValue === nextValue) {
      return;
    }

    state.personalEditingGraceDaysValue = nextValue;
    refreshPersonalPayPeriodValidation();
    schedulePersonalPreviewRender();
    PC.showToast(T.payPeriodSaving, 'save');
    schedulePersonalAutoSave(180, 'grace');
  };

  const handlePersonalPreviewInteraction = (event) => {
    if (state.profilePayPeriodManagedByBusiness) {
      return;
    }

    const target = event.target instanceof Element
      ? event.target.closest('.pp_day_cell[data-ymd]')
      : null;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    const selectedYmd = String(target.dataset.ymd || '');
    if (selectedYmd === '') {
      return;
    }

    const selectedDate = new Date(`${selectedYmd}T00:00:00`);
    if (Number.isNaN(selectedDate.getTime())) {
      return;
    }

    const selectedAnchor = PAY_PERIOD_CANONICAL_WEEKDAY_NAMES[selectedDate.getDay()] || 'Monday';

    setPersonalPayPeriodStart(selectedYmd);
    setPersonalPayAnchor(selectedAnchor);
    refreshPersonalPayPeriodValidation();
    schedulePersonalPreviewRender();
    schedulePersonalAutoSave(120, 'calendar-day');
  };

  const maskActorLabel = (actorUUID) => {
    const actor = String(actorUUID || '').trim();
    if (actor === '') {
      return T.unknown;
    }

    if (actor === currentUserUUID) {
      return T.you;
    }

    return T.businessMember;
  };

  const maskTechnicalDetails = (details) => {
    const value = String(details || '').trim();
    if (value === '') {
      return '';
    }

    if (/uuid|business_id|site_owner_uuid|target_user_uuid/i.test(value)) {
      return '';
    }

    return value;
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

  const clearInviteTokenFromURL = () => {
    try {
      const current = new URL(window.location.href);
      current.searchParams.delete('org_invite_token');
      const next = `${current.pathname}${current.search}${current.hash}`;
      window.history.replaceState({}, '', next);
    } catch (error) {
      PW.error(error);
    }
  };

  const acceptBusinessInviteToken = async (token) => {
    if (token === '') {
      return false;
    }

    const consentContext = await promptMembershipConsent('Accept business invite');
    if (consentContext === null) {
      clearInviteTokenFromURL();
      return false;
    }

    try {
      await postForm('/api/v1/businesses/invites/accept', {
        invite_token: token,
        ...consentContext,
      });
      PC.showToast(T.inviteAccepted, 'save', 9000, true);
      clearInviteTokenFromURL();
      return true;
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.inviteAcceptFailed, 'error', 9000, true);
      clearInviteTokenFromURL();
      return false;
    }
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
      setGridMessage(T.loadOrgsFailed);
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

  const closeDialog = () => {
    stopRealtimeAuditPolling();
    state.auditRealtimeReady = false;
    state.auditRealtimeTopEventId = '';
    stopDiscoveryPolling();
    closeContactImagePopover();
    if (state.inlineEditorMode) {
      return;
    }

    if (elements.dialog instanceof HTMLDialogElement && elements.dialog.open) {
      elements.dialog.close();
    }
  };

  const openDialog = () => {
    if (state.inlineEditorMode) {
      return;
    }

    if (elements.dialog instanceof HTMLDialogElement && !elements.dialog.open) {
      elements.dialog.showModal();
    }
  };




  const setTransferAvailability = (business) => {
    const premiumLocked = !canUsePremiumOrgFeatures(business);
    const businessType = String(business?.business_type || 'shared').toLowerCase();
    const role = String(business?.role || '').toLowerCase();
    const canTransfer = !premiumLocked && businessType !== T.personal && role === T.owner;
    const canLeave = role !== T.owner;

    if (elements.transferButton instanceof HTMLButtonElement) {
      elements.transferButton.disabled = !canTransfer;
    }
    if (elements.leaveButton instanceof HTMLButtonElement) {
      elements.leaveButton.disabled = !canLeave;
    }
    if (elements.transferNotice) {
      if (premiumLocked && !canLeave) {
        elements.transferNotice.textContent = T.premiumAdminLockedDetailed;
      } else {
        elements.transferNotice.textContent = canTransfer
          ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TRANSFER_SELECT_MEMBER')); ?>'
          : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TRANSFER_NOTICE')); ?>';
      }
    }

    setTransferInputLocked(state.transferSelectedUUID !== '');
  };

  const setEditorMeta = (business) => {
    if (elements.title) {
      elements.title.textContent = decodePossiblyEncodedText(String(business?.name || '<?php echo addslashes(org_js_index_i18n('BUSINESSES')); ?>'));
    }
    if (elements.subtitle) {
      const type = toTitleLabel(business?.business_type, 'Shared');
      const normalizedRole = String(business?.role || '').trim().toLowerCase();
      const role = T[normalizedRole] || T.member;
      const status = toTitleLabel(business?.status, 'Active');
      elements.subtitle.textContent = T.editorSubtitle
        .replace('%s', type)
        .replace('%s', T.nounBusiness)
        .replace('%s', role)
        .replace('%s', status);
    }
    if (elements.orgId instanceof HTMLInputElement) {
      elements.orgId.value = String(business?.business_id || '');
    }
    if (elements.type instanceof HTMLInputElement) {
      elements.type.value = String(business?.business_type || 'shared');
    }
    if (elements.type instanceof HTMLSelectElement) {
      setSelectValueSafe(elements.type, business?.business_type, 'shared');
    }
    if (elements.role instanceof HTMLInputElement) {
      elements.role.value = String(business?.role || 'member');
    }
    if (elements.role instanceof HTMLSelectElement) {
      setSelectValueSafe(elements.role, business?.role, 'member');
    }
    if (elements.status instanceof HTMLInputElement) {
      elements.status.value = String(business?.status || 'active');
    }
    if (elements.status instanceof HTMLSelectElement) {
      setSelectValueSafe(elements.status, business?.status, 'active');
    }
    syncEditorRiskBaselineFromInputs();
    setTransferAvailability(business);
    setPremiumLockedState(business);
    syncAuditControlTestPanel(business);
  };

  const parseDate = (value) => {
    if (typeof value !== 'string' || value.trim() === '') {
      return null;
    }

    const date = new Date(`${value}T00:00:00`);
    return Number.isNaN(date.getTime()) ? null : date;
  };

  const addDays = (date, days) => {
    const next = new Date(date.getTime());
    next.setDate(next.getDate() + days);
    return next;
  };

  const addMonths = (date, months) => {
    const next = new Date(date.getTime());
    next.setMonth(next.getMonth() + months);
    return next;
  };

  const formatYmd = (date) => {
    return date.toISOString().slice(0, 10);
  };

  const formatDateLabel = (date) => {
    return new Intl.DateTimeFormat(undefined, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    }).format(date);
  };

  const periodEndExclusive = (start, frequency) => {
    switch (frequency) {
      case 'weekly':
        return addDays(start, 7);
      case 'semimonthly':
        return addDays(start, 15);
      case 'monthly':
        return addMonths(start, 1);
      case 'biweekly':
      default:
        return addDays(start, 14);
    }
  };

  const getEditorPayAnchor = () => {
    if (elements.payAnchor instanceof HTMLInputElement || elements.payAnchor instanceof HTMLSelectElement) {
      return String(elements.payAnchor.value || 'Monday');
    }

    return 'Monday';
  };

  const setEditorPayAnchor = (value) => {
    if (elements.payAnchor instanceof HTMLInputElement || elements.payAnchor instanceof HTMLSelectElement) {
      elements.payAnchor.value = value;
    }
  };

  const getEditorEditingGraceDays = () => {
    const selected = document.querySelector('input[name="businesses_editor_editing_grace_days"]:checked');
    if (selected instanceof HTMLInputElement) {
      return String(selected.value || '0');
    }

    return '0';
  };

  const setEditorEditingGraceDays = (value) => {
    const normalized = String(value || '0');
    let matched = false;

    Array.from(elements.editorEditingGraceDayRadios).forEach((radio) => {
      if (!(radio instanceof HTMLInputElement)) {
        return;
      }

      const isTarget = radio.value === normalized;
      radio.checked = isTarget;
      if (isTarget) {
        matched = true;
      }
    });

    if (!matched) {
      const fallback = document.getElementById('businesses_editor_grace_0');
      if (fallback instanceof HTMLInputElement) {
        fallback.checked = true;
      }
    }
  };

  const syncPayPeriodLength = () => {
    if (!(elements.payFrequency instanceof HTMLSelectElement) || !(elements.payPeriodLength instanceof HTMLInputElement)) {
      return;
    }

    const nextLength = FREQUENCY_LENGTHS[elements.payFrequency.value] || FREQUENCY_LENGTHS.biweekly;
    elements.payPeriodLength.value = nextLength;
  };

  const renderPreview = () => {
    if (!elements.preview) {
      return;
    }

    syncPayPeriodLength();

    const parseYmd = (ymd) => new Date(`${ymd}T00:00:00`);
    const ppAddDays = (date, days) => new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
    const ppFormatYmd = (date) => {
      const y = date.getFullYear();
      const m = String(date.getMonth() + 1).padStart(2, '0');
      const d = String(date.getDate()).padStart(2, '0');
      return `${y}-${m}-${d}`;
    };
    const alignToAnchor = (start, anchorDay) => {
      const target = PAY_PERIOD_WEEKDAY_MAP[anchorDay] ?? 1;
      let cursor = new Date(start.getFullYear(), start.getMonth(), start.getDate());
      while (cursor.getDay() !== target) {
        cursor = ppAddDays(cursor, -1);
      }
      return cursor;
    };
    const nextPeriod = (start, periodFrequency) => {
      if (periodFrequency === 'weekly') {
        return ppAddDays(start, 7);
      }
      if (periodFrequency === 'biweekly') {
        return ppAddDays(start, 14);
      }
      if (periodFrequency === 'semimonthly') {
        if (start.getDate() <= 15) {
          return new Date(start.getFullYear(), start.getMonth(), 16);
        }
        return new Date(start.getFullYear(), start.getMonth() + 1, 1);
      }

      return new Date(start.getFullYear(), start.getMonth() + 1, 1);
    };
    const currentPeriod = (startYmd, periodFrequency, anchorDay) => {
      const now = new Date();
      const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

      if (periodFrequency === 'weekly') {
        const start = alignToAnchor(today, anchorDay);
        return { start, endExclusive: ppAddDays(start, 7) };
      }
      if (periodFrequency === 'biweekly') {
        const start = parseYmd(startYmd);
        return { start, endExclusive: ppAddDays(start, 14) };
      }
      if (periodFrequency === 'semimonthly') {
        if (today.getDate() <= 15) {
          const start = new Date(today.getFullYear(), today.getMonth(), 1);
          return { start, endExclusive: new Date(today.getFullYear(), today.getMonth(), 16) };
        }

        const start = new Date(today.getFullYear(), today.getMonth(), 16);
        return { start, endExclusive: new Date(today.getFullYear(), today.getMonth() + 1, 1) };
      }

      const start = new Date(today.getFullYear(), today.getMonth(), 1);
      return { start, endExclusive: new Date(today.getFullYear(), today.getMonth() + 1, 1) };
    };
    const startOfWeek = (date) => ppAddDays(date, -date.getDay());
    const inRange = (date, start, endExclusive) => date >= start && date < endExclusive;
    const monthLabel = (date) => date.toLocaleDateString(undefined, { month: 'long', year: 'numeric' });
    const buildRibbonCalendar = (periods, grace, today) => {
      const stripbar = PAY_PERIOD_DAY_NAMES.map((day) => `<span class="pp_day_head">${day}</span>`).join('');
      const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
      const gridStart = startOfWeek(firstOfMonth);
      const badgesPlaced = { p1: false, p2: false };
      let bodyRows = '';

      for (let week = 0; week < 6; week += 1) {
        bodyRows += '<tr>';
        for (let day = 0; day < 7; day += 1) {
          const offset = (week * 7) + day;
          const cellDate = ppAddDays(gridStart, offset);
          const isToday = ppFormatYmd(cellDate) === ppFormatYmd(today);
          const classes = ['pp_day_cell'];
          let badge = '';

          periods.forEach((period, index) => {
            const periodKey = index === 0 ? 'p1' : 'p2';
            const prevDate = ppAddDays(cellDate, -1);
            const nextDate = ppAddDays(cellDate, 1);
            const active = inRange(cellDate, period.start, period.endExclusive);
            const prevActive = inRange(prevDate, period.start, period.endExclusive);
            const nextActive = inRange(nextDate, period.start, period.endExclusive);
            const graceStart = period.endExclusive;
            const graceEndExclusive = ppAddDays(graceStart, grace);
            const graceActive = inRange(cellDate, graceStart, graceEndExclusive);

            if (active) {
              classes.push('pp_in_period', `pp_in_${periodKey}`);
              if (!prevActive) {
                classes.push(`pp_ribbon_start_${periodKey}`);
              }
              if (!nextActive) {
                classes.push(`pp_ribbon_end_${periodKey}`);
              }
              if (!badgesPlaced[periodKey]) {
                badge = `<span class="pp_badge ${periodKey === 'p2' ? 'pp_badge_p2' : ''}">${period.label}</span>`;
                badgesPlaced[periodKey] = true;
              }
            }

            if (graceActive && grace > 0) {
              const graceIndex = Math.min(grace, Math.max(1, Math.floor((cellDate - graceStart) / 86400000) + 1));
              classes.push('pp_grace_day', `pp_grace_${graceIndex}`, `pp_grace_${periodKey}`);
            }
          });

          if (isToday) {
            classes.push('pp_today');
          }

          bodyRows += `<td class="${classes.join(' ')}" data-ymd="${ppFormatYmd(cellDate)}" tabindex="0"><span class="pp_day_number">${String(cellDate.getDate()).padStart(2, '0')}</span>${badge}</td>`;
        }
        bodyRows += '</tr>';
      }

      return `
        <div class="pp_month_label">${monthLabel(today)}</div>
        <div class="pp_stripbar">${stripbar}</div>
        <table class="pp_three_week">
          <tbody>${bodyRows}</tbody>
        </table>
      `;
    };

    const startRaw = elements.payPeriodStart instanceof HTMLInputElement ? String(elements.payPeriodStart.value || '') : '';
    const frequency = elements.payFrequency instanceof HTMLSelectElement ? elements.payFrequency.value : 'biweekly';
    const anchor = getEditorPayAnchor();
    const graceDays = getEditorEditingGraceDays();
    const now2 = new Date();
    const today2 = new Date(now2.getFullYear(), now2.getMonth(), now2.getDate());
    const startValue = startRaw !== '' ? startRaw : ppFormatYmd(alignToAnchor(today2, anchor));

    if (elements.payPeriodStart instanceof HTMLInputElement && elements.payPeriodStart.value === '') {
      elements.payPeriodStart.value = startValue;
    }

    if (startValue === '') {
      elements.preview.textContent = T.previewEmpty;
      if (elements.payPeriodGridStatus instanceof HTMLElement) {
        elements.payPeriodGridStatus.textContent = T.previewEmpty;
      }
      return;
    }

    const period1 = currentPeriod(startValue, frequency, anchor);
    const period2 = {
      start: period1.endExclusive,
      endExclusive: nextPeriod(period1.endExclusive, frequency),
    };
    const endInclusive1 = ppAddDays(period1.endExclusive, -1);
    const endInclusive2 = ppAddDays(period2.endExclusive, -1);
    const periods = [
      { label: 'P1', start: period1.start, endExclusive: period1.endExclusive },
      { label: 'P2', start: period2.start, endExclusive: period2.endExclusive },
    ];
    const today = new Date();
    const graceDaysInt = parseInt(graceDays, 10);
    const safeGraceDays = Number.isNaN(graceDaysInt) ? 0 : graceDaysInt;

    Guardian.setHTML(elements.preview, `
      ${buildRibbonCalendar(periods, safeGraceDays, today)}
      <div class="pp_preview_summary"><span class="pp_preview_summary_item">P1: ${ppFormatYmd(period1.start)} to ${ppFormatYmd(endInclusive1)}</span><span class="pp_preview_summary_item">P2: ${ppFormatYmd(period2.start)} to ${ppFormatYmd(endInclusive2)}</span></div>
    `);

    if (elements.payPeriodGridStatus instanceof HTMLElement) {
      elements.payPeriodGridStatus.textContent = T.ppPreviewStatus
        .replace('%s', ppFormatYmd(period1.start))
        .replace('%s', ppFormatYmd(endInclusive1))
        .replace('%s', ppFormatYmd(period2.start))
        .replace('%s', ppFormatYmd(endInclusive2));
    }
  };

  const handleEditorPreviewInteraction = (event) => {
    const target = event.target instanceof Element
      ? event.target.closest('.pp_day_cell[data-ymd]')
      : null;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    const selectedYmd = String(target.dataset.ymd || '');
    if (selectedYmd === '') {
      return;
    }

    const selectedDate = new Date(`${selectedYmd}T00:00:00`);
    if (Number.isNaN(selectedDate.getTime())) {
      return;
    }

    const selectedAnchor = PAY_PERIOD_CANONICAL_WEEKDAY_NAMES[selectedDate.getDay()] || 'Monday';

    if (elements.payPeriodStart instanceof HTMLInputElement) {
      elements.payPeriodStart.value = selectedYmd;
    }
    setEditorPayAnchor(selectedAnchor);
    renderPreview();
    scheduleEditorAutoSave(220, 'calendar-day');
  };

  const hydrateSettings = (payload, business) => {
    state.editorHydrating = true;
    const settings = payload && typeof payload === 'object' && payload.settings && typeof payload.settings === 'object'
      ? payload.settings
      : {};

    renderOwnerSummary(payload, business);

    if (elements.name instanceof HTMLInputElement) {
      elements.name.value = decodePossiblyEncodedText(String((payload.business && payload.business.name) || business?.name || ''));
    }
    if (elements.defaultWage instanceof HTMLInputElement) {
      elements.defaultWage.value = String(settings.default_wage || '');
    }
    if (elements.timezone instanceof HTMLInputElement) {
      elements.timezone.value = String(settings.timezone || '');
      displayTimezoneValue(elements.timezoneSearch, String(settings.timezone || ''));
    }
    if (elements.currency instanceof HTMLInputElement) {
      elements.currency.value = String(settings.currency || '');
      displayCurrencyValue(elements.currencySearch, String(settings.currency || ''));
    }
    if (elements.payFrequency instanceof HTMLSelectElement) {
      elements.payFrequency.value = String(settings.pay_frequency || 'biweekly');
    }
    setEditorPayAnchor(String(settings.pay_anchor || 'Monday'));
    if (elements.payPeriodStart instanceof HTMLInputElement) {
      elements.payPeriodStart.value = String(settings.pay_period_start || '');
    }
    if (elements.payPeriodLength instanceof HTMLInputElement) {
      elements.payPeriodLength.value = String(settings.pay_period_length || FREQUENCY_LENGTHS.biweekly);
    }
    setEditorEditingGraceDays(String(settings.editing_grace_days || '0'));

    Object.entries(EDITOR_META_FIELD_MAP).forEach(([fieldId, payloadKey]) => {
      setEditorFieldValueById(fieldId, String(settings[payloadKey] || ''));
    });

    formatPhoneInputsWithin(elements.dialog ?? document);

    renderPreview();
    state.editorHydrating = false;
    syncEditorRiskBaselineFromInputs();
    state.editorSettingsCache = {
      ...settings,
      name: decodePossiblyEncodedText(String((payload.business && payload.business.name) || business?.name || '')),
      business_type: String(business?.business_type || settings.business_type || 'shared'),
      role: String(business?.role || settings.role || 'member'),
      status: String(business?.status || settings.status || 'active'),
    };
    const payloadSignature = buildEditorPayloadSignature(
      resolveBusinessSubPage() === 'details'
        ? collectOrganizationPayload()
        : resolveBusinessSubPage() === 'payroll'
          ? collectPayrollPayload()
          : collectBusinessEditorPayload(),
    );
    state.editorLastSavedSignature = payloadSignature;
    if (resolveBusinessSubPage() === 'details') {
      state.organizationLastSavedSignature = payloadSignature;
    }
    if (resolveBusinessSubPage() === 'payroll') {
      state.payrollLastSavedSignature = payloadSignature;
    }
  };

  const formatInviteTimestamp = (value) => {
    const raw = String(value || '').trim();
    if (raw === '') {
      return T.valueUnknown;
    }

    const parsed = new Date(raw);
    if (Number.isNaN(parsed.getTime())) {
      return raw;
    }

    return parsed.toLocaleString();
  };

  const renderOwnerSummary = (payload, business) => {
    if (!(elements.ownerSummary instanceof HTMLElement)) {
      return;
    }

    const payloadOrg = payload && typeof payload === 'object' && payload.business && typeof payload.business === 'object'
      ? payload.business
      : {};
    const ownerName = String(payloadOrg.owner_name || business?.owner_name || '').trim();
    const ownerEmail = String(payloadOrg.owner_email || business?.owner_email || '').trim();
    const ownerPhone = formatPhoneDisplayValue(String(payloadOrg.owner_phone || '').trim());
    const ownerSinceRaw = String(payloadOrg.owner_since || '').trim();
    const ownerSince = ownerSinceRaw === '' ? T.unavailable : formatInviteTimestamp(ownerSinceRaw);

    const rows = [
      [T.contactNameLabel, ownerName !== '' ? ownerName : T.unavailable],
      [T.contactEmailLabel, ownerEmail !== '' ? ownerEmail : T.unavailable],
      [T.contactPhoneLabel, ownerPhone !== '' ? ownerPhone : T.unavailable],
      [T.ownerSinceLabel, ownerSince],
    ];

    Guardian.setHTML(elements.ownerSummary, rows.map(([label, value]) => `
      <div class="businesses_owner_summary_item">
        <span>${safeText(label)}</span>
        <strong>${safeText(value)}</strong>
      </div>
    `).join(''));
  };

  const parseHistoryTimestampValue = (value) => {
    if (typeof value !== 'string' || value.trim() === '') {
      return null;
    }

    const trimmed = value.trim();
    const parsed = new Date(trimmed);
    if (!Number.isNaN(parsed.getTime())) {
      return parsed;
    }

    const dateTimeMatch = /^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2}:\d{2})$/.exec(trimmed);
    if (!dateTimeMatch) {
      return null;
    }

    const asUtc = new Date(`${dateTimeMatch[1]}T${dateTimeMatch[2]}Z`);
    return Number.isNaN(asUtc.getTime()) ? null : asUtc;
  };

  const resolveViewerLocale = () => {
    const locale = String(PC?.config?.USER_LOCALE || '').trim();
    return locale !== '' ? locale : 'en-CA';
  };

  const formatTimestampInTimeZone = (dateValue, timeZone) => {
    if (!(dateValue instanceof Date) || Number.isNaN(dateValue.getTime())) {
      return T.unavailable;
    }

    try {
      const options = {
        year: '2-digit',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
      };

      const normalizedZone = typeof timeZone === 'string' && timeZone.trim() !== ''
        ? timeZone.trim()
        : undefined;
      const viewerLocale = resolveViewerLocale();

      const formatter = normalizedZone
        ? new Intl.DateTimeFormat(viewerLocale, { ...options, timeZone: normalizedZone })
        : new Intl.DateTimeFormat(viewerLocale, options);

      const parts = formatter.formatToParts(dateValue);
      const data = {};
      parts.forEach((part) => {
        if (part.type !== 'literal') {
          data[part.type] = part.value;
        }
      });

      const mm = String(data.month || '00').padStart(2, '0');
      const dd = String(data.day || '00').padStart(2, '0');
      const yy = String(data.year || '00').slice(-2).padStart(2, '0');
      const hh = String(data.hour || '00').padStart(2, '0');
      const min = String(data.minute || '00').padStart(2, '0');

      return `${mm}/${dd}/${yy} ${hh}:${min}`;
    } catch {
      // Fall back to viewer locale timezone when timezone is unavailable.
      const mm = String(dateValue.getMonth() + 1).padStart(2, '0');
      const dd = String(dateValue.getDate()).padStart(2, '0');
      const yy = String(dateValue.getFullYear()).slice(-2);
      const hh = String(dateValue.getHours()).padStart(2, '0');
      const min = String(dateValue.getMinutes()).padStart(2, '0');
      return `${mm}/${dd}/${yy} ${hh}:${min}`;
    }
  };

  const viewerTimeZone = (() => {
    try {
      return Intl.DateTimeFormat().resolvedOptions().timeZone || 'Local';
    } catch {
      return 'Local';
    }
  })();

  const buildTimestampZoneRows = (parsedDate) => [
    { label: T.timestampLocal, value: formatTimestampInTimeZone(parsedDate, viewerTimeZone) },
    { label: T.timestampServer, value: formatTimestampInTimeZone(parsedDate, SERVER_TIMEZONE) },
    { label: T.timestampUtc, value: formatTimestampInTimeZone(parsedDate, 'UTC') },
  ];

  let openHistoryTimestampPopover = null;

  const positionHistoryTimestampPopover = (trigger, popover) => {
    if (!(trigger instanceof HTMLElement) || !(popover instanceof HTMLElement)) {
      return;
    }

    const margin = 8;
    const triggerRect = trigger.getBoundingClientRect();

    popover.style.position = 'fixed';
    popover.style.right = 'auto';
    popover.style.left = '0px';
    popover.style.top = '0px';

    const popoverWidth = Math.ceil(popover.offsetWidth || 0);
    const popoverHeight = Math.ceil(popover.offsetHeight || 0);

    let left = triggerRect.left;
    if (popoverWidth > 0) {
      left = Math.min(left, window.innerWidth - popoverWidth - margin);
    }
    left = Math.max(margin, left);

    const maxTop = Math.max(margin, window.innerHeight - popoverHeight - margin);
    const preferredBelowTop = triggerRect.bottom + 6;
    const preferredAboveTop = triggerRect.top - popoverHeight - 6;

    let top = preferredBelowTop;
    if (popoverHeight > 0) {
      if (preferredBelowTop <= maxTop) {
        top = preferredBelowTop;
      } else if (preferredAboveTop >= margin) {
        top = preferredAboveTop;
      } else {
        top = Math.min(preferredBelowTop, maxTop);
      }
    }
    top = Math.max(margin, top);

    popover.style.left = `${Math.round(left)}px`;
    popover.style.top = `${Math.round(top)}px`;
  };

  const closeHistoryTimestampPopover = ({ restoreFocus = false } = {}) => {
    if (!openHistoryTimestampPopover) {
      return;
    }

    const { trigger, popover, homeParent } = openHistoryTimestampPopover;
    if (trigger instanceof HTMLElement) {
      trigger.setAttribute('aria-expanded', 'false');
      if (restoreFocus && typeof trigger.focus === 'function') {
        trigger.focus();
      }
    }
    if (popover instanceof HTMLElement) {
      popover.hidden = true;
      popover.style.left = '';
      popover.style.top = '';
      popover.style.right = '';
      popover.style.position = '';

      if (homeParent instanceof Node && homeParent.isConnected) {
        homeParent.appendChild(popover);
      } else if (trigger instanceof HTMLElement && trigger.parentElement instanceof Node) {
        trigger.parentElement.appendChild(popover);
      }
    }

    openHistoryTimestampPopover = null;
  };

  const openHistoryTimestampPopoverFor = (trigger, popover) => {
    if (!(trigger instanceof HTMLElement) || !(popover instanceof HTMLElement)) {
      return;
    }

    if (
      openHistoryTimestampPopover
      && (openHistoryTimestampPopover.trigger !== trigger || openHistoryTimestampPopover.popover !== popover)
    ) {
      closeHistoryTimestampPopover({ restoreFocus: false });
    }

    const homeParent = popover.parentElement;
    const portalParent = trigger.closest('dialog[open]') || document.body;
    if (popover.parentElement !== portalParent) {
      portalParent.appendChild(popover);
    }

    popover.hidden = false;
    positionHistoryTimestampPopover(trigger, popover);
    window.requestAnimationFrame(() => {
      positionHistoryTimestampPopover(trigger, popover);
    });
    trigger.setAttribute('aria-expanded', 'true');
    openHistoryTimestampPopover = { trigger, popover, homeParent };
  };

  const bindHistoryTimestampPopover = (container, trigger, popover) => {
    if (!(container instanceof HTMLElement) || !(trigger instanceof HTMLButtonElement) || !(popover instanceof HTMLElement)) {
      return;
    }

    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      if (!popover.hidden && openHistoryTimestampPopover?.trigger === trigger) {
        closeHistoryTimestampPopover({ restoreFocus: false });
      } else {
        openHistoryTimestampPopoverFor(trigger, popover);
      }
    });

    trigger.addEventListener('mouseenter', () => {
      openHistoryTimestampPopoverFor(trigger, popover);
    });

    trigger.addEventListener('focus', () => {
      openHistoryTimestampPopoverFor(trigger, popover);
    });

    trigger.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        if (!popover.hidden && openHistoryTimestampPopover?.trigger === trigger) {
          closeHistoryTimestampPopover({ restoreFocus: false });
        } else {
          openHistoryTimestampPopoverFor(trigger, popover);
        }
      } else if (event.key === 'Escape') {
        event.preventDefault();
        closeHistoryTimestampPopover({ restoreFocus: true });
      }
    });

    trigger.addEventListener('mouseleave', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && popover.contains(nextTarget)) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    trigger.addEventListener('focusout', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && popover.contains(nextTarget)) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    popover.addEventListener('mouseleave', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && trigger.contains(nextTarget)) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    popover.addEventListener('focusout', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && (trigger.contains(nextTarget) || popover.contains(nextTarget))) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    if (!container.dataset.historyTimestampPopoverGlobalBound) {
      document.addEventListener('pointerdown', (event) => {
        if (!openHistoryTimestampPopover) {
          return;
        }

        const target = event.target;
        if (!(target instanceof Node)) {
          return;
        }

        if (
          openHistoryTimestampPopover.trigger.contains(target)
          || openHistoryTimestampPopover.popover.contains(target)
        ) {
          return;
        }

        closeHistoryTimestampPopover({ restoreFocus: false });
      });

      document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !openHistoryTimestampPopover) {
          return;
        }
        closeHistoryTimestampPopover({ restoreFocus: true });
      });

      window.addEventListener('scroll', () => {
        if (!openHistoryTimestampPopover) {
          return;
        }
        positionHistoryTimestampPopover(openHistoryTimestampPopover.trigger, openHistoryTimestampPopover.popover);
      }, true);

      window.addEventListener('resize', () => {
        if (!openHistoryTimestampPopover) {
          return;
        }
        positionHistoryTimestampPopover(openHistoryTimestampPopover.trigger, openHistoryTimestampPopover.popover);
      });

      container.dataset.historyTimestampPopoverGlobalBound = '1';
    }
  };

  const enhanceInviteHistoryTimestampCells = () => {
    if (!(elements.membersInviteHistoryGridContainer instanceof HTMLElement)) {
      return;
    }

    const gridEl = elements.membersInviteHistoryGridContainer.querySelector('[data-grid="businesses-invite-history-grid"]');
    if (!(gridEl instanceof HTMLElement)) {
      return;
    }

    const headerSort = gridEl.querySelector('.datagrid_sort[data-column="resolved_at"]');
    const headerCell = headerSort instanceof HTMLElement ? headerSort.closest('.datagrid_heading') : null;
    const headerId = headerCell instanceof HTMLElement ? String(headerCell.id || '') : '';

    let timestampCells = [];
    if (headerId !== '') {
      timestampCells = Array.from(gridEl.querySelectorAll(`.datagrid_item[aria-labelledby="${headerId}"]`));
    }

    if (timestampCells.length === 0) {
      timestampCells = Array.from(gridEl.querySelectorAll('.datagrid_row .datagrid_item:nth-child(4)'));
    }

    timestampCells.forEach((cell, index) => {
      if (!(cell instanceof HTMLElement)) {
        return;
      }

      if (cell.closest('.datagrid_row_empty')) {
        return;
      }

      cell.classList.add('businesses_history_timestamp_cell');

      if (cell.dataset.timePopoverBound === '1') {
        return;
      }

      const rawValue = String(cell.textContent || '').trim();
      if (rawValue === '') {
        return;
      }

      const parsedDate = parseHistoryTimestampValue(rawValue);
      const displayText = formatTimestampInTimeZone(parsedDate, viewerTimeZone);
      const rowId = String(cell.closest('.datagrid_row')?.getAttribute('data-id') || `row_${index}`);
      const safeRowId = rowId.replace(/[^a-zA-Z0-9_-]/g, '_');
      const popoverId = `businesses_history_timestamp_popover_${safeRowId}_${index}`;

      const field = document.createElement('span');
      field.className = 'businesses_history_timestamp_field';

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'businesses_history_timestamp_trigger';
      trigger.textContent = displayText;
      trigger.setAttribute('aria-haspopup', 'dialog');
      trigger.setAttribute('aria-controls', popoverId);
      trigger.setAttribute('aria-expanded', 'false');

      const popover = document.createElement('div');
      popover.id = popoverId;
      popover.className = 'businesses_history_timestamp_popover';
      popover.hidden = true;
      popover.setAttribute('role', 'dialog');
      popover.setAttribute('aria-label', T.timestampDetailsAria);

      const rows = buildTimestampZoneRows(parsedDate);

      if (!(parsedDate instanceof Date) || Number.isNaN(parsedDate.getTime())) {
        rows[1].value = rawValue;
      }

      rows.forEach((row) => {
        const rowEl = document.createElement('span');
        rowEl.className = 'businesses_history_timestamp_popover_row';

        const labelEl = document.createElement('span');
        labelEl.className = 'businesses_history_timestamp_popover_label';
        labelEl.textContent = `${row.label}:`;

        const valueEl = document.createElement('span');
        valueEl.className = 'businesses_history_timestamp_popover_value';
        valueEl.textContent = row.value;

        rowEl.appendChild(labelEl);
        rowEl.appendChild(valueEl);
        popover.appendChild(rowEl);
      });

      field.appendChild(trigger);
      field.appendChild(popover);

      cell.textContent = '';
      cell.appendChild(field);
      cell.dataset.timePopoverBound = '1';

      bindHistoryTimestampPopover(elements.membersInviteHistoryGridContainer, trigger, popover);
    });
  };

  const enhanceMembersJoinedTimestampCells = () => {
    if (!(elements.membersGridContainer instanceof HTMLElement)) {
      return;
    }

    const gridEl = elements.membersGridContainer.querySelector('[data-grid="business-members"]');
    if (!(gridEl instanceof HTMLElement)) {
      return;
    }

    const headerSort = gridEl.querySelector('.datagrid_sort[data-column="joined_at"]');
    const headerCell = headerSort instanceof HTMLElement ? headerSort.closest('.datagrid_heading') : null;
    const headerId = headerCell instanceof HTMLElement ? String(headerCell.id || '') : '';

    let timestampCells = [];
    if (headerId !== '') {
      timestampCells = Array.from(gridEl.querySelectorAll(`.datagrid_item[aria-labelledby="${headerId}"]`));
    }

    if (timestampCells.length === 0) {
      timestampCells = Array.from(gridEl.querySelectorAll('.datagrid_row .datagrid_col_joined_at'));
    }

    timestampCells.forEach((cell, index) => {
      if (!(cell instanceof HTMLElement)) {
        return;
      }

      if (cell.closest('.datagrid_row_empty')) {
        return;
      }

      cell.classList.add('businesses_history_timestamp_cell');

      if (cell.dataset.timePopoverBound === '1') {
        return;
      }

      const rawValue = String(cell.dataset.joinedAtRaw || '').trim();
      const friendlyDisplay = String(cell.dataset.joinedDisplay || cell.textContent || '').trim();
      if (rawValue === '' && friendlyDisplay === '') {
        return;
      }

      const parsedDate = parseHistoryTimestampValue(rawValue);
      const displayText = friendlyDisplay !== ''
        ? friendlyDisplay
        : formatTimestampInTimeZone(parsedDate, viewerTimeZone);
      if (displayText === T.unavailable) {
        return;
      }
      const rowId = String(cell.closest('.datagrid_row')?.getAttribute('data-id') || `member_${index}`);
      const safeRowId = rowId.replace(/[^a-zA-Z0-9_-]/g, '_');
      const popoverId = `businesses_members_joined_popover_${safeRowId}_${index}`;

      const field = document.createElement('span');
      field.className = 'businesses_history_timestamp_field';

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'businesses_history_timestamp_trigger';
      trigger.textContent = displayText;
      trigger.setAttribute('aria-haspopup', 'dialog');
      trigger.setAttribute('aria-controls', popoverId);
      trigger.setAttribute('aria-expanded', 'false');

      const popover = document.createElement('div');
      popover.id = popoverId;
      popover.className = 'businesses_history_timestamp_popover';
      popover.hidden = true;
      popover.setAttribute('role', 'dialog');
      popover.setAttribute('aria-label', T.timestampJoinedAria);

      const rows = buildTimestampZoneRows(parsedDate);

      if (!(parsedDate instanceof Date) || Number.isNaN(parsedDate.getTime())) {
        rows[1].value = rawValue;
      }

      rows.forEach((row) => {
        const rowEl = document.createElement('span');
        rowEl.className = 'businesses_history_timestamp_popover_row';

        const labelEl = document.createElement('span');
        labelEl.className = 'businesses_history_timestamp_popover_label';
        labelEl.textContent = `${row.label}:`;

        const valueEl = document.createElement('span');
        valueEl.className = 'businesses_history_timestamp_popover_value';
        valueEl.textContent = row.value;

        rowEl.appendChild(labelEl);
        rowEl.appendChild(valueEl);
        popover.appendChild(rowEl);
      });

      field.appendChild(trigger);
      field.appendChild(popover);

      cell.textContent = '';
      cell.appendChild(field);
      cell.dataset.timePopoverBound = '1';

      bindHistoryTimestampPopover(elements.membersGridContainer, trigger, popover);
    });
  };

  const deriveInviteRoleLabel = (invite) => {
    const explicitRole = String(invite?.role || '').trim().toLowerCase();
    if (explicitRole !== '') {
      return T[explicitRole] || T.member || 'member';
    }

    const scopes = Array.isArray(invite?.scopes)
      ? invite.scopes.map((scope) => String(scope || '').trim()).filter((scope) => scope !== '')
      : [];

    if (scopes.includes('access.manage') || scopes.includes('org.settings.write')) {
      return T.manager || 'manager';
    }
    if (scopes.includes('sites.write') || (scopes.includes('work.write') && scopes.includes('work.scope.org'))) {
      return T.contributor || 'contributor';
    }
    if (scopes.includes('work.self.write') || (scopes.includes('work.write') && scopes.includes('work.scope.self'))) {
      return T.member || 'member';
    }
    if (scopes.length > 0) {
      return T.viewer || 'viewer';
    }

    return T.member || 'member';
  };

  const renderInvites = (invites) => {
    const inviteTargets = [elements.invitesList, elements.membersInvitesList]
      .filter((target) => target instanceof HTMLElement);

    if (inviteTargets.length === 0) {
      return;
    }

    if (!Array.isArray(invites) || invites.length === 0) {
      inviteTargets.forEach((target) => setStackMessage(target, T.noInvites));
      announceInvitesStatus(T.invitesLoadedNone);
      return;
    }

    const invitesMarkup = invites.map((invite) => {
      const inviteId = String(invite.invite_id || '');
      const email = String(invite.invitee_email || T.unknown);
      const status = String(invite.status || T.pending);
      const roleLabel = deriveInviteRoleLabel(invite);
      const timestamp = formatInviteTimestamp(invite.created_at || '');
      const canRevoke = status === 'pending';

      return `
        <div class="businesses_stack_row businesses_stack_row_compact">
          <div class="businesses_stack_text">
            <span class="businesses_invite_compact_line">
              <strong>${email}</strong>
              <span class="businesses_invite_compact_meta">[${roleLabel}]</span>
              <span class="businesses_invite_compact_meta">[${timestamp}]</span>
            </span>
          </div>
          ${canRevoke ? `<button type="button" class="btn btn_delete" data-org-action="revoke-invite" data-invite-id="${inviteId}">${T.revoke}</button>` : ''}
        </div>
      `;
    }).join('');

    inviteTargets.forEach((target) => {
      target.classList.remove('businesses_empty');
      Guardian.setHTML(target, invitesMarkup);
    });

    announceInvitesStatus(
      T.invitesLoadedCount
        .replace('%d', String(invites.length))
        .replace('%s', invites.length === 1 ? '' : 's'),
    );
  };

  const inviteHistoryGridEndpoint = (orgId) => {
    return `/api/v1/businesses/${encodeURIComponent(orgId)}/invites/history/grid`;
  };

  const ensureInviteHistoryGridManager = (orgId) => {
    if (
      state.inviteHistoryGridManager
      && state.inviteHistoryGridOrgId === orgId
    ) {
      return state.inviteHistoryGridManager;
    }

    if (state.inviteHistoryGridManager && typeof state.inviteHistoryGridManager.destroy === 'function') {
      state.inviteHistoryGridManager.destroy();
    }

    state.inviteHistoryGridManager = createDataGrid({
      id: 'businesses-invite-history-grid',
      containerId: 'businesses-invite-history-grid-host',
      endpoint: inviteHistoryGridEndpoint(orgId),
    });
    state.inviteHistoryGridOrgId = orgId;

    return state.inviteHistoryGridManager;
  };

  const loadBusinessInviteHistoryGrid = async (businessId) => {
    const business = findBusiness(businessId);
    if (business && !canManageBusinessAccess(business)) {
      setDatagridMessage(elements.membersInviteHistoryGridContainer, ACCESS_MANAGE_WARNING);
      announceInvitesStatus(ACCESS_MANAGE_WARNING);
      return;
    }

    const manager = ensureInviteHistoryGridManager(businessId);
    if (!manager) {
      throw new Error('Unable to initialize invite history grid manager.');
    }

    try {
      await manager.reload();
      enhanceInviteHistoryTimestampCells();
    } catch (error) {
      PW.error(error);
      setDatagridMessage(elements.membersInviteHistoryGridContainer, T.manageAccessUnavailable);
    }
  };

  const renderAccessRequests = (requests) => {
    if (!elements.accessRequestsList) {
      return;
    }

    if (!Array.isArray(requests) || requests.length === 0) {
      setStackMessage(elements.accessRequestsList, T.noAccessRequests);
      announceAccessRequestsStatus(T.accessRequestsLoadedNone);
      return;
    }

    elements.accessRequestsList.classList.remove('businesses_empty');
    Guardian.setHTML(elements.accessRequestsList, requests.map((request) => {
      const requestId = String(request.request_id || '');
      const requester = String(request.requester_contact_email || request.requester_uuid || T.unknown);
      const status = String(request.status || T.pending);
      const createdAt = String(request.created_at || '');
      const canAct = status === 'pending';

      return `
        <div class="businesses_stack_row businesses_stack_row_hint">
          <div class="businesses_stack_text">
            <strong>${requester}</strong>
            <span>${status}${createdAt ? ` | ${createdAt}` : ''}${requestId ? ` | ${requestId}` : ''}</span>
          </div>
          ${canAct ? `
            <div class="businesses_actions_row">
              <button type="button" class="btn btn_secondary" data-org-action="approve-access-request" data-request-id="${requestId}">Approve</button>
              <button type="button" class="btn btn_delete" data-org-action="reject-access-request" data-request-id="${requestId}">Reject</button>
            </div>
          ` : ''}
        </div>
      `;
    }).join(''));
    announceAccessRequestsStatus(
      T.accessRequestsLoadedCount
        .replace('%d', String(requests.length))
        .replace('%s', requests.length === 1 ? '' : 's'),
    );
  };

  const formatLiveRequestCreatedAt = (rawValue) => {
    const text = String(rawValue || '').trim();
    if (text === '') {
      return '';
    }

    const parsed = new Date(text);
    if (Number.isNaN(parsed.getTime())) {
      return text;
    }

    return parsed.toLocaleString();
  };

  const normalizeLiveRequestItems = (items) => {
    if (!Array.isArray(items)) {
      return [];
    }

    return items.map((item) => {
      const businessId = String(item?.business_id || '').trim();
      const requestId = String(item?.request_id || '').trim();
      const status = String(item?.status || 'pending').trim().toLowerCase();
      const requester = String(item?.requester_contact_email || item?.requester_uuid || T.unknown).trim();
      const businessName = String(item?.business_name || item?.business_id || T.unknown).trim();
      const createdAt = String(item?.created_at || '').trim();

      return {
        businessId,
        requestId,
        status,
        requester,
        businessName,
        createdAt,
      };
    }).filter((item) => item.businessId !== '' && item.requestId !== '' && item.status === 'pending');
  };


  const fetchLiveRequestsSnapshot = async () => {
    const params = new URLSearchParams({
      channel: 'business_requests_live',
    });

    if (state.liveRequestsSignature !== '') {
      params.set('since_signature', state.liveRequestsSignature);
    }

    const response = await fetch(`${legacyWsHttpBase}?${params.toString()}`, {
      method: 'GET',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: buildHeaders(),
    });

    if (!response.ok) {
      throw new Error(`Live requests channel failed (${response.status}).`);
    }

    const payload = await response.json();
    if (!payload || (payload.status !== 'success' && payload.status !== 'ok')) {
      throw new Error(String(payload?.message || 'Live requests payload invalid.'));
    }

    return {
      items: Array.isArray(payload.pending_requests) ? payload.pending_requests : [],
      signature: String(payload.latest_signature || ''),
    };
  };




  const renderRelationships = (relationships) => {
    if (!(elements.transferTarget instanceof HTMLInputElement)) {
      return;
    }

    if (!(elements.transferTargetList instanceof HTMLDataListElement)) {
      return;
    }

    const previous = normalizeTransferLookupName(elements.transferTarget.value);
    const previousSelectedUUID = state.transferSelectedUUID;
    elements.transferTargetList.replaceChildren();
    state.transferCandidates = [];
    if (elements.transferTargetUUID instanceof HTMLInputElement) {
      elements.transferTargetUUID.value = '';
    }

    let count = 0;
    (Array.isArray(relationships) ? relationships : []).forEach((relationship) => {
      const userUUID = String(relationship.user_uuid || relationship.uuid || '').trim();
      const role = String(relationship.role || 'member').toLowerCase();
      const status = String(relationship.status || '').toLowerCase();
      if (userUUID === '' || role === 'owner' || status !== 'active') {
        return;
      }

      const displayName = deriveTransferCandidateDisplay(relationship);
      if (displayName === '') {
        return;
      }

      const option = document.createElement('option');
      option.value = displayName;
      elements.transferTargetList.appendChild(option);

      state.transferCandidates.push({
        userUUID,
        displayName,
        lookupKey: normalizeTransferLookupName(displayName),
        email: String(relationship.email || '').trim(),
        roleLabel: T[String(role || '').toLowerCase()] || T.member,
        statusLabel: status,
      });
      count += 1;
    });

    if (previousSelectedUUID !== '') {
      const selected = getTransferCandidateByUUID(previousSelectedUUID);
      if (selected) {
        applyTransferSelection(selected, false);
      } else {
        clearTransferSelection(true);
      }
    } else {
      renderTransferSelectedMember();
      setTransferInputLocked(false);
    }

    if (previousSelectedUUID === '' && previous !== '') {
      const match = state.transferCandidates.find((candidate) => candidate.lookupKey === previous);
      if (match) {
        elements.transferTarget.value = match.displayName;
        if (elements.transferTargetUUID instanceof HTMLInputElement) {
          elements.transferTargetUUID.value = match.userUUID;
        }
      } else {
        elements.transferTarget.value = '';
      }
    }

    if (elements.transferNotice) {
      if (count === 0) {
        elements.transferNotice.textContent = T.noRelationships;
      } else {
        elements.transferNotice.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TRANSFER_SELECT_MEMBER')); ?>';
      }
    }
  };

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
    'relationship.revoked': T.signalAccessRevoked,
    'relationship.withdrawn': T.signalMemberLeftBusiness,
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
      'relationship.revoked',
      'relationship.withdrawn',
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
        loadBusinessRelationships(businessId).catch((error) => PW.error(error));
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
        loadBusinessRelationships(businessId).catch((error) => PW.error(error));
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
    announceDiscoveryStatus(
      T.discoveryResultsCount
        .replace('%d', String(rows.length))
        .replace('%s', rows.length === 1 ? '' : 's'),
    );
  };

  const loadBusinessInvites = async (businessId) => {
    const business = findBusiness(businessId);
    if (business && !canUsePremiumOrgFeatures(business)) {
      if (elements.invitesList) {
        setStackMessage(elements.invitesList, T.premiumAdminLockedDetailed);
      }
      if (elements.membersInvitesList) {
        setStackMessage(elements.membersInvitesList, T.premiumAdminLockedDetailed);
      }
      if (elements.membersInviteHistoryGridContainer) {
        setDatagridMessage(elements.membersInviteHistoryGridContainer, T.premiumAdminLockedDetailed);
      }
      announceInvitesStatus(T.invitesPremiumLocked);
      return;
    }

    if (business && !canManageBusinessAccess(business)) {
      if (elements.invitesList) {
        setStackMessage(elements.invitesList, ACCESS_MANAGE_WARNING);
      }
      if (elements.membersInvitesList) {
        setStackMessage(elements.membersInvitesList, ACCESS_MANAGE_WARNING);
      }
      if (elements.membersInviteHistoryGridContainer) {
        setDatagridMessage(elements.membersInviteHistoryGridContainer, ACCESS_MANAGE_WARNING);
      }
      announceInvitesStatus(ACCESS_MANAGE_WARNING);
      return;
    }

    try {
      const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/invites`);
      renderInvites(payload.invites || []);
    } catch (error) {
      PW.error(error);
      if (elements.invitesList) {
        setStackMessage(elements.invitesList, T.manageAccessUnavailable);
      }
      if (elements.membersInvitesList) {
        setStackMessage(elements.membersInvitesList, T.manageAccessUnavailable);
      }
      announceInvitesStatus(T.invitesLoadFailed);
    }
  };

  const loadBusinessAccessRequests = async (businessId) => {
    if (!(elements.accessRequestsList instanceof HTMLElement)) {
      return;
    }

    const business = findBusiness(businessId);
    if (business && !canUsePremiumOrgFeatures(business)) {
      if (elements.accessRequestsList) {
        setStackMessage(elements.accessRequestsList, T.premiumAdminLockedDetailed);
      }
      announceAccessRequestsStatus(T.accessRequestsPremiumLocked);
      return;
    }

    if (business && !canManageBusinessAccess(business)) {
      if (elements.accessRequestsList) {
        setStackMessage(elements.accessRequestsList, ACCESS_MANAGE_WARNING);
      }
      announceAccessRequestsStatus(ACCESS_MANAGE_WARNING);
      return;
    }

    try {
      const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/access/requests`);
      renderAccessRequests(payload.requests || []);
    } catch (error) {
      PW.error(error);
      if (elements.accessRequestsList) {
        setStackMessage(elements.accessRequestsList, T.manageAccessUnavailable);
      }
      announceAccessRequestsStatus(T.accessRequestsLoadFailed);
    }
  };

  const loadBusinessRelationships = async (businessId) => {
    const business = findBusiness(businessId);
    const hasGovernanceList = elements.relationshipsList instanceof HTMLElement;

    if (business && !canUsePremiumOrgFeatures(business)) {
      renderRelationships([]);
      if (hasGovernanceList) {
        renderGovernanceRelationshipsList([]);
      }
      if (elements.transferNotice) {
        elements.transferNotice.textContent = T.premiumAdminLockedDetailed;
      }
      return;
    }

    if (business && !canManageBusinessAccess(business)) {
      renderRelationships([]);
      if (hasGovernanceList) {
        renderGovernanceRelationshipsList([]);
      }
      if (elements.transferNotice) {
        elements.transferNotice.textContent = ACCESS_MANAGE_WARNING;
      }
      if (hasGovernanceList) {
        announceRelationshipsStatus(ACCESS_MANAGE_WARNING);
      }
      return;
    }

    try {
      const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/relationships`);
      const transferMembers = Array.isArray(payload.members) ? payload.members : (payload.relationships || []);
      renderRelationships(transferMembers);
      if (hasGovernanceList) {
        renderGovernanceRelationshipsList(transferMembers);
      }
    } catch (error) {
      PW.error(error);
      renderRelationships([]);
      if (hasGovernanceList) {
        renderGovernanceRelationshipsList([]);
      }
      if (elements.transferNotice) {
        elements.transferNotice.textContent = T.manageAccessUnavailable;
      }
      if (hasGovernanceList) {
        announceRelationshipsStatus(T.manageAccessUnavailable);
      }
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

  const loadBusinessSettings = async (businessId) => {
    const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/settings`);
    const business = findBusiness(businessId);
    hydrateSettings(payload, business);
    return payload;
  };


  const openBusinessDialog = async (businessId) => {
    if (resolveBusinessSubPage() === 'members') {
      await openMembersPage(businessId);
      return;
    }

    if (resolveBusinessSubPage() === 'sites') {
      await openSitesPage(businessId);
      return;
    }

    if (resolveBusinessSubPage() === 'payroll') {
      await openPayrollPage(businessId);
      return;
    }

    if (resolveBusinessSubPage() === 'audit') {
      await openAuditPage(businessId);
      return;
    }

  };

  const refreshIndex = async (preferredBusinessId = '', reopenDialog = false) => {
    const membersPerf = resolveMembersLensPerf();
    const runRefresh = async () => {
      try {
        await loadBusinesses();
        applySingleBusinessOverviewMode();
        if (!shouldUseSingleBusinessOverview()) {
          await loadGrid();
        }
        if (resolveBusinessSubPage() === '') {
          await loadPersonalBusinessPanel();
        }

        const businessId = resolveWorkspaceBusinessId(preferredBusinessId);

        if (resolveBusinessSubPage() === 'dashboard') {
          if (businessId !== '' && findBusiness(businessId)) {
            await refreshDashboardWorkspace(businessId);
          }
          return;
        }

        if (resolveBusinessSubPage() === 'details') {
          const organizationBusinessId = resolveWorkspaceBusinessId(preferredBusinessId);

          if (organizationBusinessId !== '' && findBusiness(organizationBusinessId)) {
            await openDetailsPage(organizationBusinessId);
          }
          return;
        }

        if (resolveBusinessSubPage() === 'members') {
          const membersBusinessId = resolveWorkspaceBusinessId(preferredBusinessId);

          if (membersBusinessId !== '') {
            await openMembersPage(membersBusinessId);
          } else {
            await loadBusinessMembersGrid('');
          }
          return;
        }

        if (resolveBusinessSubPage() === 'sites') {
          const sitesBusinessId = resolveWorkspaceBusinessId(preferredBusinessId);

          if (sitesBusinessId !== '' && findBusiness(sitesBusinessId)) {
            await openSitesPage(sitesBusinessId);
          }
          return;
        }

        if (resolveBusinessSubPage() === 'payroll') {
          const payrollBusinessId = resolveWorkspaceBusinessId(preferredBusinessId);

          if (payrollBusinessId !== '' && findBusiness(payrollBusinessId)) {
            await openPayrollPage(payrollBusinessId);
          }
          return;
        }

        if (resolveBusinessSubPage() === 'audit') {
          const auditBusinessId = resolveWorkspaceBusinessId(preferredBusinessId);

          if (auditBusinessId !== '' && findBusiness(auditBusinessId)) {
            await openAuditPage(auditBusinessId);
          }
          return;
        }

        if (resolveBusinessSubPage() === 'reports') {
          const reportsBusinessId = resolveWorkspaceBusinessId(preferredBusinessId);

          await openBusinessReportsPage(reportsBusinessId);
          return;
        }

        if (resolveBusinessSubPage() !== 'sites') {
          stopDiscoveryPolling();
        }

        if (resolveBusinessSubPage() !== 'audit') {
          stopRealtimeAuditPolling();
        }

        if (preferredBusinessId !== '' && reopenDialog) {
          await openBusinessDialog(preferredBusinessId);
          return;
        }

        if (state.selectedBusinessId !== '' && findBusiness(state.selectedBusinessId) === null) {
          state.selectedBusinessId = '';
          closeDialog();
        }
      } catch (error) {
        PW.error(error);
        PC.showToast(T.loadOrgsFailed, 'error', 7000, true);
      }
    };

    if (membersPerf?.isEnabled()) {
      await membersPerf.measure('refreshIndex', runRefresh, { ranked: false });
      return;
    }

    await runRefresh();
  };

  const handleSearchBusinesses = async (event) => {
    event.preventDefault();
    const query = elements.searchInput instanceof HTMLInputElement
      ? String(elements.searchInput.value || '').trimStart()
      : '';

    if (elements.searchInput instanceof HTMLInputElement) {
      elements.searchInput.value = query;
    }

    state.grid.search = query;
    state.grid.page = '1';
    await loadGrid({ search: query, page: '1' });
    setDiscoveryPanelStatus(
      query === '' ? T.discoverySearchCleared : T.discoverySearchApplied.replace('%s', query),
    );
  };

  const handleRequestJoinBusiness = async (event) => {
    event.preventDefault();

    const rawLookup = elements.requestEmail instanceof HTMLInputElement
      ? String(elements.requestEmail.value || '').trim()
      : '';
    const contactEmail = extractLookupEmail(rawLookup);

    if (contactEmail === '') {
      PC.showToast(T.inviteEmailOrResult, 'error', 5000, true);
      return;
    }

    const accessLevel = state.requestAccessLevel || 'full';

    try {
      const payload = await postForm('/api/v1/businesses/access/request', {
        owner_email: contactEmail,
        access_level: accessLevel,
      });

      if (elements.requestEmail instanceof HTMLInputElement) {
        elements.requestEmail.value = '';
      }

      const requestId = String(payload?.request_id || '');
      const statusText = requestId === ''
        ? T.requestJoinPending
        : T.accessRequestSubmittedId.replace('%s', requestId);

      setDiscoveryPanelStatus(statusText);
      PC.showToast(statusText, 'save', 7000, true);
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message ? error.message : T.requestJoinFailed;
      setDiscoveryPanelStatus(message);
      PC.showToast(message, 'error', 7000, true);
    }
  };

  const handleSaveBusiness = async () => {
    if (state.selectedBusinessId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    if (state.editorSaveInFlight) {
      showBusinessesToast(T.updateInProgress, 'save', 2600, true);
      return;
    }

    const pendingPayload = collectBusinessEditorPayload();
    const pendingSignature = buildEditorPayloadSignature(pendingPayload);
    if (pendingSignature === state.editorLastSavedSignature) {
      showBusinessesToast(T.noChangesToUpdate, 'save', 2600, true);
      return;
    }

    await saveBusinessEditorSettings('manual', true);
  };

  const handleGenerateAuditControlTest = async () => {
    if (state.selectedBusinessId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    const business = getSelectedBusiness();
    if (!business || !canGenerateAuditControlTest(business)) {
      setAuditControlTestStatus(T.auditControlTestDenied, 'error');
      PC.showToast(T.auditControlTestDenied, 'error', 7000, true);
      return;
    }

    const summary = elements.auditControlTestSummary instanceof HTMLInputElement
      ? String(elements.auditControlTestSummary.value || '').trim()
      : '';

    if (elements.auditControlTestButton instanceof HTMLButtonElement) {
      elements.auditControlTestButton.disabled = true;
    }
    setAuditControlTestStatus(T.auditControlTestGenerating);

    try {
      const payload = await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/audit/control-test`, {
        summary,
      });
      const gcs = payload && typeof payload === 'object' ? payload.gcs || {} : {};
      const objectPath = String(gcs.object_path || '');
      const message = objectPath === ''
        ? T.auditControlTestRecorded
        : T.auditControlTestRecordedUploaded.replace('%s', objectPath);

      setAuditControlTestStatus(message, 'success');
      showBusinessesToast(message, 'save', 6000, true);
      await loadBusinessAudit(state.selectedBusinessId);
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message
        ? error.message
        : T.auditControlTestFailed;
      setAuditControlTestStatus(message, 'error');
      showBusinessesToast(message, 'error', 7000, true);
    } finally {
      if (elements.auditControlTestButton instanceof HTMLButtonElement) {
        elements.auditControlTestButton.disabled = !canGenerateAuditControlTest(getSelectedBusiness());
      }
    }
  };

  const handleBootstrapBusinessDek = async () => {
    if (state.selectedBusinessId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    if (elements.bootstrapDekButton instanceof HTMLButtonElement) {
      elements.bootstrapDekButton.disabled = true;
    }

    try {
      const payload = await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/encryption/bootstrap`, {
        segment: 'current_period',
        version: '1',
      });

      const bootstrappedCount = Number(payload && payload.bootstrapped_count ? payload.bootstrapped_count : 0);
      const failedCount = Number(payload && payload.failed_count ? payload.failed_count : 0);
      const message = failedCount > 0
        ? `${T.orgDekBootstrapDone} ${bootstrappedCount} member(s) bootstrapped, ${failedCount} failed.`
        : `${T.orgDekBootstrapDone} ${bootstrappedCount} member(s) bootstrapped.`;

      announceLiveToast(message, 'save');
      PC.showToast(message, failedCount > 0 ? 'error' : 'save', failedCount > 0 ? 9000 : 7000, true);
      await refreshIndex(state.selectedBusinessId, true);
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message
        ? error.message
        : T.orgDekBootstrapFailed;
      announceLiveToast(message, 'error');
      PC.showToast(message, 'error', 9000, true);
    } finally {
      const selectedOrg = getSelectedBusiness();
      const locked = !!selectedOrg && !canUsePremiumOrgFeatures(selectedOrg);
      if (elements.bootstrapDekButton instanceof HTMLButtonElement) {
        elements.bootstrapDekButton.disabled = locked;
      }
    }
  };

  const handleSendInvite = async () => {
    if (state.selectedBusinessId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      return;
    }

    const email = elements.inviteEmail instanceof HTMLInputElement ? elements.inviteEmail.value.trim() : '';
    const scopes = getSelectedInviteScopes();

    if (email === '') {
      PC.showToast(T.enterInviteEmail, 'error', 5000, true);
      return;
    }

    if (scopes.length === 0) {
      announceScopeSelectionStatus('required');
      PC.showToast(T.selectScope, 'error', 5000, true);
      return;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/invites/send`, {
        email,
        scopes,
      });

      if (elements.inviteEmail instanceof HTMLInputElement) {
        elements.inviteEmail.value = '';
      }
      document.querySelectorAll('#businesses_scope_grid .businesses_scope').forEach((input) => {
        if (input instanceof HTMLInputElement) {
          input.checked = false;
        }
      });
      announceScopeSelectionStatus('cleared');

      PC.showToast(T.inviteSent, 'save', 5000, true);
      await loadBusinessInvites(state.selectedBusinessId);
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.inviteSendFailed, 'error', 7000, true);
    }
  };

  const handleTransferOwnership = async () => {
    if (state.selectedBusinessId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    syncTransferTargetFromLookup();
    const targetUUID = elements.transferTargetUUID instanceof HTMLInputElement
      ? String(elements.transferTargetUUID.value || '').trim()
      : '';
    if (targetUUID === '') {
      PC.showToast(T.selectTransferTarget, 'error', 5000, true);
      return;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/ownership/transfer`, {
        target_user_uuid: targetUUID,
      });
      PC.showToast(T.ownershipTransferred, 'save', 6000, true);
      await refreshIndex(state.selectedBusinessId, true);
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.ownershipTransferFailed, 'error', 7000, true);
    }
  };

  const handleLeaveBusiness = async () => {
    if (state.selectedBusinessId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/leave`, {});
      PC.showToast(T.withdrawn, 'save', 5000, true);
      closeDialog();
      await refreshIndex();
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.withdrawFailed, 'error', 7000, true);
    }
  };

  const handleRemoveBusinessFromGrid = async (businessId) => {
    if (businessId === '') {
      return;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(businessId)}/leave`, {});
      PC.showToast(T.withdrawn, 'save', 5000, true);

      if (state.selectedBusinessId === businessId) {
        state.selectedBusinessId = '';
        closeDialog();
      }

      await refreshIndex();
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.removeFailed, 'error', 7000, true);
    }
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

  const handleLinkAction = async (action, dataset) => {
    if (state.selectedBusinessId === '') {
      PC.showToast(T.selectFirst, 'error', 5000, true);
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    try {
      if (action === 'link-site') {
        await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/sites/link`, {
          site_id: String(dataset.siteId || ''),
          site_owner_uuid: String(dataset.siteOwnerUuid || ''),
        });
        PC.showToast(T.siteLinked, 'save', 5000, true);
      }

      await handleDiscovery(false);
      if (state.selectedBusinessId !== '') {
        await loadBusinessSitesGrid(state.selectedBusinessId);
      }
    } catch (error) {
      PW.error(error);
      PC.showToast(
        T.siteLinkFailed,
        'error',
        7000,
        true
      );
    }
  };

  const handleRevokeInvite = async (inviteId) => {
    if (state.selectedBusinessId === '' || inviteId === '') {
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      return;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/invites/revoke`, {
        invite_id: inviteId,
      });
      PC.showToast(T.inviteRevoked, 'save', 4000, true);
      await Promise.allSettled([
        loadBusinessInvites(state.selectedBusinessId),
        typeof loadBusinessMembersPendingList === 'function'
          ? loadBusinessMembersPendingList(state.selectedBusinessId)
          : Promise.resolve(),
        loadBusinessInviteHistoryGrid(state.selectedBusinessId),
        loadBusinessAudit(state.selectedBusinessId),
      ]);
    } catch (error) {
      PW.error(error);
      PC.showToast(T.inviteRevokeFailed, 'error', 7000, true);
    }
  };

  const handleApproveAccessRequest = async (requestId) => {
    if (state.selectedBusinessId === '' || requestId === '') {
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      return;
    }

    const consentContext = await promptMembershipConsent('Approve access request');
    if (consentContext === null) {
      return;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/access/requests/approve`, {
        request_id: requestId,
        ...consentContext,
      });
      PC.showToast(T.accessRequestApproved, 'save', 5000, true);
      await Promise.allSettled([
        loadBusinessRelationships(state.selectedBusinessId),
        loadMembers(),
        typeof loadBusinessMembersPendingList === 'function'
          ? loadBusinessMembersPendingList(state.selectedBusinessId)
          : Promise.resolve(),
        loadBusinessInviteHistoryGrid(state.selectedBusinessId),
      ]);
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.accessRequestActionFailed, 'error', 7000, true);
    }
  };

  const handleRejectAccessRequest = async (requestId) => {
    if (state.selectedBusinessId === '' || requestId === '') {
      return;
    }

    if (blockPremiumActionWhenLocked()) {
      return;
    }

    if (blockAccessManagementActionWhenLocked()) {
      return;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/access/requests/reject`, {
        request_id: requestId,
      });
      PC.showToast(T.accessRequestRejected, 'save', 5000, true);
      await Promise.allSettled([
        loadBusinessInviteHistoryGrid(state.selectedBusinessId),
        loadMembers(),
        typeof loadBusinessMembersPendingList === 'function'
          ? loadBusinessMembersPendingList(state.selectedBusinessId)
          : Promise.resolve(),
      ]);
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.accessRequestActionFailed, 'error', 7000, true);
    }
  };

  const handleLiveRequestAction = async (businessId, requestId, action) => {
    const orgId = String(businessId || '').trim();
    const reqId = String(requestId || '').trim();
    const normalizedAction = String(action || '').trim().toLowerCase();

    if (orgId === '' || reqId === '' || !['approve', 'reject'].includes(normalizedAction)) {
      return;
    }

    const org = findBusiness(orgId);
    if (org && !canManageBusinessAccess(org)) {
      showAccessManagementDeniedWarning();
      showBusinessesToast(ACCESS_MANAGE_WARNING, 'error', 7000, true);
      return;
    }

    let consentContext = {};
    if (normalizedAction === 'approve') {
      const consentResult = await promptMembershipConsent('Approve access request');
      if (consentResult === null) {
        return;
      }
      consentContext = consentResult;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(orgId)}/access/requests/${encodeURIComponent(normalizedAction)}`, {
        request_id: reqId,
        ...consentContext,
      });

      showBusinessesToast(normalizedAction === 'approve' ? T.accessRequestApproved : T.accessRequestRejected, 'save', 4200, true);

      if (state.selectedBusinessId === orgId) {
        await Promise.allSettled([
          loadBusinessRelationships(orgId),
          loadMembers(),
          loadBusinessInviteHistoryGrid(orgId),
        ]);
      }
    } catch (error) {
      PW.error(error);
      showBusinessesToast(error instanceof Error && error.message ? error.message : T.accessRequestActionFailed, 'error', 7000, true);
    }
  };

  const handleGridClick = async (event) => {
    const rowAction = event.target.closest('.datagrid_action[data-action]');
    if (rowAction && elements.gridContainer?.contains(rowAction)) {
      const action = String(rowAction.dataset.action || '');
      const businessId = String(rowAction.dataset.id || '');
      if (action === 'remove') {
        if (!(rowAction instanceof HTMLButtonElement)) {
          return;
        }

        const isConfirmed = rowAction.dataset.confirm === '1' && state.inlineDeleteConfirmOrgId === businessId;
        if (!isConfirmed) {
          armInlineDeleteConfirm(rowAction, businessId);
          return;
        }

        resetInlineDeleteConfirm();
        await handleRemoveBusinessFromGrid(businessId);
      }
      return;
    }

    const sortButton = event.target.closest('.datagrid_sort');
    if (sortButton && elements.gridContainer?.contains(sortButton)) {
      const column = String(sortButton.dataset.column || 'name');
      const nextDirection = state.grid.sort === column && state.grid.direction === 'asc' ? 'desc' : 'asc';
      await loadGrid({ sort: column, direction: nextDirection, page: '1' });
      return;
    }

    const actionButton = event.target.closest('[data-org-action]');
    if (actionButton) {
      const action = String(actionButton.dataset.orgAction || '');
      if (action === 'revoke-invite') {
        await handleRevokeInvite(String(actionButton.dataset.inviteId || ''));
      }
      if (action === 'approve-access-request') {
        await handleApproveAccessRequest(String(actionButton.dataset.requestId || ''));
      }
      if (action === 'reject-access-request') {
        await handleRejectAccessRequest(String(actionButton.dataset.requestId || ''));
      }
      if (action === 'link-site') {
        await handleLinkAction(action, actionButton.dataset);
      }
      return;
    }

    const row = event.target.closest('.datagrid_row');
    if (!row || !elements.gridContainer?.contains(row)) {
      return;
    }

    const businessId = String(row.dataset.id || '');
    if (businessId !== '') {
      await openBusinessDialog(businessId);
    }
  };

  const handleGridInput = (event) => {
    const searchInput = event.target.closest('.datagrid_search');
    if (!searchInput) {
      return;
    }

    // Business workspace pages delegate grid search to createDataGrid instances
    // (standalone /sites/ keeps createDataGrid's own input listener).
    let datagridHost = searchInput.parentElement;
    while (datagridHost instanceof HTMLElement) {
      const instance = datagridHost.__datagridInstance;
      if (instance && typeof instance.setSearch === 'function') {
        const search = String(searchInput.value || '').trim();
        if (state.searchDebounceId !== null) {
          window.clearTimeout(state.searchDebounceId);
        }
        state.searchDebounceId = window.setTimeout(() => {
          instance.setSearch(search);
        }, 250);
        return;
      }
      datagridHost = datagridHost.parentElement;
    }

    const search = String(searchInput.value || '').trim();

    if (state.searchDebounceId !== null) {
      window.clearTimeout(state.searchDebounceId);
    }

    if (elements.gridContainer?.contains(searchInput)) {
      state.grid.search = search;
      state.grid.page = '1';
      state.searchDebounceId = window.setTimeout(() => {
        loadGrid({ search, page: '1' })
          .then(() => searchInput.focus())
          .catch((error) => PW.error(error));
      }, 250);
      return;
    }

    const managerGridContainers = [
      { container: elements.auditGridContainer, managerKey: 'auditGridManager' },
      { container: elements.freeAuditGridContainer, managerKey: 'freeAuditGridManager' },
      { container: elements.membersInviteHistoryGridContainer, managerKey: 'inviteHistoryGridManager' },
      { container: elements.membersGridContainer, managerKey: 'membersGridManager' },
    ];

    for (const { container, managerKey } of managerGridContainers) {
      if (container instanceof HTMLElement && container.contains(searchInput)) {
        let manager = state[managerKey];
        if (
          !manager
          && managerKey === 'membersGridManager'
          && typeof ensureBusinessMembersGridManager === 'function'
        ) {
          const orgId = String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
          if (orgId !== '') {
            manager = ensureBusinessMembersGridManager(orgId);
          }
        }
        if (manager && typeof manager.setSearch === 'function') {
          state.searchDebounceId = window.setTimeout(() => {
            manager.setSearch(search);
          }, 250);
        }
        return;
      }
    }
  };

  const handleGridKeydown = async (event) => {
    if (event.key !== 'Enter' && event.key !== ' ') {
      return;
    }

    if (event.target.closest('.datagrid_actions')) {
      return;
    }

    const row = event.target.closest('.datagrid_row');
    if (!row || !elements.gridContainer?.contains(row)) {
      return;
    }

    event.preventDefault();
    const businessId = String(row?.dataset.id || '');
    if (businessId !== '') {
      await openBusinessDialog(businessId);
    }
  };

  const closeMembershipConsentDialog = () => {
    if (elements.membershipConsentDialog instanceof HTMLDialogElement && elements.membershipConsentDialog.open) {
      elements.membershipConsentDialog.close('cancel');
    }
  };

  const promptMembershipConsent = async (actionLabel) => {
    if (!(elements.membershipConsentDialog instanceof HTMLDialogElement)
      || !(elements.membershipConsentForm instanceof HTMLFormElement)
      || !(elements.membershipConsentAcknowledge instanceof HTMLInputElement)
      || !(elements.membershipConsentDisclaimer instanceof HTMLTextAreaElement)) {
      return {
        consent_acknowledged: '1',
        consent_version: 'v1',
        disclaimer_text: T.membershipConsentDefaultDisclaimer,
      };
    }

    elements.membershipConsentAcknowledge.checked = false;
    elements.membershipConsentDisclaimer.value = '';
    if (elements.membershipConsentAction instanceof HTMLElement) {
      elements.membershipConsentAction.textContent = String(actionLabel || T.membershipConsentIntro);
    }
    if (elements.membershipConsentError instanceof HTMLElement) {
      elements.membershipConsentError.textContent = '';
      elements.membershipConsentError.classList.add('hidden');
    }

    return await new Promise((resolve) => {
      let settled = false;

      const settle = (value) => {
        if (settled) {
          return;
        }
        settled = true;
        cleanup();
        resolve(value);
      };

      const onSubmit = (event) => {
        event.preventDefault();
        if (!elements.membershipConsentAcknowledge.checked) {
          if (elements.membershipConsentError instanceof HTMLElement) {
            elements.membershipConsentError.textContent = T.membershipConsentAckRequired;
            elements.membershipConsentError.classList.remove('hidden');
          }
          return;
        }

        const disclaimerInput = String(elements.membershipConsentDisclaimer.value || '').trim();
        settle({
          consent_acknowledged: '1',
          consent_version: 'v1',
          disclaimer_text: disclaimerInput === '' ? T.membershipConsentDefaultDisclaimer : disclaimerInput,
        });

        if (elements.membershipConsentDialog instanceof HTMLDialogElement && elements.membershipConsentDialog.open) {
          elements.membershipConsentDialog.close('confirm');
        }
      };

      const onCancelClick = (event) => {
        event.preventDefault();
        closeMembershipConsentDialog();
      };

      const onDialogClick = (event) => {
        if (event.target === elements.membershipConsentDialog) {
          closeMembershipConsentDialog();
        }
      };

      const onDialogClose = () => {
        if (!settled) {
          settle(null);
        }
      };

      const cleanup = () => {
        elements.membershipConsentForm?.removeEventListener('submit', onSubmit);
        elements.membershipConsentCancel?.removeEventListener('click', onCancelClick);
        elements.membershipConsentClose?.removeEventListener('click', onCancelClick);
        elements.membershipConsentDialog?.removeEventListener('click', onDialogClick);
        elements.membershipConsentDialog?.removeEventListener('close', onDialogClose);
      };

      elements.membershipConsentForm.addEventListener('submit', onSubmit);
      elements.membershipConsentCancel?.addEventListener('click', onCancelClick);
      elements.membershipConsentClose?.addEventListener('click', onCancelClick);
      elements.membershipConsentDialog.addEventListener('click', onDialogClick);
      elements.membershipConsentDialog.addEventListener('close', onDialogClose);

      elements.membershipConsentDialog.showModal();
      elements.membershipConsentAcknowledge.focus();
    });
  };

  const setFieldErrorState = (input, errorId, message) => {
    const errorElement = document.getElementById(errorId);
    if (input instanceof HTMLElement) {
      input.classList.toggle('input_error', message !== '');
      if (message !== '') {
        input.setAttribute('aria-invalid', 'true');
      } else {
        input.removeAttribute('aria-invalid');
      }
    }
    if (errorElement) {
      errorElement.textContent = message;
    }
  };

  const clearFieldErrorStates = (pairs) => {
    pairs.forEach(([inputId, errorId]) => {
      setFieldErrorState(document.getElementById(inputId), errorId, '');
    });
  };

  const clearFieldInvalidStates = (ids) => {
    ids.forEach((id) => {
      const el = document.getElementById(id);
      if (el) {
        el.classList.remove('input_error');
        el.removeAttribute('aria-invalid');
      }
    });
  };

  const bindProfileEditDetails = () => {
    const editDetailsForm = document.getElementById('edit_details_form');
    const editDetailsPhone = document.getElementById('edit_details_phone');
    const editDetailsEmail = document.getElementById('edit_details_email');

    if (!(editDetailsForm instanceof HTMLFormElement)) {
      return;
    }

    if (editDetailsEmail instanceof HTMLInputElement && editDetailsEmail.readOnly) {
      const originalEmailValue = String(editDetailsEmail.value || '');

      editDetailsEmail.addEventListener('mouseenter', () => {
        editDetailsEmail.value = 'Change Email';
      });

      editDetailsEmail.addEventListener('mouseleave', () => {
        editDetailsEmail.value = originalEmailValue;
      });

      editDetailsEmail.addEventListener('click', () => {
        editDetailsEmail.value = originalEmailValue;
        resetChangeEmailModal();
        PC.openModal('modal_change_email', 'Change Email');
      });
    }

    const buildSettingsPayloadSignature = () => {
      const formData = new FormData(editDetailsForm);
      const pairs = [];
      for (const [key, value] of formData.entries()) {
        if (key === 'csrf_token') {
          continue;
        }
        pairs.push(`${key}:${String(value)}`);
      }
      pairs.sort();
      return pairs.join('|');
    };

    let lastSubmittedSignature = buildSettingsPayloadSignature();

    const validationPairs = [
      ['edit_details_full_name', 'edit_details_full_name_error'],
      ['edit_details_phone', 'edit_details_phone_error'],
      ['edit_details_province', 'edit_details_province_error'],
      ['edit_details_address_line1', 'edit_details_address_line1_error'],
      ['edit_details_address_city', 'edit_details_address_city_error'],
      ['edit_details_address_postal', 'edit_details_address_postal_error'],
    ];

    const clearValidationState = () => {
      clearFieldErrorStates(validationPairs);
    };

    const validateForm = () => {
      clearValidationState();

      const fullNameInput = document.getElementById('edit_details_full_name');
      const phoneInput = document.getElementById('edit_details_phone');
      const provinceInput = document.getElementById('edit_details_province');

      let firstInvalidField = null;
      const markInvalid = (input, errorId, message) => {
        setFieldErrorState(input, errorId, message);
        if (!firstInvalidField && input instanceof HTMLElement) {
          firstInvalidField = input;
        }
      };

      const fullName = String(fullNameInput?.value || '').trim();
      if (fullName.length < 2) {
        markInvalid(fullNameInput, 'edit_details_full_name_error', 'Enter your full name.');
      }

      const phone = String(phoneInput?.value || '').trim();
      if (phone.length > 0 && !/^\(\d{3}\) \d{3}-\d{4}$/.test(phone)) {
        markInvalid(phoneInput, 'edit_details_phone_error', 'Use phone format (123) 456-7890.');
      }

      const province = String(provinceInput?.value || '').trim();
      if (province.length !== 2) {
        markInvalid(provinceInput, 'edit_details_province_error', 'Select a province.');
      }

      if (firstInvalidField) {
        PC.showToast(T.correctHighlightedFields, 'error');
        firstInvalidField.focus();
        return false;
      }

      return true;
    };

    if (editDetailsPhone instanceof HTMLInputElement) {
      PC.formatPhoneNumber(editDetailsPhone);
      editDetailsPhone.addEventListener('input', (event) => {
        if (event.target instanceof HTMLInputElement) {
          PC.formatPhoneNumber(event.target);
        }
      });
      editDetailsPhone.addEventListener('change', (event) => {
        if (event.target instanceof HTMLInputElement) {
          PC.formatPhoneNumber(event.target);
        }
      });
    }

    editDetailsForm.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') {
        return;
      }

      const target = event.target;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      // Allow finder inputs to keep their own Enter behavior for selection.
      if (target.id === 'businesses_personal_timezone_search' || target.id === 'businesses_personal_currency_search') {
        return;
      }

      if (target instanceof HTMLTextAreaElement) {
        return;
      }

      event.preventDefault();
      editDetailsForm.dispatchEvent(new Event('submit'));
    });

    editDetailsForm.addEventListener('submit', (event) => {
      event.preventDefault();

      const payloadSignature = buildSettingsPayloadSignature();
      if (payloadSignature === lastSubmittedSignature) {
        return;
      }

      if (!validateForm()) {
        return;
      }

      PC.showToast('<?php echo addslashes(org_js_index_i18n('UPDATING_INFO')); ?>...', 'working');

      const formData = new FormData(editDetailsForm);
      PC.updateResource('account/info', formData).then(() => {
        lastSubmittedSignature = payloadSignature;
        clearValidationState();
        PC.showToast('<?php echo addslashes(org_js_index_i18n('INFO_UPDATED')); ?>', 'save');
      }).catch((error) => {
        PC.showToast(T.saveAccountDetailsFailed, 'error');
        PW.error(error);
      });
    });

    // Auto-submit on field change with debounce
    let saveTimeout = null;
    const autoSaveFields = [
      'edit_details_full_name',
      'edit_details_phone',
      'edit_details_province',
      'edit_details_address_line1',
      'edit_details_address_city',
      'edit_details_address_postal'
    ];

    autoSaveFields.forEach((fieldId) => {
      const field = document.getElementById(fieldId);
      if (field) {
        const submitForm = () => {
          clearTimeout(saveTimeout);
          saveTimeout = setTimeout(() => {
            editDetailsForm.dispatchEvent(new Event('submit'));
          }, 800);
        };

        field.addEventListener('change', submitForm);
      }
    });
  };

  /* ── Change Email modal flow ── */

  const normalizeVerificationCode = (value) => String(value || '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);

  const updateChangeEmailVerifyState = () => {
    const verifyBtn = document.getElementById('change_email_verify_btn');
    const oldCodeInput = document.getElementById('change_email_old_code');
    const newCodeInput = document.getElementById('change_email_new_code');
    if (!verifyBtn || !oldCodeInput || !newCodeInput) {
      return;
    }

    const oldCode = normalizeVerificationCode(oldCodeInput.value);
    const newCode = normalizeVerificationCode(newCodeInput.value);
    const canVerify = oldCode.length >= 6 && newCode.length >= 6;

    verifyBtn.disabled = !canVerify;
    verifyBtn.setAttribute('aria-disabled', canVerify ? 'false' : 'true');
  };

  const toggleChangeEmailStep = (showStep2) => {
    const step1 = document.getElementById('change_email_step1_section');
    const step2 = document.getElementById('change_email_step2_section');
    const startBtn = document.getElementById('change_email_start_btn');
    const verifyBtn = document.getElementById('change_email_verify_btn');
    const resendBtn = document.getElementById('change_email_resend_btn');
    const prevBtn = document.getElementById('change_email_prev_btn');

    if (step1) step1.hidden = !!showStep2;
    if (step2) step2.hidden = !showStep2;
    if (startBtn) startBtn.hidden = !!showStep2;
    if (verifyBtn) verifyBtn.hidden = !showStep2;
    if (resendBtn) resendBtn.hidden = !showStep2;
    if (prevBtn) prevBtn.textContent = showStep2 ? T.previous : T.cancel;

    updateChangeEmailVerifyState();
  };

  const resetChangeEmailModal = () => {
    document.getElementById('change_email_form')?.reset();
    const status = document.getElementById('change_email_status');
    const verifyStatus = document.getElementById('change_email_verify_status');
    const txn = document.getElementById('change_email_txn_id');
    const expiry = document.getElementById('change_email_expiry_timer');
    const oldHint = document.getElementById('old_email_hint');
    const newHint = document.getElementById('new_email_hint');
    if (status) status.textContent = '';
    if (verifyStatus) verifyStatus.textContent = '';
    if (txn) txn.value = '';
    if (expiry) expiry.textContent = '';
    if (oldHint) oldHint.textContent = '';
    if (newHint) newHint.textContent = '';
    clearFieldInvalidStates([
      'change_email_new_email',
      'change_email_confirm_email',
      'change_email_old_code',
      'change_email_new_code',
    ]);
    clearFieldErrorStates([
      ['change_email_new_email', 'change_email_new_email_error'],
      ['change_email_confirm_email', 'change_email_confirm_email_error'],
      ['change_email_old_code', 'change_email_old_code_error'],
      ['change_email_new_code', 'change_email_new_code_error'],
    ]);
    toggleChangeEmailStep(false);
  };

  const attachChangeEmailCodeInputHandlers = () => {
    ['change_email_old_code', 'change_email_new_code'].forEach((id) => {
      const input = document.getElementById(id);
      if (!input) {
        return;
      }

      const syncInput = () => {
        const normalized = normalizeVerificationCode(input.value);
        if (input.value !== normalized) {
          input.value = normalized;
        }
        const errorId = id === 'change_email_old_code' ? 'change_email_old_code_error' : 'change_email_new_code_error';
        setFieldErrorState(input, errorId, '');
        updateChangeEmailVerifyState();
      };

      input.addEventListener('input', syncInput);
      input.addEventListener('blur', syncInput);
    });
  };

  const parseChangeEmailApiResponse = async (response) => {
    const raw = await response.text();
    let data = null;
    try {
      data = JSON.parse(raw);
    } catch (_error) {
      data = null;
    }
    return { data, raw };
  };

  const CHANGE_EMAIL_I18N = {
    enterBothEmails: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_ENTER_BOTH_EMAILS')); ?>',
    enterNewEmail: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_ENTER_NEW_EMAIL')); ?>',
    confirmNewEmail: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_CONFIRM_NEW_EMAIL')); ?>',
    emailsNoMatch: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_EMAILS_NO_MATCH')); ?>',
    emailsMustMatch: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_EMAILS_MUST_MATCH')); ?>',
    working: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_WORKING')); ?>',
    codesSent: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_CODES_SENT')); ?>',
    requestFailedPrefix: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_REQUEST_FAILED_PREFIX')); ?>',
    enterBothCodes: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_ENTER_BOTH_CODES')); ?>',
    enterValid6CharCode: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_ERROR_ENTER_VALID_6_CHAR_CODE')); ?>',
    emailUpdated: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_EMAIL_UPDATED')); ?>',
    sessionExpired: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_SESSION_EXPIRED')); ?>',
    codesExpireMinutes: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_CODES_EXPIRE_MINUTES')); ?>',
    sendFailed: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_SEND_FAILED')); ?>',
    verifyFailed: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_VERIFY_FAILED')); ?>',
    resendFailed: '<?php echo addslashes(org_js_index_i18n('CHANGE_EMAIL_STATUS_RESEND_FAILED')); ?>',
  };

  const bindChangeEmail = () => {
    const hasChangeEmailUi = Boolean(
      document.getElementById('change_email_prev_btn')
      && document.getElementById('change_email_start_btn')
      && document.getElementById('change_email_verify_btn')
      && document.getElementById('change_email_resend_btn')
    );
    if (!hasChangeEmailUi) {
      return;
    }

    attachChangeEmailCodeInputHandlers();

    PC.addClickAndEnterListener('change_email_prev_btn', (e) => {
      e.preventDefault();
      const step2 = document.getElementById('change_email_step2_section');
      if (step2 && !step2.hidden) {
        toggleChangeEmailStep(false);
        return;
      }

      resetChangeEmailModal();
      PC.closeModal('modal_change_email', 'Change Email');
    });

    PC.addClickAndEnterListener('change_email_start_btn', async (e) => {
      e.preventDefault();
      const newEmailInput = document.getElementById('change_email_new_email');
      const confirmEmailInput = document.getElementById('change_email_confirm_email');
      const newEmail = String(newEmailInput?.value || '').trim();
      const confirmEmail = String(confirmEmailInput?.value || '').trim();
      const statusEl = document.getElementById('change_email_status');

      setFieldErrorState(newEmailInput, 'change_email_new_email_error', '');
      setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', '');

      if (!newEmail || !confirmEmail) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.enterBothEmails;
        if (!newEmail) {
          setFieldErrorState(newEmailInput, 'change_email_new_email_error', CHANGE_EMAIL_I18N.enterNewEmail);
        }
        if (!confirmEmail) {
          setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', CHANGE_EMAIL_I18N.confirmNewEmail);
        }
        (newEmail ? confirmEmailInput : newEmailInput)?.focus();
        return;
      }
      if (newEmail !== confirmEmail) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.emailsNoMatch;
        setFieldErrorState(newEmailInput, 'change_email_new_email_error', CHANGE_EMAIL_I18N.emailsMustMatch);
        setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', CHANGE_EMAIL_I18N.emailsMustMatch);
        confirmEmailInput?.focus();
        return;
      }

      try {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
        const response = await fetch('/api/v1/account/change-email/start', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ new_email: newEmail }),
        });
        const { data, raw } = await parseChangeEmailApiResponse(response);

        if (response.ok && data && data.status === 'success') {
          setFieldErrorState(newEmailInput, 'change_email_new_email_error', '');
          setFieldErrorState(confirmEmailInput, 'change_email_confirm_email_error', '');
          const txn = document.getElementById('change_email_txn_id');
          const oldHint = document.getElementById('old_email_hint');
          const newHint = document.getElementById('new_email_hint');
          const expiry = document.getElementById('change_email_expiry_timer');

          if (txn) txn.value = data.txn_id || '';
          if (oldHint) oldHint.textContent = data.old_email_hint || '';
          if (newHint) newHint.textContent = data.new_email_hint || '';
          if (expiry) {
            expiry.textContent = CHANGE_EMAIL_I18N.codesExpireMinutes.replace('%d', String(data.expires_in_minutes));
          }
          if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.codesSent;

          toggleChangeEmailStep(true);
          setTimeout(() => document.getElementById('change_email_old_code')?.focus(), 50);
        } else {
          const apiMessage = data && typeof data.message === 'string' ? data.message : '';
          const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
          if (statusEl) statusEl.textContent = apiMessage || CHANGE_EMAIL_I18N.sendFailed.replace('%s', fallback);
        }
      } catch (error) {
        if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || T.unknownError)}`;
        PW.error(error);
      }
    });

    PC.addClickAndEnterListener('change_email_verify_btn', async (e) => {
      e.preventDefault();
      const oldCodeInput = document.getElementById('change_email_old_code');
      const newCodeInput = document.getElementById('change_email_new_code');
      const txnId = String(document.getElementById('change_email_txn_id')?.value || '').trim();
      const oldCode = normalizeVerificationCode(oldCodeInput?.value || '');
      const newCode = normalizeVerificationCode(newCodeInput?.value || '');
      const statusEl = document.getElementById('change_email_verify_status');

      setFieldErrorState(oldCodeInput, 'change_email_old_code_error', '');
      setFieldErrorState(newCodeInput, 'change_email_new_code_error', '');

      if (!txnId || oldCode.length !== 6 || newCode.length !== 6) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.enterBothCodes;
        if (oldCode.length !== 6) {
          setFieldErrorState(oldCodeInput, 'change_email_old_code_error', CHANGE_EMAIL_I18N.enterValid6CharCode);
        }
        if (newCode.length !== 6) {
          setFieldErrorState(newCodeInput, 'change_email_new_code_error', CHANGE_EMAIL_I18N.enterValid6CharCode);
        }
        (oldCode.length !== 6 ? oldCodeInput : newCodeInput)?.focus();
        return;
      }

      try {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
        const response = await fetch('/api/v1/account/change-email/verify', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ txn_id: txnId, old_code: oldCode, new_code: newCode }),
        });
        const { data, raw } = await parseChangeEmailApiResponse(response);

        if (response.ok && data && data.status === 'success') {
          setFieldErrorState(oldCodeInput, 'change_email_old_code_error', '');
          setFieldErrorState(newCodeInput, 'change_email_new_code_error', '');
          if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.emailUpdated;
          setTimeout(() => {
            PC.closeModal('modal_change_email', 'Change Email');
            location.reload();
          }, 1000);
        } else if (statusEl) {
          const apiMessage = data && typeof data.message === 'string' ? data.message : '';
          const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
          const errorText = apiMessage || CHANGE_EMAIL_I18N.verifyFailed.replace('%s', fallback);
          statusEl.textContent = errorText;
          setFieldErrorState(oldCodeInput, 'change_email_old_code_error', errorText);
          setFieldErrorState(newCodeInput, 'change_email_new_code_error', errorText);
        }
      } catch (error) {
        if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || T.unknownError)}`;
        PW.error(error);
      }
    });

    PC.addClickAndEnterListener('change_email_resend_btn', async (e) => {
      e.preventDefault();
      const txnId = String(document.getElementById('change_email_txn_id')?.value || '').trim();
      const statusEl = document.getElementById('change_email_verify_status');
      if (!txnId) {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.sessionExpired;
        return;
      }

      try {
        if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.working;
        const response = await fetch('/api/v1/account/change-email/resend', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ txn_id: txnId }),
        });
        const { data, raw } = await parseChangeEmailApiResponse(response);
        if (response.ok && data && data.status === 'success') {
          if (statusEl) statusEl.textContent = CHANGE_EMAIL_I18N.codesSent;
        } else if (statusEl) {
          const apiMessage = data && typeof data.message === 'string' ? data.message : '';
          const fallback = raw ? raw.slice(0, 180) : `HTTP ${response.status}`;
          statusEl.textContent = apiMessage || CHANGE_EMAIL_I18N.resendFailed.replace('%s', fallback);
        }
      } catch (error) {
        if (statusEl) statusEl.textContent = `${CHANGE_EMAIL_I18N.requestFailedPrefix} ${String(error?.message || T.unknownError)}`;
        PW.error(error);
      }
    });
  };

  const bindDangerZone = () => {
    const deleteDataPill = document.getElementById('danger_delete_data_pill');
    const deleteDataPhraseInput = document.getElementById('danger_delete_data_phrase');
    const deleteDataConfirm = document.getElementById('danger_delete_data_confirm');

    const deleteAccountPill = document.getElementById('danger_delete_account_pill');
    const deleteAccountForm = document.getElementById('danger_delete_account_form');
    const deleteAccountPhraseInput = document.getElementById('danger_delete_account_phrase');
    const deleteAccountConfirm = document.getElementById('danger_delete_account_confirm');

    const dangerStatus = document.getElementById('danger_zone_status');

    if (!deleteDataPill && !deleteAccountPill) {
      return;
    }

    const updateDeleteDataConfirmState = () => {
      if (!(deleteDataPhraseInput instanceof HTMLInputElement) || !(deleteDataConfirm instanceof HTMLButtonElement)) {
        return;
      }

      const phrase = String(deleteDataPhraseInput.value || '').toUpperCase();
      deleteDataPhraseInput.value = phrase;
      deleteDataConfirm.disabled = phrase.trim() !== 'DELETE ALL DATA';
    };

    const updateDeleteAccountConfirmState = () => {
      if (!(deleteAccountPhraseInput instanceof HTMLInputElement) || !(deleteAccountConfirm instanceof HTMLButtonElement)) {
        return;
      }

      const phrase = String(deleteAccountPhraseInput.value || '').toUpperCase();
      deleteAccountPhraseInput.value = phrase;
      deleteAccountConfirm.disabled = phrase.trim() !== 'DELETE MY ACCOUNT';
    };

    deleteDataPhraseInput?.addEventListener('input', updateDeleteDataConfirmState);
    deleteAccountPhraseInput?.addEventListener('input', updateDeleteAccountConfirmState);

    updateDeleteDataConfirmState();
    updateDeleteAccountConfirmState();

    deleteAccountForm?.addEventListener('submit', (event) => {
      if (!(deleteAccountPhraseInput instanceof HTMLInputElement)) {
        return;
      }

      const phrase = String(deleteAccountPhraseInput.value || '').trim().toUpperCase();
      if (phrase !== 'DELETE MY ACCOUNT') {
        event.preventDefault();
        if (dangerStatus) {
          dangerStatus.textContent = T.deleteAccountTypePhrase;
        }
        deleteAccountPhraseInput.focus();
        deleteAccountPhraseInput.select();
      }
    });

    deleteDataConfirm?.addEventListener('click', async () => {
      if (!(deleteDataConfirm instanceof HTMLButtonElement) || !(deleteDataPhraseInput instanceof HTMLInputElement)) {
        return;
      }

      const phrase = String(deleteDataPhraseInput.value || '').trim().toUpperCase();
      if (phrase !== 'DELETE ALL DATA') {
        if (dangerStatus) {
          dangerStatus.textContent = T.deleteDataTypePhrase;
        }
        deleteDataPhraseInput.focus();
        deleteDataPhraseInput.select();
        return;
      }

      deleteDataConfirm.disabled = true;
      if (dangerStatus) {
        dangerStatus.textContent = T.deleteDataInProgress;
      }

      try {
        const formData = new FormData();
        formData.append('confirm_phrase', phrase);
        const settingsCsrfToken = String((document.getElementById('settings_csrf_token')?.value || '')).trim();
        if (settingsCsrfToken !== '') {
          formData.append('csrf_token', settingsCsrfToken);
        }

        const response = await fetch('/api/v1/account/data/delete/', {
          method: 'POST',
          credentials: 'same-origin',
          body: formData,
          headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        if (!response.ok || payload.status !== 'success') {
          throw new Error(payload.message || T.deleteDataFailed);
        }

        if (dangerStatus) {
          dangerStatus.textContent = T.deleteDataComplete;
        }
        if (deleteDataPill instanceof HTMLElement) {
          deleteDataPill.hidden = true;
        }
      } catch (error) {
        if (dangerStatus) {
          dangerStatus.textContent = error instanceof Error ? error.message : T.deleteDataFailed;
        }
      } finally {
        updateDeleteDataConfirmState();
      }
    });

    updateDeleteDataConfirmState();
    updateDeleteAccountConfirmState();
  };

  const formatActivityTimestamp = (unixSeconds) => {
    const value = Number(unixSeconds || 0);
    if (!Number.isFinite(value) || value <= 0) {
      return T.unknown;
    }

    return new Date(value * 1000).toLocaleString();
  };

  const createAccountActivityTimestampField = (unixSeconds, idSeed) => {
    const value = Number(unixSeconds || 0);
    if (!Number.isFinite(value) || value <= 0) {
      const fallback = document.createElement('span');
      fallback.textContent = T.unknown;
      return fallback;
    }

    const parsedDate = new Date(value * 1000);
    if (Number.isNaN(parsedDate.getTime())) {
      const fallback = document.createElement('span');
      fallback.textContent = T.unknown;
      return fallback;
    }

    const safeIdSeed = String(idSeed || 'activity')
      .replace(/[^a-zA-Z0-9_-]/g, '_')
      .slice(0, 64);
    const popoverId = `account_activity_timestamp_popover_${safeIdSeed}`;

    const field = document.createElement('span');
    field.className = 'account_activity_timestamp_field';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'account_activity_timestamp_trigger';
    trigger.textContent = formatTimestampInTimeZone(parsedDate, viewerTimeZone);
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-controls', popoverId);
    trigger.setAttribute('aria-expanded', 'false');

    const popover = document.createElement('div');
    popover.id = popoverId;
    popover.className = 'account_activity_timestamp_popover';
    popover.hidden = true;
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', T.timestampDetailsAria);

    buildTimestampZoneRows(parsedDate).forEach((row) => {
      const rowEl = document.createElement('span');
      rowEl.className = 'account_activity_timestamp_popover_row';

      const labelEl = document.createElement('span');
      labelEl.className = 'account_activity_timestamp_popover_label';
      labelEl.textContent = `${row.label}:`;

      const valueEl = document.createElement('span');
      valueEl.className = 'account_activity_timestamp_popover_value';
      valueEl.textContent = row.value;

      rowEl.appendChild(labelEl);
      rowEl.appendChild(valueEl);
      popover.appendChild(rowEl);
    });

    field.appendChild(trigger);
    field.appendChild(popover);

    if (elements.accountActivityPanel instanceof HTMLElement) {
      bindHistoryTimestampPopover(elements.accountActivityPanel, trigger, popover);
    }

    return field;
  };

  const renderActivityDefinitionList = (target, rows) => {
    if (!(target instanceof HTMLElement)) {
      return;
    }

    target.textContent = '';
    rows.forEach((row, index) => {
      const dt = document.createElement('dt');
      dt.textContent = String(row.label || '');
      const dd = document.createElement('dd');
      if (row.timestampValue !== undefined) {
        dd.appendChild(createAccountActivityTimestampField(row.timestampValue, `${row.label || 'timestamp'}_${index}`));
      } else {
        dd.textContent = String(row.value || '');
      }
      target.appendChild(dt);
      target.appendChild(dd);
    });
  };

  const renderActivitySessions = (sessions) => {
    if (!(elements.accountActivitySessions instanceof HTMLElement)) {
      return;
    }

    elements.accountActivitySessions.textContent = '';

    if (!Array.isArray(sessions) || sessions.length === 0) {
      const empty = document.createElement('p');
      empty.className = 'help_text';
      empty.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_ACTIVE_SESSIONS')); ?>';
      elements.accountActivitySessions.appendChild(empty);
      return;
    }

    sessions.forEach((session) => {
      const item = document.createElement('article');
      item.className = 'account_activity_session_item';
      if (session && session.is_current === true) {
        item.classList.add('account_activity_session_item_current');
      }

      const title = document.createElement('strong');
      const fingerprint = String(session?.session_fingerprint || T.valueUnknown);
      title.textContent = session && session.is_current === true
        ? T.accountActivityCurrentSession.replace('%s', fingerprint)
        : T.accountActivitySession.replace('%s', fingerprint);

      const meta = document.createElement('div');
      meta.className = 'account_activity_session_meta';

      const makeMetaSegment = (label, valueNodeOrText) => {
        const segment = document.createElement('span');
        segment.className = 'account_activity_session_meta_segment';

        const labelEl = document.createElement('span');
        labelEl.className = 'account_activity_session_meta_label';
        labelEl.textContent = `${label}: `;
        segment.appendChild(labelEl);

        if (valueNodeOrText instanceof Node) {
          segment.appendChild(valueNodeOrText);
        } else {
          const valueEl = document.createElement('span');
          valueEl.textContent = String(valueNodeOrText || T.unknown);
          segment.appendChild(valueEl);
        }

        return segment;
      };

      meta.appendChild(makeMetaSegment(T.accountActivityLastActivity, createAccountActivityTimestampField(session?.last_activity, `${session?.session_fingerprint || 'session'}_last_activity`)));
      meta.appendChild(makeMetaSegment(T.accountActivitySignedIn, createAccountActivityTimestampField(session?.created_at, `${session?.session_fingerprint || 'session'}_signed_in`)));
      meta.appendChild(makeMetaSegment(T.accountActivityIp, String(session?.last_ip || T.valueUnknown)));
      meta.appendChild(makeMetaSegment(T.accountActivityAuth, String(session?.auth_method || T.valueUnknown)));
      meta.appendChild(makeMetaSegment(T.accountActivityTtl, `${String(session?.ttl_seconds || 0)}s`));

      item.appendChild(title);
      item.appendChild(meta);
      elements.accountActivitySessions.appendChild(item);
    });
  };

  const loadAccountActivity = async () => {
    if (!(elements.accountActivityStatus instanceof HTMLElement)) {
      return;
    }

    elements.accountActivityStatus.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_LOADING_ACCOUNT_ACTIVITY')); ?>';

    try {
      const response = await fetch('/api/v1/user/account/activity', {
        method: 'GET',
        credentials: 'same-origin',
        headers: buildHeaders(),
      });

      if (!response.ok) {
        throw new Error(`Failed to load account activity (${response.status})`);
      }

      const payload = await response.json();
      const data = payload?.data && typeof payload.data === 'object' ? payload.data : {};
      const currentLogin = data?.current_login && typeof data.current_login === 'object' ? data.current_login : {};
      const browser = data?.browser && typeof data.browser === 'object' ? data.browser : {};
      const sessionData = data?.session_data && typeof data.session_data === 'object' ? data.session_data : {};
      const sessions = Array.isArray(sessionData.sessions) ? sessionData.sessions : [];

      renderActivityDefinitionList(elements.accountActivityLoginDetails, [
        { label: T.accountActivityIpAddress, value: String(currentLogin.ip_address || T.valueUnknown) },
        { label: T.accountActivitySignedIn, timestampValue: currentLogin.signed_in_at },
        { label: T.accountActivityLastActivity, timestampValue: currentLogin.last_activity_at },
        { label: T.accountActivityAuthMethod, value: String(currentLogin.auth_method || T.valueUnknown) },
        { label: T.accountActivityAuthStrength, value: String(currentLogin.auth_strength || T.valueUnknown) },
        { label: T.accountActivitySessionFingerprint, value: String(currentLogin.session_fingerprint || T.valueUnknown) },
      ]);

      renderActivityDefinitionList(elements.accountActivityBrowserDetails, [
        { label: T.accountActivityBrowser, value: `${String(browser.browser_name || T.unknown)} ${String(browser.browser_version || '').trim()}`.trim() },
        { label: T.accountActivityOperatingSystem, value: String(browser.os_name || T.unknown) },
        { label: T.accountActivityDeviceType, value: String(browser.device_type || T.unknown) },
        { label: T.accountActivityPlatform, value: String(browser.platform || T.unknown) },
        { label: T.accountActivityLanguage, value: String(browser.language || T.unknown) },
        { label: T.accountActivityUserAgent, value: String(browser.user_agent || T.unknown) },
      ]);

      renderActivitySessions(sessions);

      elements.accountActivityStatus.textContent = T.accountActivityLoaded
        .replace('%d', String(sessions.length))
        .replace('%s', sessions.length === 1 ? '' : 's');
    } catch (error) {
      PW.error(error);
      elements.accountActivityStatus.textContent = error instanceof Error
        ? error.message
        : T.accountActivityLoadFailed;
    }
  };

  const bindOrganizationContactPanelEvents = () => {
    mountContactImagePopover();

    const contactRoot = document.getElementById('business-workspace') ?? elements.dialog ?? document;
    formatPhoneInputsWithin(contactRoot);
    applyContactInputAriaLabels(contactRoot);

    document.querySelectorAll('.businesses_contact_image_input').forEach((field) => {
      if (field instanceof HTMLInputElement) {
        syncContactAvatarPreview(field);
      }
    });

    if (organizationContactPanelEventsBound) {
      return;
    }
    organizationContactPanelEventsBound = true;

    const handleContactCardPanelClick = (event) => {
      const menuToggle = event.target instanceof Element
        ? event.target.closest('.businesses_contact_card_menu_toggle')
        : null;
      if (menuToggle instanceof HTMLButtonElement) {
        const menu = menuToggle.closest('.businesses_contact_card_menu');
        if (menu instanceof HTMLElement) {
          const shouldOpen = !menu.classList.contains('is_open');
          closeAllContactCardMenus(shouldOpen ? menu : null);
          setContactCardMenuOpen(menu, shouldOpen);
        }
        return;
      }

      const deleteButton = event.target instanceof Element
        ? event.target.closest('.businesses_contact_card_menu_delete')
        : null;
      if (deleteButton instanceof HTMLButtonElement) {
        handleContactCardDeleteAction(deleteButton);
        return;
      }

      const avatar = resolveContactCardAvatar(event.target);
      if (!(avatar instanceof HTMLImageElement)) {
        return;
      }

      const targetField = getImageFieldForAvatar(avatar);
      if (!(targetField instanceof HTMLInputElement)) {
        return;
      }

      const anchor = resolveContactCardAvatarAnchor(avatar);
      if (!(anchor instanceof HTMLElement)) {
        return;
      }

      openContactImagePopover(targetField, anchor);
    };

    const businessWorkspace = document.getElementById('business-workspace');
    elements.dialog?.addEventListener('click', handleContactCardPanelClick);
    businessWorkspace?.addEventListener('click', handleContactCardPanelClick);

    const handleContactCardAvatarError = (event) => {
      const target = event.target;
      if (!(target instanceof HTMLImageElement) || !target.classList.contains('businesses_contact_card_avatar')) {
        return;
      }
      if (target.src !== CONTACT_AVATAR_PLACEHOLDER_SRC) {
        target.src = CONTACT_AVATAR_PLACEHOLDER_SRC;
      }
      target.alt = '';
      target.setAttribute('role', 'presentation');
    };

    elements.dialog?.addEventListener('error', handleContactCardAvatarError, true);
    businessWorkspace?.addEventListener('error', handleContactCardAvatarError, true);

    elements.contactImageDropzone?.addEventListener('click', () => {
      elements.contactImageFile?.click();
    });

    elements.contactImageDropzone?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        elements.contactImageFile?.click();
      }
    });

    elements.contactImageDropzone?.addEventListener('dragover', (event) => {
      event.preventDefault();
      elements.contactImageDropzone?.classList.add('is_dragover');
    });

    elements.contactImageDropzone?.addEventListener('dragleave', () => {
      elements.contactImageDropzone?.classList.remove('is_dragover');
    });

    elements.contactImageDropzone?.addEventListener('drop', (event) => {
      event.preventDefault();
      elements.contactImageDropzone?.classList.remove('is_dragover');
      const files = event.dataTransfer?.files;
      handleContactImageFiles(files).catch((error) => PW.error(error));
    });

    elements.contactImageFile?.addEventListener('change', () => {
      const files = elements.contactImageFile?.files;
      handleContactImageFiles(files).catch((error) => PW.error(error));
      if (elements.contactImageFile instanceof HTMLInputElement) {
        elements.contactImageFile.value = '';
      }
    });

    elements.contactImageClear?.addEventListener('click', () => {
      applyContactImageValue('');
    });

    elements.contactImageCancel?.addEventListener('click', () => {
      closeContactImagePopover({ restoreFocus: true });
    });

    elements.contactImagePopover?.addEventListener('keydown', (event) => {
      if (!isContactImagePopoverOpen()) {
        return;
      }

      trapContactImagePopoverFocus(event);
    });

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape' || !isContactImagePopoverOpen()) {
        return;
      }

      event.preventDefault();
      closeContactImagePopover({ restoreFocus: true });
    });

    document.addEventListener('mousedown', (event) => {
      const target = event.target;
      if (target instanceof Element && !target.closest('.businesses_contact_card_menu')) {
        closeAllContactCardMenus();
      }

      mountContactImagePopover();
      if (!(elements.contactImagePopover instanceof HTMLElement) || elements.contactImagePopover.classList.contains('hidden')) {
        return;
      }

      if (!(target instanceof Element)) {
        return;
      }

      const insidePopover = elements.contactImagePopover.contains(target);
      const onAvatarControl = target.closest('.businesses_contact_card_avatar')
        || target.closest('.businesses_contact_card_avatar_button');
      if (!insidePopover && !onAvatarControl) {
        closeContactImagePopover();
      }
    });
  };

  const bindEvents = () => {
    document.querySelectorAll('[data-business-launch="members"]').forEach((launchLink) => {
      launchLink.addEventListener('click', (event) => {
        event.preventDefault();
        const membersTab = document.getElementById('businesses_tab_members');
        if (membersTab instanceof HTMLButtonElement) {
          membersTab.click();
        }
        const membersPanel = document.getElementById('businesses_tab_members_panel');
        if (membersPanel instanceof HTMLElement) {
          membersPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });

    elements.searchForm?.addEventListener('submit', (event) => {
      handleSearchBusinesses(event).catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.loadOrgsFailed;
        setDiscoveryPanelStatus(message);
        PC.showToast(message, 'error', 7000, true);
      });
    });
    elements.requestJoinForm?.addEventListener('submit', (event) => {
      handleRequestJoinBusiness(event).catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.requestJoinFailed;
        setDiscoveryPanelStatus(message);
        setBrowserPanelStatus(message);
        PC.showToast(message, 'error', 7000, true);
      });
    });
    elements.browserSearchForm?.addEventListener('submit', (event) => {
      event.preventDefault();
      const query = elements.browserSearchInput instanceof HTMLInputElement
        ? String(elements.browserSearchInput.value || '')
        : '';
      runBrowserSearch(query).catch((error) => {
        PW.error(error);
        setBrowserPanelStatus('Business search failed. Try again.');
      });
    });
    elements.browserSearchInput?.addEventListener('input', () => {
      if (state.browserSearchDebounceId !== null) {
        window.clearTimeout(state.browserSearchDebounceId);
      }

      const query = elements.browserSearchInput instanceof HTMLInputElement
        ? String(elements.browserSearchInput.value || '')
        : '';

      state.browserSearchDebounceId = window.setTimeout(() => {
        runBrowserSearch(query).catch((error) => {
          PW.error(error);
          setBrowserPanelStatus('Business search failed. Try again.');
        });
        state.browserSearchDebounceId = null;
      }, ORG_BROWSER_SEARCH_DEBOUNCE_MS);
    });
    const handleBrowserGridAction = (event) => {
      const target = event.target instanceof Element
        ? event.target.closest('[data-browser-action="connect"]')
        : null;
      if (!(target instanceof HTMLButtonElement)) {
        return;
      }

      const email = String(target.dataset.email || '').trim();
      const businessName = String(target.dataset.orgName || '').trim();
      const ownerName = String(target.dataset.ownerName || '').trim();

      connectToBusinessFromBrowser(email, businessName, ownerName).catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.requestJoinFailed;
        setBrowserPanelStatus(message);
        setDiscoveryPanelStatus(message);
        PC.showToast(message, 'error', 7000, true);
      });
    };
    elements.browserGrid?.addEventListener('click', handleBrowserGridAction);
    elements.currentInfoLink?.addEventListener('click', () => {
      openCurrentBusinessDetailsDialog();
    });
    elements.currentRevokeButton?.addEventListener('click', () => {
      handleRevokeCurrentBusinessAccess().catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.withdrawFailed;
        setCurrentBusinessStatus(message, 'error');
        PC.showToast(message, 'error', 7000, true);
      });
    });
    document.querySelectorAll('[data-dialog-close="businesses_current_details_dialog"]').forEach((button) => {
      button.addEventListener('click', () => {
        closeCurrentBusinessDetailsDialog();
      });
    });
    elements.memberForm?.addEventListener('submit', (event) => {
      handleMemberPersonalBusiness(event).catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.inviteSendFailed;
        setDiscoveryPanelStatus(message);
        PC.showToast(message, 'error', 7000, true);
      });
    });
    bindAccessLookupInput(elements.requestEmail, elements.requestLookupDatalist);
    document.querySelectorAll('.businesses_access_level_pillbox .pill').forEach((pill) => {
      pill.addEventListener('click', (event) => {
        event.preventDefault();
        const button = event.currentTarget;
        if (!(button instanceof HTMLButtonElement)) {
          return;
        }
        const accessLevel = String(button.dataset.accessLevel || '').trim();
        if (accessLevel === '') {
          return;
        }
        state.requestAccessLevel = accessLevel;
        document.querySelectorAll('.businesses_access_level_pillbox .pill').forEach((p) => {
          p.classList.toggle('pill_selected', p === button);
          p.setAttribute('aria-pressed', p === button ? 'true' : 'false');
        });
      });
    });
    elements.bootstrapDekButton?.addEventListener('click', () => {
      handleBootstrapBusinessDek().catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message
          ? error.message
          : T.orgDekBootstrapFailed;
        announceLiveToast(message, 'error');
        PC.showToast(message, 'error', 9000, true);
      });
    });
    elements.saveButton?.addEventListener('click', handleSaveBusiness);
    elements.auditControlTestButton?.addEventListener('click', () => {
      handleGenerateAuditControlTest().catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message
          ? error.message
          : T.auditControlTestFailed;
        setAuditControlTestStatus(message, 'error');
        showBusinessesToast(message, 'error', 7000, true);
      });
    });
    elements.inviteSend?.addEventListener('click', handleSendInvite);
    elements.scopeGrid?.addEventListener('change', (event) => {
      const input = event.target;
      if (input instanceof HTMLInputElement && input.classList.contains('businesses_scope')) {
        announceScopeSelectionStatus('updated');
      }
    });
    elements.invitesReload?.addEventListener('click', () => {
      if (state.selectedBusinessId !== '') {
        loadBusinessInvites(state.selectedBusinessId).catch((error) => {
          PW.error(error);
          if (elements.invitesList instanceof HTMLElement) {
            setStackMessage(elements.invitesList, T.loadInvitesFailed);
          }
          PC.showToast(T.loadInvitesFailed, 'error', 7000, true);
        });
      }
    });
    elements.liveRequestsList?.addEventListener('click', (event) => {
      const target = event.target instanceof Element
        ? event.target.closest('[data-live-request-action]')
        : null;
      if (!(target instanceof HTMLElement)) {
        return;
      }

      const action = String(target.dataset.liveRequestAction || '');
      const businessId = String(target.dataset.liveOrgId || '');
      const requestId = String(target.dataset.liveRequestId || '');
      handleLiveRequestAction(businessId, requestId, action).catch((error) => {
        PW.error(error);
        announceLiveRequestsStatus('Unable to update access request.');
        PC.showToast(T.accessRequestActionFailed, 'error', 7000, true);
      });
    });
    elements.transferTarget?.addEventListener('input', () => {
      if (elements.transferTargetUUID instanceof HTMLInputElement) {
        elements.transferTargetUUID.value = '';
      }
      syncTransferTargetFromLookup();
    });
    elements.transferTarget?.addEventListener('change', () => {
      syncTransferTargetFromLookup();
      if (elements.transferTargetUUID instanceof HTMLInputElement && elements.transferTargetUUID.value === '') {
        PC.showToast(T.transferSelectFromList, 'error', 4500, true);
      }
    });
    elements.transferConfirmation?.addEventListener('input', () => {
      syncTransferConfirmation();
    });
    elements.transferConfirmation?.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter') {
        return;
      }

      const typed = event.currentTarget instanceof HTMLInputElement
        ? String(event.currentTarget.value || '')
        : '';
      const normalizedTyped = typed
        .toUpperCase()
        .replace(/[^A-Z\s]/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
      const ready = normalizedTyped === 'TRANSFER BUSINESS'
        && state.transferSelectedUUID !== ''
        && elements.transferButton instanceof HTMLButtonElement
        && !elements.transferButton.disabled;
      if (!ready) {
        return;
      }

      event.preventDefault();
      handleTransferOwnership();
    });
    elements.transferSelectedMember?.addEventListener('click', (event) => {
      const actionTarget = event.target instanceof Element
        ? event.target.closest('[data-transfer-selection-action="deselect"]')
        : null;
      if (!(actionTarget instanceof HTMLButtonElement)) {
        return;
      }

      clearTransferSelection(true);
      if (elements.transferTarget instanceof HTMLInputElement) {
        elements.transferTarget.focus();
      }
    });
    elements.transferButton?.addEventListener('click', handleTransferOwnership);
    elements.leaveButton?.addEventListener('click', handleLeaveBusiness);
    elements.auditReload?.addEventListener('click', () => {
      if (state.selectedBusinessId !== '') {
        loadBusinessAudit(state.selectedBusinessId).catch((error) => {
          PW.error(error);
          PC.showToast(T.loadAuditFailed, 'error', 7000, true);
        });
      }
    });


    /**
     * Audit grid row-click handler: opens popover with full event details
     */
    let auditDetailsPopoverState = null;
    let auditEventDetailsMap = {}; // Map of grid IDs to event details

    const closeAuditDetailsPopover = () => {
      if (auditDetailsPopoverState) {
        const { popover, trigger } = auditDetailsPopoverState;
        popover.hidden = true;
        if (trigger instanceof HTMLElement) {
          trigger.setAttribute('aria-expanded', 'false');
        }
        auditDetailsPopoverState = null;
      }
    };

    const openAuditDetailsPopoverFor = (trigger, popover) => {
      if (!(trigger instanceof HTMLElement) || !(popover instanceof HTMLElement)) {
        return;
      }

      if (auditDetailsPopoverState && auditDetailsPopoverState.popover !== popover) {
        closeAuditDetailsPopover();
      }

      const portalParent = trigger.closest('dialog[open]') || document.body;
      if (popover.parentElement !== portalParent) {
        portalParent.appendChild(popover);
      }

      popover.hidden = false;
      positionHistoryTimestampPopover(trigger, popover);
      window.requestAnimationFrame(() => {
        positionHistoryTimestampPopover(trigger, popover);
      });
      trigger.setAttribute('aria-expanded', 'true');
      auditDetailsPopoverState = { trigger, popover };
    };

    const handleAuditGridRowClick = (event) => {
      const row = event.target instanceof Element
        ? event.target.closest('[role="row"].datagrid_row')
        : null;
      if (!(row instanceof HTMLElement)) {
        return;
      }

      // Find the grid container and get the grid ID
      const gridContainer = row.closest('[data-grid]');
      if (!(gridContainer instanceof HTMLElement)) {
        return;
      }

      const gridId = String(gridContainer.dataset.grid || '');
      const rowId = String(row.dataset.id || '');

      if (gridId === '' || rowId === '') {
        return;
      }

      // Look up event details from the embedded script tag
      const detailsScript = document.getElementById(gridId + '_event_details');
      if (!(detailsScript instanceof HTMLElement)) {
        return;
      }

      let allEventDetails = {};
      try {
        const rawJson = String(detailsScript.dataset.eventDetailsJson || '{}');
        allEventDetails = JSON.parse(rawJson);
      } catch {
        allEventDetails = {};
      }

      const eventDetails = allEventDetails[rowId] || {};
      const detailsJson = String(eventDetails.event_details_json || '{}');
      let detailsMap = {};
      try {
        detailsMap = JSON.parse(detailsJson);
      } catch {
        detailsMap = {};
      }

      const eventType = String(eventDetails.event_type || '').trim();
      const actor = String(eventDetails.actor || '').trim();
      const target = String(eventDetails.target || '').trim();
      const timestamp = String(eventDetails.created_at || '').trim();

      console.log('Event details found:', { eventType, actor, target, timestamp, detailsMap });

      // Build details HTML using CSS classes (no inline styles)
      let detailsHtml = '<div class="businesses_audit_details_popover_container">';
      detailsHtml += `<div class="businesses_audit_details_popover_field"><strong>Event:</strong> ${Guardian.sanitizedText(eventType)}</div>`;
      detailsHtml += `<div class="businesses_audit_details_popover_field"><strong>Actor:</strong> ${Guardian.sanitizedText(actor)}</div>`;
      detailsHtml += `<div class="businesses_audit_details_popover_field"><strong>Target:</strong> ${Guardian.sanitizedText(target)}</div>`;
      detailsHtml += `<div class="businesses_audit_details_popover_field"><strong>Timestamp:</strong> ${Guardian.sanitizedText(timestamp)}</div>`;
      
      if (Object.keys(detailsMap).length > 0) {
        detailsHtml += '<div class="businesses_audit_details_popover_divider"><strong>Details:</strong></div>';
        for (const [key, value] of Object.entries(detailsMap)) {
          detailsHtml += `<div class="businesses_audit_details_popover_details_item"><strong>${Guardian.sanitizedText(String(key))}:</strong> ${Guardian.sanitizedText(String(value))}</div>`;
        }
      }
      detailsHtml += '</div>';

      // Create or reuse popover
      let popover = document.getElementById('businesses_audit_details_popover');
      if (!popover) {
        popover = document.createElement('div');
        popover.id = 'businesses_audit_details_popover';
        popover.className = 'businesses_history_timestamp_popover';
        popover.setAttribute('role', 'tooltip');
        document.body.appendChild(popover);
      }

      window.Guardian.setHTML(popover, detailsHtml);
      openAuditDetailsPopoverFor(row, popover);
    };

    // Close popover on body click outside
    document.addEventListener('click', (event) => {
      if (auditDetailsPopoverState && event.target instanceof Element) {
        const popover = auditDetailsPopoverState.popover;
        if (!popover.contains(event.target) && !auditDetailsPopoverState.trigger?.contains(event.target)) {
          closeAuditDetailsPopover();
        }
      }
    }, true);

    elements.auditGridContainer?.addEventListener('click', handleAuditGridRowClick);
    elements.freeAuditGridContainer?.addEventListener('click', handleAuditGridRowClick);

    elements.discoveryRun?.addEventListener('click', handleDiscovery);
    elements.personalPayFrequency?.addEventListener('change', () => {
      syncPersonalFrequency();
      refreshPersonalPayPeriodValidation();
      schedulePersonalPreviewRender();
      schedulePersonalAutoSave(180, 'frequency');
    });
    elements.personalPayAnchor?.addEventListener('change', () => {
      refreshPersonalPayPeriodValidation();
      schedulePersonalPreviewRender();
      schedulePersonalAutoSave(180, 'anchor');
    });
    Array.from(elements.personalEditingGraceDayRadios).forEach((radio) => {
      radio.addEventListener('change', handlePersonalGraceDaysChange);
    });
    elements.personalEditingGraceDays?.addEventListener('click', (event) => {
      const target = event.target instanceof Element ? event.target.closest('label[for]') : null;
      if (!(target instanceof HTMLLabelElement)) {
        return;
      }

      const radioId = String(target.getAttribute('for') || '');
      const radio = radioId !== '' ? document.getElementById(radioId) : null;
      if (!(radio instanceof HTMLInputElement) || radio.checked) {
        return;
      }

      event.preventDefault();
      radio.checked = true;
      radio.dispatchEvent(new Event('change', { bubbles: true }));
    });
    initCurrencyFinder('businesses_personal_currency_search', 'businesses_personal_currency', 'businesses_personal_currency_listbox', 'businesses_personal_currency_finder');
    initCurrencyFinder('businesses_editor_currency_search', 'businesses_editor_currency', 'businesses_editor_currency_listbox', 'businesses_editor_currency_finder');
    initTimezoneFinder('businesses_personal_timezone_search', 'businesses_personal_timezone', 'businesses_personal_timezone_listbox', 'businesses_personal_timezone_finder');
    initTimezoneFinder('businesses_editor_timezone_search', 'businesses_editor_timezone', 'businesses_editor_timezone_listbox', 'businesses_editor_timezone_finder');
    [elements.personalName, elements.personalDefaultWage, elements.personalTimezone, elements.personalCurrency, elements.personalLanguage, elements.personalLocale].forEach((input) => {
      input?.addEventListener('change', () => {
        syncPersonalWageCurrencyAdornment();
        syncProfilePhoneCountryAdornment();
        renderPersonalInternationalizationPreview();
        schedulePersonalAutoSave(180, 'details');
      });
    });
    elements.personalTimezoneSearch?.addEventListener('change', () => {
      syncPersonalWageCurrencyAdornment();
      syncProfilePhoneCountryAdornment();
      renderPersonalInternationalizationPreview();
      schedulePersonalAutoSave(180, 'details');
    });
    elements.personalCurrencySearch?.addEventListener('change', () => {
      syncPersonalWageCurrencyAdornment();
      syncProfilePhoneCountryAdornment();
      renderPersonalInternationalizationPreview();
      schedulePersonalAutoSave(180, 'details');
    });
    if (elements.personalDefaultWage instanceof HTMLInputElement) {
      let personalWageInputDebounceId = null;
      elements.personalDefaultWage.addEventListener('input', () => {
        if (personalWageInputDebounceId !== null) {
          window.clearTimeout(personalWageInputDebounceId);
        }

        personalWageInputDebounceId = window.setTimeout(() => {
          schedulePersonalAutoSave(180, 'details');
          personalWageInputDebounceId = null;
        }, 500);
      });
    }
    elements.personalPreview?.addEventListener('click', handlePersonalPreviewInteraction);
    elements.personalPreview?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        handlePersonalPreviewInteraction(event);
      }
    });
    elements.payFrequency?.addEventListener('change', () => {
      renderPreview();
      scheduleEditorAutoSave(240, 'pay-frequency');
    });
    Array.from(elements.editorEditingGraceDayRadios).forEach((radio) => {
      radio.addEventListener('change', () => {
        renderPreview();
        scheduleEditorAutoSave(240, 'grace-days');
      });
    });
    EDITOR_AUTOSAVE_SOURCE_IDS.forEach((fieldId) => {
      const field = document.getElementById(fieldId);
      if (!(field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement)) {
        return;
      }

      if (field instanceof HTMLInputElement && fieldId.endsWith('_phone')) {
        applyPhoneInputFormatting(field);
      }

      field.addEventListener('change', () => {
        if (!guardSensitiveEditorFieldChange(fieldId)) {
          return;
        }
        scheduleEditorAutoSave(420, `editor:${fieldId}`);
      });

      field.addEventListener('blur', () => {
        if (!guardSensitiveEditorFieldChange(fieldId)) {
          return;
        }
        scheduleEditorAutoSave(420, `editor:${fieldId}`);
      });

      if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement) {
        field.addEventListener('input', () => {
          if (field instanceof HTMLInputElement) {
            if (fieldId.endsWith('_phone')) {
              applyPhoneInputFormatting(field);
            }
            syncContactAvatarPreview(field);
          }
          scheduleEditorAutoSave(700, `editor:${fieldId}`);
        });
      }
    });

    elements.customCardsContainer?.addEventListener('change', (event) => {
      const field = event.target;
      if (!(field instanceof HTMLInputElement) || !field.classList.contains('businesses_contact_custom_input')) {
        return;
      }

      scheduleEditorAutoSave(420, `custom-contact:${String(field.dataset.customField || 'field')}`);
    });

    elements.customCardsContainer?.addEventListener('blur', (event) => {
      const field = event.target;
      if (!(field instanceof HTMLInputElement) || !field.classList.contains('businesses_contact_custom_input')) {
        return;
      }

      scheduleEditorAutoSave(420, `custom-contact:${String(field.dataset.customField || 'field')}`);
    }, true);

    elements.customCardsContainer?.addEventListener('input', (event) => {
      const field = event.target;
      if (!(field instanceof HTMLInputElement) || !field.classList.contains('businesses_contact_custom_input')) {
        return;
      }

      const cardId = String(field.dataset.customCardId || '');
      const fieldName = String(field.dataset.customField || '');
      if (cardId === '' || fieldName === '') {
        return;
      }

      if (fieldName === 'phone') {
        applyPhoneInputFormatting(field);
      }

      upsertCustomCardField(cardId, fieldName, field.value);
      scheduleEditorAutoSave(520, `custom-contact:${fieldName}`);
    });

    bindOrganizationContactPanelEvents();

    elements.preview?.addEventListener('click', handleEditorPreviewInteraction);
    elements.preview?.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        handleEditorPreviewInteraction(event);
      }
    });
    elements.closeButton?.addEventListener('click', closeDialog);
    elements.dialog?.addEventListener('click', (event) => {
      if (event.target === elements.dialog) {
        closeDialog();
      }
    });

    document.addEventListener('click', (event) => {
      handleGridClick(event).catch((error) => PW.error(error));
    });
    document.addEventListener('input', handleGridInput);
    document.addEventListener('keydown', (event) => {
      handleGridKeydown(event).catch((error) => PW.error(error));
    });

    if (elements.payrollSaveButton instanceof HTMLButtonElement) {
      elements.payrollSaveButton.addEventListener('click', () => {
        handleSavePayroll().catch((error) => PW.error(error));
      });
    }

    // Create Business Dialog
    if (elements.createButton instanceof HTMLButtonElement) {
      elements.createButton.addEventListener('click', () => {
        const ownedShared = getOwnedSharedBusiness();
        if (ownedShared) {
          openBusinessDialog(String(ownedShared.business_id || '')).catch((error) => PW.error(error));
          return;
        }

        if (elements.createDialog instanceof HTMLDialogElement && !elements.createDialog.open) {
          elements.createDialog.showModal();
          elements.createName?.focus();
        }
      });
    }

    const syncDefinitionsHelpExpanded = (expanded) => {
      if (elements.definitionsHelpButton instanceof HTMLButtonElement) {
        elements.definitionsHelpButton.setAttribute('aria-expanded', expanded ? 'true' : 'false');
      }
    };

    if (elements.definitionsHelpButton instanceof HTMLButtonElement) {
      elements.definitionsHelpButton.addEventListener('click', () => {
        if (elements.definitionsDialog instanceof HTMLDialogElement && !elements.definitionsDialog.open) {
          elements.definitionsDialog.showModal();
          syncDefinitionsHelpExpanded(true);
          elements.definitionsCloseButton?.focus();
        }
      });
    }

    if (elements.definitionsCloseButton instanceof HTMLButtonElement) {
      elements.definitionsCloseButton.addEventListener('click', () => {
        if (elements.definitionsDialog instanceof HTMLDialogElement && elements.definitionsDialog.open) {
          elements.definitionsDialog.close();
        }
      });
    }

    if (elements.definitionsDialog instanceof HTMLDialogElement) {
      elements.definitionsDialog.addEventListener('click', (event) => {
        if (event.target === elements.definitionsDialog) {
          elements.definitionsDialog.close();
        }
      });

      elements.definitionsDialog.addEventListener('close', () => {
        syncDefinitionsHelpExpanded(false);
        elements.definitionsHelpButton?.focus();
      });
    }

    if (elements.createForm instanceof HTMLFormElement) {
      elements.createForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (hasOwnedSharedBusiness()) {
          PC.showToast(T.sharedOrgSingleton, 'warning', 5000, true);
          if (elements.createDialog instanceof HTMLDialogElement && elements.createDialog.open) {
            elements.createDialog.close();
          }
          return;
        }

        const nameValue = String(elements.createName?.value || '').trim();
        if (nameValue === '') {
          if (elements.createNameError instanceof HTMLElement) {
            elements.createNameError.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CREATE_NAME_REQUIRED')); ?>';
            elements.createNameError.classList.remove('hidden');
          }
          elements.createName?.focus();
          return;
        }

        if (elements.createSubmit instanceof HTMLButtonElement) {
          elements.createSubmit.disabled = true;
        }

        try {
          const response = await fetch('/api/v1/businesses/create', {
            method: 'POST',
            headers: buildHeaders(),
            body: new URLSearchParams({
              name: nameValue,
              csrf_token: String(elements.csrfToken?.value || ''),
            }),
          });

          const payload = await response.json();
          if (payload.status !== 'success') {
            throw new Error(payload.message || T.createFailed);
          }

          PC.showToast(T.created, 'success', 3000);
          if (elements.createDialog instanceof HTMLDialogElement && elements.createDialog.open) {
            elements.createDialog.close();
          }
          elements.createForm?.reset();
          await refreshIndex(payload.business_id || '', true);
        } catch (error) {
          PC.showToast(error instanceof Error ? error.message : T.createFailed, 'error', 5000, true);
          if (elements.createStatus instanceof HTMLElement) {
            elements.createStatus.textContent = error instanceof Error ? error.message : T.createFailed;
          }
        } finally {
          if (elements.createSubmit instanceof HTMLButtonElement) {
            elements.createSubmit.disabled = false;
          }
        }
      });
    }

    const isSettingsAccountPage = document.getElementById('settings-workspace')?.dataset?.settingsSubpage === 'account';
    if (!isSettingsAccountPage) {
      bindProfileEditDetails();
      bindChangeEmail();
    }
    bindDangerZone();
  };

  const initializeProfileBilling = async () => {
    const upgradeBtn = document.getElementById('billing_upgrade_business_btn')
      || document.getElementById('billing_upgrade_business_subscribed_btn')
      || document.getElementById('billing_upgrade_btn');
    const routeGateDialog = document.getElementById('businesses_route_gate_dialog');
    const routeGateCloseBtn = document.getElementById('businesses_route_gate_close_btn');
    const routeGateCloseX = document.getElementById('businesses_route_gate_close_x');
    const routeGateBillingBtn = document.getElementById('businesses_route_gate_billing_btn');
    const billingPanel = document.getElementById('panel-billing');

    const params = new URLSearchParams(window.location.search);

    const clearBusinessesRouteIntent = () => {
      if (!params.has('from_businesses')) {
        return;
      }

      params.delete('from_businesses');
      const nextQuery = params.toString();
      const nextUrl = `${window.location.pathname}${nextQuery ? `?${nextQuery}` : ''}${window.location.hash}`;
      window.history.replaceState({}, document.title, nextUrl);
    };

    const closeRouteGateDialog = () => {
      if (routeGateDialog instanceof HTMLDialogElement && routeGateDialog.open) {
        routeGateDialog.close();
      }
    };

    if (routeGateCloseBtn instanceof HTMLButtonElement) {
      routeGateCloseBtn.addEventListener('click', closeRouteGateDialog);
    }

    if (routeGateCloseX instanceof HTMLButtonElement) {
      routeGateCloseX.addEventListener('click', closeRouteGateDialog);
    }

    if (routeGateDialog instanceof HTMLDialogElement) {
      routeGateDialog.addEventListener('click', (event) => {
        if (event.target === routeGateDialog) {
          closeRouteGateDialog();
        }
      });
    }

    if (routeGateBillingBtn instanceof HTMLButtonElement) {
      routeGateBillingBtn.addEventListener('click', () => {
        closeRouteGateDialog();
        billingPanel?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        window.setTimeout(() => {
          if (upgradeBtn instanceof HTMLButtonElement) {
            upgradeBtn.focus();
          }
        }, 180);
      });
    }

    const billingController = await initializeBillingSection({
      successUrl: '/api/v1/billing/checkout-return',
      cancelUrl: '/settings/account/?billing=cancel',
      returnUrl: '/settings/account/#panel-billing',
      onPremiumActivated: () => {
        closeRouteGateDialog();
      },
      onBusinessActivated: () => {
        closeRouteGateDialog();
      },
    });

    const subData = billingController.subscription;

    if (params.get('from_businesses') === '1' && !(subData && subData.is_business)) {
      clearBusinessesRouteIntent();
      if (routeGateDialog instanceof HTMLDialogElement && !routeGateDialog.open) {
        routeGateDialog.showModal();
      }
    }
  };

  // ── Tab Navigation ──────────────────────────────────────────

  const initializeDatagridReloadHandlers = () => {
    const decorateAuditGridTimestamps = (gridId) => {
      const grid = document.getElementById(gridId);
      if (!grid) {
        return;
      }

      const gridHostId = gridId.replace(/-grid$/, '-grid-host');
      const gridHost = document.getElementById(gridHostId) || grid.parentElement;
      let detailsStore = gridHost ? gridHost.querySelector(`[id$="_event_details"]`) : null;
      if (!detailsStore) {
        detailsStore = document.getElementById(`${gridId}_event_details`);
      }
      if (!detailsStore) {
        return;
      }

      const eventDetailsJson = detailsStore.getAttribute('data-event-details-json') || '{}';
      let eventDetailsMap = {};
      try {
        eventDetailsMap = JSON.parse(eventDetailsJson);
      } catch {
        return;
      }

      const rows = grid.querySelectorAll('.datagrid_row');
      rows.forEach((row, index) => {
        const rowId = String(row.dataset.id || '');
        const rawEventDetails = eventDetailsMap[rowId];
        if (!rawEventDetails) {
          return;
        }

        const createdAtRaw = String(rawEventDetails.created_at_raw || '').trim();
        if (createdAtRaw === '') {
          return;
        }

        const timestampCells = row.querySelectorAll('.datagrid_content');
        if (timestampCells.length === 0) {
          return;
        }

        const firstCell = timestampCells[0];
        const parsedDate = parseHistoryTimestampValue(createdAtRaw);
        if (!parsedDate) {
          return;
        }

        const displayText = formatTimestampInTimeZone(parsedDate, viewerTimeZone);
        const popoverId = `businesses_audit_timestamp_popover_${String(rowId).replace(/[^a-zA-Z0-9_-]/g, '_')}_${index}`;

        const field = document.createElement('span');
        field.className = 'businesses_history_timestamp_field';

        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'businesses_history_timestamp_trigger';
        trigger.textContent = displayText;
        trigger.setAttribute('aria-haspopup', 'dialog');
        trigger.setAttribute('aria-controls', popoverId);
        trigger.setAttribute('aria-expanded', 'false');

        const popover = document.createElement('div');
        popover.id = popoverId;
        popover.className = 'businesses_history_timestamp_popover';
        popover.hidden = true;
        popover.setAttribute('role', 'dialog');
        popover.setAttribute('aria-label', T.timestampDetailsAria);

        buildTimestampZoneRows(parsedDate).forEach((rowData) => {
          const rowEl = document.createElement('span');
          rowEl.className = 'businesses_history_timestamp_popover_row';
          const labelEl = document.createElement('span');
          labelEl.className = 'businesses_history_timestamp_popover_label';
          labelEl.textContent = `${rowData.label}:`;
          const valueEl = document.createElement('span');
          valueEl.className = 'businesses_history_timestamp_popover_value';
          valueEl.textContent = rowData.value;
          rowEl.appendChild(labelEl);
          rowEl.appendChild(valueEl);
          popover.appendChild(rowEl);
        });

        field.appendChild(trigger);
        field.appendChild(popover);
        firstCell.textContent = '';
        firstCell.appendChild(field);
        trigger.addEventListener('click', (e) => {
          e.preventDefault();
          openHistoryTimestampPopoverFor(trigger, popover);
        });
      });
    };

    document.addEventListener('paycal:datagrid-reloaded', (event) => {
      const detail = event?.detail || {};
      const gridId = String(detail.gridId || '');
      if (gridId === 'businesses-audit-grid') {
        if (!elements.auditStatus) {
          return;
        }
        const rowCount = Number(detail.rowCount || 0);
        elements.auditStatus.textContent = T.auditGridUpdated
          .replace('%d', String(rowCount))
          .replace('%s', rowCount === 1 ? '' : 's');
        decorateAuditGridTimestamps('businesses-audit-grid');
        return;
      }

      if (gridId === 'businesses-free-audit-grid') {
        decorateAuditGridTimestamps('businesses-free-audit-grid');
        return;
      }

      if (gridId !== 'businesses-members-grid') {
        return;
      }

      syncMemberRoleTriggerLabels();
      enhanceMembersJoinedTimestampCells();

      if (!elements.membersGridStatus) {
        return;
      }

      const stateInfo = detail.state || {};
      const rowCount = Number(detail.rowCount || 0);
      const order = formatDatagridOrderLabel(stateInfo.sort, stateInfo.direction);
      const search = formatDatagridSearchLabel(stateInfo.search);
      const page = stateInfo.page || 1;
      elements.membersGridStatus.textContent = T.membersGridStatusDetail
        .replace('%d', String(rowCount))
        .replace('%s', rowCount === 1 ? '' : 's')
        .replace('%s', order)
        .replace('%s', search)
        .replace('%d', String(page));
    });
  };

  const currentBusinessId = () => {
    const orgIdFromInput = document.getElementById('businesses_editor_org_id')?.value || '';
    return orgIdFromInput || state.selectedBusinessId || '';
  };

  const loadMembers = async () => {
    await loadBusinessMembersGrid(resolveWorkspaceBusinessId());
  };













  const MEMBER_ASSIGNABLE_ROLES = ['coordinator', 'contributor', 'viewer', 'member'];
  const MEMBER_ROLE_LABELS = {
    owner: T.owner,
    coordinator: T.coordinator,
    contributor: T.contributor,
    viewer: T.viewer,
    member: T.member,
  };

  let memberRolePopoverEl = null;
  let openMemberRolePopover = null;

  const getMemberRoleLabel = (role) => {
    const normalized = String(role || '').trim().toLowerCase();
    return MEMBER_ROLE_LABELS[normalized] || normalized;
  };

  const resolveMemberDisplayNameFromRow = (row) => {
    if (!(row instanceof HTMLElement)) {
      return '';
    }

    const nameCell = row.querySelector('.datagrid_col_full_name');
    const name = decodePossiblyEncodedText(String(nameCell?.textContent || '').trim());
    if (name !== '') {
      return name;
    }

    const emailCell = row.querySelector('.datagrid_col_email');
    return String(emailCell?.textContent || '').trim();
  };

  const formatMemberRoleUpdatedMessage = (memberName, roleLabel) => {
    const name = String(memberName || '').trim() || T.unknown;
    const role = String(roleLabel || '').trim();
    return String(T.memberRoleUpdated || '%s is now %s')
      .replace('%s', name)
      .replace('%s', role);
  };

  const formatMemberRoleUpdateFailedMessage = (memberName) => {
    const name = String(memberName || '').trim();
    if (name === '') {
      return T.memberRoleUpdateFailedGeneric || 'Unable to update member role right now.';
    }

    return String(T.memberRoleUpdateFailed || "Unable to update %s's role right now.")
      .replace('%s', name);
  };

  const syncMemberRoleTriggerLabels = () => {
    if (!(elements.membersGridContainer instanceof HTMLElement)) {
      return;
    }

    elements.membersGridContainer.querySelectorAll('.businesses_member_role_trigger').forEach((trigger) => {
      if (!(trigger instanceof HTMLElement)) {
        return;
      }

      const slug = String(trigger.dataset.currentRole || '').trim().toLowerCase();
      if (slug === '') {
        return;
      }

      const label = getMemberRoleLabel(slug);
      trigger.textContent = label;
      trigger.setAttribute('aria-label', `Change role, currently ${label}`);
    });

    elements.membersGridContainer.querySelectorAll('.businesses_member_role_cell_static').forEach((cell) => {
      if (!(cell instanceof HTMLElement)) {
        return;
      }

      const slug = String(cell.textContent || '').trim().toLowerCase();
      const label = getMemberRoleLabel(slug);
      if (label !== slug) {
        cell.textContent = label;
      }
    });
  };

  const resolveMemberCurrentRole = (trigger) => {
    if (!(trigger instanceof HTMLElement)) {
      return '';
    }

    const fromDataset = String(trigger.dataset.currentRole || '').trim().toLowerCase();
    if (fromDataset !== '') {
      return fromDataset;
    }

    const row = trigger.closest('.datagrid_row');
    const roleTrigger = row instanceof HTMLElement ? row.querySelector('.businesses_member_role_trigger') : null;
    if (roleTrigger instanceof HTMLElement && roleTrigger !== trigger) {
      return String(roleTrigger.dataset.currentRole || roleTrigger.textContent || '').trim().toLowerCase();
    }

    return String(trigger.textContent || '').trim().toLowerCase();
  };

  const ensureMemberRolePopoverElement = () => {
    if (memberRolePopoverEl instanceof HTMLElement) {
      return memberRolePopoverEl;
    }

    memberRolePopoverEl = document.createElement('div');
    memberRolePopoverEl.id = 'businesses_member_role_popover';
    memberRolePopoverEl.className = 'businesses_member_role_popover';
    memberRolePopoverEl.hidden = true;
    memberRolePopoverEl.setAttribute('role', 'listbox');
    memberRolePopoverEl.setAttribute('aria-label', 'Select member role');
    document.body.appendChild(memberRolePopoverEl);
    return memberRolePopoverEl;
  };

  const closeMemberRolePopover = ({ restoreFocus = false } = {}) => {
    if (!openMemberRolePopover) {
      return;
    }

    const { trigger, popover } = openMemberRolePopover;
    if (trigger instanceof HTMLElement) {
      trigger.setAttribute('aria-expanded', 'false');
      trigger.removeAttribute('aria-controls');
      if (restoreFocus && typeof trigger.focus === 'function') {
        trigger.focus();
      }
    }

    if (popover instanceof HTMLElement) {
      popover.hidden = true;
      popover.style.left = '';
      popover.style.top = '';
      popover.style.right = '';
      popover.style.position = '';
      popover.replaceChildren();
    }

    openMemberRolePopover = null;
  };

  const setMemberRolePopoverActiveIndex = (popover, index) => {
    if (!(popover instanceof HTMLElement)) {
      return;
    }

    const options = Array.from(popover.querySelectorAll('[role="option"]'));
    options.forEach((option, optionIndex) => {
      const isActive = optionIndex === index;
      option.setAttribute('aria-selected', isActive ? 'true' : 'false');
      option.classList.toggle('businesses_member_role_option_active', isActive);
      if (isActive && typeof option.scrollIntoView === 'function') {
        option.scrollIntoView({ block: 'nearest' });
      }
    });

    if (openMemberRolePopover) {
      openMemberRolePopover.activeIndex = index;
    }
  };

  const positionMemberRolePopover = (trigger, popover) => {
    positionHistoryTimestampPopover(trigger, popover);
  };

  const openMemberRolePopoverFor = (trigger, memberUuid, currentRole) => {
    if (!(trigger instanceof HTMLElement)) {
      return;
    }

    const normalizedCurrentRole = String(currentRole || '').trim().toLowerCase();
    if (normalizedCurrentRole === 'owner') {
      PC.showToast(T.membersOwnerRoleLocked, 'error', 7000, true);
      announceMembersGridStatus(T.membersOwnerRoleLocked);
      return;
    }

    if (
      openMemberRolePopover
      && openMemberRolePopover.trigger === trigger
      && openMemberRolePopover.memberUuid === memberUuid
    ) {
      closeMemberRolePopover({ restoreFocus: false });
      return;
    }

    closeMemberRolePopover({ restoreFocus: false });

    const popover = ensureMemberRolePopoverElement();
    popover.replaceChildren();

    let initialActiveIndex = 0;
    MEMBER_ASSIGNABLE_ROLES.forEach((role, index) => {
      const option = document.createElement('button');
      option.type = 'button';
      option.className = 'businesses_member_role_option';
      option.setAttribute('role', 'option');
      option.dataset.role = role;
      option.id = `businesses_member_role_option_${role}`;
      option.textContent = getMemberRoleLabel(role);

      const isCurrent = role === normalizedCurrentRole;
      option.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
      option.classList.toggle('businesses_member_role_option_current', isCurrent);
      option.disabled = isCurrent;

      option.addEventListener('click', async (event) => {
        event.preventDefault();
        if (option.disabled) {
          return;
        }
        await submitMemberRoleChange(trigger, memberUuid, role, normalizedCurrentRole);
      });

      popover.appendChild(option);

      if (isCurrent) {
        initialActiveIndex = index;
      }
    });

    openMemberRolePopover = {
      trigger,
      popover,
      memberUuid,
      activeIndex: initialActiveIndex,
    };

    popover.hidden = false;
    popover.tabIndex = -1;
    positionMemberRolePopover(trigger, popover);
    window.requestAnimationFrame(() => {
      positionMemberRolePopover(trigger, popover);
      popover.focus();
    });

    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-controls', popover.id);
    trigger.setAttribute('aria-expanded', 'true');

    setMemberRolePopoverActiveIndex(popover, initialActiveIndex);

    popover.onkeydown = (event) => {
      const options = Array.from(popover.querySelectorAll('[role="option"]'));
      const enabledOptions = options.filter((option) => !option.disabled);
      if (enabledOptions.length === 0) {
        if (event.key === 'Escape') {
          event.preventDefault();
          closeMemberRolePopover({ restoreFocus: true });
        }
        return;
      }

      const activeRole = MEMBER_ASSIGNABLE_ROLES[openMemberRolePopover?.activeIndex ?? 0] || enabledOptions[0].dataset.role;
      let enabledIndex = enabledOptions.findIndex((option) => String(option.dataset.role || '') === String(activeRole || ''));
      if (enabledIndex < 0) {
        enabledIndex = 0;
      }

      if (event.key === 'ArrowDown') {
        event.preventDefault();
        enabledIndex = Math.min(enabledOptions.length - 1, enabledIndex + 1);
      } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        enabledIndex = Math.max(0, enabledIndex - 1);
      } else if (event.key === 'Home') {
        event.preventDefault();
        enabledIndex = 0;
      } else if (event.key === 'End') {
        event.preventDefault();
        enabledIndex = enabledOptions.length - 1;
      } else if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        enabledOptions[enabledIndex]?.click();
        return;
      } else if (event.key === 'Escape') {
        event.preventDefault();
        closeMemberRolePopover({ restoreFocus: true });
        return;
      } else {
        return;
      }

      const nextRole = String(enabledOptions[enabledIndex]?.dataset.role || '');
      const nextIndex = MEMBER_ASSIGNABLE_ROLES.indexOf(nextRole);
      if (nextIndex >= 0) {
        setMemberRolePopoverActiveIndex(popover, nextIndex);
      }
      enabledOptions[enabledIndex]?.focus();
    };
  };

  const submitMemberRoleChange = async (trigger, memberUuid, nextRole, previousRole) => {
    const currentOrgId = resolveWorkspaceBusinessId();
    if (!currentOrgId || !memberUuid) {
      return;
    }

    const normalizedRole = String(nextRole || '').trim().toLowerCase();
    if (!MEMBER_ASSIGNABLE_ROLES.includes(normalizedRole)) {
      PC.showToast(T.invalidRoleSelected, 'error', 7000, true);
      return;
    }

    const business = findBusiness(currentOrgId);
    if (business && !canManageBusinessAccess(business)) {
      closeMemberRolePopover({ restoreFocus: true });
      showAccessManagementDeniedWarning();
      return;
    }

    const row = trigger.closest('.datagrid_row');
    const memberDisplayName = resolveMemberDisplayNameFromRow(row);
    const roleTrigger = row instanceof HTMLElement ? row.querySelector('.businesses_member_role_trigger') : null;
    const priorRoleLabel = roleTrigger instanceof HTMLElement ? roleTrigger.textContent : '';
    const nextRoleLabel = getMemberRoleLabel(normalizedRole);

    closeMemberRolePopover({ restoreFocus: false });

    if (roleTrigger instanceof HTMLElement) {
      roleTrigger.textContent = nextRoleLabel;
      roleTrigger.setAttribute('aria-label', `Change role, currently ${nextRoleLabel}`);
    }
    if (trigger instanceof HTMLElement) {
      trigger.dataset.currentRole = normalizedRole;
    }
    if (row instanceof HTMLElement) {
      row.classList.add('businesses_member_role_row_pending');
      row.setAttribute('aria-busy', 'true');
    }

    const body = new URLSearchParams();
    body.set('target_user_uuid', memberUuid);
    body.set('role', normalizedRole);
    const csrfToken = getCsrfToken();
    if (csrfToken !== '') {
      body.set('csrf_token', csrfToken);
    }

    try {
      const response = await fetch(`/api/v1/businesses/${encodeURIComponent(currentOrgId)}/relationships/update-role`, {
        method: 'POST',
        headers: buildHeaders(),
        body,
        credentials: 'include',
      });

      if (!response.ok) {
        throw new Error(`Failed to update role: ${response.status}`);
      }

      const successMessage = formatMemberRoleUpdatedMessage(memberDisplayName, nextRoleLabel);
      announceMembersGridStatus(successMessage);
      PC.showToast(successMessage, 'save', 6000, true);
      await loadMembers();
    } catch (error) {
      debugLog('Error updating role:', error);
      if (roleTrigger instanceof HTMLElement) {
        roleTrigger.textContent = priorRoleLabel;
        roleTrigger.setAttribute('aria-label', `Change role, currently ${priorRoleLabel}`);
      }
      if (trigger instanceof HTMLElement) {
        trigger.dataset.currentRole = String(previousRole || '').trim().toLowerCase();
      }
      const message = formatMemberRoleUpdateFailedMessage(memberDisplayName);
      announceMembersGridStatus(message);
      PC.showToast(message, 'error', 7000, true);
    } finally {
      if (row instanceof HTMLElement) {
        row.classList.remove('businesses_member_role_row_pending');
        row.removeAttribute('aria-busy');
      }
    }
  };

  const toggleMemberRolePopover = (trigger, memberUuid) => {
    if (!(trigger instanceof HTMLElement) || memberUuid === '') {
      return;
    }

    const currentOrgId = resolveWorkspaceBusinessId();
    if (!currentOrgId) {
      return;
    }

    const business = findBusiness(currentOrgId);
    if (business && !canManageBusinessAccess(business)) {
      showAccessManagementDeniedWarning();
      return;
    }

    const currentRole = resolveMemberCurrentRole(trigger);
    openMemberRolePopoverFor(trigger, memberUuid, currentRole);
  };

  const bindMemberRolePopoverDismissals = () => {
    if (document.documentElement.dataset.memberRolePopoverDismissBound === '1') {
      return;
    }

    document.documentElement.dataset.memberRolePopoverDismissBound = '1';

    document.addEventListener('pointerdown', (event) => {
      if (!openMemberRolePopover) {
        return;
      }

      const target = event.target;
      if (!(target instanceof Node)) {
        return;
      }

      if (
        openMemberRolePopover.trigger.contains(target)
        || openMemberRolePopover.popover.contains(target)
      ) {
        return;
      }

      closeMemberRolePopover({ restoreFocus: false });
    });

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape' || !openMemberRolePopover) {
        return;
      }

      closeMemberRolePopover({ restoreFocus: true });
    });
  };

  const formatMemberNamedMessage = (template, memberName) => {
    const name = String(memberName || '').trim() || T.unknown;
    return String(template || '').replace('%s', name);
  };

  const getMemberRevokeDialogElements = () => ({
    dialog: document.getElementById('businesses_member_revoke_dialog'),
    form: document.getElementById('businesses_member_revoke_form'),
    message: document.getElementById('businesses_member_revoke_dialog_message'),
    confirmButton: document.getElementById('businesses_member_revoke_confirm'),
    cancelButton: document.getElementById('businesses_member_revoke_cancel'),
    closeButton: document.getElementById('businesses_member_revoke_close'),
  });

  const closeMemberRevokeDialog = () => {
    const { dialog } = getMemberRevokeDialogElements();
    if (dialog instanceof HTMLDialogElement && dialog.open) {
      dialog.close('cancel');
    }
  };

  const promptMemberRevokeDialog = async (memberUuid, memberName, trigger) => {
    const {
      dialog,
      form,
      message,
      confirmButton,
      cancelButton,
      closeButton,
    } = getMemberRevokeDialogElements();

    if (!(dialog instanceof HTMLDialogElement) || !(form instanceof HTMLFormElement) || !(message instanceof HTMLElement)) {
      return false;
    }

    message.textContent = formatMemberNamedMessage(T.memberRevokeConfirm, memberName);

    return await new Promise((resolve) => {
      let settled = false;

      const settle = (value) => {
        if (settled) {
          return;
        }
        settled = true;
        cleanup();
        resolve(value);
      };

      const onSubmit = (event) => {
        event.preventDefault();
        settle(true);
        if (dialog.open) {
          dialog.close('confirm');
        }
      };

      const onCancel = (event) => {
        event.preventDefault();
        closeMemberRevokeDialog();
      };

      const onDialogClose = () => {
        if (!settled) {
          settle(false);
        }
        if (trigger instanceof HTMLElement && typeof trigger.focus === 'function') {
          trigger.focus();
        }
      };

      const cleanup = () => {
        form.removeEventListener('submit', onSubmit);
        cancelButton?.removeEventListener('click', onCancel);
        closeButton?.removeEventListener('click', onCancel);
        dialog.removeEventListener('close', onDialogClose);
      };

      form.addEventListener('submit', onSubmit);
      cancelButton?.addEventListener('click', onCancel);
      closeButton?.addEventListener('click', onCancel);
      dialog.addEventListener('close', onDialogClose);

      state.lastFocused = trigger instanceof HTMLElement ? trigger : document.activeElement;
      PC.openModal('businesses_member_revoke_dialog');
      if (confirmButton instanceof HTMLButtonElement) {
        confirmButton.focus();
      }
    });
  };

  const revokeMemberAccess = async (memberUuid, memberName) => {
    const currentOrgId = resolveWorkspaceBusinessId();
    if (!currentOrgId || !memberUuid) {
      return;
    }

    const body = new URLSearchParams();
    body.set('target_user_uuid', memberUuid);
    const csrfToken = getCsrfToken();
    if (csrfToken !== '') {
      body.set('csrf_token', csrfToken);
    }

    const response = await fetch(`/api/v1/businesses/${encodeURIComponent(currentOrgId)}/relationships/revoke`, {
      method: 'POST',
      headers: buildHeaders(),
      body,
      credentials: 'include',
    });

    if (!response.ok) {
      throw new Error(`Failed to revoke member: ${response.status}`);
    }

    const successMessage = formatMemberNamedMessage(T.memberRevokeSuccess, memberName);
    announceMembersGridStatus(successMessage);
    PC.showToast(successMessage, 'save', 4000, true);
    await loadMembers();
  };

  const showConfirmRevokeDialog = async (memberUuid, trigger) => {
    const currentOrgId = resolveWorkspaceBusinessId();
    if (!currentOrgId || !memberUuid) {
      return;
    }

    const business = findBusiness(currentOrgId);
    if (business && !canManageBusinessAccess(business)) {
      showAccessManagementDeniedWarning();
      return;
    }

    const row = trigger instanceof HTMLElement ? trigger.closest('.datagrid_row') : null;
    const memberName = resolveMemberDisplayNameFromRow(row);
    const confirmed = await promptMemberRevokeDialog(memberUuid, memberName, trigger);
    if (!confirmed) {
      return;
    }

    try {
      await revokeMemberAccess(memberUuid, memberName);
    } catch (error) {
      debugLog('Error revoking member:', error);
      const failureMessage = formatMemberNamedMessage(T.memberRevokeFailed, memberName);
      announceMembersGridStatus(failureMessage);
      PC.showToast(failureMessage, 'error', 7000, true);
    }
  };

  const getMemberReportsDialogElements = () => ({
    dialog: document.getElementById('businesses_member_reports_dialog'),
    title: document.getElementById('businesses_member_reports_dialog_title'),
    body: document.getElementById('businesses_member_reports_dialog_body'),
    closeButton: document.getElementById('businesses_member_reports_close'),
  });

  const formatMemberReportsDialogTitle = (memberName) => (
    formatMemberNamedMessage(T.memberReportsDialogTitleNamed, memberName)
  );

  let memberReportsViewCleanup = null;

  const resetMemberReportsDialogState = () => {
    if (typeof memberReportsViewCleanup === 'function') {
      memberReportsViewCleanup();
      memberReportsViewCleanup = null;
    }
  };

  const closeMemberReportsDialog = () => {
    const { dialog } = getMemberReportsDialogElements();
    if (dialog instanceof HTMLDialogElement && dialog.open) {
      dialog.close();
    }
    resetMemberReportsDialogState();
  };

  const openMemberReportsDialog = async (memberUuid, memberName, memberRole = '', trigger = null) => {
    const currentOrgId = resolveWorkspaceBusinessId();
    if (!currentOrgId || !memberUuid) {
      return;
    }

    const business = findBusiness(currentOrgId);
    if (business && !canManageBusinessAccess(business)) {
      PC.showToast(T.memberReportsDenied, 'error', 7000, true);
      announceMembersGridStatus(T.memberReportsDenied);
      return;
    }

    const {
      dialog,
      title,
      body,
    } = getMemberReportsDialogElements();

    if (!(dialog instanceof HTMLDialogElement) || !(title instanceof HTMLElement) || !(body instanceof HTMLElement)) {
      return;
    }

    const resolvedName = String(memberName || '').trim() || T.unknown;
    title.textContent = formatMemberReportsDialogTitle(resolvedName);
    body.setAttribute('aria-busy', 'true');
    PC.setHTML(body, `<p class="help_text">${escapeHtml(formatMemberNamedMessage(T.memberReportsLoading, resolvedName))}</p>`);

    resetMemberReportsDialogState();

    state.lastFocused = trigger instanceof HTMLElement ? trigger : document.activeElement;
    PC.openModal('businesses_member_reports_dialog');

    try {
      const year = new Date().getFullYear();
      const response = await fetch(
        `/api/v1/businesses/${encodeURIComponent(currentOrgId)}/members/${encodeURIComponent(memberUuid)}/reports?year=${encodeURIComponent(String(year))}`,
        {
          method: 'GET',
          headers: buildHeaders(),
          credentials: 'include',
        },
      );

      if (!response.ok) {
        throw new Error(`Failed to load member reports: ${response.status}`);
      }

      const payload = await response.json();
      const viewData = payload?.data && typeof payload.data === 'object' ? payload.data : null;
      const viewHtml = typeof viewData?.html === 'string' ? viewData.html : '';

      if (viewHtml === '') {
        throw new Error('Member reports view HTML missing.');
      }

      PC.setHTML(body, viewHtml);
      body.setAttribute('aria-busy', 'false');

      memberReportsViewCleanup = initMemberReportsEarningsView(body, {
        businessId: currentOrgId,
        memberUuid,
        memberName: resolvedName,
        config: PC.config,
        buildHeaders,
        showToast: (message, type = 'error') => {
          PC.showToast(message, type, 7000, true);
        },
      });

      announceMembersGridStatus(T.memberReportsLoadedFor.replace('%s', resolvedName));
    } catch (error) {
      debugLog('Error loading member reports:', error);
      const failureMessage = formatMemberNamedMessage(T.memberReportsLoadFailed, resolvedName);
      PC.setHTML(body, `<p class="form_error">${escapeHtml(failureMessage)}</p>`);
      body.setAttribute('aria-busy', 'false');
      announceMembersGridStatus(failureMessage);
      PC.showToast(failureMessage, 'error', 7000, true);
      resetMemberReportsDialogState();
    }
  };

  const initialize = async () => {
    const subPage = resolveBusinessSubPage();
    const membersPerf = subPage === 'members' ? resolveMembersLensPerf() : null;

    const runInitialize = async () => {
      bindEvents();
      syncBusinessWorkspaceElementRefs();

      if (subPage === '' && elements.browserGrid instanceof HTMLElement) {
        initializeBusinessBrowser();
      }

      if (subPage === 'members' || subPage === 'audit') {
        initializeDatagridReloadHandlers();
      }

      const params = new URLSearchParams(window.location.search);
      const inviteToken = String(params.get('org_invite_token') || '').trim();
      if (inviteToken !== '') {
        await acceptBusinessInviteToken(inviteToken);
      }

      await refreshIndex();

      if (subPage === 'details' || subPage === 'payroll') {
        renderPreview();
        schedulePersonalPreviewRender();
      }

      if (subPage === 'details') {
        announceScopeSelectionStatus('loaded');
        initDetailsContactPanel();
      }

      if (subPage === '' && document.getElementById('panel-billing')) {
        await initializeProfileBilling();
      }

      if (subPage === '' && elements.accountActivityStatus instanceof HTMLElement) {
        await loadAccountActivity();
      }

      if (isBusinessWorkspacePage()) {
        startBusinessNotificationPolling();
      }
    };

    if (membersPerf?.isEnabled()) {
      try {
        await runInitialize();
      } finally {
        if (typeof finalizeBusinessMembersLensPerfSummary === 'function') {
          finalizeBusinessMembersLensPerfSummary('Performance Summary');
        } else {
          membersPerf.markHydrationComplete();
          membersPerf.summarize('Performance Summary');
        }
      }
      return;
    }

    await runInitialize();

    if (subPage === 'members' && typeof finalizeBusinessMembersLensPerfSummary === 'function') {
      finalizeBusinessMembersLensPerfSummary('Performance Summary');
    }

    if (subPage === 'reports' && typeof finalizeBusinessReportsLensPerfSummary === 'function') {
      finalizeBusinessReportsLensPerfSummary('Performance Summary');
    }
  };

