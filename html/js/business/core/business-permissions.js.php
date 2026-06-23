<?php namespace PayCal\Domain; ?>

  const isPersonalBusiness = (business) => String(business?.business_type || '').toLowerCase() === T.personal;

  const isPersonalBusinessById = (businessId) => {
    const business = findBusiness(businessId);
    return !!business && isPersonalBusiness(business);
  };

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

    const connectionStatus = String(business.connection_status || business.status || '').trim().toLowerCase();
    if (connectionStatus !== 'active') {
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

    const connectionStatus = String(business.connection_status || business.status || '').trim().toLowerCase();
    if (connectionStatus !== 'active') {
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
    setStatusText(elements.auditControlTestStatus, message, { tone });
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
