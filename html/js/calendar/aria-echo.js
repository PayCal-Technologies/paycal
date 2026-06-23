(function() {
  'use strict';

  if (window.PayCalAriaEcho) {
    return;
  }

  window.PayCalAriaEcho = class AriaEcho {
    static normalizeText(text) {
      return String(text ?? '')
        .trim()
        .replace(/\s*\/\s*/g, ', ')
        .replace(/\s*,\s*/g, ', ')
        .replace(/\s*;\s*/g, '; ')
        .replace(/\s*\.\s*/g, '. ')
        .replace(/\s+/g, ' ')
        .trim();
    }

    static cadence(input, delimiter = ', ') {
      if (Array.isArray(input)) {
        const filtered = input
          .map((part) => this.normalizeText(part))
          .filter((part) => part !== '');
        if (filtered.length === 0) return '';
        if (filtered.length === 1) return filtered[0];
        const sep = String(delimiter || '').trim() === '' ? ', ' : delimiter;
        return `${filtered.slice(0, -1).join(sep)}${sep}and ${filtered[filtered.length - 1]}`;
      }

      const normalized = this.normalizeText(input);
      if (normalized === '') return '';

      let parts = [];
      if (String(delimiter || '').trim() !== '' && normalized.includes(delimiter)) {
        parts = normalized.split(delimiter);
      } else if (/[|/;]/.test(normalized)) {
        parts = normalized.split(/\s*(?:\||\/|;)\s*/);
      }

      if (parts.length > 1) {
        return this.cadence(parts, delimiter);
      }

      return normalized;
    }

    static cadenceList(parts) {
      return this.cadence(parts, ', ');
    }
  };
})();
