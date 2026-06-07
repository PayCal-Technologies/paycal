document.addEventListener('DOMContentLoaded', () => {
  const busyBanner = document.getElementById('soc2-action-busy-banner');
  const busyText = document.getElementById('soc2-action-busy-text');
  const copyStatus = document.getElementById('soc2-copy-status');
  const i18nNode = document.getElementById('soc2-i18n');
  const actionForms = Array.from(document.querySelectorAll('form[data-soc2-action-form="1"]'));
  const copyButtons = Array.from(document.querySelectorAll('button[data-copy-source]'));
  const timeLabels = Array.from(document.querySelectorAll('.soc2-time-label'));
  const localTimeSlots = Array.from(document.querySelectorAll('.soc2-time-local[data-time-utc]'));

  let localized = {};
  const userLocale = i18nNode?.getAttribute('data-locale') || undefined;
  const rawMessages = i18nNode?.getAttribute('data-messages') || '{}';
  try {
    localized = JSON.parse(rawMessages);
  } catch {
    localized = {};
  }

  const msg = (key, fallback) => {
    const value = localized[key];
    return typeof value === 'string' && value !== '' ? value : fallback;
  };

  const announce = (message) => {
    if (!copyStatus) {
      return;
    }
    copyStatus.textContent = '';
    window.setTimeout(() => {
      copyStatus.textContent = message;
    }, 10);
  };

  const setTooltipExpanded = (label, expanded) => {
    const tooltip = label.nextElementSibling;
    if (!(tooltip instanceof HTMLElement)) {
      return;
    }

    label.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    tooltip.setAttribute('aria-hidden', expanded ? 'false' : 'true');
  };

  timeLabels.forEach((label) => {
    if (!(label instanceof HTMLButtonElement)) {
      return;
    }

    label.addEventListener('focus', () => {
      setTooltipExpanded(label, true);
    });

    label.addEventListener('blur', () => {
      setTooltipExpanded(label, false);
    });

    label.addEventListener('mouseenter', () => {
      setTooltipExpanded(label, true);
    });

    label.addEventListener('mouseleave', () => {
      setTooltipExpanded(label, false);
    });

    label.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        setTooltipExpanded(label, false);
        label.blur();
      }
    });
  });

  localTimeSlots.forEach((slot) => {
    const utcRaw = slot.getAttribute('data-time-utc');
    if (!utcRaw || utcRaw === '-') {
      slot.textContent = '-';
      return;
    }

    const normalized = utcRaw.replace(' UTC', 'Z').replace(' ', 'T');
    const parsed = new Date(normalized);
    if (Number.isNaN(parsed.getTime())) {
      slot.textContent = msg('unavailable', 'Unavailable');
      return;
    }

    const zone = Intl.DateTimeFormat().resolvedOptions().timeZone || msg('local_timezone_fallback', 'Local');
    slot.textContent = `${parsed.toLocaleString(userLocale)} (${zone})`;
  });

  const disableActionControls = (submittedForm) => {
    const actionRow = submittedForm.closest('.soc2-actions-row');
    if (!actionRow) {
      return;
    }

    actionRow.classList.add('is-busy');

    const controls = actionRow.querySelectorAll('button, a.btn, input, select, textarea');
    controls.forEach((control) => {
      if (control instanceof HTMLButtonElement || control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement) {
        if (control instanceof HTMLInputElement && control.type === 'hidden') {
          return;
        }
        control.disabled = true;
      }

      if (control instanceof HTMLAnchorElement) {
        control.setAttribute('aria-disabled', 'true');
        control.tabIndex = -1;
      }
    });

    submittedForm.classList.add('is-busy');
  };

  const showBusy = (message) => {
    if (!busyBanner || !busyText) {
      return;
    }

    busyText.textContent = message;
    busyBanner.hidden = false;
  };

  actionForms.forEach((form) => {
    form.addEventListener('submit', () => {
      const actionInput = form.querySelector('input[name="action"]');
      const action = actionInput instanceof HTMLInputElement ? actionInput.value : '';

      let message = msg('busy_default', 'SOC 2 action in progress. Running export and generation tasks. Please wait.');
      if (action === 'run_runtime_export') {
        message = msg('busy_runtime_export', 'Running runtime evidence export. Gathering auth and access traces now.');
      } else if (action === 'run_bundle_refresh') {
        message = msg('busy_bundle_refresh', 'Running bundle refresh and validation. This can take a few moments.');
      } else if (action === 'run_compliance_snapshot') {
        message = msg('busy_snapshot', 'Generating SOC 2 compliance snapshot markdown. Please wait.');
      } else if (action === 'refresh_gcs_inventory') {
        message = msg('busy_gcs_refresh', 'Querying Google Cloud Storage for SOC 2 evidence objects. Please wait.');
      }

      showBusy(message);
      disableActionControls(form);
    });
  });

  copyButtons.forEach((button) => {
    button.addEventListener('click', async () => {
      const source = button.getAttribute('data-copy-source');
      if (!source) {
        return;
      }

      const label = button.querySelector('.soc2-copy-label');
      const previous = label ? label.textContent : button.textContent;
      button.disabled = true;

      try {
        const response = await fetch(source, { credentials: 'same-origin' });
        if (!response.ok) {
          throw new Error('Download failed');
        }

        const text = await response.text();
        await navigator.clipboard.writeText(text);
        if (label) {
          label.textContent = msg('copied', 'Copied');
        } else {
          button.textContent = msg('copied', 'Copied');
        }
        announce(msg('copy_announcement_success', 'Copied to clipboard.'));
      } catch {
        if (label) {
          label.textContent = msg('copy_failed', 'Copy Failed');
        } else {
          button.textContent = msg('copy_failed', 'Copy Failed');
        }
        announce(msg('copy_announcement_failure', 'Copy failed.'));
      }

      window.setTimeout(() => {
        if (label) {
          label.textContent = previous || msg('copy_default_label', 'Copy');
        } else {
          button.textContent = previous || msg('copy_default_label', 'Copy');
        }
        button.disabled = false;
      }, 1400);
    });
  });
});
