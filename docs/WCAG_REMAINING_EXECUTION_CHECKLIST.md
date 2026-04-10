# WCAG Remaining Execution Checklist

Date: 2026-03-23
Owner model: assign named people to the role placeholders below before execution.

## Scope

This checklist covers the two remaining open tracks from the WCAG plan:
- Screen reader optimization
- Quarterly accessibility audits

## Owners

- Accessibility Lead: accountable for WCAG decisions, defect severity, and release sign-off.
- Frontend Lead: accountable for semantic/ARIA/focus fixes and regression tests.
- QA Lead: accountable for manual assistive technology runs and evidence capture.
- Release Manager: accountable for quarterly cadence and audit completion tracking.

## Execution Checklist

- [ ] Assign named owners to each role and publish in sprint board.
- [ ] Run VoiceOver pass on macOS across `/`, `/auth/`, `/settings/`, `/sites/`, `/organizations/`, `/help/`, `/transparency/accessibility/`.
- [ ] Record defects using `.github/ISSUE_TEMPLATE/accessibility-defect.yml` with WCAG criterion and route.
- [ ] Fix P0/P1 assistive-tech defects and add regression coverage where feasible.
- [ ] Re-run `npm run test:a11y:all` after fixes and attach output summary to release notes.
- [ ] Execute quarterly audit workflow from `docs/WCAG_QUARTERLY_AUDIT_PLAYBOOK.md`.
- [ ] Publish audit summary + outstanding risks in transparency/a11y notes.

## Route Coverage Matrix

| Route | Primary Owner | Backup Owner | Manual AT Required | Notes |
| --- | --- | --- | --- | --- |
| `/` | Frontend Lead | QA Lead | Yes | Calendar grid, context menu, month status announcements |
| `/auth/` | Frontend Lead | Accessibility Lead | Yes | Tablists, alert/status messaging, passkey async states |
| `/settings/` | Frontend Lead | QA Lead | Yes | Multi-dialog focus management, status regions, destructive flows |
| `/sites/` | QA Lead | Frontend Lead | Yes | Tablist + datagrid instructions and updates |
| `/organizations/` | QA Lead | Frontend Lead | Yes | Datagrid semantics and live announcements |
| `/help/` | QA Lead | Accessibility Lead | Yes | Heading structure, breadcrumbs, media alternatives |
| `/transparency/accessibility/` | Accessibility Lead | Release Manager | Yes | Policy clarity, feedback form path, shortcut exception docs |

## Exit Criteria

- No open `P0` accessibility defects.
- All new `P1` defects have owner + due date.
- VoiceOver pass evidence attached for all covered routes.
- Quarterly audit report published and linked from release notes.
