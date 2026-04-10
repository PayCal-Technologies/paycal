<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

require_once __DIR__ . '/../../config.php';

Authentication::abortIfUnauthenticated();

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

Javascript::renderDocBlock();

$user = User::current();
$csrfNonce = $user->generateFormNonce('calendar');

$i18nKeys = [
  'ERROR_CAL_INVALID_HOURS',
  'ERROR_CAL_NONCE_FETCH',
  'ERROR_CAL_NONCE_PASTE',
  'ERROR_CAL_NONCE_SAVE',
  'ERROR_CAL_PASTE_FAILED',
  'ERROR_CAL_TOTAL_HOURS_EXCEED',
  'I_WORK_DETAILS',
  'LAST_UPDATED',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

switch ($user->calendar_autofocus) {
  case 'first':
    $sJSAutoFocusDayID = date('Y-m-01');

    break;

  default:
  case 'today':
  case 'current':
    $sJSAutoFocusDayID = date('Y-m-d');

    break;

  case 'last':
    $sJSAutoFocusDayID = date('Y-m-t');

    break;
}
?>

import PC from '<?php echo Environment::appURL('js/'); ?>';
import PW from '<?php echo Environment::appURL('js/phantomwing/'); ?>';

let sites = <?php echo Sites::getSitesAsJson($user->user_uuid); ?>;
const MSG_CAL_INVALID_HOURS = <?php echo json_encode($i18n['ERROR_CAL_INVALID_HOURS']); ?>;
const MSG_CAL_NONCE_FETCH = <?php echo json_encode($i18n['ERROR_CAL_NONCE_FETCH']); ?>;
const MSG_CAL_NONCE_PASTE = <?php echo json_encode($i18n['ERROR_CAL_NONCE_PASTE']); ?>;
const MSG_CAL_NONCE_SAVE = <?php echo json_encode($i18n['ERROR_CAL_NONCE_SAVE']); ?>;
const MSG_CAL_PASTE_FAILED = <?php echo json_encode($i18n['ERROR_CAL_PASTE_FAILED']); ?>;
const MSG_CAL_TOTAL_HOURS = <?php echo json_encode($i18n['ERROR_CAL_TOTAL_HOURS_EXCEED']); ?>;
const MSG_CAL_ENCRYPTION_REQUIRED = 'Encryption key unavailable. Unlock your account key to view or save work entries.';
const CAL_TOTAL_HOURS_MAX = 24;
const LABEL_LAST_UPDATED = <?php echo json_encode($i18n['LAST_UPDATED']); ?>;
const CAL_WORK_ENTRY_FIELDS = {
  hours: <?php echo $user->calendar_work_entry_fields_hours ? 'true' : 'false'; ?>,
  overtime: <?php echo $user->calendar_work_entry_fields_overtime ? 'true' : 'false'; ?>,
  livingOut: <?php echo $user->calendar_work_entry_fields_living_out ? 'true' : 'false'; ?>,
  travel: <?php echo $user->calendar_work_entry_fields_travel ? 'true' : 'false'; ?>
};

// Global calendar state
let currentYear = null;
let currentMonth = null;
let calendarData = null;
let preferredDayNumber = null; // Day number to focus after month navigation

// --- PayCal Encryption Integration ---
// Atomic DEK lifecycle: unwrap or generate/wrap DEK on login, store in memory only, never dual-write, never use KEK for entry encryption
const PayCalCryptoState = {
  dek: null, // CryptoKey
  dekVersion: 1
};

function showUnlockPanel(message = '') {
  const detail = String(message || '').trim();
  const fullMessage = detail === ''
    ? 'Passkey unlock is required to access encrypted entries.'
    : `Passkey unlock is required to access encrypted entries. ${detail}`;
  PC.showToast(fullMessage, 'error', 7000, true);
}

async function ensurePayCalDEK() {
  if (PayCalCryptoState.dek) {
    return true;
  }

  showUnlockPanel();
  return false;
}

// AJAX calendar loader
async function loadCalendar(year, month, pushState = true) {
  try {
    const response = await fetch(`${PC.config.pc_api}/calendar?year=${year}&month=${month}`, {
      credentials: 'include'  // Send cookies with request
    });
    const payload = await response.json();
    if (payload.status !== 'success' || !payload || !payload.weeks) {
      PW.error(`Failed to load calendar: ${payload.message || 'Unknown error'}`);
      return;
    }

    calendarData = payload;
    currentYear = year;
    currentMonth = month;
    
    renderCalendar(calendarData);
    attachCalendarEventListeners();
    
    // Update URL without page reload
    if (pushState) {
      const paddedMonth = String(month).padStart(2, '0');
      history.pushState({ year, month }, '', `?${year}-${paddedMonth}`);
    }
    
    // Update page title
    document.title = `${calendarData.monthName} ${year} - PayCal`;
    
  } catch (error) {
    PW.error(`Error loading calendar: ${error?.message || String(error)}`);
  }
}

// Render calendar HTML from JSON data
function renderCalendar(data) {
  // Update title
  const titleSection = PC.getElement('cal_date_nav');
  if (titleSection) {
    PC.setHTML(titleSection, `
      <a href="${data.navigation.prev.url}"
         id="month_nav_prev"
         class="btn"
         data-year="${data.navigation.prev.year}"
         data-month="${data.navigation.prev.month}"
         aria-label="Go to ${data.navigation.prev.url}"
         aria-keyshortcuts="[ PageUp"
        accesskey="[">
         ${PC.state.prevSVG}
      </a>
      <button id="cal_picker_button"
         aria-label="${data.monthName} ${data.year}"
         aria-keyshortcuts="ALT+\\"
        accesskey="\\">
         ${data.monthName} ${data.year}
      </button>
      <a href="${data.navigation.next.url}"
         id="month_nav_next"
         class="btn"
         data-year="${data.navigation.next.year}"
         data-month="${data.navigation.next.month}"
         aria-label="Go to ${data.navigation.next.url}"
         aria-keyshortcuts="] PageDown"
        accesskey="]">
         ${PC.state.nextSVG}
      </a>
    `);
  }

  // Update week headers
  const weekHeaderSection = PC.getElement('cal_week_header');
  if (weekHeaderSection) {
    PC.setHTML(weekHeaderSection, data.weekHeaders.map(day => `
      <div class="calendar_header" role="columnheader" aria-readonly="true" aria-label="${day.full}">
        <abbr title="${day.full}">${day.abbr}</abbr>
      </div>
    `).join(''));
  }

  const normalizeWorkHtml = (html) => {
    if (!html) return html;
    return html
      .replace(/&nbsp;\(/g, '&nbsp;/&nbsp;')
      .replace(/,\s*&nbsp;/g, '&nbsp;/&nbsp;')
      .replace(/\)/g, '');
  };

  // Render calendar grid
  const calendarGrid = PC.getElement('calendar_grid');
  if (calendarGrid) {
    let html = '';
    let weekHtml = '';

    data.days.forEach((day, index) => {
      if (index > 0 && index % 7 === 0) {
        html += `<section class="week" role="row">${weekHtml}</section>\n`;
        weekHtml = '';
      }

      weekHtml += `
        <button id="${day.id}"
             class="${day.classes}"
             ${day.isToday ? 'aria-current="date"' : ''}
             aria-label="${day.ariaLabel}"
             aria-haspopup="dialog"
             role="cell"
             data-work-entries='${JSON.stringify(day.workEntries)}'
             tabindex="-1">
          <time datetime="${day.id}" class="day_label" aria-hidden="true">${day.id}</time>
          <b class="day_number" aria-hidden="true">${day.day}</b>
          <b class="day_label">${day.dateLabel}</b>
          ${normalizeWorkHtml(day.workHtml)}
        </button>
      `;
    });

    // Add final week
    if (weekHtml) {
      html += `<section class="week" role="row">${weekHtml}</section>\n`;
    }

    PC.setHTML(calendarGrid, html);
  }
}

// Attach all event listeners to calendar elements
function attachCalendarEventListeners() {
  // Navigation click handlers (prevent page reload, use AJAX)
  const prevBtn = PC.getElement('month_nav_prev');
  const nextBtn = PC.getElement('month_nav_next');

  if (prevBtn) {
    prevBtn.addEventListener('click', (e) => {
      e.preventDefault();
      // Capture current day number before navigating
      preferredDayNumber = getCurrentDayNumber();
      const year = parseInt(prevBtn.dataset.year);
      const month = parseInt(prevBtn.dataset.month);
      loadCalendar(year, month);
    });
  }

  if (nextBtn) {
    nextBtn.addEventListener('click', (e) => {
      e.preventDefault();
      // Capture current day number before navigating
      preferredDayNumber = getCurrentDayNumber();
      const year = parseInt(nextBtn.dataset.year);
      const month = parseInt(nextBtn.dataset.month);
      loadCalendar(year, month);
    });
  }

  // Calendar picker button
  const pickerBtn = PC.getElement('cal_picker_button');
  if (pickerBtn) {
    pickerBtn.addEventListener('click', (e) => {
      var button = e.target;
      var expanded = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-label', String(!expanded));
      PC.openModal('modal_cal_picker', 'Date Picker');

      // Focus the currently selected year button when opening
      const selectedYearBtn = PC.query('#cal_menu_left button.cal_menu_selected');
      if (selectedYearBtn) {
        setTimeout(() => selectedYearBtn.focus(), 100);
      }
    });
  }

  // Year/Month picker selection handlers
  const yearButtons = Array.from(PC.queryAll('#cal_menu_left button'));
  const monthButtons = Array.from(PC.queryAll('#cal_menu_right button'));

  yearButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      // Remove selected class from all year buttons
      yearButtons.forEach(b => {
        b.classList.remove('cal_menu_selected');
        b.setAttribute('aria-pressed', 'false');
      });
      // Add selected class to clicked button
      btn.classList.add('cal_menu_selected');
      btn.setAttribute('aria-pressed', 'true');
    });
  });

  monthButtons.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      // Remove selected class from all month buttons
      monthButtons.forEach(b => {
        b.classList.remove('cal_menu_selected');
        b.setAttribute('aria-pressed', 'false');
      });
      // Add selected class to clicked button
      btn.classList.add('cal_menu_selected');
      btn.setAttribute('aria-pressed', 'true');
    });
  });

  // Day event listeners
  const days = Array.from(PC.queryAll('.calendar_day')).sort();
  const day_context_menu = PC.getElement('calendar_day_context_menu');

  days.forEach(day => {
    try {
      const payload = JSON.parse(day.getAttribute('data-work-entries') || '[]');
      renderDayEntries(day, payload);
    } catch {
      renderDayEntries(day, []);
    }

    // mouse click on day to open
    day.addEventListener('click', () => { if (PC.state.modal_is_active) return; show_modal_cal_work(day); });

    // mouse context menu
    day.addEventListener('contextmenu', (e) => { show_day_context_menu(e, day.id); });

    // keyboard context menu
    day.addEventListener('keydown', (e) => {
      if (e.key === 'contextmenu' || (e.key === 'Enter' && e.ctrlKey))
        show_day_context_menu(e, day.id);
    });

    // keyboard copy/paste
    day.addEventListener('keydown', (e) => { if ((e.ctrlKey || e.metaKey) && e.key === 'c') { day_copy(e, day.id); } });
    day.addEventListener('keydown', (e) => { if ((e.ctrlKey || e.metaKey) && e.key === 'v') { day_paste(e, day.id); } });

    // keyboard navigation
    day.addEventListener('keydown', handle_day_navigation);

    // Roving tabindex: keep only the focused day in the tab order.
    day.addEventListener('focus', () => {
      days.forEach(d => d.setAttribute('tabindex', '-1'));
      day.setAttribute('tabindex', '0');
    });

    // audio feedback on focus
    day.addEventListener('focus', (event) => {
      const label = day.getAttribute('aria-label');
      if (PC.state.audio_feedback == 'all' && label) {
        PC.textToSpeech(label);
      }
    });
  });

  // Day context menu navigation
  if (day_context_menu) {
    day_context_menu.addEventListener('keydown', (e) => {
      if (!['ArrowDown', 'ArrowUp'].includes(e.key))
        return;

      e.preventDefault();
      e.stopPropagation();
      const current = document.activeElement;
      const items = Array.from(day_context_menu.querySelectorAll('li'));

      let new_index;
      if (e.key === 'ArrowDown') {
        const next_index = items.indexOf(current) + 1;
        new_index = next_index >= items.length ? 0 : next_index;
      } else if (e.key === 'ArrowUp') {
        const prev_index = items.indexOf(current) - 1;
        new_index = prev_index < 0 ? items.length - 1 : prev_index;
      }

      items[new_index].focus();
    });
  }

  // Auto-focus active day
  // Prioritize: preferred day number > stored active_day_id > today > first day
  let activeDay = null;

  // If we have a preferred day number from navigation, try to focus on that day
  if (preferredDayNumber !== null && calendarData) {
    // Find the day with matching day number in current month
    const targetDate = `${calendarData.year}-${String(calendarData.month).padStart(2, '0')}-${String(preferredDayNumber).padStart(2, '0')}`;
    activeDay = PC.getElement(targetDate);

    // If that day doesn't exist in this month (e.g., Feb 31), try last day of month
    if (!activeDay) {
      const daysInMonth = calendarData.days.filter(d => d.month === calendarData.month);
      if (daysInMonth.length > 0) {
        const lastDayInMonth = daysInMonth[daysInMonth.length - 1];
        activeDay = PC.getElement(lastDayInMonth.id);
      }
    }
  }

  if (!activeDay) {
    // Try stored active_day_id from initial page load
    activeDay = PC.getElement(PC.state.active_day_id);
  }

  if (!activeDay) {
    // Try to find today's date in this calendar
    const today = new Date().toISOString().split('T')[0];
    activeDay = PC.getElement(today);
  }

  if (!activeDay) {
    // Fall back to first calendar day
    activeDay = PC.query('.calendar_day');
  }

  if (activeDay) {
    days.forEach(d => d.setAttribute('tabindex', '-1'));
    activeDay.setAttribute('tabindex', '0');
    setTimeout(() => activeDay.focus(), 100);
  }
}

// Keyboard navigation handler
const handle_day_navigation = (e) => {
  const day_context_menu = PC.getElement('calendar_day_context_menu');
  if (day_context_menu && !day_context_menu.classList.contains('hidden')) {
    return;
  }

  const days = Array.from(PC.queryAll('.calendar_day')).sort();
  let dx = days.findIndex(day => day === document.activeElement);

  switch (e.keyCode) {
    case 46: /* Delete   */ day_delete(e, days[dx].id); break;
    case 40: /* Down     */ dx = dx + 7 > (days.length - 1) ? days.length - 1 : dx + 7; days[dx].focus(); break;
    case 39: /* Right    */ dx = dx + 1 < (days.length - 1) ? dx + 1 : days.length - 1; days[dx].focus(); break;
    case 38: /* Up       */ dx = dx - 7 >= 0 ? dx - 7 : 0; days[dx].focus(); break;
    case 37: /* Left     */ dx = dx - 1 >= 0 ? dx - 1 : 0; days[dx].focus(); break;
    case 36: /* Home     */ e.preventDefault(); days[0].focus(); break;
    case 35: /* End      */ e.preventDefault(); days[days.length - 1].focus(); break;
    case 34: /* PageDown */
      e.preventDefault();
      preferredDayNumber = getCurrentDayNumber();
      navigateMonth('next');
      break;
    case 33: /* PageUp   */
      e.preventDefault();
      preferredDayNumber = getCurrentDayNumber();
      navigateMonth('prev');
      break;
  }

  // Bracket shortcuts for month navigation
  switch (e.keyCode) {
    case 219: /* [ : Previous Month */
      e.preventDefault();
      preferredDayNumber = getCurrentDayNumber();
      navigateMonth('prev');
      break;
    case 221: /* ] : Next Month */
      e.preventDefault();
      preferredDayNumber = getCurrentDayNumber();
      navigateMonth('next');
      break;
  }
};

// Navigate to next/prev month via AJAX
function navigateMonth(direction) {
  if (!calendarData) return;

  const nav = direction === 'next' ? calendarData.navigation.next : calendarData.navigation.prev;
  loadCalendar(nav.year, nav.month);
}

// Helper to get current day number from calendar for navigation context
function getCurrentDayNumber() {
  // If a calendar day is currently focused, use that
  if (document.activeElement && document.activeElement.classList && document.activeElement.classList.contains('calendar_day')) {
    const currentDate = document.activeElement.id;
    if (currentDate) {
      return parseInt(currentDate.split('-')[2], 10);
    }
  }

  // Otherwise, find today if it exists in the current calendar
  const today = new Date().toISOString().split('T')[0];
  const todayElement = PC.getElement(today);
  if (todayElement) {
    return parseInt(today.split('-')[2], 10);
  }

  // Fall back to the 15th of the month (middle of month)
  return 15;
}


// Legacy loader note: encrypted entry unlock is passkey-only in current PayCal runtime.

document.addEventListener('DOMContentLoaded', async () => {

  // Init
  PC.state.active_day_id = '<?php echo $sJSAutoFocusDayID; ?>';
  PC.state.context_menu_is_active = false;
  PC.state.default_site_id = '<?php echo htmlspecialchars($user->default_site_id ?? ''); ?>';
  PC.state.default_hours = Number('<?php echo htmlspecialchars($user->default_hours ?? '0'); ?>');
  PC.state.default_living_out_allowance = Number('<?php echo htmlspecialchars($user->default_living_out_allowance ?? '0'); ?>');
  PC.state.default_travel_hours = Number('<?php echo htmlspecialchars($user->default_travel_hours ?? '0'); ?>');
  PC.state.csrfNonce = '<?php echo $csrfNonce; ?>';

  // Store SVG for navigation buttons
  PC.state.prevSVG = `<?php echo str_replace(["\r", "\n"], '', Strings::html('h_PREVIOUS_SVG')); ?>`;
  PC.state.nextSVG = `<?php echo str_replace(["\r", "\n"], '', Strings::html('h_NEXT_SVG')); ?>`;

  // Fetch latest sites
  try {
    const response = await fetch(`${PC.config.pc_api}/sites`);
    const payload = await response.json();
    if (payload && payload.sites) {
      sites = payload.sites;
    }
  } catch (error) {
    PW.error(`Failed to fetch sites: ${error?.message || String(error)}`);
  }

  // Global document click to hide day context menu
  document.addEventListener('click', () => {
    const menu = PC.getElement('calendar_day_context_menu');
    if (menu) menu.classList.add('hidden');
  });

  // Modal button handlers
  const datePickerGoBtn = PC.getElement('date_picker_go_btn');
  if (datePickerGoBtn) {
    datePickerGoBtn.addEventListener('click', (e) => {
      e.preventDefault();

      // Get selected year and month from the picker
      const selectedYearBtn = PC.query('#cal_menu_left button.cal_menu_selected');
      const selectedMonthBtn = PC.query('#cal_menu_right button.cal_menu_selected');

      if (selectedYearBtn && selectedMonthBtn) {
        const year = parseInt(selectedYearBtn.dataset.year);
        const month = parseInt(selectedMonthBtn.dataset.month);
        
        // Close the modal
        PC.closeModal('modal_cal_picker', 'Date Picker');
        
        // Load the selected calendar
        preferredDayNumber = getCurrentDayNumber();
        loadCalendar(year, month);
      }
    });
  }
  
  const datePickerBtn = PC.getElement('date_picker_close_btn');
  if (datePickerBtn) {
    datePickerBtn.addEventListener('click', (e) => { PC.closeModal('modal_cal_picker', 'Date Picker'); });
  }
  
  const workCancelBtn = PC.getElement('cal_work_cancel_btn');
  if (workCancelBtn) {
    workCancelBtn.addEventListener('click', (e) => { 
      e.preventDefault(); 
      PC.closeModal('modal_cal_work', PC.toTitleCase(<?php echo json_encode($i18n['I_WORK_DETAILS']); ?>)); 
    });
  }
  
  const workForm = PC.getElement('cal_work_form');
  if (workForm) {
    workForm.addEventListener('submit', (e) => {
      e.preventDefault();
      e.stopPropagation();
      save_work(e);
    });
  }

  const addEntryBtn = PC.getElement('add-entry');
  if (addEntryBtn) {
    addEntryBtn.addEventListener('click', () => { add_new_entry(); });
  }

  const add_new_entry = () => {
    const container = PC.getElement('work-entries-container');
    const count = container.children.length;
    const new_div = document.createElement("div");
    const new_site_id = PC.generateSiteUUID();
    new_div.dataset.work = JSON.stringify({site_id: new_site_id});
    create_work_row(new_div, count);
    container.appendChild(new_div);
    updateWorkTotalMessage();
  };

  // Day context menu items
  const day_context_menu = PC.getElement('calendar_day_context_menu');
  const day_context_menu_items = day_context_menu ? day_context_menu.querySelectorAll('li') : [];

  day_context_menu_items.forEach((menu_item) => {
    
    // menu item handler, called by event listeners
    const handle_menu_item = (e, day_id) => {
      const action = menu_item.querySelector('span').textContent.trim();
      switch (action) {
        case 'Copy':   day_copy(e, day_id);   break;
        case 'Paste':  day_paste(e, day_id);  break;
        case 'Open':   day_open(e, day_id);   break;
        case 'Delete': day_delete(e, day_id); break;
      }
    };

    menu_item.addEventListener('click', (e) => {
      let day_id = PC.getElement('calendar_day_context_menu_head').innerHTML;
      handle_menu_item(e, day_id);
      day_context_menu.classList.add('hidden');
      PC.getElement(PC.state.active_day_id).focus();
    });

    menu_item.addEventListener('keydown', (e) => {
      let day_id = PC.getElement('calendar_day_context_menu_head').innerHTML;
      if (e.key === 'contextmenu') {
        e.stopPropagation();
      }

      if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        handle_menu_item(e, day_id);
        day_context_menu.classList.add('hidden');
        PC.getElement(PC.state.active_day_id).focus();
      }

      if (e.key === 'Escape') {
        e.preventDefault();
        e.stopPropagation();
        day_context_menu.classList.add('hidden');
        PC.getElement(PC.state.active_day_id).focus();
      }

      if (['Home', 'End'].includes(e.key)) {
        e.preventDefault();
        e.stopPropagation();
        const items = Array.from(day_context_menu.querySelectorAll('li'));
        if (e.key === 'Home') {
          items[0].focus();
        } else if (e.key === 'End') {
          items[items.length - 1].focus();
        }
      }
    });
  });

  // Prevent PageUp/PageDown from scrolling window when calendar is active
  document.addEventListener('keydown', (e) => {
    if ((e.keyCode === 33 || e.keyCode === 34)) { // PageUp or PageDown
      const active = document.activeElement;
      // Check if focused element is a calendar day (by class or by ID pattern YYYY-MM-DD)
      const isCalendarDay = active && (
        (active.classList && active.classList.contains('calendar_day')) ||
        (active.id && /^\d{4}-\d{2}-\d{2}$/.test(active.id)) ||
        active.closest && active.closest('[data-work]') // Work entry element
      );
      if (isCalendarDay) {
        e.preventDefault();
      }
    }
  }, true); // Capture phase to intercept before other listeners

  // Calendar Date Picker navigation with arrow keys
  const p = Array.from(PC.queryAll('#modal_cal_picker button'));
  document.addEventListener('keydown', (e) => {
    // Only handle if the active element is within the date picker
    if (!document.activeElement || !document.activeElement.closest('#modal_cal_picker')) return;
    
    let px = p.findIndex(btn => btn === document.activeElement);
    if (px === -1) return; // Not focused on a picker button
    
    switch (e.keyCode) {
      case 40: /* Down  */ 
        e.preventDefault();
        px = px + 1 > (p.length - 1) ? p.length - 1 : px + 1; 
        p[px].focus(); 
        break;
      case 39: /* Right */ 
        e.preventDefault();
        px = px + 1 > (p.length - 1) ? p.length - 1 : px + 1; 
        p[px].focus(); 
        break;
      case 38: /* Up    */ 
        e.preventDefault();
        px = px - 1 >= 0 ? px - 1 : 0; 
        p[px].focus(); 
        break;
      case 37: /* Left  */ 
        e.preventDefault();
        px = px - 1 >= 0 ? px - 1 : 0; 
        p[px].focus(); 
        break;
      case 36: /* Home  */ 
        e.preventDefault();
        p[0].focus(); 
        break;
      case 35: /* End   */ 
        e.preventDefault();
        p[p.length - 1].focus(); 
        break;
      case 13: /* Enter */
        e.preventDefault();
        // If focused on a year/month button, select it
        if (document.activeElement.dataset.year || document.activeElement.dataset.month) {
          document.activeElement.click();
        }
        break;
    }
  });
  
  // Parse initial year/month from URL
  const urlParams = new URLSearchParams(window.location.search);
  const urlPath = window.location.search.substring(1); // Remove leading ?
  let initialYear, initialMonth;
  
  if (urlPath && urlPath.match(/^\d{4}-\d{2}$/)) {
    [initialYear, initialMonth] = urlPath.split('-').map(Number);
  } else {
    const now = new Date();
    initialYear = now.getFullYear();
    initialMonth = now.getMonth() + 1;
  }
  
  // Load initial calendar data via AJAX
  if (!(await ensurePayCalDEK())) {
    PC.showToast(MSG_CAL_ENCRYPTION_REQUIRED);
  }
  await loadCalendar(initialYear, initialMonth, false);
  
  // Handle browser back/forward buttons
  window.addEventListener('popstate', (e) => {
    if (e.state && e.state.year && e.state.month) {
      preferredDayNumber = null; // Reset preferred day on browser navigation
      loadCalendar(e.state.year, e.state.month, false);
    }
  });

  // Audio introduction button: speaks a scripted page overview when activated.
  const audioIntroBtn = PC.getElement('page_audio_intro');
  if (audioIntroBtn) {
    audioIntroBtn.addEventListener('click', () => {
      const month = calendarData?.monthName ?? '';
      const year  = calendarData?.year  ?? '';
      const calendarLabel = (month && year) ? `${month} ${year}` : 'Month Year';
      const intro = [
        `Welcome to PayCal. Showing calendar for ${calendarLabel}.`,
        'Days are in a 6 week grid.',
        'Arrow keys move between days.',
        'Enter opens Work Editor.',
        'Page Up / Page Down changes months.',
        '\\ opens the date picker.',
        'Ctrl + K opens keyboard shortcuts.',
        'Days are announced on focus.',
      ].join(' ');
      PC.textToSpeech(intro, 'status');
    });
  }

}); // END DOM CONTENT LOADED

// Helper functions (outside DOMContentLoaded for global access)

const show_day_context_menu = (e, day_id) => {
  const day_context_menu = PC.getElement('calendar_day_context_menu');
  PC.getElement('calendar_day_context_menu_head').innerText = day_id;
  // Allow default behaviour -- we are not animals! :D
  if (e.shiftKey === true) {
    return;
  }

  day_context_menu.classList.remove('hidden');
  PC.state.context_menu_is_active = true;
  e.preventDefault();
  e.stopPropagation();

  const menu_width    = day_context_menu.offsetWidth;
  const menu_height   = day_context_menu.offsetHeight;
  const window_width  = window.innerWidth;
  const window_height = Math.max(document.body.scrollHeight, document.documentElement.scrollHeight, document.body.offsetHeight, document.documentElement.offsetHeight, document.body.clientHeight, document.documentElement.clientHeight);

  const day_rect      = PC.getElement(day_id).getBoundingClientRect();
  let cm_x            = day_rect.left + Math.floor(day_rect.width / 10);
  let cm_y            = day_rect.top + Math.floor(day_rect.height / 10);

  if (cm_x + menu_width > window_width) {
    cm_x = window_width - menu_width - Math.floor(menu_width / 10);
  }

  if (cm_y + menu_height > window_height) {
    cm_y = window_height - menu_height - Math.floor(menu_height / 10);
  }

  const currentAnchor = day_context_menu.parentElement;
  if (currentAnchor && currentAnchor.classList && currentAnchor.classList.contains('context-menu-anchor')) {
    currentAnchor.classList.remove('context-menu-anchor');
  }

  const dayElement = PC.getElement(day_id);
  if (dayElement) {
    dayElement.classList.add('context-menu-anchor');
    day_context_menu.classList.toggle('context-menu-align-right', cm_x + menu_width > window_width);
    day_context_menu.classList.toggle('context-menu-align-top', cm_y + menu_height > window_height);
    dayElement.appendChild(day_context_menu);
  }

  PC.state.active_day_id = day_id;
  const first_menu_item  = day_context_menu.querySelector('li');
  first_menu_item.focus();
};

const day_copy = (e, day_id) => {
  e.preventDefault();

  const works = Array.from(PC.queryAll('.work', PC.getElement(day_id)));
  if (works.length === 0) {
    PC.showToast(`${PC.getElement(day_id).ariaLabel} is blank. Copy aborted.`);
    return;
  }

  const allData = works.map(w => JSON.parse(w.dataset.work || "{}"));
  localStorage.setItem("cal_work_data", JSON.stringify(allData));

  const text = PC.query("b.day_label", PC.getElement(day_id)).textContent.trim();
  PC.showToast(`Copied ${allData.length} work entr${allData.length === 1 ? 'y' : 'ies'} from ${text}`);
};

const day_paste = async (e, day_id) => {
  e.preventDefault();
  e.stopPropagation();

  const day = PC.getElement(day_id);
  const json = localStorage.getItem("cal_work_data");
  if (!json) return;

  // Fetch fresh nonce
  const nonceUrl = `${window.location.origin}/api/v1/calendar/nonce`;
  let csrfToken = "";
  try {
    const nonceResponse = await fetch(nonceUrl, { method: 'GET', credentials: 'same-origin' });
    const nonceData = await nonceResponse.json();
    csrfToken = nonceData.data?.nonce || nonceData.nonce || nonceData.message?.nonce || '';
    if (!csrfToken) {
      PC.showToast(MSG_CAL_NONCE_PASTE);
      return;
    }
  } catch {
    PC.showToast(MSG_CAL_NONCE_FETCH);
    return;
  }

  const allData = JSON.parse(json);
  if (allData.length === 0) return;

  renderDayEntries(day, allData);

  // Optionally send all entries to server
  const form_data = new FormData();
  form_data.append("d", day_id);
  form_data.append("entries", JSON.stringify(allData));
  form_data.append("csrf_token", csrfToken);

  try {
    const response = await PC.updateResource('calendar', form_data);
    updateCalendarWeek(response?.week);
    const text = PC.query("b.day_label", day).textContent.trim();
    PC.showToast(`Pasted ${allData.length} work entr${allData.length === 1 ? 'y' : 'ies'} to ${text}`);
  } catch {
    PC.showToast(MSG_CAL_PASTE_FAILED);
  }
};

const day_open = (e, day_id) => { e.preventDefault(); PC.getElement(day_id).click(); };

const day_delete = (e, day_id) => {
  e.preventDefault();
  PC.queryAll('.work', PC.getElement(day_id)).forEach(c => c.remove());
  PC.deleteResource('calendar', day_id);
};

const formatEntryNumber = (value) => {
  const num = Number(value);
  return Number.isFinite(num) ? num : 0;
};

if (!window.PayCalAriaEcho) {
  window.PayCalAriaEcho = class AriaEcho {
    static normalizeText(text) {
      return String(text ?? '')
        .trim()
        .replace(/\s*\/\s*/g, ', ')
        .replace(/\s*,\s*/g, ', ')
        .replace(/\s*;\s*/g, '; ')
        .replace(/\s*\.\s*/g, '. ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    static cadence(input, delimiter = ', ') {
      if (Array.isArray(input)) {
        const filtered = input
          .map((part) => this.normalizeText(part))
          .filter((part) => part !== '');
        if (filtered.length === 0) return '';
        if (filtered.length === 1) return filtered[0];
        const sep = String(delimiter || '').trim() === '' ? ', ' : delimiter;
        return `${filtered.slice(0, -1).join(sep)}${sep}and ${filtered[filtered.length - 1]}`;
      }

      const normalized = this.normalizeText(input);
      if (normalized === '') return '';

      let parts = [];
      if (String(delimiter || '').trim() !== '' && normalized.includes(delimiter)) {
        parts = normalized.split(delimiter);
      } else if (/[|/;]/.test(normalized)) {
        parts = normalized.split(/\s*(?:\||\/|;)\s*/);
      }

      if (parts.length > 1) {
        return this.cadence(parts, delimiter);
      }

      return normalized;
    }

    static cadenceList(parts) {
      return this.cadence(parts, ', ');
    }
  };
}

const buildWorkEntryAriaLabel = (siteName, dateAria, metrics) => {
  const spokenSite = String(siteName || '').trim() || 'Work entry';
  const spokenSummary = window.PayCalAriaEcho.cadence(metrics, ', ');
  if (spokenSummary === '') {
    return window.PayCalAriaEcho.cadence(dateAria ? `${spokenSite} on ${dateAria}.` : `${spokenSite}.`);
  }
  return window.PayCalAriaEcho.cadence(dateAria ? `${spokenSite} on ${dateAria}. ${spokenSummary}.` : `${spokenSite}. ${spokenSummary}.`);
};

const updateWorkTotalMessage = () => {
  const container = PC.getElement('work-entries-container');
  if (!container) return;

  const hoursInputs = container.querySelectorAll('input[name="hours"]');
  let totalHours = 0;
  hoursInputs.forEach((input) => {
    const value = parseFloat(input.value);
    if (!Number.isNaN(value)) totalHours += value;
  });

  const messageEl = PC.getElement('cal_work_message');
  const saveBtn = PC.getElement('cal_work_save_close_btn');
  if (totalHours > CAL_TOTAL_HOURS_MAX) {
    if (messageEl) {
      messageEl.textContent = MSG_CAL_TOTAL_HOURS;
      // Add flash animation to visually alert user
      messageEl.classList.add('teams_highlight');
      // Remove animation after it completes (6s duration)
      setTimeout(() => messageEl.classList.remove('teams_highlight'), 6000);
    }
    if (saveBtn) saveBtn.setAttribute('disabled', 'disabled');
  } else {
    if (messageEl) {
      if (messageEl.textContent === MSG_CAL_TOTAL_HOURS) PC.setHTML(messageEl, '&nbsp;');
      messageEl.classList.remove('teams_highlight');
    }
    if (saveBtn) saveBtn.removeAttribute('disabled');
  }
};

// Decrypt entries using DEK only
const renderDayEntries = async (day, entries) => {
  if (!day) return;
  if (!PayCalCryptoState.dek) {
    day.dataset.workEntries = '[]';
    day.querySelectorAll('.work').forEach(w => w.remove());
    return;
  }

  const safeEntries = Array.isArray(entries) ? entries : [];
  const decryptedEntries = [];
  for (const entry of safeEntries) {
    if (entry.encrypted_blob && PayCalCryptoState.dek) {
      try {
        const blob = JSON.parse(atob(entry.encrypted_blob));
        const iv = Uint8Array.from(atob(blob.nonce), c => c.charCodeAt(0));
        const aad = new TextEncoder().encode(blob.aad);
        const ciphertext = Uint8Array.from(atob(blob.ciphertext), c => c.charCodeAt(0));
        const decoded = await window.crypto.subtle.decrypt(
          { name: 'AES-GCM', iv, additionalData: aad },
          PayCalCryptoState.dek,
          ciphertext
        );
        const decrypted = JSON.parse(new TextDecoder().decode(decoded));
        decryptedEntries.push(decrypted);
      } catch (e) {
        window.PayCalEncryption?.telemetry?.({ type: 'decryption-failure', error: String(e) });
      }
    }
  }
  day.dataset.workEntries = JSON.stringify(decryptedEntries);
  day.querySelectorAll('.work').forEach(w => w.remove());
  const dateAriaLabel = String(day.getAttribute('aria-label') || '').trim();

  decryptedEntries.forEach(entry => {
    const regularHours = formatEntryNumber(entry.regular_hours ?? entry.r);
    const overtimeHours = formatEntryNumber(entry.overtime_hours ?? entry.o);
    const livingOut = formatEntryNumber(entry.living_out_allowance ?? entry.l);
    const travelHours = formatEntryNumber(entry.travel_hours ?? entry.t);
    const siteName = entry.site_name || entry.n || '';

    const work_div = document.createElement('div');
    work_div.className = 'work';
    work_div.dataset.work = JSON.stringify({
      site_id: entry.site_id || entry.s || '',
      site_name: siteName,
      hours: formatEntryNumber(entry.hours ?? entry.h),
      regular_hours: regularHours,
      overtime_hours: overtimeHours,
      living_out_allowance: livingOut,
      travel_hours: travelHours
    });
    const spokenMetrics = [];
    const fields = [];
    if (CAL_WORK_ENTRY_FIELDS.hours) {
      fields.push(regularHours);
      spokenMetrics.push(`${regularHours} regular hours`);
    }
    if (CAL_WORK_ENTRY_FIELDS.overtime) {
      fields.push(overtimeHours);
      spokenMetrics.push(`${overtimeHours} overtime hours`);
    }
    if (CAL_WORK_ENTRY_FIELDS.livingOut) {
      fields.push(livingOut);
      spokenMetrics.push(`${livingOut} living out allowance`);
    }
    if (CAL_WORK_ENTRY_FIELDS.travel) {
      fields.push(travelHours);
      spokenMetrics.push(`${travelHours} travel hours`);
    }

    work_div.setAttribute('aria-label', buildWorkEntryAriaLabel(siteName, dateAriaLabel, spokenMetrics));

    PC.setHTML(work_div, `
${siteName}<br />
${fields.join('&nbsp;/&nbsp;')}
  `);
    day.appendChild(work_div);
  });
};

const updateCalendarWeek = async (week) => {
  if (!week || !week.days) return false;
  for (const [dateId, entries] of Object.entries(week.days)) {
    const day = PC.getElement(dateId);
    if (!day) continue;
    await renderDayEntries(day, entries);
  }
  const status = PC.getElement('calendar_status');
  if (status && week.updated_at) {
    status.textContent = `${LABEL_LAST_UPDATED}: ${week.updated_at}`;
  }
  return true;
};

/*
 * Saves the work details for a selected calendar day.
 * @param event e                        The HTML element representing the selected calendar day.
 * @returns void
 */
const save_work = async (e) => {
  e.preventDefault();
  e.stopPropagation();

  const day_id = PC.getElement('cal_work_date').value;
  const work_rows = PC.getElement('work-entries-container').querySelectorAll('.work_row');
  const save_as_default = PC.getElement('cal_work_save_as_default_btn').checked;
  PC.state.default_save = save_as_default;

  let totalHours = 0;
  const savedEntries = [];

  const cryptoReady = await ensurePayCalDEK();
  if (!cryptoReady || !PayCalCryptoState.dek) {
    PC.showToast(MSG_CAL_ENCRYPTION_REQUIRED);
    return;
  }

  for (const row of work_rows) {
    const site_select           = row.querySelector('[name="site_id"]');

    if (!site_select || site_select.selectedIndex < 0)
      continue;

    const selected_option       = site_select.options[site_select.selectedIndex];
    const site_id               = selected_option.value;
    const site_name             = selected_option.textContent;

    const hours                 = parseFloat(row.querySelector('[name="hours"]').value);
    const living_out_allowance_raw  = parseFloat(row.querySelector('[name="living_out_allowance"]').value);
    const travel_hours_raw          = parseFloat(row.querySelector('[name="travel_hours"]').value);

    if (isNaN(hours) || hours < 0) {
      PC.showToast(MSG_CAL_INVALID_HOURS);
      return;
    }

    const living_out_allowance = Number.isNaN(living_out_allowance_raw) ? 0 : living_out_allowance_raw;
    const travel_hours = Number.isNaN(travel_hours_raw) ? 0 : travel_hours_raw;

    totalHours += hours;

    const regular_hours = Math.min(hours, 8);
    const overtime_hours = Math.max(hours - 8, 0);

    // Encrypt work entry with DEK only
    const entry = {
      site_id: site_id,
      site_name: site_name,
      hours: hours,
      regular_hours: regular_hours,
      overtime_hours: overtime_hours,
      living_out_allowance: living_out_allowance,
      travel_hours: travel_hours
    };
    try {
      const nonce = window.crypto.getRandomValues(new Uint8Array(12));
      const aad = entry.site_id;
      const encoded = new TextEncoder().encode(JSON.stringify(entry));
      const ciphertext = await window.crypto.subtle.encrypt(
        { name: 'AES-GCM', iv: nonce, additionalData: new TextEncoder().encode(aad) },
        PayCalCryptoState.dek,
        encoded
      );
      const encrypted_blob = btoa(JSON.stringify({
        ciphertext: btoa(String.fromCharCode(...new Uint8Array(ciphertext))),
        nonce: btoa(String.fromCharCode(...nonce)),
        aad
      }));

      window.PayCalEncryption?.telemetry?.({ type: 'encryption-success', site: site_id, blobLength: encrypted_blob.length });
      savedEntries.push({ ...entry, encrypted_blob });
    } catch (err) {
      PW.warn(`[PayCal] Encryption failed: ${err.message}`);
      window.PayCalEncryption?.telemetry?.({ type: 'encryption-failure', site: site_id, error: String(err) });
      PC.showToast(MSG_CAL_ENCRYPTION_REQUIRED);
      return;
    }
  }

  // Handle save as default (use first entry if set)
  if (save_as_default && savedEntries.length > 0) {
    const first = savedEntries[0];
    PC.state.default_site_id = first.site_id;
    PC.state.default_hours = first.hours;
    PC.state.default_living_out_allowance = first.living_out_allowance;
    PC.state.default_travel_hours = first.travel_hours;
  }

  // Fetch nonce once
  const nonceUrl = `${window.location.origin}/api/v1/calendar/nonce`;
  let csrfToken = "";
  try {
    const nonceResponse = await fetch(nonceUrl, { method: 'GET', credentials: 'same-origin' });
    const nonceData = await nonceResponse.json();
    csrfToken = nonceData.data?.nonce || nonceData.nonce || nonceData.message?.nonce || '';
    if (!csrfToken) {
      PC.showToast(MSG_CAL_NONCE_SAVE);
      return;
    }
  } catch (error) {
    PC.showToast(MSG_CAL_NONCE_FETCH);
    return;
  }

  // Send all entries in one request
  const form_data = new FormData();
  form_data.append('entries', JSON.stringify(savedEntries));
  form_data.append('d', day_id);
  form_data.append('cal_work_save_as_default', save_as_default);
  form_data.append('csrf_token', csrfToken);

  const response = await PC.updateResource('calendar', form_data);

  if (totalHours > 24) {
    PC.showToast(MSG_CAL_TOTAL_HOURS);
    return;
  }

  const updatedWeek = await updateCalendarWeek(response?.week);
  if (!updatedWeek) {
    const day = PC.getElement(day_id);
    await renderDayEntries(day, savedEntries);
  }

  PC.closeModal('modal_cal_work', 'Work Details');
  PC.showToast(PC.getElement('cal_work_title').getAttribute('name') + ' work updated.');
}

const create_work_row = (work_div, index = 0) => {
  const wrapper = document.createElement("div");
  wrapper.classList.add("work_row", "flex", "mar_sm");

  const workData = JSON.parse(work_div.dataset.work || '{}');
  const site_id = workData.site_id || workData.s || '';

  let template_site_select = document.importNode(PC.getElement("template_site_select").content, true);
  let selectElement = template_site_select.querySelector("select");

  selectElement = set_select_value(selectElement, PC.state.default_site_id);
  selectElement.setAttribute("name", "site_id");
  selectElement.className = "list_item pad_sm w100";

  Array.from(selectElement.options).forEach(option => {
    if (option.value === site_id) {
      option.selected = true;
    }
  });

  selectElement.addEventListener("change", (event) => {
    const selectedOption = event.target.options[event.target.selectedIndex];
    const optionText = selectedOption.text;
    if (PC.state.audio_feedback === "all" && optionText) {
      PC.textToSpeech(optionText);
    }
  });

  const span = document.createElement("span");
  span.classList.add("w100");

  const label = document.createElement("label");
  label.className = "list_item pad_sm w100";
  label.textContent = "Site";

  const br = document.createElement("br");

  span.appendChild(label);
  span.appendChild(br);
  span.appendChild(selectElement);
  wrapper.appendChild(span);

  append_site_fields(work_div, wrapper);

  const deleteBtn = document.createElement("button");
  deleteBtn.type = "button";
  deleteBtn.className = "btn btn_cancel mar_md";
  deleteBtn.textContent = "Delete";
  deleteBtn.addEventListener("click", () => {
    wrapper.remove();
    updateWorkTotalMessage();
  });

  const btnWrapper = document.createElement("span");
  const btnLabel = document.createElement("label");
  PC.setHTML(btnLabel, '&nbsp;');
  btnWrapper.appendChild(btnLabel);
  
  const btnBr = document.createElement("br");
  btnWrapper.appendChild(btnBr);
  
  btnWrapper.appendChild(deleteBtn);
  wrapper.appendChild(btnWrapper);

  PC.getElement('work-entries-container').appendChild(wrapper);
};

const append_site_fields = (source_div, target_div) => {
  const workData = JSON.parse(source_div.dataset.work || '{}');
  const site_fields = ['hours', 'living_out_allowance', 'travel_hours'];
  site_fields.forEach(field => {
    field = field.replace(' ', '');
    const placeholder = field.replace(/_/g, ' ').replace(/\b\w/g, char => char.toUpperCase()); // Title Case
    let field_value = workData[field] || '';
    if (field_value === '') {
      if (field === 'hours') field_value = PC.state.default_hours;
      else if (field === 'living_out_allowance') field_value = PC.state.default_living_out_allowance;
      else if (field === 'travel_hours') field_value = PC.state.default_travel_hours;
    }
    const input_element = document.createElement('input');
    input_element.className = 'list_item list_item_secondary pad_sm w100';
    input_element.name = field;
    input_element.type = 'number';
    input_element.inputMode = 'decimal';
    input_element.placeholder = placeholder;
    input_element.autofocus = !window.location.hash;
    input_element.tabIndex = 0;
    input_element.value = field_value;
    
    const span = document.createElement("span");
    span.classList.add('w100');

    const label = document.createElement("label");
    label.className = 'list_item pad_sm w100';
    label.textContent = placeholder;

    const br = document.createElement("br");

    span.appendChild(label);
    span.appendChild(br);
    span.appendChild(input_element);
    target_div.appendChild(span);
    
    if (PC.state.audio_feedback == 'all' && input_element.value) PC.textToSpeech(input_element.value);
    
    input_element.addEventListener('focus', (event) => {
      const inputValue = event.target.value;
      if (PC.state.audio_feedback == 'all' && inputValue) PC.textToSpeech(inputValue);
    });

    if (field === 'hours') {
      input_element.addEventListener('input', () => updateWorkTotalMessage());
    }
  });
}

/**
 * Sets the selected value of a <select> element and applies standard styling.
 * @function set_select_value
 * @param HTMLSelectElement el           <select> element to update
 * @param string value                   Value to set as selected
 * @returns HTMLSelectElement            Updated <select> element
 */
const set_select_value = (el, value) => {
  if (!el) return null;

  const options = el.options;
  for (let i = 0; i < options.length; i++) {
    if (options[i].value === value) {
      el.selectedIndex = i;
      break;
    }
  }

  if (el.selectedIndex === -1 && options.length > 0) {
    el.selectedIndex = 0; // Select first option if no match
  }

  el.className = "list_item pad_sm";
  return el;
};

/*
 * Shows the work details modal for a selected calendar day, supporting multiple work entries.
 * Displays the modal, populates it with existing entries from data-work-entries, and allows adding/editing/deleting multiple entries.
 * The modal includes the date, converted to human-readable format.
 * @param HTMLElement el                 HTML element representing the selected calendar day.
 * @returns void
 */
const show_modal_cal_work = (el) => {
  PC.state.active_day_id = el.id;
  PC.getElement('cal_work_date').value = el.id;
  PC.getElement('cal_work_date').readOnly = true;
  PC.getElement('work-entries-container').textContent = '';
  const work_entries_container = PC.getElement('work-entries-container');
  const entries = JSON.parse(el.dataset.workEntries || '[]');

  if (PC.state.default_save === true)
    PC.getElement('cal_work_save_as_default_btn').checked = true;
  else
    PC.getElement('cal_work_save_as_default_btn').checked = false;

  if (entries.length === 0) {
    const new_work_div = document.createElement("div");
    const new_site_id = PC.generateSiteUUID();

    new_work_div.dataset.work = JSON.stringify({site_id: new_site_id});
    create_work_row(new_work_div, 0);
  } else {
    entries.forEach((entry, index) => {
      const work_div = document.createElement("div");
      work_div.dataset.work = JSON.stringify(entry);
      create_work_row(work_div, index);
    });
  }

  updateWorkTotalMessage();

  const readable_date = PC.formatReadableDate(el.id);
  PC.setHTML(PC.getElement('cal_work_title'), readable_date + ' ' + PC.toTitleCase(<?php echo json_encode($i18n['I_WORK_DETAILS']); ?>));
  PC.getElement('cal_work_title').setAttribute('name', readable_date);
  PC.getElement('cal_work_title').setAttribute('aria-label', readable_date);
  PC.getElement('modal_cal_work').setAttribute('aria-label', readable_date + ' ' + PC.toTitleCase(<?php echo json_encode($i18n['I_WORK_DETAILS']); ?>));
  PC.openModal('modal_cal_work', PC.toTitleCase(readable_date + ' ' + <?php echo json_encode($i18n['I_WORK_DETAILS']); ?>));
  PC.state.modal_is_active = true;
}

/* Add ability to hit Enter to trigger form submission */
PC.getElement('cal_work_form').addEventListener('keydown', (e) => {
  if (e.keyCode == 13) {
    e.stopPropagation();
    save_work(e);
    PC.closeModal('modal_cal_work');
  }
});

/* Play Audio Event listeners for each calendar day. Useful when navigating by keyboard. */
const cal_days = PC.queryAll('.calendar_day[aria-label]');
cal_days.forEach(div => {
  div.addEventListener('focus', (event) => {
    const label = div.getAttribute('aria-label');
    if (PC.state.audio_feedback == 'all' && label) {
      PC.textToSpeech(label);
    }
  });
});

/* Additional Calendar page specific shortcuts */
document.addEventListener('keydown', (e) => {

  // Return early if we're in any modal
  if (PC.state.modal_is_active) {
    return;
  }

  // Bare backslash: Open Date Picker
  if (!e.ctrlKey && !e.metaKey && !e.altKey && !e.shiftKey && !e.repeat && e.code === 'Backslash') {
    if (e.target instanceof Element && e.target.closest('input, textarea, select, [contenteditable]')) return;
    e.preventDefault();
    PC.getElement('cal_picker_button').click();
    return;
  }

  // Bare slash: Toggle Calendar screen mode
  const isCalendarSwitcherShortcut = !e.ctrlKey && !e.metaKey && !e.altKey && !e.shiftKey && !e.repeat && e.code === 'Slash';

  if (isCalendarSwitcherShortcut) {
    e.preventDefault();
    switch (PC.state.calendar_screen) {
      case 'normal':
        PC.getElement('cal_date_nav').classList.add('hidden');
        PC.getElement('cal_week_header').classList.add('hidden');
        PC.getElement('page_footer').classList.add('hidden');
        PC.state.calendar_screen = 'no_sub_headers';
        break;
      case 'no_sub_headers':
        PC.getElement('page_header').classList.add('hidden');
        PC.state.calendar_screen = 'no_nav';
        break;
      case 'no_nav':
        PC.queryAll('.day_number').forEach((el) => el.classList.add('hidden'));
        PC.state.calendar_screen = 'no_number';
        break;
      case 'no_number':
        PC.getElement('page_header').classList.remove('hidden');
        PC.getElement('page_footer').classList.remove('hidden');
        PC.getElement('cal_date_nav').classList.remove('hidden');
        PC.getElement('cal_week_header').classList.remove('hidden');
        PC.queryAll('.day_number').forEach((el) => el.classList.remove('hidden'));
        PC.state.calendar_screen = 'normal';
        break;
    }
  }
});

function addOnChangeListener(selectId, callback) {
  const selectElement = PC.getElement(selectId);

  if (selectElement) {
    selectElement.addEventListener('change', function(event) {
      callback(event.target.value);
    });
  } else {
    PW.error(`Select element with id '${selectId}' not found`);
  }
}


