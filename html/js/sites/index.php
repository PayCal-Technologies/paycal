<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();
CORS::handleORIGIN();
CORS::renderContentType('text/javascript');
Javascript::renderDocBlock();

$user = User::current();
$availableEarningsYears = iterator_to_array(Work::getAvailableYears($user->user_uuid));
if ([] === $availableEarningsYears) {
  $availableEarningsYears = [(int) date('Y')];
}
?>
const isDebugEnabled = window.PAYCAL_DEBUG === true;
const debugLog = (...args) => {
  if (!isDebugEnabled) {
    return;
  }
  PW.log('[Sites Debug]', ...args);
};
const getErrorMessage = (error) => error?.message || String(error);

import PC from "<?php echo Environment::appURL('js/'); ?>";
import PW from "<?php echo Environment::appURL('js/phantomwing/'); ?>";
import { createDataGrid } from "/js/datagrid/";

debugLog('Imports loaded successfully');

// API helper - construct API endpoint URLs with version
// Use full URL with protocol to avoid relative path issues
const apiBase = `<?php echo Environment::appURL('api/' . Environment::apiVersion() . '/'); ?>`;
const apiUrl = (endpoint) => apiBase + endpoint;
const AJAX_HEADERS = Object.freeze({ 'X-Requested-With': 'XMLHttpRequest' });
const JSON_AJAX_HEADERS = Object.freeze({ ...AJAX_HEADERS, 'Accept': 'application/json' });
const FORM_HEADERS = Object.freeze({ 'Content-Type': 'application/x-www-form-urlencoded' });
const FORM_JSON_AJAX_HEADERS = Object.freeze({
  ...FORM_HEADERS,
  ...JSON_AJAX_HEADERS,
});
const AVAILABLE_EARNINGS_YEARS = <?php echo json_encode(array_values($availableEarningsYears), JSON_UNESCAPED_SLASHES); ?>;
const NUMBER_FORMATTER_CACHE = new Map();

function getNumberFormatter(minimumFractionDigits = 0, maximumFractionDigits = minimumFractionDigits) {
  const min = Number.isFinite(minimumFractionDigits) ? Math.max(0, Number(minimumFractionDigits)) : 0;
  const max = Number.isFinite(maximumFractionDigits) ? Math.max(min, Number(maximumFractionDigits)) : min;
  const cacheKey = `${min}:${max}`;
  if (NUMBER_FORMATTER_CACHE.has(cacheKey)) {
    return NUMBER_FORMATTER_CACHE.get(cacheKey);
  }

  const locale = PC?.config?.USER_LOCALE || undefined;
  const formatter = new Intl.NumberFormat(locale, {
    useGrouping: true,
    minimumFractionDigits: min,
    maximumFractionDigits: max,
  });
  NUMBER_FORMATTER_CACHE.set(cacheKey, formatter);
  return formatter;
}

function formatNumberLocale(value, minimumFractionDigits = 0, maximumFractionDigits = minimumFractionDigits) {
  const numeric = Number(value);
  if (!Number.isFinite(numeric)) {
    return '0';
  }

  return getNumberFormatter(minimumFractionDigits, maximumFractionDigits).format(numeric);
}

function formatCurrencyLocale(value) {
  return `$${formatNumberLocale(value, 2, 2)}`;
}

debugLog('API configuration', {
  apiBase,
  resolvedExample: apiUrl('sites/earnings?year=2025'),
  origin: window.location.origin,
  availableYears: AVAILABLE_EARNINGS_YEARS,
});

document.addEventListener("DOMContentLoaded", async () =>
{
  debugLog('Sites page loaded (DOMContentLoaded)');
  debugLog('Initializing DataGrids...');

  const initializeHoverHelp = () => {
    const targets = Array.from(document.querySelectorAll('[data-hover-help]'));
    if (targets.length === 0) {
      return;
    }

    const tooltipEl = document.createElement('div');
    tooltipEl.className = 'hover_help_tooltip';
    tooltipEl.setAttribute('role', 'tooltip');
    tooltipEl.setAttribute('aria-hidden', 'true');
    document.body.appendChild(tooltipEl);

    let showTimer = null;
    let activeTarget = null;

    const clearShowTimer = () => {
      if (showTimer !== null) {
        window.clearTimeout(showTimer);
        showTimer = null;
      }
    };

    const hideTooltip = () => {
      clearShowTimer();
      activeTarget = null;
      tooltipEl.classList.remove('is-visible');
      tooltipEl.setAttribute('aria-hidden', 'true');
    };

    const positionTooltip = (targetEl) => {
      if (!targetEl) {
        return;
      }
      const margin = '1.5rem';
      tooltipEl.style.bottom = margin;
      tooltipEl.style.right = margin;
      tooltipEl.style.top = 'auto';
      tooltipEl.style.left = 'auto';
    };

    const showTooltip = (targetEl) => {
      const helpText = (targetEl?.getAttribute('data-hover-help') || '').trim();
      if (!helpText) {
        return;
      }

      activeTarget = targetEl;
      tooltipEl.textContent = helpText;
      tooltipEl.classList.add('is-visible');
      tooltipEl.setAttribute('aria-hidden', 'false');
      positionTooltip(targetEl);
    };

    const scheduleShow = (targetEl) => {
      clearShowTimer();
      showTimer = window.setTimeout(() => {
        showTimer = null;
        showTooltip(targetEl);
      }, 250);
    };

    targets.forEach((targetEl) => {
      targetEl.addEventListener('mouseenter', () => scheduleShow(targetEl));
      targetEl.addEventListener('mouseleave', hideTooltip);
      targetEl.addEventListener('focus', () => scheduleShow(targetEl));
      targetEl.addEventListener('blur', hideTooltip);
      targetEl.addEventListener('mousedown', hideTooltip);
    });

    window.addEventListener('scroll', () => {
      if (activeTarget && tooltipEl.classList.contains('is-visible')) {
        positionTooltip(activeTarget);
      }
    }, true);
  };

  initializeHoverHelp();
  
  // Initialize both active and archived grids with status parameter
  const gridManagerActive = createDataGrid({
    id: "sites-grid-active",
    endpoint: apiUrl("sites/grid?status=active"),
    onRowClick: handleRowClick
  });
  
  const gridManagerArchived = createDataGrid({
    id: "sites-grid-archived",
    endpoint: apiUrl("sites/grid?status=archived"),
    onRowClick: handleRowClick
  });

  const announceSitesGridStatus = (gridId, rowCount, state, reason = 'loaded') => {
    const statusId = gridId === 'sites-grid-archived' ? 'sites_grid_archived_sr_status' : 'sites_grid_active_sr_status';
    const statusNode = PC.getElement(statusId);
    if (!statusNode) {
      return;
    }

    const order = state?.sort ? `${state.sort} ${state.direction || 'asc'}` : 'default order';
    const search = state?.search ? `search ${state.search}` : 'no search filter';
    const page = state?.page || 1;
    const label = gridId === 'sites-grid-archived' ? 'Archived sites' : 'Active sites';
    statusNode.textContent = `${label} grid ${reason}. ${rowCount} result${rowCount === 1 ? '' : 's'}. ${order}. ${search}. Page ${page}.`;
  };

  document.addEventListener('paycal:datagrid-reloaded', (event) => {
    const detail = event?.detail || {};
    const gridId = String(detail.gridId || '');
    if (gridId !== 'sites-grid-active' && gridId !== 'sites-grid-archived') {
      return;
    }

    announceSitesGridStatus(gridId, Number(detail.rowCount || 0), detail.state || {}, 'updated');
  });
  
  debugLog('DataGrids initialized', { gridManagerActive, gridManagerArchived });

  // Grids validation
  if (!gridManagerActive || !gridManagerArchived) {
    PW.error('Grids not initialized!');
    return;
  }

  // Load initial data for both grids
  debugLog('Loading grid data...');
  await gridManagerActive.reload();
  await gridManagerArchived.reload();
  debugLog('Grid data loaded');

  let currentDeleteSiteId = null;
  let currentDeleteSiteName = null;
  let currentDeleteSiteStatus = 'active';

  const setFieldInvalidState = (input, isInvalid) => {
    if (!input) {
      return;
    }

    input.setAttribute('aria-invalid', isInvalid ? 'true' : 'false');
  };

  const setFormStatus = (statusElementId, message) => {
    const statusEl = PC.getElement(statusElementId);
    if (statusEl) {
      statusEl.textContent = message;
    }
  };

  const setFieldError = (input, errorElementId, message) => {
    const text = String(message || '').trim();
    setFieldInvalidState(input, text.length > 0);
    const errorEl = PC.getElement(errorElementId);
    if (errorEl) {
      errorEl.textContent = text;
    }
  };

  const validateRequiredSiteFields = (nameInput, wageInput, statusElementId, nameErrorId, wageErrorId) => {
    const nameValue = String(nameInput?.value || '').trim();
    const wageValue = String(wageInput?.value || '').trim();

    setFieldError(nameInput, nameErrorId, '');
    setFieldError(wageInput, wageErrorId, '');
    setFormStatus(statusElementId, '');

    if (!nameValue || !wageValue) {
      if (!nameValue) {
        setFieldError(nameInput, nameErrorId, 'Enter a site name.');
      }
      if (!wageValue) {
        setFieldError(wageInput, wageErrorId, 'Enter an hourly wage.');
      }
      setFormStatus(statusElementId, 'Enter both site name and wage.');
      (nameValue ? wageInput : nameInput)?.focus();
      return false;
    }

    return true;
  };

  // SIMPLIFIED: earnings loaded via setupYearTabs() later

  function handleRowClick(id, rowElement)
  {
    debugLog('Row clicked', {id, rowElement});
    document.querySelectorAll(".datagrid_row").forEach(r =>
      r.classList.remove("is-selected")
    );

    rowElement.classList.add("is-selected");
    openEditDialog(id);
  }


  // ============================================================================
  // Create Site Dialog
  // ============================================================================

  /**
   * Open create site dialog
   * @param {string} status - 'active' or 'archived'
   */
  function openCreateSiteDialog(status) {
    debugLog('openCreateSiteDialog called', status);
    const modal = PC.getElement('modal_create_site');
    const form = PC.getElement('create_site_form');
    const statusInput = PC.getElement('create_site_status');

    if (!modal || !form) return;

    // Reset form
    form.reset();

    // Set status
    if (statusInput) {
      statusInput.value = status;
    }

    if (!modal.open) {
      modal.showModal();
    }
  }


  /**
   * Handle create site form submission
   * @param {Event} e - Submit event
   */
  async function handleCreateSiteSubmit(e) {
    debugLog('handleCreateSiteSubmit called');
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const nameInput = PC.getElement('site_name_input');
    const wageInput = PC.getElement('site_wage_input');

    if (!validateRequiredSiteFields(nameInput, wageInput, 'create_site_form_status', 'site_name_error', 'site_wage_error')) {
      return;
    }

    try {
      debugLog('Submitting create site form');
      const httpResponse = await fetch(apiUrl('sites/create'), {
        method: 'POST',
        credentials: 'include',
        body: new URLSearchParams(formData)
      });

      const responseData = await httpResponse.json();

      if (responseData.status === 'success') {
        debugLog('Site created successfully');
        const modal = PC.getElement('modal_create_site');
        modal?.close();

        PC.showToast('Site created successfully');

        form.reset();
        setFormStatus('create_site_form_status', '');
        setFieldError(nameInput, 'site_name_error', '');
        setFieldError(wageInput, 'site_wage_error', '');

        // Reload appropriate grid
        const status = formData.get('status');
        const manager = status === 'archived' ? gridManagerArchived : gridManagerActive;
        manager.reload();
      } else {
        PW.error('Error creating site');
        setFormStatus('create_site_form_status', responseData.message || 'Error creating site.');
        PC.showToast('Error creating site');
      }
    } catch (error) {
      PW.error(`Error creating site: ${getErrorMessage(error)}`);
      setFormStatus('create_site_form_status', 'Unable to create site right now. Please try again.');
      PC.showToast('Error creating site');
    }
  }


  // ============================================================================
  // Edit Site Dialog
  // ============================================================================

  /**
   * Open edit site dialog
   * @param {string} siteId - Site ID
   */
  async function openEditDialog(siteId) {
    debugLog('openEditDialog called', siteId);
    const modal = PC.getElement('modal_edit_site');
    if (!modal) {
      PW.error('Edit modal not found');
      return;
    }

    try {
      debugLog('Fetching site for edit', siteId);
      const httpResponse = await fetch(apiUrl(`sites/get?id=${encodeURIComponent(siteId)}`), {
        method: 'GET',
        credentials: 'include'
      });

      const responseData = await httpResponse.json();

      if (responseData.status === 'success') {
        debugLog('Site loaded for edit', responseData.site);
        const site = responseData.site;

        PC.getElement('edit_site_id').value = site.id || siteId;
        PC.getElement('edit_site_name_input').value = site.site_name || '';
        PC.getElement('edit_site_wage_input').value = site.wage || '';
        PC.getElement('edit_site_loa_input').value = site.living_out_allowance || '';
        PC.getElement('edit_site_travel_input').value = site.travel_hours || '';
        PC.getElement('edit_site_province_select').value = site.province || '';
        PC.getElement('edit_site_status_select').value = site.status || 'active';

        if (!modal.open) {
          modal.showModal();
        }

        const editNameInput = PC.getElement('edit_site_name_input');
        if (editNameInput instanceof HTMLInputElement) {
          window.requestAnimationFrame(() => {
            editNameInput.focus();
          });
        }
      }
    } catch (error) {
      PW.error(`Error loading site data: ${getErrorMessage(error)}`);
      PC.showToast('Error loading site data');
    }
  }


  /**
   * Handle edit site form submission
   * @param {Event} e - Submit event
   */
  async function handleEditSiteSubmit(e) {
    debugLog('handleEditSiteSubmit called');
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);
    const nameInput = PC.getElement('edit_site_name_input');
    const wageInput = PC.getElement('edit_site_wage_input');

    if (!validateRequiredSiteFields(nameInput, wageInput, 'edit_site_form_status', 'edit_site_name_error', 'edit_site_wage_error')) {
      return;
    }

    try {
      debugLog('Submitting edit site form');
      const httpResponse = await fetch(apiUrl('sites/update'), {
        method: 'POST',
        credentials: 'include',
        body: new URLSearchParams(formData)
      });

      const responseData = await httpResponse.json();

      if (responseData.status === 'success') {
        debugLog('Site updated successfully');
        const modal = PC.getElement('modal_edit_site');
        modal?.close();
        setFormStatus('edit_site_form_status', '');
        setFieldError(nameInput, 'edit_site_name_error', '');
        setFieldError(wageInput, 'edit_site_wage_error', '');

        PC.showToast('Site updated successfully');

        // Reload both grids in case status changed
        gridManagerActive.reload();
        gridManagerArchived.reload();
        
        // Reload earnings panel to reflect any work entry changes
        loadEarningsPanel();
      } else {
        PW.error('Error updating site');
        setFormStatus('edit_site_form_status', responseData.message || 'Error updating site.');
        PC.showToast('Error updating site');
      }
    } catch (error) {
      PW.error(`Error updating site: ${getErrorMessage(error)}`);
      setFormStatus('edit_site_form_status', 'Unable to update site right now. Please try again.');
      PC.showToast('Error updating site');
    }
  }


  // ============================================================================
  // Delete Site Dialog
  // ============================================================================

  /**
   * Open delete confirmation dialog
   * @param {string} siteId - Site ID
   * @param {string} siteName - Site name
   * @param {string} siteStatus - Site status ('active' or 'archived')
   */
  function openDeleteDialog(siteId, siteName, siteStatus = 'active') {
    debugLog('openDeleteDialog called', {siteId, siteName, siteStatus});
    const modal = PC.getElement('modal_confirm_delete_site');
    const messageEl = PC.getElement('confirm_delete_site_message');
    const confirmBtn = PC.getElement('confirm_delete_site_yes');

    if (!modal) return;

    const escapedName = siteName || 'this site';
    
    // Different messages and button text based on status
    if (siteStatus === 'archived') {
      // Permanent delete from archived
      PC.setHTML(messageEl, `<strong class="delete_message_danger">⚠️ PERMANENT DELETION</strong><br><br>` +
        `You are about to <strong>permanently delete</strong> "${escapedName}" and all its archived work entries.<br><br>` +
        `<strong>This action cannot be undone. All historical data will be lost forever.</strong>`);
      
      if (confirmBtn) {
        confirmBtn.textContent = 'Yes, Permanently Delete';
        confirmBtn.className = 'btn btn_danger';
      }
    } else {
      // Archive active site
      PC.setHTML(messageEl, `Are you sure you want to archive "${escapedName}"?<br><br>` +
        `<strong>The site will be moved to Archived, and all associated work entries will be preserved.</strong><br><br>` +
        `You can permanently delete it later from the Archived tab if needed.`);
      
      if (confirmBtn) {
        confirmBtn.textContent = 'Archive Site';
        confirmBtn.className = 'btn btn_primary';
      }
    }

    // Set state
    currentDeleteSiteId = siteId;
    currentDeleteSiteName = siteName;
    currentDeleteSiteStatus = siteStatus;

    if (!modal.open) {
      modal.showModal();
    }
  }


  /**
   * Close delete confirmation dialog
   */
  function closeDeleteDialog() {
    debugLog('closeDeleteDialog called');
    const modal = PC.getElement('modal_confirm_delete_site');
    modal?.close();
  }


  /**
   * Handle confirmed deletion
   */
  async function handleConfirmDelete() {
    debugLog('handleConfirmDelete called');
    if (!currentDeleteSiteId) return;

    const confirmBtn = PC.getElement('confirm_delete_site_yes');
    if (confirmBtn) confirmBtn.disabled = true;

    try {
      debugLog('Sending delete/finality request', {currentDeleteSiteId, currentDeleteSiteStatus});
      let url, method;
      
      if (currentDeleteSiteStatus === 'archived') {
        // Permanent delete for archived sites
        url = apiUrl('sites/permanent-delete');
        method = 'POST';
      } else {
        // Archive for active sites
        url = apiUrl('sites/delete');
        method = 'POST';
      }

      const httpResponse = await fetch(url, {
        method,
        credentials: 'include',
        headers: FORM_HEADERS,
        body: new URLSearchParams({ id: currentDeleteSiteId })
      });

      const responseData = await httpResponse.json();

      if (responseData.status === 'success') {
        debugLog('Delete/finality success');
        closeDeleteDialog();
        
        if (currentDeleteSiteStatus === 'archived') {
          // Permanent delete
          const deletedCount = responseData?.deleted_work_count ?? 0;
          PC.showToast(`Site permanently deleted. ${deletedCount} archived work ${deletedCount === 1 ? 'entry' : 'entries'} removed.`);
        } else {
          // Archive
          const archivedCount = responseData?.archived_count ?? 0;
          const message = archivedCount > 0
            ? `Site archived. ${archivedCount} work ${archivedCount === 1 ? 'entry' : 'entries'} preserved.`
            : 'Site archived (no work entries found).';
          PC.showToast(message);
        }

        // Reload both grids
        gridManagerActive.reload();
        gridManagerArchived.reload();
        
        // Reload earnings
        const currentYear = document.querySelector('#earnings_year_tabs .tab.active')?.textContent || new Date().getFullYear();
        await loadSiteEarnings(parseInt(currentYear));
      } else {
        PW.error('Error deleting site');
        PC.showToast('Error deleting site');
        if (confirmBtn) confirmBtn.disabled = false;
      }
    } catch (error) {
      PW.error(`Error deleting site: ${getErrorMessage(error)}`);
      PC.showToast('Error deleting site');
      if (confirmBtn) confirmBtn.disabled = false;
    }
  }


  // ============================================================================
  // Tab Switching
  // ============================================================================

  /**
   * Handle tab switching
   */
  function setupTabSwitching() {
    debugLog('setupTabSwitching called');
    const tabs = document.querySelectorAll('.tab');
    const disclaimers = document.querySelectorAll('.tab-disclaimer');

    const activateTab = (tab) => {
      // Roving tabindex: only active tab is tabbable.
      tabs.forEach(t => {
        t.classList.remove('active');
        t.setAttribute('aria-selected', 'false');
        t.setAttribute('tabindex', '-1');
      });
      tab.classList.add('active');
      tab.setAttribute('aria-selected', 'true');
      tab.setAttribute('tabindex', '0');

      // Update content visibility
      const target = tab.dataset.tabTarget;
      const contents = document.querySelectorAll('[data-tab-content]');
      contents.forEach(content => {
        content.classList.remove('active');
      });

      const targetContent = document.querySelector(`[data-tab-content="${target.replace('#', '')}"]`);
      if (targetContent) {
        targetContent.classList.add('active');
      }

      // Update disclaimers
      disclaimers.forEach(disclaimer => {
        const forTab = disclaimer.dataset.forTab;
        if (forTab === target.replace('#', '')) {
          disclaimer.classList.remove('hidden');
        } else {
          disclaimer.classList.add('hidden');
        }
      });

      // Grid already loaded by setupGridManagement
      if (target === '#active_sites') {
        announceSitesGridStatus('sites-grid-active', document.querySelectorAll('#sites-grid-active .datagrid_row').length, gridManagerActive.getState(), 'focused');
      }
      if (target === '#archived_sites') {
        announceSitesGridStatus('sites-grid-archived', document.querySelectorAll('#sites-grid-archived .datagrid_row').length, gridManagerArchived.getState(), 'focused');
      }
    };

    tabs.forEach((tab, index) => {
      tab.addEventListener('click', () => {
        activateTab(tab);
      });

      // Keyboard support
      tab.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          tab.click();
        }

        if (e.key === 'ArrowRight' || e.key === 'ArrowLeft' || e.key === 'Home' || e.key === 'End') {
          e.preventDefault();

          let nextIndex = index;
          if (e.key === 'ArrowRight') {
            nextIndex = (index + 1) % tabs.length;
          } else if (e.key === 'ArrowLeft') {
            nextIndex = (index - 1 + tabs.length) % tabs.length;
          } else if (e.key === 'Home') {
            nextIndex = 0;
          } else if (e.key === 'End') {
            nextIndex = tabs.length - 1;
          }

          const nextTab = tabs[nextIndex];
          if (nextTab) {
            activateTab(nextTab);
            nextTab.focus();
          }
        }
      });
    });
  }


  // ============================================================================
  // Dialog Close Handlers
  // ============================================================================

  /**
   * Setup dialog close buttons
   */
  function setupDialogCloseButtons() {
    debugLog('setupDialogCloseButtons called');
    const closeButtons = document.querySelectorAll('[data-dialog-close]');

    closeButtons.forEach(button => {
      button.addEventListener('click', () => {
        const dialogId = button.dataset.dialogClose;
        const dialog = PC.getElement(dialogId);
        dialog?.close();
      });
    });

    // Reset delete state when modal closes
    const deleteModal = PC.getElement('modal_confirm_delete_site');
    deleteModal?.addEventListener('close', () => {
      currentDeleteSiteId = null;
      currentDeleteSiteName = null;
      const confirmBtn = PC.getElement('confirm_delete_site_yes');
      if (confirmBtn) confirmBtn.disabled = false;
    });
  }


  // ============================================================================
  // Site Earnings Analytics
  // ============================================================================

  /**
   * Load earnings data for selected year
   * @param {number} year - Year to load earnings for
   */
  async function loadSiteEarnings(year = new Date().getFullYear()) {
    debugLog('loadSiteEarnings called', year);
    const loadingEl = PC.getElement('site_earnings_loading');
    const listEl = PC.getElement('site_earnings_list');
    const totalsEl = PC.getElement('site_earnings_totals');
    const emptyEl = PC.getElement('site_earnings_empty');

    // Show loading state
    loadingEl?.classList.remove('hidden');
    listEl?.classList.add('hidden');
    totalsEl?.classList.add('hidden');
    emptyEl?.classList.add('hidden');

    try {
      debugLog('Fetching site earnings', year);
      const url = apiUrl(`sites/earnings?year=${year}`);
      debugLog('Fetching earnings', { url });
      
      const httpResponse = await fetch(url, {
        method: 'GET',
        credentials: 'include',
        headers: AJAX_HEADERS,
      });

      debugLog('Earnings response', {
        status: httpResponse.status,
        statusText: httpResponse.statusText,
        contentType: httpResponse.headers.get('Content-Type'),
      });

      // Check if response is actually JSON before parsing
      const contentType = httpResponse.headers.get('Content-Type') || '';
      if (!contentType.includes('application/json')) {
        const text = await httpResponse.text();
        PW.error(`API returned non-JSON response (${contentType}) while loading earnings.`);
        debugLog('Non-JSON earnings response body preview', text.substring(0, 1000));
        throw new Error(`Server returned ${contentType} instead of JSON - check API endpoint`);
      }

      const responseData = await httpResponse.json();

      if (responseData.status === 'success') {
        debugLog('Site earnings loaded', responseData);
        const { sites, totals } = responseData;

        loadingEl?.classList.add('hidden');

        if (sites.length === 0) {
          emptyEl?.classList.remove('hidden');
        } else {
          renderEarningsList(sites, totals.earnings);
          renderEarningsTotals(totals);
          listEl?.classList.remove('hidden');
          totalsEl?.classList.remove('hidden');
        }
      } else {
        throw new Error(responseData.message || 'Failed to load earnings');
      }
    } catch (error) {
      PW.error(`Error loading site earnings: ${getErrorMessage(error)}`);
      loadingEl?.classList.add('hidden');
      if (listEl) {
        PC.setHTML(listEl, '<div class="f_center earnings_error">Failed to load earnings data</div>');
        listEl.classList.remove('hidden');
      }
    }
  }


  /**
   * Render earnings list
   * @param {Array} sites - Array of site earnings data
   * @param {number} totalEarnings - Total earnings across all sites
   */
  function renderEarningsList(sites, totalEarnings) {
    debugLog('renderEarningsList called', {sites, totalEarnings});
    const listEl = PC.getElement('site_earnings_list');
    if (!listEl) return;

    listEl.textContent = '';

    sites.forEach(site => {
      const percentage = totalEarnings > 0 ? (site.total_earnings / totalEarnings) * 100 : 0;
      // Round to nearest 5 for CSS data attribute matching
      const roundedPercentage = Math.round(percentage / 5) * 5;

      const row = document.createElement('div');
      row.className = 'site_earnings_row';

      PC.setHTML(row, `
        <div class="flex f_space_between f_center site_earnings_header">
          <div class="site_name_bold">
            ${site.site_name}
            ${site.site_status === 'archived' ? '<span class="site_archived_badge"> (Archived)</span>' : ''}
          </div>
          <div class="site_earnings_amount">
            ${formatCurrencyLocale(site.total_earnings)}
          </div>
        </div>
        
        <div class="site_earnings_bar">
          <div class="site_earnings_bar_fill" data-width="${roundedPercentage}"></div>
        </div>
        
        <div class="flex f_space_between site_earnings_details">
          <span>${formatNumberLocale(site.total_hours, 1, 1)} hours (${formatNumberLocale(site.regular_hours, 1, 1)} reg, ${formatNumberLocale(site.overtime_hours, 1, 1)} OT)</span>
          <span>${formatNumberLocale(site.work_days, 0, 0)} days ◆ ${formatNumberLocale(percentage, 1, 1)}%</span>
        </div>
      `);

      listEl.appendChild(row);
    });
  }


  /**
   * Render totals summary
   * @param {Object} totals - Totals object from API
   */
  function renderEarningsTotals(totals) {
    debugLog('renderEarningsTotals called', totals);
    const totalsEl = PC.getElement('site_earnings_totals');
    if (!totalsEl) return;

    const avgPerSite = totals.sites_count > 0 ? totals.earnings / totals.sites_count : 0;

    PC.setHTML(totalsEl, `
      <div class="datagrid datagrid_cols_5 datagrid_layout_auto site_earnings_totals_datagrid" data-grid="site-earnings-totals" role="region" aria-label="Site earnings totals summary">
        <div class="datagrid_table" role="grid" aria-colcount="5" aria-rowcount="1">
          <div class="datagrid_header_row" role="rowgroup">
            <div class="datagrid_header_content" role="row">
              <div class="datagrid_heading" role="columnheader">Earnings</div>
              <div class="datagrid_heading" role="columnheader">Hours</div>
              <div class="datagrid_heading" role="columnheader">Days</div>
              <div class="datagrid_heading" role="columnheader">Sites</div>
              <div class="datagrid_heading" role="columnheader">Average</div>
            </div>
          </div>

          <div class="datagrid_body" role="rowgroup">
            <div class="datagrid_row" role="row">
              <div class="datagrid_row_content" role="presentation">
                <div class="datagrid_item" role="gridcell">${formatCurrencyLocale(totals.earnings)}</div>
                <div class="datagrid_item" role="gridcell">${formatNumberLocale(totals.hours, 1, 1)}</div>
                <div class="datagrid_item" role="gridcell">${formatNumberLocale(totals.work_days, 0, 0)}</div>
                <div class="datagrid_item" role="gridcell">${formatNumberLocale(totals.sites_count, 0, 0)}</div>
                <div class="datagrid_item" role="gridcell">${formatCurrencyLocale(avgPerSite)}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `);
  }


  /**
   * Populate year tabs from available work-entry years.
   */
  async function setupYearTabs() {
    debugLog('setupYearTabs called');
    const yearTabsContainer = PC.getElement('earnings_year_tabs');
    if (!yearTabsContainer) return;

    yearTabsContainer.textContent = '';

    const years = Array.isArray(AVAILABLE_EARNINGS_YEARS) && AVAILABLE_EARNINGS_YEARS.length > 0
      ? AVAILABLE_EARNINGS_YEARS
      : [new Date().getFullYear()];

    years.forEach((yearValue, index) => {
      const year = Number(yearValue);
      if (!Number.isFinite(year)) {
        return;
      }

      const tab = document.createElement('li');
      tab.className = index === 0 ? 'tab active' : 'tab';
      tab.setAttribute('data-tab-target', `earnings-year-${year}`);
      tab.setAttribute('role', 'tab');
      tab.setAttribute('tabindex', index === 0 ? '0' : '-1');
      tab.textContent = String(year);
      tab.dataset.year = String(year);
      tab.setAttribute('aria-selected', index === 0 ? 'true' : 'false');

      tab.addEventListener('click', () => {
        yearTabsContainer.querySelectorAll('.tab').forEach((tabEl) => {
          tabEl.classList.remove('active');
          tabEl.setAttribute('aria-selected', 'false');
          tabEl.setAttribute('tabindex', '-1');
        });

        tab.classList.add('active');
        tab.setAttribute('aria-selected', 'true');
        tab.setAttribute('tabindex', '0');

        loadSiteEarnings(year);
      });

      yearTabsContainer.appendChild(tab);
    });

    const initialYear = Number(years[0]);
    await loadSiteEarnings(Number.isFinite(initialYear) ? initialYear : new Date().getFullYear());
  }


  // ============================================================================
  // Archived Work Management
  // ============================================================================

  let currentArchivedSiteId = null;
  let currentArchivedSiteName = null;

  /**
   * View archived work for a site
   * @param {string} siteId - Site ID
   * @param {string} siteName - Site name
   */
  async function viewArchivedWork(siteId, siteName) {
    debugLog('viewArchivedWork called', {siteId, siteName});
    currentArchivedSiteId = siteId;
    currentArchivedSiteName = siteName;

    const modal = PC.getElement('modal_archived_work');
    const titleEl = PC.getElement('archived_work_title');
    const contentEl = PC.getElement('archived_work_content');
    const finalityBtn = PC.getElement('archived_work_finality_delete');

    if (!modal || !contentEl) return;

    if (titleEl) {
      titleEl.textContent = `Archived Work: ${siteName}`;
    }

    // Show loading state
    PC.setHTML(contentEl, '<p class="loading-message">Loading archived data...</p>');
    
    if (finalityBtn) {
      finalityBtn.classList.add('hidden');
    }

    modal.showModal();

    try {
      debugLog('Fetching archived work', siteId);
      const httpResponse = await fetch(apiUrl(`sites/archived?site_id=${encodeURIComponent(siteId)}`), {
        credentials: 'include'
      });

      const responseData = await httpResponse.json();

      if (responseData.status === 'success') {
        debugLog('Archived work loaded', responseData);
        const summary = responseData;
        
        if (summary.count === 0) {
          PC.setHTML(contentEl, '<p class="loading-message">No archived work entries found.</p>');
          return;
        }

        // Show finality delete button
        if (finalityBtn) {
          finalityBtn.classList.remove('hidden');
        }

        // Render archived work summary
        let html = `
          <div class="archived-summary-card">
            <div class="flex f_space_between archived-summary-row">
              <strong>Total Archived Entries:</strong>
                <span>${formatNumberLocale(summary.count, 0, 0)}</span>
            </div>
            <div class="flex f_space_between archived-summary-row">
              <strong>Total Earnings:</strong>
                <span class="primary-text">${formatCurrencyLocale(summary.total_earnings)}</span>
            </div>
            <div class="flex f_space_between archived-summary-row">
              <strong>Total Hours:</strong>
                <span>${formatNumberLocale(summary.total_hours, 1, 1)}</span>
            </div>
            <div class="flex f_space_between">
              <strong>Date Range:</strong>
              <span>${summary.date_range.start || 'N/A'} to ${summary.date_range.end || 'N/A'}</span>
            </div>
          </div>

          <h3 class="archived-entries-title">Archived Entries</h3>
          <div class="archived-entries-list">
        `;

        // Sort entries by date descending
        summary.entries.sort((a, b) => b.date.localeCompare(a.date));

        summary.entries.forEach(entry => {
          html += `
            <div class="archived-entry-item">
              <div class="flex f_space_between">
                <strong>${entry.date}</strong>
                <span class="primary-text">${formatCurrencyLocale(entry.gross)}</span>
              </div>
              <div class="flex f_space_between archived-entry-meta">
                <span>${entry.site_name}</span>
                <span>${formatNumberLocale(entry.hours, 1, 1)} hours</span>
              </div>
            </div>
          `;
        });

        html += '</div>';
        PC.setHTML(contentEl, html);

      } else {
        PC.setHTML(contentEl, '<p class="error-message">Error loading archived data.</p>');
      }
    } catch (error) {
      PW.error(`Error loading archived work: ${getErrorMessage(error)}`);
      PC.setHTML(contentEl, '<p class="error-message">Error loading archived data.</p>');
    }
  }


  /**
   * Close archived work dialog
   */
  function closeArchivedWorkDialog() {
    debugLog('closeArchivedWorkDialog called');
    const modal = PC.getElement('modal_archived_work');
    modal?.close();
    currentArchivedSiteId = null;
    currentArchivedSiteName = null;
  }


  /**
   * Open finality delete confirmation
   */
  function openFinalityDeleteConfirm() {
    debugLog('openFinalityDeleteConfirm called');
    const modal = PC.getElement('modal_finality_delete');
    const messageEl = PC.getElement('finality_delete_message');

    if (!modal) return;

    if (messageEl && currentArchivedSiteName) {
      PC.setHTML(messageEl, `
        <strong class="warning-strong">⚠️ PERMANENT DELETION WARNING</strong><br><br>
        You are about to <strong>permanently delete</strong> all archived work entries for:<br>
        <strong>"${currentArchivedSiteName}"</strong><br><br>
        <strong>This action cannot be undone. All historical data will be lost forever.</strong><br><br>
        Are you absolutely sure you want to proceed?
      `);
    }

    modal.showModal();
  }


  /**
   * Close finality delete confirmation
   */
  function closeFinalityDeleteConfirm() {
    debugLog('closeFinalityDeleteConfirm called');
    const modal = PC.getElement('modal_finality_delete');
    modal?.close();
  }


  /**
   * Handle finality deletion
   */
  async function handleFinalityDelete() {
    debugLog('handleFinalityDelete called');
    if (!currentArchivedSiteId) return;

    const confirmBtn = PC.getElement('finality_delete_yes');
    if (confirmBtn) confirmBtn.disabled = true;

    try {
      debugLog('Sending finality delete request', currentArchivedSiteId);
      const httpResponse = await fetch(apiUrl('sites/finality-delete'), {
        method: 'DELETE',
        credentials: 'include',
        headers: FORM_HEADERS,
        body: new URLSearchParams({ site_id: currentArchivedSiteId })
      });

      const responseData = await httpResponse.json();

      if (responseData.status === 'success') {
        debugLog('Finality delete success');
        closeFinalityDeleteConfirm();
        closeArchivedWorkDialog();
        
        const deletedCount = responseData?.deleted_count ?? 0;
        PC.showToast(`Permanently deleted ${deletedCount} archived work ${deletedCount === 1 ? 'entry' : 'entries'}.`);

        // Reload earnings
        const currentYear = document.querySelector('#earnings_year_tabs .tab.active')?.textContent || new Date().getFullYear();
        await loadSiteEarnings(parseInt(currentYear));
      } else {
        PW.error('Error performing finality delete');
        PC.showToast('Error performing finality delete');
        if (confirmBtn) confirmBtn.disabled = false;
      }
    } catch (error) {
      PW.error(`Error performing finality delete: ${getErrorMessage(error)}`);
      PC.showToast('Error performing finality delete');
      if (confirmBtn) confirmBtn.disabled = false;
    }
  }


  // ============================================================================
  // Initialization
  // ============================================================================

  /**
   * Set initial focus to search input
   */
  function focusSearchInput() {
    debugLog('focusSearchInput called');
    setTimeout(() => {
      const activeGrid = PC.getElement('sites-grid-active');
      if (activeGrid) {
        const datagrid = activeGrid.querySelector('.datagrid');
        if (datagrid) {
          const searchInput = datagrid.querySelector('.datagrid_search');
          if (searchInput) {
            searchInput.focus();
          }
        }
      }
    }, 0);
  }

  // Grids are already initialized by gridManagerActive and gridManagerArchived
  // Focus search input after page load
  focusSearchInput();

  // Setup tabs
  setupTabSwitching();

  // Setup dialog close buttons
  setupDialogCloseButtons();

  // Setup datagrid control handlers (Create Site button)
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-action="create-site"]');
    if (btn) {
      openCreateSiteDialog('active');
    }
  });

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.datagrid_action');
    if (!btn || btn.dataset.action !== 'delete') {
      return;
    }

    const row = btn.closest('.datagrid_row');
    if (!row) {
      return;
    }

    const siteId = row.dataset.id || '';
    const siteName = row.querySelector('.datagrid_item')?.textContent?.trim() || 'this site';
    const grid = row.closest('.datagrid_container');
    const siteStatus = grid?.id === 'sites-grid-archived' ? 'archived' : 'active';

    openDeleteDialog(siteId, siteName, siteStatus);
  });

  // Setup earnings analytics
  await setupYearTabs();

  // Setup form handlers
  const createForm = PC.getElement('create_site_form');
  createForm?.addEventListener('submit', handleCreateSiteSubmit);

  ['site_name_input', 'site_wage_input'].forEach((id) => {
    const input = PC.getElement(id);
    input?.addEventListener('input', () => {
      setFieldInvalidState(input, false);
      setFormStatus('create_site_form_status', '');
    });
  });

  const editForm = PC.getElement('edit_site_form');
  editForm?.addEventListener('submit', handleEditSiteSubmit);

  ['edit_site_name_input', 'edit_site_wage_input'].forEach((id) => {
    const input = PC.getElement(id);
    input?.addEventListener('input', () => {
      setFieldInvalidState(input, false);
      setFormStatus('edit_site_form_status', '');
    });
  });

  const deleteYesBtn = PC.getElement('confirm_delete_site_yes');
  deleteYesBtn?.addEventListener('click', handleConfirmDelete);

  const deleteNoBtn = PC.getElement('confirm_delete_site_no');
  deleteNoBtn?.addEventListener('click', closeDeleteDialog);

  // Archived work handlers
  const archivedWorkClose = PC.getElement('archived_work_close');
  archivedWorkClose?.addEventListener('click', closeArchivedWorkDialog);

  const archivedWorkFinalityBtn = PC.getElement('archived_work_finality_delete');
  archivedWorkFinalityBtn?.addEventListener('click', openFinalityDeleteConfirm);

  const finalityDeleteYes = PC.getElement('finality_delete_yes');
  finalityDeleteYes?.addEventListener('click', handleFinalityDelete);

  const finalityDeleteNo = PC.getElement('finality_delete_no');
  finalityDeleteNo?.addEventListener('click', closeFinalityDeleteConfirm);

  // Run orphaned-work discovery on load so recovery banner appears automatically.
  checkOrphanedWork();
  
  const btnShowOrphaned = PC.getElement('btn_show_orphaned_work');
  btnShowOrphaned?.addEventListener('click', showOrphanedWorkDialog);

  const recoveryForm = PC.getElement('recovery_site_form');
  recoveryForm?.addEventListener('submit', handleRecoverySiteSubmit);

  // Expose viewArchivedWork globally for use in earnings section
  window.viewArchivedWork = viewArchivedWork;


  // ==========================================================================
  // Orphaned Work Recovery
  // ==========================================================================

  /**
   * Check whether work entries still point to deleted/unknown sites.
   * If any are found, show a banner so the user can recover and re-link them.
   */
  async function checkOrphanedWork() {
    debugLog('checkOrphanedWork called');
    
    try {
      const url = apiUrl('sites/orphaned');
      debugLog('Fetching orphaned work', { url });
      const httpResponse = await fetch(url, {
        method: 'GET',
        headers: JSON_AJAX_HEADERS,
        credentials: 'include'
      });

      debugLog('Orphaned work response', {
        status: httpResponse.status,
        statusText: httpResponse.statusText,
        contentType: httpResponse.headers.get('Content-Type'),
      });

      if (!httpResponse.ok) {
        const text = await httpResponse.text();
        PW.error(`Orphaned work check failed (${httpResponse.status}).`);
        debugLog('Orphaned work error body preview', text.substring(0, 500));
        PW.warn(`Orphaned work check failed: ${httpResponse.status} ${httpResponse.statusText}`);
        return;
      }

      // Check if response is actually JSON
      const contentType = httpResponse.headers.get('Content-Type') || '';
      if (!contentType.includes('application/json')) {
        const text = await httpResponse.text();
        PW.error(`Orphaned work API returned non-JSON content (${contentType}).`);
        debugLog('Orphaned work non-JSON body preview', text.substring(0, 500));
        throw new Error(`API returned ${contentType} instead of JSON`);
      }

      const data = await httpResponse.json();
      
      // Check if the response indicates an error
      if (data.status === 'error') {
        PW.warn(`Orphaned work check error: ${data.message}`);
        return;
      }
      
      // Response format: { status, message, orphaned_groups, total_count }
      if (data.orphaned_groups && data.orphaned_groups.length > 0) {
        const banner = PC.getElement('orphaned_work_banner');
        const countEl = PC.getElement('orphaned_work_count');
        
        if (banner && countEl) {
          const totalCount = data.total_count || 0;
          const groupCount = data.orphaned_groups.length;
          
          countEl.textContent = `${totalCount} orphaned work ${totalCount === 1 ? 'entry' : 'entries'}`;
          
          if (groupCount > 1) {
            countEl.textContent += ` (${groupCount} different sites)`;
          }
          
          banner.classList.remove('hidden');
        } else {
          PW.warn('Banner elements not found: ' + JSON.stringify({ banner, countEl }));
          PW.warn('Banner elements not found');
        }
      } else {
      }
    } catch (error) {
      PW.error(`Failed to check orphaned work: ${error?.message || String(error)}`);
    }
  }


  /**
   * Open a dialog listing orphaned work groups.
   * Each group can be recovered by creating or choosing a valid destination site.
   */
  async function showOrphanedWorkDialog() {
    debugLog('showOrphanedWorkDialog called');
    const modal = PC.getElement('modal_orphaned_work');
    if (!modal) return;

    try {
      debugLog('Fetching orphaned work for dialog');
      const httpResponse = await fetch(apiUrl('sites/orphaned'), {
        method: 'GET',
        headers: JSON_AJAX_HEADERS,
        credentials: 'include'
      });

      if (!httpResponse.ok) {
        PW.error('Failed to load orphaned work data');
        throw new Error('Failed to load orphaned work data');
      }

      const data = await httpResponse.json();
      
      // Response format: { status, message, orphaned_groups, total_count }
      debugLog('Rendering orphaned groups', data.orphaned_groups || []);
      renderOrphanedGroups(data.orphaned_groups || []);
      
      modal.showModal();
    } catch (error) {
      PW.error(`Failed to show orphaned work dialog: ${error?.message || String(error)}`);
      alert('Failed to load orphaned work data. Please try again.');
    }
  }


  /**
   * Render orphaned work groups in the dialog
   * @param {Array} groups - Orphaned work groups
   */
  function renderOrphanedGroups(groups) {
    debugLog('renderOrphanedGroups called', groups);
    const container = PC.getElement('orphaned_groups_container');
    if (!container) return;

    if (groups.length === 0) {
      PC.setHTML(container, '<p class="orphaned_groups_empty">No orphaned work entries found.</p>');
      return;
    }

    const escapeHTML = (str) => {
      const div = document.createElement('div');
      div.textContent = str;
      return div.innerHTML;
    };

    const html = groups.map(group => `
      <div class="orphaned_group_card">
        <div class="orphaned_group_info">
          <h3 class="orphaned_group_name">${escapeHTML(group.site_name)}</h3>
          <div class="orphaned_group_stats">
            <span class="orphaned_stat">${group.count} ${group.count === 1 ? 'entry' : 'entries'}</span>
            <span class="orphaned_stat">${formatNumberLocale(group.total_hours, 1, 1)} hours</span>
            <span class="orphaned_stat">${escapeHTML(group.date_range)}</span>
          </div>
        </div>
        <button 
          type="button" 
          class="btn btn_primary btn_recover" 
          data-orphaned-site-id="${escapeHTML(group.site_id)}"
          data-site-name="${escapeHTML(group.site_name)}"
          data-work-count="${group.count}">
          🔧 Recover
        </button>
      </div>
    `).join('');

    PC.setHTML(container, html);

    // Attach click handlers
    container.querySelectorAll('.btn_recover').forEach(btn => {
      btn.addEventListener('click', () => {
        const orphanedSiteId = btn.dataset.orphanedSiteId;
        const siteName = btn.dataset.siteName;
        const workCount = btn.dataset.workCount;
        
        debugLog('Opening recovery site dialog', {orphanedSiteId, siteName, workCount});
        openRecoverySiteDialog(orphanedSiteId, siteName, workCount);
      });
    });
  }


  /**
   * Open recovery site creation dialog
   * @param {string} orphanedSiteId - Old site ID
   * @param {string} siteName - Suggested site name
   * @param {number} workCount - Number of work entries
   */
  function openRecoverySiteDialog(orphanedSiteId, siteName, workCount) {
    debugLog('openRecoverySiteDialog called', {orphanedSiteId, siteName, workCount});
    const modal = PC.getElement('modal_recovery_site');
    const form = PC.getElement('recovery_site_form');
    
    if (!modal || !form) return;

    // Close orphaned work modal
    const orphanedModal = PC.getElement('modal_orphaned_work');
    orphanedModal?.close();

    // Set form values
    PC.getElement('recovery_orphaned_site_id').value = orphanedSiteId;
    PC.getElement('recovery_site_name_input').value = siteName;
    PC.getElement('recovery_site_name_display').textContent = siteName;
    PC.getElement('recovery_work_count_display').textContent = 
      `${workCount} work ${workCount === 1 ? 'entry' : 'entries'}`;
    
    // Clear other fields
    PC.getElement('recovery_site_wage_input').value = '';
    PC.getElement('recovery_site_loa_input').value = '';
    PC.getElement('recovery_site_travel_input').value = '';
    PC.getElement('recovery_site_province_select').value = 'AB';

    modal.showModal();
  }


  /**
   * Handle "Create Site & Bind Work" action.
   * Creates a site from form data, then re-links orphaned entries to that new site.
   * @param {Event} e - Submit event
   */
  async function handleRecoverySiteSubmit(e) {
    debugLog('handleRecoverySiteSubmit called');
    e.preventDefault();
    
    const form = e.target;
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);
    const recoveryNameInput = PC.getElement('recovery_site_name_input');
    const recoveryWageInput = PC.getElement('recovery_site_wage_input');

    if (!validateRequiredSiteFields(recoveryNameInput, recoveryWageInput, 'recovery_site_form_status', 'recovery_site_name_error', 'recovery_site_wage_error')) {
      return;
    }

    setFormStatus('recovery_site_form_status', '');

    const submitBtn = PC.getElement('recovery_site_submit');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Creating...';
    }

    try {
      debugLog('Submitting recovery site form');
      const httpResponse = await fetch(apiUrl('sites/recover'), {
        method: 'POST',
        headers: FORM_JSON_AJAX_HEADERS,
        credentials: 'include',
        body: params
      });

      const data = await httpResponse.json();

      // Response is now flattened: { status, message, new_site_id, bound_count }
      if (httpResponse.ok && data.new_site_id) {
        debugLog('Recovery site created and work bound', data);
        
        // Close recovery modal
        const modal = PC.getElement('modal_recovery_site');
        modal?.close();
        
        // Reload grids
        gridManagerActive.reload();
        
        // Reload earnings
        await loadSiteEarnings();
        
        // Recheck orphaned work (should be one less group now)
        await checkOrphanedWork();
        
        // Reset form
        form.reset();
        setFormStatus('recovery_site_form_status', '');
        setFieldError(recoveryNameInput, 'recovery_site_name_error', '');
        setFieldError(recoveryWageInput, 'recovery_site_wage_error', '');
      } else {
        PW.error(`Recovery failed: ${data.message || 'Unknown error'}`);
        setFormStatus('recovery_site_form_status', data.message || 'Failed to recover orphaned work.');
        PC.showToast(data.message || 'Failed to recover orphaned work');
      }
    } catch (error) {
      PW.error(`Recovery error: ${error?.message || String(error)}`);
      setFormStatus('recovery_site_form_status', 'Failed to recover orphaned work. Please try again.');
      PC.showToast('Failed to recover orphaned work. Please try again.');
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Create Site & Bind Work';
      }
    }
  }
});


