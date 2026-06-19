# PayCal

Privacy-first payroll, work-entry, reporting, and business-member payroll visibility.

PayCal helps workers understand pay, taxes, sites, pay periods, and business-shared work data while keeping sensitive work records behind passkey authentication, encrypted envelopes, explicit consent, and audit trails.

Latest documented release: **v1.058.000**

[![Test Suite](https://img.shields.io/badge/tests-2151%20listed-blue)](html/tests/)
[![PHPStan](https://img.shields.io/badge/phpstan-level%209-brightgreen)](phpstan.neon)
[![License](https://img.shields.io/badge/license-Proprietary-lightgrey)](LICENSE.txt)

## Contents

- Current Release
- What PayCal Does
- Security And Privacy Model
- Business Protected Work Data
- Product Surface
- System Components
- Transparency Hub
- Localization
- Test And Quality Gates
- Developer Commands
- Recent Releases
- Documentation
- License

## Current Release

Version `1.058.000` is a protected business work-data lifecycle hardening release.

The current platform boundary is:

> Protected business member work rows may only originate from `BusinessProtectedDataAccess`.

This release closes the lifecycle around:

- actor authority
- active business membership
- target member consent
- active org DEK wrap
- encrypted envelope context
- business visibility policy
- protected read/report/export paths
- requested, started, completed, failed, and denied audit events
- revocation and cache purge behavior
- architecture tests that block direct protected-row materialization outside the canonical gate

Release tags:

- `v1.058.000`
- `private/v1.058.000`
- `public/v1.058.000`

## What PayCal Does

PayCal includes:

- encrypted work-entry tracking
- calendar-based time, site, and wage entry
- Canadian payroll and tax calculations
- daily, monthly, yearly, pay-period, and forecast reporting
- CSV/TXT browser convenience exports
- server-rendered XLSX/PDF report exports
- business workspaces for shared payroll visibility
- member, site, payroll, report, and audit business subpages
- pricing and subscription gates for Public, Premium, and Business tiers
- passkey-based account access
- optional federated sign-in/linking support for configured identity providers
- recovery-email and email-change verification flows
- account data portability with export, prepare-import, and commit-import stages
- accessibility, localization, transparency, and security documentation surfaces

## Security And Privacy Model

### Authentication

PayCal uses WebAuthn/FIDO2 passkeys instead of passwords.

Important account controls include:

- passwordless sign-in
- multi-credential passkey support
- account recovery by verified recovery email
- email-change verification across old and new inboxes
- step-up checks around sensitive mutations
- rate limiting and structured denial handling

### Encryption

Sensitive work data is stored as encrypted envelopes. The platform uses:

- client-side Web Crypto
- AES-GCM data encryption keys
- passkey-derived key wrapping for personal work data
- organization/business shared DEK wrap records for consented business visibility
- envelope context validation before protected business rows can be read

### Runtime Defenses

PayCal includes layered runtime controls:

- `RequestGuard` for request policy enforcement
- `SecurityLog` and business audit events for structured traceability
- `WorkEntryLockService` for historical record lock rules
- `Guardian` for TrustedHTML and DOM sink governance
- `ShadowTalon` for global PHP fault handling
- `Phantom Wing` for client telemetry
- `Lens` for controlled diagnostics and performance instrumentation
- `EmailGarum` for transactional email coordination
- `AriaEcho` for assistive UI narration
- extension runtime manifests for billing, admin, SOC2, earnings, and business-signal surfaces

The Superheroes system map is published at `/transparency/superheroes/`.

## Business Protected Work Data

Business protected work data follows this lifecycle:

```text
identity -> membership -> consent -> DEK wrap -> envelope -> visibility -> read -> report/export -> audit -> revoke -> cache purge
```

The canonical protected read path is:

```text
BusinessProtectedDataAccess
```

Report/export and cache consumers must receive protected rows from that gate. The release includes regression coverage for:

- forged business/member export payload rejection
- generic PDF/XLSX personal-only export behavior
- server-side report row re-read before XLSX/PDF rendering
- payroll package isolation from the generic exporter
- revoked member denial through stale cache paths
- business workspace/member/team earnings cache invalidation
- 100-member audit batch coherence
- architecture-level blocking of raw `MemberWorkEntriesFetcher` business use

CSV/TXT/ZIP browser artifacts remain convenience exports. Evidence-grade server-rendered exports use authorized server-side rows.

## Product Surface

### Personal Workspace

- calendar work-entry UI
- sites and wages
- pay periods
- earnings dashboards
- forecast workspace
- reports under `/reports/`
- settings, profile, security, passkeys, recovery email, and account lifecycle

### Business Workspace

Business routes are under `/business/` and `/api/v1/businesses/...`.

Current business subpages include:

- dashboard
- details
- members
- sites
- payroll
- reports
- audit
- compliance support pages

Business capabilities include:

- create/list business workspaces
- invite members
- request access to discoverable businesses
- approve/reject access requests
- manage member roles and revocation
- view member report dialogs through protected reads
- export member XLSX/PDF reports through server-side authorization
- link/create/unlink business-visible sites
- manage payroll/site settings
- bootstrap business encryption for active members
- inspect business and member audit timelines

### Admin, SOC, And Transparency

The repository includes admin and governance surfaces for:

- language editor and language dashboard
- user roles
- admin/security controls
- SOC and SOC2 status pages
- release ledger status
- Transparency Hub articles

## System Components

Source code is organized primarily under:

- `html/src/Domain/` for domain services, repositories, renderers, policies, and infrastructure adapters
- `html/src/Controllers/` for HTTP/API controllers
- `html/js/` for browser modules
- `html/business/` for business workspace pages and partials
- `html/extensions/` for runtime extension manifests, hooks, and overrides
- `html/tests/` for PHPUnit suites
- `strings/` for localization files
- `docs/` and `html/transparency/` for internal and public documentation

Key current domain components include:

- `BusinessProtectedDataAccess`
- `BusinessMemberReportExportService`
- `BusinessMemberReportsService`
- `BusinessDiscoveryService`
- `BusinessWorkspaceCache`
- `BusinessWorkspaceWarmer`
- `BusinessWorkVisibilityPolicy`
- `BusinessMemberReportCatalog`
- `MemberWorkEntriesFetcher`
- `EarningsPdf`
- `Xlsx`
- `SubscriptionGate`
- `SubscriptionRepository`
- `StripeBillingService`
- `FederatedAuth`
- `WorkEntry`
- `WorkEntryLockService`
- `SecurityLog`
- `SystemAuditPolicy`
- `html/extensions/runtime.php`

## Transparency Hub

Public transparency pages include articles for:

- protected business work data: `/transparency/protected-work-data-2026-06/`
- Superheroes system map: `/transparency/superheroes/`
- business membership
- members performance
- network capabilities
- authentication hardening
- diagnostics
- dependency CI
- verification governance
- SOC2
- testing
- security audit
- taxes
- accessibility

## Localization

PayCal currently ships 10 active language files:

- `de`
- `en`
- `es`
- `fr`
- `hi`
- `it`
- `nl`
- `pt`
- `tl`
- `tr`

Localization source files live in `strings/`. Backup files such as `*.bak` are not counted as active languages.

## Test And Quality Gates

Suite inventory (as of 2026-06-19):

- **2,151 listed tests**
- **244 repository test files**
- **Active public suite file split:** **122 Unit**, **62 Integration**, **35 Contract**, **1 Timezone**, **12 Accessibility**
- **SOC2 and Exploit suites are present in the tree but excluded from the public PHPUnit profile**
- **2 Manual verification files**

Latest validation snapshot (2026-06-19):

- **1,306 public quick tests**, **7,642 assertions**
- **0 failures**
- **0 errors**
- **PHPStan Level 9 clean**
- **Public release health clean**: quick PHPUnit, PHPStan Level 9, and policy meta checks passed during release verification

### Test Categories

| Configured public suite | Files | Purpose |
|------|------:|---------|
| PayCal Unit | 122 | Domain, service, renderer, policy, and invariant behavior |
| PayCal Integration | 62 | Controller/API flows, auth, encryption, account lifecycle, and cross-service behavior |
| PayCal Contract | 35 | Stable API, route, manifest, persistence, and architecture boundaries |
| PayCal Timezone | 1 | Timezone-sensitive pay-period behavior |
| PayCal Accessibility | 12 | ARIA, WCAG, keyboard, and accessibility contracts |

Current inventory reflects `phpunit.public.xml` as re-evaluated on 2026-06-19 via `./vendor/bin/phpunit --configuration phpunit.public.xml --list-tests`. Repository test-file count includes public-excluded SOC2/Exploit files and the two manual verification files.

## Developer Commands

Run commands from the repository root.

### PHP Quality

```bash
composer run phpstan
composer run phpstan:strict
composer run format:check
composer run quality:semantic-diff
composer run security:audit
```

### PHP Tests

```bash
composer run test
composer run test:quick
composer run test:unit
composer run test:integration
composer run test:contract
composer run test:soc2
composer run test:compliance
composer run test:knockknock
composer run test:affected
```

Useful direct PHPUnit commands:

```bash
vendor/bin/phpunit --configuration phpunit.xml
vendor/bin/phpunit --configuration phpunit.xml --testsuite "PayCal Unit"
vendor/bin/phpunit --configuration phpunit.xml --testsuite "PayCal Integration"
vendor/bin/phpunit --configuration phpunit.xml --testsuite "PayCal Contract"
vendor/bin/phpunit --configuration phpunit.xml --group soc2
```

### JavaScript And Accessibility

```bash
npm run test:smoke:ui
npm run test:aria:unit
npm run test:aria:smoke
npm run test:wcag:smoke
npm run test:a11y:all
npm run test:a11y:contrast
```

## Recent Releases

## v1.058.000 (2026-06-19)

**Release Focus:** Protected business work data lifecycle hardening

- Hardened PayCal Business protected work data so business member work rows can only be materialized through the canonical protected access gate.
- Closed legacy export bypasses for forged business/member markers and moved XLSX/PDF exports to server-side authorized row rendering.
- Enforced actor authority, active membership, consent, DEK wrap, encrypted envelope, business visibility, audit, revocation, and cache behavior with focused regression tests.
- Added architecture tests preventing direct business use of raw protected work fetchers.
- Published the protected-work-data Transparency Hub article.
- Verified with full PHPUnit: 2,274 tests, 18,892 assertions, 31 skipped.

## v1.057.006 (2026-06-15)

**Release Focus:** Mobile sidebar hamburger icon visibility fix

- Restored mobile sidebar toggle visibility across compact layouts.

## v1.057.005 (2026-06-15)

**Release Focus:** Mobile sidebar navigation and compact page-title bar

- Refined mobile navigation behavior and compact page-title presentation.

## v1.057.004 (2026-06-15)

**Release Focus:** Governance documentation cleanup

- Reframed GitHub as passive storage and local CI as the authoritative release gate.

## v1.057.000 (2026-06-12)

**Release Focus:** Business IA launch, three-tier billing, Argus observability, trust/listing moderation, and members workspace polish

- Shipped Public, Premium, and Business billing tiers.
- Rebuilt business workspace IA with dashboard, details, members, sites, payroll, reports, and audit pages.
- Added business trust, visibility, listing submission moderation, and members grid performance work.
- Expanded transparency, localization, diagnostics, and deployment reliability documentation.

Historical release detail lives in:

- `docs/CHANGELOG.md`
- `docs/v1.changelog.md`

## Documentation

Useful documentation entry points:

- `docs/CHANGELOG.md`
- `docs/v1.changelog.md`
- `docs/internal/SITE_OWNERSHIP_CONSOLIDATION_POLICY.md`
- `docs/security/ORG_SHARED_ENCRYPTION_IMPLEMENTATION_SPEC.md`
- `docs/security/ORG_SHARED_ENCRYPTION_TASK_BREAKDOWN.md`
- `docs/engineering/formatter-policy.md`
- `html/transparency/`

## License

Proprietary. See `LICENSE.txt`.
