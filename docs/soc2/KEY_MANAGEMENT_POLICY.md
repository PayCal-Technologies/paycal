# Key Management Policy

Version: 2026-04-10
Owner: Security + Identity Engineering
Applies to: DEK lifecycle, KEK derivation, key wrapping/unwrapping, key rotation, account recovery key handling

## Purpose

Define strict, auditable controls for generation, storage, wrapping, rotation, and retirement of encryption keys in PayCal.

## Default Rule

- Key material handling is denied by default except for explicit lifecycle operations.
- Raw key exposure outside controlled crypto routines is prohibited.

## Allowed Key Lifecycle Contexts (Allowlist)

- `issue-wrap-initial`
- `unwrap-active-session`
- `rotate-crypto-material`
- `recover-account-verified-transaction`

## Prohibited Key Management Patterns

- deriving KEK from non-deterministic signature output
- regenerating user DEK when only adding an additional passkey wrapper
- storing raw DEK in Redis profile hashes, browser localStorage, or logs
- key lifecycle actions without authenticated identity and scoped intent

## Required Controls for Any Key Operation

- deterministic KEK derivation using stable credential identifiers where applicable
- dual-wrap compatibility support (password-wrapped + passkey-wrapped) for migration safety
- one-shot or time-bounded proof for account recovery and replacement-key bootstrap
- key operation audit records with actor, action, target scope, and timestamp
- explicit revoke/replace flow for compromised or lost credentials

## Runtime Mapping

Primary runtime surfaces and policy-relevant implementations:

- `PayCal\\Controllers\\KekController`
- `PayCal\\Controllers\\DEKController`
- `PayCal\\Controllers\\PasskeyController`
- `PayCal\\Controllers\\AccountRecoveryController`
- `PayCal\\Domain\\OrganizationDiscoveryService` org DEK wrap bootstrap flows

Policy is lifecycle-governance; enforcement remains in controller and domain services.

## Verification Expectations

- Tests verify deterministic credential-based key resolution and unwrap success paths.
- Tests verify fallback and migration compatibility without raw key leakage.
- Security review verifies key material is never logged, exposed, or persisted in plaintext.
- Evidence references this policy and key-lifecycle test/telemetry outputs.
