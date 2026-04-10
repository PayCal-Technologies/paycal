# Guardian Metadata Correlation Policy

Version: 2026-03-23
Owner: Security + Privacy Engineering
Applies to: `window.Guardian` consumers, telemetry pipelines, diagnostics dashboards, admin reporting

## Purpose

Define explicit, auditable constraints for correlating identity/profile metadata with productivity or behavioral event streams.

## Default Rule

- Correlation is denied by default.
- Correlation may proceed only for approved contexts listed below and only when all required controls are present.

## Allowed Contexts (Allowlist)

- `security-incident`
- `fraud-investigation`
- `regulatory-legal-hold`

## Prohibited Correlation Pairs

- `profile_pii` + `productivity_events`
- `profile_pii` + `telemetry_content`
- `account_identity` + `work_hours_detail`

## Required Controls for Any Allowed Correlation

- `documented_ticket`: traceable request identifier
- `least_privilege_approval`: explicit approval by authorized reviewer
- `time_bounded_access`: bounded access window with expiry
- `audit_log_entry`: immutable access and query audit record

## Runtime Contract

Guardian exposes:

- `window.Guardian.getMetadataCorrelationPolicy()`
- `window.Guardian.canCorrelateMetadata(context)`

The runtime contract is policy-surface only; enforcement remains at service/controller boundaries and data-access layers.

## Verification Expectations

- Unit/contract tests assert policy object availability and deny-by-default behavior.
- Security review verifies no unauthorized context bypasses allowlist checks.
- Evidence/reporting references this document and the runtime surface for proof closure.
