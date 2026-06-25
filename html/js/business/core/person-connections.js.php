<?php namespace PayCal\Domain; ?>

  const setPersonConnectionsStatus = (message) => {
    setStatusText(elements.personConnectionsStatus, message);
  };

  const personConnectionById = (connectionId) => state.personConnections
    .find((connection) => String(connection.connection_id || '') === String(connectionId)) || null;

  const personConnectionDisplayName = (connection) => {
    const isOwner = String(connection?.owner_uuid || '') === currentUserUUID;
    const name = isOwner
      ? String(connection?.target_name || connection?.target_email || '').trim()
      : String(connection?.owner_name || connection?.owner_email || '').trim();

    return name || T.businessMember || 'Person';
  };

  const personConnectionGrantMap = (connection) => {
    const grants = {};
    (Array.isArray(connection?.grants) ? connection.grants : []).forEach((grant) => {
      const capability = String(grant?.capability || '').trim();
      if (capability !== '') {
        grants[capability] = grant;
      }
    });

    return grants;
  };

  const personConnectionHasActiveGrant = (connection, capability) => {
    const grant = personConnectionGrantMap(connection)[String(capability || '').trim()] || {};
    return String(grant.status || '').trim().toLowerCase() === 'active';
  };

  const personConnectionStatusLabel = (connection) => {
    const status = String(connection?.status || '').trim().toLowerCase();
    if (status === 'active') {
      return T.connectionsPersonConnected;
    }
    if (status === 'pending') {
      return T.pending;
    }
    if (status === 'declined') {
      return T.connectionsDecline;
    }

    return formatConnectionStatusLabel(status || T.unknown);
  };

  const personConnectionSummary = (connection) => {
    const status = String(connection?.status || '').trim().toLowerCase();
    const isOwner = String(connection?.owner_uuid || '') === currentUserUUID;
    if (status === 'pending') {
      return isOwner ? T.connectionsPendingOutgoing : T.connectionsPendingIncoming;
    }
    if (status === 'active') {
      return String(connection?.access_summary || '').trim() || T.connectionsNoAccess;
    }
    if (status === 'declined') {
      return T.connectionsPersonDeclinedSummary;
    }

    return T.connectionsPersonRemovedSummary;
  };

  const renderPersonConnectionActions = (connection) => {
    const connectionId = String(connection?.connection_id || '').trim();
    if (connectionId === '') {
      return '';
    }

    const status = String(connection?.status || '').trim().toLowerCase();
    const isOwner = String(connection?.owner_uuid || '') === currentUserUUID;
    if (status === 'pending' && !isOwner) {
      return `
        <button type="button" class="btn btn_primary" data-person-action="approve" data-connection-id="${safeText(connectionId)}">${safeText(T.connectionsApprove)}</button>
        <button type="button" class="btn btn_secondary" data-person-action="decline" data-connection-id="${safeText(connectionId)}">${safeText(T.connectionsDecline)}</button>
      `;
    }
    if (status === 'pending' && isOwner) {
      return `<button type="button" class="btn btn_secondary" data-person-action="cancel" data-connection-id="${safeText(connectionId)}">${safeText(T.connectionsCancelRequest)}</button>`;
    }
    if (status === 'active') {
      const ownerUuid = String(connection?.owner_uuid || '').trim();
      const canViewSharedWork = !isOwner
        && ownerUuid !== ''
        && personConnectionHasActiveGrant(connection, 'calendar_view');
      return `
        ${canViewSharedWork ? `<a class="btn btn_primary" href="/calendar/?user_uuid=${encodeURIComponent(ownerUuid)}">${safeText(T.connectionsPersonViewSharedWork)}</a>` : ''}
        <button type="button" class="btn btn_secondary" data-person-action="manage" data-connection-id="${safeText(connectionId)}">${safeText(T.connectionsManage)}</button>
        <button type="button" class="btn btn_delete" data-person-action="remove" data-connection-id="${safeText(connectionId)}">${safeText(T.connectionsRemove)}</button>
      `;
    }

    return '';
  };

  const renderPersonConnectionCard = (connection) => {
    const connectionId = String(connection?.connection_id || '').trim();
    const status = String(connection?.status || '').trim().toLowerCase();
    const statusClass = status === 'active'
      ? 'is-active'
      : status === 'pending'
        ? 'is-waiting'
        : 'is-unavailable';
    const displayName = personConnectionDisplayName(connection);
    const isOwner = String(connection?.owner_uuid || '') === currentUserUUID;
    const detailLabel = isOwner ? T.connectionsPersonLabel : T.connectionsPersonSharedByLabel;
    const detailValue = isOwner
      ? String(connection?.target_email || '').trim()
      : String(connection?.owner_email || '').trim();
    const createdAt = formatBusinessConnectionDate(String(connection?.created_at || '').trim());
    const rows = [
      [detailLabel, safeText(detailValue)],
      [T.connectionsPersonRequestedLabel, safeText(createdAt)],
    ].filter(([, value]) => String(value || '').trim() !== '');

    return `
      <article class="connections_person_card ${status === 'pending' ? 'is-pending' : ''}" data-connection-id="${safeText(connectionId)}">
        <header class="connections_person_card_header">
          <div>
            <h3>${safeText(displayName)}</h3>
            <p>${safeText(isOwner ? T.connectionsPersonLabel : T.connectionsPersonConnectedToYou)}</p>
          </div>
          <span class="businesses_current_status_pill ${statusClass}">${safeText(personConnectionStatusLabel(connection))}</span>
        </header>
        <p class="connections_person_summary">${safeText(personConnectionSummary(connection))}</p>
        <dl class="connections_person_meta">
          ${rows.map(([label, value]) => `<dt>${safeText(label)}</dt><dd>${String(value || '')}</dd>`).join('')}
        </dl>
        <footer class="connections_person_card_footer">
          ${renderPersonConnectionActions(connection)}
        </footer>
      </article>
    `;
  };

  const renderPersonConnections = () => {
    if (!(elements.personConnectionsList instanceof HTMLElement)) {
      return;
    }

    if (state.personConnections.length === 0) {
      Guardian.setHTML(elements.personConnectionsList, `<p class="datagrid_empty">${safeText(T.connectionsPeopleEmpty)}</p>`);
      return;
    }

    Guardian.setHTML(elements.personConnectionsList, state.personConnections.map(renderPersonConnectionCard).join(''));
  };

  const loadPersonConnections = async () => {
    if (!(elements.personConnectionsList instanceof HTMLElement)) {
      return;
    }

    try {
      const payload = await apiRequest('/api/v1/connections/people');
      state.personConnections = Array.isArray(payload.connections) ? payload.connections : [];
      state.personCapabilities = payload.capabilities && typeof payload.capabilities === 'object' ? payload.capabilities : {};
      renderPersonConnections();
      setPersonConnectionsStatus('');
    } catch (error) {
      PW.error(error);
      state.personConnections = [];
      renderPersonConnections();
      setPersonConnectionsStatus(T.connectionsPeopleLoadFailed);
    }
  };

  const handlePersonConnectionRequest = async (event) => {
    event.preventDefault();
    const email = elements.personConnectionEmail instanceof HTMLInputElement
      ? String(elements.personConnectionEmail.value || '').trim()
      : '';
    if (email === '') {
      setPersonConnectionsStatus(T.connectionsPersonEmailRequired);
      return;
    }

    await postForm('/api/v1/connections/people/request', { target_email: email });
    if (elements.personConnectionEmail instanceof HTMLInputElement) {
      elements.personConnectionEmail.value = '';
    }
    setPersonConnectionsStatus(T.connectionsPersonRequestSent);
    PC.showToast(T.connectionsPersonRequestSent, 'save', 5000, true);
    await loadPersonConnections();
  };

  const renderPersonManageDialog = (connection) => {
    if (!(elements.personManageBody instanceof HTMLElement)) {
      return;
    }

    const displayName = personConnectionDisplayName(connection);
    const isOwner = String(connection?.owner_uuid || '') === currentUserUUID;
    const isActive = String(connection?.status || '').trim().toLowerCase() === 'active';
    const grantMap = personConnectionGrantMap(connection);
    const capabilities = state.personCapabilities && typeof state.personCapabilities === 'object'
      ? state.personCapabilities
      : {};
    const capabilityRows = Object.entries(capabilities).map(([capability, label]) => {
      const grant = grantMap[capability] || {};
      const grantStatus = String(grant.status || '').trim().toLowerCase();
      const checked = grantStatus === 'active' || grantStatus === 'pending';
      const disabled = !isOwner || !isActive;
      const pending = grantStatus === 'pending' && String(grant.effective_at || '').trim() !== '';
      const pendingText = pending
        ? ` <span class="connections_person_grant_note">Available ${safeText(formatBusinessConnectionDate(grant.effective_at))}</span>`
        : '';
      return `
        <label class="connections_person_grant_toggle">
          <input type="checkbox" name="capability" value="${safeText(capability)}" ${checked ? 'checked' : ''} data-original-enabled="${checked ? '1' : '0'}" ${disabled ? 'disabled' : ''}>
          <span>${safeText(String(label || capability))}${pendingText}</span>
        </label>
      `;
    }).join('');

    if (elements.personManageTitle instanceof HTMLElement) {
      elements.personManageTitle.textContent = displayName;
    }

    Guardian.setHTML(elements.personManageBody, `
      <dl class="connections_person_manage_summary">
        <dt>${safeText(T.connectionsPersonConnectionTypeLabel)}</dt><dd>${safeText(T.connectionsPersonLabel)}</dd>
        <dt>${safeText(T.connectionsPersonStatusLabel)}</dt><dd>${safeText(personConnectionStatusLabel(connection))}</dd>
        <dt>${safeText(T.connectionsPersonAccessLabel)}</dt><dd>${safeText(String(connection?.access_summary || T.connectionsNoAccess))}</dd>
      </dl>
      <p class="help_text">${safeText(T.connectionsGrantsHelp)}</p>
      ${!isOwner ? `<p class="help_text">${safeText(T.connectionsPersonOwnerOnly)}</p>` : ''}
      <fieldset class="connections_person_grant_fieldset">
        <legend>${safeText(T.connectionsPersonPermissionsLabel)}</legend>
        ${capabilityRows}
      </fieldset>
    `);
  };

  const openPersonManageDialog = (connectionId) => {
    const connection = personConnectionById(connectionId);
    if (!connection || !(elements.personManageDialog instanceof HTMLDialogElement)) {
      return;
    }

    state.personManageConnectionId = String(connectionId || '');
    renderPersonManageDialog(connection);
    if (!elements.personManageDialog.open) {
      elements.personManageDialog.showModal();
    }
  };

  const closePersonManageDialog = () => {
    if (elements.personManageDialog instanceof HTMLDialogElement && elements.personManageDialog.open) {
      elements.personManageDialog.close();
    }
  };

  if (elements.personManageDialog instanceof HTMLDialogElement && elements.personManageDialog.dataset.personManageCloseBound !== '1') {
    elements.personManageDialog.dataset.personManageCloseBound = '1';
    elements.personManageDialog.addEventListener('close', () => {
      state.personManageConnectionId = '';
    });
  }

  const savePersonManageDialog = async (event) => {
    event.preventDefault();
    const connectionId = String(state.personManageConnectionId || '').trim();
    if (connectionId === '' || !(elements.personManageBody instanceof HTMLElement)) {
      closePersonManageDialog();
      return;
    }

    const toggles = Array.from(elements.personManageBody.querySelectorAll('input[name="capability"]'))
      .filter((input) => input instanceof HTMLInputElement && !input.disabled);

    for (const toggle of toggles) {
      const enabled = toggle.checked;
      const originalEnabled = String(toggle.dataset.originalEnabled || '0') === '1';
      if (enabled === originalEnabled) {
        continue;
      }

      await postForm(`/api/v1/connections/people/${encodeURIComponent(connectionId)}/grants`, {
        capability: toggle.value,
        enabled: enabled ? '1' : '0',
      });
    }

    await loadPersonConnections();
    closePersonManageDialog();
    PC.showToast(T.connectionsPersonSaveSuccess, 'save', 5000, true);
  };

  const handlePersonConnectionAction = async (event) => {
    const target = event.target instanceof Element
      ? event.target.closest('[data-person-action]')
      : null;
    if (!(target instanceof HTMLButtonElement)) {
      return;
    }

    const connectionId = String(target.dataset.connectionId || '').trim();
    const action = String(target.dataset.personAction || '').trim();
    if (connectionId === '') {
      return;
    }

    try {
      if (action === 'manage') {
        openPersonManageDialog(connectionId);
        return;
      }

      if (action === 'approve') {
        await postForm(`/api/v1/connections/people/${encodeURIComponent(connectionId)}/approve`, {});
      } else if (action === 'decline') {
        await postForm(`/api/v1/connections/people/${encodeURIComponent(connectionId)}/revoke`, { status: 'declined' });
      } else if (action === 'cancel' || action === 'remove') {
        await postForm(`/api/v1/connections/people/${encodeURIComponent(connectionId)}/revoke`, { status: 'revoked' });
      }

      await loadPersonConnections();
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message ? error.message : T.connectionsPersonRequestFailed;
      setPersonConnectionsStatus(message);
      PC.showToast(message, 'error', 7000, true);
    }
  };
