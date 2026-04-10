<?php declare(strict_types=1);

namespace PayCal\Domain;

use PayCal\Domain\Config\Environment;

require_once __DIR__ . '/../../config.php';

Authentication::abortIfUnauthenticated();
Authentication::isAdminOrDie();

CORS::handleORIGIN();
CORS::renderContentType('application/javascript');
Javascript::renderDocBlock();
?>

import PW from '<?php echo Environment::appURL('js/phantomwing/'); ?>';

function getApiBase() {
  const configNode = document.getElementById('admin-tax-brackets-config');
  const value = configNode?.dataset?.apiBase || '';
  return String(value).replace(/\/+$/, '');
}

function setStatus(message) {
  const status = document.getElementById('status');
  if (!status) {
    return;
  }
  status.textContent = message;
}

async function getCapabilityToken(action) {
  const apiBase = getApiBase();
  const response = await fetch(`${apiBase}/admin/capability/${encodeURIComponent(action)}`, {
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

async function postTaxBrackets(endpoint, formId, action, successMessage, errorMessage) {
  const apiBase = getApiBase();
  const formEl = document.getElementById(formId);
  if (!(formEl instanceof HTMLFormElement)) {
    setStatus(errorMessage);
    return;
  }

  try {
    const token = await getCapabilityToken(action);
    const formData = new FormData(formEl);
    formData.set('capability_token', token);

    const response = await fetch(`${apiBase}/${endpoint}`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-PayCal-Capability': token,
      },
      body: formData,
    });

    const data = await response.json();
    if (!response.ok || data.status !== 'success') {
      throw new Error(data.message || errorMessage);
    }

    setStatus(data.message || successMessage);
  } catch (error) {
    PW.error(`[admin-tax-brackets] ${error instanceof Error ? error.message : String(error)}`);
    setStatus(errorMessage);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  const provinceSelect = document.getElementById('province_select');
  if (provinceSelect) {
    provinceSelect.addEventListener('change', (event) => {
      const target = event.target;
      const province = target && 'value' in target ? String(target.value) : '';
      window.location.href = `?province=${encodeURIComponent(province)}`;
    });
  }

  const saveFederal = document.getElementById('save_federal');
  if (saveFederal) {
    saveFederal.addEventListener('click', () => {
      void postTaxBrackets(
        'admin/tax-brackets/federal',
        'federal_form',
        'admin.tax-brackets.federal',
        'Federal tax brackets saved.',
        'Error saving federal brackets.'
      );
    });
  }

  const saveProvincial = document.getElementById('save_provincial');
  if (saveProvincial) {
    saveProvincial.addEventListener('click', () => {
      void postTaxBrackets(
        'admin/tax-brackets/provincial',
        'provincial_form',
        'admin.tax-brackets.provincial',
        'Provincial tax brackets saved.',
        'Error saving provincial brackets.'
      );
    });
  }
});
