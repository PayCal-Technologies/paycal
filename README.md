# PayCal

Privacy-first payroll, work-entry, reporting, and business-member payroll visibility.

PayCal helps workers understand pay, taxes, sites, pay periods, and business-shared work data while keeping sensitive work records behind passkey authentication, encrypted envelopes, explicit consent, and audit trails.

Latest documented release: **v1.059.011**

[![Test Suite](https://img.shields.io/badge/tests-2452%20listed-blue)](html/tests/)
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

Version `1.059.009` is the Business members reports dialog release.

The current remediation boundary is:

> Protected data, compatibility cleanup, date math, Redis migrations, security findings, and durable settings preferences must be proven by audits, bounded behavior, and regression tests before old assumptions are removed.

This release closes the lifecycle around:

- protected business work-data access, export, revocation, cache, and audit guardrails
- the biweekly pay-period DST bug that could affect earnings grouping and lazy report rendering
- route shims, redirects, method aliases, placeholder classes, generated TODO noise, and stale compatibility paths
- Redis connection-index drift, relationship/metaphor migration, field-name compatibility checks, and fake-persona audit tagging
- crypto/passkey compatibility telemetry, plaintext work-entry audit tooling, and guarded compatibility decisions
- closed security findings for IP handling, CORS, constant-time comparisons, dev security flags, chain hashes, rate-limit keys, and admin test output
- delayed sidebar hover intent and cancellation
- grouped sidebar navigation across collapsed and expanded states
- bottom-anchored Settings, Help, and Sign Out utility actions
- 16 spectrum accent swatches with hover popovers
- shared accent tokens and live appearance preview feedback
- Theme and Mode layout consistency
- canonical top/bottom notification position values
- contrast-tested settings nav hover, active, and focus states
- contract tests that block regressions in the settings UI boundary

Release tags:

- `v1.059.009`
- `private/v1.059.009`
- `public/v1.059.009`

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
- business-shared DEK wrap records for consented business visibility
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
- `GoldMaster` for canonical code, UI, test, and architecture examples
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
- GoldMaster canonical-example browser
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
- `golden_masters/` for curated canonical examples used by humans and AI agents

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
- `GoldMasterCatalog`
- `html/extensions/runtime.php`

## Transparency Hub

Public transparency pages include articles for:

- protected business work data: `/transparency/protected-work-data-2026-06/`
- GoldMaster canonical examples: `/transparency/goldmaster/`
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

Suite inventory (as of 2026-06-26):

- **2,452 listed tests**
- **288 repository test files**
- **Active public suite file split:** **144 Unit**, **61 Integration**, **41 Contract**, **1 Timezone**, **24 Accessibility**
- **2 Manual verification files**

Latest validation snapshot (2026-06-22):

- **2,362 tests**, **20,231 assertions**, **1 skipped**
- **0 failures**
- **0 errors**
- **PHPStan Level 9 clean**
- **JavaScript security check clean**
- **Public repository health gate clean** via `bash scripts/check-public-repo-health.sh /private/var/www/paycal`

### Test Categories

| Configured public suite | Files | Purpose |
|------|------:|---------|
| PayCal Unit | 133 | Domain, service, renderer, policy, and invariant behavior |
| PayCal Integration | 61 | Controller/API flows, auth, encryption, account lifecycle, and cross-service behavior |
| PayCal Contract | 40 | Stable API, route, manifest, persistence, and architecture boundaries |
| PayCal Timezone | 1 | Timezone-sensitive pay-period behavior |
| PayCal Accessibility | 24 | ARIA, WCAG, keyboard, and accessibility contracts |

Current inventory reflects `phpunit.public.xml` as re-evaluated on 2026-06-26 via `./vendor/bin/phpunit --configuration phpunit.public.xml --list-tests`. Repository test-file count includes public-excluded SOC2/Exploit/private-moat files and the two manual verification files.

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
npm run test:js
npm run test:smoke:ui
npm run test:aria:unit
npm run test:aria:smoke
npm run test:wcag:smoke
npm run test:a11y:all
npm run test:a11y:contrast
```

## Recent Releases

## v1.059.011 (2026-06-26)

- WCAG 4.75 contrast for calendar earnings badges and shared surfaces.
- Calendar Alt/Option earnings hover tooltip; keyboard scroll-into-view for month grid and datagrids.

## v1.059.010 (2026-06-26)

- Accessibility: unique keyboard shortcuts (no duplicate accesskeys), `inert` on aria-hidden panels, focus contract coverage.
- SEO: dynamic `robots.php` / `sitemap.php`, host-aware `X-Robots-Tag`, crawl policy for auth surfaces.
- Performance: `Cache-Control: no-store` on PHP JS modules; nginx static asset cache documentation updates.
- Dev/mac: optional inline source maps for PHP JS bundles (not enabled in production).

## Unreleased (2026-06-21)

This work adds GoldMaster, a read-only admin/dev catalog for canonical PayCal examples, plus the first dialog golden master and related Transparency Hub documentation.

## v1.059.009 (2026-06-21)

This release converts Business member report options into a modal dialog with editable selected-member pillboxes, add/remove member search, and all-members grid rendering without pagination controls.

## v1.059.008 (2026-06-21)

This release fixes the Business members selection toolbar height so selected-row actions no longer push the member list downward.

## v1.059.007 (2026-06-21)

This release makes Business members selection behave more like Gmail: the header checkbox controls visible selections, selected-row actions swap into the existing control strip without moving the list, and redundant Pending/Selected metric chips are removed.

## v1.059.006 (2026-06-21)

This release refines the Business members workspace into a calmer, multi-row toolbar with compact count chips, a real role filter, hidden bulk actions until selection, clearer bulk labels, and grouped pagination controls.

## v1.059.005 (2026-06-21)

This release fixes the Business members guide info button so it renders as a compact circle instead of stretching across the metric strip.

## v1.059.004 (2026-06-21)

This release tightens the Business members guide into a quick-reference dialog with compact workspace controls, role capability badges, a concise role matrix, and quieter dialog controls.

## v1.059.003 (2026-06-21)

This release adds the Members guide dialog to the Business members page. The control strip now includes a circled info button that opens a full-width role guide explaining member metrics and each workspace role.

## v1.059.002 (2026-06-21)

This release adds a friendly Business members page guard for users who belong to an org but do not have access-management permissions. The page now shows the existing access-management message instead of making restricted member, invite, and access-request calls that return 403 responses.

## v1.059.001 (2026-06-21)

**Release Focus:** Passkey DEK guard and device-registration cache refresh

- Blocked calendar DEK regeneration when an account already has passkey-wrapped DEKs for other credentials.
- Made add-passkey setup require an existing DEK unlock before registering another passkey on accounts with encrypted work history.
- Added a pre-save encrypted-week decryptability check and replaced raw crypto-worker diagnostics with an actionable passkey unlock mismatch message.
- Bumped the app asset version so browsers fetch the corrected calendar and settings scripts.

## v1.059.000 (2026-06-19)

**Release Focus:** June 2026 remediation transparency release

- Published the June 2026 remediation Transparency Hub article covering protected work-data boundaries, compatibility cleanup, Redis drift, crypto/plaintext readiness, pay-period DST correctness, security findings, and settings controls.
- Fixed the biweekly pay-period DST bug by switching period navigation to calendar-day differences; added the America/Edmonton spring DST regression and re-enabled the affected lazy earnings render test.
- Removed stale route shims, redirects, API aliases, method wrappers, placeholder classes, generated TODO noise, and compatibility branches where audits proved them safe to retire.
- Repaired Redis connection-index drift, migrated old relationship/metaphor data, replaced stale Redis field-migration tooling, and documented checkpoint/verification results.
- Added crypto/passkey compatibility telemetry, plaintext work-entry audit tooling, work-entry alias audit evidence, and snapshot backfill verification while keeping real-user plaintext compatibility guarded.
- Added bounded sidebar hover Trigger timing, grouped sidebar navigation, 16 accent swatches, live appearance preview feedback, canonical notification position values, and contrast-tested settings nav states.
- Verified with protected-data, pay-period, Redis/connection, crypto/plaintext, security, and settings regression suites plus full PHPUnit: 2,318 tests, 19,222 assertions, 29 skipped.

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
- `docs/security/BUSINESS_SHARED_ENCRYPTION_IMPLEMENTATION_SPEC.md`
- `docs/security/BUSINESS_SHARED_ENCRYPTION_TASK_BREAKDOWN.md`
- `docs/engineering/formatter-policy.md`
- `html/transparency/`

## License

Proprietary. See `LICENSE.txt`.
