const CONTROL_STATUS_ID = 'redis-control-status';
const Guardian = window.Guardian;

if (!Guardian || typeof Guardian.setHTML !== 'function') {
  throw new Error('Guardian module is required before admin/redis.js');
}

const ENDPOINTS = {
  status: '/api/v1/admin/redis/status',
  capability: (action) => `/api/v1/admin/capability/${encodeURIComponent(action)}`,
  freeze: '/api/v1/admin/redis/freeze',
  openBreaker: '/api/v1/admin/redis/breaker/open',
  resetBreaker: '/api/v1/admin/redis/breaker/reset',
};

/**
 * @param {string} id
 * @returns {HTMLElement|null}
 */
function byId(id) {
  return document.getElementById(id);
}

/**
 * @param {number} value
 * @returns {string}
 */
function fmt(value) {
  if (!Number.isFinite(value)) {
    return '-';
  }
  return Number(value).toLocaleString();
}

/**
 * @param {number} value
 * @returns {string}
 */
function pct(value) {
  if (!Number.isFinite(value)) {
    return '-';
  }
  return `${Number(value).toFixed(2)}%`;
}

/**
 * @param {string} text
 * @param {'error'|'success'} kind
 */
function setControlStatus(text, kind = 'success') {
  const el = byId(CONTROL_STATUS_ID);
  if (!el) {
    return;
  }
  el.textContent = text;
  el.classList.remove('is-error', 'is-success');
  el.classList.add(kind === 'error' ? 'is-error' : 'is-success');
}

/**
 * @param {string} id
 * @param {string} value
 */
function writeText(id, value) {
  const el = byId(id);
  if (!el) {
    return;
  }
  el.textContent = value;
}

/**
 * @param {'open'|'half-open'|'closed'} state
 * @returns {string}
 */
function breakerPill(state) {
  const safe = state === 'open' || state === 'half-open' || state === 'closed' ? state : 'closed';
  return `<span class="redis-pill is-${safe}">${safe}</span>`;
}

/**
 * @param {boolean} frozen
 * @returns {string}
 */
function freezePill(frozen) {
  if (frozen) {
    return '<span class="redis-pill is-frozen">frozen</span>';
  }
  return '<span class="redis-pill is-live">live</span>';
}

/**
 * @param {Record<string, unknown>} row
 * @returns {string}
 */
function renderQuotaRow(name, row) {
  const current = Number(row.current ?? 0);
  const quota = Number(row.quota ?? 0);
  const percent = Number(row.percent ?? 0);
  const status = percent >= 100 ? 'OVER QUOTA' : percent >= 90 ? 'NEAR LIMIT' : 'OK';

  return `<tr>
    <td>${name}</td>
    <td>${fmt(current)}</td>
    <td>${fmt(quota)}</td>
    <td>${pct(percent)}</td>
    <td>${status}</td>
  </tr>`;
}

/**
 * @param {Record<string, number>} counts
 * @param {Record<string, number>} churn
 * @returns {string}
 */
function renderChurnRows(counts, churn) {
  return Object.keys(counts)
    .sort()
    .map((namespace) => `<tr>
      <td>${namespace}</td>
      <td>${fmt(Number(counts[namespace] ?? 0))}</td>
      <td>${fmt(Number(churn[namespace] ?? 0))}</td>
    </tr>`)
    .join('');
}

/**
 * @param {Array<Record<string, unknown>>} alerts
 * @returns {string}
 */
function renderAlertRows(alerts) {
  if (!Array.isArray(alerts) || alerts.length === 0) {
    return '<tr><td colspan="6">No active alerts.</td></tr>';
  }

  return alerts
    .map((alert) => {
      const severity = String(alert.severity || 'warning');
      const code = String(alert.code || 'REDIS_TIER0_ALERT');
      const namespace = String(alert.namespace || '-');
      const value = Number(alert.value || 0);
      const threshold = Number(alert.threshold || 0);
      const message = String(alert.message || 'Redis Tier-0 alert');
      return `<tr>
        <td><span class="redis-pill is-severity-${severity}">${severity}</span></td>
        <td>${code}</td>
        <td>${namespace}</td>
        <td>${fmt(value)}</td>
        <td>${fmt(threshold)}</td>
        <td>${message}</td>
      </tr>`;
    })
    .join('');
}

async function getCapabilityToken(action) {
  const response = await fetch(ENDPOINTS.capability(action), {
    method: 'GET',
    headers: {
      Accept: 'application/json',
    },
    credentials: 'same-origin',
  });

  const payload = await response.json();
  if (!response.ok || payload.status !== 'success') {
    const message = payload && typeof payload.message === 'string'
      ? payload.message
      : 'Capability request failed.';
    throw new Error(message);
  }

  const token = String(payload.capability?.token || '').trim();
  if (!token) {
    throw new Error('Capability token missing in response.');
  }

  return token;
}

async function apiRequest(url, method = 'GET', data = null, capabilityAction = '') {
  const options = {
    method,
    headers: {
      Accept: 'application/json',
    },
    credentials: 'same-origin',
  };

  if (data) {
    options.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
    options.body = new URLSearchParams(data).toString();
  }

  if (capabilityAction) {
    const token = await getCapabilityToken(capabilityAction);
    options.headers['X-PayCal-Capability'] = token;

    const payloadData = data ? { ...data } : {};
    payloadData.capability_token = token;
    options.headers['Content-Type'] = 'application/x-www-form-urlencoded;charset=UTF-8';
    options.body = new URLSearchParams(payloadData).toString();
  }

  const response = await fetch(url, options);
  const payload = await response.json();

  if (!response.ok || payload.status !== 'success') {
    const message = payload && typeof payload.message === 'string'
      ? payload.message
      : 'Request failed.';
    throw new Error(message);
  }

  return payload;
}

async function refreshSnapshot() {
  const payload = await apiRequest(ENDPOINTS.status, 'GET');
  const snapshot = payload.snapshot || {};
  const state = snapshot.state || {};
  const redis = snapshot.redis || {};
  const quotas = snapshot.tier0_quotas || {};
  const counts = snapshot.namespace_counts || {};
  const churn = snapshot.churn_per_minute || {};
  const alerts = snapshot.alerts || [];

  writeText('metric-failures', fmt(Number(state.failure_count ?? 0)));
  writeText('metric-successes', fmt(Number(state.success_count ?? 0)));
  writeText('metric-memory', `${fmt(Number(redis.used_memory_mb ?? 0))} MB`);
  writeText('metric-max-memory', `${fmt(Number(redis.max_memory_mb ?? 0))} MB`);
  writeText('metric-memory-percent', pct(Number(redis.memory_percent ?? 0)));
  writeText('metric-evicted', fmt(Number(redis.evicted_keys ?? 0)));
  writeText('metric-eviction-rate', fmt(Number(redis.eviction_rate_per_minute ?? 0)));
  writeText('metric-clients', fmt(Number(redis.connected_clients ?? 0)));
  writeText('metric-ops', fmt(Number(redis.instantaneous_ops_per_sec ?? 0)));
  writeText('metric-cpu-sys', fmt(Number(redis.used_cpu_sys ?? 0)));
  writeText('metric-cpu-user', fmt(Number(redis.used_cpu_user ?? 0)));

  const freezeEl = byId('metric-freeze');
  if (freezeEl) {
    Guardian.setHTML(freezeEl, freezePill(Boolean(state.mutation_freeze)));
  }

  const breakerEl = byId('metric-breaker');
  if (breakerEl) {
    Guardian.setHTML(breakerEl, breakerPill(String(state.breaker_state || 'closed')));
  }

  const quotaRows = Object.keys(quotas)
    .sort()
    .map((name) => renderQuotaRow(name, quotas[name]))
    .join('');

  const quotaTable = byId('redis-quota-table');
  if (quotaTable) {
    Guardian.setHTML(quotaTable, `<table>
      <thead>
        <tr><th>Namespace</th><th>Current</th><th>Quota</th><th>Use</th><th>Status</th></tr>
      </thead>
      <tbody>${quotaRows}</tbody>
    </table>`);
  }

  const churnTable = byId('redis-churn-table');
  if (churnTable) {
    Guardian.setHTML(churnTable, `<table>
      <thead>
        <tr><th>Namespace</th><th>Key Count</th><th>Churn/min</th></tr>
      </thead>
      <tbody>${renderChurnRows(counts, churn)}</tbody>
    </table>`);
  }

  const alertsTable = byId('redis-alerts-table');
  if (alertsTable) {
    Guardian.setHTML(alertsTable, `<table>
      <thead>
        <tr><th>Severity</th><th>Code</th><th>Namespace</th><th>Value</th><th>Threshold</th><th>Message</th></tr>
      </thead>
      <tbody>${renderAlertRows(alerts)}</tbody>
    </table>`);
  }

  setControlStatus(`Snapshot updated: ${snapshot.timestamp || 'now'}`, 'success');
}

function reasonValue() {
  const input = byId('redis-control-reason');
  if (!input) {
    return '';
  }
  const value = input.value.trim();
  return value.slice(0, 140);
}

async function setFreeze(enabled) {
  await apiRequest(ENDPOINTS.freeze, 'POST', {
    enabled: enabled ? '1' : '0',
    reason: reasonValue(),
  }, 'admin.redis.freeze');

  setControlStatus(enabled ? 'Mutation freeze enabled.' : 'Mutation freeze disabled.', 'success');
  await refreshSnapshot();
}

async function openBreaker() {
  await apiRequest(ENDPOINTS.openBreaker, 'POST', {
    reason: reasonValue() || 'Manual admin open',
  }, 'admin.redis.breaker.open');

  setControlStatus('Redis circuit breaker opened.', 'success');
  await refreshSnapshot();
}

async function resetBreaker() {
  await apiRequest(ENDPOINTS.resetBreaker, 'POST', null, 'admin.redis.breaker.reset');
  setControlStatus('Redis circuit breaker reset.', 'success');
  await refreshSnapshot();
}

function bindAction(buttonId, handler) {
  const button = byId(buttonId);
  if (!button) {
    return;
  }

  button.addEventListener('click', async () => {
    button.disabled = true;
    try {
      await handler();
    } catch (error) {
      const message = error instanceof Error ? error.message : 'Action failed.';
      setControlStatus(message, 'error');
    } finally {
      button.disabled = false;
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  bindAction('redis-refresh', refreshSnapshot);
  bindAction('redis-freeze-on', () => setFreeze(true));
  bindAction('redis-freeze-off', () => setFreeze(false));
  bindAction('redis-breaker-open', openBreaker);
  bindAction('redis-breaker-reset', resetBreaker);

  refreshSnapshot().catch((error) => {
    const message = error instanceof Error ? error.message : 'Unable to load snapshot.';
    setControlStatus(message, 'error');
  });
});
