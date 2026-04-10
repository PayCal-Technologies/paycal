/**
 * PayCalCore - Status & Toast Module
 * 
 * Unified status messaging with themed styling, icons, audio feedback.
 * Canonical cross-page toast API.
 * 
 * IMPORT:
 *   import StatusModule from '/js/core/status.js';
 */

import PW from '/js/phantomwing/';

const StatusModule = (state, trustLayer, textToSpeechFn) => (() => {

  /**
   * Update status message with themed styling and icons.
   * @param {string} message - The message to display
   * @param {string} type - 'success', 'save', 'copy', 'paste', 'delete', 'error', 'info', 'working'
   * @param {number} autoClearMs - Auto-clear after this many ms (0 = don't auto-clear)
   * @param {boolean} skipTTS - Skip text-to-speech announcement
   */
  function updateStatusMessage(message, type = 'info', autoClearMs = 4000, skipTTS = false) {
    const statusDiv = document.getElementById('status');
    
    if (!statusDiv) {
      PW.warn('[Status] #status div not found');
      return;
    }

    // Icon name mapping by type (CSS class handles styling via tokens)
    const iconNames = {
      copy: 'copy',
      save: 'save',
      paste: 'paste',
      delete: 'delete-sign',
      error: 'error',
      info: 'info',
      working: 'hourglass',
      success: 'checkmark',
    };

    const iconName = iconNames[type] || iconNames.info;
    const token = `${Date.now()}-${Math.random()}`;

    statusDiv.textContent = '';
    statusDiv.className = `status visible status-${type}`;

    const iconBox = document.createElement('span');
    iconBox.className = 'status-icon-box';
    iconBox.setAttribute('aria-hidden', 'true');

    const iconImg = document.createElement('img');
    const baseHref = (document.querySelector('base') && document.querySelector('base').href) || window.location.origin + '/';
    const iconCandidates = [
      new URL(`images/icons8/status/${iconName}.png`, baseHref).href,
      new URL(`img/icons8/status/${iconName}.png`, baseHref).href,
      `/images/icons8/status/${iconName}.png`,
      `/img/icons8/status/${iconName}.png`
    ];
    let iconCandidateIndex = 0;
    iconImg.src = iconCandidates[iconCandidateIndex];
    iconImg.alt = '';
    iconImg.width = 20;
    iconImg.height = 20;
    iconImg.addEventListener('error', () => {
      iconCandidateIndex += 1;
      if (iconCandidateIndex < iconCandidates.length) {
        iconImg.src = iconCandidates[iconCandidateIndex];
        return;
      }
      PW.warn('[Status] Icon failed to load', {
        type,
        iconName: iconName,
        attempted: iconCandidates
      });
      iconImg.remove();
      iconBox.textContent = '!';
    });
    iconBox.appendChild(iconImg);

    const messageText = document.createElement('span');
    messageText.className = 'status-message-text';
    messageText.textContent = message;

    statusDiv.appendChild(iconBox);
    statusDiv.appendChild(messageText);
    statusDiv.dataset.statusToken = token;

    // Audio feedback
    if (!skipTTS && state.audio_feedback === "all") {
      try {
        const spokenMessage = String(message ?? '').replace(/^status:\s*/i, '').trim();
        if (spokenMessage !== '') {
          const category = type === 'error' ? 'error' : 'status';
          textToSpeechFn(spokenMessage, category);
        }
      } catch {}
    }

    // Auto-clear if specified
    if (autoClearMs > 0) {
      const effectiveAutoClearMs = autoClearMs * 2;
      setTimeout(() => {
        if (statusDiv.dataset.statusToken === token) {
          statusDiv.textContent = '';
          statusDiv.className = 'status';
          delete statusDiv.dataset.statusToken;
        }
      }, effectiveAutoClearMs);
    }
  }

  /**
   * Canonical cross-page toast API.
   * Use this from page scripts to avoid page-level toast duplication.
   */
  function showToast(message, type = 'info', autoClearMs = 3000, skipTTS = false) {
    updateStatusMessage(message, type, autoClearMs, skipTTS);
  }

  return {
    updateStatusMessage,
    showToast,
  };
})();

export default StatusModule;
