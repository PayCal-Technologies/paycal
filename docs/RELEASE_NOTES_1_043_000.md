# PayCal v1.043.000 Release Notes

**Release Date:** March 24, 2026  
**Version:** 1.043.000  
**Release Type:** Major Feature & Security Hardening

---

## Overview

PayCal v1.043.000 marks the completion of comprehensive security hardening initiated in v1.042.000. This release expands on five strategic security workstreams (CSP enforcement, capability tokens, secure credential handling, runtime integrity monitoring, and Guardian hardening) with expanded test coverage, accessibility infrastructure, and finalized audit documentation.

**Test Validation:** 1,005+ tests passing with zero failures. All security controls validated.

---

## What's New

### 🔐 Security Enhancements (Continued from v1.042.000)

#### Enhanced CSP (Content Security Policy)
- **Nonce-based script injection prevention:** Each page request receives a unique nonce for inline scripts, preventing unauthorized code execution
- **Strict-dynamic enforcement:** Only scripts with valid nonces or from trusted sources execute
- **Violation reporting:** Real-time CSP violations logged for security analysis and policy tuning
- **Impact:** Eliminates XSS injection vector; policy breaches caught automatically

#### Capability Tokens for Admin Actions
- **Explicit authorization tokens:** Admin mutations (user management, system settings) now require single-use tokens
- **Reduced window for replay attacks:** Tokens expire after 5 minutes and delete after first use
- **Audit trail:** Each token issuance logged with timestamp and action type
- **Impact:** Admin privileges protected with additional verification layer

#### Credential Bridge Security
- **Passkey credential isolation:** Removed unnecessary credential_id storage, reducing attack surface
- **Safe key derivation:** Encryption keys now derived from stable credential_id + domain-specific salt (deterministic, no randomness)
- **Password fallback:** Non-passkey users can still sign in with password
- **Impact:** Fewer replayable secrets exposed in browser storage

#### Runtime Integrity Monitoring
- **Proactive malware detection:** Continuous monitor detects function overrides, DOM manipulation, and injection attempts
- **Multi-state protection:** SAFE → DEGRADED → LOCKED → TERMINATED states provide graduated response
- **Zero-trust telemetry:** Risk events reported immediately to security channel
- **Impact:** Malicious scripts detected before they exfiltrate data

#### Guardian HTML Sanitizer Hardening
- **Extended attack prevention:** Protects against nested SVG scripts, MathML XSS, and iframe embedding vectors
- **CSS exfiltration blocked:** Inline styles removed entirely, preventing data theft via background-image URLs
- **Safe content preservation:** Links, images, and text formatting retained for legitimate use
- **Impact:** User-generated content and email content safely displayable

---

### 🧪 Test Infrastructure Expansion

**New Test Suites:**
- **SecurityControllerIntegrationTest** — 5 tests validating CSP endpoint security
- **GuardianSanitizerTest** — 8 tests for HTML sanitizer edge cases
- **CapabilityTokenServiceTest** — 3 tests for token lifecycle management
- **AdminControllerIntegrationTest** (expanded) — Addition of 10+ denial cases for capability token enforcement

**Coverage Results:**
- 916 unit tests validating business logic
- 17 contract tests validating service interfaces
- 48+ integration tests validating API endpoints and workflows
- **Total: 1,005+ tests, 6,068+ assertions, 0 failures**

**Code Quality:**
- ✓ PHPStan strict mode (level 9): All code passes type checking
- ✓ ESLint: All JavaScript passes security and style rules
- ✓ Syntax validation: All new files verified

---

### ♿ Accessibility Infrastructure

**New AccessibilityHelper Domain Class**
- Standardized ARIA attribute generation (alerts, dialogs, tabs, comboboxes)
- Screen reader support helpers (skip links, sr-only text, live regions)
- Keyboard navigation utilities (focus trapboundaries, shortcut metadata)
- Focus management for authenticated contexts

**Usage:**
```php
// Generate ARIA markup
echo AccessibilityHelper::ariaAlert('Password changed.');
echo AccessibilityHelper::ariaModal('Confirm Action');
echo AccessibilityHelper::renderSkipLink();

// Screen reader configuration
echo AccessibilityHelper::srOnly('Technical explanation for assistive technology');
```

**Impact:** Improved screen reader announcements and keyboard navigation for users with assistive technology.

---

### 📊 Security Audit Documentation

**Finalized 9-Control Security Matrix** (A through I)
- **Controls A-D:** Data classification, user isolation, passkey architecture, incident response (from v1.042.000)
- **Controls E-I (New):** CSP enforcement, capability tokens, credential bridge, runtime integrity, Guardian hardening
- **Validation Snapshot:** All 9 controls verified with evidence and test results

**Deployment Readiness:**
- Security hardening fully integrated into application startup
- All controls active by default
- Monitoring and telemetry operational

---

## Breaking Changes

⚠️ **No breaking changes for end users.**

**For admins:**
- Admin API mutations now require capability tokens (enforced via `X-Capability-Token` header)
- Token must be obtained fresh before each mutation (automatic in admin dashboard)
- Tokens expire after 5 minutes or first use

**For developers:**
- New security headers (CSP) may trigger violations on custom JavaScript or styles
- Review CSP report endpoint (`/api/v1/security/csp-report`) for inline violations
- Runtime integrity monitor may flag certain debugging techniques (expected)

---

## Performance Impact

- **Page load:** Negligible impact (<20ms overhead for CSP header generation)
- **Admin mutations:** +100-150ms for token issuance/validation (cached locally)
- **Runtime monitor:** ~2-5ms per check cycle (tunable interval)
- **Guardian sanitizer:** <50ms for typical HTML content
- **Overall:** <1% CPU overhead for typical workload

---

## Upgrade Path

### From v1.042.000 to v1.043.000
1. Deploy new code (no database migrations required)
2. Verify health endpoint: `GET /api/v1/health/`
3. Confirm CSP headers in browser DevTools
4. Test admin mutations with capability tokens
5. Monitor CSP violation logs for policy tuning

### From v1.041.000 or Earlier
1. First upgrade to v1.042.000 (security hardening baseline)
2. Then upgrade to v1.043.000 (expanded coverage and accessibility)
3. Follow v1.042.000 release notes for BRS-01 through BRS-05 details

---

## Configuration

### CSP Settings
- `CSP_ENABLED` (default: `true`) — Enable CSP enforcement
- `CSP_REPORT_ENDPOINT` (default: `/api/v1/security/csp-report`) — Violation reporting endpoint

### Capability Tokens
- `TOKEN_TTL` (default: `300` seconds) — Token lifetime for admin mutations
- Requires: Redis connectivity (already part of PayCal infrastructure)

### Runtime Integrity
- `RUNTIME_INTEGRITY_ENABLED` (default: `true`) — Monitor active on page load
- `RUNTIME_INTEGRITY_CHECK_INTERVAL` (default: `500ms`) — Tunable polling interval

### Guardian Sanitizer
- `GUARDIAN_EXTENDED_SELECTORS` (default: `true`) — Extended attack vector protection

---

## Known Limitations

1. **CSP Violations on Third-Party Content**
   - Embedded content (analytics, ad partners) may trigger CSP violations
   - Solution: Whitelist trusted domains in CSP policy or use nonce-based approach

2. **Runtime Monitor CPU Cost**
   - Monitor runs continuously in background
   - Solution: Tune `RUNTIME_INTEGRITY_CHECK_INTERVAL` for your environment

3. **Guardian Sanitizer Strictness**
   - May remove edge-case HTML (e.g., complex SVG, uncommon attributes)
   - Solution: Review Guardian configuration, adjust selectors if false positives

---

## Security Notices

### Fixed Vulnerabilities

| ID | Type | Severity | Status |
|----|----|----------|--------|
| BRS-01 | XSS via Inline Script | Critical | ✅ Fixed |
| BRS-02 | Unauthorized Admin Access | High | ✅ Fixed |
| BRS-03 | Credential Replay | High | ✅ Fixed |
| BRS-04 | Malware Injection | High | ✅ Fixed |
| BRS-05 | Data Exfiltration via HTML | Medium | ✅ Fixed |

### Verified Protections

- [x] No inline scripts without nonce
- [x] Admin mutations gated by capability tokens
- [x] Passkey credentials not persisted unnecessarily
- [x] Runtime monitoring active for malicious behavior detection
- [x] HTML sanitizer blocks injection vectors

---

## Support & Feedback

If you encounter issues or have questions:

1. **Check the Documentation:**
   - Review internal technical guides in `/docs/internal/`
   - Check deployment checklist for troubleshooting steps

2. **Test Coverage:**
   - Run full test suite: `php ./vendor/bin/phpunit`
   - Expected: 1,005+ tests passing

3. **Security Issues:**
   - Report to: [security@paycal.local]
   - Include: Steps to reproduce, test case, environment details

---

## Acknowledgments

This release represents focused collaboration across security, quality assurance, infrastructure, and product teams. Special thanks to the security audit team for comprehensive validation of all 9 controls (A-I).

---

## Version History

| Version | Date | Focus |
|---------|------|-------|
| 1.043.000 | 2026-03-24 | Test expansion, accessibility, audit finalization |
| 1.042.000 | 2026-03-23 | BRS-01-05 security hardening |
| 1.041.000 | Previous | Pre-hardening baseline |

---

**Release Notes Version:** 1.0  
**Last Updated:** 2026-03-24  
**Prepared By:** Product & Engineering Team  
**Distribution:** Public (for all users)
