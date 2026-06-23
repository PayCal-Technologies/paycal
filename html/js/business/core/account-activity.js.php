<?php namespace PayCal\Domain; ?>

  const createAccountActivityTimestampField = (unixSeconds, idSeed) => {
    const value = Number(unixSeconds || 0);
    if (!Number.isFinite(value) || value <= 0) {
      const fallback = document.createElement('span');
      fallback.textContent = T.unknown;
      return fallback;
    }

    const parsedDate = new Date(value * 1000);
    if (Number.isNaN(parsedDate.getTime())) {
      const fallback = document.createElement('span');
      fallback.textContent = T.unknown;
      return fallback;
    }

    const safeIdSeed = String(idSeed || 'activity')
      .replace(/[^a-zA-Z0-9_-]/g, '_')
      .slice(0, 64);
    const popoverId = `account_activity_timestamp_popover_${safeIdSeed}`;

    const field = document.createElement('span');
    field.className = 'account_activity_timestamp_field';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'account_activity_timestamp_trigger';
    trigger.textContent = formatTimestampInTimeZone(parsedDate, viewerTimeZone);
    trigger.setAttribute('aria-haspopup', 'dialog');
    trigger.setAttribute('aria-controls', popoverId);
    trigger.setAttribute('aria-expanded', 'false');

    const popover = document.createElement('div');
    popover.id = popoverId;
    popover.className = 'account_activity_timestamp_popover';
    popover.hidden = true;
    popover.setAttribute('role', 'dialog');
    popover.setAttribute('aria-label', T.timestampDetailsAria);

    buildTimestampZoneRows(parsedDate).forEach((row) => {
      const rowEl = document.createElement('span');
      rowEl.className = 'account_activity_timestamp_popover_row';

      const labelEl = document.createElement('span');
      labelEl.className = 'account_activity_timestamp_popover_label';
      labelEl.textContent = `${row.label}:`;

      const valueEl = document.createElement('span');
      valueEl.className = 'account_activity_timestamp_popover_value';
      valueEl.textContent = row.value;

      rowEl.appendChild(labelEl);
      rowEl.appendChild(valueEl);
      popover.appendChild(rowEl);
    });

    field.appendChild(trigger);
    field.appendChild(popover);

    if (elements.accountActivityPanel instanceof HTMLElement) {
      bindHistoryTimestampPopover(elements.accountActivityPanel, trigger, popover);
    }

    return field;
  };

  const appendAccountActivityMetaSegment = (target, label, valueNodeOrText, options = {}) => {
    if (!(target instanceof HTMLElement)) {
      return;
    }

    const segment = document.createElement('div');
    segment.className = 'account_activity_session_meta_segment item_pair';

    const labelEl = document.createElement('span');
    labelEl.className = 'account_activity_session_meta_label item_label';
    labelEl.textContent = String(label || '');
    segment.appendChild(labelEl);

    const valueEl = document.createElement('span');
    valueEl.className = 'account_activity_session_meta_value item_value';
    if (valueNodeOrText instanceof Node) {
      valueEl.appendChild(valueNodeOrText);
    } else {
      const value = String(valueNodeOrText || T.unknown);
      valueEl.textContent = options.titleCase === true ? titleCaseActivityValue(value) : value;
    }
    segment.appendChild(valueEl);
    target.appendChild(segment);
  };

  const titleCaseActivityValue = (value) => String(value || '')
    .replace(/[_-]+/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .replace(/\b([a-z])([a-z0-9']*)/gi, (_match, first, rest) => `${String(first).toUpperCase()}${String(rest).toLowerCase()}`);

  const renderActivitySessions = (sessions, currentLogin = {}, browser = {}) => {
    if (!(elements.accountActivitySessions instanceof HTMLElement)) {
      return;
    }

    elements.accountActivitySessions.textContent = '';
    const sessionRows = Array.isArray(sessions) ? sessions.slice() : [];
    const currentFingerprint = String(currentLogin?.session_fingerprint || '');
    const hasCurrentLogin = currentFingerprint !== ''
      || String(currentLogin?.ip_address || '') !== ''
      || Number(currentLogin?.signed_in_at || 0) > 0;

    if (sessionRows.length === 0 && hasCurrentLogin) {
      sessionRows.push({
        is_current: true,
        session_fingerprint: currentFingerprint,
        last_ip: currentLogin?.ip_address,
        created_at: currentLogin?.signed_in_at,
        last_activity: currentLogin?.last_activity_at,
        auth_method: currentLogin?.auth_method,
        ttl_seconds: currentLogin?.ttl_seconds,
      });
    }

    if (sessionRows.length === 0) {
      const empty = document.createElement('p');
      empty.className = 'help_text';
      empty.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_NO_ACTIVE_SESSIONS')); ?>';
      elements.accountActivitySessions.appendChild(empty);
      return;
    }

    sessionRows.forEach((session, index) => {
      const item = document.createElement('article');
      item.className = 'account_activity_session_item';
      item.setAttribute('role', 'listitem');

      const fingerprint = String(session?.session_fingerprint || currentFingerprint || T.valueUnknown);
      const isCurrentSession = session && session.is_current === true;
      const matchesCurrentSession = currentFingerprint !== '' && fingerprint === currentFingerprint;
      const useCurrentLogin = isCurrentSession || matchesCurrentSession;

      if (useCurrentLogin) {
        item.classList.add('account_activity_session_item_current');
      }

      const title = document.createElement('strong');
      title.className = 'account_activity_session_title';
      title.textContent = useCurrentLogin
        ? formatPhpTemplate(T.accountActivityCurrentSession, [fingerprint])
        : formatPhpTemplate(T.accountActivitySession, [fingerprint]);

      const meta = document.createElement('div');
      meta.className = 'account_activity_session_meta';

      const idSeed = fingerprint !== T.valueUnknown ? fingerprint : `session_${index}`;
      const signedInValue = useCurrentLogin
        ? (currentLogin?.signed_in_at || session?.created_at)
        : session?.created_at;
      const lastActivityValue = useCurrentLogin
        ? (currentLogin?.last_activity_at || session?.last_activity)
        : session?.last_activity;
      const ipValue = useCurrentLogin
        ? (currentLogin?.ip_address || session?.last_ip)
        : session?.last_ip;
      const authMethod = useCurrentLogin
        ? (currentLogin?.auth_method || session?.auth_method)
        : session?.auth_method;
      const authStrength = useCurrentLogin ? currentLogin?.auth_strength : '';
      const ttlSeconds = Number(session?.ttl_seconds || currentLogin?.ttl_seconds || 0);
      const browserName = `${String(browser?.browser_name || '').trim()} ${String(browser?.browser_version || '').trim()}`.trim();

      appendAccountActivityMetaSegment(meta, T.accountActivityLastActivity, createAccountActivityTimestampField(lastActivityValue, `${idSeed}_last_activity`));
      appendAccountActivityMetaSegment(meta, T.accountActivitySignedIn, createAccountActivityTimestampField(signedInValue, `${idSeed}_signed_in`));
      appendAccountActivityMetaSegment(meta, T.accountActivityIp, String(ipValue || T.valueUnknown));
      appendAccountActivityMetaSegment(meta, T.accountActivityAuth, String(authMethod || T.valueUnknown), { titleCase: true });
      if (authStrength) {
        appendAccountActivityMetaSegment(meta, T.accountActivityAuthStrength, String(authStrength), { titleCase: true });
      }
      if (ttlSeconds > 0) {
        appendAccountActivityMetaSegment(meta, T.accountActivityTtl, `${String(ttlSeconds)}s`);
      }
      if (useCurrentLogin && browserName !== '') {
        appendAccountActivityMetaSegment(meta, T.accountActivityBrowser, browserName);
      }
      if (useCurrentLogin && String(browser?.os_name || '') !== '') {
        appendAccountActivityMetaSegment(meta, T.accountActivityOperatingSystem, String(browser.os_name));
      }
      if (useCurrentLogin && String(browser?.device_type || '') !== '') {
        appendAccountActivityMetaSegment(meta, T.accountActivityDeviceType, String(browser.device_type), { titleCase: true });
      }

      item.appendChild(title);
      item.appendChild(meta);
      elements.accountActivitySessions.appendChild(item);
    });
  };

  const loadAccountActivity = async () => {
    if (!(elements.accountActivityStatus instanceof HTMLElement)) {
      return;
    }

    elements.accountActivityStatus.hidden = false;
    elements.accountActivityStatus.textContent = '<?php echo addslashes(org_js_index_i18n('BUSINESSES_LOADING_ACCOUNT_ACTIVITY')); ?>';

    try {
      const payload = await apiRequest('/api/v1/user/account/activity');
      const data = payload?.data && typeof payload.data === 'object' ? payload.data : {};
      const currentLogin = data?.current_login && typeof data.current_login === 'object' ? data.current_login : {};
      const browser = data?.browser && typeof data.browser === 'object' ? data.browser : {};
      const sessionData = data?.session_data && typeof data.session_data === 'object' ? data.session_data : {};
      const sessions = Array.isArray(sessionData.sessions) ? sessionData.sessions : [];

      renderActivitySessions(sessions, currentLogin, browser);

      elements.accountActivityStatus.textContent = '';
      elements.accountActivityStatus.hidden = true;
    } catch (error) {
      PW.error(error);
      elements.accountActivityStatus.hidden = false;
      elements.accountActivityStatus.textContent = error instanceof Error
        ? error.message
        : T.accountActivityLoadFailed;
    }
  };
