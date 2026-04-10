const RuntimeIntegrity = (() => {
  let started = false;
  let timer = null;
  let currentState = 'SAFE';

  const BASELINE = {
    fetchRef: null,
    xhrOpenRef: null,
    iframeCount: 0,
    fullscreenElement: null,
  };

  function safeReport(report, type, data) {
    try {
      if (typeof report === 'function') {
        report(type, data);
      }
    } catch {
      // Keep integrity checks non-fatal.
    }
  }

  function buildRiskResult() {
    const drifts = [];

    if (window.fetch !== BASELINE.fetchRef) {
      drifts.push('fetch_monkeypatch_detected');
    }

    if (window.XMLHttpRequest && window.XMLHttpRequest.prototype.open !== BASELINE.xhrOpenRef) {
      drifts.push('xhr_open_monkeypatch_detected');
    }

    const iframes = document.querySelectorAll('iframe');
    if (iframes.length > BASELINE.iframeCount) {
      drifts.push('unexpected_iframe_growth');
    }

    const overlay = detectSuspiciousOverlay();
    if (overlay) {
      drifts.push(overlay);
    }

    const riskScore = Math.min(100, drifts.length * 25);

    return {
      riskScore,
      drifts,
      state: scoreToState(riskScore),
      iframeCount: iframes.length,
      timestamp: new Date().toISOString(),
    };
  }

  function scoreToState(score) {
    if (score >= 75) {
      return 'TERMINATED';
    }
    if (score >= 50) {
      return 'LOCKED';
    }
    if (score >= 25) {
      return 'DEGRADED';
    }
    return 'SAFE';
  }

  function applyState(nextState, riskScore, report, drifts) {
    if (nextState === currentState) {
      return;
    }

    const from = currentState;
    currentState = nextState;
    document.body?.setAttribute('data-runtime-risk-state', nextState.toLowerCase());

    safeReport(report, 'runtime_integrity_state_transition', {
      from,
      to: nextState,
      riskScore,
      drifts,
      timestamp: new Date().toISOString(),
    });
  }

  function detectSuspiciousOverlay() {
    const nodes = document.querySelectorAll('body *');
    for (const node of nodes) {
      if (!(node instanceof HTMLElement)) {
        continue;
      }

      const style = window.getComputedStyle(node);
      if (style.position !== 'fixed') {
        continue;
      }

      if (style.pointerEvents === 'none' || style.visibility === 'hidden' || style.display === 'none') {
        continue;
      }

      const z = Number.parseInt(style.zIndex || '0', 10);
      if (!Number.isFinite(z) || z < 999) {
        continue;
      }

      const rect = node.getBoundingClientRect();
      const coversViewport = rect.width >= window.innerWidth * 0.9 && rect.height >= window.innerHeight * 0.9;
      if (coversViewport) {
        return 'fullscreen_overlay_detected';
      }
    }

    return '';
  }

  function snapshotBaseline() {
    BASELINE.fetchRef = window.fetch;
    BASELINE.xhrOpenRef = window.XMLHttpRequest ? window.XMLHttpRequest.prototype.open : null;
    BASELINE.iframeCount = document.querySelectorAll('iframe').length;
    BASELINE.fullscreenElement = document.fullscreenElement || null;
  }

  function start({ intervalMs = 10000, report } = {}) {
    if (started || typeof window === 'undefined' || typeof document === 'undefined') {
      return;
    }

    started = true;
    snapshotBaseline();

    safeReport(report, 'runtime_integrity_boot', {
      iframeCount: BASELINE.iframeCount,
      timestamp: new Date().toISOString(),
    });

    timer = window.setInterval(() => {
      const result = buildRiskResult();
      applyState(result.state, result.riskScore, report, result.drifts);
      if (result.drifts.length > 0) {
        safeReport(report, 'runtime_integrity_drift', result);
      }
    }, intervalMs);
  }

  function stop() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
    started = false;
  }

  return {
    start,
    stop,
  };
})();

export default RuntimeIntegrity;
