/**
 * Shared pie graph dataset, locale formatting, and SVG rendering for earnings views.
 */

import { resolveUserLocale } from '/js/core/locale.js';

function defaultEscapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function isIsoDateKey(value) {
  return /^\d{4}-\d{2}-\d{2}$/.test(String(value || ''));
}

function parseMoneyLike(value) {
  const normalized = String(value ?? '0').replace(/[^0-9.-]/g, '');
  const amount = Number(normalized);
  return Number.isFinite(amount) ? amount : 0;
}

/**
 * @param {object} [options]
 * @param {string} [options.locale]
 * @param {string} [options.grossLabel]
 * @param {string} [options.netLabel]
 * @param {string} [options.deductionsLabel]
 * @param {string} [options.emptyLabel]
 * @param {function} [options.escapeHtml]
 */
export function createPieGraphHelpers(options = {}) {
  const locale = String(options.locale || resolveUserLocale()).trim() || 'en-US';
  const labels = {
    gross: String(options.grossLabel || 'Gross'),
    net: String(options.netLabel || 'Net'),
    deductions: String(options.deductionsLabel || 'Deductions'),
    empty: String(options.emptyLabel || 'No values available.'),
  };
  const escapeHtml = typeof options.escapeHtml === 'function' ? options.escapeHtml : defaultEscapeHtml;

  const amountFormatter = new Intl.NumberFormat(locale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
  const percentFormatter = new Intl.NumberFormat(locale, {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  });
  const monthFormatter = new Intl.DateTimeFormat(locale, {
    month: 'short',
    year: 'numeric',
  });

  const formatPieAmount = (value) => `$${amountFormatter.format(Number(value) || 0)}`;
  const formatPiePercent = (value) => `${percentFormatter.format(Number(value) || 0)}%`;

  const monthLabelFromKey = (monthKey) => {
    const [year, month] = String(monthKey).split('-');
    const y = Number(year);
    const m = Number(month);
    if (!Number.isFinite(y) || !Number.isFinite(m) || m < 1 || m > 12) {
      return String(monthKey);
    }
    return monthFormatter.format(new Date(y, m - 1, 1));
  };

  const pieSegmentsFromTotals = (totals, palette) => {
    const colors = palette || {};
    const normalizedTotals = normalizeCompositionTotals(totals);
    return [
      {
        key: 'net',
        label: labels.net,
        value: normalizedTotals.net,
        color: String(colors.net || '#8bb7e6'),
      },
      {
        key: 'deductions',
        label: labels.deductions,
        value: normalizedTotals.deductions,
        color: String(colors.deductions || '#f2d2a6'),
      },
    ];
  };

  const renderPieSvg = (svgEl, legendEl, totals, palette, guardian = globalThis.Guardian) => {
    if (!svgEl || !legendEl) {
      return;
    }

    const segments = pieSegmentsFromTotals(totals, palette);
    const normalizedTotals = normalizeCompositionTotals(totals);
    const total = normalizedTotals.gross;
    svgEl.textContent = '';

    if (!Number.isFinite(total) || total <= 0) {
      if (guardian && typeof guardian.setHTML === 'function') {
        guardian.setHTML(
          legendEl,
          `<p class="earnings_piegraphs_empty">${escapeHtml(labels.empty)}</p>`,
        );
      }
      return;
    }

    const cx = 120;
    const cy = 120;
    const r = 90;
    let start = -Math.PI / 2;
    const parts = [];

    segments.forEach((seg) => {
      const ratio = seg.value / total;
      const sweep = ratio * Math.PI * 2;
      const end = start + sweep;

      const x1 = cx + r * Math.cos(start);
      const y1 = cy + r * Math.sin(start);
      const x2 = cx + r * Math.cos(end);
      const y2 = cy + r * Math.sin(end);
      const largeArc = sweep > Math.PI ? 1 : 0;

      const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      path.setAttribute('d', `M ${cx} ${cy} L ${x1} ${y1} A ${r} ${r} 0 ${largeArc} 1 ${x2} ${y2} Z`);
      path.setAttribute('fill', seg.color);
      path.setAttribute('class', `earnings_piegraphs_slice earnings_piegraphs_slice_${seg.key}`);
      path.dataset.segKey = seg.key;
      const title = document.createElementNS('http://www.w3.org/2000/svg', 'title');
      title.textContent = `${seg.label}: ${formatPieAmount(seg.value)} (${formatPiePercent(ratio * 100)})`;
      path.appendChild(title);
      svgEl.appendChild(path);

      parts.push({ ...seg, pct: ratio * 100 });
      start = end;
    });

    const cutout = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    cutout.setAttribute('cx', String(cx));
    cutout.setAttribute('cy', String(cy));
    cutout.setAttribute('r', '46');
    cutout.setAttribute('class', 'earnings_piegraphs_cutout');
    cutout.setAttribute('fill', 'var(--surface, #111)');
    svgEl.appendChild(cutout);

    const totalText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    totalText.setAttribute('x', String(cx));
    totalText.setAttribute('y', String(cy + 4));
    totalText.setAttribute('text-anchor', 'middle');
    totalText.setAttribute('class', 'earnings_piegraphs_total');
    totalText.textContent = formatPieAmount(total);
    const totalTitle = document.createElementNS('http://www.w3.org/2000/svg', 'title');
    totalTitle.textContent = `${labels.gross}: ${formatPieAmount(total)}`;
    totalText.appendChild(totalTitle);
    svgEl.appendChild(totalText);

    if (guardian && typeof guardian.setHTML === 'function') {
      guardian.setHTML(legendEl, parts.map((seg) => (
        `<div class="earnings_piegraphs_legend_row" data-seg-key="${escapeHtml(seg.key)}">`
        + `<span class="earnings_piegraphs_legend_dot earnings_piegraphs_legend_dot_${seg.key}"></span>`
        + `<span class="earnings_piegraphs_legend_label">${escapeHtml(seg.label)}</span>`
        + `<span class="earnings_piegraphs_legend_value">${formatPieAmount(seg.value)} (${formatPiePercent(seg.pct)})</span>`
        + `</div>`
      )).join(''));
    }

    const setHoveredSegment = (segKey) => {
      svgEl.querySelectorAll('.earnings_piegraphs_slice').forEach((slice) => {
        const active = segKey !== '' && slice.dataset.segKey === segKey;
        slice.classList.toggle('is-hovered', active);
      });

      legendEl.querySelectorAll('.earnings_piegraphs_legend_row').forEach((row) => {
        const active = segKey !== '' && row.dataset.segKey === segKey;
        row.classList.toggle('is-hovered', active);
      });
    };

    svgEl.querySelectorAll('.earnings_piegraphs_slice').forEach((slice) => {
      const segKey = String(slice.dataset.segKey || '');
      slice.addEventListener('mouseenter', () => setHoveredSegment(segKey));
      slice.addEventListener('mouseleave', () => setHoveredSegment(''));
    });

    legendEl.querySelectorAll('.earnings_piegraphs_legend_row').forEach((row) => {
      const segKey = String(row.dataset.segKey || '');
      row.addEventListener('mouseenter', () => setHoveredSegment(segKey));
      row.addEventListener('mouseleave', () => setHoveredSegment(''));
    });
  };

  return {
    locale,
    formatPieAmount,
    formatPiePercent,
    normalizeCompositionTotals,
    monthLabelFromKey,
    pieSegmentsFromTotals,
    renderPieSvg,
  };
}

export function normalizeCompositionTotals(totals) {
  const gross = Math.max(0, Number(totals?.gross || 0));
  let deductions = Math.max(0, Number(totals?.deductions || 0));
  let net = Math.max(0, Number(totals?.net || 0));

  if (gross > 0 && deductions > 0 && net <= 0) {
    net = Math.max(0, gross - deductions);
  } else if (gross > 0 && net > 0 && deductions <= 0) {
    deductions = Math.max(0, gross - net);
  } else if (gross > 0 && Math.abs((net + deductions) - gross) > 0.01) {
    if (deductions > 0) {
      net = Math.max(0, gross - deductions);
    } else {
      deductions = Math.max(0, gross - net);
    }
  }

  return { gross, deductions, net };
}

export function buildPieGraphDataset(dailyPayload) {
  const ytd = { gross: 0, deductions: 0, net: 0 };
  const monthly = {};

  Object.entries(dailyPayload || {}).forEach(([dateKey, record]) => {
    if (!isIsoDateKey(dateKey) || !record || typeof record !== 'object') {
      return;
    }

    const rowTotals = normalizeCompositionTotals({
      gross: parseMoneyLike(record.gross),
      deductions: parseMoneyLike(record.deductions ?? record.tax),
      net: parseMoneyLike(record.net),
    });
    const monthKey = String(dateKey).slice(0, 7);

    ytd.gross += rowTotals.gross;
    ytd.deductions += rowTotals.deductions;
    ytd.net += rowTotals.net;

    if (!monthly[monthKey]) {
      monthly[monthKey] = { gross: 0, deductions: 0, net: 0 };
    }
    monthly[monthKey].gross += rowTotals.gross;
    monthly[monthKey].deductions += rowTotals.deductions;
    monthly[monthKey].net += rowTotals.net;
  });

  return { ytd, monthly };
}

export function getPieGraphPalette(panelEl) {
  const colorSource = panelEl || document.documentElement;
  const styles = getComputedStyle(colorSource);
  const readVar = (varName, fallback) => {
    const value = styles.getPropertyValue(varName).trim();
    return value !== '' ? value : fallback;
  };

  return {
    gross: readVar('--earnings-piegraphs-color-gross', '#1e4778'),
    net: readVar('--earnings-piegraphs-color-net', '#8bb7e6'),
    deductions: readVar('--earnings-piegraphs-color-deductions', '#f2d2a6'),
  };
}
