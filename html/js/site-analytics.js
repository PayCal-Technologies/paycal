/* Optional public-page measurement. No network request to Google before consent. */
(function () {
  'use strict';
  var config = document.currentScript;
  if (!config) return;
  var id = config.dataset.measurementId;
  var nonce = config.nonce;
  if (!/^G-[A-Z0-9]+$/.test(id || '')) return;
  var key = 'site-analytics-choice-v1';
  var started = false;
  var privacySignal = navigator.globalPrivacyControl === true || navigator.doNotTrack === '1';
  function readChoice() { try { return localStorage.getItem(key); } catch (_) { return null; } }
  function cleanURL(value) { try { var url = new URL(value); return url.origin + url.pathname; } catch (_) { return ''; } }
  function start() {
    if (started || privacySignal) return;
    started = true;
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
    window.gtag('consent', 'default', { analytics_storage: 'granted', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' });
    window.gtag('js', new Date());
    window.gtag('config', id, {
      page_location: cleanURL(location.href), page_referrer: cleanURL(document.referrer),
      allow_google_signals: false, allow_ad_personalization_signals: false,
      cookie_domain: 'none', cookie_expires: 15552000
    });
    var script = document.createElement('script');
    if (nonce) script.nonce = nonce;
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
    document.head.appendChild(script);
  }
  function ready() {
    var panel = document.createElement('section');
    panel.className = 'site-analytics-choice';
    panel.setAttribute('aria-label', 'Website analytics choice');
    var text = document.createElement('p');
    text.textContent = 'Allow Google Analytics to measure visits to public pages? Google receives page, browser and device information and uses analytics cookies. Your choice stays on this site.';
    if (privacySignal) text.textContent = 'Your browser’s privacy signal keeps Google Analytics off. Public pages remain available without analytics.';
    panel.appendChild(text);
    function button(label, fn) {
      var el = document.createElement('button'); el.type = 'button'; el.textContent = label;
      el.addEventListener('click', fn); panel.appendChild(el); return el;
    }
    var settings = document.createElement('button');
    settings.type = 'button'; settings.className = 'site-analytics-settings'; settings.textContent = 'Analytics preferences';
    settings.addEventListener('click', function () { panel.hidden = false; decline.focus(); });
    function save(choice) {
      try { localStorage.setItem(key, choice); } catch (_) { /* The choice applies to this page if storage is unavailable. */ }
      panel.hidden = true;
      if (choice === 'allow') {
        if (window.gtag) window.gtag('consent', 'update', {analytics_storage: 'granted'});
        start();
      }
      else {
        window['ga-disable-' + id] = true;
        if (window.gtag) window.gtag('consent', 'update', {analytics_storage: 'denied'});
        document.cookie.split(';').forEach(function (item) {
          var name = item.trim().split('=')[0];
          if (/^_ga(?:_|$)/.test(name)) document.cookie = name + '=; Max-Age=0; Path=/; SameSite=Lax; Secure';
        });
      }
      settings.focus();
    }
    var decline = button('Decline analytics', function () { save('decline'); });
    var allow = button('Allow analytics', function () { window['ga-disable-' + id] = false; save('allow'); });
    allow.disabled = privacySignal;
    document.body.appendChild(panel); document.body.appendChild(settings);
    var choice = readChoice();
    panel.hidden = choice === 'allow' || choice === 'decline' || privacySignal;
    if (choice === 'allow') start();
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready, { once: true }); else ready();
}());
