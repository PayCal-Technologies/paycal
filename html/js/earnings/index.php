<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
Javascript::renderModuleContentType('application/javascript');

Javascript::renderDocBlock();

$user = User::current();
$earningsI18nKeys = [
  'GROSS', 'NET', 'DEDUCTIONS', 'EARNINGS_PIEGRAPHS_NO_VALUES', 'EARNINGS_LABEL',
  'DATE', 'SITE', 'WAGE', 'HOURS', 'REGULAR_HOURS', 'OVERTIME_HOURS', 'LOA', 'TRAVEL',
  'NOT_FOUND', 'EARNINGS_GRID_ROW_SINGULAR', 'EARNINGS_GRID_ROW_PLURAL', 'EARNINGS_GRID_STATUS_TEMPLATE',
  'EARNINGS_TREND_NO_DATA_STATUS', 'EARNINGS_TREND_NO_DATA_DESC', 'EARNINGS_TREND_NO_NUMERIC_STATUS',
  'EARNINGS_TREND_NO_NUMERIC_DESC', 'EARNINGS_TREND_UPDATED_STATUS', 'EARNINGS_TREND_UPDATED_DESC',
  'EARNINGS_CHART_DATA_LOAD_FAILED', 'EARNINGS_TREND_LOAD_FAILED_STATUS',
  'EARNINGS_TREND_DIRECTION_INCREASING', 'EARNINGS_TREND_DIRECTION_DECREASING', 'EARNINGS_TREND_DIRECTION_FLAT',
  'EARNINGS_TREND_HOVER_TOOLTIP', 'EARNINGS_TREND_Y_AXIS_LABEL', 'EARNINGS_TREND_TOUCH_HINT',
  'EARNINGS_DAILY_LOAD_FAILED_PREFIX', 'EARNINGS_DAILY_NO_DATA_FOR_YEAR', 'EARNINGS_UNKNOWN_ERROR',
  'EARNINGS_FORECAST_TITLE', 'EARNINGS_FORECAST_BADGE_ESTIMATE', 'EARNINGS_FORECAST_BADGE_NOT_CRA',
  'EARNINGS_FORECAST_WORKSPACE_ARIA', 'EARNINGS_FORECAST_LOADING', 'EARNINGS_FORECAST_NEXT_PAYCHECK',
  'EARNINGS_FORECAST_NEXT_30_DAYS', 'EARNINGS_FORECAST_YEAR_PROJECTION', 'EARNINGS_FORECAST_CARD_GROSS',
  'EARNINGS_FORECAST_CARD_HOURS', 'EARNINGS_FORECAST_CARD_CONFIDENCE', 'EARNINGS_FORECAST_SCENARIO_CONSERVATIVE',
  'EARNINGS_FORECAST_SCENARIO_NORMAL', 'EARNINGS_FORECAST_SCENARIO_OVERTIME', 'EARNINGS_FORECAST_SCENARIO_CUSTOM',
  'EARNINGS_FORECAST_SCENARIOS_TITLE', 'EARNINGS_FORECAST_ASSUMPTIONS_TITLE', 'EARNINGS_FORECAST_ASSUMPTIONS_EMPTY',
  'EARNINGS_FORECAST_ASSUMP_FIELD', 'EARNINGS_FORECAST_ASSUMP_VALUE', 'EARNINGS_FORECAST_ASSUMP_SOURCE',
  'EARNINGS_FORECAST_VALUE_MISSING', 'EARNINGS_FORECAST_SOURCE_SAVED', 'EARNINGS_FORECAST_SOURCE_SCHEDULED',
  'EARNINGS_FORECAST_SOURCE_TEMPORARY', 'EARNINGS_FORECAST_SOURCE_ESTIMATED', 'EARNINGS_FORECAST_SOURCE_MISSING',
  'EARNINGS_FORECAST_CONFIDENCE_HIGH', 'EARNINGS_FORECAST_CONFIDENCE_MEDIUM', 'EARNINGS_FORECAST_CONFIDENCE_LOW',
  'EARNINGS_FORECAST_TIMELINE_TITLE', 'EARNINGS_FORECAST_TIMELINE_SR_FMT', 'EARNINGS_FORECAST_CALC_TITLE',
  'EARNINGS_FORECAST_RESET_PROFILE', 'EARNINGS_FORECAST_RESET_SCHEDULED', 'EARNINGS_FORECAST_CALC_WAGE',
  'EARNINGS_FORECAST_CALC_REG_HRS', 'EARNINGS_FORECAST_CALC_OT_HRS', 'EARNINGS_FORECAST_CALC_LOA',
  'EARNINGS_FORECAST_CALC_TRAVEL', 'EARNINGS_FORECAST_CALC_PROVINCE', 'EARNINGS_FORECAST_CALC_PAY_FREQ',
  'EARNINGS_FORECAST_CALC_ANCHOR', 'EARNINGS_FORECAST_CALC_YTD_GROSS', 'EARNINGS_FORECAST_PAY_FREQ_WEEKLY',
  'EARNINGS_FORECAST_PAY_FREQ_BIWEEKLY', 'EARNINGS_FORECAST_PAY_FREQ_SEMIMONTHLY', 'EARNINGS_FORECAST_PAY_FREQ_MONTHLY',
  'EARNINGS_FORECAST_ASSUMP_WAGE', 'EARNINGS_FORECAST_ASSUMP_REG_HRS', 'EARNINGS_FORECAST_ASSUMP_OT_HRS',
  'EARNINGS_FORECAST_ASSUMP_LOA', 'EARNINGS_FORECAST_ASSUMP_TRAVEL', 'EARNINGS_FORECAST_ASSUMP_PROVINCE',
  'EARNINGS_FORECAST_ASSUMP_PAY_FREQ', 'EARNINGS_FORECAST_ASSUMP_ANCHOR', 'EARNINGS_FORECAST_ASSUMP_YTD_GROSS',
  'EARNINGS_FORECAST_PREVIEW_FAILED', 'EARNINGS_FORECAST_SUMMARY_UPDATED_FMT', 'EARNINGS_FORECAST_DISCLAIMER',
    'EARNINGS_FORECAST_NO_DATA', 'EARNINGS_FORECAST_LOAD_FAILED', 'EARNINGS_FORECAST_SETUP_NOTICE',
    'EARNINGS_FORECAST_PROGRESS_LABEL', 'EARNINGS_FORECAST_PROGRESS_CURRENT', 'EARNINGS_FORECAST_PROGRESS_FORECAST',
];
$earningsI18n = [];
foreach ($earningsI18nKeys as $earningsI18nKey) {
  $earningsI18n[$earningsI18nKey] = Strings::i18n($earningsI18nKey);
}
?>import PC from '<?php echo Render::jsModuleURL(); ?>';
import PW from '<?php echo Render::jsModuleURL('phantomwing'); ?>';
import nacl from '<?php echo Render::jsStaticURL('js/vendor/tweetnacl.js'); ?>';
import EarningsExport from '<?php echo Render::jsStaticURL('js/earnings/earnings-export.js'); ?>';
import { fromBase64 as decodeBase64 } from '<?php echo Render::jsStaticURL('js/core/binary-codec.js'); ?>';
import {
  buildPieGraphDataset,
  createPieGraphHelpers,
  getPieGraphPalette,
} from '<?php echo Render::jsStaticURL('js/earnings/pie-graph-core.js'); ?>';
import { createEarningsFormatHelpers } from '<?php echo Render::jsStaticURL('js/earnings/format.js'); ?>';
import { drawLineGraph } from '<?php echo Render::jsStaticURL('js/earnings/trend-chart.js'); ?>';
import { initForecastWorkspace } from '<?php echo Render::jsStaticURL('js/earnings/forecast-calculator.js'); ?>';
import { escapeHtml } from '<?php echo Render::jsStaticURL('js/core/escape.js'); ?>';
import {
  formatI18n as formatConfigI18n,
  getI18nLabel as getConfigI18nLabel,
} from '<?php echo Render::jsStaticURL('js/core/template.js'); ?>';

// === Canonical Verification Payload Utilities ===
// Fixed key order, no whitespace, locale-independent, v1

function buildCanonicalVerificationPayload({
  period, employeeId, jurisdiction, bracketVersion, engineVersion, grossCents, taxCents, netCents, signingKeyVersion = 1
}) {
  return {
    v: 1,
    scope: 'pay_period',
    period: {
      start: period.start,
      end: period.end,
      frequency: period.frequency,
    },
    employeeId,
    jurisdiction,
    bracketVersion,
    engineVersion,
    grossCents,
    taxCents,
    netCents,
    signingKeyVersion,
  };
}


function serializeCanonicalVerificationPayload(payload) {
  // Fixed key order, no whitespace, unescaped slashes/unicode
  const ordered = {
    v: payload.v,
    scope: payload.scope,
    period: {
      start: payload.period.start,
      end: payload.period.end,
      frequency: payload.period.frequency,
    },
    employeeId: payload.employeeId,
    jurisdiction: payload.jurisdiction,
    bracketVersion: payload.bracketVersion,
    engineVersion: payload.engineVersion,
    grossCents: payload.grossCents,
    taxCents: payload.taxCents,
    netCents: payload.netCents,
    signingKeyVersion: payload.signingKeyVersion,
  };
  return JSON.stringify(ordered);
}


// Canonical verification payload uses shared binary codec helpers.

// Pure JS SHA-256 (works in HTTP contexts without crypto.subtle)
function sha256(str) {
  function rightRotate(value, amount) {
    return (value >>> amount) | (value << (32 - amount));
  }
  
  const mathPow = Math.pow;
  const maxWord = mathPow(2, 32);
  const lengthProperty = 'length';
  let i, j;
  let result = '';
  
  const words = [];
  const asciiBitLength = str[lengthProperty] * 8;
  
  let hash = sha256.h = sha256.h || [];
  const k = sha256.k = sha256.k || [];
  let primeCounter = k[lengthProperty];
  
  const isComposite = {};
  for (let candidate = 2; primeCounter < 64; candidate++) {
    if (!isComposite[candidate]) {
      for (i = 0; i < 313; i += candidate) {
        isComposite[i] = candidate;
      }
      hash[primeCounter] = (mathPow(candidate, .5) * maxWord) | 0;
      k[primeCounter++] = (mathPow(candidate, 1 / 3) * maxWord) | 0;
    }
  }
  
  str += '\x80';
  while (str[lengthProperty] % 64 - 56) str += '\x00';
  for (i = 0; i < str[lengthProperty]; i++) {
    j = str.charCodeAt(i);
    if (j >> 8) return;
    words[i >> 2] |= j << ((3 - i) % 4) * 8;
  }
  words[words[lengthProperty]] = ((asciiBitLength / maxWord) | 0);
  words[words[lengthProperty]] = (asciiBitLength);
  
  for (j = 0; j < words[lengthProperty];) {
    const w = words.slice(j, j += 16);
    const oldHash = hash;
    hash = hash.slice(0, 8);
    
    for (i = 0; i < 64; i++) {
      const w15 = w[i - 15], w2 = w[i - 2];
      
      const a = hash[0], e = hash[4];
      const temp1 = hash[7]
        + (rightRotate(e, 6) ^ rightRotate(e, 11) ^ rightRotate(e, 25))
        + ((e & hash[5]) ^ ((~e) & hash[6]))
        + k[i]
        + (w[i] = (i < 16) ? w[i] : (
            w[i - 16]
            + (rightRotate(w15, 7) ^ rightRotate(w15, 18) ^ (w15 >>> 3))
            + w[i - 7]
            + (rightRotate(w2, 17) ^ rightRotate(w2, 19) ^ (w2 >>> 10))
          ) | 0
        );
      const temp2 = (rightRotate(a, 2) ^ rightRotate(a, 13) ^ rightRotate(a, 22))
        + ((a & hash[1]) ^ (a & hash[2]) ^ (hash[1] & hash[2]));
      
      hash = [(temp1 + temp2) | 0].concat(hash);
      hash[4] = (hash[4] + temp1) | 0;
    }
    
    for (i = 0; i < 8; i++) {
      hash[i] = (hash[i] + oldHash[i]) | 0;
    }
  }
  
  for (i = 0; i < 8; i++) {
    for (j = 3; j + 1; j--) {
      const b = (hash[i] >> (j * 8)) & 255;
      result += ((b < 16) ? 0 : '') + b.toString(16);
    }
  }
  return result;
}

// Ed25519 signature verification using tweetnacl, multi-key support
function verifySignature(serialized, signatureBase64, publicKeyBase64) {
  const message = new TextEncoder().encode(serialized);
  const signature = decodeBase64(signatureBase64);
  const publicKey = decodeBase64(publicKeyBase64);

  return nacl.sign.detached.verify(message, signature, publicKey);
}

// Map of public keys by version (injected server-side)
<?php
  use PayCal\Domain\Earnings;
  use PayCal\Domain\Security;

  // Keep this JS endpoint parse-safe even if key bootstrap fails.
  $userUUID = '';
  $publicKeys = [];
  $revokedKeys = [];

  $exportIdentity = [
    'fullName' => '',
    'email' => '',
    'phone' => '',
    'address' => '',
    'city' => '',
    'province' => '',
    'postal' => '',
    'clientIp' => Security::getClientIPAddress(),
  ];

  try {
    $currentUser = User::current();
    $userUUID = $currentUser->uuid();

    $exportIdentity = [
      'fullName'  => trim($currentUser->full_name ?? ''),
      'email'     => trim($currentUser->email ?? ''),
      'phone'     => trim($currentUser->phone ?? ''),
      'address'   => trim($currentUser->address_line1 ?? ''),
      'city'      => trim($currentUser->address_city ?? ''),
      'province'  => trim($currentUser->province ?? ''),
      'postal'    => trim($currentUser->address_postal ?? ''),
      'clientIp'  => Security::getClientIPAddress(),
    ];

    if ($userUUID !== '') {
      Earnings::ensureUserSigningKeys($userUUID, 1);
      $publicKeys = Earnings::getActivePublicKeys($userUUID);
      $revokedKeys = Earnings::getRevokedKeyVersions();
    }
  } catch (\Throwable $exception) {
    \PayCal\Observability\Lens::add('[EARNINGS JS] Signing key bootstrap failed', [
      'user_uuid' => $userUUID,
      'error' => $exception->getMessage(),
    ]);
    $publicKeys = [];
    $revokedKeys = [];
  }
?>
window.PAYROLL_SIGNING_PUBLIC_KEYS = <?php echo json_encode($publicKeys, JSON_UNESCAPED_SLASHES); ?>;
window.PAYROLL_SIGNING_REVOKED_KEYS = <?php echo json_encode($revokedKeys, JSON_UNESCAPED_SLASHES); ?>;
// Module-private: not exposed on window. Keeping the UUID out of the global
// object prevents injected scripts from scraping it by name, even when
// non-enumerable. Zeroed in clearEarningsTransientGlobals on page unload.
let _paycalUserUUID = '<?php echo $userUUID; ?>';
Object.defineProperty(window, 'PAYCAL_EXPORT_IDENTITY', { configurable: true, enumerable: false, writable: true, value: <?php echo json_encode($exportIdentity, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?> });

// Do not expose plaintext profile PII in page source.
// Profile data must be fetched/decrypted through authenticated runtime paths only.
// (PAYCAL_EXPORT_IDENTITY above is intentionally server-injected minimal identity
//  for export headers only; cleared on page unload alongside _paycalUserUUID.)
const paycalEncryptedProfileState = (() => {
  let encryptedProfile = {};

  return {
    get() {
      return encryptedProfile;
    },
    set(value) {
      encryptedProfile = value;
    },
    clear() {
      encryptedProfile = {};
    },
  };
})();

Object.defineProperty(window, 'PAYCAL_USER_PROFILE_ENCRYPTED', {
  configurable: true,
  enumerable: false,
  get() {
    return paycalEncryptedProfileState.get();
  },
  set(value) {
    paycalEncryptedProfileState.set(value);
  },
});

function clearEarningsTransientGlobals() {
  try {
    _paycalUserUUID = '';
    paycalEncryptedProfileState.clear();
    delete window.PAYCAL_USER_PROFILE_ENCRYPTED;
    delete window.PAYCAL_EXPORT_IDENTITY;
  } catch {
    // Ignore teardown failures during unload paths.
  }
}

window.addEventListener('pagehide', clearEarningsTransientGlobals);

document.addEventListener("DOMContentLoaded", () => {
  Object.assign(PC.config, <?php echo json_encode($earningsI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
  const earningsFormatHelpers = createEarningsFormatHelpers({ locale: PC.config.USER_LOCALE });

  const getI18nLabel = (key, fallback = '') => getConfigI18nLabel(PC?.config, key, fallback);
  const formatI18n = (key, fallback, params = {}) => formatConfigI18n(PC?.config, key, fallback, params);

  function buildDailyGridCell(content, colId, colKey, colLabel) {
    const cell = document.createElement('div');
    cell.className = 'datagrid_item';
    cell.setAttribute('role', 'gridcell');
    cell.setAttribute('aria-labelledby', colId);
    cell.dataset.colKey = String(colKey || '');
    cell.dataset.colLabel = String(colLabel || '');
    cell.textContent = String(content ?? '');
    return cell;
  }

  function buildDailyGridRow(year, row, fieldList, headers) {
    const rowElement = document.createElement('div');
    rowElement.className = 'datagrid_row';
    rowElement.setAttribute('role', 'row');
    rowElement.dataset.id = String(row.id || '');

    const rowContent = document.createElement('div');
    rowContent.className = 'datagrid_row_content';
    rowContent.setAttribute('role', 'presentation');

    fieldList.forEach((fieldName, fieldIndex) => {
      const colId = `earnings_daily_${year}_col_${fieldIndex + 1}`;
      rowContent.appendChild(buildDailyGridCell(row[fieldName], colId, fieldName, headers[fieldIndex] || fieldName));
    });

    rowElement.appendChild(rowContent);
    return rowElement;
  }

  function buildDailyGridElement(year, headers, rows, useLegacyPrivateColumns) {
    const gridRegion = document.createElement('div');
    gridRegion.className = `datagrid ${useLegacyPrivateColumns ? 'datagrid_cols_11' : 'datagrid_cols_4'} datagrid_layout_auto earnings_daily_datagrid`;
    gridRegion.dataset.grid = `earnings-daily-${year}`;
    gridRegion.dataset.page = '1';
    gridRegion.setAttribute('role', 'region');
    gridRegion.setAttribute('aria-label', `${getI18nLabel('EARNINGS_LABEL', 'Earnings')} ${year}`);
    gridRegion.setAttribute('aria-describedby', `daily_earnings_${year}_sr_instructions daily_earnings_${year}_sr_context daily_earnings_${year}_sr_status`);

    const gridTable = document.createElement('div');
    gridTable.className = 'datagrid_table';
    gridTable.setAttribute('role', 'grid');
    gridTable.setAttribute('aria-colcount', String(useLegacyPrivateColumns ? 11 : 4));
    gridTable.setAttribute('aria-rowcount', String(rows.length + 1));

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
      heading.id = `earnings_daily_${year}_col_${index + 1}`;
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
      empty.setAttribute('aria-live', 'polite');
      empty.textContent = getI18nLabel('NOT_FOUND', 'Not Found');
      bodyGroup.appendChild(empty);
    } else {
      const legacyFieldList = ['date', 'site', 'wage', 'hours', 'regular', 'overtime', 'loa', 'travel', 'gross', 'tax', 'net'];
      const compactFieldList = ['date', 'gross', 'deductions', 'net'];
      const fieldList = useLegacyPrivateColumns ? legacyFieldList : compactFieldList;

      const rowsFragment = document.createDocumentFragment();
      rows.forEach((row) => {
        rowsFragment.appendChild(buildDailyGridRow(year, row, fieldList, headers));
      });
      bodyGroup.appendChild(rowsFragment);
    }

    gridTable.appendChild(bodyGroup);
    gridRegion.appendChild(gridTable);

    return gridRegion;
  }

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

  function announceDailyGridStatus(year, rowCount, reason = 'loaded') {
    const statusNode = PC.getElement(`daily_earnings_${year}_sr_status`);
    if (!statusNode) {
      return;
    }

    const rowLabel = rowCount === 1
      ? getI18nLabel('EARNINGS_GRID_ROW_SINGULAR', 'row')
      : getI18nLabel('EARNINGS_GRID_ROW_PLURAL', 'rows');
    statusNode.textContent = formatI18n(
      'EARNINGS_GRID_STATUS_TEMPLATE',
      'Daily earnings grid {reason} for {year}. {rows} {rowLabel} available.',
      { reason, year, rows: rowCount, rowLabel }
    );
  }

  function announceEarningsGraphStatus(year, dates, values) {
    const statusNode = PC.getElement(`earnings_line_graph_${year}_status`);
    const descNode = PC.getElement(`earnings_line_graph_${year}_desc`);
    if (!statusNode || !descNode) {
      return;
    }

    if (!Array.isArray(dates) || !Array.isArray(values) || dates.length === 0 || values.length === 0) {
      statusNode.textContent = formatI18n(
        'EARNINGS_TREND_NO_DATA_STATUS',
        'Earnings trend chart for {year} loaded with no data points.',
        { year }
      );
      descNode.textContent = formatI18n(
        'EARNINGS_TREND_NO_DATA_DESC',
        'Line chart showing gross earnings trend across {year}. No earnings data points are available yet.',
        { year }
      );
      return;
    }

    const safeValues = values
      .map((value) => Number(value))
      .filter((value) => Number.isFinite(value));

    if (safeValues.length === 0) {
      statusNode.textContent = formatI18n(
        'EARNINGS_TREND_NO_NUMERIC_STATUS',
        'Earnings trend chart for {year} loaded with no numeric data points.',
        { year }
      );
      descNode.textContent = formatI18n(
        'EARNINGS_TREND_NO_NUMERIC_DESC',
        'Line chart showing gross earnings trend across {year}. Data points were present but contained invalid numeric values.',
        { year }
      );
      return;
    }

    const firstDate = formatDateKeyShort(dates[0], PC.config.USER_LOCALE);
    const lastDate = formatDateKeyShort(dates[dates.length - 1], PC.config.USER_LOCALE);
    const minValue = Math.min(...safeValues);
    const maxValue = Math.max(...safeValues);
    const deltaValue = safeValues[safeValues.length - 1] - safeValues[0];
    const direction = deltaValue > 0
      ? getI18nLabel('EARNINGS_TREND_DIRECTION_INCREASING', 'increasing')
      : (deltaValue < 0
        ? getI18nLabel('EARNINGS_TREND_DIRECTION_DECREASING', 'decreasing')
        : getI18nLabel('EARNINGS_TREND_DIRECTION_FLAT', 'flat'));

    statusNode.textContent = formatI18n(
      'EARNINGS_TREND_UPDATED_STATUS',
      'Earnings trend chart updated for {year}. {points} points from {firstDate} to {lastDate}.',
      { year, points: values.length, firstDate, lastDate }
    );
    descNode.textContent = formatI18n(
      'EARNINGS_TREND_UPDATED_DESC',
      'Line chart showing gross earnings trend across {year}. Data spans {firstDate} to {lastDate} with {points} points. Values range from {minValue} to {maxValue} and overall trend is {direction}.',
      {
        year,
        firstDate,
        lastDate,
        points: values.length,
        minValue: earningsFormatHelpers.formatCurrency(minValue),
        maxValue: earningsFormatHelpers.formatCurrency(maxValue),
        direction,
      }
    );
  }

  function announceEarningsGraphError(year, message = '') {
    const statusNode = PC.getElement(`earnings_line_graph_${year}_status`);
    if (!statusNode) {
      return;
    }

    const finalMessage = message || getI18nLabel('EARNINGS_CHART_DATA_LOAD_FAILED', 'Chart data could not be loaded.');
    statusNode.textContent = formatI18n(
      'EARNINGS_TREND_LOAD_FAILED_STATUS',
      'Earnings trend chart for {year} could not be loaded. {message}',
      { year, message: finalMessage }
    );
  }

  // === Canonical Verification: Trust-Layer Hash Check ===
  // Fetches canonical payloads/hashes from trust-layer API, reconstructs and verifies client-side

  async function verifyCanonicalHashesForYear(year) {
    try {
      const resp = await fetch(`/api/v1/verification/year/${year}`);
      if (!resp.ok) {
        const error = `[API] Failed to fetch verification data: ${resp.status} ${resp.statusText}`;
        PW.error(error);
        throw new Error(error);
      }
      const data = await resp.json();
      if (!data || data.status !== 'success') {
        const error = `[VERIFY] API error: ${data?.message || 'Unknown error'}`;
        PW.warn(error);
        return;
      }
      const periods = data.periods || [];
      if (!Array.isArray(periods)) {
        const error = `[VERIFY] Unexpected API response: expected periods array, got ${typeof periods}`;
        PW.warn(error);
        return;
      }

      const verifiablePeriods = periods.filter((period) => Boolean(
        period
        && period.canonicalPayload
        && period.verificationSignature
        && period.payloadHash
        && period.chainHash
      ));

      if (verifiablePeriods.length === 0) {
        PW.log(`[TRUST-LAYER] No verifiable signed periods available for year ${year}; skipping signature checks.`);
        return;
      }
      
      // Debug: Check what public keys are available
      if (Object.keys(window.PAYROLL_SIGNING_PUBLIC_KEYS || {}).length === 0) {
        PW.log(`[TRUST-LAYER] No public keys loaded from server for ${verifiablePeriods.length} verifiable period(s); skipping signature verification.`);
        return;
      } else {
        const keyInfo = Object.entries(window.PAYROLL_SIGNING_PUBLIC_KEYS).map(([k, v]) => `v${k}=${v ? `${v.substring(0, 10)}...` : '(empty)'}`).join(', ');
        PW.log(`[TRUST-LAYER] Available public keys: ${keyInfo}`);
      }
      let mismatches = 0;
      let chainBreaks = 0;
      let prevChainHash = '0'.repeat(64);
      for (const period of periods) {
        let telemetryFields = {
          userUUID: (_paycalUserUUID || ''),
          periodStart: '',
          periodEnd: '',
          engineVersion: '',
          signingKeyVersion: '',
          reason: ''
        };
        
        // Select correct public key by version
        let keyVersion = period.signingKeyVersion || 1;
        const publicKey = window.PAYROLL_SIGNING_PUBLIC_KEYS[keyVersion] || '';
        
        if (!publicKey) {
          telemetryFields.reason = 'missing_public_key';
          PW.report('verification', 'signature_failure', telemetryFields);
          PW.warn(`[TRUST-LAYER] Public key not available for version ${keyVersion}. Available versions: ${Object.keys(window.PAYROLL_SIGNING_PUBLIC_KEYS || {}).join(', ')}`);
          continue;
        }
        
        if (window.PAYROLL_SIGNING_REVOKED_KEYS.includes(keyVersion)) {
          telemetryFields.reason = 'revoked_key_version_used';
          PW.report('verification', 'revoked_key_used', telemetryFields);
          PW.error(`[TRUST-LAYER] Verification attempted with revoked key version ${keyVersion}`);
        }
        
        if (!period.canonicalPayload || !period.verificationSignature || !period.payloadHash || !period.chainHash) {
          PW.warn(`[VERIFY] Missing required period properties: canonicalPayload=${!!period.canonicalPayload}, signature=${!!period.verificationSignature}, payloadHash=${!!period.payloadHash}, chainHash=${!!period.chainHash}`);
          continue;
        }
        let payloadObj;
        try {
          payloadObj = JSON.parse(period.canonicalPayload);
          telemetryFields.periodStart = payloadObj.period?.start || '';
          telemetryFields.periodEnd = payloadObj.period?.end || '';
          telemetryFields.engineVersion = payloadObj.engineVersion || '';
          telemetryFields.signingKeyVersion = payloadObj.signingKeyVersion || '';
        } catch (e) {
          telemetryFields.reason = 'invalid_canonical_payload_json';
          PW.report('verification', 'signature_failure', telemetryFields);
          PW.warn(`[VERIFY] Invalid canonicalPayload JSON: ${e.message}`);
          continue;
        }
        // CRITICAL: Verify the canonicalPayload AS-IS (PHP already serialized it)
        // Do NOT re-serialize, as that may produce different output
        const valid = verifySignature(period.canonicalPayload, period.verificationSignature, publicKey);
        if (!valid) {
          mismatches++;
          telemetryFields.reason = 'signature_verification_failed';
          PW.report('verification', 'signature_failure', telemetryFields);
          PW.error(`[TRUST-LAYER] Signature verification failed for period ${payloadObj.period.start}–${payloadObj.period.end}`);
        }
        // Audit chain: recompute payloadHash and chainHash
        const payloadHashHex = sha256(period.canonicalPayload);
        if (payloadHashHex !== period.payloadHash) {
          chainBreaks++;
          telemetryFields.reason = 'payload_hash_mismatch';
          // Debug: log hash mismatch
          PW.warn(`[DEBUG] Hash mismatch. Server: ${period.payloadHash?.substring(0, 16)}..., Client: ${payloadHashHex.substring(0, 16)}...`);
          PW.report('audit_chain', 'break_detected', telemetryFields);
          PW.error(`[AUDIT-CHAIN] Payload hash mismatch for period ${payloadObj.period.start}–${payloadObj.period.end}`);
        }
        const concat = prevChainHash + payloadHashHex;
        const chainHashHex = sha256(concat);
        if (chainHashHex !== period.chainHash) {
          chainBreaks++;
          telemetryFields.reason = 'chain_hash_mismatch';
          PW.report('audit_chain', 'break_detected', telemetryFields);
          PW.error(`[AUDIT-CHAIN] Chain hash mismatch for period ${payloadObj.period.start}–${payloadObj.period.end}`);
        }
        prevChainHash = chainHashHex;
      }
      if (mismatches === 0 && chainBreaks === 0) {
        PW.log(`[TRUST-LAYER] All signatures and audit chain verified for year ${year}`);
      } else {
        if (mismatches > 0) {
          PW.warn(`[TRUST-LAYER] ${mismatches} signature verification failures detected for year ${year}`);
        }
        if (chainBreaks > 0) {
          PW.warn(`[AUDIT-CHAIN] ${chainBreaks} audit chain failures detected for year ${year}`);
        }
        if (window.showTrustLayerWarning) {
          showTrustLayerWarning('Payroll trust-layer verification failed: signature or audit chain verification failed. Please contact support.');
        }
      }
    } catch (err) {
      const errorMsg = `[TRUST-LAYER] Verification failed: ${err.message}`;
      PW.error(errorMsg);
    }
  }

  function draw_line_graph(data, svgID) {
    drawLineGraph(data, svgID, {
      getElement: (id) => PC.getElement(id),
      warn: (message) => PW.warn(message),
      userLocale: PC.config.USER_LOCALE,
      getI18nLabel,
      formatI18n,
      formatHelpers: earningsFormatHelpers,
      onStatus: announceEarningsGraphStatus,
      onError: announceEarningsGraphError,
    });
  }

  async function fetch_gross_year(year) {
    const endpoint = `gross/year/${year}`;
    const responseText = await PC.readResource(endpoint);
    const jsonResponse = JSON.parse(responseText);

    if (jsonResponse.status === "success") {
      const payload = (jsonResponse.data && typeof jsonResponse.data === 'object')
        ? jsonResponse.data
        : (() => {
            const { status, message, ...rest } = jsonResponse;
            return rest;
          })();

      const normalized = {};
      Object.entries(payload).forEach(([dateKey, amount]) => {
        if (!isIsoDateKey(dateKey)) {
          return;
        }

        const numericAmount = Number(amount);
        if (!Number.isFinite(numericAmount)) {
          return;
        }

        normalized[dateKey] = numericAmount;
      });

      return normalized;
    } else {
      const error = `[API] Earnings data retrieval failed: ${jsonResponse.message}`;
      PW.error(error);
      throw new Error(jsonResponse.message || "Failed to retrieve earnings data.");
    }
  }

  async function fetchDailyYearData(year) {
    const endpoint = `daily/year/${year}`;
    const responseText = await PC.readResource(endpoint, { timeoutMs: 30000 });
    const jsonResponse = JSON.parse(responseText);

    if (jsonResponse.status !== 'success') {
      throw new Error(jsonResponse.message || 'Failed to retrieve daily earnings data.');
    }

    return extractDailyPayload(jsonResponse);
  }

  function isIsoDateKey(value) {
    return /^\d{4}-\d{2}-\d{2}$/.test(String(value || ''));
  }

  function extractDailyPayload(jsonResponse) {
    if (!jsonResponse || typeof jsonResponse !== 'object') {
      return {};
    }

    const dataCandidate = (jsonResponse.data && typeof jsonResponse.data === 'object')
      ? jsonResponse.data
      : (() => {
          const { status, message, ...rest } = jsonResponse;
          return rest;
        })();

    const normalized = {};
    Object.entries(dataCandidate).forEach(([key, value]) => {
      if (!isIsoDateKey(key)) {
        return;
      }
      if (!value || typeof value !== 'object' || Array.isArray(value)) {
        return;
      }
      normalized[key] = value;
    });

    return normalized;
  }

  const pieHelpers = createPieGraphHelpers({
    locale: PC.config.USER_LOCALE,
    grossLabel: getI18nLabel('GROSS', 'Gross'),
    netLabel: getI18nLabel('NET', 'Net'),
    deductionsLabel: getI18nLabel('DEDUCTIONS', 'Deductions'),
    emptyLabel: getI18nLabel('EARNINGS_PIEGRAPHS_NO_VALUES', 'No values available.'),
    escapeHtml,
  });
  const { renderPieSvg, monthLabelFromKey } = pieHelpers;

  function renderPieGraphsForYear(year, dailyPayload) {
    const panel = PC.getElement(`earnings_piegraphs_panel_${year}`);
    if (!panel) {
      return;
    }

    const palette = getPieGraphPalette(panel);

    const ytdSvg = PC.getElement(`earnings_piegraphs_ytd_svg_${year}`);
    const ytdLegend = PC.getElement(`earnings_piegraphs_ytd_legend_${year}`);
    const monthSelect = PC.getElement(`earnings_piegraphs_month_select_${year}`);
    const monthSvg = PC.getElement(`earnings_piegraphs_month_svg_${year}`);
    const monthLegend = PC.getElement(`earnings_piegraphs_month_legend_${year}`);
    if (!ytdSvg || !ytdLegend || !monthSelect || !monthSvg || !monthLegend) {
      return;
    }

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

  async function fetchDailyRangeData(startDate, endDate) {
    if (!isIsoDateKey(startDate) || !isIsoDateKey(endDate)) {
      throw new Error('Invalid export date range.');
    }

    if (startDate > endDate) {
      throw new Error('Export date range start must be before end.');
    }

    const startYear = Number(startDate.slice(0, 4));
    const endYear = Number(endDate.slice(0, 4));
    if (!Number.isFinite(startYear) || !Number.isFinite(endYear) || endYear < startYear) {
      throw new Error('Invalid export year range.');
    }

    const merged = {};
    for (let year = startYear; year <= endYear; year += 1) {
      const payload = await fetchDailyYearData(year);
      Object.entries(payload).forEach(([dateKey, value]) => {
        if (dateKey >= startDate && dateKey <= endDate) {
          merged[dateKey] = value;
        }
      });
    }

    return merged;
  }

  function resolveExportIdentityProfile() {
    const identity = (window.PAYCAL_EXPORT_IDENTITY && typeof window.PAYCAL_EXPORT_IDENTITY === 'object')
      ? window.PAYCAL_EXPORT_IDENTITY
      : {};

    const s = (v) => (typeof v === 'string' ? v.trim() : '');

    return {
      fullName: s(identity.fullName),
      email:    s(identity.email),
      phone:    s(identity.phone),
      address:  s(identity.address),
      city:     s(identity.city),
      province: s(identity.province),
      postal:   s(identity.postal),
      clientIp: s(identity.clientIp),
    };
  }

  async function initializeExport(scope, format, year) {
    const response = await fetch('/api/v1/export/init', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({
        scope,
        format,
        year: Number(year),
      }),
    });
    if (!response.ok) {
      throw new Error(`Failed to initialize export: ${response.status}`);
    }
    const data = await response.json();
    return data?.data?.reference_code || '';
  }

  async function runScopedExport(scope, format, year, startDate = '', endDate = '', refCode = '') {
    const normalizedScope = (scope || 'yearly').toLowerCase();
    const exportYear = Number(year);
    let reportYear = exportYear;

    let dailyPayload = null;
    if (normalizedScope === 'payperiod') {
      dailyPayload = await fetchDailyRangeData(startDate, endDate);
      reportYear = Number(startDate.slice(0, 4));
    } else {
      if (!Number.isFinite(exportYear) || exportYear < 1900) {
        throw new Error('Invalid export year.');
      }
      dailyPayload = await fetchDailyYearData(exportYear);
      reportYear = exportYear;
    }

    const rows = EarningsExport.buildDetailedRows(dailyPayload);
    if (!rows.length) {
      throw new Error('No earnings records found for this export range.');
    }

    const employee = _paycalUserUUID || 'PayCal User';
    const identity = resolveExportIdentityProfile();
    const reportParams = {
      year: reportYear,
      employee,
      fullName: identity.fullName || '',
      // Note: fullName falls back to empty string; identity table will show blank rather than UUID
      referenceCode: refCode,
      email: identity.email,
      phone: identity.phone,
      ipAddress: identity.clientIp || 'unknown',
      address: identity.address,
      city: identity.city,
      province: identity.province,
      postal: identity.postal,
      rows,
    };

    let report = null;
    if (normalizedScope === 'yearly') {
      report = EarningsExport.buildYearlyReportJson(reportParams);
    } else if (normalizedScope === 'monthly') {
      report = EarningsExport.buildMonthlyReportJson(reportParams);
    } else if (normalizedScope === 'daily' || normalizedScope === 'payperiod') {
      report = EarningsExport.buildDailyReportJson(reportParams);
    } else {
      throw new Error(`Unsupported export scope: ${normalizedScope}`);
    }

    const fileSuffix = normalizedScope === 'payperiod'
      ? `${startDate}_to_${endDate}`
      : String(reportYear);

    if (format === 'csv') {
      let csv = '';
      if (normalizedScope === 'yearly') {
        csv = EarningsExport.generateYearlyCsv(rows, report);
      } else if (normalizedScope === 'monthly') {
        csv = EarningsExport.generateMonthlyCsv(rows, report);
      } else if (normalizedScope === 'daily' || normalizedScope === 'payperiod') {
        csv = EarningsExport.generateDailyCsv(rows, report);
      } else {
        throw new Error(`Unsupported export scope: ${normalizedScope}`);
      }
      EarningsExport.downloadTextFile(csv, `paycal-${normalizedScope}-${fileSuffix}.csv`, 'text/csv;charset=utf-8');
      return;
    }

    if (format === 'xlsx') {
      await EarningsExport.downloadXlsxFile(
        normalizedScope,
        rows,
        report,
        `paycal-${normalizedScope}-${fileSuffix}.xlsx`,
        startDate,
        endDate,
      );
      return;
    }

    if (format === 'txt') {
      let txt = '';
      if (normalizedScope === 'yearly') {
        txt = EarningsExport.generateYearlyTxt(rows, report);
      } else if (normalizedScope === 'monthly') {
        txt = EarningsExport.generateMonthlyTxt(rows, report);
      } else if (normalizedScope === 'daily' || normalizedScope === 'payperiod') {
        txt = EarningsExport.generateDailyTxt(rows, report);
      } else {
        throw new Error(`Unsupported export scope: ${normalizedScope}`);
      }
      EarningsExport.downloadTextFile(txt, `paycal-${normalizedScope}-${fileSuffix}.txt`, 'text/plain;charset=utf-8');
      return;
    }

    if (format === 'pdf') {
      const printMode = ['bw', 'grayscale', 'color'].includes(String(document.documentElement.dataset.printMode || '').toLowerCase())
        ? String(document.documentElement.dataset.printMode).toLowerCase()
        : 'color';
      await EarningsExport.downloadPdfServerSide(
        normalizedScope,
        rows,
        report,
        `paycal-${normalizedScope}-${fileSuffix}.pdf`,
        startDate,
        endDate,
        printMode,
      );
      return;
    }

    throw new Error(`Unsupported export format: ${format}`);
  }

  function bindYearlyExportButtons() {
    // Use event delegation so dynamically-injected buttons (e.g. pay-period cards
    // loaded via loadSection/Guardian.setHTML) are covered without re-binding.
    document.addEventListener('click', async (event) => {
      const button = event.target.closest('[data-export-scope][data-export-format]');
      if (!button) {
        return;
      }

      event.preventDefault();
      event.stopPropagation();

      const scope = (button.dataset.exportScope || 'yearly').toLowerCase();
      const format = (button.dataset.exportFormat || '').toLowerCase();
      const year = button.dataset.exportYear || '';
      const startDate = button.dataset.exportStart || '';
      const endDate = button.dataset.exportEnd || '';
      const originalText = button.textContent;

      try {
        button.disabled = true;
        button.textContent = '...';
        const refCode = await initializeExport(scope, format, year || new Date().getFullYear());
        await runScopedExport(scope, format, year, startDate, endDate, refCode);
      } catch (error) {
        const message = PC.resolveThrownMessage(error, getI18nLabel('EARNINGS_UNKNOWN_ERROR', 'Unknown error.'));
        PW.error(`[EXPORT] ${scope.toUpperCase()} ${format.toUpperCase()} ${year} failed: ${error.message}`);
        PC.showToast(`${getI18nLabel('EARNINGS_EXPORT_FAILED_PREFIX', 'Export failed:')} ${message}`);
      } finally {
        button.disabled = false;
        button.textContent = originalText;
      }
    });
  }

  
async function render_daily_year(year) {
  const dailyEarningsSection = PC.getElement(`daily_earnings_${year}`);

  if (!dailyEarningsSection) {
    PW.warn(`[DAILY] Section not found for year: ${year}`);
    return;
  }

  dailyEarningsSection.textContent = '';
  let dailyData;

  try {
    const endpoint = `daily/year/${year}`;
    const responseText = await PC.readResource(endpoint, { timeoutMs: 30000 });
    const jsonResponse = JSON.parse(responseText);

    if (jsonResponse.status === "success") {
      dailyData = extractDailyPayload(jsonResponse);
    } else {
      PC.showToast(`${getI18nLabel('EARNINGS_DAILY_LOAD_FAILED_PREFIX', 'Error: Could not load daily earnings data.')} ${jsonResponse.message || getI18nLabel('EARNINGS_UNKNOWN_ERROR', 'Unknown error.')}`);

      return;
    }
  } catch (error) {
    const message = PC.resolveThrownMessage(error, getI18nLabel('EARNINGS_UNKNOWN_ERROR', 'Unknown error.'));
    PC.showToast(`${getI18nLabel('EARNINGS_DAILY_LOAD_FAILED_PREFIX', 'Error: Could not load daily earnings data.')} ${message}`);
    return;
  }

  // Check if dailyData is valid
  if (!dailyData || typeof dailyData !== 'object') {
    PC.showToast(formatI18n('EARNINGS_DAILY_NO_DATA_FOR_YEAR', 'Error: No daily earnings data available for {year}.', { year }));
    return;
  }

  const useLegacyPrivateColumns = Object.values(dailyData).some((record) => {
    if (!record || typeof record !== 'object') {
      return false;
    }

    return Boolean(
      record.site_name
      || record.wage
      || record.hours
      || record.regular_hours
      || record.overtime_hours
      || record.travel_hours
      || record.living_out_allowance
      || record.tax
    );
  });

  const headers = useLegacyPrivateColumns
    ? [
      getI18nLabel('DATE', 'Date'),
      getI18nLabel('SITE', 'Site'),
      getI18nLabel('WAGE', 'Wage'),
      getI18nLabel('HOURS', 'Hours'),
      getI18nLabel('REGULAR_HOURS', 'Regular'),
      getI18nLabel('OVERTIME_HOURS', 'OT'),
      getI18nLabel('LOA', 'LOA'),
      getI18nLabel('TRAVEL', 'Travel'),
      getI18nLabel('EARNINGS_LABEL', 'Gross'),
      getI18nLabel('DEDUCTIONS', 'Tax'),
      getI18nLabel('NET', 'Net')
    ]
    : [
      getI18nLabel('DATE', 'Date'),
      getI18nLabel('EARNINGS_LABEL', 'Gross'),
      getI18nLabel('DEDUCTIONS', 'Deductions'),
      getI18nLabel('NET', 'Net')
    ];

  const formatMoneyCell = (value) => earningsFormatHelpers.formatAmount(value, 2, 2);
  const rows = Object.entries(dailyData)
    .sort(([d1], [d2]) => parseDateKeyToLocalMs(d1) - parseDateKeyToLocalMs(d2))
    .map(([date, record], index) => ({
      id: `daily-${year}-${index}`,
      date: formatDateKeyForDisplay(date, PC.config.USER_LOCALE),
      site: (record.site_name || '').toString(),
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

  const gridElement = buildDailyGridElement(year, headers, rows, useLegacyPrivateColumns);
  const fragment = document.createDocumentFragment();
  fragment.appendChild(gridElement);
  dailyEarningsSection.appendChild(fragment);
  announceDailyGridStatus(year, rows.length);
  renderPieGraphsForYear(year, dailyData);

}

  // Earnings tab navigation bar
  const tabs        = PC.queryAll('[data-tab-target]');
  const tabContents = PC.queryAll('[data-tab-content]');
  const earningsRoot = PC.query('[data-earnings-mode]');
  const earningsMode = (earningsRoot && earningsRoot.dataset && earningsRoot.dataset.earningsMode)
    ? String(earningsRoot.dataset.earningsMode).toLowerCase()
    : 'lazy';
  const lazyMode = earningsMode !== 'eager';
  const graphDataCache = {}; // Cache graph data for re-rendering
  const loadedSections = new Set();
  const loadedGraphs = new Set();
  const loadedDaily = new Set();
  let eagerLoadToken = 0;

  async function fetchSectionHtml(section, year) {
    let endpoint = `${section}/year/${year}`;
    if (section === 'ytd') {
      const searchParams = new URLSearchParams(window.location.search || '');
      const extCompare = String(searchParams.get('ext_compare') || '').trim().toLowerCase();
      const extMode = String(searchParams.get('ext_mode') || '').trim().toLowerCase();
      const allowedModes = ['auto', 'basic', 'override'];
      const passthrough = new URLSearchParams();

      if (extCompare === 'earnings-ytd') {
        passthrough.set('ext_compare', 'earnings-ytd');
      }

      if (allowedModes.includes(extMode)) {
        passthrough.set('ext_mode', extMode);
      } else {
        // Default YTD to private override renderer unless explicitly requested otherwise.
        passthrough.set('ext_mode', 'override');
      }

      const queryString = passthrough.toString();
      if (queryString !== '') {
        endpoint += `?${queryString}`;
      }
    }

    const responseText = await PC.readResource(endpoint);
    const jsonResponse = JSON.parse(responseText);

    if (jsonResponse.status !== 'success') {
      throw new Error(
        jsonResponse.message
        || formatI18n('EARNINGS_FAILED_TO_LOAD_SECTION', 'Failed to load {section} section.', { section })
      );
    }

    const payload = (jsonResponse.data && typeof jsonResponse.data === 'object')
      ? jsonResponse.data
      : (() => {
          const { status, message, ...rest } = jsonResponse;
          return rest;
        })();

    return typeof payload.html === 'string' ? payload.html : '';
  }

  async function loadSection(section, year, targetId) {
    const key = `${section}:${year}`;
    if (loadedSections.has(key)) {
      return;
    }

    const target = PC.getElement(targetId);
    if (!target) {
      return;
    }

    try {
      const html = await fetchSectionHtml(section, year);
      window.Guardian.setHTML(
        target,
        html || `<p class="earnings_async_status">${escapeHtml(getI18nLabel('EARNINGS_ASYNC_NO_DATA', 'No data available.'))}</p>`
      );
      loadedSections.add(key);
    } catch (error) {
      const message = PC.resolveThrownMessage(error, getI18nLabel('EARNINGS_UNKNOWN_ERROR', 'unknown error'));
      window.Guardian.setHTML(
        target,
        `<p class="earnings_async_status">${escapeHtml(formatI18n('EARNINGS_ASYNC_SECTION_LOAD_FAILED', 'Unable to load section: {message}.', { message }))}</p>`
      );
      PW.error(`[EARNINGS] ${section} year ${year} failed: ${error.message}`);
    }
  }

  function loadSectionsForYear(year) {
    loadSection('ytd', year, `earnings_ytd_${year}`);
    loadSection('payperiods', year, `earnings_pay_periods_${year}`);
    loadSection('monthly', year, `earnings_monthly_${year}`);
  }

  function scheduleIdleTask(task, timeoutMs = 3000) {
    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(task, { timeout: timeoutMs });
      return;
    }

    window.setTimeout(task, 250);
  }

  function loadPriorityYearContent(year) {
    loadSection('ytd', year, `earnings_ytd_${year}`);
    loadGraphForYear(year);
  }

  function loadSecondaryYearSections(year) {
    loadSection('payperiods', year, `earnings_pay_periods_${year}`);
    loadSection('monthly', year, `earnings_monthly_${year}`);
  }

  function scheduleDailyForYear(year) {
    if (loadedDaily.has(year)) {
      return;
    }

    const section = document.getElementById(`daily_earnings_${year}`);
    if (!section) {
      return;
    }

    let started = false;
    const startLoad = () => {
      if (started || loadedDaily.has(year)) {
        return;
      }
      started = true;
      loadDailyForYear(year);
    };

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        if (!entries.some((entry) => entry.isIntersecting)) {
          return;
        }
        observer.disconnect();
        scheduleIdleTask(startLoad, 4000);
      }, { rootMargin: '240px 0px' });
      observer.observe(section);
      scheduleIdleTask(() => {
        observer.disconnect();
        startLoad();
      }, 8000);
      return;
    }

    scheduleIdleTask(startLoad, 4000);
  }

  function loadYearContentProgressive(year, { includeDaily = true, secondaryDelayMs = 1800 } = {}) {
    loadPriorityYearContent(year);
    scheduleIdleTask(() => {
      loadSecondaryYearSections(year);
    }, secondaryDelayMs);

    if (includeDaily) {
      scheduleDailyForYear(year);
    }
  }

  function scheduleTrustLayerVerification(year) {
    const run = () => {
      void verifyCanonicalHashesForYear(year);
    };

    if ('requestIdleCallback' in window) {
      window.requestIdleCallback(run, { timeout: 5000 });
      return;
    }

    window.setTimeout(run, 250);
  }

  function loadGraphForYear(year) {
    if (loadedGraphs.has(year)) {
      const svgId = `earnings_line_graph_${year}`;
      const svg = PC.getElement(svgId);
      if (svg && graphDataCache[year] && svg.children.length === 0 && svg.parentElement && svg.parentElement.clientWidth > 0) {
        draw_line_graph(graphDataCache[year], svgId);
      }
      return;
    }

    loadedGraphs.add(year);
    fetch_gross_year(year)
      .then(data => {
        const svgId = `earnings_line_graph_${year}`;
        graphDataCache[year] = data;
        draw_line_graph(data, svgId);
        scheduleTrustLayerVerification(year);
      })
      .catch(error => {
        const message = PC.resolveThrownMessage(error, getI18nLabel('EARNINGS_UNABLE_TO_RETRIEVE_TREND', 'Unable to retrieve earnings trend data.'));
        PW.error(`[INIT] Error drawing earnings graph for ${year}: ${error.message}`);
        announceEarningsGraphError(
          year,
          message
        );
      });
  }

  function loadDailyForYear(year) {
    if (loadedDaily.has(year)) {
      return;
    }
    loadedDaily.add(year);
    render_daily_year(year);
  }

  let forecastWorkspaceCleanup = null;
  const initForecastTab = () => {
    const workspace = PC.getElement('forecast_workspace');
    if (!(workspace instanceof HTMLElement) || workspace.dataset.forecastInitialized === '1') {
      return;
    }
    if (forecastWorkspaceCleanup) {
      forecastWorkspaceCleanup();
    }
    workspace.dataset.forecastInitialized = '1';
    const loadingEl = workspace.querySelector('.forecast-workspace__loading');
    if (loadingEl instanceof HTMLElement) {
      loadingEl.classList.add('visually_hidden');
    }
    forecastWorkspaceCleanup = initForecastWorkspace(workspace, {
      config: PC.config,
      previewUrl: `${PC.config.pc_api}forecast/preview`,
      locale: PC.config.USER_LOCALE,
    });
  };

  const activateEarningsTab = (tab) => {
    const target = PC.query("#" + tab.dataset.tabTarget);
    if (!target) {
      return;
    }

    tabContents.forEach(tabContent => {
      tabContent.classList.remove("active");
    });

    tabs.forEach(t => {
      t.classList.remove("active");
      t.setAttribute('aria-selected', 'false');
      t.setAttribute('tabindex', '-1');
    });

    tab.classList.add("active");
    tab.setAttribute('aria-selected', 'true');
    tab.setAttribute('tabindex', '0');
    target.classList.add("active");

    if (tab.dataset.tabTarget === 'tab-forecast') {
      initForecastTab();
      return;
    }

    // Extract year from tab target (tab-2026 -> 2026)
    const year = parseInt(tab.dataset.tabTarget.replace('tab-', ''), 10);
    if (!isNaN(year)) {
      if (lazyMode) {
        loadYearContentProgressive(year, { secondaryDelayMs: 600 });
      } else {
        eagerLoadToken += 1;
        loadSectionsForYear(year);
        loadGraphForYear(year);
        loadDailyForYear(year);
      }
    }
  };

  tabs.forEach((tab, index) => {
    tab.addEventListener("click", () => {
      activateEarningsTab(tab);
    });

    tab.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        activateEarningsTab(tab);
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
        if (nextTab) {
          activateEarningsTab(nextTab);
          nextTab.focus();
        }
      }
    });
  });

  
function initializeEarningsGraphs() {
  if (!lazyMode) {
    const yearTabs = PC.queryAll('[data-tab-target^="tab-"]');
    const years = Array.from(yearTabs).map(tab => {
      const target = tab.dataset.tabTarget;
      return parseInt(target.replace('tab-', ''), 10);
    }).filter(y => !isNaN(y));

    const currentToken = ++eagerLoadToken;
    const activeTab = PC.query('[data-tab-target].active') || PC.query('[data-tab-target^="tab-"]');
    const activeYear = activeTab ? parseInt(activeTab.dataset.tabTarget.replace('tab-', ''), 10) : NaN;
    const sortedYears = years.slice().sort((a, b) => b - a);
    const prioritizedYears = Number.isFinite(activeYear)
      ? [activeYear, ...sortedYears.filter((year) => year !== activeYear)]
      : sortedYears;

    const runEagerQueue = (index = 0) => {
      if (currentToken !== eagerLoadToken || index >= prioritizedYears.length) {
        return;
      }

      const queueYear = prioritizedYears[index];
      loadPriorityYearContent(queueYear);
      if (index === 0) {
        scheduleIdleTask(() => {
          loadSecondaryYearSections(queueYear);
        }, 900);
        scheduleDailyForYear(queueYear);
      } else {
        scheduleIdleTask(() => {
          loadSecondaryYearSections(queueYear);
        }, 1800);
      }

      const jitterMs = 120 + Math.floor(Math.random() * 140);
      window.setTimeout(() => {
        runEagerQueue(index + 1);
      }, jitterMs);
    };

    runEagerQueue();

    return;
  }

  const activeTab = PC.query('[data-tab-target].active') || PC.query('[data-tab-target^="tab-"]');
  if (!activeTab) {
    return;
  }

  if (activeTab.dataset.tabTarget === 'tab-forecast') {
    initForecastTab();
    return;
  }

  const year = parseInt(activeTab.dataset.tabTarget.replace('tab-', ''), 10);
  if (isNaN(year)) {
    return;
  }

  loadYearContentProgressive(year);
}

  // Initialize graphs on page load
  initializeEarningsGraphs();
  bindYearlyExportButtons();

});
