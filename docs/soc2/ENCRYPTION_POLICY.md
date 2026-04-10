# Encryption Policy

Version: 2026-04-10
Owner: Security + Platform Engineering
Applies to: user data at rest, organization-shared records, transport/session encryption boundaries, and all encryption/decryption service paths

## Purpose

Define explicit, auditable encryption requirements for PayCal data confidentiality, integrity, and controlled decryption access.

## Default Rule

- Encryption is required by default for sensitive data at rest.
- Decryption is denied by default unless the request context is explicitly authorized and all required controls are present.

## Allowed Encryption Contexts (Allowlist)

- `user-private-records`
- `org-shared-records`
- `account-recovery-bootstrap`
- `incident-forensics-readonly`

## Prohibited Encryption States

- plaintext persistence of sensitive fields in application storage
- plaintext DEK storage in browser persistence or server-side profile fields
- bypassing envelope/version checks for encrypted payloads
- decrypting organization-shared payloads without valid relationship/consent gating

## Required Controls for Encryption and Decryption

- authenticated caller and scoped authorization check
- envelope/version validation before decrypt operations
- deny-safe behavior when unwrap/decrypt prerequisites are missing
- immutable audit telemetry for critical encryption and decrypt decisions
- bounded retry/rate-limit controls on sensitive crypto endpoints

## Runtime Mapping

Primary runtime surfaces and enforcement points:

- `PayCal\\Controllers\\DEKController`
- `PayCal\\Controllers\\KekController`
- `PayCal\\Domain\\OrganizationEncryptionService`
- `PayCal\\Domain\\WorkEntry` encryption/decryption paths

Policy is declarative; enforcement remains at controller and domain service boundaries.

## Verification Expectations

- Unit/integration tests verify deny-safe behavior for missing/invalid unwrap context.
- Contract tests verify encrypted payload handling and envelope compatibility behavior.
- Security review verifies no unauthorized plaintext persistence or bypass paths.
- Evidence references this policy and associated crypto-path test suites.
