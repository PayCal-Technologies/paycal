  // Core module: member revoke and member report dialog actions

  const loadMembers = async () => {
    await loadBusinessMembersGrid(resolveWorkspaceBusinessId());
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

  const formatMemberNamedMessage = (template, memberName) => {
    const name = String(memberName || '').trim() || T.unknown;
    return formatPhpTemplate(template, [name]);
  };

  const getMemberRevokeDialogElements = () => ({
    dialog: document.getElementById('businesses_member_revoke_dialog'),
    form: document.getElementById('businesses_member_revoke_form'),
    message: document.getElementById('businesses_member_revoke_dialog_message'),
    confirmButton: document.getElementById('businesses_member_revoke_confirm'),
    cancelButton: document.getElementById('businesses_member_revoke_cancel'),
  });

  const promptMemberRevokeDialog = async (memberUuid, memberName, trigger) => {
    const {
      dialog,
      form,
      message,
      confirmButton,
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
        dialog.removeEventListener('close', onDialogClose);
      };

      form.addEventListener('submit', onSubmit);
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

    await postForm(`/api/v1/businesses/${encodeURIComponent(currentOrgId)}/connections/revoke`, {
      target_user_uuid: memberUuid,
    });

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

    const { dialog: reportsDialog } = getMemberReportsDialogElements();
    if (reportsDialog instanceof HTMLDialogElement && reportsDialog.dataset.memberReportsCloseBound !== '1') {
      reportsDialog.dataset.memberReportsCloseBound = '1';
      reportsDialog.addEventListener('close', () => {
        resetMemberReportsDialogState();
      });
    }

    try {
      const year = new Date().getFullYear();
      const payload = await apiRequest(
        `/api/v1/businesses/${encodeURIComponent(currentOrgId)}/members/${encodeURIComponent(memberUuid)}/reports?year=${encodeURIComponent(String(year))}`,
      );
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
        resolveThrownMessage: PC.resolveThrownMessage,
      });

      announceMembersGridStatus(formatPhpTemplate(T.memberReportsLoadedFor, [resolvedName]));
    } catch (error) {
      debugLog('Error loading member reports:', error);
      const detailMessage = PC.resolveThrownMessage(error, '');
      const failureMessage = detailMessage !== '' && !detailMessage.startsWith('Failed to load member reports:')
        ? detailMessage
        : formatMemberNamedMessage(T.memberReportsLoadFailed, resolvedName);
      PC.setHTML(body, `<p class="form_error">${escapeHtml(failureMessage)}</p>`);
      body.setAttribute('aria-busy', 'false');
      announceMembersGridStatus(failureMessage);
      PC.showToast(failureMessage, 'error', 7000, true);
      resetMemberReportsDialogState();
    }
  };
