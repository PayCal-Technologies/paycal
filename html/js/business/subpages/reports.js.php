
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
    setPlainStatusText(status, message);
  };

  const setPayrollPackageStatus = (message = '') => {
    const status = document.getElementById('business_payroll_package_status');
    setPlainStatusText(status, message);
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
    const payload = await apiRequest(`/api/v1/businesses/${encodeURIComponent(orgId)}/connections`);
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

    if (members.length > PAYROLL_PACKAGE_CONFIRM_THRESHOLD && !window.confirm(formatPhpTemplate(T.payrollPackageConfirm, [members.length]))) {
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
        setPayrollPackageStatus(formatPhpTemplate(T.payrollPackageProgress, [
          index + 1,
          members.length,
          member.name,
        ]));
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
        ? formatPhpTemplate(T.payrollPackageGeneratedFailed, [batch.generated, batch.failed])
        : formatPhpTemplate(T.payrollPackageGeneratedSuccess, [batch.generated]);
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

  const BUSINESS_REPORT_TABS = ['overview', 'payroll', 'workforce', 'sites', 'groups', 'risks'];
  const BUSINESS_REPORT_PRESETS = {
    executive: ['primary-kpis', 'alerts-financial', 'payroll-trend', 'hours-overtime-trend', 'cost-drivers', 'risk-register'],
    payroll: ['primary-kpis', 'forecast', 'budget-status', 'payroll-trend', 'payroll-composition', 'risk-register'],
    workforce: ['primary-kpis', 'workforce-health', 'alerts-workforce', 'workforce-overview', 'hours-overtime-trend', 'risk-register'],
    'site-manager': ['primary-kpis', 'alerts-operations', 'site-planning', 'site-payroll-cost', 'risk-register'],
  };
  let businessReportDefaultOrder = [];

  const reportStorageKey = (suffix) => {
    const workspace = document.getElementById('business-workspace');
    const businessId = workspace instanceof HTMLElement
      ? String(workspace.dataset.selectedBusinessId || workspace.dataset.businessId || 'business').trim()
      : 'business';
    return `paycal.businessReports.${businessId || 'business'}.${suffix}`;
  };

  const readReportPrefs = () => {
    try {
      const parsed = JSON.parse(localStorage.getItem(reportStorageKey('layout')) || '{}');
      return parsed && typeof parsed === 'object' ? parsed : {};
    } catch {
      return {};
    }
  };

  const writeReportPrefs = (prefs) => {
    localStorage.setItem(reportStorageKey('layout'), JSON.stringify(prefs));
  };

  const reportModules = () => Array.from(document.querySelectorAll('[data-report-module]'))
    .filter((module) => module instanceof HTMLElement);

  const reportModuleKey = (module) => String(module.dataset.reportModule || '').trim();

  const applyReportVisibility = () => {
    const params = new URLSearchParams(window.location.search);
    const prefs = readReportPrefs();
    const activeTab = BUSINESS_REPORT_TABS.includes(params.get('tab') || '')
      ? params.get('tab')
      : (BUSINESS_REPORT_TABS.includes(prefs.tab) ? prefs.tab : 'overview');
    const hiddenModules = new Set(Array.isArray(prefs.hiddenModules) ? prefs.hiddenModules : []);
    const exceptionsOnly = params.get('exceptions') === '1' || prefs.exceptionsOnly === true;

    document.querySelectorAll('[data-report-tab-button]').forEach((button) => {
      if (!(button instanceof HTMLButtonElement)) {
        return;
      }
      const isActive = String(button.dataset.reportTabButton || '') === activeTab;
      button.classList.toggle('active', isActive);
      button.setAttribute('aria-selected', isActive ? 'true' : 'false');
    });

    reportModules().forEach((module) => {
      const key = reportModuleKey(module);
      const tab = String(module.dataset.reportTab || 'overview');
      const exceptionModule = key.includes('risk') || key.includes('alert');
      module.hidden = tab !== activeTab || hiddenModules.has(key) || (exceptionsOnly && !exceptionModule);
    });

    document.querySelectorAll('.et_reports_panel_row, .et_intel_row').forEach((row) => {
      if (!(row instanceof HTMLElement)) {
        return;
      }
      const childModules = Array.from(row.querySelectorAll('[data-report-module]'));
      row.hidden = childModules.length > 0 && childModules.every((module) => module instanceof HTMLElement && module.hidden);
    });

    const exceptionsInput = document.querySelector('[data-report-filter="exceptions"]');
    if (exceptionsInput instanceof HTMLInputElement) {
      exceptionsInput.checked = exceptionsOnly;
    }
  };

  const persistReportParams = (updates) => {
    const params = new URLSearchParams(window.location.search);
    Object.entries(updates).forEach(([key, value]) => {
      const stringValue = String(value ?? '').trim();
      if (stringValue === '' || stringValue === '0') {
        params.delete(key);
      } else {
        params.set(key, stringValue);
      }
    });
    const nextUrl = `${window.location.pathname}${params.toString() ? `?${params.toString()}` : ''}${window.location.hash}`;
    window.history.replaceState({}, '', nextUrl);
  };

  const moveModule = (key, direction) => {
    const modules = reportModules();
    const module = modules.find((item) => reportModuleKey(item) === key);
    if (!(module instanceof HTMLElement)) {
      return;
    }
    const sibling = direction < 0 ? module.previousElementSibling : module.nextElementSibling;
    if (!(sibling instanceof HTMLElement) || !sibling.matches('[data-report-module]')) {
      return;
    }
    if (direction < 0) {
      module.parentNode?.insertBefore(module, sibling);
    } else {
      module.parentNode?.insertBefore(sibling, module);
    }
    const prefs = readReportPrefs();
    prefs.order = reportModules().map(reportModuleKey);
    writeReportPrefs(prefs);
    buildCustomizeModuleList();
    applyReportVisibility();
  };

  const applySavedReportOrder = () => {
    const prefs = readReportPrefs();
    if (!Array.isArray(prefs.order) || prefs.order.length === 0) {
      return;
    }
    const modules = new Map(reportModules().map((module) => [reportModuleKey(module), module]));
    const anchor = document.querySelector('.business_reports_panel_shell .earnings_team_panel');
    if (!(anchor instanceof HTMLElement)) {
      return;
    }
    prefs.order.forEach((key) => {
      const module = modules.get(key);
      if (module instanceof HTMLElement) {
        anchor.appendChild(module);
      }
    });
  };

  const applyDefaultReportOrder = () => {
    if (businessReportDefaultOrder.length === 0) {
      return;
    }
    const modules = new Map(reportModules().map((module) => [reportModuleKey(module), module]));
    const anchor = document.querySelector('.business_reports_panel_shell .earnings_team_panel');
    if (!(anchor instanceof HTMLElement)) {
      return;
    }
    businessReportDefaultOrder.forEach((key) => {
      const module = modules.get(key);
      if (module instanceof HTMLElement) {
        anchor.appendChild(module);
      }
    });
  };

  function buildCustomizeModuleList() {
    const list = document.querySelector('[data-report-module-list]');
    if (!(list instanceof HTMLElement)) {
      return;
    }
    const prefs = readReportPrefs();
    const hiddenModules = new Set(Array.isArray(prefs.hiddenModules) ? prefs.hiddenModules : []);
    list.textContent = '';
    reportModules().forEach((module) => {
      const key = reportModuleKey(module);
      const row = document.createElement('div');
      row.className = 'business_reports_module_item';
      row.draggable = true;
      row.dataset.moduleKey = key;
      row.addEventListener('dragstart', (event) => {
        event.dataTransfer?.setData('text/plain', key);
      });
      row.addEventListener('dragover', (event) => {
        event.preventDefault();
      });
      row.addEventListener('drop', (event) => {
        event.preventDefault();
        const draggedKey = event.dataTransfer?.getData('text/plain') || '';
        if (draggedKey === '' || draggedKey === key) {
          return;
        }
        const modules = new Map(reportModules().map((item) => [reportModuleKey(item), item]));
        const draggedModule = modules.get(draggedKey);
        const targetModule = modules.get(key);
        if (draggedModule instanceof HTMLElement && targetModule instanceof HTMLElement) {
          targetModule.parentNode?.insertBefore(draggedModule, targetModule);
          const prefs = readReportPrefs();
          prefs.order = reportModules().map(reportModuleKey);
          writeReportPrefs(prefs);
          buildCustomizeModuleList();
          applyReportVisibility();
        }
      });

      const label = document.createElement('label');
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.checked = !hiddenModules.has(key);
      checkbox.addEventListener('change', () => {
        const nextPrefs = readReportPrefs();
        const nextHidden = new Set(Array.isArray(nextPrefs.hiddenModules) ? nextPrefs.hiddenModules : []);
        if (checkbox.checked) {
          nextHidden.delete(key);
        } else {
          nextHidden.add(key);
        }
        nextPrefs.hiddenModules = Array.from(nextHidden);
        writeReportPrefs(nextPrefs);
        applyReportVisibility();
      });
      label.append(checkbox, ` ${module.dataset.reportTitle || key}`);

      const up = document.createElement('button');
      up.type = 'button';
      up.className = 'btn btn_secondary btn_compact';
      up.textContent = 'Up';
      up.addEventListener('click', () => moveModule(key, -1));

      const down = document.createElement('button');
      down.type = 'button';
      down.className = 'btn btn_secondary btn_compact';
      down.textContent = 'Down';
      down.addEventListener('click', () => moveModule(key, 1));

      row.append(label, up, down);
      list.appendChild(row);
    });
  }

  const markSparseReportCharts = () => {
    reportModules().forEach((module) => {
      if (!(module instanceof HTMLElement) || module.querySelectorAll('.ytd_axis_label--x').length >= 3) {
        return;
      }
      if (!module.querySelector('.earnings_ytd_svg')) {
        return;
      }
      module.classList.add('business_reports_module--insufficient-history');
      if (!module.querySelector('.business_reports_insufficient_history')) {
        const note = document.createElement('p');
        note.className = 'business_reports_insufficient_history';
        note.textContent = 'Insufficient history for a meaningful chart.';
        module.appendChild(note);
      }
    });
  };

  const exportVisibleReportCsv = () => {
    const csvRows = [['module', 'field', 'value']];
    reportModules().forEach((module) => {
      if (!(module instanceof HTMLElement) || module.hidden || !module.dataset.groupRows) {
        return;
      }
      let rows = [];
      try {
        rows = JSON.parse(module.dataset.groupRows);
      } catch {
        rows = [];
      }
      if (!Array.isArray(rows)) {
        return;
      }
      const moduleName = module.dataset.reportTitle || module.dataset.groupType || reportModuleKey(module);
      rows.forEach((row, index) => {
        if (!row || typeof row !== 'object') {
          return;
        }
        Object.entries(row).forEach(([field, value]) => {
          csvRows.push([`${moduleName} ${index + 1}`, field, value]);
        });
      });
    });

    if (csvRows.length === 1) {
      PC.showToast('No visible export rows are available for this view.', 'error', 5000, true);
      return;
    }

    const csv = csvRows.map(reportsCsvRow).join('\n');
    const year = document.getElementById('business-workspace')?.dataset.groupReportsYear || String(new Date().getFullYear());
    downloadBlob(new Blob([csv], { type: 'text/csv;charset=utf-8' }), `paycal-business-reports-${year}.csv`);
  };

  const initBusinessReportHub = () => {
    businessReportDefaultOrder = reportModules().map(reportModuleKey);
    applySavedReportOrder();
    markSparseReportCharts();
    buildCustomizeModuleList();
    applyReportVisibility();

    document.querySelectorAll('[data-report-tab-button]').forEach((button) => {
      if (!(button instanceof HTMLButtonElement) || button.dataset.reportHubBound === '1') {
        return;
      }
      button.dataset.reportHubBound = '1';
      button.addEventListener('click', () => {
        const tab = String(button.dataset.reportTabButton || 'overview');
        const prefs = readReportPrefs();
        prefs.tab = tab;
        writeReportPrefs(prefs);
        persistReportParams({ tab });
        applyReportVisibility();
      });
    });

    document.querySelectorAll('[data-report-filter]').forEach((filter) => {
      if (!(filter instanceof HTMLInputElement || filter instanceof HTMLSelectElement) || filter.dataset.reportFilterBound === '1') {
        return;
      }
      filter.dataset.reportFilterBound = '1';
      const key = String(filter.dataset.reportFilter || '');
      const params = new URLSearchParams(window.location.search);
      const existingValue = params.get(key);
      if (existingValue !== null && filter instanceof HTMLSelectElement) {
        filter.value = existingValue;
      }
      filter.addEventListener('change', () => {
        if (key === 'year' && filter instanceof HTMLSelectElement && filter.value !== '') {
          persistReportParams({ year: filter.value });
          window.location.assign(window.location.href);
          return;
        }
        const value = filter instanceof HTMLInputElement && filter.type === 'checkbox'
          ? (filter.checked ? '1' : '')
          : filter.value;
        const prefs = readReportPrefs();
        if (key === 'exceptions') {
          prefs.exceptionsOnly = value === '1';
        }
        writeReportPrefs(prefs);
        persistReportParams({ [key]: value });
        applyReportVisibility();
      });
    });

    const customizeDrawer = document.querySelector('[data-report-customize-drawer]');
    document.querySelector('[data-report-customize-open]')?.addEventListener('click', () => {
      if (customizeDrawer instanceof HTMLElement) {
        customizeDrawer.hidden = false;
      }
    });
    document.querySelector('[data-report-customize-close]')?.addEventListener('click', () => {
      if (customizeDrawer instanceof HTMLElement) {
        customizeDrawer.hidden = true;
      }
    });

    const exportDrawer = document.querySelector('[data-report-export-drawer]');
    const exportPanel = document.querySelector('[data-report-export-panel]');
    document.querySelector('[data-report-export-open]')?.addEventListener('click', () => {
      if (exportDrawer instanceof HTMLElement) {
        exportDrawer.hidden = false;
      }
      if (exportPanel instanceof HTMLElement) {
        exportPanel.hidden = false;
      }
    });
    document.querySelector('[data-report-export-close]')?.addEventListener('click', () => {
      if (exportDrawer instanceof HTMLElement) {
        exportDrawer.hidden = true;
      }
      if (exportPanel instanceof HTMLElement) {
        exportPanel.hidden = true;
      }
    });
    document.querySelector('[data-report-export-zip-focus]')?.addEventListener('click', () => {
      if (exportPanel instanceof HTMLElement) {
        exportPanel.hidden = false;
        exportPanel.scrollIntoView({ block: 'start', behavior: 'smooth' });
      }
    });
    document.querySelector('[data-report-export-csv]')?.addEventListener('click', exportVisibleReportCsv);

    document.querySelectorAll('[data-report-preset]').forEach((button) => {
      if (!(button instanceof HTMLButtonElement)) {
        return;
      }
      button.addEventListener('click', () => {
        const preset = BUSINESS_REPORT_PRESETS[String(button.dataset.reportPreset || '')] || BUSINESS_REPORT_PRESETS.executive;
        const prefs = readReportPrefs();
        const allKeys = reportModules().map(reportModuleKey);
        prefs.hiddenModules = allKeys.filter((key) => !preset.includes(key));
        prefs.order = [...preset, ...allKeys.filter((key) => !preset.includes(key))];
        writeReportPrefs(prefs);
        applySavedReportOrder();
        buildCustomizeModuleList();
        applyReportVisibility();
      });
    });

    document.querySelector('[data-report-save-view]')?.addEventListener('click', () => {
      const input = document.querySelector('[data-report-view-name]');
      const prefs = readReportPrefs();
      prefs.name = input instanceof HTMLInputElement ? input.value.trim() : '';
      prefs.order = reportModules().map(reportModuleKey);
      writeReportPrefs(prefs);
      PC.showToast('Report view saved.', 'save', 3500, true);
    });

    document.querySelector('[data-report-reset-view]')?.addEventListener('click', () => {
      localStorage.removeItem(reportStorageKey('layout'));
      applyDefaultReportOrder();
      buildCustomizeModuleList();
      applyReportVisibility();
    });
  };

  if (isBusinessReportsSubPage()) {
    logBusinessReportsLensPageDebug();
    logBusinessReportsLensDebug();
    syncBusinessReportsPanelFromDom();
    initBusinessReportHub();

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
