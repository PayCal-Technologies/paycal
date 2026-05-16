<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

Javascript::renderDocBlock();

$user = User::current();
?>import PC from '/js/';
import PW from '/js/phantomwing/';
import nacl from '/js/vendor/tweetnacl.js';
import EarningsExport from '/js/earnings/earnings-export.js';

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


// use our own decodeBase64
const decodeBase64 = (b64) =>
  Uint8Array.from(atob(b64), c => c.charCodeAt(0));

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
// Non-enumerable: hidden from window property enumeration by extensions and injected scripts.
Object.defineProperty(window, 'PAYCAL_USER_UUID', { configurable: true, enumerable: false, writable: true, value: '<?php echo $userUUID; ?>' });
Object.defineProperty(window, 'PAYCAL_EXPORT_IDENTITY', { configurable: true, enumerable: false, writable: true, value: <?php echo json_encode($exportIdentity, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?> });

// Do not expose plaintext profile PII in page source.
// Profile data must be fetched/decrypted through authenticated runtime paths only.
// (PAYCAL_EXPORT_IDENTITY above is intentionally server-injected minimal identity
//  for export headers only; cleared on page unload alongside PAYCAL_USER_UUID.)
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
    paycalEncryptedProfileState.clear();
    delete window.PAYCAL_USER_PROFILE_ENCRYPTED;
    delete window.PAYCAL_USER_UUID;
    delete window.PAYCAL_EXPORT_IDENTITY;
  } catch {
    // Ignore teardown failures during unload paths.
  }
}

window.addEventListener('pagehide', clearEarningsTransientGlobals);
window.addEventListener('beforeunload', clearEarningsTransientGlobals);

document.addEventListener("DOMContentLoaded", () => {

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function getI18nLabel(key, fallback = '') {
    const value = String(PC?.config?.[key] ?? '').trim();
    return value !== '' ? value : fallback;
  }

  function initDelayedHoverHelp() {
    const targets = Array.from(document.querySelectorAll('[data-hover-help]'));
    if (targets.length === 0) {
      return;
    }

    const tooltipEl = document.createElement('div');
    tooltipEl.className = 'hover_help_tooltip';
    tooltipEl.setAttribute('role', 'tooltip');
    tooltipEl.setAttribute('aria-hidden', 'true');
    document.body.appendChild(tooltipEl);

    let showTimer = null;
    let activeTarget = null;

    const clearShowTimer = () => {
      if (showTimer !== null) {
        window.clearTimeout(showTimer);
        showTimer = null;
      }
    };

    const hideTooltip = () => {
      clearShowTimer();
      activeTarget = null;
      tooltipEl.classList.remove('is-visible');
      tooltipEl.setAttribute('aria-hidden', 'true');
    };

    const positionTooltip = (targetEl) => {
      if (!targetEl) {
        return;
      }

      tooltipEl.style.bottom = '1.5rem';
      tooltipEl.style.right = '1.5rem';
      tooltipEl.style.top = 'auto';
      tooltipEl.style.left = 'auto';
    };

    const showTooltip = (targetEl) => {
      const helpText = (targetEl?.getAttribute('data-hover-help') || '').trim();
      if (!helpText) {
        return;
      }

      activeTarget = targetEl;
      tooltipEl.textContent = helpText;
      tooltipEl.classList.add('is-visible');
      tooltipEl.setAttribute('aria-hidden', 'false');
      positionTooltip(targetEl);
    };

    const scheduleShow = (targetEl) => {
      clearShowTimer();
      showTimer = window.setTimeout(() => {
        showTimer = null;
        showTooltip(targetEl);
      }, 250);
    };

    targets.forEach((targetEl) => {
      targetEl.addEventListener('mouseenter', () => scheduleShow(targetEl));
      targetEl.addEventListener('mouseleave', hideTooltip);
      targetEl.addEventListener('focus', () => scheduleShow(targetEl));
      targetEl.addEventListener('blur', hideTooltip);
      targetEl.addEventListener('mousedown', hideTooltip);
    });

    window.addEventListener('scroll', () => {
      if (activeTarget && tooltipEl.classList.contains('is-visible')) {
        positionTooltip(activeTarget);
      }
    }, true);

    window.addEventListener('resize', () => {
      if (activeTarget && tooltipEl.classList.contains('is-visible')) {
        positionTooltip(activeTarget);
      }
    });
  }

  initDelayedHoverHelp();

  function buildDailyGridCell(content, colId) {
    const cell = document.createElement('div');
    cell.className = 'datagrid_item';
    cell.setAttribute('role', 'gridcell');
    cell.setAttribute('aria-labelledby', colId);
    cell.textContent = String(content ?? '');
    return cell;
  }

  function buildDailyGridRow(year, row, fieldList) {
    const rowElement = document.createElement('div');
    rowElement.className = 'datagrid_row';
    rowElement.setAttribute('role', 'row');
    rowElement.dataset.id = String(row.id || '');

    const rowContent = document.createElement('div');
    rowContent.className = 'datagrid_row_content';
    rowContent.setAttribute('role', 'presentation');

    fieldList.forEach((fieldName, fieldIndex) => {
      const colId = `earnings_daily_${year}_col_${fieldIndex + 1}`;
      rowContent.appendChild(buildDailyGridCell(row[fieldName], colId));
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
        rowsFragment.appendChild(buildDailyGridRow(year, row, fieldList));
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

    statusNode.textContent = `Daily earnings grid ${reason} for ${year}. ${rowCount} row${rowCount === 1 ? '' : 's'} available.`;
  }

  function announceEarningsGraphStatus(year, dates, values) {
    const statusNode = PC.getElement(`earnings_line_graph_${year}_status`);
    const descNode = PC.getElement(`earnings_line_graph_${year}_desc`);
    if (!statusNode || !descNode) {
      return;
    }

    if (!Array.isArray(dates) || !Array.isArray(values) || dates.length === 0 || values.length === 0) {
      statusNode.textContent = `Earnings trend chart for ${year} loaded with no data points.`;
      descNode.textContent = `Line chart showing gross earnings trend across ${year}. No earnings data points are available yet.`;
      return;
    }

    const safeValues = values
      .map((value) => Number(value))
      .filter((value) => Number.isFinite(value));

    if (safeValues.length === 0) {
      statusNode.textContent = `Earnings trend chart for ${year} loaded with no numeric data points.`;
      descNode.textContent = `Line chart showing gross earnings trend across ${year}. Data points were present but contained invalid numeric values.`;
      return;
    }

    const firstDate = formatDateKeyShort(dates[0], PC.config.USER_LOCALE);
    const lastDate = formatDateKeyShort(dates[dates.length - 1], PC.config.USER_LOCALE);
    const minValue = Math.min(...safeValues);
    const maxValue = Math.max(...safeValues);
    const deltaValue = safeValues[safeValues.length - 1] - safeValues[0];
    const direction = deltaValue > 0 ? 'increasing' : (deltaValue < 0 ? 'decreasing' : 'flat');

    statusNode.textContent = `Earnings trend chart updated for ${year}. ${values.length} point${values.length === 1 ? '' : 's'} from ${firstDate} to ${lastDate}.`;
    descNode.textContent = `Line chart showing gross earnings trend across ${year}. Data spans ${firstDate} to ${lastDate} with ${values.length} points. Values range from $${minValue.toFixed(2)} to $${maxValue.toFixed(2)} and overall trend is ${direction}.`;
  }

  function announceEarningsGraphError(year, message = 'Chart data could not be loaded.') {
    const statusNode = PC.getElement(`earnings_line_graph_${year}_status`);
    if (!statusNode) {
      return;
    }

    statusNode.textContent = `Earnings trend chart for ${year} could not be loaded. ${message}`;
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
          userUUID: (window.PAYCAL_USER_UUID || ''),
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
    const SVG_NS = "http://www.w3.org/2000/svg";
    const linegraphSVG = PC.getElement(svgID);
    
    if (!linegraphSVG) {
      PW.warn(`[GRAPH] SVG element not found: ${svgID}`);
      return;
    }
    if (!data || typeof data !== 'object') {
      PW.warn(`[GRAPH] Invalid data: received type ${typeof data}, expected object`);
      return;
    }

    // --- prep & sort ---
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
      PW.warn(`[GRAPH] Filtered ${invalidPoints} invalid earnings point(s) for ${svgID}`);
    }

    const pairs = rawPairs
      .filter((entry) => entry.valid)
      .sort((a, b) => a.dateMs - b.dateMs);

    const yearFromId = Number((svgID.match(/(\d{4})$/) || [])[1]);
    if (!pairs.length) {
      if (Number.isFinite(yearFromId)) {
        announceEarningsGraphStatus(yearFromId, [], []);
      }
      return;
    }

    const dateKeys = pairs.map((entry) => entry.dateKey);
    const datesMs = pairs.map((entry) => entry.dateMs);
    const amounts = pairs.map((entry) => entry.amount);

    if (datesMs.length === 0 || amounts.length === 0) {
      if (Number.isFinite(yearFromId)) {
        announceEarningsGraphStatus(yearFromId, [], []);
      }
      return;
    }

    // Extract year directly from string to avoid timezone issues
    const derivedYear = parseInt(String(dateKeys[0]).split('-')[0], 10);
    const year = Number.isFinite(derivedYear) ? derivedYear : yearFromId;

    if (!Number.isFinite(year)) {
      PW.warn(`[GRAPH] Could not derive chart year from ${svgID}`);
      return;
    }

    announceEarningsGraphStatus(year, dateKeys, amounts);

    // X domain: full year
    const xMin = new Date(year, 0, 1).getTime();
    const xMax = new Date(year, 11, 31, 23, 59, 59, 999).getTime();

    // Y domain: min..rounded max (nearest 10)
    const yMin = Math.min(0, ...amounts);
    const yMaxRaw = Math.max(...amounts);

    if (!Number.isFinite(yMin) || !Number.isFinite(yMaxRaw)) {
      PW.warn(`[GRAPH] Invalid Y-axis domain for ${svgID}`);
      announceEarningsGraphError(year, 'Chart data contained invalid numeric values.');
      return;
    }

    const yMax = Math.ceil(yMaxRaw / 10) * 10;

    // --- size & margins (left auto based on label width) ---
    const parentWidth = linegraphSVG.parentElement?.clientWidth;
    const width = Number.isFinite(parentWidth) ? Number(parentWidth) : 0;
    const height = 200;
    
    if (width <= 0) {
      return;
    }
    
    let margin = { top: 10, right: 16, bottom: 32, left: 40 };

    // probe widest Y label text (e.g., "12345 (100%)")
    linegraphSVG.setAttribute("width", width);
    linegraphSVG.setAttribute("height", height);
    linegraphSVG.textContent = '';

    // Get theme primary color for graph elements
    const primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim() || '#3a86ff';
    const graphLineColor = primaryColor;
    const graphStrokeStrong = `${primaryColor}cc`;
    const graphStrokeNormal = `${primaryColor}66`;
    const graphStrokeLight = `${primaryColor}26`;
    const graphStrokeVeryLight = `${primaryColor}03`;
    // Use light gray tooltip that works on both light and dark themes
    const tooltipBg = 'rgba(255, 255, 255, 0.98)';

    const probe = document.createElementNS(SVG_NS, "text");
    probe.setAttribute("font-size", "13");
    probe.textContent = `$\${yMax} (100%)`;
    linegraphSVG.appendChild(probe);
    let labelWidth = 0;
    try {
      labelWidth = Math.ceil(probe.getBBox().width);
    } catch (error) {
      PW.warn(`[GRAPH] Unable to measure Y-axis label width for ${svgID}`);
    }
    linegraphSVG.removeChild(probe);
    margin.left = Math.max(margin.left, labelWidth + 12);

    const innerW = Math.max(0, width - margin.left - margin.right);
    const innerH = Math.max(0, height - margin.top - margin.bottom);

    if (innerW <= 0 || innerH <= 0) {
      PW.warn(`[GRAPH] Invalid chart inner dimensions for ${svgID}`);
      return;
    }

    // --- scales ---
    const xSpan = xMax - xMin;
    if (!Number.isFinite(xSpan) || xSpan <= 0) {
      PW.warn(`[GRAPH] Invalid X-axis domain for ${svgID}`);
      return;
    }

    const xScale = d => ((d - xMin) / xSpan) * innerW + margin.left;

    const yScale = (yMax === yMin)
      ? () => margin.top + innerH / 2
      : v => (margin.top + innerH) - ((v - yMin) / (yMax - yMin)) * innerH;

    // --- path (area fill under curve) ---
    let dPath = `M ${xScale(datesMs[0])},${yScale(amounts[0])}`;
    for (let i = 1; i < datesMs.length; i++) {
      const x1 = xScale(datesMs[i - 1]), y1 = yScale(amounts[i - 1]);
      const x2 = xScale(datesMs[i]),     y2 = yScale(amounts[i]);
      const xc = (x1 + x2) / 2;
      dPath += ` C ${xc},${y1} ${xc},${y2} ${x2},${y2}`;
    }
    // close area to baseline
    dPath += ` L ${xScale(datesMs[datesMs.length - 1])},${margin.top + innerH}`;
    dPath += ` L ${xScale(datesMs[0])},${margin.top + innerH} Z`;

    // --- gradient defs ---
    const defs = document.createElementNS(SVG_NS, "defs");
    const grad = document.createElementNS(SVG_NS, "linearGradient");
    const gradientId = `verticalGradient_${svgID}`;
    grad.setAttribute("id", gradientId);
    grad.setAttribute("x1", "0%"); grad.setAttribute("y1", "0%");
    grad.setAttribute("x2", "0%"); grad.setAttribute("y2", "100%");
    const stop1 = document.createElementNS(SVG_NS, "stop");
    stop1.setAttribute("offset", "0%");
    stop1.setAttribute("stop-color", graphStrokeLight);
    const stop2 = document.createElementNS(SVG_NS, "stop");
    stop2.setAttribute("offset", "100%");
    stop2.setAttribute("stop-color", graphStrokeVeryLight);
    grad.appendChild(stop1); grad.appendChild(stop2);
    defs.appendChild(grad);
    linegraphSVG.appendChild(defs);

    // --- area fill ---
    const areaPath = document.createElementNS(SVG_NS, "path");
    areaPath.setAttribute("d", dPath);
    areaPath.setAttribute("fill", `url(#${gradientId})`);
    areaPath.setAttribute("stroke", "none");
    linegraphSVG.appendChild(areaPath);

    // --- stroke on top (same curve without closing) ---
    let strokePath = `M ${xScale(datesMs[0])},${yScale(amounts[0])}`;
    for (let i = 1; i < datesMs.length; i++) {
      const x1 = xScale(datesMs[i - 1]), y1 = yScale(amounts[i - 1]);
      const x2 = xScale(datesMs[i]),     y2 = yScale(amounts[i]);
      const xc = (x1 + x2) / 2;
      strokePath += ` C ${xc},${y1} ${xc},${y2} ${x2},${y2}`;
    }
    const linePath = document.createElementNS(SVG_NS, "path");
    linePath.setAttribute("d", strokePath);
    linePath.setAttribute("stroke", graphStrokeStrong);
    linePath.setAttribute("stroke-width", "2");
    linePath.setAttribute("fill", "none");
    linegraphSVG.appendChild(linePath);

    // --- axes: baseline & Y axis ---
    const xAxisLine = document.createElementNS(SVG_NS, "line");
    xAxisLine.setAttribute("x1", margin.left);
    xAxisLine.setAttribute("x2", margin.left + innerW);
    xAxisLine.setAttribute("y1", margin.top + innerH);
    xAxisLine.setAttribute("y2", margin.top + innerH);
    xAxisLine.setAttribute("stroke", graphStrokeNormal);
    xAxisLine.setAttribute("stroke-width", "1");
    linegraphSVG.appendChild(xAxisLine);

    const yAxisLine = document.createElementNS(SVG_NS, "line");
    yAxisLine.setAttribute("x1", margin.left);
    yAxisLine.setAttribute("x2", margin.left);
    yAxisLine.setAttribute("y1", margin.top + innerH);
    yAxisLine.setAttribute("y2", margin.top);
    yAxisLine.setAttribute("stroke", graphStrokeNormal);
    yAxisLine.setAttribute("stroke-width", "1");
    linegraphSVG.appendChild(yAxisLine);

    // --- Y labels & gridlines at min, 25%, 50%, 75%, 100% ---
    const yPercents = [0, 0.25, 0.5, 0.75, 1];
    yPercents.forEach(p => {
      const v = Math.round(yMin + (yMax - yMin) * p);
      const y = yScale(v);

      if (p > 0) {
        const gl = document.createElementNS(SVG_NS, "line");
        gl.setAttribute("x1", margin.left);
        gl.setAttribute("x2", margin.left + innerW);
        gl.setAttribute("y1", y);
        gl.setAttribute("y2", y);
        gl.setAttribute("stroke", graphStrokeLight);
        gl.setAttribute("stroke-width", "1");
        linegraphSVG.appendChild(gl);
      }

      const t = document.createElementNS(SVG_NS, "text");
      t.setAttribute("x", margin.left - 10);
      t.setAttribute("y", y + 3);
      t.setAttribute("text-anchor", "end");
      t.setAttribute("font-size", "13");
      const textColor = getComputedStyle(document.documentElement).getPropertyValue('--color-text').trim() || '#000';
      t.setAttribute("fill", textColor);
      t.textContent = `\$${v} (${(p * 100).toFixed(0)}%)`;
      linegraphSVG.appendChild(t);
    });

    // --- month labels (localized) ---
    const monthFormatter = new Intl.DateTimeFormat(PC.config.USER_LOCALE, { month: "short" });
    for (let m = 0; m < 12; m++) {
      const mid = new Date(year, m, 15).getTime();
      const x = xScale(mid);
      const y = margin.top + innerH + 15;

      const label = document.createElementNS(SVG_NS, "text");
      label.setAttribute("x", x);
      label.setAttribute("y", y);
      label.setAttribute("text-anchor", "middle");
      label.setAttribute("font-size", "13");
      const textColor = getComputedStyle(document.documentElement).getPropertyValue('--color-text').trim() || '#000';
      label.setAttribute("fill", textColor);
      label.textContent = monthFormatter.format(new Date(year, m, 1));
      linegraphSVG.appendChild(label);
    }

    // === Mouseover tooltip / hover tracker ===
    const overlay = document.createElementNS(SVG_NS, "rect");
    overlay.setAttribute("x", margin.left);
    overlay.setAttribute("y", margin.top);
    overlay.setAttribute("width", innerW);
    overlay.setAttribute("height", innerH);
    overlay.setAttribute("fill", "transparent");
    overlay.classList.add("earnings-crosshair");

    // hairline, dot, tooltip group
    const hair = document.createElementNS(SVG_NS, "line");
    hair.setAttribute("stroke", graphStrokeNormal);
    hair.setAttribute("stroke-width", "1");
    hair.classList.add("svg-hidden");

    const dot = document.createElementNS(SVG_NS, "circle");
    dot.setAttribute("r", "3");
    dot.setAttribute("fill", graphStrokeStrong);
    dot.classList.add("svg-hidden");

    const tipG = document.createElementNS(SVG_NS, "g");
    tipG.classList.add("svg-hidden");
    const tipRect = document.createElementNS(SVG_NS, "rect");
    tipRect.setAttribute("rx", "2");
    tipRect.setAttribute("ry", "2");
    tipRect.setAttribute("fill", tooltipBg);
    tipRect.setAttribute("stroke", "rgba(200, 200, 200, 0.8)");
    tipRect.setAttribute("stroke-width", "0.5");
    const tipText = document.createElementNS(SVG_NS, "text");
    tipText.setAttribute("font-size", "14");
    tipText.setAttribute("fill", "#1a1a1a");
    tipText.setAttribute("x", "5");
    tipText.setAttribute("y", "16");
    tipG.appendChild(tipRect);
    tipG.appendChild(tipText);

    linegraphSVG.appendChild(hair);
    linegraphSVG.appendChild(dot);
    linegraphSVG.appendChild(tipG);
    linegraphSVG.appendChild(overlay);

    // helpers
    function getSVGX(evt) {
      const ctm = linegraphSVG.getScreenCTM();
      if (!ctm) {
        return Number.NaN;
      }
      const pt = linegraphSVG.createSVGPoint();
      pt.x = evt.clientX;
      const cursor = pt.matrixTransform(ctm.inverse());
      return cursor.x;
    }
    function invX(px) {
      return xMin + ((px - margin.left) / innerW) * xSpan;
    }
    function nearestIndex(t) {
      let lo = 0, hi = datesMs.length - 1;
      while (hi - lo > 1) {
        const mid = (hi + lo) >> 1;
        if (datesMs[mid] < t) lo = mid; else hi = mid;
      }
      return (t - datesMs[lo] < datesMs[hi] - t) ? lo : hi;
    }

    // event handlers
    const setVisible = (el, visible) => {
      el.classList.toggle("svg-hidden", !visible);
      el.classList.toggle("svg-visible", visible);
    };

    overlay.addEventListener("mousemove", evt => {
      const px = getSVGX(evt);
      if (!Number.isFinite(px)) return;
      if (px < margin.left || px > margin.left + innerW) return;
      const t = invX(px);
      const i = nearestIndex(t);
      const x = xScale(datesMs[i]);
      const y = yScale(amounts[i]);

      if (!Number.isFinite(x) || !Number.isFinite(y)) return;

      hair.setAttribute("x1", x);
      hair.setAttribute("x2", x);
      hair.setAttribute("y1", margin.top);
      hair.setAttribute("y2", margin.top + innerH);
      setVisible(hair, true);

      dot.setAttribute("cx", x);
      dot.setAttribute("cy", y);
      setVisible(dot, true);

      const dateStr = formatDateKeyShort(dateKeys[i]);
      const amountValue = Number(amounts[i]);
      if (!Number.isFinite(amountValue)) return;
      const valStr = amountValue.toFixed(2);
      tipText.textContent = `${dateStr}: \$${valStr}`;
      const bbox = tipText.getBBox();
      tipRect.setAttribute("width", bbox.width + 8);
      tipRect.setAttribute("height", bbox.height + 6);

      let tx = x + 8;
      if (tx + bbox.width + 12 > margin.left + innerW) tx = x - bbox.width - 12;
      let ty = y - bbox.height - 8;
      if (ty < margin.top) ty = y + 12;

      tipG.setAttribute("transform", `translate(${tx},${ty})`);
      setVisible(tipG, true);
    });

    overlay.addEventListener("mouseleave", () => {
      setVisible(hair, false);
      setVisible(dot, false);
      setVisible(tipG, false);
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

  function parseMoneyLike(value) {
    const normalized = String(value ?? '0').replace(/[^0-9.-]/g, '');
    const amount = Number(normalized);
    return Number.isFinite(amount) ? amount : 0;
  }

  function monthLabelFromKey(monthKey) {
    const [year, month] = String(monthKey).split('-');
    const y = Number(year);
    const m = Number(month);
    if (!Number.isFinite(y) || !Number.isFinite(m) || m < 1 || m > 12) {
      return String(monthKey);
    }

    return new Date(y, m - 1, 1).toLocaleDateString(PC.config.USER_LOCALE, {
      month: 'long',
      year: 'numeric',
    });
  }

  function buildPieGraphDataset(dailyPayload) {
    const ytd = { gross: 0, deductions: 0, net: 0 };
    const monthly = {};

    Object.entries(dailyPayload || {}).forEach(([dateKey, record]) => {
      if (!isIsoDateKey(dateKey) || !record || typeof record !== 'object') {
        return;
      }

      const gross = parseMoneyLike(record.gross);
      const deductions = parseMoneyLike(record.deductions ?? record.tax);
      const net = parseMoneyLike(record.net);
      const monthKey = String(dateKey).slice(0, 7);

      ytd.gross += gross;
      ytd.deductions += deductions;
      ytd.net += net;

      if (!monthly[monthKey]) {
        monthly[monthKey] = { gross: 0, deductions: 0, net: 0 };
      }

      monthly[monthKey].gross += gross;
      monthly[monthKey].deductions += deductions;
      monthly[monthKey].net += net;
    });

    return { ytd, monthly };
  }

  function pieSegmentsFromTotals(totals, palette) {
    const colors = palette || {};
    return [
      { key: 'gross', label: 'Gross', value: Math.max(0, Number(totals?.gross || 0)), color: String(colors.gross || '#1e4778') },
      { key: 'net', label: 'Net', value: Math.max(0, Number(totals?.net || 0)), color: String(colors.net || '#8bb7e6') },
      { key: 'deductions', label: 'Deductions', value: Math.max(0, Number(totals?.deductions || 0)), color: String(colors.deductions || '#f2d2a6') },
    ];
  }

  function getPieGraphPalette(panelEl) {
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

  const pieAmountFormatter = new Intl.NumberFormat(PC.config.USER_LOCALE || undefined, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });

  const piePercentFormatter = new Intl.NumberFormat(PC.config.USER_LOCALE || undefined, {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
  });

  function formatPieAmount(value) {
    return `$${pieAmountFormatter.format(Number(value) || 0)}`;
  }

  function formatPiePercent(value) {
    return `${piePercentFormatter.format(Number(value) || 0)}%`;
  }

  function renderPieSvg(svgEl, legendEl, totals, palette) {
    if (!svgEl || !legendEl) {
      return;
    }

    const segments = pieSegmentsFromTotals(totals, palette);
    const total = segments.reduce((sum, seg) => sum + seg.value, 0);
    svgEl.textContent = '';

    if (!Number.isFinite(total) || total <= 0) {
      window.Guardian.setHTML(legendEl, '<p class="earnings_piegraphs_empty">No values available.</p>');
      return;
    }

    const cx = 120;
    const cy = 120;
    const r = 90;
    const grossRatio = segments[0].value / total;
    // Center gross on the left side; remaining segments flow clockwise.
    let start = Math.PI - (grossRatio * Math.PI);
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
    svgEl.appendChild(totalText);

    window.Guardian.setHTML(legendEl, parts.map((seg) => (
      `<div class="earnings_piegraphs_legend_row" data-seg-key="${escapeHtml(seg.key)}">`
      + `<span class="earnings_piegraphs_legend_dot earnings_piegraphs_legend_dot_${seg.key}"></span>`
      + `<span class="earnings_piegraphs_legend_label">${escapeHtml(seg.label)}</span>`
      + `<span class="earnings_piegraphs_legend_value">${formatPieAmount(seg.value)} (${formatPiePercent(seg.pct)})</span>`
      + `</div>`
    )).join(''));

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
  }

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
    renderPieSvg(ytdSvg, ytdLegend, dataset.ytd, palette);

    const months = Object.keys(dataset.monthly).sort();
    if (months.length === 0) {
      monthSelect.textContent = '';
      renderPieSvg(monthSvg, monthLegend, { gross: 0, deductions: 0, net: 0 }, palette);
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
      renderPieSvg(monthSvg, monthLegend, dataset.monthly[selectedKey] || { gross: 0, deductions: 0, net: 0 }, palette);
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

    const employee = window.PAYCAL_USER_UUID || 'PayCal User';
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
      await EarningsExport.downloadPdfServerSide(
        normalizedScope,
        rows,
        report,
        `paycal-${normalizedScope}-${fileSuffix}.pdf`,
        startDate,
        endDate,
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
        PW.error(`[EXPORT] ${scope.toUpperCase()} ${format.toUpperCase()} ${year} failed: ${error.message}`);
        PC.showToast(`Export failed: ${error.message}`);
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
      PC.showToast(`Error: Could not load daily earnings data. ${jsonResponse.message || "Unknown error."}`);

      return;
    }
  } catch (error) {
    PC.showToast(`Error: Could not load daily earnings data. ${error.message}`);
    return;
  }

  // Check if dailyData is valid
  if (!dailyData || typeof dailyData !== 'object') {
    PC.showToast(`Error: No daily earnings data available for ${year}.`);
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

  const rows = Object.entries(dailyData)
    .sort(([d1], [d2]) => parseDateKeyToLocalMs(d1) - parseDateKeyToLocalMs(d2))
    .map(([date, record], index) => ({
      id: `daily-${year}-${index}`,
      date: formatDateKeyForDisplay(date, PC.config.USER_LOCALE),
      site: (record.site_name || '').toString(),
      wage: (record.wage || '0.00').toString(),
      hours: (record.hours || '0.00').toString(),
      regular: (record.regular_hours || '0.00').toString(),
      overtime: (record.overtime_hours || '0.00').toString(),
      travel: (record.travel_hours ?? record.travel ?? '0.00').toString(),
      loa: (record.living_out_allowance ?? record.loa ?? '0.00').toString(),
      gross: (record.gross || '0.00').toString(),
      tax: (record.tax || record.deductions || '0.00').toString(),
      deductions: (record.deductions || record.tax || '0.00').toString(),
      net: (record.net || '0.00').toString(),
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
      throw new Error(jsonResponse.message || `Failed to load ${section} section.`);
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
      window.Guardian.setHTML(target, html || '<p class="earnings_async_status">No data available.</p>');
      loadedSections.add(key);
    } catch (error) {
      window.Guardian.setHTML(target, `<p class="earnings_async_status">Unable to load section: ${escapeHtml(error.message || 'unknown error')}.</p>`);
      PW.error(`[EARNINGS] ${section} year ${year} failed: ${error.message}`);
    }
  }

  function loadSectionsForYear(year) {
    loadSection('ytd', year, `earnings_ytd_${year}`);
    loadSection('payperiods', year, `earnings_pay_periods_${year}`);
    loadSection('monthly', year, `earnings_monthly_${year}`);
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
        verifyCanonicalHashesForYear(year);
      })
      .catch(error => {
        PW.error(`[INIT] Error drawing earnings graph for ${year}: ${error.message}`);
        announceEarningsGraphError(year, error.message || 'Unable to retrieve earnings trend data.');
      });
  }

  function loadDailyForYear(year) {
    if (loadedDaily.has(year)) {
      return;
    }
    loadedDaily.add(year);
    render_daily_year(year);
  }

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

    // Extract year from tab target (tab-2026 -> 2026)
    const year = parseInt(tab.dataset.tabTarget.replace('tab-', ''), 10);
    if (!isNaN(year)) {
      if (lazyMode) {
        loadSectionsForYear(year);
        loadGraphForYear(year);
        loadDailyForYear(year);
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
      loadSectionsForYear(queueYear);
      loadGraphForYear(queueYear);
      if (index === 0) {
        loadDailyForYear(queueYear);
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

  const year = parseInt(activeTab.dataset.tabTarget.replace('tab-', ''), 10);
  if (isNaN(year)) {
    return;
  }

  loadSectionsForYear(year);
  loadGraphForYear(year);
  loadDailyForYear(year);
}

  // Initialize graphs on page load
  initializeEarningsGraphs();
  bindYearlyExportButtons();

});
