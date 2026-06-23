const DAY_MS = 86400000;

export const PAY_PERIOD_CANONICAL_WEEKDAY_NAMES = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

const DEFAULT_DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const PAY_PERIOD_WEEKDAY_MAP = PAY_PERIOD_CANONICAL_WEEKDAY_NAMES.reduce((map, dayName, index) => {
  map[dayName] = index;
  return map;
}, {});

function escapeHtml(value) {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function coerceDay(date = new Date()) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate());
}

function isValidDate(date) {
  return date instanceof Date && !Number.isNaN(date.getTime());
}

function normalizeDayNames(dayNames = DEFAULT_DAY_NAMES) {
  return PAY_PERIOD_CANONICAL_WEEKDAY_NAMES.map((_, index) => {
    const fallback = DEFAULT_DAY_NAMES[index];
    const label = Array.isArray(dayNames) ? dayNames[index] : '';
    const normalized = String(label || fallback || '').trim();
    return normalized !== '' ? normalized : fallback;
  });
}

function normalizeGraceDays(graceDays) {
  const parsed = parseInt(String(graceDays || '0'), 10);
  return Number.isNaN(parsed) ? 0 : Math.max(0, parsed);
}

export function buildPayPeriodDayNames(locale = undefined, fallbackDayNames = DEFAULT_DAY_NAMES) {
  try {
    const formatter = new Intl.DateTimeFormat(locale, { weekday: 'short' });
    const sunday = new Date(Date.UTC(2026, 0, 4));

    return PAY_PERIOD_CANONICAL_WEEKDAY_NAMES.map((_, index) => (
      formatter.format(new Date(sunday.getTime() + (index * DAY_MS)))
    ));
  } catch {
    return normalizeDayNames(fallbackDayNames).map((dayName) => String(dayName).slice(0, 3));
  }
}

export function payPeriodParseYmd(ymd) {
  return new Date(`${ymd}T00:00:00`);
}

export function payPeriodAddDays(date, days) {
  return new Date(date.getFullYear(), date.getMonth(), date.getDate() + days);
}

export function payPeriodFormatYmd(date) {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, '0');
  const d = String(date.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

export function alignPayPeriodToAnchor(start, anchorDay = 'Monday') {
  const target = PAY_PERIOD_WEEKDAY_MAP[anchorDay] ?? 1;
  let cursor = coerceDay(start);
  while (cursor.getDay() !== target) {
    cursor = payPeriodAddDays(cursor, -1);
  }
  return cursor;
}

export function nextPayPeriodStart(start, periodFrequency = 'biweekly') {
  if (periodFrequency === 'weekly') {
    return payPeriodAddDays(start, 7);
  }
  if (periodFrequency === 'biweekly') {
    return payPeriodAddDays(start, 14);
  }
  if (periodFrequency === 'semimonthly') {
    if (start.getDate() <= 15) {
      return new Date(start.getFullYear(), start.getMonth(), 16);
    }
    return new Date(start.getFullYear(), start.getMonth() + 1, 1);
  }

  return new Date(start.getFullYear(), start.getMonth() + 1, 1);
}

export function buildPayPeriodCurrentRange({
  startYmd = '',
  frequency = 'biweekly',
  anchor = 'Monday',
  alignBiweeklyToAnchor = false,
  rollBiweeklyToToday = false,
  today = new Date(),
} = {}) {
  const currentDay = coerceDay(today);

  if (frequency === 'weekly') {
    const start = alignPayPeriodToAnchor(currentDay, anchor);
    return { start, endExclusive: payPeriodAddDays(start, 7) };
  }

  if (frequency === 'biweekly') {
    const parsedStart = payPeriodParseYmd(startYmd);
    let start = isValidDate(parsedStart) ? parsedStart : alignPayPeriodToAnchor(currentDay, anchor);
    if (alignBiweeklyToAnchor) {
      start = alignPayPeriodToAnchor(start, anchor);
    }
    if (rollBiweeklyToToday) {
      while (currentDay < start) {
        start = payPeriodAddDays(start, -14);
      }
      while (currentDay >= payPeriodAddDays(start, 14)) {
        start = payPeriodAddDays(start, 14);
      }
    }
    return { start, endExclusive: payPeriodAddDays(start, 14) };
  }

  if (frequency === 'semimonthly') {
    if (currentDay.getDate() <= 15) {
      const start = new Date(currentDay.getFullYear(), currentDay.getMonth(), 1);
      return { start, endExclusive: new Date(currentDay.getFullYear(), currentDay.getMonth(), 16) };
    }

    const start = new Date(currentDay.getFullYear(), currentDay.getMonth(), 16);
    return { start, endExclusive: new Date(currentDay.getFullYear(), currentDay.getMonth() + 1, 1) };
  }

  const start = new Date(currentDay.getFullYear(), currentDay.getMonth(), 1);
  return { start, endExclusive: new Date(currentDay.getFullYear(), currentDay.getMonth() + 1, 1) };
}

function buildMonthLabel(date, locale) {
  return date.toLocaleDateString(locale, { month: 'long', year: 'numeric' });
}

function inRange(date, start, endExclusive) {
  return date >= start && date < endExclusive;
}

export function buildPayPeriodRibbonCalendar(
  periods,
  graceDays,
  today = new Date(),
  {
    dayNames = DEFAULT_DAY_NAMES,
    locale = undefined,
    headerMode = 'table',
    selectable = false,
    dayNumberClass = false,
    clampRibbonToWeek = false,
  } = {},
) {
  const currentDay = coerceDay(today);
  const safeGraceDays = normalizeGraceDays(graceDays);
  const headerLabels = normalizeDayNames(dayNames).map(escapeHtml);
  const firstOfMonth = new Date(currentDay.getFullYear(), currentDay.getMonth(), 1);
  const gridStart = payPeriodAddDays(firstOfMonth, -firstOfMonth.getDay());
  const badgesPlaced = { p1: false, p2: false };
  let bodyRows = '';

  for (let week = 0; week < 6; week += 1) {
    bodyRows += '<tr>';
    for (let day = 0; day < 7; day += 1) {
      const offset = (week * 7) + day;
      const cellDate = payPeriodAddDays(gridStart, offset);
      const cellYmd = payPeriodFormatYmd(cellDate);
      const isToday = cellYmd === payPeriodFormatYmd(currentDay);
      const classes = ['pp_day_cell'];
      let badge = '';

      periods.forEach((period, index) => {
        const periodKey = index === 0 ? 'p1' : 'p2';
        const prevDate = payPeriodAddDays(cellDate, -1);
        const nextDate = payPeriodAddDays(cellDate, 1);
        const active = inRange(cellDate, period.start, period.endExclusive);
        const prevActive = inRange(prevDate, period.start, period.endExclusive);
        const nextActive = inRange(nextDate, period.start, period.endExclusive);
        const graceStart = period.endExclusive;
        const graceEndExclusive = payPeriodAddDays(graceStart, safeGraceDays);
        const graceActive = inRange(cellDate, graceStart, graceEndExclusive);

        if (active) {
          classes.push('pp_in_period', `pp_in_${periodKey}`);
          if (!prevActive || (clampRibbonToWeek && day === 0)) {
            classes.push(`pp_ribbon_start_${periodKey}`);
          }
          if (!nextActive || (clampRibbonToWeek && day === 6)) {
            classes.push(`pp_ribbon_end_${periodKey}`);
          }
          if (!badgesPlaced[periodKey]) {
            badge = `<span class="pp_badge ${periodKey === 'p2' ? 'pp_badge_p2' : ''}">${escapeHtml(period.label)}</span>`;
            badgesPlaced[periodKey] = true;
          }
        }

        if (graceActive && safeGraceDays > 0) {
          const graceIndex = Math.min(safeGraceDays, Math.max(1, Math.floor((cellDate - graceStart) / DAY_MS) + 1));
          classes.push('pp_grace_day', `pp_grace_${graceIndex}`, `pp_grace_${periodKey}`);
        }
      });

      if (isToday) {
        classes.push('pp_today');
      }

      const cellAttributes = selectable ? ` data-ymd="${cellYmd}" tabindex="0"` : '';
      const dayNumberAttributes = dayNumberClass ? ' class="pp_day_number"' : '';
      bodyRows += `<td class="${classes.join(' ')}"${cellAttributes}><span${dayNumberAttributes}>${String(cellDate.getDate()).padStart(2, '0')}</span>${badge}</td>`;
    }
    bodyRows += '</tr>';
  }

  if (headerMode === 'stripbar') {
    const stripbar = headerLabels.map((dayName) => `<span class="pp_day_head">${dayName}</span>`).join('');
    return `
      <div class="pp_month_label">${escapeHtml(buildMonthLabel(currentDay, locale))}</div>
      <div class="pp_stripbar">${stripbar}</div>
      <table class="pp_three_week">
        <tbody>${bodyRows}</tbody>
      </table>
    `;
  }

  const header = headerLabels.map((dayName) => `<th class="pp_day_head">${dayName}</th>`).join('');
  return `
    <div class="pp_month_label">${escapeHtml(buildMonthLabel(currentDay, locale))}</div>
    <table class="pp_three_week">
      <thead><tr>${header}</tr></thead>
      <tbody>${bodyRows}</tbody>
    </table>
  `;
}

export function buildPayPeriodPreviewState({
  startYmd = '',
  frequency = 'biweekly',
  anchor = 'Monday',
  graceDays = '0',
  dayNames = DEFAULT_DAY_NAMES,
  locale = undefined,
  alignBiweeklyToAnchor = false,
  rollBiweeklyToToday = false,
  includeSummary = false,
  calendarOptions = {},
  today = new Date(),
} = {}) {
  const currentDay = coerceDay(today);
  const startValue = String(startYmd || '') !== ''
    ? String(startYmd || '')
    : payPeriodFormatYmd(alignPayPeriodToAnchor(currentDay, anchor));
  const period1 = buildPayPeriodCurrentRange({
    startYmd: startValue,
    frequency,
    anchor,
    alignBiweeklyToAnchor,
    rollBiweeklyToToday,
    today: currentDay,
  });
  const period2 = {
    start: period1.endExclusive,
    endExclusive: nextPayPeriodStart(period1.endExclusive, frequency),
  };
  const endInclusive1 = payPeriodAddDays(period1.endExclusive, -1);
  const endInclusive2 = payPeriodAddDays(period2.endExclusive, -1);
  const periods = [
    { label: 'P1', start: period1.start, endExclusive: period1.endExclusive },
    { label: 'P2', start: period2.start, endExclusive: period2.endExclusive },
  ];
  const safeGraceDays = normalizeGraceDays(graceDays);
  const p1Start = payPeriodFormatYmd(period1.start);
  const p1End = payPeriodFormatYmd(endInclusive1);
  const p2Start = payPeriodFormatYmd(period2.start);
  const p2End = payPeriodFormatYmd(endInclusive2);
  const summary = `P1 ${p1Start} → ${p1End}   P2 ${p2Start} → ${p2End}`;
  const calendarHtml = buildPayPeriodRibbonCalendar(periods, safeGraceDays, currentDay, {
    dayNames,
    locale,
    ...calendarOptions,
  });
  const summaryHtml = `<div class="pp_preview_summary"><span class="pp_preview_summary_item">P1: ${p1Start} to ${p1End}</span><span class="pp_preview_summary_item">P2: ${p2Start} to ${p2End}</span></div>`;

  return {
    startValue,
    frequency,
    anchor,
    safeGraceDays,
    p1Start,
    p1End,
    p2Start,
    p2End,
    period1,
    period2,
    periods,
    summary,
    summaryHtml,
    signature: [
      frequency,
      startValue,
      anchor,
      String(safeGraceDays),
      payPeriodFormatYmd(currentDay),
    ].join('|'),
    html: includeSummary ? `${calendarHtml}${summaryHtml}` : calendarHtml,
  };
}

export function resolvePayPeriodPreviewSelection(event) {
  const target = event.target instanceof Element
    ? event.target.closest('.pp_day_cell[data-ymd]')
    : null;
  if (!(target instanceof HTMLElement)) {
    return null;
  }

  const selectedYmd = String(target.dataset.ymd || '');
  if (selectedYmd === '') {
    return null;
  }

  const selectedDate = payPeriodParseYmd(selectedYmd);
  if (!isValidDate(selectedDate)) {
    return null;
  }

  return {
    ymd: selectedYmd,
    anchor: PAY_PERIOD_CANONICAL_WEEKDAY_NAMES[selectedDate.getDay()] || 'Monday',
  };
}
