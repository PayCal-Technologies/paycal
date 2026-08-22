# PayCal

Privacy-first work-hours, pay visibility, and payroll-adjacent reporting for workers and businesses.

Workers keep control of sensitive work history. Businesses get authorized reporting without broad plaintext access.

Latest documented release: **v1.059.014**

[![PHPUnit](https://github.com/PayCal-Technologies/paycal/actions/workflows/phpunit.yml/badge.svg)](https://github.com/PayCal-Technologies/paycal/actions/workflows/phpunit.yml)
[![PHPStan](https://github.com/PayCal-Technologies/paycal/actions/workflows/phpstan.yml/badge.svg)](https://github.com/PayCal-Technologies/paycal/actions/workflows/phpstan.yml)
[![JavaScript](https://github.com/PayCal-Technologies/paycal/actions/workflows/javascript.yml/badge.svg)](https://github.com/PayCal-Technologies/paycal/actions/workflows/javascript.yml)
[![Security Gates](https://github.com/PayCal-Technologies/paycal/actions/workflows/security-gates.yml/badge.svg)](https://github.com/PayCal-Technologies/paycal/actions/workflows/security-gates.yml)
[![Test Suite](https://img.shields.io/badge/tests-2454%20listed-blue)](html/tests/)
[![License](https://img.shields.io/badge/license-Proprietary-lightgrey)](LICENSE.txt)

![PayCal application preview](html/images/paycal_mockup.jpg)

## Official Links

- Product: https://paycal.app/
- Company: https://paycaltech.com/
- GitHub: https://github.com/PayCal-Technologies/paycal
- Security policy: [SECURITY.md](SECURITY.md)
- Contributor guide: [CONTRIBUTING.md](CONTRIBUTING.md)
- Public examples: [examples/README.md](examples/README.md)
- Privacy and terms: https://paycaltech.com/policies/

<!-- scribe:begin -->

<!-- scribe:what:begin -->
## What PayCal Does

PayCal helps workers track hours, sites, wages, pay periods, forecasts, and exports while giving businesses controlled payroll-adjacent visibility through consented reporting.

<!-- scribe:what:end -->

<!-- scribe:why:begin -->
## Why PayCal Exists

Workers need payroll clarity before questions become disputes. Businesses need operational visibility without turning every worker record into broadly readable company data.
<!-- scribe:why:end -->

<!-- scribe:audience:begin -->
## Who PayCal Serves

- Workers who want clear records of hours, sites, wages, pay periods, and expected pay.
- Businesses that need authorized reporting without broad plaintext access.
- Managers who review team, site, or group-level work activity.
- Developers maintaining privacy, accessibility, reporting, and payroll-adjacent workflows.
<!-- scribe:audience:end -->

<!-- scribe:features:begin -->
## Core Features

- Calendar work entries for tracking hours, sites, wages, pay periods, forecasts, and reports.
- Business groups for managing members, invitations, protected reports, and audit trails.
- Exports for browser downloads plus server-rendered PDF and XLSX reports.
- Account controls for passkeys, recovery, data portability, email changes, and lifecycle management.
<!-- scribe:features:end -->

<!-- scribe:trust:begin -->
## Privacy, Consent, and Security

- Passkeys protect authentication and sensitive step-up flows.
- Work records use encrypted envelopes and validated protected read paths.
- Personal data encryption uses DEK/KEK wrapping patterns; business visibility requires explicit consent and wrapped access.
- Business reads, reports, exports, revocation, cache purge, and audit trails must pass through the protected-data lifecycle.
<!-- scribe:trust:end -->

<!-- scribe:business-access:begin -->
## Business Access Model

Business access is permissioned, auditable, and revocable. PayCal should only expose protected work data after identity, membership, consent, and wrapped access have been validated.

```text
identity -> membership -> consent -> DEK wrap -> envelope -> visibility -> read -> report/export -> audit -> revoke -> cache purge
```
<!-- scribe:business-access:end -->

<!-- scribe:accessibility:begin -->
## Accessibility

Accessibility is product quality and compliance evidence. PayCal treats keyboard, contrast, reflow, screen-reader, and localization behavior as release-blocking surfaces when they affect user workflows.

- Keyboard and focus behavior for navigation, menus, dialogs, datagrids, and settings.
- Theme-token contrast gates with PayCal's stricter 5.0:1 policy where applicable.
- Reflow, text-spacing, axe Playwright, Lightpanda, and optional scanner evidence.
- Accessibility documentation under `docs/a11y/` and PHPUnit/Playwright regression suites.
<!-- scribe:accessibility:end -->

<!-- scribe:architecture:begin -->
## Architecture Map

- PHP application code under `html/src/Domain/`, `html/src/Controllers/`, `html/src/Infrastructure/`, and `html/src/Observability/`.
- Redis-backed state with explicit DB separation for local, test, and production expectations.
- Browser modules under `html/js/` for calendar, reports, crypto, accessibility, and UI behavior.
- Business workspace pages under `html/business/` and extension manifests under `html/extensions/`.
- Vigil provides local policy, README, hook, accessibility, test, and release-support gates.
<!-- scribe:architecture:end -->

<!-- scribe:platform-agents:begin -->
## Platform Agents

| Agent | Responsibility |
|-------|----------------|
| Argus | Scoped diagnostic traces with expiry, redaction, and admin-controlled presets. |
| Lens | Controlled performance and debugging signals without becoming a plaintext data channel. |
| EmailGarum | Transactional email for verification, recovery, and account-change flows. |
| Vigil | Local policy, hook, accessibility, test, release, and repository-health gates. |
| Scribe | Trust-first README shape and generated README components. |
| Guardian | TrustedHTML and browser DOM sink governance. |
| ShadowTalon | Global PHP fault capture and structured failure reporting. |
| Phantom Wing | Bounded browser-side client telemetry. |
| AriaEcho | Assistive UI narration and live-region behavior. |
| GoldMaster | Canonical code, UI, test, and architecture examples for humans and agents. |
| Tabularium | Structured report artifacts alongside PDF/XLSX export services. |
<!-- scribe:platform-agents:end -->

<!-- scribe:github-install:begin -->
## GitHub Install

```bash
git clone https://github.com/PayCal-Technologies/paycal.git paycal
cd paycal
composer install
npm install
cp html/.env.example html/.env
./vigil/bin/vigil readme:check
composer run test:quick
```

Set local environment values in `html/.env`, run Redis/PHP/nginx for the target host, and keep production deploys on the ledger-driven release flow.
<!-- scribe:github-install:end -->

<!-- scribe:quality:begin -->
## Developer Quality Gates

| Signal | Current value |
|--------|--------------:|
| PHPUnit listed tests | 2454 |
| Configured PHPUnit files | 290 |
| Repository PHP test files | 290 |
| Frontend tests | 23 Playwright specs, 25 JS/MJS files |
| Static analysis | PHPStan Level 9, ESLint, Vigil policy checks |

Suite inventory (as of 2026-08-22):

- **2,454 listed tests**
- **290 repository test files**
- **Active public suite file split:** **145 Unit**, **60 Integration**, **41 Contract**, **1 Timezone**, **24 Accessibility**
- **2 Indigenous suite files**
- **2 Manual verification files**

Current inventory reflects `phpunit.public.xml` as re-evaluated on 2026-08-22 via `./vendor/bin/phpunit --configuration phpunit.public.xml --list-tests`. Repository test-file count follows the README policy inventory and includes the two manual verification files, which are not configured PHPUnit suites.
<!-- scribe:quality:end -->

<!-- scribe:recent-releases:begin -->
## Recent Releases

## v1.059.014 (2026-08-22)

**Release Focus:** Public GitHub visibility, community files, and current repository metadata.

- Adds root-level contributor, security, and code-of-conduct guidance tailored to PayCal's proprietary public source model.
- Adds GitHub bug and feature issue templates, contact routing, a social preview asset, public examples, and a GitHub visibility plan.
- Refreshes the README with current CI badges, official PayCal links, repository links, preview media, and current test inventory.
- Updates GitHub repository description, homepage, and topics for discoverability.
- Refreshes PHPStan from 2.2.8 to 2.2.9 while preserving the repository's documented conflict-blocked PHPUnit line.

## v1.059.013 (2026-08-19)

**Release Focus:** Cross-tab coordination and foldable mobile layout support.

- Adds feature-detected Web Locks around business settings, personal settings, and realtime audit polling so duplicate tabs coordinate write and polling work.
- Adds progressive CSS viewport segment support for multi-screen mobile devices, including mobile shell placement, dialog bounds, and business route refinements.
- Mirrors the private release version for the Web Locks and foldable mobile support update.

## v1.059.012 (2026-08-18)

**Release Focus:** Front-end runtime health fixes for account and business route loading.

- Fixes settings account profile JavaScript imports and account activity rendering.
- Treats denied best-effort business cache warm requests as non-fatal background work.
- Mirrors the private release version for the authenticated smoke coverage expansion.

## v1.059.011 (2026-06-26)

**Release Focus:** Calendar/datagrid accessibility improvements and README trust positioning.

- See `docs/CHANGELOG.md` and `docs/v1.changelog.md` for concise technical release notes.
<!-- scribe:recent-releases:end -->

<!-- scribe:license-contact:begin -->
## License and Contact

PayCal is proprietary software.

- Website: https://paycal.app
- Company: PayCal Technologies Inc. — https://paycaltech.com
- Support: support@paycal.app
- Privacy: https://paycaltech.com/policies/#privacy
- Terms: https://paycaltech.com/policies/#terms
- Security/contact: security@paycal.app
<!-- scribe:license-contact:end -->
<!-- scribe:end -->
