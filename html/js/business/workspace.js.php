<?php
namespace PayCal\Domain;

require_once __DIR__ . '/_bootstrap.php';
?>
  const EDITOR_SENSITIVE_FIELD_IDS = [
    'businesses_editor_type',
    'businesses_editor_role',
    'businesses_editor_status',
  ];

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
    browserProfileDialog: document.getElementById('businesses_browser_profile_dialog'),
    browserProfileTitle: document.getElementById('businesses_browser_profile_title'),
    browserProfileBody: document.getElementById('businesses_browser_profile_body'),
    browserProfileConnect: document.getElementById('businesses_browser_profile_connect'),
    browserProfileStatus: document.getElementById('businesses_browser_profile_status'),
    currentPanel: document.getElementById('businesses_current_panel'),
    currentMeta: document.getElementById('businesses_current_meta'),
    currentStatus: document.getElementById('businesses_current_status'),
    currentDetailsDialog: document.getElementById('businesses_current_details_dialog'),
    currentDetailsBody: document.getElementById('businesses_current_details_body'),
    personConnectionForm: document.getElementById('connections_person_form'),
    personConnectionEmail: document.getElementById('connections_person_email'),
    personConnectionsList: document.getElementById('connections_people_list'),
    personConnectionsStatus: document.getElementById('connections_people_status'),
    personManageDialog: document.getElementById('connections_person_manage_dialog'),
    personManageForm: document.getElementById('connections_person_manage_form'),
    personManageTitle: document.getElementById('connections_person_manage_title'),
    personManageBody: document.getElementById('connections_person_manage_body'),
    personManageCancel: document.getElementById('connections_person_manage_cancel'),
    membershipConsentDialog: document.getElementById('businesses_membership_consent_dialog'),
    membershipConsentForm: document.getElementById('businesses_membership_consent_form'),
    membershipConsentClose: document.getElementById('businesses_membership_consent_close'),
    membershipConsentCancel: document.getElementById('businesses_membership_consent_cancel'),
    membershipConsentAction: document.getElementById('businesses_membership_consent_action'),
    membershipConsentCurrentAck: document.getElementById('businesses_membership_consent_current_ack'),
    membershipConsentCurrentVersion: document.getElementById('businesses_membership_consent_current_version'),
    membershipConsentCurrentSharing: document.getElementById('businesses_membership_consent_current_sharing'),
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
    businessDetailsStatus: document.getElementById('businesses_business_details_status'),
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
    businessIdField: document.getElementById('businesses_editor_business_id'),
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
    connectionsReload: document.getElementById('businesses_connections_reload'),
    connectionsList: document.getElementById('businesses_connections_list'),
    connectionsStatus: document.getElementById('businesses_connections_sr_status'),
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
  let lastAutosaveTarget = null;

  const rememberAutosaveTarget = (target) => {
    if (!(target instanceof HTMLElement)) {
      return;
    }

    const field = target.closest('input, select, textarea');
    if (!(field instanceof HTMLElement) || !String(field.id || '').startsWith('businesses_')) {
      return;
    }

    lastAutosaveTarget = field;
  };

  const markAutosaveTargetSaved = () => {
    const field = lastAutosaveTarget instanceof HTMLElement && document.contains(lastAutosaveTarget)
      ? lastAutosaveTarget
      : null;
    const target = field?.closest('.form-field, .item_pair, .businesses_field, .settings_field') || field;
    if (!(target instanceof HTMLElement)) {
      return;
    }

    window.clearTimeout(Number(target.dataset.autosaveShimmerTimer || 0));
    target.classList.remove('is-autosaved');
    target.dataset.autosaveShimmer = 'saved';
    target.classList.add('is-autosaved');
    target.dataset.autosaveShimmerTimer = String(window.setTimeout(() => {
      target.classList.remove('is-autosaved');
      delete target.dataset.autosaveShimmer;
      delete target.dataset.autosaveShimmerTimer;
    }, 950));
  };

  document.addEventListener('input', (event) => rememberAutosaveTarget(event.target), true);
  document.addEventListener('change', (event) => rememberAutosaveTarget(event.target), true);

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
    businesses_editor_indigenous_owned: 'indigenous_owned',
    businesses_editor_resident_on_reserve: 'resident_on_reserve',
    businesses_editor_reserve_name: 'reserve_name',
    businesses_editor_address_line1: 'address_line1',
    businesses_editor_address_line2: 'address_line2',
    businesses_editor_address_city: 'address_city',
    businesses_editor_address_region: 'address_region',
    businesses_editor_address_postal: 'address_postal',
    businesses_editor_address_country: 'address_country',
    businesses_editor_support_hours: 'support_hours',
    businesses_editor_business_notes: 'org_notes',
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
    businesses_editor_contact_support_name: 'contact_support_name',
    businesses_editor_contact_support_image_url: 'contact_support_image_url',
    businesses_editor_contact_support_email: 'contact_support_email',
    businesses_editor_contact_support_phone: 'contact_support_phone',
    businesses_editor_contact_support_role: 'contact_support_role',
    businesses_editor_contact_operations_name: 'contact_operations_name',
    businesses_editor_contact_operations_image_url: 'contact_operations_image_url',
    businesses_editor_contact_operations_email: 'contact_operations_email',
    businesses_editor_contact_operations_phone: 'contact_operations_phone',
    businesses_editor_contact_operations_role: 'contact_operations_role',
    businesses_editor_contact_manager_name: 'contact_manager_name',
    businesses_editor_contact_manager_image_url: 'contact_manager_image_url',
    businesses_editor_contact_manager_email: 'contact_manager_email',
    businesses_editor_contact_manager_phone: 'contact_manager_phone',
    businesses_editor_contact_manager_role: 'contact_manager_role',
    businesses_editor_contact_custom_json: 'contact_custom_json',
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
            : formatPhpTemplate(T.confirmTypeMemberWarningMany, [memberCount]))
          : '';
        return window.confirm(
          formatPhpTemplate(T.confirmTypeSharedToPersonal, [orgName, memberWarning]),
        );
      }

      return window.confirm(
        formatPhpTemplate(T.confirmTypeGeneric, [
          orgName,
          previousValue || T.valueUnknown,
          nextValue || T.valueUnknown,
        ]),
      );
    }

    if (fieldId === 'businesses_editor_role') {
      return window.confirm(
        formatPhpTemplate(T.confirmRole, [
          previousValue || T.valueUnknown,
          nextValue || T.valueUnknown,
        ]),
      );
    }

    if (fieldId === 'businesses_editor_status') {
      if (previousValue === 'active' && nextValue === 'pending') {
        return window.confirm(T.confirmStatusActivePending);
      }

      return window.confirm(
        formatPhpTemplate(T.confirmStatusGeneric, [
          previousValue || T.valueUnknown,
          nextValue || T.valueUnknown,
        ]),
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

  const collectBusinessDetailsPayload = () => {
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

  const summarizeBusinessDetailsPayloadForDiagnostics = (payload, signature = '') => {
    const payloadObject = payload && typeof payload === 'object' ? payload : {};
    const keys = Object.keys(payloadObject);
    const settingsCache = state.editorSettingsCache && typeof state.editorSettingsCache === 'object'
      ? state.editorSettingsCache
      : {};

    const changedKeys = keys.filter((key) => {
      const nextValue = String(payloadObject[key] ?? '');
      const previousValue = key === 'name'
        ? String(settingsCache.name ?? getSelectedBusiness()?.name ?? '')
        : String(settingsCache[key] ?? '');
      return nextValue !== previousValue;
    });

    return {
      payload_key_count: keys.length,
      payload_name_length: String(payloadObject.name || '').length,
      payload_timezone_present: String(payloadObject.timezone || '').trim() !== '',
      payload_currency_present: String(payloadObject.currency || '').trim() !== '',
      payload_signature_length: String(signature || '').length,
      payload_signature_changed: String(signature || '') !== String(state.businessDetailsLastSavedSignature || ''),
      changed_keys: changedKeys.slice(0, 24),
      changed_key_count: changedKeys.length,
    };
  };

  const emitBusinessDetailsAutosaveDiagnostic = (eventName, detail = {}, level = 'log') => {
    if (resolveBusinessSubPage() !== 'details') {
      return;
    }

    const payload = {
      source: String(detail.source || ''),
      refresh_after_save: Boolean(detail.refresh_after_save),
      selected_business_id_present: String(state.selectedBusinessId || '').trim() !== '',
      editor_hydrating: Boolean(state.editorHydrating),
      business_details_save_in_flight: Boolean(state.businessDetailsSaveInFlight),
      business_details_pending_source: String(state.businessDetailsSavePendingSource || ''),
      business_details_signature_present: String(state.businessDetailsLastSavedSignature || '') !== '',
      ...detail,
    };

    if (typeof pushBusinessDetailsDiagnostic === 'function') {
      pushBusinessDetailsDiagnostic(eventName, payload, level);
      return;
    }

    const message = `[Business Details][autosave] ${eventName}`;
    if (level === 'error') {
      PW.error(message, payload);
    } else if (level === 'warn') {
      PW.warn(message, payload);
    } else {
      PW.log(message, payload);
    }
  };

  const saveBusinessEditorSettings = async (source = 'manual', refreshAfterSave = false) => {
    if (resolveBusinessSubPage() === 'details') {
      return saveBusinessDetailsSettings(source, refreshAfterSave);
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
      markAutosaveTargetSaved();
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
    if (state.editorHydrating) {
      emitBusinessDetailsAutosaveDiagnostic('autosave-skip-hydrating', {
        source,
        delay_ms: delayMs,
      }, 'warn');
      return;
    }

    if (subPage === 'payroll') {
      return;
    }

    if (state.editorAutoSaveTimerId !== null) {
      window.clearTimeout(state.editorAutoSaveTimerId);
      emitBusinessDetailsAutosaveDiagnostic('autosave-reschedule', {
        source,
        delay_ms: delayMs,
      });
    }

    emitBusinessDetailsAutosaveDiagnostic('autosave-scheduled', {
      source,
      delay_ms: delayMs,
    });

    state.editorAutoSaveTimerId = window.setTimeout(() => {
      emitBusinessDetailsAutosaveDiagnostic('autosave-timer-fired', {
        source,
        delay_ms: delayMs,
      });
      saveBusinessEditorSettings(source, false).catch((error) => PW.error(error));
    }, delayMs);
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

  const isBusinessWorkspacePage = () => {
    const subPage = resolveBusinessSubPage();
    return subPage !== '' && subPage !== 'connections';
  };

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
      const connectionStatus = String(business.connection_status || business.status || 'active').toLowerCase();
      return type === 'shared' && (connectionStatus === 'active' || connectionStatus === 'pending');
    }) || null;
  };

  const setBusinessDetailsStatus = (message, tone = 'info') => {
    setStatusText(elements.businessDetailsStatus, message, { tone, emptyClass: 'visually_hidden' });
  };

  const setPayrollStatus = (message, tone = 'info') => {
    setStatusText(elements.payrollStatus, message, { tone });
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
      markAutosaveTargetSaved();
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

  const saveBusinessDetailsSettings = async (source = 'auto', refreshAfterSave = false) => {
    emitBusinessDetailsAutosaveDiagnostic('save-start', {
      source,
      refresh_after_save: refreshAfterSave,
    });

    if (state.selectedBusinessId === '') {
      emitBusinessDetailsAutosaveDiagnostic('save-skip-no-business-id', {
        source,
        refresh_after_save: refreshAfterSave,
      }, 'warn');
      return false;
    }

    if (state.editorHydrating) {
      emitBusinessDetailsAutosaveDiagnostic('save-skip-hydrating', {
        source,
        refresh_after_save: refreshAfterSave,
      }, 'warn');
      return false;
    }

    const payload = collectBusinessDetailsPayload();
    const signature = buildEditorPayloadSignature(payload);
    const payloadSummary = summarizeBusinessDetailsPayloadForDiagnostics(payload, signature);
    emitBusinessDetailsAutosaveDiagnostic('save-payload-collected', {
      source,
      refresh_after_save: refreshAfterSave,
      ...payloadSummary,
    });

    if (signature === state.businessDetailsLastSavedSignature) {
      emitBusinessDetailsAutosaveDiagnostic('save-skip-no-changes', {
        source,
        refresh_after_save: refreshAfterSave,
        ...payloadSummary,
      });
      return false;
    }

    if (payload.name.length < 2) {
      emitBusinessDetailsAutosaveDiagnostic('save-skip-name-too-short', {
        source,
        refresh_after_save: refreshAfterSave,
        ...payloadSummary,
      }, 'warn');
      setBusinessDetailsStatus(T.nameMin, 'error');
      showBusinessesToast(T.nameMin, 'error', 7000, true);
      return false;
    }

    if (state.businessDetailsSaveInFlight) {
      state.businessDetailsSavePendingSource = source;
      emitBusinessDetailsAutosaveDiagnostic('save-queued-in-flight', {
        source,
        refresh_after_save: refreshAfterSave,
        pending_source: source,
        ...payloadSummary,
      }, 'warn');
      return false;
    }

    state.businessDetailsSaveInFlight = true;
    emitBusinessDetailsAutosaveDiagnostic('request-start', {
      source,
      refresh_after_save: refreshAfterSave,
      ...payloadSummary,
    });
    const requestStartedAt = performance.now();
    try {
      const responseData = await postForm(`/api/v1/businesses/${encodeURIComponent(state.selectedBusinessId)}/settings/update`, payload);
      const requestDurationMs = Math.round(performance.now() - requestStartedAt);
      state.businessDetailsLastSavedSignature = signature;
      state.editorLastSavedSignature = signature;
      let toastMessage = T.businessDetailsSaved;
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
      markAutosaveTargetSaved();
      emitBusinessDetailsAutosaveDiagnostic('request-success', {
        source,
        refresh_after_save: refreshAfterSave,
        request_duration_ms: requestDurationMs,
        response_key_count: responseData && typeof responseData === 'object' ? Object.keys(responseData).length : 0,
        toast_message_present: toastMessage !== '',
        ...payloadSummary,
      });
      setBusinessDetailsStatus('');
      if (refreshAfterSave) {
        await refreshIndex(state.selectedBusinessId, false);
      } else {
        await loadBusinesses();
        applySingleBusinessOverviewMode();
      }
      return true;
    } catch (error) {
      const message = error instanceof Error && error.message ? error.message : T.businessDetailsSaveFailed;
      emitBusinessDetailsAutosaveDiagnostic('request-failed', {
        source,
        refresh_after_save: refreshAfterSave,
        request_duration_ms: Math.round(performance.now() - requestStartedAt),
        error_message: message,
        error_status: Number(error?.status || 0),
        error_data_keys: error?.data && typeof error.data === 'object' ? Object.keys(error.data) : [],
        ...payloadSummary,
      }, 'error');
      setBusinessDetailsStatus(message, 'error');
      showBusinessesToast(message, 'error', 7000, true);
      return false;
    } finally {
      state.businessDetailsSaveInFlight = false;
      if (state.businessDetailsSavePendingSource !== '') {
        const pending = state.businessDetailsSavePendingSource;
        state.businessDetailsSavePendingSource = '';
        emitBusinessDetailsAutosaveDiagnostic('save-replay-pending', {
          source: pending,
          refresh_after_save: false,
        });
        saveBusinessDetailsSettings(pending, false).catch((error) => PW.error(error));
      }
    }
  };

  const openDetailsPage = async (businessId) => {
    emitBusinessDetailsAutosaveDiagnostic('open-details-start', {
      business_id_present: String(businessId || '').trim() !== '',
    });

    const business = findBusiness(businessId);
    if (!business) {
      emitBusinessDetailsAutosaveDiagnostic('open-details-missing-business', {
        business_id_present: String(businessId || '').trim() !== '',
      }, 'warn');
      setBusinessDetailsStatus(T.businessDetailsNoBusiness, 'error');
      return;
    }

    const perf = typeof resolveBusinessDetailsLensPerf === 'function'
      ? resolveBusinessDetailsLensPerf()
      : null;
    if (perf?.isEnabled()) {
      perf.start('openDetailsPage');
    }

    stopDiscoveryPolling();
    stopRealtimeAuditPolling();
    state.selectedBusinessId = businessId;
    setEditorMeta(business);
    closeContactImagePopover();
    emitBusinessDetailsAutosaveDiagnostic('open-details-business-selected', {
      business_id_present: String(businessId || '').trim() !== '',
      business_found: true,
    });

    try {
      await loadBusinessSettings(businessId);
      state.businessDetailsLastSavedSignature = buildEditorPayloadSignature(collectBusinessDetailsPayload());
      setBusinessDetailsStatus('');
      bindBusinessDetailsContactPanelEvents();
      emitBusinessDetailsAutosaveDiagnostic('open-details-ready', {
        business_id_present: String(businessId || '').trim() !== '',
        business_details_signature_present: String(state.businessDetailsLastSavedSignature || '') !== '',
      });
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message ? error.message : T.loadDefaultsFailed;
      emitBusinessDetailsAutosaveDiagnostic('open-details-failed', {
        business_id_present: String(businessId || '').trim() !== '',
        error_message: message,
        error_status: Number(error?.status || 0),
      }, 'error');
      setBusinessDetailsStatus(message, 'error');
      PC.showToast(message, 'error', 7000, true);
    } finally {
      if (perf?.isEnabled()) {
        perf.end('openDetailsPage', { ranked: false });
      }
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
    const status = String(business?.connection_status || business?.status || '').trim().toLowerCase();
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
      tabNode.classList.toggle('is-hidden-reserved', !visible);
      tabNode.classList.remove('hidden');
      tabNode.setAttribute('aria-hidden', visible ? 'false' : 'true');
      tabNode.tabIndex = visible ? 0 : -1;
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
    const selectedNotice = formatPhpTemplate(T.premiumNoticeTypeSelected, [typeLabel, T.nounBusiness]);
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
    if (elements.connectionsReload instanceof HTMLButtonElement) {
      elements.connectionsReload.disabled = isLocked || accessLocked;
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

  const warmBusinessWorkspaceCache = (businessId) => {
    const orgId = String(businessId || '').trim();
    if (orgId === '') {
      return;
    }

    const year = new Date().getFullYear();
    const url = `/api/v1/businesses/${encodeURIComponent(orgId)}/cache/warm?year=${encodeURIComponent(String(year))}`;

    apiRequest(url, { timeoutMs: 10000 }).catch((error) => {
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
    elements.scopeStatus.textContent = formatPhpTemplate(template, [count, count === 1 ? '' : 's']);
  };

  const announceBusinessSitesStatus = (message) => {
    setStatusText(elements.businessSitesStatus, message);
  };

  const announceAuditStatus = (message) => {
    setStatusText(elements.auditStatus, message);
  };

  const announceFreeAuditStatus = (message) => {
    setStatusText(elements.freeAuditStatus, message);
  };

  const findBusiness = (businessId) => {
    return state.businesses.find((business) => String(business.business_id || '') === String(businessId)) || null;
  };

  const getCurrentConnectionBusinesses = () => state.businesses
    .filter((business) => {
      if (!business || isPersonalBusiness(business)) {
        return false;
      }

      const status = String(business.connection_status || '').toLowerCase();
      return status === 'active' || status === 'pending';
    })
    .sort((left, right) => {
      const leftStatus = String(left.connection_status || '').toLowerCase();
      const rightStatus = String(right.connection_status || '').toLowerCase();
      if (leftStatus !== rightStatus) {
        return leftStatus === 'active' ? -1 : 1;
      }

      return String(left.name || '').localeCompare(String(right.name || ''));
    });

  const getCurrentConnectionBusiness = () => {
    const selectedBusinessId = String(state.currentConnectionBusinessId || '').trim();
    if (selectedBusinessId !== '') {
      const selectedBusiness = findBusiness(selectedBusinessId);
      if (selectedBusiness) {
        return selectedBusiness;
      }
    }

    return getCurrentConnectionBusinesses()[0] || null;
  };

  const setCurrentBusinessStatus = (message) => {
    setStatusText(elements.currentStatus, message);
  };

  const formatPhoneDisplayValue = (value) => {
    if (typeof PC.formatPhoneNumberValue === 'function') {
      return PC.formatPhoneNumberValue(String(value || ''));
    }

    return String(value || '');
  };

  const formatCurrentBusinessAddress = (business) => {
    const addressLine1 = String(business?.address_line1 || '').trim();
    const addressLine2 = String(business?.address_line2 || '').trim();
    const city = String(business?.address_city || '').trim();
    const province = String(business?.address_region || '').trim();
    const postalCode = String(business?.address_postal || '').trim();
    const country = String(business?.address_country || '').trim();
    const localityLine = [city, province, postalCode].filter((part) => part !== '').join(' ');

    return [addressLine1, addressLine2, localityLine, country].filter((line) => line !== '');
  };

  const formatCurrentBusinessWebsite = (rawValue) => {
    const rawWebsite = String(rawValue || '').trim();
    if (rawWebsite === '') {
      return '';
    }

    const websiteText = rawWebsite.replace(/^https?:\/\//i, '').replace(/\/$/, '');
    const websiteHref = /^https?:\/\//i.test(rawWebsite) ? rawWebsite : `https://${rawWebsite}`;

    return `<a href="${safeText(websiteHref)}" target="_blank" rel="noopener noreferrer">${safeText(websiteText)}</a>`;
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
      elements.subtitle.textContent = formatPhpTemplate(T.editorSubtitle, [
        type,
        T.nounBusiness,
        role,
        status,
      ]);
    }
    if (elements.businessIdField instanceof HTMLInputElement) {
      elements.businessIdField.value = String(business?.business_id || '');
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

    const startRaw = elements.payPeriodStart instanceof HTMLInputElement ? String(elements.payPeriodStart.value || '') : '';
    const frequency = elements.payFrequency instanceof HTMLSelectElement ? elements.payFrequency.value : 'biweekly';
    const preview = buildPayPeriodPreviewState({
      startYmd: startRaw,
      frequency,
      anchor: getEditorPayAnchor(),
      graceDays: getEditorEditingGraceDays(),
      dayNames: PAY_PERIOD_DAY_NAMES,
      alignBiweeklyToAnchor: false,
      includeSummary: true,
      calendarOptions: {
        headerMode: 'stripbar',
        selectable: true,
        dayNumberClass: true,
      },
    });

    if (elements.payPeriodStart instanceof HTMLInputElement && elements.payPeriodStart.value === '') {
      elements.payPeriodStart.value = preview.startValue;
    }

    if (preview.startValue === '') {
      elements.preview.textContent = T.previewEmpty;
      if (elements.payPeriodGridStatus instanceof HTMLElement) {
        elements.payPeriodGridStatus.textContent = T.previewEmpty;
      }
      return;
    }

    Guardian.setHTML(elements.preview, preview.html);

    if (elements.payPeriodGridStatus instanceof HTMLElement) {
      elements.payPeriodGridStatus.textContent = formatPhpTemplate(T.ppPreviewStatus, [
        preview.p1Start,
        preview.p1End,
        preview.p2Start,
        preview.p2End,
      ]);
    }
  };

  const handleEditorPreviewInteraction = (event) => {
    const selection = resolvePayPeriodPreviewSelection(event);
    if (selection === null) {
      return;
    }

    if (elements.payPeriodStart instanceof HTMLInputElement) {
      elements.payPeriodStart.value = selection.ymd;
    }
    setEditorPayAnchor(selection.anchor);
    renderPreview();
    scheduleEditorAutoSave(220, 'calendar-day');
  };

  const hydrateSettings = (payload, business) => {
    state.editorHydrating = true;
    const settings = payload && typeof payload === 'object' && payload.settings && typeof payload.settings === 'object'
      ? payload.settings
      : {};

    emitBusinessDetailsAutosaveDiagnostic('hydrate-start', {
      business_present: Boolean(business),
      settings_key_count: Object.keys(settings).length,
    });

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
        ? collectBusinessDetailsPayload()
        : resolveBusinessSubPage() === 'payroll'
          ? collectPayrollPayload()
          : collectBusinessEditorPayload(),
    );
    state.editorLastSavedSignature = payloadSignature;
    if (resolveBusinessSubPage() === 'details') {
      state.businessDetailsLastSavedSignature = payloadSignature;
    }
    if (resolveBusinessSubPage() === 'payroll') {
      state.payrollLastSavedSignature = payloadSignature;
    }

    emitBusinessDetailsAutosaveDiagnostic('hydrate-complete', {
      business_present: Boolean(business),
      settings_key_count: Object.keys(settings).length,
      ...summarizeBusinessDetailsPayloadForDiagnostics(
        resolveBusinessSubPage() === 'details' ? collectBusinessDetailsPayload() : {},
        payloadSignature,
      ),
    });
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

  const loadBusinessSettings = async (businessId) => {
    emitBusinessDetailsAutosaveDiagnostic('settings-load-start', {
      business_id_present: String(businessId || '').trim() !== '',
    });
    const requestStartedAt = performance.now();
    try {
      const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/settings`);
      emitBusinessDetailsAutosaveDiagnostic('settings-load-success', {
        business_id_present: String(businessId || '').trim() !== '',
        request_duration_ms: Math.round(performance.now() - requestStartedAt),
        payload_key_count: payload && typeof payload === 'object' ? Object.keys(payload).length : 0,
        settings_key_count: payload?.settings && typeof payload.settings === 'object' ? Object.keys(payload.settings).length : 0,
      });
      const business = findBusiness(businessId);
      hydrateSettings(payload, business);
      return payload;
    } catch (error) {
      emitBusinessDetailsAutosaveDiagnostic('settings-load-failed', {
        business_id_present: String(businessId || '').trim() !== '',
        request_duration_ms: Math.round(performance.now() - requestStartedAt),
        error_message: error instanceof Error && error.message ? error.message : T.loadDefaultsFailed,
        error_status: Number(error?.status || 0),
      }, 'error');
      throw error;
    }
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

        if (resolveBusinessSubPage() === 'connections') {
          await loadPersonConnections();
          stopDiscoveryPolling();
          stopRealtimeAuditPolling();
          return;
        }

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
          const detailsBusinessId = resolveWorkspaceBusinessId(preferredBusinessId);

          if (detailsBusinessId !== '' && findBusiness(detailsBusinessId)) {
            await openDetailsPage(detailsBusinessId);
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

        if (resolveBusinessSubPage() === 'groups') {
          initBusinessGroupsPage();
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
        PC.showToast(T.loadBusinessesFailed, 'error', 7000, true);
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
      query === '' ? T.discoverySearchCleared : formatPhpTemplate(T.discoverySearchApplied, [query]),
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
        : formatPhpTemplate(T.accessRequestSubmittedId, [requestId]);

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
        : formatPhpTemplate(T.auditControlTestRecordedUploaded, [objectPath]);

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
        ? `${T.businessDekBootstrapDone} ${bootstrappedCount} member(s) bootstrapped, ${failedCount} failed.`
        : `${T.businessDekBootstrapDone} ${bootstrappedCount} member(s) bootstrapped.`;

      announceLiveToast(message, 'save');
      PC.showToast(message, failedCount > 0 ? 'error' : 'save', failedCount > 0 ? 9000 : 7000, true);
      await refreshIndex(state.selectedBusinessId, true);
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message
        ? error.message
        : T.businessDekBootstrapFailed;
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
        const message = error instanceof Error && error.message ? error.message : T.loadBusinessesFailed;
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
    elements.personConnectionForm?.addEventListener('submit', (event) => {
      handlePersonConnectionRequest(event).catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.connectionsPersonRequestFailed;
        setPersonConnectionsStatus(message);
        PC.showToast(message, 'error', 7000, true);
      });
    });
    elements.personConnectionsList?.addEventListener('click', (event) => {
      handlePersonConnectionAction(event).catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.connectionsPersonRequestFailed;
        setPersonConnectionsStatus(message);
        PC.showToast(message, 'error', 7000, true);
      });
    });
    elements.personManageForm?.addEventListener('submit', (event) => {
      savePersonManageDialog(event).catch((error) => {
        PW.error(error);
        const message = error instanceof Error && error.message ? error.message : T.connectionsRevokeGrantFailed;
        setPersonConnectionsStatus(message);
        PC.showToast(message, 'error', 7000, true);
      });
    });
    elements.personManageCancel?.addEventListener('click', () => {
      closePersonManageDialog();
    });
    document.querySelectorAll('[data-dialog-close="connections_person_manage_dialog"]').forEach((button) => {
      button.addEventListener('click', () => {
        closePersonManageDialog();
      });
    });
    bindBusinessBrowserEvents();
    elements.currentMeta?.addEventListener('click', (event) => {
      const target = event.target instanceof Element
        ? event.target.closest('[data-current-business-action]')
        : null;
      if (!(target instanceof HTMLButtonElement)) {
        return;
      }

      const action = String(target.dataset.currentBusinessAction || '').trim();
      const businessId = String(target.dataset.businessId || '').trim();
      if (action === 'view-profile') {
        if (businessId !== '') {
          state.currentConnectionBusinessId = businessId;
        }
        openCurrentBusinessDetailsDialog();
        return;
      }

      if (action === 'leave') {
        handleRevokeCurrentBusinessAccess(businessId).catch((error) => {
          PW.error(error);
          const message = error instanceof Error && error.message ? error.message : T.withdrawFailed;
          setCurrentBusinessStatus(message, 'error');
          PC.showToast(message, 'error', 7000, true);
        });
      }
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
          : T.businessDekBootstrapFailed;
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
    elements.scopeGrid?.addEventListener('change', (event) => {
      const input = event.target;
      if (input instanceof HTMLInputElement && input.classList.contains('businesses_scope')) {
        announceScopeSelectionStatus('updated');
      }
    });
    bindAccessManagementEvents();
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
    const auditDetailsPopoverController = createAnchoredPopoverController();

    const openAuditDetailsPopoverFor = (trigger, popover) => {
      auditDetailsPopoverController.open(trigger, popover);
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

    bindAnchoredPopoverGlobalDismissals(auditDetailsPopoverController, 'auditDetailsPopoverDismissBound', {
      capture: true,
      pointerEvent: 'click',
    });

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

    bindBusinessDetailsContactPanelEvents();

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
          const payload = await postForm('/api/v1/businesses/create', { name: nameValue });

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

  const initialize = async () => {
    const subPage = resolveBusinessSubPage();
    const membersPerf = subPage === 'members' ? resolveMembersLensPerf() : null;
    const detailsPerf = subPage === 'details' && typeof resolveBusinessDetailsLensPerf === 'function'
      ? resolveBusinessDetailsLensPerf()
      : null;

    const runInitialize = async () => {
      bindEvents();
      syncBusinessWorkspaceElementRefs();

      if (elements.browserGrid instanceof HTMLElement) {
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

      if (subPage === 'join') {
        return;
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

    if (membersPerf?.isEnabled() || detailsPerf?.isEnabled()) {
      try {
        await runInitialize();
      } finally {
        if (typeof finalizeBusinessMembersLensPerfSummary === 'function') {
          finalizeBusinessMembersLensPerfSummary('Performance Summary');
        } else if (membersPerf?.isEnabled()) {
          membersPerf.markHydrationComplete();
          membersPerf.summarize('Performance Summary');
        }
        if (typeof finalizeBusinessDetailsLensPerfSummary === 'function') {
          finalizeBusinessDetailsLensPerfSummary('Autosave Path Summary');
        } else if (detailsPerf?.isEnabled()) {
          detailsPerf.markHydrationComplete();
          detailsPerf.summarize('Autosave Path Summary');
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

    if (subPage === 'details' && typeof finalizeBusinessDetailsLensPerfSummary === 'function') {
      finalizeBusinessDetailsLensPerfSummary('Autosave Path Summary');
    }
  };
