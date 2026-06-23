(() => {
  'use strict';

  const STORAGE_KEY = 'paycal_plaintext_work_capture_last_ms';
  const MIN_INTERVAL_MS = 60 * 1000;
  const BATCH_LIMIT = 25;
  const MAX_BATCHES = 12;

  const idle = (fn) => {
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(fn, { timeout: 2500 });
      return;
    }

    window.setTimeout(fn, 250);
  };

  const shouldRun = () => {
    try {
      const last = Number(window.localStorage?.getItem(STORAGE_KEY) || '0');
      return !(Number.isFinite(last) && last > 0 && (Date.now() - last) < MIN_INTERVAL_MS);
    } catch {
      return true;
    }
  };

  const markRun = () => {
    try {
      window.localStorage?.setItem(STORAGE_KEY, String(Date.now()));
    } catch {
      // Ignore storage failures; capture remains best-effort.
    }
  };

  const fetchWithTimeout = async (url, options, timeoutMs = 15000) => {
    const controller = new AbortController();
    const timer = window.setTimeout(() => controller.abort(), timeoutMs);
    try {
      return await fetch(url, {
        ...options,
        signal: controller.signal,
      });
    } finally {
      window.clearTimeout(timer);
    }
  };

  const fetchJson = async (url, options, timeoutMs = 15000) => {
    const response = await fetchWithTimeout(url, options, timeoutMs);
    const payload = await response.json().catch(() => ({}));
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    return payload;
  };

  const beginRuntimeAudit = () => {
    if (window.PayCalRuntimeIntegrity?.beginAudit) {
      return window.PayCalRuntimeIntegrity.beginAudit();
    }

    window.__PAYCAL_RUNTIME_AUDIT_IN_PROGRESS = true;
    return () => {
      window.__PAYCAL_RUNTIME_AUDIT_IN_PROGRESS = false;
    };
  };

  const createWorkerBridge = () => {
    let worker = null;
    let requestId = 1;

    const workerUrl = new URL('/js/calendar/crypto-worker.js', window.location.origin);
    const version = new URL(import.meta.url).searchParams.get('v');
    if (version) {
      workerUrl.searchParams.set('v', version);
    }

    const getWorker = () => {
      if (!worker) {
        worker = new Worker(workerUrl.toString(), { type: 'module' });
      }

      return worker;
    };

    return (action, payload = {}) => {
      const activeWorker = getWorker();
      const id = requestId++;

      return new Promise((resolve, reject) => {
        const onMessage = (event) => {
          const data = event?.data || {};
          if (data.id !== id) {
            return;
          }

          activeWorker.removeEventListener('message', onMessage);
          if (data.ok) {
            resolve(data.result || {});
            return;
          }

          reject(new Error(data.error || 'Crypto worker request failed.'));
        };

        activeWorker.addEventListener('message', onMessage);
        activeWorker.postMessage({ id, action, payload });
      });
    };
  };

  const fetchBootstrap = async () => {
    const payload = await fetchJson('/api/v1/user/account/bootstrap', {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
    });

    return payload?.data && typeof payload.data === 'object' ? payload.data : payload;
  };

  const fetchNonce = async () => {
    const payload = await fetchJson('/api/v1/calendar/nonce', {
      method: 'GET',
      credentials: 'same-origin',
      headers: { 'Accept': 'application/json' },
    });
    const token = payload?.data?.nonce || payload?.nonce || '';
    if (!token) {
      throw new Error('Missing csrf nonce');
    }

    return token;
  };

  const runStandaloneCapture = async () => {
    const endRuntimeAudit = beginRuntimeAudit();
    const callWorker = createWorkerBridge();
    try {
      const bootstrap = await fetchBootstrap();
      const wrappedDekPasskey = String(bootstrap.wrappedDekPasskeyForCredential || bootstrap.wrappedDekPasskey || '');
      const credentialId = String(bootstrap.credentialId || bootstrap.sessionCredentialId || '');
      const encryptionSalt = String(bootstrap.encryptionSalt || '');
      if (!wrappedDekPasskey || !credentialId || !encryptionSalt) {
        return { status: 'skipped', reason: 'dek_unavailable', encrypted: 0 };
      }

      const unwrapPayload = {
        wrappedDekPasskey,
        credentialId,
        userId: String(bootstrap.userId || ''),
        saltBase64: encryptionSalt,
        dekVersion: Number(bootstrap.dekVersion || 1),
        cryptoVersion: Number(bootstrap.cryptoVersion || 1),
      };

      try {
        await callWorker('unwrapWithPasskeyCredential', {
          ...unwrapPayload,
          derivationMode: 'credential-only',
        });
      } catch {
        await callWorker('unwrapWithPasskeyCredential', {
          ...unwrapPayload,
          derivationMode: 'credential-user',
        });
      }

      let encryptedTotal = 0;
      let skippedTotal = 0;
      let failedTotal = 0;

      for (let batch = 0; batch < MAX_BATCHES; batch += 1) {
        const queuePayload = await fetchJson(
          `/api/v1/calendar/plaintext-capture?limit=${encodeURIComponent(String(BATCH_LIMIT))}&include_archived=1`,
          {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
          }
        );
        const queueData = queuePayload?.data || {};
        const entries = Array.isArray(queueData.entries) ? queueData.entries : [];
        if (entries.length === 0) {
          return { status: 'complete', encrypted: encryptedTotal, skipped: skippedTotal, failed: failedTotal };
        }

        const finalized = [];
        for (const item of entries) {
          if (!item || typeof item !== 'object' || !item.key || !item.capture_token || !item.entry) {
            continue;
          }

          const encrypted = await callWorker('encryptEntry', { entry: item.entry });
          finalized.push({
            key: String(item.key),
            capture_token: String(item.capture_token),
            encrypted_blob: encrypted.encrypted_blob,
          });
        }

        if (finalized.length === 0) {
          return { status: 'stalled', encrypted: encryptedTotal, skipped: skippedTotal, failed: failedTotal };
        }

        const finalizePayload = await fetchJson('/api/v1/calendar/plaintext-capture', {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            csrf_token: await fetchNonce(),
            entries: finalized,
          }),
        }, 20000);

        const finalizeData = finalizePayload?.data || {};
        encryptedTotal += Number(finalizeData.encrypted || 0);
        skippedTotal += Number(finalizeData.skipped || 0);
        failedTotal += Number(finalizeData.failed || 0);

        if (Number(finalizeData.failed || 0) > 0 || Number(queueData.remaining || 0) <= 0) {
          return {
            status: Number(finalizeData.failed || 0) > 0 ? 'partial' : 'complete',
            encrypted: encryptedTotal,
            skipped: skippedTotal,
            failed: failedTotal,
          };
        }
      }

      return { status: 'limited', encrypted: encryptedTotal, skipped: skippedTotal, failed: failedTotal };
    } finally {
      endRuntimeAudit();
    }
  };

  if (!shouldRun()) {
    return;
  }

  idle(() => {
    const runner = window.PayCalCrypto?.capturePlaintextWorkEntries
      ? window.PayCalCrypto.capturePlaintextWorkEntries({ reason: 'global_authenticated_boot' })
      : runStandaloneCapture();

    Promise.resolve(runner)
      .catch(() => ({ status: 'failed', encrypted: 0 }))
      .finally(markRun);
  });
})();
