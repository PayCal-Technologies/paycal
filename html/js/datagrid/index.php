<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();

CORS::renderContentType('text/javascript');
Javascript::renderDocBlock();
?>

import PC from "<?php echo Environment::appURL('js/'); ?>";
import { formatPhpTemplate } from '/js/core/template.js';


window.PAYCAL_DEBUG = typeof window.PAYCAL_DEBUG !== 'undefined' ? window.PAYCAL_DEBUG : false;

const COLUMN_VISIBILITY_STORAGE_PREFIX = 'paycal:datagrid:columns:';
const DATAGRID_KEYBOARD_PAGE_STEP = 25;

const DATAGRID_T = {
  reloadFailed: '<?php echo addslashes(Strings::i18n('DATAGRID_RELOAD_FAILED')); ?>',
  invalidResponse: '<?php echo addslashes(Strings::i18n('DATAGRID_INVALID_RESPONSE')); ?>',
  columnShown: '<?php echo addslashes(Strings::i18n('DATAGRID_COLUMN_SHOWN')); ?>',
  columnHidden: '<?php echo addslashes(Strings::i18n('DATAGRID_COLUMN_HIDDEN')); ?>',
};

const DATAGRID_KEYBOARD_INTERACTIVE_SELECTOR = [
  'a',
  'button',
  'input',
  'select',
  'textarea',
  'label',
  '[contenteditable="true"]',
  '.datagrid_action',
  '.datagrid_sort',
  '.datagrid_search',
  '.datagrid_pager',
  '.datagrid_pagination',
  '.datagrid_column_toggle',
  '.datagrid_column_toggle_input',
  '.datagrid_column_menu',
  '.datagrid_column_menu_toggle',
  '.datagrid_column_menu_panel',
].join(', ');

function isElementVisible(element)
{
  return element instanceof HTMLElement
    && !element.hidden
    && element.getClientRects().length > 0
    && !element.closest('[hidden]');
}

function isTextInputTarget(target)
{
  if (!(target instanceof Element)) {
    return false;
  }

  if (target instanceof HTMLTextAreaElement || target instanceof HTMLSelectElement) {
    return true;
  }

  if (target instanceof HTMLInputElement) {
    return !['button', 'checkbox', 'color', 'file', 'hidden', 'image', 'radio', 'range', 'reset', 'submit'].includes(target.type);
  }

  return target.closest('[contenteditable="true"]') instanceof HTMLElement;
}

function isKeyboardSpace(event)
{
  return event.key === ' '
    || event.key === 'Space'
    || event.key === 'Spacebar'
    || event.code === 'Space';
}

function scrollDocumentToTop()
{
  const scrollingElement = document.scrollingElement || document.documentElement;
  if (scrollingElement instanceof Element && typeof scrollingElement.scrollTo === 'function') {
    scrollingElement.scrollTo({ top: 0, left: 0, behavior: 'auto' });
    return;
  }

  window.scrollTo(0, 0);
}

function scrollDocumentToBottom()
{
  const scrollingElement = document.scrollingElement || document.documentElement;
  const maxTop = scrollingElement instanceof Element
    ? Math.max(0, scrollingElement.scrollHeight - scrollingElement.clientHeight)
    : Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
  if (scrollingElement instanceof Element && typeof scrollingElement.scrollTo === 'function') {
    scrollingElement.scrollTo({ top: maxTop, left: 0, behavior: 'auto' });
    return;
  }

  window.scrollTo(0, maxTop);
}

function resolveKeyboardRoot(root)
{
  if (root instanceof HTMLElement) {
    return root;
  }

  if (typeof root === 'string' && root.trim() !== '') {
    const selector = root.trim();
    let element = document.getElementById(selector);
    if (!(element instanceof HTMLElement)) {
      try {
        element = document.querySelector(selector);
      } catch (_error) {
        element = null;
      }
    }
    return element instanceof HTMLElement ? element : null;
  }

  return null;
}

export function bindDataGridKeyboardNavigation(config = {})
{
  const root = resolveKeyboardRoot(config.root);
  if (!(root instanceof HTMLElement)) {
    return null;
  }

  if (root.__datagridKeyboardNavigation) {
    if (typeof root.__datagridKeyboardNavigation.syncRows === 'function') {
      root.__datagridKeyboardNavigation.syncRows();
    }
    return root.__datagridKeyboardNavigation;
  }

  const rowSelector = typeof config.rowSelector === 'string' && config.rowSelector.trim() !== ''
    ? config.rowSelector
    : '.datagrid_body .datagrid_row:not(.datagrid_row_empty)';
  const searchSelector = typeof config.searchSelector === 'string' && config.searchSelector.trim() !== ''
    ? config.searchSelector
    : '.datagrid_search';
  const activeRowClass = typeof config.activeRowClass === 'string' && config.activeRowClass.trim() !== ''
    ? config.activeRowClass
    : 'datagrid_row_keyboard_active';
  const interactiveSelector = typeof config.interactiveSelector === 'string' && config.interactiveSelector.trim() !== ''
    ? config.interactiveSelector
    : DATAGRID_KEYBOARD_INTERACTIVE_SELECTOR;
  const pageStep = Number.isFinite(Number(config.pageStep))
    ? Math.max(1, Number(config.pageStep))
    : DATAGRID_KEYBOARD_PAGE_STEP;
  const isEnabled = typeof config.isEnabled === 'function'
    ? config.isEnabled
    : () => true;

  let activeRow = null;
  let autofocusPending = config.autofocusSearch === true;

  const getRows = () => Array.from(root.querySelectorAll(rowSelector))
    .filter((row) => row instanceof HTMLElement && isElementVisible(row));

  const getSearchInput = () => Array.from(root.querySelectorAll(searchSelector))
    .find((input) => input instanceof HTMLInputElement && isElementVisible(input)) || null;

  const clearActiveRow = () => {
    root.querySelectorAll(`.${activeRowClass}`).forEach((row) => {
      if (row instanceof HTMLElement) {
        row.classList.remove(activeRowClass);
      }
    });
  };

  const syncRows = () => {
    const rows = getRows();
    rows.forEach((row) => {
      row.tabIndex = row === activeRow ? 0 : -1;
    });

    if (!(activeRow instanceof HTMLElement) || !rows.includes(activeRow)) {
      clearActiveRow();
      activeRow = null;
    }

    return rows;
  };

  const setActiveRow = (row, options = {}) => {
    if (!(row instanceof HTMLElement) || !isElementVisible(row)) {
      return false;
    }

    clearActiveRow();
    activeRow = row;
    row.classList.add(activeRowClass);
    syncRows();

    if (options.scroll !== false && typeof row.scrollIntoView === 'function') {
      row.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }
    if (options.focus !== false) {
      row.focus({ preventScroll: true });
    }

    return true;
  };

  const focusSearch = (options = {}) => {
    const searchInput = getSearchInput();
    if (!(searchInput instanceof HTMLInputElement)) {
      return false;
    }

    clearActiveRow();
    activeRow = null;
    syncRows();

    if (options.scrollTop === true) {
      scrollDocumentToTop();
    }
    searchInput.focus({ preventScroll: options.scrollTop === true });
    if (options.select !== false) {
      searchInput.select();
    }

    return true;
  };

  const shouldIgnoreGlobalShortcut = (event) => {
    if (!isEnabled() || !isElementVisible(root) || isTextInputTarget(event.target)) {
      return true;
    }

    const activeDialog = document.querySelector('dialog[open]');
    return activeDialog instanceof HTMLDialogElement && !activeDialog.contains(root);
  };

  const maybeAutofocusSearch = () => {
    if (!autofocusPending || !isEnabled() || !isElementVisible(root)) {
      return false;
    }

    const activeDialog = document.querySelector('dialog[open]');
    if (activeDialog instanceof HTMLDialogElement && !activeDialog.contains(root)) {
      return false;
    }

    if (isTextInputTarget(document.activeElement)) {
      return false;
    }

    const didFocus = focusSearch({ select: false });
    if (didFocus) {
      autofocusPending = false;
    }

    return didFocus;
  };

  const focusRowAt = (index, options = {}) => {
    const rows = syncRows();
    if (rows.length === 0) {
      return false;
    }

    const targetIndex = Math.max(0, Math.min(rows.length - 1, index));
    return setActiveRow(rows[targetIndex], options);
  };

  const focusRelativeRow = (offset, options = {}) => {
    const rows = syncRows();
    if (rows.length === 0) {
      return false;
    }

    const currentIndex = activeRow instanceof HTMLElement ? rows.indexOf(activeRow) : -1;
    if (currentIndex < 0) {
      return focusRowAt(offset > 0 ? 0 : rows.length - 1, options);
    }

    return focusRowAt(currentIndex + offset, options);
  };

  const focusContextAction = (row) => {
    if (typeof config.onContextAction === 'function' && config.onContextAction(row) === true) {
      return true;
    }

    const action = row instanceof HTMLElement
      ? row.querySelector('.datagrid_action:not([disabled])')
      : null;
    if (action instanceof HTMLElement) {
      action.focus({ preventScroll: true });
      return true;
    }

    return false;
  };

  const activateRow = (row, event) => {
    if (!(row instanceof HTMLElement)) {
      return false;
    }

    if (typeof config.onActivate === 'function') {
      return config.onActivate(row, event) === true;
    }

    const content = row.querySelector('.datagrid_row_content');
    if (content instanceof HTMLElement) {
      content.click();
      return true;
    }

    row.click();
    return true;
  };

  const handleSearchKeydown = (event) => {
    if (!(event.target instanceof HTMLInputElement) || !event.target.classList.contains('datagrid_search')) {
      return false;
    }

    if (event.key === 'ArrowRight') {
      const valueLength = String(event.target.value || '').length;
      const selectionStart = Number.isInteger(event.target.selectionStart) ? event.target.selectionStart : valueLength;
      const selectionEnd = Number.isInteger(event.target.selectionEnd) ? event.target.selectionEnd : selectionStart;
      if (selectionStart === valueLength
        && selectionEnd === valueLength
        && focusFirstColumnVisibilityToggle(event.target.closest('.datagrid[data-grid]'))) {
        event.preventDefault();
        return true;
      }
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      focusRowAt(0);
      return true;
    }

    if (event.key === 'Home') {
      event.preventDefault();
      scrollDocumentToTop();
      focusRowAt(0, { scroll: false });
      return true;
    }

    if (event.key === 'End') {
      event.preventDefault();
      scrollDocumentToBottom();
      focusRowAt(syncRows().length - 1, { scroll: false });
      return true;
    }

    if (event.key === 'PageDown') {
      event.preventDefault();
      focusRowAt(pageStep - 1);
      return true;
    }

    if (event.key === 'PageUp') {
      event.preventDefault();
      focusRowAt(0);
      return true;
    }

    return false;
  };

  const handleRowKeydown = (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const row = target?.closest(rowSelector);
    if (!(row instanceof HTMLElement) || !root.contains(row)) {
      return false;
    }

    if (target instanceof Element && target.closest(interactiveSelector)) {
      return false;
    }

    setActiveRow(row, { focus: false, scroll: false });

    const currentRows = syncRows();
    const currentIndex = currentRows.indexOf(row);
    const isContextKey = event.key === 'ContextMenu'
      || (event.shiftKey && event.key === 'F10')
      || (event.shiftKey && event.key === 'Enter')
      || (event.shiftKey && isKeyboardSpace(event));

    if (isContextKey) {
      event.preventDefault();
      focusContextAction(row);
      return true;
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      focusRelativeRow(1);
      return true;
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault();
      if (currentIndex <= 0) {
        focusSearch({ select: false });
        return true;
      }
      focusRelativeRow(-1);
      return true;
    }

    if (event.key === 'Home') {
      event.preventDefault();
      scrollDocumentToTop();
      focusRowAt(0, { scroll: false });
      return true;
    }

    if (event.key === 'End') {
      event.preventDefault();
      scrollDocumentToBottom();
      focusRowAt(currentRows.length - 1, { scroll: false });
      return true;
    }

    if (event.key === 'PageDown') {
      event.preventDefault();
      focusRowAt(currentIndex + pageStep);
      return true;
    }

    if (event.key === 'PageUp') {
      event.preventDefault();
      focusRowAt(Math.max(0, currentIndex - pageStep));
      return true;
    }

    if (event.key === 'Enter' || isKeyboardSpace(event)) {
      event.preventDefault();
      activateRow(row, event);
      return true;
    }

    return false;
  };

  const handleKeydown = (event) => {
    if (!isEnabled()) {
      return;
    }

    if (event.key === '/' && !isTextInputTarget(event.target)) {
      event.preventDefault();
      focusSearch({ scrollTop: true });
      return;
    }

    if (handleSearchKeydown(event)) {
      return;
    }

    handleRowKeydown(event);
  };

  const handleFocusIn = (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const row = target?.closest(rowSelector);
    if (row instanceof HTMLElement && root.contains(row)) {
      setActiveRow(row, { focus: false, scroll: false });
    }
  };

  const handleReload = () => {
    syncRows();
    if (autofocusPending) {
      window.setTimeout(maybeAutofocusSearch, 0);
    }
  };

  const handleDocumentKeydown = (event) => {
    if (event.defaultPrevented || event.key !== '/' || shouldIgnoreGlobalShortcut(event)) {
      return;
    }

    event.preventDefault();
    focusSearch({ scrollTop: true });
  };

  root.addEventListener('keydown', handleKeydown);
  root.addEventListener('focusin', handleFocusIn);
  document.addEventListener('paycal:datagrid-reloaded', handleReload);
  document.addEventListener('keydown', handleDocumentKeydown);
  syncRows();

  const api = {
    syncRows,
    focusSearch,
    destroy() {
      root.removeEventListener('keydown', handleKeydown);
      root.removeEventListener('focusin', handleFocusIn);
      document.removeEventListener('paycal:datagrid-reloaded', handleReload);
      document.removeEventListener('keydown', handleDocumentKeydown);
      root.querySelectorAll(rowSelector).forEach((row) => {
        if (row instanceof HTMLElement) {
          row.classList.remove(activeRowClass);
          row.tabIndex = 0;
        }
      });
      delete root.__datagridKeyboardNavigation;
    },
  };

  root.__datagridKeyboardNavigation = api;

  if (config.autofocusSearch === true) {
    window.setTimeout(() => {
      maybeAutofocusSearch();
    }, 0);
  }

  return api;
}

function resolveColumnVisibilityGrid(root)
{
  if (!(root instanceof HTMLElement)) {
    return null;
  }

  if (root.dataset.columnVisibility === '1') {
    return root;
  }

  return root.querySelector('[data-column-visibility="1"]');
}

function readStoredColumnVisibility(gridId)
{
  try {
    const raw = localStorage.getItem(`${COLUMN_VISIBILITY_STORAGE_PREFIX}${gridId}`);
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

function writeStoredColumnVisibility(gridId, visibility)
{
  try {
    localStorage.setItem(
      `${COLUMN_VISIBILITY_STORAGE_PREFIX}${gridId}`,
      JSON.stringify(visibility),
    );
  }
  catch (_error) {
    // Ignore storage failures (private mode, quota, etc.).
  }
}

function countVisibleDataColumns(grid)
{
  const headers = Array.from(grid.querySelectorAll('.datagrid_header_content .datagrid_heading[data-col-key]'));
  return headers.filter((header) => !header.classList.contains('datagrid_col_hidden')).length;
}

function syncVisibleColumnState(grid)
{
  if (!(grid instanceof HTMLElement)) {
    return;
  }

  const headerContent = grid.querySelector('.datagrid_header_content');
  const visibleCount = headerContent instanceof HTMLElement
    ? Array.from(headerContent.children)
      .filter((child) => child instanceof HTMLElement
        && !child.classList.contains('datagrid_col_hidden')
        && !child.hasAttribute('aria-hidden'))
      .length
    : 0;

  grid.dataset.visibleColumns = String(visibleCount);
}

function announceColumnVisibilityStatus(grid, columnLabel, visible)
{
  const status = grid.querySelector('.datagrid_column_strip_status')
    || grid.querySelector('.datagrid_column_menu_panel .datagrid_column_strip_status');
  if (!(status instanceof HTMLElement)) {
    return;
  }

  status.textContent = visible
    ? formatPhpTemplate(DATAGRID_T.columnShown, [columnLabel])
    : formatPhpTemplate(DATAGRID_T.columnHidden, [columnLabel]);
}

function getColumnVisibilityMenuFocusables(panel)
{
  if (!(panel instanceof HTMLElement)) {
    return [];
  }

  return Array.from(panel.querySelectorAll(
    'button:not([disabled]), input:not([disabled]), [href], [tabindex]:not([tabindex="-1"])',
  )).filter((element) => element instanceof HTMLElement && !element.closest('[hidden]'));
}

function getColumnVisibilityToggleInputs(scope)
{
  if (!(scope instanceof HTMLElement)) {
    return [];
  }

  return Array.from(scope.querySelectorAll('.datagrid_column_toggle_input[data-col-key]'))
    .filter((element) => element instanceof HTMLInputElement
      && !element.disabled
      && element.getClientRects().length > 0
      && !element.closest('[hidden]'));
}

function focusFirstDatagridRow(grid)
{
  if (!(grid instanceof HTMLElement)) {
    return false;
  }

  const row = Array.from(grid.querySelectorAll('.datagrid_body .datagrid_row:not(.datagrid_row_empty)'))
    .find((candidate) => candidate instanceof HTMLElement
      && candidate.getClientRects().length > 0
      && !candidate.closest('[hidden]'));
  if (!(row instanceof HTMLElement)) {
    return false;
  }

  row.focus({ preventScroll: true });
  if (typeof row.scrollIntoView === 'function') {
    row.scrollIntoView({ block: 'nearest', inline: 'nearest' });
  }

  return true;
}

function focusDatagridSearch(grid)
{
  if (!(grid instanceof HTMLElement)) {
    return false;
  }

  const search = grid.querySelector('.datagrid_search');
  if (!(search instanceof HTMLInputElement)
    || search.getClientRects().length === 0
    || search.closest('[hidden]')) {
    return false;
  }

  search.focus({ preventScroll: true });
  return true;
}

function focusFirstColumnVisibilityToggle(grid)
{
  if (!(grid instanceof HTMLElement)) {
    return false;
  }

  const toggles = getColumnVisibilityToggleInputs(
    grid.querySelector('.datagrid_column_strip') || grid.querySelector('.datagrid_column_menu_panel') || grid,
  );
  if (!(toggles[0] instanceof HTMLInputElement)) {
    return false;
  }

  toggles[0].focus({ preventScroll: true });
  return true;
}

function initColumnVisibilityMenu(grid)
{
  const menu = grid.querySelector('.datagrid_column_menu');
  if (!(menu instanceof HTMLElement) || menu.dataset.columnMenuBound === '1') {
    return null;
  }

  const toggle = menu.querySelector('.datagrid_column_menu_toggle');
  const panel = menu.querySelector('.datagrid_column_menu_panel');
  if (!(toggle instanceof HTMLButtonElement) || !(panel instanceof HTMLElement)) {
    return null;
  }

  menu.dataset.columnMenuBound = '1';
  let previousFocus = null;

  const closePanel = (restoreFocus = true) => {
    panel.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
    if (restoreFocus && previousFocus instanceof HTMLElement) {
      previousFocus.focus();
    }
    previousFocus = null;
  };

  const openPanel = () => {
    grid.querySelectorAll('.datagrid_column_menu_panel:not([hidden])').forEach((openPanelEl) => {
      if (openPanelEl instanceof HTMLElement && openPanelEl !== panel) {
        openPanelEl.hidden = true;
      }
    });

    previousFocus = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    panel.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
    const focusables = getColumnVisibilityMenuFocusables(panel);
    if (focusables[0] instanceof HTMLElement) {
      focusables[0].focus();
    }
  };

  const handleDocumentClick = (event) => {
    if (panel.hidden || !menu.contains(event.target)) {
      closePanel(false);
    }
  };

  const handleDocumentKeydown = (event) => {
    if (panel.hidden) {
      return;
    }

    if (event.key === 'Escape') {
      event.preventDefault();
      closePanel(true);
      return;
    }

    if (event.key !== 'Tab') {
      return;
    }

    const focusables = getColumnVisibilityMenuFocusables(panel);
    if (focusables.length === 0) {
      return;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const active = document.activeElement;
    if (event.shiftKey && active === first) {
      event.preventDefault();
      last.focus();
      return;
    }

    if (!event.shiftKey && active === last) {
      event.preventDefault();
      first.focus();
    }
  };

  toggle.addEventListener('click', (event) => {
    event.preventDefault();
    if (panel.hidden) {
      openPanel();
      return;
    }
    closePanel(true);
  });

  document.addEventListener('click', handleDocumentClick);
  document.addEventListener('keydown', handleDocumentKeydown);

  return {
    destroy() {
      document.removeEventListener('click', handleDocumentClick);
      document.removeEventListener('keydown', handleDocumentKeydown);
      delete menu.dataset.columnMenuBound;
    },
  };
}

function applyColumnVisibility(grid, visibilityByKey)
{
  const table = grid.querySelector('.datagrid_table');
  // Scope to grid headers/cells only — column-toggle checkboxes also carry data-col-key
  // and must stay focusable (never aria-hidden while focused).
  const columnElements = Array.from(grid.querySelectorAll('.datagrid_table [data-col-key]'));

  columnElements.forEach((element) => {
    const columnKey = String(element.dataset.colKey || '');
    if (columnKey === '') {
      return;
    }

    const visible = visibilityByKey[columnKey] !== false;
    element.classList.toggle('datagrid_col_hidden', !visible);
    if (visible) {
      element.removeAttribute('aria-hidden');
    } else {
      element.setAttribute('aria-hidden', 'true');
    }
  });

  if (table instanceof HTMLElement) {
    const visibleCount = countVisibleDataColumns(grid);
    const hasActions = !!grid.querySelector('.datagrid_heading_actions');
    table.setAttribute('aria-colcount', String(visibleCount + (hasActions ? 1 : 0)));
  }

  syncVisibleColumnState(grid);

  const toggles = Array.from(grid.querySelectorAll('.datagrid_column_toggle_input[data-col-key]'));
  toggles.forEach((toggle) => {
    if (!(toggle instanceof HTMLInputElement)) {
      return;
    }

    const columnKey = String(toggle.dataset.colKey || '');
    if (columnKey === '') {
      return;
    }

    toggle.checked = visibilityByKey[columnKey] !== false;
  });
}

export function initColumnVisibility(root)
{
  const grid = resolveColumnVisibilityGrid(root);
  if (!(grid instanceof HTMLElement)) {
    return null;
  }

  const gridId = String(grid.dataset.grid || grid.id || '');
  if (gridId === '') {
    return null;
  }

  if (grid.__columnVisibilityInstance) {
    applyColumnVisibility(grid, grid.__columnVisibilityInstance.visibility);
    initColumnVisibilityMenu(grid);
    return grid.__columnVisibilityInstance;
  }

  const visibility = {};
  const toggles = Array.from(grid.querySelectorAll('.datagrid_column_toggle_input[data-col-key]'));
  const stored = readStoredColumnVisibility(gridId);

  toggles.forEach((toggle) => {
    if (!(toggle instanceof HTMLInputElement)) {
      return;
    }

    const columnKey = String(toggle.dataset.colKey || '');
    if (columnKey === '') {
      return;
    }

    visibility[columnKey] = stored && Object.prototype.hasOwnProperty.call(stored, columnKey)
      ? stored[columnKey] !== false
      : toggle.checked;
  });

  const persistAndApply = () => {
    writeStoredColumnVisibility(gridId, visibility);
    applyColumnVisibility(grid, visibility);
  };

  const handleToggleChange = (event) => {
    const toggle = event.target;
    if (!(toggle instanceof HTMLInputElement) || !grid.contains(toggle)) {
      return;
    }

    const columnKey = String(toggle.dataset.colKey || '');
    if (columnKey === '') {
      return;
    }

    visibility[columnKey] = toggle.checked;
    persistAndApply();

    const label = toggle.closest('.datagrid_column_toggle')
      ?.querySelector('.datagrid_column_toggle_label')?.textContent?.trim() || columnKey;
    announceColumnVisibilityStatus(grid, label, toggle.checked);
  };

  const handleToggleKeydown = (event) => {
    const toggle = event.target;
    if (!(toggle instanceof HTMLInputElement)
      || !toggle.classList.contains('datagrid_column_toggle_input')
      || !grid.contains(toggle)) {
      return;
    }

    if (event.key === 'ArrowDown') {
      if (focusFirstDatagridRow(grid)) {
        event.preventDefault();
      }
      return;
    }

    if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') {
      return;
    }

    const toggles = getColumnVisibilityToggleInputs(
      toggle.closest('.datagrid_column_strip, .datagrid_column_menu_panel') || grid,
    );
    const currentIndex = toggles.indexOf(toggle);
    if (currentIndex < 0) {
      return;
    }

    const delta = event.key === 'ArrowRight' ? 1 : -1;
    const nextIndex = currentIndex + delta;
    if (nextIndex < 0) {
      if (focusDatagridSearch(grid)) {
        event.preventDefault();
      }
      return;
    }

    if (nextIndex < 0 || nextIndex >= toggles.length) {
      return;
    }

    event.preventDefault();
    toggles[nextIndex].focus();
  };

  grid.addEventListener('change', handleToggleChange);
  grid.addEventListener('keydown', handleToggleKeydown);
  persistAndApply();
  initColumnVisibilityMenu(grid);

  const api = {
    visibility,
    destroy() {
      grid.removeEventListener('change', handleToggleChange);
      grid.removeEventListener('keydown', handleToggleKeydown);
      delete grid.__columnVisibilityInstance;
    },
  };

  grid.__columnVisibilityInstance = api;

  return api;
}

function isDatagridAbortError(error)
{
  return !!error && typeof error === 'object' && error.name === 'AbortError';
}

function formatDatagridReloadError(error)
{
  if (error instanceof Error && error.message.trim() !== '') {
    return error.message;
  }

  if (error && typeof error === 'object' && typeof error.message === 'string' && error.message.trim() !== '') {
    return error.message;
  }

  return DATAGRID_T.reloadFailed;
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

  const body = grid.querySelector(":scope > .datagrid_body") || grid.querySelector(".datagrid_body");
  if (!body) {
    console.error('Datagrid body not found for grid:', config.id);
    return null;
  }

  let abortController = null;
  let searchDebounceId = null;
  let activeReloadToken = 0;

  function resolveStateGrid()
  {
    const inner = body.querySelector(".datagrid[data-grid]");
    return inner instanceof HTMLElement ? inner : grid;
  }

  function readStateFromGrid(stateGrid)
  {
    return {
      page: parseInt(stateGrid.dataset.page || "1", 10),
      totalPages: parseInt(stateGrid.dataset.totalPages || "1", 10),
      search: stateGrid.dataset.search || "",
      sort: stateGrid.dataset.sort || "",
      direction: stateGrid.dataset.direction || "asc",
    };
  }

  const state = readStateFromGrid(resolveStateGrid());

  function syncDataset()
  {
    grid.dataset.page = String(state.page);
    grid.dataset.search = state.search;
    grid.dataset.sort = state.sort;
    grid.dataset.direction = state.direction;
    grid.dataset.totalPages = String(state.totalPages);

    const stateGrid = resolveStateGrid();
    if (stateGrid !== grid) {
      stateGrid.dataset.page = String(state.page);
      stateGrid.dataset.search = state.search;
      stateGrid.dataset.sort = state.sort;
      stateGrid.dataset.direction = state.direction;
      stateGrid.dataset.totalPages = String(state.totalPages);
    }
  }

  function syncStateFromDom()
  {
    const nextState = readStateFromGrid(resolveStateGrid());
    state.page = nextState.page;
    state.totalPages = nextState.totalPages;
    state.search = nextState.search;
    state.sort = nextState.sort;
    state.direction = nextState.direction;

    const searchInput = grid.querySelector(".datagrid_search");
    if (searchInput instanceof HTMLInputElement) {
      const inputSearch = String(searchInput.value || "").trim();
      if (inputSearch !== state.search) {
        state.search = inputSearch;
      }
    }
  }

  function syncSearchFromInput()
  {
    const searchInput = grid.querySelector(".datagrid_search");
    if (searchInput instanceof HTMLInputElement) {
      state.search = String(searchInput.value || "").trim();
    }
  }

  function readExtraParams()
  {
    const params = {};
    grid.querySelectorAll("[data-datagrid-param]").forEach((control) => {
      if (!(control instanceof HTMLInputElement)
        && !(control instanceof HTMLSelectElement)
        && !(control instanceof HTMLTextAreaElement)) {
        return;
      }

      const key = String(control.dataset.datagridParam || "").trim();
      if (key === "") {
        return;
      }

      const value = String(control.value || "").trim();
      if (value !== "") {
        params[key] = value;
      }
    });

    return params;
  }

  function buildPayload()
  {
    const payload = {
      page: state.page,
      search: state.search,
      sort: state.sort,
      direction: state.direction,
      ...readExtraParams(),
    };
    return payload;
  }

  function isStaleReload(reloadToken, requestSignal)
  {
    return reloadToken !== activeReloadToken
      || (requestSignal instanceof AbortSignal && requestSignal.aborted);
  }

  async function reload()
  {
    if (abortController)
    {
      abortController.abort();
    }

    const reloadToken = ++activeReloadToken;
    abortController = new AbortController();
    const requestSignal = abortController.signal;

    try {
      syncSearchFromInput();
      syncDataset();
      grid.classList.add('datagrid_container_loading');

      // Use GET for endpoints that are data fetch (like sites/grid)
      let url = config.endpoint;
      let fetchOptions = {
        method: "GET",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
          "X-Requested-With": "XMLHttpRequest",
        },
        signal: requestSignal
      };

      // For endpoints that use query-parameter pagination/search/sort, use GET
      const baseEndpoint = config.endpoint.split('?')[0];
      const isGetQueryGridEndpoint = baseEndpoint.includes('sites/grid')
        || baseEndpoint.includes('/sites/grid')
        || baseEndpoint.includes('groups/grid')
        || baseEndpoint.includes('/groups/grid')
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
          signal: requestSignal
        };
      }

      const response = await fetch(url, fetchOptions);

      if (isStaleReload(reloadToken, requestSignal)) {
        return;
      }

      // Read response body once since it can only be consumed once
      const text = await response.text();

      if (isStaleReload(reloadToken, requestSignal)) {
        return;
      }

      if (!response.ok)
      {
        console.error(`DataGrid request failed: ${response.status}`, text);
        throw new Error(`DataGrid request failed (${response.status}).`);
      }

      let result;
      try {
        result = JSON.parse(text);
      } catch (e) {
        console.error('JSON parse error:', e);
        console.error('Response text:', text.substring(0, 500));
        throw new Error(DATAGRID_T.invalidResponse);
      }

      if (result.status !== "success")
      {
        console.warn('Invalid DataGrid response:', result);
        console.error(`Invalid DataGrid response status: ${result.status}`, result);
        throw new Error(typeof result.message === 'string' && result.message.trim() !== ''
          ? result.message
          : DATAGRID_T.invalidResponse);
      }

      if (isStaleReload(reloadToken, requestSignal)) {
        return;
      }

      const searchFocusPreserve = captureSearchInputFocusState();

      if (typeof result.html === "string")
      {
        document.dispatchEvent(new CustomEvent('paycal:datagrid-before-reload', {
          detail: {
            gridId: config.id,
            state: { ...state },
          }
        }));

        PC.setHTML(body, result.html);
        grid.classList.remove('datagrid_container_loading');
        initColumnVisibility(grid);
        initColumnVisibilityMenu(resolveColumnVisibilityGrid(grid) || grid);
        syncStateFromDom();
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

      const rowCount = grid.querySelectorAll('.datagrid_row').length;
      document.dispatchEvent(new CustomEvent('paycal:datagrid-reloaded', {
        detail: {
          gridId: config.id,
          state: { ...state },
          rowCount
        }
      }));

      restoreSearchInputFocus(searchFocusPreserve);
    }
    catch (error) {
      if (isDatagridAbortError(error) || isStaleReload(reloadToken, requestSignal)) {
        return;
      }

      grid.classList.remove('datagrid_container_loading');
      throw error;
    }
  }

  function handlePagination(e)
  {
    const button = e.target.closest(".datagrid_pagination_btn");
    if (!(button instanceof HTMLButtonElement) || button.disabled || !grid.contains(button)) {
      return;
    }

    // Prefer server-rendered pager state (data-page / disabled buttons) over in-memory
    // defaults that may be stale when init ran against a loading skeleton.
    syncStateFromDom();

    const direction = String(button.dataset.direction || "");
    if (direction === "prev") {
      state.page = Math.max(1, state.page - 1);
      reload();
      return;
    }

    if (direction === "next") {
      state.page += 1;
      reload();
    }
  }

  function captureSearchInputFocusState()
  {
    const activeSearch = document.activeElement;
    if (!(activeSearch instanceof HTMLInputElement) || !activeSearch.classList.contains("datagrid_search")) {
      return null;
    }

    if (!grid.contains(activeSearch)) {
      return null;
    }

    return {
      value: activeSearch.value,
      selectionStart: activeSearch.selectionStart,
      selectionEnd: activeSearch.selectionEnd,
    };
  }

  function restoreSearchInputFocus(preserve)
  {
    if (!preserve) {
      return;
    }

    const newInput = grid.querySelector(".datagrid_search");
    if (!(newInput instanceof HTMLInputElement)) {
      return;
    }

    if (typeof preserve.value === "string" && newInput.value !== preserve.value) {
      newInput.value = preserve.value;
    }

    window.requestAnimationFrame(() => {
      newInput.focus({ preventScroll: true });

      if (typeof preserve.selectionStart === "number" && typeof preserve.selectionEnd === "number") {
        try {
          const len = newInput.value.length;
          newInput.setSelectionRange(
            Math.min(preserve.selectionStart, len),
            Math.min(preserve.selectionEnd, len),
          );
        } catch (_err) {
          // setSelectionRange is unsupported on some input types.
        }
      }
    });
  }

  function handleSearchInput(e)
  {
    const input = e.target.closest(".datagrid_search");
    if (!(input instanceof HTMLInputElement) || !grid.contains(input)) {
      return;
    }

    if (searchDebounceId !== null) {
      window.clearTimeout(searchDebounceId);
    }

    searchDebounceId = window.setTimeout(() => {
      state.search = String(input.value || "").trim();
      state.page = 1;
      reload().catch((error) => {
        console.error(`DataGrid reload failed (${config.id}): ${formatDatagridReloadError(error)}`);
      });
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

  function handleRowClick(e)
  {
    if (e.target.closest(".datagrid_action")) return;

    const row = e.target.closest(".datagrid_row");
    if (!row || !grid.contains(row)) return;

    if (typeof config.onRowClick === "function")
    {
      config.onRowClick(row.dataset.id, row);
    }
  }

  const workspaceManagedSearch = document.getElementById("business-workspace") instanceof HTMLElement;

  function bindEvents()
  {
    grid.addEventListener("click", handlePagination);
    grid.addEventListener("click", handleSort);
    grid.addEventListener("click", handleRowClick);
    if (!workspaceManagedSearch) {
      grid.addEventListener("input", handleSearchInput);
    }
  }

  function unbindEvents()
  {
    grid.removeEventListener("click", handlePagination);
    grid.removeEventListener("click", handleSort);
    grid.removeEventListener("click", handleRowClick);
    if (!workspaceManagedSearch) {
      grid.removeEventListener("input", handleSearchInput);
    }
  }

  function destroy()
  {
    if (abortController)
    {
      abortController.abort();
    }

    if (searchDebounceId !== null) {
      window.clearTimeout(searchDebounceId);
      searchDebounceId = null;
    }

    unbindEvents();
    const innerGrid = resolveColumnVisibilityGrid(grid);
    if (innerGrid instanceof HTMLElement && innerGrid.__columnVisibilityInstance?.destroy) {
      innerGrid.__columnVisibilityInstance.destroy();
    }
    delete grid.__datagridInstance;
  }

  bindEvents();
  initColumnVisibility(grid);

  const api = {
    reload,
    destroy,
    getState()
    {
      return { ...state };
    },
    setSearch(value)
    {
      state.search = String(value || "").trim();
      state.page = 1;
      reload().catch((error) => {
        console.error(`DataGrid reload failed (${config.id}): ${formatDatagridReloadError(error)}`);
      });
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
