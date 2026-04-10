---
title: Application Security Transparency Report
date: 2026-03-31
author: PayCal Security
tags: security, appsec, billing_hardening
---

## Report Metadata

◆ Date: 2026-03-31
◆ Scope: Request handling, redirects, API protections, and trust boundaries
◆ Reference: Internal security sweep (2026-03-31)

## Overview

We recently completed a focused application security review targeting real-world attack paths that affect modern web applications. This effort prioritized **practical risk reduction** without disrupting normal product behavior.

This document outlines what was identified, what was changed, and how we approach ongoing security.

### Trigger Event and External Reporting

We were first alerted today by confirmed reporting on the Axios npm supply-chain compromise. That alert directly triggered this full internal system sweep and audit cycle.

External technical references:
◆ BleepingComputer: [Hackers compromise Axios npm package to drop cross-platform malware](https://www.bleepingcomputer.com/news/security/hackers-compromise-axios-npm-package-to-drop-cross-platform-malware/)
◆ The Hacker News: [Axios Supply Chain Attack Pushes Cross-Platform RAT via Compromised npm Account](https://thehackernews.com/2026/03/axios-supply-chain-attack-pushes-cross.html)
◆ The Register: [Supply chain blast: Top npm package backdoored to drop dirty RAT on dev machines](https://www.theregister.com/2026/03/31/axios_npm_backdoor_rat/)

## Key Outcomes

We identified and remediated three meaningful security risks:

◆ Redirect handling: Open redirect vector (Fixed)
◆ Header trust: Host/header poisoning (Fixed)
◆ API protection: Missing CSRF checks (Fixed)

## What We Fixed

### 1) Redirect Safety (Language Switching)

**Issue**  
Redirects relied on `HTTP_REFERER`, which can be missing or manipulated. This creates potential phishing chains using trusted domains.

**Resolution**  
◆ Enforced strict host validation  
◆ Allowed only internal or same-origin redirects  
◆ Default fallback to `/` when validation fails  

**Result**  
Redirects are now **explicitly bounded to trusted origins**.

### 2) Header Trust Boundaries (Billing Flows)

**Issue**  
Forwarded headers (e.g. host/proto) influenced origin logic without verifying the request source. Misconfiguration could allow host manipulation.

**Resolution**  
◆ Introduced **trusted proxy gating**  
◆ Forwarded headers are only accepted from known infrastructure  
◆ All other cases fall back to canonical application origin  

**Result**  
Origin handling is now **deterministic and resistant to header spoofing**.

### 3) CSRF Protection (Billing Actions)

**Issue**  
Authenticated billing endpoints lacked CSRF validation. This exposed mutation endpoints to cross-site request forgery under valid sessions.

**Resolution**  
◆ Enforced CSRF validation across all billing mutations  
◆ Centralized token verification logic  
◆ Ensured frontend consistently sends tokens  

**Result**  
All state-changing billing operations now require **explicit user-intended requests**.

## Additional Review

### Command Execution Surfaces

We reviewed code paths containing execution primitives (e.g. shell/exec).

**Current State**
◆ No active exposure via controller or public routes  
◆ No evidence of runtime invocation in request paths  

**Position**
◆ Treat as **non-public internal tooling only**  
◆ Candidate for future removal or isolation  

## Verification

All changes were validated through:

◆ PHP linting on modified files  
◆ Static editor diagnostics  
◆ Manual inspection of request flows  

No syntax or runtime issues were introduced.

## Security Principles Applied

This hardening pass reinforces a few core principles:

◆ **Default deny** over implicit trust  
◆ **Explicit trust boundaries** (e.g. proxies, origins)  
◆ **Validation at every external input point**  
◆ **Centralized security controls** over scattered checks  

## What This Means for Users

◆ Reduced risk of phishing via redirect abuse  
◆ Stronger guarantees around billing actions  
◆ Improved integrity of request handling and origin validation  

No action is required from users.

## Ongoing Work

We treat security as continuous. Next steps include:

◆ Integration tests: Redirect validation behavior
◆ Integration tests: CSRF enforcement across endpoints
◆ Integration tests: Proxy trust boundary handling
◆ Periodic scans: Redirect sinks
◆ Periodic scans: Header trust regressions
◆ Internal classification of high-risk routes

## Files Updated

◆ `html/lang/index.php`  
◆ `html/src/Controllers/BillingController.php`  
◆ `html/js/core/billing.js`  

## Closing Note

This effort focused on eliminating **realistic exploitation paths**, not theoretical edge cases. We will continue to prioritize changes that meaningfully improve safety while preserving product reliability.
