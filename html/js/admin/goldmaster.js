const dialog = document.getElementById('goldmaster_dialog');
const openButtons = Array.from(document.querySelectorAll('[data-goldmaster-open]'));
const closeButtons = Array.from(document.querySelectorAll('[data-goldmaster-close]'));
const copyButton = document.querySelector('[data-goldmaster-copy]');
const copyStatus = document.querySelector('[data-goldmaster-copy-status]');
const viewFileButton = document.querySelector('[data-goldmaster-view-file]');
const filePreview = document.querySelector('[data-goldmaster-file-preview]');

let lastTrigger = null;

function focusDialog() {
  if (!dialog) {
    return;
  }

  const target = dialog.querySelector('a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])');
  if (target instanceof HTMLElement) {
    target.focus({ preventScroll: true });
  }
}

function openGoldMaster(trigger = null) {
  if (!(dialog instanceof HTMLDialogElement)) {
    return;
  }

  if (trigger instanceof HTMLElement) {
    lastTrigger = trigger;
  }

  if (!dialog.open) {
    dialog.showModal();
  }

  requestAnimationFrame(focusDialog);
}

function closeGoldMaster() {
  if (!(dialog instanceof HTMLDialogElement) || !dialog.open) {
    return;
  }

  dialog.close();

  if (lastTrigger instanceof HTMLElement && document.contains(lastTrigger)) {
    lastTrigger.focus({ preventScroll: true });
  }
}

openButtons.forEach((button) => {
  button.addEventListener('click', () => openGoldMaster(button));
});

closeButtons.forEach((button) => {
  button.addEventListener('click', closeGoldMaster);
});

if (dialog) {
  dialog.addEventListener('cancel', () => {
    if (lastTrigger instanceof HTMLElement && document.contains(lastTrigger)) {
      requestAnimationFrame(() => lastTrigger.focus({ preventScroll: true }));
    }
  });
}

if (copyButton) {
  copyButton.addEventListener('click', async () => {
    const pathNode = document.querySelector('[data-goldmaster-path]');
    const path = pathNode ? pathNode.textContent.trim() : '';
    if (!path) {
      return;
    }

    try {
      await navigator.clipboard.writeText(path);
      if (copyStatus) {
        copyStatus.textContent = `Copied ${path}`;
      }
    } catch {
      if (copyStatus) {
        copyStatus.textContent = 'Copy failed. Select and copy the path manually.';
      }
    }
  });
}

if (viewFileButton && filePreview) {
  viewFileButton.addEventListener('click', () => {
    const shouldShow = filePreview.hidden;
    filePreview.hidden = !shouldShow;
    viewFileButton.setAttribute('aria-expanded', shouldShow ? 'true' : 'false');
    viewFileButton.textContent = shouldShow ? 'Hide file' : 'View file';

    if (shouldShow) {
      filePreview.scrollIntoView({ block: 'nearest' });
    }
  });
}

if (dialog instanceof HTMLDialogElement && dialog.dataset.openOnLoad === '1') {
  requestAnimationFrame(() => openGoldMaster());
}
