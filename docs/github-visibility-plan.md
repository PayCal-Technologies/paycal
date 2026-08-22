# GitHub Visibility Plan

This plan adapts the same discoverability work used for Clyde and Vigil to the PayCal public repository while preserving PayCal's proprietary license and privacy-first positioning.

## Repository Positioning

PayCal should be presented as a public source repository for a proprietary product, not as an open-source package. The public GitHub surface should make the project understandable, searchable, and trustworthy without implying permissive redistribution rights.

Primary message:

> Privacy-first work-hours, pay visibility, and payroll-adjacent reporting for workers and businesses.

Primary destinations:

- Product: https://paycal.app/
- Company: https://paycaltech.com/
- GitHub: https://github.com/PayCal-Technologies/paycal
- Policies: https://paycaltech.com/policies/
- Security: `security@paycal.app`

## Completed Baseline

- README top matter includes official links, CI badges, proprietary license badge, and an application preview image.
- Root-level community files exist: `CONTRIBUTING.md`, `SECURITY.md`, and `CODE_OF_CONDUCT.md`.
- Issue templates exist for bug reports, feature requests, and accessibility defects.
- Pull request template requires testing, accessibility, privacy/security, documentation, and release-note notes.
- Public examples live under `examples/`.
- GitHub social preview source exists at `.github/social-preview.svg`.

## Recommended GitHub Metadata

Description:

```text
Privacy-first work-hours, pay visibility, and payroll-adjacent reporting for workers and businesses.
```

Homepage:

```text
https://paycal.app/
```

Topics:

```text
php
payroll
time-tracking
privacy
accessibility
passkeys
webauthn
redis
stripe
phpstan
playwright
wcag
```

## Ongoing Visibility Procedure

1. Keep README badges accurate when workflows are renamed.
2. Keep the release summary, test inventory, and public feature summary current.
3. Add screenshots or product previews only when they do not expose private worker, business, billing, or account data.
4. Use issue labels that make public triage easy: `bug`, `feature`, `accessibility`, `security-private`, `documentation`, `good first issue`, `help wanted`, `privacy`, `tests`.
5. Review `CONTRIBUTING.md`, `SECURITY.md`, issue templates, and README links during each release checklist pass.
6. Keep GitHub topics aligned with the visible product and technology surface.

## Maintenance Notes

- Do not add permissive open-source language unless the license changes.
- Do not publish production screenshots, customer data, payroll data, Redis dumps, secrets, or real business reports.
- Security issues remain private-first through `security@paycal.app`.
- Public examples should prefer local-only flows, fake data, and explicit safety notes.
