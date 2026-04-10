# PayCal Security & Privacy Hardening Implementation Plan

**Status:** In Progress  
**Last Updated:** 2026-03-23  
**Objective:** Implement seven architectural security and privacy improvements to reduce PII exposure and enhance zero-knowledge encryption coverage.

---

## Executive Summary

This document outlines a structured 7-part plan to harden the PayCal application's security posture, focusing on:
- Expanding zero-knowledge encryption to protect metadata
- Eliminating plaintext PII from frontend and admin interfaces
- Preventing account enumeration attacks
- Enhancing telemetry privacy
- Implementing risk-based session re-authentication

## Phase Closure Program Alignment (2026-03-23)

PayCal now tracks a long-lived architectural closure roadmap in:

- `docs/security/PHASE_CLOSURE_PROGRAM.md`

This supplements the tactical hardening work in this document and defines four closure workstreams:

1. Correlation broker architecture (P0)
2. Telemetry query governance (P0)
3. Admin surface policy closure (P1)
4. Runtime decrypted lifecycle hardening (P2)

Current alignment snapshot:

- Correlation policy deny-by-default is already enforced in key self-service controllers.
- Admin dashboard now applies policy-gated safe exclusion for session/credential enrichment.
- A1 foundation model work has begun with new security domain objects (`CorrelationContext`, `CorrelationDecision`).
- Remaining closure focus is broker centralization, telemetry query governance, and integration-level enforcement proof.

---

## Area 1: Extend Zero-Knowledge Coverage to Site Metadata

**Priority:** HIGH  
**Complexity:** HIGH  
**Estimated Effort:** 4-6 hours

### Current State Analysis
- **File:** `<REPO_ROOT>/html/js/calendar/crypto-worker.js` (lines 418-445)
- **Issue:** `encryptEntry()` returns `site_id` and `site_name` as plaintext alongside `encrypted_blob`
- **Risk:** Server operator can identify all work locations associated with each user
- **Impact:** Reduces zero-knowledge coverage; violates privacy-by-default principle

### Implementation Approach

#### 1.1 Add Site Metadata to Encrypted Blob
**Change Location:** `crypto-worker.js` → `encryptEntry()` function

- Move `site_name` from plaintext field into the JSON object that gets encrypted
- Preserve `site_id` for relational integrity only (opaque identifier pattern)
- Update entry schema: `{ site_id, encrypted_blob }` instead of `{ site_id, site_name, encrypted_blob }`

**Code Changes:**
```javascript
// Before: site_name leaked
return {
  site_id: entry.site_id,
  site_name: entry.site_name,              // ❌ Plaintext metadata
  encrypted_blob: btoa(JSON.stringify({ ... }))
};

// After: site_name encrypted
return {
  site_id: entry.site_id,                   // ✅ Opaque ID only
  encrypted_blob: btoa(JSON.stringify({
    ...existing fields...,
    site_name: entry.site_name,             // ✅ Inside encrypted blob
    metadata: entry.metadata                // ✅ Further metadata protection
  }))
};
```

#### 1.2 Update Client-Side Site Catalog Handling
- Ensure UI passes full entry object to `encryptEntry()`, not just entry ID
- Update site list rendering to work exclusively from decrypted entries
- Add decryption guard: refuse to render site names unless decrypted in memory

#### 1.3 Database Schema & Migration
- No server-side schema change needed (site_name can be dropped from transmission)
- Deprecate plaintext `site_name` storage if currently persisted serverside
- Ensure relational queries work with opaque `site_id` only

### Testing Strategy
- [ ] Verify encrypted entries decrypt correctly after change
- [ ] Confirm DEK version mismatch detection works across old/new format
- [ ] Test site list rendering with both password and passkey auth
- [ ] Validate admin cannot see site names from database directly

---

## Area 2: Encrypt the User Profile Object

**Priority:** HIGH  
**Complexity:** MEDIUM  
**Estimated Effort:** 3-5 hours

### Current State Analysis
- **File:** `<REPO_ROOT>/html/js/earnings/index.php` (lines 170-195)
- **Exposed Fields:** name, email, phone, address, IP address
- **Issue:** `window.PAYCAL_USER_PROFILE` injected as plaintext JSON into frontend
- **Risk:** High-value PII accessible via page source, dev tools, or memory dumps

### Implementation Approach

#### 2.1 Create Encrypted Profile Blob on Server
**Location:** `<REPO_ROOT>/html/js/earnings/index.php`

- Wrap profile data in an envelope: `{ profile_encrypted_blob, profile_nonce, profile_version }`
- Encrypt using same DEK management as work entry encryption
- Only inject encrypted blob, not plaintext profile

**Schema:**
```php
window.PAYCAL_USER_PROFILE_ENCRYPTED = {
  blob: "base64-encoded-aes-gcm-ciphertext",
  nonce: "base64-encoded-nonce",
  version: 1,
  userId: "opaque-id-if-needed"
};
window.PAYCAL_USER_PROFILE = undefined;  // Remove plaintext
```

#### 2.2 Client-Side Decryption on DEK Unlock
**Location:** `crypto-worker.js` → new message handler `decryptProfile()`

- Add new WebWorker message type: `decryptProfile`
- Decrypt profile blob immediately after DEK unlock in calendar unlock flow
- Store decrypted profile in WebWorker memory only (not in window object)
- Expose getter function: `getCachedProfile()` for legitimate frontend requests

```javascript
async function decryptProfile(payload) {
  if (!self.cryptoState.dek) {
    throw new Error('DEK unavailable');
  }
  const blob = JSON.parse(atob(payload.encryptedBlob));
  // Decrypt using existing pattern from decryptEntry
  const profileJson = await crypto.subtle.decrypt(...);
  self.cryptoState.profile = JSON.parse(decoder.decode(profileJson));
  return { success: true };
}

self.getCachedProfile = () => self.cryptoState.profile || null;
```

#### 2.3 Update Earnings Report Generation
- Modify earnings rendering to fetch profile via WebWorker getter
- Ensure report generation waits for DEK unlock before requesting profile
- Test rendering pipeline: unlock → decrypt profile → render

### Testing Strategy
- [ ] Verify profile not in page source after change
- [ ] Confirm earnings reports still render correctly
- [ ] Test page reload maintains encrypted state (no plaintext cached)
- [ ] Validate error handling if profile decryption fails

---

## Area 3: Administrative PII Masking

**Priority:** MEDIUM  
**Complexity:** MEDIUM  
**Estimated Effort:** 2-3 hours

### Current State Analysis
- **File:** `<REPO_ROOT>/html/src/Controllers/AdminPageController.php` (line 235)
- **Exposed Data:** registered IP, login IP, session IP, phone number all in data attributes
- **Issue:** Admin interface exposes full plaintext values in HTML data attributes
- **Risk:** Insider threat; accidental PII exposure in screenshots/exports

### Implementation Approach

#### 3.1 Implement Masking Utility Function
**Location:** `AdminPageController.php` → new static method

```php
private static function maskIPAddress(string $ip): string
{
  if (empty($ip)) return 'Unknown';
  $parts = explode('.', $ip);
  if (count($parts) === 4) {
    return $parts[0] . '.' . $parts[1] . '.X.X';  // e.g., 192.168.X.X
  }
  return 'X.X.X.X';  // IPv6 or invalid
}

private static function maskPhoneNumber(string $phone): string
{
  if (empty($phone)) return 'Unknown';
  $cleaned = preg_replace('/[^0-9]/', '', $phone);
  if (strlen($cleaned) < 4) return 'Unknown';
  return substr($cleaned, 0, 3) . '-XXX-' . substr($cleaned, -4);
}
```

#### 3.2 Update Admin Dashboard Button Generation
**Location:** Line 235 of AdminPageController

- Change data attributes to use masked values
- Keep full plaintext in sanitized in database (no change there)
- Store original IP/phone in separate encrypted audit storage (future work)

```php
// Before:
$safeRegisteredIp = htmlspecialchars($registeredIp, ENT_QUOTES, 'UTF-8');

// After:
$maskedRegisteredIp = self::maskIPAddress($registeredIp);
$safeRegisteredIp = htmlspecialchars($maskedRegisteredIp, ENT_QUOTES, 'UTF-8');
```

#### 3.3 Add "View Full Details" Permission & Audit Logging
- Create new permission check: `User::canViewUnmaskedPII()`
- Require explicit admin action (button click) to reveal full values
- Log each access attempt with timestamp and admin UUID

**Implementation (future phase):**
```php
// In admin JS when "view details" clicked:
POST /api/v1/admin/audit/request-pii-access
{ userId: "...", field: "phone|ip_address" }
// Log event, return full value only if permitted
```

### Testing Strategy
- [ ] Verify admin dashboard shows masked values by default
- [ ] Check data attributes contain masked IPs (developer tools)
- [ ] Confirm masked format: 192.168.X.X and 555-XXX-1234
- [ ] Test with various IP formats (valid, invalid, missing)

---

## Area 4: Harden Recovery & Verification Workflows

**Priority:** MEDIUM-HIGH  
**Complexity:** MEDIUM  
**Estimated Effort:** 2-4 hours

### Current State Analysis
- **Files:**
  - `<REPO_ROOT>/html/js/auth-recovery/index.php` (line 145-160)
  - `<REPO_ROOT>/html/src/Controllers/RecoveryEmailController.php`
- **Issue:** API responses may differ for valid vs. invalid email (account enumeration risk)
- **Risk:** Attacker can enumerate registered users via timing or response variance

### Implementation Approach

#### 4.1 Implement Uniform Response Pattern
**Location:** `RecoveryEmailController.php` → all start/verify/resend endpoints

- Always respond with HTTP 200 OK for all requests (valid or invalid email)
- Return identical JSON structure regardless of account existence
- Add artificial delay to equalize response times (random 100-500ms)

```php
// Before: May return different status codes
if (!userExists($email)) {
  return Response::error('Account not found', HttpStatus::HTTP_NOT_FOUND);
}

// After: Uniform response
$userExists = checkUserExists($email);
if (!$userExists) {
  // Still generate response structure, but txn_id is null
  // Caller will fail silently on verification step
}
// Always respond with same structure:
Response::success([
  'txn_id' => $userExists ? $txnId : null,
  'message' => 'If account exists, verification code has been sent',
]);
```

#### 4.2 Add Response Time Equalization
**Location:** Recovery endpoint handlers

```php
$startTime = microtime(true);
// ... do work ...
$elapsed = (microtime(true) - $startTime) * 1000;  // milliseconds
$targetDelay = random_int(100, 500);  // 100-500ms baseline
$remainingDelay = max(0, ($targetDelay - $elapsed));
if ($remainingDelay > 0) {
  usleep($remainingDelay * 1000);
}
```

#### 4.3 Client-Side Verification Enforcement
**Location:** `auth-recovery/index.php` recovery flow

- Client should not distinguish between valid/invalid responses
- Verification step will naturally fail if account doesn't exist (txn_id is null)
- Error message should remain generic: "Invalid code or session expired"

### Testing Strategy
- [ ] Test with valid email: should succeed with valid txn_id
- [ ] Test with invalid email: should return same structure, null txn_id
- [ ] Measure response times: should be within ±50ms across both cases
- [ ] Verify verification step fails gracefully with null txn_id

---

## Area 5: Minimize Client Fingerprinting

**Priority:** MEDIUM  
**Complexity:** LOW-MEDIUM  
**Estimated Effort:** 2-3 hours

### Current State Analysis
- **File:** `<REPO_ROOT>/html/js/calendar/crypto-worker.js` (lines 15-25)
- **Current Function:** `safeFingerprint()` creates djb2 hash of environment variable
- **Issue:** Fingerprint is persistent across sessions, linked to recovery payloads
- **Risk:** Permanent hardware-to-user correlation; fingerprint collection across databases could identify user

### Implementation Approach

#### 5.1 Generate Session-Based Diagnostic Token
**Location:** `crypto-worker.js` → replace `safeFingerprint()` usage

- Generate random 256-bit token on DEK unlock, not on page load
- Store in WebWorker memory, associated with current DEK session
- Clear on DEK lock or session end

```javascript
function generateSessionDiagnosticToken() {
  // Generate fresh random token per session
  const randomBytes = crypto.getRandomValues(new Uint8Array(32));
  return bytesToB64(randomBytes);
}

// Use at recovery proof time, not page load:
self.cryptoState.sessionDiagnosticToken = generateSessionDiagnosticToken();
```

#### 5.2 Update Recovery Proof Payload
**Location:** `crypto-worker.js` → `generateRecoveryMaterial()` function

- Replace `clientFingerprintHash` with session token
- Token is discarded after session ends, new token on next authentication
- Server cannot link tokens across sessions

```javascript
// Before:
recoveryProof.clientFingerprintHash = safeFingerprint(navigator.userAgent);

// After:
if (!self.cryptoState.sessionDiagnosticToken) {
  self.cryptoState.sessionDiagnosticToken = generateSessionDiagnosticToken();
}
recoveryProof.sessionToken = self.cryptoState.sessionDiagnosticToken;
```

#### 5.3 Deprecate Fingerprint from Backend
**Location:** Recovery verification endpoints

- Stop validating fingerprint in recovery proof validation
- Accept session token for current recovery flow only
- Server deletes session token after recovery completion

### Testing Strategy
- [ ] Verify session token regenerated on each new authentication
- [ ] Confirm different sessions get different tokens
- [ ] Test recovery still works without fingerprint validation
- [ ] Verify token is cleared on logout

---

## Area 6: Strengthen Telemetry Anonymization

**Priority:** LOW-MEDIUM  
**Complexity:** MEDIUM  
**Estimated Effort:** 3-4 hours

### Current State Analysis
- **Files:**
  - `<REPO_ROOT>/html/src/Domain/AccountRecoveryAbuseGuard.php`
  - Telemetry infrastructure found in `increment*` methods and `/api/v1/telemetry` endpoints
- **Current Pattern:** Real-time event beacons with timestamps tied to specific dates
- **Issue:** Immediate beacons allow correlation attacks; timestamps reveal user activity patterns

### Implementation Approach

#### 6.1 Client-Side Event Aggregation Buffer
**Location:** Client-side telemetry module (new or existing)

- Collect events in memory buffer (not immediate beacons)
- Batch events every 5-15 minutes with randomized jitter
- Clear buffer on logout or idle timeout (>30 minutes)

```javascript
class TelemetryBuffer {
  constructor() {
    this.events = {};
    this.flushIntervalMs = 5 * 60 * 1000 + Math.random() * 10 * 60 * 1000;
    this.startFlushTimer();
  }

  recordEvent(eventType, count = 1) {
    this.events[eventType] = (this.events[eventType] || 0) + count;
  }

  async flush() {
    if (Object.keys(this.events).length === 0) return;
    await postJson('/api/v1/telemetry/batch', {
      events: this.events,
      timestamp: Date.now()
    });
    this.events = {};
  }

  startFlushTimer() {
    setTimeout(() => this.flush(), this.flushIntervalMs);
  }
}
```

#### 6.2 Server-Side Telemetry Aggregation
**Location:** Telemetry or metrics endpoints

- Accept batched event counts instead of individual events
- Store date-bucketed aggregates: reduce precision to hourly or daily
- Do not store per-user telemetry in queryable form

#### 6.3 Add Noise Injection for Small Counts
**Location:** Batch submission or server aggregation

- For low-count events (< 5), add ±1-2 uniform random noise to counts
- Example: 2 failures becomes 1-3 in aggregated report; indistinguishable from other users

```php
if ($count <= 5) {
  $noise = random_int(-2, 2);
  $count = max(0, $count + $noise);
}
```

### Testing Strategy
- [ ] Verify events buffer in memory and don't send immediately
- [ ] Confirm batch sent only after flush timer expires
- [ ] Test events with noise applied to low-count aggregates
- [ ] Verify batch format reaches server correctly

---

## Area 7: Enforce Stricter Session Dynamics

**Priority:** MEDIUM  
**Complexity:** MEDIUM-HIGH  
**Estimated Effort:** 3-5 hours

### Current State Analysis
- **Files:**
  - Session management: `Authentication.php`, `UserPreferenceDefaults.php`
  - Default timeout: `'forever'` for session_timeout preference
- **Current Pattern:** Long-lived sessions with no re-authentication for sensitive actions
- **Risk:** Device compromise or theft allows attacker extended access to sensitive operations

### Implementation Approach

#### 7.1 Identify High-Risk Actions
**Scope:** Operations requiring sliding-window re-auth

- Export YTD PDF report (sensitive financial data)
- Change recovery email address
- Modify payment method or corporate account settings
- Download full wage data
- Change security settings (session timeout, MFA)

#### 7.2 Implement Sliding-Window Re-authentication Check
**Location:** New middleware/controller method

```php
public static function requireRecentAuthentication(
  int $maxAgeSeconds = 300  // 5 minutes
): void {
  if (!Authentication::validateAndTouchSession()) {
    Response::error('Unauthorized', HttpStatus::HTTP_UNAUTHORIZED);
    return;
  }

  $lastAuthTime = (int) (User::current()->last_signin ?? 0);
  $ageSeconds = time() - $lastAuthTime;

  if ($ageSeconds > $maxAgeSeconds) {
    Response::error(
      'Session re-authentication required for this action',
      ['requiresReauth' => true],
      HttpStatus::HTTP_403
    );
    return;
  }
}
```

#### 7.3 Client-Side Re-auth Challenge UI
**Location:** High-risk action buttons and endpoints

- Intercept high-risk action requests
- If API returns `requiresReauth`, prompt modal for password/passkey re-authentication
- Re-authenticate, refresh session token, retry original action

```javascript
async function executeHighRiskAction(action) {
  try {
    const response = await postJson(action.endpoint, action.payload);
    if (response.requiresReauth) {
      await showReauthModal();
      return executeHighRiskAction(action);  // Retry
    }
    return response;
  } catch (error) {
    // handle error
  }
}
```

#### 7.4 Configurable Re-auth Age
**Location:** User preferences (settings page)

- Allow user to set custom re-auth window: 1 minute, 5 minutes, 15 minutes, 1 hour
- Default: 5 minutes for "High" security preset, 15 minutes for "Balanced"
- Store in user preferences table

### Testing Strategy
- [ ] Verify PDF export prompts re-auth after 5 minutes of inactivity
- [ ] Confirm recovery email change requires recent authentication
- [ ] Test successful re-auth allows action to proceed
- [ ] Verify modal dismissal properly cancels high-risk action
- [ ] Test user preference for custom re-auth window applies correctly

---

## Cross-Cutting Concerns

### Backward Compatibility
- Existing encrypted entries (v1) must continue to decrypt
- DEK version check prevents format mismatches
- No breaking changes to authentication flows

### Audit Logging
- All PII access (especially Area 3 unmasking) must be logged
- Include admin UUID, timestamp, and field accessed
- Maintain audit log for minimum 90 days

### CSP Compliance
- No inline styles or scripts (already enforced)
- All dynamic functionality in external modules
- No style injection for masking (use CSS classes only)

### Testing Matrix
| Area | Unit Tests | Integration Tests | Manual Tests |
|------|-----------|------------------|-------------|
| 1    | ✓         | ✓                | ✓           |
| 2    | ✓         | ✓                | ✓           |
| 3    | ✓         | ✓                | ✓           |
| 4    | ✓         | ✓                | ✓           |
| 5    | ✓         | ✓                | ✓           |
| 6    | -         | ✓                | ✓           |
| 7    | ✓         | ✓                | ✓           |

---

## Implementation Schedule

### Phase 1: Foundational (Areas 1-2)
**Duration:** 1-2 weeks
- Extend crypto coverage to site metadata
- Encrypt user profile object
- Maintain DEK backward compatibility
- Comprehensive crypto testing

### Phase 2: Access Control (Area 3)
**Duration:** 1 week
- Implement admin PII masking
- Add audit logging framework
- Test masking across admin workflows

### Phase 3: Authentication Hardening (Areas 4-5)
**Duration:** 1-2 weeks
- Uniform recovery responses
- Replace fingerprinting with session tokens
- Test account enumeration mitigations

### Phase 4: Privacy Mechanisms (Areas 6-7)
**Duration:** 1-2 weeks
- Client-side telemetry aggregation
- Sliding-window re-authentication
- User preference defaults

### Phase 5: Hardening & Rollout (All Areas)
**Duration:** 1-2 weeks
- Comprehensive integration testing
- Version and changelog updates
- Staged rollout to production

---

## Rollback & Contingency

### Per-Area Rollback
- Each area can be independently disabled via feature flag
- Feature flags stored in Redis: `feature:skip_area_*`
- Admin dashboard to toggle per-area (future addition)

### DEK Compatibility
- Keep old DEK version logic functional for 2 releases minimum
- Never break DEK decryption for existing users
- Test decrypt path before removing legacy code

### Database Migrations
- Use blue-green deployment for schema changes (if any)
- No data loss during migrations
- Rollback plan: restore from snapshot within 24 hours

---

## Success Metrics

After implementation, measure:
- ✓ Zero plaintext PII in page source for authenticated pages
- ✓ Zero plaintext PII in debug output or browser storage
- ✓ Admin masking reduces sensitive data exposure by 95%+
- ✓ No detectable account enumeration via recovery endpoint
- ✓ Session token diversity across logged-in sessions
- ✓ Telemetry batch deployment eliminates single-event timestamps
- ✓ Re-auth prompts trigger for high-risk actions without false positives

---

## References

**Related Documentation:**
- WCAG Accessibility Audit: `docs/WCAG_AUDIT_AND_ACTION_PLAN.md`
- Theme Hardening: `docs/THEME_TOKEN_MIGRATION_ACTION_PLAN.md`
- Existing Crypto Implementation: `html/js/calendar/crypto-worker.js`
- Admin Architecture: `html/src/Controllers/AdminPageController.php`

**Compliance & Standards:**
- **Principle of Least Privilege:** Minimize plaintext PII exposure
- **Data Minimization:** Encrypt metadata; return only opaque identifiers
- **Defense in Depth:** Multiple layers (encryption, masking, audit, re-auth)
- **Privacy by Design:** Zero-knowledge architecture as core principle
