<?php namespace PayCal\Domain; ?>
  // Core module: public business browser and access request UI

  const BUSINESS_BROWSER_T = <?php echo json_encode([
    'unknownName' => org_js_index_i18n('BUSINESSES_UNKNOWN_NAME'),
    'locationUnavailable' => org_js_index_i18n('BUSINESSES_LOCATION_UNAVAILABLE'),
    'noEmailAvailable' => org_js_index_i18n('BUSINESSES_NO_EMAIL_AVAILABLE'),
    'industryUnavailable' => org_js_index_i18n('BUSINESSES_INDUSTRY_UNAVAILABLE'),
    'employeesUnavailable' => org_js_index_i18n('BUSINESSES_EMPLOYEES_UNAVAILABLE'),
    'websiteUnavailable' => org_js_index_i18n('BUSINESSES_WEBSITE_UNAVAILABLE'),
    'supportHoursUnavailable' => org_js_index_i18n('BUSINESSES_SUPPORT_HOURS_UNAVAILABLE'),
    'cardsAria' => org_js_index_i18n('BUSINESSES_CARDS_ARIA'),
    'minChars' => org_js_index_i18n('BUSINESSES_BROWSER_MIN_CHARS'),
    'noMatches' => org_js_index_i18n('BUSINESSES_BROWSER_NO_MATCHES'),
    'selectValidContact' => org_js_index_i18n('BUSINESSES_BROWSER_SELECT_VALID_CONTACT'),
    'accessSubmittedFor' => org_js_index_i18n('BUSINESSES_ACCESS_REQUEST_SUBMITTED_FOR'),
    'accessSubmittedTo' => org_js_index_i18n('BUSINESSES_ACCESS_REQUEST_SUBMITTED_TO'),
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE); ?>;

  const businessBrowserText = (key, fallback = '') => {
    const value = String(BUSINESS_BROWSER_T?.[key] ?? '').trim();
    return value === '' || /<!doctype\s+html|<html[\s>]/i.test(value) ? fallback : value;
  };

  const normalizeBrowserSuggestion = (suggestion) => {
    const email = String(suggestion?.email || '').trim().toLowerCase();
    const ownerName = String(suggestion?.name || '').trim();
    const businessName = String(suggestion?.business_name || '').trim();
    const key = `${businessName.toLowerCase()}|${email}`;

    return {
      key,
      email,
      ownerName,
      businessName,
      publicProfile: suggestion && typeof suggestion.public_profile === 'object' && suggestion.public_profile
        ? suggestion.public_profile
        : {},
      searchedAt: Date.now(),
    };
  };

  const setBrowserPanelStatus = (message) => {
    const text = String(message || '');
    setStatusText(elements.browserPanelStatus, text);
    setStatusText(elements.browserGridStatus, text);
  };

  const setBrowserProfileStatus = (message, mode = 'error') => {
    if (!(elements.browserProfileStatus instanceof HTMLElement)) {
      return;
    }

    const text = String(message || '');
    const normalizedMode = mode === true ? 'error' : (mode === false ? 'muted' : String(mode || 'error'));
    elements.browserProfileStatus.textContent = text;
    elements.browserProfileStatus.classList.toggle('hidden', text === '');
    elements.browserProfileStatus.classList.toggle('form_error', normalizedMode === 'error');
    elements.browserProfileStatus.classList.toggle('help_text', normalizedMode === 'muted');
    elements.browserProfileStatus.classList.toggle('is_error', normalizedMode === 'error');
    elements.browserProfileStatus.classList.toggle('is_success', normalizedMode === 'success');
    elements.browserProfileStatus.classList.toggle('is_pending', normalizedMode === 'pending');
  };

  const formatBrowserProfileLocation = (profile) => {
    const city = String(profile?.address_city || '').trim();
    const province = String(profile?.address_region || '').trim();
    const countryRaw = String(profile?.address_country || '').trim();
    const country = countryRaw === '' ? 'Canada' : countryRaw;
    const cityProvince = [city, province].filter((part) => part !== '').join(' ');

    return [cityProvince, country].filter((part) => part !== '').join(', ');
  };

  const formatBrowserProfileWebsite = (rawValue) => {
    const rawWebsite = String(rawValue || '').trim();
    if (rawWebsite === '') {
      return '';
    }

    const websiteText = rawWebsite.replace(/^https?:\/\//i, '').replace(/\/$/, '');
    const websiteHref = /^https?:\/\//i.test(rawWebsite) ? rawWebsite : `https://${rawWebsite}`;

    return `<a href="${safeText(websiteHref)}" target="_blank" rel="noopener noreferrer">${safeText(websiteText)}</a>`;
  };

  const renderBrowserProfileDialog = (row) => {
    if (!(elements.browserProfileBody instanceof HTMLElement)) {
      return;
    }

    const profile = row?.publicProfile && typeof row.publicProfile === 'object' ? row.publicProfile : {};
    const businessName = String(row?.businessName || '').trim() || 'Business profile';
    const ownerName = String(row?.ownerName || '').trim();
    const ownerEmail = String(row?.email || '').trim();
    const location = formatBrowserProfileLocation(profile);
    const websiteMarkup = formatBrowserProfileWebsite(profile.website);
    const employeeCount = String(profile.employee_count || '').trim();
    const contactEmail = String(profile.contact_email || '').trim();
    const contactPhone = formatPhoneDisplayValue(String(profile.contact_phone || '').trim());
    const rows = [
      ['Business', businessName],
      ['Location', location],
      ['Industry', String(profile.industry || '').trim()],
      ['Website', websiteMarkup],
      ['Owner', ownerName],
      ['Owner email', ownerEmail],
      ['Contact email', contactEmail],
      ['Contact phone', contactPhone],
      ['Employees', employeeCount],
      ['Support hours', String(profile.support_hours || '').trim()],
    ].filter(([, value]) => String(value || '').trim() !== '');

    if (elements.browserProfileTitle instanceof HTMLElement) {
      elements.browserProfileTitle.textContent = businessName;
    }

    Guardian.setHTML(elements.browserProfileBody, `
      <dl class="businesses_browser_profile_grid">
        ${rows.map(([label, value]) => {
          const renderedValue = label === 'Website' ? String(value || '') : safeText(value);
          return `<dt>${safeText(label)}</dt><dd>${renderedValue}</dd>`;
        }).join('')}
      </dl>
    `);

    if (elements.browserProfileConnect instanceof HTMLButtonElement) {
      elements.browserProfileConnect.dataset.key = String(row?.key || '');
      elements.browserProfileConnect.disabled = ownerEmail === '';
      elements.browserProfileConnect.setAttribute('aria-disabled', ownerEmail === '' ? 'true' : 'false');
      elements.browserProfileConnect.classList.remove('is_success');
      elements.browserProfileConnect.textContent = T.browserRequestAccess;
    }

    setBrowserProfileStatus('');
  };

  const openBrowserProfileDialog = (key) => {
    const row = state.browserLastResults.find((candidate) => String(candidate?.key || '') === String(key || ''));
    if (!row || !(elements.browserProfileDialog instanceof HTMLDialogElement)) {
      return;
    }

    state.browserSelectedResultKey = String(row.key || '');
    renderBrowserProfileDialog(row);
    if (!elements.browserProfileDialog.open) {
      elements.browserProfileDialog.showModal();
    }
  };

  const closeBrowserProfileDialog = () => {
    if (elements.browserProfileDialog instanceof HTMLDialogElement && elements.browserProfileDialog.open) {
      elements.browserProfileDialog.close();
    }
  };

  const renderBrowserGrid = (container, rows, emptyMessage) => {
    if (!(container instanceof HTMLElement)) {
      return;
    }

    const body = container.querySelector('.datagrid_body');
    if (!(body instanceof HTMLElement)) {
      return;
    }

    if (!Array.isArray(rows) || rows.length === 0) {
      setDatagridMessage(container, emptyMessage);
      return;
    }

    const cards = rows.map((row) => {
      const businessText = row.businessName === '' ? businessBrowserText('unknownName', 'Unknown Business') : row.businessName;
      const profile = row.publicProfile && typeof row.publicProfile === 'object' ? row.publicProfile : {};

      const locationLine = formatBrowserProfileLocation(profile);

      const industry = String(profile.industry || '').trim();
      const employeeCountRaw = String(profile.employee_count || '').trim();
      const employeeCount = /^\d+$/.test(employeeCountRaw)
        ? `${employeeCountRaw} ${employeeCountRaw === '1' ? 'employee' : 'employees'}`
        : employeeCountRaw;
      const supportHours = String(profile.support_hours || '').trim();
      const locationDisplay = locationLine === '' ? businessBrowserText('locationUnavailable', 'Location unavailable') : locationLine;
      const ownerEmailDisplay = String(row.email || '').trim() === '' ? businessBrowserText('noEmailAvailable', 'No email available') : String(row.email || '').trim();
      const industryDisplay = industry === '' ? businessBrowserText('industryUnavailable', 'Industry unavailable') : industry;
      const employeesDisplay = employeeCount === '' ? businessBrowserText('employeesUnavailable', 'Employees unavailable') : employeeCount;
      const websiteDisplay = formatBrowserProfileWebsite(profile.website) === ''
        ? businessBrowserText('websiteUnavailable', 'Website unavailable')
        : formatBrowserProfileWebsite(profile.website);
      const supportDisplay = supportHours === '' ? businessBrowserText('supportHoursUnavailable', 'Support hours unavailable') : safeText(supportHours);

      return `
        <article class="businesses_browser_card">
          <section class="businesses_browser_data_grid">
            <p class="businesses_browser_cell businesses_browser_name businesses_browser_span_full">${safeText(businessText)}</p>
            <p class="businesses_browser_cell businesses_browser_location businesses_browser_span_full">${safeText(locationDisplay)}</p>
            <p class="businesses_browser_cell businesses_browser_owner_email businesses_browser_span_full">${safeText(ownerEmailDisplay)}</p>

            <p class="businesses_browser_cell businesses_browser_cell_label businesses_browser_website">${websiteDisplay}</p>
            <p class="businesses_browser_cell businesses_browser_cell_value businesses_browser_employees">${safeText(employeesDisplay)}</p>

            <p class="businesses_browser_cell businesses_browser_cell_label businesses_browser_industry">${safeText(industryDisplay)}</p>
            <p class="businesses_browser_cell businesses_browser_cell_value businesses_browser_support">${supportDisplay}</p>
          </section>
          <section class="businesses_browser_card_footer">
            <button
              type="button"
              class="btn btn_secondary btn_sm businesses_browser_row_action"
              data-browser-action="profile"
              data-key="${safeText(row.key)}"
            >View profile</button>
          </section>
        </article>
      `;
    }).join('');

    Guardian.setHTML(body, `<div class="businesses_browser_cards" aria-label="${safeText(businessBrowserText('cardsAria', 'Business cards'))}">${cards}</div>`);
  };

  const runBrowserSearch = async (query) => {
    const trimmed = String(query || '').trim();
    if (trimmed.length < ACCESS_LOOKUP_MIN_CHARS) {
      state.browserLastResults = [];
      renderBrowserGrid(elements.browserGrid, [], businessBrowserText('minChars', 'Type at least 2 characters to search businesses.'));
      setBrowserPanelStatus(businessBrowserText('minChars', 'Type at least 2 characters to search businesses.'));
      return;
    }

    const suggestions = await fetchAccessLookupSuggestions(trimmed);
    const rows = suggestions
      .map((suggestion) => normalizeBrowserSuggestion(suggestion))
      .filter((row) => row.email !== '');

    state.browserLastResults = rows;

    if (rows.length === 0) {
      const noMatchMessage = formatPhpTemplate(T.browserNoMatchQuery, [trimmed]);
      renderBrowserGrid(elements.browserGrid, [], noMatchMessage);
      setBrowserPanelStatus('');
      return;
    }

    renderBrowserGrid(elements.browserGrid, rows, businessBrowserText('noMatches', 'No businesses matched your search.'));

    setBrowserPanelStatus(formatPhpTemplate(T.browserFoundCount, [
      rows.length,
      rows.length === 1 ? '' : 's',
      trimmed,
    ]));
  };

  const connectToBusinessFromBrowser = async (email, businessName = '', ownerName = '') => {
    const normalizedEmail = String(email || '').trim().toLowerCase();
    if (normalizedEmail === '') {
      PC.showToast(businessBrowserText('selectValidContact', 'Select a valid business contact.'), 'error', 5000, true);
      return;
    }

    await postForm('/api/v1/businesses/access/request', {
      owner_email: normalizedEmail,
      access_level: state.requestAccessLevel || 'readonly',
    });

    const businessLabel = String(businessName || '').trim();
    const successMessage = businessLabel === ''
      ? formatPhpTemplate(businessBrowserText('accessSubmittedFor', 'Request sent to %s. No protected work data is shared by this request.'), [normalizedEmail])
      : formatPhpTemplate(businessBrowserText('accessSubmittedTo', 'Request sent to %s. No protected work data is shared by this request.'), [businessLabel]);

    if (elements.requestEmail instanceof HTMLInputElement) {
      elements.requestEmail.value = normalizedEmail;
    }

    setDiscoveryPanelStatus(successMessage);
    setBrowserPanelStatus(successMessage);
    PC.showToast(successMessage, 'save', 7000, true);

    return {
      successMessage,
      profileMessage: T.browserRequestSentStatus,
    };
  };

  const initializeBusinessBrowser = () => {
    if (!(elements.browserGrid instanceof HTMLElement)) {
      return;
    }

    setBrowserPanelStatus('');
  };

  const bindBusinessBrowserEvents = () => {
    elements.browserSearchForm?.addEventListener('submit', (event) => {
      event.preventDefault();
      const query = elements.browserSearchInput instanceof HTMLInputElement
        ? String(elements.browserSearchInput.value || '')
        : '';
      runBrowserSearch(query).catch((error) => {
        PW.error(error);
        setBrowserPanelStatus('Business search failed. Try again.');
      });
    });

    elements.browserSearchInput?.addEventListener('input', () => {
      if (state.browserSearchDebounceId !== null) {
        window.clearTimeout(state.browserSearchDebounceId);
      }

      const query = elements.browserSearchInput instanceof HTMLInputElement
        ? String(elements.browserSearchInput.value || '')
        : '';

      state.browserSearchDebounceId = window.setTimeout(() => {
        runBrowserSearch(query).catch((error) => {
          PW.error(error);
          setBrowserPanelStatus('Business search failed. Try again.');
        });
        state.browserSearchDebounceId = null;
      }, ORG_BROWSER_SEARCH_DEBOUNCE_MS);
    });

    const handleBrowserGridAction = (event) => {
      const target = event.target instanceof Element
        ? event.target.closest('[data-browser-action="profile"]')
        : null;
      if (!(target instanceof HTMLButtonElement)) {
        return;
      }

      openBrowserProfileDialog(String(target.dataset.key || ''));
    };

    const handleBrowserProfileConnect = () => {
      const key = String(elements.browserProfileConnect?.dataset?.key || state.browserSelectedResultKey || '');
      const row = state.browserLastResults.find((candidate) => String(candidate?.key || '') === key);
      if (!row) {
        return;
      }

      if (elements.browserProfileConnect instanceof HTMLButtonElement) {
        elements.browserProfileConnect.disabled = true;
        elements.browserProfileConnect.setAttribute('aria-disabled', 'true');
        elements.browserProfileConnect.textContent = T.browserRequestSending;
      }
      setBrowserProfileStatus(T.browserRequestSending, 'pending');

      connectToBusinessFromBrowser(row.email, row.businessName, row.ownerName).then((result) => {
        if (elements.browserProfileConnect instanceof HTMLButtonElement) {
          elements.browserProfileConnect.disabled = true;
          elements.browserProfileConnect.setAttribute('aria-disabled', 'true');
          elements.browserProfileConnect.classList.add('is_success');
          elements.browserProfileConnect.textContent = T.browserRequestSent;
        }
        setBrowserProfileStatus(result?.profileMessage || T.browserRequestSentStatus, 'success');
      }).catch((error) => {
        PW.error(error);
        const message = cleanApiMessage(error instanceof Error && error.message ? error.message : T.requestJoinFailed, T.requestJoinFailed);
        setBrowserProfileStatus(message, true);
        setBrowserPanelStatus(message);
        setDiscoveryPanelStatus(message);
        PC.showToast(message, 'error', 7000, true);
        if (elements.browserProfileConnect instanceof HTMLButtonElement) {
          elements.browserProfileConnect.disabled = false;
          elements.browserProfileConnect.setAttribute('aria-disabled', 'false');
          elements.browserProfileConnect.classList.remove('is_success');
          elements.browserProfileConnect.textContent = T.browserRequestAccess;
        }
      });
    };

    elements.browserGrid?.addEventListener('click', handleBrowserGridAction);
    elements.browserProfileConnect?.addEventListener('click', handleBrowserProfileConnect);
    document.querySelectorAll('[data-dialog-close="businesses_browser_profile_dialog"]').forEach((button) => {
      button.addEventListener('click', () => {
        closeBrowserProfileDialog();
      });
    });
  };
