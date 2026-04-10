# Application Security Sweep: What We Found and How We Fixed It

Date: 2026-03-31

This post summarizes a focused application security sweep across request handling, redirects, API mutation protections, and host/header trust boundaries. The goal was practical hardening: remove realistic exploit paths without disrupting normal product behavior.

## Executive Summary

We confirmed and remediated three concrete web-app attack surfaces:

1. Open redirect risk in language switching flow.
2. Host-header/forwarded-header influence on billing callback origin logic.
3. Missing CSRF token enforcement on billing mutation endpoints.

We also reviewed lower-confidence command-execution surfaces and documented their current exposure state.

## Scope and Method

The review focused on:

- Redirect sinks and URL construction behavior.
- API mutation routes and anti-forgery checks.
- Header trust model in origin/callback logic.
- Dangerous execution primitives and exposure paths.

Primary reference points reviewed:

- html/lang/index.php
- html/src/Controllers/BillingController.php
- html/js/core/billing.js
- html/src/Domain/RequestGuard.php
- html/src/Domain/Authentication.php
- html/src/Domain/Security.php
- html/src/Domain/RedisSyncService.php

## Findings and Fixes

### 1) Open Redirect in Language Endpoint (Fixed)

#### Risk

The language endpoint redirected to HTTP_REFERER directly, which can be attacker-controlled or absent. That creates a phishing/chaining vector where a trusted domain bounces users to an attacker domain.

#### Vulnerable Logic (Before)

- html/lang/index.php (direct redirect from HTTP_REFERER)

#### Hardening Implemented

- Added referrer parsing and host allowlist checks.
- Allowed only same-host/app-host referrers, otherwise fallback to /.
- Preserved internal path/query only.

#### Code References

- Updated import for app origin host parsing: html/lang/index.php:6
- Redirect target normalization/allowlist logic: html/lang/index.php:19
- Safe redirect sink now uses validated target: html/lang/index.php:50

### 2) Host Header Poisoning Path in Billing Origin Normalization (Fixed)

#### Risk

Billing callback normalization accepted host-derived origin from forwarding headers. If forwarding/header trust is misconfigured, an attacker can influence redirect host handling.

#### Vulnerable Logic (Before)

- requestOrigin accepted forwarded host/proto without explicit trusted-proxy gate.

#### Hardening Implemented

- requestOrigin now requires REMOTE_ADDR to be in TRUSTED_PROXIES before using forwarded host/proto.
- If remote peer is not trusted, falls back to configured app origin path logic.
- Preserved host syntax checks and scheme handling.

#### Code References

- Trusted-proxy gate in requestOrigin: html/src/Controllers/BillingController.php:281
- Forwarded-host parsing now occurs only after trust check: html/src/Controllers/BillingController.php:291
- Trusted proxy list helper: html/src/Controllers/BillingController.php:328

### 3) Billing POST Endpoints Missing CSRF Enforcement (Fixed)

#### Risk

Billing mutation endpoints were authenticated but lacked anti-forgery token validation. With same-origin credentials and a valid user session, this increased CSRF exposure for checkout/portal/cancel/confirm flows.

#### Hardening Implemented

- Added centralized billing CSRF validation in controller.
- Enforced token presence and validation for settings or organizations nonce contexts.
- Added CSRF token propagation in frontend billing requests.

#### Backend Code References

- CSRF enforced in checkout-session: html/src/Controllers/BillingController.php:27
- CSRF enforced in portal-session: html/src/Controllers/BillingController.php:94
- CSRF enforced in confirm-checkout: html/src/Controllers/BillingController.php:123
- CSRF enforced in cancel-subscription: html/src/Controllers/BillingController.php:155
- Shared CSRF validator: html/src/Controllers/BillingController.php:340

#### Frontend Code References

- Token resolver (settings + organizations + fallback hidden fields): html/js/core/billing.js:176
- CSRF sent in confirm-checkout: html/js/core/billing.js:593
- CSRF sent in checkout-session: html/js/core/billing.js:722
- CSRF sent in portal-session: html/js/core/billing.js:760
- CSRF sent in cancel-subscription: html/js/core/billing.js:857

## Additional Observations

### Command Execution Primitives (Reviewed)

The class below contains shell/exec paths:

- html/src/Domain/RedisSyncService.php

Current repo sweep did not show active runtime references from app controller paths in html/src (no direct class usage found during route/controller reference search). This lowers immediate internet-exposure risk, but the code still warrants strict containment and future deprecation if reintroduced.

Suggested follow-up:

- Keep this functionality non-routable/non-public.
- If needed operationally, move to private CLI-only tooling boundary with explicit operator auth and environment guards.

## Verification Performed

- PHP syntax checks:
  - php -l html/lang/index.php
  - php -l html/src/Controllers/BillingController.php
- Editor diagnostics check:
  - html/js/core/billing.js
  - html/lang/index.php
  - html/src/Controllers/BillingController.php

No syntax or editor errors were reported for changed files.

## Why These Fixes Matter

These fixes close common and high-value web exploitation chains:

- Open redirect abuse in trusted-domain phishing flows.
- Host-header based callback manipulation in payment-related routes.
- CSRF pressure on billing mutations that can change account state.

Each fix favors fail-safe defaults and explicit trust boundaries.

## Next Hardening Steps

1. Add integration tests for:
   - language redirect allowlist behavior,
   - billing mutation rejection when csrf_token is absent/invalid,
   - billing origin normalization under untrusted proxy conditions.
2. Add route-level threat labels for billing/security-sensitive endpoints.
3. Continue periodic scans for redirect sinks and header trust regressions.

## Appendix: Key Files Touched in This Hardening Pass

- html/lang/index.php
- html/src/Controllers/BillingController.php
- html/js/core/billing.js
