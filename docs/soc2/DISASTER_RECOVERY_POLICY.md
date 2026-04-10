# Disaster Recovery Policy

Version: 2026-04-10
Owner: Security Operations + Platform Reliability
Applies to: service disruption, data corruption events, infrastructure outage, security incident containment and restoration workflows

## Purpose

Define explicit, auditable controls for service recovery, data restoration, and post-incident assurance during high-impact incidents.

## Default Rule

- Recovery actions are denied by default unless initiated under an approved incident context.
- Emergency operations must preserve authentication, authorization, and auditability requirements.

## Allowed Disaster Recovery Contexts (Allowlist)

- `service-outage-recovery`
- `data-corruption-restore`
- `security-containment-failover`
- `legal-regulatory-hold-recovery`

## Prohibited Recovery Actions

- restoring data from unverified backup/snapshot artifacts
- bypassing access controls to accelerate restoration
- production data mutation without incident ticket linkage
- closing incidents without restore validation and integrity checks

## Required Controls for Any Recovery Operation

- documented incident ticket with severity classification and owner
- least-privilege approval for restore/failover operations
- time-bounded emergency access with automatic expiry
- immutable audit log entries for all recovery actions
- post-restore validation: data integrity checks, application health checks, and access boundary verification
- post-incident review with corrective actions and tracked closure

## Runtime Mapping

Operational and application surfaces associated with recovery execution:

- incident response and ops runbook workflow
- application diagnostics and health endpoints
- backup/snapshot restore tooling and failover procedures
- security telemetry and audit reporting pipelines

Policy is operational governance; technical execution remains in ops runbooks and service controls.

## Verification Expectations

- Quarterly recovery drills validate restore correctness and operational readiness.
- Drill and incident records include timing, actions, outcomes, and follow-up items.
- Security review confirms emergency access controls were scoped, logged, and revoked.
- Evidence references this policy and disaster recovery drill artifacts.
