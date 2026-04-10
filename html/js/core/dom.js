/**
 * PayCalCore - DOM Access Module
 * 
 * Safe element access, DOM queries, attribute manipulation.
 * 
 * IMPORT:
 *   import DOMModule from '/js/core/dom.js';
 */

import PW from '/js/phantomwing/';

const DOMModule = (() => {

  function getElement(id) {
    const el = document.getElementById(id);
    if (!el) PW.warn(`Element not found: ${id}`);
    return el;
  }

  function query(selector, parent = document) {
    return parent.querySelector(selector);
  }

  function queryAll(selectors, parent = document) {
    return Array.from(parent.querySelectorAll(selectors));
  }

  function removeElement(id) {
    const el = getElement(id);
    if (el) el.remove();
  }

  function copyAttribute(target, source) {
    const targetEl = getElement(target);
    const sourceEl = query(`[${source}]`);
    if (!targetEl || !sourceEl) return;
    targetEl.setAttribute("value", sourceEl.getAttribute(source) ?? '');
  }

  function getDataAttribute(el, id, name) {
    return el?.getAttribute(`data-${id}-${name}`) ?? null;
  }

  function escapeCssId(id) {
    if (typeof id !== 'string') return '';
    if (!isNaN(parseInt(id.charAt(0)))) {
      return `\\${id.charCodeAt(0).toString(16)} ${id.slice(1)}`;
    }
    return id;
  }

  function setSelectOption(select, selected_value) {
    if (!select?.options) return;
    for (let i = 0; i < select.options.length; i++) {
      if (select.options[i].value.toUpperCase() === (selected_value ?? '').toUpperCase()) {
        select.options[i].selected = true;
        break;
      }
    }
  }

  function togglePasswordVisibility(id) {
    const el = getElement(id);
    if (!el || !el.type) return;
    el.type = el.type === "password" ? "text" : "password";
  }

  function addClickAndEnterListener(id, func) {
    const el = getElement(id);
    if (!el) return null;
    el.addEventListener("click", func);
    el.addEventListener("keypress", (event) => {
      if (event.key === "Enter") func(event);
    });
    return el;
  }

  return {
    getElement,
    query,
    queryAll,
    removeElement,
    copyAttribute,
    getDataAttribute,
    escapeCssId,
    setSelectOption,
    togglePasswordVisibility,
    addClickAndEnterListener,
  };
})();

export default DOMModule;
