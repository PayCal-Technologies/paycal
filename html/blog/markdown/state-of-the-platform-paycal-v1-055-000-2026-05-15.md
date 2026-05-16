---
title: State of the Platform: PayCal Version 1.055.000
date: 2026-05-15
author: PayCal Team
tags: release, security, redis, soc2, ci, css, i18n
---

## Overview

PayCal Version 1.055.000 closes a concentrated engineering cycle covering six version increments since 1.049.000. The work spans a deep security sweep across the full stack, Redis infrastructure hardened to serializable-safe atomicity, a complete CI/CD overhaul, a SOC2 audit trail capable of producing verifiable evidence bundles, a full migration from hardcoded pixel sizes to proportional rem tokens, and expanded internationalization coverage.

This is not a feature-expansion release. It is a platform-integrity release — the kind of cycle that makes everything that follows faster, safer, and more auditable.

## Security Hardening

The largest block of work in this cycle is systematic security hardening. Over two formal sweep rounds, the following classes of vulnerability were identified and closed:

### Centralised Security Headers

All core HTTP security headers are now emitted through a single authoritative call: `Security::sendCoreSecurityHeaders()`. Prior to this change, headers were applied inconsistently across controllers. Centralising the call eliminates the risk of any future controller omitting a header by omission.

### Information Disclosure

◆ Exception detail stripped from all API responses — stack traces and internal error messages now log server-side only; callers receive opaque error codes
◆ API route map no longer exposed in public responses
◆ Test output leak closed — test runner results cannot surface through production endpoints

### Request Forgery and Spoofing

◆ Open redirect in `BillingController` checkout-return — fixed with strict same-origin validation
◆ Stripe webhook endpoint hardened against queue poisoning
◆ IP spoofing via `X-Forwarded-For` — trusted-proxy gating applied
◆ CORS `OPTIONS` fallthrough — explicit handling added; no implicit pass-through

### Rate Limiting and Key Hygiene

◆ Rate-limit keys migrated from MD5 to SHA-256
◆ `exec()` replaced with `proc_open` argument arrays wherever shell surfaces existed (SOC2 admin script runner)
◆ `escapeshellarg` applied to remaining path constructs

### Session and Memory Safety

◆ Calendar work clipboard moved out of DOM-accessible storage into a sessionStorage scope, then further hardened to an in-memory IIFE variable
◆ HKDF fallback visibility audited; non-enumerable `window` globals enforced for key material
◆ SettingsController auth guard added — unauthenticated access to settings API endpoints is now explicitly rejected

### Auth and Passkey

◆ Passkey autofill gated to active sign-in tab only — prevents autofill from triggering in background contexts
◆ Auth gate hardening across recovery and passkey registration flows
◆ CSRF-safe random entropy (CSRNG) applied wherever token generation previously used weaker sources

## Redis Atomicity

Redis operations across the application have been systematically upgraded from non-atomic patterns to safe, race-free alternatives:

◆ All `hset + expire` call pairs replaced with atomic `hsetex`
◆ Token consumption migrated to `GETDEL` — single-operation read-and-delete eliminates TOCTOU windows
◆ Counter increment races and webhook deduplication races fixed with Lua-script atomics
◆ `touchLastSignin` migrated to batched `hset` — single round-trip instead of per-field calls
◆ Persistent connections (`pconnect`) introduced for hot paths
◆ `WAIT` replica confirmation added for critical write paths

The Redis ACL docblock is updated to reflect the current command surface. Cleanup and migration scripts are included for any deployments carrying stale key formats.

## SOC2 Audit Trail

The SOC2 evidence pipeline graduated from a manual-assembly workflow to a fully automated, production-scheduled system:

◆ Daily systemd timer on production generates the monthly evidence bundle automatically
◆ TheLedger now mirrors 13 org governance events — every material organisation action creates an immutable audit record
◆ Admin audit trail and org audit read endpoints allow auditors to query evidence directly through the admin surface
◆ SOC2 dashboard tables migrated to the shared datagrid component — consistent presentation across audit views
◆ CC1 through CC9 control coverage gaps closed with test suites, trace evidence, and cross-artifact integrity checks
◆ ContentView system added — transparency documents can be served as both HTML and PDF from a single source

A transparency article covering auth, passkey, and Redis hardening was published to `/transparency/` alongside a PHP package dependency article.

## CI/CD Overhaul

The GitHub Actions pipeline was substantially hardened:

◆ SHA pinning on all third-party action references — supply-chain safety
◆ Least-privilege permissions on all workflow jobs
◆ Per-job timeouts to prevent runaway billing
◆ Dependabot expanded to cover Composer and npm, running daily
◆ Daily dependency security audit script — CVE scanning, version drift detection
◆ PHPStan job added to CI matrix as a first-class gate
◆ Gitleaks migrated to CLI invocation — no license required
◆ CodeQL removed — PHP was unsupported; PHPStan is the static analysis gate

PHP platform upgraded:

◆ PHPUnit 12 → 13
◆ Platform target: PHP 8.4 (production), 8.5 (dev preview); PHP 8.2 matrix dropped

## Design Token System: px → rem

All hardcoded pixel font-size values across the codebase have been replaced with proportional `rem` tokens:

◆ Calendar — `14818956`
◆ Datagrid — `a57dd349`
◆ Common CSS — `dc137193`
◆ Help, Organisations, Settings — `ebbb59e0`

Font sizes now scale correctly with the system base font size, fixing the longstanding issue where accessibility-triggered font-size increases had no effect on several UI surfaces.

The density preference was renamed to **Spacing** throughout — DB field, PHP, JavaScript, CSS, i18n strings, and tests all updated consistently.

## Dependency Modernisation

◆ `vlucas/phpdotenv` replaced with a first-party `Infrastructure\Env\Dotenv` implementation — removes a third-party dependency from the environment-loading critical path
◆ Removed unused packages: `erusev/parsedown`, `yupmin/magoo` (PHP); `pdf-lib` (npm)
◆ Removed orphaned `vendor/pdepend` artefact

## Internationalisation

◆ Six additional pages localised: auth recovery, help/tax-brackets, organisations, profile, security, sites
◆ Admin language editor added (`/admin/language-editor/`) — full-width panel with locale tab strip and monospace textarea for in-browser string editing
◆ Admin language audit dashboard (`/admin/language-dashboard/`) with `LanguageAuditService` — shows missing key coverage per locale
◆ `declare(strict_types=1)` enforced in all locale files via pre-commit hook

## Premium and UX

◆ `/premium` upgrade landing page launched — outcome-focused copy, benefit pillars, pricing clarity
◆ Premium page copy rewritten per UX brief — features described in user-outcome terms, not implementation terms
◆ Organisation page reframed as a discovery and membership hub — free users see the value proposition rather than a locked gate
◆ Profile billing section given outcome-focused messaging and UX improvements
◆ XLSX export added to earnings — joins PDF, CSV, and text in the one-click export set
◆ Calendar earnings hover tooltip — shows earnings summary for a day without leaving the calendar view
◆ Breadcrumb ticket-stub styling — visual refinement applied across document navigation surfaces

## Closing

PayCal 1.055.000 is the platform proving it can hold a consistent security posture across a sustained multi-week engineering cycle. Every change in this release — whether a Redis atomic upgrade, a CI SHA pin, or a rem token migration — is traceable, tested, and auditable.

The SOC2 pipeline now runs automatically. The CI pipeline now audits dependencies daily. The security headers now emit from a single source of truth.

The work of this cycle is infrastructure that the next cycle gets to stand on.

**Secure. Auditable. Maintained. This is PayCal.**
