/**
 * Personal Earnings Forecast workspace — calculator, scenarios, assumptions, timeline.
 */

import { createEarningsFormatHelpers } from '/js/earnings/format.js';
import { resolveUserLocale } from '/js/earnings/locale.js';

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function getI18nLabel(config, key, fallback = '') {
  const value = String(config?.[key] ?? '').trim();
  return value !== '' ? value : fallback;
}

function formatI18n(config, key, fallback, params = {}) {
  let label = getI18nLabel(config, key, fallback);
  Object.entries(params).forEach(([paramKey, paramValue]) => {
    label = label.replace(new RegExp(`\\{${paramKey}\\}`, 'g'), String(paramValue));
  });
  return label;
}

function centsToInputDollars(cents) {
  const numeric = Number(cents);
  if (!Number.isFinite(numeric)) {
    return '';
  }
  return (numeric / 100).toFixed(2);
}

function dollarsInputToCents(value) {
  const numeric = Number(String(value ?? '').replace(/[^0-9.-]/g, ''));
  if (!Number.isFinite(numeric)) {
    return null;
  }
  return Math.round(numeric * 100);
}

const SCENARIO_KEYS = {
  conservative: 'EARNINGS_FORECAST_SCENARIO_CONSERVATIVE',
  normal: 'EARNINGS_FORECAST_SCENARIO_NORMAL',
  overtime: 'EARNINGS_FORECAST_SCENARIO_OVERTIME',
  custom: 'EARNINGS_FORECAST_SCENARIO_CUSTOM',
};

const SUMMARY_CARD_KEYS = {
  next_paycheck: 'EARNINGS_FORECAST_NEXT_PAYCHECK',
  next_30_days: 'EARNINGS_FORECAST_NEXT_30_DAYS',
  year_projection: 'EARNINGS_FORECAST_YEAR_PROJECTION',
};

const SOURCE_BADGE_KEYS = {
  saved: 'EARNINGS_FORECAST_SOURCE_SAVED',
  scheduled: 'EARNINGS_FORECAST_SOURCE_SCHEDULED',
  temporary: 'EARNINGS_FORECAST_SOURCE_TEMPORARY',
  estimated: 'EARNINGS_FORECAST_SOURCE_ESTIMATED',
  missing: 'EARNINGS_FORECAST_SOURCE_MISSING',
};

const CONFIDENCE_KEYS = {
  high: 'EARNINGS_FORECAST_CONFIDENCE_HIGH',
  medium: 'EARNINGS_FORECAST_CONFIDENCE_MEDIUM',
  low: 'EARNINGS_FORECAST_CONFIDENCE_LOW',
};

function buildSummaryCardsHtml(state, config, formatHelpers) {
  const projection = state?.forecast_state?.projection_result?.summary_cards ?? {};
  const confidence = state?.forecast_state?.confidence ?? 'low';
  const confidenceLabel = getI18nLabel(config, CONFIDENCE_KEYS[confidence] || '', confidence);
  const ytdGrossCents = Math.max(0, Number(state?.forecast_state?.ytd_context?.gross_cents) || 0);
  const progressLabel = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_PROGRESS_LABEL', 'YTD vs forecast'));
  const currentLabel = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_PROGRESS_CURRENT', 'Current'));
  const forecastLabel = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_PROGRESS_FORECAST', 'Forecast'));

  return Object.entries(SUMMARY_CARD_KEYS).map(([key, labelKey]) => {
    const card = projection[key] ?? {};
    const title = escapeHtml(getI18nLabel(config, labelKey, key));
    const net = formatHelpers.formatCurrency((Number(card.net_cents) || 0) / 100);
    const gross = formatHelpers.formatCurrency((Number(card.gross_cents) || 0) / 100);
    const hours = formatHelpers.formatHours(Number(card.hours) || 0);
    const grossLabel = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_CARD_GROSS', 'Gross'));
    const hoursLabel = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_CARD_HOURS', 'Hours'));
    const confidenceField = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_CARD_CONFIDENCE', 'Confidence'));
    const grossCents = Math.max(0, Number(card.gross_cents) || 0);
    const progressMax = Math.max(1, grossCents);
    const progressValue = Math.max(0, Math.min(progressMax, ytdGrossCents));
    const progressPct = progressMax > 0 ? Math.round((progressValue / progressMax) * 100) : 0;
    const progressText = `${currentLabel} ${formatHelpers.formatCurrency(progressValue / 100)} / ${forecastLabel} ${gross}`;

    return `<article class="forecast-summary-card" aria-label="${title}">
      <h3 class="forecast-summary-card__title">${title}</h3>
      <p class="forecast-summary-card__net">${net}</p>
      <dl class="forecast-summary-card__meta">
        <div><dt>${grossLabel}</dt><dd>${gross}</dd></div>
        <div><dt>${hoursLabel}</dt><dd>${hours}</dd></div>
        <div><dt>${confidenceField}</dt><dd>${escapeHtml(confidenceLabel)}</dd></div>
      </dl>
      <div class="forecast-summary-card__progress">
        <div class="forecast-summary-card__progress-label">
          <span>${progressLabel}</span>
          <strong>${progressPct}%</strong>
        </div>
        <progress class="forecast-summary-card__progress-bar" max="${progressMax}" value="${progressValue}" aria-label="${escapeHtml(progressText)}"></progress>
      </div>
    </article>`;
  }).join('');
}

function buildScenarioCardsHtml(state, config, formatHelpers, activeScenario) {
  const scenarios = state?.forecast_state?.projection_result?.scenarios ?? {};
  return Object.entries(SCENARIO_KEYS).map(([scenarioKey, labelKey]) => {
    const data = scenarios[scenarioKey] ?? {};
    const label = escapeHtml(getI18nLabel(config, labelKey, scenarioKey));
    const net = formatHelpers.formatCurrency((Number(data.net_cents) || 0) / 100);
    const isActive = scenarioKey === activeScenario;
    return `<button type="button" class="forecast-scenario-card${isActive ? ' forecast-scenario-card--active' : ''}"
      data-forecast-scenario="${escapeHtml(scenarioKey)}" aria-pressed="${isActive ? 'true' : 'false'}">
      <span class="forecast-scenario-card__label">${label}</span>
      <span class="forecast-scenario-card__net">${net}</span>
    </button>`;
  }).join('');
}

function buildAssumptionsHtml(state, config) {
  const rows = state?.forecast_state?.assumption_sources ?? [];
  if (!Array.isArray(rows) || rows.length === 0) {
    return `<p class="help_text">${escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_ASSUMPTIONS_EMPTY', 'No assumptions available.'))}</p>`;
  }

  const fieldLabel = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_ASSUMP_FIELD', 'Field'));
  const valueLabel = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_ASSUMP_VALUE', 'Value'));
  const sourceLabel = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_ASSUMP_SOURCE', 'Source'));

  const body = rows.map((row) => {
    const label = escapeHtml(getI18nLabel(config, row.label_key || '', row.field || ''));
    const value = escapeHtml(row.value ?? getI18nLabel(config, 'EARNINGS_FORECAST_VALUE_MISSING', '—'));
    const sourceKey = SOURCE_BADGE_KEYS[row.source] || 'EARNINGS_FORECAST_SOURCE_MISSING';
    const source = escapeHtml(getI18nLabel(config, sourceKey, row.source || ''));
    return `<tr>
      <th scope="row">${label}</th>
      <td>${value}</td>
      <td><span class="forecast-source-badge forecast-source-badge--${escapeHtml(row.source || 'missing')}">${source}</span></td>
    </tr>`;
  }).join('');

  return `<table class="forecast-assumptions-table">
    <thead><tr><th scope="col">${fieldLabel}</th><th scope="col">${valueLabel}</th><th scope="col">${sourceLabel}</th></tr></thead>
    <tbody>${body}</tbody>
  </table>`;
}

function buildTimelineHtml(state, config, formatHelpers) {
  const segments = state?.forecast_state?.projection_result?.timeline ?? [];
  if (!Array.isArray(segments) || segments.length === 0) {
    return '';
  }

  const bars = segments.map((segment) => {
    const label = escapeHtml(getI18nLabel(config, segment.label_key || '', segment.id || ''));
    const pctRaw = Math.max(4, Math.min(100, Number(segment.net_pct) || 0));
    const widthBucket = Math.max(1, Math.min(12, Math.ceil(pctRaw / (100 / 12))));
    const net = formatHelpers.formatCurrency((Number(segment.net_cents) || 0) / 100);
    const range = `${segment.window_start || ''} – ${segment.window_end || ''}`;
    const srText = escapeHtml(formatI18n(config, 'EARNINGS_FORECAST_TIMELINE_SR_FMT', '{label}: {net} ({range})', {
      label,
      net,
      range,
    }));
    return `<div class="forecast-timeline__segment">
      <div class="forecast-timeline__bar-wrap" aria-hidden="true">
        <div class="forecast-timeline__bar forecast-timeline__bar--w-${widthBucket}"></div>
      </div>
      <div class="forecast-timeline__label">${label}</div>
      <div class="forecast-timeline__value">${net}</div>
      <span class="visually_hidden">${srText}</span>
    </div>`;
  }).join('');

  const title = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_TIMELINE_TITLE', 'Forecast Timeline'));
  return `<section class="forecast-timeline" aria-label="${title}"><h3 class="forecast-panel__heading">${title}</h3>${bars}</section>`;
}

function buildCalculatorFormHtml(state, config) {
  const profile = state?.forecast_state?.profile_defaults ?? {};
  const wage = centsToInputDollars(profile.wage_rate_cents);
  const loa = centsToInputDollars(profile.loa_per_day_cents);
  const regHrs = String(profile.regular_hours_week ?? '');
  const otHrs = String(profile.overtime_hours_week ?? '0');
  const travel = String(profile.travel_hours_week ?? '0');
  const province = String(profile.province ?? 'AB');
  const payFreq = String(profile.pay_frequency ?? 'biweekly');
  const anchor = String(profile.anchor_date ?? '');
  const ytdGross = centsToInputDollars(state?.forecast_state?.ytd_context?.gross_cents ?? 0);

  const provinces = ['AB', 'BC', 'SK', 'MB', 'ON', 'QC', 'NS', 'NB', 'NL', 'PE', 'YT', 'NT', 'NU'];
  const provinceOptions = provinces.map((code) => {
    const selected = code === province ? ' selected' : '';
    return `<option value="${code}"${selected}>${code}</option>`;
  }).join('');

  const frequencies = ['weekly', 'biweekly', 'semimonthly', 'monthly'];
  const freqOptions = frequencies.map((freq) => {
    const selected = freq === payFreq ? ' selected' : '';
    const label = escapeHtml(getI18nLabel(config, `EARNINGS_FORECAST_PAY_FREQ_${freq.toUpperCase()}`, freq));
    return `<option value="${freq}"${selected}>${label}</option>`;
  }).join('');

  const title = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_CALC_TITLE', 'Forecast Calculator'));
  const resetProfile = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_RESET_PROFILE', 'Reset to Profile'));
  const resetScheduled = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_RESET_SCHEDULED', 'Reset to Scheduled Work'));

  const field = (id, labelKey, fallback, value, type = 'text', step = '') => {
    const label = escapeHtml(getI18nLabel(config, labelKey, fallback));
    const stepAttr = step ? ` step="${step}"` : '';
    return `<div class="forecast-calc__field">
      <label for="${id}">${label}</label>
      <input id="${id}" name="${id}" type="${type}" value="${escapeHtml(value)}"${stepAttr} data-forecast-input="1">
    </div>`;
  };

  return `<section class="forecast-calculator" aria-label="${title}">
    <h3 class="forecast-panel__heading">${title}</h3>
    <form class="forecast-calc__form" data-forecast-form="1">
      ${field('forecast_wage', 'EARNINGS_FORECAST_CALC_WAGE', 'Hourly wage ($)', wage, 'number', '0.01')}
      ${field('forecast_reg_hrs', 'EARNINGS_FORECAST_CALC_REG_HRS', 'Regular hrs/week', regHrs, 'number', '0.1')}
      ${field('forecast_ot_hrs', 'EARNINGS_FORECAST_CALC_OT_HRS', 'OT hrs/week', otHrs, 'number', '0.1')}
      ${field('forecast_loa', 'EARNINGS_FORECAST_CALC_LOA', 'LOA / day ($)', loa, 'number', '0.01')}
      ${field('forecast_travel', 'EARNINGS_FORECAST_CALC_TRAVEL', 'Travel hrs/week', travel, 'number', '0.1')}
      <div class="forecast-calc__field">
        <label for="forecast_province">${escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_CALC_PROVINCE', 'Province'))}</label>
        <select id="forecast_province" name="forecast_province" data-forecast-input="1">${provinceOptions}</select>
      </div>
      <div class="forecast-calc__field">
        <label for="forecast_pay_freq">${escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_CALC_PAY_FREQ', 'Pay frequency'))}</label>
        <select id="forecast_pay_freq" name="forecast_pay_freq" data-forecast-input="1">${freqOptions}</select>
      </div>
      ${field('forecast_anchor', 'EARNINGS_FORECAST_CALC_ANCHOR', 'Rotation anchor', anchor, 'date')}
      ${field('forecast_ytd_gross', 'EARNINGS_FORECAST_CALC_YTD_GROSS', 'YTD gross ($)', ytdGross, 'number', '0.01')}
      <div class="forecast-calc__actions">
        <button type="button" class="btn btn--secondary" data-forecast-reset="profile">${resetProfile}</button>
        <button type="button" class="btn btn--secondary" data-forecast-reset="scheduled">${resetScheduled}</button>
      </div>
    </form>
  </section>`;
}

function renderWorkspace(root, state, config, formatHelpers, activeScenario) {
  const disclaimer = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_DISCLAIMER', ''));
  const scenariosTitle = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_SCENARIOS_TITLE', 'Scenario Comparison'));
  const assumptionsTitle = escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_ASSUMPTIONS_TITLE', 'Assumptions'));

  root.innerHTML = `<div class="forecast-workspace__grid">
    <div class="forecast-workspace__summary" aria-live="polite">${buildSummaryCardsHtml(state, config, formatHelpers)}</div>
    <div class="forecast-workspace__main">
      ${buildCalculatorFormHtml(state, config)}
      <section class="forecast-scenarios" aria-label="${scenariosTitle}">
        <h3 class="forecast-panel__heading">${scenariosTitle}</h3>
        <div class="forecast-scenarios__cards" role="group">${buildScenarioCardsHtml(state, config, formatHelpers, activeScenario)}</div>
      </section>
    </div>
    <aside class="forecast-workspace__aside">
      <section class="forecast-assumptions" aria-label="${assumptionsTitle}">
        <h3 class="forecast-panel__heading">${assumptionsTitle}</h3>
        ${buildAssumptionsHtml(state, config)}
      </section>
      ${buildTimelineHtml(state, config, formatHelpers)}
    </aside>
    <p class="forecast_estimate_disclaimer">${disclaimer}</p>
  </div>`;
}

function readOverridesFromForm(form) {
  if (!(form instanceof HTMLFormElement)) {
    return {};
  }
  const wageCents = dollarsInputToCents(form.querySelector('#forecast_wage')?.value);
  const loaCents = dollarsInputToCents(form.querySelector('#forecast_loa')?.value);
  const ytdCents = dollarsInputToCents(form.querySelector('#forecast_ytd_gross')?.value);
  const regHrs = Number(form.querySelector('#forecast_reg_hrs')?.value);
  const otHrs = Number(form.querySelector('#forecast_ot_hrs')?.value);
  const travelHrs = Number(form.querySelector('#forecast_travel')?.value);

  const overrides = {};
  if (wageCents !== null) overrides.wage_rate_cents = wageCents;
  if (Number.isFinite(regHrs)) overrides.regular_hours_week = regHrs;
  if (Number.isFinite(otHrs)) overrides.overtime_hours_week = otHrs;
  if (loaCents !== null) overrides.loa_per_day_cents = loaCents;
  if (Number.isFinite(travelHrs)) overrides.travel_hours_week = travelHrs;
  overrides.province = String(form.querySelector('#forecast_province')?.value || '').trim();
  overrides.pay_frequency = String(form.querySelector('#forecast_pay_freq')?.value || '').trim();
  overrides.anchor_date = String(form.querySelector('#forecast_anchor')?.value || '').trim();
  if (ytdCents !== null) overrides.ytd_gross_cents = ytdCents;

  return overrides;
}

function populateFormFromProfile(form, state) {
  const profile = state?.forecast_state?.profile_defaults ?? {};
  const ytd = state?.forecast_state?.ytd_context ?? {};
  if (!(form instanceof HTMLFormElement)) {
    return;
  }
  const set = (id, value) => {
    const el = form.querySelector(`#${id}`);
    if (el instanceof HTMLInputElement || el instanceof HTMLSelectElement) {
      el.value = value;
    }
  };
  set('forecast_wage', centsToInputDollars(profile.wage_rate_cents));
  set('forecast_reg_hrs', String(profile.regular_hours_week ?? ''));
  set('forecast_ot_hrs', String(profile.overtime_hours_week ?? '0'));
  set('forecast_loa', centsToInputDollars(profile.loa_per_day_cents));
  set('forecast_travel', String(profile.travel_hours_week ?? '0'));
  set('forecast_province', String(profile.province ?? 'AB'));
  set('forecast_pay_freq', String(profile.pay_frequency ?? 'biweekly'));
  set('forecast_anchor', String(profile.anchor_date ?? ''));
  set('forecast_ytd_gross', centsToInputDollars(ytd.gross_cents ?? 0));
}

function populateFormFromScheduled(form, state) {
  const scheduled = state?.forecast_state?.scheduled_periods ?? {};
  const profile = state?.forecast_state?.profile_defaults ?? {};
  if (!(form instanceof HTMLFormElement)) {
    return;
  }
  const set = (id, value) => {
    const el = form.querySelector(`#${id}`);
    if (el instanceof HTMLInputElement || el instanceof HTMLSelectElement) {
      el.value = value;
    }
  };
  if (scheduled.wage_rate_cents != null) set('forecast_wage', centsToInputDollars(scheduled.wage_rate_cents));
  if (scheduled.regular_hours_week != null) set('forecast_reg_hrs', String(scheduled.regular_hours_week));
  if (scheduled.overtime_hours_week != null) set('forecast_ot_hrs', String(scheduled.overtime_hours_week));
  if (scheduled.loa_per_day_cents != null) set('forecast_loa', centsToInputDollars(scheduled.loa_per_day_cents));
  if (scheduled.travel_hours_week != null) set('forecast_travel', String(scheduled.travel_hours_week));
  set('forecast_province', String(profile.province ?? 'AB'));
  set('forecast_pay_freq', String(profile.pay_frequency ?? 'biweekly'));
  set('forecast_anchor', String(profile.anchor_date ?? ''));
}

function announceSummary(state, config, formatHelpers, srEl) {
  if (!(srEl instanceof HTMLElement)) {
    return;
  }
  const card = state?.forecast_state?.projection_result?.summary_cards?.next_paycheck ?? {};
  const net = formatHelpers.formatCurrency((Number(card.net_cents) || 0) / 100);
  srEl.textContent = formatI18n(config, 'EARNINGS_FORECAST_SUMMARY_UPDATED_FMT', 'Next paycheck estimate updated: {net}', { net });
}

/**
 * @param {HTMLElement} container
 * @param {object} options
 * @param {object} options.config i18n map
 * @param {object} [options.initialState]
 * @param {string} [options.previewUrl]
 * @param {string} [options.locale]
 */
export function initForecastWorkspace(container, options = {}) {
  if (!(container instanceof HTMLElement)) {
    return () => {};
  }

  const config = options.config && typeof options.config === 'object' ? options.config : {};
  const formatHelpers = createEarningsFormatHelpers({ locale: options.locale || resolveUserLocale() });
  let state = options.initialState && typeof options.initialState === 'object' ? options.initialState : {};
  let activeScenario = state?.forecast_state?.active_scenario || 'normal';
  let debounceTimer = null;
  const previewUrl = String(options.previewUrl || '/api/v1/forecast/preview');
  const mount = container.querySelector('[data-forecast-mount="1"]') || container;
  const srEl = container.querySelector('#forecast_summary_sr')
    || document.getElementById('forecast_summary_sr');

  if (container.dataset.forecastState) {
    try {
      state = JSON.parse(container.dataset.forecastState);
    } catch {
      // keep passed initialState
    }
  }

  const paint = () => {
    renderWorkspace(mount, state, config, formatHelpers, activeScenario);
    bindEvents();
    announceSummary(state, config, formatHelpers, srEl);
  };

  const requestPreview = async (overrides = {}, scenario = activeScenario) => {
    const response = await fetch(previewUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ overrides, scenario }),
    });
    const payload = await response.json();
    if (!response.ok || payload?.status !== 'success') {
      throw new Error(payload?.message || getI18nLabel(config, 'EARNINGS_FORECAST_PREVIEW_FAILED', 'Preview failed.'));
    }
    state = payload.data && typeof payload.data === 'object' ? payload.data : payload;
    activeScenario = scenario;
    paint();
  };

  const schedulePreview = () => {
    window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(() => {
      const form = mount.querySelector('[data-forecast-form="1"]');
      requestPreview(readOverridesFromForm(form), activeScenario).catch(() => {});
    }, 350);
  };

  const bindEvents = () => {
    const form = mount.querySelector('[data-forecast-form="1"]');
    form?.querySelectorAll('[data-forecast-input]').forEach((input) => {
      input.addEventListener('input', schedulePreview);
      input.addEventListener('change', schedulePreview);
    });

    form?.querySelectorAll('[data-forecast-reset]').forEach((button) => {
      button.addEventListener('click', () => {
        const mode = button.getAttribute('data-forecast-reset');
        if (mode === 'scheduled') {
          populateFormFromScheduled(form, state);
        } else {
          populateFormFromProfile(form, state);
        }
        activeScenario = 'custom';
        schedulePreview();
      });
    });

    mount.querySelectorAll('[data-forecast-scenario]').forEach((button) => {
      button.addEventListener('click', () => {
        const scenario = button.getAttribute('data-forecast-scenario') || 'normal';
        activeScenario = scenario;
        const overrides = scenario === 'custom' ? readOverridesFromForm(form) : {};
        requestPreview(overrides, scenario).catch(() => {});
      });
      button.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          button.click();
        }
      });
    });
  };

  if (state?.setup_required) {
    return () => {};
  }

  paint();
  return () => window.clearTimeout(debounceTimer);
}

export default { initForecastWorkspace };
