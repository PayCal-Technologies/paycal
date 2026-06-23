<?php namespace PayCal\Domain; ?>
  // Core module: business access management, invites, requests, and transfer controls

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
      showBusinessesToast(formatPhpTemplate(T.transferMemberChosen, [candidate.displayName]), 'save', 3200, true);
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

  const announceInvitesStatus = (message) => {
    setStatusText(elements.invitesStatus, message);
  };

  const announceAccessRequestsStatus = (message) => {
    setStatusText(elements.accessRequestsStatus, message);
  };

  const announceConnectionsStatus = (message) => {
    setStatusText(elements.connectionsStatus, message);
  };

  const announceLiveRequestsStatus = (message) => {
    setStatusText(elements.liveRequestsStatus, message);
  };

  const renderGovernanceConnectionsList = (connections) => {
    if (!(elements.connectionsList instanceof HTMLElement)) {
      return;
    }

    const rows = (Array.isArray(connections) ? connections : [])
      .map((connection) => {
        const displayName = String(
          connection.display_name
          || connection.name
          || connection.email
          || connection.user_uuid
          || T.unknown,
        ).trim();
        const email = String(connection.email || '').trim();
        const role = String(connection.role || 'member').trim() || 'member';
        const status = String(connection.status || connection.connection_status || T.pending).trim() || T.pending;
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
      setStackMessage(elements.connectionsList, T.governanceConnectionsEmpty);
      announceConnectionsStatus(T.connectionsLoadedNone);
      return;
    }

    elements.connectionsList.classList.remove('businesses_empty');
    Guardian.setHTML(elements.connectionsList, rows.join(''));
    announceConnectionsStatus(formatPhpTemplate(T.connectionsLoadedCount, [
      rows.length,
      rows.length === 1 ? '' : 's',
    ]));
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

    announceInvitesStatus(formatPhpTemplate(T.invitesLoadedCount, [
      invites.length,
      invites.length === 1 ? '' : 's',
    ]));
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
    announceAccessRequestsStatus(formatPhpTemplate(T.accessRequestsLoadedCount, [
      requests.length,
      requests.length === 1 ? '' : 's',
    ]));
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

  const renderConnections = (connections) => {
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
    (Array.isArray(connections) ? connections : []).forEach((connection) => {
      const userUUID = String(connection.user_uuid || connection.uuid || '').trim();
      const role = String(connection.role || 'member').toLowerCase();
      const status = String(connection.status || '').toLowerCase();
      if (userUUID === '' || role === 'owner' || status !== 'active') {
        return;
      }

      const displayName = deriveTransferCandidateDisplay(connection);
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
        email: String(connection.email || '').trim(),
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
        elements.transferNotice.textContent = T.noConnections;
      } else {
        elements.transferNotice.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_TRANSFER_SELECT_MEMBER')); ?>';
      }
    }
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

  const loadBusinessConnections = async (businessId) => {
    const business = findBusiness(businessId);
    const hasGovernanceList = elements.connectionsList instanceof HTMLElement;

    if (business && !canUsePremiumOrgFeatures(business)) {
      renderConnections([]);
      if (hasGovernanceList) {
        renderGovernanceConnectionsList([]);
      }
      if (elements.transferNotice) {
        elements.transferNotice.textContent = T.premiumAdminLockedDetailed;
      }
      return;
    }

    if (business && !canManageBusinessAccess(business)) {
      renderConnections([]);
      if (hasGovernanceList) {
        renderGovernanceConnectionsList([]);
      }
      if (elements.transferNotice) {
        elements.transferNotice.textContent = ACCESS_MANAGE_WARNING;
      }
      if (hasGovernanceList) {
        announceConnectionsStatus(ACCESS_MANAGE_WARNING);
      }
      return;
    }

    try {
      const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/connections`);
      const transferMembers = Array.isArray(payload.members) ? payload.members : (payload.connections || []);
      renderConnections(transferMembers);
      if (hasGovernanceList) {
        renderGovernanceConnectionsList(transferMembers);
      }
    } catch (error) {
      PW.error(error);
      renderConnections([]);
      if (hasGovernanceList) {
        renderGovernanceConnectionsList([]);
      }
      if (elements.transferNotice) {
        elements.transferNotice.textContent = T.manageAccessUnavailable;
      }
      if (hasGovernanceList) {
        announceConnectionsStatus(T.manageAccessUnavailable);
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
        loadBusinessConnections(state.selectedBusinessId),
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
          loadBusinessConnections(orgId),
          loadMembers(),
          loadBusinessInviteHistoryGrid(orgId),
        ]);
      }
    } catch (error) {
      PW.error(error);
      showBusinessesToast(error instanceof Error && error.message ? error.message : T.accessRequestActionFailed, 'error', 7000, true);
    }
  };

  const bindAccessManagementEvents = () => {
    elements.inviteSend?.addEventListener('click', handleSendInvite);
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
        ? String(event.currentTarget.value || '').toUpperCase()
        : '';
      const normalizedTyped = typed
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
  };
