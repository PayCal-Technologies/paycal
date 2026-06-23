<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

Javascript::renderDocBlock();

$teamEarningsI18nKeys = [
  'NAME', 'GROSS', 'NET', 'LOA', 'MEMBERS', 'CSV', 'TXT',
  'EARNINGS_ROLE', 'EARNINGS_SITE', 'EARNINGS_MONTH', 'EARNINGS_REPORT',
  'EARNINGS_TEAM_EARNINGS', 'EARNINGS_MEMBER_DIALOG_TITLE',
  'EARNINGS_BREAKDOWN_REG_HRS', 'EARNINGS_BREAKDOWN_OT_HRS', 'EARNINGS_BREAKDOWN_TOTAL',
  'EARNINGS_BREAKDOWN_NO_ENTRIES_FOR_YEAR',
  'EARNINGS_SEVERITY', 'EARNINGS_TITLE', 'EARNINGS_CAUSE', 'EARNINGS_ACTION',
  'EARNINGS_PRIORITY', 'EARNINGS_SOURCE',
  'EARNINGS_MEMBER_EARNINGS_RANKING', 'EARNINGS_SITE_PAYROLL_COST',
  'EARNINGS_RISK_REGISTER', 'EARNINGS_RECOMMENDED_ACTIONS',
];
$teamEarningsI18n = [];
foreach ($teamEarningsI18nKeys as $teamEarningsI18nKey) {
  $teamEarningsI18n[$teamEarningsI18nKey] = Strings::i18n($teamEarningsI18nKey);
}

?>
import PC from '/js/';
import { escapeHtml } from '/js/core/escape.js';
import {
  formatI18n as formatConfigI18n,
  getI18nLabel as getConfigI18nLabel,
} from '/js/core/template.js';

Object.assign(PC.config, <?php echo json_encode($teamEarningsI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);

document.addEventListener('DOMContentLoaded', () => {
  const getI18nLabel = (key, fallback = '') => getConfigI18nLabel(PC?.config, key, fallback);
  const formatI18n = (key, fallback, params = {}) => formatConfigI18n(PC?.config, key, fallback, params);

  function buildGroupCsv(type, org, year, rows) {
    const hdr = `"${org}","${type}","${year}"\n`;
    if (type === 'members') {
      const head = [
        getI18nLabel('NAME', 'Name'),
        getI18nLabel('EARNINGS_ROLE', 'Role'),
        getI18nLabel('GROSS', 'Gross'),
        getI18nLabel('EARNINGS_BREAKDOWN_REG_HRS', 'Reg Hours'),
        getI18nLabel('EARNINGS_BREAKDOWN_OT_HRS', 'OT Hours'),
        getI18nLabel('LOA', 'LOA'),
      ].join(',') + '\n';
      return hdr + head + rows.map(r =>
        `"${r.name}","${r.role}",${r.gross.toFixed(2)},${r.reg_hours.toFixed(2)},${r.ot_hours.toFixed(2)},${r.loa.toFixed(2)}`
      ).join('\n');
    }
    if (type === 'sites') {
      const head = [
        getI18nLabel('EARNINGS_SITE', 'Site'),
        getI18nLabel('GROSS', 'Gross'),
        getI18nLabel('MEMBERS', 'Members'),
        getI18nLabel('EARNINGS_BREAKDOWN_REG_HRS', 'Reg Hours'),
        getI18nLabel('EARNINGS_BREAKDOWN_OT_HRS', 'OT Hours'),
      ].join(',') + '\n';
      return hdr + head + rows.map(r =>
        `"${r.site}",${r.gross.toFixed(2)},${r.members},${r.reg_hrs.toFixed(2)},${r.ot_hrs.toFixed(2)}`
      ).join('\n');
    }
    if (type === 'risks') {
      const head = [
        getI18nLabel('EARNINGS_SEVERITY', 'Severity'),
        getI18nLabel('EARNINGS_TITLE', 'Title'),
        getI18nLabel('EARNINGS_CAUSE', 'Cause'),
        getI18nLabel('EARNINGS_ACTION', 'Action'),
      ].join(',') + '\n';
      return hdr + head + rows.map(r =>
        `"${r.severity}","${r.title}","${r.cause}","${r.action}"`
      ).join('\n');
    }
    if (type === 'recommendations') {
      const head = [
        getI18nLabel('EARNINGS_PRIORITY', 'Priority'),
        getI18nLabel('EARNINGS_ACTION', 'Action'),
        getI18nLabel('EARNINGS_SOURCE', 'Source'),
      ].join(',') + '\n';
      return hdr + head + rows.map(r =>
        `"${r.priority}","${r.text}","${r.source}"`
      ).join('\n');
    }
    return '';
  }

  function buildGroupTxt(type, org, year, rows) {
    const sep = '\u2500'.repeat(60);
    const reportLabel = getI18nLabel('EARNINGS_REPORT', 'REPORT').toUpperCase();
    const typeLabelByKey = {
      members: getI18nLabel('EARNINGS_MEMBER_EARNINGS_RANKING', 'Members'),
      sites: getI18nLabel('EARNINGS_SITE_PAYROLL_COST', 'Sites'),
      risks: getI18nLabel('EARNINGS_RISK_REGISTER', 'Risks'),
      recommendations: getI18nLabel('EARNINGS_RECOMMENDED_ACTIONS', 'Recommendations'),
    };
    const typeLabel = (typeLabelByKey[type] || type).toUpperCase();
    const hdr = `${org.toUpperCase()} \u2014 ${typeLabel} ${reportLabel} \u2014 ${year}\n${sep}\n`;
    if (type === 'members') {
      const regLabel = getI18nLabel('EARNINGS_BREAKDOWN_REG_HRS', 'Reg Hrs').toLowerCase();
      const otLabel = getI18nLabel('EARNINGS_BREAKDOWN_OT_HRS', 'OT Hrs').toLowerCase();
      return hdr + rows.map((r, i) =>
        `${String(i + 1).padStart(2)}. ${r.name.padEnd(28)} ${r.role.padEnd(12)} $${r.gross.toFixed(2).padStart(12)}  ${regLabel} ${r.reg_hours.toFixed(1)}h  ${otLabel} ${r.ot_hours.toFixed(1)}h`
      ).join('\n');
    }
    if (type === 'sites') {
      const membersLabel = getI18nLabel('MEMBERS', 'members').toLowerCase();
      const regLabel = getI18nLabel('EARNINGS_BREAKDOWN_REG_HRS', 'Reg Hrs').toLowerCase();
      const otLabel = getI18nLabel('EARNINGS_BREAKDOWN_OT_HRS', 'OT Hrs').toLowerCase();
      return hdr + rows.map((r, i) =>
        `${String(i + 1).padStart(2)}. ${r.site.padEnd(28)} $${r.gross.toFixed(2).padStart(12)}  ${r.members} ${membersLabel}  ${regLabel} ${r.reg_hrs.toFixed(1)}h  ${otLabel} ${r.ot_hrs.toFixed(1)}h`
      ).join('\n');
    }
    if (type === 'risks') {
      const actionPrefix = getI18nLabel('EARNINGS_ACTION', 'Action');
      return hdr + rows.map((r, i) =>
        `${String(i + 1).padStart(2)}. [${r.severity.toUpperCase()}] ${r.title}\n    ${r.cause}\n    \u2192 ${actionPrefix}: ${r.action}`
      ).join('\n\n');
    }
    if (type === 'recommendations') {
      const sourcePrefix = getI18nLabel('EARNINGS_SOURCE', 'Source');
      return hdr + rows.map((r, i) =>
        `${String(i + 1).padStart(2)}. [${r.priority.toUpperCase()}] ${r.text}\n    ${sourcePrefix}: ${r.source}`
      ).join('\n\n');
    }
    return '';
  }

  function downloadGroupText(content, filename, mime) {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(new Blob([content], { type: mime }));
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => { document.body.removeChild(a); URL.revokeObjectURL(a.href); }, 100);
  }

  document.addEventListener('click', (event) => {
    const btn = event.target.closest('[data-group-export-format], [data-team-export-format]');
    if (!btn) { return; }
    event.preventDefault();

    const format = btn.dataset.groupExportFormat || btn.dataset.teamExportFormat;
    if (format === 'pdf') {
      window.print();
      return;
    }

    const figure = btn.closest('[data-group-type], [data-team-type]');
    if (!figure) { return; }
    const type = figure.dataset.groupType || figure.dataset.teamType || 'data';
    const year = figure.dataset.groupYear || figure.dataset.teamYear || String(new Date().getFullYear());
    const org  = figure.dataset.groupOrg || figure.dataset.teamOrg || getI18nLabel('EARNINGS_TEAM_EARNINGS', 'Business Reports');
    const raw  = figure.dataset.groupRows || figure.dataset.teamRows;
    if (!raw) { return; }

    let rows;
    try { rows = JSON.parse(raw); } catch { return; }

    const origText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '\u2026';
    try {
      const fname = `paycal-group-${type}-${year}`;
      if (format === 'csv') {
        downloadGroupText(buildGroupCsv(type, org, year, rows), `${fname}.csv`, 'text/csv;charset=utf-8');
      } else if (format === 'txt') {
        downloadGroupText(buildGroupTxt(type, org, year, rows), `${fname}.txt`, 'text/plain;charset=utf-8');
      }
    } finally {
      btn.disabled = false;
      btn.textContent = origText;
    }
  });

  const teamOrgSelect = document.getElementById('earnings_team_org');
  if (teamOrgSelect instanceof HTMLSelectElement) {
    teamOrgSelect.addEventListener('change', () => {
      const form = teamOrgSelect.closest('form');
      if (form instanceof HTMLFormElement) {
        form.submit();
      }
    });
  }

  function formatMoney(v) {
    return '$' + Number(v).toLocaleString('en-CA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function formatHours(v) {
    return Number(v).toLocaleString('en-CA', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function openMemberDialog(memberData) {
    const body = document.getElementById('earnings_team_member_dialog_body');
    const title = document.getElementById('earnings_team_member_dialog_title');
    if (!(body instanceof HTMLElement) || !(title instanceof HTMLElement)) return;

    title.textContent = formatI18n('EARNINGS_MEMBER_DIALOG_TITLE', '{name} - {year} Earnings', {
      name: memberData.name,
      year: memberData.year,
    });

    const months = Array.isArray(memberData.months) ? memberData.months : [];

    let html = '<div class="earnings_breakdown_meta">';
    html += '<span class="earnings_breakdown_role">' + escapeHtml(memberData.role.charAt(0).toUpperCase() + memberData.role.slice(1)) + '</span>';
    html += '</div>';

    if (months.length === 0) {
      html += '<p class="earnings_breakdown_empty">' + escapeHtml(formatI18n('EARNINGS_BREAKDOWN_NO_ENTRIES_FOR_YEAR', 'No entries for {year}.', { year: String(memberData.year) })) + '</p>';
    } else {
      html += '<div class="earnings_breakdown_grid">';
      html += '<div class="earnings_breakdown_header">';
      html += '<span>' + escapeHtml(getI18nLabel('EARNINGS_MONTH', 'Month')) + '</span><span>' + escapeHtml(getI18nLabel('EARNINGS_BREAKDOWN_REG_HRS', 'Reg Hrs')) + '</span><span>' + escapeHtml(getI18nLabel('EARNINGS_BREAKDOWN_OT_HRS', 'OT Hrs')) + '</span><span>' + escapeHtml(getI18nLabel('GROSS', 'Gross')) + '</span>';
      html += '</div>';

      for (const m of months) {
        html += '<div class="earnings_breakdown_row">';
        html += '<span>' + escapeHtml(m.label) + '</span>';
        html += '<span class="earnings_breakdown_num">' + formatHours(m.reg_hours) + '</span>';
        html += '<span class="earnings_breakdown_num">' + formatHours(m.ot_hours)  + '</span>';
        html += '<span class="earnings_breakdown_num">' + formatMoney(m.gross)     + '</span>';
        html += '</div>';
      }

      html += '<div class="earnings_breakdown_totals">';
      html += '<span>' + escapeHtml(getI18nLabel('EARNINGS_BREAKDOWN_TOTAL', 'Total')) + '</span>';
      html += '<span class="earnings_breakdown_num">' + formatHours(memberData.reg_hours) + '</span>';
      html += '<span class="earnings_breakdown_num">' + formatHours(memberData.ot_hours)  + '</span>';
      html += '<span class="earnings_breakdown_num">' + formatMoney(memberData.gross)     + '</span>';
      html += '</div>';

      html += '</div>';
    }

    PC.setHTML(body, html);
    PC.openModal('earnings_team_member_dialog');
  }

  document.querySelectorAll('.earnings_ytd_controls').forEach((controls) => {
    if (!(controls instanceof HTMLElement)) return;
    controls.addEventListener('change', (e) => {
      const cb = e.target;
      if (!(cb instanceof HTMLInputElement) || cb.type !== 'checkbox') return;
      const series = cb.dataset.series;
      const body = cb.closest('.earnings_ytd_body');
      if (series && body instanceof HTMLElement) {
        body.toggleAttribute('data-hide-' + series, !cb.checked);
      }
    });
  });
});
