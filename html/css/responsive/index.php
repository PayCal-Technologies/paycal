<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/* Responsive overrides extracted from common stylesheet. */

/* Ultra-compact mobile heading overrides */
@media (max-width: 450px) {
  h1, h2, h3, h4, h5, h6 {
    font-size: 0.75rem !important;
  }

  input,
  select,
  textarea,
  button,
  .btn,
  [role="button"],
  a,
  label,
  p,
  .nav_menu a,
  .nav_menu .nav_label {
    font-size: 0.75rem !important;
  }
}

@media (max-width: 900px) {
  :root {
    --dialog-edge-top-size: 6px;
    --dialog-edge-right-size: 6px;
    --dialog-edge-bottom-size: 6px;
    --dialog-edge-left-size: 6px;
  }

  .site_create_fields {
    flex-wrap: wrap;
  }

  .site_create_fields .form_group {
    flex: 1 1 45%;
  }
}

@media (max-width: 640px) {
  :root {
    --dialog-edge-top-size: 4px;
    --dialog-edge-right-size: 4px;
    --dialog-edge-bottom-size: 4px;
    --dialog-edge-left-size: 4px;
  }

  dialog {
    top: max(0.5rem, 4dvh);
    width: calc(100vw - 0.5rem);
    max-width: calc(100vw - 0.5rem);
    max-height: 90dvh;
  }

  .modal_title {
    padding: 0 2.2rem;
  }

  #modal_help .modal_content {
    flex-direction: column;
  }

  #modal_help .keyboard_shortcuts_layout {
    grid-template-columns: 1fr;
  }

  #modal_help .keyboard_shortcuts_row {
    align-items: flex-start;
    grid-template-columns: 1fr;
    gap: 0.45rem;
  }

  #modal_help .keyboard_shortcuts_keys {
    justify-content: flex-start;
    text-align: left;
    flex-wrap: wrap;
  }
}

/* Shared responsive styles */
@media only screen and (max-width: 900px) {
  header:not(.nav_component--header) .nav_label, .small_hidden {
    display: none;
    visibility: hidden;
  }

  #page_header nav.nav_menu ul li.desktop {
    display: none;
    visibility: hidden;
  }

  .nav_menu a {
    font-size: 1rem;
    padding: 0;
  }
}

@media only screen and (max-width: 900px) {
  header:not(.nav_component--header) .nav_label,
  header:not(.nav_component--header) [data-help-trigger="true"],
  header:not(.nav_component--header) #current_time,
  header:not(.nav_component--header) .desktop,
  header:not(.nav_component--header) time,
  header:not(.nav_component--header) .list_head,
  header:not(.nav_component--header) .small_hidden {
    display: none;
    visibility: hidden;
  }

  #page_header:not(.nav_component--header) nav.nav_menu ul {
    display: flex;
    justify-content: space-between;
    align-items: center;
    list-style: none;
  }

  #page_header:not(.nav_component--header) nav.nav_menu li {
    flex: 1 1 20%;
    text-align: center;
    height: 2rem;
  }

  #profile_dropdown li{
    margin: var(--mar-xs) 0;
    padding: var(--pad-xs);
  }

  body:has(#page_header.nav_component--public) .main-content {
    display: flex;
    margin: 3rem 0 0 0;
    padding: 0;
  }

  body:has(#page_header.nav_component--header) #main .main-content {
    margin: 0;
    padding: 0;
  }

  a {
    margin: 0;
    padding: 0;
    cursor: pointer;
  }

  ul, li {
    margin: 0;
    padding: 0;
  }

  .btn, button:not(#profile_button) {
    margin: 0;
    padding: 0;
    font-size: 1rem;
  }

  .modal_footer .btn,
  .modal_footer button:not(#profile_button) {
    font-size: 1rem;
  }

  .panel {
    width: 100%;
    margin: 0;
    padding: var(--pad-sm);
  }

  p { padding: var(--pad-xs); }
  .pad_md { padding: 2px;  }
  .pad_lg { padding: 4px;  }
  .mar_xs, .mar_sm, .mar_wide { margin: 0;  }
  .mar_md { margin: 2px; }
  .mar_lg { margin: 4px; }

  .nav_menu a .nav_label { font-size: 1rem; }

  footer nav.nav_menu ul {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: var(--gap-lg, 2rem);
    height: auto;
  }

  footer nav.nav_menu li {
    flex: none;
    height: auto;
  }

  footer nav.nav_menu a {
    display: block;
    padding: var(--pad-sm) var(--pad-md);
    justify-content: center;
  }

  .status {
    width: fit-content;
    max-width: calc(100vw - (var(--pad-lg) * 2));
    padding: var(--pad-xs);
    font-size: var(--font-md);
  }

  form {
    width: stretch;
    max-width: stretch;
    margin: 0;
    padding: 0;
  }

  input[type="checkbox"] {
    margin-right: 0.5em;
  }

  label, input, select {
    display: flex;
    align-items: center;
    font-size: 1rem;
  }

  /* Global compact baseline for dense mobile/tablet layout. */
  h1, h2, h3, h4, h5, h6 {
    margin: 0 !important;
    padding: 0 !important;
    font-size: 1rem !important;
    line-height: 1.1;
  }

  input,
  select,
  textarea,
  button,
  .btn,
  [role="button"] {
    margin: 0 !important;
    padding: 0 !important;
    font-size: 1rem !important;
    line-height: 1.2;
    min-height: 0;
  }

  body[data-nav-primary-position='left']:has(#page_header.nav_component--public) #page_header.nav_component--public,
  body[data-nav-primary-position='right']:has(#page_header.nav_component--public) #page_header.nav_component--public {
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    height: var(--nav-block-size);
    min-height: var(--nav-block-size);
    transform: none;
    margin: 0;
  }

  body[data-nav-primary-position='left']:has(#page_header.nav_component--public) #page_header.nav_component--public .nav_menu--primary,
  body[data-nav-primary-position='right']:has(#page_header.nav_component--public) #page_header.nav_component--public .nav_menu--primary,
  body[data-nav-primary-position='left']:has(#page_header.nav_component--public) #page_header.nav_component--public .nav_menu--primary > ul,
  body[data-nav-primary-position='right']:has(#page_header.nav_component--public) #page_header.nav_component--public .nav_menu--primary > ul {
    flex-direction: row;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: var(--nav-block-size);
    min-height: var(--nav-block-size);
  }

  body[data-nav-primary-position='left']:has(#page_header.nav_component--public) #page_header.nav_component--public .nav_menu--primary li,
  body[data-nav-primary-position='right']:has(#page_header.nav_component--public) #page_header.nav_component--public .nav_menu--primary li {
    flex: 0 1 auto;
    width: auto;
    min-width: 0;
    max-width: none;
    min-height: var(--nav-block-size);
    pointer-events: auto;
  }

  body[data-nav-primary-position='left']:has(#page_header.nav_component--public) #page_header.nav_component--public .nav_menu--primary a,
  body[data-nav-primary-position='right']:has(#page_header.nav_component--public) #page_header.nav_component--public .nav_menu--primary a {
    width: auto;
    min-width: 0;
    max-width: none;
    gap: 0.45rem;
    padding-inline: 0.55rem;
    font-size: 1rem;
  }

  body[data-nav-primary-position='left']:has(#page_header.nav_component--public) #page_header.nav_component--public .nav_menu--primary .nav_label,
  body[data-nav-primary-position='right']:has(#page_header.nav_component--public) #page_header.nav_component--public .nav_menu--primary .nav_label {
    display: inline;
    visibility: visible;
    position: static;
    clip: auto;
    clip-path: none;
    width: auto;
    height: auto;
    margin: 0;
    white-space: nowrap;
  }

  body[data-nav-primary-position='left']:has(#page_header.nav_component--public) #main,
  body[data-nav-primary-position='left']:has(#page_header.nav_component--public) #page_footer {
    margin-left: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
  }

  body[data-nav-primary-position='right']:has(#page_header.nav_component--public) #main,
  body[data-nav-primary-position='right']:has(#page_header.nav_component--public) #page_footer {
    margin-right: 0 !important;
    width: 100% !important;
    max-width: 100% !important;
  }
}

@media only screen and (max-width: 480px) {
  footer nav.nav_menu ul {
    grid-template-columns: 1fr;
  }
}

@media only screen and (max-width: 450px) {
  @keyframes navLanguageTrayDrop {
    from {
      opacity: 0;
      transform: translateY(-0.45rem) scale(0.985);
    }
    to {
      opacity: 1;
      transform: translateY(0) scale(1);
    }
  }

  body[data-nav-primary-position='left'] #page_header.nav_component--public,
  body[data-nav-primary-position='right'] #page_header.nav_component--public {
    overflow: visible;
  }

  body[data-nav-primary-position='left'] #page_header.nav_component--public .nav_menu--primary .nav_language_switcher,
  body[data-nav-primary-position='right'] #page_header.nav_component--public .nav_menu--primary .nav_language_switcher {
    position: relative;
    overflow: visible;
  }

  body[data-nav-primary-position='left'] #page_header.nav_component--public .nav_menu--primary .nav_language_current,
  body[data-nav-primary-position='right'] #page_header.nav_component--public .nav_menu--primary .nav_language_current {
    width: auto;
    min-width: 2rem;
    height: 2rem;
    margin: 0.15rem 0.25rem;
    padding: 0 0.45rem;
    border-radius: 999px;
    border: 1px solid transparent;
  }

  body[data-nav-primary-position='left'] #page_header.nav_component--public .nav_menu--primary .nav_language_current:hover,
  body[data-nav-primary-position='left'] #page_header.nav_component--public .nav_menu--primary .nav_language_current:focus-visible,
  body[data-nav-primary-position='right'] #page_header.nav_component--public .nav_menu--primary .nav_language_current:hover,
  body[data-nav-primary-position='right'] #page_header.nav_component--public .nav_menu--primary .nav_language_current:focus-visible {
    border-color: var(--panel-border);
    background-color: color-mix(in srgb, var(--color-text) 10%, transparent);
  }

  body[data-nav-primary-position='left'] #page_header.nav_component--public .nav_menu--primary .nav_language_list,
  body[data-nav-primary-position='right'] #page_header.nav_component--public .nav_menu--primary .nav_language_list {
    display: flex !important;
    flex-direction: row;
    align-items: center;
    gap: 0.3rem;
    position: absolute;
    top: calc(100% + 0.25rem);
    right: 0.25rem;
    left: auto;
    min-width: 0;
    width: min(calc(100vw - 0.5rem), 20rem);
    max-width: calc(100vw - 0.5rem);
    margin: 0;
    padding: 0.3rem;
    list-style: none;
    border: 1px solid var(--panel-border);
    border-radius: 0.55rem;
    background-color: var(--color-bg);
    box-shadow: 0 8px 18px color-mix(in srgb, var(--panel-head-text) 24%, black);
    overflow-x: auto;
    overflow-y: hidden;
    overscroll-behavior-x: contain;
    transform: translateY(-0.35rem);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transform-origin: top right;
    transition: transform 180ms ease, opacity 180ms ease;
    z-index: 10090;
  }

  body[data-nav-primary-position='left'] #page_header.nav_component--public .nav_menu--primary .nav_language_switcher:hover .nav_language_list,
  body[data-nav-primary-position='left'] #page_header.nav_component--public .nav_menu--primary .nav_language_switcher:focus-within .nav_language_list,
  body[data-nav-primary-position='right'] #page_header.nav_component--public .nav_menu--primary .nav_language_switcher:hover .nav_language_list,
  body[data-nav-primary-position='right'] #page_header.nav_component--public .nav_menu--primary .nav_language_switcher:focus-within .nav_language_list {
    transform: translateY(0);
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    animation: navLanguageTrayDrop 180ms cubic-bezier(0.22, 0.61, 0.36, 1) both;
  }

  body[data-nav-primary-position='left'] #page_header.nav_component--public .nav_menu--primary .nav_language_item,
  body[data-nav-primary-position='right'] #page_header.nav_component--public .nav_menu--primary .nav_language_item {
    flex: 0 0 auto;
    width: auto;
    min-width: 0;
    max-width: none;
    height: auto;
    min-height: 0;
  }

  body[data-nav-primary-position='left'] #page_header.nav_component--public .nav_menu--primary .nav_language_link,
  body[data-nav-primary-position='right'] #page_header.nav_component--public .nav_menu--primary .nav_language_link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: auto;
    min-width: 2rem;
    height: 2rem;
    padding: 0.2rem 0.35rem;
    border: 1px solid transparent;
    border-radius: 999px;
    white-space: nowrap;
  }

  body[data-nav-primary-position='left'] #page_header.nav_component--public .nav_menu--primary .nav_language_name,
  body[data-nav-primary-position='right'] #page_header.nav_component--public .nav_menu--primary .nav_language_name {
    display: none;
  }

  /* Force consistent hidden->drop-open tray behavior for public mobile header. */
  #page_header.nav_component--public .nav_language_switcher > .nav_language_list {
    display: flex !important;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    gap: 0.3rem;
    position: absolute;
    top: calc(100% + 0.25rem);
    right: 0.25rem;
    left: auto;
    width: min(calc(100vw - 0.5rem), 20rem);
    max-width: calc(100vw - 0.5rem);
    min-width: 0;
    max-height: 2.8rem;
    margin: 0;
    padding: 0.3rem;
    overflow-x: auto;
    overflow-y: hidden;
    border: 1px solid var(--panel-border);
    border-radius: 0.55rem;
    background-color: var(--color-bg);
    box-shadow: 0 8px 18px color-mix(in srgb, var(--panel-head-text) 24%, black);
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
    transform: translateY(-0.35rem) scale(0.99) !important;
    transform-origin: top right;
    transition: transform 180ms ease, opacity 180ms ease, visibility 180ms linear;
    z-index: 10090;
  }

  #page_header.nav_component--public .nav_language_switcher:hover > .nav_language_list,
  #page_header.nav_component--public .nav_language_switcher:focus-within > .nav_language_list {
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
    transform: translateY(0) scale(1) !important;
    animation: navLanguageTrayDrop 180ms cubic-bezier(0.22, 0.61, 0.36, 1) both;
  }
}

@media (max-width: 640px) {
  .verification-banner-content {
    flex-direction: column;
    text-align: center;
  }

  .banner-actions {
    width: 100%;
  }

  .resend-verification-btn {
    width: 100%;
  }
}

@media (max-width: 450px) and (prefers-reduced-motion: reduce) {
  body[data-nav-primary-position='left'] #page_header.nav_component--public .nav_menu--primary .nav_language_list,
  body[data-nav-primary-position='right'] #page_header.nav_component--public .nav_menu--primary .nav_language_list {
    transition: none;
    animation: none;
  }
}
