<?php declare(strict_types=1);

namespace PayCal\Domain; ?>

  const getPersonalPayAnchor = () => {
    if (elements.personalPayAnchor instanceof HTMLInputElement || elements.personalPayAnchor instanceof HTMLSelectElement) {
      return String(elements.personalPayAnchor.value || 'Monday');
    }

    return 'Monday';
  };

  const setPersonalPayAnchor = (value) => {
    if (elements.personalPayAnchor instanceof HTMLInputElement || elements.personalPayAnchor instanceof HTMLSelectElement) {
      elements.personalPayAnchor.value = value;
    }
  };

  const getPersonalPayPeriodStart = () => {
    if (elements.personalPayPeriodStart instanceof HTMLInputElement) {
      return String(elements.personalPayPeriodStart.value || '');
    }

    return '';
  };

  const setPersonalPayPeriodStart = (value) => {
    if (elements.personalPayPeriodStart instanceof HTMLInputElement) {
      elements.personalPayPeriodStart.value = value;
    }
  };

  const getPersonalEditingGraceDays = () => {
    const checkedRadio = Array.from(elements.personalEditingGraceDayRadios).find((radio) => radio instanceof HTMLInputElement && radio.checked);
    return checkedRadio instanceof HTMLInputElement ? String(checkedRadio.value || '0') : '0';
  };

  const setPersonalEditingGraceDays = (value) => {
    const normalizedValue = ['0', '1', '2', '3'].includes(String(value)) ? String(value) : '0';
    Array.from(elements.personalEditingGraceDayRadios).forEach((radio) => {
      if (radio instanceof HTMLInputElement) {
        radio.checked = radio.value === normalizedValue;
      }
    });
  };

  const isValidYmdDate = (value) => {
    const text = String(value || '').trim();
    if (!/^\d{4}-\d{2}-\d{2}$/.test(text)) {
      return false;
    }

    const date = new Date(`${text}T00:00:00`);
    if (Number.isNaN(date.getTime())) {
      return false;
    }

    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}` === text;
  };

  const setPersonalPayPeriodWarning = (message) => {
    if (!(elements.personalPayPeriodWarning instanceof HTMLElement)) {
      return;
    }

    const text = String(message || '').trim();
    elements.personalPayPeriodWarning.textContent = text;
    elements.personalPayPeriodWarning.classList.toggle('is-visible', text !== '');
  };

  const getPersonalPayPeriodValidationMessage = () => {
    const frequency = elements.personalPayFrequency instanceof HTMLSelectElement
      ? String(elements.personalPayFrequency.value || '').trim().toLowerCase()
      : 'biweekly';
    const allowedFrequencies = Object.keys(FREQUENCY_LENGTHS);
    if (!allowedFrequencies.includes(frequency)) {
      return T.ppInvalidFrequency;
    }

    const expectedLength = String(FREQUENCY_LENGTHS[frequency] || '14');
    const actualLength = elements.personalPayPeriodLength instanceof HTMLInputElement
      ? String(elements.personalPayPeriodLength.value || '').trim()
      : expectedLength;
    if (actualLength !== expectedLength) {
      return formatPhpTemplate(T.ppInvalidLength, [frequency, expectedLength]);
    }

    const anchor = getPersonalPayAnchor();
    if (!(anchor in PAY_PERIOD_WEEKDAY_MAP)) {
      return T.ppInvalidAnchor;
    }

    const graceRaw = getPersonalEditingGraceDays();
    const graceValue = parseInt(graceRaw, 10);
    const allowedGraceValues = Array.from(elements.personalEditingGraceDayRadios)
      .filter((radio) => radio instanceof HTMLInputElement)
      .map((radio) => parseInt(String(radio.value || ''), 10))
      .filter((value) => Number.isFinite(value));
    const graceMin = allowedGraceValues.length > 0 ? Math.min(...allowedGraceValues) : 0;
    const graceMax = allowedGraceValues.length > 0 ? Math.max(...allowedGraceValues) : 3;
    if (!Number.isFinite(graceValue) || graceValue < graceMin || graceValue > graceMax) {
      return formatPhpTemplate(T.ppInvalidGrace, [graceMin, graceMax]);
    }

    const payPeriodStart = getPersonalPayPeriodStart();
    if ((frequency === 'weekly' || frequency === 'biweekly') && payPeriodStart === '') {
      return T.ppSelectStartDate;
    }

    if (payPeriodStart !== '' && !isValidYmdDate(payPeriodStart)) {
      return T.ppInvalidStartDate;
    }

    return '';
  };

  const refreshPersonalPayPeriodValidation = () => {
    const message = getPersonalPayPeriodValidationMessage();
    setPersonalPayPeriodWarning(message);
    return message === '';
  };

  const getPersonalBusiness = () => {
    return state.businesses.find((business) => isPersonalBusiness(business)) || null;
  };

  const syncPersonalFrequency = () => {
    if (!(elements.personalPayFrequency instanceof HTMLSelectElement)) {
      if (elements.personalPayPeriodLength instanceof HTMLInputElement) {
        elements.personalPayPeriodLength.value = FREQUENCY_LENGTHS.biweekly;
      }
      return FREQUENCY_LENGTHS.biweekly;
    }

    const nextLength = FREQUENCY_LENGTHS[elements.personalPayFrequency.value] || FREQUENCY_LENGTHS.biweekly;
    if (elements.personalPayPeriodLength instanceof HTMLInputElement) {
      elements.personalPayPeriodLength.value = nextLength;
    }

    return nextLength;
  };

  const renderPersonalPreview = () => {
    if (!elements.personalPreview) {
      return;
    }

    const frequency = elements.personalPayFrequency instanceof HTMLSelectElement
      ? elements.personalPayFrequency.value
      : 'biweekly';
    const preview = buildPayPeriodPreviewState({
      startYmd: getPersonalPayPeriodStart(),
      frequency,
      anchor: getPersonalPayAnchor(),
      graceDays: getPersonalEditingGraceDays(),
      dayNames: PAY_PERIOD_DAY_NAMES,
      alignBiweeklyToAnchor: true,
      includeSummary: true,
      calendarOptions: {
        headerMode: 'stripbar',
        selectable: true,
        dayNumberClass: true,
      },
    });

    if (state.personalPreviewSignature === preview.signature) {
      return;
    }

    state.personalPreviewSignature = preview.signature;

    Guardian.setHTML(elements.personalPreview, preview.html);
  };

  const schedulePersonalPreviewRender = () => {
    if (state.personalPreviewRafId !== null) {
      return;
    }

    state.personalPreviewRafId = window.requestAnimationFrame(() => {
      state.personalPreviewRafId = null;
      renderPersonalPreview();
    });
  };

  const loadPersonalBusinessPanel = async () => {
    const panel = document.getElementById('panel-pay-period');
    if (panel instanceof HTMLElement) {
      panel.classList.add('settings_panel_pay_period');
    }
    let settings = {};
    try {
      const raw = typeof panel?.dataset.userSettings === 'string' ? panel.dataset.userSettings : '';
      if (raw !== '') {
        settings = JSON.parse(raw);
      }
    } catch (_err) {
      PW.error(_err);
    }

    const ownedShared = getOwnedSharedBusiness();
    state.profilePayPeriodManagedByBusiness = ownedShared !== null;
    updateProfilePayPeriodManagedBanner(ownedShared);
    setProfilePayPeriodControlsLocked(state.profilePayPeriodManagedByBusiness);

    if (ownedShared) {
      const businessId = String(ownedShared.business_id || '').trim();
      if (businessId !== '') {
        try {
          const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(businessId)}/settings`);
          const orgSettings = payload && typeof payload.settings === 'object' && payload.settings
            ? payload.settings
            : null;
          if (orgSettings) {
            settings = {
              ...settings,
              pay_frequency: String(orgSettings.pay_frequency || settings.pay_frequency || 'biweekly'),
              pay_anchor: String(orgSettings.pay_anchor || settings.pay_anchor || 'Monday'),
              pay_period_start: String(orgSettings.pay_period_start || settings.pay_period_start || ''),
              pay_period_length: String(orgSettings.pay_period_length || settings.pay_period_length || '14'),
              editing_grace_days: String(orgSettings.editing_grace_days || settings.editing_grace_days || '0'),
              pay_rate: String(orgSettings.default_wage || settings.pay_rate || ''),
              timezone: String(orgSettings.timezone || settings.timezone || 'America/Edmonton'),
              currency: String(orgSettings.currency || settings.currency || 'CAD'),
            };
          }
        } catch (error) {
          PW.error(error);
        }
      }
    }

    applyPersonalPayPeriodSettings(settings);
  };

  const applyPersonalPayPeriodSettings = (settings) => {
    if (!settings || typeof settings !== 'object') {
      settings = {};
    }

    if (elements.personalDefaultWage instanceof HTMLInputElement) {
      elements.personalDefaultWage.value = String(settings.pay_rate || '');
    }
    if (elements.personalTimezone instanceof HTMLInputElement) {
      const tz = String(settings.timezone || '');
      elements.personalTimezone.value = tz;
      displayTimezoneValue(elements.personalTimezoneSearch, tz);
    }
    if (elements.personalCurrency instanceof HTMLInputElement) {
      const cur = String(settings.currency || '');
      elements.personalCurrency.value = cur;
      displayCurrencyValue(elements.personalCurrencySearch, cur);
    }
    if (elements.personalLanguage instanceof HTMLSelectElement) {
      elements.personalLanguage.value = String(settings.language || 'en');
    }
    if (elements.personalLocale instanceof HTMLSelectElement) {
      const locale = String(settings.locale || 'en-CA');
      elements.personalLocale.value = locale;
    }
    if (elements.personalPayFrequency instanceof HTMLSelectElement) {
      elements.personalPayFrequency.value = String(settings.pay_frequency || 'biweekly');
    }
    setPersonalPayAnchor(String(settings.pay_anchor || 'Monday'));
    setPersonalPayPeriodStart(String(settings.pay_period_start || ''));
    if (elements.personalPayPeriodLength instanceof HTMLInputElement) {
      elements.personalPayPeriodLength.value = String(settings.pay_period_length || syncPersonalFrequency());
    }
    setPersonalEditingGraceDays(String(settings.editing_grace_days || '0'));
    state.personalEditingGraceDaysValue = getPersonalEditingGraceDays();

    syncPersonalFrequency();
    refreshPersonalPayPeriodValidation();
    schedulePersonalPreviewRender();
    syncPersonalWageCurrencyAdornment();
    syncProfilePhoneCountryAdornment();
    renderPersonalInternationalizationPreview();

    const bootFormData = buildPersonalSettingsFormData();
    state.personalLastSavedSignature = [
      bootFormData.get('pay_frequency'),
      bootFormData.get('pay_anchor'),
      bootFormData.get('pay_period_start'),
      bootFormData.get('pay_period_length'),
      bootFormData.get('editing_grace_days'),
      bootFormData.get('pay_rate'),
      bootFormData.get('timezone'),
      bootFormData.get('currency'),
      bootFormData.get('language'),
      bootFormData.get('locale'),
    ].join('|');
  };

  const updateProfilePayPeriodManagedBanner = (business) => {
    const panel = document.getElementById('panel-pay-period');
    const banner = document.getElementById('profile_pay_period_managed_banner');
    if (panel instanceof HTMLElement) {
      panel.classList.add('settings_panel_pay_period');
    }
    if (!(panel instanceof HTMLElement) || !(banner instanceof HTMLElement)) {
      return;
    }

    if (!business) {
      panel.classList.remove('is-managed-by-business');
      panel.removeAttribute('data-pay-period-managed');
      banner.hidden = true;
      return;
    }

    const businessName = String(business.name || '').trim() || T.nounBusiness;
    const payrollHref = String(business.payroll_href || panel.dataset.managedPayrollHref || '/business/payroll/').trim() || '/business/payroll/';

    panel.classList.add('is-managed-by-business');
    panel.dataset.payPeriodManaged = 'true';
    panel.dataset.managedBusinessId = String(business.business_id || '');
    panel.dataset.managedBusinessName = businessName;
    panel.dataset.managedPayrollHref = payrollHref;
    banner.hidden = false;

    const lede = banner.querySelector('.profile_pay_period_managed_banner_lede');
    if (lede instanceof HTMLElement) {
      lede.textContent = formatPhpTemplate(T.profilePayPeriodManagedBanner, [businessName]);
    }

    const help = banner.querySelector('.profile_pay_period_managed_banner_help');
    if (help instanceof HTMLElement) {
      help.textContent = T.profilePayPeriodManagedHelp;
    }

    const link = banner.querySelector('.profile_pay_period_managed_banner_action a');
    if (link instanceof HTMLAnchorElement) {
      link.href = payrollHref;
      link.textContent = T.profilePayPeriodManagedLink;
    }
  };

  const setProfilePayPeriodControlsLocked = (locked) => {
    const controls = [
      elements.personalPayFrequency,
      elements.personalPayPeriodLength,
      ...Array.from(elements.personalEditingGraceDayRadios),
    ];

    controls.forEach((control) => {
      if (control instanceof HTMLInputElement || control instanceof HTMLSelectElement) {
        control.disabled = locked;
      }
    });

    if (elements.personalPreview instanceof HTMLElement) {
      elements.personalPreview.classList.toggle('is-read-only', locked);
    }
  };

  const syncPersonalWageCurrencyAdornment = () => {
    if (!(elements.personalDefaultWage instanceof HTMLInputElement)) {
      return;
    }

    const input = elements.personalDefaultWage;
    const parent = input.parentElement;
    if (!(parent instanceof HTMLElement)) {
      return;
    }

    let shell = parent.querySelector('.personal_wage_input_shell');
    if (!(shell instanceof HTMLElement)) {
      shell = document.createElement('div');
      shell.className = 'personal_wage_input_shell';
      parent.insertBefore(shell, input);
      shell.appendChild(input);
    }

    let symbolEl = shell.querySelector('.personal_wage_currency_symbol');
    if (!(symbolEl instanceof HTMLElement)) {
      symbolEl = document.createElement('span');
      symbolEl.className = 'personal_wage_currency_symbol';
      symbolEl.setAttribute('aria-hidden', 'true');
      shell.insertBefore(symbolEl, input);
    }

    const currencyCode = elements.personalCurrency instanceof HTMLInputElement
      ? String(elements.personalCurrency.value || 'CAD').trim().toUpperCase()
      : 'CAD';
    const currencyMeta = CURRENCY_LIST[currencyCode] || null;
    const symbol = currencyMeta && String(currencyMeta.symbol || '').trim() !== ''
      ? String(currencyMeta.symbol)
      : '$';

    symbolEl.textContent = symbol;
  };

  const resolveDialCodeFromLocale = (localeValue) => {
    const locale = String(localeValue || '').trim();
    const dialCodesByLocale = {
      'en-CA': '+1',
      'fr-CA': '+1',
      'en-US': '+1',
      'en-GB': '+44',
      'fr-FR': '+33',
      'de-DE': '+49',
      'es-ES': '+34',
      'pt-BR': '+55',
    };

    return dialCodesByLocale[locale] || '+1';
  };

  const syncProfilePhoneCountryAdornment = () => {
    const phoneInput = document.getElementById('edit_details_phone');
    if (!(phoneInput instanceof HTMLInputElement)) {
      return;
    }

    const parent = phoneInput.parentElement;
    if (!(parent instanceof HTMLElement)) {
      return;
    }

    let shell = phoneInput.closest('.personal_phone_input_shell');
    if (!(shell instanceof HTMLElement)) {
      shell = document.createElement('div');
      shell.className = 'personal_phone_input_shell';
      parent.insertBefore(shell, phoneInput);
      shell.appendChild(phoneInput);
    }

    let dialCodeEl = shell.querySelector('.personal_phone_country_code');
    if (!(dialCodeEl instanceof HTMLElement)) {
      dialCodeEl = document.createElement('span');
      dialCodeEl.className = 'personal_phone_country_code';
      dialCodeEl.setAttribute('aria-hidden', 'true');
      shell.insertBefore(dialCodeEl, phoneInput);
    }
    shell.querySelectorAll('.personal_phone_country_code').forEach((candidate) => {
      if (candidate !== dialCodeEl) {
        candidate.remove();
      }
    });

    const locale = elements.personalLocale instanceof HTMLSelectElement
      ? String(elements.personalLocale.value || 'en-CA')
      : 'en-CA';
    dialCodeEl.textContent = resolveDialCodeFromLocale(locale);
  };

  const renderPersonalInternationalizationPreview = () => {
    if (!(elements.personalI18nPreview instanceof HTMLElement)) {
      return;
    }

    const locale = elements.personalLocale instanceof HTMLSelectElement
      ? String(elements.personalLocale.value || 'en-CA')
      : 'en-CA';
    const timeZone = elements.personalTimezone instanceof HTMLInputElement
      ? String(elements.personalTimezone.value || 'UTC')
      : 'UTC';
    const currency = elements.personalCurrency instanceof HTMLInputElement
      ? String(elements.personalCurrency.value || 'CAD').toUpperCase()
      : 'CAD';
    const language = elements.personalLanguage instanceof HTMLSelectElement
      ? String(elements.personalLanguage.value || 'en')
      : 'en';
    const languageLabel = elements.personalLanguage instanceof HTMLSelectElement
      ? String(elements.personalLanguage.options[elements.personalLanguage.selectedIndex]?.text || language)
      : language;

    const sampleDate = new Date(Date.UTC(2026, 3, 13, 20, 45, 0));

    let numberSample = '';
    let currencySample = '';
    let dateSample = '';
    try {
      numberSample = new Intl.NumberFormat(locale, {
        useGrouping: true,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(45977.2);
      currencySample = new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
      }).format(45977.2);
      dateSample = new Intl.DateTimeFormat(locale, {
        dateStyle: 'full',
        timeStyle: 'short',
        timeZone,
      }).format(sampleDate);
    } catch (_error) {
      numberSample = '45,977.20';
      currencySample = '$45,977.20';
      dateSample = sampleDate.toISOString();
    }

    Guardian.setHTML(elements.personalI18nPreview, `
      <div class="profile_i18n_preview_rows">
        <div class="profile_i18n_preview_pair item_pair">
          <span class="item_label label_muted_xs">Language</span>
          <span class="item_value">${escapeHtml(languageLabel)} (${escapeHtml(language)})</span>
        </div>
        <div class="profile_i18n_preview_pair item_pair">
          <span class="item_label label_muted_xs">Locale</span>
          <span class="item_value">${escapeHtml(locale)}</span>
        </div>
        <div class="profile_i18n_preview_pair item_pair">
          <span class="item_label label_muted_xs">Timezone</span>
          <span class="item_value">${escapeHtml(timeZone)}</span>
        </div>
        <div class="profile_i18n_preview_pair item_pair">
          <span class="item_label label_muted_xs">Currency</span>
          <span class="item_value">${escapeHtml(currency)}</span>
        </div>
        <div class="profile_i18n_preview_pair item_pair">
          <span class="item_label label_muted_xs">Number</span>
          <span class="item_value">${escapeHtml(numberSample)}</span>
        </div>
        <div class="profile_i18n_preview_pair item_pair">
          <span class="item_label label_muted_xs">Money</span>
          <span class="item_value">${escapeHtml(currencySample)}</span>
        </div>
        <div class="profile_i18n_preview_pair item_pair">
          <span class="item_label label_muted_xs">Date + Time</span>
          <span class="item_value">${escapeHtml(dateSample)}</span>
        </div>
      </div>
    `);
  };

  const buildPersonalSettingsFormData = () => {
    const formData = new FormData();

    const settingsCsrf = String(
      (document.getElementById('settings_csrf_token') instanceof HTMLInputElement
        ? (/** @type {HTMLInputElement} */ (document.getElementById('settings_csrf_token'))).value
        : '') || ''
    ).trim();
    if (settingsCsrf !== '') {
      formData.set('csrf_token', settingsCsrf);
    }

    formData.set('pay_frequency', elements.personalPayFrequency instanceof HTMLSelectElement ? elements.personalPayFrequency.value : 'biweekly');
    formData.set('pay_anchor', getPersonalPayAnchor());
    formData.set('pay_period_start', getPersonalPayPeriodStart());
    formData.set('pay_period_length', String(syncPersonalFrequency()));
    formData.set('editing_grace_days', getPersonalEditingGraceDays());
    formData.set('pay_rate', elements.personalDefaultWage instanceof HTMLInputElement ? elements.personalDefaultWage.value.trim() : '');
    formData.set('timezone', elements.personalTimezone instanceof HTMLInputElement ? elements.personalTimezone.value.trim() : '');
    formData.set('currency', elements.personalCurrency instanceof HTMLInputElement ? elements.personalCurrency.value.trim() : '');
    formData.set('language', elements.personalLanguage instanceof HTMLSelectElement ? elements.personalLanguage.value.trim() : '');
    formData.set('locale', elements.personalLocale instanceof HTMLSelectElement ? elements.personalLocale.value.trim() : '');

    return formData;
  };

  /**
   * Save personal pay/profile settings with request de-duplication.
   *
   * Plain-language behavior:
   * 1) Build the payload from current form values.
   * 2) Skip saving if nothing changed.
   * 3) If a save is already running, queue one final save with the latest values.
   */
  const PAY_PERIOD_SAVE_SOURCES = new Set(['frequency', 'anchor', 'grace', 'calendar-day']);

  const savePersonalBusinessSettings = async (source = 'auto') => {
    const payPeriodManaged = state.profilePayPeriodManagedByBusiness;

    if (payPeriodManaged && PAY_PERIOD_SAVE_SOURCES.has(source)) {
      return;
    }

    const payPeriodValid = refreshPersonalPayPeriodValidation();
    if (PAY_PERIOD_SAVE_SOURCES.has(source) && !payPeriodValid) {
      return;
    }

    const formData = buildPersonalSettingsFormData();
    if (!payPeriodValid || payPeriodManaged) {
      ['pay_frequency', 'pay_anchor', 'pay_period_start', 'pay_period_length', 'editing_grace_days'].forEach((field) => {
        formData.delete(field);
      });
    }

    const previousLanguage = String(document.documentElement.lang || '').trim().toLowerCase();

    // Build a stable signature of the current values for dedup.
    const payloadSignature = [
      formData.get('pay_frequency'),
      formData.get('pay_anchor'),
      formData.get('pay_period_start'),
      formData.get('pay_period_length'),
      formData.get('editing_grace_days'),
      formData.get('pay_rate'),
      formData.get('timezone'),
      formData.get('currency'),
      formData.get('language'),
      formData.get('locale'),
    ].join('|');

    if (state.personalSaveInFlight) {
      state.personalSavePendingSource = source;
      state.personalPendingSignature = payloadSignature;
      return;
    }

    if (state.personalLastSavedSignature === payloadSignature) {
      return;
    }

    state.personalSaveInFlight = true;

    const savingMessage = source === 'calendar-day'
      ? T.payPeriodSavingStart
      : T.profileSettingsSaving;
    PC.showToast(savingMessage, 'save');

    try {
      return await withWebLock(`profile-settings:${currentUserUUID}`, async () => {
        // Debug: log what we're sending
        const debugPayload = {
          csrf_token: formData.get('csrf_token') ? '***' : 'MISSING',
          pay_frequency: formData.get('pay_frequency'),
          pay_anchor: formData.get('pay_anchor'),
          pay_period_start: formData.get('pay_period_start'),
          pay_period_length: formData.get('pay_period_length'),
          editing_grace_days: formData.get('editing_grace_days'),
          pay_rate: formData.get('pay_rate'),
          timezone: formData.get('timezone'),
          currency: formData.get('currency'),
          language: formData.get('language'),
          locale: formData.get('locale'),
        };
        debugLog('[savePersonalBusinessSettings] Sending to /api/v1/account/profile/update/', debugPayload);

        const result = await PC.updateResource('account/profile', formData, { timeoutMs: 45000 });
        debugLog('[savePersonalBusinessSettings] Success response', result);
        state.personalLastSavedSignature = payloadSignature;

        const savedLanguage = String(formData.get('language') || '').trim().toLowerCase();
        if (savedLanguage !== '' && savedLanguage !== previousLanguage) {
          PC.showToast(T.profileSettingsSaved, 'save');
          await PC.delay(1);
          window.location.reload();
          return true;
        }

        const successMessage = source === 'calendar-day'
          ? T.payPeriodStartUpdated
          : T.profileSettingsSaved;
        PC.showToast(successMessage, 'save');
        markAutosaveTargetSaved();
        return true;
      });
    } catch (error) {
      PW.error(error);
      debugLog('[savePersonalBusinessSettings] Error caught:', {
        message: error instanceof Error ? error.message : String(error),
        stack: error instanceof Error ? error.stack : undefined,
      });
      PC.showToast(error instanceof Error && error.message ? error.message : T.defaultsSaveFailed, 'error');
    } finally {
      state.personalSaveInFlight = false;

      if (state.personalSavePendingSource !== '' && state.personalPendingSignature !== state.personalLastSavedSignature) {
        const queuedSource = state.personalSavePendingSource;
        state.personalSavePendingSource = '';
        state.personalPendingSignature = '';
        savePersonalBusinessSettings(queuedSource).catch((error) => PW.error(error));
      } else {
        state.personalSavePendingSource = '';
        state.personalPendingSignature = '';
      }
    }
  };

  /**
   * Debounce helper for autosave.
   * Waits briefly after user input so we do one save for a burst of changes.
   */
  const schedulePersonalAutoSave = (delayMs = 450, source = 'auto') => {
    if (state.personalAutoSaveTimerId !== null) {
      window.clearTimeout(state.personalAutoSaveTimerId);
    }

    state.personalAutoSaveTimerId = window.setTimeout(() => {
      savePersonalBusinessSettings(source).catch((error) => PW.error(error));
      state.personalAutoSaveTimerId = null;
    }, delayMs);
  };

  const handlePersonalGraceDaysChange = () => {
    const nextValue = getPersonalEditingGraceDays();
    if (state.personalEditingGraceDaysValue === nextValue) {
      return;
    }

    state.personalEditingGraceDaysValue = nextValue;
    refreshPersonalPayPeriodValidation();
    schedulePersonalPreviewRender();
    PC.showToast(T.payPeriodSaving, 'save');
    schedulePersonalAutoSave(180, 'grace');
  };

  const handlePersonalPreviewInteraction = (event) => {
    if (state.profilePayPeriodManagedByBusiness) {
      return;
    }

    const selection = resolvePayPeriodPreviewSelection(event);
    if (selection === null) {
      return;
    }

    setPersonalPayPeriodStart(selection.ymd);
    setPersonalPayAnchor(selection.anchor);
    refreshPersonalPayPeriodValidation();
    schedulePersonalPreviewRender();
    schedulePersonalAutoSave(120, 'calendar-day');
  };
