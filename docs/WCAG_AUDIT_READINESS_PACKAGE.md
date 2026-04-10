# WCAG External Audit Readiness Package

**Package date:** 2026-03-23  
**Prepared by:** Accessibility Owner  
**Covers:** WCAG 2.1 Level AA, quarter closing 2026-Q1  
**Next refresh due:** 2026-06-23 (Q2 close)

This document serves as the master index for an external accessibility audit. It lists every evidence artifact, documents the full remediation history, records the mock-readiness checkthrough, and provides a one-day assembly procedure so a complete evidence package can be handed to an external auditor within one business day.

---

## Evidence Artifact Index

All artifacts below are version-controlled in the codebase unless noted otherwise.

### Governance and Policy

| Artifact | Location | Status |
|----------|----------|--------|
| WCAG Audit and Action Plan | `docs/WCAG_AUDIT_AND_ACTION_PLAN.md` | Current |
| WCAG Execution Backlog (full issue registry) | `docs/WCAG_EXECUTION_BACKLOG.md` | Current |
| Quarterly Audit Playbook | `docs/WCAG_QUARTERLY_AUDIT_PLAYBOOK.md` | Current |
| Remaining Execution Checklist | `docs/WCAG_REMAINING_EXECUTION_CHECKLIST.md` | Current |
| Top-20 Research, Action Plan, and Page Sweep | `docs/WCAG_TOP20_RESEARCH_ACTION_PLAN_AND_PAGE_SWEEP.md` | Current |
| Work-Entry Token Report | `docs/WCAG_WORK_ENTRY_TOKEN_REPORT.md` | Current |

### Component Standards and Developer Enablement

| Artifact | Location | Status |
|----------|----------|--------|
| Component Accessibility Standards (dialogs, tabs, datagrids, forms) | `html/tests/ACCESSIBILITY_COMPONENT_STANDARDS.md` | Current |
| Accessibility Regression Workflow | `html/tests/ACCESSIBILITY_REGRESSION_WORKFLOW.md` | Current |
| UI PR Accessibility Checklist | `.github/PULL_REQUEST_TEMPLATE.md` | Current |
| Accessibility Defect Issue Template | `.github/ISSUE_TEMPLATE/accessibility-defect.yml` | Current |

### Automated Test Suite

| Test suite | NPM command | File |
|------------|-------------|------|
| WCAG route checks (axe) | `npm run test:a11y:wcag` | `tests/smoke-ui/wcag-regression.spec.js` |
| WCAG route checks (strict) | `npm run test:a11y:wcag:strict` | same |
| Reflow / text-spacing | `npm run test:a11y:reflow` | `tests/smoke-ui/wcag-reflow.spec.js` |
| Contrast matrix (68 themes) | `npm run test:a11y:contrast` | `scripts/generate-theme-contrast-matrix.js` |
| Heading structure contracts | `npm run test:a11y:headings` | `tests/smoke-ui/wcag-heading-structure.spec.js` |
| Navigation paths | `npm run test:a11y:nav` | `tests/smoke-ui/wcag-navigation-paths.spec.js` |
| Keyboard shortcuts | `npm run test:a11y:shortcuts` | `tests/smoke-ui/wcag-shortcuts.spec.js` |
| Menu keyboard behavior | `npm run test:a11y:menu-keyboard` | `tests/smoke-ui/wcag-menu-keyboard.spec.js` |
| Complex descriptions (aria-describedby) | `npm run test:a11y:complex-descriptions` | `tests/smoke-ui/wcag-complex-descriptions.spec.js` |
| Live-region contracts | `npm run test:a11y:live-regions` | `tests/smoke-ui/wcag-live-regions.spec.js` |
| Shortcut map completeness | (part of shortcuts suite) | `tests/smoke-ui/wcag-shortcut-map.spec.js` |
| Full Playwright suite | `npm run test:a11y:playwright:suite` | all above |
| PHPUnit accessibility contracts | `npm run test:a11y:phpunit:suite` | `html/tests/Contract/` |

### Transparency and Public Disclosure

| Route | Last verified | Scope |
|-------|---------------|-------|
| `/transparency/accessibility/` | 2026-03-23 | Full WCAG status, AT support policy, SLA model, dependency review, defect response |
| `/transparency/metrics/` | 2026-03-23 | Metric key definitions, retention, manual review |
| `/transparency/taxes/` | 2026-03-23 | CRA parameter tables (next due 2027-01-01) |
| `/transparency/verification-governance/` | 2026-03-23 | Email and passkey verification gate implementations |

### Contrast Evidence

| Artifact | Location | Status |
|----------|----------|--------|
| Theme contrast matrix (68 themes, 31 token pairs) | `docs/WCAG_THEME_CONTRAST_MATRIX.md` | Current |
| Contrast matrix generator | `scripts/generate-theme-contrast-matrix.js` | Current |

---

## Issue and Remediation Registry

The following table summarises every tracked item from the WCAG execution backlog. Full details including acceptance criteria, WCAG criterion mapping, and progress notes are in `docs/WCAG_EXECUTION_BACKLOG.md`.

| ID | Title | Priority | Status | Closed date |
|----|-------|----------|--------|-------------|
| WCAG-001 | Auth page live-region structure | P0 | Closed | — |
| WCAG-002 | Settings page live-region structure | P0 | Closed | — |
| WCAG-003 | Focus management — dialogs | P0 | Closed | — |
| WCAG-004 | Focus visible — global pass | P1 | Closed | — |
| WCAG-005 | Calendar keyboard and ARIA contracts | P1 | Closed | — |
| WCAG-006 | Navigation path coverage | P1 | Closed | — |
| WCAG-007 | Heading structure contracts | P1 | Closed | — |
| WCAG-008 | Reflow / text-spacing at 400% | P1 | Closed | — |
| WCAG-009 | Complex widget descriptions | P1 | Closed | — |
| WCAG-010 | Keyboard shortcut map and documentation | P1 | Closed | — |
| WCAG-011 | Contrast pass — public routes | P1 | Closed | — |
| WCAG-012 | Contrast matrix — all 68 themes | P1 | Closed | — |
| WCAG-013 | Language attribute coverage | P2 | Closed | — |
| WCAG-014 | dyslexia-friendly typography preference | P2 | Closed | — |
| WCAG-015 | Screen-reader optimization pass | P2 | Deferred (manual, out of scope for Q1) | — |
| WCAG-016 | Quarterly audit governance | P2 | Deferred (manual, out of scope for Q1) | — |
| WCAG-017 | Live-region quality pass (auth + settings) | P1 | Closed | 2026-03-23 |
| WCAG-018 | UI PR accessibility gate | P1 | Closed | 2026-03-23 |
| WCAG-019 | Accessibility defect SLA model | P1 | Closed | 2026-03-23 |
| WCAG-020 | Accessibility design tokens v2 | P1 | Closed | 2026-03-23 |
| WCAG-021 | Transparency route content governance metadata | P1 | Closed | 2026-03-23 |
| WCAG-022 | Third-party dependency accessibility review | P1 | Closed | 2026-03-23 |
| WCAG-023 | AT support policy | P1 | Closed | 2026-03-23 |
| WCAG-024 | Plain-language copy pass | P2 | Closed | 2026-03-23 |
| WCAG-025 | Developer enablement standards | P2 | Closed | 2026-03-23 |
| WCAG-026 | External audit readiness package | P2 | Closed | 2026-03-23 |

**Open P0/P1 items at Q1 close: 0.**  
**Deferred items: WCAG-015 and WCAG-016 (both P2, manual process, scheduled for Q2).**

---

## Mock Readiness Check (Q1 2026)

This section records the results of running a self-assessment against the criteria an external auditor would typically verify before engaging.

### Criterion 1: Evidence package can be assembled within one business day

**Result: PASS**

All evidence artifacts are version-controlled in this repository. The one-day assembly procedure below produces the complete package in under two hours once repository access is granted.

### Criterion 2: Automated suite passes with zero critical/serious violations

**Result: PASS**

Last full run (2026-03-23):
- `npm run test:a11y:wcag:strict`: 6/6 routes, zero axe violations at serious or critical level.
- `npm run test:a11y:reflow:strict`: 12/12 route checks, no two-axis scrolling at 400%.
- `npm run test:a11y:contrast:strict`: 68 themes, 31 token pair checks, 0 failures, 0 unresolved.
- `npm run test:a11y:playwright:suite`: all suites pass including 12 live-region contracts, heading structure, navigation paths, keyboard shortcuts, menu keyboard, and complex descriptions.

### Criterion 3: Manual keyboard sweep completed with no P0/P1 open items

**Result: PASS**

Manual browser sweep snapshot (2026-03-22):
- `/auth/`: Auth tablist Arrow/Right/Home/End keyboard behavior verified; focus returns to trigger on dialog close; error announcement fires exactly once via `role="alert" aria-live="assertive"` region.
- `/settings/`: All panels have scoped `role="status" aria-live="polite"` regions; zero assertive regions; passkey grid live-region contract verified.
- `/help/`: skip-to-main link visible on focus; first heading after landmark is `h1`; no two-axis scrolling.
- `/transparency/accessibility/`: focus indicators visible; no heading skips; table has visible caption.
- Signed-in routes (`/`, `/sites/`, `/organizations/`): no document-level two-axis scrolling; first-focus visual indicators visible.

No P0 or P1 items are open.

### Criterion 4: Assistive-technology policy is published and discoverable

**Result: PASS**

AT support matrix and deprecation policy are published at `/transparency/accessibility/` and linked from `/help/`. Policy covers:
- Primary: macOS + Safari + VoiceOver
- Audit combinations: Windows + Firefox + NVDA, Windows + Chrome + JAWS
- Best-effort: all other combinations
- Communication: changes to browser/AT support announced via changelog and transparency page

### Criterion 5: Defect intake process is operational

**Result: PASS**

`.github/ISSUE_TEMPLATE/accessibility-defect.yml` is published and enforces severity (P0/P1/P2), WCAG criterion, named owner, and due date at intake. The PR checklist requires WCAG criteria mapping for every UI change.

### Criterion 6: No direct color coupling in shared styles

**Result: PASS**

`html/css/common/index.php` no longer contains hardcoded hex values for any component-level concern (status bars, verification banners, countdown timers, resend buttons, verification reminder inputs, disabled states). All colors are resolved through design tokens in `html/css/tokens/index.php`, which can be overridden per-theme.

### Criterion 7: Transparency surfaces expose current metadata

**Result: PASS**

All four active transparency sub-pages have Verification Metadata sections with last-verified date, next-review date, verification scope, and known limitations.

### Criterion 8: Developer accessibility standards are published and linked from onboarding

**Result: PASS**

`html/tests/ACCESSIBILITY_COMPONENT_STANDARDS.md` is published and linked from:
- `html/tests/ACCESSIBILITY_REGRESSION_WORKFLOW.md`
- `.github/PULL_REQUEST_TEMPLATE.md`

---

## One-Day Assembly Procedure

Follow these steps to produce a complete audit evidence package. Expected elapsed time: approximately 90 minutes to 2 hours.

### Step 1: Repository snapshot (10 min)

```bash
# Clone or update to HEAD on main branch
git clone <repo> paycal-audit-snapshot
cd paycal-audit-snapshot
git log --oneline -20 > audit-assembly/git-log-head-20.txt
```

### Step 2: Run full automated suite (20-30 min)

```bash
npm ci
npm run test:a11y:wcag:strict         2>&1 | tee audit-assembly/wcag-strict.txt
npm run test:a11y:reflow:strict       2>&1 | tee audit-assembly/reflow-strict.txt
npm run test:a11y:contrast:strict     2>&1 | tee audit-assembly/contrast-strict.txt
npm run test:a11y:playwright:suite    2>&1 | tee audit-assembly/playwright-suite.txt
npm run test:a11y:phpunit:suite       2>&1 | tee audit-assembly/phpunit-suite.txt
```

Notes:
- `npm ci` is required for automated and audit runs because it enforces lockfile fidelity and reproducible installs.
- If `npm ci` fails due to lockfile mismatch, update `package-lock.json` intentionally in a separate maintenance change before rerunning the audit flow.

### Step 3: Collect policy artifacts (10 min)

Copy the following files into `audit-assembly/policy/`:
- `docs/WCAG_AUDIT_AND_ACTION_PLAN.md`
- `docs/WCAG_EXECUTION_BACKLOG.md`
- `docs/WCAG_QUARTERLY_AUDIT_PLAYBOOK.md`
- `docs/WCAG_THEME_CONTRAST_MATRIX.md`
- `html/tests/ACCESSIBILITY_COMPONENT_STANDARDS.md`
- `html/tests/ACCESSIBILITY_REGRESSION_WORKFLOW.md`
- `.github/PULL_REQUEST_TEMPLATE.md`
- `.github/ISSUE_TEMPLATE/accessibility-defect.yml`
- This file: `docs/WCAG_AUDIT_READINESS_PACKAGE.md`

### Step 4: Screenshot transparency pages (10 min)

Capture browser screenshots of:
- `/transparency/accessibility/`
- `/transparency/metrics/`
- `/transparency/taxes/`
- `/transparency/verification-governance/`

Save to `audit-assembly/screenshots/`.

### Step 5: Export open defect list (5 min)

From the issue tracker, export all open accessibility-labeled issues with severity, owner, and due date. Save to `audit-assembly/open-defects.csv`.

### Step 6: Complete residual-risk summary (15 min)

Copy the template below and fill in the current quarter values:

```
Audit Period: [Q and year]
Prepared by: [Name]
Review date: [Date]

Automated suite: [PASS / FAIL with note if FAIL]
Manual keyboard sweep: [PASS / FAIL with note]
Open P0 defects: [count]
Open P1 defects: [count]
Open P2 defects: [count]
Deferred items: [list with rationale]
Residual risks accepted this quarter:
  - [describe each or write "None"]
Next audit due: [date]
```

Save to `audit-assembly/residual-risk-summary.md`.

### Step 7: Archive (5 min)

```bash
tar czf paycal-wcag-audit-evidence-$(date +%F).tar.gz audit-assembly/
```

Hand the `.tar.gz` to the external auditor.

---

## Residual Risks Accepted at Q1 2026 Close

| Risk | Rationale | Owner | Review |
|------|-----------|-------|--------|
| WCAG-015 (screen reader optimization pass) deferred | Manual VoiceOver route-by-route annotation requires dedicated sprint time; no P0/P1 defects were found in manual sweep | Accessibility Owner | Q2 2026 |
| WCAG-016 (quarterly audit formal governance) deferred | Process cadence established; formal ownership assignment and external-audit scheduling scheduled for Q2 | Accessibility Lead | Q2 2026 |
| Announcement timing across non-primary AT combinations (NVDA/JAWS) | No user reports; primary AT combination (VoiceOver) verified; cross-AT formal verification deferred | QA Lead | Q2 2026 |

---

## Contact

Accessibility issues or questions about this package: use the secure feedback form linked from `/transparency/accessibility/` or file an issue using `.github/ISSUE_TEMPLATE/accessibility-defect.yml`.
