<?php namespace PayCal\Domain; ?>

  // Subpage module: members (data-business-subpage="members")
  // Entry: openMembersPage via refreshIndex; loadBusinessMembersGrid fetches the datagrid.

  const BUSINESS_MEMBERS_LENS_PREFIX = '[PayCal Lens][business/members]';
  const MEMBERS_KEYBOARD_PAGE_STEP = 25;
  const MEMBER_REPORT_CONFIRM_THRESHOLD = 25;
  const MEMBER_REPORT_REPEAT_COOLDOWN_MS = 3000;
  let memberReportBatchRunning = false;
  let memberReportLastStartedAt = 0;
  let memberReportLastBatch = null;
  let memberReportAllMembers = [];
  let memberReportSelectionMembers = [];
  let memberGroupOptionsCache = null;
  let membersKeyboardActiveRowId = '';
  let membersReportCloseShouldFocusSearch = false;

  const syncInertHiddenState = (container, hidden, options = {}) => {
    if (typeof PC?.setInertHiddenState === 'function') {
      PC.setInertHiddenState(container, hidden, options);
      return;
    }
    if (!(container instanceof HTMLElement)) {
      return;
    }
    container.toggleAttribute('aria-hidden', hidden);
    if (hidden) {
      container.setAttribute('inert', '');
    } else {
      container.removeAttribute('inert');
    }
  };

  const safeAttr = (value) => safeText(value).replace(/'/g, '&#039;');
  const setText = (element, value) => {
    if (element instanceof HTMLElement) {
      element.textContent = String(value ?? '');
    }
  };

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
    elements.membersSelectAllCheckbox = document.getElementById('business_members_select_all_checkbox');
    elements.membersClearSelectionButton = document.getElementById('business_members_clear_selection');
    elements.membersSelectionCount = document.getElementById('business_members_selection_count');
    elements.membersSelectionBadgeCount = document.getElementById('business_members_selection_badge_count');
    elements.membersBulkStatus = document.getElementById('business_members_bulk_status');
    elements.membersBulkGroupControl = document.getElementById('business_members_bulk_group_control');
    elements.membersBulkGroupToggle = document.getElementById('business_members_bulk_group_toggle');
    elements.membersBulkGroupMenu = document.getElementById('business_members_bulk_group_menu');
    elements.membersReportToggle = document.getElementById('business_members_report_toggle');
    elements.membersReportPanel = document.getElementById('business_members_report_panel');
    elements.membersReportClose = document.getElementById('business_members_report_close');
    elements.membersReportMemberFilter = document.getElementById('business_members_report_member_filter');
    elements.membersReportMemberAdd = document.getElementById('business_members_report_member_add');
    elements.membersReportMemberPills = document.getElementById('business_members_report_member_pills');
    elements.membersReportMemberEmpty = document.getElementById('business_members_report_member_empty');
    elements.membersReportMemberAddResults = document.getElementById('business_members_report_member_add_results');
    elements.membersReportSelectedCount = document.getElementById('business_members_report_selected_count');
    elements.membersReportType = document.getElementById('business_members_report_type');
    elements.membersReportDescription = document.getElementById('business_members_report_description');
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
    elements.membersInfoButton = document.getElementById('business_members_info_button');
    elements.membersInfoDialog = document.getElementById('business_members_info_dialog');
  };

  const formatMembersPendingSummaryLabel = (count = 0) => (
    formatPhpTemplate(T.membersPendingSummary, [Math.max(0, Number(count || 0))])
  );

  const formatMembersPendingMetricAria = (count = 0) => {
    const normalized = Math.max(0, Number(count || 0));
    if (normalized === 0) {
      return T.pending;
    }

    return formatPhpTemplate(T.membersPendingMetricAria, [
      normalized,
      normalized === 1 ? '' : 's',
    ]);
  };

  const announceMembersPendingStatus = (message) => {
    setPlainStatusText(elements.membersPendingStatus, message);
  };

  const membersGridRowSelector = '[data-grid="business-members"] .datagrid_body .datagrid_row:not(.datagrid_row_empty)';

  const getMembersGridRows = () => {
    syncMembersGridElementRefs();
    if (!(elements.membersGridContainer instanceof HTMLElement)) {
      return [];
    }

    return Array.from(elements.membersGridContainer.querySelectorAll(membersGridRowSelector))
      .filter((row) => row instanceof HTMLElement && !row.hidden);
  };

  const getMembersGridSearchInput = () => {
    syncMembersGridElementRefs();
    if (!(elements.membersGridContainer instanceof HTMLElement)) {
      return null;
    }

    const searchInput = elements.membersGridContainer.querySelector('[data-grid="business-members"] .datagrid_search');
    return searchInput instanceof HTMLInputElement ? searchInput : null;
  };

  const scrollMembersPageToTop = () => {
    const scrollingElement = document.scrollingElement || document.documentElement;
    if (scrollingElement instanceof Element && typeof scrollingElement.scrollTo === 'function') {
      scrollingElement.scrollTo({ top: 0, left: 0, behavior: 'auto' });
      return;
    }

    window.scrollTo(0, 0);
  };

  const scrollMembersPageToBottom = () => {
    const scrollingElement = document.scrollingElement || document.documentElement;
    const maxTop = scrollingElement instanceof Element
      ? Math.max(0, scrollingElement.scrollHeight - scrollingElement.clientHeight)
      : Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
    if (scrollingElement instanceof Element && typeof scrollingElement.scrollTo === 'function') {
      scrollingElement.scrollTo({ top: maxTop, left: 0, behavior: 'auto' });
      return;
    }

    window.scrollTo(0, maxTop);
  };

  const focusMembersGridSearch = (scrollToTop = false) => {
    const searchInput = getMembersGridSearchInput();
    if (!(searchInput instanceof HTMLInputElement)) {
      return false;
    }

    if (scrollToTop) {
      scrollMembersPageToTop();
    }

    searchInput.focus({ preventScroll: true });
    return true;
  };

  const focusMembersGridSearchSoon = (scrollToTop = false) => {
    window.requestAnimationFrame(() => {
      focusMembersGridSearch(scrollToTop);
    });
  };

  const memberRowId = (row) => (
    row instanceof HTMLElement
      ? String(row.dataset.memberId || row.dataset.id || '').trim()
      : ''
  );

  const resolveMembersGridRowFromTarget = (target) => {
    if (!(target instanceof Element)) {
      return null;
    }

    const row = target.closest('.businesses_member_row_clickable, .datagrid_row');
    return row instanceof HTMLElement && !row.classList.contains('datagrid_row_empty') ? row : null;
  };

  const syncMembersGridKeyboardRows = (preferredRow = null) => {
    const rows = getMembersGridRows();
    if (rows.length === 0) {
      membersKeyboardActiveRowId = '';
      return;
    }

    let activeRow = preferredRow instanceof HTMLElement && rows.includes(preferredRow)
      ? preferredRow
      : null;

    if (!(activeRow instanceof HTMLElement)) {
      const focusedRow = resolveMembersGridRowFromTarget(document.activeElement);
      activeRow = focusedRow instanceof HTMLElement && rows.includes(focusedRow)
        ? focusedRow
        : null;
    }

    if (!(activeRow instanceof HTMLElement) && membersKeyboardActiveRowId !== '') {
      activeRow = rows.find((row) => memberRowId(row) === membersKeyboardActiveRowId) || null;
    }

    if (!(activeRow instanceof HTMLElement)) {
      activeRow = rows[0];
    }

    rows.forEach((row) => {
      row.tabIndex = row === activeRow ? 0 : -1;
      const checkbox = row.querySelector('.business_members_row_checkbox');
      row.setAttribute('aria-selected', checkbox instanceof HTMLInputElement && checkbox.checked ? 'true' : 'false');
    });

    membersKeyboardActiveRowId = memberRowId(activeRow);
  };

  const focusMembersGridRow = (row, options = {}) => {
    if (!(row instanceof HTMLElement)) {
      return false;
    }

    syncMembersGridKeyboardRows(row);
    if (options.scroll === 'top') {
      scrollMembersPageToTop();
      window.requestAnimationFrame(() => {
        syncMembersGridKeyboardRows(row);
        row.focus({ preventScroll: true });
      });
    } else if (options.scroll === 'bottom') {
      scrollMembersPageToBottom();
      window.requestAnimationFrame(() => {
        syncMembersGridKeyboardRows(row);
        row.focus({ preventScroll: true });
      });
    } else if (options.scroll === 'nearest' && typeof row.scrollIntoView === 'function') {
      row.focus({ preventScroll: true });
      row.scrollIntoView({ block: 'nearest', inline: 'nearest', behavior: 'auto' });
    } else {
      row.focus();
    }
    return true;
  };

  const focusMembersGridRowByIndex = (index, options = {}) => {
    const rows = getMembersGridRows();
    if (rows.length === 0) {
      return false;
    }

    const boundedIndex = Math.max(0, Math.min(index, rows.length - 1));
    return focusMembersGridRow(rows[boundedIndex], options);
  };

  const focusMembersGridRelativeRow = (row, direction, options = {}) => {
    const rows = getMembersGridRows();
    const currentIndex = rows.indexOf(row);
    if (currentIndex === -1) {
      return false;
    }

    return focusMembersGridRowByIndex(currentIndex + direction, options);
  };

  const focusMembersGridPagedRow = (row, direction) => {
    const rows = getMembersGridRows();
    const currentIndex = rows.indexOf(row);
    if (currentIndex === -1) {
      return false;
    }

    return focusMembersGridRowByIndex(
      currentIndex + (direction * MEMBERS_KEYBOARD_PAGE_STEP),
      { scroll: 'nearest' },
    );
  };

  const toggleMembersGridRowSelection = (row) => {
    if (!(row instanceof HTMLElement)) {
      return false;
    }

    const checkbox = row.querySelector('.business_members_row_checkbox');
    if (!(checkbox instanceof HTMLInputElement) || checkbox.disabled) {
      return false;
    }

    checkbox.checked = !checkbox.checked;
    handleMembersBulkCheckboxChange(checkbox);
    syncMembersGridKeyboardRows(row);
    return true;
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
      elements.membersPendingDetails.classList.toggle('is-empty', normalized === 0);
      syncInertHiddenState(elements.membersPendingDetails, normalized === 0);
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
    announceMembersPendingStatus(formatPhpTemplate(T.membersPendingLoaded, [
      total,
      total === 1 ? '' : 's',
    ]));

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
      elements.membersPendingDetails.classList.remove('is-empty');
      syncInertHiddenState(elements.membersPendingDetails, false);
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
    if (!business) {
      setMembersPendingAccordionMessage(ACCESS_MANAGE_WARNING);
      return 0;
    }

    if (!canUsePremiumOrgFeatures(business)) {
      setMembersPendingAccordionMessage(T.premiumAdminLockedDetailed);
      return 0;
    }

    if (!canManageBusinessAccess(business)) {
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

    return formatPhpTemplate(T.membersSelectionCount, [normalized]);
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

    if (elements.membersSelectAllCheckbox instanceof HTMLInputElement) {
      const visibleCheckboxes = Array.from(
        elements.membersGridContainer?.querySelectorAll('.business_members_row_checkbox') || [],
      ).filter((checkbox) => checkbox instanceof HTMLInputElement);
      const checkedVisible = visibleCheckboxes.filter((checkbox) => checkbox.checked).length;
      elements.membersSelectAllCheckbox.checked = visibleCheckboxes.length > 0 && checkedVisible === visibleCheckboxes.length;
      elements.membersSelectAllCheckbox.indeterminate = checkedVisible > 0 && checkedVisible < visibleCheckboxes.length;
    }

    setMembersBulkToolbarSelectedState(count > 0);

    if (elements.membersGenerateReportsButton instanceof HTMLButtonElement) {
      elements.membersGenerateReportsButton.disabled = count === 0;
    }
  };

  const syncMembersBulkCheckboxes = () => {
    syncMembersGridElementRefs();
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

    syncMembersGridKeyboardRows();
    updateMembersBulkSelectionUi();
  };

  const clearMembersBulkSelection = (focusSearch = false) => {
    state.membersBulkSelectAllActive = false;
    state.membersBulkSelectedIds = [];
    syncMembersBulkCheckboxes();
    if (focusSearch) {
      focusMembersGridSearchSoon(false);
    }
  };

  const setMembersBulkToolbarVisible = (visible) => {
    if (elements.membersBulkToolbar instanceof HTMLElement) {
      const active = visible && getMembersBulkSelectedCount() > 0;
      elements.membersBulkToolbar.dataset.accessAllowed = visible ? '1' : '0';
      elements.membersBulkToolbar.classList.toggle('is-active', active);
      syncInertHiddenState(elements.membersBulkToolbar, !active);
    }
    integrateMembersToolbarLayout();
  };

  const setMembersBulkToolbarSelectedState = (hasSelection) => {
    if (!(elements.membersBulkToolbar instanceof HTMLElement)) {
      return;
    }

    const canAccess = String(elements.membersBulkToolbar.dataset.accessAllowed || '0') === '1';
    const active = canAccess && hasSelection;
    elements.membersBulkToolbar.classList.toggle('is-active', active);
    syncInertHiddenState(elements.membersBulkToolbar, !active);
    elements.membersBulkToolbar.dataset.hasSelection = hasSelection ? '1' : '0';

    const filters = document.querySelector('[data-grid="business-members"] .business_members_toolbar_filters');
    if (filters instanceof HTMLElement) {
      filters.classList.toggle('is-inactive', canAccess && hasSelection);
      syncInertHiddenState(filters, canAccess && hasSelection);
    }

    if (!hasSelection) {
      closeMembersBulkGroupMenu();
      if (elements.membersBulkToolbar.contains(document.activeElement)) {
        focusMembersGridSearchSoon(false);
      }
    }
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

    const filterSlot = datagridToolbar.querySelector('.business_members_toolbar_filters');
    if (!(filterSlot instanceof HTMLElement)) {
      return;
    }

    const columnMenu = gridEl.querySelector('.datagrid_column_menu');
    if (columnMenu instanceof HTMLElement && !filterSlot.contains(columnMenu)) {
      filterSlot.appendChild(columnMenu);
    }

    const bulkSlot = datagridToolbar.querySelector('.business_members_toolbar_bulk');
    if (!(bulkSlot instanceof HTMLElement)) {
      return;
    }

    if (!bulkSlot.contains(bulkToolbar)) {
      bulkSlot.appendChild(bulkToolbar);
    }

    setMembersBulkToolbarSelectedState(getMembersBulkSelectedCount() > 0);
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
      menu.closest('.datagrid_row')?.classList.remove('business_member_row_menu_open');
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
    menu.closest('.datagrid_row')?.classList.toggle('business_member_row_menu_open', isOpen);
    if (!isOpen) {
      menu.querySelectorAll('.business_member_row_submenu').forEach((submenu) => {
        submenu.hidden = true;
      });
      menu.querySelectorAll('.business_member_row_menu_item_has_submenu').forEach((submenuToggle) => {
        submenuToggle.setAttribute('aria-expanded', 'false');
      });
    }
  };

  const getMemberRowMenuDirectItems = (scope) => {
    if (!(scope instanceof HTMLElement)) {
      return [];
    }

    return Array.from(scope.children).filter((item) => {
      if (!(item instanceof HTMLElement) || item.getAttribute('role') !== 'menuitem') {
        return false;
      }
      if (item.closest('[hidden]') || item.getAttribute('aria-disabled') === 'true') {
        return false;
      }
      return !(item instanceof HTMLButtonElement) || !item.disabled;
    });
  };

  const focusMemberRowMenuItem = (item) => {
    if (item instanceof HTMLElement) {
      item.focus({ preventScroll: true });
      return true;
    }

    return false;
  };

  const focusFirstMemberRowMenuItem = (menu) => {
    const panel = menu instanceof HTMLElement
      ? menu.querySelector('.business_member_row_menu_panel')
      : null;
    const firstItem = panel instanceof HTMLElement
      ? getMemberRowMenuDirectItems(panel)[0]
      : null;

    return focusMemberRowMenuItem(firstItem);
  };

  const focusMemberRowMenuItemByOffset = (scope, activeItem, offset) => {
    const items = getMemberRowMenuDirectItems(scope);
    if (items.length === 0) {
      return false;
    }

    const currentIndex = Math.max(0, items.indexOf(activeItem));
    const nextIndex = (currentIndex + offset + items.length) % items.length;
    return focusMemberRowMenuItem(items[nextIndex]);
  };

  const findMemberRowMenuParentToggle = (menu, submenu) => {
    if (!(menu instanceof HTMLElement) || !(submenu instanceof HTMLElement)) {
      return null;
    }

    if (submenu.classList.contains('business_member_role_submenu')) {
      return menu.querySelector('[data-member-action="edit-role"]');
    }

    if (submenu.classList.contains('business_member_group_submenu')) {
      return menu.querySelector('[data-member-action="add-to-group"]');
    }

    return null;
  };

  const openMemberRowMenuForRow = (row, focusFirst = true) => {
    if (!(row instanceof HTMLElement)) {
      return false;
    }

    const menu = row.querySelector('.business_member_row_menu');
    if (!(menu instanceof HTMLElement)) {
      return false;
    }

    closeAllMemberRowMenus(menu);
    syncMembersGridKeyboardRows(row);
    setMemberRowMenuOpen(menu, true);
    if (focusFirst) {
      focusFirstMemberRowMenuItem(menu);
    }

    return true;
  };

  const closeMemberRowMenuToSearch = (menu) => {
    if (!(menu instanceof HTMLElement)) {
      return false;
    }

    setMemberRowMenuOpen(menu, false);
    focusMembersGridSearchSoon(false);

    return true;
  };

  const loadActiveMemberGroups = async () => {
    if (Array.isArray(memberGroupOptionsCache)) {
      return memberGroupOptionsCache;
    }

    const businessId = String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
    if (businessId === '') {
      return [];
    }

    const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/groups?active=1`);
    memberGroupOptionsCache = Array.isArray(payload?.groups) ? payload.groups : [];
    return memberGroupOptionsCache;
  };

  const closeSiblingMemberRowSubmenus = (menu, keepSubmenu) => {
    if (!(menu instanceof HTMLElement)) {
      return;
    }

    menu.querySelectorAll('.business_member_row_submenu').forEach((submenu) => {
      if (submenu !== keepSubmenu) {
        submenu.hidden = true;
      }
    });

    menu.querySelectorAll('.business_member_row_menu_item_has_submenu').forEach((toggle) => {
      const action = String(toggle.dataset.memberAction || '').trim();
      const keepGroup = keepSubmenu?.classList.contains('business_member_group_submenu') && action === 'add-to-group';
      const keepRole = keepSubmenu?.classList.contains('business_member_role_submenu') && action === 'edit-role';
      toggle.setAttribute('aria-expanded', keepGroup || keepRole ? 'true' : 'false');
    });
  };

  const openMemberRoleSubmenu = (menu) => {
    if (!(menu instanceof HTMLElement)) {
      return;
    }

    const submenu = menu.querySelector('.business_member_role_submenu');
    const toggle = menu.querySelector('[data-member-action="edit-role"]');
    if (!(submenu instanceof HTMLElement)) {
      return;
    }

    closeSiblingMemberRowSubmenus(menu, submenu);
    submenu.hidden = false;
    if (toggle instanceof HTMLElement) {
      toggle.setAttribute('aria-expanded', 'true');
    }
  };

  const renderMemberGroupSubmenuHtml = (groups, options = {}) => {
    const memberUuid = String(options.memberUuid || '').trim();
    const itemClass = options.bulk === true
      ? 'business_members_bulk_group_menu_item'
      : 'business_member_group_menu_item';
    const groupItems = groups.map((group) => {
      const groupId = String(group.group_id || '').trim();
      const memberAttr = memberUuid !== ''
        ? ' data-member-id="' + safeAttr(memberUuid) + '"'
        : '';

      return '<button type="button" class="business_member_row_submenu_item ' + itemClass + '" role="menuitem" data-group-id="' + safeAttr(groupId) + '"' + memberAttr + '>'
        + '<span>' + safeText(group.name || 'Group') + '</span>'
        + '<span class="business_member_row_submenu_meta">' + safeText(String(group.member_count ?? 0)) + ' members</span>'
        + '</button>';
    }).join('');

    return (groupItems !== '' ? groupItems : '<p class="business_member_row_submenu_note">' + safeText(T.businessGroupsEmpty || 'No active groups yet.') + '</p>')
      + '<a class="business_member_row_submenu_item business_member_row_submenu_link" role="menuitem" href="/business/groups/?create=1">' + safeText(T.businessGroupsCreateNew || 'Create new group') + '</a>';
  };

  const memberGroupNameFromMenuItem = (menuItem) => {
    if (!(menuItem instanceof HTMLElement)) {
      return '';
    }
    const label = menuItem.querySelector('span');
    return String(label?.textContent || '').trim();
  };

  const memberGroupToastLabel = (groupName) => {
    const normalized = String(groupName || '').trim();
    if (normalized === '') {
      return 'selected group';
    }
    return /\bgroup$/i.test(normalized) ? normalized : normalized + ' group';
  };

  const formatMemberGroupToast = (template, groupName, count = null) => {
    const numericCount = Number(count || 0);
    const fallback = count === null
      ? 'Added to {group}.'
      : '{count} member{plural} added to {group}.';
    const message = String(template || '').trim() || fallback;

    return message
      .replace(/\{group\}/g, memberGroupToastLabel(groupName))
      .replace(/\{count\}/g, String(numericCount))
      .replace(/\{plural\}/g, numericCount === 1 ? '' : 's');
  };

  const openMemberGroupSubmenu = async (menu, memberUuid) => {
    if (!(menu instanceof HTMLElement) || memberUuid === '') {
      return;
    }

    const submenu = menu.querySelector('.business_member_group_submenu');
    const toggle = menu.querySelector('[data-member-action="add-to-group"]');
    if (!(submenu instanceof HTMLElement)) {
      return;
    }

    closeSiblingMemberRowSubmenus(menu, submenu);
    submenu.hidden = false;
    if (toggle instanceof HTMLElement) {
      toggle.setAttribute('aria-expanded', 'true');
    }
    Guardian.setHTML(submenu, '<p class="business_member_row_submenu_note">' + safeText(T.loading) + '</p>');

    const groups = await loadActiveMemberGroups();
    Guardian.setHTML(submenu, renderMemberGroupSubmenuHtml(groups, { memberUuid }));
  };

  const closeMembersBulkGroupMenu = (options = {}) => {
    const menu = elements.membersBulkGroupMenu;
    const toggle = elements.membersBulkGroupToggle;
    if (menu instanceof HTMLElement) {
      menu.hidden = true;
    }
    if (toggle instanceof HTMLButtonElement) {
      toggle.setAttribute('aria-expanded', 'false');
    }
    if (options.focusSearch === true) {
      focusMembersGridSearchSoon(false);
    }
  };

  const openMembersBulkGroupMenu = async () => {
    syncMembersGridElementRefs();
    const menu = elements.membersBulkGroupMenu;
    const toggle = elements.membersBulkGroupToggle;
    if (!(menu instanceof HTMLElement) || !(toggle instanceof HTMLButtonElement)) {
      return;
    }

    closeAllMemberRowMenus();
    const willOpen = menu.hidden;
    if (!willOpen) {
      closeMembersBulkGroupMenu({ focusSearch: true });
      return;
    }

    menu.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
    Guardian.setHTML(menu, '<p class="business_member_row_submenu_note">' + safeText(T.loading) + '</p>');

    try {
      const groups = await loadActiveMemberGroups();
      Guardian.setHTML(menu, renderMemberGroupSubmenuHtml(groups, { bulk: true }));
    } catch (error) {
      PW.error(error);
      Guardian.setHTML(menu, '<p class="business_member_row_submenu_note">' + safeText(T.businessGroupsLoadFailed || 'Unable to load groups.') + '</p>');
    }
  };

  const addSelectedMembersToGroup = async (groupId, trigger = null) => {
    const businessId = String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
    const normalizedGroupId = String(groupId || '').trim();
    if (businessId === '' || normalizedGroupId === '') {
      return;
    }

    const selectedMembers = await resolveMembersForReportGeneration(businessId);
    const memberIds = selectedMembers
      .map((member) => String(member?.id || '').trim())
      .filter((memberId, index, ids) => memberId !== '' && ids.indexOf(memberId) === index);
    if (memberIds.length === 0) {
      PC.showToast(T.businessGroupsAddNoMembers || 'Select at least one member first.', 'error', 5000, true);
      closeMembersBulkGroupMenu({ focusSearch: true });
      return;
    }

    if (trigger instanceof HTMLElement) {
      trigger.setAttribute('aria-disabled', 'true');
    }
    if (trigger instanceof HTMLButtonElement) {
      trigger.disabled = true;
    }
    if (elements.membersBulkGroupToggle instanceof HTMLButtonElement) {
      elements.membersBulkGroupToggle.disabled = true;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(businessId)}/groups/${encodeURIComponent(normalizedGroupId)}/members`, {
        member_uuids: memberIds,
      });
      memberGroupOptionsCache = null;
      closeMembersBulkGroupMenu({ focusSearch: true });
      const message = formatMemberGroupToast(
        T.businessGroupsMembersAddedNamed,
        memberGroupNameFromMenuItem(trigger),
        memberIds.length
      );
      PC.showToast(message, 'success', 3500, true);
    } catch (error) {
      PW.error(error);
      PC.showToast(T.businessGroupsAddFailed || 'Unable to add members to group.', 'error', 6000, true);
    } finally {
      if (trigger instanceof HTMLElement) {
        trigger.removeAttribute('aria-disabled');
      }
      if (trigger instanceof HTMLButtonElement) {
        trigger.disabled = false;
      }
      if (elements.membersBulkGroupToggle instanceof HTMLButtonElement) {
        elements.membersBulkGroupToggle.disabled = false;
      }
    }
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
      const menu = menuItem.closest('.business_member_row_menu');
      openMemberRoleSubmenu(menu);
      return;
    }

    if (action === 'add-to-group') {
      const menu = menuItem.closest('.business_member_row_menu');
      openMemberGroupSubmenu(menu, memberUuid).catch((error) => {
        PW.error(error);
      });
      return;
    }

    if (action === 'revoke') {
      showConfirmRevokeDialog(memberUuid, menuItem).catch((error) => PW.error(error));
    }
  };

  const focusFirstMemberRowSubmenuItem = (submenu) => {
    const firstItem = submenu instanceof HTMLElement
      ? getMemberRowMenuDirectItems(submenu)[0]
      : null;

    return focusMemberRowMenuItem(firstItem);
  };

  const openMemberRowSubmenuFromToggle = (menuItem) => {
    if (!(menuItem instanceof HTMLElement)) {
      return false;
    }

    const menu = menuItem.closest('.business_member_row_menu');
    if (!(menu instanceof HTMLElement)) {
      return false;
    }

    const action = String(menuItem.dataset.memberAction || '').trim();
    const memberUuid = String(menuItem.dataset.memberId || '').trim();
    if (action === 'edit-role') {
      openMemberRoleSubmenu(menu);
      focusFirstMemberRowSubmenuItem(menu.querySelector('.business_member_role_submenu'));
      return true;
    }

    if (action === 'add-to-group' && memberUuid !== '') {
      openMemberGroupSubmenu(menu, memberUuid).then(() => {
        focusFirstMemberRowSubmenuItem(menu.querySelector('.business_member_group_submenu'));
      }).catch((error) => {
        PW.error(error);
      });
      return true;
    }

    return false;
  };

  const chooseMemberRowMenuItem = (menuItem) => {
    if (!(menuItem instanceof HTMLElement)) {
      return false;
    }

    if (menuItem.classList.contains('business_member_row_menu_item_has_submenu')) {
      return openMemberRowSubmenuFromToggle(menuItem);
    }

    menuItem.click();
    return true;
  };

  const handleMemberRowMenuKeyboard = (event, container) => {
    const target = event.target instanceof Element ? event.target : null;
    if (!(container instanceof HTMLElement) || target === null || !container.contains(target)) {
      return false;
    }

    const menuToggle = target.closest('.business_member_row_menu_toggle');
    if (menuToggle instanceof HTMLElement) {
      if (
        event.key === 'Enter'
        || event.key === ' '
        || event.key === 'Space'
        || event.key === 'Spacebar'
        || event.code === 'Space'
        || event.key === 'ArrowDown'
        || event.key === 'ArrowRight'
      ) {
        event.preventDefault();
        event.stopPropagation();
        const row = menuToggle.closest('.businesses_member_row_clickable, .datagrid_row');
        return openMemberRowMenuForRow(row, true);
      }

      return false;
    }

    const menu = target.closest('.business_member_row_menu');
    const panel = target.closest('.business_member_row_menu_panel');
    if (!(menu instanceof HTMLElement) || !(panel instanceof HTMLElement)) {
      return false;
    }

    const submenu = target.closest('.business_member_row_submenu:not([hidden])');
    const scope = submenu instanceof HTMLElement ? submenu : panel;
    const activeItem = target.closest('[role="menuitem"]');

    if (event.key === 'Escape') {
      event.preventDefault();
      event.stopPropagation();
      return closeMemberRowMenuToSearch(menu);
    }

    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      event.stopPropagation();
      return focusMemberRowMenuItemByOffset(scope, activeItem, event.key === 'ArrowDown' ? 1 : -1);
    }

    if (event.key === 'ArrowRight') {
      if (activeItem instanceof HTMLElement && activeItem.classList.contains('business_member_row_menu_item_has_submenu')) {
        event.preventDefault();
        event.stopPropagation();
        return openMemberRowSubmenuFromToggle(activeItem);
      }

      return false;
    }

    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      event.stopPropagation();
      if (submenu instanceof HTMLElement) {
        submenu.hidden = true;
        const parentToggle = findMemberRowMenuParentToggle(menu, submenu);
        if (parentToggle instanceof HTMLElement) {
          parentToggle.setAttribute('aria-expanded', 'false');
          parentToggle.focus({ preventScroll: true });
        }
        return true;
      }

      return closeMemberRowMenuToSearch(menu);
    }

    if (event.key === 'Enter' || event.key === ' ' || event.key === 'Space' || event.key === 'Spacebar' || event.code === 'Space') {
      if (activeItem instanceof HTMLElement) {
        event.preventDefault();
        event.stopPropagation();
        return chooseMemberRowMenuItem(activeItem);
      }
    }

    return false;
  };

  const refreshMembersBulkToolbar = async (orgId = '') => {
    syncMembersGridElementRefs();
    const businessId = String(orgId || resolveWorkspaceBusinessId() || state.selectedBusinessId || '').trim();

    setMembersBulkToolbarVisible(true);

    if (businessId !== '') {
      try {
        const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/connections`);
        const members = Array.isArray(payload?.members) ? payload.members : [];
        state.membersBulkTotalCount = members.length;
      } catch (_error) {
        state.membersBulkTotalCount = 0;
      }
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
    syncMembersGridKeyboardRows(checkbox.closest('.businesses_member_row_clickable, .datagrid_row'));
    updateMembersBulkSelectionUi();
  };

  const toggleAllVisibleBusinessMembers = (checked) => {
    state.membersBulkSelectAllActive = false;
    const selected = new Set(state.membersBulkSelectedIds);

    if (elements.membersGridContainer instanceof HTMLElement) {
      elements.membersGridContainer.querySelectorAll('.business_members_row_checkbox').forEach((checkbox) => {
        if (!(checkbox instanceof HTMLInputElement)) {
          return;
        }
        const memberId = String(checkbox.dataset.memberId || '').trim();
        if (memberId === '') {
          return;
        }
        if (checked) {
          selected.add(memberId);
        } else {
          selected.delete(memberId);
        }
      });
    }

    state.membersBulkSelectedIds = Array.from(selected);
    syncMembersBulkCheckboxes();
  };

  const setMembersReportStatus = (message = '') => {
    setPlainStatusText(elements.membersReportStatus, message);
  };

  const setMembersReportSummaryVisible = (isVisible) => {
    if (elements.membersReportSummary instanceof HTMLElement) {
      elements.membersReportSummary.hidden = !isVisible;
    }
  };

  const memberMatchesQuery = (member, query) => (
    query === ''
    || String(member.name || '').toLowerCase().includes(query)
    || String(member.id || '').toLowerCase().includes(query)
  );

  const normalizeReportMember = (member) => {
    const id = String(member?.id || member?.user_uuid || member?.uuid || '').trim();
    if (id === '') {
      return null;
    }

    return {
      id,
      name: String(member?.name || member?.full_name || member?.email || id).trim() || id,
    };
  };

  const loadAllMembersForReportDialog = async (orgId) => {
    const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(orgId)}/connections`);
    const members = Array.isArray(payload?.members) ? payload.members : [];
    const unique = new Map();
    members.forEach((member) => {
      const normalized = normalizeReportMember(member);
      if (normalized !== null) {
        unique.set(normalized.id, normalized);
      }
    });
    memberReportAllMembers = Array.from(unique.values()).sort((a, b) => a.name.localeCompare(b.name));
  };

  const syncReportDialogSelectionToBulkState = () => {
    state.membersBulkSelectAllActive = false;
    state.membersBulkSelectedIds = memberReportSelectionMembers.map((member) => member.id);
    syncMembersBulkCheckboxes();
  };

  const renderMembersReportSelectionList = () => {
    const selectedQuery = elements.membersReportMemberFilter instanceof HTMLInputElement
      ? String(elements.membersReportMemberFilter.value || '').trim().toLowerCase()
      : '';
    const addQuery = elements.membersReportMemberAdd instanceof HTMLInputElement
      ? String(elements.membersReportMemberAdd.value || '').trim().toLowerCase()
      : '';
    const selectedIds = new Set(memberReportSelectionMembers.map((member) => member.id));
    const selectedMatches = memberReportSelectionMembers.filter((member) => memberMatchesQuery(member, selectedQuery));
    const addMatches = addQuery === ''
      ? []
      : memberReportAllMembers
        .filter((member) => !selectedIds.has(member.id) && memberMatchesQuery(member, addQuery))
        .slice(0, 12);

    setText(elements.membersReportSelectedCount, String(memberReportSelectionMembers.length));

    if (elements.membersReportMemberPills instanceof HTMLElement) {
      Guardian.setHTML(
        elements.membersReportMemberPills,
        selectedMatches.map((member) => (
          '<span class="business_members_report_member_pill" role="listitem">'
          + '<span class="business_members_report_member_pill_name">' + safeText(member.name) + '</span>'
          + '<button type="button" class="business_members_report_member_remove" data-member-id="' + safeAttr(member.id) + '" aria-label="' + safeAttr(formatPhpTemplate(T.memberReportRemoveAria, [member.name])) + '">&times;</button>'
          + '</span>'
        )).join(''),
      );
    }

    if (elements.membersReportMemberEmpty instanceof HTMLElement) {
      elements.membersReportMemberEmpty.hidden = selectedMatches.length > 0;
    }

    if (elements.membersReportMemberAddResults instanceof HTMLElement) {
      Guardian.setHTML(
        elements.membersReportMemberAddResults,
        addMatches.map((member) => (
          '<button type="button" class="business_members_report_member_pill business_members_report_member_add" data-member-id="' + safeAttr(member.id) + '">'
          + '<span class="business_members_report_member_pill_name">' + safeText(member.name) + '</span>'
          + '<span aria-hidden="true">+</span>'
          + '</button>'
        )).join(''),
      );
    }
  };

  const refreshMembersReportSelectionDialog = async () => {
    const orgId = String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
    if (orgId === '') {
      memberReportAllMembers = [];
      memberReportSelectionMembers = [];
      renderMembersReportSelectionList();
      return;
    }

    await loadAllMembersForReportDialog(orgId);
    const selectedMembers = await resolveMembersForReportGeneration(orgId);
    const unique = new Map();
    selectedMembers.forEach((member) => {
      const normalized = normalizeReportMember(member);
      if (normalized !== null) {
        unique.set(normalized.id, normalized);
      }
    });
    memberReportSelectionMembers = Array.from(unique.values()).sort((a, b) => a.name.localeCompare(b.name));
    renderMembersReportSelectionList();
  };

  const setMembersReportPanelOpen = (isOpen, options = {}) => {
    if (!(elements.membersReportPanel instanceof HTMLDialogElement)) {
      return;
    }

    if (isOpen && !elements.membersReportPanel.open) {
      if (typeof elements.membersReportPanel.showModal === 'function') {
        elements.membersReportPanel.showModal();
      } else {
        elements.membersReportPanel.setAttribute('open', '');
      }
    } else if (!isOpen && elements.membersReportPanel.open) {
      elements.membersReportPanel.close();
    }

    if (elements.membersReportToggle instanceof HTMLButtonElement) {
      elements.membersReportToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }
    if (isOpen) {
      setMembersReportStatus('');
      if (memberReportLastBatch === null) {
        setMembersReportSummaryVisible(false);
      }
      updateMembersReportDescription();
      refreshMembersReportSelectionDialog().catch((error) => {
        PW.error(error);
        setMembersReportStatus('Unable to load selected members.');
      });
    } else if (options.focusSearch === true) {
      focusMembersGridSearchSoon(false);
    }
  };

  const toggleMembersReportPanel = () => {
    if (!(elements.membersReportPanel instanceof HTMLDialogElement)) {
      return;
    }

    const willOpen = !elements.membersReportPanel.open;
    setMembersReportPanelOpen(willOpen, { focusSearch: !willOpen });
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

  const updateMembersReportDescription = () => {
    if (!(elements.membersReportDescription instanceof HTMLElement)) {
      return;
    }

    const selected = elements.membersReportType instanceof HTMLSelectElement
      ? elements.membersReportType.selectedOptions[0]
      : null;
    setText(elements.membersReportDescription, String(selected?.dataset?.reportDescription || '').trim());
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
      const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(orgId)}/connections`);
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

  const postProtectedMemberReportBlob = async (orgId, memberId, scope, format, year) => {
    return postJsonBlob(
      `/api/v1/businesses/${encodeURIComponent(orgId)}/members/${encodeURIComponent(memberId)}/reports/export/${encodeURIComponent(format)}`,
      {
        scope,
        year,
      },
      {
        errorPrefix: 'Report export failed',
        timeoutMs: 60000,
      },
    );
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

  const csvEscape = businessCsvEscape;

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
      PC.showToast(T.memberReportsNoSelectedMembers, 'error', 6000, true);
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
        formatPhpTemplate(T.memberReportsConfirm, [members.length, format.toUpperCase()]),
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
        setMembersReportStatus(formatPhpTemplate(T.memberReportsProgress, [
          index + 1,
          members.length,
          member.name,
        ]));
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
        ? formatPhpTemplate(T.memberReportsGeneratedFailed, [
          batch.generated,
          batch.generated === 1 ? '' : 's',
          batch.failed,
        ])
        : formatPhpTemplate(T.memberReportsGeneratedSuccess, [
          batch.generated,
          batch.generated === 1 ? '' : 's',
        ]);
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

  const bindMembersBulkToolbar = () => {
    syncMembersGridElementRefs();
    const toolbar = elements.membersBulkToolbar;
    if (!(toolbar instanceof HTMLElement) || toolbar.dataset.membersBulkBound === '1') {
      return;
    }
    toolbar.dataset.membersBulkBound = '1';

    if (elements.membersClearSelectionButton instanceof HTMLButtonElement) {
      elements.membersClearSelectionButton.addEventListener('click', () => {
        clearMembersBulkSelection(true);
      });
    }

    if (elements.membersReportToggle instanceof HTMLButtonElement) {
      elements.membersReportToggle.addEventListener('click', () => {
        closeMembersBulkGroupMenu();
        toggleMembersReportPanel();
      });
    }

    if (elements.membersBulkGroupToggle instanceof HTMLButtonElement) {
      elements.membersBulkGroupToggle.addEventListener('click', () => {
        setMembersReportPanelOpen(false);
        openMembersBulkGroupMenu().catch((error) => {
          PW.error(error);
        });
      });
    }

    if (elements.membersBulkGroupMenu instanceof HTMLElement) {
      elements.membersBulkGroupMenu.addEventListener('click', (event) => {
        const menuItem = event.target instanceof Element
          ? event.target.closest('.business_members_bulk_group_menu_item')
          : null;
        if (!(menuItem instanceof HTMLButtonElement)) {
          return;
        }

        event.preventDefault();
        event.stopPropagation();
        addSelectedMembersToGroup(menuItem.dataset.groupId || '', menuItem).catch((error) => {
          PW.error(error);
        });
      });
    }

    toolbar.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') {
        return;
      }

      closeMembersBulkGroupMenu({ focusSearch: true });
    });

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

    if (elements.membersReportPanel instanceof HTMLDialogElement) {
      elements.membersReportPanel.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          event.preventDefault();
          membersReportCloseShouldFocusSearch = true;
          setMembersReportPanelOpen(false, { focusSearch: true });
        }
      });

      elements.membersReportPanel.addEventListener('close', () => {
        if (elements.membersReportToggle instanceof HTMLButtonElement) {
          elements.membersReportToggle.setAttribute('aria-expanded', 'false');
        }
        if (membersReportCloseShouldFocusSearch) {
          focusMembersGridSearchSoon(false);
          membersReportCloseShouldFocusSearch = false;
        }
      });
    }

    if (elements.membersReportClose instanceof HTMLButtonElement) {
      elements.membersReportClose.addEventListener('click', () => {
        membersReportCloseShouldFocusSearch = true;
      }, { capture: true });
    }

    if (elements.membersReportType instanceof HTMLSelectElement) {
      elements.membersReportType.addEventListener('change', updateMembersReportDescription);
      updateMembersReportDescription();
    }

    if (elements.membersReportMemberFilter instanceof HTMLInputElement) {
      elements.membersReportMemberFilter.addEventListener('input', renderMembersReportSelectionList);
    }

    if (elements.membersReportMemberAdd instanceof HTMLInputElement) {
      elements.membersReportMemberAdd.addEventListener('input', renderMembersReportSelectionList);
    }

    if (elements.membersReportMemberPills instanceof HTMLElement) {
      elements.membersReportMemberPills.addEventListener('click', (event) => {
        const button = event.target instanceof Element
          ? event.target.closest('.business_members_report_member_remove')
          : null;
        if (!(button instanceof HTMLButtonElement)) {
          return;
        }

        const memberId = String(button.dataset.memberId || '').trim();
        memberReportSelectionMembers = memberReportSelectionMembers.filter((member) => member.id !== memberId);
        syncReportDialogSelectionToBulkState();
        renderMembersReportSelectionList();
      });
    }

    if (elements.membersReportMemberAddResults instanceof HTMLElement) {
      elements.membersReportMemberAddResults.addEventListener('click', (event) => {
        const button = event.target instanceof Element
          ? event.target.closest('.business_members_report_member_add')
          : null;
        if (!(button instanceof HTMLButtonElement)) {
          return;
        }

        const memberId = String(button.dataset.memberId || '').trim();
        const member = memberReportAllMembers.find((candidate) => candidate.id === memberId);
        if (!member || memberReportSelectionMembers.some((selected) => selected.id === member.id)) {
          return;
        }

        memberReportSelectionMembers = [...memberReportSelectionMembers, member]
          .sort((a, b) => a.name.localeCompare(b.name));
        syncReportDialogSelectionToBulkState();
        renderMembersReportSelectionList();
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

  };

  document.addEventListener('click', (event) => {
    if (!isBusinessMembersSubPage()) {
      return;
    }

    const reportControl = document.getElementById('business_members_report_control');
    const groupControl = document.getElementById('business_members_bulk_group_control');
    const target = event.target instanceof Node ? event.target : null;
    if (target === null) {
      return;
    }

    if (reportControl instanceof HTMLElement && !reportControl.contains(target)) {
      setMembersReportPanelOpen(false);
    }

    if (groupControl instanceof HTMLElement && !groupControl.contains(target)) {
      closeMembersBulkGroupMenu();
    }
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
    announceMembersGridStatus(formatPhpTemplate(T.membersGridReady, [
      rowCount,
      rowCount === 1 ? '' : 's',
    ]));
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

    return state.membersGridManager;
  };

  const announceMembersGridStatus = (message) => {
    setPlainStatusText(elements.membersGridStatus, message);
  };

  const isMembersGridInteractiveTarget = (target) => {
    if (!(target instanceof Element)) {
      return false;
    }

    return !!target.closest(
      'a, button, input, select, textarea, label, .datagrid_action, .business_members_row_checkbox, .business_members_row_select, .business_member_row_menu, .business_member_row_menu_toggle, .business_member_row_menu_item, .datagrid_sort, .datagrid_search, .datagrid_pager, .datagrid_pagination, .datagrid_column_toggle, .datagrid_column_toggle_input, .datagrid_column_menu, .datagrid_column_menu_toggle, .datagrid_column_menu_panel, .business_members_bulk_toolbar, .business_members_toolbar_bulk',
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

  const handleMembersGridSearchKeyboard = (event) => {
    const searchInput = getMembersGridSearchInput();
    if (!(searchInput instanceof HTMLInputElement) || event.target !== searchInput) {
      return false;
    }

    if (event.ctrlKey || event.metaKey || event.altKey) {
      return false;
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      return focusMembersGridRowByIndex(0);
    }

    if (event.key === 'Home') {
      event.preventDefault();
      return focusMembersGridRowByIndex(0, { scroll: 'top' });
    }

    if (event.key === 'End') {
      const rows = getMembersGridRows();
      if (rows.length === 0) {
        return false;
      }

      event.preventDefault();
      return focusMembersGridRowByIndex(rows.length - 1, { scroll: 'bottom' });
    }

    if (event.key === 'PageDown') {
      event.preventDefault();
      return focusMembersGridRowByIndex(MEMBERS_KEYBOARD_PAGE_STEP - 1, { scroll: 'nearest' });
    }

    if (event.key === 'PageUp') {
      event.preventDefault();
      return focusMembersGridRowByIndex(0, { scroll: 'nearest' });
    }

    return false;
  };

  const handleMembersGridRowKeyboard = (event, container) => {
    if (isMembersGridInteractiveTarget(event.target)) {
      return false;
    }

    const row = resolveMembersGridRowFromTarget(event.target);
    if (!(row instanceof HTMLElement) || !container.contains(row)) {
      return false;
    }

    if (
      event.key === 'ContextMenu'
      || (event.shiftKey && event.key === 'F10')
      || (event.shiftKey && event.key === 'Enter')
      || (
        event.shiftKey
        && (
          event.key === ' '
          || event.key === 'Space'
          || event.key === 'Spacebar'
          || event.code === 'Space'
        )
      )
    ) {
      event.preventDefault();
      event.stopPropagation();
      return openMemberRowMenuForRow(row, true);
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      return focusMembersGridRelativeRow(row, 1);
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault();
      const rows = getMembersGridRows();
      if (rows.indexOf(row) <= 0) {
        return focusMembersGridSearch(false);
      }

      return focusMembersGridRelativeRow(row, -1);
    }

    if (event.key === 'Home') {
      event.preventDefault();
      return focusMembersGridRowByIndex(0, { scroll: 'top' });
    }

    if (event.key === 'End') {
      const rows = getMembersGridRows();
      if (rows.length === 0) {
        return false;
      }

      event.preventDefault();
      return focusMembersGridRowByIndex(rows.length - 1, { scroll: 'bottom' });
    }

    if (event.key === 'PageDown') {
      event.preventDefault();
      return focusMembersGridPagedRow(row, 1);
    }

    if (event.key === 'PageUp') {
      event.preventDefault();
      return focusMembersGridPagedRow(row, -1);
    }

    if (event.key === ' ' || event.key === 'Space' || event.key === 'Spacebar' || event.code === 'Space') {
      event.preventDefault();
      return toggleMembersGridRowSelection(row);
    }

    if (event.key === 'Enter') {
      event.preventDefault();
      activateMemberReportsFromRow(row, row);
      return true;
    }

    return false;
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
      syncMembersGridKeyboardRows();

      container.addEventListener('change', (event) => {
        const checkbox = event.target instanceof Element
          ? event.target.closest('.business_members_row_checkbox')
          : null;
        if (checkbox instanceof HTMLInputElement && container.contains(checkbox)) {
          handleMembersBulkCheckboxChange(checkbox);
        }

        const selectAllCheckbox = event.target instanceof Element
          ? event.target.closest('.business_members_select_all_checkbox')
          : null;
        if (selectAllCheckbox instanceof HTMLInputElement && container.contains(selectAllCheckbox)) {
          toggleAllVisibleBusinessMembers(selectAllCheckbox.checked);
        }
      });

      container.addEventListener('focusin', (event) => {
        const row = resolveMembersGridRowFromTarget(event.target);
        if (row instanceof HTMLElement && container.contains(row)) {
          syncMembersGridKeyboardRows(row);
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
        } else if (isOpen) {
          focusMembersGridSearchSoon(false);
        }
        return;
      }

      const rowMenuItem = event.target.closest('.business_member_row_menu_item');
      if (rowMenuItem instanceof HTMLElement && container.contains(rowMenuItem)) {
        event.preventDefault();
        event.stopPropagation();
        const row = rowMenuItem.closest('.datagrid_row');
        const menu = rowMenuItem.closest('.business_member_row_menu');
        const action = String(rowMenuItem.dataset.memberAction || '').trim();
        if (!rowMenuItem.disabled) {
          handleMemberRowMenuAction(rowMenuItem, row);
        }
        if (action === 'add-to-group' || action === 'edit-role') {
          return;
        }
        if (menu instanceof HTMLElement) {
          setMemberRowMenuOpen(menu, false);
        }
        closeAllMemberRowMenus();
        return;
      }

      const groupMenuItem = event.target.closest('.business_member_group_menu_item');
      if (groupMenuItem instanceof HTMLElement && container.contains(groupMenuItem)) {
        event.preventDefault();
        event.stopPropagation();
        const businessId = String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
        const groupId = String(groupMenuItem.dataset.groupId || '').trim();
        const memberUuid = String(groupMenuItem.dataset.memberId || '').trim();
        const groupName = memberGroupNameFromMenuItem(groupMenuItem);
        if (businessId === '' || groupId === '' || memberUuid === '') {
          return;
        }

        groupMenuItem.setAttribute('aria-disabled', 'true');
        postForm(`/api/v1/businesses/${encodeURIComponent(businessId)}/groups/${encodeURIComponent(groupId)}/members`, {
          member_uuids: [memberUuid],
        }).then(() => {
          memberGroupOptionsCache = null;
          closeAllMemberRowMenus();
          focusMembersGridSearchSoon(false);
          PC.showToast(formatMemberGroupToast(T.businessGroupsMemberAddedNamed, groupName), 'success', 3500, true);
        }).catch((error) => {
          PW.error(error);
          groupMenuItem.removeAttribute('aria-disabled');
          PC.showToast(T.businessGroupsAddFailed || 'Unable to add member to group.', 'error', 6000, true);
        });
        return;
      }

      const roleMenuItem = event.target.closest('.business_member_role_menu_item');
      if (roleMenuItem instanceof HTMLElement && container.contains(roleMenuItem)) {
        event.preventDefault();
        event.stopPropagation();
        if (roleMenuItem.hasAttribute('disabled')) {
          return;
        }
        const memberUuid = String(roleMenuItem.dataset.memberId || '').trim();
        const nextRole = String(roleMenuItem.dataset.role || '').trim();
        const menu = roleMenuItem.closest('.business_member_row_menu');
        const trigger = menu?.querySelector('[data-member-action="edit-role"]');
        const previousRole = trigger instanceof HTMLElement
          ? String(trigger.dataset.currentRole || '').trim()
          : '';
        if (!(trigger instanceof HTMLElement) || memberUuid === '' || nextRole === '') {
          return;
        }

        roleMenuItem.setAttribute('aria-disabled', 'true');
        submitMemberRoleChange(trigger, memberUuid, nextRole, previousRole).then(() => {
          closeAllMemberRowMenus();
          focusMembersGridSearchSoon(false);
        }).catch((error) => {
          PW.error(error);
          roleMenuItem.removeAttribute('aria-disabled');
        });
        return;
      }

      if (!(event.target instanceof Element) || !event.target.closest('.business_member_row_menu_panel')) {
        closeAllMemberRowMenus();
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
      if (handleMemberRowMenuKeyboard(event, container)) {
        return;
      }

      if (handleMembersGridSearchKeyboard(event)) {
        return;
      }

      handleMembersGridRowKeyboard(event, container);
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

  const canFocusMembersGridSearchOnInitialLoad = (searchInput = null) => {
    const active = document.activeElement;
    if (active instanceof HTMLElement && active !== document.body && active !== document.documentElement) {
      if (searchInput instanceof HTMLInputElement && active === searchInput) {
        return true;
      }

      if (active.closest('[data-grid="business-members"]')) {
        return true;
      }

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
      const searchInput = getMembersGridSearchInput();
      if (!(searchInput instanceof HTMLInputElement)) {
        return;
      }

      if (!canFocusMembersGridSearchOnInitialLoad(searchInput)) {
        return;
      }

      focusMembersGridSearch(false);
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

    const searchInput = getMembersGridSearchInput();
    if (!(searchInput instanceof HTMLInputElement)) {
      return;
    }

    const target = event.target instanceof Element ? event.target : null;
    if (target === searchInput) {
      return;
    }

    if (isMembersSearchShortcutBlockedTarget(target)) {
      return;
    }

    event.preventDefault();
    focusMembersGridSearch(true);
  };

  const bindBusinessMembersInfoDialog = () => {
    syncMembersGridElementRefs();
    const dialog = elements.membersInfoDialog;
    if (!(dialog instanceof HTMLDialogElement)) {
      return;
    }

    if (dialog.dataset.membersInfoCloseBound === '1') {
      return;
    }

    dialog.dataset.membersInfoCloseBound = '1';
    dialog.addEventListener('close', () => {
      focusMembersGridSearchSoon(false);
    });
  };

  if (isBusinessMembersSubPage()) {
    bindBusinessMembersGridInteractions();
    bindMembersBulkToolbar();
    bindMembersPendingAccordion();
    bindBusinessMembersInfoDialog();
    integrateMembersToolbarLayout();

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
      if (!business) {
        setDatagridMessage(elements.membersGridContainer, ACCESS_MANAGE_WARNING);
        announceMembersGridStatus(ACCESS_MANAGE_WARNING);
        return;
      }

      if (!canUsePremiumOrgFeatures(business)) {
        setDatagridMessage(elements.membersGridContainer, T.premiumAdminLockedDetailed);
        announceMembersGridStatus(T.premiumAdminLockedDetailed);
        return;
      }

      state.selectedBusinessId = orgId;

      if (!canManageBusinessAccess(business)) {
        setDatagridMessage(elements.membersGridContainer, ACCESS_MANAGE_WARNING);
        announceMembersGridStatus(ACCESS_MANAGE_WARNING);
        closeMemberRolePopover({ restoreFocus: false });
        await loadBusinessMembersPendingList(orgId);
        return;
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
