<?php declare(strict_types=1);

/**
 * js/admin/language-dashboard.php
 *
 * Purpose: JavaScript module for the admin language translation dashboard.
 * Served at: /js/admin/language-dashboard.php
 * Loaded as: <script type="module"> from html/admin/language-dashboard/index.php
 *
 * Responsibilities:
 * - Fetch and render live audit data from GET /api/v1/admin/languages/audit
 * - Drive batch-by-batch AI translation for a selected language
 * - Display a live log of translation progress per batch
 * - Re-render audit metrics in-place after each batch completes
 *
 * Security:
 * - All DOM HTML injection goes through Guardian.setHTML()
 * - No inline event handlers — addEventListener only
 * - Authentication enforced before JS is served
 *
 * This file requires:
 * - PC (PayCal global module) for PC.config.pc_api and PC.showToast
 * - Guardian global for safe DOM HTML insertion
 */

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();
Authentication::isAdminOrDie();

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');
Javascript::renderDocBlock();

?>

import PC from "<?php echo Environment::appURL('js/'); ?>";

const API = PC.config.pc_api;

// ─── Capability token helpers ────────────────────────────────────────────────

/**
 * Fetch a short-lived capability token for a given admin action.
 * @param {string} action
 * @returns {Promise<string>}
 */
async function capToken(action) {
  const res = await fetch(`${API}/admin/capability/${encodeURIComponent(action)}`, {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
  });
  const body = await res.json();
  if (!res.ok || body.status !== 'success') {
    throw new Error(body.message || 'Capability token request failed.');
  }
  return String(body.token || '');
}

// ─── DOM helpers ─────────────────────────────────────────────────────────────

const $ = (sel, root = document) => root.querySelector(sel);

function esc(str) {
  const d = document.createElement('div');
  d.textContent = String(str ?? '');
  return d.innerHTML;
}

function pct(ratio) {
  return (100 - Number(ratio || 0)).toFixed(1) + '%';
}

function progressBar(translatedPct) {
  const val = Math.min(100, Math.max(0, parseFloat(String(translatedPct)))).toFixed(1);
  return `<div class="lang-dash__progress-wrap">
    <div class="lang-dash__progress-bar-track" role="progressbar" aria-valuenow="${val}" aria-valuemin="0" aria-valuemax="100">
      <div class="lang-dash__progress-bar" style="width:${val}%"></div>
    </div>
    <span class="lang-dash__progress-pct">${val}%</span>
  </div>`;
}

function badge(ok, labelOk, labelFail) {
  const cls = ok ? 'lang-dash__badge--ok' : 'lang-dash__badge--error';
  return `<span class="lang-dash__badge ${cls}">${esc(ok ? labelOk : labelFail)}</span>`;
}

function ratioClass(ratio) {
  if (ratio <= 10) { return 'lang-dash__badge--ok'; }
  if (ratio <= 40) { return 'lang-dash__badge--warn'; }
  return 'lang-dash__badge--error';
}

// ─── Audit rendering ──────────────────────────────────────────────────────────

/**
 * Render the summary table + per-language cards from a fullReport payload.
 * @param {Object} report
 */
function renderReport(report) {
  const container = document.getElementById('lang-dash-root');
  if (!container) { return; }

  const languages = Array.isArray(report.languages) ? report.languages : [];
  const allUntranslated = Array.isArray(report.all_untranslated) ? report.all_untranslated : [];
  const partial = Array.isArray(report.partial) ? report.partial : [];

  // Summary stats bar
  const statsEl = document.getElementById('lang-dash-stats');
  if (statsEl) {
    const avgPct = languages.length > 0
      ? (languages.reduce((s, l) => s + (100 - l.ratio), 0) / languages.length).toFixed(1)
      : '0.0';
    Guardian.setHTML(statsEl, `
      <span>Total keys: <strong>${esc(report.en_key_count)}</strong></span>
      &ensp;·&ensp;
      <span>Languages: <strong>${esc(languages.length)}</strong></span>
      &ensp;·&ensp;
      <span>Avg coverage: <strong>${esc(avgPct)}%</strong></span>
      &ensp;·&ensp;
      <span>Untranslated in all langs: <strong>${esc(allUntranslated.length)}</strong></span>
      &ensp;·&ensp;
      <span>Partially translated: <strong>${esc(partial.length)}</strong></span>
    `);
  }

  // Summary table
  const tableEl = document.getElementById('lang-dash-table-body');
  if (tableEl) {
    let rows = '';
    for (const lang of languages) {
      const translatedPct = pct(lang.ratio);
      rows += `<tr>
        <td><strong>${esc(lang.name)}</strong> <small style="color:var(--text-muted)">${esc(lang.lang)}</small></td>
        <td>${esc(lang.translated)} / ${esc(lang.total)}</td>
        <td>
          <div class="lang-dash__ratio-cell">
            ${progressBar(translatedPct)}
            <span class="lang-dash__badge ${ratioClass(lang.ratio)}">${esc(lang.ratio)}% untrans.</span>
          </div>
        </td>
        <td>${badge(lang.order_ok, 'OK', 'Reorder needed')}</td>
        <td>${badge(lang.encoding_ok, 'OK', 'Encoding issue')}</td>
        <td>
          <button class="btn btn_secondary btn--sm lang-dash__translate-btn"
            data-lang="${esc(lang.lang)}"
            data-name="${esc(lang.name)}"
            ${lang.untranslated === 0 ? 'disabled' : ''}>
            ${lang.untranslated === 0 ? 'Complete' : `Translate (${esc(lang.untranslated)} keys)`}
          </button>
        </td>
      </tr>`;
    }
    Guardian.setHTML(tableEl, rows);
  }

  // Per-language cards
  const cardsEl = document.getElementById('lang-dash-cards');
  if (cardsEl) {
    let cards = '';
    for (const lang of languages) {
      const translatedPct = pct(lang.ratio);
      const keyListHtml = lang.untranslated_keys.length > 0
        ? lang.untranslated_keys.slice(0, 30).map(k => `<li>${esc(k)}</li>`).join('')
          + (lang.untranslated_keys.length > 30 ? `<li>… and ${lang.untranslated_keys.length - 30} more</li>` : '')
        : '<li>All keys translated!</li>';
      cards += `<div class="lang-dash__card panel" aria-expanded="false" data-lang-card="${esc(lang.lang)}">
        <div class="lang-dash__card-header" role="button" tabindex="0" aria-controls="lang-card-body-${esc(lang.lang)}">
          <span class="lang-dash__card-title">
            <span class="lang-dash__card-toggle-icon" aria-hidden="true"></span>
            ${esc(lang.name)} <small style="color:var(--text-muted)">(${esc(lang.lang)})</small>
          </span>
          <span class="lang-dash__card-meta">
            ${progressBar(translatedPct)}
            ${badge(lang.order_ok, 'Ordered', 'Reorder needed')}
            ${badge(lang.encoding_ok, 'UTF-8 OK', 'Encoding issue')}
          </span>
        </div>
        <div class="lang-dash__card-body" id="lang-card-body-${esc(lang.lang)}">
          <p style="margin:0 0 0.5rem"><strong>${esc(lang.untranslated)}</strong> keys need translation.</p>
          <ul style="font-size:0.82rem;max-height:10rem;overflow:auto;padding-left:1.2em;margin:0 0 0.75rem">
            ${keyListHtml}
          </ul>
          <div class="lang-dash__translate-row">
            <button class="btn btn_primary lang-dash__translate-btn"
              data-lang="${esc(lang.lang)}"
              data-name="${esc(lang.name)}"
              ${lang.untranslated === 0 ? 'disabled' : ''}>
              ${lang.untranslated === 0 ? 'All translated' : `Translate ${esc(lang.name)} with AI`}
            </button>
            <span class="lang-dash__translate-status" id="lang-translate-status-${esc(lang.lang)}" aria-live="polite"></span>
          </div>
          <div class="lang-dash__log" id="lang-translate-log-${esc(lang.lang)}" hidden aria-label="Translation log for ${esc(lang.name)}"></div>
        </div>
      </div>`;
    }
    Guardian.setHTML(cardsEl, cards);
  }

  // Wire up card toggle + translate buttons
  wireCardToggles();
  wireTranslateButtons();
}

// ─── Card expand/collapse ─────────────────────────────────────────────────────

function wireCardToggles() {
  const cards = Array.from(document.querySelectorAll('.lang-dash__card[data-lang-card]'));
  for (const card of cards) {
    const header = card.querySelector('.lang-dash__card-header');
    if (!header) { continue; }
    header.removeEventListener('click', onCardToggle);
    header.addEventListener('click', onCardToggle);
    header.removeEventListener('keydown', onCardKeydown);
    header.addEventListener('keydown', onCardKeydown);
  }
}

function onCardToggle(e) {
  const header = e.currentTarget;
  const card = header.closest('.lang-dash__card');
  if (!card) { return; }
  const expanded = card.getAttribute('aria-expanded') === 'true';
  card.setAttribute('aria-expanded', expanded ? 'false' : 'true');
}

function onCardKeydown(e) {
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault();
    onCardToggle(e);
  }
}

// ─── Translation flow ─────────────────────────────────────────────────────────

let translatingLang = null;

function wireTranslateButtons() {
  const btns = Array.from(document.querySelectorAll('.lang-dash__translate-btn'));
  for (const btn of btns) {
    btn.removeEventListener('click', onTranslateClick);
    btn.addEventListener('click', onTranslateClick);
  }
}

function onTranslateClick(e) {
  const btn = e.currentTarget;
  const lang = btn.getAttribute('data-lang');
  const name = btn.getAttribute('data-name') || lang;
  if (!lang || translatingLang) {
    if (translatingLang) {
      PC.showToast(`Translation already in progress for ${translatingLang}.`);
    }
    return;
  }
  startTranslation(lang, name);
}

/**
 * Drive the full batch translation loop for a language.
 * Iterates batch_index 0..N-1, posting to /api/v1/admin/languages/translate.
 * Updates the live log and progress indicator in-place.
 *
 * @param {string} lang  Two-letter language code
 * @param {string} name  Display name
 */
async function startTranslation(lang, name) {
  translatingLang = lang;

  // Disable all translate buttons
  const allBtns = Array.from(document.querySelectorAll('.lang-dash__translate-btn'));
  for (const b of allBtns) { b.disabled = true; }

  const statusEl = document.getElementById(`lang-translate-status-${lang}`);
  const logEl    = document.getElementById(`lang-translate-log-${lang}`);

  // Ensure card is open
  const card = document.querySelector(`.lang-dash__card[data-lang-card="${lang}"]`);
  if (card) { card.setAttribute('aria-expanded', 'true'); }

  if (logEl) {
    logEl.removeAttribute('hidden');
    Guardian.setHTML(logEl, '');
  }

  const announce = document.getElementById('lang-dash-announce');

  function setStatus(msg) {
    if (statusEl) { statusEl.textContent = msg; }
    if (announce) { announce.textContent = msg; }
  }

  function appendLog(text, type = '') {
    if (!logEl) { return; }
    const line = document.createElement('span');
    line.className = 'lang-dash__log-line' + (type ? ` lang-dash__log-line--${type}` : '');
    line.textContent = text;
    logEl.appendChild(line);
    logEl.scrollTop = logEl.scrollHeight;
  }

  setStatus(`Starting AI translation for ${name}…`);
  appendLog(`[start] AI translation for ${name} (${lang})`);

  let batchIndex = 0;
  let totalBatches = null;
  let totalApplied = 0;

  try {
    while (true) {
      setStatus(`Fetching capability token…`);
      let token;
      try {
        token = await capToken('admin.languages.translate');
      } catch (capErr) {
        appendLog(`[error] Could not obtain capability token: ${capErr.message}`, 'err');
        setStatus('Capability token error. Stopped.');
        break;
      }

      setStatus(`Translating batch ${batchIndex + 1}${totalBatches !== null ? ' / ' + totalBatches : ''}…`);

      const params = new URLSearchParams();
      params.set('lang', lang);
      params.set('batch_index', String(batchIndex));
      params.set('capability_token', token);

      let res, body;
      try {
        res = await fetch(`${API}/admin/languages/translate`, {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
            'X-PayCal-Capability': token,
          },
          body: params.toString(),
        });
        body = await res.json();
      } catch (fetchErr) {
        appendLog(`[error] Network error on batch ${batchIndex}: ${fetchErr.message}`, 'err');
        setStatus('Network error. Stopped.');
        break;
      }

      if (!res.ok || body.status !== 'success') {
        appendLog(`[error] Batch ${batchIndex} failed: ${body.message || 'Unknown error'}`, 'err');
        setStatus('Translation error. Stopped.');
        break;
      }

      const applied     = Number(body.applied ?? 0);
      const doneBool    = Boolean(body.done);
      totalBatches      = Number(body.total_batches ?? totalBatches ?? '?');
      totalApplied     += applied;

      appendLog(`[batch ${batchIndex + 1}/${totalBatches}] applied ${applied} translations`, 'ok');

      if (doneBool || batchIndex >= totalBatches - 1) {
        appendLog(`[done] ${totalApplied} keys translated for ${name}.`, 'ok');
        setStatus(`Done — ${totalApplied} keys translated.`);
        break;
      }

      batchIndex++;
    }
  } catch (err) {
    appendLog(`[fatal] Unexpected error: ${err.message}`, 'err');
    setStatus('Unexpected error. Stopped.');
  } finally {
    translatingLang = null;
    // Re-fetch audit and re-render so metrics update live
    setStatus('Refreshing audit…');
    try {
      await loadAndRenderAudit();
      setStatus('');
    } catch {
      setStatus('Refresh failed — reload the page.');
    }
  }
}

// ─── Audit fetch + render ─────────────────────────────────────────────────────

async function loadAndRenderAudit() {
  const loadingEl = document.getElementById('lang-dash-loading');
  const errorEl   = document.getElementById('lang-dash-error');

  if (loadingEl) { loadingEl.removeAttribute('hidden'); }
  if (errorEl)   { errorEl.setAttribute('hidden', ''); }

  let token;
  try {
    token = await capToken('admin.languages.audit');
  } catch (err) {
    if (loadingEl) { loadingEl.setAttribute('hidden', ''); }
    if (errorEl)   {
      errorEl.removeAttribute('hidden');
      Guardian.setHTML(errorEl, `Failed to load audit: ${esc(err.message)}`);
    }
    return;
  }

  let res, body;
  try {
    res = await fetch(`${API}/admin/languages/audit`, {
      method: 'GET',
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-PayCal-Capability': token,
      },
    });
    body = await res.json();
  } catch (err) {
    if (loadingEl) { loadingEl.setAttribute('hidden', ''); }
    if (errorEl)   {
      errorEl.removeAttribute('hidden');
      Guardian.setHTML(errorEl, `Network error: ${esc(err.message)}`);
    }
    return;
  }

  if (loadingEl) { loadingEl.setAttribute('hidden', ''); }

  if (!res.ok || body.status !== 'success') {
    if (errorEl) {
      errorEl.removeAttribute('hidden');
      Guardian.setHTML(errorEl, `Audit failed: ${esc(body.message || 'Unknown error')}`);
    }
    return;
  }

  renderReport(body.report);
}

// ─── Init ─────────────────────────────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', () => {
  loadAndRenderAudit();
});
