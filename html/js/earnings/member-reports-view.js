/**
 * Member reports dialog — full My Earnings parity for business coordinators.
 * Mirrors /reports/ lazy section loading against business-scoped member APIs.
 */

import { drawLineGraph } from '/js/earnings/trend-chart.js';
import EarningsExport from '/js/earnings/earnings-export.js';
import {
  buildPieGraphDataset,
  createPieGraphHelpers,
  getPieGraphPalette,
} from '/js/earnings/pie-graph-core.js';
import { createEarningsFormatHelpers } from '/js/earnings/format.js';
import { resolveUserLocale } from '/js/core/locale.js';
import { escapeHtml } from '/js/core/escape.js';
import { formatI18n, getI18nLabel } from '/js/core/template.js';
import { initForecastWorkspace } from '/js/earnings/forecast-calculator.js';

const GRAPH_PREFIX = 'member_reports_line_graph_';

function parseDateKeyToLocalMs(dateKey) {
  const match = String(dateKey).match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) {
    return new Date(dateKey).getTime();
  }
  const year = Number(match[1]);
  const monthIndex = Number(match[2]) - 1;
  const day = Number(match[3]);
  return new Date(year, monthIndex, day, 12, 0, 0, 0).getTime();
}

function formatDateKeyForDisplay(dateKey, locale = undefined) {
  const match = String(dateKey).match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) {
    return new Date(dateKey).toLocaleDateString(locale, { month: '2-digit', day: '2-digit' });
  }
  const year = Number(match[1]);
  const monthIndex = Number(match[2]) - 1;
  const day = Number(match[3]);
  return new Date(Date.UTC(year, monthIndex, day, 12, 0, 0, 0)).toLocaleDateString(locale, {
    month: '2-digit',
    day: '2-digit',
    timeZone: 'UTC',
  });
}

function isIsoDateKey(value) {
  return /^\d{4}-\d{2}-\d{2}$/.test(String(value || ''));
}

function normalizeGrossPayload(payload) {
  if (!payload || typeof payload !== 'object') {
    return {};
  }
  const normalized = {};
  Object.entries(payload).forEach(([dateKey, amount]) => {
    if (!isIsoDateKey(dateKey)) {
      return;
    }
    const numericAmount = Number(amount);
    if (Number.isFinite(numericAmount)) {
      normalized[dateKey] = numericAmount;
    }
  });
  return normalized;
}

function extractDailyPayload(jsonResponse) {
  if (!jsonResponse || typeof jsonResponse !== 'object') {
    return {};
  }
  const dataCandidate = (jsonResponse.data && typeof jsonResponse.data === 'object')
    ? jsonResponse.data
    : (() => {
        const { status: _status, message: _message, ...rest } = jsonResponse;
        return rest;
      })();
  const normalized = {};
  Object.entries(dataCandidate).forEach(([key, value]) => {
    if (!isIsoDateKey(key) || !value || typeof value !== 'object' || Array.isArray(value)) {
      return;
    }
    normalized[key] = value;
  });
  return normalized;
}

function buildDailyGridElement(year, headers, rows, useLegacyPrivateColumns, getElement, config) {
  const gridRegion = document.createElement('div');
  gridRegion.className = `datagrid ${useLegacyPrivateColumns ? 'datagrid_cols_11' : 'datagrid_cols_4'} datagrid_layout_auto earnings_daily_datagrid`;
  gridRegion.dataset.grid = `member-reports-daily-${year}`;
  gridRegion.setAttribute('role', 'region');
  gridRegion.setAttribute('aria-label', formatI18n(
    config,
    'EARNINGS_DAILY_GRID_INSTRUCTIONS_FOR',
    'Daily earnings grid for {year}',
    { year },
  ));

  const gridTable = document.createElement('div');
  gridTable.className = 'datagrid_table';
  gridTable.setAttribute('role', 'grid');

  const headerRowGroup = document.createElement('div');
  headerRowGroup.className = 'datagrid_header_row';
  headerRowGroup.setAttribute('role', 'rowgroup');
  const headerContent = document.createElement('div');
  headerContent.className = 'datagrid_header_content';
  headerContent.setAttribute('role', 'row');
  headers.forEach((label, index) => {
    const heading = document.createElement('div');
    heading.className = 'datagrid_heading';
    heading.setAttribute('role', 'columnheader');
    heading.id = `member_reports_daily_${year}_col_${index + 1}`;
    heading.textContent = String(label || '');
    headerContent.appendChild(heading);
  });
  headerRowGroup.appendChild(headerContent);
  gridTable.appendChild(headerRowGroup);

  const bodyGroup = document.createElement('div');
  bodyGroup.className = 'datagrid_body';
  bodyGroup.setAttribute('role', 'rowgroup');
  if (rows.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'datagrid_empty';
    empty.setAttribute('role', 'status');
    empty.textContent = getI18nLabel(config, 'NOT_FOUND', 'Not Found');
    bodyGroup.appendChild(empty);
  } else {
    const legacyFieldList = ['date', 'site', 'wage', 'hours', 'regular', 'overtime', 'loa', 'travel', 'gross', 'tax', 'net'];
    const compactFieldList = ['date', 'gross', 'deductions', 'net'];
    const fieldList = useLegacyPrivateColumns ? legacyFieldList : compactFieldList;
    rows.forEach((row) => {
      const rowElement = document.createElement('div');
      rowElement.className = 'datagrid_row';
      rowElement.setAttribute('role', 'row');
      const rowContent = document.createElement('div');
      rowContent.className = 'datagrid_row_content';
      rowContent.setAttribute('role', 'presentation');
      fieldList.forEach((fieldName, fieldIndex) => {
        const colLabel = headers[fieldIndex] || fieldName;
        const colId = `member_reports_daily_${year}_col_${fieldIndex + 1}`;
        const cell = document.createElement('div');
        cell.className = 'datagrid_item';
        cell.setAttribute('role', 'gridcell');
        cell.setAttribute('aria-labelledby', colId);
        cell.dataset.colKey = fieldName;
        cell.dataset.colLabel = String(colLabel);
        cell.textContent = String(row[fieldName] ?? '');
        rowContent.appendChild(cell);
      });
      rowElement.appendChild(rowContent);
      bodyGroup.appendChild(rowElement);
    });
  }
  gridTable.appendChild(bodyGroup);
  gridRegion.appendChild(gridTable);
  return gridRegion;
}

function renderPieGraphsForYear(year, dailyPayload, getElement, pieHelpers) {
  const panel = getElement(`member_reports_piegraphs_panel_${year}`);
  if (!panel) {
    return;
  }
  const ytdSvg = getElement(`member_reports_piegraphs_ytd_svg_${year}`);
  const ytdLegend = getElement(`member_reports_piegraphs_ytd_legend_${year}`);
  const monthSelect = getElement(`member_reports_piegraphs_month_select_${year}`);
  const monthSvg = getElement(`member_reports_piegraphs_month_svg_${year}`);
  const monthLegend = getElement(`member_reports_piegraphs_month_legend_${year}`);
  if (!ytdSvg || !ytdLegend || !monthSelect || !monthSvg || !monthLegend) {
    return;
  }
  const palette = getPieGraphPalette(panel);
  const { renderPieSvg, monthLabelFromKey } = pieHelpers;
  const dataset = buildPieGraphDataset(dailyPayload);
  renderPieSvg(ytdSvg, ytdLegend, dataset.ytd, palette, window.Guardian);
  const months = Object.keys(dataset.monthly).sort();
  if (months.length === 0) {
    monthSelect.textContent = '';
    renderPieSvg(monthSvg, monthLegend, { gross: 0, deductions: 0, net: 0 }, palette, window.Guardian);
    return;
  }
  const selectedBefore = String(monthSelect.value || '');
  window.Guardian.setHTML(monthSelect, months.map((monthKey) => (
    `<option value="${escapeHtml(monthKey)}">${escapeHtml(monthLabelFromKey(monthKey))}</option>`
  )).join(''));
  const selected = months.includes(selectedBefore) ? selectedBefore : months[months.length - 1];
  monthSelect.value = selected;
  const renderSelectedMonth = () => {
    const selectedKey = String(monthSelect.value || '');
    renderPieSvg(
      monthSvg,
      monthLegend,
      dataset.monthly[selectedKey] || { gross: 0, deductions: 0, net: 0 },
      palette,
      window.Guardian,
    );
  };
  if (!monthSelect.dataset.piegraphsBound) {
    monthSelect.addEventListener('change', renderSelectedMonth);
    monthSelect.dataset.piegraphsBound = '1';
  }
  renderSelectedMonth();
}

/**
 * @param {HTMLElement} container Mount node containing [data-member-reports-root]
 * @param {object} options
 * @param {string} options.businessId
 * @param {string} options.memberUuid
 * @param {string} [options.memberName]
 * @param {object} [options.config] PC.config-like i18n map
 * @param {function} [options.buildHeaders] auth header builder
 * @param {function} [options.showToast]
 * @param {function} [options.resolveThrownMessage]
 */
export function initMemberReportsEarningsView(container, options = {}) {
  if (!(container instanceof HTMLElement)) {
    return () => {};
  }

  const root = container.querySelector('[data-member-reports-root]');
  if (!(root instanceof HTMLElement)) {
    return () => {};
  }

  const businessId = String(options.businessId || root.dataset.memberReportsBusinessId || '').trim();
  const memberUuid = String(options.memberUuid || root.dataset.memberReportsMemberUuid || '').trim();
  if (businessId === '' || memberUuid === '') {
    return () => {};
  }

  const config = options.config || {};
  const buildHeaders = typeof options.buildHeaders === 'function' ? options.buildHeaders : () => ({ Accept: 'application/json' });
  const showToast = typeof options.showToast === 'function' ? options.showToast : () => {};
  const resolveThrownMessage = typeof options.resolveThrownMessage === 'function'
    ? options.resolveThrownMessage
    : (error, fallbackMessage = 'Unable to complete the request.') => {
        const message = error instanceof Error && error.message ? error.message.trim() : '';
        return message || fallbackMessage;
      };
  const userLocale = String(config.USER_LOCALE || resolveUserLocale()).trim() || resolveUserLocale();
  const apiBase = `/api/v1/businesses/${encodeURIComponent(businessId)}/members/${encodeURIComponent(memberUuid)}/reports`;
  const hasPremiumReporting = root.dataset.memberReportsPremium === '1';
  const protectedExportEndpoint = (format) => (
    `${apiBase}/export/${encodeURIComponent(format)}`
  );
  const getElement = (id) => root.querySelector(`#${CSS.escape(id)}`) || document.getElementById(id);
  const formatHelpers = createEarningsFormatHelpers({ locale: userLocale });
  const trendChartOptions = {
    getElement,
    userLocale,
    svgIdPrefix: GRAPH_PREFIX,
    getI18nLabel: (key, fallback = '') => getI18nLabel(config, key, fallback),
    formatI18n: (key, fallback, params = {}) => formatI18n(config, key, fallback, params),
    formatHelpers,
  };
  const pieHelpers = createPieGraphHelpers({
    locale: userLocale,
    grossLabel: getI18nLabel(config, 'GROSS', 'Gross'),
    netLabel: getI18nLabel(config, 'NET', 'Net'),
    deductionsLabel: getI18nLabel(config, 'DEDUCTIONS', 'Deductions'),
    emptyLabel: getI18nLabel(config, 'EARNINGS_PIEGRAPHS_NO_VALUES', 'No values available.'),
    escapeHtml,
  });

  const graphDataCache = {};
  const loadedSections = new Set();
  const loadedGraphs = new Set();
  const loadedDaily = new Set();

  const fetchJson = async (path) => {
    const response = await fetch(`${apiBase}${path}`, {
      method: 'GET',
      headers: buildHeaders(),
      credentials: 'include',
    });
    if (!response.ok) {
      throw new Error(`Request failed (${response.status})`);
    }
    return response.json();
  };

  const fetchSectionHtml = async (section, year) => {
    const payload = await fetchJson(`/${section}/year/${encodeURIComponent(String(year))}`);
    const data = payload?.data && typeof payload.data === 'object' ? payload.data : payload;
    return typeof data?.html === 'string' ? data.html : '';
  };

  const fetchGrossYear = async (year) => {
    const payload = await fetchJson(`/gross/year/${encodeURIComponent(String(year))}`);
    const data = payload?.data && typeof payload.data === 'object' ? payload.data : payload;
    return normalizeGrossPayload(data?.gross_by_date ?? data);
  };

  const fetchDailyYearData = async (year) => {
    const payload = await fetchJson(`/daily/year/${encodeURIComponent(String(year))}`);
    return extractDailyPayload(payload);
  };

  const loadSection = async (section, year, targetId) => {
    const key = `${section}:${year}`;
    if (loadedSections.has(key)) {
      return;
    }
    const target = getElement(targetId);
    if (!(target instanceof HTMLElement)) {
      return;
    }
    try {
      const html = await fetchSectionHtml(section, year);
      window.Guardian.setHTML(
        target,
        html || `<p class="earnings_async_status">${escapeHtml(getI18nLabel(config, 'EARNINGS_ASYNC_NO_DATA', 'No data available.'))}</p>`,
      );
      loadedSections.add(key);
    } catch (error) {
      const message = resolveThrownMessage(error, 'Unable to load section.');
      window.Guardian.setHTML(
        target,
        `<p class="earnings_async_status">${escapeHtml(message)}</p>`,
      );
    }
  };

  const loadSectionsForYear = (year) => {
    loadSection('ytd', year, `member_reports_ytd_${year}`);
    loadSection('payperiods', year, `member_reports_pay_periods_${year}`);
    loadSection('monthly', year, `member_reports_monthly_${year}`);
  };

  const loadGraphForYear = (year) => {
    if (loadedGraphs.has(year)) {
      const svgId = `${GRAPH_PREFIX}${year}`;
      const svg = getElement(svgId);
      if (svg && graphDataCache[year] && svg.children.length === 0 && svg.parentElement?.clientWidth > 0) {
        drawLineGraph(graphDataCache[year], svgId, trendChartOptions);
      }
      return;
    }
    loadedGraphs.add(year);
    fetchGrossYear(year)
      .then((data) => {
        graphDataCache[year] = data;
        drawLineGraph(data, `${GRAPH_PREFIX}${year}`, trendChartOptions);
      })
      .catch(() => {});
  };

  const renderDailyYear = async (year) => {
    const dailySection = getElement(`member_reports_daily_earnings_${year}`);
    if (!(dailySection instanceof HTMLElement)) {
      return;
    }
    dailySection.textContent = '';
    let dailyData;
    try {
      dailyData = await fetchDailyYearData(year);
    } catch (error) {
      const message = resolveThrownMessage(error, 'Unable to load daily earnings.');
      showToast(formatI18n(config, 'EARNINGS_DAILY_LOAD_FAILED_FMT', 'Could not load daily earnings: {message}', { message }));
      return;
    }
    if (!dailyData || typeof dailyData !== 'object') {
      return;
    }
    const useLegacyPrivateColumns = Object.values(dailyData).some((record) => record && (
      record.site_name || record.wage || record.hours || record.regular_hours
      || record.overtime_hours || record.travel_hours || record.living_out_allowance || record.tax
    ));
    const headers = useLegacyPrivateColumns
      ? [
        getI18nLabel(config, 'DATE', 'Date'),
        getI18nLabel(config, 'SITE', 'Site'),
        getI18nLabel(config, 'WAGE', 'Wage'),
        getI18nLabel(config, 'HOURS', 'Hours'),
        getI18nLabel(config, 'REGULAR_HOURS', 'Regular'),
        getI18nLabel(config, 'OVERTIME_HOURS', 'OT'),
        getI18nLabel(config, 'LOA', 'LOA'),
        getI18nLabel(config, 'TRAVEL', 'Travel'),
        getI18nLabel(config, 'EARNINGS_LABEL', 'Gross'),
        getI18nLabel(config, 'DEDUCTIONS', 'Tax'),
        getI18nLabel(config, 'NET', 'Net'),
      ]
      : [
        getI18nLabel(config, 'DATE', 'Date'),
        getI18nLabel(config, 'EARNINGS_LABEL', 'Gross'),
        getI18nLabel(config, 'DEDUCTIONS', 'Deductions'),
        getI18nLabel(config, 'NET', 'Net'),
      ];
    const formatMoneyCell = (value) => formatHelpers.formatAmount(value, 2, 2);
    const rows = Object.entries(dailyData)
      .sort(([d1], [d2]) => parseDateKeyToLocalMs(d1) - parseDateKeyToLocalMs(d2))
      .map(([date, record], index) => ({
        id: `daily-${year}-${index}`,
        date: formatDateKeyForDisplay(date, userLocale),
        site: String(record.site_name || ''),
        wage: formatMoneyCell(record.wage || 0),
        hours: formatMoneyCell(record.hours || 0),
        regular: formatMoneyCell(record.regular_hours || 0),
        overtime: formatMoneyCell(record.overtime_hours || 0),
        travel: formatMoneyCell(record.travel_hours ?? record.travel ?? 0),
        loa: formatMoneyCell(record.living_out_allowance ?? record.loa ?? 0),
        gross: formatMoneyCell(record.gross || 0),
        tax: formatMoneyCell(record.tax || record.deductions || 0),
        deductions: formatMoneyCell(record.deductions || record.tax || 0),
        net: formatMoneyCell(record.net || 0),
      }));
    dailySection.appendChild(buildDailyGridElement(year, headers, rows, useLegacyPrivateColumns, getElement, config));
    renderPieGraphsForYear(year, dailyData, getElement, pieHelpers);
  };

  const loadDailyForYear = (year) => {
    if (loadedDaily.has(year)) {
      return;
    }
    loadedDaily.add(year);
    renderDailyYear(year).catch(() => {});
  };

  const loadForecast = async () => {
    const target = getElement('member_reports_forecast_content');
    if (!(target instanceof HTMLElement) || target.dataset.loaded === '1') {
      return;
    }
    try {
      const payload = await fetchJson('/forecast');
      const data = payload?.data && typeof payload.data === 'object' ? payload.data : payload;
      const html = typeof data?.html === 'string' ? data.html : '';
      window.Guardian.setHTML(
        target,
        html || `<p class="help_text">${escapeHtml(getI18nLabel(config, 'EARNINGS_FORECAST_NO_DATA', 'No forecast available.'))}</p>`,
      );
      target.dataset.loaded = '1';
      const workspace = target.querySelector('[data-forecast-workspace="1"]') || target;
      if (workspace instanceof HTMLElement && workspace.dataset.forecastState) {
        initForecastWorkspace(workspace, {
          config,
          previewUrl: `${apiBase}/forecast/preview`,
          locale: userLocale,
        });
      }
    } catch (error) {
      window.Guardian.setHTML(
        target,
        `<p class="earnings_async_status">${escapeHtml(error.message || getI18nLabel(config, 'EARNINGS_FORECAST_LOAD_FAILED', 'Unable to load forecast.'))}</p>`,
      );
    }
  };

  const activateYearTab = (tab) => {
    const targetId = String(tab.dataset.tabTarget || '').trim();
    const tabs = root.querySelectorAll('.member_reports_year_tabs [data-tab-target]');
    const tabContents = root.querySelectorAll('.member_reports_tab_content [data-tab-content]');

    tabs.forEach((candidate) => {
      if (!(candidate instanceof HTMLElement)) {
        return;
      }
      const isActive = candidate === tab;
      candidate.classList.toggle('active', isActive);
      candidate.setAttribute('aria-selected', isActive ? 'true' : 'false');
      candidate.setAttribute('tabindex', isActive ? '0' : '-1');
    });

    tabContents.forEach((candidate) => {
      if (!(candidate instanceof HTMLElement)) {
        return;
      }
      candidate.classList.toggle('active', candidate.id === targetId);
    });

    if (targetId.endsWith('-forecast')) {
      loadForecast().catch(() => {});
      return;
    }

    const year = parseInt(targetId.replace('member_reports_tab-', ''), 10);
    if (!Number.isFinite(year)) {
      return;
    }
    loadSectionsForYear(year);
    loadGraphForYear(year);
    loadDailyForYear(year);
  };

  const tabs = Array.from(root.querySelectorAll('.member_reports_year_tabs [data-tab-target]'))
    .filter((tab) => tab instanceof HTMLElement);

  tabs.forEach((tab, index) => {
    if (tab.dataset.memberReportsBound === '1') {
      return;
    }
    tab.dataset.memberReportsBound = '1';
    tab.addEventListener('click', () => activateYearTab(tab));
    tab.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        activateYearTab(tab);
        return;
      }
      if (event.key === 'ArrowRight' || event.key === 'ArrowLeft' || event.key === 'Home' || event.key === 'End') {
        event.preventDefault();
        let nextIndex = index;
        if (event.key === 'ArrowRight') {
          nextIndex = (index + 1) % tabs.length;
        } else if (event.key === 'ArrowLeft') {
          nextIndex = (index - 1 + tabs.length) % tabs.length;
        } else if (event.key === 'Home') {
          nextIndex = 0;
        } else if (event.key === 'End') {
          nextIndex = tabs.length - 1;
        }
        const nextTab = tabs[nextIndex];
        if (nextTab instanceof HTMLElement) {
          activateYearTab(nextTab);
          nextTab.focus();
        }
      }
    });
  });

  const activeTab = root.querySelector('.member_reports_year_tabs [data-tab-target].active')
    || root.querySelector('.member_reports_year_tabs [data-tab-target]');
  if (activeTab instanceof HTMLElement) {
    activateYearTab(activeTab);
  }

  const onExportClick = async (event) => {
    const button = event.target.closest('[data-member-export-scope][data-member-export-format]');
    if (!button || !root.contains(button)) {
      return;
    }
    event.preventDefault();
    const scope = (button.dataset.memberExportScope || 'yearly').toLowerCase();
    const format = (button.dataset.memberExportFormat || '').toLowerCase();
    const year = button.dataset.memberExportYear || '';
    const originalText = button.textContent;
    try {
      button.disabled = true;
      button.textContent = '...';
      const fileSuffix = String(year);
      if (format !== 'pdf' && !hasPremiumReporting) {
        throw new Error('Premium subscription required for this export format.');
      }
      if (format === 'xlsx' || format === 'pdf') {
        const response = await fetch(protectedExportEndpoint(format), {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ scope, year: Number(year) }),
        });
        if (!response.ok) {
          const text = await response.text();
          throw new Error(`Export failed (${response.status}): ${text}`);
        }
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `paycal-member-${scope}-${fileSuffix}.${format}`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        return;
      }

      const dailyPayload = await fetchDailyYearData(Number(year));
      const rows = EarningsExport.buildDetailedRows(dailyPayload);
      if (!rows.length) {
        throw new Error('No earnings records found for this export range.');
      }
      const browserConvenienceSuffix = `${fileSuffix}-browser-convenience`;
      const reportParams = {
        year: Number(year),
        employee: memberUuid,
        fullName: String(options.memberName || ''),
        referenceCode: '',
        rows,
      };
      let report = null;
      if (scope === 'yearly') {
        report = EarningsExport.buildYearlyReportJson(reportParams);
      } else if (scope === 'monthly') {
        report = EarningsExport.buildMonthlyReportJson(reportParams);
      } else if (scope === 'daily') {
        report = EarningsExport.buildDailyReportJson(reportParams);
      } else {
        throw new Error(`Unsupported export scope: ${scope}`);
      }
      if (format === 'csv') {
        const csv = scope === 'yearly'
          ? EarningsExport.generateYearlyCsv(rows, report)
          : scope === 'monthly'
            ? EarningsExport.generateMonthlyCsv(rows, report)
            : EarningsExport.generateDailyCsv(rows, report);
        EarningsExport.downloadTextFile(csv, `paycal-member-${scope}-${browserConvenienceSuffix}.csv`, 'text/csv;charset=utf-8');
      } else if (format === 'txt') {
        const txt = scope === 'yearly'
          ? EarningsExport.generateYearlyTxt(rows, report)
          : scope === 'monthly'
            ? EarningsExport.generateMonthlyTxt(rows, report)
            : EarningsExport.generateDailyTxt(rows, report);
        EarningsExport.downloadTextFile(txt, `paycal-member-${scope}-${browserConvenienceSuffix}.txt`, 'text/plain;charset=utf-8');
      }
    } catch (error) {
      const message = resolveThrownMessage(error, 'Unable to export report.');
      showToast(formatI18n(config, 'EARNINGS_EXPORT_FAILED_FMT', 'Export failed: {message}', { message }));
    } finally {
      button.disabled = false;
      button.textContent = originalText;
    }
  };

  root.addEventListener('click', onExportClick);

  return () => {
    root.removeEventListener('click', onExportClick);
  };
}

export default { initMemberReportsEarningsView };
