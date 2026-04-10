# Telemetry Retention and Access Boundary Policy

Version: 2026-03-23
Owner: Security + Product Observability

## Stream Separation

- Product telemetry stream: operational counters and aggregated UX events.
- Security telemetry stream: abuse, replay, incident, and forensic signals.

## Retention

- Product telemetry retention: 30 days.
- Security telemetry retention: 90 days.

## Access Boundaries

- Product telemetry access boundary: `product-observability-only`.
- Security telemetry access boundary: `security-operations-only`.

## Cross-Stream Rule

- Cross-stream joins are denied by default.
- Exception contexts require documented ticket, least-privilege approval, time-bound access, and audit logs.

## Runtime Mapping

- `PayCal\\Domain\\TelemetryPolicy::describeStream()`
- `PayCal\\Domain\\TelemetryPolicy::canAccess()`
