/**
 * Shared field error and invalid-state helpers.
 */

function isElement(value) {
  return typeof HTMLElement !== 'undefined' && value instanceof HTMLElement;
}

function resolveElement(target) {
  if (isElement(target)) {
    return target;
  }

  if (typeof document !== 'undefined' && typeof target === 'string' && target !== '') {
    return document.getElementById(target);
  }

  return null;
}

function syncFieldErrorMessageBinding(inputEl, errorEl, hasError) {
  if (!isElement(inputEl) || !isElement(errorEl)) {
    return;
  }

  const errorId = String(errorEl.id || '').trim();
  if (errorId === '') {
    return;
  }

  if (hasError) {
    inputEl.setAttribute('aria-errormessage', errorId);
  } else {
    inputEl.removeAttribute('aria-errormessage');
  }
}

export function setFieldInvalidState(input, isInvalid) {
  const inputEl = resolveElement(input);
  if (!isElement(inputEl)) {
    return false;
  }

  if (isInvalid) {
    inputEl.setAttribute('aria-invalid', 'true');
  } else {
    inputEl.removeAttribute('aria-invalid');
    inputEl.removeAttribute('aria-errormessage');
  }

  return true;
}

export function setFieldErrorText(errorTarget, message, { trim = true } = {}) {
  const errorEl = resolveElement(errorTarget);
  if (!isElement(errorEl)) {
    return false;
  }

  const text = String(message ?? '');
  errorEl.textContent = trim ? text.trim() : text;
  return true;
}

export function setFieldErrorState(input, errorTarget, message, { trim = true } = {}) {
  const text = String(message ?? '');
  const renderedText = trim ? text.trim() : text;
  const inputEl = resolveElement(input);
  const errorEl = resolveElement(errorTarget);
  const hasError = renderedText.length > 0;

  setFieldInvalidState(inputEl, hasError);
  setFieldErrorText(errorEl, renderedText, { trim: false });
  syncFieldErrorMessageBinding(inputEl, errorEl, hasError);
  return renderedText;
}

export function clearFieldErrorStates(pairs) {
  if (!Array.isArray(pairs)) {
    return;
  }

  pairs.forEach(([input, errorTarget]) => {
    setFieldErrorState(input, errorTarget, '');
  });
}

export function clearFieldInvalidStates(targets) {
  if (!Array.isArray(targets)) {
    return;
  }

  targets.forEach((target) => {
    setFieldInvalidState(target, false);
  });
}
