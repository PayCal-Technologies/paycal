<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();

CORS::renderContentType('text/javascript');
Javascript::renderDocBlock();
?>

import PC from "<?php echo Environment::appURL('js/'); ?>";


window.PAYCAL_DEBUG = typeof window.PAYCAL_DEBUG !== 'undefined' ? window.PAYCAL_DEBUG : false;

const COLUMN_WIDTH_STORAGE_PREFIX = 'paycal:datagrid:widths:';
const COLUMN_RESIZE_MIN_PX = 48;
const COLUMN_WIDTH_SNAP_PX = 8;
const COLUMN_WIDTH_CLASS_PREFIX = 'dg_col_';
const COLUMN_WIDTH_CLASS_PATTERN = /^dg_col_[a-z0-9]+_w_\d+$/;
const COLUMN_RESIZE_MOBILE_MAX = 719;
const VIRTUAL_ROW_HEIGHT_PX = 30;
const VIRTUAL_BUFFER_ROWS = 3;
const VIRTUAL_MIN_ROWS = 16;
const CURRENT_ROW_STORAGE_PREFIX = 'paycal:datagrid:current-row:';

function resolveDataGridRowId(row)
{
  if (!(row instanceof HTMLElement)) {
    return '';
  }

  return String(row.dataset.id || row.dataset.memberId || '').trim();
}

function getDataGridRows(container)
{
  const innerGrid = resolveInnerDataGrid(container) || resolveDataGridRoot(container);
  const root = innerGrid instanceof HTMLElement ? innerGrid : container;
  return Array.from(root.querySelectorAll('.datagrid_row:not(.datagrid_row_empty)'));
}

function isDataGridRowNavigationBlockedTarget(target)
{
  if (!(target instanceof Element)) {
    return false;
  }

  return !!target.closest(
    'input, textarea, select, button, a, .datagrid_action, .datagrid_sort, .datagrid_search, .datagrid_pager, .datagrid_pagination, .datagrid_column_toggle, .datagrid_column_toggle_input, .datagrid_fullscreen_toggle, .businesses_member_role_trigger',
  );
}

function readStoredCurrentRowId(gridId)
{
  try {
    return String(sessionStorage.getItem(`${CURRENT_ROW_STORAGE_PREFIX}${gridId}`) || '').trim();
  }
  catch (_error) {
    return '';
  }
}

function writeStoredCurrentRowId(gridId, rowId)
{
  try {
    const nextId = String(rowId || '').trim();
    if (nextId === '') {
      sessionStorage.removeItem(`${CURRENT_ROW_STORAGE_PREFIX}${gridId}`);
      return;
    }

    sessionStorage.setItem(`${CURRENT_ROW_STORAGE_PREFIX}${gridId}`, nextId);
  }
  catch (_error) {
    // Ignore storage failures (private mode, quota, etc.).
  }
}

function isMobileGridLayout()
{
  return window.matchMedia(`(max-width: ${COLUMN_RESIZE_MOBILE_MAX}px)`).matches;
}

function getDataGridKey(grid)
{
  return String(grid.dataset.grid || grid.id || '');
}

function resolveDataGridRoot(root)
{
  if (!(root instanceof HTMLElement)) {
    return null;
  }

  if (root.classList.contains('datagrid')) {
    return root;
  }

  return root.querySelector('.datagrid');
}

function readStoredColumnWidths(gridId)
{
  try {
    const raw = localStorage.getItem(`${COLUMN_WIDTH_STORAGE_PREFIX}${gridId}`);
    if (!raw) {
      return null;
    }

    const parsed = JSON.parse(raw);
    return parsed && typeof parsed === 'object' ? parsed : null;
  }
  catch (_error) {
    return null;
  }
}

function writeStoredColumnWidths(gridId, widths)
{
  try {
    localStorage.setItem(
      `${COLUMN_WIDTH_STORAGE_PREFIX}${gridId}`,
      JSON.stringify(widths),
    );
  }
  catch (_error) {
    // Ignore storage failures (private mode, quota, etc.).
  }
}

function columnClassSuffix(columnKey)
{
  return String(columnKey).toLowerCase().replace(/[^a-z0-9]+/g, '_');
}

function snapColumnWidth(widthPx)
{
  const snapped = Math.round(widthPx / COLUMN_WIDTH_SNAP_PX) * COLUMN_WIDTH_SNAP_PX;
  return Math.max(COLUMN_RESIZE_MIN_PX, snapped);
}

function columnWidthClass(columnKey, widthPx)
{
  return `${COLUMN_WIDTH_CLASS_PREFIX}${columnClassSuffix(columnKey)}_w_${snapColumnWidth(widthPx)}`;
}

function clearColumnWidthClasses(grid)
{
  const staleClasses = Array.from(grid.classList).filter((className) => COLUMN_WIDTH_CLASS_PATTERN.test(className));
  staleClasses.forEach((className) => {
    grid.classList.remove(className);
  });
  grid.classList.remove('datagrid_layout_custom');
}

function applyColumnWidthClasses(grid, widthByKey)
{
  clearColumnWidthClasses(grid);

  const entries = Object.entries(widthByKey).filter(([, width]) => typeof width === 'number' && width > 0);
  if (entries.length === 0) {
    return;
  }

  entries.forEach(([columnKey, width]) => {
    grid.classList.add(columnWidthClass(columnKey, width));
  });
  grid.classList.add('datagrid_layout_custom');
}

function applyColumnLayout(grid, widthByKey = null)
{
  if (isMobileGridLayout()) {
    clearColumnWidthClasses(grid);
    return;
  }

  const gridKey = getDataGridKey(grid);
  const stored = widthByKey || readStoredColumnWidths(gridKey) || {};
  applyColumnWidthClasses(grid, stored);
}

export function initColumnLayout(root)
{
  const grid = resolveDataGridRoot(root);
  if (!(grid instanceof HTMLElement)) {
    return null;
  }

  applyColumnLayout(grid);
  return { apply: () => applyColumnLayout(grid) };
}

export function initColumnResize(root)
{
  const grid = resolveDataGridRoot(root);
  if (!(grid instanceof HTMLElement) || grid.dataset.columnResize !== '1') {
    return null;
  }

  const gridKey = getDataGridKey(grid);
  if (gridKey === '') {
    return null;
  }

  if (grid.__columnResizeInstance) {
    applyColumnLayout(grid, grid.__columnResizeInstance.widths);
    return grid.__columnResizeInstance;
  }

  const widths = { ...(readStoredColumnWidths(gridKey) || {}) };

  const apply = () => {
    applyColumnLayout(grid, widths);
  };

  let activeResize = null;

  const handlePointerDown = (event) => {
    if (isMobileGridLayout()) {
      return;
    }

    const handle = event.target.closest('.datagrid_col_resize');
    if (!(handle instanceof HTMLElement) || !grid.contains(handle)) {
      return;
    }

    const columnKey = String(handle.dataset.resizeCol || '');
    if (columnKey === '') {
      return;
    }

    event.preventDefault();
    event.stopPropagation();

    const header = handle.closest('.datagrid_heading[data-col-key]');
    if (!(header instanceof HTMLElement)) {
      return;
    }

    const startWidth = header.getBoundingClientRect().width;
    const pointerId = event.pointerId;
    handle.classList.add('datagrid_col_resize_active');
    handle.setPointerCapture(pointerId);

    activeResize = {
      columnKey,
      startX: event.clientX,
      startWidth,
      pointerId,
      handle,
    };
  };

  const handlePointerMove = (event) => {
    if (!activeResize || event.pointerId !== activeResize.pointerId) {
      return;
    }

    const deltaX = event.clientX - activeResize.startX;
    widths[activeResize.columnKey] = snapColumnWidth(activeResize.startWidth + deltaX);
    apply();
  };

  const finishResize = (event) => {
    if (!activeResize || event.pointerId !== activeResize.pointerId) {
      return;
    }

    activeResize.handle.classList.remove('datagrid_col_resize_active');
    activeResize.handle.releasePointerCapture(activeResize.pointerId);
    writeStoredColumnWidths(gridKey, widths);
    activeResize = null;
  };

  const handleWindowResize = () => {
    if (isMobileGridLayout()) {
      clearColumnWidthClasses(grid);
      return;
    }

    apply();
  };

  grid.addEventListener('pointerdown', handlePointerDown);
  grid.addEventListener('pointermove', handlePointerMove);
  grid.addEventListener('pointerup', finishResize);
  grid.addEventListener('pointercancel', finishResize);
  window.addEventListener('resize', handleWindowResize);

  apply();

  const api = {
    widths,
    apply,
    destroy() {
      grid.removeEventListener('pointerdown', handlePointerDown);
      grid.removeEventListener('pointermove', handlePointerMove);
      grid.removeEventListener('pointerup', finishResize);
      grid.removeEventListener('pointercancel', finishResize);
      window.removeEventListener('resize', handleWindowResize);
      clearColumnWidthClasses(grid);
      delete grid.__columnResizeInstance;
    },
  };

  grid.__columnResizeInstance = api;

  return api;
}

function resolveInnerDataGrid(container)
{
  if (!(container instanceof HTMLElement)) {
    return null;
  }

  if (container.classList.contains('datagrid')) {
    return container;
  }

  return container.querySelector('.datagrid');
}

function syncStateFromInnerGrid(container, state)
{
  const innerGrid = resolveInnerDataGrid(container);
  if (!(innerGrid instanceof HTMLElement)) {
    return;
  }

  if (typeof innerGrid.dataset.search !== 'undefined') {
    state.search = innerGrid.dataset.search || '';
  }
  if (typeof innerGrid.dataset.sort !== 'undefined') {
    state.sort = innerGrid.dataset.sort || '';
  }
  if (typeof innerGrid.dataset.direction !== 'undefined') {
    state.direction = innerGrid.dataset.direction || state.direction;
  }
  if (typeof innerGrid.dataset.page !== 'undefined') {
    state.page = parseInt(innerGrid.dataset.page || String(state.page), 10) || state.page;
  }
  if (typeof innerGrid.dataset.totalPages !== 'undefined') {
    state.totalPages = parseInt(innerGrid.dataset.totalPages || String(state.totalPages), 10) || state.totalPages;
  }
}

export function syncSearchInput(container, searchValue)
{
  if (!(container instanceof HTMLElement)) {
    return;
  }

  const searchInput = container.querySelector('.datagrid_search');
  if (!(searchInput instanceof HTMLInputElement)) {
    return;
  }

  const nextValue = String(searchValue ?? '');
  if (searchInput.value !== nextValue) {
    searchInput.value = nextValue;
  }
}

function initStateFromGrid(container, state)
{
  syncStateFromInnerGrid(container, state);

  const searchInput = container.querySelector('.datagrid_search');
  if (searchInput instanceof HTMLInputElement && searchInput.value !== '') {
    state.search = searchInput.value;
  }
}

export function initFullscreen(root)
{
  const grid = resolveDataGridRoot(root) || resolveInnerDataGrid(root);
  if (!(grid instanceof HTMLElement)) {
    return null;
  }

  const toggle = grid.querySelector('.datagrid_fullscreen_toggle');
  if (!(toggle instanceof HTMLButtonElement)) {
    return null;
  }

  if (grid.__fullscreenInstance) {
    return grid.__fullscreenInstance;
  }

  const enterLabel = String(toggle.dataset.labelEnter || toggle.getAttribute('aria-label') || 'Expand grid to fullscreen');
  const exitLabel = String(toggle.dataset.labelExit || 'Exit grid fullscreen');
  let previousFocus = null;

  const isFullscreen = () => grid.classList.contains('datagrid_fullscreen');

  const updateToggle = () => {
    const expanded = isFullscreen();
    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    toggle.setAttribute('aria-label', expanded ? exitLabel : enterLabel);
  };

  const exit = () => {
    if (!isFullscreen()) {
      return;
    }

    grid.classList.remove('datagrid_fullscreen');
    document.body.classList.remove('datagrid_fullscreen_active');
    updateToggle();

    if (previousFocus instanceof HTMLElement && document.contains(previousFocus)) {
      previousFocus.focus({ preventScroll: true });
    } else {
      toggle.focus({ preventScroll: true });
    }
  };

  const enter = () => {
    if (isFullscreen()) {
      return;
    }

    previousFocus = document.activeElement;
    grid.classList.add('datagrid_fullscreen');
    document.body.classList.add('datagrid_fullscreen_active');
    updateToggle();
    toggle.focus({ preventScroll: true });
  };

  const handleToggleClick = (event) => {
    const button = event.target.closest('.datagrid_fullscreen_toggle');
    if (!(button instanceof HTMLButtonElement) || !grid.contains(button)) {
      return;
    }

    event.preventDefault();
    if (isFullscreen()) {
      exit();
    } else {
      enter();
    }
  };

  const handleToggleKeydown = (event) => {
    if (event.target !== toggle) {
      return;
    }

    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      if (isFullscreen()) {
        exit();
      } else {
        enter();
      }
    }
  };

  const handleDocumentKeydown = (event) => {
    if (event.key !== 'Escape' || !isFullscreen()) {
      return;
    }

    event.preventDefault();
    exit();
  };

  grid.addEventListener('click', handleToggleClick);
  toggle.addEventListener('keydown', handleToggleKeydown);
  document.addEventListener('keydown', handleDocumentKeydown);
  updateToggle();

  const api = {
    enter,
    exit,
    toggle() {
      if (isFullscreen()) {
        exit();
      } else {
        enter();
      }
    },
    isActive: isFullscreen,
    destroy() {
      exit();
      grid.removeEventListener('click', handleToggleClick);
      toggle.removeEventListener('keydown', handleToggleKeydown);
      document.removeEventListener('keydown', handleDocumentKeydown);
      delete grid.__fullscreenInstance;
    },
  };

  grid.__fullscreenInstance = api;

  return api;
}

function fillPhantomSpacer(spacer, rowCount)
{
  if (!(spacer instanceof HTMLElement)) {
    return;
  }

  spacer.textContent = '';
  for (let index = 0; index < rowCount; index += 1) {
    const phantom = document.createElement('div');
    phantom.className = 'datagrid_virtual_phantom';
    spacer.appendChild(phantom);
  }
}

export function initVirtualScroll(root, options = {})
{
  const container = root instanceof HTMLElement ? root : null;
  const innerGrid = resolveInnerDataGrid(container) || resolveDataGridRoot(root);
  if (!(innerGrid instanceof HTMLElement)) {
    return null;
  }

  const virtualizeEnabled = options.force === true
    || options.virtualize === true
    || innerGrid.dataset.virtualize === '1';

  if (!virtualizeEnabled || isMobileGridLayout()) {
    return null;
  }

  if (innerGrid.__virtualScrollInstance?.destroy) {
    innerGrid.__virtualScrollInstance.destroy();
  }

  const body = innerGrid.querySelector('.datagrid_table > .datagrid_body');
  if (!(body instanceof HTMLElement)) {
    return null;
  }

  const sourceRows = Array.from(body.querySelectorAll('.datagrid_row:not(.datagrid_row_empty)'));
  const minRows = typeof options.minRows === 'number' ? options.minRows : VIRTUAL_MIN_ROWS;
  if (sourceRows.length < minRows) {
    return null;
  }

  const measuredHeight = sourceRows[0].getBoundingClientRect().height;
  const rowHeight = Math.max(
    VIRTUAL_ROW_HEIGHT_PX,
    Math.ceil(Number.isFinite(measuredHeight) && measuredHeight > 0 ? measuredHeight : VIRTUAL_ROW_HEIGHT_PX),
  );
  const rowStore = sourceRows.map((row) => row.cloneNode(true));

  body.classList.add('datagrid_virtual_scroll');
  body.textContent = '';

  const spacerTop = document.createElement('div');
  spacerTop.className = 'datagrid_virtual_spacer datagrid_virtual_spacer_top';
  spacerTop.setAttribute('aria-hidden', 'true');

  const windowEl = document.createElement('div');
  windowEl.className = 'datagrid_virtual_window';
  windowEl.setAttribute('role', 'presentation');

  const spacerBottom = document.createElement('div');
  spacerBottom.className = 'datagrid_virtual_spacer datagrid_virtual_spacer_bottom';
  spacerBottom.setAttribute('aria-hidden', 'true');

  body.appendChild(spacerTop);
  body.appendChild(windowEl);
  body.appendChild(spacerBottom);

  let rafId = null;

  const renderWindow = () => {
    const scrollTop = body.scrollTop;
    const viewportHeight = body.clientHeight;
    const firstVisible = Math.max(0, Math.floor(scrollTop / rowHeight) - VIRTUAL_BUFFER_ROWS);
    const visibleCount = Math.ceil(viewportHeight / rowHeight) + (VIRTUAL_BUFFER_ROWS * 2);
    const lastVisible = Math.min(rowStore.length, firstVisible + visibleCount);

    fillPhantomSpacer(spacerTop, firstVisible);
    fillPhantomSpacer(spacerBottom, Math.max(0, rowStore.length - lastVisible));

    windowEl.textContent = '';
    for (let index = firstVisible; index < lastVisible; index += 1) {
      windowEl.appendChild(rowStore[index].cloneNode(true));
    }
  };

  const scheduleRender = () => {
    if (rafId !== null) {
      return;
    }

    rafId = window.requestAnimationFrame(() => {
      rafId = null;
      renderWindow();
    });
  };

  const handleScroll = () => {
    scheduleRender();
  };

  const resolveStoredRowId = (row) => {
    if (!(row instanceof HTMLElement)) {
      return '';
    }

    return String(row.dataset.id || row.dataset.memberId || '').trim();
  };

  const api = {
    destroy() {
      if (rafId !== null) {
        window.cancelAnimationFrame(rafId);
        rafId = null;
      }

      body.removeEventListener('scroll', handleScroll);
      window.removeEventListener('resize', handleResize);
      body.classList.remove('datagrid_virtual_scroll');
      body.textContent = '';
      rowStore.forEach((row) => {
        body.appendChild(row.cloneNode(true));
      });
      delete innerGrid.__virtualScrollInstance;
    },
    refresh() {
      scheduleRender();
    },
    getRowIds() {
      return rowStore.map((row) => resolveStoredRowId(row)).filter((rowId) => rowId !== '');
    },
    scrollToRowId(rowId) {
      const targetId = String(rowId || '').trim();
      if (targetId === '') {
        return;
      }

      const index = rowStore.findIndex((row) => resolveStoredRowId(row) === targetId);
      if (index < 0) {
        return;
      }

      const viewportHeight = body.clientHeight;
      const centeredScrollTop = (index * rowHeight) - Math.floor(viewportHeight / 2) + Math.floor(rowHeight / 2);
      body.scrollTop = Math.max(0, centeredScrollTop);
      renderWindow();
    },
  };

  const handleResize = () => {
    if (isMobileGridLayout()) {
      api.destroy();
      return;
    }

    scheduleRender();
  };

  body.addEventListener('scroll', handleScroll, { passive: true });
  window.addEventListener('resize', handleResize);

  innerGrid.__virtualScrollInstance = api;
  renderWindow();

  return api;
}

function refreshGridEnhancements(container, config = {})
{
  const innerGrid = resolveInnerDataGrid(container) || resolveDataGridRoot(container);
  if (!(innerGrid instanceof HTMLElement)) {
    return;
  }

  initColumnLayout(innerGrid);
  initColumnResize(innerGrid);
  initVirtualScroll(container, config);
  initFullscreen(container);
}

function initRowNavigation(container, state, config = {})
{
  if (!(container instanceof HTMLElement)) {
    return null;
  }

  const gridId = String(config.id || getDataGridKey(container) || container.id || '');

  if (container.__rowNavigationInstance) {
    container.__rowNavigationInstance.refresh();
    return container.__rowNavigationInstance;
  }

  if (!Object.prototype.hasOwnProperty.call(state, 'currentRowId')) {
    state.currentRowId = String(config.initialRowId || readStoredCurrentRowId(gridId) || '').trim();
  }

  const getVirtualScrollInstance = () => {
    const innerGrid = resolveInnerDataGrid(container);
    return innerGrid?.__virtualScrollInstance || null;
  };

  const getRowIds = () => {
    const virtualScroll = getVirtualScrollInstance();
    if (virtualScroll && typeof virtualScroll.getRowIds === 'function') {
      return virtualScroll.getRowIds();
    }

    return getDataGridRows(container)
      .map((row) => resolveDataGridRowId(row))
      .filter((rowId) => rowId !== '');
  };

  const findRowById = (rowId) => {
    const targetId = String(rowId || '').trim();
    if (targetId === '') {
      return null;
    }

    return getDataGridRows(container).find((row) => resolveDataGridRowId(row) === targetId) || null;
  };

  const resolveCurrentRowId = () => {
    const rowIds = getRowIds();
    const storedId = String(state.currentRowId || '').trim();

    if (storedId !== '' && rowIds.includes(storedId)) {
      return storedId;
    }

    if (rowIds.length > 0) {
      return rowIds[0];
    }

    state.currentRowId = '';
    return '';
  };

  const applyCurrentRow = (options = {}) => {
    const focus = options.focus === true;
    const scroll = options.scroll === true;
    const currentId = resolveCurrentRowId();
    state.currentRowId = currentId;

    const virtualScroll = getVirtualScrollInstance();
    if (scroll && currentId !== '' && virtualScroll && typeof virtualScroll.scrollToRowId === 'function') {
      virtualScroll.scrollToRowId(currentId);
    }

    const rows = getDataGridRows(container);
    let currentRow = null;

    rows.forEach((row) => {
      const rowId = resolveDataGridRowId(row);
      const isCurrent = rowId !== '' && rowId === currentId;
      row.classList.toggle('datagrid_row_current', isCurrent);
      row.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
      row.setAttribute('tabindex', isCurrent ? '0' : '-1');
      if (isCurrent) {
        currentRow = row;
      }
    });

    if (currentId !== '') {
      writeStoredCurrentRowId(gridId, currentId);
    }
    else {
      writeStoredCurrentRowId(gridId, '');
    }

    if (currentRow instanceof HTMLElement) {
      if (focus) {
        currentRow.focus({ preventScroll: true });
      }
      else if (scroll) {
        currentRow.scrollIntoView({ block: 'nearest' });
      }
    }

    return currentRow;
  };

  const setCurrentRowId = (rowId, options = {}) => {
    const nextId = String(rowId || '').trim();
    state.currentRowId = nextId;
    return applyCurrentRow(options);
  };

  const moveCurrentRow = (delta) => {
    const rowIds = getRowIds();
    if (rowIds.length === 0) {
      return null;
    }

    const currentId = resolveCurrentRowId();
    let index = rowIds.indexOf(currentId);
    if (index < 0) {
      index = 0;
    }

    const nextIndex = Math.max(0, Math.min(rowIds.length - 1, index + delta));
    if (nextIndex === index) {
      return findRowById(currentId);
    }

    state.currentRowId = rowIds[nextIndex];
    return applyCurrentRow({ focus: true, scroll: true });
  };

  const handleKeydown = (event) => {
    if (isDataGridRowNavigationBlockedTarget(event.target)) {
      return;
    }

    const focusedRow = event.target instanceof Element
      ? event.target.closest('.datagrid_row:not(.datagrid_row_empty)')
      : null;
    if (!(focusedRow instanceof HTMLElement) || !container.contains(focusedRow)) {
      return;
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      moveCurrentRow(1);
      return;
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault();
      moveCurrentRow(-1);
      return;
    }

    if ((event.key === 'Enter' || event.key === ' ') && typeof config.onRowEnter === 'function') {
      const currentRow = findRowById(resolveCurrentRowId());
      if (!(currentRow instanceof HTMLElement)) {
        return;
      }

      event.preventDefault();
      config.onRowEnter(resolveDataGridRowId(currentRow), currentRow);
    }
  };

  const handleFocusIn = (event) => {
    const target = event.target;
    if (!(target instanceof Element) || !container.contains(target)) {
      return;
    }

    if (isDataGridRowNavigationBlockedTarget(target)) {
      return;
    }

    const row = target.closest('.datagrid_row:not(.datagrid_row_empty)');
    if (!(row instanceof HTMLElement)) {
      return;
    }

    const currentId = resolveCurrentRowId();
    const rowId = resolveDataGridRowId(row);
    if (rowId === '' || rowId === currentId) {
      return;
    }

    const currentRow = findRowById(currentId);
    if (currentRow instanceof HTMLElement) {
      window.requestAnimationFrame(() => {
        currentRow.focus({ preventScroll: true });
      });
    }
  };

  container.addEventListener('keydown', handleKeydown);
  container.addEventListener('focusin', handleFocusIn);
  applyCurrentRow();

  const api = {
    refresh(options = {}) {
      return applyCurrentRow(options);
    },
    setCurrentRowId,
    getCurrentRowId() {
      return String(state.currentRowId || '').trim();
    },
    destroy() {
      container.removeEventListener('keydown', handleKeydown);
      container.removeEventListener('focusin', handleFocusIn);
      delete container.__rowNavigationInstance;
    },
  };

  container.__rowNavigationInstance = api;

  return api;
}

const searchHotkeyGrids = new Set();
let searchHotkeyDocumentListenerAttached = false;
let lastSearchHotkeyGrid = null;

function isEditableKeyboardTarget(target)
{
  if (!(target instanceof Element)) {
    return false;
  }

  return !!target.closest(
    'input, textarea, select, [contenteditable=""], [contenteditable="true"], [contenteditable="plaintext-only"]',
  );
}

function isSearchHotkeyGridVisible(grid)
{
  if (!(grid instanceof HTMLElement)) {
    return false;
  }

  return grid.getClientRects().length > 0;
}

function resolveSearchHotkeyGrid(eventTarget)
{
  if (eventTarget instanceof Element) {
    for (const grid of searchHotkeyGrids) {
      if (grid.contains(eventTarget) && isSearchHotkeyGridVisible(grid)) {
        return grid;
      }
    }
  }

  if (lastSearchHotkeyGrid instanceof HTMLElement
    && searchHotkeyGrids.has(lastSearchHotkeyGrid)
    && isSearchHotkeyGridVisible(lastSearchHotkeyGrid)) {
    return lastSearchHotkeyGrid;
  }

  for (const grid of searchHotkeyGrids) {
    if (isSearchHotkeyGridVisible(grid) && grid.querySelector('.datagrid_search')) {
      return grid;
    }
  }

  return null;
}

function handleDocumentSearchHotkey(event)
{
  if (event.key !== '/' || event.altKey || event.metaKey || event.ctrlKey) {
    return;
  }

  if (isEditableKeyboardTarget(event.target)) {
    return;
  }

  const grid = resolveSearchHotkeyGrid(event.target);
  if (!(grid instanceof HTMLElement)) {
    return;
  }

  const searchInput = grid.querySelector('.datagrid_search');
  if (!(searchInput instanceof HTMLInputElement)) {
    return;
  }

  event.preventDefault();
  searchInput.focus({ preventScroll: true });
}

function handleSearchHotkeyGridFocusIn(event)
{
  const grid = event.currentTarget;
  if (searchHotkeyGrids.has(grid)) {
    lastSearchHotkeyGrid = grid;
  }
}

function registerSearchHotkeyGrid(grid)
{
  if (!(grid instanceof HTMLElement) || searchHotkeyGrids.has(grid)) {
    return;
  }

  searchHotkeyGrids.add(grid);
  grid.addEventListener('focusin', handleSearchHotkeyGridFocusIn);

  if (!searchHotkeyDocumentListenerAttached) {
    document.addEventListener('keydown', handleDocumentSearchHotkey);
    searchHotkeyDocumentListenerAttached = true;
  }
}

function unregisterSearchHotkeyGrid(grid)
{
  if (!(grid instanceof HTMLElement) || !searchHotkeyGrids.has(grid)) {
    return;
  }

  grid.removeEventListener('focusin', handleSearchHotkeyGridFocusIn);
  searchHotkeyGrids.delete(grid);

  if (lastSearchHotkeyGrid === grid) {
    lastSearchHotkeyGrid = null;
  }

  if (searchHotkeyGrids.size === 0 && searchHotkeyDocumentListenerAttached) {
    document.removeEventListener('keydown', handleDocumentSearchHotkey);
    searchHotkeyDocumentListenerAttached = false;
  }
}

export function createDataGrid(config)
{
  if (!config || !config.id || !config.endpoint)
  {
    console.error('Missing id or endpoint in config', config);
    throw new Error("DataGrid requires id and endpoint");
  }

  const containerId = config.containerId || config.id;
  const grid = document.getElementById(containerId);
  if (!grid) {
    console.error('Grid element not found:', containerId);
    return null;
  }

  // Prevent double init
  if (grid.__datagridInstance)
  {
    console.warn('Grid already initialized, returning existing instance');
    return grid.__datagridInstance;
  }

  const body = grid.querySelector(".datagrid_body");
  if (!body) {
    console.error('Datagrid body not found for grid:', config.id);
    return null;
  }

  let abortController = null;
  let searchDebounceId = null;
  let restoreSearchFocus = false;
  let searchSelectionStart = null;
  let searchSelectionEnd = null;

  const state = {
    page: parseInt(grid.dataset.page || "1", 10),
    totalPages: parseInt(grid.dataset.totalPages || "1", 10),
    search: grid.dataset.search || "",
    sort: grid.dataset.sort || "",
    direction: grid.dataset.direction || "asc",
    currentRowId: String(config.initialRowId || readStoredCurrentRowId(config.id) || '').trim(),
  };

  let rowNavigation = null;

  function refreshRowNavigation(options = {})
  {
    rowNavigation = initRowNavigation(grid, state, config);
    if (rowNavigation && typeof rowNavigation.refresh === 'function') {
      rowNavigation.refresh(options);
    }
  }

  function syncDataset()
  {
    grid.dataset.page = String(state.page);
    grid.dataset.search = state.search;
    grid.dataset.sort = state.sort;
    grid.dataset.direction = state.direction;
  }

  function buildPayload()
  {
    const payload = {
      page: state.page,
      search: state.search,
      sort: state.sort,
      direction: state.direction
    };
    return payload;
  }

  function focusSearchInput()
  {
    const searchInput = grid.querySelector('.datagrid_search');
    if (!(searchInput instanceof HTMLInputElement)) {
      return;
    }

    searchInput.focus({ preventScroll: true });

    if (searchSelectionStart !== null && searchSelectionEnd !== null) {
      try {
        searchInput.setSelectionRange(searchSelectionStart, searchSelectionEnd);
      }
      catch (_error) {
        // Some input types do not support selection ranges.
      }
    }
  }

  function clearSearchDebounce()
  {
    if (searchDebounceId !== null) {
      window.clearTimeout(searchDebounceId);
      searchDebounceId = null;
    }
  }

  async function reload()
  {
    if (abortController)
    {
      abortController.abort();
    }

    abortController = new AbortController();

    syncDataset();

    // Use GET for endpoints that are data fetch (like sites/grid)
    let url = config.endpoint;
    let fetchOptions = {
      method: "GET",
      signal: abortController.signal
    };
    
    // For endpoints that use query-parameter pagination/search/sort, use GET
    const baseEndpoint = config.endpoint.split('?')[0];
    const isGetQueryGridEndpoint = baseEndpoint.includes('sites/grid')
      || baseEndpoint.includes('members/grid')
      || baseEndpoint.includes('audit/grid')
      || baseEndpoint.includes('audit/member/grid')
      || baseEndpoint.includes('invites/history/grid');
    
    if (isGetQueryGridEndpoint) {
      const payload = buildPayload();
      
      // Parse existing query params from endpoint
      const existingParams = new URLSearchParams(config.endpoint.includes('?') ? config.endpoint.split('?')[1] : '');
      
      // Merge with payload
      Object.entries(payload).forEach(([key, value]) => {
        if (value) existingParams.set(key, value);
      });
      
      url = `${baseEndpoint}?${existingParams.toString()}`;
    } else {
      // For other endpoints, use POST with JSON body
      fetchOptions = {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(buildPayload()),
        signal: abortController.signal
      };
    }

    const response = await fetch(url, fetchOptions);

    // Read response body once since it can only be consumed once
    const text = await response.text();

    if (!response.ok)
    {
      console.error(`DataGrid request failed: ${response.status}`, text);
      return;
    }

    let result;
    try {
      result = JSON.parse(text);
    } catch (e) {
      console.error('JSON parse error:', e);
      console.error('Response text:', text.substring(0, 500));
      return;
    }

    if (result.status !== "success")
    {
      console.warn('Invalid DataGrid response:', result);
      console.error(`Invalid DataGrid response status: ${result.status}`, result);
      return;
    }

    if (typeof result.html === "string")
    {
      PC.setHTML(body, result.html);
      refreshGridEnhancements(grid, config);
      refreshRowNavigation({ scroll: true });
      syncStateFromInnerGrid(grid, state);
      syncDataset();
      syncSearchInput(grid, state.search);
    }

    if (result.meta)
    {
      if (typeof result.meta.page !== "undefined")
      {
        state.page = parseInt(result.meta.page, 10);
      }

      if (typeof result.meta.totalPages !== "undefined")
      {
        state.totalPages = parseInt(result.meta.totalPages, 10);
      }
    }

    syncDataset();

    const innerGrid = resolveInnerDataGrid(grid);
    const rowCount = innerGrid
      ? innerGrid.querySelectorAll('.datagrid_row:not(.datagrid_row_empty)').length
      : grid.querySelectorAll('.datagrid_row:not(.datagrid_row_empty)').length;
    document.dispatchEvent(new CustomEvent('paycal:datagrid-reloaded', {
      detail: {
        gridId: config.id,
        state: { ...state },
        rowCount,
        currentRowId: String(state.currentRowId || '').trim(),
      }
    }));

    syncSearchInput(grid, state.search);

    if (restoreSearchFocus) {
      restoreSearchFocus = false;
      focusSearchInput();
    }
  }

  function handleSearchInput(event)
  {
    const searchInput = event.target.closest('.datagrid_search');
    if (!(searchInput instanceof HTMLInputElement) || !grid.contains(searchInput)) {
      return;
    }

    const search = String(searchInput.value || '');
    searchSelectionStart = searchInput.selectionStart;
    searchSelectionEnd = searchInput.selectionEnd;

    clearSearchDebounce();
    searchDebounceId = window.setTimeout(() => {
      searchDebounceId = null;
      restoreSearchFocus = true;
      state.search = search;
      state.page = 1;
      reload();
    }, 250);
  }

  function handleSort(e)
  {
    const header = e.target.closest(".datagrid_sort");
    if (!header || !grid.contains(header)) return;

    const column = header.dataset.column;
    if (!column) return;

    if (state.sort === column)
    {
      state.direction = state.direction === "asc" ? "desc" : "asc";
    }
    else
    {
      state.sort = column;
      state.direction = "asc";
    }

    state.page = 1;
    reload();
  }

  function handlePaginationClick(e)
  {
    const button = e.target.closest('.datagrid_pagination_btn');
    if (!(button instanceof HTMLButtonElement) || !grid.contains(button) || button.disabled) {
      return;
    }

    if (button.dataset.direction === 'prev') {
      state.page = Math.max(1, state.page - 1);
      reload();
      return;
    }

    if (button.dataset.direction === 'next') {
      state.page = Math.min(state.totalPages || 1, state.page + 1);
      reload();
      return;
    }

    const pageNumber = parseInt(button.dataset.page || '', 10);
    if (Number.isFinite(pageNumber) && pageNumber >= 1 && pageNumber <= (state.totalPages || 1)) {
      state.page = pageNumber;
      reload();
    }
  }

  function handleRowClick(e)
  {
    if (e.target.closest(".datagrid_action")) return;

    const row = e.target.closest(".datagrid_row");
    if (!row || !grid.contains(row)) return;

    const rowId = resolveDataGridRowId(row);
    if (rowId !== '' && rowNavigation && typeof rowNavigation.setCurrentRowId === 'function') {
      rowNavigation.setCurrentRowId(rowId);
    }

    if (typeof config.onRowClick === "function")
    {
      config.onRowClick(row.dataset.id, row);
    }
  }

  function bindEvents()
  {
    grid.addEventListener("click", handleSort);
    grid.addEventListener("click", handlePaginationClick);
    grid.addEventListener("click", handleRowClick);
    grid.addEventListener("input", handleSearchInput);
  }

  function unbindEvents()
  {
    grid.removeEventListener("click", handleSort);
    grid.removeEventListener("click", handlePaginationClick);
    grid.removeEventListener("click", handleRowClick);
    grid.removeEventListener("input", handleSearchInput);
    clearSearchDebounce();
  }

  function destroy()
  {
    if (abortController)
    {
      abortController.abort();
    }

    restoreSearchFocus = false;
    unbindEvents();
    if (rowNavigation && typeof rowNavigation.destroy === 'function') {
      rowNavigation.destroy();
      rowNavigation = null;
    }
    unregisterSearchHotkeyGrid(grid);
    const innerGrid = resolveInnerDataGrid(grid) || resolveDataGridRoot(grid);
    if (innerGrid instanceof HTMLElement) {
      if (innerGrid.__virtualScrollInstance?.destroy) {
        innerGrid.__virtualScrollInstance.destroy();
      }
      if (innerGrid.__columnResizeInstance?.destroy) {
        innerGrid.__columnResizeInstance.destroy();
      }
      if (innerGrid.__fullscreenInstance?.destroy) {
        innerGrid.__fullscreenInstance.destroy();
      }
    }
    delete grid.__datagridInstance;
  }

  bindEvents();
  registerSearchHotkeyGrid(grid);
  initStateFromGrid(grid, state);
  syncDataset();
  syncSearchInput(grid, state.search);
  refreshGridEnhancements(grid, config);
  refreshRowNavigation();

  const api = {
    reload,
    destroy,
    getState()
    {
      return { ...state };
    },
    getCurrentRowId()
    {
      return String(state.currentRowId || '').trim();
    },
    setCurrentRowId(rowId, options = {})
    {
      if (rowNavigation && typeof rowNavigation.setCurrentRowId === 'function') {
        return rowNavigation.setCurrentRowId(rowId, options);
      }

      state.currentRowId = String(rowId || '').trim();
      writeStoredCurrentRowId(config.id, state.currentRowId);
      return null;
    },
    setSearch(value, options = {})
    {
      state.search = String(value);
      state.page = 1;
      restoreSearchFocus = options.restoreFocus === true;
      reload();
    },
    setPage(page)
    {
      state.page = parseInt(page, 10) || 1;
      reload();
    },
    setSort(column, direction = "asc")
    {
      state.sort = column;
      state.direction = direction === "desc" ? "desc" : "asc";
      state.page = 1;
      reload();
    }
  };

  grid.__datagridInstance = api;

  return api;
}
