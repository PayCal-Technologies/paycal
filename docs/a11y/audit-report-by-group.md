# PayCal ARIA/WCAG Audit Report (by Group)

**Date:** 2026-06-13
**Scope:** 741 UI-relevant PHP files across 5 groups (467 non-UI excluded)
**Method:** Source-only static analysis; cross-referenced with `html/tests/Unit/A11y/`
**Manifest:** `docs/a11y/php-file-groups.json`

---

## Executive Summary

| Group | Files | Critical | High | Medium | Low |
|-------|------:|---------:|-----:|-------:|----:|
| G1 Core shell / auth / templates | 63 | 0 | 1 | 2 | 3 |
| G2 User features / sites | 11 | 0 | 0 | 1 | 2 |
| G3 Business workspace | 34 | 0 | 0 | 1 | 2 |
| G4 Public / docs / admin | 324 | 1 | 4 | 6 | 4 |
| G5 Domain renderers / JS UI | 309 | 0 | 1 | 3 | 4 |
| **Total** | **741** | **1** | **6** | **13** | **15** |

**Overall:** PayCal has strong accessibility foundations (skip links, `<main>` landmarks, native `<dialog>`, `aria-live` status regions, roving tabindex, 9 contract tests). Remaining issues cluster in admin/SOC surfaces (CSP inline styles), hardcoded English admin headings, and a few legacy menu/tab patterns.

---

## Top 5 Cross-Group Issues

1. **CSP inline `style=` on admin SOC2 datagrids** — `html/admin/soc2/index.php` uses `style="--grid-template-columns: …"` on regions (blocked by CSP). **WCAG 1.3.1 / CSP**
2. **CSP inline styles on SOC auditor portal** — `html/soc/index.php` multiple `style=` on KPI/display elements. **WCAG 1.3.1**
3. **Admin pages: English-only `<h1>` headings** — metrics, stripe, argus, redis, user-roles, language-dashboard lack i18n. **WCAG 3.1.2**
4. **Hardcoded `lang="en"` on error/404 paths** — `html/index.php:185`, `ShadowTalon.php:208` while app supports 9+ locales. **WCAG 3.1.1**
5. **Calendar context menu: `<li role="menuitem">`** — non-standard; should be focusable `<button>` children. Keyboard works but AT semantics weak. **WCAG 4.1.2**

---

## G1 — Core Shell, Auth, Templates (63 files)

**Examined:** header.php, footer.php, index.php (calendar), auth/*, verify/*, unverified/*, templates/* (non-email)

### Strengths
- Skip link with `aria-keyshortcuts` (`header.php`)
- `<main id="main" tabindex="-1">` landmark
- Calendar: visually hidden h1, grid instructions via `aria-describedby`, live month status, `aria-current="date"`, native dialog with labelledby/describedby
- Auth: labelled tablist, visually hidden h1, hero image `aria-hidden="true"`
- Contact template: `aria-invalid` + `aria-describedby` on errors (contract-tested)
- Language switcher: i18n ARIA labels via `NAV_LANGUAGE_*` keys (recent fix)

### Findings

| Sev | File | Issue | WCAG | Fix |
|-----|------|-------|------|-----|
| High | `html/index.php:185` | 404 fallback hardcodes `lang="en"` | 3.1.1 | Use `USER_LANGUAGE` |
| Med | `html/index.php:635-654` | Context menu uses `<li role="menuitem">`; wrapper now has `role="menu"` | 4.1.2 | Use `<button role="menuitem">` inside `<ul role="none">` |
| Med | `templates/calendar-date-picker-dialog.php` | Verify focus trap on open (delegated to A11yModule) | 2.4.3 | Manual AT pass |
| Low | `html/verify/index.php` | Minimal page — no skip link (standalone auth page) | 2.4.1 | Add skip-to-main if page grows |
| Low | Email templates | Inline styles present — **exempt** (email client requirement) | — | No change |
| Low | `templates/keyboard-shortcuts.php` | Good shortcut documentation; verify modal focus return | 2.4.3 | Spot-check |

### Recurring patterns
- Visually hidden h1 pattern on authenticated pages ✓
- Native `<dialog aria-modal="true">` on sign-out, session timeout ✓

### Test coverage
- `FormErrorRecoveryContractTest` guards contact template
- `KeyboardShortcutPolicyContractTest` guards shortcut docs
- Gap: no contract for 404 `lang` attribute

---

## G2 — User Features & Sites (11 files)

**Examined:** settings, profile, reports, earnings, forecast, payperiods, sites index

### Strengths
- Settings/profile: extensive `aria-live` regions, form error binding, dialog labelledby
- Sites: tablists have `aria-label`, grid status regions + `aria-describedby` chains (contract-tested)
- Reports: SR status region, org selector `aria-label`

### Findings

| Sev | File | Issue | WCAG | Fix |
|-----|------|-------|------|-----|
| Med | `html/settings/account/index.php` and `html/settings/subscription/index.php` | Settings subpages — verify heading hierarchy across account and billing panels | 1.3.1 | Audit h2/h3 order |
| Low | `html/forecast/index.php` | Forecast workspace relies on JS-init ARIA — verify `aria-live` on calculator | 4.1.3 | Covered by member-reports contract partially |
| Low | `html/payperiods/index.php` | Thin wrapper — inherits shell patterns | — | OK |

### Recurring patterns
- `role="status" aria-live="polite"` on async operations ✓
- Honeypot fields properly `aria-hidden` ✓

### Test coverage
- `StatusRegionContractTest` — settings, sites, members
- `FormErrorRecoveryContractTest` — settings email change, sites editor
- Gap: profile billing panel status regions not contract-tested

---

## G3 — Business Workspace (34 files)

**Examined:** business/* index pages, `_partials/*`, `_context_header.php`

### Strengths
- Every subpage: `<h1 class="visually_hidden">` with nav label
- Subnav: `aria-current="page"`, `aria-label` on nav
- Dialogs: `aria-modal`, labelledby on revoke/reports/definitions dialogs
- Members grid: SR status, grid ARIA label, bulk toolbar semantics
- Pay period preview: `aria-live="polite"` region
- No inline `style=` found in business PHP (CSP clean)

### Findings

| Sev | File | Issue | WCAG | Fix |
|-----|------|-------|------|-----|
| Med | `business_details_panel.php:124` | Image popover `role="dialog" aria-modal="false"` — verify ESC/focus return | 2.4.3 | Align with popover pattern |
| Low | `workspace.js.php` (via G5) | Pay-period `<td tabindex="0">` — unusual grid focus | 2.1.1 | Document or use roving tabindex |
| Low | Archive partials | `_archive/partials/*` still referenced in tests but not live pages | — | Deprecation cleanup |

### Recurring patterns
- `visually_hidden` h1 on all 7 subpages ✓ (contract-tested)
- Permission toggles use `aria-pressed` ✓

### Test coverage
- `BusinessesAndEarningsA11yContractTest` — extensive structural + i18n guards
- `BusinessesCspStyleContractTest` — no inline styles in rendered grids
- Best-covered module in codebase

---

## G4 — Public, Docs, Admin (324 files)

**Examined:** All transparency (215), help (32), public marketing (44), admin (19), html-other (16). Deep sample + pattern grep across all.

### Strengths
- Transparency/help: visible `<h1>` per page, doc-section structure, tables with headers
- Error-handling/diagnostics: inline margin styles **fixed** → `.doc-section-footer-note`
- AST admin: rich canvas ARIA (`role="img"`, keyshortcuts, live status)
- SOC2 admin: extensive `aria-live` regions on copy/action status
- Admin user-roles: flash panels use CSS classes; confirm via accessible pattern (recent fix)

### Findings

| Sev | File | Issue | WCAG | Fix |
|-----|------|-------|------|-----|
| **Crit** | `html/admin/soc2/index.php:1827+` | Datagrid regions use inline `style="--grid-template-columns:…"` — CSP blocked | 1.3.1 | Use predefined `datagrid_cols_N` classes or CSS custom props via class |
| High | `html/soc/index.php:534+` | Multiple inline `style=` on auditor portal KPIs | 1.3.1 | Move to `html/css/soc/` classes |
| High | `html/admin/language-dashboard/index.php:68,91` | Inline `style=` on help text and stats | 1.3.1 | Use utility classes |
| High | `html/admin/*` (8 pages) | English-only `<h1>` (Redis, Stripe, Argus, Metrics, etc.) | 3.1.2 | Add i18n keys |
| High | `html/transparency/*/en.php` + legacy pages | Some hardcoded English h1 (auth-hardening, extensions paradigm) | 3.1.2 | Use i18n keys like other locales |
| Med | `html/admin/language-editor.php:76` | Tablist `aria-label='Language selector'` hardcoded English | 3.1.2 | i18n |
| Med | `html/soc2/request/index.php` | Email/NDA HTML with inline styles | — | Email exempt; page UI separate |
| Med | `html/help/*/index.php` (non-en) | Some locale index pages use `FAQ_TITLE` for h1 — verify correct key per section | 3.1.2 | Audit string keys |
| Med | Transparency pages (~215) | Rely on Layout.php for `lang` — **fixed** to dynamic | 3.1.1 | Verify per-locale rendering |
| Med | `html/media/index.php` | `<h1>Media</h1>` hardcoded English | 3.1.2 | i18n |
| Low | Admin metrics | Emoji in h1 — **fixed** with `aria-hidden` span | 1.1.1 | Done |
| Low | Transparency images | members-performance figures have excellent alt+figcaption ✓ | 1.1.1 | Model for other pages |
| Low | `html/about/*`, `html/contact/*` | Generally good landmark structure via Layout | — | OK |

### Recurring patterns
- Transparency multi-locale duplication (215 files) — consistency good, maintenance heavy
- Admin surfaces lag behind core app on i18n and CSP compliance

### Test coverage
- Gap: no A11y contract tests for transparency, help, admin, or soc pages
- `BusinessesCspStyleContractTest` pattern could extend to admin SOC2

---

## G5 — Domain Renderers & JS UI (309 files)

**Examined:** domain-domain (158), domain-controllers (26), js-php-served (44), extensions (31), infra/observability (47 sampled for HTML emission)

### Strengths
- `A11yModule` centralizes focus trap, modal open/close, dialog chrome (`html/js/core/a11y.js`)
- `Earnings.php`, `BusinessMemberReportsService.php`: tablists now have `aria-label` (recent fix)
- `DataGrid.php`: `role="row"`, `aria-current="date"`, column menu `role="dialog"`
- `Layout.php`: skip link, dynamic `lang` (recent fix), dyslexia typography data attribute
- `Render.php`: language nav i18n ARIA (recent fix)
- JS modules: roving tabindex on calendar, sites, earnings tabs; datagrid column menu `aria-expanded`
- No inline `style=` in `html/js/*.php` after language-dashboard fix

### Findings

| Sev | File | Issue | WCAG | Fix |
|-----|------|-------|------|-----|
| High | `AdminPageController.php:443` | Admin palette swatches emit `style='background:{$hex}'` | 1.3.1 | Use `data-hex` + CSS attribute selector or predefined swatch classes |
| Med | `BusinessMembersGridRenderer.php:599` | Column menu `role="dialog"` — verify arrow-key nav | 2.1.1 | Manual / extend datagrid tests |
| Med | `ShadowTalon.php:208` | Error page hardcodes `lang="en"` | 3.1.1 | Dynamic lang |
| Med | `EmailGarum.php:772+` | Inline styles in notification email HTML | — | Email exempt |
| Low | `Earnings.php` / member reports | Tab panels lack `role="tabpanel"` + `aria-labelledby` linkage | 4.1.2 | Add tabpanel semantics |
| Low | `ForecastWorkspaceRenderer.php` | Verify all dynamic labels use i18n | 3.1.2 | Contract test exists partially |
| Low | Extension hooks | Some inject HTML strings — spot-check for alt/aria | 4.1.2 | Per-extension review |
| Low | `html/js/business/workspace.js.php` | Contact card menu toggle uses `aria-haspopup="true"` not `menu` | 4.1.2 | Use `aria-haspopup="menu"` |

### Recurring patterns
- Domain renderers consistently escape output and use i18n for visible copy ✓
- JS-served PHP injects i18n via `SETTINGS_T`, `AUTH_T` patterns ✓ (contract-tested)

### Test coverage
- `A11yModuleWiringContractTest` — modal/focus delegation
- `ThemeButtonTokenContractTest`, `SitesI18nContractTest`
- Gap: no test for `AdminPageController` palette inline styles
- Gap: tabpanel role linkage not contract-tested

---

## Existing Test Coverage vs Gaps

| Guarded | Not guarded |
|---------|-------------|
| Business structure + i18n | Transparency (215 files) |
| CSP no-inline-styles (business grids) | Admin SOC2 datagrid inline styles |
| Status regions (settings, sites, members) | Profile billing panels |
| Form error recovery (contact, settings, sites) | Admin page headings i18n |
| Keyboard shortcut policy | Tabpanel aria-labelledby linkage |
| A11yModule wiring | 404/error page lang attribute |
| Language switcher i18n (recent) | SOC auditor portal inline styles |

---

## Recommended Fix Order

### Phase 1 — CSP violations (user-visible breakage)
1. `admin/soc2/index.php` — datagrid column templates via CSS classes
2. `soc/index.php` — KPI/chart skeleton classes
3. `AdminPageController.php` — palette swatch classes
4. `admin/language-dashboard/index.php` — remaining inline styles

### Phase 2 — i18n / lang
5. Admin h1 headings → string keys
6. `index.php` 404 + `ShadowTalon.php` → dynamic lang
7. Hardcoded transparency legacy h1s

### Phase 3 — ARIA polish
8. Calendar context menu → button menuitems
9. Earnings/member-reports tabpanels
10. Contact image popover focus management

### Phase 4 — Contract tests
11. Extend `BusinessesCspStyleContractTest` to admin SOC2
12. Add lang-attribute contract for Layout + error pages
13. Add tabpanel linkage contract for Earnings.php

---

## Files Changed Since Prior Audit (already fixed)

- `Layout.php`, `verify/index.php`, `unverified/index.php` — dynamic lang
- `Render.php` — language switcher i18n ARIA
- `Earnings.php`, `BusinessMemberReportsService.php` — tablist aria-label
- Transparency error-handling/diagnostics — `.doc-section-footer-note`
- `team_earnings_panel.php` — CSP-safe skeleton classes
- `admin/user-roles/index.php` — flash CSS + accessible confirm
- `js/admin/language-dashboard.php` — native `<progress>`, CSS classes
- `admin/metrics/index.php` — emoji aria-hidden
- `index.php` — calendar context menu `role="menu"`

---

*Report generated in foreground after subagent `[ARIA audit 5 PHP groups](6560aeaa-db66-4ae3-a9f9-134410ec5fd3)` failed on usage limits.*
