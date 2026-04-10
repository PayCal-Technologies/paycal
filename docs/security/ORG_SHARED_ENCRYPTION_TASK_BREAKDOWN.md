# ORG Shared Encryption Task Breakdown

Status: Execution plan
Date: 2026-04-07
Depends on: `docs/security/ORG_SHARED_ENCRYPTION_IMPLEMENTATION_SPEC.md`

## Scope

Translate the spec into concrete implementation tasks mapped to existing files and methods.

## Phase A - Data Contracts and Constants

### A1. Add encryption-mode constants and membership states

Files:
- `html/src/Domain/OrganizationDiscoveryService.php`
- `html/src/Domain/Constants/Keys.php`

Tasks:
1. Add membership states: `pending`, `consented`, `active`, `suspended`, `revoked`.
2. Add org encryption mode constants: `personal`, `organization`.
3. Add org DEK segment constants: `current_period`, `archive`.
4. Add Redis key builders in `Keys.php` for:
- consent records and indexes
- org DEK registry/version
- org DEK wraps

Acceptance:
- New constants used by service/controller code (no hardcoded repeated strings).

### A2. Envelope metadata validation contract

Files:
- `html/src/Domain/WorkEntry.php`

Tasks:
1. Add strict metadata validator for org-mode envelope fields:
- `encryption_mode`
- `org_id`
- `segment`
- `key_version`
- `dek_id`
- `needs_rewrap`
2. Keep backward compatibility behind a feature gate (reject malformed org-mode payloads when enabled).

Acceptance:
- Unit tests enforce explicit required fields and fail on inferred/missing values.

## Phase B - Consent + Membership Activation

### B1. Consent record persistence and verification

Files:
- `html/src/Domain/OrganizationDiscoveryService.php`

Tasks:
1. Add methods:
- `recordOrgConsent(...)`
- `loadActiveOrgConsent(...)`
- `isConsentValidForWrap(...)`
2. Persist consent record with disclaimer hash and version.
3. Add helper to deny wrap/unwrap when consent is missing/inactive.

Acceptance:
- Membership cannot reach `active` without consent record.

### B2. Membership state transition enforcement

Files:
- `html/src/Domain/OrganizationDiscoveryService.php`
- `html/src/Controllers/OrganizationDiscoveryController.php`

Tasks:
1. Add transition guard map for membership lifecycle.
2. Update invite/request acceptance flows:
- `acceptInvite(...)`
- `approveAccessRequest(...)`
3. Force `pending -> consented -> active` activation path.

Acceptance:
- Direct transition to `active` without consent path is rejected.

## Phase C - ORG DEK Lifecycle Service

### C1. ORG DEK version and wrap management

Files:
- `html/src/Domain/OrganizationDiscoveryService.php`
- New file: `html/src/Domain/OrganizationEncryptionService.php`

Tasks:
1. Create service for:
- create version (`current_period`, `archive`)
- wrap for active member credentials
- rotate by segment/reason
- fallback-version listing
2. Require consent binding on wrap record creation (`consent_id`).

Acceptance:
- Wrap creation fails if no valid consent link.

### C2. Rotation integration hooks

Files:
- `html/src/Domain/OrganizationDiscoveryService.php`

Tasks:
1. On membership revoke/suspend: trigger rotation by policy.
2. On ownership transfer/member removal: trigger rotation and re-wrap.

Acceptance:
- Rotation audit event emitted on every membership removal path.

## Phase D - API Surface

### D1. Membership consent endpoint

Files:
- `html/src/Controllers/OrganizationDiscoveryController.php`
- `html/src/Domain/OrganizationDiscoveryService.php`

Tasks:
1. Add endpoint (route alias style consistent with controller):
- `POST organizations/{organizationID}/membership/accept`
2. Endpoint behavior:
- validate invite/request token context
- persist consent
- provision wraps
- activate membership

Acceptance:
- Partial failures do not leave invalid `active` state.

### D2. Key rotation endpoint

Files:
- `html/src/Controllers/OrganizationDiscoveryController.php`
- `html/src/Domain/OrganizationDiscoveryService.php`
- `html/src/Domain/OrganizationEncryptionService.php`

Tasks:
1. Add endpoint:
- `POST organizations/{organizationID}/keys/rotate`
2. Validate caller role/scope (`org.settings.write` or higher policy).

Acceptance:
- Rotation updates current version atomically per segment.

### D3. Org-mode work read/write endpoints

Files:
- New file: `html/src/Controllers/OrganizationWorkController.php` (preferred)
- OR extend: `html/src/Controllers/CalendarController.php` with explicit org mode branch

Tasks:
1. Add read endpoint:
- `POST org/work/read`
2. Add write endpoint:
- `POST org/work/write`
3. Server-side validations:
- active membership state
- scope checks (`work.read`, `work.write`)
- target member belongs to org
- current pay-period edit constraint for write
- envelope metadata strict validation

Acceptance:
- No trust in client-provided authorization decisions.

## Phase E - Calendar and WorkEntry Integration

### E1. Save path integration

Files:
- `html/src/Controllers/CalendarController.php`
- `html/src/Domain/WorkEntryRepository.php`
- `html/src/Domain/WorkEntry.php`

Tasks:
1. Keep existing personal mode behavior unchanged.
2. Add explicit org-mode save path to pass owner/target context and metadata checks.
3. Ensure lock checks still run for target user date.

Acceptance:
- Current personal calendar path remains green in regression tests.

### E2. Dual-read + rewrap

Files:
- `html/src/Domain/WorkEntry.php`
- `html/src/Controllers/EarningsController.php`
- `html/src/Controllers/SitesController.php`

Tasks:
1. Add decrypt strategy:
- current key version first
- fallback versions second
2. Set `needs_rewrap=true` when fallback used.
3. Add rewrap routine to write current key version and clear flag.

Acceptance:
- Mixed-version payloads are readable and converge to current version.

## Phase F - UI and Disclaimer Flows

### F1. Consent UX before activation

Files:
- `html/organizations/index.php`
- `html/js/organizations/index.php`

Tasks:
1. Add required consent checkpoint UI before final join/accept actions.
2. Include explicit forward-secrecy limitation text.
3. Block activation submit until acknowledgment is checked.

Acceptance:
- Join path cannot complete without explicit consent.

### F2. Shared-mode signaling

Files:
- `html/js/calendar/index.php`
- `html/js/calendar/calendar.js`
- Optional supporting CSS in `html/css/...`

Tasks:
1. Show `Shared with Organization` indicator in org mode.
2. Show org context (org name, segment, key-version migration notices).

Acceptance:
- Users can distinguish personal vs organization encryption mode at save/read time.

## Phase G - Audit and Telemetry

### G1. Audit events

Files:
- `html/src/Domain/OrganizationDiscoveryService.php`
- `html/src/Controllers/OrganizationDiscoveryController.php`

Tasks:
1. Emit events:
- `org.consent.accepted`
- `org.membership.state_changed`
- `org.dek.wrap.created`
- `org.dek.rotated`
- `org.dek.unwrap.denied`
- `org.work.read`
- `org.work.write`
- `org.work.rewrapped`

Acceptance:
- All event types visible through existing org audit timeline/grid mechanisms.

### G2. Telemetry counters

Files:
- `html/src/Domain/*` where encryption decisions occur

Tasks:
1. Add counters:
- `unwrap_denied_no_consent`
- `unwrap_denied_inactive_membership`
- `unwrap_denied_missing_wrap`
- `decrypt_fallback_version_used`
- `rewrap_success`
- `rewrap_failure`

Acceptance:
- Counters increment deterministically in test scenarios.

## Phase H - Tests

### H1. Unit tests

Files (new):
- `html/tests/Unit/OrganizationEncryptionServiceTest.php`
- `html/tests/Unit/OrgConsentBindingTest.php`
- `html/tests/Unit/WorkEntryOrgEnvelopeValidationTest.php`

Coverage:
- consent binding required for unwrap
- membership-state gating
- strict envelope metadata
- fallback version + rewrap behavior

### H2. Integration tests

Files (new):
- `html/tests/Integration/OrganizationMembershipConsentIntegrationTest.php`
- `html/tests/Integration/OrganizationKeyRotationIntegrationTest.php`
- `html/tests/Integration/OrganizationWorkReadWriteIntegrationTest.php`

Coverage:
- join without consent denied
- revoke triggers rotation and blocks future decrypt
- org read/write scope gating
- current pay-period write-only enforcement
- replay/unauthorized unwrap rejection

## Phase I - Rollout Controls

Files:
- `html/src/Domain/Config/SystemConfig.php`
- `html/src/Domain/Config/...` feature toggle config locations

Tasks:
1. Add feature flags:
- `org_shared_encryption_enabled`
- `org_shared_encryption_enforce_strict_envelope`
- `org_shared_encryption_enable_write`
2. Launch read-only before write.

Acceptance:
- Flags allow phased enablement and rollback.

## Suggested Build Order (Low-Risk)

1. Phase A + B
2. Phase C
3. Phase D read endpoint
4. Phase E dual-read
5. Phase F consent UI
6. Phase D write endpoint
7. Phase G + H
8. Phase I staged rollout

## Open Coordination Items

1. Confirm final route naming convention (`organizations/{orgId}/...` vs top-level `/org/...`).
2. Confirm fallback-version retention policy window.
3. Confirm whether archive segment rotates on schedule or incident-only.
