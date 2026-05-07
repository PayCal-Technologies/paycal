<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__ . '/../../config.php';

if (headers_sent() === false) {
  header('Content-type: text/css');
}
// Navigation output depends on user preference state (e.g. sticky/fixed, side-nav placement).
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Vary: Cookie');

?>

:root {
  --nav-block-size: 3rem;
  --nav-inline-size: 14rem;
}

/* Placement hooks: defaults can be overridden via body data attributes. */
body {
  --primary-nav-position: left;
  --nav-sticky: static;
}

body[data-nav-primary-position='left'] {
  --primary-nav-position: left;
  --nav-block-size: 2.6rem;
}

body[data-nav-primary-position='right'] {
  --primary-nav-position: right;
  --nav-block-size: 2.6rem;
}

body[data-nav-initial-state='fixed'] {
  --nav-sticky: fixed;
}

body[data-nav-initial-state='static'] {
  --nav-sticky: static;
}

#main {
  width: 100%;
  padding-inline: var(--pad-md);
  padding-block-start: var(--pad-md);
  margin: calc(var(--nav-block-size) - var(--pad-xs)) 0 0 0;
  background-color: var(--color-bg);
  color: var(--color-text);
}

#page_header.nav_component--header {
  display: block;
  position: var(--nav-sticky);
  top: 0;
  left: 0;
  right: 0;
  z-index: 10000;
  width: 100%;
  height: var(--nav-block-size);
  margin: 0 0 var(--nav-block-size) 0;
  padding: 0;
  background-color: var(--panel-head-bg);
  color: var(--panel-head-text);
}

#page_header.nav_component--header .nav_menu--primary li {
  flex: 1;
  width: 100%;
  text-align: center;
  height: var(--nav-block-size);
  padding: 0;
  z-index: 1000;
}

#page_header.nav_component--header .nav_menu--primary ul li.pages { flex: 0 1 auto; }
#speaker_icon, [data-help-trigger="true"] { border: none; }

.nav_component--footer {
  margin: 0;
  padding: var(--pad-lg);
  min-block-size: 0;
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  justify-items: center;
  row-gap: var(--gap-sm);
  border-top: 1px solid color-mix(in srgb, var(--panel-border) 72%, transparent);
  background: linear-gradient(
    180deg,
    color-mix(in srgb, var(--panel-bg) 86%, transparent),
    color-mix(in srgb, var(--panel-head-bg, var(--panel-bg)) 88%, transparent)
  );
}

.nav_component--footer .nav_menu {
  width: 100%;
  height: auto;
  flex-wrap: wrap;
  justify-content: center;
}

.nav_component--footer .nav_menu ul {
  height: auto;
}

.nav_component--footer .nav_menu--secondary {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  width: 100%;
  margin: 0;
  padding: 0;
  list-style: none;
  gap: var(--gap-md, 1rem);
}

.nav_component--footer .nav_menu--secondary ul {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  width: 100%;
  gap: var(--gap-md, 1rem);
  height: auto;
}

.nav_component--footer .nav_menu--secondary li {
  flex: 0 0 auto;
  height: auto;
}

.nav_component--footer .nav_menu--secondary a {
  display: inline-flex;
  flex-grow: 0;
  width: auto;
  color: var(--nav-menu-fore, var(--color-text));
  background-color: var(--nav-menu-back, transparent);
  text-decoration: none;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
}

.nav_component--footer .nav_menu--secondary a:hover,
.nav_component--footer .nav_menu--secondary a:focus {
  background-color: var(--panel-text);
  color: var(--panel-bg);
  transition: background-color 0.2s ease;
}

.nav_component--footer .nav_menu--secondary a:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.nav_component--footer .footer_copyright {
  width: 100%;
  margin: 0.65rem auto 0;
  text-align: center;
  color: color-mix(in srgb, var(--color-text) 88%, var(--panel-border));
  text-wrap: balance;
  line-height: 1.35;
  max-width: 44ch;
}

.nav_component--footer .footer_soc2_badge_wrap {
  width: 100%;
  display: flex;
  justify-content: center;
  margin: 0.75rem 0;
}

.nav_component--footer .footer_soc2_badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35em;
  border: 1px solid color-mix(in srgb, var(--color-primary) 55%, var(--panel-border) 45%);
  border-radius: 999px;
  padding: 0.2rem 0.6rem;
  font-size: 0.78rem;
  line-height: 1.1;
  color: color-mix(in srgb, var(--color-primary) 80%, var(--color-text) 20%);
  background: color-mix(in srgb, var(--color-primary) 10%, transparent);
  text-decoration: none;
}

.nav_component--footer .footer_soc2_badge:hover,
.nav_component--footer .footer_soc2_badge:focus {
  background: color-mix(in srgb, var(--color-primary) 18%, transparent);
  color: color-mix(in srgb, var(--color-primary) 90%, var(--color-text) 10%);
}

.nav_component--footer .footer_soc2_badge:focus-visible {
  outline: 2px solid var(--color-focus-ring, #0096d6);
  outline-offset: 2px;
}

.nav_component--footer .footer_soc2_badge_icon {
  flex-shrink: 0;
  display: block;
}

.nav_menu {
  display: flex;
  width: 250px;
  margin: 0;
  align-items: center;
  height: var(--nav-block-size);
  overflow: visible;
}

.nav_menu ul {
  display: flex;
  flex-grow: 1;
  height: var(--nav-block-size);
  margin: 0;
  padding: 0;
  overflow: visible;
}

.nav_menu h1, .nav_menu div {
  display: flex;
  flex-grow: 1;
  align-items: center;
  height: var(--nav-block-size);
  padding: var(--pad-md);
  margin: 0;
}

#page_header.nav_component--header .nav_menu--primary h1,
#page_header.nav_component--header .nav_menu--primary h2,
#page_header.nav_component--header .nav_menu--primary h3,
#page_header.nav_component--header .nav_menu--primary h4,
#page_header.nav_component--header .nav_menu--primary h5,
#page_header.nav_component--header .nav_menu--primary h6 {
  margin: 0;
  line-height: 1.1;
}

.nav_menu a {
  display: flex;
  flex-grow: 1;
  justify-content: center;
  align-items: center;
  position: relative;
  padding: 0;
  text-align: center;
  color: var(--nav-menu-fore, var(--color-text));
  background-color: var(--nav-menu-back, transparent);
  text-decoration: none;
  letter-spacing: 0.05rem;
}

.nav_menu a.nav_admin_toggle {
  cursor: pointer;
}

.nav_menu a:active,
.nav_menu a.nav_admin_toggle:active { border-top: calc(var(--border-size)) inset var(--panel-border); }

.nav_menu li:hover a,
.nav_menu li:hover a.nav_admin_toggle {
  border: var(--border-size) double var(--color-text);
  border-radius: 0;
  background-color: var(--color-text);
  color: var(--color-text-inverse);
  transition: background-color var(--short-transition) ease;
}

.nav_menu li.active a,
.nav_menu li.active a.nav_admin_toggle {
  border: 0;
  border-radius: 0;
  border-top-left-radius: var(--border-radius);
  border-top-right-radius: var(--border-radius);
  box-shadow: inset 0 1px 0 color-mix(in srgb, var(--color-text) 25%, white), 
    inset 0 -1px 1px color-mix(in srgb, var(--color-text) 25%, black), 0 -1px 1px color-mix(in srgb, var(--color-text) 25%, black);
  background-color: var(--color-bg);
  color: var(--color-text);
  font-weight: 700;
}

span.active svg { fill: var(--color-text); }

.nav_menu li.active:hover a,
.nav_menu li.active:hover a.nav_admin_toggle,
.nav_menu li:focus-within a,
.nav_menu li:focus-within a.nav_admin_toggle {
  background-color: var(--color-bg);
  color: var(--color-text);
  transition: background-color var(--short-transition) ease;
}

.nav_admin_group {
  position: relative;
}

.nav_admin_popover {
  position: absolute;
  display: none;
  z-index: 10050;
  min-width: 13rem;
  padding: 0.35rem;
  border: 1px solid var(--panel-border);
  background-color: var(--color-bg, #111111);
  color: var(--color-text, #f5f5f5);
  opacity: 1;
  box-shadow: 0 10px 24px color-mix(in srgb, var(--panel-head-text) 24%, black);
}

.nav_admin_popover.is-portal {
  position: fixed;
  top: 0;
  left: 0;
  margin: 0;
}

.nav_admin_popover[hidden] {
  display: none !important;
}

.nav_admin_popover:popover-open,
.nav_admin_popover.is-open {
  display: grid;
  gap: 0.15rem;
}

.nav_admin_group:hover > .nav_admin_popover,
.nav_admin_popover::backdrop {
  background: transparent;
  backdrop-filter: none;
}

.nav_admin_popover .nav_admin_item {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  min-height: 2.25rem;
  padding: 0 0.55rem;
  text-decoration: none;
  color: inherit;
  white-space: nowrap;
}

.nav_admin_popover .nav_admin_item .nav_icon {
  display: inline-flex;
  flex: 0 0 var(--nav-icon-size);
}

.nav_admin_popover .nav_admin_item .nav_label {
  display: inline;
}

.nav_admin_popover .nav_admin_item:hover,
.nav_admin_popover .nav_admin_item:focus-visible,
.nav_admin_popover .nav_admin_item.active {
  background-color: var(--color-text);
  color: var(--color-text-inverse);
  outline: none;
}

body[data-nav-primary-position='top'] .nav_admin_popover {
  left: 0;
  top: calc(100% + 0.25rem);
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_admin_group {
  overflow: visible;
  max-width: none;
  z-index: 10060;
}

/* In side-nav, the global li rule sets z-index: 1000 which activates a stacking
   context on admin_group (the only positioned li). This makes its border paint in
   a different compositor layer from all other li elements, causing visible rendering
   inconsistency. Reset to auto to keep position:relative (popover anchor) without
   creating a stacking context. */
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary li.nav_admin_group,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary li.nav_admin_group {
  z-index: auto;
}

#page_header.nav_component--header > .nav_menu--primary > ul > li:not(.active) {
  border-bottom: 1px solid var(--panel-border);
}

/* Public-mode header is logo-only: force visible, high-contrast branding regardless of theme token gaps. */
#page_header.nav_component--public .nav_menu--primary ul {
  justify-content: center;
}

#page_header.nav_component--public .nav_menu--primary li.pages {
  flex: 0 1 auto;
  min-width: 0;
}

#page_header.nav_component--public .nav_menu--primary a {
  gap: 0.45rem;
  color: var(--panel-head-text, var(--color-primary, #6fb7ff));
  background-color: transparent;
}

#page_header.nav_component--public {
  --nav-brand-accent: var(--panel-head-text, var(--color-primary, #6fb7ff));
  --nav-brand-accent-soft: color-mix(in srgb, var(--nav-brand-accent) 84%, white);
  --nav-brand-accent-deep: color-mix(in srgb, var(--nav-brand-accent) 88%, black);
}

#page_header.nav_component--header:not(.nav_component--public) {
  --nav-brand-accent: var(--panel-head-text, var(--color-primary, #6fb7ff));
  --nav-brand-accent-soft: color-mix(in srgb, var(--nav-brand-accent) 82%, white);
  --nav-brand-accent-deep: color-mix(in srgb, var(--nav-brand-accent) 90%, black);
}

#page_header.nav_component--public .nav_menu--primary .nav_icon {
  display: inline-flex;
  color: currentColor;
}

#page_header.nav_component--public .nav_menu--primary .nav_brand_mark {
  position: relative;
  width: 2rem;
  height: 2rem;
  flex: 0 0 2rem;
  overflow: hidden;
  isolation: isolate;
  border-radius: 999px;
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--nav-brand-accent) 52%, transparent),
    0 0 18px color-mix(in srgb, var(--nav-brand-accent) 28%, transparent);
}

#page_header.nav_component--public .nav_menu--primary .nav_brand_mark_base {
  display: block;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

#page_header.nav_component--public .nav_menu--primary .nav_brand_mark_tint {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    radial-gradient(circle at 50% 26%, color-mix(in srgb, white 36%, var(--nav-brand-accent-soft)) 0%, transparent 42%),
    linear-gradient(180deg, var(--nav-brand-accent-soft) 0%, var(--nav-brand-accent) 52%, var(--nav-brand-accent-deep) 100%);
  mix-blend-mode: color;
}

#page_header.nav_component--public .nav_menu--primary .nav_brand_mark::after {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    radial-gradient(circle at 50% 28%, color-mix(in srgb, white 48%, var(--nav-brand-accent-soft)) 0%, transparent 40%),
    radial-gradient(circle at 50% 50%, color-mix(in srgb, var(--nav-brand-accent) 48%, transparent) 0%, transparent 76%);
  mix-blend-mode: soft-light;
  opacity: 1;
}

#page_header.nav_component--header:not(.nav_component--public) .nav_menu--primary .nav_brand_mark {
  position: relative;
  display: inline-block;
  width: var(--nav-icon-size);
  height: var(--nav-icon-size);
  flex: 0 0 var(--nav-icon-size);
  overflow: hidden;
  isolation: isolate;
  border-radius: 999px;
  box-shadow:
    0 0 0 1px color-mix(in srgb, var(--nav-brand-accent) 40%, transparent),
    0 0 12px color-mix(in srgb, var(--nav-brand-accent) 20%, transparent);
}

#page_header.nav_component--header:not(.nav_component--public) .nav_menu--primary .nav_brand_mark_base {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

#page_header.nav_component--header:not(.nav_component--public) .nav_menu--primary .nav_brand_mark_tint {
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    radial-gradient(circle at 50% 24%, color-mix(in srgb, white 40%, var(--nav-brand-accent-soft)) 0%, transparent 40%),
    linear-gradient(180deg, var(--nav-brand-accent-soft) 0%, var(--nav-brand-accent) 50%, var(--nav-brand-accent-deep) 100%);
  mix-blend-mode: color;
}

#page_header.nav_component--header:not(.nav_component--public) .nav_menu--primary .nav_brand_mark::after {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    radial-gradient(circle at 50% 26%, color-mix(in srgb, white 52%, var(--nav-brand-accent-soft)) 0%, transparent 38%),
    radial-gradient(circle at 50% 50%, color-mix(in srgb, var(--nav-brand-accent) 40%, transparent) 0%, transparent 78%);
  mix-blend-mode: soft-light;
  opacity: 1;
}

#page_header.nav_component--public .nav_menu--primary .nav_label {
  display: inline;
  visibility: visible;
  opacity: 1;
  font-weight: 700;
}

.nav_icon {
  display: none;
  align-items: center;
  justify-content: center;
  font-size: var(--font-md);
  line-height: 0;
  color: inherit;
}

.nav_icon svg {
  display: block;
  width: var(--nav-icon-size);
  height: var(--nav-icon-size);
}

.nav_icon img {
  display: block;
  width: var(--nav-icon-size);
  height: var(--nav-icon-size);
  object-fit: contain;
}

.nav_icon svg * {
  fill: currentColor;
}

.nav_icon .nav_brand_mark_base--app {
  border-radius: 999px;
  box-shadow:
    0 0 0 1px color-mix(in srgb, currentColor 22%, transparent),
    0 0 14px color-mix(in srgb, currentColor 12%, transparent);
}

.nav_menu a .nav_label { line-height: 1.1; }

.nav_menu a .nav_notification_dot {
  --nav-notification-dot-color: var(--color-notification-danger, color-mix(in srgb, var(--color-danger, #d64545) 88%, #ff837a));
  position: absolute;
  inset-block-start: 0.42rem;
  inset-inline-end: 0.48rem;
  inline-size: 0.78rem;
  block-size: 0.78rem;
  min-inline-size: 0.78rem;
  min-block-size: 0.78rem;
  background: var(--nav-notification-dot-color);
  clip-path: polygon(50% 0%, 86% 14%, 100% 50%, 86% 86%, 50% 100%, 14% 86%, 0% 50%, 14% 14%);
  box-shadow:
    0 0 0 2px color-mix(in srgb, var(--surface, #ffffff) 70%, transparent),
    0 2px 8px color-mix(in srgb, var(--nav-notification-dot-color) 42%, transparent);
  pointer-events: none;
  z-index: 2;
}

.nav_icon svg path,
.nav_icon svg circle,
.nav_icon svg rect,
.nav_icon svg ellipse,
.nav_icon svg polygon,
.nav_menu a:not(.nav_language_link) svg path,
.nav_menu a:not(.nav_language_link) svg circle,
.nav_menu a:not(.nav_language_link) svg rect,
.nav_menu a:not(.nav_language_link) svg ellipse,
.nav_menu a:not(.nav_language_link) svg polygon {
  fill: currentColor;
}

/* ============================================================================
   TOP NAV: flat icon+label inline navigation
   Items show icon + label when space allows; labels collapse from the right
   first. When the window is too narrow for icon-only on one row, items wrap
   to a second row. Sign Out is pinned to the far right of the last row.
   ============================================================================ */

/* Structural: sticky keeps the header in the document flow so it can grow
   to accommodate a second row without needing a fixed margin-top on #main. */
body[data-nav-primary-position='top'] #page_header.nav_component--header {
  position: sticky;
  top: 0;
  height: auto;
  min-height: var(--nav-block-size);
  margin-bottom: 0;
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary,
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary ul {
  height: auto;
  min-height: var(--nav-block-size);
  flex-wrap: wrap;
  align-items: center;
}

/* No top-margin offset needed — sticky header is in normal flow */
body[data-nav-primary-position='top'] #main {
  margin-top: 0;
}

/* Show icons in top nav */
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_icon {
  display: inline-flex;
  flex-shrink: 0;
}

/* Show labels in top nav */
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_label {
  display: inline;
  white-space: nowrap;
  overflow: hidden;
}

/* Each item: shrinkable, never below icon width, sized to content not 100% */
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .pages {
  flex: 0 1 auto;
  width: auto;
  max-width: 8rem;
  min-width: calc(var(--nav-icon-size) + 1.25rem);
  overflow: hidden;
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .pages.nav_admin_group {
  flex: 0 0 auto;
  max-width: none;
  overflow: visible;
  z-index: 10060;
}

/* Link: icon + label side by side, clips when tight */
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary a,
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle {
  gap: 0.35rem;
  padding-inline: 0.6rem;
  overflow: hidden;
}

/* Sign Out: always pinned to far right */
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_signout {
  margin-left: auto;
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_switcher {
  position: relative;
  margin-left: auto;
  flex: 0 0 auto;
  max-width: none;
  width: auto;
  overflow: visible;
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_current {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  margin: 0.5rem;
  padding: 0;
  border: 1px solid transparent;
  border-radius: 999px;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_current:hover,
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_current:focus-visible {
  border-color: var(--panel-border);
  background-color: color-mix(in srgb, var(--color-text) 10%, transparent);
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_list {
  display: none;
  position: absolute;
  right: 0.4rem;
  top: calc(100% - 0.15rem);
  min-width: 11rem;
  border: 1px solid var(--panel-border);
  background-color: var(--color-bg);
  box-shadow: 0 8px 18px color-mix(in srgb, var(--panel-head-text) 24%, black);
  z-index: 10070;
  grid-template-columns: 1fr;
  align-items: center;
  gap: 0;
  margin: 0;
  padding: 0.3rem;
  list-style: none;
  height: auto;
  max-height: calc(100dvh - 1rem);
  overflow-y: auto;
  overscroll-behavior: contain;
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_switcher:hover .nav_language_list,
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_switcher:focus-within .nav_language_list {
  display: grid;
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_item {
  flex: 0 0 auto;
  width: 100%;
  max-width: none;
  min-width: 0;
  height: auto;
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_link {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.5rem;
  width: 100%;
  height: auto;
  padding: 0.35rem 0.45rem;
  border-radius: 0.4rem;
  border: 1px solid transparent;
  background-color: transparent;
  color: var(--color-text);
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_link:hover,
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_link:focus-visible {
  border-color: var(--panel-border);
  background-color: color-mix(in srgb, var(--color-text) 10%, transparent);
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_flag,
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_flag svg {
  width: 20px;
  height: 20px;
  display: block;
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_link svg path,
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_link svg circle,
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_link svg rect,
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_link svg ellipse,
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_link svg polygon {
  fill: revert;
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_name {
  display: inline;
  font-size: 0.9rem;
  line-height: 1.2;
  color: var(--color-text);
}

body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .nav_language_current .nav_language_name {
  display: none;
}

/* Sidebar mode: open language popover away from the nav rail so it is not clipped. */
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_switcher,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_switcher {
  position: relative;
  overflow: visible;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_current,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_current {
  display: inline-flex;
  align-items: center;
  justify-content: flex-start;
  gap: 0.45rem;
  width: 100%;
  min-height: var(--nav-block-size);
  margin: 0;
  padding-inline: 0.65rem;
  border: 0;
  border-radius: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_current .nav_language_name,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_current .nav_language_name {
  display: inline;
  visibility: visible;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_list,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_list {
  display: none;
  position: absolute;
  min-width: 11rem;
  border: 1px solid var(--panel-border);
  background-color: var(--color-bg);
  box-shadow: 0 8px 18px color-mix(in srgb, var(--panel-head-text) 24%, black);
  z-index: 10070;
  grid-template-columns: 1fr;
  align-items: center;
  gap: 0;
  margin: 0;
  padding: 0.3rem;
  list-style: none;
  height: auto;
  max-height: calc(100dvh - 1rem);
  overflow-y: auto;
  overscroll-behavior: contain;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_list {
  left: calc(100% + 0.1rem);
  top: auto;
  bottom: 0;
  transform: none;
}

body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_list {
  right: calc(100% + 0.1rem);
  top: auto;
  bottom: 0;
  transform: none;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_item,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_item {
  flex: 0 0 auto;
  width: 100%;
  max-width: none;
  min-width: 0;
  height: auto;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_link,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_link {
  display: grid;
  grid-template-columns: 20px minmax(0, 1fr);
  align-items: center;
  gap: 0.4rem;
  width: 100%;
  height: auto;
  padding: 0.35rem var(--pad-md);
  border: 1px solid transparent;
  border-radius: 0.4rem;
  text-align: left;
  background-color: transparent;
  color: var(--color-text);
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_link:hover,
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_link:focus-visible,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_link:hover,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_link:focus-visible {
  border-color: var(--color-text);
  background-color: var(--color-text);
  color: var(--color-bg);
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_link .nav_language_flag,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_link .nav_language_flag {
  justify-self: start;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_link .nav_language_name,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_link .nav_language_name {
  justify-self: end;
  width: 100%;
  text-align: right;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_switcher:hover .nav_language_list,
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_switcher:focus-within .nav_language_list,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_switcher:hover .nav_language_list,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_switcher:focus-within .nav_language_list {
  display: grid;
}

/* Collapse order: core pages (first 3) shrink last; everything else shrinks first */
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .pages:nth-child(-n+3) {
  flex-shrink: 1;
}
body[data-nav-primary-position='top'] #page_header.nav_component--header .nav_menu--primary .pages:nth-child(n+4) {
  flex-shrink: 3;
}

body[data-nav-primary-position='top'][data-nav-top-density='compact'] #page_header.nav_component--header .nav_menu--primary .nav_label {
  display: none;
  visibility: hidden;
}

body[data-nav-primary-position='top'][data-nav-top-density='compact'] #page_header.nav_component--header .nav_menu--primary .pages {
  flex: 1 0 auto;
  overflow: visible;
}

body[data-nav-primary-position='top'][data-nav-top-density='compact'] #page_header.nav_component--header .nav_menu--primary a,
body[data-nav-primary-position='top'][data-nav-top-density='compact'] #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle {
  width: calc(var(--nav-icon-size) + 0.5rem);
  min-width: calc(var(--nav-icon-size) + 0.5rem);
  max-width: calc(var(--nav-icon-size) + 0.5rem);
  padding-inline: 0;
  gap: 0;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header,
body[data-nav-primary-position='right'] #page_header.nav_component--header {
  position: fixed;
  top: 0;
  bottom: 0;
  width: var(--nav-inline-size);
  height: 100dvh;
  margin: 0;
  overflow: visible;
  background-color: var(--panel-bg, var(--color-bg, #111111));
  color: var(--panel-text, var(--color-text, #f5f5f5));
}

body[data-nav-primary-position='left'] #page_header.nav_component--header {
  left: 0;
  right: auto;
  border-right: 0;
  box-shadow: 1px 0 1px color-mix(in srgb, var(--panel-head-text) 25%, black);
}

body[data-nav-primary-position='right'] #page_header.nav_component--header {
  left: auto;
  right: 0;
  border-left: 0;
  box-shadow: -1px 0 1px color-mix(in srgb, var(--panel-head-text) 25%, black);
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary,
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary > ul,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary > ul {
  flex-direction: column;
  align-items: stretch;
  height: 100%;
  background-color: inherit;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary {
  width: 100%;
  max-width: 100%;
  overflow-y: visible;
  overflow-x: visible;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary li,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary li {
  flex: 0 0 auto;
  width: 100%;
  min-height: var(--nav-block-size);
}

body[data-nav-primary-position='left'] #page_header.nav_component--header > .nav_menu--primary > ul > li,
body[data-nav-primary-position='right'] #page_header.nav_component--header > .nav_menu--primary > ul > li {
  border-bottom: 1px solid var(--panel-border);
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary a,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary a,
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle {
  justify-content: flex-start;
  gap: 0.45rem;
  padding-inline: 0.65rem;
  text-align: left;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary li:hover a,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary li:hover a,
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary li:hover a.nav_admin_toggle,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary li:hover a.nav_admin_toggle {
  border: 0;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary li.active a,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary li.active a,
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary li.active a.nav_admin_toggle,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary li.active a.nav_admin_toggle {
  border: 0;
  border-radius: 0;
  border-top-left-radius: 0;
  border-top-right-radius: 0;
  background-color: transparent;
  color: inherit;
  box-shadow: none;
  font-weight: inherit;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary li.active:hover a,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary li.active:hover a,
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary li.active:hover a.nav_admin_toggle,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary li.active:hover a.nav_admin_toggle,
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary li.active:focus-within a,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary li.active:focus-within a,
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary li.active:focus-within a.nav_admin_toggle,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary li.active:focus-within a.nav_admin_toggle {
  background-color: transparent;
  color: inherit;
}

/* Normalize focus-within across ALL side-nav items (active and non-active).
   The global rule fires background-color: var(--color-bg) which looks incorrect
   on a sidebar. */
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary li:focus-within a,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary li:focus-within a {
  background-color: transparent;
  color: inherit;
}

/* Suppress outline for pointer/touch focus (not keyboard).
   Use :focus-visible to show a consistent ring for keyboard navigation,
   avoiding the a[href] vs a[tabindex] browser default shape difference. */
body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary a:focus:not(:focus-visible),
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary a:focus:not(:focus-visible) {
  outline: none;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary a:focus-visible,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary a:focus-visible {
  outline: 2px solid var(--color-focus-ring, var(--color-text));
  outline-offset: -2px;
}

body[data-nav-primary-position='left'] .nav_admin_popover {
  top: 0;
  left: calc(100% + 0.25rem);
}

body[data-nav-primary-position='right'] .nav_admin_popover {
  top: 0;
  right: calc(100% + 0.25rem);
  left: auto;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_label,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_label {
  display: inline;
  visibility: visible;
}

body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_icon,
body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_icon {
  display: inline-flex;
}

body[data-nav-primary-position='left'] #main {
  margin: 0 0 0 var(--nav-inline-size);
  width: calc(100% - var(--nav-inline-size));
  max-width: calc(100% - var(--nav-inline-size));
  overflow-x: clip;
  min-width: 0;
}

body[data-nav-primary-position='right'] #main {
  margin: 0 var(--nav-inline-size) 0 0;
  width: calc(100% - var(--nav-inline-size));
  max-width: calc(100% - var(--nav-inline-size));
  overflow-x: clip;
  min-width: 0;
}

body[data-nav-primary-position='left'] #page_footer {
  margin-left: var(--nav-inline-size);
  width: calc(100% - var(--nav-inline-size));
  max-width: calc(100% - var(--nav-inline-size));
  box-sizing: border-box;
}

body[data-nav-primary-position='right'] #page_footer {
  margin-right: var(--nav-inline-size);
  width: calc(100% - var(--nav-inline-size));
  max-width: calc(100% - var(--nav-inline-size));
  box-sizing: border-box;
}

/* Keep content wrappers from forcing horizontal overflow in sidebar mode. */
body[data-nav-primary-position='left'] #main .main-content,
body[data-nav-primary-position='right'] #main .main-content {
  display: block;
  width: 100%;
  min-width: 0;
  margin: 0;
}

/* Calendar screen mode override: remove all chrome and reclaim full content width. */
body.calendar-screenmode-minimal #page_header.nav_component--header,
body.calendar-screenmode-minimal #page_footer {
  display: none;
}

body.calendar-screenmode-minimal #main,
body.calendar-screenmode-minimal[data-nav-primary-position='left'] #main,
body.calendar-screenmode-minimal[data-nav-primary-position='right'] #main {
  margin: 0;
  padding: 0;
  width: 100%;
  max-width: 100%;
}

body.calendar-screenmode-minimal #calendar-v2-root.panel {
  margin: 0;
  padding: 0;
}

/* ============================================================================
   SIDEBAR STATE SYSTEM: collapsed | peek | pinned
   Motion: transform-based, GPU-accelerated, no layout thrash
   Layout: overlay for peek, push for pinned
   ============================================================================ */

:root {
  --nav-icon-size: 20px;      /* matches .nav_icon svg dimensions */
  --nav-edge-size: 48px;
  --nav-collapsed-strip-size: 48px;
  --nav-transition: transform .22s cubic-bezier(.2,.8,.2,1);
  --nav-margin-transition: margin  .22s cubic-bezier(.2,.8,.2,1),
                           width   .22s cubic-bezier(.2,.8,.2,1),
                           max-width .22s cubic-bezier(.2,.8,.2,1);
}

body[data-nav-primary-position='left'],
body[data-nav-primary-position='right'] {
  --nav-icon-size: 20px;
  --nav-collapsed-strip-size: 48px;
}

.sidebar_toggle_accessible {
  position: fixed;
  width: 1px;
  height: 1px;
  padding: 0;
  overflow: hidden;
  clip: rect(1px 1px 1px 1px);
  clip: rect(1px, 1px, 1px, 1px);
  white-space: nowrap;
  border: 0;
  z-index: 10002;
}

body[data-nav-primary-position='left'] .sidebar_toggle_accessible {
  top: 0;
  left: 0;
}

body[data-nav-primary-position='right'] .sidebar_toggle_accessible {
  top: 0;
  right: 0;
}

body[data-nav-primary-position='left'] .sidebar_toggle_accessible:focus,
body[data-nav-primary-position='left'] .sidebar_toggle_accessible:focus-visible,
body[data-nav-primary-position='right'] .sidebar_toggle_accessible:focus,
body[data-nav-primary-position='right'] .sidebar_toggle_accessible:focus-visible {
  width: auto;
  height: auto;
  clip: auto;
  overflow: visible;
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--panel-head-bg);
  border-radius: 0.375rem;
  background-color: var(--panel-head-text);
  color: var(--color-text-inverse);
  box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.2);
}

body[data-nav-primary-position='left'] .sidebar_toggle_accessible:focus,
body[data-nav-primary-position='left'] .sidebar_toggle_accessible:focus-visible {
  top: 0.5rem;
  left: 0.5rem;
}

body[data-nav-primary-position='right'] .sidebar_toggle_accessible:focus,
body[data-nav-primary-position='right'] .sidebar_toggle_accessible:focus-visible {
  top: 0.5rem;
  right: 0.5rem;
}

/* Sidebar: GPU-accelerated transform */
body[data-nav-primary-position='left']  #page_header.nav_component--header,
body[data-nav-primary-position='right'] #page_header.nav_component--header {
  transition: var(--nav-transition);
  will-change: transform;
}

/* Main content: margin transitions */
body[data-nav-primary-position='left']  #main,
body[data-nav-primary-position='right'] #main {
  transition: var(--nav-margin-transition);
}

/* Pre-hydration: apply server-provided sidebar state before JS boot. */
body[data-nav-primary-position='left'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header {
  transform: translateX(calc(-100% + var(--nav-collapsed-strip-size)));
}

body[data-nav-primary-position='right'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header {
  transform: translateX(calc(100% - var(--nav-collapsed-strip-size)));
}

body[data-nav-primary-position='left'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #main,
body[data-nav-primary-position='left'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_footer {
  margin-left: var(--nav-collapsed-strip-size);
  width: calc(100% - var(--nav-collapsed-strip-size));
  max-width: calc(100% - var(--nav-collapsed-strip-size));
}

body[data-nav-primary-position='right'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #main,
body[data-nav-primary-position='right'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_footer {
  margin-right: var(--nav-collapsed-strip-size);
  width: calc(100% - var(--nav-collapsed-strip-size));
  max-width: calc(100% - var(--nav-collapsed-strip-size));
}

body[data-nav-primary-position='left'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary .nav_label,
body[data-nav-primary-position='right'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary .nav_label {
  display: none;
  visibility: hidden;
}

body[data-nav-primary-position='left'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary li.nav_language_switcher,
body[data-nav-primary-position='right'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary li.nav_language_switcher {
  width: var(--nav-collapsed-strip-size);
  min-width: var(--nav-collapsed-strip-size);
  max-width: var(--nav-collapsed-strip-size);
}

body[data-nav-primary-position='left'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary .nav_language_current,
body[data-nav-primary-position='right'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary .nav_language_current {
  width: var(--nav-collapsed-strip-size);
  min-width: var(--nav-collapsed-strip-size);
  max-width: var(--nav-collapsed-strip-size);
  justify-content: center;
  padding: 0;
}

body[data-nav-primary-position='left'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary .nav_language_current .nav_language_name,
body[data-nav-primary-position='right'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary .nav_language_current .nav_language_name {
  display: none;
  visibility: hidden;
}

body[data-nav-primary-position='left'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary,
body[data-nav-primary-position='left'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary > ul {
  align-items: flex-end;
}

body[data-nav-primary-position='right'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary,
body[data-nav-primary-position='right'][data-nav-initial-state='collapsed']:not(.nav-ready):not(.calendar-screenmode-minimal) #page_header.nav_component--header .nav_menu--primary > ul {
  align-items: flex-start;
}

/* ---- COLLAPSED: slide to thin icon strip ---- */

body.nav-collapsed[data-nav-primary-position='left']  #page_header.nav_component--header {
  transform: translateX(calc(-100% + var(--nav-collapsed-strip-size)));
  cursor: e-resize;
}

body.nav-collapsed[data-nav-primary-position='right'] #page_header.nav_component--header {
  transform: translateX(calc(100% - var(--nav-collapsed-strip-size)));
  cursor: e-resize;
}

/* Force icons visible in the strip (overrides any base display:none rules) */
body.nav-collapsed #page_header.nav_component--header .nav_icon {
  display: inline-flex;
  visibility: visible;
  opacity: 1;
  color: inherit;
}

body.nav-collapsed #page_header.nav_component--header .nav_icon svg,
body.nav-collapsed #page_header.nav_component--header .nav_icon svg path,
body.nav-collapsed #page_header.nav_component--header .nav_icon svg circle,
body.nav-collapsed #page_header.nav_component--header .nav_icon svg rect,
body.nav-collapsed #page_header.nav_component--header .nav_icon svg ellipse,
body.nav-collapsed #page_header.nav_component--header .nav_icon svg polygon {
  fill: currentColor;
}

body.nav-collapsed #page_header.nav_component--header .nav_icon svg * {
  fill: currentColor;
}

/* Hide labels in collapsed mode with high specificity to beat side-nav defaults. */
body.nav-collapsed[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_label,
body.nav-collapsed[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_label {
  position: absolute;
  inline-size: 1px;
  block-size: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0 0 0 0);
  clip-path: inset(50%);
  white-space: nowrap;
  display: none;
  visibility: hidden;
}

body.nav-collapsed[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary,
body.nav-collapsed[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary > ul {
  align-items: flex-end;
}

body.nav-collapsed[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary,
body.nav-collapsed[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary > ul {
  align-items: flex-start;
}

body.nav-collapsed #page_header.nav_component--header .nav_menu--primary a:not(.nav_language_link),
body.nav-collapsed #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle {
  flex: 0 0 var(--nav-collapsed-strip-size);
  width: var(--nav-collapsed-strip-size);
  min-width: var(--nav-collapsed-strip-size);
  max-width: var(--nav-collapsed-strip-size);
  justify-content: center;
  gap: 0;
  padding: 0;
  overflow: hidden;
  font-size: 0;
}

body.nav-collapsed #page_header.nav_component--header .nav_menu--primary a .nav_icon,
body.nav-collapsed #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle .nav_icon {
  font-size: var(--font-md);
}

body.nav-collapsed #page_header.nav_component--header .nav_menu--primary li {
  pointer-events: none;
  flex: 0 0 var(--nav-collapsed-strip-size);
  width: var(--nav-collapsed-strip-size);
  min-width: var(--nav-collapsed-strip-size);
  max-width: var(--nav-collapsed-strip-size);
}

body.nav-collapsed #page_header.nav_component--header .nav_menu--primary li.nav_language_switcher,
body.nav-collapsed #page_header.nav_component--header .nav_menu--primary li.nav_language_switcher .nav_language_current,
body.nav-collapsed #page_header.nav_component--header .nav_menu--primary li.nav_language_switcher .nav_language_list {
  pointer-events: auto;
}

body.nav-collapsed[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_current,
body.nav-collapsed[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_current {
  width: var(--nav-collapsed-strip-size);
  min-width: var(--nav-collapsed-strip-size);
  max-width: var(--nav-collapsed-strip-size);
  justify-content: center;
  padding: 0;
}

body.nav-collapsed #page_header.nav_component--header .nav_menu--primary .nav_language_current .nav_language_name {
  display: none;
  visibility: hidden;
}

/* In collapsed mode, constrain link hitboxes to the visible strip so centered icons stay on-screen. */
body.nav-collapsed[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary a,
body.nav-collapsed[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle {
  width: var(--nav-collapsed-strip-size);
  margin-left: 0;
  margin-right: 0;
}

body.nav-collapsed[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary a,
body.nav-collapsed[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle {
  width: var(--nav-collapsed-strip-size);
  margin-left: 0;
  margin-right: 0;
}

/* Push content to clear the icon strip */
body.nav-collapsed[data-nav-primary-position='left'] #main,
body.nav-collapsed[data-nav-primary-position='left'] #page_footer {
  margin-left: var(--nav-collapsed-strip-size);
  width: calc(100% - var(--nav-collapsed-strip-size));
  max-width: calc(100% - var(--nav-collapsed-strip-size));
}

body.nav-collapsed[data-nav-primary-position='right'] #main,
body.nav-collapsed[data-nav-primary-position='right'] #page_footer {
  margin-right: var(--nav-collapsed-strip-size);
  width: calc(100% - var(--nav-collapsed-strip-size));
  max-width: calc(100% - var(--nav-collapsed-strip-size));
}

/* ---- PEEK: full sidebar as overlay (no content reflow) ---- */

body.nav-peek[data-nav-primary-position='left']  #page_header.nav_component--header {
  transform: translateX(0);
  box-shadow: 2px 0 16px rgba(0,0,0,.18);
  z-index: 10001;
}

body.nav-peek[data-nav-primary-position='right'] #page_header.nav_component--header {
  transform: translateX(0);
  box-shadow: -2px 0 16px rgba(0,0,0,.18);
  z-index: 10001;
}

/* ---- PINNED: push model, default translateX(0), base layout handles margin ---- */

body:not(.nav-collapsed)[data-nav-primary-position='left'] #page_header.nav_component--header,
body:not(.nav-collapsed)[data-nav-primary-position='right'] #page_header.nav_component--header {
  cursor: w-resize;
}

/* Calendar screen mode: remove the entire sidebar shell, not just nav list items. */
body.calendar-screenmode-minimal #page_header.nav_component--header,
body.calendar-screenmode-minimal .sidebar_toggle_accessible {
  display: none !important;
}

/* Cancel edge-strip layout offsets while calendar minimal mode is active. */
body.calendar-screenmode-minimal[data-nav-primary-position='left'] #main,
body.calendar-screenmode-minimal[data-nav-primary-position='left'] #page_footer,
body.calendar-screenmode-minimal[data-nav-primary-position='right'] #main,
body.calendar-screenmode-minimal[data-nav-primary-position='right'] #page_footer {
  margin-left: 0;
  margin-right: 0;
  width: 100%;
  max-width: 100%;
}

body:not(.nav-collapsed) #page_header.nav_component--header a,
body:not(.nav-collapsed) #page_header.nav_component--header button,
body:not(.nav-collapsed) #page_header.nav_component--header [role='button'] {
  cursor: pointer;
}

@media (max-width: 768px) {
  .nav_component--footer {
    min-block-size: 0;
    align-content: start;
    padding-block: 0.55rem;
  }

  .nav_component--footer .footer_copyright {
    margin-top: 0.4rem;
  }

  .nav_component--footer .footer_soc2_badge_wrap {
    justify-content: center;
  }
}

@media (max-width: 900px) {
  body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle,
  body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle {
    padding-inline: 0.65rem !important;
    padding-block: 0 !important;
    min-height: var(--nav-block-size) !important;
    line-height: 1.1 !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 0.45rem !important;
  }

  body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary .nav_language_current,
  body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary .nav_language_current {
    padding-inline: 0.65rem !important;
    padding-block: 0 !important;
    min-height: var(--nav-block-size) !important;
    line-height: 1.1 !important;
    align-items: center !important;
    justify-content: flex-start !important;
    gap: 0.45rem !important;
  }
}

@media (max-width: 450px) {
  body[data-nav-primary-position='left'],
  body[data-nav-primary-position='right'] {
    --nav-inline-size: 10rem;
    --nav-block-size: 1.72rem;
    --nav-collapsed-strip-size: 36px;
    --nav-icon-size: 15px;
  }

  body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary a,
  body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary a,
  body[data-nav-primary-position='left'] #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle,
  body[data-nav-primary-position='right'] #page_header.nav_component--header .nav_menu--primary a.nav_admin_toggle {
    gap: 0.25rem;
    padding-inline: 0.35rem;
  }

  .nav_component--footer {
    padding: 0.6rem 0.45rem 0.75rem;
  }

  .nav_component--footer .footer_copyright {
    margin-top: 0.45rem;
    font-size: 0.72rem;
    max-width: 30ch;
  }

  /* On narrow screens, reserve only the icon strip so content never sits under the rail. */
  body[data-nav-primary-position='left'] #main,
  body[data-nav-primary-position='left'] #page_footer,
  body.nav-collapsed[data-nav-primary-position='left'] #main,
  body.nav-collapsed[data-nav-primary-position='left'] #page_footer {
    margin-left: var(--nav-collapsed-strip-size) !important;
    margin-right: 0 !important;
    width: calc(100% - var(--nav-collapsed-strip-size)) !important;
    max-width: calc(100% - var(--nav-collapsed-strip-size)) !important;
  }

  body[data-nav-primary-position='right'] #main,
  body[data-nav-primary-position='right'] #page_footer,
  body.nav-collapsed[data-nav-primary-position='right'] #main,
  body.nav-collapsed[data-nav-primary-position='right'] #page_footer {
    margin-right: var(--nav-collapsed-strip-size) !important;
    margin-left: 0 !important;
    width: calc(100% - var(--nav-collapsed-strip-size)) !important;
    max-width: calc(100% - var(--nav-collapsed-strip-size)) !important;
  }
}

/* ---- ACCESSIBILITY: screen reader utility ---- */

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

/* ---- REDUCED MOTION ---- */

@media (prefers-reduced-motion: reduce) {
  body[data-nav-primary-position='left']  #page_header.nav_component--header,
  body[data-nav-primary-position='right'] #page_header.nav_component--header,
  body[data-nav-primary-position='left']  #main,
  body[data-nav-primary-position='right'] #main {
    transition-duration: 1ms;
  }
}
