<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

require_once '../../config.php';

Authentication::abortIfUnauthenticated();
Authentication::isAdminOrDie();

$i18nKeys = [
  'ADMIN_LIMITS_RESET_ERROR',
  'ADMIN_LIMITS_RESET_CATEGORY_ERROR',
  'INFO_UPDATED',
  'EMAIL',
  'ACTIVE',
  'INACTIVE',
  'ADMIN_USER_AUTH_LEVEL',
  'ADMIN_USER_VERIFIED',
  'ADMIN_USER_UNVERIFIED',
  'ADMIN_USER_UNKNOWN',
];
$i18n = [];
foreach ($i18nKeys as $i18nKey) {
  $i18n[$i18nKey] = Strings::i18n($i18nKey);
}

CORS::handleORIGIN();

CORS::renderContentType('application/javascript');
Javascript::renderDocBlock();

?>

import PC from "<?php echo Environment::appURL('js/'); ?>";
import PW from "<?php echo Environment::appURL('js/phantomwing/'); ?>";

const MSG_RESET_LIMIT_ERROR = <?php echo json_encode($i18n['ADMIN_LIMITS_RESET_ERROR']); ?>;
const MSG_RESET_CATEGORY_LIMITS_ERROR = <?php echo json_encode($i18n['ADMIN_LIMITS_RESET_CATEGORY_ERROR']); ?>;
const MSG_INFO_UPDATED = <?php echo json_encode($i18n['INFO_UPDATED']); ?>;
const MSG_ADMIN_USER_UNKNOWN = <?php echo json_encode($i18n['ADMIN_USER_UNKNOWN']); ?>;

function formatAccountStateFlags(rawFlags) {
  const fallback = MSG_ADMIN_USER_UNKNOWN || 'Unknown';
  const source = String(rawFlags || '').trim();
  if (source === '') {
    return fallback;
  }

  const labels = {
    email_verified: <?php echo json_encode($i18n['EMAIL']); ?> + ' ' + <?php echo json_encode($i18n['ADMIN_USER_VERIFIED']); ?>,
    webauthn_enabled: 'WebAuthn',
    auth_level: <?php echo json_encode($i18n['ADMIN_USER_AUTH_LEVEL']); ?>,
  };

  const truthyMap = {
    yes: <?php echo json_encode($i18n['ADMIN_USER_VERIFIED']); ?>,
    no: <?php echo json_encode($i18n['ADMIN_USER_UNVERIFIED']); ?>,
    true: <?php echo json_encode($i18n['ACTIVE']); ?>,
    false: <?php echo json_encode($i18n['INACTIVE']); ?>,
    '1': <?php echo json_encode($i18n['ACTIVE']); ?>,
    '0': <?php echo json_encode($i18n['INACTIVE']); ?>,
  };

  const parts = source
    .split(',')
    .map((token) => token.trim())
    .filter((token) => token !== '');

  if (parts.length === 0) {
    return fallback;
  }

  const formatted = parts.map((part) => {
    const [rawKey, ...valueChunks] = part.split('=');
    const key = String(rawKey || '').trim();
    const rawValue = valueChunks.join('=').trim();
    if (key === '') {
      return null;
    }

    const normalizedValue = rawValue.toLowerCase();
    const mappedValue = truthyMap[normalizedValue] || rawValue || fallback;
    const label = labels[key] || key.replace(/_/g, ' ');
    return `${label}: ${mappedValue}`;
  }).filter(Boolean);

  return formatted.length > 0 ? formatted.join(' | ') : fallback;
}

async function getCapabilityToken(action) {
  const response = await fetch(`${PC.config.pc_api}/admin/capability/${encodeURIComponent(action)}`, {
    method: 'GET',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
    },
  });

  const payload = await response.json();
  if (!response.ok || payload.status !== 'success') {
    throw new Error(payload.message || 'Capability token request failed.');
  }

  const token = String(payload.capability?.token || '').trim();
  if (!token) {
    throw new Error('Capability token missing.');
  }

  return token;
}

function formatUtcOffsetForDate(dateObj) {
  const offsetMinutes = -dateObj.getTimezoneOffset();
  const sign = offsetMinutes >= 0 ? "+" : "-";
  const absolute = Math.abs(offsetMinutes);
  const hh = String(Math.floor(absolute / 60)).padStart(2, "0");
  const mm = String(absolute % 60).padStart(2, "0");
  return `UTC${sign}${hh}:${mm}`;
}

function parseTimestampValue(rawValue) {
  if (!rawValue && rawValue !== 0) {
    return null;
  }

  const trimmed = String(rawValue).trim();
  if (trimmed === "") {
    return null;
  }

  if (/^\d+$/.test(trimmed)) {
    const n = Number(trimmed);
    if (!Number.isFinite(n) || n <= 0) {
      return null;
    }

    // Treat values >= 1e12 as ms epoch, otherwise seconds epoch.
    const ms = n >= 1e12 ? n : (n * 1000);
    const fromEpoch = new Date(ms);
    return Number.isNaN(fromEpoch.getTime()) ? null : fromEpoch;
  }

  const parsed = new Date(trimmed);
  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

function formatUnixTimestamp(rawValue, emptyFallback = "Unknown") {
  const asDate = parseTimestampValue(rawValue);
  if (!asDate) {
    return emptyFallback;
  }

  const viewerTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || "Local";
  const offset = formatUtcOffsetForDate(asDate);
  return `${asDate.toLocaleString()} (${viewerTimeZone}, ${offset}, your time)`;
}

function setTextById(id, value, fallback = "Unknown") {
  const node = PC.getElement(id);
  if (!node) return;
  node.textContent = (value && String(value).trim() !== "") ? String(value) : fallback;
}

function hideDeleteConfirmPill() {
  const confirmPill = PC.getElement("delete_user_confirm_pill");
  if (confirmPill) confirmPill.classList.add("hidden");

  const deleteTriggerBtn = PC.getElement("edit_user_delete_trigger");
  if (deleteTriggerBtn) deleteTriggerBtn.classList.remove("hidden");
}

function showDeleteConfirmPill() {
  const confirmPill = PC.getElement("delete_user_confirm_pill");
  if (confirmPill) confirmPill.classList.remove("hidden");

  const deleteTriggerBtn = PC.getElement("edit_user_delete_trigger");
  if (deleteTriggerBtn) deleteTriggerBtn.classList.add("hidden");
}

function editUser(button) {
  const uuid = button.dataset.uuid;
  const fullName = button.dataset.fullName;
  const email = button.dataset.email;
  const authLevel = button.dataset.authLevel;
  const phone = button.dataset.phone;
  const registeredAt = button.dataset.registeredAt;
  const registeredIp = button.dataset.registeredIp;
  const lastLoginAt = button.dataset.lastLoginAt;
  const lastLoginIp = button.dataset.lastLoginIp;
  const lastSessionAt = button.dataset.lastSessionAt;
  const lastSessionHash = button.dataset.lastSessionHash;
  const lastAuthMethod = button.dataset.lastAuthMethod;
  const credentialCount = button.dataset.credentialCount;
  const lastPasskeyUsedAt = button.dataset.lastPasskeyUsedAt;
  const accountStateFlags = button.dataset.accountStateFlags;

  const uuidInput = PC.getElement("edit_user_uuid");
  if (uuidInput) uuidInput.value = uuid;

  const fullNameInput = PC.getElement("edit_full_name");
  if (fullNameInput) fullNameInput.value = fullName;

  const emailInput = PC.getElement("edit_email");
  if (emailInput) emailInput.value = email;

  const authLevelSelect = PC.getElement("edit_auth_level");
  if (authLevelSelect) authLevelSelect.value = authLevel;

  const phoneInput = PC.getElement("edit_phone");
  if (phoneInput) phoneInput.value = phone;

  setTextById("edit_registered_at", formatUnixTimestamp(registeredAt, "Unknown"), "Unknown");
  setTextById("edit_registered_ip", registeredIp, "Unknown");
  setTextById("edit_last_login_at", formatUnixTimestamp(lastLoginAt, "No login on record"), "No login on record");
  setTextById("edit_last_login_ip", lastLoginIp, "Unknown");
  setTextById("edit_last_session_at", formatUnixTimestamp(lastSessionAt, "No session on record"), "No session on record");
  setTextById("edit_last_session_ip", lastLoginIp, "Unknown");
  setTextById("edit_last_session_hash", lastSessionHash, "Unknown");
  setTextById("edit_last_auth_method", lastAuthMethod, "Unknown");
  setTextById("edit_credential_count", credentialCount, "0");
  setTextById("edit_last_passkey_used_at", formatUnixTimestamp(lastPasskeyUsedAt, "Unknown"), "Unknown");
  setTextById("edit_account_state_flags", formatAccountStateFlags(accountStateFlags), MSG_ADMIN_USER_UNKNOWN || "Unknown");

  hideDeleteConfirmPill();

  PC.openModal("modal_edit_user");
}

function closeEditDialog() {
  hideDeleteConfirmPill();
  PC.closeModal("modal_edit_user");
}

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("#user_list .btn_edit").forEach(button => {
    button.addEventListener("click", () => editUser(button));
  });

  const editUserDialog = PC.getElement("modal_edit_user");
  if (editUserDialog) {
    // Ensure destructive-action confirmation state is reset for any close path.
    editUserDialog.addEventListener("close", hideDeleteConfirmPill);
  }

  const deleteTriggerBtn = PC.getElement("edit_user_delete_trigger");
  if (deleteTriggerBtn) {
    deleteTriggerBtn.addEventListener("click", () => {
      showDeleteConfirmPill();
    });
  }

  const deleteNoBtn = PC.getElement("edit_user_delete_no");
  if (deleteNoBtn) {
    deleteNoBtn.addEventListener("click", () => {
      hideDeleteConfirmPill();
    });
  }

  const deleteYesBtn = PC.getElement("edit_user_delete_yes");
  if (deleteYesBtn) {
    deleteYesBtn.addEventListener("click", async () => {
      const userUuidInput = PC.getElement("edit_user_uuid");
      const userUUID = userUuidInput ? userUuidInput.value : "";

      if (!userUUID) {
        alert("Missing user UUID. Close and reopen Edit User.");
        return;
      }

      deleteYesBtn.disabled = true;
      try {
        const capabilityToken = await getCapabilityToken('admin.user.delete');
        const response = await fetch(`${PC.config.pc_api}/admin/user/delete`, {
          method: "POST",
          credentials: "include",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded",
            "X-Requested-With": "XMLHttpRequest",
            "X-PayCal-Capability": capabilityToken,
          },
          body: new URLSearchParams({ user_uuid: userUUID, capability_token: capabilityToken })
        });

        const data = await response.json();
        if (data.success) {
          alert("User deleted successfully.");
          closeEditDialog();
          location.reload();
          return;
        }

        alert(data.message || "Delete failed.");
      } catch (error) {
        PW.error(`Error deleting user: ${error.message}`);
        alert("Error deleting user: " + error.message);
      } finally {
        deleteYesBtn.disabled = false;
      }
    });
  }

  const form = PC.getElement("edit_user_form");
  if (form) form.addEventListener("submit", function(e) {
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);
    const updateEndpoint = `${PC.config.pc_api}/admin/user/update`;
    getCapabilityToken('admin.user.update').then((capabilityToken) => {
      formData.set('capability_token', capabilityToken);
      return fetch(updateEndpoint, {
      method: "POST",
      credentials: "include",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "X-PayCal-Capability": capabilityToken,
      },
      body: formData
    });
    })
    .then(response => {
      if (!response.ok) {
        throw new Error("Network response was not ok");
      }
      return response.json();
    })
    .then(data => {
      if (data.success) {
        PC.showToast(data.message || 'User updated successfully.', 'save', 3000, true);
        closeEditDialog();
        // Optionally, refresh the user list or page
        location.reload(); // Refresh to see changes
      } else {
        PC.showToast(data.message || 'Update failed.', 'error', 5000, true);
      }
    })
    .catch(error => {
      PW.error(`Error updating user: ${error.message}`);
      PC.showToast(`Error updating user: ${error.message}`, 'error', 5000, true);
    });
  });

// Language Editor
let currentLang = '<?php echo \PayCal\Domain\Language::DEFAULT; ?>';

  // Load content when tab changes
  const tabBtns = document.querySelectorAll('.lang-editor__tab-btn');
  if (tabBtns.length > 0) {
    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        tabBtns.forEach(b => b.setAttribute('aria-selected', 'false'));
        btn.setAttribute('aria-selected', 'true');
        const lang = btn.dataset.lang;
        currentLang = lang;
        loadLangContent(lang);
      });
    });

    // Load initial content for active tab
    const activeBtn = document.querySelector('.lang-editor__tab-btn[aria-selected="true"]');
    if (activeBtn) {
      currentLang = activeBtn.dataset.lang;
      loadLangContent(currentLang);
    }
  }

  // Save button
  const saveBtn = PC.getElement('save_btn');
  if (saveBtn) saveBtn.addEventListener('click', () => saveLang(currentLang));

  // Update invite code button
  const updateInviteBtn = PC.getElement('update_invite_code');
  if (updateInviteBtn) updateInviteBtn.addEventListener('click', () => updateInviteCode());

  // Testing tools - Create orphaned work button
  const btnCreateOrphanedWork = PC.getElement('btn_create_orphaned_work');
  if (btnCreateOrphanedWork) {
    btnCreateOrphanedWork.addEventListener('click', async () => {
      const resultSpan = PC.getElement('orphaned_work_result');
      const btn = btnCreateOrphanedWork;
      
      btn.disabled = true;
      btn.textContent = 'Creating...';
      if (resultSpan) resultSpan.textContent = '';
      
      try {
        const capabilityToken = await getCapabilityToken('admin.testing.create-orphaned-work');
        const response = await fetch(`${PC.config.pc_api}/admin/testing/create-orphaned-work`, {
          method: 'POST',
          credentials: 'include',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-PayCal-Capability': capabilityToken,
          },
          body: new URLSearchParams({ capability_token: capabilityToken }),
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
          const result = data.data || data;
          if (resultSpan) {
            resultSpan.textContent = `✓ Created ${result.dates?.length || 5} orphaned entries (Site ID: ${result.orphaned_site_id || 'unknown'})`;
            resultSpan.classList.remove('error');
            resultSpan.classList.add('success');
          }
        } else {
          if (resultSpan) {
            resultSpan.textContent = `✗ ${data.message || 'Failed to create orphaned work'}`;
            resultSpan.classList.remove('success');
            resultSpan.classList.add('error');
          }
        }
      } catch (error) {
        PW.error(`Error creating orphaned work: ${error.message}`);
        if (resultSpan) {
          resultSpan.textContent = '✗ Error creating orphaned work';
          resultSpan.classList.remove('success');
          resultSpan.classList.add('error');
        }
      } finally {
        btn.disabled = false;
        btn.textContent = 'Generate Test Orphaned Work';
      }
    });
  }

  // System Limits handlers
  setupSystemLimitsHandlers();
});

  // ==================== System Audit Event Stream ====================

  /**
   * Connects a real WebSocket to exact /ws and renders immutable audit events
   * into #audit_event_feed on the admin dashboard.
   *
   * Purpose: replace the prior SSE transport with a true WebSocket while
   * preserving the legacy /ws/ HTTP channels used elsewhere in the app.
   */
  function initAuditEventStream() {
    const feedEl = document.getElementById('audit_event_feed');
    const statusEl = document.getElementById('audit_stream_status');
    const countEl = document.getElementById('audit_event_count');
    const reconnectBtn = document.getElementById('btn_audit_stream_reconnect');
    if (!feedEl) return;

    let eventCount = 0;
    let socket = null;
    let reconnectTimer = null;
    let manualReconnect = false;

    function websocketURL() {
      const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
      return `${protocol}//${window.location.host}/ws`;
    }

    function renderAuditEvent(data) {
      eventCount++;
      if (countEl) countEl.textContent = `(${eventCount})`;

      const ts = data.created_at ? new Date(data.created_at).toLocaleTimeString() : '';
      const seq = data.ledger_sequence ? `#${data.ledger_sequence}` : '';
      const actor = String(data.actor_uuid || '').slice(0, 8);
      const type = String(data.event_type || 'unknown');

      const li = document.createElement('li');
      li.className = 'audit-event-item';
      li.textContent = [ts, seq, type, actor ? `actor:${actor}…` : ''].filter(Boolean).join('  ');
      feedEl.prepend(li);

      while (feedEl.children.length > 100) {
        feedEl.removeChild(feedEl.lastChild);
      }
    }

    function scheduleReconnect() {
      if (manualReconnect) {
        manualReconnect = false;
        return;
      }

      if (reconnectTimer !== null) {
        window.clearTimeout(reconnectTimer);
      }

      reconnectTimer = window.setTimeout(() => {
        connect();
      }, 2000);
    }

    function connect() {
      if (reconnectTimer !== null) {
        window.clearTimeout(reconnectTimer);
        reconnectTimer = null;
      }

      if (socket) {
        socket.close();
      }

      if (statusEl) statusEl.textContent = ' — Connecting…';

      socket = new WebSocket(websocketURL());

      socket.addEventListener('open', () => {
        if (statusEl) statusEl.textContent = ' — Connected';
        socket.send(JSON.stringify({ action: 'subscribe', channel: 'system_audit' }));
      });

      socket.addEventListener('message', (message) => {
        let payload;
        try {
          payload = JSON.parse(message.data);
        } catch {
          return;
        }

        if (payload.type === 'audit_snapshot' && Array.isArray(payload.events)) {
          feedEl.textContent = '';
          eventCount = 0;
          payload.events.slice().reverse().forEach((event) => renderAuditEvent(event));
          return;
        }

        if (payload.type === 'audit_event' && payload.event) {
          renderAuditEvent(payload.event);
          return;
        }

        if (payload.type === 'error' && statusEl) {
          statusEl.textContent = ` — ${String(payload.message || 'Connection error')}`;
        }
      });

      socket.addEventListener('close', () => {
        if (statusEl) statusEl.textContent = ' — Reconnecting…';
        scheduleReconnect();
      });

      socket.addEventListener('error', () => {
        if (statusEl) statusEl.textContent = ' — Reconnecting…';
      });
    }

    connect();

    if (reconnectBtn) {
      reconnectBtn.addEventListener('click', () => {
        manualReconnect = true;
        eventCount = 0;
        if (countEl) countEl.textContent = '(0)';
        if (feedEl) feedEl.textContent = '';
        if (statusEl) statusEl.textContent = ' — Connecting…';
        connect();
      });
    }
  }

  document.addEventListener('DOMContentLoaded', initAuditEventStream);

function loadLangContent(lang) {
  fetch(`<?php echo Environment::appURL('admin/languages.php'); ?>?lang=${lang}`)
    .then(async (response) => {
      const raw = await response.text();
      let data;

      try {
        data = JSON.parse(raw);
      } catch (error) {
        throw new Error(`Language response was not valid JSON: ${raw.slice(0, 200)}`);
      }

      if (!response.ok) {
        throw new Error(data.error || data.message || `Language request failed (${response.status})`);
      }

      return data;
    })
    .then(data => {
      if (data.success) {
        const languageDirectory = (PC && PC.config && PC.config.languages && typeof PC.config.languages === 'object')
          ? PC.config.languages
          : {};
        const languageName = String(languageDirectory[lang] || lang || '').trim() || 'Unknown';
        PC.getElement('lang_title').textContent = `${languageName} Language Editor`;
        PC.getElement('language_textarea').value = data.content;
      } else {
        PC.showToast("Error loading language file: " + data.error);
      }
    })
    .catch(error => {
      PW.error(`Error loading language content: ${error.message}`);
      PC.showToast("Error loading content: " + error.message);
    });
}

function saveLang(lang) {
  const content = PC.getElement('language_textarea').value;
  const formData = new FormData();
  formData.append('lang', lang);
  formData.append('content', content);

  getCapabilityToken('admin.languages.update').then((capabilityToken) => {
    formData.set('capability_token', capabilityToken);
    return fetch(`${PC.config.pc_api}/admin/languages/update`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-PayCal-Capability': capabilityToken,
      },
      body: formData,
    });
  }).then((response) => {
    if (!response.ok) {
      throw new Error('Language update request failed.');
    }
    return response.json();
  }).then((data) => {
    if (data.status !== 'success') {
      throw new Error(data.message || 'Language update failed.');
    }
    PC.showToast(MSG_INFO_UPDATED);
  }).catch(error => {
    PC.showToast("Error saving language file: " + error.message);
  });
}

function updateInviteCode() {
  const inviteCode = PC.getElement('invite_code').value;
  const formData = new FormData();
  formData.append('invite_code', inviteCode);

  getCapabilityToken('admin.settings.update-invite').then((capabilityToken) => {
    formData.set('capability_token', capabilityToken);
    return fetch(`${PC.config.pc_api}/admin/settings/update-invite`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-PayCal-Capability': capabilityToken,
      },
      body: formData,
    });
  }).then((response) => {
    if (!response.ok) {
      throw new Error('Invite code update request failed.');
    }
    return response.json();
  }).then((data) => {
    if (data.status !== 'success') {
      throw new Error(data.message || 'Invite code update failed.');
    }
    PC.showToast("Invite code updated successfully!");
  }).catch(error => {
    PC.showToast("Error updating invite code: " + error.message);
  });
}

// ==================== System Limits ====================

function setupSystemLimitsHandlers() {
  // Individual reset buttons
  document.querySelectorAll('.limit_reset').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const key = e.target.dataset.key;
      if (confirm('Reset this limit to default?')) {
        await resetLimit(key);
      }
    });
  });

  // Category reset all buttons
  document.querySelectorAll('.category_reset_all').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const category = e.target.dataset.category;
      const categoryLabel = e.target.closest('.admin_panel[data-category]').querySelector('.admin_panel_title').textContent;
      if (confirm(`Reset all ${categoryLabel} limits to defaults?`)) {
        await resetCategoryLimits(category);
      }
    });
  });

  // Auto-save on change for all inputs
  document.querySelectorAll('.limit_value').forEach(input => {
    input.addEventListener('change', async (e) => {
      const key = e.target.dataset.key;
      await saveLimit(key);
    });
  });
}

async function saveLimit(key) {
  const input = document.querySelector(`.limit_value[data-key="${key}"]`);
  if (!input) return;

  let value;
  if (input.type === 'checkbox') {
    value = input.checked;
  } else if (input.tagName === 'SELECT') {
    value = input.value;
  } else {
    value = input.value;
  }

  // Disable input during save
  input.disabled = true;

  try {
    const capabilityToken = await getCapabilityToken('admin.limits.update');
    const response = await fetch(`${PC.config.pc_api}/admin/limits/update`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-PayCal-Capability': capabilityToken,
      },
      body: new URLSearchParams({ key, value: String(value), capability_token: capabilityToken })
    });

    const data = await response.json();
    
    if (data.status === 'success') {
      PC.showToast(data.message || 'Limit updated successfully');
      // Reload the section to show updated UI (including reset button if needed)
      await reloadSystemLimitsUI();
    } else {
      PC.showToast('Error: ' + (data.message || 'Failed to update limit'));
    }
  } catch (error) {
    PW.error(`${MSG_RESET_LIMIT_ERROR}: ${error.message}`);
    PC.showToast(MSG_RESET_LIMIT_ERROR);
  } finally {
    input.disabled = false;
  }
}

async function resetLimit(key) {
  const resetBtn = document.querySelector(`.limit_reset[data-key="${key}"]`);
  if (resetBtn) resetBtn.disabled = true;

  try {
    const capabilityToken = await getCapabilityToken('admin.limits.reset');
    const response = await fetch(`${PC.config.pc_api}/admin/limits/reset`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-PayCal-Capability': capabilityToken,
      },
      body: new URLSearchParams({ key, capability_token: capabilityToken })
    });

    const data = await response.json();
    
    if (data.status === 'success') {
      PC.showToast('Limit reset to default');
      await reloadSystemLimitsUI();
    } else {
      PC.showToast('Error: ' + (data.message || 'Failed to reset limit'));
    }
  } catch (error) {
    PW.error(`Error resetting limit: ${error.message}`);
    PC.showToast(MSG_RESET_LIMIT_ERROR);
  } finally {
    if (resetBtn) resetBtn.disabled = false;
  }
}

async function resetCategoryLimits(category) {
  const resetBtn = document.querySelector(`.category_reset_all[data-category="${category}"]`);
  if (resetBtn) resetBtn.disabled = true;

  try {
    // Get all limit keys for this category
    const panel = document.querySelector(`.admin_panel[data-category="${category}"]`);
    const limitRows = panel.querySelectorAll('.admin_row');
    const keys = Array.from(limitRows).map(row => row.dataset.limitKey);

    // Reset each limit in the category
    for (const key of keys) {
      const capabilityToken = await getCapabilityToken('admin.limits.reset');
      await fetch(`${PC.config.pc_api}/admin/limits/reset`, {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'X-PayCal-Capability': capabilityToken,
        },
        body: new URLSearchParams({ key, capability_token: capabilityToken })
      });
    }

    PC.showToast('Category limits reset to defaults');
    await reloadSystemLimitsUI();
  } catch (error) {
    PW.error(`Error resetting category limits: ${error.message}`);
    PC.showToast(MSG_RESET_CATEGORY_LIMITS_ERROR);
  } finally {
    if (resetBtn) resetBtn.disabled = false;
  }
}

async function reloadSystemLimitsUI() {
  // Reload the page to get fresh HTML with updated values and buttons
  location.reload();
}
