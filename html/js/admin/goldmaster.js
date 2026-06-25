const dialog = document.getElementById('goldmaster_dialog');
const copyButton = document.querySelector('[data-goldmaster-copy]');
const copyStatus = document.querySelector('[data-goldmaster-copy-status]');
const viewFileButton = document.querySelector('[data-goldmaster-view-file]');
const filePreview = document.querySelector('[data-goldmaster-file-preview]');

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
  const pc = window.PayCalCore;
  if (pc && typeof pc.openModal === 'function') {
    requestAnimationFrame(() => pc.openModal('goldmaster_dialog', 'GoldMaster'));
  }
}
