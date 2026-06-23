<?php namespace PayCal\Domain; ?>

  const extractLookupEmail = (rawValue) => {
    const value = String(rawValue || '').trim();
    if (value === '') {
      return '';
    }

    const emailMatch = value.match(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i);
    if (!emailMatch) {
      return '';
    }

    return String(emailMatch[0] || '').trim().toLowerCase();
  };

  const renderAccessLookupOptions = (datalistEl, suggestions) => {
    if (!(datalistEl instanceof HTMLDataListElement)) {
      return;
    }

    Guardian.setHTML(datalistEl, '');

    suggestions.forEach((suggestion) => {
      const email = String(suggestion && suggestion.email ? suggestion.email : '').trim();
      if (email === '') {
        return;
      }

      const ownerName = String(suggestion && suggestion.name ? suggestion.name : '').trim();
      const businessName = String(suggestion && suggestion.business_name ? suggestion.business_name : '').trim();

      let value = ownerName === '' ? email : `${ownerName} <${email}>`;
      if (businessName !== '') {
        value = `${businessName} (${value})`;
      }

      const option = document.createElement('option');
      option.value = value;
      datalistEl.appendChild(option);
    });
  };

  const fetchAccessLookupSuggestions = async (query, options = {}) => {
    const params = new URLSearchParams();
    const trimmed = String(query || '').trim();
    if (trimmed !== '') {
      params.set('q', trimmed);
    }

    if (typeof options.mode === 'string' && options.mode.trim() !== '') {
      params.set('mode', options.mode.trim());
    }

    if (Number.isFinite(options.limit)) {
      params.set('limit', String(Math.max(1, Math.min(25, Number(options.limit)))));
    }

    const qs = params.toString();
    const endpoint = qs === ''
      ? '/api/v1/businesses/access/search'
      : `/api/v1/businesses/access/search?${qs}`;

    const payload = await apiRequest(endpoint, {
      timeoutMs: 12000,
    });

    return Array.isArray(payload.suggestions) ? payload.suggestions : [];
  };

  const bindAccessLookupInput = (inputEl, datalistEl) => {
    if (!(inputEl instanceof HTMLInputElement) || !(datalistEl instanceof HTMLDataListElement)) {
      return;
    }

    let debounceId = null;
    let requestSeq = 0;

    const runLookup = async () => {
      const query = String(inputEl.value || '').trim();
      if (query.length < ACCESS_LOOKUP_MIN_CHARS) {
        renderAccessLookupOptions(datalistEl, []);
        return;
      }

      requestSeq += 1;
      const mySeq = requestSeq;

      try {
        const suggestions = await fetchAccessLookupSuggestions(query);
        if (mySeq !== requestSeq) {
          return;
        }

        renderAccessLookupOptions(datalistEl, suggestions);
      } catch (_error) {
        if (mySeq !== requestSeq) {
          return;
        }

        renderAccessLookupOptions(datalistEl, []);
      }
    };

    inputEl.addEventListener('input', () => {
      if (debounceId !== null) {
        window.clearTimeout(debounceId);
      }

      debounceId = window.setTimeout(() => {
        runLookup().catch((error) => PW.error(error));
        debounceId = null;
      }, ACCESS_LOOKUP_DEBOUNCE_MS);
    });

    inputEl.addEventListener('focus', () => {
      const query = String(inputEl.value || '').trim();
      if (query.length >= ACCESS_LOOKUP_MIN_CHARS) {
        runLookup().catch((error) => PW.error(error));
      }
    });
  };
