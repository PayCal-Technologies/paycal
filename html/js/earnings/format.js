/**
 * Shared locale-aware number and currency formatting for earnings charts.
 */

import { resolveUserLocale } from '/js/core/locale.js';

/**
 * @param {object} [options]
 * @param {string} [options.locale]
 */
export function createEarningsFormatHelpers(options = {}) {
  const locale = String(options.locale || resolveUserLocale()).trim() || 'en-US';

  const amountFormatter = new Intl.NumberFormat(locale, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
  const compactAmountFormatter = new Intl.NumberFormat(locale, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
  const percentFormatter = new Intl.NumberFormat(locale, {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  });
  const percentOneDecimalFormatter = new Intl.NumberFormat(locale, {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  });
  const hoursFormatter = new Intl.NumberFormat(locale, {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  });

  const formatCurrency = (value) => `$${amountFormatter.format(Number(value) || 0)}`;
  const formatCurrencyCompact = (value) => `$${compactAmountFormatter.format(Number(value) || 0)}`;
  const formatAmount = (value, minFractionDigits = 2, maxFractionDigits = 2) => (
    new Intl.NumberFormat(locale, {
      minimumFractionDigits: minFractionDigits,
      maximumFractionDigits: maxFractionDigits,
    }).format(Number(value) || 0)
  );
  const formatPercent = (value, fractionDigits = 0) => {
    const formatter = fractionDigits === 1 ? percentOneDecimalFormatter : percentFormatter;
    return `${formatter.format(Number(value) || 0)}%`;
  };
  const formatHours = (value) => hoursFormatter.format(Number(value) || 0);

  return {
    locale,
    amountFormatter,
    formatCurrency,
    formatCurrencyCompact,
    formatAmount,
    formatPercent,
    formatHours,
  };
}
