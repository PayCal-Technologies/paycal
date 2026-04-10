const DEFAULT_MESSAGES = {
  success: 'Premium is now active.',
  cancel: 'Premium is disabled.',
  confirming: 'Confirming your Premium status...',
  delayed: 'Premium status update is still in flight. Refresh in a moment if it does not appear yet.',
  loadingStatus: 'Checking billing status...',
  offline: 'You appear to be offline. Billing status will refresh when your connection returns.',
  online: 'Connection restored. Billing status refreshed.',
  timeout: 'Billing request timed out. Please try again.',
  checkoutRedirect: 'Enabling Premium...',
  portalRedirect: 'Updating Premium status...',
  checkoutError: 'Unable to enable Premium right now.',
  portalError: 'Unable to update Premium status right now.',
  refreshReady: 'Billing status refreshed.',
  downgradeWorking: 'Disabling Premium...',
  downgradeDone: 'Premium has been disabled and your account is now on Free.',
  downgradeError: 'Unable to disable Premium right now.',
  pendingCancellationConfirm: 'Cancellation is already scheduled. You keep full Premium access until {date}. Continue only if you want to end Premium now and switch to Free immediately.',
};

const ACTIVE_BADGE_LABELS = {
  past_due: 'Past due',
  canceled: 'Cancelled',
  unpaid: 'Unpaid',
  incomplete_expired: 'Expired',
};

const sleep = (delayMs) => new Promise((resolve) => window.setTimeout(resolve, delayMs));

const isRecord = (value) => value !== null && typeof value === 'object' && !Array.isArray(value);

const extractPayloadData = (payload) => {
  if (isRecord(payload?.data)) {
    return payload.data;
  }

  return isRecord(payload) ? payload : {};
};

const extractMessage = (payload, fallback) => {
  if (payload && typeof payload.message === 'string' && payload.message.trim() !== '') {
    return payload.message.trim();
  }

  return fallback;
};

const normalizeSubscription = (payload) => {
  const data = extractPayloadData(payload);
  const rawStatus = data.subscription_status ?? data.status ?? '';
  const normalizedStatus = typeof rawStatus === 'string' ? rawStatus.trim().toLowerCase() : '';
  const cancelDate = typeof data.cancel_date === 'string' ? data.cancel_date : '';
  const isPendingCancellation = Boolean(data.is_pending_cancellation);

  return {
    is_premium: Boolean(data.is_premium),
    is_pending_cancellation: isPendingCancellation,
    subscription_status: normalizedStatus,
    start_date: typeof data.start_date === 'string' ? data.start_date : '',
    renewal_date: typeof data.renewal_date === 'string' ? data.renewal_date : '',
    cancel_date: cancelDate,
    subscription_id: typeof data.subscription_id === 'string' ? data.subscription_id : '',
    raw: data,
  };
};

const replaceSearchParam = (key, value = null) => {
  const params = new URLSearchParams(window.location.search);

  if (value === null) {
    params.delete(key);
  } else {
    params.set(key, value);
  }

  const query = params.toString();
  const nextUrl = `${window.location.pathname}${query ? `?${query}` : ''}${window.location.hash}`;
  window.history.replaceState({}, document.title, nextUrl);
};

const formatStartDate = (value) => {
  if (typeof value !== 'string' || value.trim() === '') {
    return '—';
  }

  try {
    return new Date(value).toLocaleDateString(undefined, {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  } catch {
    return value;
  }
};

const parseBillingDateValue = (value) => {
  if (typeof value !== 'string' || value.trim() === '') {
    return null;
  }

  const trimmed = value.trim();
  const dateOnlyMatch = /^(\d{4})-(\d{2})-(\d{2})$/.exec(trimmed);
  if (dateOnlyMatch) {
    const asUtcDate = new Date(`${trimmed}T00:00:00Z`);
    return Number.isNaN(asUtcDate.getTime()) ? null : asUtcDate;
  }

  const parsed = new Date(trimmed);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
};

const formatDateTimeInTimeZone = (dateValue, timeZone) => {
  if (!(dateValue instanceof Date) || Number.isNaN(dateValue.getTime())) {
    return 'Unavailable';
  }

  const options = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    second: '2-digit',
    timeZoneName: 'short',
  };

  try {
    if (typeof timeZone === 'string' && timeZone.trim() !== '') {
      return new Intl.DateTimeFormat(undefined, {
        ...options,
        timeZone,
      }).format(dateValue);
    }
  } catch {
    // Fall back to viewer locale timezone when an IANA value is unavailable.
  }

  return new Intl.DateTimeFormat(undefined, options).format(dateValue);
};

export const initializeBillingSection = async (options = {}) => {
  const root = options.root instanceof Document || options.root instanceof HTMLElement ? options.root : document;
  const billingPanel = root.querySelector('#panel-billing');
  const messages = { ...DEFAULT_MESSAGES, ...(isRecord(options.messages) ? options.messages : {}) };
  const fetchImpl = typeof options.fetchImpl === 'function' ? options.fetchImpl : window.fetch.bind(window);
  const successUrl = typeof options.successUrl === 'string' ? options.successUrl : '/api/v1/billing/checkout-return';
  const cancelUrl = typeof options.cancelUrl === 'string' ? options.cancelUrl : '/profile/?billing=cancel';
  const returnUrl = typeof options.returnUrl === 'string' ? options.returnUrl : '/profile/#panel-billing';
  const activationPollAttempts = Number.isInteger(options.activationPollAttempts) ? options.activationPollAttempts : 8;
  const activationPollDelayMs = Number.isInteger(options.activationPollDelayMs) ? options.activationPollDelayMs : 1500;
  const requestTimeoutMs = Number.isInteger(options.requestTimeoutMs) ? options.requestTimeoutMs : 7000;
  const statusRetryAttempts = Number.isInteger(options.statusRetryAttempts) ? options.statusRetryAttempts : 2;
  const statusRetryDelayMs = Number.isInteger(options.statusRetryDelayMs) ? options.statusRetryDelayMs : 450;
  const cleanupQueryParam = options.cleanupQueryParam !== false;
  const runtimeOrigin = typeof window !== 'undefined' && typeof window.location?.origin === 'string'
    ? window.location.origin
    : '';
  const billingProvider = billingPanel instanceof HTMLElement
    ? String(billingPanel.dataset.billingProvider || 'public-toggle').trim().toLowerCase()
    : 'public-toggle';
  const isStripeBilling = billingProvider === 'stripe';

  const resolveCallbackUrl = (value) => {
    if (typeof value !== 'string') {
      return value;
    }

    if (value.startsWith('/')) {
      return runtimeOrigin !== '' ? `${runtimeOrigin}${value}` : value;
    }

    return value;
  };

  const checkoutSuccessUrl = resolveCallbackUrl(successUrl);
  const checkoutCancelUrl = resolveCallbackUrl(cancelUrl);
  const portalReturnUrl = resolveCallbackUrl(returnUrl);

  const resolveCsrfToken = () => {
    const candidates = [
      root.querySelector('#settings_csrf_token'),
      root.querySelector('#organizations_csrf_token'),
      document.getElementById('settings_csrf_token'),
      document.getElementById('organizations_csrf_token'),
      root.querySelector('input[name="csrf_token"]'),
      document.querySelector('input[name="csrf_token"]'),
    ];

    for (const candidate of candidates) {
      if (candidate instanceof HTMLInputElement) {
        const value = String(candidate.value || '').trim();
        if (value !== '') {
          return value;
        }
      }
    }

    return '';
  };

  const freeView = root.querySelector('#billing_free_view');
  const premiumView = root.querySelector('#billing_premium_view');
  if (!(freeView instanceof HTMLElement) || !(premiumView instanceof HTMLElement)) {
    return {
      subscription: null,
      refreshSubscription: async () => null,
      setScreenReaderStatus: () => {},
    };
  }

  const srStatus = root.querySelector('#billing_status_sr');
  const upgradeBtn = root.querySelector('#billing_upgrade_btn');
  const upgradeStatus = root.querySelector('#billing_upgrade_status');
  const portalBtn = root.querySelector('#billing_portal_btn');
  const portalStatus = root.querySelector('#billing_portal_status');
  const refreshBtn = root.querySelector('#billing_refresh_btn');
  const refreshBtnPremium = root.querySelector('#billing_refresh_btn_premium');
  const startDateEl = root.querySelector('#billing_start_date');
  const renewalDateEl = root.querySelector('#billing_renewal_date');
  const renewalLineEl = root.querySelector('#billing_renewal_line');
  const cancelDateEl = root.querySelector('#billing_cancel_date');
  const cancelNoticeEl = root.querySelector('#billing_cancel_notice');
  const cancelDateTriggerEl = root.querySelector('#billing_cancel_date_trigger');
  const dateTimePopoverEl = root.querySelector('#billing_datetime_popover');
  const dateTimePopoverRowsEl = root.querySelector('#billing_datetime_popover_rows');
  const downgradeHelpEl = root.querySelector('#billing_downgrade_help');
  const statusBadge = root.querySelector('#billing_plan_status_badge');
  const downgradePhraseInput = root.querySelector('#billing_downgrade_phrase');
  const downgradeConfirmBtn = root.querySelector('#billing_downgrade_confirm');
  const downgradeStatus = root.querySelector('#billing_downgrade_status');

  let subscription = null;
  let isDateTimePopoverOpen = false;

  const viewerTimeZone = (() => {
    try {
      return Intl.DateTimeFormat().resolvedOptions().timeZone || 'Local';
    } catch {
      return 'Local';
    }
  })();

  const accountTimeZone = billingPanel instanceof HTMLElement
    ? String(billingPanel.dataset.accountTimezone || '').trim()
    : '';

  const setDateTimePopoverExpanded = (expanded) => {
    if (cancelDateTriggerEl instanceof HTMLElement) {
      cancelDateTriggerEl.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    }
  };

  const closeDateTimePopover = ({ restoreFocus = false } = {}) => {
    if (!(dateTimePopoverEl instanceof HTMLElement)) {
      return;
    }

    isDateTimePopoverOpen = false;
    dateTimePopoverEl.hidden = true;
    setDateTimePopoverExpanded(false);

    if (restoreFocus && cancelDateTriggerEl instanceof HTMLElement) {
      cancelDateTriggerEl.focus();
    }
  };

  const openDateTimePopover = () => {
    if (!(dateTimePopoverEl instanceof HTMLElement)) {
      return;
    }

    isDateTimePopoverOpen = true;
    dateTimePopoverEl.hidden = false;
    setDateTimePopoverExpanded(true);
  };

  const renderDateTimePopoverRows = (value) => {
    if (!(dateTimePopoverRowsEl instanceof HTMLElement)) {
      return;
    }

    dateTimePopoverRowsEl.textContent = '';

    const parsedDate = parseBillingDateValue(value);
    const rows = [
      {
        label: 'Local (device)',
        value: formatDateTimeInTimeZone(parsedDate, viewerTimeZone),
      },
      {
        label: accountTimeZone !== '' ? `Account (${accountTimeZone})` : 'Account timezone',
        value: formatDateTimeInTimeZone(parsedDate, accountTimeZone || viewerTimeZone),
      },
      {
        label: 'UTC',
        value: formatDateTimeInTimeZone(parsedDate, 'UTC'),
      },
    ];

    rows.forEach((row) => {
      const rowEl = document.createElement('span');
      rowEl.className = 'billing_datetime_popover_row';

      const labelEl = document.createElement('span');
      labelEl.className = 'billing_datetime_popover_label';
      labelEl.textContent = `${row.label}:`;

      const valueEl = document.createElement('span');
      valueEl.className = 'billing_datetime_popover_value';
      valueEl.textContent = row.value;

      rowEl.appendChild(labelEl);
      rowEl.appendChild(valueEl);
      dateTimePopoverRowsEl.appendChild(rowEl);
    });
  };

  if (cancelDateTriggerEl instanceof HTMLButtonElement && dateTimePopoverEl instanceof HTMLElement) {
    cancelDateTriggerEl.addEventListener('click', (event) => {
      event.preventDefault();
      if (isDateTimePopoverOpen) {
        closeDateTimePopover({ restoreFocus: false });
      } else {
        openDateTimePopover();
      }
    });

    cancelDateTriggerEl.addEventListener('mouseenter', openDateTimePopover);
    cancelDateTriggerEl.addEventListener('focus', openDateTimePopover);

    cancelDateTriggerEl.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        if (isDateTimePopoverOpen) {
          closeDateTimePopover({ restoreFocus: false });
        } else {
          openDateTimePopover();
        }
      } else if (event.key === 'Escape') {
        event.preventDefault();
        closeDateTimePopover({ restoreFocus: true });
      }
    });

    cancelDateTriggerEl.addEventListener('mouseleave', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && dateTimePopoverEl.contains(nextTarget)) {
        return;
      }
      closeDateTimePopover({ restoreFocus: false });
    });

    cancelDateTriggerEl.addEventListener('focusout', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && dateTimePopoverEl.contains(nextTarget)) {
        return;
      }
      closeDateTimePopover({ restoreFocus: false });
    });

    dateTimePopoverEl.addEventListener('mouseleave', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && cancelDateTriggerEl.contains(nextTarget)) {
        return;
      }
      closeDateTimePopover({ restoreFocus: false });
    });

    dateTimePopoverEl.addEventListener('focusout', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && (cancelDateTriggerEl.contains(nextTarget) || dateTimePopoverEl.contains(nextTarget))) {
        return;
      }
      closeDateTimePopover({ restoreFocus: false });
    });

    document.addEventListener('pointerdown', (event) => {
      if (!isDateTimePopoverOpen) {
        return;
      }

      const target = event.target;
      if (!(target instanceof Node)) {
        return;
      }

      if (cancelDateTriggerEl.contains(target) || dateTimePopoverEl.contains(target)) {
        return;
      }

      closeDateTimePopover({ restoreFocus: false });
    });

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape' || !isDateTimePopoverOpen) {
        return;
      }

      closeDateTimePopover({ restoreFocus: true });
    });

    closeDateTimePopover({ restoreFocus: false });
  }

  const setScreenReaderStatus = (message) => {
    if (srStatus instanceof HTMLElement) {
      srStatus.textContent = message;
    }
  };

  const setInlineStatus = (element, message) => {
    if (element instanceof HTMLElement) {
      element.textContent = message;
    }
  };

  const setBillingState = (isPremium) => {
    freeView.hidden = Boolean(isPremium);
    premiumView.hidden = !isPremium;
  };

  const renderStatusBadge = (status) => {
    if (!(statusBadge instanceof HTMLElement)) {
      return;
    }

    const label = ACTIVE_BADGE_LABELS[status] || '';
    if (label === '') {
      statusBadge.hidden = true;
      statusBadge.textContent = '';
      statusBadge.className = 'badge';
      return;
    }

    statusBadge.hidden = false;
    statusBadge.textContent = label;
    statusBadge.className = 'badge badge_' + status.replace(/_/g, '-');
  };

  const renderSubscription = (nextSubscription) => {
    setBillingState(Boolean(nextSubscription?.is_premium));

    if (billingPanel instanceof HTMLElement) {
      billingPanel.setAttribute('data-billing-hydrated', 'true');
    }

    if (startDateEl instanceof HTMLElement) {
      startDateEl.textContent = nextSubscription?.is_premium ? formatStartDate(nextSubscription.start_date) : '—';
    }

    if (renewalDateEl instanceof HTMLElement && renewalLineEl instanceof HTMLElement) {
      if (nextSubscription?.is_premium && nextSubscription?.renewal_date && !nextSubscription?.is_pending_cancellation) {
        renewalDateEl.textContent = formatStartDate(nextSubscription.renewal_date);
        renewalLineEl.hidden = false;
      } else {
        renewalLineEl.hidden = true;
        renewalDateEl.textContent = '—';
      }
    }

    // Show pending cancellation notice if subscription will cancel at period end
    if (cancelNoticeEl instanceof HTMLElement && cancelDateEl instanceof HTMLElement) {
      if (nextSubscription?.is_premium && nextSubscription?.is_pending_cancellation && nextSubscription?.cancel_date) {
        cancelDateEl.textContent = formatStartDate(nextSubscription.cancel_date);
        renderDateTimePopoverRows(nextSubscription.cancel_date);
        cancelNoticeEl.hidden = false;
      } else {
        cancelNoticeEl.hidden = true;
        cancelDateEl.textContent = '—';
        renderDateTimePopoverRows('');
        closeDateTimePopover({ restoreFocus: false });
      }
    }

    // Update downgrade help text based on pending cancellation state
    if (downgradeHelpEl instanceof HTMLElement) {
      if (nextSubscription?.is_premium && nextSubscription?.is_pending_cancellation) {
        downgradeHelpEl.textContent = 'Cancellation is already scheduled. Premium remains active until the end date shown above. Use the action below only to end Premium immediately.';
      } else {
        downgradeHelpEl.textContent = 'Use Stripe to manage renewal timing. Use this action only if you want to cancel now and switch to Free immediately.';
      }
    }

    renderStatusBadge(nextSubscription?.subscription_status || '');
  };

  const notifySubscriptionChange = (nextSubscription) => {
    if (typeof options.onSubscriptionChange === 'function') {
      options.onSubscriptionChange(nextSubscription);
    }
  };

  const announcePremiumActivation = (nextSubscription) => {
    window.dispatchEvent(new CustomEvent('paycal:billing-premium-activated', {
      detail: { subscription: nextSubscription },
    }));

    if (typeof options.onPremiumActivated === 'function') {
      options.onPremiumActivated(nextSubscription);
    }
  };

  const announceSubscriptionError = (error) => {
    window.dispatchEvent(new CustomEvent('paycal:billing-subscription-error', {
      detail: { error },
    }));

    if (typeof options.onSubscriptionError === 'function') {
      options.onSubscriptionError(error);
    }
  };

  const createTimedAbortController = (timeoutMs) => {
    const controller = new AbortController();
    const timerId = window.setTimeout(() => {
      controller.abort();
    }, timeoutMs);

    return {
      controller,
      clear: () => window.clearTimeout(timerId),
    };
  };

  const fetchJson = async (url, init = {}, config = {}) => {
    const retries = Number.isInteger(config.retries) ? config.retries : 0;
    const retryDelayMs = Number.isInteger(config.retryDelayMs) ? config.retryDelayMs : 0;
    const timeoutMs = Number.isInteger(config.timeoutMs) ? config.timeoutMs : requestTimeoutMs;

    for (let attempt = 0; attempt <= retries; attempt += 1) {
      const timeout = createTimedAbortController(timeoutMs);
      try {
        const response = await fetchImpl(url, {
          ...init,
          signal: timeout.controller.signal,
        });
        timeout.clear();
        const payload = await response.json().catch(() => ({}));
        return { response, payload };
      } catch (error) {
        timeout.clear();

        const timedOut = error instanceof DOMException && error.name === 'AbortError';
        if (timedOut) {
          throw new Error(messages.timeout);
        }

        if (attempt >= retries) {
          throw error;
        }

        if (retryDelayMs > 0) {
          await sleep(retryDelayMs);
        }
      }
    }

    throw new Error(messages.timeout);
  };

  const fetchSubscription = async () => {
    if (typeof navigator !== 'undefined' && navigator.onLine === false) {
      throw new Error(messages.offline);
    }

    const { response, payload } = await fetchJson('/api/v1/billing/subscription', {
      method: 'GET',
      credentials: 'same-origin',
      headers: { Accept: 'application/json' },
    }, {
      retries: statusRetryAttempts,
      retryDelayMs: statusRetryDelayMs,
    });

    if (!response.ok || payload?.status !== 'success') {
      throw new Error(extractMessage(payload, 'Unable to load billing status.'));
    }

    return normalizeSubscription(payload);
  };

  const confirmCheckoutSession = async (sessionId) => {
    if (typeof sessionId !== 'string' || sessionId.trim() === '') {
      return false;
    }

    const csrfToken = resolveCsrfToken();

    const { response, payload } = await fetchJson('/api/v1/billing/confirm-checkout', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        session_id: sessionId.trim(),
        csrf_token: csrfToken,
      }),
    }, {
      retries: 0,
    });

    return response.ok && payload?.status === 'success';
  };

  const refreshSubscription = async ({ silent = true } = {}) => {
    try {
      subscription = await fetchSubscription();
      renderSubscription(subscription);
      notifySubscriptionChange(subscription);
      return subscription;
    } catch (error) {
      // Preserve the last known subscription state on transient read failures.
      if (subscription === null) {
        renderSubscription(null);
        notifySubscriptionChange(null);
      } else {
        renderSubscription(subscription);
        notifySubscriptionChange(subscription);
      }
      announceSubscriptionError(error);
      if (!silent) {
        setScreenReaderStatus(error instanceof Error ? error.message : 'Unable to load billing status.');
      }
      return subscription;
    }
  };

  const waitForPremiumActivation = async () => {
    for (let attempt = 0; attempt < activationPollAttempts; attempt += 1) {
      const nextSubscription = attempt === 0 && subscription !== null
        ? subscription
        : await refreshSubscription({ silent: true });

      if (nextSubscription?.is_premium) {
        return nextSubscription;
      }

      if (attempt < activationPollAttempts - 1) {
        await sleep(activationPollDelayMs);
      }
    }

    return subscription;
  };

  setBillingState(false);
  setScreenReaderStatus(messages.loadingStatus);
  await refreshSubscription({ silent: true });

  if (typeof navigator !== 'undefined' && navigator.onLine === false) {
    setScreenReaderStatus(messages.offline);
  }

  window.addEventListener('online', () => {
    void refreshSubscription({ silent: true }).then(() => {
      setScreenReaderStatus(messages.online);
    });
  });

  window.addEventListener('offline', () => {
    setScreenReaderStatus(messages.offline);
  });

  const billingQuery = new URLSearchParams(window.location.search).get('billing');
  if (billingQuery === 'success') {
    const successParams = new URLSearchParams(window.location.search);
    const checkoutSessionId = successParams.get('session_id');

    if (checkoutSessionId) {
      try {
        const confirmed = await confirmCheckoutSession(checkoutSessionId);
        if (confirmed) {
          await refreshSubscription({ silent: true });
        }
      } catch {
        // Webhook synchronization can still update status shortly after return.
      }
    }

    if (subscription?.is_premium) {
      setScreenReaderStatus(messages.success);
      announcePremiumActivation(subscription);
      if (cleanupQueryParam) {
        replaceSearchParam('billing', null);
        replaceSearchParam('session_id', null);
      }
    } else {
      setScreenReaderStatus(messages.confirming);
      const confirmedSubscription = await waitForPremiumActivation();
      if (confirmedSubscription?.is_premium) {
        setScreenReaderStatus(messages.success);
        announcePremiumActivation(confirmedSubscription);
      } else {
        setScreenReaderStatus(messages.delayed);
      }

      if (cleanupQueryParam) {
        replaceSearchParam('billing', null);
        replaceSearchParam('session_id', null);
      }
    }
  } else if (billingQuery === 'cancel') {
    setScreenReaderStatus(messages.cancel);
    if (cleanupQueryParam) {
      replaceSearchParam('billing', null);
    }
  }

  if (upgradeBtn instanceof HTMLButtonElement) {
    upgradeBtn.addEventListener('click', async () => {
      upgradeBtn.disabled = true;
      setInlineStatus(upgradeStatus, messages.checkoutRedirect);

      try {
        const csrfToken = resolveCsrfToken();
        const { response, payload } = await fetchJson('/api/v1/billing/checkout-session', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            success_url: checkoutSuccessUrl,
            cancel_url: checkoutCancelUrl,
            csrf_token: csrfToken,
          }),
        }, {
          retries: 0,
        });
        const data = extractPayloadData(payload);

        if (response.ok && payload?.status === 'success') {
          if (isStripeBilling) {
            const checkoutUrl = typeof data.checkout_url === 'string' ? data.checkout_url : '';
            if (checkoutUrl !== '') {
              window.location.href = checkoutUrl;
              return;
            }
          }

          await refreshSubscription({ silent: false });
          setInlineStatus(upgradeStatus, messages.success);
          setScreenReaderStatus(messages.success);
          if (subscription?.is_premium) {
            announcePremiumActivation(subscription);
          }
          upgradeBtn.disabled = false;
          return;
        }

        throw new Error(extractMessage(payload, messages.checkoutError));
      } catch (error) {
        upgradeBtn.disabled = false;
        setInlineStatus(upgradeStatus, error instanceof Error ? error.message : messages.checkoutError);
      }
    });
  }

  if (portalBtn instanceof HTMLButtonElement) {
    portalBtn.addEventListener('click', async () => {
      portalBtn.disabled = true;
      setInlineStatus(portalStatus, messages.portalRedirect);

      try {
        const csrfToken = resolveCsrfToken();
        const endpoint = isStripeBilling ? '/api/v1/billing/portal-session' : '/api/v1/billing/cancel-subscription';
        const body = isStripeBilling
          ? {
              return_url: portalReturnUrl,
              csrf_token: csrfToken,
            }
          : {
              csrf_token: csrfToken,
            };

        const { response, payload } = await fetchJson(endpoint, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(body),
        }, {
          retries: 0,
        });
        const data = extractPayloadData(payload);

        if (response.ok && payload?.status === 'success') {
          if (isStripeBilling) {
            const portalUrl = typeof data.portal_url === 'string' ? data.portal_url : '';
            if (portalUrl !== '') {
              window.location.href = portalUrl;
              return;
            }
          }

          await refreshSubscription({ silent: false });
          setInlineStatus(portalStatus, messages.cancel);
          setScreenReaderStatus(messages.cancel);
          portalBtn.disabled = false;
          return;
        }

        throw new Error(extractMessage(payload, messages.portalError));
      } catch (error) {
        portalBtn.disabled = false;
        setInlineStatus(portalStatus, error instanceof Error ? error.message : messages.portalError);
      }
    });
  }

  const bindRefreshButton = (button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    button.addEventListener('click', async () => {
      button.disabled = true;
      setScreenReaderStatus(messages.loadingStatus);
      try {
        await refreshSubscription({ silent: false });
        setScreenReaderStatus(messages.refreshReady);
      } finally {
        button.disabled = false;
      }
    });
  };

  bindRefreshButton(refreshBtn);
  bindRefreshButton(refreshBtnPremium);

  const setDowngradeStatus = (message) => {
    if (downgradeStatus instanceof HTMLElement) {
      downgradeStatus.textContent = message;
    }
  };

  const updateDowngradeConfirmState = () => {
    if (!(downgradePhraseInput instanceof HTMLInputElement) || !(downgradeConfirmBtn instanceof HTMLButtonElement)) {
      return;
    }

    const phrase = String(downgradePhraseInput.value || '').toUpperCase();
    downgradePhraseInput.value = phrase;
    downgradeConfirmBtn.disabled = phrase.trim() !== 'DOWNGRADE ME';
  };

  downgradePhraseInput?.addEventListener('input', updateDowngradeConfirmState);
  updateDowngradeConfirmState();

  if (downgradeConfirmBtn instanceof HTMLButtonElement) {
    downgradeConfirmBtn.addEventListener('click', async () => {
      if (!(downgradePhraseInput instanceof HTMLInputElement)) {
        return;
      }

      const phrase = String(downgradePhraseInput.value || '').trim().toUpperCase();
      if (phrase !== 'DOWNGRADE ME') {
        setDowngradeStatus('Type DOWNGRADE ME exactly to confirm cancellation.');
        downgradePhraseInput.focus();
        downgradePhraseInput.select();
        return;
      }

      // If subscription is already pending cancellation, show end date confirmation
      if (subscription?.is_pending_cancellation && subscription?.cancel_date) {
        const formattedDate = formatStartDate(subscription.cancel_date);
        const confirmMsg = messages.pendingCancellationConfirm.replace('{date}', formattedDate);
        if (!window.confirm(confirmMsg)) {
          return;
        }
      }

      downgradeConfirmBtn.disabled = true;
      setDowngradeStatus(messages.downgradeWorking);

      try {
        const csrfToken = resolveCsrfToken();
        const { response, payload } = await fetchJson('/api/v1/billing/cancel-subscription', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            confirm_phrase: 'DOWNGRADE ME',
            csrf_token: csrfToken,
          }),
        }, {
          retries: 0,
        });

        if (!response.ok || payload?.status !== 'success') {
          throw new Error(extractMessage(payload, messages.downgradeError));
        }

        setDowngradeStatus(messages.downgradeDone);
        setScreenReaderStatus(messages.downgradeDone);
        downgradePhraseInput.value = '';
        await refreshSubscription({ silent: false });
      } catch (error) {
        setDowngradeStatus(error instanceof Error ? error.message : messages.downgradeError);
      } finally {
        updateDowngradeConfirmState();
      }
    });
  }

  return {
    get subscription() {
      return subscription;
    },
    refreshSubscription,
    setScreenReaderStatus,
  };
};