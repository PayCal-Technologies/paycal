/**
 * Client-side tax calculator parity with PayCal\Domain\Taxes.
 * Brackets are sourced from tax-rate-manifest.php generated from
 * html/src/Domain/TaxRateTablesData.json.
 */

import TAX_RATE_TABLES from '/js/earnings/tax-rate-manifest.php';

function normalizeBracketRow(row) {
  if (!Array.isArray(row) || row.length < 3) {
    return null;
  }

  const start = Number(row[0]);
  const endRaw = Number(row[1]);
  const basisPoints = Number(row[2]);

  if (!Number.isFinite(start) || !Number.isFinite(endRaw) || !Number.isFinite(basisPoints)) {
    return null;
  }

  const end = Math.min(endRaw, Number.MAX_SAFE_INTEGER);
  return [Math.max(0, start), Math.max(0, end), Math.max(0, basisPoints)];
}

function normalizeBrackets(rows) {
  if (!Array.isArray(rows)) {
    return [];
  }

  return rows
    .map(normalizeBracketRow)
    .filter((row) => Array.isArray(row));
}

function getSupportedTaxYears() {
  return Object.keys(TAX_RATE_TABLES?.federal || {})
    .map((year) => Number(year))
    .filter((year) => Number.isFinite(year))
    .sort((a, b) => a - b);
}

function resolveTaxYear(preferredYear = new Date().getFullYear()) {
  const years = getSupportedTaxYears();
  if (years.length === 0) {
    return Number(preferredYear) || new Date().getFullYear();
  }

  const requested = Number(preferredYear) || years[years.length - 1];
  const minYear = years[0];
  const maxYear = years[years.length - 1];

  if (requested < minYear) {
    return minYear;
  }
  if (requested > maxYear) {
    return maxYear;
  }

  return requested;
}

function getFederalBracketsForYear(year) {
  const byYear = TAX_RATE_TABLES?.federal || {};
  const resolved = byYear[String(year)] || byYear[year];
  const normalized = normalizeBrackets(resolved);
  return normalized.length > 0 ? normalized : [[0, Number.MAX_SAFE_INTEGER, 0]];
}

function getProvincialBracketsForYear(year) {
  const provincial = TAX_RATE_TABLES?.provincial || {};
  const out = {};

  Object.entries(provincial).forEach(([provinceName, byYear]) => {
    const yearRows = (byYear && typeof byYear === 'object')
      ? (byYear[String(year)] || byYear[year])
      : null;
    const normalized = normalizeBrackets(yearRows);
    if (normalized.length > 0) {
      out[provinceName] = normalized;
    }
  });

  return out;
}

const ACTIVE_TAX_YEAR = resolveTaxYear();
const FEDERAL_BRACKETS = getFederalBracketsForYear(ACTIVE_TAX_YEAR);
const PROVINCIAL_BRACKETS = getProvincialBracketsForYear(ACTIVE_TAX_YEAR);

const EI_MAX_INSURABLE_EARNINGS_CENTS = 6320000;
const EI_RATE_BASIS_POINTS = 158;

const CPP_BASIC_EXEMPTION_CENTS = 350000;
const CPP_MAX_PENSIONABLE_CENTS = 6850000;
const CPP_RATE_BASIS_POINTS = 595;

const OAS_THRESHOLD_CENTS = 8728200;
const OAS_RATE_BASIS_POINTS = 1500;

function roundHalfUp(value) {
  return Math.round(value);
}

function calculateBracketedTaxCents(incomeCents, brackets) {
  const income = Math.max(0, Number(incomeCents) || 0);
  let total = 0;

  for (const [start, end, basisPoints] of brackets) {
    if (income <= start) {
      continue;
    }

    const taxable = Math.min(income, end) - start;
    if (taxable <= 0) {
      continue;
    }

    total += roundHalfUp((taxable * basisPoints) / 10000);

    if (income < end) {
      break;
    }
  }

  return total;
}

export function calculateTaxesCents(incomeCents, province = 'Alberta') {
  const normalizedIncome = Math.max(0, Number(incomeCents) || 0);
  const fallbackProvince = PROVINCIAL_BRACKETS.Alberta
    || PROVINCIAL_BRACKETS.Ontario
    || Object.values(PROVINCIAL_BRACKETS)[0]
    || [[0, Number.MAX_SAFE_INTEGER, 0]];
  const provincialBrackets = PROVINCIAL_BRACKETS[province] || fallbackProvince;

  const federal = calculateBracketedTaxCents(normalizedIncome, FEDERAL_BRACKETS);
  const provincial = calculateBracketedTaxCents(normalizedIncome, provincialBrackets);

  const eiCapped = Math.min(normalizedIncome, EI_MAX_INSURABLE_EARNINGS_CENTS);
  const employment_insurance = roundHalfUp((eiCapped * EI_RATE_BASIS_POINTS) / 10000);

  const cppAdjusted = Math.max(0, normalizedIncome - CPP_BASIC_EXEMPTION_CENTS);
  const cppCap = CPP_MAX_PENSIONABLE_CENTS - CPP_BASIC_EXEMPTION_CENTS;
  const cppCapped = Math.min(cppAdjusted, cppCap);
  const canada_pension_plan = roundHalfUp((cppCapped * CPP_RATE_BASIS_POINTS) / 10000);

  const old_age_security = normalizedIncome <= OAS_THRESHOLD_CENTS
    ? 0
    : roundHalfUp(((normalizedIncome - OAS_THRESHOLD_CENTS) * OAS_RATE_BASIS_POINTS) / 10000);

  const incomeTax = federal + provincial;
  const totalDeductions = incomeTax + employment_insurance + canada_pension_plan + old_age_security;

  return {
    federal,
    provincial,
    employment_insurance,
    canada_pension_plan,
    old_age_security,
    incomeTax,
    totalDeductions,
  };
}

export function calculateTaxes(incomeDollars, province = 'Alberta') {
  const incomeCents = Math.round((Number(incomeDollars) || 0) * 100);
  const cents = calculateTaxesCents(incomeCents, province);

  return {
    federal: cents.federal / 100,
    provincial: cents.provincial / 100,
    employment_insurance: cents.employment_insurance / 100,
    canada_pension_plan: cents.canada_pension_plan / 100,
    old_age_security: cents.old_age_security / 100,
    incomeTax: cents.incomeTax / 100,
    totalDeductions: cents.totalDeductions / 100,
  };
}

export const TaxBrackets = {
  taxYear: ACTIVE_TAX_YEAR,
  federal: FEDERAL_BRACKETS,
  provincial: PROVINCIAL_BRACKETS,
};

if (typeof window !== 'undefined') {
  window.PayCalTaxes = {
    calculateTaxes,
    calculateTaxesCents,
    TaxBrackets,
  };
}
