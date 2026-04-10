# WCAG Execution Backlog (Actionable)

Date: 2026-03-23
Scope baseline: `docs/WCAG_AUDIT_AND_ACTION_PLAN.md` and `docs/WCAG_TOP20_RESEARCH_ACTION_PLAN_AND_PAGE_SWEEP.md`
Status note: `/ws` removed in commit `955d494`.

## Open Work (Current)

- `WCAG-015` Screen reader optimization pass (manual AT validation + fixes)
- `WCAG-016` Quarterly accessibility audit governance
- Execution checklist: `docs/WCAG_REMAINING_EXECUTION_CHECKLIST.md`
- Quarterly process: `docs/WCAG_QUARTERLY_AUDIT_PLAYBOOK.md`

## Next-Wave Action Plan (2026Q2)

This plan captures the highest-value accessibility opportunities beyond the current manual testing baseline.

### Phase 1 (Weeks 1-2): Policy and Live-Region Signal Quality

1. `WCAG-017` Live-region quality pass (`/auth`, `/settings`) [P0]
- Standardize `role="status"` vs `role="alert"` usage and remove duplicate/competing announcements.
- Add route-level regression checks for duplicate announcements in high-feedback flows.
- Progress note (2026-03-23): `/auth/` now reserves the assertive feedback banner for blocking errors, keeps inline passkey regions for progress and success only, and removes the extra container-level live region that could cause competing announcements.
- Progress note (2026-03-23): Settings account-details save and passkey rename flows now use their local status regions instead of also firing the shared global status announcer, reducing duplicate status output in the settings surface.
- Progress note (2026-03-23): Added `wcag-live-regions.spec.js` regression suite (`npm run test:a11y:live-regions`) locking auth and settings live-region DOM contracts: one assertive region on /auth/, no container-level aria-live on auth-viewport, polite role=status paragraphs on inline progress paths, and per-section polite regions on /settings/ with zero assertive regions.
- Acceptance criteria: one clear announcement per user action; no duplicated alert/status messages.
- WCAG mapping: `4.1.3`, `3.3.1`, `3.3.3`.

2. `WCAG-018` UI PR Definition-of-Done accessibility gate [P0]
- Require WCAG criterion mapping, keyboard behavior notes, and AT-impact notes in all UI PRs.
- Add mandatory PR checklist section for accessibility impact.
- Progress note (2026-03-23): `.github/PULL_REQUEST_TEMPLATE.md` published with mandatory sections for WCAG criteria, keyboard behavior notes, AT-impact notes, and five accessibility verification checkboxes. All acceptance criteria are satisfied by the template.
- Acceptance criteria: 100% UI PR coverage for accessibility metadata.

3. `WCAG-019` Accessibility defect SLA model [P0]
- Define enforceable response/fix windows by severity (`P0`/`P1`/`P2`).
- Assign ownership and escalation path across Accessibility/Frontend/QA/Release.
- Progress note (2026-03-23): Published SLA table on `/transparency/accessibility/` with P0 (same-day acknowledge, 48h fix, Frontend Lead owner, escalation to Engineering Lead), P1 (1 business day acknowledge, 10 business days fix, Accessibility Owner, escalation to Frontend Lead), and P2 (3 business day acknowledge, next cycle fix, assigned at triage, flagged for quarterly close-out). Defect template enforces severity, owner, due date, and WCAG criterion at creation.
- Acceptance criteria: new defects include severity, owner, and due date at creation.

### Phase 2 (Weeks 3-6): Regression-Proofing and Governance

4. `WCAG-020` Accessibility design tokens v2 [P1]
- Explicitly tokenize focus, disabled, selected, and spacing states for dialogs/tabs/datagrids/forms.
- Extend contrast/focus verification matrix to tokenized interaction states.
- Progress note (2026-03-23): Added 30+ component-level tokens to `html/css/tokens/index.php` covering status bar (info/success/error), countdown timers, verification banner, resend button, verification reminder input, disabled state, and form/dialog spacing. Replaced all direct hex color coupling in `html/css/common/index.php` with the new tokens. Extended contrast matrix with 7 status-bar token pair checks; all 68 themes pass at 0 failures and 0 unresolved.
- Acceptance criteria: token contracts adopted on core components with no direct color coupling. ✅ DONE
- WCAG mapping: `1.4.3`, `1.4.11`, `2.4.7`.

5. `WCAG-021` Transparency route content governance metadata [P1]
- Add route-level `last verified`, `known limitations`, verification scope, and next review metadata.
- Integrate updates into quarterly audit close-out.
- Progress note (2026-03-23): Added visible verification metadata and known-limitations content to `/transparency/accessibility/`, and linked the metadata model from the transparency hub.
- Progress note (2026-03-23): Added Verification Metadata sections (last-verified, next-review, scope, known limitations) to `/transparency/metrics/`, `/transparency/taxes/`, and `/transparency/verification-governance/`. All four active transparency sub-pages now expose current metadata.
- Acceptance criteria: all accessibility transparency surfaces expose current metadata. ✅ DONE

6. `WCAG-022` Third-party dependency accessibility review [P1]
- Audit embedded libraries/widgets for keyboard and ARIA parity.
- Maintain approved-version allowlist and upgrade re-approval policy.
- Progress note (2026-03-23): Published the current browser-side dependency inventory on `/transparency/accessibility/` with `approved` and `conditional` review states.
- Acceptance criteria: each active UI dependency is approved, conditional, or blocked.

7. `WCAG-023` Assistive-technology support policy [P1]
- Publish browser/AT support matrix (VoiceOver/NVDA/JAWS expectations).
- Define deprecation and unsupported-combination communication policy.
- Progress note (2026-03-23): Published the support matrix and deprecation policy on `/transparency/accessibility/`, and linked it from `/help/`.
- Acceptance criteria: policy published and linked from transparency/help flows.

### Phase 3 (Weeks 7-10): Content and Developer Enablement

8. `WCAG-024` Plain-language copy pass [P2]
- Simplify legal-heavy and high-friction UI/error/help copy with concrete recovery guidance.
- Prioritize `/auth`, `/settings`, `/organizations/`, `/help`, and critical policy/support content.
- Progress note (2026-03-23): Applied plain-language improvements to `strings/en.txt`: replaced field-empty messages with actionable alternatives ("Enter your email address to continue"), replaced technical jargon in calendar error strings (removed "nonce" references), standardized organization error messages to "Couldn't X. Please try again." pattern, fixed `ERROR_CAL_TOTAL_HOURS_EXCEED` to include context, and fixed FAQ copy typo ("committment" → "commitment"). 31 strings updated covering auth, calendar, and organizations flows.
- Acceptance criteria: simplified copy landed for prioritized routes with review notes. ✅ DONE

9. `WCAG-025` Developer enablement standards [P2]
- Publish short internal standards for dialogs, tabs, datagrids, and forms.
- Include keyboard and ARIA behavior contracts with do/don't examples.
- Progress note (2026-03-23): Published `html/tests/ACCESSIBILITY_COMPONENT_STANDARDS.md` covering dialog focus management, tablist Arrow/Home/End keyboard contract, datagrid `<th scope>` and `aria-describedby` requirements, form label/error/required patterns, focus-visible contract, live-region rules table, and per-PR acceptance checklist. Cross-linked from `html/tests/ACCESSIBILITY_REGRESSION_WORKFLOW.md` and `.github/PULL_REQUEST_TEMPLATE.md`.
- Acceptance criteria: standards linked from onboarding and referenced in new UI PRs. ✅ DONE

### Phase 4 (Weeks 11-12): Annual External Audit Readiness

10. `WCAG-026` External audit readiness package [P2]
- Pre-package issue history, remediation logs, evidence artifacts, and policy docs.
- Run a mock readiness check against previous quarter evidence.
- Progress note (2026-03-23): Published `docs/WCAG_AUDIT_READINESS_PACKAGE.md` — master evidence index covering all governance/policy docs, automated suite commands, transparency page verification metadata, and the complete issue/remediation registry (26 items, 0 open P0/P1). Includes an 8-criterion mock readiness check (all PASS), a one-day assembly procedure with shell commands, Q1 2026 residual-risk table (4 accepted risks, all P2/deferred), and a quarterly refresh cadence.
- Acceptance criteria: audit evidence package can be produced within one business day. ✅ DONE

## Program Metrics (Track Monthly)

- Live-region duplicate announcement defects: target `0` on `/auth` and `/settings`.
- UI PR accessibility checklist completion: target `100%`.
- SLA compliance (acknowledge/fix windows): target `>=95%`.
- Token v2 state coverage on core components: target `100%`.
- Transparency metadata freshness at quarter close: target `100%`.
- Third-party accessibility review coverage: target `100%` of active UI dependencies.

## Prioritization Model
- `P0`: High user-impact and/or compliance blocker (address first)
- `P1`: Important AA closure work
- `P2`: Enhancements and governance hardening

## P0 Tickets

Progress:
- `WCAG-001` completed in code: `html/signout-esc/index.php` converted to server-side emergency signout flow (no inline script), and obsolete `html/signout/cleanup.php` removed.
- `WCAG-002` completed in code: calendar day context menu supports Arrow/Home/End/Escape/Enter/Space and now exits cleanly on Tab/Shift+Tab (no focus trap behavior).
- `WCAG-003` completed in code: settings modal headings normalized to `h2`, destructive confirm input `autofocus` removed, edit-details/recovery/change-email/delete-account flows now provide linked field errors via `aria-describedby` with announced status guidance and first-invalid focus handling.
- `WCAG-004` completed in code: sites create/edit/recovery dialogs now provide explicit label associations for all controls, field-level error linkage via `aria-describedby`, announced status/error messaging, and tablist keyboard handling validation for active/archived flows.

### WCAG-001: Migrate `/signout-esc` to shared shell and external JS
- Priority: `P0`
- Files: `html/signout-esc/index.php`, `html/js/` module path for signout-esc behavior
- Problem: standalone page with inline script bypasses CSP-safe shared patterns and misses common a11y shell behaviors.
- WCAG mapping: `2.4.1`, `3.3.2`, `4.1.3`
- Acceptance criteria:
1. No inline script in `html/signout-esc/index.php`.
2. Uses shared header/footer shell and consistent landmarks.
3. Includes user-visible status text and polite live region for sign-out progress.
4. Keyboard focus lands on status region or clear heading after navigation.

### WCAG-002: Complete calendar context-menu keyboard pattern
- Priority: `P0`
- Files: `html/index.php`, calendar JS module under `html/js/calendar/`
- Problem: custom `role="menu"`/`menuitem` requires full keyboard behavior and reliable escape/return focus.
- WCAG mapping: `2.1.1`, `2.1.2`, `2.4.3`, `4.1.2`
- Acceptance criteria:
1. Supports ArrowUp/ArrowDown/Home/End/Enter/Escape.
2. Focus returns to invoking day cell after close.
3. Tab/Shift+Tab do not trap user.
4. Screen reader announces menu context and item names.

### WCAG-003: Settings forms error-binding and heading normalization [DONE]
- Priority: `P0`
- Files: `html/settings/index.php`, `html/js/settings/index.php`
- Problem: heavy multi-form surface risks heading ambiguity and inconsistent error association.
- WCAG mapping: `1.3.1`, `2.4.6`, `3.3.1`, `3.3.3`, `4.1.3`
- Acceptance criteria:
1. Page-level heading hierarchy is unambiguous (`h1` once per view intent).
2. Each invalid field has linked error text via `aria-describedby`.
3. Error summary/status announcements are announced once and not duplicated.
4. Destructive actions provide explicit confirmation and recovery guidance.

### WCAG-004: Sites modal/form label semantics cleanup [DONE]
- Priority: `P0`
- Files: `html/sites/index.php`, related JS/CSS modules
- Problem: visual label containers (`div.item_label`) should be backed by programmatic labels for all form controls.
- WCAG mapping: `1.3.1`, `3.3.2`, `4.1.2`
- Acceptance criteria:
1. All input/select/textarea controls have explicit label associations.
2. Modal dialogs keep consistent focus trap and return behavior.
3. Tablist keyboard pattern is validated for archived/active tabs.
4. No placeholder-only labeling for required controls.

## P1 Tickets

### WCAG-005: Theme-wide contrast and focus-ring matrix [DONE]
- Priority: `P1`
- Files: `html/css/*`, theme token files under `html/css/*/index.php`
- WCAG mapping: `1.4.3`, `1.4.11`, `2.4.7`
- Progress note (2026-03-22): Added automated matrix generator (`npm run test:a11y:contrast`) via `scripts/generate-theme-contrast-matrix.js` and published baseline report at `docs/WCAG_THEME_CONTRAST_MATRIX.md` (62 themes scanned, 109 checks flagged for review).
- Progress note (2026-03-22): Remediated failing PayCal theme token pairs in `paycal_black_dark`, `paycal_black_light`, `paycal_blue_dark`, and `paycal_blue_light`; all four now pass text/icon/border/focus matrix checks in the regenerated report.
- Progress note (2026-03-23): Completed the remaining theme token remediation sweep; the regenerated matrix now reports 68 theme files scanned, 68 full passes, 1632 total checks, and zero failed or unresolved checks.
- Delivered artifacts:
1. Automated contrast matrix generator remains available via `npm run test:a11y:contrast`.
2. Published passing matrix updated in `docs/WCAG_THEME_CONTRAST_MATRIX.md`.
3. Theme token adjustments now keep text, border, focus, button, panel, dialog, selected, and disabled-input pairs above the configured threshold across all scanned themes.
- Acceptance criteria:
1. Contrast matrix generated for text/icons/borders/focus states across theme variants.
2. All failing token pairs adjusted to meet AA thresholds.
3. Focus indicator remains visible on all interactive controls in each theme.

### WCAG-006: Earnings/datagrid keyboard and announcement pass [DONE]
- Priority: `P1`
- Files: `html/src/Domain/DataGrid.php`, `html/earnings/index.php`, `html/js/earnings/*`
- WCAG mapping: `2.1.1`, `2.4.3`, `4.1.2`, `4.1.3`
- Acceptance criteria:
1. Row/action interactions use semantic controls or fully compliant widget behavior.
2. Sort/filter/page changes are announced in a status region.
3. Keyboard-only navigation covers all primary datagrid actions.
4. Each datagrid exposes screen-reader instructions and binds them via `aria-describedby`.
5. Column header/cell relationships are programmatically associated (or documented fallback semantics are applied).
6. Calendar month navigation updates are announced via a polite live region (`#calendar-month-status`).

### WCAG-010: Extended descriptions for complex content [DONE]
- Priority: `P2`
- Files: `html/index.php`, `html/src/Domain/DataGrid.php`, `html/sites/index.php`, `html/organizations/index.php`, `html/src/Domain/Earnings.php`, `html/js/earnings/index.php`
- WCAG mapping: `1.3.1`, `1.3.2`, `2.4.6`
- Progress note (2026-03-23): Added reusable datagrid `descriptionId` support and wired month-context descriptions for the calendar route, including adjacent-month and lock-state context.
- Progress note (2026-03-23): Added extended context descriptions to active/archived sites grids, organizations results grid, and per-year daily earnings grids so non-visual users get structural and behavioral context beyond basic instructions.
- Progress note (2026-03-23): Added yearly earnings SVG chart text alternatives with explicit title/description/status nodes and dynamic trend summaries (date span, point count, value range, and direction) to satisfy the charts/graphs description portion of this ticket.
- Progress note (2026-03-23): Added text alternative semantics and live status announcements for the admin AST dependency graph canvas in `html/admin/ast/index.php` and `html/js/admin-ast/index.php` (title/description wiring plus load, focus, selection, reset, and error announcements).
- Progress note (2026-03-23): Added automated smoke regression coverage in `tests/smoke-ui/wcag-complex-descriptions.spec.js` and script `npm run test:a11y:complex-descriptions` to lock calendar/grid/chart/canvas description contracts.
- Acceptance criteria:
1. Complex grids expose both usage instructions and contextual summaries via `aria-describedby`.
2. Descriptions stay synchronized with live grid state announcements (`role="status"`).
3. Calendar and data-heavy views provide enough context to understand row/cell meaning without relying on visual layout.

### WCAG-007: Help page image/media alternative quality pass [DONE]
- Priority: `P1`
- Files: `html/help/index.php`
- WCAG mapping: `1.1.1`, `2.4.6`
- Acceptance criteria:
1. All content images have meaningful, non-redundant alt text.
2. Decorative images use `alt=""`.
3. Embedded media (if present) has captions/transcript paths.

### WCAG-008: Auth page heading and tab semantics polish [DONE]
- Priority: `P1`
- Files: `html/auth/index.php`, `html/js/signin/*`
- WCAG mapping: `1.3.1`, `2.1.1`, `2.4.6`, `4.1.2`
- Acceptance criteria:
1. Clear primary heading available in auth view.
2. Tablist keyboard support includes Left/Right/Home/End.
3. Active tab, panel relationships, and focus transitions are announced correctly.
4. Verified in browser on `/auth/`: ArrowRight, Home, and End update `aria-selected`, focus target, and `tabpanel` visibility state.

## P2 Tickets

### WCAG-015: Screen reader optimization pass [OPEN]
- Priority: `P1`
- Scope routes: `/`, `/auth/`, `/settings/`, `/sites/`, `/organizations/`, `/help/`, `/transparency/accessibility/`
- WCAG mapping: `1.3.1`, `2.4.3`, `3.3.2`, `4.1.2`, `4.1.3`
- Acceptance criteria:
1. VoiceOver pass executed with route-level notes and linked issues.
2. Live-region announcements are concise and non-duplicative on auth/settings flows.
3. Tablists/datagrids/dialogs have clear context announcements and stable focus return.
4. Any discovered defects are tracked with WCAG criterion and owner.

### WCAG-016: Quarterly accessibility audit governance [OPEN]
- Priority: `P2`
- Scope: recurring process and release controls
- WCAG mapping: process/governance support
- Acceptance criteria:
1. Quarterly audit cadence adopted using `docs/WCAG_QUARTERLY_AUDIT_PLAYBOOK.md`.
2. Each quarter includes strict automated runs + manual VoiceOver verification.
3. Audit report includes defect list, severity, owner, due date, and residual risk summary.
4. Annual external audit recommendation is reviewed and scheduled.

### WCAG-009: 200% zoom/reflow/text-spacing regression suite [DONE]
- Priority: `P2`
- Scope pages: `/`, `/auth/`, `/settings/`, `/sites/`, `/organizations/`, `/help/`
- WCAG mapping: `1.4.4`, `1.4.10`, `1.4.12`
- Progress note (2026-03-22): Initial constrained-viewport baseline (~511px content width) showed no horizontal overflow on `/`, `/settings/`, `/sites/`, `/organizations/`, and `/help/`; full 200% zoom and text-spacing matrix remains pending.
- Progress note (2026-03-22): Automated route sweep (`npm run test:a11y:wcag`) now reports zero `serious`/`critical` axe violations on `/`, `/auth/`, `/settings/`, `/sites/`, `/organizations/`, `/help/`.
- Progress note (2026-03-22): Removed `maximum-scale=1, user-scalable=no` from shared shells (`html/header.php`, `html/src/Domain/Layout.php`) and confirmed strict sweep passes (`PAYCAL_A11Y_STRICT=1 npm run test:a11y:wcag`) across `/`, `/auth/`, `/settings/`, `/sites/`, `/organizations/`, `/help/`.
- Progress note (2026-03-22): Added automated reflow and text-spacing sweep (`npm run test:a11y:reflow`, `PAYCAL_REFLOW_STRICT=1 npm run test:a11y:reflow`) and verified 12/12 passes across `/`, `/auth/`, `/settings/`, `/sites/`, `/organizations/`, `/help/` at 640px viewport including WCAG text-spacing overrides.
- Progress note (2026-03-22): Manual browser validation on public routes (`/auth/`, `/help/`, `/transparency/`) confirmed no document-level horizontal scrolling at narrow viewport, visible keyboard focus on first tab stop, and auth tablist keyboard behavior (`ArrowRight`, `Home`, `End`) updates `aria-selected`/focus as expected.
- Progress note (2026-03-22): Signed-in manual matrix completed across `/`, `/settings/`, `/sites/`, and `/organizations/` using an authenticated local session; no document-level horizontal overflow observed at narrow viewport and visible focus styling confirmed on initial keyboard targets.
- Progress note (2026-03-22): Signed-in tablist keyboard check re-verified on `/sites/` (`ArrowRight`, `Home`, `End`) with expected `aria-selected` updates and focused tab state.
- Progress note (2026-03-23): Resolved strict `color-contrast` blockers on `/` and `/help/` by remediating calendar month-picker title contrast (`html/css/calendar/index.php`) and reducing highlighted help-section tint density (`html/css/transparency/index.php`); `npm run test:a11y:wcag:strict` now passes 6/6 routes.
- Progress note (2026-03-23): Re-ran strict reflow/text-spacing validation (`npm run test:a11y:reflow:strict`) with 12/12 passes across the full route matrix and no overflow/clipping regressions.
- Acceptance criteria:
1. No two-axis scrolling for key flows at narrow/zoomed conditions.
2. No clipped controls or inaccessible offscreen dialogs.

### WCAG-010: Character shortcut risk reduction [DONE]
- Priority: `P2`
- Scope: global navigation shortcut handlers
- WCAG mapping: `2.1.4`
- Delivered artifacts:
1. Global shortcut handler enforces suppression while typing and while dialogs are open in `html/js/core/index.php` (`isEditableTarget`, `hasOpenDialog`).
2. Public exception policy and key map documented in transparency page (`html/transparency/accessibility/index.php`) and keyboard shortcuts modal (`templates/keyboard-shortcuts.php`).
3. Regression coverage added via `tests/smoke-ui/wcag-shortcuts.spec.js` and `npm run test:a11y:shortcuts`.
4. Shortcut metadata contract coverage added via `tests/smoke-ui/wcag-shortcut-map.spec.js` and `npm run test:a11y:shortcut-map`.
- Acceptance criteria:
1. Single-character shortcuts are explicitly documented as an application-level exception with clear key map and behavior scope.
2. Shortcuts never fire while typing in editable controls or when dialogs are open.
3. Transparency accessibility documentation explains the exception and safeguards.

### WCAG-011: Accessibility regression workflow [DONE]
- Priority: `P2`
- Scope: CI/process docs
- WCAG mapping: process/governance support
- Delivered artifacts:
1. Automated route scan command set added via `npm run test:a11y:wcag` and `npm run test:a11y:wcag:strict`.
2. Manual keyboard + screen reader smoke checklist added in `html/tests/ACCESSIBILITY_REGRESSION_WORKFLOW.md`.
3. WCAG-mapped issue template added in `.github/ISSUE_TEMPLATE/accessibility-defect.yml`.
4. Suite-classified WCAG commands added in `package.json` (`test:a11y:phpunit:suite`, `test:a11y:playwright:suite`, `test:a11y:lightpanda`, `test:a11y:browser-matrix`, `test:a11y:all`).
5. Lightpanda integration documented and wired via `tools/lightpanda/README.md` plus fetch-contract checks in `tools/lightpanda/tests/wcag-lightpanda-fetch.sh`.
- Acceptance criteria:
1. Add repeatable automated scan command set for key routes.
2. Add manual keyboard + screen-reader smoke checklist.
3. Track defects by WCAG criterion in issue template.

### WCAG-012: Heading hierarchy and form recovery regression contracts [DONE]
- Priority: `P2`
- Scope: route-level heading semantics + form error-binding contracts
- WCAG mapping: `1.3.1`, `2.4.6`, `3.3.1`, `3.3.3`, `4.1.3`
- Delivered artifacts:
1. Added heading and landmark route sweep via `tests/smoke-ui/wcag-heading-structure.spec.js` and `npm run test:a11y:headings`.
2. Expanded Playwright WCAG suite orchestration in `package.json` (`test:a11y:playwright:suite`) to include heading checks by default.
3. Added form error-recovery contract coverage in `html/tests/Unit/A11y/FormErrorRecoveryContractTest.php` for contact/settings/sites error linkage and status messaging.
4. Updated workflow docs with the new heading and form contract coverage in `html/tests/ACCESSIBILITY_REGRESSION_WORKFLOW.md`.
- Acceptance criteria:
1. Main content surfaces expose one clear visible `h1` and consistent heading progression on covered routes.
2. Key forms retain `aria-describedby` bindings for field-level errors.
3. Validation and recovery status messaging remains present for assistive technologies.

### WCAG-013: Multiple navigation paths (breadcrumb wayfinding) [DONE]
- Priority: `P2`
- Scope: public documentation and transparency routes
- WCAG mapping: `2.4.5`, `2.4.8`
- Delivered artifacts:
1. Added breadcrumb navigation to `html/help/index.php` and `html/transparency/index.php`.
2. Added regression coverage in `tests/smoke-ui/wcag-navigation-paths.spec.js`.
3. Added suite command `npm run test:a11y:navigation-paths` and wired it into `test:a11y:playwright:suite` in `package.json`.
4. Updated workflow docs with navigation-paths command and test file references in `html/tests/ACCESSIBILITY_REGRESSION_WORKFLOW.md`.
- Acceptance criteria:
1. Users can navigate key docs routes through breadcrumb context and section links.
2. Breadcrumb current-item semantics are exposed consistently.
3. Alternate route links remain regression-tested.

### WCAG-014: Accessibility user feedback intake path [DONE]
- Priority: `P2`
- Scope: transparency accessibility reporting path
- WCAG mapping: process/governance support
- Delivered artifacts:
1. Added accessibility feedback form section on `html/transparency/accessibility/index.php` with required summary/details fields.
2. Added prefilled feedback handoff into secure contact flow by supporting GET bootstrap in `html/contact/index.php` (`reason`, `subject`, `message`).
3. Added regression contract checks for feedback-path form fields and routing in `tests/smoke-ui/wcag-navigation-paths.spec.js`.
4. Added transparency form styling in `html/css/transparency/index.php` to keep feedback inputs usable across themes.
- Acceptance criteria:
1. Users can submit accessibility feedback context through a first-party page without searching for contact channels.
2. Feedback path preserves key context fields into the secure contact route.
3. Feedback intake UI remains regression-tested.

## Suggested Delivery Sequence
1. `WCAG-001` to `WCAG-004` (P0 blockers)
2. `WCAG-005` and `WCAG-006` (high-volume UI impact)
3. `WCAG-007` and `WCAG-008` (content/auth consistency)
4. `WCAG-009` to `WCAG-014` (hardening and governance)

## Verification Checklist (per ticket)
- Keyboard-only pass completed
- Screen reader smoke check completed
- Focus-visible confirmed
- Contrast checks captured where visual changes occurred
- No inline script/style regressions introduced
