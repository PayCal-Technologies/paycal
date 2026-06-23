/**
 * Small shared helpers for action controls.
 */

function isElement(value) {
  return typeof HTMLElement !== 'undefined' && value instanceof HTMLElement;
}

export function setActionBusy(control, busy, {
  ariaBusy = true,
  ariaBusyWhenIdle = true,
  ariaDisabled = false,
  busyClass = '',
  disable = true,
} = {}) {
  if (!isElement(control)) {
    return false;
  }

  const isBusy = busy === true;

  if (disable && 'disabled' in control) {
    control.disabled = isBusy;
  }

  if (ariaDisabled) {
    control.setAttribute('aria-disabled', isBusy ? 'true' : 'false');
  }

  if (ariaBusy) {
    if (isBusy || ariaBusyWhenIdle) {
      control.setAttribute('aria-busy', isBusy ? 'true' : 'false');
    } else {
      control.removeAttribute('aria-busy');
    }
  }

  if (busyClass !== '') {
    control.classList.toggle(busyClass, isBusy);
  }

  return true;
}
