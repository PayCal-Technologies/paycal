/**
 * PayCalCore - Binary/base64 codec helpers
 *
 * Uses native Uint8Array base64/hex helpers when available, else legacy atob/btoa paths.
 *
 * IMPORT:
 *   import { fromBase64, toBase64, fromBase64Url, toBase64Url } from '/js/core/binary-codec.js';
 */

import { detectUint8ArrayBase64 } from './capabilities.js';

const hasNativeBase64 = detectUint8ArrayBase64();
const hasNativeHex = typeof Uint8Array.fromHex === 'function'
  && typeof Uint8Array.prototype.toHex === 'function';

function toUint8Array(input) {
  if (input instanceof Uint8Array) {
    return input;
  }
  if (input instanceof ArrayBuffer) {
    return new Uint8Array(input);
  }
  if (ArrayBuffer.isView(input)) {
    return new Uint8Array(input.buffer, input.byteOffset, input.byteLength);
  }
  return new Uint8Array(input);
}

export function fromBase64(b64) {
  const value = String(b64 ?? '');
  if (hasNativeBase64) {
    return Uint8Array.fromBase64(value);
  }
  return Uint8Array.from(atob(value), (char) => char.charCodeAt(0));
}

export function toBase64(bytes) {
  const view = toUint8Array(bytes);
  if (hasNativeBase64) {
    return view.toBase64();
  }
  return btoa(String.fromCharCode(...view));
}

export function fromHex(hex) {
  const value = String(hex ?? '');
  if (hasNativeHex) {
    return Uint8Array.fromHex(value);
  }

  const normalized = value.trim();
  if (normalized.length % 2 !== 0) {
    throw new RangeError('Hex input must have an even number of characters');
  }

  const output = new Uint8Array(normalized.length / 2);
  for (let index = 0; index < output.length; index += 1) {
    output[index] = Number.parseInt(normalized.slice(index * 2, index * 2 + 2), 16);
  }
  return output;
}

export function toHex(bytes) {
  const view = toUint8Array(bytes);
  if (hasNativeHex) {
    return view.toHex();
  }

  let output = '';
  for (let index = 0; index < view.length; index += 1) {
    output += view[index].toString(16).padStart(2, '0');
  }
  return output;
}

export function latin1FromBase64(b64) {
  if (hasNativeBase64) {
    return new TextDecoder('latin1').decode(fromBase64(b64));
  }
  return atob(String(b64 ?? ''));
}

export function latin1ToBase64(value) {
  const text = String(value ?? '');
  if (hasNativeBase64) {
    const bytes = Uint8Array.from(text, (char) => char.charCodeAt(0));
    return bytes.toBase64();
  }
  return btoa(text);
}

export function fromBase64Url(b64url) {
  const padding = '='.repeat((4 - (String(b64url ?? '').length % 4)) % 4);
  const base64 = `${String(b64url ?? '')}${padding}`.replace(/-/g, '+').replace(/_/g, '/');
  return fromBase64(base64).buffer;
}

export function toBase64Url(input) {
  const bytes = toUint8Array(input);
  return toBase64(bytes).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
}

export const b64ToBytes = fromBase64;
export const bytesToB64 = toBase64;
export const decodeBase64 = fromBase64;

const api = {
  fromBase64,
  toBase64,
  fromHex,
  toHex,
  latin1FromBase64,
  latin1ToBase64,
  fromBase64Url,
  toBase64Url,
  b64ToBytes,
  bytesToB64,
  decodeBase64,
};

if (typeof window !== 'undefined') {
  window.PayCalBinaryCodec = api;
}

export default api;
