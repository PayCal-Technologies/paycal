/* Public website navigation and reading preferences. Authenticated PayCal is excluded. */
(() => {
  'use strict';
  const init = () => {
    const appHeader = document.querySelector('#page_header.nav_component--public');
    const header = appHeader || document.querySelector('.site-header');
    if (!header || header.classList.contains('pc-public-header')) return;
    const brand = header.querySelector('.public_brand a, .brand');
    if (!brand) return;
    const root = document.documentElement;
    const read = key => { try { return localStorage.getItem(key); } catch { return null; } };
    const save = (key, value) => { try { localStorage.setItem(key, String(value)); } catch {} };
    const el = (tag, className, text) => {
      const node = document.createElement(tag);
      if (className) node.className = className;
      if (text) node.textContent = text;
      return node;
    };
    const icon = (kind) => {
      const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
      svg.setAttribute('viewBox', '0 0 24 24');
      svg.setAttribute('aria-hidden', 'true');
      svg.setAttribute('focusable', 'false');
      const path = document.createElementNS(svg.namespaceURI, 'path');
      path.setAttribute('d', kind === 'menu' ? 'M4 6h16M4 12h16M4 18h16' : kind === 'close' ? 'm6 6 12 12M18 6 6 18' : 'm9 3-.6 2.2-2 .9-2-.6-1.5 2.6 1.5 1.7-.2 2.2L2.5 14 4 16.6l2.2-.4 1.8 1.3.4 2.3h3l.7-2.2 2-.9 2 .6 1.5-2.6-1.5-1.7.2-2.2 1.7-1.8L18.5 6l-2.2.4-1.8-1.3L14.1 3Z');
      svg.append(path);
      if (kind === 'preferences') {
        const circle = document.createElementNS(svg.namespaceURI, 'circle');
        circle.setAttribute('cx', '10.5'); circle.setAttribute('cy', '11.4'); circle.setAttribute('r', '3'); svg.append(circle);
      }
      return svg;
    };
    const control = (label, kind) => {
      const button = el('button', 'pc-control pc-' + kind);
      button.type = 'button'; button.setAttribute('aria-label', label); button.title = label; button.append(icon(kind));
      return button;
    };
    const language = header.querySelector('.public_language_bar, .lang-switcher');
    const originalTheme = header.querySelector('[data-theme-toggle], #theme_toggle');
    const row = el('nav', 'pc-header-row'); row.setAttribute('aria-label', 'Primary');
    if (appHeader) row.id = 'primary_navigation';
    const brandName = appHeader ? 'PayCal' : 'PayCal Technologies';
    brand.textContent = '';
    const logo = el('img'); logo.src = '/apple-touch-icon.png'; logo.alt = ''; logo.width = 32; logo.height = 32;
    brand.append(logo, el('span', 'pc-brand-name', brandName)); brand.className = 'pc-brand';
    brand.setAttribute('role', 'button'); brand.setAttribute('aria-label', brandName + ' menu');
    const preferences = control('Preferences', 'preferences'); preferences.id = 'pc-preferences-button';
    preferences.setAttribute('aria-haspopup', 'dialog'); preferences.setAttribute('aria-controls', 'pc-preferences-panel'); preferences.setAttribute('aria-expanded', 'false');
    const menuButton = control('Menu', 'menu'); menuButton.append(el('span', 'pc-menu-label', 'Menu'));
    const menu = el('div', 'pc-public-menu'); menu.id = 'pc-public-menu'; menu.hidden = true;
    const links = el('nav', 'pc-menu-links'); links.setAttribute('aria-label', 'Website');
    const home = el('a', '', appHeader ? 'Sign in to PayCal' : 'Home'); home.href = appHeader ? '/auth/' : brand.href; links.append(home);
    if (appHeader) { const signup = el('a', '', 'Create an account'); signup.href = '/auth/signup/'; links.append(signup); }
    const footerLinks = document.querySelectorAll(appHeader ? '#page_footer .nav_menu--secondary a' : '.site-footer nav a');
    footerLinks.forEach(source => {
      const link = el('a', '', source.textContent.trim()); link.href = source.href;
      if (source.getAttribute('aria-current') === 'page') link.setAttribute('aria-current', 'page');
      links.append(link);
    });
    menu.append(links);
    if (language) {
      const languages = el('div', 'pc-languages'); languages.append(el('p', 'pc-menu-caption', 'Language'));
      const choices = el('nav', 'pc-language-links'); choices.setAttribute('aria-label', 'Language');
      // Keep the original links and their locale-specific destinations.
      language.querySelectorAll('a').forEach(link => { link.className = ''; choices.append(link); });
      languages.append(choices); menu.append(languages);
    }
    const panel = el('dialog', 'pc-preferences-panel'); panel.id = 'pc-preferences-panel'; panel.setAttribute('aria-labelledby', 'pc-preferences-title');
    const panelHead = el('div', 'pc-dialog-head'); const title = el('h2', '', 'Preferences'); title.id = 'pc-preferences-title';
    const dismiss = control('Close preferences', 'close'); panelHead.append(title, dismiss);
    const panelBody = el('div', 'pc-dialog-body');
    const themeLabel = el('label', '', 'Colour theme'); themeLabel.htmlFor = 'pc-colour-theme';
    const theme = el('select'); theme.id = 'pc-colour-theme';
    [['system','Use device setting'],['light','Light'],['dark','Dark']].forEach(([value,label]) => { const option = el('option', '', label); option.value = value; theme.append(option); });
    const themeBridge = el('div'); themeBridge.hidden = true; if (originalTheme) themeBridge.append(originalTheme);
    const labelRow = el('div', 'pc-size-label'); const textLabel = el('label', '', 'Text size'); textLabel.htmlFor = 'pc-text-size';
    const output = el('output'); output.htmlFor = 'pc-text-size';
    const textSize = el('input'); textSize.type = 'range'; textSize.id = 'pc-text-size'; textSize.min = '100'; textSize.max = '200'; textSize.step = '10';
    labelRow.append(textLabel, output);
    const preview = el('div', 'pc-reading-preview'); preview.append(el('span', '', 'Reading preview'), el('p', '', 'Clear information. Made for people.'));
    const reset = el('button', 'pc-reset', 'Reset preferences'); reset.type = 'button';
    const status = el('span', 'pc-reader-status'); status.setAttribute('role', 'status');
    panelBody.append(themeLabel, theme, themeBridge, labelRow, textSize, preview, reset, status); panel.append(panelHead, panelBody);
    row.append(brand, preferences, menuButton, menu); header.replaceChildren(row); header.classList.add('pc-public-header');
    root.classList.add('pc-public-site'); document.body.append(panel);
    let menuTrigger = menuButton;
    const setMenu = (open, restore = false) => {
      menu.hidden = !open;
      for (const trigger of [brand, menuButton]) trigger.setAttribute('aria-expanded', String(open));
      if (open) header.classList.remove('is-scroll-hidden');
      if (restore) menuTrigger.focus();
    };
    for (const trigger of [brand, menuButton]) {
      trigger.setAttribute('aria-controls', menu.id); trigger.setAttribute('aria-expanded', 'false');
      trigger.addEventListener('click', event => { event.preventDefault(); menuTrigger = trigger; setMenu(menu.hidden); });
    }
    brand.addEventListener('keydown', event => { if (event.key === ' ') { event.preventDefault(); brand.click(); } });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !menu.hidden) { event.preventDefault(); setMenu(false, true); } });
    document.addEventListener('click', event => { if (!header.contains(event.target)) setMenu(false); });
    menu.addEventListener('click', event => { if (event.target.closest('a')) setMenu(false); });
    preferences.addEventListener('click', () => { setMenu(false); panel.showModal(); preferences.setAttribute('aria-expanded', 'true'); });
    dismiss.addEventListener('click', () => panel.close());
    panel.addEventListener('close', () => { preferences.setAttribute('aria-expanded', 'false'); preferences.focus(); });
    panel.addEventListener('click', event => { const rect = panel.getBoundingClientRect(); if (event.target === panel && (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom)) panel.close(); });
    const mode = () => appHeader ? root.dataset.themeMode || 'dark' : root.dataset.theme || 'light';
    const device = matchMedia('(prefers-color-scheme: dark)');
    const applyTheme = value => {
      const desired = value === 'system' ? (device.matches ? 'dark' : 'light') : value;
      if (desired === mode()) return;
      const radio = themeBridge.querySelector('input[data-theme-mode-option][value="' + desired + '"]');
      if (radio) radio.click();
      else if (originalTheme?.id === 'theme_toggle') originalTheme.click();
      else if (appHeader) {
        // Older maintained checkouts have a decorative appearance placeholder.
        // Use the same public theme cookie and stylesheet endpoints there.
        root.dataset.themeMode = desired;
        root.classList.toggle('dark-mode', desired === 'dark'); root.classList.toggle('light-mode', desired === 'light');
        document.cookie = 'PayCal_Theme_Mode=' + desired + '; Max-Age=94608000; Path=/; SameSite=Lax; Secure';
        save('PayCal_Theme_Mode', desired);
        for (const sheet of document.querySelectorAll('#theme_stylesheet, link[rel="stylesheet"][href*="/css/?"]')) {
          const url = new URL(sheet.href, location.href);
          if (sheet.id === 'theme_stylesheet') {
            const name = url.searchParams.get('name') || 'paycal_blue_dark';
            url.searchParams.set('name', name.replace(/_(light|dark)$/, '_' + desired));
          }
          url.searchParams.set('themeMode', desired); url.searchParams.set('ts', String(Date.now())); sheet.href = url.toString();
        }
      }
    };
    const savedTheme = read('pc-public-theme'); theme.value = ['light','dark','system'].includes(savedTheme) ? savedTheme : mode();
    if (savedTheme) applyTheme(theme.value);
    theme.addEventListener('change', () => { applyTheme(theme.value); save('pc-public-theme', theme.value); });
    device.addEventListener('change', () => { if (theme.value === 'system') applyTheme('system'); });
    const setText = value => {
      const scale = Math.max(100, Math.min(200, Math.round((Number(value) || 100) / 10) * 10));
      root.dataset.pcText = String(scale); textSize.value = String(scale); output.value = scale + '%'; textSize.setAttribute('aria-valuetext', scale + ' percent');
    };
    setText(read('pc-public-text'));
    textSize.addEventListener('input', () => { setText(textSize.value); save('pc-public-text', textSize.value); });
    reset.addEventListener('click', () => { theme.value = 'system'; applyTheme('system'); save('pc-public-theme', 'system'); setText(100); save('pc-public-text', 100); status.textContent = 'Preferences reset to device theme and default text size.'; });
    const position = () => Math.max(0, Math.min(scrollY, Math.max(0, document.documentElement.scrollHeight - innerHeight)));
    let previous = position();
    header.addEventListener('focusin', () => header.classList.remove('is-scroll-hidden'));
    window.addEventListener('scroll', () => {
      const y = position(), delta = y - previous; previous = y;
      if (y < 24 || delta < 0 || !menu.hidden || panel.open) header.classList.remove('is-scroll-hidden');
      else if (delta > 0) header.classList.add('is-scroll-hidden');
    }, {passive:true});
  };
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
