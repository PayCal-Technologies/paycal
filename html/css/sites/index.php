<?php declare(strict_types=1);

namespace PayCal\Domain;

require_once __DIR__.'/../../config.php';
if (headers_sent() === false) {
  header('Content-type: text/css');
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
?>
/**
 * PayCal - Sites Page Styles
 * 
 * Extracted from main CSS index.php
 * Date: March 1, 2026
 */

/* Profile-style page wrapper contract for sites layout. */
#main:has(.sites_main_container) {
  display: flex;
  flex-direction: column;
  gap: clamp(1rem, 2vw, 1.6rem);
}

#main:has(.sites_main_container) > .sites_main_container {
  width: min(80vw, 1240px);
  margin-left: auto;
  margin-right: auto;
  flex-direction: column;
  align-items: flex-start;
  gap: clamp(0.9rem, 1.8vw, 1.45rem);
}

#main:has(.sites_main_container) > .sites_main_container > .f_column {
  width: 100%;
  gap: clamp(0.9rem, 1.4vw, 1.2rem);
}

#main:has(.sites_main_container) section.panel {
  padding: clamp(1rem, 2vw, 1.5rem);
}

/* SITES GRID */
.sites_grid .list_item {
  padding: var(--pad-xs);
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.sites_grid_body .list_item.row_hover {
  background-color: rgba(0, 188, 212, 0.15);
}

/* ========================================================================== */
/* Orphaned Work Recovery Styles                                             */
/* ========================================================================== */
.modal_orphaned_work .modal_content {
  padding-bottom: 4rem;
}
.orphaned_group_card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--gap-md);
  padding: var(--mar-md);
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-panel, var(--border-radius));
  background: var(--panel-bg);
  transition: all 0.2s ease;
}

.orphaned_group_card:hover {
  border-color: var(--color-primary);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.orphaned_group_card + .orphaned_group_card {
  margin-top: 0;
  border-top: 1px solid var(--panel-border);
}

.orphaned_group_info {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: var(--gap-sm);
}

.orphaned_group_name {
  margin: 0;
  font-size: 1.1em;
  font-weight: 600;
  color: var(--text-color);
}

.orphaned_group_stats {
  display: flex;
  flex-wrap: wrap;
  gap: var(--gap-md);
  font-size: 0.9em;
  color: var(--text-muted);
}

.orphaned_stat {
  white-space: nowrap;
}

.orphaned_stat:not(:last-child)::after {
  content: '◆';
  margin-left: var(--gap-md);
  opacity: 0.5;
}
.btn_warning:hover {
  background: #FFB300;
}

.hover_help_tooltip {
  position: fixed;
  z-index: 1400;
  bottom: 1.5rem;
  right: 1.5rem;
  max-width: min(32rem, calc(100vw - 2rem));
  padding: 1.25rem 1.75rem;
  border: 1px solid var(--panel-border);
  border-radius: 12px;
  background: var(--back, #101010);
  color: var(--fore, #f5f5f5);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3), 0 0 60px rgba(88, 166, 255, 0.35);
  font-size: 1.125rem;
  line-height: 1.5;
  pointer-events: none;
  opacity: 0;
  transform: translateY(2px);
  transition: opacity 0.12s ease, transform 0.12s ease;
}

.hover_help_tooltip.is-visible {
  opacity: 1;
  transform: translateY(0);
}

.modal_orphaned_work .modal_header .btn_close {
  z-index: 10;
}

#sites-grid-active .datagrid,
#sites-grid-archived .datagrid {
  --grid-template-columns: minmax(0, 2.6fr) minmax(0, 0.9fr) minmax(0, 0.9fr) minmax(0, 0.9fr) minmax(0, 1.2fr) minmax(0, 0.8fr) minmax(3.5rem, 0.6fr);
  font-size: 0.9em;
}

#sites-grid-active .datagrid_item,
#sites-grid-active .datagrid_heading,
#sites-grid-archived .datagrid_item,
#sites-grid-archived .datagrid_heading {
  min-width: 0;
  padding: 0.4rem 0.5rem;
}

#sites-grid-active .datagrid_heading,
#sites-grid-archived .datagrid_heading {
  padding: 0.5rem 0.5rem 0.6rem 0.5rem;
}

#sites-grid-active .datagrid_item:nth-child(1),
#sites-grid-archived .datagrid_item:nth-child(1) {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

#sites-grid-active .datagrid_heading_actions,
#sites-grid-active .datagrid_item_actions,
#sites-grid-archived .datagrid_heading_actions,
#sites-grid-archived .datagrid_item_actions {
  text-align: right;
}

#sites-grid-active .datagrid_heading_actions,
#sites-grid-archived .datagrid_heading_actions {
  color: transparent;
  user-select: none;
}

/* Sites earnings analytics spacing */
.sites_main_container {
  gap: var(--mar-md);
}

/* Spacing between tabs and tab-disclaimer within merged panels */
#sites_list_panel .tabs,
#sites_earnings_panel .tabs {
  margin-bottom: 0.6rem;
}

/* Spacing between tab-disclaimer and tab content within merged panels */
#sites_list_panel .tab-disclaimer,
#sites_earnings_panel .tab-disclaimer {
  margin-bottom: 1rem;
  margin-top: 0;
}

/* Ensure tab content divs have spacing from above */
#sites_list_panel > .tab-content,
#sites_earnings_panel > #site_earnings_container {
  margin-top: 0.5rem;
}

@media (max-width: 750px) {
  #main:has(.sites_main_container) > .sites_main_container {
    width: min(92vw, 1240px);
    flex-direction: column;
  }

  #main:has(.sites_main_container) > .sites_main_container > .f_column {
    width: 100%;
  }
}

@media (max-width: 600px) {
  #main:has(.sites_main_container) > .sites_main_container {
    width: 100%;
    gap: clamp(0.5rem, 1.5vw, 0.8rem);
  }

  #main:has(.sites_main_container) section.panel {
    padding: clamp(0.6rem, 1.5vw, 0.9rem);
  }

  #sites_list_panel .tabs,
  #sites_earnings_panel .tabs {
    margin-bottom: 0.3rem;
  }

  #sites_list_panel .tab-disclaimer,
  #sites_earnings_panel .tab-disclaimer {
    margin-bottom: 0.6rem;
    font-size: 0.9em;
  }

  #sites_list_panel > .tab-content,
  #sites_earnings_panel > #site_earnings_container {
    margin-top: 0.25rem;
  }

  #sites-grid-active .datagrid,
  #sites-grid-archived .datagrid {
    font-size: 0.8em;
  }

  #sites-grid-active .datagrid_item,
  #sites-grid-active .datagrid_heading,
  #sites-grid-archived .datagrid_item,
  #sites-grid-archived .datagrid_heading {
    padding: 0.3rem 0.3rem;
  }

  #sites-grid-active .datagrid_heading,
  #sites-grid-archived .datagrid_heading {
    padding: 0.35rem 0.3rem 0.4rem 0.3rem;
  }
}

@media (max-width: 450px) {
  #main:has(.sites_main_container) > .sites_main_container {
    width: 100%;
    gap: 0.4rem;
  }

  #main:has(.sites_main_container) section.panel {
    padding: 0.5rem;
  }

  #sites_list_panel .tabs,
  #sites_earnings_panel .tabs {
    margin-bottom: 0.2rem;
    gap: 0.25rem;
  }

  #sites_list_panel .tab-disclaimer,
  #sites_earnings_panel .tab-disclaimer {
    margin-bottom: 0.4rem;
    font-size: 0.85em;
    line-height: 1.3;
  }

  #sites_list_panel > .tab-content,
  #sites_earnings_panel > #site_earnings_container {
    margin-top: 0.15rem;
  }

  #sites-grid-active .datagrid,
  #sites-grid-archived .datagrid {
    font-size: 0.75em;
  }

  #sites-grid-active .datagrid_item,
  #sites-grid-active .datagrid_heading,
  #sites-grid-archived .datagrid_item,
  #sites-grid-archived .datagrid_heading {
    padding: 0.25rem 0.2rem;
  }

  #sites-grid-active .datagrid_heading,
  #sites-grid-archived .datagrid_heading {
    padding: 0.3rem 0.2rem 0.35rem 0.2rem;
  }

  /* Keep columns readable on ultra-small screens; scroll instead of overlapping text. */
  #sites-grid-active,
  #sites-grid-archived {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  #sites-grid-active .datagrid,
  #sites-grid-archived .datagrid {
    min-width: 34rem;
  }
}

#site_earnings_container {
  display: grid;
  gap: 0.85rem;
}

#site_earnings_list,
#site_earnings_totals,
#site_earnings_empty {
  margin-top: 0.35rem;
}

.site_earnings_totals_datagrid {
  width: 100%;
  --grid-template-columns: minmax(8.5rem, 1.2fr) minmax(5.5rem, 0.9fr) minmax(4.5rem, 0.8fr) minmax(4rem, 0.7fr) minmax(7rem, 1fr);
}

.site_earnings_totals_datagrid .datagrid_heading,
.site_earnings_totals_datagrid .datagrid_item {
  text-align: right;
}

.site_earnings_row {
  padding: 0.85rem;
  border: 1px solid var(--panel-border);
  border-radius: var(--radius-panel, var(--border-radius));
  background: var(--panel-bg);
}

.site_earnings_row + .site_earnings_row {
  margin-top: 0.6rem;
}

.site_earnings_header {
  margin-bottom: 0.45rem;
}

.site_earnings_details {
  margin-top: 0.45rem;
  line-height: 1.45;
}

/* Sites main container (two-panel layout) */
/* Tab disclaimer */
/* Modal styles for Sites page */
.delete_message_danger {
  color: var(--error);
}

#sites-grid-active .datagrid_empty,
#sites-grid-archived .datagrid_empty {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 10rem;
  text-align: center;
  font-size: 1.6rem;
  font-weight: 700;
}
