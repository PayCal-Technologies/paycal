<?php namespace PayCal\Domain; ?>

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
  const memberRolePopoverController = createAnchoredPopoverController({
    clearOnClose: (popover) => {
      popover.replaceChildren();
    },
    onClose: () => {
      openMemberRolePopover = null;
    },
  });

  const getMemberRoleLabel = (role) => {
    const normalized = String(role || '').trim().toLowerCase();
    return MEMBER_ROLE_LABELS[normalized] || normalized;
  };

  const formatMemberRoleUpdatedMessage = (memberName, roleLabel) => {
    const name = String(memberName || '').trim() || T.unknown;
    const role = String(roleLabel || '').trim();
    return formatPhpTemplate(T.memberRoleUpdated || '%s is now %s', [name, role]);
  };

  const formatMemberRoleUpdateFailedMessage = (memberName) => {
    const name = String(memberName || '').trim();
    if (name === '') {
      return T.memberRoleUpdateFailedGeneric || 'Unable to update member role right now.';
    }

    return formatPhpTemplate(T.memberRoleUpdateFailed || "Unable to update %s's role right now.", [name]);
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
    const closed = memberRolePopoverController.close({ restoreFocus });
    if (closed?.trigger instanceof HTMLElement) {
      closed.trigger.removeAttribute('aria-controls');
    }
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

    popover.tabIndex = -1;
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-controls', popover.id);
    openMemberRolePopover = memberRolePopoverController.open(trigger, popover, {
      activeIndex: initialActiveIndex,
      memberUuid,
    });
    if (!openMemberRolePopover) {
      return;
    }
    window.requestAnimationFrame(() => {
      popover.focus();
    });

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

    try {
      await postForm(`/api/v1/businesses/${encodeURIComponent(currentOrgId)}/connections/update-role`, {
        target_user_uuid: memberUuid,
        role: normalizedRole,
      });

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
    bindAnchoredPopoverGlobalDismissals(memberRolePopoverController, 'memberRolePopoverDismissBound', {
      repositionOnResize: true,
      repositionOnScroll: true,
    });
  };
