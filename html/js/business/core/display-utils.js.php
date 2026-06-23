<?php namespace PayCal\Domain; ?>

  const BUSINESS_SCOPE_LABELS = <?php echo json_encode(\PayCal\Domain\Business\BusinessPermissionPresenter::scopeLabels(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
  const BUSINESS_SCOPE_UNKNOWN_LABEL = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SCOPE_UNKNOWN_CUSTOM')); ?>';
  const BUSINESS_SCOPE_EMPTY_LABEL = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_SCOPES_NONE_LISTED')); ?>';
  const BUSINESS_SCOPE_ORDER = Object.keys(BUSINESS_SCOPE_LABELS);

  const normalizeBusinessScopeTokens = (scopes) => {
    const rawScopes = Array.isArray(scopes)
      ? scopes
      : String(scopes || '').split(',');
    const scopeSet = new Set();

    rawScopes.forEach((scopeRaw) => {
      const scope = String(scopeRaw || '').trim().toLowerCase();
      if (scope === '') {
        return;
      }

      if (scope === 'all') {
        scopeSet.clear();
        scopeSet.add('all');
        return;
      }

      if (scopeSet.has('all')) {
        return;
      }

      if (scope === 'work.self.write') {
        scopeSet.add('work.write');
        scopeSet.add('work.scope.self');
        return;
      }

      scopeSet.add(scope);
    });

    const tokens = Array.from(scopeSet);
    if (tokens.includes('all')) {
      return ['all'];
    }

    return tokens.sort((left, right) => {
      const leftRank = BUSINESS_SCOPE_ORDER.indexOf(left);
      const rightRank = BUSINESS_SCOPE_ORDER.indexOf(right);
      const normalizedLeftRank = leftRank === -1 ? Number.MAX_SAFE_INTEGER : leftRank;
      const normalizedRightRank = rightRank === -1 ? Number.MAX_SAFE_INTEGER : rightRank;
      return normalizedLeftRank === normalizedRightRank
        ? left.localeCompare(right)
        : normalizedLeftRank - normalizedRightRank;
    });
  };

  const formatBusinessScopeList = (scopes) => {
    const tokens = normalizeBusinessScopeTokens(scopes);
    if (tokens.length === 0) {
      return BUSINESS_SCOPE_EMPTY_LABEL;
    }

    const labels = tokens.map((scope) => BUSINESS_SCOPE_LABELS[scope] || BUSINESS_SCOPE_UNKNOWN_LABEL);
    return Array.from(new Set(labels)).join('; ');
  };

  const formatBusinessConnectionDate = (rawValue) => {
    const value = String(rawValue || '').trim();
    if (value === '') {
      return '';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return value;
    }

    return new Intl.DateTimeFormat('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric'
    }).format(date);
  };

  const formatBusinessRoleLabel = (role) => {
    const normalizedRole = String(role || '').trim().toLowerCase();
    return T[normalizedRole] || String(role || '').trim() || T.member || 'Member';
  };

  const setStackMessage = (container, message) => {
    if (!container) {
      return;
    }

    container.textContent = message;
    container.classList.add('businesses_empty');
  };

  const buildSkeletonRows = (colCount = 4, rowCount = 4) => {
    const cell = '<span class="sk-line businesses_datagrid_skeleton_cell"></span>';
    const rowClass = colCount === 4
      ? 'businesses_datagrid_skeleton_row'
      : `businesses_datagrid_skeleton_row businesses_datagrid_skeleton_row--${colCount}`;
    const row = `<div class="skeleton ${rowClass}">${cell.repeat(colCount)}</div>`;
    return row.repeat(rowCount);
  };

  const setDatagridMessage = (container, message, isLoading = false) => {
    if (!(container instanceof HTMLElement)) {
      return;
    }

    const body = container.querySelector('.datagrid_body');
    if (!(body instanceof HTMLElement)) {
      return;
    }

    if (isLoading) {
      Guardian.setHTML(body, buildSkeletonRows(4, 4));
    } else {
      Guardian.setHTML(body, `<div class="datagrid_empty">${String(message || '')}</div>`);
    }
  };

  const setDiscoveryPanelStatus = (message) => {
    setStatusText(elements.discoveryPanelStatus, message);
  };
