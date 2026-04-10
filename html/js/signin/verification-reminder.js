/**
 * Verification Module
 * Handles email verification form submission, resend, profile dropdown, and logout
 */

(function() {
  'use strict';

  // Profile dropdown toggle
  const profileBtn = document.getElementById('profile-btn');
  const profileMenu = document.getElementById('profile-menu');

  if (profileBtn && profileMenu) {
    const syncProfileMenuState = (open) => {
      profileBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      profileMenu.setAttribute('aria-hidden', open ? 'false' : 'true');
    };

    syncProfileMenuState(false);

    profileBtn.addEventListener('click', () => {
      const open = !profileMenu.classList.contains('show');
      profileMenu.classList.toggle('show', open);
      syncProfileMenuState(open);
    });

    document.addEventListener('click', (e) => {
      if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
        profileMenu.classList.remove('show');
        syncProfileMenuState(false);
      }
    });
  }

  // Logout
  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async () => {
      try {
        const response = await fetch('/api/v1/auth/logout', { method: 'POST' });
        if (!response.ok) {
          showStatus('Unable to sign out right now. Try again.', 'error');
          return;
        }
        window.location.href = '/auth/';
      } catch (err) {
        console.error('[Logout] Error:', err);
        showStatus('Unable to sign out right now. Try again.', 'error');
      }
    });
  }

  // Verification form submission
  const verificationForm = document.getElementById('verification-form') || document.getElementById('verification-code-form');
  const codeInput = document.getElementById('code-input') || document.querySelector('input[name="verification_code"]');
  const formStatus = document.getElementById('form-status');
  const resendEmailLink = document.getElementById('resend-email-link') || document.getElementById('resend-verification-email-link');
  const resendCooldownHintEl = document.getElementById('verification_resend_cooldown_hint');
  const resendEndpointInput = document.getElementById('resend-verification-endpoint');
  const resendEndpoint = (resendEndpointInput && resendEndpointInput.value)
    ? resendEndpointInput.value
    : '/api/v1/account/resend-verification';
  let resendInFlight = false;
  const RESEND_DEFAULT_COOLDOWN_SECONDS = 60;
  let resendCooldownRemainingSeconds = 0;
  let resendCooldownInterval = null;
  const resendOriginalText = resendEmailLink ? resendEmailLink.textContent : '';

  function stopResendCooldownTimer() {
    if (resendCooldownInterval !== null) {
      window.clearInterval(resendCooldownInterval);
      resendCooldownInterval = null;
    }
  }

  function setResendInteractiveState(enabled) {
    if (!resendEmailLink) {
      return;
    }

    resendEmailLink.setAttribute('aria-disabled', enabled ? 'false' : 'true');
    if (enabled) {
      resendEmailLink.classList.remove('is-disabled');
      resendEmailLink.removeAttribute('tabindex');
    } else {
      resendEmailLink.classList.add('is-disabled');
      resendEmailLink.setAttribute('tabindex', '-1');
    }
  }

  function formatCooldownTime(totalSeconds) {
    const safeSeconds = Math.max(0, totalSeconds);
    const minutes = Math.floor(safeSeconds / 60);
    const seconds = safeSeconds % 60;
    return `${minutes}:${String(seconds).padStart(2, '0')}`;
  }

  function renderResendCooldownState() {
    if (!resendEmailLink) {
      return;
    }

    const isCoolingDown = resendCooldownRemainingSeconds > 0;
    const countdownText = formatCooldownTime(resendCooldownRemainingSeconds);

    if (resendInFlight) {
      resendEmailLink.textContent = `${resendOriginalText || 'Resend verification email'} (sending...)`;
    } else {
      resendEmailLink.textContent = isCoolingDown
        ? `${resendOriginalText || 'Resend verification email'} (${countdownText})`
        : (resendOriginalText || 'Resend verification email');
    }

    if (!resendInFlight) {
      setResendInteractiveState(!isCoolingDown);
    }

    if (resendCooldownHintEl) {
      if (isCoolingDown) {
        resendCooldownHintEl.hidden = false;
        resendCooldownHintEl.textContent = `Please wait ${countdownText} before resending.`;
      } else {
        resendCooldownHintEl.hidden = true;
        resendCooldownHintEl.textContent = '';
      }
    }
  }

  function startResendCooldown(seconds) {
    resendCooldownRemainingSeconds = Number.isFinite(seconds) && seconds > 0
      ? Math.floor(seconds)
      : RESEND_DEFAULT_COOLDOWN_SECONDS;

    stopResendCooldownTimer();
    renderResendCooldownState();

    if (resendCooldownRemainingSeconds <= 0) {
      return;
    }

    resendCooldownInterval = window.setInterval(() => {
      resendCooldownRemainingSeconds = Math.max(0, resendCooldownRemainingSeconds - 1);
      renderResendCooldownState();

      if (resendCooldownRemainingSeconds <= 0) {
        stopResendCooldownTimer();
      }
    }, 1000);
  }

  if (verificationForm) {
    verificationForm.addEventListener('submit', (e) => {
      const code = codeInput ? codeInput.value.trim().toUpperCase() : '';
      if (!code) {
        e.preventDefault();
        showStatus('Enter the verification code.', 'error');
        return;
      }

      // Keep native form submit as the source of truth so verification works
      // even if this JS file is cached, blocked, or fails to execute.
      if (codeInput) {
        codeInput.value = code;
      }
      showStatus('Working…', 'info');
    });
  }

  // Resend email
  if (resendEmailLink) {
    resendEmailLink.addEventListener('click', async (e) => {
      e.preventDefault();

      if (resendInFlight || resendCooldownRemainingSeconds > 0) {
        return;
      }

      resendInFlight = true;
      setResendInteractiveState(false);
      resendEmailLink.setAttribute('aria-busy', 'true');
      resendEmailLink.classList.add('is-working');
      renderResendCooldownState();
      showStatus('Sending verification email...', 'info');

      try {
        const response = await fetch(resendEndpoint, {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: new URLSearchParams().toString(),
        });

        const data = await response.json();

        // 🔍 Lens debugging
        console.log('[Resend] Response:', {
          status: response.status,
          ok: response.ok,
          data: data,
          headers: {
            'retry-after': response.headers.get('retry-after'),
            'x-ratelimit-remaining': response.headers.get('x-ratelimit-remaining'),
          }
        });

        if (response.status === 401) {
          showStatus('Session expired. Please sign in again.', 'error');
          setTimeout(() => {
            window.location.href = '/auth/?signin_message=' + encodeURIComponent('Session expired. Please sign in again.');
          }, 600);
          return;
        }

        if (response.ok && data.status === 'success') {
          showStatus(data.message || 'Verification email sent.', 'success');
          startResendCooldown(RESEND_DEFAULT_COOLDOWN_SECONDS);
          if (codeInput) {
            codeInput.value = '';
            codeInput.focus();
          }
        } else {
          const message = data.message || 'Failed to resend. Try again.';
          showStatus(message, 'error');
          
          // Log detailed error for debugging
          if (response.status === 429) {
            console.warn('[Resend] Rate limited. Retry after:', response.headers.get('retry-after'));
            const retryAfterRaw = parseInt(response.headers.get('retry-after') || '', 10);
            startResendCooldown(Number.isFinite(retryAfterRaw) ? retryAfterRaw : RESEND_DEFAULT_COOLDOWN_SECONDS);
          }
        }
      } catch (err) {
        console.error('[Resend] Error:', err);
        showStatus('Something went wrong. Try again.', 'error');
      } finally {
        resendInFlight = false;
        resendEmailLink.removeAttribute('aria-busy');
        resendEmailLink.classList.remove('is-working');
        renderResendCooldownState();
      }
    });
  }

  function showStatus(message, type = 'info') {
    if (!formStatus) return;

    formStatus.textContent = message;
    formStatus.className = `status status-drop-in status-${type}`;
    formStatus.classList.remove('is-hidden');

    // Auto-hide success messages
    if (type === 'success') {
      setTimeout(() => {
        formStatus.classList.add('is-hidden');
      }, 3000);
    }
  }
})();
