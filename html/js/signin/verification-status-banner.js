/*
 * Verification status banner behavior.
 * Reads i18n messages from data attributes and handles resend actions.
 */
(function () {
  'use strict';

  function getMessages(banner) {
    return {
      sending: banner.dataset.msgSending || 'Sending...',
      sent: banner.dataset.msgSent || 'Email Sent!',
      failed: banner.dataset.msgFailed || 'Failed to send email',
      failedAlert: banner.dataset.msgFailedAlert || 'Failed to resend verification email. Please try again later.'
    };
  }

  async function resendVerificationEmail(banner, button, messages) {
    var originalText = button.textContent;
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.classList.add('is-working');
    button.textContent = messages.sending;

    try {
      var response = await fetch('/api/v1/account/resend-verification', {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: new URLSearchParams().toString()
      });

      var data = null;
      try {
        data = await response.json();
      } catch (_parseError) {
        data = null;
      }

      if (response.ok && data && data.status === 'success') {
        button.textContent = messages.sent;
        button.classList.remove('is-error');
        button.classList.add('is-success');

        setTimeout(function () {
          button.textContent = originalText;
          button.disabled = false;
          button.removeAttribute('aria-busy');
          button.classList.remove('is-working');
          button.classList.remove('is-success');
        }, 3000);
      } else {
        throw new Error((data && data.message) || messages.failed);
      }
    } catch (error) {
      console.error('Resend verification failed:', error);
      button.textContent = messages.failed;
      button.classList.remove('is-success');
      button.classList.add('is-error');
      window.setTimeout(function () {
        button.textContent = originalText;
        button.disabled = false;
        button.removeAttribute('aria-busy');
        button.classList.remove('is-working');
        button.classList.remove('is-error');
      }, 3500);
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    var banner = document.querySelector('.verification-status-banner');
    if (!banner) {
      return;
    }

    var resendBtn = document.getElementById('resend-verification-btn');
    var emailVerified = banner.dataset.emailVerified === 'true';
    var messages = getMessages(banner);

    if (resendBtn) {
      resendBtn.addEventListener('click', function () {
        resendVerificationEmail(banner, resendBtn, messages);
      });
    }

    if (!emailVerified) {
      banner.classList.remove('hidden');
    }
  });
})();
