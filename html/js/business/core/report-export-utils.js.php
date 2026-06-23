<?php namespace PayCal\Domain; ?>

  const cents = (value) => Math.round((Number(value) || 0) * 100);

  const formatMoneyForCsv = (value) => (Number(value) || 0).toFixed(2);

  const reportsCsvRow = businessCsvRow;
  const hashBlobSha256 = businessHashBlobSha256;

  const buildPayrollPackageCsv = (memberRows) => {
    const columns = [
      'member_id',
      'member_name',
      'date',
      'site_id',
      'site_name',
      'regular_hours',
      'overtime_hours',
      'travel_hours',
      'gross',
      'net',
    ];
    const lines = [columns.join(',')];
    memberRows.forEach(({ member, rows }) => {
      rows.forEach((row) => {
        lines.push(reportsCsvRow([
          member.id,
          member.name,
          row.date,
          row.siteId || '',
          row.siteName || '',
          row.regularHours,
          row.overtimeHours,
          row.travel,
          formatMoneyForCsv(row.gross),
          formatMoneyForCsv(row.net),
        ]));
      });
    });

    return lines.join('\n');
  };

  const buildPayrollSiteSummaryCsv = (memberRows) => {
    const sites = new Map();
    memberRows.forEach(({ rows }) => {
      rows.forEach((row) => {
        const key = String(row.siteId || row.siteName || 'unassigned');
        const existing = sites.get(key) || {
          site_id: row.siteId || '',
          site_name: row.siteName || 'Unassigned',
          regular_hours: 0,
          overtime_hours: 0,
          gross_cents: 0,
          net_cents: 0,
          entries: 0,
        };
        existing.regular_hours += Number(row.regularHours || 0);
        existing.overtime_hours += Number(row.overtimeHours || 0);
        existing.gross_cents += cents(row.gross);
        existing.net_cents += cents(row.net);
        existing.entries += 1;
        sites.set(key, existing);
      });
    });

    const columns = ['site_id', 'site_name', 'entries', 'regular_hours', 'overtime_hours', 'gross', 'net'];
    return [
      columns.join(','),
      ...Array.from(sites.values()).map((site) => reportsCsvRow([
        site.site_id,
        site.site_name,
        site.entries,
        site.regular_hours.toFixed(2),
        site.overtime_hours.toFixed(2),
        formatMoneyForCsv(site.gross_cents / 100),
        formatMoneyForCsv(site.net_cents / 100),
      ])),
    ].join('\n');
  };

  const buildPayrollExceptionsCsv = (memberRows, failures) => {
    const rows = [];
    failures.forEach((failure) => {
      rows.push({
        member_id: failure.member.id,
        member_name: failure.member.name,
        date: '',
        severity: 'error',
        issue: failure.error || 'Report generation failed.',
      });
    });
    memberRows.forEach(({ member, rows: detailRows }) => {
      if (detailRows.length === 0) {
        rows.push({ member_id: member.id, member_name: member.name, date: '', severity: 'warning', issue: 'No report rows available.' });
        return;
      }
      detailRows.forEach((row) => {
        const totalHours = Number(row.hours || 0) + Number(row.travel || 0);
        if (totalHours > 16) {
          rows.push({ member_id: member.id, member_name: member.name, date: row.date, severity: 'warning', issue: `High daily hours (${totalHours.toFixed(2)}).` });
        }
        if (Number(row.overtimeHours || 0) > 8) {
          rows.push({ member_id: member.id, member_name: member.name, date: row.date, severity: 'warning', issue: `High overtime hours (${Number(row.overtimeHours).toFixed(2)}).` });
        }
        if (String(row.siteName || '').trim() === '') {
          rows.push({ member_id: member.id, member_name: member.name, date: row.date, severity: 'warning', issue: 'Missing site name.' });
        }
      });
    });

    const columns = ['member_id', 'member_name', 'date', 'severity', 'issue'];
    return [
      columns.join(','),
      ...rows.map((row) => reportsCsvRow(columns.map((column) => row[column]))),
    ].join('\n');
  };

  const buildPayrollPackageReadme = (batch) => [
    T.payrollPackageReadmeTitle,
    '',
    `Business ID: ${batch.orgId}`,
    `Actor ID: ${batch.actorId}`,
    `Year: ${batch.year}`,
    `Generation path: ${batch.generationPath}`,
    `Trust level: ${batch.trustLevel}`,
    `Generated at: ${batch.generatedAt}`,
    `Members completed: ${batch.generated} / ${batch.total}`,
    `Exceptions: ${batch.exceptionCount}`,
    '',
    T.payrollPackageReadmeContents,
    '- pdf/: server-authorized member reports',
    '- csv/: browser convenience member report CSV files from authorized report data',
    '- exports/payroll-import.csv: browser convenience aggregate payroll import data',
    '- summaries/site-summary.csv: browser convenience labour cost by site',
    '- exceptions/exceptions.csv: browser convenience missing or unusual entries detected during package generation',
    '- audit/audit-manifest.json and audit/audit-manifest.csv: generation record',
    '- consent/consent-snapshot.json: access basis snapshot at generation time',
    '- SHA256SUMS.txt: hash ledger for package files',
    '',
    T.payrollPackageReadmePolicy,
  ].join('\n');

  const buildPayrollConsentSnapshot = (batch) => JSON.stringify({
    generated_at: batch.generatedAt,
    business_id: batch.orgId,
    actor_uuid: batch.actorId,
    access_basis: 'active business connection and report endpoint authorization at generation time',
    member_count: batch.total,
    member_uuids: batch.results.map((result) => result.member.id),
  }, null, 2);

  const buildPayrollPackageManifestCsv = (batch) => {
    const columns = ['generated_at', 'actor_id', 'business_id', 'report', 'year', 'generation_path', 'trust_level', 'trust_note', 'status', 'path', 'sha256', 'member_id', 'member_name', 'error'];
    return [
      columns.join(','),
      ...batch.manifest.map((row) => reportsCsvRow(columns.map((column) => row[column]))),
    ].join('\n');
  };

  const buildPayrollPackageManifestJson = (batch) => JSON.stringify({
    generated_at: batch.generatedAt,
    actor_id: batch.actorId,
    business_id: batch.orgId,
    report: batch.reportKey,
    year: batch.year,
    generation_path: batch.generationPath,
    trust_level: batch.trustLevel,
    trust_note: batch.trustNote,
    total: batch.total,
    succeeded: batch.generated,
    failed: batch.failed,
    exceptions: batch.exceptionCount,
    duration_ms: batch.durationMs,
    files: batch.manifest,
  }, null, 2);

  const payrollPackageFilename = (batch) => `paycal-payroll-package-${batch.year}.zip`;

  const downloadPayrollPackageZip = async (batch) => {
    downloadBlob(await createZipBlob(batch.files), payrollPackageFilename(batch));
  };
