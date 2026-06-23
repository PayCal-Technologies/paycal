
  // Subpage module: audit (data-business-subpage="audit")
  // Entry: openAuditPage, loadBusinessAudit, realtime audit polling

  const openAuditPage = async (businessId) => {
    if (resolveBusinessSubPage() !== 'audit') {
      return;
    }

    const business = findBusiness(businessId);
    if (!business) {
      announceAuditStatus(T.auditNoWorkspace);
      return;
    }

    stopDiscoveryPolling();
    state.selectedBusinessId = businessId;
    setEditorMeta(business);
    closeContactImagePopover();

    markBusinessNotificationsRead(businessId).catch(() => {});

    if (!(elements.auditGridContainer instanceof HTMLElement)) {
      return;
    }

    const premiumLocked = !canUsePremiumOrgFeatures(business);
    if (premiumLocked) {
      setDatagridMessage(elements.auditGridContainer, T.premiumAdminLockedDetailed);
      announceAuditStatus(T.premiumAdminLockedDetailed);
      stopRealtimeAuditPolling();
      return;
    }

    setDatagridMessage(elements.auditGridContainer, T.loading, true);
    announceAuditStatus(T.auditLoading);

    try {
      await loadBusinessAudit(businessId);
      startRealtimeAuditPolling(businessId);
    } catch (error) {
      PW.error(error);
      PC.showToast(error instanceof Error && error.message ? error.message : T.loadAuditFailed, 'error', 7000, true);
    }
  };
