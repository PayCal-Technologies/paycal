# PayCal™

**Privacy-first payroll tracking with zero-knowledge encryption and passkey authentication**

PayCal™ is a payroll tracking platform focused on transparency, accessibility, and strong privacy protections. Earnings data is encrypted client-side before it reaches the server, and authentication relies entirely on modern passkeys instead of passwords.

The goal is simple: make pay easier to understand while keeping personal financial data private.

> Historical note: this public repository initial commit (including this README replacement) is derived from approximately three years of prior development in a private PayCal codebase.

Public repository: https://github.com/PayCal-Technologies/paycal

Latest documented release: **v1.049.000**

[![Test
Suite](https://img.shields.io/badge/tests-1479%20listed-blue)](html/tests/)
[![PHPStan](https://img.shields.io/badge/phpstan-level%209-brightgreen)](phpstan.neon)
[![License](https://img.shields.io/badge/license-Proprietary-lightgrey)](LICENSE.txt)

---

# Table of Contents

- Security Architecture
- Core Features
- System Components
- Test Coverage
- Technology Stack
- Getting Started
- Recent Releases
- Documentation
- Contributing
- License

---

# Security Architecture

## Passkey Authentication

PayCal uses **WebAuthn / FIDO2 passkeys** instead of passwords.

Key characteristics:

- Passwordless authentication
- Hardware-backed private keys (TPM, Secure Enclave, security keys)
- Multi-credential support
- Cross-device passkey registration
- UX protections against accidental lockout (requires ≥2 passkeys before deletion)

Passkeys help protect against phishing and credential reuse attacks.

---

## Zero-Knowledge Encryption

Sensitive work entries are encrypted **in the browser before transmission**.  
The server stores only encrypted data.

### Encryption Design

| Component | Description |
|-----------|-------------|
| DEK | 256-bit AES-GCM Data Encryption Key generated client-side |
| KEK | Key Encryption Key derived from passkey credential ID and server salt |
| Work entries | Encrypted with DEK using AES-GCM |
| Key wrapping | DEK wrapped with KEK and stored server-side |

### Security Characteristics

- Client-side encryption via Web Crypto API
- Server stores only ciphertext envelopes
- Authenticated encryption prevents tampering
- Deterministic KEK derivation for passkey recovery

### Threat Model Coverage

| Scenario | Protection |
|---------|------------|
| Network interception | TLS + encrypted payloads |
| Database breach | Ciphertext-only storage |
| Unauthorized access | Passkey authentication required |
| Password attacks | Not applicable (no passwords) |

---

# Core Features

## Work Entry Management

- Encrypted calendar interface for logging work hours
- Configurable historical record locking (0–3 day grace period)
- Multi-site wage tracking
- Instant earnings preview calculations

## Payroll and Tax

- Canadian federal and provincial tax support
- CPP and EI deduction calculations
- Pay period options:
  - Weekly
  - Bi‑weekly
  - Semi‑monthly
  - Monthly
- CSV export for payroll processing

## Security Controls

- Redis-based rate limiting
- Structured audit logging
- Multi-layer historical record lock enforcement
- Server‑validated encrypted work entries

## Account Security and Recovery

- Recovery-email based account ownership verification
- Passwordless email-change flow with dual inbox confirmation
- Step-up sensitive-action guards for account mutation paths
- 6-character verification code consistency across backend and UI

## Accessibility and UX

- WCAG 2.1 AA accessibility support
- Keyboard navigation and screen reader compatibility
- Internationalization (11 languages)
- Optional audio feedback
- Mobile‑friendly responsive design
- Dark and light themes
- Automated route-level WCAG regression scans via `npm run test:a11y:wcag`
- Strict accessibility gate option via `npm run test:a11y:wcag:strict`
- Automated reflow/text-spacing regression scans via `npm run test:a11y:reflow`
- Strict reflow/text-spacing gate option via `npm run test:a11y:reflow:strict`
- Automated theme contrast/focus matrix report via `npm run test:a11y:contrast`
- Strict contrast gate option via `npm run test:a11y:contrast:strict`
- ARIA contract suite (PHPUnit) via `npm run test:aria:unit`
- ARIA behavior smoke suite (Playwright) via `npm run test:aria:smoke`
- WCAG smoke sweep suite (Playwright) via `npm run test:wcag:smoke`
- Lazy section loading on earnings view (DOMContentLoaded: -88.93% on real data)
- HTTP/3 (QUIC) support with Alt-Svc advertisement
- Network security header and transport policy article at `/transparency/network-capabilities/`

---

# System Components

PayCal organizes functionality into clearly scoped components.

## Email and Verification

| Component | Role |
|-----------|------|
| EmailGarum | Transactional email coordination |
| EmailTransport | SMTP delivery layer |
| EmailVerificationController | Verification workflow handling |

## Security

| Component | Role |
|-----------|------|
| ShadowTalon | Global PHP fault guardian with dedicated daily rotating logs |
| Guardian | TrustedHTML protection for DOM operations |
| RequestGuard | Request policy enforcement |
| RateLimiter | Abuse prevention |
| SecurityLog | Structured audit events |
| WorkEntryLockService | Historical record locking |

## Observability and Client Core

| Component | Role |
|-----------|------|
| Phantom Wing | Client telemetry and error collection |
| Lens | Controlled diagnostics layer |
| PayCalCore | Shared browser utilities |

---

# Test Coverage

Suite inventory (as of 2026-04-09):

- **1,479 listed tests**
- **161 test files**
- **70 Unit**, **53 Integration**, **30 Contract**, **2 Manual**

Latest validation snapshot (2026-03-26):

- **0 test failures**
- **0 test errors**
- **26 skipped tests**
- **1 PHPUnit runner warning**
- **8 PHPUnit deprecations**
- **PHPStan Level 9 clean**

### Test Categories

| Type | Description |
|------|-------------|
| Unit tests (70 files) | Domain logic and service-layer behavior, including security, accessibility, and fault-surface safety checks |
| Integration tests (53 files) | Full-stack workflows including encryption/passkey, auth/account lifecycle, email, and controller/API behavior |
| Contract tests (30 files) | API and persistence boundary guarantees |
| Manual tests (2 files) | Operator verification scripts for complex scenarios |

Current inventory reflects the active PHPUnit suite layout in `phpunit.xml` as re-evaluated on 2026-04-09 via `./vendor/bin/phpunit --configuration phpunit.xml --list-tests`.

### Run Tests

```bash
cd html
composer run test
composer run test:unit
composer run test:integration
composer run test:contract
composer run test:quick
composer run test:junit
composer run test:coverage
composer run test:knockknock
composer run test:affected
```

Direct PHPUnit equivalents:

```bash
cd html
./vendor/bin/phpunit --configuration phpunit.xml tests/Unit tests/Integration tests/Contract
./vendor/bin/phpunit --configuration phpunit.xml --testsuite "PayCal Unit"
./vendor/bin/phpunit --configuration phpunit.xml --testsuite "PayCal Integration"
./vendor/bin/phpunit --configuration phpunit.xml --testsuite "PayCal Contract"
./vendor/bin/phpunit --configuration phpunit.xml --log-junit ../junit.xml tests/Unit tests/Integration tests/Contract
```

Live email template sweep (opt-in, real SMTP):

```bash
cd html
PAYCAL_RUN_LIVE_EMAIL_SWEEP=1 PAYCAL_LIVE_EMAIL_RECIPIENT=you@example.com \
  ./vendor/bin/phpunit --configuration phpunit.xml tests/Integration/LiveEmailTemplateSweepTest.php
```

Optional UI smoke tests (manual, non-blocking):

```bash
# one-time browser install
npm run test:smoke:ui:install

# headless smoke run against local/dev URL
npm run test:smoke:ui

# headed mode for interactive debugging
npm run test:smoke:ui:headed
```

Notes:

- Smoke specs that touch authenticated pages assume an already-authenticated browser session.
- Not part of git hooks and not required for PR merge gates.
- Override target host with `PAYCAL_SMOKE_BASE_URL`, for example:
  - `PAYCAL_SMOKE_BASE_URL=https://dev.paycal.local npm run test:smoke:ui`

Static analysis:

- PHPStan Level 9
- Zero baseline suppressions
- Pre‑commit validation

---

# Technology Stack

## Backend

- PHP 8.4+
- Redis 7.x
- WebAuthn via `lbuchs/WebAuthn`

## Frontend

- Vanilla JavaScript
- Web Crypto API
- WebAuthn API
- Standards‑based CSS

## Infrastructure

- Redis‑backed sessions
- Strict Content Security Policy
- Git‑based deployment workflows

---

# Getting Started

## Requirements

- PHP 8.4+
- Redis 7.x
- Modern browser with WebAuthn support

## Installation

```bash
git clone https://github.com/cshaiku/paycal.git
cd paycal/html

composer install --no-dev --optimize-autoloader

cp config.example.php config.php
# edit Redis settings

./vendor/bin/phpunit -c phpunit.xml

php -S localhost:8000
```

## Native Quick Start (Recommended)

```bash
cd <REPO_ROOT>

# start local stack
bash scripts/native-up.sh

# run diagnostics
bash scripts/native-diagnostics.sh

# open interactive helper menu
bash scripts/native-menu.sh menu
```

Useful URLs:

- `https://dev.paycal.local/auth/`

Useful native commands:

```bash
# stop local native services
bash scripts/native-down.sh

# repair redis launchagent conflicts if needed
bash scripts/native-fix-redis-launchagent.sh
```

## First Run

1. Navigate to `/auth/?auth_tab=register`
2. Create an account using a passkey
3. The browser generates a DEK client‑side
4. The DEK is wrapped using a passkey‑derived KEK
5. Begin logging work hours

---

# Recent Releases

## v1.049.000 (2026-04-09)

- Expanded transparency governance surfaces by adding publication timestamps across hub and article pages in all maintained locales, with matching styling updates for consistent chronology rendering.
- Hardened verification resend reliability and observability with explicit pipeline/stage logs, transport-failure security logs, Redis-timeout classification, configured-sender enforcement, and code-only fallback when link email delivery fails.
- Improved account and shell UX with verification resend cooldown alignment, busy-state feedback treatment, authenticated-header cleanup, and navigation/language tray refinements.
- Advanced organizations workflows with role-scope/manager-control refinements, notification and panel UX updates, service/controller hardening, and synchronized English/French string updates.
- Consolidated earnings parity and dashboard follow-through across backend calculations, controller/data flow cleanup, extension hook parity, view/template updates, CSS/JS adjustments, and targeted integration/unit coverage expansions.
- Completed release hygiene synchronization for `VERSION`, `README.md`, `docs/CHANGELOG.md`, `docs/v1.changelog.md`, and `ai-notes/PAYCAL_SOURCE_OF_TRUTH.md`.
- Recomputed live test inventory from the active suite and filesystem: **1,479 listed tests** across **161 test files** (`70 Unit / 53 Integration / 30 Contract / 2 Manual`).
- Validated this release line with full PHPUnit, PHPStan level 9 strict analysis, JS quality gate, and full accessibility stack.

## v1.048.000 (2026-04-07)

- Added consent-bound organization DEK wrap lifecycle enforcement, including membership/consent validation, wrap revocation, and strict org envelope metadata validation paths.
- Expanded earnings runtime and UX behavior with extension follow-through (historical intelligence/piegraphs), theme parity cleanup, localized numeric formatting, and export hardening (REF code generation, route/JSON validation, audit logging).
- Added passkey mismatch recovery flow and related auth error-path fixes; migrated organizations delegate semantics to member across backend/UI/tests/strings.
- Added opt-in diagnostics controls (default-off), polished settings/admin/shell UX (footer rhythm, nav ordering, keyboard shortcut labels), and updated branding with the new PayCal shield treatment in header and home icon surfaces.
- Added calendar root month permalinks, updated public media delivery to YouTube embed/frame-source flow, and captured routing/ops follow-through (FastCGI blog path notes and private KnockKnock report routing defaults).
- Strengthened release quality controls with docblock gate enforcement and policy/hardening updates, then validated release commits with PHPStan level 9 and focused suite execution.

## v1.047.001 (2026-04-03)

- Added compact sidebar interaction refinements for small screens, including outside-content click collapse behavior and tightened spacing cadence.
- Improved footer presentation with centered trademark copy and balanced wrapping treatment.
- Hardened language switcher flyout behavior so full locale options remain reachable near viewport edges.
- Added `100svh` minimum block sizing for footer shell behavior on small viewport-height devices.
- Captured server-side IP address and browser/user-agent details on all contact submissions to aid spam triage.

## v1.047.000 (2026-04-03)

- Expanded localization across auth, blog, help, transparency, and shared navigation with `?l=` language override/propagation, translated blog article bodies, and localized verification UI modules.
- Completed extension runtime separation with isolated bootstrap/hook/capability bridges, override scaffolding under `html/extensions/`, admin extension-boundary extraction, and published extension-system transparency/docs.
- Added Stripe webhook queue processing, queue monitoring/alerting, richer webhook failure telemetry, DataGrid-backed Stripe admin tables, and Stripe billing smoke coverage.
- Added browser-driven test administration with `/api/tests/run.php` and `/api/tests/results.php`, live result streaming/download UI, stop/cancel controls, and `PHP_BINARY`/workspace-root execution hardening.
- Hardened calendar editor recovery/DEK bootstrap plus auth, recovery, verification, and organization failure handling, and refreshed native ops/health-monitor tooling.

## v1.046.000 (2026-03-31)

- Launched markdown-backed blog system with clean routes: `/blog/`, `/blog/{slug}/`, and `/blog/tags/{tag}/`.
- Added `BlogRepository` parsing/rendering pipeline for frontmatter, snippets, filtering, pagination, and previous/next navigation.
- Added filesystem tag indexes (`html/blog/tags/*.tag`) that map tags to markdown filenames for deterministic tag filtering.
- Added shared-shell integration (header/footer navigation + page mapping) and blog-specific stylesheet endpoint.
- Added backward-compatible redirect from legacy `?slug=` blog URLs to canonical clean slug URLs.

## v1.045.000 (2026-03-31)

- Hardened redirect/request boundaries and billing mutation security behavior in active billing flows.
- Tightened CSRF-sensitive billing controller validation and aligned core billing JS request handling.
- Updated localization strings to keep billing/security state messaging consistent with backend enforcement.
- Published `docs/security/APPSEC_SWEEP_2026-03-31.md` with security sweep scope, findings, and closure evidence.

## v1.044.002 (2026-03-26)

- Re-evaluated and documented the current PHPUnit suite state: **1,246 tests**, **7,018 assertions**, **26 skipped**, with runner status surfaced (`1 warning`, `8 deprecations`).
- Refreshed README test inventory to match current suite structure (**124 files**: `62 Unit / 52 Integration / 8 Contract / 2 Manual`).
- Added release documentation for ShadowTalon runtime observability enhancement: structured `[ShadowTalonRef]` capture and Lens error telemetry fanout.
- Synchronized release metadata for the `1.044.002` patch line (`VERSION`, README, rolling changelogs).

## v1.044.001 (2026-03-26)

- Completed organization access-request lifecycle end-to-end: request submission, owner listing, approve/reject routes, and owner-facing editor actions.
- Added organization signal fanout seam (`OrganizationSignalHooks`) so core emits normalized events while optional extension hooks can persist owner-scoped signal rows.
- Hardened organization cleanup to remove access-request records, requester/org indexes, and active requester pointers on organization deletion.
- Added service and controller integration coverage for access-request submit/approve/reject flows and response-contract behavior.

## v1.043.021 (2026-03-25)

- Consolidated profile and billing UX flows with cleaner free vs premium state handling and stricter network response contracts.
- Removed a legacy `innerHTML` decode sink in organizations JS to keep security sink enforcement green.
- Hardened account deletion confirmation by requiring `DELETE MY ACCOUNT` consistently across UI and backend validation.
- Updated profile/organization language to reflect profile-linked external organizations and simplified request/delegate controls.
- Completed `/mis` release hygiene sync across version/changelog/README/source-of-truth metadata.
- Added release/governance discovery links on transparency, help, policies, and about pages.

## v1.043.001 (2026-03-24)

- Release metadata and transparency synchronization for the `1.043.x` line.
- Added public transparency articles for testing framework and backend/framework change tracking.
- Updated security-audit and verification-governance transparency pages to the 2026-03-24 verification state.
- Documented Guardian sanitizer test refactor as configuration-anchored coverage.

## v1.043.000 (2026-03-24)

- Expanded security test coverage for CSP report ingestion, capability token lifecycle controls, and Guardian hardening.
- Added `AccessibilityHelper` domain utilities for ARIA patterns, screen-reader support, and keyboard/focus behavior.
- Finalized security audit handoff updates for controls E-I and aligned release evidence artifacts.

## v1.042.000 (2026-03-24)

- Completed BRS-01 through BRS-05 security hardening: CSP nonce/strict-dynamic policy, capability tokens, credential bridge removal, runtime integrity monitor, and Guardian sanitizer expansion.

## v1.041.000 (2026-03-23)

- Security phase-closure PASS release with lifecycle hardening and transparency publication.

## v1.039.000 (2026-03-23)

- Consolidated internal working notes under repo-root `ai-notes/`, moved test/reference markdown out of `html/`, and aligned release/source-of-truth documentation to the new layout.
- Simplified public authentication routing so `/auth/` is the single sign-in and registration entry point, with registration handled through `/auth/?auth_tab=register` and legacy shims removed.
- Hardened the live `html/internal/opcache-reset/` maintenance endpoint to require an authenticated admin user and `POST` requests.

## v1.038.001 (2026-03-23)

- Release cleanup sweep for the `1.038.x` line: bumped `VERSION`, synchronized README/changelog metadata, and published a new live source-of-truth note under `ai-notes/`.
- Refreshed documented test inventory to **1,134 listed tests** across **104 files** (`52 Unit / 46 Integration / 4 Contract / 2 Manual`).
- Updated WCAG contrast reporting totals to **2,040 checks**, with all **68 themes** still passing at **0 failures** and **0 unresolved**.
- Refreshed dev verification tooling through Composer lock updates, including `infection/infection` `^0.32` plus current locked `phpstan/phpstan` and `phpunit/phpunit` patch versions.

## v1.037.000 (2026-03-22)

- Introduced Organizations model replacing Teams: per-scope access control, invite lifecycle, org creation/site-linking/audit trail, personal org provisioned on first passkey login, and org invite email templates. All 13 i18n files updated. `TeamController`, `Team`, `TeamService` and all `TEAM_*` constants removed.
- Added six role-based radius design tokens (`--radius-button/control/panel/dialog/cell/article`) applied across all 66 themes so corner shapes can be tuned per-role independently.
- Normalized dialog system: 32px circular close button, shared `data-dialog-close-on-backdrop` backdrop delegation, keyboard shortcuts modal restructured to 44rem cap with centered close button, close labels standardized to "Close" across all modals.
- Converted calendar entry modal to native `<dialog>` with Escape/backdrop/button delegation; replaced year button list with `<datalist>` year picker; added `ensureDialogAria()` auto-wiring for dialogs missing ARIA attributes.

## v1.036.002 (2026-03-20)

- Release hygiene sweep for the `1.036.x` line: synchronized `VERSION`, README release notes, rolling changelog, and Git tags.
- Ignored generated KnockKnock test output so local verification artifacts no longer dirty the working tree.

## v1.036.001 (2026-03-20)

- Contact workflow refresh with shared templating, safer keyboard handling, clearer localized support copy, and improved cross-theme form contrast.
- Outbound email standardization with repo-level template resolution fixes, professionalized HTML/text templates, and automated render plus live-send sweep coverage.

## v1.035.000 (2026-03-19)

- Redis Tier-0 reliability controls and release groundwork.
- Passwordless email-change flow with recovery-email verification and dual code checks
- Recovery-email endpoint/template completion with 6-character code standardization
- Settings/admin UX refinements (Edit Details modal, passkey panel polish, admin security dashboards)
- No-cache hardening for `/settings` and settings page assets to prevent stale sensitive UI

## v1.034.000 (2026-03-18)

- Release cut and documentation alignment for the 1.034 line.

## v1.033.000 (2026-03-17)

- Account security and UX hardening.

## v1.032.000 (2026-03-09)

Metrics and layout infrastructure

- Admin metrics dashboard and public transparency metrics page
- Session lifecycle metrics and privacy-bound telemetry rollups
- Public/authenticated reusable layout system with strict CSP separation

## v1.030.000 (2026‑03‑09)

Email and verification improvements

- Email system refactored (`EmailTransport`, `EmailGarum`)
- Verification flow improvements
- Redis TTL handling fixes
- Guardian TrustedHTML integration

## v1.029.000 (2026‑03‑07)

Encrypted‑only enforcement

- Removed plaintext fallback paths
- Server‑side envelope validation
- Improved IP normalization for logs and rate limiting

## v1.028.000 (2026‑03‑07)

Security improvements

- Lock boundary caching fixes
- Telemetry endpoint hardening
- Repository mutation gateway

Full changelog:

- docs/CHANGELOG.md
- docs/v1.changelog.md

---

# Documentation

| Document | Description |
|----------|-------------|
| ENCRYPTION_SECURITY_AUDIT.md | Encryption architecture |
| ARCHITECTURE.md | System design |
| INTEGRITY_ARCHITECTURE.md | Data integrity guarantees |
| RATE_LIMITING_ARCHITECTURE_GUIDE.md | Rate limiting design |
| docs/DEVELOPER_PHILOSOPHY.md | Developer-first architecture and implementation philosophy |
| docs/RELEASE_NOTES_1_043_021.md | Public release notes for the profile, billing, and governance discoverability release |
| docs/RELEASE_NOTES_1_043_000.md | Public release notes for security and testing workstreams |
| docs/SECURITY_TRANSPARENCY_REPORT_Q1_2026.md | Public Q1 2026 security and transparency posture report |

Developer references:

- docs/DEVELOPER_PHILOSOPHY.md
- CONTRIBUTING.md
- coding-standards.md
- GIT_VERSIONING_QUICK_REFERENCE.md
- .github/skills/mis/SKILL.md (`/mis` slash command for release hygiene sweeps)

---

# Contributing

Contributions are welcome.

General expectations:

- PHP 8.4 strict typing
- PHPStan Level 9 compliance
- Accessible HTML
- CSP‑compatible styling
- Framework‑free JavaScript
- Redis persistence conventions

Development workflow:

```bash
composer install
composer run test
composer run phpstan
composer run cs-fix
```

---

# License

Copyright (C) 2026 PayCal Technologies Inc. All rights reserved.

See **LICENSE.txt** for full terms.

---

**PayCal™** · PayCal Technologies Inc.

Privacy‑first payroll tracking designed to make pay easier to understand while preserving strong data privacy.

PayCal is a trademark of PayCal Technologies Inc. pending registration (CIPO).
