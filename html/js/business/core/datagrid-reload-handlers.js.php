  // Core module: datagrid reload announcements and timestamp decoration

  const initializeDatagridReloadHandlers = () => {
    const decorateAuditGridTimestamps = (gridId) => {
      const grid = document.getElementById(gridId);
      if (!grid) {
        return;
      }

      const gridHostId = gridId.replace(/-grid$/, '-grid-host');
      const gridHost = document.getElementById(gridHostId) || grid.parentElement;
      let detailsStore = gridHost ? gridHost.querySelector(`[id$="_event_details"]`) : null;
      if (!detailsStore) {
        detailsStore = document.getElementById(`${gridId}_event_details`);
      }
      if (!detailsStore) {
        return;
      }

      const eventDetailsJson = detailsStore.getAttribute('data-event-details-json') || '{}';
      let eventDetailsMap = {};
      try {
        eventDetailsMap = JSON.parse(eventDetailsJson);
      } catch {
        return;
      }

      const rows = grid.querySelectorAll('.datagrid_row');
      rows.forEach((row, index) => {
        const rowId = String(row.dataset.id || '');
        const rawEventDetails = eventDetailsMap[rowId];
        if (!rawEventDetails) {
          return;
        }

        const createdAtRaw = String(rawEventDetails.created_at_raw || '').trim();
        if (createdAtRaw === '') {
          return;
        }

        const timestampCells = row.querySelectorAll('.datagrid_content');
        if (timestampCells.length === 0) {
          return;
        }

        const firstCell = timestampCells[0];
        const parsedDate = parseHistoryTimestampValue(createdAtRaw);
        if (!parsedDate) {
          return;
        }

        const displayText = formatTimestampInTimeZone(parsedDate, viewerTimeZone);
        const popoverId = `businesses_audit_timestamp_popover_${String(rowId).replace(/[^a-zA-Z0-9_-]/g, '_')}_${index}`;

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

        buildTimestampZoneRows(parsedDate).forEach((rowData) => {
          const rowEl = document.createElement('span');
          rowEl.className = 'businesses_history_timestamp_popover_row';
          const labelEl = document.createElement('span');
          labelEl.className = 'businesses_history_timestamp_popover_label';
          labelEl.textContent = `${rowData.label}:`;
          const valueEl = document.createElement('span');
          valueEl.className = 'businesses_history_timestamp_popover_value';
          valueEl.textContent = rowData.value;
          rowEl.appendChild(labelEl);
          rowEl.appendChild(valueEl);
          popover.appendChild(rowEl);
        });

        field.appendChild(trigger);
        field.appendChild(popover);
        firstCell.textContent = '';
        firstCell.appendChild(field);
        trigger.addEventListener('click', (e) => {
          e.preventDefault();
          openHistoryTimestampPopoverFor(trigger, popover);
        });
      });
    };

    document.addEventListener('paycal:datagrid-reloaded', (event) => {
      const detail = event?.detail || {};
      const gridId = String(detail.gridId || '');
      if (gridId === 'businesses-audit-grid') {
        if (!elements.auditStatus) {
          return;
        }
        const rowCount = Number(detail.rowCount || 0);
        elements.auditStatus.textContent = formatPhpTemplate(T.auditGridUpdated, [
          rowCount,
          rowCount === 1 ? '' : 's',
        ]);
        decorateAuditGridTimestamps('businesses-audit-grid');
        return;
      }

      if (gridId === 'businesses-free-audit-grid') {
        decorateAuditGridTimestamps('businesses-free-audit-grid');
        return;
      }

      if (gridId !== 'businesses-members-grid') {
        return;
      }

      syncMemberRoleTriggerLabels();
      enhanceMembersJoinedTimestampCells();

      if (!elements.membersGridStatus) {
        return;
      }

      const stateInfo = detail.state || {};
      const rowCount = Number(detail.rowCount || 0);
      const order = formatDatagridOrderLabel(stateInfo.sort, stateInfo.direction);
      const search = formatDatagridSearchLabel(stateInfo.search);
      const page = stateInfo.page || 1;
      elements.membersGridStatus.textContent = formatPhpTemplate(T.membersGridStatusDetail, [
        rowCount,
        rowCount === 1 ? '' : 's',
        order,
        search,
        page,
      ]);
    });
  };
