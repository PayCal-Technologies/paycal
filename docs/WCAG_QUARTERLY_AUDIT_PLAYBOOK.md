# WCAG Quarterly Audit Playbook

Date: 2026-03-23
Cadence: once per quarter (Q1/Q2/Q3/Q4), plus an annual external audit recommendation.

## Purpose

Provide a repeatable quarterly process to validate WCAG 2.1 AA behavior, capture regressions, and assign fixes with clear ownership.

## Roles

- Accessibility Lead: approves scope, severity, and final report.
- Frontend Lead: resolves code defects and adds regression tests.
- QA Lead: executes manual keyboard and screen-reader checks.
- Release Manager: schedules audit windows and tracks completion.

## Audit Timeline (2-week window)

### Week 1: Automated + Manual Sweep

1. Run automated suites from repo root:
   - `npm run test:a11y:wcag`
   - `npm run test:a11y:wcag:strict`
   - `npm run test:a11y:reflow:strict`
   - `npm run test:a11y:contrast:strict`
   - `npm run test:a11y:playwright:suite`
   - `npm run test:a11y:phpunit:suite`
2. Run manual keyboard smoke checklist (`html/tests/ACCESSIBILITY_REGRESSION_WORKFLOW.md`).
3. Run manual VoiceOver sweep on:
   - `/`, `/auth/`, `/settings/`, `/sites/`, `/organizations/`, `/help/`, `/transparency/accessibility/`
4. Log each issue using `.github/ISSUE_TEMPLATE/accessibility-defect.yml`.

### Week 2: Remediation + Sign-off

1. Triage defects by severity and WCAG criterion.
2. Fix `P0` defects in current quarter; schedule `P1`/`P2` with owners.
3. Add/adjust tests for each fixed defect where practical.
4. Re-run full accessibility suite and attach evidence.
5. Publish quarterly report summary and residual risks.

## Severity Policy

- `P0`: blocks release or violates essential keyboard/screen-reader operation.
- `P1`: significant usability friction or repeated assistive-tech confusion.
- `P2`: enhancement or governance hardening.

## Accessibility SLA

| Severity | Triage standard | Response window | Fix or mitigation window | Release expectation |
| --- | --- | --- | --- | --- |
| `P0` | Release blocker or core task failure for keyboard/screen-reader users | Same business day | Within 48 hours | Must be fixed or mitigated before release |
| `P1` | Major usability friction or repeated AT confusion on important flows | Within 1 business day | Within 10 business days | Fix in current active cycle unless explicitly deferred by Accessibility Lead |
| `P2` | Localized issue, hardening work, or governance improvement | Within 3 business days | Schedule in next planned cycle | Track in backlog with owner and target milestone |

Operational rules:

1. Every accessibility defect must be assigned a severity, named owner, and due date at intake.
2. `P0` and `P1` defects must include a mitigation note if a same-cycle fix is not yet landed.
3. Release Manager and Accessibility Lead review overdue items during quarterly audit sign-off.

## Required Evidence

- Command output summary for strict suites.
- Route-by-route VoiceOver notes (pass/fail + defect links).
- WCAG criterion mapping for each defect.
- Severity, owner, due date, and mitigation status for each open defect.
- Final sign-off from Accessibility Lead and Release Manager.

## Annual External Audit

Recommendation: once per calendar year, run an independent external accessibility audit and map findings back into this backlog model.
