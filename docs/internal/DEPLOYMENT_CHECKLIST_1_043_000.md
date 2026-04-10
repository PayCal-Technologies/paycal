# PayCal 1.043.000: Deployment Checklist

**Release Date:** 2026-03-24  
**Version:** 1.043.000  
**Environment Target:** Production (after staging validation)

---

## Pre-Deployment Validation

### Code Quality Gates

- [ ] **PHP Type Safety Check**
  ```bash
  cd html && composer run phpstan:strict
  # Expected: [OK] No errors found
  ```
  - Result: ✓ VERIFIED

- [ ] **JavaScript Linting & Security**
  ```bash
  npm run test:js
  # Expected: All rules pass, no security sinks detected
  ```
  - Result: ✓ VERIFIED

- [ ] **Test Suite Execution**
  ```bash
  cd html && php ./vendor/bin/phpunit --no-progress
  # Expected: 1,005+ tests passing, 0 failures
  ```
  - Result: ✓ VERIFIED

### File Integrity

- [ ] **Syntax Validation** (all new files)
  ```bash
  php -l src/Domain/Services/CapabilityTokenService.php
  php -l src/Domain/AccessibilityHelper.php
  php -l tests/Unit/CapabilityTokenServiceTest.php
  php -l tests/Unit/GuardianSanitizerTest.php
  php -l tests/Integration/SecurityControllerIntegrationTest.php
  # Expected: No syntax errors
  ```
  - Result: ✓ VERIFIED

- [ ] **Git Status Clean**
  ```bash
  git status --short
  # Expected: All changes staged/committed, no unstaged modifications
  ```
  - Result: ✓ VERIFIED

---

## Security Control Deployment

### Control E: CSP Enforcement

**Deployment Tasks:**

- [ ] **Verify CSP Headers in Production**
  - File: `html/header.php`
  - Check: `Content-Security-Policy` header present in responses
  - Command: `curl -skI https://paycal.local/ | grep -i content-security-policy`
  - Expected: Header includes `nonce-`, `strict-dynamic`, report-uri

- [ ] **Verify CSP Report Endpoint**
  - Endpoint: `POST /api/v1/security/csp-report`
  - Check: Endpoint accessible, returns 200 OK
  - Command: 
    ```bash
    curl -X POST https://paycal.local/api/v1/security/csp-report \
      -H "Content-Type: application/csp-report" \
      -d '{"csp-report":{"violated-directive":"script-src"}}'
    ```
  - Expected: `{"status":"success"}`

- [ ] **Monitor CSP Violations**
  - Enable: CSP violation logging in SecurityLog
  - Verify: Real violations recorded after policy deployment
  - Timeline: First 24 hours of deployment (audit for false positives)

**Rollback Plan:**
- If CSP violations spike unexpectedly, revert to `Content-Security-Policy-Report-Only` header
- Investigate violations before re-enabling enforcement
- Command: `git revert <commit_hash>`

---

### Control F: Capability Tokens

**Deployment Tasks:**

- [ ] **Verify Redis Connection**
  - Check: Redis is accessible and running
  - Command: `redis-cli ping`
  - Expected: `PONG`

- [ ] **Test Token Issuance**
  - Endpoint: `GET /api/v1/admin/capability-token`
  - Check: Token returns as 32-char hex
  - Command:
    ```bash
    curl -X GET https://paycal.local/api/v1/admin/capability-token \
      -H "Authorization: Bearer $ADMIN_TOKEN"
    ```
  - Expected: `{"token":"[32-char-hex]","expires_at":"[timestamp]"}`

- [ ] **Test Token Consumption**
  - Action: Attempt admin mutation with valid token
  - Check: Mutation succeeds (token consumed)
  - Verify: Second attempt with same token fails (one-shot enforcement)

- [ ] **Test Denial Without Token**
  - Action: Attempt admin mutation without token
  - Check: Returns 401 Unauthorized
  - Verify: User is admin (not just unauthenticated)

- [ ] **Configure Token TTL**
  - Setting: Capability token lifetime (default: 5 minutes)
  - Verify: `CapabilityTokenService::TOKEN_TTL` in code is correct
  - Consider: Adjust if admin workflows need longer window

**Deployment Config:**
```php
// html/src/Domain/Services/CapabilityTokenService.php
private const TOKEN_TTL = 300; // 5 minutes (in seconds)
private const TOKEN_LENGTH = 32; // 32-char hex = 128 bits
```

**Rollback Plan:**
- If capability token service fails: 
  - Revert `AdminController` to pre-gating logic (temporary)
  - Keep token service code but disable `requireCapability()` checks
  - Investigate Redis connection or token generation issue

---

### Control G: Credential Bridge Removal

**Deployment Tasks:**

- [ ] **Verify Passkey Sign-In Flow**
  - Test: Sign in with passkey
  - Check: No `credential_id` in browser sessionStorage
  - Command: Browser DevTools → Application → Session Storage
  - Verify: KEK derivation still works (key unwrapping succeeds)

- [ ] **Verify Password Fallback**
  - Test: Sign in with password (passkey fallback)
  - Check: DEK unwrapping via password works
  - Expected: User session established, authenticated

- [ ] **Audit Logs**
  - Verify: No error logs about missing credential_id
  - Check: `logs/app.log` for warnings during sign-in

**Rollback Plan:**
- If sign-in fails: Revert `js/signin/index.php` and `js/calendar/calendar.js`
- Re-add sessionStorage credential_id temporarily
- Redeploy password-only flow as interim

---

### Control H: Runtime Integrity Monitor

**Deployment Tasks:**

- [ ] **Bootstrap Verification**
  - File: `js/core/index.php`
  - Check: Includes `<!-- Guardian Runtime Integrity Monitor -->` with `<script>` tag
  - Verify: `js/runtime-integrity.js` is loaded

- [ ] **Console Check**
  - Open: Browser DevTools console
  - Verify: No errors from `runtime-integrity.js` initialization
  - Expected: Monitor initializes in SAFE state

- [ ] **PhantomWing Integration**
  - Verify: Telemetry channel ready for risk-state events
  - Check: `PhantomWing` object is accessible in JS context

- [ ] **Performance Baseline**
  - Measure: Runtime integrity overhead per check cycle
  - Target: <2ms per monitoring iteration
  - Tool: Browser DevTools Performance profiler

**Stability Window:**
- Monitor for 24-48 hours post-deployment
- Collect metrics: false-positive rate, CPU impact
- Alert if CPU spikes or too many DEGRADED transitions

**Rollback Plan:**
- If monitor causes performance issues:
  - Set `RUNTIME_INTEGRITY_ENABLED=false` env var
  - Revert `js/runtime-integrity.js` and bootstrap
  - Investigate root cause (likely: too-frequent checks)

---

### Control I: Guardian Hardening

**Deployment Tasks:**

- [ ] **Guardian Sanitizer Verification**
  - Test: Pass HTML with inline styles and script tags through Guardian
  - Check: 
    - Inline styles removed (no `style="..."` attributes)
    - Script tags removed (all variants)
    - SVG/MathML scripts removed
    - Safe content preserved (links, images, formatting intact)

- [ ] **Extended Selector Validation**
  - Verify: `Guardian/RuntimeIntegrity.php` is loaded
  - Check: Extended selectors in config (SVG, MathML, foreignObject)
  - Command:
    ```bash
    grep -E "svg|mathml|foreignObject" html/src/Domain/Guardian/RuntimeIntegrity.php
    ```
  - Expected: All selectors present

**Rollback Plan:**
- If Guardian breaks legitimate content:
  - Revert sanitizer config
  - Audit false positives
  - Adjust selectors

---

## Infrastructure & Dependencies

### Database Migrations

- [ ] **No Schema Changes Required**
  - Note: All security hardening is app-level (no DB schema updates)
  - Verify: Application boots without migration prompts

### Environment Variables

- [ ] **CSP Configuration**
  - `CSP_REPORT_ENDPOINT` → `/api/v1/security/csp-report` (default)
  - `CSP_ENABLED` → `true` (default)
  - Check: `Layout.php` uses these values correctly

- [ ] **Capability Token Configuration**
  - `REDIS_HOST` → verify connectivity
  - `REDIS_PORT` → verify port accessibility
  - `TOKEN_TTL` → 300 seconds (default, can be overridden)

- [ ] **Runtime Integrity Configuration**
  - `RUNTIME_INTEGRITY_ENABLED` → `true` (default)
  - `RUNTIME_INTEGRITY_CHECK_INTERVAL` → configurable (e.g., 500ms)

- [ ] **Guardian Configuration**
  - `GUARDIAN_EXTENDED_SELECTORS` → `true` (default)
  - Verify: New extended selectors are in use

### Network & Connectivity

- [ ] **Redis Connectivity**
  - Test: From app server, connect to Redis
  - Command: `redis-cli -h $REDIS_HOST -p $REDIS_PORT ping`
  - Expected: `PONG`

- [ ] **CSP Report Ingestion**
  - Verify: SecurityController can log reports
  - Check: Database/logging backend is accessible

- [ ] **Telemetry Pipeline**
  - Verify: PhantomWing channel is operational
  - Check: Risk-state reports can be transmitted

---

## Staging Validation (Before Production)

### Smoke Tests

- [ ] **Health Check Endpoint**
  ```bash
  curl https://staging.paycal.local/api/v1/health/
  # Expected: {"status":"ok","environment":"staging",...}
  ```

- [ ] **User Sign-In** (passkey + password)
  - Test account: `testuser@example.com`
  - Verify: Both flows work post-deployment

- [ ] **Admin Dashboard Access**
  - Test: Admin capability token flow
  - Verify: Admin mutations work with valid token

- [ ] **CSP Header Presence**
  ```bash
  curl -skI https://staging.paycal.local/ | grep -i security
  # Expected: Content-Security-Policy headers present
  ```

- [ ] **No Console Errors**
  - Open: Browser DevTools console
  - Verify: No errors related to new security features

### Load Testing

- [ ] **Baseline Traffic**
  - Run: 1000 concurrent requests to health endpoint
  - Monitor: Response time, error rate
  - Target: <200ms p99 latency

- [ ] **Admin Token Issuance Under Load**
  - Run: 100 concurrent admin token requests
  - Monitor: Token generation latency, Redis connections
  - Target: <100ms p99 latency

---

## Production Deployment

### Rollout Strategy

- [ ] **Blue-Green Deployment** (if supported)
  - Deploy v1.043.000 to green environment
  - Run full smoke tests
  - Switch traffic from blue to green

- [ ] **Canary Rollout** (alternative)
  - Deploy v1.043.000 to 10% of servers first
  - Monitor for 2+ hours
  - Gradually increase to 100%

- [ ] **Rollback Plan Ready**
  - Previous version: v1.042.000 (tagged in git)
  - Rollback command: `git revert <1.043.000_commit>`
  - Verify: Database state compatible (no migrations)

### Monitoring & Alerting

- [ ] **Error Rate Monitoring**
  - Track: Errors per minute in SecurityController
  - Alert: If error rate > 5x baseline
  - Action: Investigate, possibly rollback

- [ ] **CSP Violation Volume**
  - Track: CSP violations ingested per hour
  - Alert: If volume anomalously high (indicates false positives)
  - Action: Audit violations, adjust CSP policy if needed

- [ ] **Capability Token Service**
  - Track: Token issuance/consumption rate
  - Track: Redis connection failures
  - Alert: If Redis disconnects or token generation fails

- [ ] **Runtime Integrity Monitor**
  - Track: State transitions (SAFE → DEGRADED → LOCKED)
  - Track: CPU usage of monitor
  - Alert: If too many LOCKED transitions (possible attack pattern)

- [ ] **Guardian Sanitizer Performance**
  - Track: Sanitization latency per request
  - Alert: If sanitization takes >50ms (performance regression)

### Communication

- [ ] **Status Update to Stakeholders**
  - Notify: Security team, DevOps, Product
  - Message: Deployment in progress, expected completion time
  - Include: Rollback plan and escalation contacts

- [ ] **Incident Response Readiness**
  - Verify: On-call team has runbook for v1.043.000
  - Ensure: Rollback procedures are documented and tested
  - Check: Escalation contacts are current

---

## Post-Deployment Validation (24-48 hours)

### Health Check

- [ ] **Application Functionality**
  - [ ] User registration works
  - [ ] Sign-in (passkey) works
  - [ ] Sign-in (password) works
  - [ ] Admin dashboard accessible
  - [ ] Admin mutations (with token) work
  - [ ] Earnings reports generate correctly
  - [ ] Settings page responsive

- [ ] **Security Controls Active**
  - [ ] CSP headers present in all responses
  - [ ] CSP violations logged correctly
  - [ ] Capability tokens required for admin mutations
  - [ ] Runtime integrity monitor runs without errors
  - [ ] Guardian sanitizer active (test with malicious content)

- [ ] **Performance Metrics**
  - [ ] Page load time: <2s (p95)
  - [ ] API response time: <500ms (p95)
  - [ ] Runtime integrity overhead: <5% CPU
  - [ ] No memory leaks in JavaScript (monitor heap)

### Log Analysis

- [ ] **No Unexpected Errors**
  ```bash
  tail -n 1000 logs/app.log | grep -i error | head -n 20
  # Expected: No errors related to new features
  ```

- [ ] **CSP Violations Reasonable**
  ```bash
  grep "CSP violation" logs/app.log | wc -l
  # Expected: <100 violations in first 24h (tune policy if higher)
  ```

- [ ] **Capability Token Activity Normal**
  ```bash
  grep "capability.*token" logs/app.log | wc -l
  # Expected: Matches admin activity volume
  ```

### User Feedback

- [ ] **No Escalations**
  - Monitor: Support tickets related to authentication, admin access
  - Follow-up: Any issues reported

- [ ] **Performance Feedback**
  - Ask: Have users reported slower performance?
  - Action: Investigate if multiple reports

---

## Rollback Procedures

### Immediate Rollback (if critical issue)

```bash
# 1. Identify current commit
git log --oneline -n 1
# Expected: commit hash for 1.043.000

# 2. Revert to 1.042.000
git revert --no-edit <1.043.000_commit_hash>

# 3. Deploy reverted version
# (follow your normal deployment process)

# 4. Notify stakeholders
# Issue resolved ticket describing the issue and rollback reason
```

### Graceful Rollback (for performance/UX issues)

1. **Disable new feature via environment variable:**
   ```bash
   export CSP_ENABLED=false
   export RUNTIME_INTEGRITY_ENABLED=false
   export GUARDIAN_EXTENDED_SELECTORS=false
   # Restart application
   ```

2. **Investigate root cause** (don't immediately revert)

3. **Deploy fix** once root cause identified

### Data Consistency After Rollback

- **No schema changes:** Safe to rollback
- **Capability tokens in Redis:** Will be ignored post-rollback (acceptable)
- **CSP violation logs:** Will remain in database (acceptable)
- **No data loss:** All rollbacks are safe

---

## Sign-Off Checklist

- [ ] **QA Lead:** All tests passing, deployment ready
- [ ] **DevOps Lead:** Infrastructure validated, monitoring in place
- [ ] **Security Lead:** Controls verified, audit documentation finalized
- [ ] **Product Lead:** Stakeholders notified, user communication ready
- [ ] **Engineering Lead:** Code review complete, no open blockers

---

## Post-Deployment Documentation

- [ ] **Deployment Log:** Record actual deployment time, any issues encountered
- [ ] **Performance Baseline:** Document CPU, memory, latency metrics
- [ ] **Security Posture:** Confirm all 9 controls (A-I) are active
- [ ] **Monitoring Status:** Verify all alerts configured and firing correctly
- [ ] **Incident Report:** If any issues, document resolution for future reference

---

**Checklist Version:** 1.0  
**Last Updated:** 2026-03-24  
**Prepared By:** DevOps & Release Team  
**Review Status:** Ready for deployment
