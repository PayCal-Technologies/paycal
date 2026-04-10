<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - Shared Utility CSS
 *
 * Utility helper classes and shared tab presentation styles.
 */

/* CONSTRAINTS */
.w10  { width: 10%; }
.w20  { width: 20%; }
.w30  { width: 30%; }
.w33  { width: 33%; min-width: 33%; max-width: 33%; }
.w40  { width: 40%; }
.w50  { width: 50%; }
.w60  { width: 60%; }
.w66  { width: 65%; }
.w70  { width: 70%; }
.w75  { width: 75%; }
.w80  { width: 80%; }
.w90  { width: 90%; }
.w100 { width: 100%; }

/* MARGIN */
.mar_xs   { margin: var(--mar-xs); }
.mar_sm   { margin: var(--mar-sm); }
.mar_md   { margin: var(--mar-md); }
.mar_lg   { margin: var(--mar-lg); }
.mar_wide { margin-inline: var(--mar-md); }

/* PADDING */
.pad_xs   { padding: var(--pad-xs); }
.pad_sm   { padding: var(--pad-sm); }
.pad_md   { padding: var(--pad-md); }
.pad_lg   { padding: var(--pad-lg); }
.pad_wide { padding-inline: var(--pad-md); }

/* FLEX RELATED */
.flex            { display: flex; width: 100%; }
.f_baseline      { align-items: baseline; }
.f_column        { display: flex; flex-direction: column; }
.f_first         { order: -1; }
.f_input         { display: block; width: 100%; }
.f_last          { order: 999; }
.f_just_start    { justify-content: flex-start; }
.f_just_center   { justify-content: center; }
.f_just_right    { justify-content: flex-end; }
.f_nowrap        { flex-wrap: nowrap; }
.f_space_between { justify-content: space-between; }
.f_space_around  { justify-content: space-around; }
.f_space_evenly  { justify-content: space-evenly; }
.f_no_grow       { flex-grow: 0; }
.f_start         { align-items: start; }
.f_stretch       { align-items: stretch; }
.f_wrap          { flex-wrap: wrap; }
.f_end           { align-content: flex-end; }

/* DISPLAY UTILITIES */
.hidden {
  display: none;
}

.display-flex {
  display: flex;
}

.display-block {
  display: block;
}

.display-inline-block {
  display: inline-block;
}

/* SIDEBAR UTILITIES */
.sidebar {
  position: sticky;
  flex: 0 0 8%;
  width: 8%;
  height: fit-content;
  padding: var(--mar-md);
}

.sidebar nav ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

.sidebar nav ul li {
  margin: var(--mar-sm) 0;
}

.sidebar nav ul li a {
  display: block;
  padding: var(--mar-sm) var(--mar-md);
  text-decoration: none;
  border-radius: 4px;
  transition: background-color 0.2s ease;
}

.sidebar nav ul li a:hover {
  background-color: var(--panel-text);
  color: var(--panel-bg);
  transition: background-color 0.2s ease;
}

.sidebar_divider {
  height: 1px;
  margin: var(--mar-md) 0;
  border: none;
  background-color: var(--border-inset-color);
}

.sidebar_actions {
  display: flex;
  flex-direction: column;
  gap: var(--gap-md);
}

.btn_sidebar {
  flex-grow: 0;
  width: 100%;
  padding: var(--pad-sm) var(--pad-md);
  font-size: var(--font-sm);
  text-align: left;
  white-space: normal;
}

/* MAIN CONTENT PANEL (paired with sidebar) */
.main {
  display: flex;
  flex-wrap: wrap;
  flex: 0 1 84%;
  width: 84%;
  gap: var(--gap-md);
  overflow-x: hidden;
}

/* TABBED CONTENT */
[data-tab-content] {
  display: none;
}

.active[data-tab-content] {
  display: flex;
}

.tabs {
  position: relative;
  display: flex;
  flex-wrap: nowrap;
  justify-content: flex-start;
  gap: var(--pad-sm);
  width: 100%;
  margin: 0 0 var(--mar-sm) 0;
  padding: var(--pad-sm);
  border: var(--border-size) solid var(--panel-border);
  border-radius: var(--border-radius);
  background-color: var(--panel-bg);
  backdrop-filter: blur(10px);
  list-style-type: none;
  box-shadow: 0 0.25rem 0.25rem rgba(0, 0, 0, 0.750);
  overflow-x: auto;
}

.tab {
  flex-shrink: 0;
  min-width: max-content;
  padding: var(--pad-sm) var(--pad-md);
  font-size: var(--font-md);
  letter-spacing: 0.1rem;
  text-align: center;
  white-space: nowrap;
  cursor: pointer;
}

.tab.active {
  border-color: var(--button-border-active);
  border-bottom: var(--border-bottom);
  background-color: var(--btn-selected-back);
  color: var(--button-text);
}

.tab:hover {
  background-color: var(--color-primary);
  transition: background-color var(--short-transition) ease;
}

/* Vertical Tabs */
.vertical_tabs {
  display: flex;
  flex-direction: column;
}

.vertical_tabs input[type="radio"] {
  display: none;
}

.vertical_tabs .tab_label {
  display: block;
  margin-bottom: var(--mar-xs);
  padding: var(--pad-sm);
  border: var(--border-size) solid var(--panel-border);
  background-color: var(--panel-bg);
  text-align: center;
  cursor: pointer;
}

.vertical_tabs input[type="radio"]:checked + .tab_label {
  border-color: var(--button-border-active);
  background-color: var(--btn-selected-back);
  color: var(--button-text);
}

.tab_content {
  display: none;
}

/* RESPONSIVE: Mobile layout adjustments */
@media (max-width: 900px) {
  .main {
    width: 100%;
    flex: 1 1 100%;
  }

  .w33, .w50, .w66, .w75, .w100 {
    flex-grow: 1;
    max-width: stretch;
    width: 99%;
    margin: auto;
  }

}
