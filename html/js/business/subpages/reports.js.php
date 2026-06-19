
  // Subpage module: reports (data-business-subpage="reports")
  // Entry: openBusinessReportsPage via refreshIndex; analytics SSR only.

  const BUSINESS_REPORTS_LENS_PREFIX = '[PayCal Lens][business/reports]';
  const PAYROLL_PACKAGE_CONFIRM_THRESHOLD = 25;
  let payrollPackageRunning = false;
  let payrollPackageLastBatch = null;

  const isBusinessReportsSubPage = () => resolveBusinessSubPage() === 'reports';

  const resolveBusinessReportsLensBootOptions = () => {
    const fromWindow = window.__PAYCAL_LENS_PERF__?.['business/reports'];
    if (fromWindow && typeof fromWindow === 'object') {
      return { ranked: true, enabled: true, ...fromWindow };
    }

    const workspace = document.getElementById('business-workspace');
    if (workspace instanceof HTMLElement && workspace.dataset.lensPerfBoot) {
      try {
        const parsed = JSON.parse(workspace.dataset.lensPerfBoot);
        if (parsed && typeof parsed === 'object') {
          return { ranked: true, enabled: true, ...parsed };
        }
      } catch (error) {
        console.warn(BUSINESS_REPORTS_LENS_PREFIX, 'Invalid data-lens-perf-boot JSON', error);
      }
    }

    if (workspace instanceof HTMLElement && workspace.dataset.lensPageDebug) {
      return { ranked: true, enabled: true, scope: 'business/reports' };
    }

    return { ranked: true, enabled: false };
  };

  const resolveBusinessReportsLensPerf = () => {
    if (!isBusinessReportsSubPage()) {
      return null;
    }

    if (typeof window.PayCalLensPerformance?.create !== 'function') {
      console.warn(BUSINESS_REPORTS_LENS_PREFIX, 'PayCalLensPerformance.create unavailable — perf summary disabled');
      return null;
    }

    const bootOptions = resolveBusinessReportsLensBootOptions();
    const shouldEnable = bootOptions.enabled !== false;
    const existing = window.PayCalLensReportsPerf;

    if (!existing) {
      window.PayCalLensReportsPerf = window.PayCalLensPerformance.create('business/reports', bootOptions);
      if (window.PayCalLensReportsPerf?.isEnabled()) {
        window.PayCalLensReportsPerf.markSsrPainted();
      }
      return window.PayCalLensReportsPerf;
    }

    if (shouldEnable && !existing.isEnabled()) {
      window.PayCalLensReportsPerf = window.PayCalLensPerformance.create('business/reports', bootOptions);
      if (window.PayCalLensReportsPerf?.isEnabled()) {
        window.PayCalLensReportsPerf.markSsrPainted();
      }
    }

    return window.PayCalLensReportsPerf;
  };

  let businessReportsLensPerfSummaryEmitted = false;

  const finalizeBusinessReportsLensPerfSummary = (title = 'Performance Summary') => {
    const perf = resolveBusinessReportsLensPerf();
    if (!perf?.isEnabled()) {
      return;
    }

    if (businessReportsLensPerfSummaryEmitted) {
      return;
    }

    businessReportsLensPerfSummaryEmitted = true;
    perf.markHydrationComplete();
    perf.summarize(title);
  };

  const logBusinessReportsLensPageDebug = () => {
    const workspace = document.getElementById('business-workspace');
    if (!(workspace instanceof HTMLElement) || !workspace.dataset.lensPageDebug) {
      return;
    }

    try {
      const debug = JSON.parse(workspace.dataset.lensPageDebug);
      console.groupCollapsed(BUSINESS_REPORTS_LENS_PREFIX + ' page debug');
      console.log(BUSINESS_REPORTS_LENS_PREFIX, 'Lens mode requested:', debug.lens_requested);
      console.log(BUSINESS_REPORTS_LENS_PREFIX, 'Lens enabled:', debug.lens_enabled);
      console.dir(debug.snapshot);
      if (debug.lens_meta && Object.keys(debug.lens_meta).length) {
        console.log(BUSINESS_REPORTS_LENS_PREFIX, 'Lens meta:', debug.lens_meta);
      }
      if (Array.isArray(debug.lens_events) && debug.lens_events.length) {
        console.group(BUSINESS_REPORTS_LENS_PREFIX + ' Lens events');
        debug.lens_events.forEach((event) => {
          console.group((event.label || 'event') + ' (' + (event.type || 'data') + ')');
          console.dir(event.payload);
          console.groupEnd();
        });
        console.groupEnd();
      }
      if (debug.lens_counters && Object.keys(debug.lens_counters).length) {
        console.log(BUSINESS_REPORTS_LENS_PREFIX, 'Lens counters:', debug.lens_counters);
      }
      console.groupEnd();
    } catch (error) {
      console.warn(BUSINESS_REPORTS_LENS_PREFIX, 'Invalid data-lens-page-debug JSON', error);
    }
  };

  const logBusinessReportsLensDebug = () => {
    const workspace = document.getElementById('business-workspace');
    if (!(workspace instanceof HTMLElement)) {
      console.warn(BUSINESS_REPORTS_LENS_PREFIX, 'Missing #business-workspace');
      return;
    }

    const panelShell = document.querySelector('.business_reports_panel_shell');
    const teamPanel = panelShell?.querySelector('.earnings_team_panel') ?? null;
    const pageHeading = document.querySelector('#business-workspace > h1.visually_hidden');
    const emptyState = document.querySelector('.business_reports_empty, .et_empty_state');
    const errorPanel = document.querySelector('.business_reports_error');
    const loadingPanel = panelShell?.querySelector('[data-reports-panel-loading="1"]') ?? null;

    console.groupCollapsed(BUSINESS_REPORTS_LENS_PREFIX + ' DOM init');
    console.log(BUSINESS_REPORTS_LENS_PREFIX, 'subpage', workspace.dataset.businessSubpage || '(missing)');
    console.log(BUSINESS_REPORTS_LENS_PREFIX, 'data-business-id', workspace.dataset.businessId || '(none)');
    console.log(BUSINESS_REPORTS_LENS_PREFIX, 'data-lens-mode', workspace.dataset.lensMode || '0');
    console.log(BUSINESS_REPORTS_LENS_PREFIX, 'page heading present', pageHeading instanceof HTMLElement);
    console.log(BUSINESS_REPORTS_LENS_PREFIX, 'panel shell present', panelShell instanceof HTMLElement);
    console.log(BUSINESS_REPORTS_LENS_PREFIX, 'SSR panel flag', panelShell instanceof HTMLElement ? panelShell.dataset.ssrReportsPanel || '0' : '(none)');
    console.log(BUSINESS_REPORTS_LENS_PREFIX, 'team panel present', teamPanel instanceof HTMLElement);
    console.log(BUSINESS_REPORTS_LENS_PREFIX, 'loading skeleton present', loadingPanel instanceof HTMLElement);
    console.log(BUSINESS_REPORTS_LENS_PREFIX, 'empty state present', emptyState instanceof HTMLElement);
    console.log(BUSINESS_REPORTS_LENS_PREFIX, 'error panel present', errorPanel instanceof HTMLElement);
    console.groupEnd();
  };

  const canHydrateBusinessReportsPanelFromSsr = () => {
    const panelShell = document.querySelector('.business_reports_panel_shell');
    if (!(panelShell instanceof HTMLElement)) {
      return false;
    }

    if (String(panelShell.dataset.ssrReportsPanel || '').trim() !== '1') {
      return false;
    }

    const panel = panelShell.querySelector('.earnings_team_panel');
    if (!(panel instanceof HTMLElement)) {
      return false;
    }

    if (panel.dataset.reportsPanelLoading === '1') {
      return false;
    }

    return panel.querySelector('.et_exec_snapshot, .et_empty_state, .business_reports_error') instanceof HTMLElement;
  };

  const announceBusinessReportsStatus = (message) => {
    const status = document.getElementById('business_reports_sr_status');
    if (status instanceof HTMLElement) {
      status.textContent = String(message || '');
    }
  };

  const setPayrollPackageStatus = (message = '') => {
    const status = document.getElementById('business_payroll_package_status');
    if (status instanceof HTMLElement) {
      status.textContent = String(message || '');
    }
  };

  const setPayrollPackageSummaryVisible = (isVisible) => {
    const summary = document.getElementById('business_payroll_package_summary');
    if (summary instanceof HTMLElement) {
      summary.hidden = !isVisible;
    }
  };

  const resolvePayrollPackageOrgId = () => {
    const workspace = document.getElementById('business-workspace');
    if (workspace instanceof HTMLElement) {
      return String(workspace.dataset.selectedBusinessId || workspace.dataset.businessId || '').trim();
    }

    return String(state.selectedBusinessId || resolveWorkspaceBusinessId() || '').trim();
  };

  const selectedPayrollPackageYear = () => {
    const input = document.getElementById('business_payroll_package_year');
    const currentYear = new Date().getFullYear();
    if (!(input instanceof HTMLInputElement)) {
      return currentYear;
    }

    const parsed = Number.parseInt(String(input.value || ''), 10);
    const year = Number.isFinite(parsed) ? Math.max(2000, Math.min(2100, parsed)) : currentYear;
    input.value = String(year);
    return year;
  };

  const resolvePayrollPackageMembers = async (orgId) => {
    const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(orgId)}/relationships`);
    const members = Array.isArray(payload?.members) ? payload.members : [];
    return members
      .map((member) => {
        const id = String(member?.user_uuid || member?.uuid || '').trim();
        if (id === '') {
          return null;
        }

        const name = String(member?.full_name || member?.email || id).trim();
        return { id, name };
      })
      .filter((member) => member !== null);
  };

  const cents = (value) => Math.round((Number(value) || 0) * 100);

  const formatMoneyForCsv = (value) => (Number(value) || 0).toFixed(2);

  const hashBlobSha256 = async (blob) => {
    const digest = await crypto.subtle.digest('SHA-256', await blob.arrayBuffer());
    return Array.from(new Uint8Array(digest))
      .map((byte) => byte.toString(16).padStart(2, '0'))
      .join('');
  };

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
        lines.push([
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
        ].map(csvEscape).join(','));
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
      ...Array.from(sites.values()).map((site) => [
        site.site_id,
        site.site_name,
        site.entries,
        site.regular_hours.toFixed(2),
        site.overtime_hours.toFixed(2),
        formatMoneyForCsv(site.gross_cents / 100),
        formatMoneyForCsv(site.net_cents / 100),
      ].map(csvEscape).join(',')),
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
      ...rows.map((row) => columns.map((column) => csvEscape(row[column])).join(',')),
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
    access_basis: 'active business relationship and report endpoint authorization at generation time',
    member_count: batch.total,
    member_uuids: batch.results.map((result) => result.member.id),
  }, null, 2);

  const buildPayrollPackageManifestCsv = (batch) => {
    const columns = ['generated_at', 'actor_id', 'business_id', 'report', 'year', 'generation_path', 'trust_level', 'trust_note', 'status', 'path', 'sha256', 'member_id', 'member_name', 'error'];
    return [
      columns.join(','),
      ...batch.manifest.map((row) => columns.map((column) => csvEscape(row[column])).join(',')),
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

  const updatePayrollPackageSummary = (batch) => {
    const setText = (id, value) => {
      const element = document.getElementById(id);
      if (element instanceof HTMLElement) {
        element.textContent = String(value);
      }
    };

    setText('business_payroll_package_summary_members', batch.total);
    setText('business_payroll_package_summary_year', batch.year);
    setText('business_payroll_package_summary_files', batch.files.length);
    setText('business_payroll_package_summary_completed', `${batch.generated} / ${batch.total}`);
    setText('business_payroll_package_summary_exceptions', batch.exceptionCount);
    setText('business_payroll_package_summary_time', `${(batch.durationMs / 1000).toFixed(1)} ${T.businessExportSeconds}`);

    const zipButton = document.getElementById('business_payroll_package_download_zip');
    if (zipButton instanceof HTMLButtonElement) {
      zipButton.disabled = batch.files.length === 0;
    }
    const manifestButton = document.getElementById('business_payroll_package_download_manifest');
    if (manifestButton instanceof HTMLButtonElement) {
      manifestButton.disabled = batch.manifest.length === 0;
    }
    setPayrollPackageSummaryVisible(true);
  };

  const payrollPackageFilename = (batch) => `paycal-payroll-package-${batch.year}.zip`;

  const downloadPayrollPackageZip = async (batch) => {
    downloadBlob(await createZipBlob(batch.files), payrollPackageFilename(batch));
  };

  const generatePayrollPackage = async () => {
    if (payrollPackageRunning) {
      return;
    }

    const orgId = resolvePayrollPackageOrgId();
    if (orgId === '') {
      PC.showToast(T.noBusinessSelected, 'error', 6000, true);
      return;
    }

    const year = selectedPayrollPackageYear();
    const members = await resolvePayrollPackageMembers(orgId);
    if (members.length === 0) {
      PC.showToast(T.payrollPackageNoActiveMembers, 'error', 7000, true);
      return;
    }

    if (members.length > PAYROLL_PACKAGE_CONFIRM_THRESHOLD && !window.confirm(T.payrollPackageConfirm.replace('%d', String(members.length)))) {
      return;
    }

    const button = document.getElementById('business_payroll_package_generate');
    const originalText = button instanceof HTMLButtonElement ? button.textContent : '';
    if (button instanceof HTMLButtonElement) {
      button.disabled = true;
      button.textContent = T.businessExportGenerating;
    }

    payrollPackageRunning = true;
    payrollPackageLastBatch = null;
    setPayrollPackageSummaryVisible(false);
    const startedAt = performance.now();
    const batch = {
      orgId,
      year,
      generatedAt: new Date().toISOString(),
      total: members.length,
      actorId: typeof currentUserUUID === 'string' ? currentUserUUID : '',
      reportKey: 'payroll_package',
      generationPath: 'mixed_server_authorized_and_browser_convenience',
      trustLevel: 'mixed_package_server_authorized_pdf_and_browser_convenience_csv',
      trustNote: 'PDF reports are server-authorized; CSV files are browser convenience exports from authorized report data.',
      generated: 0,
      failed: 0,
      exceptionCount: 0,
      durationMs: 0,
      files: [],
      manifest: [],
      results: [],
    };
    const memberRows = [];
    const failures = [];

    try {
      await recordMembersReportAudit({
        ...batch,
        scope: 'yearly',
        format: 'zip',
        delivery: 'payroll-package',
        results: members.map((member) => ({ member })),
      }, 'requested');
      await recordMembersReportAudit({
        ...batch,
        scope: 'yearly',
        format: 'zip',
        delivery: 'payroll-package',
        results: members.map((member) => ({ member })),
      }, 'started');

      for (let index = 0; index < members.length; index += 1) {
        const member = members[index];
        setPayrollPackageStatus(
          T.payrollPackageProgress
            .replace('%d', String(index + 1))
            .replace('%d', String(members.length))
            .replace('%s', member.name),
        );
        try {
          const dailyPayload = await fetchMemberDailyPayloadForReport(orgId, member.id, year);
          const rows = EarningsExport.buildDetailedRows(dailyPayload);
          if (!rows.length) {
            throw new Error('No report rows available.');
          }
          const report = buildSelectedMemberReport('yearly', year, member, rows);
          const pdf = await postProtectedMemberReportBlob(orgId, member.id, 'yearly', 'pdf', year);
          const csv = EarningsExport.generateYearlyCsv(rows, report);
          const name = sanitizeMemberReportFilenamePart(member.name);
          batch.files.push({
            filename: `pdf/PayCal-${year}-Yearly-Report-${name}.pdf`,
            blob: pdf,
            member,
            generationPath: 'server_authorized',
            trustLevel: 'server_authorized_artifact',
          });
          batch.files.push({
            filename: `csv/PayCal-${year}-Yearly-Report-${name}.csv`,
            blob: new Blob([csv], { type: 'text/csv;charset=utf-8' }),
            member,
            generationPath: 'browser_convenience_from_authorized_report_data',
            trustLevel: 'convenience_browser_export',
          });
          memberRows.push({ member, rows });
          batch.results.push({ member, status: 'succeeded' });
          batch.generated += 1;
        } catch (error) {
          const message = error instanceof Error && error.message ? error.message : T.payrollPackageMemberReportFailed;
          failures.push({ member, error: message });
          batch.results.push({ member, status: 'failed', error: message });
          batch.failed += 1;
          PW.error(error);
        }
      }

      const exceptionsCsv = buildPayrollExceptionsCsv(memberRows, failures);
      batch.exceptionCount = Math.max(0, exceptionsCsv.split('\n').length - 1);
      batch.files.push({
        filename: 'exports/payroll-import.csv',
        blob: new Blob([buildPayrollPackageCsv(memberRows)], { type: 'text/csv;charset=utf-8' }),
        generationPath: 'browser_convenience_from_authorized_report_data',
        trustLevel: 'convenience_browser_export',
      });
      batch.files.push({
        filename: 'summaries/site-summary.csv',
        blob: new Blob([buildPayrollSiteSummaryCsv(memberRows)], { type: 'text/csv;charset=utf-8' }),
        generationPath: 'browser_convenience_from_authorized_report_data',
        trustLevel: 'convenience_browser_export',
      });
      batch.files.push({
        filename: 'exceptions/exceptions.csv',
        blob: new Blob([exceptionsCsv], { type: 'text/csv;charset=utf-8' }),
        generationPath: 'browser_convenience_from_authorized_report_data',
        trustLevel: 'convenience_browser_export',
      });
      batch.files.push({
        filename: 'consent/consent-snapshot.json',
        blob: new Blob([buildPayrollConsentSnapshot(batch)], { type: 'application/json;charset=utf-8' }),
        generationPath: 'package_metadata',
        trustLevel: 'package_metadata',
      });
      batch.files.push({
        filename: 'README.txt',
        blob: new Blob([buildPayrollPackageReadme(batch)], { type: 'text/plain;charset=utf-8' }),
        generationPath: 'package_metadata',
        trustLevel: 'package_metadata',
      });

      for (const file of batch.files) {
        const hash = await hashBlobSha256(file.blob);
        batch.manifest.push({
          generated_at: batch.generatedAt,
          actor_id: batch.actorId,
          business_id: batch.orgId,
          report: batch.reportKey,
          year: batch.year,
          generation_path: file.generationPath || 'package_metadata',
          trust_level: file.trustLevel || 'package_metadata',
          trust_note: batch.trustNote,
          status: 'included',
          path: file.filename,
          sha256: hash,
          member_id: file.member?.id || '',
          member_name: file.member?.name || '',
          error: '',
        });
      }
      failures.forEach((failure) => {
        batch.manifest.push({
          generated_at: batch.generatedAt,
          actor_id: batch.actorId,
          business_id: batch.orgId,
          report: batch.reportKey,
          year: batch.year,
          generation_path: 'not_generated',
          trust_level: 'not_generated',
          trust_note: batch.trustNote,
          status: 'failed',
          path: '',
          sha256: '',
          member_id: failure.member.id,
          member_name: failure.member.name,
          error: failure.error,
        });
      });

      const manifestJson = buildPayrollPackageManifestJson(batch);
      const manifestCsv = buildPayrollPackageManifestCsv(batch);
      batch.files.push({ filename: 'manifest.json', blob: new Blob([manifestJson], { type: 'application/json;charset=utf-8' }) });
      batch.files.push({ filename: 'manifest.csv', blob: new Blob([manifestCsv], { type: 'text/csv;charset=utf-8' }) });
      batch.files.push({
        filename: 'audit/audit-manifest.json',
        blob: new Blob([manifestJson], { type: 'application/json;charset=utf-8' }),
      });
      batch.files.push({
        filename: 'audit/audit-manifest.csv',
        blob: new Blob([manifestCsv], { type: 'text/csv;charset=utf-8' }),
      });
      const hashLines = batch.manifest
        .filter((row) => row.sha256 !== '' && row.path !== '')
        .map((row) => `${row.sha256}  ${row.path}`)
        .join('\n');
      batch.files.push({ filename: 'SHA256SUMS.txt', blob: new Blob([hashLines + '\n'], { type: 'text/plain;charset=utf-8' }) });

      batch.durationMs = Math.round(performance.now() - startedAt);
      payrollPackageLastBatch = batch;
      await downloadPayrollPackageZip(batch);
      await recordMembersReportAudit({
        orgId,
        reportKey: batch.reportKey,
        scope: 'yearly',
        format: 'zip',
        delivery: 'payroll-package',
        year,
        actorId: batch.actorId,
        generationPath: batch.generationPath,
        trustLevel: batch.trustLevel,
        total: batch.total,
        generated: batch.generated,
        failed: batch.failed,
        durationMs: batch.durationMs,
        generatedAt: batch.generatedAt,
        results: batch.results,
      });
      updatePayrollPackageSummary(batch);
      const message = batch.failed > 0
        ? T.payrollPackageGeneratedFailed
          .replace('%d', String(batch.generated))
          .replace('%d', String(batch.failed))
        : T.payrollPackageGeneratedSuccess.replace('%d', String(batch.generated));
      setPayrollPackageStatus(message);
      PC.showToast(message, batch.failed > 0 ? 'error' : 'save', 8000, true);
    } finally {
      payrollPackageRunning = false;
      if (button instanceof HTMLButtonElement) {
        button.disabled = false;
        button.textContent = originalText || T.payrollPackageGenerate;
      }
    }
  };

  const syncBusinessReportsPanelFromDom = () => {
    const perf = resolveBusinessReportsLensPerf();
    const sync = () => {
      const panelShell = document.querySelector('.business_reports_panel_shell');
      const panel = panelShell?.querySelector('.earnings_team_panel') ?? null;
      if (!(panel instanceof HTMLElement)) {
        return;
      }

      if (panel.dataset.reportsPanelLoading === '1') {
        announceBusinessReportsStatus(T.reportsLoadingAnalytics);
        return;
      }

      if (canHydrateBusinessReportsPanelFromSsr()) {
        panel.removeAttribute('aria-busy');
      }

      if (panel.querySelector('.et_empty_state, .business_reports_error')) {
        announceBusinessReportsStatus(T.reportsAnalyticsLoadedStatus);
        return;
      }

      announceBusinessReportsStatus(T.reportsAnalyticsLoadedStatus);
    };

    if (perf?.isEnabled()) {
      perf.measureSync('syncBusinessReportsPanelFromDom', sync);
      return;
    }

    sync();
  };

  if (isBusinessReportsSubPage()) {
    logBusinessReportsLensPageDebug();
    logBusinessReportsLensDebug();
    syncBusinessReportsPanelFromDom();

    const generateButton = document.getElementById('business_payroll_package_generate');
    if (generateButton instanceof HTMLButtonElement && generateButton.dataset.payrollPackageBound !== '1') {
      generateButton.dataset.payrollPackageBound = '1';
      generateButton.addEventListener('click', () => {
        generatePayrollPackage().catch((error) => {
          PW.error(error);
          const message = error instanceof Error && error.message ? error.message : T.payrollPackageGenerateFailed;
          setPayrollPackageStatus(message);
          PC.showToast(message, 'error', 8000, true);
        });
      });
    }

    const zipButton = document.getElementById('business_payroll_package_download_zip');
    if (zipButton instanceof HTMLButtonElement && zipButton.dataset.payrollPackageZipBound !== '1') {
      zipButton.dataset.payrollPackageZipBound = '1';
      zipButton.addEventListener('click', () => {
        if (payrollPackageLastBatch !== null) {
          downloadPayrollPackageZip(payrollPackageLastBatch).catch((error) => {
            PW.error(error);
            PC.showToast(T.payrollPackageDownloadZipFailed, 'error', 8000, true);
          });
        }
      });
    }

    const manifestButton = document.getElementById('business_payroll_package_download_manifest');
    if (manifestButton instanceof HTMLButtonElement && manifestButton.dataset.payrollPackageManifestBound !== '1') {
      manifestButton.dataset.payrollPackageManifestBound = '1';
      manifestButton.addEventListener('click', () => {
        if (payrollPackageLastBatch !== null) {
          downloadBlob(
            new Blob([buildPayrollPackageManifestCsv(payrollPackageLastBatch)], { type: 'text/csv;charset=utf-8' }),
            `paycal-payroll-package-${payrollPackageLastBatch.year}-manifest.csv`,
          );
        }
      });
    }
  }
