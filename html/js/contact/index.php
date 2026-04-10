<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once '../../config.php';

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');

Javascript::renderDocBlock();

?>

const form = document.getElementById('contact_form');
const statusEl = document.getElementById('contact_status');
const submitBtn = document.getElementById('submit');
const messageEl = document.getElementById('message');
const messageCountEl = document.getElementById('message_count');
const successCard = document.getElementById('contact_success_card');
const sendAnotherBtn = document.getElementById('contact_send_another');
const sentTimeEl = document.getElementById('contact_sent_time');
const cooldownHintEl = document.getElementById('contact_cooldown_hint');
const successCooldownEl = document.getElementById('contact_success_cooldown');
const formTokenInput = form ? form.querySelector('input[name="contact_form_token"]') : null;

const FIELD_IDS = ['name', 'email', 'reason', 'subject', 'message'];

let formLoadTime = Date.now();
let cooldownTimerId = null;

const cooldownDurationSeconds = form
  ? Math.max(0, Number.parseInt(form.dataset.cooldownDuration || '300', 10) || 300)
  : 300;
let cooldownRemainingSeconds = form
  ? Math.max(0, Number.parseInt(form.dataset.cooldownRemaining || '0', 10) || 0)
  : 0;
const submitBaseLabel = submitBtn?.dataset.baseLabel || 'Send Email';

function formatCooldownTime(totalSeconds) {
  const safeSeconds = Math.max(0, totalSeconds);
  const minutes = Math.floor(safeSeconds / 60);
  const seconds = safeSeconds % 60;
  return `${minutes}:${String(seconds).padStart(2, '0')}`;
}

function clearCooldownTimer() {
  if (!cooldownTimerId) return;
  window.clearInterval(cooldownTimerId);
  cooldownTimerId = null;
}

function refreshFormToken(nextToken) {
  if (!formTokenInput) return;
  if (typeof nextToken !== 'string' || nextToken.length === 0) return;
  formTokenInput.value = nextToken;
}

function renderCooldownState() {
  const isCoolingDown = cooldownRemainingSeconds > 0;
  const countdownText = formatCooldownTime(cooldownRemainingSeconds);

  if (submitBtn) {
    submitBtn.disabled = isCoolingDown;
    submitBtn.setAttribute('aria-disabled', isCoolingDown ? 'true' : 'false');
    submitBtn.classList.toggle('contact-send-button--cooldown', isCoolingDown);
    submitBtn.textContent = isCoolingDown
      ? `${submitBaseLabel} (${countdownText})`
      : submitBaseLabel;
  }

  if (cooldownHintEl) {
    if (isCoolingDown) {
      cooldownHintEl.hidden = false;
      cooldownHintEl.textContent = `Please wait ${countdownText} before sending another message.`;
    } else {
      cooldownHintEl.hidden = true;
      cooldownHintEl.textContent = '';
    }
  }

  if (successCooldownEl) {
    if (isCoolingDown) {
      successCooldownEl.hidden = false;
      successCooldownEl.textContent = `Send another available in ${countdownText}.`;
    } else {
      successCooldownEl.hidden = true;
      successCooldownEl.textContent = '';
    }
  }
}

function startCooldown(seconds) {
  cooldownRemainingSeconds = Math.max(0, Number.parseInt(String(seconds), 10) || 0);
  clearCooldownTimer();
  renderCooldownState();

  if (form) {
    form.dataset.cooldownRemaining = String(cooldownRemainingSeconds);
  }

  if (cooldownRemainingSeconds <= 0) {
    return;
  }

  cooldownTimerId = window.setInterval(() => {
    cooldownRemainingSeconds = Math.max(0, cooldownRemainingSeconds - 1);
    if (form) {
      form.dataset.cooldownRemaining = String(cooldownRemainingSeconds);
    }
    renderCooldownState();

    if (cooldownRemainingSeconds <= 0) {
      clearCooldownTimer();
      setStatus('You can send another message now.', 'info');
    }
  }, 1000);
}

// Analytics event tracking
function trackEvent(eventName, eventData = {}) {
  try {
    // Send analytics beacon to server
    const payload = {
      event: eventName,
      timestamp: Date.now(),
      ...eventData,
    };
    
    if (navigator.sendBeacon) {
      navigator.sendBeacon(
        '/api/analytics',
        JSON.stringify(payload)
      );
    } else {
      // Fallback: use fetch with keepalive
      fetch('/api/analytics', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        keepalive: true,
      }).catch(() => {});
    }
  } catch (e) {
    // Silently fail - don't break form if analytics fails
  }
}

function focusInitialTarget() {
  if (!form) return;

  // Respect server-rendered validation errors first.
  const firstInvalidField = FIELD_IDS
    .map((fieldId) => document.getElementById(fieldId))
    .find((el) => el && el.getAttribute('aria-invalid') === 'true');

  if (firstInvalidField) {
    firstInvalidField.focus();
    return;
  }

  // If a non-info status is shown after a server post, announce it by focus.
  if (statusEl && !statusEl.classList.contains('contact-status--info')) {
    statusEl.focus();
    return;
  }

  const nameInput = document.getElementById('name');
  if (!nameInput) return;

  // Avoid stealing focus if something else is already focused.
  const active = document.activeElement;
  const canTakeFocus = !active || active === document.body;
  if (canTakeFocus) {
    nameInput.focus({preventScroll: true});
  }
}

function setStatus(message, tone = 'info') {
  if (!statusEl) return;
  statusEl.textContent = message;
  statusEl.classList.remove('contact-status--info', 'contact-status--success', 'contact-status--error');
  statusEl.classList.add(`contact-status--${tone}`);
  statusEl.setAttribute('role', tone === 'error' ? 'alert' : 'status');
  statusEl.setAttribute('aria-live', tone === 'error' ? 'assertive' : 'polite');
  statusEl.removeAttribute('hidden');  // Show the status box
}

function setFieldError(fieldId, message) {
  const field = document.getElementById(fieldId);
  const errorEl = document.getElementById(`${fieldId}_error`);
  if (!field || !errorEl) return;

  if (message) {
    field.setAttribute('aria-invalid', 'true');
    field.setAttribute('aria-describedby', `${fieldId}_error`);
    errorEl.textContent = message;
    errorEl.hidden = false;
    return;
  }

  field.removeAttribute('aria-invalid');
  if (field.hasAttribute('aria-describedby') && field.getAttribute('aria-describedby') === `${fieldId}_error`) {
    field.removeAttribute('aria-describedby');
  }
  errorEl.textContent = '';
  errorEl.hidden = true;
}

function clearAllFieldErrors() {
  FIELD_IDS.forEach((fieldId) => setFieldError(fieldId, ''));
}

function updateMessageCounter() {
  if (!messageEl || !messageCountEl) return;
  const currentLength = messageEl.value.length;
  messageCountEl.textContent = `${currentLength}/6000`;
  
  // Warn at ~75% (4500 chars)
  if (currentLength >= 4500) {
    messageCountEl.classList.add('contact-field-count--warn');
  } else {
    messageCountEl.classList.remove('contact-field-count--warn');
  }
}

function formatTime(date) {
  const options = {
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
    hour12: true,
  };
  return new Intl.DateTimeFormat('en-US', options).format(date);
}

function showSuccessCard() {
  if (!successCard || !sentTimeEl) return;
  
  successCard.hidden = false;
  sentTimeEl.textContent = formatTime(new Date());
  
  if (form) form.hidden = true;
  if (statusEl) statusEl.hidden = true;
  
  successCard.focus();
}

function resetForNewMessage() {
  if (form) {
    form.hidden = false;
    form.reset();
  }
  if (statusEl) statusEl.hidden = true;
  if (successCard) successCard.hidden = true;
  
  const nameInput = document.getElementById('name');
  if (nameInput) nameInput.focus();
  clearAllFieldErrors();
  
  // Reset form load time for next submission
  formLoadTime = Date.now();
  renderCooldownState();
}

if (form) {
  if (document.readyState === 'loading') {
    window.addEventListener('DOMContentLoaded', focusInitialTarget, {once: true});
  } else {
    focusInitialTarget();
  }

  // Track form focus
  form.addEventListener('focusin', () => {
    trackEvent('contact_form_focus');
  }, {once: true});

  // Initialize message counter on load
  if (messageEl) {
    updateMessageCounter();
    messageEl.addEventListener('input', updateMessageCounter);
    messageEl.addEventListener('change', updateMessageCounter);
  }

  startCooldown(cooldownRemainingSeconds);

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearAllFieldErrors();

    if (cooldownRemainingSeconds > 0) {
      setStatus(`Please wait ${formatCooldownTime(cooldownRemainingSeconds)} before sending another message.`, 'error');
      if (statusEl) statusEl.focus();
      return;
    }

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.setAttribute('aria-disabled', 'true');
      submitBtn.setAttribute('aria-busy', 'true');
    }
    setStatus('Sending your message...', 'info');

    try {
      const formData = new FormData(form);
      formData.set('pc_method', 'xhr');

      // Track form display time for anti-bot check
      const formTime = Date.now() - formLoadTime;
      formData.set('contact_form_time', Math.round(formTime));

      const response = await fetch(form.action, {
        method: 'POST',
        credentials: 'same-origin',
        body: formData,
        headers: {
          'Accept': 'application/json',
        },
      });

      const payload = await response.json();
      refreshFormToken(payload.formToken);
      const ok = response.ok && payload.status === 'success';

      if (!ok) {
        const fieldErrors = payload.fieldErrors && typeof payload.fieldErrors === 'object'
          ? payload.fieldErrors
          : {};

        let firstErrorField = null;
        FIELD_IDS.forEach((fieldId) => {
          const message = typeof fieldErrors[fieldId] === 'string' ? fieldErrors[fieldId] : '';
          if (message && firstErrorField === null) {
            firstErrorField = fieldId;
          }
          setFieldError(fieldId, message);
        });

        // Track validation errors
        if (Object.keys(fieldErrors).length > 0) {
          trackEvent('contact_validation_error', {
            fields: Object.keys(fieldErrors),
            fieldCount: Object.keys(fieldErrors).length,
          });
        }

        const errorMessage = typeof payload.message === 'string' && payload.message !== ''
          ? payload.message
          : 'Unable to send your message right now.';
        setStatus(errorMessage, 'error');

        if (typeof payload.cooldownRemaining === 'number' && payload.cooldownRemaining > 0) {
          startCooldown(payload.cooldownRemaining);
        }

        // Track form submission error
        trackEvent('contact_submit_error', {
          errorType: Object.keys(fieldErrors).length > 0 ? 'validation' : 'submission',
          formTimeMs: formTime,
        });

        if (firstErrorField) {
          const firstFieldEl = document.getElementById(firstErrorField);
          if (firstFieldEl) firstFieldEl.focus();
        } else if (statusEl) {
          statusEl.focus();
        }
        return;
      }

      setStatus(typeof payload.message === 'string' ? payload.message : 'Message sent.', 'success');
      showSuccessCard();
      form.reset();
      updateMessageCounter();

      if (typeof payload.cooldownRemaining === 'number' && payload.cooldownRemaining > 0) {
        startCooldown(payload.cooldownRemaining);
      } else {
        startCooldown(cooldownDurationSeconds);
      }

      // Track successful submission
      trackEvent('contact_submit_success', {
        formTimeMs: formTime,
        reason: formData.get('reason') || 'unknown',
      });
    } catch (error) {
      setStatus('Network issue while sending. Please try again.', 'error');
      if (statusEl) statusEl.focus();
    } finally {
      if (submitBtn) {
        submitBtn.removeAttribute('aria-busy');
      }
      renderCooldownState();
    }
  });

  // Handle "Send Another" button
  if (sendAnotherBtn) {
    sendAnotherBtn.addEventListener('click', () => {
      trackEvent('contact_send_another');
      resetForNewMessage();
    });
  }
}



