/**
 * Earnings export utilities for client-side report generation.
 * Builds a normalized layout model once, then renders JSON/CSV/TXT outputs.
 */

import { calculateTaxes } from '/js/earnings/taxes.js';
import { renderPayCalPdf } from '/js/earnings/pdf/renderer.js';
import { resolveUserLocale } from '/js/earnings/locale.js';

const USER_LOCALE = resolveUserLocale();

function toNumber(value) {
  const n = Number(value);
  return Number.isFinite(n) ? n : 0;
}

function toFixedNumber(value, digits = 2) {
  return Number(toNumber(value).toFixed(digits));
}

function toCurrency(value) {
  return toFixedNumber(value, 2);
}

function toHours(value) {
  return toFixedNumber(value, 2);
}

function formatCreatedTimestampUtc(date = new Date()) {
  const pad = (value) => String(value).padStart(2, '0');

  return [
    date.getUTCFullYear(),
    pad(date.getUTCMonth() + 1),
    pad(date.getUTCDate()),
  ].join('-') + ` ${pad(date.getUTCHours())}:${pad(date.getUTCMinutes())}:${pad(date.getUTCSeconds())} UTC`;
}

function ordinalDay(day) {
  const mod100 = day % 100;
  if (mod100 >= 11 && mod100 <= 13) {
    return `${day}th`;
  }

  const mod10 = day % 10;
  if (mod10 === 1) {
    return `${day}st`;
  }
  if (mod10 === 2) {
    return `${day}nd`;
  }
  if (mod10 === 3) {
    return `${day}rd`;
  }

  return `${day}th`;
}

function formatAsAtDate(date = new Date()) {
  const month = date.toLocaleString(USER_LOCALE, { month: 'long' });
  const day = ordinalDay(date.getDate());
  const year = date.getFullYear();
  return `as at ${month} ${day}, ${year}`;
}

function buildTitleLine(meta, fallbackTitle) {
  const title = String(meta?.title || fallbackTitle || 'PayCal.app - Earnings Report').trim();
  const asAt = String(meta?.as_at || meta?.subtitle || formatAsAtDate()).trim();

  if (asAt === '' || title.toLowerCase().includes(' as at ')) {
    return title;
  }

  return `${title} ${asAt}`.trim();
}

function normalizeIdentity(meta = {}) {
  const fullName = String(meta.full_name || meta.employee || '').trim();
  const email = String(meta.email || '').trim();
  const phone = String(meta.phone || '').trim();
  const address = String(meta.address || meta.address_line || '').trim();
  const city = String(meta.city || '').trim();
  const province = String(meta.province || '').trim();
  const postal = String(meta.postal || '').trim();

  const location = [city, province, postal].filter((part) => part !== '').join(', ');

  return {
    fullName,
    email,
    address,
    phone,
    location,
  };
}

function buildIdentityCsvLine(columnCount, meta = {}) {
  const identity = normalizeIdentity(meta);
  const parts = [identity.fullName, identity.email, identity.phone, identity.address, identity.location]
    .filter((p) => p !== '');
  const text = parts.join(' | ');
  return [text, ...Array(Math.max(0, columnCount - 1)).fill('')];
}

function buildIdentityTextLines(meta = {}) {
  const identity = normalizeIdentity(meta);
  const line1Parts = [identity.fullName, identity.email].filter((p) => p !== '');
  const line2Parts = [identity.address, identity.phone].filter((p) => p !== '');
  const lines = [];

  if (line1Parts.length > 0) {
    lines.push(line1Parts.join('    '));
  }
  if (line2Parts.length > 0) {
    lines.push(line2Parts.join('    '));
  }
  if (identity.location !== '') {
    lines.push(identity.location);
  }

  return lines;
}

function generateReferenceCode(length = 16) {
  const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
  const values = new Uint32Array(length);

  if (globalThis.crypto?.getRandomValues) {
    globalThis.crypto.getRandomValues(values);
  } else {
    for (let index = 0; index < length; index += 1) {
      values[index] = Math.floor(Math.random() * alphabet.length);
    }
  }

  let code = '';
  for (let index = 0; index < length; index += 1) {
    code += alphabet[values[index] % alphabet.length];
  }

  return code;
}

const DAILY_REGULAR_HOURS_LIMIT = 8;

function splitRegularAndOvertimeHours(hours, regularHoursValue, overtimeHoursValue) {
  const regularHours = toHours(regularHoursValue);
  const overtimeHours = toHours(overtimeHoursValue);

  if (regularHours > 0 || overtimeHours > 0) {
    const looksLikeLegacyStraightTimeRow =
      hours > DAILY_REGULAR_HOURS_LIMIT
      && overtimeHours === 0
      && Math.abs(regularHours - hours) < 0.01;

    if (!looksLikeLegacyStraightTimeRow) {
      return {
        regularHours,
        overtimeHours,
        derivedFromTotalHours: false,
      };
    }
  }

  if (hours <= 0) {
    return {
      regularHours: 0,
      overtimeHours: 0,
      derivedFromTotalHours: false,
    };
  }

  return {
    regularHours: toHours(Math.min(hours, DAILY_REGULAR_HOURS_LIMIT)),
    overtimeHours: toHours(Math.max(0, hours - DAILY_REGULAR_HOURS_LIMIT)),
    derivedFromTotalHours: true,
  };
}

function escapeCsvCell(value) {
  const cell = String(value ?? '');
  if (cell.includes(',') || cell.includes('"') || cell.includes('\n')) {
    return `"${cell.replace(/"/g, '""')}"`;
  }

  return cell;
}

function normalizeInputRows(dailyPayload) {
  if (Array.isArray(dailyPayload)) {
    return dailyPayload;
  }

  if (!dailyPayload || typeof dailyPayload !== 'object') {
    return [];
  }

  return Object.entries(dailyPayload)
    .filter(([key]) => key !== '_debug')
    .map(([date, row]) => ({
      date,
      ...(row && typeof row === 'object' ? row : {}),
    }));
}

export function buildDetailedRows(dailyPayload, options = {}) {
  const province = options.province || 'Alberta';
  const rows = normalizeInputRows(dailyPayload);

  return rows
    .map((row) => {
      const date = String(row.date || '');
      const siteId = String(row.site_id || row.siteId || '');
      const siteName = String(row.site_name || row.siteName || siteId || '');
      const wage = toCurrency(row.wage);
      const hours = toHours(row.hours);
      const {
        regularHours,
        overtimeHours,
      } = splitRegularAndOvertimeHours(hours, row.regular_hours, row.overtime_hours);
      const travel = toHours(row.travel_hours ?? row.travel ?? 0);
      const loa = toCurrency(row.living_out_allowance ?? row.loa ?? 0);
      const calculatedGross = toCurrency(
        (wage * regularHours)
        + (wage * overtimeHours * 1.5)
        + (wage * travel)
        + loa
      );
      const sourceGross = toCurrency(row.gross);
      const gross = sourceGross > 0 ? sourceGross : calculatedGross;

      const taxBreakdown = calculateTaxes(gross, province);
      const federalTax = toCurrency(toNumber(row.federal_tax) > 0 ? row.federal_tax : taxBreakdown.federal);
      const provincialTax = toCurrency(toNumber(row.provincial_tax) > 0 ? row.provincial_tax : taxBreakdown.provincial);
      const ei = toCurrency(toNumber(row.employment_insurance) > 0 ? row.employment_insurance : taxBreakdown.employment_insurance);
      // CPP and OAS: old entries store 0 for these fields. Annualise the daily
      // gross before the fallback calculation so the per-year exemptions
      // ($3 500 CPP basic / $87 282 OAS threshold) are applied correctly.
      const ANNUALIZE = 260;
      const annualTax = calculateTaxes(gross * ANNUALIZE, province);
      const cpp = toCurrency(toNumber(row.canada_pension_plan) > 0
        ? row.canada_pension_plan
        : annualTax.canada_pension_plan / ANNUALIZE);
      const oas = toCurrency(toNumber(row.old_age_security) > 0
        ? row.old_age_security
        : annualTax.old_age_security / ANNUALIZE);
      const sourceTax = toCurrency(row.tax ?? row.tx ?? 0);
      const totalTax = toCurrency(sourceTax > 0 ? sourceTax : (federalTax + provincialTax + ei + cpp + oas));
      const sourceNet = toCurrency(row.net ?? 0);
      const net = toCurrency(sourceNet > 0 ? sourceNet : (gross - totalTax));

      // Regular and overtime pay (dollar amounts, not hours).
      // Use wage when available; fall back to deriving it from gross / total hours.
      const effectiveWage = wage > 0 ? wage : (hours > 0 ? toCurrency(gross / hours) : 0);
      const regular_pay = toCurrency(effectiveWage * regularHours);
      const overtime_pay = toCurrency(effectiveWage * overtimeHours * 1.5);

      return {
        date,
        site_id: siteId,
        site_name: siteName,
        wage,
        hours,
        regular_hours: regularHours,
        overtime_hours: overtimeHours,
        travel,
        loa,
        regular_pay,
        overtime_pay,
        gross,
        net,
        federal_tax: federalTax,
        provincial_tax: provincialTax,
        employment_insurance: ei,
        canada_pension_plan: cpp,
        old_age_security: oas,
        tax: totalTax,
      };
    })
    .sort((a, b) => String(a.date).localeCompare(String(b.date)));
}

export function buildYtdReportJson(params) {
  const refCode = params?.referenceCode || '';
  const year = Number(params?.year) || new Date().getFullYear();
  const detailedRows = Array.isArray(params?.rows) ? params.rows : [];

  const summary = detailedRows.reduce((acc, row) => {
    acc.regular_hours += toNumber(row.regular_hours);
    acc.overtime_hours += toNumber(row.overtime_hours);
    acc.gross += toNumber(row.gross);
    acc.federal_tax += toNumber(row.federal_tax);
    acc.provincial_tax += toNumber(row.provincial_tax);
    acc.employment_insurance += toNumber(row.employment_insurance);
    acc.canada_pension_plan += toNumber(row.canada_pension_plan);
    acc.old_age_security += toNumber(row.old_age_security);
    acc.taxes += toNumber(row.tax);
    acc.net += toNumber(row.net);
    return acc;
  }, {
    regular_hours: 0,
    overtime_hours: 0,
    gross: 0,
    federal_tax: 0,
    provincial_tax: 0,
    employment_insurance: 0,
    canada_pension_plan: 0,
    old_age_security: 0,
    taxes: 0,
    net: 0,
  });

  const siteTotals = new Map();
  for (const row of detailedRows) {
    const siteKey = row.site_id || row.site_name || 'UNKNOWN';
    const existing = siteTotals.get(siteKey) || {
      site_id: siteKey,
      site_name: row.site_name || row.site_id || siteKey,
      regular: 0,
      overtime: 0,
      gross: 0,
      net: 0,
      employment_insurance: 0,
      canada_pension_plan: 0,
      old_age_security: 0,
      tax: 0,
    };
    existing.regular += toNumber(row.regular_hours);
    existing.overtime += toNumber(row.overtime_hours);
    existing.gross += toNumber(row.gross);
    existing.net += toNumber(row.net);
    existing.employment_insurance += toNumber(row.employment_insurance);
    existing.canada_pension_plan += toNumber(row.canada_pension_plan);
    existing.old_age_security += toNumber(row.old_age_security);
    existing.tax += toNumber(row.tax);
    siteTotals.set(siteKey, existing);
  }

  return {
    meta: {
      ...buildCommonMeta(params, 'Yearly', refCode),
      scope: 'yearly',
      year,
    },
    summary: {
      regular_hours: toHours(summary.regular_hours),
      overtime_hours: toHours(summary.overtime_hours),
      gross: toCurrency(summary.gross),
      federal_tax: toCurrency(summary.federal_tax),
      provincial_tax: toCurrency(summary.provincial_tax),
      employment_insurance: toCurrency(summary.employment_insurance),
      canada_pension_plan: toCurrency(summary.canada_pension_plan),
      old_age_security: toCurrency(summary.old_age_security),
      taxes: toCurrency(summary.taxes),
      net: toCurrency(summary.net),
    },
    rows: Array.from(siteTotals.values()).map((row) => ({
      site_id: row.site_id,
      site_name: row.site_name || row.site_id,
      regular: toHours(row.regular),
      overtime: toHours(row.overtime),
      gross: toCurrency(row.gross),
      net: toCurrency(row.net),
      employment_insurance: toCurrency(row.employment_insurance),
      canada_pension_plan: toCurrency(row.canada_pension_plan),
      old_age_security: toCurrency(row.old_age_security),
      tax: toCurrency(row.tax),
    })),
  };
}

function buildSummaryFromDetailedRows(detailedRows) {
  return detailedRows.reduce((acc, row) => {
    acc.regular_hours += toNumber(row.regular_hours);
    acc.overtime_hours += toNumber(row.overtime_hours);
    acc.gross += toNumber(row.gross);
    acc.federal_tax += toNumber(row.federal_tax);
    acc.provincial_tax += toNumber(row.provincial_tax);
    acc.employment_insurance += toNumber(row.employment_insurance);
    acc.canada_pension_plan += toNumber(row.canada_pension_plan);
    acc.old_age_security += toNumber(row.old_age_security);
    acc.taxes += toNumber(row.tax);
    acc.net += toNumber(row.net);
    return acc;
  }, {
    regular_hours: 0,
    overtime_hours: 0,
    gross: 0,
    federal_tax: 0,
    provincial_tax: 0,
    employment_insurance: 0,
    canada_pension_plan: 0,
    old_age_security: 0,
    taxes: 0,
    net: 0,
  });
}

function normalizeSummary(summary) {
  return {
    regular_hours: toHours(summary.regular_hours),
    overtime_hours: toHours(summary.overtime_hours),
    gross: toCurrency(summary.gross),
    federal_tax: toCurrency(summary.federal_tax),
    provincial_tax: toCurrency(summary.provincial_tax),
    employment_insurance: toCurrency(summary.employment_insurance),
    canada_pension_plan: toCurrency(summary.canada_pension_plan),
    old_age_security: toCurrency(summary.old_age_security),
    taxes: toCurrency(summary.taxes),
    net: toCurrency(summary.net),
  };
}

function buildCommonMeta(params, scopeLabel, refCode = '') {
  const year = Number(params?.year) || new Date().getFullYear();
  const employee = String(params?.employee || '');
  const email = String(params?.email || '');
  const phone = String(params?.phone || '');
  const ipAddress = String(params?.ipAddress || 'unknown');
  const address = String(params?.address || '');
  const fullName = String(params?.fullName ?? '');
  const city = String(params?.city || '');
  const province = String(params?.province || '');
  const postal = String(params?.postal || '');
  const createdAt = formatCreatedTimestampUtc();
  const asAt = formatAsAtDate();
  const referenceCode = refCode || generateReferenceCode(16);

  return {
    title: `PayCal.app - ${year} ${scopeLabel} Earnings Report`,
    subtitle: asAt,
    employee,
    year,
    email,
    phone,
    ip_address: ipAddress,
    address,
    full_name: fullName,
    city,
    province,
    postal,
    created_at: createdAt,
    as_at: asAt,
    reference_code: referenceCode,
  };
}

export function buildYearlyReportJson(params) {
  return buildYtdReportJson(params);
}

export function buildMonthlyReportJson(params) {
  const refCode = params?.referenceCode || '';
  const detailedRows = Array.isArray(params?.rows) ? params.rows : [];
  const monthlySiteTotals = new Map();

  for (const row of detailedRows) {
    const monthKey = String(row.date || '').slice(0, 7);
    if (!monthKey) {
      continue;
    }

    const siteKey = String(row.site_name || row.site_id || 'UNKNOWN');
    const mapKey = `${monthKey}::${siteKey}`;

    const existing = monthlySiteTotals.get(mapKey) || {
      month: monthKey,
      site_name: siteKey,
      regular: 0,
      overtime: 0,
      gross: 0,
      employment_insurance: 0,
      canada_pension_plan: 0,
      old_age_security: 0,
      tax: 0,
    };

    existing.regular += toNumber(row.regular_hours);
    existing.overtime += toNumber(row.overtime_hours);
    existing.gross += toNumber(row.gross);
    existing.employment_insurance += toNumber(row.employment_insurance);
    existing.canada_pension_plan += toNumber(row.canada_pension_plan);
    existing.old_age_security += toNumber(row.old_age_security);
    existing.tax += toNumber(row.tax);
    monthlySiteTotals.set(mapKey, existing);
  }

  const summary = normalizeSummary(buildSummaryFromDetailedRows(detailedRows));

  return {
    meta: {
      ...buildCommonMeta(params, 'Monthly', refCode),
      scope: 'monthly',
    },
    summary,
    rows: Array.from(monthlySiteTotals.values())
      .sort((a, b) => {
        if (a.month !== b.month) {
          return a.month.localeCompare(b.month);
        }
        return a.site_name.localeCompare(b.site_name);
      })
      .map((row) => ({
        month: row.month,
        site_name: row.site_name,
        regular: toHours(row.regular),
        overtime: toHours(row.overtime),
        gross: toCurrency(row.gross),
        employment_insurance: toCurrency(row.employment_insurance),
        canada_pension_plan: toCurrency(row.canada_pension_plan),
        old_age_security: toCurrency(row.old_age_security),
        tax: toCurrency(row.tax),
      })),
  };
}

export function buildDailyReportJson(params) {
  const refCode = params?.referenceCode || '';
  const detailedRows = Array.isArray(params?.rows) ? params.rows : [];
  const dailyTotals = new Map();

  for (const row of detailedRows) {
    const dayKey = String(row.date || '');
    if (!dayKey) {
      continue;
    }

    const existing = dailyTotals.get(dayKey) || {
      date: dayKey,
      site_name: '',
      regular: 0,
      overtime: 0,
      travel: 0,
      loa: 0,
      gross: 0,
      employment_insurance: 0,
      canada_pension_plan: 0,
      old_age_security: 0,
      tax: 0,
      net: 0,
    };

    const rowSite = String(row.site_name || '').trim();
    if (rowSite) {
      if (!existing.site_name) {
        existing.site_name = rowSite;
      } else if (existing.site_name !== rowSite) {
        existing.site_name = 'Multiple Sites';
      }
    }

    existing.regular += toNumber(row.regular_hours);
    existing.overtime += toNumber(row.overtime_hours);
    existing.travel += toNumber(row.travel);
    existing.loa += toNumber(row.loa);
    existing.gross += toNumber(row.gross);
    existing.employment_insurance += toNumber(row.employment_insurance);
    existing.canada_pension_plan += toNumber(row.canada_pension_plan);
    existing.old_age_security += toNumber(row.old_age_security);
    existing.tax += toNumber(row.tax);
    existing.net += toNumber(row.net);
    dailyTotals.set(dayKey, existing);
  }

  const summary = normalizeSummary(buildSummaryFromDetailedRows(detailedRows));

  return {
    meta: {
      ...buildCommonMeta(params, 'Daily', refCode),
      scope: 'daily',
    },
    summary,
    rows: Array.from(dailyTotals.entries())
      .sort(([a], [b]) => a.localeCompare(b))
      .map(([date, row]) => ({
        date: row.date || date,
        site_name: row.site_name || '',
        travel: toHours(row.travel),
        loa: toCurrency(row.loa),
        regular: toHours(row.regular),
        overtime: toHours(row.overtime),
        gross: toCurrency(row.gross),
        employment_insurance: toCurrency(row.employment_insurance),
        canada_pension_plan: toCurrency(row.canada_pension_plan),
        old_age_security: toCurrency(row.old_age_security),
        tax: toCurrency(row.tax),
        net: toCurrency(row.net),
      })),
  };
}

export function generateYearlyCsv(detailedRows, report = null) {
  const rowOf = (size, first = '') => [first, ...Array(Math.max(0, size - 1)).fill('')];

  const header = [
    'Date', 'Site', 'Wage', 'Hours', 'Regular', 'OT',
    'Gross', 'Net', 'FTax', 'PTax', 'EI', 'CPP', 'OAS',
  ];

  const meta = report?.meta || {};
  const year = String(detailedRows[0]?.date || '').slice(0, 4) || String(new Date().getFullYear());
  const titleLine = buildTitleLine(meta, `PayCal.app - ${year} Yearly Earnings Report`);

  const lines = [
    rowOf(header.length, titleLine).map(escapeCsvCell).join(','),
    buildIdentityCsvLine(header.length, meta).map(escapeCsvCell).join(','),
    rowOf(header.length).map(escapeCsvCell).join(','),
    header.join(','),
  ];

  for (const row of detailedRows) {
    lines.push([
      row.date,
      row.site_name,
      row.wage.toFixed(2),
      row.hours.toFixed(2),
      row.regular_pay.toFixed(2),
      row.overtime_pay.toFixed(2),
      row.gross.toFixed(2),
      row.net.toFixed(2),
      row.federal_tax.toFixed(2),
      row.provincial_tax.toFixed(2),
      row.employment_insurance.toFixed(2),
      row.canada_pension_plan.toFixed(2),
      row.old_age_security.toFixed(2),
    ].map(escapeCsvCell).join(','));
  }

  const totals = detailedRows.reduce((acc, row) => {
    acc.regular += toNumber(row.regular_pay);
    acc.ot += toNumber(row.overtime_pay);
    acc.gross += toNumber(row.gross);
    return acc;
  }, { regular: 0, ot: 0, gross: 0 });

  lines.push([
    'TOTALS', '', '', '',
    totals.regular.toFixed(2),
    totals.ot.toFixed(2),
    totals.gross.toFixed(2),
    '', '', '', '', '', '',
  ].map(escapeCsvCell).join(','));

  lines.push(rowOf(header.length).map(escapeCsvCell).join(','));
  lines.push(rowOf(header.length, `Created: ${String(meta.created_at || formatCreatedTimestampUtc())} from IP Address ${String(meta.ip_address || 'unknown')}`).map(escapeCsvCell).join(','));
  lines.push(rowOf(header.length, `REF: ${String(meta.reference_code || generateReferenceCode(16))}`).map(escapeCsvCell).join(','));

  return `${lines.join('\n')}\n`;
}

export function generateYearlyTxt(detailedRows, report = null) {
  const money = (value) => {
    const amount = Number(value || 0);
    return `$${amount.toLocaleString(USER_LOCALE, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })}`;
  };

  const summaryFromRows = detailedRows.reduce((acc, row) => {
    acc.gross += toNumber(row.gross);
    acc.federal_tax += toNumber(row.federal_tax);
    acc.provincial_tax += toNumber(row.provincial_tax);
    acc.employment_insurance += toNumber(row.employment_insurance);
    acc.canada_pension_plan += toNumber(row.canada_pension_plan);
    acc.old_age_security += toNumber(row.old_age_security);
    acc.taxes += toNumber(row.tax);
    acc.net += toNumber(row.net);
    return acc;
  }, {
    gross: 0,
    federal_tax: 0,
    provincial_tax: 0,
    employment_insurance: 0,
    canada_pension_plan: 0,
    old_age_security: 0,
    taxes: 0,
    net: 0,
  });

  const summary = report?.summary || summaryFromRows;
  const meta = report?.meta || {};
  const inferredYear = String(detailedRows[0]?.date || '').slice(0, 4) || new Date().getFullYear();
  const titleLine = buildTitleLine(meta, `PayCal.app - ${inferredYear} Yearly Earnings Report`);

  const lines = [
    titleLine,
    '',
    ...buildIdentityTextLines(meta),
    '',
  ];

  for (const row of detailedRows) {
    lines.push(`${row.date}  ${row.site_name}`);
    lines.push(`  Wage: ${row.wage.toFixed(2)}  Hours: ${row.hours.toFixed(2)} (Reg ${row.regular_hours.toFixed(2)} / OT ${row.overtime_hours.toFixed(2)})`);
    lines.push(`  Gross: ${row.gross.toFixed(2)}  Net: ${row.net.toFixed(2)}  Tax: ${row.tax.toFixed(2)}`);
    lines.push(`  Federal: ${row.federal_tax.toFixed(2)}  Provincial: ${row.provincial_tax.toFixed(2)}  EI: ${row.employment_insurance.toFixed(2)}  CPP: ${row.canada_pension_plan.toFixed(2)}  OAS: ${row.old_age_security.toFixed(2)}`);
    lines.push('');
  }

  while (lines.length > 0 && lines[lines.length - 1] === '') {
    lines.pop();
  }

  lines.push('');
  lines.push('Summary');
  lines.push(`Gross YTD: ${money(summary.gross)}`);
  lines.push(`Federal Tax: ${money(summary.federal_tax)}`);
  lines.push(`Provincial Tax: ${money(summary.provincial_tax)}`);
  lines.push(`EI: ${money(summary.employment_insurance)}`);
  lines.push(`CPP: ${money(summary.canada_pension_plan)}`);
  lines.push(`OAS: ${money(summary.old_age_security)}`);
  lines.push(`Taxes YTD: ${money(summary.taxes)}`);
  lines.push(`Net YTD: ${money(summary.net)}`);
  lines.push('');
  lines.push(`Created: ${String(meta.created_at || '')} from IP Address ${String(meta.ip_address || 'unknown')}`);
  lines.push(`REF: ${String(meta.reference_code || '')}`);

  return `${lines.join('\n')}\n`;
}

export function generateMonthlyCsv(detailedRows, report = null) {
  const rowOf = (size, first = '') => [first, ...Array(Math.max(0, size - 1)).fill('')];

  const header = ['Month', 'Regular', 'OT', 'Gross', 'Tax', 'Net'];
  const meta = report?.meta || {};
  const inferredYear = String(detailedRows[0]?.date || '').slice(0, 4) || String(new Date().getFullYear());
  const titleLine = buildTitleLine(meta, `PayCal.app - ${inferredYear} Monthly Earnings Report`);
  const monthly = new Map();

  for (const row of detailedRows) {
    const monthKey = String(row.date || '').slice(0, 7);
    if (!monthKey) {
      continue;
    }
    const m = monthly.get(monthKey) || { regular: 0, ot: 0, gross: 0, tax: 0, net: 0 };
    m.regular += toNumber(row.regular_hours);
    m.ot += toNumber(row.overtime_hours);
    m.gross += toNumber(row.gross);
    m.tax += toNumber(row.tax);
    m.net += toNumber(row.net);
    monthly.set(monthKey, m);
  }

  const lines = [
    rowOf(header.length, titleLine).map(escapeCsvCell).join(','),
    buildIdentityCsvLine(header.length, meta).map(escapeCsvCell).join(','),
    rowOf(header.length).map(escapeCsvCell).join(','),
    header.join(','),
  ];
  for (const [month, m] of Array.from(monthly.entries()).sort(([a], [b]) => a.localeCompare(b))) {
    lines.push([
      month,
      m.regular.toFixed(2),
      m.ot.toFixed(2),
      m.gross.toFixed(2),
      m.tax.toFixed(2),
      m.net.toFixed(2),
    ].map(escapeCsvCell).join(','));
  }

  lines.push(rowOf(header.length).map(escapeCsvCell).join(','));
  lines.push(rowOf(header.length, `Created: ${String(meta.created_at || formatCreatedTimestampUtc())} from IP Address ${String(meta.ip_address || 'unknown')}`).map(escapeCsvCell).join(','));
  lines.push(rowOf(header.length, `REF: ${String(meta.reference_code || generateReferenceCode(16))}`).map(escapeCsvCell).join(','));

  return `${lines.join('\n')}\n`;
}

export function generateMonthlyTxt(detailedRows, report = null) {
  const money = (value) => {
    const amount = Number(value || 0);
    return `$${amount.toLocaleString(USER_LOCALE, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })}`;
  };
  const monthly = new Map();

  for (const row of detailedRows) {
    const monthKey = String(row.date || '').slice(0, 7);
    if (!monthKey) {
      continue;
    }
    const m = monthly.get(monthKey) || { regular: 0, ot: 0, gross: 0, tax: 0, net: 0 };
    m.regular += toNumber(row.regular_hours);
    m.ot += toNumber(row.overtime_hours);
    m.gross += toNumber(row.gross);
    m.tax += toNumber(row.tax);
    m.net += toNumber(row.net);
    monthly.set(monthKey, m);
  }

  const summaryFromRows = detailedRows.reduce((acc, row) => {
    acc.regular_hours += toNumber(row.regular_hours);
    acc.overtime_hours += toNumber(row.overtime_hours);
    acc.gross += toNumber(row.gross);
    acc.federal_tax += toNumber(row.federal_tax);
    acc.provincial_tax += toNumber(row.provincial_tax);
    acc.employment_insurance += toNumber(row.employment_insurance);
    acc.canada_pension_plan += toNumber(row.canada_pension_plan);
    acc.old_age_security += toNumber(row.old_age_security);
    acc.taxes += toNumber(row.tax);
    acc.net += toNumber(row.net);
    return acc;
  }, {
    regular_hours: 0,
    overtime_hours: 0,
    gross: 0,
    federal_tax: 0,
    provincial_tax: 0,
    employment_insurance: 0,
    canada_pension_plan: 0,
    old_age_security: 0,
    taxes: 0,
    net: 0,
  });

  const summary = report?.summary || summaryFromRows;
  const meta = report?.meta || {};
  const inferredYear = String(detailedRows[0]?.date || '').slice(0, 4) || new Date().getFullYear();
  const titleLine = buildTitleLine(meta, `PayCal.app - ${inferredYear} Monthly Earnings Report`);

  const lines = [
    titleLine,
    '',
    ...buildIdentityTextLines(meta),
    '',
  ];

  for (const [month, m] of Array.from(monthly.entries()).sort(([a], [b]) => a.localeCompare(b))) {
    lines.push(month);
    lines.push(`  Regular: ${m.regular.toFixed(2)}  OT: ${m.ot.toFixed(2)}`);
    lines.push(`  Gross: ${m.gross.toFixed(2)}  Tax: ${m.tax.toFixed(2)}  Net: ${m.net.toFixed(2)}`);
    lines.push('');
  }

  while (lines.length > 0 && lines[lines.length - 1] === '') {
    lines.pop();
  }

  lines.push('');
  lines.push('Summary');
  lines.push(`Regular Hours: ${Number(summary.regular_hours || 0).toFixed(2)}`);
  lines.push(`Overtime Hours: ${Number(summary.overtime_hours || 0).toFixed(2)}`);
  lines.push(`Gross: ${money(summary.gross)}`);
  lines.push(`Federal Tax: ${money(summary.federal_tax)}`);
  lines.push(`Provincial Tax: ${money(summary.provincial_tax)}`);
  lines.push(`EI: ${money(summary.employment_insurance)}`);
  lines.push(`CPP: ${money(summary.canada_pension_plan)}`);
  lines.push(`OAS: ${money(summary.old_age_security)}`);
  lines.push(`Taxes: ${money(summary.taxes)}`);
  lines.push(`Net: ${money(summary.net)}`);
  lines.push('');
  lines.push(`Created: ${String(meta.created_at || '')} from IP Address ${String(meta.ip_address || 'unknown')}`);
  lines.push(`REF: ${String(meta.reference_code || '')}`);

  return `${lines.join('\n')}\n`;
}

export function generateDailyCsv(detailedRows, report = null) {
  const rowOf = (size, first = '') => [first, ...Array(Math.max(0, size - 1)).fill('')];
  const header = [
    'Date', 'Site', 'Regular', 'Overtime', 'Travel', 'LOA',
    'Gross', 'Net',
  ];
  const meta = report?.meta || {};
  const inferredYear = String(detailedRows[0]?.date || '').slice(0, 4) || String(new Date().getFullYear());
  const titleLine = buildTitleLine(meta, `PayCal.app - ${inferredYear} Daily Earnings Report`);

  const csvRows = Array.isArray(report?.rows) && report.rows.length > 0
    ? [...report.rows]
        .sort((a, b) => String(a?.date || '').localeCompare(String(b?.date || '')))
        .map((row) => ({
          date: String(row?.date || ''),
          site_name: String(row?.site_name || ''),
          travel: toNumber(row?.travel),
          loa: toNumber(row?.loa),
          regular: toNumber(row?.regular),
          overtime: toNumber(row?.overtime),
          gross: toNumber(row?.gross),
          net: toNumber(row?.net),
        }))
    : Array.from(detailedRows.reduce((acc, row) => {
        const dayKey = String(row?.date || '');
        if (!dayKey) {
          return acc;
        }

        const existing = acc.get(dayKey) || {
          date: dayKey,
          site_name: '',
          travel: 0,
          loa: 0,
          regular: 0,
          overtime: 0,
          gross: 0,
          net: 0,
        };

        const siteName = String(row?.site_name || '').trim();
        if (siteName) {
          if (!existing.site_name) {
            existing.site_name = siteName;
          } else if (existing.site_name !== siteName) {
            existing.site_name = 'Multiple Sites';
          }
        }

        existing.travel += toNumber(row?.travel);
        existing.loa += toNumber(row?.loa);
        existing.regular += toNumber(row?.regular_hours);
        existing.overtime += toNumber(row?.overtime_hours);
        existing.gross += toNumber(row?.gross);
        existing.net += toNumber(row?.net);
        acc.set(dayKey, existing);
        return acc;
      }, new Map()).entries())
        .sort(([a], [b]) => a.localeCompare(b))
        .map(([, row]) => row);

  const lines = [
    rowOf(header.length, titleLine).map(escapeCsvCell).join(','),
    buildIdentityCsvLine(header.length, meta).map(escapeCsvCell).join(','),
    rowOf(header.length).map(escapeCsvCell).join(','),
    header.join(','),
  ];

  for (const row of csvRows) {
    lines.push([
      row.date,
      row.site_name,
      toNumber(row.regular).toFixed(2),
      toNumber(row.overtime).toFixed(2),
      toNumber(row.travel).toFixed(2),
      toNumber(row.loa).toFixed(2),
      toNumber(row.gross).toFixed(2),
      toNumber(row.net).toFixed(2),
    ].map(escapeCsvCell).join(','));
  }

  return `${lines.join('\n')}\n`;
}

export function generateDailyTxt(detailedRows, report = null) {
  const money = (value) => {
    const amount = Number(value || 0);
    return `$${amount.toLocaleString(USER_LOCALE, {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })}`;
  };
  const summaryFromRows = detailedRows.reduce((acc, row) => {
    acc.regular_hours += toNumber(row.regular_hours);
    acc.overtime_hours += toNumber(row.overtime_hours);
    acc.gross += toNumber(row.gross);
    acc.federal_tax += toNumber(row.federal_tax);
    acc.provincial_tax += toNumber(row.provincial_tax);
    acc.employment_insurance += toNumber(row.employment_insurance);
    acc.canada_pension_plan += toNumber(row.canada_pension_plan);
    acc.old_age_security += toNumber(row.old_age_security);
    acc.taxes += toNumber(row.tax);
    acc.net += toNumber(row.net);
    return acc;
  }, {
    regular_hours: 0,
    overtime_hours: 0,
    gross: 0,
    federal_tax: 0,
    provincial_tax: 0,
    employment_insurance: 0,
    canada_pension_plan: 0,
    old_age_security: 0,
    taxes: 0,
    net: 0,
  });

  const summary = report?.summary || summaryFromRows;
  const meta = report?.meta || {};
  const inferredYear = String(detailedRows[0]?.date || '').slice(0, 4) || new Date().getFullYear();
  const titleLine = buildTitleLine(meta, `PayCal.app - ${inferredYear} Daily Earnings Report`);

  const lines = [
    titleLine,
    '',
    ...buildIdentityTextLines(meta),
    '',
  ];

  for (const row of detailedRows) {
    lines.push(`${row.date}  ${row.site_name}`);
    lines.push(`  Wage: ${row.wage.toFixed(2)}  Hours: ${row.hours.toFixed(2)} (Reg ${row.regular_hours.toFixed(2)} / OT ${row.overtime_hours.toFixed(2)}) Travel: ${toNumber(row.travel).toFixed(2)} LOA: ${toNumber(row.loa).toFixed(2)}`);
    lines.push(`  Gross: ${row.gross.toFixed(2)}  Tax: ${row.tax.toFixed(2)}  Net: ${row.net.toFixed(2)}`);
    lines.push('');
  }

  while (lines.length > 0 && lines[lines.length - 1] === '') {
    lines.pop();
  }

  lines.push('');
  lines.push('Summary');
  lines.push(`Regular Hours: ${Number(summary.regular_hours || 0).toFixed(2)}`);
  lines.push(`Overtime Hours: ${Number(summary.overtime_hours || 0).toFixed(2)}`);
  lines.push(`Gross: ${money(summary.gross)}`);
  lines.push(`Federal Tax: ${money(summary.federal_tax)}`);
  lines.push(`Provincial Tax: ${money(summary.provincial_tax)}`);
  lines.push(`EI: ${money(summary.employment_insurance)}`);
  lines.push(`CPP: ${money(summary.canada_pension_plan)}`);
  lines.push(`OAS: ${money(summary.old_age_security)}`);
  lines.push(`Taxes: ${money(summary.taxes)}`);
  lines.push(`Net: ${money(summary.net)}`);
  lines.push('');
  lines.push(`Created: ${String(meta.created_at || '')} from IP Address ${String(meta.ip_address || 'unknown')}`);
  lines.push(`REF: ${String(meta.reference_code || '')}`);

  return `${lines.join('\n')}\n`;
}

export function downloadTextFile(content, filename, mimeType = 'text/plain;charset=utf-8') {
  const blob = new Blob([content], { type: mimeType });
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);
}

export async function generatePdfWithExternalUtility(reportJson, options = {}) {
  const explicitGenerator = options.generator;
  if (typeof explicitGenerator === 'function') {
    return explicitGenerator(reportJson, options);
  }

  const windowGenerator =
    window?.PayCalPdfGenerator?.generatePdfFromJson ||
    window?.PayCalPdf?.generatePdfFromJson;

  if (typeof windowGenerator === 'function') {
    return windowGenerator(reportJson, options);
  }

  return renderPayCalPdf(reportJson);
}

export function downloadPdfFile(pdfData, filename = 'earnings-report.pdf') {
  const blob = pdfData instanceof Blob
    ? pdfData
    : new Blob([pdfData], { type: 'application/pdf' });
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(url);
}

const EarningsExport = {
  buildDetailedRows,
  buildYearlyReportJson,
  buildMonthlyReportJson,
  buildDailyReportJson,
  buildYtdReportJson,
  generateYearlyCsv,
  generateYearlyTxt,
  generateMonthlyCsv,
  generateMonthlyTxt,
  generateDailyCsv,
  generateDailyTxt,
  generatePdfWithExternalUtility,
  downloadPdfFile,
  downloadTextFile,
};

if (typeof window !== 'undefined') {
  window.PayCalEarningsExport = EarningsExport;
}

export default EarningsExport;
