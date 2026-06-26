<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
Javascript::renderModuleContentType('application/javascript');

Javascript::renderDocBlock();

$reportsPrintI18nKeys = [
  'CLOSE',
  'CANCEL',
  'EARNINGS_PRINT_REPORT',
  'REPORTS_PRINT_DIALOG_DESC',
  'REPORTS_PRINT_MODE_LEGEND',
  'REPORTS_PRINT_MODE_BW',
  'REPORTS_PRINT_MODE_GRAYSCALE',
  'REPORTS_PRINT_MODE_COLOR',
  'REPORTS_PRINT_MODE_BW_DESC',
  'REPORTS_PRINT_MODE_GRAYSCALE_DESC',
  'REPORTS_PRINT_MODE_COLOR_DESC',
];
$reportsPrintI18n = [];
foreach ($reportsPrintI18nKeys as $reportsPrintI18nKey) {
  $reportsPrintI18n[$reportsPrintI18nKey] = Strings::i18n($reportsPrintI18nKey);
}
?>
const REPORTS_PRINT_T = <?php echo json_encode($reportsPrintI18n, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
const REPORTS_PRINT_STORAGE_KEY = 'paycal.reports.printMode';
const REPORTS_PRINT_DEFAULT_MODE = 'bw';
const REPORTS_PRINT_MODES = new Set(['bw', 'grayscale', 'color']);
const REPORTS_PRINT_BYPASS_ATTR = 'data-reports-print-bypass';
const BUSINESS_REPORT_PDF_SELECTOR = '[data-group-export-format="pdf"], [data-team-export-format="pdf"]';
let reportsPrintPendingAction = null;

const isReportsPrintPage = () => {
  const path = window.location.pathname.replace(/\/+$/, '/') || '/';
  return path === '/reports/' || path === '/business/reports/';
};

const normalizePrintMode = (mode) => {
  const normalized = String(mode || '').trim().toLowerCase();
  return REPORTS_PRINT_MODES.has(normalized) ? normalized : REPORTS_PRINT_DEFAULT_MODE;
};

const readPrintMode = () => {
  try {
    return normalizePrintMode(window.localStorage.getItem(REPORTS_PRINT_STORAGE_KEY));
  } catch {
    return REPORTS_PRINT_DEFAULT_MODE;
  }
};

const writePrintMode = (mode) => {
  const normalized = normalizePrintMode(mode);
  document.documentElement.dataset.printMode = normalized;
  try {
    window.localStorage.setItem(REPORTS_PRINT_STORAGE_KEY, normalized);
  } catch {
    // Storage can be unavailable in private contexts; the data attribute is enough for this print.
  }
  return normalized;
};

const modeLabels = {
  bw: REPORTS_PRINT_T.REPORTS_PRINT_MODE_BW,
  grayscale: REPORTS_PRINT_T.REPORTS_PRINT_MODE_GRAYSCALE,
  color: REPORTS_PRINT_T.REPORTS_PRINT_MODE_COLOR,
};

const modeDescriptions = {
  bw: REPORTS_PRINT_T.REPORTS_PRINT_MODE_BW_DESC,
  grayscale: REPORTS_PRINT_T.REPORTS_PRINT_MODE_GRAYSCALE_DESC,
  color: REPORTS_PRINT_T.REPORTS_PRINT_MODE_COLOR_DESC,
};

const setReportsPrintMarkup = (target, markup) => {
  const guardian = window.Guardian;
  if (!guardian || typeof guardian.setHTML !== 'function') {
    throw new Error('Guardian module is required before reports print controls.');
  }
  guardian.setHTML(target, markup);
};

const findPanelPdfButton = (target) => {
  if (!(target instanceof Element)) {
    return null;
  }
  return target.closest(
    `[data-export-scope][data-export-format="pdf"], ${BUSINESS_REPORT_PDF_SELECTOR}`,
  );
};

const printAfterStyleFlush = (mode) => {
  document.documentElement.dataset.printMode = mode;
  document.documentElement.classList.add('reports_print_mode_ready');
  window.dispatchEvent(new CustomEvent('paycal:reports-print-mode-applied', { detail: { mode } }));
  // Force a layout read so Chrome's print preview sees the selected mode.
  void document.documentElement.offsetHeight;
  window.requestAnimationFrame(() => {
    window.requestAnimationFrame(() => {
      window.print();
    });
  });
};

const runPrintAction = (mode) => {
  const action = reportsPrintPendingAction;
  reportsPrintPendingAction = null;
  document.documentElement.dataset.printMode = mode;

  if (action && action.type === 'button' && action.button instanceof HTMLElement && action.button.isConnected) {
    if (action.button.matches(BUSINESS_REPORT_PDF_SELECTOR)) {
      printAfterStyleFlush(mode);
      return;
    }

    action.button.setAttribute(REPORTS_PRINT_BYPASS_ATTR, '1');
    action.button.click();
    return;
  }

  printAfterStyleFlush(mode);
};

const ensurePrintDialog = () => {
  let dialog = document.getElementById('reports_print_dialog');
  if (dialog instanceof HTMLDialogElement) {
    return dialog;
  }

  dialog = document.createElement('dialog');
  dialog.id = 'reports_print_dialog';
  dialog.className = 'dialog reports_print_dialog';
  dialog.setAttribute('data-dialog-invoker-bridge', '');
  dialog.setAttribute('data-dialog-close-tts', REPORTS_PRINT_T.EARNINGS_PRINT_REPORT);
  dialog.setAttribute('aria-modal', 'true');
  dialog.setAttribute('aria-labelledby', 'reports_print_dialog_title');
  dialog.setAttribute('aria-describedby', 'reports_print_dialog_desc');
  setReportsPrintMarkup(dialog, `
    <form method="dialog" class="reports_print_form">
      <section class="modal_header reports_print_header">
        <h2 id="reports_print_dialog_title" class="modal_title">${REPORTS_PRINT_T.EARNINGS_PRINT_REPORT}</h2>
        <button type="button" class="btn_close" data-dialog-close="reports_print_dialog" commandfor="reports_print_dialog" command="close" data-reports-print-close aria-label="${REPORTS_PRINT_T.CLOSE}">&times;</button>
      </section>
      <section class="modal_content reports_print_content">
        <p id="reports_print_dialog_desc" class="reports_print_desc">${REPORTS_PRINT_T.REPORTS_PRINT_DIALOG_DESC}</p>
        <fieldset class="reports_print_modes">
          <legend class="visually_hidden">${REPORTS_PRINT_T.REPORTS_PRINT_MODE_LEGEND}</legend>
          ${Array.from(REPORTS_PRINT_MODES).map((mode) => `
            <label class="reports_print_mode" data-print-mode-option="${mode}">
              <input type="radio" name="reports_print_mode" value="${mode}">
              <span class="reports_print_mode_body">
                <span class="reports_print_mode_title">${modeLabels[mode]}</span>
                <span class="reports_print_mode_desc">${modeDescriptions[mode]}</span>
              </span>
            </label>
          `).join('')}
        </fieldset>
      </section>
      <section class="modal_footer reports_print_footer">
        <button type="button" class="btn btn_secondary" data-dialog-close="reports_print_dialog" commandfor="reports_print_dialog" command="close" data-reports-print-close>${REPORTS_PRINT_T.CANCEL}</button>
        <button type="submit" class="btn btn_primary" value="print">${REPORTS_PRINT_T.EARNINGS_PRINT_REPORT}</button>
      </section>
    </form>
  `);

  document.body.appendChild(dialog);
  window.PayCalCore?.bindAllDialogInvokerBridges?.();

  dialog.addEventListener('click', (event) => {
    if (event.target === dialog) {
      dialog.close('cancel');
    }
  });

  dialog.addEventListener('close', () => {
    document.documentElement.classList.remove('reports_print_dialog_open');
  });

  dialog.querySelector('form')?.addEventListener('submit', (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const data = new FormData(form);
    const mode = writePrintMode(data.get('reports_print_mode'));
    dialog.close('print');
    window.setTimeout(() => {
      runPrintAction(mode);
    }, 50);
  });

  return dialog;
};

const syncDialogMode = (dialog) => {
  const selected = readPrintMode();
  const input = dialog.querySelector(`input[name="reports_print_mode"][value="${selected}"]`);
  if (input instanceof HTMLInputElement) {
    input.checked = true;
  }
};

const openReportsPrintDialog = (action = null) => {
  const dialog = ensurePrintDialog();
  reportsPrintPendingAction = action;
  syncDialogMode(dialog);
  writePrintMode(readPrintMode());
  document.documentElement.classList.add('reports_print_dialog_open');
  if (typeof dialog.showModal === 'function') {
    dialog.showModal();
  } else {
    dialog.setAttribute('open', 'open');
  }
  const checked = dialog.querySelector('input[name="reports_print_mode"]:checked');
  if (checked instanceof HTMLElement) {
    checked.focus();
  }
};

const ensurePrintButton = () => {
  if (document.getElementById('reports_print_options_button')) {
    return;
  }

  const mount = document.querySelector('[data-earnings-mode]')
    || document.querySelector('.business_reports_panel_shell')
    || document.getElementById('business-workspace')
    || document.getElementById('main');
  if (!(mount instanceof HTMLElement)) {
    return;
  }

  const toolbar = document.createElement('div');
  toolbar.className = 'reports_print_toolbar';
  setReportsPrintMarkup(toolbar, `
    <button type="button" class="btn btn_secondary reports_print_button" id="reports_print_options_button" aria-haspopup="dialog" aria-controls="reports_print_dialog" aria-label="${REPORTS_PRINT_T.EARNINGS_PRINT_REPORT}">
      ${REPORTS_PRINT_T.EARNINGS_PRINT_REPORT}
    </button>
  `);
  mount.insertAdjacentElement('beforebegin', toolbar);
  toolbar.querySelector('button')?.addEventListener('click', () => openReportsPrintDialog());
};

const shouldIgnorePrintShortcut = (event) => {
  if (event.defaultPrevented) {
    return true;
  }
  if (!(event.metaKey || event.ctrlKey) || event.shiftKey || event.altKey) {
    return true;
  }
  return String(event.key || '').toLowerCase() !== 'p';
};

if (isReportsPrintPage()) {
  writePrintMode(readPrintMode());

  document.addEventListener('DOMContentLoaded', ensurePrintButton);

  document.addEventListener('click', (event) => {
    const button = findPanelPdfButton(event.target);
    if (!(button instanceof HTMLElement)) {
      return;
    }

    if (button.hasAttribute(REPORTS_PRINT_BYPASS_ATTR)) {
      button.removeAttribute(REPORTS_PRINT_BYPASS_ATTR);
      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();
    openReportsPrintDialog({ type: 'button', button });
  }, true);

  document.addEventListener('keydown', (event) => {
    if (shouldIgnorePrintShortcut(event)) {
      return;
    }
    event.preventDefault();
    openReportsPrintDialog();
  }, true);

  window.addEventListener('beforeprint', () => {
    writePrintMode(readPrintMode());
  });
}
