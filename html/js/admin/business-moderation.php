<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

require_once dirname(__DIR__, 2) . '/config.php';

Authentication::abortIfUnauthenticated();
AdminSurface::redirectHomeIfPageUnavailable('/admin/business-moderation/');

CORS::handleORIGIN();
Javascript::renderModuleContentType('application/javascript');
Javascript::renderDocBlock();

?>
import PC from "<?php echo Render::jsModuleURL(); ?>";

/**
 * @param {string} id
 * @returns {HTMLElement|null}
 */
function byId(id) {
  return document.getElementById(id);
}

function initModerationFeedback() {
  const dataEl = byId('business-moderation-flash-data');
  if (!(dataEl instanceof HTMLScriptElement)) {
    return;
  }

  let payload = {};
  try {
    payload = JSON.parse(dataEl.textContent || '{}');
  } catch {
    return;
  }

  const message = String(payload.message || payload.detail || '').trim();
  if (message === '') {
    return;
  }

  const type = payload.type === 'error' ? 'error' : 'save';
  const feedback = byId('business-moderation-feedback');

  if (feedback && !feedback.classList.contains('is-visible')) {
    const title = String(payload.title || '').trim();
    const detail = String(payload.detail || message).trim();
    const icon = type === 'error' ? '!' : '✓';

    feedback.classList.add('is-visible');
    feedback.classList.add(type === 'error' ? 'business-moderation-feedback--error' : 'business-moderation-feedback--success');
    const iconEl = document.createElement('div');
    iconEl.className = 'business-moderation-feedback__icon';
    iconEl.setAttribute('aria-hidden', 'true');
    iconEl.textContent = icon;

    const bodyEl = document.createElement('div');
    bodyEl.className = 'business-moderation-feedback__body';

    const titleEl = document.createElement('p');
    titleEl.className = 'business-moderation-feedback__title';
    titleEl.textContent = title !== '' ? title : (type === 'error' ? 'Action failed' : 'Action completed');

    const detailEl = document.createElement('p');
    detailEl.className = 'business-moderation-feedback__detail';
    detailEl.textContent = detail;

    bodyEl.append(titleEl, detailEl);
    feedback.replaceChildren(iconEl, bodyEl);

    feedback.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
  }

  if (typeof PC.showToast === 'function') {
    PC.showToast(message, type, 7000, true);
  }
}

document.addEventListener('DOMContentLoaded', initModerationFeedback);
