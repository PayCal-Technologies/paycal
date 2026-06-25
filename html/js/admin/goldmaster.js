const dialog = document.getElementById('goldmaster_dialog');
const openButtons = Array.from(document.querySelectorAll('[data-goldmaster-open]'));
const copyButton = document.querySelector('[data-goldmaster-copy]');
const copyStatus = document.querySelector('[data-goldmaster-copy-status]');
const viewFileButton = document.querySelector('[data-goldmaster-view-file]');
const filePreview = document.querySelector('[data-goldmaster-file-preview]');

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

  const pc = window.PayCalCore;
  if (pc && pc.state) {
    pc.state.lastFocused = trigger instanceof HTMLElement ? trigger : document.activeElement;
    pc.state.modal_is_active = true;
  }

  if (!dialog.open) {
    dialog.showModal();
  }

  dialog.setAttribute('aria-modal', 'true');
  dialog.setAttribute('aria-hidden', 'false');

  requestAnimationFrame(focusDialog);
}

openButtons.forEach((button) => {
  button.addEventListener('click', () => openGoldMaster(button));
});

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
