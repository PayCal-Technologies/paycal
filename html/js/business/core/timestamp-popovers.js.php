<?php namespace PayCal\Domain; ?>

  const formatInviteTimestamp = (value) => {
    const raw = String(value || '').trim();
    if (raw === '') {
      return T.valueUnknown;
    }

    const parsed = new Date(raw);
    if (Number.isNaN(parsed.getTime())) {
      return raw;
    }

    return parsed.toLocaleString();
  };

  const parseHistoryTimestampValue = (value) => {
    if (typeof value !== 'string' || value.trim() === '') {
      return null;
    }

    const trimmed = value.trim();
    const parsed = new Date(trimmed);
    if (!Number.isNaN(parsed.getTime())) {
      return parsed;
    }

    const dateTimeMatch = /^(\d{4}-\d{2}-\d{2})[ T](\d{2}:\d{2}:\d{2})$/.exec(trimmed);
    if (!dateTimeMatch) {
      return null;
    }

    const asUtc = new Date(`${dateTimeMatch[1]}T${dateTimeMatch[2]}Z`);
    return Number.isNaN(asUtc.getTime()) ? null : asUtc;
  };

  const resolveViewerLocale = () => {
    const locale = String(PC?.config?.USER_LOCALE || '').trim();
    return locale !== '' ? locale : 'en-CA';
  };

  const formatTimestampInTimeZone = (dateValue, timeZone) => {
    if (!(dateValue instanceof Date) || Number.isNaN(dateValue.getTime())) {
      return T.unavailable;
    }

    try {
      const options = {
        year: '2-digit',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
      };

      const normalizedZone = typeof timeZone === 'string' && timeZone.trim() !== ''
        ? timeZone.trim()
        : undefined;
      const viewerLocale = resolveViewerLocale();

      const formatter = normalizedZone
        ? new Intl.DateTimeFormat(viewerLocale, { ...options, timeZone: normalizedZone })
        : new Intl.DateTimeFormat(viewerLocale, options);

      const parts = formatter.formatToParts(dateValue);
      const data = {};
      parts.forEach((part) => {
        if (part.type !== 'literal') {
          data[part.type] = part.value;
        }
      });

      const mm = String(data.month || '00').padStart(2, '0');
      const dd = String(data.day || '00').padStart(2, '0');
      const yy = String(data.year || '00').slice(-2).padStart(2, '0');
      const hh = String(data.hour || '00').padStart(2, '0');
      const min = String(data.minute || '00').padStart(2, '0');

      return `${mm}/${dd}/${yy} ${hh}:${min}`;
    } catch {
      // Fall back to viewer locale timezone when timezone is unavailable.
      const mm = String(dateValue.getMonth() + 1).padStart(2, '0');
      const dd = String(dateValue.getDate()).padStart(2, '0');
      const yy = String(dateValue.getFullYear()).slice(-2);
      const hh = String(dateValue.getHours()).padStart(2, '0');
      const min = String(dateValue.getMinutes()).padStart(2, '0');
      return `${mm}/${dd}/${yy} ${hh}:${min}`;
    }
  };

  const viewerTimeZone = (() => {
    try {
      return Intl.DateTimeFormat().resolvedOptions().timeZone || 'Local';
    } catch {
      return 'Local';
    }
  })();

  const buildTimestampZoneRows = (parsedDate) => [
    { label: T.timestampLocal, value: formatTimestampInTimeZone(parsedDate, viewerTimeZone) },
    { label: T.timestampServer, value: formatTimestampInTimeZone(parsedDate, SERVER_TIMEZONE) },
    { label: T.timestampUtc, value: formatTimestampInTimeZone(parsedDate, 'UTC') },
  ];

  const historyTimestampPopoverController = createAnchoredPopoverController({
    restoreHomeOnClose: true,
  });

  const closeHistoryTimestampPopover = ({ restoreFocus = false } = {}) => {
    historyTimestampPopoverController.close({ restoreFocus });
  };

  const openHistoryTimestampPopoverFor = (trigger, popover) => {
    historyTimestampPopoverController.open(trigger, popover);
  };

  const bindHistoryTimestampPopover = (container, trigger, popover) => {
    if (!(container instanceof HTMLElement) || !(trigger instanceof HTMLButtonElement) || !(popover instanceof HTMLElement)) {
      return;
    }

    trigger.addEventListener('click', (event) => {
      event.preventDefault();
      if (!popover.hidden && historyTimestampPopoverController.isOpenFor(trigger, popover)) {
        closeHistoryTimestampPopover({ restoreFocus: false });
      } else {
        openHistoryTimestampPopoverFor(trigger, popover);
      }
    });

    trigger.addEventListener('mouseenter', () => {
      openHistoryTimestampPopoverFor(trigger, popover);
    });

    trigger.addEventListener('focus', () => {
      openHistoryTimestampPopoverFor(trigger, popover);
    });

    trigger.addEventListener('keydown', (event) => {
      if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        if (!popover.hidden && historyTimestampPopoverController.isOpenFor(trigger, popover)) {
          closeHistoryTimestampPopover({ restoreFocus: false });
        } else {
          openHistoryTimestampPopoverFor(trigger, popover);
        }
      } else if (event.key === 'Escape') {
        event.preventDefault();
        closeHistoryTimestampPopover({ restoreFocus: true });
      }
    });

    trigger.addEventListener('mouseleave', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && popover.contains(nextTarget)) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    trigger.addEventListener('focusout', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && popover.contains(nextTarget)) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    popover.addEventListener('mouseleave', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && trigger.contains(nextTarget)) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    popover.addEventListener('focusout', (event) => {
      const nextTarget = event.relatedTarget;
      if (nextTarget instanceof Node && (trigger.contains(nextTarget) || popover.contains(nextTarget))) {
        return;
      }
      closeHistoryTimestampPopover({ restoreFocus: false });
    });

    bindAnchoredPopoverGlobalDismissals(historyTimestampPopoverController, 'historyTimestampPopoverGlobalBound');
  };

  const enhanceInviteHistoryTimestampCells = () => {
    if (!(elements.membersInviteHistoryGridContainer instanceof HTMLElement)) {
      return;
    }

    const gridEl = elements.membersInviteHistoryGridContainer.querySelector('[data-grid="businesses-invite-history-grid"]');
    if (!(gridEl instanceof HTMLElement)) {
      return;
    }

    const headerSort = gridEl.querySelector('.datagrid_sort[data-column="resolved_at"]');
    const headerCell = headerSort instanceof HTMLElement ? headerSort.closest('.datagrid_heading') : null;
    const headerId = headerCell instanceof HTMLElement ? String(headerCell.id || '') : '';

    let timestampCells = [];
    if (headerId !== '') {
      timestampCells = Array.from(gridEl.querySelectorAll(`.datagrid_item[aria-labelledby="${headerId}"]`));
    }

    if (timestampCells.length === 0) {
      timestampCells = Array.from(gridEl.querySelectorAll('.datagrid_row .datagrid_item:nth-child(4)'));
    }

    timestampCells.forEach((cell, index) => {
      if (!(cell instanceof HTMLElement)) {
        return;
      }

      if (cell.closest('.datagrid_row_empty')) {
        return;
      }

      cell.classList.add('businesses_history_timestamp_cell');

      if (cell.dataset.timePopoverBound === '1') {
        return;
      }

      const rawValue = String(cell.textContent || '').trim();
      if (rawValue === '') {
        return;
      }

      const parsedDate = parseHistoryTimestampValue(rawValue);
      const displayText = formatTimestampInTimeZone(parsedDate, viewerTimeZone);
      const rowId = String(cell.closest('.datagrid_row')?.getAttribute('data-id') || `row_${index}`);
      const safeRowId = rowId.replace(/[^a-zA-Z0-9_-]/g, '_');
      const popoverId = `businesses_history_timestamp_popover_${safeRowId}_${index}`;

      const field = document.createElement('span');
      field.className = 'businesses_history_timestamp_field';

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'businesses_history_timestamp_trigger';
      trigger.textContent = displayText;
      trigger.setAttribute('aria-haspopup', 'dialog');
      trigger.setAttribute('aria-controls', popoverId);
      trigger.setAttribute('aria-expanded', 'false');

      const popover = document.createElement('div');
      popover.id = popoverId;
      popover.className = 'businesses_history_timestamp_popover';
      popover.hidden = true;
      popover.setAttribute('role', 'dialog');
      popover.setAttribute('aria-label', T.timestampDetailsAria);

      const rows = buildTimestampZoneRows(parsedDate);

      if (!(parsedDate instanceof Date) || Number.isNaN(parsedDate.getTime())) {
        rows[1].value = rawValue;
      }

      rows.forEach((row) => {
        const rowEl = document.createElement('span');
        rowEl.className = 'businesses_history_timestamp_popover_row';

        const labelEl = document.createElement('span');
        labelEl.className = 'businesses_history_timestamp_popover_label';
        labelEl.textContent = `${row.label}:`;

        const valueEl = document.createElement('span');
        valueEl.className = 'businesses_history_timestamp_popover_value';
        valueEl.textContent = row.value;

        rowEl.appendChild(labelEl);
        rowEl.appendChild(valueEl);
        popover.appendChild(rowEl);
      });

      field.appendChild(trigger);
      field.appendChild(popover);

      cell.textContent = '';
      cell.appendChild(field);
      cell.dataset.timePopoverBound = '1';

      bindHistoryTimestampPopover(elements.membersInviteHistoryGridContainer, trigger, popover);
    });
  };

  const enhanceMembersJoinedTimestampCells = () => {
    if (!(elements.membersGridContainer instanceof HTMLElement)) {
      return;
    }

    const gridEl = elements.membersGridContainer.querySelector('[data-grid="business-members"]');
    if (!(gridEl instanceof HTMLElement)) {
      return;
    }

    const headerSort = gridEl.querySelector('.datagrid_sort[data-column="joined_at"]');
    const headerCell = headerSort instanceof HTMLElement ? headerSort.closest('.datagrid_heading') : null;
    const headerId = headerCell instanceof HTMLElement ? String(headerCell.id || '') : '';

    let timestampCells = [];
    if (headerId !== '') {
      timestampCells = Array.from(gridEl.querySelectorAll(`.datagrid_item[aria-labelledby="${headerId}"]`));
    }

    if (timestampCells.length === 0) {
      timestampCells = Array.from(gridEl.querySelectorAll('.datagrid_row .datagrid_col_joined_at'));
    }

    timestampCells.forEach((cell, index) => {
      if (!(cell instanceof HTMLElement)) {
        return;
      }

      if (cell.closest('.datagrid_row_empty')) {
        return;
      }

      cell.classList.add('businesses_history_timestamp_cell');

      if (cell.dataset.timePopoverBound === '1') {
        return;
      }

      const rawValue = String(cell.dataset.joinedAtRaw || '').trim();
      const friendlyDisplay = String(cell.dataset.joinedDisplay || cell.textContent || '').trim();
      if (rawValue === '' && friendlyDisplay === '') {
        return;
      }

      const parsedDate = parseHistoryTimestampValue(rawValue);
      const displayText = friendlyDisplay !== ''
        ? friendlyDisplay
        : formatTimestampInTimeZone(parsedDate, viewerTimeZone);
      if (displayText === T.unavailable) {
        return;
      }
      const rowId = String(cell.closest('.datagrid_row')?.getAttribute('data-id') || `member_${index}`);
      const safeRowId = rowId.replace(/[^a-zA-Z0-9_-]/g, '_');
      const popoverId = `businesses_members_joined_popover_${safeRowId}_${index}`;

      const field = document.createElement('span');
      field.className = 'businesses_history_timestamp_field';

      const trigger = document.createElement('button');
      trigger.type = 'button';
      trigger.className = 'businesses_history_timestamp_trigger';
      trigger.textContent = displayText;
      trigger.setAttribute('aria-haspopup', 'dialog');
      trigger.setAttribute('aria-controls', popoverId);
      trigger.setAttribute('aria-expanded', 'false');

      const popover = document.createElement('div');
      popover.id = popoverId;
      popover.className = 'businesses_history_timestamp_popover';
      popover.hidden = true;
      popover.setAttribute('role', 'dialog');
      popover.setAttribute('aria-label', T.timestampJoinedAria);

      const rows = buildTimestampZoneRows(parsedDate);

      if (!(parsedDate instanceof Date) || Number.isNaN(parsedDate.getTime())) {
        rows[1].value = rawValue;
      }

      rows.forEach((row) => {
        const rowEl = document.createElement('span');
        rowEl.className = 'businesses_history_timestamp_popover_row';

        const labelEl = document.createElement('span');
        labelEl.className = 'businesses_history_timestamp_popover_label';
        labelEl.textContent = `${row.label}:`;

        const valueEl = document.createElement('span');
        valueEl.className = 'businesses_history_timestamp_popover_value';
        valueEl.textContent = row.value;

        rowEl.appendChild(labelEl);
        rowEl.appendChild(valueEl);
        popover.appendChild(rowEl);
      });

      field.appendChild(trigger);
      field.appendChild(popover);

      cell.textContent = '';
      cell.appendChild(field);
      cell.dataset.timePopoverBound = '1';

      bindHistoryTimestampPopover(elements.membersGridContainer, trigger, popover);
    });
  };
