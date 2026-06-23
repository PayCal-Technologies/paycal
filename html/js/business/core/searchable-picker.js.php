<?php namespace PayCal\Domain; ?>

  const displayCurrencyValue = (searchEl, code) => {
    if (!(searchEl instanceof HTMLInputElement)) return;
    const entry = (code && CURRENCY_LIST[code]) ? CURRENCY_LIST[code] : null;
    searchEl.value = entry ? `${entry.code} \u2014 ${entry.name}` : (code || '');
  };

  const initSearchListbox = ({
    searchId,
    hiddenId,
    listboxId,
    wrapperId,
    itemSelector,
    activeClass,
    valueAttribute,
    buildMatches,
    renderItem,
    displayValue,
    onSelect,
    resolveFocusQuery = (searchEl) => searchEl.value,
  }) => {
    const searchEl = document.getElementById(searchId);
    const hiddenEl = document.getElementById(hiddenId);
    const listboxEl = document.getElementById(listboxId);
    const wrapperEl = document.getElementById(wrapperId);
    if (!(searchEl instanceof HTMLInputElement) || !(hiddenEl instanceof HTMLInputElement) || !listboxEl || !wrapperEl) return;

    let activeIndex = -1;

    const closeList = () => {
      listboxEl.hidden = true;
      wrapperEl.setAttribute('aria-expanded', 'false');
      activeIndex = -1;
    };

    const setActive = (index) => {
      const items = Array.from(listboxEl.querySelectorAll(itemSelector));
      items.forEach((item, i) => {
        const on = i === index;
        item.setAttribute('aria-selected', on ? 'true' : 'false');
        item.classList.toggle(activeClass, on);
      });
      if (items[index]) items[index].scrollIntoView({ block: 'nearest' });
      activeIndex = index;
    };

    const selectValue = (value) => {
      hiddenEl.value = value;
      displayValue(searchEl, value);
      closeList();
      onSelect(value);
      hiddenEl.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const buildList = (query) => {
      const matches = buildMatches(query);
      if (matches.length === 0) { closeList(); return; }
      const html = matches.map((item, i) => renderItem(item, i, listboxId)).join('');
      Guardian.setHTML(listboxEl, html);
      activeIndex = -1;
      listboxEl.hidden = false;
      wrapperEl.setAttribute('aria-expanded', 'true');
      listboxEl.querySelectorAll(itemSelector).forEach((item) => {
        item.addEventListener('mousedown', (e) => {
          e.preventDefault();
          const value = String(item.getAttribute(valueAttribute) || '');
          if (value) selectValue(value);
        });
      });
    };

    searchEl.addEventListener('input', () => buildList(searchEl.value));
    searchEl.addEventListener('focus', () => buildList(resolveFocusQuery(searchEl, hiddenEl)));
    searchEl.addEventListener('blur', () => setTimeout(closeList, 160));
    searchEl.addEventListener('keydown', (e) => {
      const items = Array.from(listboxEl.querySelectorAll(itemSelector));
      const pageStep = 10;
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        setActive(Math.min(activeIndex + 1, items.length - 1));
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        setActive(Math.max(activeIndex - 1, 0));
      } else if (e.key === 'Home') {
        e.preventDefault();
        if (items.length > 0) {
          setActive(0);
        }
      } else if (e.key === 'End') {
        e.preventDefault();
        if (items.length > 0) {
          setActive(items.length - 1);
        }
      } else if (e.key === 'PageDown') {
        e.preventDefault();
        if (items.length > 0) {
          const nextIndex = activeIndex < 0
            ? Math.min(pageStep - 1, items.length - 1)
            : Math.min(activeIndex + pageStep, items.length - 1);
          setActive(nextIndex);
        }
      } else if (e.key === 'PageUp') {
        e.preventDefault();
        if (items.length > 0) {
          const nextIndex = activeIndex < 0
            ? 0
            : Math.max(activeIndex - pageStep, 0);
          setActive(nextIndex);
        }
      } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activeIndex >= 0 && items[activeIndex]) {
          const value = String(items[activeIndex].getAttribute(valueAttribute) || '');
          if (value) selectValue(value);
        }
      } else if (e.key === 'Escape') {
        closeList();
        displayValue(searchEl, hiddenEl.value);
      }
    });
  };

  const initCurrencyFinder = (searchId, hiddenId, listboxId, wrapperId) => {
    initSearchListbox({
      searchId,
      hiddenId,
      listboxId,
      wrapperId,
      itemSelector: '.currency_finder_item',
      activeClass: 'currency_finder_item_active',
      valueAttribute: 'data-code',
      buildMatches: (query) => {
        const q = String(query || '').toLowerCase().trim();
        return Object.values(CURRENCY_LIST).filter((c) =>
          q === ''
          || c.code.toLowerCase().includes(q)
          || c.name.toLowerCase().includes(q)
          || c.countries.toLowerCase().includes(q)
        ).slice(0, 60);
      },
      renderItem: (c, i, idPrefix) => (
        `<li class="currency_finder_item" role="option" id="${idPrefix}_item_${i}" data-code="${c.code}" aria-selected="false" tabindex="-1">` +
        `<span class="currency_finder_code">${c.code}</span>` +
        `<span class="currency_finder_symbol">${c.symbol}</span>` +
        `<span class="currency_finder_name">${c.name}</span>` +
        `</li>`
      ),
      displayValue: displayCurrencyValue,
      onSelect: (code) => {
        const currency = CURRENCY_LIST[code] || null;
        const label = currency ? `${currency.code} - ${currency.name}` : code;
        PC.showToast(formatPhpTemplate(T.currencyUpdatedLabel, [label]), 'save');
      },
      resolveFocusQuery: (searchEl, hiddenEl) => {
        const currentCode = hiddenEl.value || '';
        return currentCode && CURRENCY_LIST[currentCode] ? '' : searchEl.value;
      },
    });
  };

  const displayTimezoneValue = (searchEl, value) => {
    if (!(searchEl instanceof HTMLInputElement)) return;
    const zone = String(value || '');
    if (zone === '') {
      searchEl.value = '';
      return;
    }
    const meta = TIMEZONE_MAP[zone] || null;
    searchEl.value = meta ? meta.label : zone;
  };

  const initTimezoneFinder = (searchId, hiddenId, listboxId, wrapperId) => {
    initSearchListbox({
      searchId,
      hiddenId,
      listboxId,
      wrapperId,
      itemSelector: '.timezone_finder_item',
      activeClass: 'timezone_finder_item_active',
      valueAttribute: 'data-zone',
      buildMatches: (query) => {
        const q = String(query || '').toLowerCase().trim();
        return TIMEZONE_META.filter((item) => q === '' || item.searchable.includes(q)).slice(0, 80);
      },
      renderItem: (item, i, idPrefix) => (
        `<li class="timezone_finder_item" role="option" id="${idPrefix}_item_${i}" data-zone="${item.zone}" aria-selected="false" tabindex="-1">` +
        `<span class="timezone_finder_name">${item.zone}</span>` +
        `<span class="timezone_finder_offset">[UTC${item.offsetNow}]</span>` +
        `<span class="timezone_finder_abbr">${item.abbreviations.join('/')}</span>` +
        `</li>`
      ),
      displayValue: displayTimezoneValue,
      onSelect: (zone) => {
        const meta = TIMEZONE_MAP[zone] || null;
        const label = meta ? `${zone} [UTC${meta.offsetNow}]` : zone;
        PC.showToast(formatPhpTemplate(T.timezoneUpdatedLabel, [label]), 'save');
      },
    });
  };
