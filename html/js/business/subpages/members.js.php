<?php namespace PayCal\Domain; ?>

  // Subpage module: members (data-business-subpage="members")
  // Entry: openMembersPage via refreshIndex; loadBusinessMembersGrid fetches the datagrid.

  const BUSINESS_MEMBERS_LENS_PREFIX = '[PayCal Lens][business/members]';
  const MEMBER_REPORT_CONFIRM_THRESHOLD = 25;
  const MEMBER_REPORT_REPEAT_COOLDOWN_MS = 3000;
  const MEMBER_REPORT_TEXT_ENCODER = new TextEncoder();
  let memberReportBatchRunning = false;
  let memberReportLastStartedAt = 0;
  let memberReportLastBatch = null;

  const isBusinessMembersSubPage = () => resolveBusinessSubPage() === 'members';

  const resolveBusinessMembersLensBootOptions = () => {
    const fromWindow = window.__PAYCAL_LENS_PERF__?.['business/members'];
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
        console.warn(BUSINESS_MEMBERS_LENS_PREFIX, 'Invalid data-lens-perf-boot JSON', error);
      }
    }

    if (workspace instanceof HTMLElement && workspace.dataset.lensPageDebug) {
      return { ranked: true, enabled: true, scope: 'business/members' };
    }

    return { ranked: true, enabled: false };
  };

  const resolveBusinessMembersLensPerf = () => {
    if (!isBusinessMembersSubPage()) {
      return null;
    }

    if (typeof window.PayCalLensPerformance?.create !== 'function') {
      console.warn(BUSINESS_MEMBERS_LENS_PREFIX, 'PayCalLensPerformance.create unavailable — perf summary disabled');
      return null;
    }

    const bootOptions = resolveBusinessMembersLensBootOptions();
    const shouldEnable = bootOptions.enabled !== false;
    const existing = window.PayCalLensMembersPerf;

    if (!existing) {
      window.PayCalLensMembersPerf = window.PayCalLensPerformance.create('business/members', bootOptions);
      if (window.PayCalLensMembersPerf?.isEnabled()) {
        window.PayCalLensMembersPerf.markSsrPainted();
      }
      return window.PayCalLensMembersPerf;
    }

    if (shouldEnable && !existing.isEnabled()) {
      window.PayCalLensMembersPerf = window.PayCalLensPerformance.create('business/members', bootOptions);
      if (window.PayCalLensMembersPerf?.isEnabled()) {
        window.PayCalLensMembersPerf.markSsrPainted();
      }
    }

    return window.PayCalLensMembersPerf;
  };

  let businessMembersLensPerfSummaryEmitted = false;

  const finalizeBusinessMembersLensPerfSummary = (title = 'Performance Summary') => {
    const perf = resolveBusinessMembersLensPerf();
    if (!perf?.isEnabled()) {
      return;
    }

    if (businessMembersLensPerfSummaryEmitted) {
      return;
    }

    businessMembersLensPerfSummaryEmitted = true;
    perf.markHydrationComplete();
    perf.summarize(title);
  };

  const logBusinessMembersLensPageDebug = () => {
    const workspace = document.getElementById('business-workspace');
    if (!(workspace instanceof HTMLElement) || !workspace.dataset.lensPageDebug) {
      return;
    }

    try {
      const debug = JSON.parse(workspace.dataset.lensPageDebug);
      console.groupCollapsed(BUSINESS_MEMBERS_LENS_PREFIX + ' page debug');
      console.log(BUSINESS_MEMBERS_LENS_PREFIX, 'Lens mode requested:', debug.lens_requested);
      console.log(BUSINESS_MEMBERS_LENS_PREFIX, 'Lens enabled:', debug.lens_enabled);
      console.dir(debug.snapshot);
      if (debug.lens_meta && Object.keys(debug.lens_meta).length) {
        console.log(BUSINESS_MEMBERS_LENS_PREFIX, 'Lens meta:', debug.lens_meta);
      }
      if (Array.isArray(debug.lens_events) && debug.lens_events.length) {
        console.group(BUSINESS_MEMBERS_LENS_PREFIX + ' Lens events');
        debug.lens_events.forEach((event) => {
          console.group((event.label || 'event') + ' (' + (event.type || 'data') + ')');
          console.dir(event.payload);
          console.groupEnd();
        });
        console.groupEnd();
      }
      if (debug.lens_counters && Object.keys(debug.lens_counters).length) {
        console.log(BUSINESS_MEMBERS_LENS_PREFIX, 'Lens counters:', debug.lens_counters);
      }
      console.groupEnd();
    } catch (error) {
      console.warn(BUSINESS_MEMBERS_LENS_PREFIX, 'Invalid data-lens-page-debug JSON', error);
    }
  };

  const logBusinessMembersLensDebug = () => {
    const workspace = document.getElementById('business-workspace');
    if (!(workspace instanceof HTMLElement)) {
      console.warn(BUSINESS_MEMBERS_LENS_PREFIX, 'Missing #business-workspace');
      return;
    }

    const gridContainer = document.getElementById('businesses-members-grid');
    const gridBody = gridContainer?.querySelector('.datagrid_body') ?? null;
    const rowCount = gridBody
      ? gridBody.querySelectorAll('.businesses_member_row_clickable, .datagrid_row').length
      : 0;

    console.groupCollapsed(BUSINESS_MEMBERS_LENS_PREFIX + ' DOM init');
    console.log(BUSINESS_MEMBERS_LENS_PREFIX, 'subpage', workspace.dataset.businessSubpage || '(missing)');
    console.log(BUSINESS_MEMBERS_LENS_PREFIX, 'data-business-id', workspace.dataset.businessId || '(none)');
    console.log(BUSINESS_MEMBERS_LENS_PREFIX, 'data-lens-mode', workspace.dataset.lensMode || '0');
    console.log(BUSINESS_MEMBERS_LENS_PREFIX, 'grid container present', gridContainer instanceof HTMLElement);
    console.log(BUSINESS_MEMBERS_LENS_PREFIX, 'SSR grid rows', rowCount);
    console.groupEnd();
  };

  if (isBusinessMembersSubPage()) {
    logBusinessMembersLensPageDebug();
    logBusinessMembersLensDebug();
  }

  const syncMembersGridElementRefs = () => {
    elements.membersGridContainer = document.getElementById('businesses-members-grid');
    elements.membersGridStatus = document.getElementById('businesses_members_grid_sr_status');
    elements.membersBulkToolbar = document.getElementById('business_members_bulk_toolbar');
    elements.membersSelectAllButton = document.getElementById('business_members_select_all');
    elements.membersClearSelectionButton = document.getElementById('business_members_clear_selection');
    elements.membersSelectionCount = document.getElementById('business_members_selection_count');
    elements.membersSelectionBadgeCount = document.getElementById('business_members_selection_badge_count');
    elements.membersMetricSelected = document.getElementById('business_members_metric_selected');
    elements.membersApplySiteSelect = document.getElementById('business_members_apply_site_select');
    elements.membersApplyWorkSiteButton = document.getElementById('business_members_apply_work_site');
    elements.membersBulkStatus = document.getElementById('business_members_bulk_status');
    elements.membersReportToggle = document.getElementById('business_members_report_toggle');
    elements.membersReportPanel = document.getElementById('business_members_report_panel');
    elements.membersReportType = document.getElementById('business_members_report_type');
    elements.membersReportFormat = document.getElementById('business_members_report_format');
    elements.membersReportDelivery = document.getElementById('business_members_report_delivery');
    elements.membersReportYear = document.getElementById('business_members_report_year');
    elements.membersGenerateReportsButton = document.getElementById('business_members_generate_reports');
    elements.membersReportStatus = document.getElementById('business_members_report_status');
    elements.membersReportSummary = document.getElementById('business_members_report_summary');
    elements.membersReportSummaryMembers = document.getElementById('business_members_report_summary_members');
    elements.membersReportSummaryReport = document.getElementById('business_members_report_summary_report');
    elements.membersReportSummaryYear = document.getElementById('business_members_report_summary_year');
    elements.membersReportSummaryFormat = document.getElementById('business_members_report_summary_format');
    elements.membersReportSummaryCompleted = document.getElementById('business_members_report_summary_completed');
    elements.membersReportSummaryTime = document.getElementById('business_members_report_summary_time');
    elements.membersReportDownloadZip = document.getElementById('business_members_report_download_zip');
    elements.membersReportDownloadManifest = document.getElementById('business_members_report_download_manifest');
    elements.membersReportGenerateAnother = document.getElementById('business_members_report_generate_another');
    elements.membersPendingDetails = document.getElementById('business_members_pending_details');
    elements.membersPendingList = document.getElementById('business_members_pending_list');
    elements.membersPendingSummaryLabel = document.getElementById('business_members_pending_summary_label');
    elements.membersPendingStatus = document.getElementById('business_members_pending_sr_status');
    elements.membersMetricPending = document.getElementById('business_members_metric_pending');
    elements.membersMetricPendingChip = document.getElementById('business_members_metric_pending_chip');
    elements.membersMetricPendingStatic = document.getElementById('business_members_metric_pending_static');
  };

  const formatMembersPendingSummaryLabel = (count = 0) => (
    T.membersPendingSummary.replace('%d', String(Math.max(0, Number(count || 0))))
  );

  const formatMembersPendingMetricAria = (count = 0) => {
    const normalized = Math.max(0, Number(count || 0));
    if (normalized === 0) {
      return T.pending;
    }

    return T.membersPendingMetricAria
      .replace('%d', String(normalized))
      .replace('%s', normalized === 1 ? '' : 's');
  };

  const announceMembersPendingStatus = (message) => {
    if (elements.membersPendingStatus instanceof HTMLElement) {
      elements.membersPendingStatus.textContent = String(message || '');
    }
  };

  const syncMembersPendingMetricUi = (count = 0) => {
    const normalized = Math.max(0, Number(count || 0));
    const countLabel = String(normalized);

    if (elements.membersMetricPending instanceof HTMLElement) {
      elements.membersMetricPending.textContent = countLabel;
    }

    if (elements.membersPendingSummaryLabel instanceof HTMLElement) {
      elements.membersPendingSummaryLabel.textContent = formatMembersPendingSummaryLabel(normalized);
    }

    if (elements.membersMetricPendingChip instanceof HTMLButtonElement) {
      elements.membersMetricPendingChip.hidden = normalized === 0;
      elements.membersMetricPendingChip.disabled = normalized === 0;
      elements.membersMetricPendingChip.setAttribute('aria-label', formatMembersPendingMetricAria(normalized));
    }

    if (elements.membersMetricPendingStatic instanceof HTMLElement) {
      elements.membersMetricPendingStatic.hidden = normalized > 0;
    }

    if (elements.membersPendingDetails instanceof HTMLDetailsElement) {
      elements.membersPendingDetails.hidden = normalized === 0;
      if (normalized === 0) {
        elements.membersPendingDetails.open = false;
      }
    }
  };

  const setMembersPendingAccordionOpen = (isOpen = true) => {
    if (!(elements.membersPendingDetails instanceof HTMLDetailsElement)) {
      return;
    }

    elements.membersPendingDetails.open = isOpen;
    if (elements.membersMetricPendingChip instanceof HTMLButtonElement) {
      elements.membersMetricPendingChip.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
  };

  const renderMembersPendingInviteRow = (invite) => {
    const inviteId = String(invite?.invite_id || '').trim();
    const email = String(invite?.invitee_email || T.unknown).trim();
    const status = String(invite?.status || T.pending).trim();
    const roleLabel = deriveInviteRoleLabel(invite);
    const timestamp = formatInviteTimestamp(invite?.created_at || '');
    const metaParts = [roleLabel, timestamp, status].filter((part) => String(part || '').trim() !== '');

    return ''
      + '<div class="business_members_pending_row" role="listitem" data-pending-type="invite">'
      + '<div class="business_members_pending_info">'
      + '<span class="business_members_pending_type">' + safeText(T.membersPendingTypeInvite) + '</span>'
      + '<strong class="business_members_pending_email">' + safeText(email) + '</strong>'
      + '<span class="business_members_pending_meta">' + safeText(metaParts.join(' · ')) + '</span>'
      + '</div>'
      + '<div class="business_members_pending_actions">'
      + '<button type="button" class="btn btn_delete" data-org-action="revoke-invite" data-invite-id="'
      + safeText(inviteId)
      + '">'
      + safeText(T.revoke)
      + '</button>'
      + '</div>'
      + '</div>';
  };

  const renderMembersPendingRequestRow = (request) => {
    const requestId = String(request?.request_id || '').trim();
    const requester = String(request?.requester_contact_email || request?.requester_uuid || T.unknown).trim();
    const status = String(request?.status || T.pending).trim();
    const createdAt = formatLiveRequestCreatedAt(request?.created_at || '');
    const metaParts = [status];
    if (createdAt !== '') {
      metaParts.push(createdAt);
    }

    return ''
      + '<div class="business_members_pending_row" role="listitem" data-pending-type="request">'
      + '<div class="business_members_pending_info">'
      + '<span class="business_members_pending_type">' + safeText(T.membersPendingTypeRequest) + '</span>'
      + '<strong class="business_members_pending_email">' + safeText(requester) + '</strong>'
      + '<span class="business_members_pending_meta">' + safeText(metaParts.join(' · ')) + '</span>'
      + '</div>'
      + '<div class="business_members_pending_actions">'
      + '<button type="button" class="btn btn_secondary" data-org-action="approve-access-request" data-request-id="'
      + safeText(requestId)
      + '">'
      + safeText(T.membersPendingApprove)
      + '</button>'
      + '<button type="button" class="btn btn_delete" data-org-action="reject-access-request" data-request-id="'
      + safeText(requestId)
      + '">'
      + safeText(T.membersPendingReject)
      + '</button>'
      + '</div>'
      + '</div>';
  };

  const renderMembersPendingAccordion = (invites = [], requests = []) => {
    syncMembersGridElementRefs();

    const pendingInvites = (Array.isArray(invites) ? invites : [])
      .filter((invite) => String(invite?.status || 'pending').trim().toLowerCase() === 'pending');
    const pendingRequests = (Array.isArray(requests) ? requests : [])
      .filter((request) => String(request?.status || 'pending').trim().toLowerCase() === 'pending');
    const total = pendingInvites.length + pendingRequests.length;

    syncMembersPendingMetricUi(total);

    const list = elements.membersPendingList;
    if (!(list instanceof HTMLElement)) {
      return total;
    }

    if (total === 0) {
      list.replaceChildren();
      announceMembersPendingStatus('');
      return 0;
    }

    const rows = pendingInvites.map(renderMembersPendingInviteRow)
      .concat(pendingRequests.map(renderMembersPendingRequestRow));
    Guardian.setHTML(list, rows.join(''));
    announceMembersPendingStatus(
      T.membersPendingLoaded
        .replace('%d', String(total))
        .replace('%s', total === 1 ? '' : 's'),
    );

    return total;
  };

  const setMembersPendingAccordionMessage = (message) => {
    syncMembersGridElementRefs();
    syncMembersPendingMetricUi(0);

    if (elements.membersPendingList instanceof HTMLElement) {
      Guardian.setHTML(
        elements.membersPendingList,
        '<p class="business_members_pending_empty">' + safeText(message) + '</p>',
      );
    }

    if (elements.membersPendingDetails instanceof HTMLDetailsElement) {
      elements.membersPendingDetails.hidden = false;
      elements.membersPendingDetails.open = true;
    }

    announceMembersPendingStatus(String(message || ''));
  };

  const loadBusinessMembersPendingList = async (businessId = '') => {
    if (resolveBusinessSubPage() !== 'members') {
      return 0;
    }

    syncMembersGridElementRefs();

    const orgId = String(businessId || resolveWorkspaceBusinessId() || state.selectedBusinessId || '').trim();
    if (orgId === '') {
      renderMembersPendingAccordion([], []);
      return 0;
    }

    const business = findBusiness(orgId);
    if (business && !canUsePremiumOrgFeatures(business)) {
      setMembersPendingAccordionMessage(T.premiumAdminLockedDetailed);
      return 0;
    }

    if (business && !canManageBusinessAccess(business)) {
      setMembersPendingAccordionMessage(ACCESS_MANAGE_WARNING);
      return 0;
    }

    if (elements.membersPendingList instanceof HTMLElement) {
      Guardian.setHTML(
        elements.membersPendingList,
        '<p class="business_members_pending_empty">' + safeText(T.membersPendingLoading) + '</p>',
      );
    }
    announceMembersPendingStatus(T.membersPendingLoading);

    try {
      const [invitesPayload, requestsPayload] = await Promise.all([
        apiRequest(`/api/v1/businesses/${encodeURIComponent(orgId)}/invites`),
        apiRequest(`/api/v1/businesses/${encodeURIComponent(orgId)}/access/requests`),
      ]);

      return renderMembersPendingAccordion(
        Array.isArray(invitesPayload?.invites) ? invitesPayload.invites : [],
        Array.isArray(requestsPayload?.requests) ? requestsPayload.requests : [],
      );
    } catch (error) {
      PW.error(error);
      setMembersPendingAccordionMessage(T.membersPendingLoadFailed);
      return 0;
    }
  };

  const bindMembersPendingAccordion = () => {
    syncMembersGridElementRefs();

    const details = elements.membersPendingDetails;
    const metricChip = elements.membersMetricPendingChip;
    if (!(details instanceof HTMLDetailsElement) || details.dataset.membersPendingBound === '1') {
      return;
    }
    details.dataset.membersPendingBound = '1';

    details.addEventListener('toggle', () => {
      if (metricChip instanceof HTMLButtonElement) {
        metricChip.setAttribute('aria-expanded', details.open ? 'true' : 'false');
      }
    });

    if (metricChip instanceof HTMLButtonElement) {
      metricChip.addEventListener('click', () => {
        if (details.hidden) {
          return;
        }

        setMembersPendingAccordionOpen(true);
        details.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
      });
    }
  };

  const getMembersBulkSelectedCount = () => (
    state.membersBulkSelectAllActive
      ? Math.max(0, Number(state.membersBulkTotalCount || 0))
      : state.membersBulkSelectedIds.length
  );

  const formatMembersSelectionCountLabel = (count = getMembersBulkSelectedCount()) => {
    const normalized = Math.max(0, Number(count || 0));
    if (normalized === 0) {
      return T.membersSelectionCountNone;
    }

    return T.membersSelectionCount.replace('%d', String(normalized));
  };

  const updateMembersBulkSelectionUi = () => {
    const count = getMembersBulkSelectedCount();
    const countLabel = String(Math.max(0, Number(count || 0)));

    if (elements.membersSelectionBadgeCount instanceof HTMLElement) {
      elements.membersSelectionBadgeCount.textContent = countLabel;
    }

    if (elements.membersSelectionCount instanceof HTMLElement) {
      elements.membersSelectionCount.setAttribute('aria-label', formatMembersSelectionCountLabel(count));
    }

    if (elements.membersMetricSelected instanceof HTMLElement) {
      elements.membersMetricSelected.textContent = countLabel;
    }

    const siteSelected = elements.membersApplySiteSelect instanceof HTMLSelectElement
      && String(elements.membersApplySiteSelect.value || '').trim() !== '';
    if (elements.membersApplyWorkSiteButton instanceof HTMLButtonElement) {
      elements.membersApplyWorkSiteButton.disabled = count === 0 || !siteSelected;
    }

    if (elements.membersGenerateReportsButton instanceof HTMLButtonElement) {
      elements.membersGenerateReportsButton.disabled = count === 0;
    }
  };

  const syncMembersBulkCheckboxes = () => {
    const container = elements.membersGridContainer;
    if (!(container instanceof HTMLElement)) {
      return;
    }

    const selected = new Set(state.membersBulkSelectedIds);
    container.querySelectorAll('.business_members_row_checkbox').forEach((checkbox) => {
      if (!(checkbox instanceof HTMLInputElement)) {
        return;
      }

      const memberId = String(checkbox.dataset.memberId || '').trim();
      checkbox.checked = state.membersBulkSelectAllActive || (memberId !== '' && selected.has(memberId));
    });

    updateMembersBulkSelectionUi();
  };

  const clearMembersBulkSelection = () => {
    state.membersBulkSelectAllActive = false;
    state.membersBulkSelectedIds = [];
    syncMembersBulkCheckboxes();
  };

  const setMembersBulkToolbarVisible = (visible) => {
    if (elements.membersBulkToolbar instanceof HTMLElement) {
      elements.membersBulkToolbar.classList.toggle('hidden', !visible);
    }
    integrateMembersToolbarLayout();
  };

  const evacuateMembersBulkToolbar = () => {
    const mount = document.getElementById('business_members_bulk_toolbar_mount');
    const bulkToolbar = document.getElementById('business_members_bulk_toolbar');
    if (!(mount instanceof HTMLElement) || !(bulkToolbar instanceof HTMLElement)) {
      return;
    }

    if (!mount.contains(bulkToolbar)) {
      mount.appendChild(bulkToolbar);
    }
  };

  const integrateMembersToolbarLayout = () => {
    syncMembersGridElementRefs();
    const bulkToolbar = elements.membersBulkToolbar;
    const gridContainer = elements.membersGridContainer;
    if (!(bulkToolbar instanceof HTMLElement) || !(gridContainer instanceof HTMLElement)) {
      return;
    }

    const gridEl = gridContainer.querySelector('[data-grid="business-members"]');
    const datagridToolbar = gridEl instanceof HTMLElement
      ? gridEl.querySelector('.datagrid_toolbar_search_pagination')
      : null;
    if (!(datagridToolbar instanceof HTMLElement)) {
      return;
    }

    let bulkSlot = datagridToolbar.querySelector('.business_members_toolbar_bulk');
    if (!(bulkSlot instanceof HTMLElement)) {
      bulkSlot = document.createElement('div');
      bulkSlot.className = 'datagrid_toolbar_bulk business_members_toolbar_bulk';
      const columnMenu = gridEl.querySelector('.datagrid_column_menu');
      const toolbarCenter = datagridToolbar.querySelector('.datagrid_toolbar_center');
      if (columnMenu instanceof HTMLElement) {
        datagridToolbar.insertBefore(columnMenu, toolbarCenter);
      }
      datagridToolbar.insertBefore(bulkSlot, toolbarCenter);
    }

    if (!bulkSlot.contains(bulkToolbar)) {
      bulkSlot.appendChild(bulkToolbar);
    }

    bulkToolbar.classList.toggle('hidden', bulkToolbar.classList.contains('hidden'));
  };

  const closeAllMemberRowMenus = (exceptMenu = null) => {
    const container = elements.membersGridContainer;
    if (!(container instanceof HTMLElement)) {
      return;
    }

    container.querySelectorAll('.business_member_row_menu.is_open').forEach((menu) => {
      if (!(menu instanceof HTMLElement)) {
        return;
      }
      if (exceptMenu instanceof HTMLElement && menu === exceptMenu) {
        return;
      }

      const toggle = menu.querySelector('.business_member_row_menu_toggle');
      const panel = menu.querySelector('.business_member_row_menu_panel');
      if (toggle instanceof HTMLButtonElement) {
        toggle.setAttribute('aria-expanded', 'false');
      }
      if (panel instanceof HTMLElement) {
        panel.hidden = true;
      }
      menu.classList.remove('is_open');
    });
  };

  const setMemberRowMenuOpen = (menu, isOpen) => {
    if (!(menu instanceof HTMLElement)) {
      return;
    }

    const toggle = menu.querySelector('.business_member_row_menu_toggle');
    const panel = menu.querySelector('.business_member_row_menu_panel');
    if (toggle instanceof HTMLButtonElement) {
      toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
    if (panel instanceof HTMLElement) {
      panel.hidden = !isOpen;
    }
    menu.classList.toggle('is_open', isOpen);
  };

  const handleMemberRowMenuAction = (menuItem, row) => {
    if (!(menuItem instanceof HTMLButtonElement)) {
      return;
    }

    const action = String(menuItem.dataset.memberAction || '').trim();
    const memberUuid = String(menuItem.dataset.memberId || row?.dataset?.memberId || row?.dataset?.id || '').trim();
    if (memberUuid === '') {
      return;
    }

    if (action === 'edit-role') {
      const roleTrigger = row instanceof HTMLElement
        ? row.querySelector('.businesses_member_role_trigger')
        : null;
      if (roleTrigger instanceof HTMLElement) {
        toggleMemberRolePopover(roleTrigger, memberUuid);
      }
      return;
    }

    if (action === 'revoke') {
      showConfirmRevokeDialog(memberUuid, menuItem).catch((error) => PW.error(error));
    }
  };

  const populateMembersApplySiteOptions = (sites = []) => {
    if (!(elements.membersApplySiteSelect instanceof HTMLSelectElement)) {
      return;
    }

    const previousValue = String(elements.membersApplySiteSelect.value || '');
    const options = ['<option value="">' + safeText(T.membersApplySitePlaceholder) + '</option>'];
    (Array.isArray(sites) ? sites : []).forEach((site) => {
      if (!site || typeof site !== 'object') {
        return;
      }

      const siteId = String(site.site_id || '').trim();
      const siteOwnerUuid = String(site.site_owner_uuid || '').trim();
      const siteName = String(site.site_name || siteId).trim();
      if (siteId === '' || siteOwnerUuid === '') {
        return;
      }

      const value = siteOwnerUuid + ':' + siteId;
      options.push(
        '<option value="' + safeText(value) + '">' + safeText(siteName) + '</option>',
      );
    });

    Guardian.setHTML(elements.membersApplySiteSelect, options.join(''));
    if (previousValue !== '' && elements.membersApplySiteSelect.querySelector('option[value="' + CSS.escape(previousValue) + '"]')) {
      elements.membersApplySiteSelect.value = previousValue;
    }

    updateMembersBulkSelectionUi();
  };

  const loadMembersApplySiteOptions = async (orgId) => {
    const businessId = String(orgId || '').trim();
    if (businessId === '') {
      populateMembersApplySiteOptions([]);
      return;
    }

    if (state.membersBulkSitesLoadedOrgId === businessId
      && elements.membersApplySiteSelect instanceof HTMLSelectElement
      && elements.membersApplySiteSelect.options.length > 1) {
      return;
    }

    try {
      const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/sites`);
      const sites = Array.isArray(payload?.sites) ? payload.sites : [];
      populateMembersApplySiteOptions(sites);
      state.membersBulkSitesLoadedOrgId = businessId;
    } catch (error) {
      PW.error(error);
      populateMembersApplySiteOptions([]);
    }
  };

  const refreshMembersBulkToolbar = async (orgId = '') => {
    syncMembersGridElementRefs();
    const businessId = String(orgId || resolveWorkspaceBusinessId() || state.selectedBusinessId || '').trim();
    const business = findBusiness(businessId);
    const canBulkApply = canWriteBusinessSites(business);

    setMembersBulkToolbarVisible(canBulkApply);
    if (!canBulkApply) {
      clearMembersBulkSelection();
      return;
    }

    if (businessId !== '') {
      try {
        const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/relationships`);
        const members = Array.isArray(payload?.members) ? payload.members : [];
        state.membersBulkTotalCount = members.length;
      } catch (_error) {
        state.membersBulkTotalCount = 0;
      }

      await loadMembersApplySiteOptions(businessId);
    }

    syncMembersBulkCheckboxes();
  };

  const handleMembersBulkCheckboxChange = (checkbox) => {
    if (!(checkbox instanceof HTMLInputElement)) {
      return;
    }

    const memberId = String(checkbox.dataset.memberId || '').trim();
    if (memberId === '') {
      return;
    }

    state.membersBulkSelectAllActive = false;
    const selected = new Set(state.membersBulkSelectedIds);
    if (checkbox.checked) {
      selected.add(memberId);
    } else {
      selected.delete(memberId);
    }
    state.membersBulkSelectedIds = Array.from(selected);
    updateMembersBulkSelectionUi();
  };

  const selectAllBusinessMembers = () => {
    state.membersBulkSelectAllActive = true;
    state.membersBulkSelectedIds = [];
    syncMembersBulkCheckboxes();
  };

  const MEMBERS_APPLY_WORK_SITE_BATCH_SIZE = 25;

  const resolveMemberIdsForWorkSiteApply = async (orgId) => {
    if (state.membersBulkSelectAllActive) {
      const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(orgId)}/relationships`);
      const members = Array.isArray(payload?.members) ? payload.members : [];

      return members
        .map((member) => String(member?.user_uuid || member?.uuid || '').trim())
        .filter((memberId) => memberId !== '');
    }

    return state.membersBulkSelectedIds
      .map((memberId) => String(memberId || '').trim())
      .filter((memberId) => memberId !== '');
  };

  const setMembersReportStatus = (message = '') => {
    if (elements.membersReportStatus instanceof HTMLElement) {
      elements.membersReportStatus.textContent = String(message || '');
    }
  };

  const setMembersReportSummaryVisible = (isVisible) => {
    if (elements.membersReportSummary instanceof HTMLElement) {
      elements.membersReportSummary.hidden = !isVisible;
    }
  };

  const setMembersReportPanelOpen = (isOpen) => {
    if (!(elements.membersReportPanel instanceof HTMLElement)) {
      return;
    }

    elements.membersReportPanel.hidden = !isOpen;
    if (elements.membersReportToggle instanceof HTMLButtonElement) {
      elements.membersReportToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
    if (isOpen) {
      setMembersReportStatus('');
      if (memberReportLastBatch === null) {
        setMembersReportSummaryVisible(false);
      }
    }
  };

  const toggleMembersReportPanel = () => {
    if (!(elements.membersReportPanel instanceof HTMLElement)) {
      return;
    }

    setMembersReportPanelOpen(elements.membersReportPanel.hidden);
  };

  const sanitizeMemberReportFilenamePart = (value) => {
    const normalized = String(value || '')
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '');

    return normalized || 'member';
  };

  const selectedReportKey = () => (
    elements.membersReportType instanceof HTMLSelectElement
      ? String(elements.membersReportType.value || 'ytd').trim().toLowerCase()
      : 'ytd'
  );

  const selectedReportLabel = () => {
    if (!(elements.membersReportType instanceof HTMLSelectElement)) {
      return 'Yearly work summary';
    }

    const selected = elements.membersReportType.selectedOptions[0];
    return String(selected?.textContent || 'Yearly work summary').trim();
  };

  const selectedReportScope = () => {
    if (!(elements.membersReportType instanceof HTMLSelectElement)) {
      return 'yearly';
    }

    const selected = elements.membersReportType.selectedOptions[0];
    return String(selected?.dataset?.reportScope || 'yearly').trim() || 'yearly';
  };

  const selectedReportFormat = () => (
    elements.membersReportFormat instanceof HTMLSelectElement
      ? String(elements.membersReportFormat.value || 'csv').trim().toLowerCase()
      : 'csv'
  );

  const selectedReportFormatLabel = () => {
    if (!(elements.membersReportFormat instanceof HTMLSelectElement)) {
      return 'CSV';
    }

    const selected = elements.membersReportFormat.selectedOptions[0];
    return String(selected?.textContent || elements.membersReportFormat.value || 'CSV').trim();
  };

  const selectedReportDelivery = () => (
    elements.membersReportDelivery instanceof HTMLSelectElement
      ? String(elements.membersReportDelivery.value || 'zip').trim().toLowerCase()
      : 'zip'
  );

  const selectedReportYear = () => {
    const currentYear = new Date().getFullYear();
    if (!(elements.membersReportYear instanceof HTMLInputElement)) {
      return currentYear;
    }

    const parsed = Number.parseInt(String(elements.membersReportYear.value || ''), 10);
    if (!Number.isFinite(parsed)) {
      return currentYear;
    }

    const normalized = Math.max(2000, Math.min(2100, parsed));
    elements.membersReportYear.value = String(normalized);
    return normalized;
  };

  const resolveMembersForReportGeneration = async (orgId) => {
    if (state.membersBulkSelectAllActive) {
      const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(orgId)}/relationships`);
      const members = Array.isArray(payload?.members) ? payload.members : [];
      return members
        .map((member) => {
          const id = String(member?.user_uuid || member?.uuid || '').trim();
          if (id === '') {
            return null;
          }

          const name = String(member?.full_name || member?.email || id).trim();
          return { id, name };
        })
        .filter((member) => member !== null);
    }

    const selected = new Set(
      state.membersBulkSelectedIds
        .map((memberId) => String(memberId || '').trim())
        .filter((memberId) => memberId !== ''),
    );
    const members = [];

    if (elements.membersGridContainer instanceof HTMLElement) {
      elements.membersGridContainer.querySelectorAll('.businesses_member_row[data-member-id]').forEach((row) => {
        if (!(row instanceof HTMLElement)) {
          return;
        }

        const id = String(row.dataset.memberId || '').trim();
        if (id === '' || !selected.has(id)) {
          return;
        }

        const name = String(row.dataset.memberName || id).trim();
        members.push({ id, name });
      });
    }

    selected.forEach((memberId) => {
      if (!members.some((member) => member.id === memberId)) {
        members.push({ id: memberId, name: memberId });
      }
    });

    return members;
  };

  const fetchMemberDailyPayloadForReport = async (orgId, memberId, year) => (
    apiRequest(
      `/api/v1/businesses/${encodeURIComponent(orgId)}/members/${encodeURIComponent(memberId)}/reports/daily/year/${encodeURIComponent(String(year))}`,
      { timeoutMs: 60000 },
    )
  );

  const reportScopeFilenameLabel = (scope) => {
    if (scope === 'monthly') {
      return 'Monthly';
    }
    if (scope === 'daily') {
      return 'Daily';
    }
    return 'Yearly';
  };

  const buildSelectedMemberReport = (scope, year, member, rows) => {
    const params = {
      year,
      employee: member.id,
      fullName: member.name,
      referenceCode: '',
      rows,
    };

    if (scope === 'yearly') {
      return EarningsExport.buildYearlyReportJson(params);
    }
    if (scope === 'monthly') {
      return EarningsExport.buildMonthlyReportJson(params);
    }
    if (scope === 'daily') {
      return EarningsExport.buildDailyReportJson(params);
    }

    throw new Error('Unsupported report type.');
  };

  const downloadBlob = (blob, filename) => {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  };

  const postProtectedMemberReportBlob = async (orgId, memberId, scope, format, year) => {
    const response = await fetch(
      `/api/v1/businesses/${encodeURIComponent(orgId)}/members/${encodeURIComponent(memberId)}/reports/export/${encodeURIComponent(format)}`,
      {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ scope, year }),
      },
    );

    if (!response.ok) {
      const text = await response.text();
      throw new Error(`Report export failed (${response.status}): ${text}`);
    }

    return response.blob();
  };

  const buildProtectedMemberReportFile = async (orgId, scope, format, year, member) => {
    const filename = `PayCal-${year}-${reportScopeFilenameLabel(scope)}-Report-${sanitizeMemberReportFilenamePart(member.name)}.${format}`;
    return {
      filename,
      blob: await postProtectedMemberReportBlob(orgId, member.id, scope, format, year),
    };
  };

  const buildSelectedMemberReportFile = async (scope, format, year, member, rows, report) => {
    const filename = `PayCal-${year}-${reportScopeFilenameLabel(scope)}-Report-${sanitizeMemberReportFilenamePart(member.name)}.${format}`;
    if (format === 'csv') {
      const csv = scope === 'yearly'
        ? EarningsExport.generateYearlyCsv(rows, report)
        : scope === 'monthly'
          ? EarningsExport.generateMonthlyCsv(rows, report)
          : EarningsExport.generateDailyCsv(rows, report);
      return { filename, blob: new Blob([csv], { type: 'text/csv;charset=utf-8' }) };
    }

    if (format === 'txt') {
      const txt = scope === 'yearly'
        ? EarningsExport.generateYearlyTxt(rows, report)
        : scope === 'monthly'
          ? EarningsExport.generateMonthlyTxt(rows, report)
          : EarningsExport.generateDailyTxt(rows, report);
      return { filename, blob: new Blob([txt], { type: 'text/plain;charset=utf-8' }) };
    }

    throw new Error('Unsupported report format.');
  };

  const crc32 = (bytes) => {
    let table = crc32.table;
    if (!Array.isArray(table)) {
      table = [];
      for (let index = 0; index < 256; index += 1) {
        let value = index;
        for (let bit = 0; bit < 8; bit += 1) {
          value = (value & 1) ? (0xEDB88320 ^ (value >>> 1)) : (value >>> 1);
        }
        table[index] = value >>> 0;
      }
      crc32.table = table;
    }

    let crc = 0xFFFFFFFF;
    for (let index = 0; index < bytes.length; index += 1) {
      crc = (crc >>> 8) ^ table[(crc ^ bytes[index]) & 0xFF];
    }

    return (crc ^ 0xFFFFFFFF) >>> 0;
  };

  const uint16 = (value) => {
    const bytes = new Uint8Array(2);
    bytes[0] = value & 0xFF;
    bytes[1] = (value >>> 8) & 0xFF;
    return bytes;
  };

  const uint32 = (value) => {
    const bytes = new Uint8Array(4);
    bytes[0] = value & 0xFF;
    bytes[1] = (value >>> 8) & 0xFF;
    bytes[2] = (value >>> 16) & 0xFF;
    bytes[3] = (value >>> 24) & 0xFF;
    return bytes;
  };

  const concatUint8 = (parts) => {
    const total = parts.reduce((sum, part) => sum + part.length, 0);
    const out = new Uint8Array(total);
    let offset = 0;
    parts.forEach((part) => {
      out.set(part, offset);
      offset += part.length;
    });
    return out;
  };

  const zipDosTime = (date) => (
    (date.getHours() << 11)
    | (date.getMinutes() << 5)
    | Math.floor(date.getSeconds() / 2)
  );

  const zipDosDate = (date) => (
    ((date.getFullYear() - 1980) << 9)
    | ((date.getMonth() + 1) << 5)
    | date.getDate()
  );

  const createZipBlob = async (files) => {
    const localParts = [];
    const centralParts = [];
    let offset = 0;
    const now = new Date();
    const time = zipDosTime(now);
    const date = zipDosDate(now);

    for (const file of files) {
      const nameBytes = MEMBER_REPORT_TEXT_ENCODER.encode(file.filename);
      const content = new Uint8Array(await file.blob.arrayBuffer());
      const checksum = crc32(content);
      const localHeader = concatUint8([
        uint32(0x04034b50), uint16(20), uint16(0x0800), uint16(0),
        uint16(time), uint16(date), uint32(checksum), uint32(content.length),
        uint32(content.length), uint16(nameBytes.length), uint16(0), nameBytes,
      ]);
      localParts.push(localHeader, content);
      centralParts.push(concatUint8([
        uint32(0x02014b50), uint16(20), uint16(20), uint16(0x0800), uint16(0),
        uint16(time), uint16(date), uint32(checksum), uint32(content.length),
        uint32(content.length), uint16(nameBytes.length), uint16(0), uint16(0),
        uint16(0), uint16(0), uint32(0), uint32(offset), nameBytes,
      ]));
      offset += localHeader.length + content.length;
    }

    const centralDirectory = concatUint8(centralParts);
    const end = concatUint8([
      uint32(0x06054b50), uint16(0), uint16(0), uint16(files.length), uint16(files.length),
      uint32(centralDirectory.length), uint32(offset), uint16(0),
    ]);

    return new Blob([concatUint8([...localParts, centralDirectory, end])], { type: 'application/zip' });
  };

  const buildReportManifest = (batch) => {
    const generatedAt = batch.generatedAt;
    return batch.results.map((result) => ({
      generated_at: generatedAt,
      actor_id: batch.actorId,
      business_id: batch.orgId,
      member_id: result.member.id,
      member_name: result.member.name,
      report: batch.reportLabel,
      scope: batch.scope,
      year: batch.year,
      format: batch.format,
      delivery: batch.delivery,
      generation_path: batch.generationPath,
      trust_level: batch.trustLevel,
      trust_note: batch.trustNote,
      status: result.status,
      filename: result.filename || '',
      error: result.error || '',
    }));
  };

  const buildReportManifestJson = (batch) => (
    JSON.stringify({
      generated_at: batch.generatedAt,
      actor_id: batch.actorId,
      business_id: batch.orgId,
      report: batch.reportLabel,
      scope: batch.scope,
      year: batch.year,
      format: batch.format,
      delivery: batch.delivery,
      generation_path: batch.generationPath,
      trust_level: batch.trustLevel,
      trust_note: batch.trustNote,
      total: batch.total,
      succeeded: batch.generated,
      failed: batch.failed,
      duration_ms: batch.durationMs,
      files: buildReportManifest(batch),
    }, null, 2)
  );

  const csvEscape = (value) => {
    const text = String(value ?? '');
    return /[",\n\r]/.test(text) ? `"${text.replace(/"/g, '""')}"` : text;
  };

  const buildReportManifestCsv = (batch) => {
    const columns = ['generated_at', 'actor_id', 'business_id', 'member_id', 'member_name', 'report', 'scope', 'year', 'format', 'delivery', 'generation_path', 'trust_level', 'trust_note', 'status', 'filename', 'error'];
    const rows = buildReportManifest(batch);
    return [
      columns.join(','),
      ...rows.map((row) => columns.map((column) => csvEscape(row[column])).join(',')),
    ].join('\n');
  };

  const buildManifestFiles = (batch) => ([
    {
      filename: 'manifest.json',
      blob: new Blob([buildReportManifestJson(batch)], { type: 'application/json;charset=utf-8' }),
    },
    {
      filename: 'manifest.csv',
      blob: new Blob([buildReportManifestCsv(batch)], { type: 'text/csv;charset=utf-8' }),
    },
  ]);

  const batchZipFilename = (batch) => (
    `PayCal-${batch.year}-${reportScopeFilenameLabel(batch.scope)}-Reports-${batch.format.toUpperCase()}.zip`
  );

  const downloadBatchZip = async (batch) => {
    const files = [
      ...batch.files,
      ...buildManifestFiles(batch),
    ];
    downloadBlob(await createZipBlob(files), batchZipFilename(batch));
  };

  const downloadBatchManifest = (batch) => {
    downloadBlob(
      new Blob([buildReportManifestCsv(batch)], { type: 'text/csv;charset=utf-8' }),
      `PayCal-${batch.year}-${reportScopeFilenameLabel(batch.scope)}-Manifest.csv`,
    );
  };

  const updateMembersReportSummary = (batch) => {
    const setText = (element, value) => {
      if (element instanceof HTMLElement) {
        element.textContent = String(value);
      }
    };

    setText(elements.membersReportSummaryMembers, String(batch.total));
    setText(elements.membersReportSummaryReport, batch.reportLabel);
    setText(elements.membersReportSummaryYear, String(batch.year));
    setText(elements.membersReportSummaryFormat, batch.formatLabel);
    setText(elements.membersReportSummaryCompleted, `${batch.generated} / ${batch.total}`);
    setText(elements.membersReportSummaryTime, `${(batch.durationMs / 1000).toFixed(1)} ${T.businessExportSeconds}`);
    if (elements.membersReportDownloadZip instanceof HTMLButtonElement) {
      elements.membersReportDownloadZip.disabled = batch.files.length === 0;
    }
    if (elements.membersReportDownloadManifest instanceof HTMLButtonElement) {
      elements.membersReportDownloadManifest.disabled = batch.results.length === 0;
    }
    setMembersReportSummaryVisible(true);
  };

  const recordMembersReportAudit = async (batch, eventPhase = 'completed', reason = '') => {
    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(batch.orgId)}/members/reports/audit`, {
        report_key: batch.reportKey,
        report_scope: batch.scope,
        year: batch.year,
        format: batch.format,
        delivery: batch.delivery,
        member_count: batch.total,
        succeeded: batch.generated,
        failed: batch.failed,
        duration_ms: batch.durationMs,
        generated_at: batch.generatedAt,
        event_phase: eventPhase,
        result: eventPhase,
        reason,
        generation_path: batch.generationPath,
        trust_level: batch.trustLevel,
        member_uuids: batch.results.map((result) => result.member.id),
      });
    } catch (error) {
      PW.error(error);
    }
  };

  const generateReportsForSelectedMembers = async () => {
    if (memberReportBatchRunning) {
      return;
    }

    const now = Date.now();
    if (now - memberReportLastStartedAt < MEMBER_REPORT_REPEAT_COOLDOWN_MS) {
      PC.showToast(T.memberReportsCooldown, 'error', 5000, true);
      return;
    }

    const orgId = String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
    if (orgId === '') {
      return;
    }

    const members = await resolveMembersForReportGeneration(orgId);
    if (members.length === 0) {
      PC.showToast(T.membersApplyWorkSiteNoSelection, 'error', 6000, true);
      return;
    }

    const scope = selectedReportScope();
    const reportKey = selectedReportKey();
    const reportLabel = selectedReportLabel();
    const format = selectedReportFormat();
    const formatLabel = selectedReportFormatLabel();
    const delivery = selectedReportDelivery();
    const year = selectedReportYear();
    const generationPath = (format === 'xlsx' || format === 'pdf')
      ? 'server_authorized'
      : 'browser_convenience_from_authorized_report_data';
    const trustLevel = (format === 'xlsx' || format === 'pdf')
      ? 'server_authorized_artifact'
      : 'convenience_browser_export';
    const trustNote = (format === 'xlsx' || format === 'pdf')
      ? 'Server-authorized artifact rebuilt from protected business access checks.'
      : 'CSV/TXT are browser convenience exports from authorized report data.';
    if (!['yearly', 'monthly', 'daily'].includes(scope) || !['csv', 'txt', 'xlsx', 'pdf'].includes(format) || !['zip', 'files'].includes(delivery)) {
      throw new Error(T.memberReportsUnsupportedOption);
    }
    if (members.length > MEMBER_REPORT_CONFIRM_THRESHOLD) {
      const confirmed = window.confirm(
        T.memberReportsConfirm
          .replace('%d', String(members.length))
          .replace('%s', format.toUpperCase()),
      );
      if (!confirmed) {
        return;
      }
    }

    const originalText = elements.membersGenerateReportsButton instanceof HTMLButtonElement
      ? elements.membersGenerateReportsButton.textContent
      : '';

    if (elements.membersGenerateReportsButton instanceof HTMLButtonElement) {
      elements.membersGenerateReportsButton.disabled = true;
      elements.membersGenerateReportsButton.textContent = T.businessExportGenerating;
    }

    memberReportBatchRunning = true;
    memberReportLastStartedAt = now;
    memberReportLastBatch = null;
    setMembersReportSummaryVisible(false);

    const startedAt = performance.now();
    const batch = {
      orgId,
      actorId: typeof currentUserUUID === 'string' ? currentUserUUID : '',
      reportKey,
      reportLabel,
      scope,
      format,
      formatLabel,
      delivery,
      generationPath,
      trustLevel,
      trustNote,
      year,
      total: members.length,
      generated: 0,
      failed: 0,
      durationMs: 0,
      generatedAt: new Date().toISOString(),
      files: [],
      results: [],
    };

    try {
      await recordMembersReportAudit({
        ...batch,
        results: members.map((member) => ({ member })),
      }, 'requested');
      await recordMembersReportAudit({
        ...batch,
        results: members.map((member) => ({ member })),
      }, 'started');

      for (let index = 0; index < members.length; index += 1) {
        const member = members[index];
        setMembersReportStatus(
          T.memberReportsProgress
            .replace('%d', String(index + 1))
            .replace('%d', String(members.length))
            .replace('%s', member.name),
        );
        try {
          if (format === 'xlsx' || format === 'pdf') {
            const file = await buildProtectedMemberReportFile(orgId, scope, format, year, member);
            batch.files.push(file);
            batch.results.push({ member, status: 'succeeded', filename: file.filename });
            batch.generated += 1;
            if (delivery === 'files') {
              downloadBlob(file.blob, file.filename);
            }
            continue;
          }

          const dailyPayload = await fetchMemberDailyPayloadForReport(orgId, member.id, year);
          const rows = EarningsExport.buildDetailedRows(dailyPayload);
          if (!rows.length) {
            batch.failed += 1;
            batch.results.push({ member, status: 'failed', error: T.memberReportsNoRows });
            continue;
          }

          const report = buildSelectedMemberReport(scope, year, member, rows);
          const file = await buildSelectedMemberReportFile(scope, format, year, member, rows, report);
          batch.files.push(file);
          batch.results.push({ member, status: 'succeeded', filename: file.filename });
          batch.generated += 1;
          if (delivery === 'files') {
            downloadBlob(file.blob, file.filename);
          }
        } catch (error) {
          batch.failed += 1;
          batch.results.push({
            member,
            status: 'failed',
            error: error instanceof Error && error.message ? error.message : T.memberReportsGenerationFailed,
          });
          PW.error(error);
        }
      }

      batch.durationMs = Math.round(performance.now() - startedAt);
      memberReportLastBatch = batch;
      if (delivery === 'zip' && batch.files.length > 0) {
        await downloadBatchZip(batch);
      }

      updateMembersReportSummary(batch);
      await recordMembersReportAudit(batch, batch.failed > 0 ? 'failed' : 'completed', batch.failed > 0 ? 'one_or_more_member_reports_failed' : '');

      const message = batch.failed > 0
        ? T.memberReportsGeneratedFailed
          .replace('%d', String(batch.generated))
          .replace('%s', batch.generated === 1 ? '' : 's')
          .replace('%d', String(batch.failed))
        : T.memberReportsGeneratedSuccess
          .replace('%d', String(batch.generated))
          .replace('%s', batch.generated === 1 ? '' : 's');
      setMembersReportStatus(message);
      PC.showToast(message, batch.failed > 0 ? 'error' : 'save', 7000, true);
    } catch (error) {
      batch.durationMs = Math.round(performance.now() - startedAt);
      await recordMembersReportAudit(batch, 'failed', error instanceof Error && error.message ? error.message : T.memberReportsGenerationFailed);
      throw error;
    } finally {
      memberReportBatchRunning = false;
      if (elements.membersGenerateReportsButton instanceof HTMLButtonElement) {
        elements.membersGenerateReportsButton.disabled = getMembersBulkSelectedCount() === 0;
        elements.membersGenerateReportsButton.textContent = originalText || T.memberReportsGenerateSelected;
      }
    }
  };

  const applyWorkSiteToSelectedMembers = async () => {
    const orgId = String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
    if (orgId === '') {
      return;
    }

    if (!(elements.membersApplySiteSelect instanceof HTMLSelectElement)) {
      return;
    }

    const selectedCount = getMembersBulkSelectedCount();
    if (selectedCount === 0) {
      PC.showToast(T.membersApplyWorkSiteNoSelection, 'error', 6000, true);
      return;
    }

    const siteValue = String(elements.membersApplySiteSelect.value || '').trim();
    if (siteValue === '') {
      PC.showToast(T.membersApplyWorkSiteNoSite, 'error', 6000, true);
      return;
    }

    const [siteOwnerUuid, siteId] = siteValue.split(':');
    if (!siteOwnerUuid || !siteId) {
      PC.showToast(T.membersApplyWorkSiteNoSite, 'error', 6000, true);
      return;
    }

    const confirmed = window.confirm(
      T.membersApplyWorkSiteConfirm.replace('%d', String(selectedCount)),
    );
    if (!confirmed) {
      return;
    }

    if (elements.membersApplyWorkSiteButton instanceof HTMLButtonElement) {
      elements.membersApplyWorkSiteButton.disabled = true;
    }

    try {
      const memberIds = await resolveMemberIdsForWorkSiteApply(orgId);
      if (memberIds.length === 0) {
        PC.showToast(T.membersApplyWorkSiteNoSelection, 'error', 6000, true);
        return;
      }

      const requestBase = {
        site_owner_uuid: siteOwnerUuid,
        site_id: siteId,
        apply_scope: 'unlinked',
      };

      let membersUpdated = 0;
      let entriesMigrated = 0;

      for (let offset = 0; offset < memberIds.length; offset += MEMBERS_APPLY_WORK_SITE_BATCH_SIZE) {
        const batch = memberIds.slice(offset, offset + MEMBERS_APPLY_WORK_SITE_BATCH_SIZE);
        const processed = Math.min(offset + batch.length, memberIds.length);
        if (elements.membersBulkStatus instanceof HTMLElement) {
          elements.membersBulkStatus.textContent = T.membersApplyWorkSiteProgress
            .replace('%d', String(processed))
            .replace('%d', String(memberIds.length));
        }

        const payload = await postForm(
          `/api/v1/businesses/${encodeURIComponent(orgId)}/members/apply-work-site`,
          {
            ...requestBase,
            member_uuids: batch,
          },
        );

        membersUpdated += Number(payload?.members_updated || 0);
        entriesMigrated += Number(payload?.entries_migrated || 0);
      }

      const successMessage = T.membersApplyWorkSiteSuccess
        .replace('%d', String(membersUpdated))
        .replace('%d', String(entriesMigrated));
      PC.showToast(successMessage, 'save', 7000, true);
      if (elements.membersBulkStatus instanceof HTMLElement) {
        elements.membersBulkStatus.textContent = successMessage;
      }

      clearMembersBulkSelection();
      await loadBusinessMembersGrid(orgId);
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message
        ? error.message
        : T.membersApplyWorkSiteFailed;
      PC.showToast(message, 'error', 7000, true);
      if (elements.membersBulkStatus instanceof HTMLElement) {
        elements.membersBulkStatus.textContent = message;
      }
    } finally {
      updateMembersBulkSelectionUi();
    }
  };

  const bindMembersBulkToolbar = () => {
    syncMembersGridElementRefs();
    const toolbar = elements.membersBulkToolbar;
    if (!(toolbar instanceof HTMLElement) || toolbar.dataset.membersBulkBound === '1') {
      return;
    }
    toolbar.dataset.membersBulkBound = '1';

    if (elements.membersSelectAllButton instanceof HTMLButtonElement) {
      elements.membersSelectAllButton.addEventListener('click', () => {
        selectAllBusinessMembers();
      });
    }

    if (elements.membersClearSelectionButton instanceof HTMLButtonElement) {
      elements.membersClearSelectionButton.addEventListener('click', () => {
        clearMembersBulkSelection();
      });
    }

    if (elements.membersApplyWorkSiteButton instanceof HTMLButtonElement) {
      elements.membersApplyWorkSiteButton.addEventListener('click', () => {
        applyWorkSiteToSelectedMembers().catch((error) => PW.error(error));
      });
    }

    if (elements.membersReportToggle instanceof HTMLButtonElement) {
      elements.membersReportToggle.addEventListener('click', () => {
        toggleMembersReportPanel();
      });
    }

    if (elements.membersGenerateReportsButton instanceof HTMLButtonElement) {
      elements.membersGenerateReportsButton.addEventListener('click', () => {
        generateReportsForSelectedMembers().catch((error) => {
          PW.error(error);
          const message = error instanceof Error && error.message
            ? error.message
            : 'Unable to generate selected member reports.';
          setMembersReportStatus(message);
          PC.showToast(message, 'error', 7000, true);
          updateMembersBulkSelectionUi();
        });
      });
    }

    if (elements.membersReportPanel instanceof HTMLElement) {
      elements.membersReportPanel.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          event.preventDefault();
          setMembersReportPanelOpen(false);
          if (elements.membersReportToggle instanceof HTMLButtonElement) {
            elements.membersReportToggle.focus();
          }
        }
      });
    }

    if (elements.membersReportDownloadZip instanceof HTMLButtonElement) {
      elements.membersReportDownloadZip.addEventListener('click', () => {
        if (memberReportLastBatch !== null) {
          downloadBatchZip(memberReportLastBatch).catch((error) => {
            PW.error(error);
            PC.showToast('Unable to download report ZIP.', 'error', 7000, true);
          });
        }
      });
    }

    if (elements.membersReportDownloadManifest instanceof HTMLButtonElement) {
      elements.membersReportDownloadManifest.addEventListener('click', () => {
        if (memberReportLastBatch !== null) {
          downloadBatchManifest(memberReportLastBatch);
        }
      });
    }

    if (elements.membersReportGenerateAnother instanceof HTMLButtonElement) {
      elements.membersReportGenerateAnother.addEventListener('click', () => {
        memberReportLastBatch = null;
        setMembersReportStatus('');
        setMembersReportSummaryVisible(false);
        if (elements.membersReportType instanceof HTMLSelectElement) {
          elements.membersReportType.focus();
        }
      });
    }

    if (elements.membersApplySiteSelect instanceof HTMLSelectElement) {
      elements.membersApplySiteSelect.addEventListener('change', () => {
        updateMembersBulkSelectionUi();
      });
    }
  };

  document.addEventListener('click', (event) => {
    if (!isBusinessMembersSubPage()) {
      return;
    }

    const reportControl = document.getElementById('business_members_report_control');
    const target = event.target instanceof Node ? event.target : null;
    if (!(reportControl instanceof HTMLElement) || target === null || reportControl.contains(target)) {
      return;
    }

    setMembersReportPanelOpen(false);
  });

  const membersGridEndpoint = (orgId) => (
    `/api/v1/businesses/${encodeURIComponent(orgId)}/members/grid`
  );

  const hasRenderedMembersGrid = () => {
    const container = elements.membersGridContainer;
    if (!(container instanceof HTMLElement)) {
      return false;
    }

    return container.querySelector('[data-grid="business-members"] .datagrid_search') instanceof HTMLInputElement;
  };

  const canHydrateBusinessMembersGridFromSsr = () => {
    const container = elements.membersGridContainer;
    if (!(container instanceof HTMLElement)) {
      return false;
    }

    if (String(container.dataset.ssrMembersGrid || '').trim() !== '1') {
      return false;
    }

    return container.querySelector('[data-grid="business-members"]') instanceof HTMLElement;
  };

  const announceBusinessMembersGridFromDom = () => {
    const container = elements.membersGridContainer;
    if (!(container instanceof HTMLElement)) {
      return;
    }

    const rowCount = container.querySelectorAll(
      '[data-grid="business-members"] .datagrid_row:not(.datagrid_row_empty)',
    ).length;
    announceMembersGridStatus(
      T.membersGridReady
        .replace('%d', String(rowCount))
        .replace('%s', rowCount === 1 ? '' : 's'),
    );
  };

  const ensureBusinessMembersGridManager = (orgId) => {
    if (state.membersGridManager && state.membersGridOrgId === orgId) {
      return state.membersGridManager;
    }

    if (state.membersGridManager && typeof state.membersGridManager.destroy === 'function') {
      state.membersGridManager.destroy();
    }

    state.membersGridManager = createDataGrid({
      id: 'businesses-members-grid',
      endpoint: membersGridEndpoint(orgId),
    });
    state.membersGridOrgId = orgId;
    state.membersGridRoleFilter = '';

    return state.membersGridManager;
  };

  const announceMembersGridStatus = (message) => {
    if (elements.membersGridStatus instanceof HTMLElement) {
      elements.membersGridStatus.textContent = String(message || '');
    }
  };

  const isMembersGridInteractiveTarget = (target) => {
    if (!(target instanceof Element)) {
      return false;
    }

    return !!target.closest(
      'a, button, input, select, textarea, label, .datagrid_action, .businesses_member_role_trigger, .business_members_row_checkbox, .business_members_row_select, .business_member_row_menu, .business_member_row_menu_toggle, .business_member_row_menu_item, .datagrid_sort, .datagrid_search, .datagrid_pager, .datagrid_pagination, .datagrid_column_toggle, .datagrid_column_toggle_input, .datagrid_column_menu, .datagrid_column_menu_toggle, .datagrid_column_menu_panel, .business_members_bulk_toolbar, .business_members_toolbar_bulk',
    );
  };

  const resolveMemberRowContext = (row) => {
    if (!(row instanceof HTMLElement)) {
      return {
        memberUuid: '',
        memberName: '',
        memberRole: '',
      };
    }

    const memberUuid = String(row.dataset.memberId || row.dataset.id || '').trim();
    const memberName = String(row.dataset.memberName || '').trim() || resolveMemberDisplayNameFromRow(row);
    const memberRole = String(row.dataset.memberRole || '').trim().toLowerCase();

    return {
      memberUuid,
      memberName,
      memberRole,
    };
  };

  const activateMemberReportsFromRow = (row, trigger = null) => {
    const { memberUuid, memberName, memberRole } = resolveMemberRowContext(row);
    if (memberUuid === '') {
      return;
    }

    openMemberReportsDialog(memberUuid, memberName, memberRole, trigger).catch((error) => {
      PW.error(error);
    });
  };

  const bindBusinessMembersGridInteractions = () => {
    const perf = resolveBusinessMembersLensPerf();
    const bind = () => {
      syncMembersGridElementRefs();
      const container = elements.membersGridContainer;
      if (!(container instanceof HTMLElement)) {
        return;
      }
      if (container.dataset.membersGridInteractionsBound === '1') {
        return;
      }
      container.dataset.membersGridInteractionsBound = '1';

      bindMemberRolePopoverDismissals();
      bindMembersBulkToolbar();

      container.addEventListener('change', (event) => {
        const checkbox = event.target instanceof Element
          ? event.target.closest('.business_members_row_checkbox')
          : null;
        if (checkbox instanceof HTMLInputElement && container.contains(checkbox)) {
          handleMembersBulkCheckboxChange(checkbox);
        }
      });

      container.addEventListener('click', (event) => {
      const rowMenuToggle = event.target.closest('.business_member_row_menu_toggle');
      if (rowMenuToggle instanceof HTMLElement && container.contains(rowMenuToggle)) {
        event.preventDefault();
        event.stopPropagation();
        const menu = rowMenuToggle.closest('.business_member_row_menu');
        const isOpen = menu instanceof HTMLElement && menu.classList.contains('is_open');
        closeAllMemberRowMenus();
        if (menu instanceof HTMLElement && !isOpen) {
          setMemberRowMenuOpen(menu, true);
        }
        return;
      }

      const rowMenuItem = event.target.closest('.business_member_row_menu_item');
      if (rowMenuItem instanceof HTMLElement && container.contains(rowMenuItem)) {
        event.preventDefault();
        event.stopPropagation();
        const row = rowMenuItem.closest('.datagrid_row');
        const menu = rowMenuItem.closest('.business_member_row_menu');
        if (!rowMenuItem.disabled) {
          handleMemberRowMenuAction(rowMenuItem, row);
        }
        if (menu instanceof HTMLElement) {
          setMemberRowMenuOpen(menu, false);
        }
        closeAllMemberRowMenus();
        return;
      }

      if (!(event.target instanceof Element) || !event.target.closest('.business_member_row_menu_panel')) {
        closeAllMemberRowMenus();
      }

      const roleTrigger = event.target.closest('.businesses_member_role_trigger');
      if (roleTrigger instanceof HTMLElement && container.contains(roleTrigger)) {
        event.preventDefault();
        const memberUuid = String(roleTrigger.dataset.memberId || '').trim();
        if (memberUuid !== '') {
          toggleMemberRolePopover(roleTrigger, memberUuid);
        }
        return;
      }

      const actionBtn = event.target.closest('.datagrid_action');
      if (actionBtn instanceof HTMLElement && container.contains(actionBtn)) {
        event.preventDefault();
        const action = String(actionBtn.dataset.action || '');
        const memberUuid = String(actionBtn.dataset.id || actionBtn.dataset.memberId || '').trim();
        if (memberUuid !== '' && action === 'revoke') {
          showConfirmRevokeDialog(memberUuid, actionBtn).catch((error) => PW.error(error));
        }
        return;
      }

      if (isMembersGridInteractiveTarget(event.target)) {
        return;
      }

      const row = event.target.closest('.businesses_member_row_clickable, .datagrid_row');
      if (!(row instanceof HTMLElement) || !container.contains(row)) {
        return;
      }

      event.preventDefault();
      activateMemberReportsFromRow(row, row);
    });

    container.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ') {
        return;
      }

      const roleTrigger = event.target.closest('.businesses_member_role_trigger');
      if (roleTrigger instanceof HTMLElement && container.contains(roleTrigger)) {
        event.preventDefault();
        const memberUuid = String(roleTrigger.dataset.memberId || '').trim();
        if (memberUuid !== '') {
          toggleMemberRolePopover(roleTrigger, memberUuid);
        }
        return;
      }

      if (isMembersGridInteractiveTarget(event.target)) {
        return;
      }

      const row = event.target.closest('.businesses_member_row_clickable, .datagrid_row');
      if (!(row instanceof HTMLElement) || !container.contains(row)) {
        return;
      }

      event.preventDefault();
      activateMemberReportsFromRow(row, row);
    });
    };

    if (perf?.isEnabled()) {
      perf.measureSync('bindBusinessMembersGridInteractions (role popover init)', bind);
      return;
    }

    bind();
  };

  const isMembersSearchShortcutBlockedTarget = (target) => {
    if (!(target instanceof Element)) {
      return true;
    }

    if (target.closest('[contenteditable="true"]')) {
      return true;
    }

    const tagName = target.tagName;
    return tagName === 'INPUT' || tagName === 'TEXTAREA' || tagName === 'SELECT';
  };

  const isMembersSearchShortcutBlocked = () => (
    document.querySelector('dialog[open]') instanceof HTMLDialogElement
  );

  let membersSearchInitialFocusDone = false;

  const canFocusMembersGridSearchOnInitialLoad = () => {
    const active = document.activeElement;
    if (active instanceof HTMLElement && active !== document.body && active !== document.documentElement) {
      return false;
    }

    return true;
  };

  const focusMembersGridSearchOnInitialLoad = () => {
    if (membersSearchInitialFocusDone) {
      return;
    }

    membersSearchInitialFocusDone = true;

    window.requestAnimationFrame(() => {
      if (!canFocusMembersGridSearchOnInitialLoad()) {
        return;
      }

      syncMembersGridElementRefs();
      const container = elements.membersGridContainer;
      if (!(container instanceof HTMLElement)) {
        return;
      }

      const searchInput = container.querySelector('[data-grid="business-members"] .datagrid_search');
      if (!(searchInput instanceof HTMLInputElement)) {
        return;
      }

      searchInput.focus({ preventScroll: true });
    });
  };

  const handleMembersGridSearchShortcut = (event) => {
    if (!isBusinessMembersSubPage()) {
      return;
    }

    if (event.key !== '/' || event.ctrlKey || event.metaKey || event.altKey || event.shiftKey || event.repeat) {
      return;
    }

    if (isMembersSearchShortcutBlocked()) {
      return;
    }

    syncMembersGridElementRefs();
    const container = elements.membersGridContainer;
    if (!(container instanceof HTMLElement)) {
      return;
    }

    const searchInput = container.querySelector('.datagrid_search');
    if (!(searchInput instanceof HTMLInputElement)) {
      return;
    }

    const target = event.target instanceof Element ? event.target : null;
    if (target === searchInput) {
      event.preventDefault();
      return;
    }

    if (isMembersSearchShortcutBlockedTarget(target)) {
      return;
    }

    event.preventDefault();
    searchInput.focus({ preventScroll: true });
  };

  if (isBusinessMembersSubPage()) {
    bindBusinessMembersGridInteractions();
    bindMembersBulkToolbar();
    bindMembersPendingAccordion();
    integrateMembersToolbarLayout();
    refreshMembersBulkToolbar(resolveWorkspaceBusinessId()).catch((error) => PW.error(error));
    loadBusinessMembersPendingList(resolveWorkspaceBusinessId()).catch((error) => PW.error(error));

    syncMembersGridElementRefs();
    const bootstrapOrgId = String(resolveWorkspaceBusinessId() || '').trim();
    if (bootstrapOrgId !== '' && hasRenderedMembersGrid()) {
      ensureBusinessMembersGridManager(bootstrapOrgId);
    }

    document.addEventListener('keydown', handleMembersGridSearchShortcut, true);

    document.addEventListener('paycal:datagrid-before-reload', (event) => {
      if (String(event?.detail?.gridId || '') !== 'businesses-members-grid') {
        return;
      }
      evacuateMembersBulkToolbar();
    });

    document.addEventListener('paycal:datagrid-reloaded', (event) => {
      if (String(event?.detail?.gridId || '') !== 'businesses-members-grid') {
        return;
      }
      syncMemberRoleTriggerLabels();
      integrateMembersToolbarLayout();
      syncMembersBulkCheckboxes();
    });
  }

  const loadBusinessMembersGrid = async (businessId = '') => {
    if (resolveBusinessSubPage() !== 'members') {
      return;
    }

    const perf = resolveBusinessMembersLensPerf();
    const loadGrid = async () => {
      syncMembersGridElementRefs();

      const orgId = String(businessId || resolveWorkspaceBusinessId() || '').trim();
      if (orgId === '') {
        if (elements.membersGridContainer instanceof HTMLElement) {
          setDatagridMessage(elements.membersGridContainer, T.selectFirst);
        }
        announceMembersGridStatus(T.noBusinessSelected);
        return;
      }

      if (!(elements.membersGridContainer instanceof HTMLElement)) {
        return;
      }

      const business = findBusiness(orgId);
      if (business && !canUsePremiumOrgFeatures(business)) {
        setDatagridMessage(elements.membersGridContainer, T.premiumAdminLockedDetailed);
        announceMembersGridStatus(T.premiumAdminLockedDetailed);
        return;
      }

      if (business) {
        state.selectedBusinessId = orgId;
      }

      closeMemberRolePopover({ restoreFocus: false });

      try {
        const ensureManager = () => {
          const manager = ensureBusinessMembersGridManager(orgId);
          if (!manager) {
            throw new Error(T.membersGridInitFailed);
          }
          return manager;
        };

        const manager = perf?.isEnabled()
          ? perf.measureSync('ensureBusinessMembersGridManager', ensureManager)
          : ensureManager();

        const hydrateFromSsr = canHydrateBusinessMembersGridFromSsr();
        const keepRenderedGrid = hydrateFromSsr || hasRenderedMembersGrid();
        if (!keepRenderedGrid) {
          setDatagridMessage(elements.membersGridContainer, T.loading, true);
          announceMembersGridStatus(T.membersLoading);
        }

        if (hydrateFromSsr) {
          delete elements.membersGridContainer.dataset.ssrMembersGrid;
          if (perf?.isEnabled()) {
            perf.measureSync('syncMemberRoleTriggerLabels', () => syncMemberRoleTriggerLabels());
            perf.measureSync('integrateMembersToolbarLayout', () => integrateMembersToolbarLayout());
          } else {
            syncMemberRoleTriggerLabels();
            integrateMembersToolbarLayout();
          }
          await Promise.allSettled([
            refreshMembersBulkToolbar(orgId),
            loadBusinessMembersPendingList(orgId),
          ]);
          syncMembersBulkCheckboxes();
          announceBusinessMembersGridFromDom();
          focusMembersGridSearchOnInitialLoad();
          return;
        }

        if (perf?.isEnabled()) {
          await perf.measure('loadBusinessMembersGrid (API) / createDataGrid.reload', () => manager.reload());
        } else {
          await manager.reload();
        }

        if (perf?.isEnabled()) {
          perf.measureSync('syncMemberRoleTriggerLabels', () => syncMemberRoleTriggerLabels());
          perf.measureSync('integrateMembersToolbarLayout', () => integrateMembersToolbarLayout());
        } else {
          syncMemberRoleTriggerLabels();
          integrateMembersToolbarLayout();
        }

        await Promise.allSettled([
          refreshMembersBulkToolbar(orgId),
          loadBusinessMembersPendingList(orgId),
        ]);
        syncMembersBulkCheckboxes();
        focusMembersGridSearchOnInitialLoad();
      } catch (error) {
        PW.error(error);
        const message = error instanceof Error && error.message
          ? error.message
          : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_FAILED_LOAD_MEMBERS_GRID')); ?>';
        setDatagridMessage(elements.membersGridContainer, message);
        announceMembersGridStatus(message);
        PC.showToast(message, 'error', 7000, true);
      }
    };

    try {
      if (perf?.isEnabled()) {
        await perf.measure('loadBusinessMembersGrid', loadGrid, { ranked: false });
        return;
      }

      await loadGrid();
    } finally {
      finalizeBusinessMembersLensPerfSummary('Performance Summary');
    }
  };
