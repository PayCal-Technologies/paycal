/**
 * PayCalCore - RegExp helpers
 *
 * IMPORT:
 *   import { escapePattern } from '/js/core/regex.js';
 */

import { detectRegExpEscape } from './capabilities.js';

const FALLBACK_ESCAPE_PATTERN = /[.*+?^${}()|[\]\\]/g;

export function escapePattern(text) {
  const input = String(text ?? '');
  if (detectRegExpEscape()) {
    return RegExp.escape(input);
  }
  return input.replace(FALLBACK_ESCAPE_PATTERN, '\\$&');
}

export default escapePattern;
