/**
 * PayCalCore - Browser capability detection
 *
 * Detects optional modern APIs with safe runtime probes and legacy fallbacks elsewhere.
 *
 * IMPORT:
 *   import { capabilities, detectUint8ArrayBase64 } from '/js/core/capabilities.js';
 */

export function detectUint8ArrayBase64() {
  try {
    if (typeof Uint8Array.fromBase64 !== 'function') {
      return false;
    }

    const sample = Uint8Array.fromBase64('AQID');
    if (!(sample instanceof Uint8Array) || sample.length !== 3) {
      return false;
    }

    if (typeof sample.toBase64 !== 'function') {
      return false;
    }

    return sample.toBase64() === 'AQID';
  } catch {
    return false;
  }
}

export function detectSetMethods() {
  try {
    const left = new Set([1, 2]);
    const right = new Set([2, 3]);
    if (typeof left.union !== 'function'
      || typeof left.intersection !== 'function'
      || typeof left.difference !== 'function') {
      return false;
    }

    const merged = left.union(right);
    return merged instanceof Set && merged.size === 3;
  } catch {
    return false;
  }
}

export function detectRegExpEscape() {
  return typeof RegExp.escape === 'function';
}

export function isWebAuthnCapableBrowser({ requireSecureContext = true } = {}) {
  const hasPublicKeyCredential = typeof window !== 'undefined'
    && typeof window.PublicKeyCredential !== 'undefined';
  const credentials = typeof navigator !== 'undefined' ? navigator.credentials : null;
  const hasCredentialsApi = credentials !== undefined && credentials !== null;
  const hasGet = hasCredentialsApi && typeof credentials.get === 'function';
  const hasCreate = hasCredentialsApi && typeof credentials.create === 'function';
  const secureContext = requireSecureContext === false
    || (typeof window !== 'undefined' && window.isSecureContext === true);

  return secureContext && hasPublicKeyCredential && hasCredentialsApi && hasGet && hasCreate;
}

export const capabilities = Object.freeze({
  uint8arrayBase64: detectUint8ArrayBase64(),
  setMethods: detectSetMethods(),
  regexpEscape: detectRegExpEscape(),
  webAuthn: isWebAuthnCapableBrowser(),
});

export default capabilities;
