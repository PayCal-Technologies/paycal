<?php namespace PayCal\Domain; ?>

  const formatConnectionStatusLabel = (status) => {
    const value = String(status || '').trim();
    if (value === '') {
      return '';
    }

    return value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
  };

  const connectionSummaryForStatus = (connectionStatusKey) => {
    if (connectionStatusKey === 'pending') {
      return '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_PENDING_SUMMARY')); ?>';
    }
    if (connectionStatusKey === 'active') {
      return '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_ACTIVE_SUMMARY')); ?>';
    }

    return '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_INACTIVE_SUMMARY')); ?>';
  };

  const renderCurrentBusinessCard = (business) => {
    const role = String(business?.role || 'member').trim() || 'member';
    const connectionStatus = String(business?.connection_status || business?.status || 'active').trim() || 'active';
    const connectionStatusKey = connectionStatus.toLowerCase();
    const businessName = String(business?.name || 'Business').trim() || 'Business';
    const businessId = String(business?.business_id || '').trim();
    const requestedOrJoinedAt = formatBusinessConnectionDate(String(business?.joined_at || '').trim());
    const isPending = connectionStatusKey === 'pending';
    const isOwner = String(business?.role || '').toLowerCase() === 'owner';
    const roleLabel = formatBusinessRoleLabel(role);
    const connectionSummary = connectionSummaryForStatus(connectionStatusKey);
    const dataAccessText = isPending
      ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_DATA_ACCESS_PENDING')); ?>'
      : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_DATA_ACCESS_ACTIVE')); ?>';
    const statusClass = connectionStatusKey === 'active'
      ? 'is-active'
      : connectionStatusKey === 'pending'
        ? 'is-waiting'
        : 'is-unavailable';

    const rows = [
      [isPending ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_ROLE_REQUESTED_LABEL')); ?>' : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ROLE')); ?>', safeText(roleLabel)],
      ['<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_DATA_ACCESS_LABEL')); ?>', safeText(dataAccessText)],
      [isPending ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_REQUESTED_LABEL')); ?>' : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_JOINED_LABEL')); ?>', safeText(requestedOrJoinedAt)],
    ].filter(([, value]) => String(value || '').trim() !== '');

    const leaveButtonText = isPending
      ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_CANCEL_REQUEST_BTN')); ?>'
      : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_REVOKE_ACCESS_BTN')); ?>';
    const leaveButtonClass = isPending ? 'btn btn_secondary' : 'btn btn_delete';

    return `
      <article class="businesses_current_card ${isPending ? 'is-pending' : 'is-active'}" aria-label="${safeText(businessName)}">
        <header class="businesses_current_card_header">
          <div class="businesses_current_card_identity">
            <h3>${safeText(businessName)}</h3>
          </div>
          <span class="businesses_current_status_pill ${statusClass}">${safeText(formatConnectionStatusLabel(connectionStatus))}</span>
        </header>
        <p class="businesses_current_card_summary">${safeText(connectionSummary)}</p>
        <dl class="businesses_current_card_grid">
          ${rows.map(([label, value]) => `<dt>${safeText(label)}</dt><dd>${String(value || '')}</dd>`).join('')}
        </dl>
        <footer class="businesses_current_card_footer">
          <button type="button" class="${leaveButtonClass} businesses_current_leave_btn" data-current-business-action="leave" data-business-id="${safeText(businessId)}"${isOwner || businessId === '' ? ' disabled aria-disabled="true"' : ''}>${safeText(leaveButtonText)}</button>
          <button type="button" class="btn btn_secondary businesses_current_profile_btn" data-current-business-action="view-profile" data-business-id="${safeText(businessId)}"><?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_VIEW_PROFILE_BTN')); ?></button>
        </footer>
      </article>
    `;
  };

  const renderCurrentBusinessMeta = (businesses) => {
    if (!(elements.currentMeta instanceof HTMLElement)) {
      return;
    }

    const rows = Array.isArray(businesses) ? businesses : [businesses].filter(Boolean);
    Guardian.setHTML(elements.currentMeta, `
      <div class="businesses_current_card_list">
        ${rows.map(renderCurrentBusinessCard).join('')}
      </div>
    `);
  };

  const renderCurrentBusinessPanel = () => {
    if (!(elements.currentPanel instanceof HTMLElement)) {
      return;
    }

    const businesses = getCurrentConnectionBusinesses();
    if (businesses.length === 0) {
      state.currentConnectionBusinessId = '';
      elements.currentPanel.classList.add('hidden');
      if (elements.freeAuditPanel instanceof HTMLElement) {
        elements.freeAuditPanel.classList.add('hidden');
      }
      if (elements.freeAuditGridContainer instanceof HTMLElement) {
        setDatagridMessage(elements.freeAuditGridContainer, '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_CURRENT_CONNECTION')); ?>');
      }
      announceFreeAuditStatus('<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_CURRENT_SELECTED_AUDIT')); ?>');
      return;
    }

    const activeBusiness = businesses.find((candidate) => String(candidate.connection_status || '').toLowerCase() === 'active') || null;
    const primaryBusiness = activeBusiness || businesses[0];
    state.currentConnectionBusinessId = String(primaryBusiness.business_id || '');
    elements.currentPanel.classList.remove('hidden');

    renderCurrentBusinessMeta(businesses);
    setCurrentBusinessStatus('');

    if (!activeBusiness) {
      if (elements.freeAuditPanel instanceof HTMLElement) {
        elements.freeAuditPanel.classList.add('hidden');
      }
      return;
    }

    state.currentConnectionBusinessId = String(activeBusiness.business_id || '');

    if (elements.freeAuditPanel instanceof HTMLElement) {
      elements.freeAuditPanel.classList.remove('hidden');
    }

    loadFreeProfileAudit(state.currentConnectionBusinessId).catch((error) => PW.error(error));
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
    const rows = [
      { label: 'Business', value: String(business?.name || '') },
      { label: 'Connection', value: formatBusinessRoleLabel(String(business?.role || '')) },
      {
        label: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONNECTION_STATUS_LABEL')); ?>',
        value: formatConnectionStatusLabel(String(business?.connection_status || business?.status || '')),
      },
      { label: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_OWNER_NAME_LABEL')); ?>', value: String(business?.owner_name || '') },
      { label: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_OWNER_EMAIL_LABEL')); ?>', value: String(business?.owner_email || '') },
      { label: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_EMAIL')); ?>', value: String(business?.contact_email || '') },
      { label: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CONTACT_PHONE')); ?>', value: formatPhoneDisplayValue(String(business?.contact_phone || '')) },
      { label: 'Industry', value: String(business?.industry || '') },
      { label: 'Website', value: String(business?.website || '') },
      { label: '<?php echo addslashes(org_js_index_i18n('ADDRESS')); ?>', value: addressMarkup, html: true },
      { label: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SUPPORT_HOURS')); ?>', value: String(business?.support_hours || '') },
      { label: '<?php echo addslashes(org_js_index_i18n('BUSINESSES_EMPLOYEE_COUNT')); ?>', value: String(business?.employee_count || '') },
    ].filter((row) => String(row.value || '').trim() !== '');

    Guardian.setHTML(elements.currentDetailsBody, `
      <dl class="businesses_current_details_grid">
        ${rows.map((row) => `<dt>${safeText(row.label)}</dt><dd>${row.html === true ? String(row.value || '') : safeText(row.value)}</dd>`).join('')}
      </dl>
      <p class="help_text"><?php echo addslashes(org_js_index_i18n('BUSINESSES_REVOKE_DETAILS_HELP')); ?></p>
    `);
  };

  const openCurrentBusinessDetailsDialog = () => {
    const business = getCurrentConnectionBusiness();
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

  const handleRevokeCurrentBusinessAccess = async (businessIdOverride = '') => {
    const requestedBusinessId = String(businessIdOverride || '').trim();
    const business = requestedBusinessId === ''
      ? getCurrentConnectionBusiness()
      : (findBusiness(requestedBusinessId) || getCurrentConnectionBusiness());
    const businessId = String(business?.business_id || '');
    if (businessId === '') {
      return;
    }
    const isPending = String(business?.connection_status || business?.status || '').trim().toLowerCase() === 'pending';

    const confirmed = window.confirm(isPending
      ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_CANCEL_REQUEST_CONFIRM')); ?>'
      : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_REVOKE_CONFIRM')); ?>');
    if (!confirmed) {
      return;
    }

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(businessId)}/leave`, {});
      const message = isPending
        ? '<?php echo addslashes(org_js_index_i18n('BUSINESSES_CURRENT_CANCEL_REQUEST_STATUS')); ?>'
        : '<?php echo addslashes(org_js_index_i18n('BUSINESSES_ACCESS_REVOKED_STATUS')); ?>';
      setCurrentBusinessStatus(message);
      PC.showToast(isPending ? message : T.withdrawn, 'save', 6000, true);
      closeCurrentBusinessDetailsDialog();
      await refreshIndex();
    } catch (error) {
      PW.error(error);
      const message = error instanceof Error && error.message ? error.message : T.withdrawFailed;
      setCurrentBusinessStatus(message);
      PC.showToast(message, 'error', 7000, true);
    }
  };
