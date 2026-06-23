<?php namespace PayCal\Domain; ?>

  const escapeBusinessText = (value) => {
    if (Guardian && typeof Guardian.sanitizedText === 'function') {
      return Guardian.sanitizedText(String(value ?? ''));
    }

    return String(value ?? '')
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#39;');
  };

  const escapeHtml = escapeBusinessText;
  const safeText = escapeBusinessText;

  const setStatusText = (element, message = '', options = {}) => setCoreStatusText(element, message, options);

  const setPlainStatusText = (element, message = '') => setCorePlainStatusText(element, message);

  const normalizeActiveArchivedStatus = (status = 'active') => {
    const normalized = String(status || 'active').trim().toLowerCase();
    return normalized === 'archived' ? 'archived' : 'active';
  };

  const syncActiveArchivedStatusControls = ({
    status = 'active',
    tabSelector = '',
    tabDatasetKey = '',
    noteSelector = '',
    noteDatasetKey = '',
    createSelector = '',
    resolveStatus = normalizeActiveArchivedStatus,
  } = {}) => {
    const normalizedStatus = resolveStatus(status);

    if (tabSelector !== '' && tabDatasetKey !== '') {
      document.querySelectorAll(tabSelector).forEach((candidate) => {
        if (!(candidate instanceof HTMLButtonElement)) {
          return;
        }
        const isActive = resolveStatus(candidate.dataset[tabDatasetKey] || 'active') === normalizedStatus;
        candidate.classList.toggle('active', isActive);
        candidate.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });
    }

    if (noteSelector !== '' && noteDatasetKey !== '') {
      document.querySelectorAll(noteSelector).forEach((note) => {
        if (note instanceof HTMLElement) {
          note.classList.toggle('hidden', resolveStatus(note.dataset[noteDatasetKey] || 'active') !== normalizedStatus);
        }
      });
    }

    if (createSelector !== '') {
      document.querySelectorAll(createSelector).forEach((button) => {
        if (button instanceof HTMLElement) {
          button.hidden = normalizedStatus !== 'active';
        }
      });
    }

    return normalizedStatus;
  };

  const openHtmlDialog = (dialog) => {
    if (!(dialog instanceof HTMLDialogElement)) {
      return false;
    }

    dialog.setAttribute('aria-hidden', 'false');
    if (!dialog.open) {
      dialog.showModal();
    }
    return true;
  };

  const closeHtmlDialog = (dialog) => {
    if (!(dialog instanceof HTMLDialogElement)) {
      return false;
    }

    const active = document.activeElement;
    if (active instanceof HTMLElement && dialog.contains(active)) {
      active.blur();
    }
    if (dialog.open) {
      dialog.close();
    }
    dialog.setAttribute('aria-hidden', 'true');
    return true;
  };

  const createConfirmDialogController = ({
    getElements = null,
    openDialog = openHtmlDialog,
    closeDialog = closeHtmlDialog,
    defaultConfirmText = 'Confirm',
  } = {}) => {
    let currentResolve = null;
    let bound = false;

    const readElements = () => (typeof getElements === 'function' ? getElements() : {});

    const resolveConfirm = (confirmed) => {
      const resolver = currentResolve;
      currentResolve = null;
      if (typeof resolver === 'function') {
        resolver(confirmed === true);
      }
    };

    const close = (confirmed = false) => {
      const { dialog } = readElements();
      resolveConfirm(confirmed);
      closeDialog(dialog);
    };

    const confirm = ({
      title = '',
      message = '',
      confirmText = '',
      confirmClass = 'btn btn_primary',
    } = {}) => new Promise((resolve) => {
      const {
        dialog,
        title: titleEl,
        message: messageEl,
        confirmButton,
      } = readElements();
      if (!(dialog instanceof HTMLDialogElement)) {
        resolve(false);
        return;
      }

      if (currentResolve) {
        resolveConfirm(false);
      }
      currentResolve = resolve;

      setPlainStatusText(titleEl, title);
      setPlainStatusText(messageEl, message);
      if (confirmButton instanceof HTMLButtonElement) {
        confirmButton.textContent = String(confirmText || title || defaultConfirmText);
        confirmButton.className = confirmClass;
        confirmButton.disabled = false;
        confirmButton.focus();
      }

      openDialog(dialog);
      window.requestAnimationFrame(() => {
        if (confirmButton instanceof HTMLButtonElement) {
          confirmButton.focus();
        }
      });
    });

    const bind = () => {
      if (bound) {
        return;
      }
      bound = true;

      const {
        dialog,
        confirmButton,
        cancelButton,
        closeButton,
        closeButtons = [],
      } = readElements();

      if (confirmButton instanceof HTMLButtonElement) {
        confirmButton.addEventListener('click', () => close(true));
      }

      Array.from(new Set([cancelButton, closeButton, ...Array.from(closeButtons)]))
        .forEach((button) => {
          if (!(button instanceof HTMLElement)) {
            return;
          }
          button.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            close(false);
          });
        });

      if (dialog instanceof HTMLDialogElement) {
        dialog.addEventListener('cancel', (event) => {
          event.preventDefault();
          close(false);
        });
        dialog.addEventListener('close', () => resolveConfirm(false));
      }
    };

    return { bind, close, confirm };
  };

  const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
  ].join(',');

  const getFocusableElements = (container) => {
    if (!(container instanceof HTMLElement)) {
      return [];
    }

    return Array.from(container.querySelectorAll(FOCUSABLE_SELECTOR))
      .filter((el) => el instanceof HTMLElement && (el.offsetParent !== null || el === document.activeElement));
  };

  const trapFocusWithin = (container, event) => {
    if (event.key !== 'Tab' || !(container instanceof HTMLElement)) {
      return false;
    }

    const focusableElements = getFocusableElements(container);
    if (focusableElements.length === 0) {
      event.preventDefault();
      return true;
    }

    const first = focusableElements[0];
    const last = focusableElements[focusableElements.length - 1];
    const active = document.activeElement;

    if (event.shiftKey && (active === first || !container.contains(active))) {
      event.preventDefault();
      last.focus();
      return true;
    }

    if (!event.shiftKey && (active === last || !container.contains(active))) {
      event.preventDefault();
      first.focus();
      return true;
    }

    return false;
  };

  const positionAnchoredPopover = (trigger, popover, {
    leftProperty = '--businesses-popover-left',
    topProperty = '--businesses-popover-top',
    margin = 8,
    gap = 6,
  } = {}) => {
    if (!(trigger instanceof HTMLElement) || !(popover instanceof HTMLElement)) {
      return;
    }

    const triggerRect = trigger.getBoundingClientRect();

    popover.style.setProperty(leftProperty, `${margin}px`);
    popover.style.setProperty(topProperty, `${margin}px`);

    const popoverWidth = Math.ceil(popover.offsetWidth || 0);
    const popoverHeight = Math.ceil(popover.offsetHeight || 0);

    let left = triggerRect.left;
    if (popoverWidth > 0) {
      left = Math.min(left, window.innerWidth - popoverWidth - margin);
    }
    left = Math.max(margin, left);

    const maxTop = Math.max(margin, window.innerHeight - popoverHeight - margin);
    const preferredBelowTop = triggerRect.bottom + gap;
    const preferredAboveTop = triggerRect.top - popoverHeight - gap;

    let top = preferredBelowTop;
    if (popoverHeight > 0) {
      if (preferredBelowTop <= maxTop) {
        top = preferredBelowTop;
      } else if (preferredAboveTop >= margin) {
        top = preferredAboveTop;
      } else {
        top = Math.min(preferredBelowTop, maxTop);
      }
    }
    top = Math.max(margin, top);

    popover.style.setProperty(leftProperty, `${Math.round(left)}px`);
    popover.style.setProperty(topProperty, `${Math.round(top)}px`);
  };

  const createAnchoredPopoverController = ({
    leftProperty = '--businesses-popover-left',
    topProperty = '--businesses-popover-top',
    restoreHomeOnClose = false,
    clearOnClose = null,
    onClose = null,
    portalParent = (trigger) => trigger.closest('dialog[open]') || document.body,
  } = {}) => {
    let current = null;

    const position = (state = current) => {
      if (!state) {
        return;
      }
      positionAnchoredPopover(state.trigger, state.popover, { leftProperty, topProperty });
    };

    const close = ({ restoreFocus = false } = {}) => {
      if (!current) {
        return null;
      }

      const closed = current;
      const { trigger, popover, homeParent } = closed;

      if (trigger instanceof HTMLElement) {
        trigger.setAttribute('aria-expanded', 'false');
        if (restoreFocus && typeof trigger.focus === 'function') {
          trigger.focus();
        }
      }

      if (popover instanceof HTMLElement) {
        popover.hidden = true;
        popover.style.removeProperty(leftProperty);
        popover.style.removeProperty(topProperty);
        if (typeof clearOnClose === 'function') {
          clearOnClose(popover, closed);
        }
        if (restoreHomeOnClose) {
          if (homeParent instanceof Node && homeParent.isConnected) {
            homeParent.appendChild(popover);
          } else if (trigger instanceof HTMLElement && trigger.parentElement instanceof Node) {
            trigger.parentElement.appendChild(popover);
          }
        }
      }

      current = null;
      if (typeof onClose === 'function') {
        onClose(closed);
      }
      return closed;
    };

    const open = (trigger, popover, metadata = {}) => {
      if (!(trigger instanceof HTMLElement) || !(popover instanceof HTMLElement)) {
        return null;
      }

      if (current && (current.trigger !== trigger || current.popover !== popover)) {
        close({ restoreFocus: false });
      }

      const homeParent = popover.parentElement;
      const resolvedPortalParent = portalParent(trigger, popover, metadata);
      const parent = resolvedPortalParent instanceof Node ? resolvedPortalParent : document.body;
      if (popover.parentElement !== parent) {
        parent.appendChild(popover);
      }

      popover.hidden = false;
      trigger.setAttribute('aria-expanded', 'true');
      current = {
        ...metadata,
        trigger,
        popover,
        homeParent,
      };
      position(current);
      window.requestAnimationFrame(() => position(current));
      return current;
    };

    const containsTarget = (target) => {
      return !!(
        current
        && target instanceof Node
        && (
          current.trigger.contains(target)
          || current.popover.contains(target)
        )
      );
    };

    return {
      close,
      containsTarget,
      get current() {
        return current;
      },
      isOpenFor(trigger, popover) {
        return !!current && current.trigger === trigger && current.popover === popover;
      },
      open,
      position,
    };
  };

  const bindAnchoredPopoverGlobalDismissals = (controller, datasetKey, {
    pointerEvent = 'pointerdown',
    capture = false,
    repositionOnScroll = true,
    repositionOnResize = true,
  } = {}) => {
    if (!controller || typeof controller.close !== 'function' || typeof controller.position !== 'function') {
      return;
    }

    const key = String(datasetKey || '').trim();
    if (key === '' || document.documentElement.dataset[key] === '1') {
      return;
    }
    document.documentElement.dataset[key] = '1';

    document.addEventListener(pointerEvent, (event) => {
      if (!controller.current) {
        return;
      }

      if (controller.containsTarget(event.target)) {
        return;
      }

      controller.close({ restoreFocus: false });
    }, capture);

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape' || !controller.current) {
        return;
      }
      controller.close({ restoreFocus: true });
    });

    if (repositionOnScroll) {
      window.addEventListener('scroll', () => {
        controller.position();
      }, true);
    }

    if (repositionOnResize) {
      window.addEventListener('resize', () => {
        controller.position();
      });
    }
  };

  const businessCsvEscape = (value) => {
    const text = String(value ?? '');
    return /[",\n\r]/.test(text) ? `"${text.replaceAll('"', '""')}"` : text;
  };

  const businessCsvRow = (values) => values.map(businessCsvEscape).join(',');

  const businessHashBlobSha256 = async (blob) => {
    const digest = await crypto.subtle.digest('SHA-256', await blob.arrayBuffer());
    return Array.from(new Uint8Array(digest))
      .map((byte) => byte.toString(16).padStart(2, '0'))
      .join('');
  };

  const downloadBlob = (blob, filename) => {
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(url);
  };

  const businessZipTextEncoder = new TextEncoder();

  const businessZipCrc32 = (bytes) => {
    let table = businessZipCrc32.table;
    if (!Array.isArray(table)) {
      table = [];
      for (let index = 0; index < 256; index += 1) {
        let value = index;
        for (let bit = 0; bit < 8; bit += 1) {
          value = (value & 1) ? (0xEDB88320 ^ (value >>> 1)) : (value >>> 1);
        }
        table[index] = value >>> 0;
      }
      businessZipCrc32.table = table;
    }

    let crc = 0xFFFFFFFF;
    for (let index = 0; index < bytes.length; index += 1) {
      crc = (crc >>> 8) ^ table[(crc ^ bytes[index]) & 0xFF];
    }

    return (crc ^ 0xFFFFFFFF) >>> 0;
  };

  const businessZipUint16 = (value) => {
    const bytes = new Uint8Array(2);
    bytes[0] = value & 0xFF;
    bytes[1] = (value >>> 8) & 0xFF;
    return bytes;
  };

  const businessZipUint32 = (value) => {
    const bytes = new Uint8Array(4);
    bytes[0] = value & 0xFF;
    bytes[1] = (value >>> 8) & 0xFF;
    bytes[2] = (value >>> 16) & 0xFF;
    bytes[3] = (value >>> 24) & 0xFF;
    return bytes;
  };

  const concatUint8 = (parts) => {
    const total = parts.reduce((sum, part) => sum + part.length, 0);
    const out = new Uint8Array(total);
    let offset = 0;
    parts.forEach((part) => {
      out.set(part, offset);
      offset += part.length;
    });
    return out;
  };

  const businessZipDosTime = (date) => (
    (date.getHours() << 11)
    | (date.getMinutes() << 5)
    | Math.floor(date.getSeconds() / 2)
  );

  const businessZipDosDate = (date) => (
    ((date.getFullYear() - 1980) << 9)
    | ((date.getMonth() + 1) << 5)
    | date.getDate()
  );

  const createZipBlob = async (files) => {
    const localParts = [];
    const centralParts = [];
    let offset = 0;
    const now = new Date();
    const time = businessZipDosTime(now);
    const date = businessZipDosDate(now);

    for (const file of files) {
      const nameBytes = businessZipTextEncoder.encode(file.filename);
      const content = new Uint8Array(await file.blob.arrayBuffer());
      const checksum = businessZipCrc32(content);
      const localHeader = concatUint8([
        businessZipUint32(0x04034b50), businessZipUint16(20), businessZipUint16(0x0800), businessZipUint16(0),
        businessZipUint16(time), businessZipUint16(date), businessZipUint32(checksum), businessZipUint32(content.length),
        businessZipUint32(content.length), businessZipUint16(nameBytes.length), businessZipUint16(0), nameBytes,
      ]);
      localParts.push(localHeader, content);
      centralParts.push(concatUint8([
        businessZipUint32(0x02014b50), businessZipUint16(20), businessZipUint16(20), businessZipUint16(0x0800), businessZipUint16(0),
        businessZipUint16(time), businessZipUint16(date), businessZipUint32(checksum), businessZipUint32(content.length),
        businessZipUint32(content.length), businessZipUint16(nameBytes.length), businessZipUint16(0), businessZipUint16(0),
        businessZipUint16(0), businessZipUint16(0), businessZipUint32(0), businessZipUint32(offset), nameBytes,
      ]));
      offset += localHeader.length + content.length;
    }

    const centralDirectory = concatUint8(centralParts);
    const end = concatUint8([
      businessZipUint32(0x06054b50), businessZipUint16(0), businessZipUint16(0), businessZipUint16(files.length), businessZipUint16(files.length),
      businessZipUint32(centralDirectory.length), businessZipUint32(offset), businessZipUint16(0),
    ]);

    return new Blob([concatUint8([...localParts, centralDirectory, end])], { type: 'application/zip' });
  };
