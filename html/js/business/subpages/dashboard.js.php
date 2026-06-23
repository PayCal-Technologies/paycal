
  // Subpage module: dashboard (data-business-subpage="dashboard")
  // Entry: refreshDashboardWorkspace in refreshIndex when subpage is dashboard

  const animateDashboardMetricCounts = (root = document) => {
    if (resolveBusinessSubPage() !== 'dashboard') {
      return;
    }

    const motionReduced = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches === true;
    const compactViewport = window.matchMedia?.('(max-width: 768px)')?.matches === true;
    if (compactViewport) {
      return;
    }

    const metrics = root.querySelectorAll('[data-count-up-metric="1"]');
    metrics.forEach((metric) => {
      if (!(metric instanceof HTMLElement) || metric.dataset.countUpReady === '1') {
        return;
      }

      const original = String(metric.textContent || '').trim();
      const numericMatch = original.match(/-?\d[\d,]*(?:\.\d+)?/);
      if (!numericMatch) {
        return;
      }

      const target = Number(numericMatch[0].replace(/,/g, ''));
      if (!Number.isFinite(target) || target < 1) {
        return;
      }

      metric.dataset.countUpReady = '1';
      if (motionReduced) {
        return;
      }

      const prefix = original.slice(0, numericMatch.index);
      const suffix = original.slice((numericMatch.index || 0) + numericMatch[0].length);
      const decimals = numericMatch[0].includes('.') ? numericMatch[0].split('.')[1].length : 0;
      const formatter = new Intl.NumberFormat(PC.config?.USER_LOCALE || undefined, {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals,
      });
      const duration = 700;
      const start = performance.now();

      const renderFrame = (now) => {
        const progress = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - progress, 3);
        metric.textContent = `${prefix}${formatter.format(target * eased)}${suffix}`;

        if (progress < 1) {
          window.requestAnimationFrame(renderFrame);
          return;
        }

        metric.textContent = original;
      };

      metric.textContent = `${prefix}${formatter.format(0)}${suffix}`;
      window.requestAnimationFrame(renderFrame);
    });
  };

  const refreshDashboardWorkspace = async (businessId) => {
    if (resolveBusinessSubPage() !== 'dashboard') {
      return;
    }

    syncBusinessWorkspaceElementRefs();

    const orgId = String(businessId || '').trim();
    if (orgId === '') {
      return;
    }

    applySingleBusinessOverviewMode();
    animateDashboardMetricCounts();
    warmBusinessWorkspaceCache(orgId);
  };
