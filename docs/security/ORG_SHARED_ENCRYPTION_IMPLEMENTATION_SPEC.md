# ORG Shared Encryption Implementation Spec

Status: Draft (implementation-ready)
Date: 2026-04-06
Owner: Platform Security + Organizations + Calendar/Earnings

## 1. Goal

Enable organization-authorized members to view member work data and edit current pay-period work data using organization-shared encryption, without weakening personal encryption guarantees.

This spec treats encryption behavior as a contract, not a UI feature.

## 2. Non-Negotiable Security Requirements

1. Consent is cryptographically bound to key access.
2. Membership state gates all decrypt capability.
3. Encryption mode is explicit per record; never inferred.
4. Revocation is forward-secrecy only.
5. Server validates encryption and authorization decisions regardless of client claims.

## 3. Threat and Blast-Radius Model

### 3.1 DEK Segmentation Strategy

Default: Per-period segmentation at org scope.

- `ORG_DEK_CURRENT_PERIOD`
- `ORG_DEK_ARCHIVE`

Rationale:
- Better exposure control than single ORG DEK.
- Lower operational complexity than per-user ORG DEKs.

### 3.2 Revocation Semantics (Explicit)

Revocation guarantees:
- No new unwrap operations for removed/suspended users.
- No future data encrypted under post-rotation versions is readable by removed/suspended users.

Revocation cannot guarantee:
- Recovery of previously decrypted and exfiltrated plaintext.

UI and policy copy must state this limitation.

## 4. Membership and Consent State Machine

Membership state values:
- `pending`: invited/requested; no consent
- `consented`: user accepted org data-sharing terms
- `active`: key wraps provisioned and usable
- `suspended`: temporary block; no unwrap
- `revoked`: removed; no unwrap; triggers rotation

Access rule:
- Work read/write in org mode requires `membership_state == active`.

Transitions:
- `pending -> consented -> active`
- `active -> suspended`
- `suspended -> active`
- `active|suspended -> revoked`

## 5. Data Model and Redis Key Contract

## 5.1 Consent Records

`organization:consent:{consent_id}`
- `consent_id`
- `org_id`
- `user_uuid`
- `consent_version`
- `accepted_at`
- `ip_hash`
- `user_agent_hash`
- `disclaimer_text_hash`
- `status` (`active|revoked`)

Index keys:
- `organization:consents:org:{org_id}` -> set(consent_id)
- `organization:consents:user:{user_uuid}` -> set(consent_id)

## 5.2 ORG DEK Version Registry

`organization:dek:{org_id}:{segment}`
- `current_version`
- `status`
- `rotated_at`

`organization:dek:version:{org_id}:{segment}:{version}`
- `dek_id`
- `segment`
- `key_version`
- `created_at`
- `rotation_reason`
- `status` (`active|fallback|retired`)

Segments:
- `current_period`
- `archive`

## 5.3 ORG DEK Wrap Records (Consent-Bound)

`organization:dek:wrap:{org_id}:{segment}:{version}:{user_uuid}:{credential_id}`
- `org_id`
- `segment`
- `key_version`
- `user_uuid`
- `credential_id`
- `wrapped_dek`
- `kdf_profile`
- `consent_id` (required)
- `created_at`
- `expires_at` (optional)
- `status` (`active|revoked`)

Hard rule:
- Unwrap denied if wrap record is missing, non-active, consent_id missing, or consent invalid/inactive.

## 5.4 Work Entry Envelope Metadata (Strict)

Each work entry must include explicit encryption metadata:
- `encryption_mode`: `personal|organization`
- `org_id`: required when mode is `organization`
- `segment`: `current_period|archive` when mode is `organization`
- `key_version`: required when mode is `organization`
- `dek_id`: required when mode is `organization`
- `needs_rewrap`: `true|false`

## 6. API Contract (v1)

Note: Route naming may be mounted under existing organizations controller prefixes; behavior is normative.

## 6.1 Membership Acceptance + Consent

`POST /api/v1/org/membership/accept`

Request:
- `org_id`
- `invite_id|request_id`
- `consent_version`
- `consent_acknowledged=true`
- `csrf_token`

Server behavior:
1. Validate invite/request and caller identity.
2. Persist consent record with disclaimer hash.
3. Set membership to `consented`.
4. Provision wraps for active ORG DEK versions.
5. Set membership to `active`.
6. Emit audit events.

Failure behavior:
- No partial activation. If wrap provisioning fails, remain `consented` and return recoverable status.

## 6.2 ORG Key Rotation

`POST /api/v1/org/keys/rotate`

Request:
- `org_id`
- `segment` (`current_period|archive|all`)
- `reason` (`member_revoked|incident|scheduled`)

Server behavior:
1. Create new version(s).
2. Re-wrap for currently `active` members only.
3. Mark previous version as `fallback` for bounded migration window.
4. Record rotation audit event.

## 6.3 Work Read (Org Mode)

`POST /api/v1/work/read`

Request:
- `mode` (`personal|organization`)
- `org_id` (required for org mode)
- `target_user_uuid` (required for org mode)
- date range fields

Server validations in org mode:
- Membership state active.
- Scope includes `work.read`.
- Target user is active org member.
- Linked site/org ownership constraints pass.
- Decrypt allowed only with valid wrap + valid consent binding.

## 6.4 Work Write (Org Mode)

`POST /api/v1/work/write`

Request:
- `mode` (`personal|organization`)
- `org_id` (required for org mode)
- `target_user_uuid`
- encrypted entries payload

Server validations in org mode:
- Membership state active.
- Scope includes `work.write`.
- Date must be in current pay period for target user.
- Historical lock enforcement unchanged.
- Encryption metadata must be explicit and valid.

## 7. Decrypt and Migration Logic

## 7.1 Dual-Read Version Handling

For org-mode entries:
1. Attempt current key version.
2. Attempt allowed fallback versions.
3. If fallback succeeds, set `needs_rewrap=true` and schedule rewrap.

## 7.2 Rewrap Behavior

- Rewrap writes ciphertext under current key version.
- Preserve business fields exactly.
- Clear `needs_rewrap` after successful rewrite.
- Emit `work.rewrapped` audit event.

## 8. UX and Disclaimer Requirements

## 8.1 Mandatory Join/Accept Disclaimer

Display before consent acceptance:
- Org admins/members with granted scopes may read your org-shared work records.
- Current pay-period edits may be made by authorized members.
- Leaving/revocation prevents future access but cannot retract previously viewed data.

User must explicitly acknowledge (unchecked by default).

## 8.2 Runtime UX Signals

When mode is organization:
- Show persistent badge: `Shared with Organization`.
- Show org name and period segment.
- Show key-version mismatch/rewrap notices when applicable.

## 9. Audit and Telemetry

Required audit events:
- `org.consent.accepted`
- `org.membership.state_changed`
- `org.dek.wrap.created`
- `org.dek.unwrap.denied`
- `org.dek.rotated`
- `org.work.read`
- `org.work.write`
- `org.work.rewrapped`

Required telemetry counters:
- unwrap_denied_no_consent
- unwrap_denied_inactive_membership
- unwrap_denied_missing_wrap
- decrypt_fallback_version_used
- rewrap_success
- rewrap_failure

## 10. Rollout Plan

Phase 0: Schema + feature flags
- Add data model keys and parser support for strict envelope metadata.

Phase 1: Consent-bound membership activation
- Enforce `pending -> consented -> active`.

Phase 2: Org read path
- Enable `work.read` in org mode behind flag.

Phase 3: Org current-period write path
- Enable `work.write` in org mode behind flag.

Phase 4: Rotation and migration tooling
- Add scheduled rotation and fallback rewrap jobs.

Phase 5: Enforcement hardening
- Remove legacy inference paths; reject malformed envelopes.

## 11. Test Matrix (Required)

Consent:
- Join without consent -> denied.
- Consent revoked/inactive -> unwrap denied.

Membership:
- `pending|consented|suspended|revoked` cannot decrypt.
- Only `active` can decrypt.

Key rotation:
- Revoked member cannot decrypt post-rotation versions.
- Active members retain access after re-wrap.

Migration:
- Mixed key versions readable via fallback.
- Rewrap upgrades to current version and clears `needs_rewrap`.

Attack cases:
- Replay old wrap rejected.
- Unwrap without org membership rejected.
- Org mismatch between record metadata and request context rejected.

## 12. Open Decisions (Short List)

1. Fallback-key retention TTL and maximum fallback depth.
2. Whether archive segment rotates on schedule or incident-only.
3. Whether per-user DEK segmentation is needed for high-risk enterprise tier.
4. Final route naming under existing controller namespace (`organizations/{orgId}/...` aliasing).

## 13. Definition of Done

Feature is complete when:
- Consent-bound wraps are enforced server-side.
- Org mode read/write passes required tests.
- Membership state machine gates decrypt and write paths.
- Rotation + fallback + rewrap operate without plaintext loss.
- UX disclaimer and shared-visibility indicators are live.
