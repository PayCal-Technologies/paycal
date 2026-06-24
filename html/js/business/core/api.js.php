<?php namespace PayCal\Domain; ?>

  const getCsrfToken = () => {
    const tokenInput = document.getElementById('businesses_csrf_token');
    if (!(tokenInput instanceof HTMLInputElement)) {
      return '';
    }

    return String(tokenInput.value || '');
  };

  const buildHeaders = (extra = {}) => ({
    Accept: 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
    ...extra,
  });

  const withCsrfHeader = (headers = {}) => {
    const csrfToken = getCsrfToken();
    if (csrfToken === '') {
      return headers;
    }

    return {
      'X-CSRF-Token': csrfToken,
      ...headers,
    };
  };

  const extractPayloadData = (payload) => {
    if (payload && typeof payload === 'object') {
      const { status, message, _lens, ...data } = payload;
      return data;
    }

    return {};
  };

  const cleanApiMessage = (message, fallback = 'Request failed.') => {
    const text = String(message || fallback).trim().replace(/^\[[^\]]+\]\s*/, '');
    return text === '' ? fallback : text;
  };

  const isApiPayloadSuccess = (payload) => {
    if (!payload || typeof payload !== 'object' || !('status' in payload)) {
      return true;
    }

    const status = payload.status;
    if (status === true || status === 'success' || status === 'ok') {
      return true;
    }

    if (typeof status === 'number') {
      return status >= 200 && status < 300;
    }

    const normalized = String(status || '').trim().toLowerCase();
    if (normalized === 'success' || normalized === 'ok') {
      return true;
    }

    const numericStatus = Number(normalized);
    return Number.isFinite(numericStatus) && numericStatus >= 200 && numericStatus < 300;
  };

  const buildApiError = (message, status = 0, data = {}) => {
    const error = new Error(cleanApiMessage(message));
    error.status = Number(status || 0);
    error.data = data && typeof data === 'object' ? data : {};
    return error;
  };

  const apiFetch = async (url, options = {}) => {
    const { timeoutMs: customTimeoutMs, ...fetchOptions } = options;
    const {
      headers: requestHeaders = {},
      signal: requestSignal = null,
      ...requestOptions
    } = fetchOptions;
    const timeoutMs = Number.isFinite(customTimeoutMs)
      ? Math.max(1000, Number(customTimeoutMs))
      : 30000;
    const controller = new AbortController();
    const timeoutId = window.setTimeout(() => controller.abort(), timeoutMs);
    const mergedSignal = requestSignal || controller.signal;

    let response;
    try {
      response = await fetch(url, {
        credentials: 'same-origin',
        headers: buildHeaders(requestHeaders),
        signal: mergedSignal,
        ...requestOptions,
      });
    } catch (error) {
      window.clearTimeout(timeoutId);
      if (error instanceof DOMException && error.name === 'AbortError') {
        throw new Error('<?php echo addslashes(org_js_index_i18n('BUSINESSES_REQUEST_TIMEOUT')); ?>');
      }
      throw new Error('<?php echo addslashes(org_js_index_i18n('BUSINESSES_REQUEST_NETWORK_FAILED')); ?>');
    }

    window.clearTimeout(timeoutId);
    return response;
  };

  const readApiJsonResponse = async (response) => {
    const raw = await response.text();
    let payload = null;
    if (raw.trim() !== '') {
      try {
        payload = JSON.parse(raw);
      } catch (_error) {
        payload = null;
      }
    }

    return { payload, raw };
  };

  const apiRequest = async (url, options = {}) => {
    const response = await apiFetch(url, options);
    const { payload, raw } = await readApiJsonResponse(response);

    if (raw.trim() !== '' && payload === null) {
      if (!response.ok) {
        throw new Error(`Request failed (${response.status}).`);
      }
      return {};
    }

    if (!response.ok) {
      const message = payload && typeof payload === 'object' && 'message' in payload
        ? String(payload.message || 'Request failed.')
        : `Request failed (${response.status}).`;
      const data = payload && typeof payload === 'object' && 'data' in payload && payload.data && typeof payload.data === 'object'
        ? payload.data
        : {};
      throw buildApiError(message, response.status, data);
    }

    if (payload && typeof payload === 'object' && 'status' in payload && !isApiPayloadSuccess(payload)) {
      const data = payload && typeof payload === 'object' && 'data' in payload && payload.data && typeof payload.data === 'object'
        ? payload.data
        : {};
      throw buildApiError(String(payload.message || 'Request failed.'), response.status, data);
    }

    return extractPayloadData(payload || {});
  };

  const apiJsonRaw = async (url, options = {}) => {
    const response = await apiFetch(url, options);
    const { payload, raw } = await readApiJsonResponse(response);
    return { response, data: payload, raw };
  };

  const postJsonRaw = async (url, values = {}, requestOptions = {}) => {
    const { headers = {}, ...options } = requestOptions;
    return apiJsonRaw(url, {
      method: 'POST',
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...withCsrfHeader(headers),
      },
      body: JSON.stringify(values ?? {}),
    });
  };

  const postJsonBlob = async (url, values = {}, requestOptions = {}) => {
    const {
      errorPrefix = 'Request failed',
      headers = {},
      ...options
    } = requestOptions;
    const response = await apiFetch(url, {
      method: 'POST',
      ...options,
      headers: {
        'Content-Type': 'application/json',
        ...withCsrfHeader(headers),
      },
      body: JSON.stringify(values ?? {}),
    });

    if (!response.ok) {
      const text = await response.text();
      throw buildApiError(`${errorPrefix} (${response.status}): ${text}`, response.status);
    }

    return response.blob();
  };

  const postFormDataRaw = async (url, formData, requestOptions = {}) => {
    const { headers = {}, ...options } = requestOptions;
    return apiJsonRaw(url, {
      method: 'POST',
      ...options,
      headers,
      body: formData,
    });
  };

  const postForm = async (url, values, requestOptions = {}) => {
    const body = new URLSearchParams();

    Object.entries(values).forEach(([key, value]) => {
      if (Array.isArray(value)) {
        value.forEach((item) => body.append(`${key}[]`, String(item)));
        return;
      }

      if (value === null || typeof value === 'undefined') {
        return;
      }

      body.set(key, String(value));
    });

    const csrfToken = getCsrfToken();
    if (csrfToken !== '') {
      body.set('csrf_token', csrfToken);
    }

    return apiRequest(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: body.toString(),
      ...requestOptions,
    });
  };
