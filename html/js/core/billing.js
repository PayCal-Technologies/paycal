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
  planChangeWorking: 'Changing subscription plan...',
  planChangeDone: 'Premium is now active.',
  planChangeError: 'Unable to change subscription plan right now.',
  refreshReady: 'Billing status refreshed.',
  downgradeWorking: 'Disabling Premium...',
  downgradeDone: 'Premium has been disabled and your account is now on Free.',
  downgradeError: 'Unable to disable Premium right now.',
  pendingCancellationConfirm: 'Cancellation is already scheduled. You keep full Premium access until {date}. Continue only if you want to end Premium now and switch to Free immediately.',
  downgradeHelpScheduled: 'Cancellation is already scheduled. Premium remains active until the end date shown above. Use the action below only to end Premium immediately.',
  downgradeHelpDefault: 'Use Stripe to manage renewal timing. Use this action only if you want to cancel now and switch to Free immediately.',
};

const ACTIVE_BADGE_LABELS = {
  past_due: 'Past due',
  canceled: 'Cancelled',
  unpaid: 'Unpaid',
  incomplete_expired: 'Expired',
};

const sleep = (delayMs) => new Promise((resolve) => window.setTimeout(resolve, delayMs));

const animationFrame = () => new Promise((resolve) => {
  if (typeof window.requestAnimationFrame === 'function') {
    window.requestAnimationFrame(resolve);
    return;
  }

  window.setTimeout(resolve, 0);
});

const resolveAudioFeedbackMode = () => {
  const candidates = [
    window.tts?.audio_feedback,
    window.PC?.state?.audio_feedback,
  ];

  const mode = candidates.find((candidate) => typeof candidate === 'string' && candidate.trim() !== '');
  return String(mode || 'all').trim().toLowerCase();
};

let businessUpgradeAudioContext = null;
let businessUpgradeCelebrationActive = false;
let businessUpgradeHighlightTimer = 0;
let businessUpgradeCleanupTimer = 0;
let businessUpgradeStatusResetTimer = 0;

const getBusinessUpgradeAudioContext = async () => {
  if (resolveAudioFeedbackMode() === 'none') {
    return null;
  }

  const AudioContextConstructor = window.AudioContext || window.webkitAudioContext;
  if (typeof AudioContextConstructor !== 'function') {
    return null;
  }

  try {
    if (!businessUpgradeAudioContext || businessUpgradeAudioContext.state === 'closed') {
      businessUpgradeAudioContext = new AudioContextConstructor();
    }

    const audioContext = businessUpgradeAudioContext;
    if (audioContext.state === 'suspended') {
      await audioContext.resume();
    }

    return audioContext;
  } catch {
    return null;
  }
};

const primeBusinessUpgradeAudio = () => {
  void getBusinessUpgradeAudioContext();
};

const playBusinessUpgradeSound = async () => {
  const audioContext = await getBusinessUpgradeAudioContext();
  if (!audioContext || audioContext.state !== 'running') {
    return;
  }

  try {
    const now = audioContext.currentTime;
    const masterGain = audioContext.createGain();
    const configuredVolume = Number(window.tts?.voice_volume ?? 1);
    const volume = Number.isFinite(configuredVolume) ? Math.min(Math.max(configuredVolume, 0), 1) : 1;

    masterGain.gain.setValueAtTime(0.0001, now);
    masterGain.gain.exponentialRampToValueAtTime(0.075 * volume, now + 0.30);
    masterGain.gain.exponentialRampToValueAtTime(0.12 * volume, now + 0.95);
    masterGain.gain.exponentialRampToValueAtTime(0.0001, now + 1.42);
    masterGain.connect(audioContext.destination);

    const noiseBuffer = audioContext.createBuffer(1, Math.floor(audioContext.sampleRate * 1.45), audioContext.sampleRate);
    const noiseData = noiseBuffer.getChannelData(0);
    for (let index = 0; index < noiseData.length; index += 1) {
      noiseData[index] = (Math.random() * 2) - 1;
    }

    const noiseSource = audioContext.createBufferSource();
    const noiseFilter = audioContext.createBiquadFilter();
    const noiseGain = audioContext.createGain();
    noiseSource.buffer = noiseBuffer;
    noiseFilter.type = 'bandpass';
    noiseFilter.frequency.setValueAtTime(240, now);
    noiseFilter.frequency.exponentialRampToValueAtTime(3400, now + 0.95);
    noiseFilter.frequency.exponentialRampToValueAtTime(900, now + 1.42);
    noiseFilter.Q.setValueAtTime(0.7, now);
    noiseGain.gain.setValueAtTime(0.0001, now);
    noiseGain.gain.exponentialRampToValueAtTime(0.72, now + 0.38);
    noiseGain.gain.exponentialRampToValueAtTime(0.0001, now + 1.42);
    noiseSource.connect(noiseFilter);
    noiseFilter.connect(noiseGain);
    noiseGain.connect(masterGain);
    noiseSource.start(now);
    noiseSource.stop(now + 1.45);

    [
      { frequency: 146.83, endFrequency: 98.00, start: 0.00, duration: 1.25, type: 'sine', gain: 0.18 },
      { frequency: 440.00, endFrequency: 659.25, start: 0.66, duration: 0.62, type: 'triangle', gain: 0.16 },
      { frequency: 880.00, endFrequency: 1174.66, start: 0.92, duration: 0.42, type: 'sine', gain: 0.10 },
    ].forEach((note) => {
      const oscillator = audioContext.createOscillator();
      const noteGain = audioContext.createGain();
      const startAt = now + note.start;
      const stopAt = startAt + note.duration;

      oscillator.type = note.type;
      oscillator.frequency.setValueAtTime(note.frequency, startAt);
      oscillator.frequency.exponentialRampToValueAtTime(note.endFrequency, stopAt);
      noteGain.gain.setValueAtTime(0.0001, startAt);
      noteGain.gain.exponentialRampToValueAtTime(note.gain, startAt + 0.08);
      noteGain.gain.exponentialRampToValueAtTime(0.0001, stopAt);
      oscillator.connect(noteGain);
      noteGain.connect(masterGain);
      oscillator.start(startAt);
      oscillator.stop(stopAt + 0.02);
    });

    window.setTimeout(() => {
      masterGain.disconnect();
    }, 1700);
  } catch {
    // Browser autoplay policy can block return-page audio; visual confirmation remains authoritative.
  }
};

const triggerBusinessUpgradeCelebration = () => {
  if (!(document.body instanceof HTMLElement)) {
    return;
  }

  if (businessUpgradeCelebrationActive) {
    return;
  }
  businessUpgradeCelebrationActive = true;
  window.clearTimeout(businessUpgradeHighlightTimer);
  window.clearTimeout(businessUpgradeCleanupTimer);
  window.clearTimeout(businessUpgradeStatusResetTimer);

  const existingVortex = document.getElementById('business-upgrade-vortex');
  if (existingVortex instanceof HTMLElement) {
    existingVortex.remove();
  }

  const overlay = document.getElementById('business-upgrade-wormhole') ?? document.createElement('div');
  overlay.id = 'business-upgrade-wormhole';
  overlay.setAttribute('aria-hidden', 'true');
  overlay.className = '';
  overlay.textContent = '';
  ['wormhole-strand strand-1', 'wormhole-strand strand-2', 'wormhole-strand strand-3', 'wormhole-strand strand-4'].forEach((strandClass) => {
    const strandEl = document.createElement('span');
    strandEl.className = strandClass;
    overlay.appendChild(strandEl);
  });
  const coreEl = document.createElement('span');
  coreEl.className = 'wormhole-core';
  overlay.appendChild(coreEl);
  const flashEl = document.createElement('span');
  flashEl.className = 'wormhole-flash';
  overlay.appendChild(flashEl);

  if (!overlay.parentElement) {
    document.body.appendChild(overlay);
  }

  const status = document.getElementById('business-upgrade-status');
  if (status instanceof HTMLElement) {
    status.hidden = false;
    status.classList.remove('is-highlighted');
  }

  void animationFrame().then(() => {
    const targetRect = status instanceof HTMLElement
      ? status.getBoundingClientRect()
      : {
        left: window.innerWidth / 2,
        top: window.innerHeight / 2,
        width: 1,
        height: 1,
      };
    const targetX = targetRect.left + (targetRect.width / 2);
    const targetY = targetRect.top + (targetRect.height / 2);
    const widthScale = targetRect.width > 0 ? (targetRect.width / window.innerWidth) * 1.8 : 0.16;
    const heightScale = targetRect.height > 0 ? (targetRect.height / window.innerHeight) * 1.8 : 0.16;
    const targetScale = Math.min(Math.max(Math.max(widthScale, heightScale), 0.10), 0.28);

    overlay.style.setProperty('--upgrade-target-x', `${targetX}px`);
    overlay.style.setProperty('--upgrade-target-y', `${targetY}px`);
    overlay.style.setProperty('--upgrade-target-width', `${targetRect.width}px`);
    overlay.style.setProperty('--upgrade-target-height', `${targetRect.height}px`);
    overlay.style.setProperty('--upgrade-target-scale', String(targetScale));

    document.body.classList.remove('business-upgrade-celebrate');
    overlay.classList.remove('is-landing');
    requestAnimationFrame(() => {
      overlay.classList.add('is-landing');
      document.body.classList.add('business-upgrade-celebrate');
      void playBusinessUpgradeSound();
    });

    businessUpgradeHighlightTimer = window.setTimeout(() => {
      if (status instanceof HTMLElement) {
        status.classList.add('is-highlighted');
      }
    }, 5200);
  });

  businessUpgradeCleanupTimer = window.setTimeout(() => {
    document.body.classList.remove('business-upgrade-celebrate');
    overlay.remove();
    businessUpgradeCelebrationActive = false;
  }, 7000);

  businessUpgradeStatusResetTimer = window.setTimeout(() => {
    if (status instanceof HTMLElement) {
      status.classList.remove('is-highlighted');
    }
  }, 7900);
};

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
    tier: typeof data.tier === 'string' ? data.tier : 'free',
    is_premium: Boolean(data.is_premium),
    is_business: Boolean(data.is_business),
    has_paid_plan: Boolean(data.has_paid_plan ?? data.is_premium ?? data.is_business),
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
  const cancelUrl = typeof options.cancelUrl === 'string' ? options.cancelUrl : '/settings/account/?billing=cancel';
  const returnUrl = typeof options.returnUrl === 'string' ? options.returnUrl : '/settings/account/#panel-billing';
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

  const startCheckout = async (plan, statusEl, button) => {
    const csrfToken = resolveCsrfToken();
    const { response, payload } = await fetchJson('/api/v1/billing/checkout-session', {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        plan,
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
          return true;
        }
      }

      await refreshSubscription({ silent: false });
      setInlineStatus(statusEl, messages.success);
      setScreenReaderStatus(messages.success);
      if (subscription?.has_paid_plan) {
        announcePaidActivation(subscription);
      }
      if (button instanceof HTMLButtonElement) {
        button.disabled = false;
      }
      return true;
    }

    throw new Error(extractMessage(payload, messages.checkoutError));
  };

  const resolveCsrfToken = () => {
    const candidates = [
      root.querySelector('#settings_csrf_token'),
      root.querySelector('#businesses_csrf_token'),
      document.getElementById('settings_csrf_token'),
      document.getElementById('businesses_csrf_token'),
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
  const downgradePremiumBtn = root.querySelector('#billing_downgrade_premium_btn');
  const downgradePremiumStatus = root.querySelector('#billing_downgrade_premium_status');
  const downgradeFreeBtn = root.querySelector('#billing_downgrade_free_btn');
  const downgradeFreeStatus = root.querySelector('#billing_downgrade_free_status');
  const downgradeFreeDialog = root.querySelector('#billing_downgrade_free_dialog');
  const downgradeFreeDialogBody = root.querySelector('#billing_downgrade_free_dialog_body');
  const downgradeFreeContinueBtn = root.querySelector('#billing_downgrade_free_continue');
  const upgradeBusinessPlanBtn = root.querySelector('#billing_upgrade_business_plan_btn');
  const upgradeBusinessPlanStatus = root.querySelector('#billing_upgrade_business_plan_status');
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
  const downgradeZoneEl = root.querySelector('#billing_downgrade_zone');
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

  const setElementHidden = (element, hidden) => {
    if (element instanceof HTMLElement) {
      element.hidden = hidden;
    }
  };

  const setBillingState = (nextSubscription) => {
    const hasPaid = Boolean(nextSubscription?.has_paid_plan);
    const isBusiness = Boolean(nextSubscription?.is_business);
    freeView.hidden = hasPaid;
    premiumView.hidden = !hasPaid;
    setElementHidden(upgradeBusinessPlanBtn, !hasPaid || isBusiness);
    setElementHidden(downgradePremiumBtn, !hasPaid || !isBusiness);
    setElementHidden(downgradeFreeBtn, !hasPaid);
    setElementHidden(portalBtn, !hasPaid);

    if (billingPanel instanceof HTMLElement) {
      const tier = typeof nextSubscription?.tier === 'string' ? nextSubscription.tier : 'free';
      const hint = tier === 'business' ? 'business' : (hasPaid ? 'premium' : 'free');
      billingPanel.setAttribute('data-billing-hint', hint);
    }
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

  const planLabelEl = root.querySelector('#billing_plan_label');

  const renderSubscription = (nextSubscription) => {
    setBillingState(nextSubscription);

    if (billingPanel instanceof HTMLElement) {
      billingPanel.setAttribute('data-billing-hydrated', 'true');
    }

    if (planLabelEl instanceof HTMLElement) {
      if (nextSubscription?.is_business) {
        planLabelEl.textContent = 'Business';
      } else if (nextSubscription?.is_premium) {
        planLabelEl.textContent = 'Premium';
      } else {
        planLabelEl.textContent = 'Premium';
      }
    }

    const hasPaid = Boolean(nextSubscription?.has_paid_plan);

    if (startDateEl instanceof HTMLElement) {
      startDateEl.textContent = hasPaid ? formatStartDate(nextSubscription.start_date) : '—';
    }

    if (renewalDateEl instanceof HTMLElement && renewalLineEl instanceof HTMLElement) {
      if (hasPaid && nextSubscription?.renewal_date && !nextSubscription?.is_pending_cancellation) {
        renewalDateEl.textContent = formatStartDate(nextSubscription.renewal_date);
        renewalLineEl.hidden = false;
      } else if (hasPaid) {
        renewalDateEl.textContent = isStripeBilling ? 'Stripe' : 'Local';
        renewalLineEl.hidden = false;
      } else {
        renewalLineEl.hidden = true;
        renewalDateEl.textContent = '—';
      }
    }

    // Show pending cancellation notice if subscription will cancel at period end
    if (cancelNoticeEl instanceof HTMLElement && cancelDateEl instanceof HTMLElement) {
      if (hasPaid && nextSubscription?.is_pending_cancellation && nextSubscription?.cancel_date) {
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
      if (hasPaid && nextSubscription?.is_pending_cancellation) {
        downgradeHelpEl.textContent = messages.downgradeHelpScheduled || DEFAULT_MESSAGES.downgradeHelpScheduled;
      } else if (nextSubscription?.is_business) {
        downgradeHelpEl.textContent = 'This removes Business and Premium access immediately.';
      } else if (hasPaid) {
        downgradeHelpEl.textContent = 'This removes Premium access immediately.';
      } else {
        downgradeHelpEl.textContent = messages.downgradeHelpDefault || DEFAULT_MESSAGES.downgradeHelpDefault;
      }
    }

    if (downgradeFreeDialogBody instanceof HTMLElement) {
      if (nextSubscription?.is_business) {
        downgradeFreeDialogBody.textContent = 'Free removes paid plan access immediately. You will lose Business workspace access, group viewing, sites, listings, roles, reports, aggregate analysis, audit tools, Premium reports, and paid export formats.';
      } else {
        downgradeFreeDialogBody.textContent = 'Free removes paid plan access immediately. You will lose Premium forecasting, additional export formats, advanced graphs, and financial reports.';
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

  const announceBusinessActivation = (nextSubscription) => {
    window.dispatchEvent(new CustomEvent('paycal:billing-business-activated', {
      detail: { subscription: nextSubscription },
    }));

    if (typeof options.onBusinessActivated === 'function') {
      options.onBusinessActivated(nextSubscription);
    }
  };

  const announcePaidActivation = (nextSubscription) => {
    announcePremiumActivation(nextSubscription);
    if (nextSubscription?.is_business) {
      announceBusinessActivation(nextSubscription);
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

  const waitForPaidActivation = async () => {
    for (let attempt = 0; attempt < activationPollAttempts; attempt += 1) {
      const nextSubscription = attempt === 0 && subscription !== null
        ? subscription
        : await refreshSubscription({ silent: true });

      if (nextSubscription?.has_paid_plan) {
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

    if (subscription?.has_paid_plan) {
      setScreenReaderStatus(messages.success);
      announcePaidActivation(subscription);
      if (cleanupQueryParam) {
        replaceSearchParam('billing', null);
        replaceSearchParam('session_id', null);
      }
    } else {
      setScreenReaderStatus(messages.confirming);
      const confirmedSubscription = await waitForPaidActivation();
      if (confirmedSubscription?.has_paid_plan) {
        setScreenReaderStatus(messages.success);
        announcePaidActivation(confirmedSubscription);
      } else {
        setScreenReaderStatus(messages.delayed);
      }

      if (cleanupQueryParam) {
        replaceSearchParam('billing', null);
        replaceSearchParam('session_id', null);
      }
    }
  } else if (billingQuery === 'business-upgrade') {
    const currentSubscription = await refreshSubscription({ silent: true });
    if (currentSubscription?.is_business) {
      triggerBusinessUpgradeCelebration();
      setScreenReaderStatus('Business unlocked.');
    } else {
      setScreenReaderStatus(messages.confirming);
      const confirmedSubscription = await waitForPaidActivation();
      if (confirmedSubscription?.is_business) {
        triggerBusinessUpgradeCelebration();
        setScreenReaderStatus('Business unlocked.');
      } else {
        setScreenReaderStatus(messages.delayed);
      }
    }

    if (cleanupQueryParam) {
      replaceSearchParam('billing', null);
    }
  } else if (billingQuery === 'cancel') {
    setScreenReaderStatus(messages.cancel);
    if (cleanupQueryParam) {
      replaceSearchParam('billing', null);
    }
  }

  const bindUpgradeButton = (button, statusEl, plan = 'premium') => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    button.addEventListener('click', async () => {
      button.disabled = true;
      setInlineStatus(statusEl, messages.checkoutRedirect);

      try {
        await startCheckout(plan, statusEl, button);
      } catch (error) {
        button.disabled = false;
        setInlineStatus(statusEl, error instanceof Error ? error.message : messages.checkoutError);
      }
    });
  };

  bindUpgradeButton(root.querySelector('#billing_upgrade_premium_btn'), root.querySelector('#billing_upgrade_premium_status'), 'premium');
  bindUpgradeButton(root.querySelector('#billing_upgrade_business_btn'), root.querySelector('#billing_upgrade_business_status'), 'business');
  bindUpgradeButton(root.querySelector('#billing_upgrade_business_subscribed_btn'), root.querySelector('#billing_upgrade_business_subscribed_status'), 'business');
  bindUpgradeButton(upgradeBtn, upgradeStatus, 'premium');

  const bindPlanChangeButton = (button, statusEl, plan = 'premium') => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    button.addEventListener('click', async () => {
      button.disabled = true;
      setInlineStatus(statusEl, messages.planChangeWorking);

      try {
        const csrfToken = resolveCsrfToken();
        const { response, payload } = await fetchJson('/api/v1/billing/change-plan', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            plan,
            proration_behavior: 'create_prorations',
            csrf_token: csrfToken,
          }),
        }, {
          retries: 0,
        });

        if (!response.ok || payload?.status !== 'success') {
          throw new Error(extractMessage(payload, messages.planChangeError));
        }

        await refreshSubscription({ silent: false });
        const doneMessage = plan === 'business' ? 'Business is now active.' : messages.planChangeDone;
        setInlineStatus(statusEl, doneMessage);
        setScreenReaderStatus(doneMessage);
      } catch (error) {
        setInlineStatus(statusEl, error instanceof Error ? error.message : messages.planChangeError);
      } finally {
        button.disabled = false;
      }
    });
  };

  bindPlanChangeButton(downgradePremiumBtn, downgradePremiumStatus, 'premium');

  const bindPlanChangePortalButton = (button, statusEl, plan = 'business') => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    if (plan === 'business') {
      button.addEventListener('pointerdown', primeBusinessUpgradeAudio, { passive: true });
      button.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          primeBusinessUpgradeAudio();
        }
      });
    }

    button.addEventListener('click', async () => {
      if (plan === 'business') {
        primeBusinessUpgradeAudio();
      }

      button.disabled = true;
      setInlineStatus(statusEl, messages.portalRedirect);

      try {
        const csrfToken = resolveCsrfToken();
        const { response, payload } = await fetchJson('/api/v1/billing/plan-change-portal-session', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            plan,
            return_url: portalReturnUrl,
            csrf_token: csrfToken,
          }),
        }, {
          retries: 0,
        });
        const data = extractPayloadData(payload);

        if (response.ok && payload?.status === 'success') {
          const portalUrl = typeof data.portal_url === 'string' ? data.portal_url : '';
          if (portalUrl !== '') {
            window.location.href = portalUrl;
            return;
          }
        }

        throw new Error(extractMessage(payload, messages.portalError));
      } catch (error) {
        button.disabled = false;
        setInlineStatus(statusEl, error instanceof Error ? error.message : messages.portalError);
      }
    });
  };

  bindPlanChangePortalButton(upgradeBusinessPlanBtn, upgradeBusinessPlanStatus, 'business');

  if (downgradeFreeBtn instanceof HTMLButtonElement) {
    downgradeFreeBtn.addEventListener('click', () => {
      setInlineStatus(downgradeFreeStatus, '');
      if (typeof HTMLDialogElement !== 'undefined' && downgradeFreeDialog instanceof HTMLDialogElement && typeof downgradeFreeDialog.showModal === 'function') {
        downgradeFreeDialog.showModal();
        return;
      }

      const message = downgradeFreeDialogBody instanceof HTMLElement
        ? downgradeFreeDialogBody.textContent || 'Free removes paid plan access immediately.'
        : 'Free removes paid plan access immediately.';
      if (window.confirm(message)) {
        if (typeof HTMLDetailsElement !== 'undefined' && downgradeZoneEl instanceof HTMLDetailsElement) {
          downgradeZoneEl.open = true;
        }
        if (downgradePhraseInput instanceof HTMLInputElement) {
          downgradePhraseInput.focus();
        }
      }
    });
  }

  if (downgradeFreeContinueBtn instanceof HTMLButtonElement) {
    downgradeFreeContinueBtn.addEventListener('click', () => {
      if (typeof HTMLDialogElement !== 'undefined' && downgradeFreeDialog instanceof HTMLDialogElement) {
        downgradeFreeDialog.close('continue');
      }
      if (typeof HTMLDetailsElement !== 'undefined' && downgradeZoneEl instanceof HTMLDetailsElement) {
        downgradeZoneEl.open = true;
      }
      if (downgradePhraseInput instanceof HTMLInputElement) {
        downgradePhraseInput.focus();
      }
      setInlineStatus(downgradeFreeStatus, 'Type DOWNGRADE ME in Danger Zone to confirm.');
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
