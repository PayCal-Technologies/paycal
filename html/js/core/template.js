/**
 * Replace {name} placeholders in server-provided UI messages.
 */

export function formatTemplate(template, replacements = {}, { fallbackToken = '' } = {}) {
  let message = String(template || '');
  const values = replacements && typeof replacements === 'object' ? replacements : {};
  const fallback = typeof fallbackToken === 'string' ? fallbackToken : '';

  Object.entries(values).forEach(([key, value]) => {
    const replacement = String(value ?? '');
    message = message.split(`{${key}}`).join(replacement);
    if (fallback !== '') {
      message = message.split(fallback).join(replacement);
    }
  });

  return message;
}

export function formatPhpTemplate(template, values = []) {
  const replacements = Array.isArray(values) ? values : [values];
  let index = 0;

  return String(template || '').replace(/%[sd]/g, (match) => {
    if (index >= replacements.length) {
      return match;
    }

    const value = replacements[index];
    index += 1;
    return String(value ?? '');
  });
}

export function getI18nLabel(config, key, fallback = '') {
  const value = String(config?.[key] ?? '').trim();
  return value !== '' ? value : fallback;
}

export function formatI18n(config, key, fallback, params = {}) {
  return formatTemplate(getI18nLabel(config, key, fallback), params);
}
