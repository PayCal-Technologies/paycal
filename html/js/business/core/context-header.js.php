
  const syncBusinessWorkspaceElementRefs = () => {
    elements.contextHeaderName = document.getElementById('business_context_name');
    elements.execSummaryHeading = document.getElementById('businesses_exec_summary_heading');
    elements.execSummaryLede = document.getElementById('businesses_exec_summary_lede');
    elements.execSummaryRole = document.getElementById('businesses_exec_role');
    elements.execSummaryStatus = document.getElementById('businesses_exec_status');
    elements.execSummaryIndustry = document.getElementById('businesses_exec_industry');
    elements.execSummaryPending = document.getElementById('businesses_exec_pending');
    elements.execSummaryNotices = document.getElementById('businesses_exec_notices');
  };

  const syncMembersPanelElementRefs = () => {
    elements.connectionsReload = document.getElementById('businesses_connections_reload');
    elements.connectionsList = document.getElementById('businesses_connections_list');
    elements.connectionsStatus = document.getElementById('businesses_connections_sr_status');
    elements.accessRequestsList = document.getElementById('businesses_access_requests_list');
    elements.accessRequestsStatus = document.getElementById('businesses_access_requests_sr_status');
  };

  const updateBusinessContextHeader = (pendingCount = null) => {
    if (!isBusinessWorkspacePage()) {
      return;
    }

    syncBusinessWorkspaceElementRefs();

    const business = resolveControlCenterBusiness();
    if (!business) {
      return;
    }

    if (pendingCount !== null) {
      state.execSummaryPendingCount = Math.max(0, Number(pendingCount || 0));
    }

    const name = decodePossiblyEncodedText(String(business.name || T.businessNameFallback));

    if (elements.contextHeaderName instanceof HTMLElement) {
      elements.contextHeaderName.textContent = name;
    }

    updateBusinessSubNavVisibility(business);
  };
