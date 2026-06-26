/**
 * Shared earnings trend line chart renderer for /reports/ and member reports dialog.
 */

import { createEarningsFormatHelpers } from '/js/earnings/format.js';

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

function formatDateKeyShort(dateKey, locale = undefined) {
  const match = String(dateKey).match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) {
    return new Date(dateKey).toLocaleDateString(locale, { month: 'short', day: 'numeric' });
  }

  const year = Number(match[1]);
  const monthIndex = Number(match[2]) - 1;
  const day = Number(match[3]);
  return new Date(Date.UTC(year, monthIndex, day, 12, 0, 0, 0)).toLocaleDateString(locale, {
    month: 'short',
    day: 'numeric',
    timeZone: 'UTC',
  });
}

function getStatusNodes(svgId, getElement) {
  return {
    statusNode: getElement(`${svgId}_status`),
    descNode: getElement(`${svgId}_desc`),
  };
}

function resolveTrendDirection(deltaValue, getI18nLabel) {
  if (typeof getI18nLabel === 'function') {
    if (deltaValue > 0) {
      return getI18nLabel('EARNINGS_TREND_DIRECTION_INCREASING', 'increasing');
    }
    if (deltaValue < 0) {
      return getI18nLabel('EARNINGS_TREND_DIRECTION_DECREASING', 'decreasing');
    }
    return getI18nLabel('EARNINGS_TREND_DIRECTION_FLAT', 'flat');
  }

  if (deltaValue > 0) {
    return 'increasing';
  }
  if (deltaValue < 0) {
    return 'decreasing';
  }
  return 'flat';
}

function isCompactTrendChart(width) {
  const compactByWidth = Number.isFinite(width) && width <= 480;
  const compactByPointer = typeof window !== 'undefined'
    && typeof window.matchMedia === 'function'
    && window.matchMedia('(pointer: coarse) and (max-width: 700px)').matches;
  return compactByWidth || compactByPointer;
}

function resolveTouchHintLabel(getI18nLabel) {
  if (typeof getI18nLabel === 'function') {
    return getI18nLabel('EARNINGS_TREND_TOUCH_HINT', 'Touch to reveal');
  }
  return 'Touch to reveal';
}

function markEarningsChartDecorative(el) {
  if (el && typeof el.setAttribute === 'function') {
    el.setAttribute('aria-hidden', 'true');
  }
}

function syncTouchHint(linegraphSVG, hintText, visible) {
  const container = linegraphSVG.parentElement;
  if (!container || typeof container.querySelectorAll !== 'function') {
    return;
  }

  let hint = Array.from(container.querySelectorAll('.earnings-chart-touch-hint'))
    .find((candidate) => candidate?.dataset?.chartFor === linegraphSVG.id);
  if (!hint) {
    hint = document.createElement('p');
    hint.className = 'earnings-chart-touch-hint';
    hint.dataset.chartFor = linegraphSVG.id;
    linegraphSVG.insertAdjacentElement('afterend', hint);
  }

  hint.textContent = hintText;
  hint.hidden = !visible;
}

export function announceEarningsGraphStatus(year, dates, values, options = {}) {
  const {
    svgIdPrefix = 'earnings_line_graph_',
    getElement = (id) => document.getElementById(id),
    userLocale = undefined,
    formatStatus = null,
    formatDesc = null,
    getI18nLabel = null,
    formatI18n = null,
    formatHelpers = null,
  } = options;

  const svgId = `${svgIdPrefix}${year}`;
  const { statusNode, descNode } = getStatusNodes(svgId, getElement);
  if (!statusNode || !descNode) {
    return;
  }

  const helpers = formatHelpers || createEarningsFormatHelpers({ locale: userLocale });

  if (!Array.isArray(dates) || !Array.isArray(values) || dates.length === 0 || values.length === 0) {
    statusNode.textContent = typeof formatStatus === 'function'
      ? formatStatus('no_data', { year })
      : (typeof formatI18n === 'function'
        ? formatI18n('EARNINGS_TREND_NO_DATA_STATUS', 'Earnings trend chart for {year} loaded with no data points.', { year })
        : `Earnings trend chart for ${year} loaded with no data points.`);
    descNode.textContent = typeof formatDesc === 'function'
      ? formatDesc('no_data', { year })
      : (typeof formatI18n === 'function'
        ? formatI18n('EARNINGS_TREND_NO_DATA_DESC', 'Line chart showing gross earnings trend across {year}. No earnings data points are available yet.', { year })
        : `Line chart showing gross earnings trend across ${year}. No earnings data points are available yet.`);
    return;
  }

  const safeValues = values
    .map((value) => Number(value))
    .filter((value) => Number.isFinite(value));

  if (safeValues.length === 0) {
    statusNode.textContent = typeof formatStatus === 'function'
      ? formatStatus('invalid', { year })
      : (typeof formatI18n === 'function'
        ? formatI18n('EARNINGS_TREND_NO_NUMERIC_STATUS', 'Earnings trend chart for {year} loaded with no numeric data points.', { year })
        : `Earnings trend chart for ${year} loaded with invalid data points.`);
    descNode.textContent = typeof formatDesc === 'function'
      ? formatDesc('invalid', { year })
      : (typeof formatI18n === 'function'
        ? formatI18n('EARNINGS_TREND_NO_NUMERIC_DESC', 'Line chart showing gross earnings trend across {year}. Data points were present but contained invalid numeric values.', { year })
        : `Line chart showing gross earnings trend across ${year}. Data points were present but contained invalid numeric values.`);
    return;
  }

  const firstDate = formatDateKeyShort(dates[0], userLocale);
  const lastDate = formatDateKeyShort(dates[dates.length - 1], userLocale);
  const minValue = Math.min(...safeValues);
  const maxValue = Math.max(...safeValues);
  const deltaValue = safeValues[safeValues.length - 1] - safeValues[0];
  const direction = resolveTrendDirection(deltaValue, getI18nLabel);
  const minValueLabel = helpers.formatCurrency(minValue);
  const maxValueLabel = helpers.formatCurrency(maxValue);

  statusNode.textContent = typeof formatStatus === 'function'
    ? formatStatus('updated', { year, points: values.length, firstDate, lastDate })
    : (typeof formatI18n === 'function'
      ? formatI18n(
        'EARNINGS_TREND_UPDATED_STATUS',
        'Earnings trend chart updated for {year}. {points} points from {firstDate} to {lastDate}.',
        { year, points: values.length, firstDate, lastDate },
      )
      : `Earnings trend chart updated for ${year}. ${values.length} points from ${firstDate} to ${lastDate}.`);
  descNode.textContent = typeof formatDesc === 'function'
    ? formatDesc('updated', {
      year,
      firstDate,
      lastDate,
      points: values.length,
      minValue: minValueLabel,
      maxValue: maxValueLabel,
      direction,
    })
    : (typeof formatI18n === 'function'
      ? formatI18n(
        'EARNINGS_TREND_UPDATED_DESC',
        'Line chart showing gross earnings trend across {year}. Data spans {firstDate} to {lastDate} with {points} points. Values range from {minValue} to {maxValue} and overall trend is {direction}.',
        {
          year,
          firstDate,
          lastDate,
          points: values.length,
          minValue: minValueLabel,
          maxValue: maxValueLabel,
          direction,
        },
      )
      : `Line chart showing gross earnings trend across ${year}. Data spans ${firstDate} to ${lastDate} with ${values.length} points. Values range from ${minValueLabel} to ${maxValueLabel} and overall trend is ${direction}.`);
}

export function announceEarningsGraphError(year, message = '', options = {}) {
  const {
    svgIdPrefix = 'earnings_line_graph_',
    getElement = (id) => document.getElementById(id),
    formatError = null,
    formatI18n = null,
    getI18nLabel = null,
  } = options;

  const svgId = `${svgIdPrefix}${year}`;
  const { statusNode } = getStatusNodes(svgId, getElement);
  if (!statusNode) {
    return;
  }

  const finalMessage = message || (typeof getI18nLabel === 'function'
    ? getI18nLabel('EARNINGS_CHART_DATA_LOAD_FAILED', 'Chart data could not be loaded.')
    : 'Chart data could not be loaded.');
  statusNode.textContent = typeof formatError === 'function'
    ? formatError({ year, message: finalMessage })
    : (typeof formatI18n === 'function'
      ? formatI18n('EARNINGS_TREND_LOAD_FAILED_STATUS', 'Earnings trend chart for {year} could not be loaded. {message}', { year, message: finalMessage })
      : `Earnings trend chart for ${year} could not be loaded. ${finalMessage}`);
}

export function drawLineGraph(data, svgId, options = {}) {
  const {
    getElement = (id) => document.getElementById(id),
    warn = () => {},
    userLocale = undefined,
    svgIdPrefix = 'earnings_line_graph_',
    onStatus = null,
    onError = null,
    getI18nLabel = null,
    formatI18n = null,
    formatHelpers = null,
  } = options;

  const helpers = formatHelpers || createEarningsFormatHelpers({ locale: userLocale });
  const formatHoverTooltip = (dateStr, amountValue) => {
    const amountLabel = helpers.formatCurrency(amountValue);
    if (typeof formatI18n === 'function') {
      return formatI18n('EARNINGS_TREND_HOVER_TOOLTIP', '{date}: {amount}', { date: dateStr, amount: amountLabel });
    }
    return `${dateStr}: ${amountLabel}`;
  };
  const formatYAxisLabel = (amountValue, percentValue) => {
    const amountLabel = helpers.formatCurrency(amountValue);
    const percentLabel = helpers.formatPercent(percentValue, 0);
    if (typeof formatI18n === 'function') {
      return formatI18n('EARNINGS_TREND_Y_AXIS_LABEL', '{amount} ({pct})', { amount: amountLabel, pct: percentLabel });
    }
    return `${amountLabel} (${percentLabel})`;
  };

  const SVG_NS = 'http://www.w3.org/2000/svg';
  const linegraphSVG = getElement(svgId);

  if (!linegraphSVG) {
    warn(`[GRAPH] SVG element not found: ${svgId}`);
    return;
  }
  if (!data || typeof data !== 'object') {
    warn(`[GRAPH] Invalid data: received type ${typeof data}, expected object`);
    return;
  }

  const rawPairs = Object.entries(data).map(([dateKey, amount]) => {
    const dateMs = parseDateKeyToLocalMs(dateKey);
    const numericAmount = Number(amount);
    return {
      dateKey,
      dateMs,
      amount: numericAmount,
      valid: Number.isFinite(dateMs) && Number.isFinite(numericAmount),
    };
  });

  const invalidPoints = rawPairs.filter((entry) => !entry.valid).length;
  if (invalidPoints > 0) {
    warn(`[GRAPH] Filtered ${invalidPoints} invalid earnings point(s) for ${svgId}`);
  }

  const pairs = rawPairs
    .filter((entry) => entry.valid)
    .sort((a, b) => a.dateMs - b.dateMs);

  const yearFromId = Number((svgId.match(/(\d{4})$/) || [])[1]);
  const announceOptions = {
    svgIdPrefix,
    getElement,
    userLocale,
    getI18nLabel,
    formatI18n,
    formatHelpers: helpers,
  };

  if (!pairs.length) {
    if (Number.isFinite(yearFromId)) {
      const announce = typeof onStatus === 'function'
        ? onStatus
        : (year, dates, values) => announceEarningsGraphStatus(year, dates, values, announceOptions);
      announce(yearFromId, [], []);
    }
    return;
  }

  const dateKeys = pairs.map((entry) => entry.dateKey);
  const datesMs = pairs.map((entry) => entry.dateMs);
  const amounts = pairs.map((entry) => entry.amount);

  if (datesMs.length === 0 || amounts.length === 0) {
    if (Number.isFinite(yearFromId)) {
      const announce = typeof onStatus === 'function'
        ? onStatus
        : (year, dates, values) => announceEarningsGraphStatus(year, dates, values, announceOptions);
      announce(yearFromId, [], []);
    }
    return;
  }

  const derivedYear = parseInt(String(dateKeys[0]).split('-')[0], 10);
  const year = Number.isFinite(derivedYear) ? derivedYear : yearFromId;

  if (!Number.isFinite(year)) {
    warn(`[GRAPH] Could not derive chart year from ${svgId}`);
    return;
  }

  const announce = typeof onStatus === 'function'
    ? onStatus
    : (targetYear, dates, values) => announceEarningsGraphStatus(targetYear, dates, values, announceOptions);
  announce(year, dateKeys, amounts);

  const xMin = new Date(year, 0, 1).getTime();
  const xMax = new Date(year, 11, 31, 23, 59, 59, 999).getTime();
  const yMin = Math.min(0, ...amounts);
  const yMaxRaw = Math.max(...amounts);

  if (!Number.isFinite(yMin) || !Number.isFinite(yMaxRaw)) {
    warn(`[GRAPH] Invalid Y-axis domain for ${svgId}`);
    const errorAnnounce = typeof onError === 'function'
      ? onError
      : (targetYear, message) => announceEarningsGraphError(targetYear, message, announceOptions);
    errorAnnounce(year, 'Chart data contained invalid numeric values.');
    return;
  }

  const yMax = Math.ceil(yMaxRaw / 10) * 10;
  const parentWidth = linegraphSVG.parentElement?.clientWidth;
  const width = Number.isFinite(parentWidth) ? Number(parentWidth) : 0;
  const height = 200;

  if (width <= 0) {
    return;
  }

  const compactChart = isCompactTrendChart(width);
  let margin = compactChart
    ? { top: 10, right: 8, bottom: 10, left: 8 }
    : { top: 10, right: 16, bottom: 32, left: 40 };
  linegraphSVG.setAttribute('width', width);
  linegraphSVG.setAttribute('height', height);
  linegraphSVG.dataset.compactChart = compactChart ? 'true' : 'false';
  linegraphSVG.textContent = '';
  syncTouchHint(linegraphSVG, resolveTouchHintLabel(getI18nLabel), compactChart);

  const rootStyles = getComputedStyle(document.documentElement);
  const primaryColor = rootStyles.getPropertyValue('--color-primary').trim() || '#3a86ff';
  const textColor = rootStyles.getPropertyValue('--color-text').trim() || '#000';
  const graphStrokeStrong = `${primaryColor}cc`;
  const graphStrokeNormal = `${primaryColor}66`;
  const graphStrokeLight = `${primaryColor}26`;
  const graphStrokeVeryLight = `${primaryColor}03`;
  const tooltipBg = rootStyles.getPropertyValue('--panel-bg').trim()
    || rootStyles.getPropertyValue('--surface').trim()
    || 'rgba(255, 255, 255, 0.98)';
  const tooltipBorder = rootStyles.getPropertyValue('--panel-border').trim()
    || rootStyles.getPropertyValue('--border').trim()
    || 'rgba(200, 200, 200, 0.8)';

  if (!compactChart) {
    const probe = document.createElementNS(SVG_NS, 'text');
    probe.setAttribute('font-size', '13');
    probe.textContent = formatYAxisLabel(yMax, 100);
    linegraphSVG.appendChild(probe);
    let labelWidth = 0;
    try {
      labelWidth = Math.ceil(probe.getBBox().width);
    } catch (_error) {
      warn(`[GRAPH] Unable to measure Y-axis label width for ${svgId}`);
    }
    linegraphSVG.removeChild(probe);
    margin.left = Math.max(margin.left, labelWidth + 12);
  }

  const innerW = Math.max(0, width - margin.left - margin.right);
  const innerH = Math.max(0, height - margin.top - margin.bottom);

  if (innerW <= 0 || innerH <= 0) {
    warn(`[GRAPH] Invalid chart inner dimensions for ${svgId}`);
    return;
  }

  const xSpan = xMax - xMin;
  if (!Number.isFinite(xSpan) || xSpan <= 0) {
    warn(`[GRAPH] Invalid X-axis domain for ${svgId}`);
    return;
  }

  const xScale = (d) => ((d - xMin) / xSpan) * innerW + margin.left;
  const yScale = (yMax === yMin)
    ? () => margin.top + innerH / 2
    : (v) => (margin.top + innerH) - ((v - yMin) / (yMax - yMin)) * innerH;

  let dPath = `M ${xScale(datesMs[0])},${yScale(amounts[0])}`;
  for (let i = 1; i < datesMs.length; i++) {
    const x1 = xScale(datesMs[i - 1]);
    const y1 = yScale(amounts[i - 1]);
    const x2 = xScale(datesMs[i]);
    const y2 = yScale(amounts[i]);
    const xc = (x1 + x2) / 2;
    dPath += ` C ${xc},${y1} ${xc},${y2} ${x2},${y2}`;
  }
  dPath += ` L ${xScale(datesMs[datesMs.length - 1])},${margin.top + innerH}`;
  dPath += ` L ${xScale(datesMs[0])},${margin.top + innerH} Z`;

  const defs = document.createElementNS(SVG_NS, 'defs');
  const grad = document.createElementNS(SVG_NS, 'linearGradient');
  const gradientId = `verticalGradient_${svgId}`;
  grad.setAttribute('id', gradientId);
  grad.setAttribute('x1', '0%');
  grad.setAttribute('y1', '0%');
  grad.setAttribute('x2', '0%');
  grad.setAttribute('y2', '100%');
  const stop1 = document.createElementNS(SVG_NS, 'stop');
  stop1.setAttribute('offset', '0%');
  stop1.setAttribute('stop-color', graphStrokeLight);
  const stop2 = document.createElementNS(SVG_NS, 'stop');
  stop2.setAttribute('offset', '100%');
  stop2.setAttribute('stop-color', graphStrokeVeryLight);
  grad.appendChild(stop1);
  grad.appendChild(stop2);
  defs.appendChild(grad);
  markEarningsChartDecorative(defs);
  linegraphSVG.appendChild(defs);

  const areaPath = document.createElementNS(SVG_NS, 'path');
  areaPath.setAttribute('d', dPath);
  areaPath.setAttribute('fill', `url(#${gradientId})`);
  areaPath.setAttribute('stroke', 'none');
  markEarningsChartDecorative(areaPath);
  linegraphSVG.appendChild(areaPath);

  let strokePath = `M ${xScale(datesMs[0])},${yScale(amounts[0])}`;
  for (let i = 1; i < datesMs.length; i++) {
    const x1 = xScale(datesMs[i - 1]);
    const y1 = yScale(amounts[i - 1]);
    const x2 = xScale(datesMs[i]);
    const y2 = yScale(amounts[i]);
    const xc = (x1 + x2) / 2;
    strokePath += ` C ${xc},${y1} ${xc},${y2} ${x2},${y2}`;
  }
  const linePath = document.createElementNS(SVG_NS, 'path');
  linePath.setAttribute('d', strokePath);
  linePath.setAttribute('stroke', graphStrokeStrong);
  linePath.setAttribute('stroke-width', '2');
  linePath.setAttribute('fill', 'none');
  markEarningsChartDecorative(linePath);
  linegraphSVG.appendChild(linePath);

  const xAxisLine = document.createElementNS(SVG_NS, 'line');
  xAxisLine.setAttribute('x1', margin.left);
  xAxisLine.setAttribute('x2', margin.left + innerW);
  xAxisLine.setAttribute('y1', margin.top + innerH);
  xAxisLine.setAttribute('y2', margin.top + innerH);
  xAxisLine.setAttribute('stroke', graphStrokeNormal);
  xAxisLine.setAttribute('stroke-width', '1');
  markEarningsChartDecorative(xAxisLine);
  linegraphSVG.appendChild(xAxisLine);

  const yAxisLine = document.createElementNS(SVG_NS, 'line');
  yAxisLine.setAttribute('x1', margin.left);
  yAxisLine.setAttribute('x2', margin.left);
  yAxisLine.setAttribute('y1', margin.top + innerH);
  yAxisLine.setAttribute('y2', margin.top);
  yAxisLine.setAttribute('stroke', graphStrokeNormal);
  yAxisLine.setAttribute('stroke-width', '1');
  markEarningsChartDecorative(yAxisLine);
  linegraphSVG.appendChild(yAxisLine);

  const yPercents = [0, 0.25, 0.5, 0.75, 1];
  yPercents.forEach((p) => {
    const v = Math.round(yMin + (yMax - yMin) * p);
    const y = yScale(v);

    if (p > 0) {
      const gl = document.createElementNS(SVG_NS, 'line');
      gl.setAttribute('x1', margin.left);
      gl.setAttribute('x2', margin.left + innerW);
      gl.setAttribute('y1', y);
      gl.setAttribute('y2', y);
      gl.setAttribute('stroke', graphStrokeLight);
      gl.setAttribute('stroke-width', '1');
      markEarningsChartDecorative(gl);
      linegraphSVG.appendChild(gl);
    }

    if (!compactChart) {
      const t = document.createElementNS(SVG_NS, 'text');
      t.classList.add('earnings-chart-axis-label', 'earnings-chart-axis-label-y');
      t.setAttribute('x', margin.left - 10);
      t.setAttribute('y', y + 3);
      t.setAttribute('text-anchor', 'end');
      t.setAttribute('font-size', '13');
      t.setAttribute('fill', textColor);
      t.textContent = formatYAxisLabel(v, p * 100);
      markEarningsChartDecorative(t);
      linegraphSVG.appendChild(t);
    }
  });

  if (!compactChart) {
    const monthFormatter = new Intl.DateTimeFormat(userLocale, { month: 'short' });
    for (let m = 0; m < 12; m++) {
      const mid = new Date(year, m, 15).getTime();
      const x = xScale(mid);
      const y = margin.top + innerH + 15;

      const label = document.createElementNS(SVG_NS, 'text');
      label.classList.add('earnings-chart-axis-label', 'earnings-chart-axis-label-x');
      label.setAttribute('x', x);
      label.setAttribute('y', y);
      label.setAttribute('text-anchor', 'middle');
      label.setAttribute('font-size', '13');
      label.setAttribute('fill', textColor);
      label.textContent = monthFormatter.format(new Date(year, m, 1));
      markEarningsChartDecorative(label);
      linegraphSVG.appendChild(label);
    }
  }

  const overlay = document.createElementNS(SVG_NS, 'rect');
  overlay.setAttribute('x', margin.left);
  overlay.setAttribute('y', margin.top);
  overlay.setAttribute('width', innerW);
  overlay.setAttribute('height', innerH);
  overlay.setAttribute('fill', 'transparent');
  overlay.setAttribute('pointer-events', 'all');
  overlay.classList.add('earnings-crosshair');

  const hair = document.createElementNS(SVG_NS, 'line');
  hair.setAttribute('stroke', graphStrokeNormal);
  hair.setAttribute('stroke-width', '1');
  hair.classList.add('svg-hidden');
  markEarningsChartDecorative(hair);

  const dot = document.createElementNS(SVG_NS, 'circle');
  dot.setAttribute('r', '3');
  dot.setAttribute('fill', graphStrokeStrong);
  dot.classList.add('svg-hidden');
  markEarningsChartDecorative(dot);

  const tipG = document.createElementNS(SVG_NS, 'g');
  tipG.classList.add('svg-hidden');
  markEarningsChartDecorative(tipG);
  const tipRect = document.createElementNS(SVG_NS, 'rect');
  tipRect.setAttribute('rx', '2');
  tipRect.setAttribute('ry', '2');
  tipRect.setAttribute('fill', tooltipBg);
  tipRect.setAttribute('stroke', tooltipBorder);
  tipRect.setAttribute('stroke-width', '0.5');
  const tipText = document.createElementNS(SVG_NS, 'text');
  tipText.setAttribute('font-size', '14');
  tipText.setAttribute('fill', textColor);
  tipText.setAttribute('x', '5');
  tipText.setAttribute('y', '16');
  tipG.appendChild(tipRect);
  tipG.appendChild(tipText);

  linegraphSVG.appendChild(hair);
  linegraphSVG.appendChild(dot);
  linegraphSVG.appendChild(tipG);
  linegraphSVG.appendChild(overlay);

  function getSVGX(clientX) {
    const ctm = linegraphSVG.getScreenCTM();
    if (!ctm) {
      return Number.NaN;
    }
    const pt = linegraphSVG.createSVGPoint();
    pt.x = clientX;
    const cursor = pt.matrixTransform(ctm.inverse());
    return cursor.x;
  }

  function invX(px) {
    return xMin + ((px - margin.left) / innerW) * xSpan;
  }

  function nearestIndex(t) {
    let lo = 0;
    let hi = datesMs.length - 1;
    while (hi - lo > 1) {
      const mid = (hi + lo) >> 1;
      if (datesMs[mid] < t) lo = mid;
      else hi = mid;
    }
    return (t - datesMs[lo] < datesMs[hi] - t) ? lo : hi;
  }

  const setVisible = (el, visible) => {
    el.classList.toggle('svg-hidden', !visible);
    el.classList.toggle('svg-visible', visible);
  };

  const hideInteraction = () => {
    setVisible(hair, false);
    setVisible(dot, false);
    setVisible(tipG, false);
  };

  const revealAtClientX = (clientX) => {
    const px = getSVGX(clientX);
    if (!Number.isFinite(px)) return;
    if (px < margin.left || px > margin.left + innerW) return;
    const t = invX(px);
    const i = nearestIndex(t);
    const x = xScale(datesMs[i]);
    const y = yScale(amounts[i]);

    if (!Number.isFinite(x) || !Number.isFinite(y)) return;

    hair.setAttribute('x1', x);
    hair.setAttribute('x2', x);
    hair.setAttribute('y1', margin.top);
    hair.setAttribute('y2', margin.top + innerH);
    setVisible(hair, true);

    dot.setAttribute('cx', x);
    dot.setAttribute('cy', y);
    setVisible(dot, true);

    const dateStr = formatDateKeyShort(dateKeys[i], userLocale);
    const amountValue = Number(amounts[i]);
    if (!Number.isFinite(amountValue)) return;
    tipText.textContent = formatHoverTooltip(dateStr, amountValue);
    const bbox = tipText.getBBox();
    const tipWidth = bbox.width + 8;
    const tipHeight = bbox.height + 6;
    tipRect.setAttribute('width', tipWidth);
    tipRect.setAttribute('height', tipHeight);

    let tx = x + 8;
    if (tx + tipWidth > margin.left + innerW) tx = x - tipWidth - 8;
    tx = Math.max(margin.left + 2, Math.min(tx, margin.left + innerW - tipWidth - 2));
    let ty = y - tipHeight - 8;
    if (ty < margin.top) ty = y + 12;
    ty = Math.max(margin.top + 2, Math.min(ty, margin.top + innerH - tipHeight - 2));

    tipG.setAttribute('transform', `translate(${tx},${ty})`);
    setVisible(tipG, true);
  };

  overlay.addEventListener('mousemove', (evt) => {
    revealAtClientX(evt.clientX);
  });

  overlay.addEventListener('pointerdown', (evt) => {
    if (evt.pointerType !== 'mouse') {
      evt.preventDefault();
      revealAtClientX(evt.clientX);
    }
  });

  overlay.addEventListener('pointermove', (evt) => {
    if (evt.pointerType !== 'mouse') {
      evt.preventDefault();
      revealAtClientX(evt.clientX);
    }
  });

  overlay.addEventListener('touchstart', (evt) => {
    const touch = evt.touches?.[0] || evt.changedTouches?.[0];
    if (!touch) return;
    evt.preventDefault();
    revealAtClientX(touch.clientX);
  }, { passive: false });

  overlay.addEventListener('touchmove', (evt) => {
    const touch = evt.touches?.[0] || evt.changedTouches?.[0];
    if (!touch) return;
    evt.preventDefault();
    revealAtClientX(touch.clientX);
  }, { passive: false });

  overlay.addEventListener('mouseleave', () => {
    hideInteraction();
  });
}
