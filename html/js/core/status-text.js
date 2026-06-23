/**
 * Shared plain status text helpers.
 */

function isElement(value) {
  return typeof HTMLElement !== 'undefined' && value instanceof HTMLElement;
}

export function setStatusText(element, message = '', {
  tone = 'info',
  toneClasses = ['error', 'success'],
  emptyClass = '',
  visibleClass = '',
} = {}) {
  if (!isElement(element)) {
    return false;
  }

  const text = String(message || '');
  const classes = Array.isArray(toneClasses) ? toneClasses.filter((item) => item !== '') : [];
  element.textContent = text;

  if (classes.length > 0) {
    element.classList.remove(...classes);
  }
  if (emptyClass !== '') {
    element.classList.toggle(emptyClass, text === '');
  }
  if (visibleClass !== '') {
    element.classList.toggle(visibleClass, text !== '');
  }
  if ((tone === 'error' || tone === 'success') && classes.includes(tone)) {
    element.classList.add(tone);
  }

  return true;
}

export function setPlainStatusText(element, message = '') {
  return setStatusText(element, message, { toneClasses: [] });
}

export function setPrefixedStatusText(element, message = '', tone = 'info', {
  prefix = 'status_message_',
  tones = ['error', 'muted', 'info', 'success'],
  updateText = true,
} = {}) {
  if (!isElement(element)) {
    return false;
  }

  const statusTones = Array.isArray(tones) ? tones.filter((item) => item !== '') : [];
  const classPrefix = String(prefix || '');
  const normalizedTone = String(tone || '');

  if (updateText) {
    element.textContent = String(message || '');
  }

  if (statusTones.length > 0) {
    element.classList.remove(...statusTones.map((item) => `${classPrefix}${item}`));
  }
  if (normalizedTone !== '') {
    element.classList.add(`${classPrefix}${normalizedTone}`);
  }

  return true;
}
