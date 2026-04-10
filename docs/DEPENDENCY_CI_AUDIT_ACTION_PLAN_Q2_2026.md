# Dependency and CI/CD Governance — Audit Action Plan Q2 2026

**Due date:** 2026-06-30
**Scope:** npm lockfile policy, CI gate documentation, and transparency page quarterly review
**Related page:** `/transparency/dependency-ci/`

---

## Background

The `/transparency/dependency-ci/` page was published 2026-03-31 and lists three planned documentation improvements. This plan assigns concrete completion criteria and ordering so the June review can be a verification pass rather than a catch-up session.

---

## Action Items

### 1. Publish canonical CI gate matrix

**What:** Add a structured table to `/transparency/dependency-ci/` mapping every CI workflow to its trigger, primary command, blocking policy, and owner area.

**Why:** The page currently names three workflows in prose. The matrix makes the gate model auditable at a glance and exposes gaps when workflows are added or removed.

**Inputs needed:**
- `.github/workflows/javascript.yml`
- `.github/workflows/phpunit.yml`
- `.github/workflows/phpstan.yml`
- `.github/workflows/ci-smoke.yml`
- `.github/workflows/no-redis-cli.yml`

**Completion criterion:** Table exists in the `dependency-ci/index.php` `doc-section`, columns: Workflow, Trigger, Primary command(s), Blocking status, Owner. All five workflows are represented and PHP syntax passes.

---

### 2. Add quarterly npm dependency governance snapshot

**What:** Add a section (or linked page) documenting direct npm packages, the rationale for each, and the intended update cadence.

**Why:** The `package.json` has 4 direct devDependencies and 1 prod dependency. These change rarely but justify explanation for external auditors — especially why `@axe-core/playwright`, `pdf-lib`, and ESLint are present.

**Direct packages to document:**
| Package | Type | Rationale |
|---|---|---|
| `@axe-core/playwright` | devDependency | WCAG/a11y automation via Playwright |
| `@playwright/test` | devDependency | Browser smoke and route tests |
| `eslint` | devDependency | JS lint + security sink policy checks |
| `globals` | devDependency | Peer dependency for ESLint flat config |
| `pdf-lib` | dependency | Client-side PDF generation (earnings exports) |

**Completion criterion:** Table is live in the transparency article. "Update cadence" column or note confirms that updates go through a dedicated maintenance change (not inline with feature work) and always require `npm ci` re-run in CI.

---

### 3. Add npm governance release checklist item

**What:** Add an item to the release checklist (or `WCAG_QUARTERLY_AUDIT_PLAYBOOK.md`) requiring a reviewer to confirm that `dependency-ci/index.php` reflects current workflow and lockfile state before shipping.

**Why:** Known limitation — transparency pages can drift if not checked on release. A single checklist line prevents silent drift.

**Where to add it:** `docs/WCAG_QUARTERLY_AUDIT_PLAYBOOK.md` under whatever pre-release verification section exists there, or as a new "Dependency governance sign-off" item.

**Completion criterion:** The checklist item is present and references `/transparency/dependency-ci/` by path.

---

### 4. Quarterly review of `/transparency/dependency-ci/`

**What:** Perform the review declared in the page's Verification Metadata (`Next review due: 2026-06-30`).

**Checklist for that review:**
- [ ] Verify `package.json` and `package-lock.json` are still in sync (`npm ci` exits 0)
- [ ] Confirm all five workflows still exist and the gate matrix (from item 1) matches current `.github/workflows/` files
- [ ] Confirm `npm run test:js`, `npm run test:smoke:ui`, `npm run test:a11y:all`, and `npm run test:a11y:contrast` commands are still valid (check `package.json` scripts)
- [ ] Confirm the direct packages table (from item 2) still matches `package.json`
- [ ] Update `<time datetime="...">Last verified</time>` to the review date
- [ ] Update `<time datetime="...">Next review due</time>` to 2026-09-30
- [ ] Run `php -l html/transparency/dependency-ci/index.php` after edits
- [ ] Run Sync Dev before browser spot-check

---

## Sequencing

Items 1–3 can be done at any time before the quarterly review; item 4 is the review itself.

Suggested order:
1. Item 3 (checklist line) — 10 minutes, safeguards the other work
2. Item 2 (package snapshot table) — 20 minutes, pure content addition
3. Item 1 (gate matrix) — 30 minutes, requires reading workflow files and structuring the table
4. Item 4 (quarterly review) — on or before 2026-06-30

---

## Verification Commands (for the June review)

```bash
# Confirm lockfile is clean
npm ci

# Confirm JS gates pass locally
npm run test:js

# Check workflow files exist and names match the gate matrix
ls .github/workflows/

# PHP syntax on the transparency article after any edits
php -l html/transparency/dependency-ci/index.php

# Sync to dev before browser check
bash ../scripts/sync.dev.paycal.app.sh
```
