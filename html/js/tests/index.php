<?php declare(strict_types=1);

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

let activeTestStream = null;
let isStoppingTestStream = false;

async function getCapabilityToken(action) {
  const response = await fetch(`${PC.config.pc_api}/admin/capability/${encodeURIComponent(action)}`, {
    method: 'GET',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  });

  const payload = await response.json();
  if (!response.ok || payload.status !== 'success') {
    throw new Error(payload.message || 'Capability token request failed.');
  }

  const token = String(payload.capability?.token || '').trim();
  if (!token) {
    throw new Error('Capability token missing.');
  }

  return token;
}


/**
 * Test Dashboard JavaScript
 *
 * Handles test execution, results display, and report downloads.
 */

/**
 * Initialize Test Dashboard
 */
function initTestDashboard() {
  const runTestsBtn = document.getElementById('btn_run_tests');
  const stopTestsBtn = document.getElementById('btn_stop_tests');
  const downloadReportBtn = document.getElementById('btn_download_report');

  if (runTestsBtn) {
    runTestsBtn.addEventListener('click', handleRunTests);
  }

  if (stopTestsBtn) {
    stopTestsBtn.addEventListener('click', handleStopTests);
  }

  if (downloadReportBtn) {
    downloadReportBtn.addEventListener('click', handleDownloadReport);
  }
}

/**
 * Handle Run Tests Button Click
 */
async function handleRunTests() {
  const btn = document.getElementById('btn_run_tests');
  const stopBtn = document.getElementById('btn_stop_tests');
  const spinner = document.getElementById('test_spinner');
  const statusDiv = document.getElementById('status');
  const resultsTextarea = document.getElementById('test_results_output');
  const lastRunResultsDiv = document.getElementById('last_run_results');
  const finalResultCallout = document.getElementById('final_result_callout');
  const finalResultMessage = document.getElementById('final_result_message');

  if (activeTestStream !== null) {
    activeTestStream.close();
    activeTestStream = null;
  }
  
  btn.disabled = true;
  if (stopBtn) {
    stopBtn.disabled = false;
    stopBtn.classList.remove('hidden');
  }
  spinner.classList.remove('hidden');
  statusDiv.textContent = 'Running tests (live stream)...';
  statusDiv.className = 'status centered info';
  isStoppingTestStream = false;

  if (resultsTextarea) {
    resultsTextarea.value = '';
  }

  if (lastRunResultsDiv) {
    lastRunResultsDiv.textContent = 'Live run in progress...';
  }

  if (finalResultCallout && finalResultMessage) {
    finalResultCallout.classList.remove('hidden', 'success', 'error');
    finalResultCallout.classList.add('info');
    finalResultMessage.textContent = 'Run in progress...';
  }

  const passRateElement = document.getElementById('pass_rate');
  if (passRateElement) {
    passRateElement.textContent = '--';
  }
  
  try {
    const capabilityToken = await getCapabilityToken('admin.tests.run');
    const stream = new EventSource(`/ws/?channel=test_suite_stream&capability_token=${encodeURIComponent(capabilityToken)}`);
    activeTestStream = stream;

    stream.addEventListener('start', () => {
      statusDiv.textContent = 'Streaming PHPUnit output...';
      statusDiv.className = 'status centered info';
    });

    stream.addEventListener('line', (event) => {
      let payload = null;
      try {
        payload = JSON.parse(event.data);
      } catch (_err) {
        payload = null;
      }

      const line = String(payload?.text || '');
      if (resultsTextarea) {
        resultsTextarea.value += (resultsTextarea.value === '' ? '' : '\n') + line;
        resultsTextarea.scrollTop = resultsTextarea.scrollHeight;
      }
    });

    stream.addEventListener('done', (event) => {
      let data = null;
      try {
        data = JSON.parse(event.data);
      } catch (_err) {
        data = null;
      }

      const total = Number(data?.testCount || 0);
      const assertions = Number(data?.assertionCount || 0);
      const failures = Number(data?.failures || 0);
      const success = Boolean(data?.success);

      if (success || total > 0) {
        statusDiv.textContent = '✅ Tests completed! ' + total + ' tests, ' + assertions + ' assertions.';
        statusDiv.className = 'status centered success';

        if (finalResultCallout && finalResultMessage) {
          finalResultCallout.classList.remove('hidden', 'info', 'error');
          finalResultCallout.classList.add('success');
          finalResultMessage.textContent = 'Completed: ' + total + ' tests, ' + assertions + ' assertions, ' + failures + ' failures.';
        }
      } else {
        statusDiv.textContent = '❌ Test run failed';
        statusDiv.className = 'status centered error';

        if (finalResultCallout && finalResultMessage) {
          finalResultCallout.classList.remove('hidden', 'info', 'success');
          finalResultCallout.classList.add('error');
          finalResultMessage.textContent = 'Failed: no complete test totals were produced.';
        }
      }

      const passRate = total > 0 ? Math.round(((total - failures) / total) * 100) : 0;
      if (passRateElement) {
        passRateElement.textContent = passRate + '%';
      }

      if (lastRunResultsDiv) {
        lastRunResultsDiv.textContent = 'Last run: ' + total + ' tests, ' + assertions + ' assertions, ' + failures + ' failures (' + (data?.timestamp || 'just now') + ')';
      }

      const downloadBtn = document.getElementById('btn_download_report');
      if (downloadBtn) {
        downloadBtn.classList.remove('hidden');
      }

      btn.disabled = false;
      if (stopBtn) {
        stopBtn.disabled = true;
        stopBtn.classList.add('hidden');
      }
      spinner.classList.add('hidden');

      stream.close();
      if (activeTestStream === stream) {
        activeTestStream = null;
      }
    });

    stream.addEventListener('error', () => {
      if (isStoppingTestStream) {
        statusDiv.textContent = '⏹ Test run stopped.';
        statusDiv.className = 'status centered info';

        if (finalResultCallout && finalResultMessage) {
          finalResultCallout.classList.remove('hidden', 'success', 'error');
          finalResultCallout.classList.add('info');
          finalResultMessage.textContent = 'Stopped by user before completion.';
        }
      } else {
        statusDiv.textContent = '❌ Live stream connection failed.';
        statusDiv.className = 'status centered error';

        if (finalResultCallout && finalResultMessage) {
          finalResultCallout.classList.remove('hidden', 'info', 'success');
          finalResultCallout.classList.add('error');
          finalResultMessage.textContent = 'Stream failed before completion.';
        }
      }

      if (lastRunResultsDiv) {
        lastRunResultsDiv.textContent = isStoppingTestStream
          ? 'Live run was stopped by user.'
          : 'Live run failed before completion.';
      }

      btn.disabled = false;
      if (stopBtn) {
        stopBtn.disabled = true;
        stopBtn.classList.add('hidden');
      }
      spinner.classList.add('hidden');

      stream.close();
      if (activeTestStream === stream) {
        activeTestStream = null;
      }
    });
  } catch (error) {
    statusDiv.textContent = '❌ Error running tests: ' + error.message;
    statusDiv.className = 'status centered error';
  }
}

function handleStopTests() {
  const btn = document.getElementById('btn_run_tests');
  const stopBtn = document.getElementById('btn_stop_tests');
  const spinner = document.getElementById('test_spinner');
  const statusDiv = document.getElementById('status');
  const lastRunResultsDiv = document.getElementById('last_run_results');
  const finalResultCallout = document.getElementById('final_result_callout');
  const finalResultMessage = document.getElementById('final_result_message');

  if (activeTestStream === null) {
    return;
  }

  isStoppingTestStream = true;
  activeTestStream.close();
  activeTestStream = null;

  statusDiv.textContent = '⏹ Stopping test run...';
  statusDiv.className = 'status centered info';

  if (lastRunResultsDiv) {
    lastRunResultsDiv.textContent = 'Stopping live run...';
  }

  if (finalResultCallout && finalResultMessage) {
    finalResultCallout.classList.remove('hidden', 'success', 'error');
    finalResultCallout.classList.add('info');
    finalResultMessage.textContent = 'Stopping run...';
  }

  if (btn) {
    btn.disabled = false;
  }

  if (stopBtn) {
    stopBtn.disabled = true;
    stopBtn.classList.add('hidden');
  }

  if (spinner) {
    spinner.classList.add('hidden');
  }
}

/**
 * Handle Download Report Button Click
 */
async function handleDownloadReport() {
  try {
    // Fetch the latest test results
    const response = await fetch('<?php echo Environment::appURL('api/tests/results.php'); ?>');
    const data = await response.json();
    
    // Build JSON report
    const lastRun = data.lastRun || {};
    const report = {
      generatedAt: new Date().toISOString(),
      tests: {
        total: lastRun.testCount || data.metrics?.totalTests || 0,
        assertions: lastRun.assertionCount || data.metrics?.totalAssertions || 0,
        failures: lastRun.failures || 0,
      },
      timestamp: lastRun.timestamp || null,
      output: lastRun.output || ''
    };
    
    // Create blob from JSON
    const blob = new Blob([JSON.stringify(report, null, 2)], { type: 'application/json' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'test-report-' + new Date().toISOString().slice(0, 10) + '.json';
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
    
    const statusDiv = document.getElementById('status');
    statusDiv.textContent = '✅ Test report downloaded successfully';
    statusDiv.className = 'status centered success';
  } catch (error) {
    const statusDiv = document.getElementById('status');
    statusDiv.textContent = '❌ Error downloading report: ' + error.message;
    statusDiv.className = 'status centered error';
  }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initTestDashboard);
} else {
  initTestDashboard();
}
