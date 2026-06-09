/**
 * PayCalCore - Set helper utilities
 *
 * Uses native Set.union/intersection/difference when supported, else manual fallbacks.
 *
 * IMPORT:
 *   import { uniqueTruthy, uniqueNonEmptyStrings } from '/js/core/set-utils.js';
 */

import { detectSetMethods } from './capabilities.js';

const hasNativeSetMethods = detectSetMethods();

function manualUnique(values) {
  return values.filter((value, index, arr) => arr.indexOf(value) === index);
}

export function uniqueTruthy(values) {
  const items = values.filter(Boolean);
  if (hasNativeSetMethods) {
    return [...new Set(items)];
  }
  return manualUnique(items);
}

export function uniqueNonEmptyStrings(values) {
  const items = values.filter((value) => value !== '');
  if (hasNativeSetMethods) {
    return [...new Set(items)];
  }
  return manualUnique(items);
}

export function unionSets(left, right) {
  if (hasNativeSetMethods) {
    return left.union(right);
  }
  return new Set([...left, ...right]);
}

export function intersectionSets(left, right) {
  if (hasNativeSetMethods) {
    return left.intersection(right);
  }
  const output = new Set();
  left.forEach((value) => {
    if (right.has(value)) {
      output.add(value);
    }
  });
  return output;
}

export function differenceSets(left, right) {
  if (hasNativeSetMethods) {
    return left.difference(right);
  }
  const output = new Set();
  left.forEach((value) => {
    if (!right.has(value)) {
      output.add(value);
    }
  });
  return output;
}

const api = {
  uniqueTruthy,
  uniqueNonEmptyStrings,
  unionSets,
  intersectionSets,
  differenceSets,
};

if (typeof window !== 'undefined') {
  window.PayCalSetUtils = api;
}

export default api;
