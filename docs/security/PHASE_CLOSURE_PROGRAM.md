# PayCal Security Hardening Roadmap - Phase Closure Program

Status: Active
Last Updated: 2026-03-23
Tags: security, phase-closure, architecture, privacy, correlation-broker, telemetry-governance

## Mission
PayCal is moving from control-by-discipline to control-by-architecture. Remaining work focuses on making privacy violations structurally difficult or impossible.

## North Star
- Correlation is centralized and enforced.
- Telemetry deanonymization is blocked at query layer.
- Admin and export surfaces are policy-gated by construction.
- Runtime decrypted data lifetime is minimized.

## Workstreams

### Workstream A (P0): Correlation Broker Architecture
Tags: workstream-A, P0, correlation, broker, policy-enforcement
Objective: centralize sensitive joins in a broker.
- A1: Correlation context model.
- A2: Correlation broker service and denial envelope.
- A3: Repository separation for metadata vs financial payload.
- A4: Integration enforcement tests.

Current progress:
- A1 started: `CorrelationContext` and `CorrelationDecision` introduced.
- A2 started: `CorrelationBroker` scaffold added with centralized evaluation and structured denial/success envelope behavior.
- A2 advanced: `AdminPageController` and `EarningsController` correlation decisions are now broker-evaluated.
- A2 advanced: `SitesController` and `CalendarController` correlation decisions are now broker-evaluated.
- A2 evidence rule added: `scripts/check-correlation-broker-enforcement.sh` fails when controllers directly reference `MetadataCorrelationPolicy`.
- A2 advanced: `EarningsController` now uses `CorrelationBroker::compose(...)` probe for structured denied envelope output.
- A2 advanced: `SitesController` and `CalendarController` now use `CorrelationBroker::compose(...)` probes for structured denied envelope output.
- Existing `MetadataCorrelationPolicy` remains active and deny-by-default.

### Workstream B (P0): Telemetry Query Governance
Tags: workstream-B, P0, telemetry, query-governance, stream-isolation
Objective: prevent analyst/tooling deanonymization.
- B1: Telemetry repository abstraction.
- B2: stream access token model.
- B3: cross-stream join guard.
- B4: privacy budget controls (optional advanced).

Current progress:
- Telemetry pseudonymization and retention policy exist.
- B1 started: `TelemetryRepository` introduced as query/write boundary abstraction with explicit stream authorization and join guards.
- B2 started: `TelemetryAccessToken` model introduced for role/stream-scoped access decisions.
- B3 started: cross-stream join guard introduced (`cross_stream_join_denied`) with unit coverage.
- `TelemetryController` now routes stream authorization and counter increment through `TelemetryRepository`.
- First query path migrated: `MetricsService::getTelemetryEvents()` now uses tokenized `TelemetryRepository` read guard instead of direct datastore reads.
- Second query path migrated: `EncryptionController::getTelemetrySummary()` now uses tokenized repository reads for security-stream counters.
- Endpoint deny proof added: cross-stream join attempts are rejected at controller boundary with explicit reason code.
- Stream-role matrix governance added: contract test now enforces stream access and join-guard invariants for product/security boundaries.
- Endpoint integration proof restored and expanded: encryption telemetry summary now has explicit allow (`security`) and deny (`product` cross-stream) integration assertions.
- Additional consumer migrated: `MetricsService::getSessionMetrics()` now uses tokenized `TelemetryRepository` reads for auth/session telemetry counters.
- Health endpoint integration proof added: admin `HealthController::getSessionHealth()` assertions now validate repository-backed session telemetry values.
- Additional consumer migrated: `MetricsService::getContactSupportMetrics()` now uses tokenized `TelemetryRepository` reads for contact telemetry snapshot access.
- Health snapshot integration proof added: admin `HealthController::getHealthSnapshot()` assertions validate contact telemetry metrics from repository-backed path.
- Additional consumer migrated: `MetricsService::getScraperDefenseMetrics()` now uses tokenized `TelemetryRepository` reads for scraper-defense telemetry aggregates.
- Health snapshot integration proof expanded: admin `HealthController::getHealthSnapshot()` assertions validate scraper-defense telemetry metrics from repository-backed path.

### Workstream C (P1): Admin Surface Policy Closure
Tags: workstream-C, P1, admin, policy-closure, integration-proof
Objective: prove and enforce policy for admin enrichment joins.
- C1: Admin integration tests for policy-deny omission behavior.
- C2: move admin join logic into correlation broker.
- C3: harden privileged role hierarchy and singleton high-privilege controls.

Current progress:
- Admin dashboard now applies policy-gated safe exclusion for session/credential enrichment.
- C1 started: integration proof now verifies denied context omits session/credential enrichment in admin dashboard output.
- C1 advanced: deny-safe integration proof now asserts fallback redaction attributes (`credential_count=0`, empty last-session hash, empty last-passkey timestamp) under denied context.
- C1 advanced: integration proof now also asserts session-derived timestamps are present only in allowlisted context and explicitly empty under denied context.
- C2 advanced: dashboard correlation context fallback is now deny-safe by default (no implicit allowlisted context when query parameter is absent).
- C3 started: `SUPERADMIN` auth level added above `ADMIN`, `User::isAdmin()` now treats admin checks as admin-or-higher, and `UserRepository` enforces singleton `SUPERADMIN` by demoting any previous holder on reassignment.
- C3 advanced: admin user-management path now enforces privileged role mutation guards so only `SUPERADMIN` can assign or modify `ADMIN`/`SUPERADMIN` auth levels.

### Workstream D (P2): Runtime Decrypted Data Lifecycle
Tags: workstream-D, P2, runtime, worker-memory, zeroization
Objective: reduce decrypted data lifetime in browser runtime.
- D1: view-scoped decrypt.
- D2: idle/tab-hidden/navigation zeroization.
- D3: DOM sensitivity guards.

Current progress:
- Prior hardening removed plaintext profile bootstrap dependencies.
- D2 started: calendar runtime now zeroizes in-memory crypto state on `visibilitychange` (hidden) and navigation lifecycle events (`pagehide`, `beforeunload`).
- D2 advanced: hidden-tab zeroization now uses delayed/cancelable scheduling to avoid transient visibility race regressions during immediate hide->show transitions.
- D2 proof added: Playwright smoke regression test now asserts transient hide->show does not clear decrypted profile marker and calendar month navigation remains intact.
- D2 proof expanded: Playwright smoke regression test now asserts explicit `PayCalCrypto.clear()` zeroizes decrypted marker state and month-grid navigation remains functional after clear.
- D2 proof expanded: Playwright smoke regression test now asserts delayed hidden-tab lifecycle does not clear profile marker when `PayCalCrypto.hasDek` is false, preserving locked-state UI consistency.
- D2 proof expanded: Playwright smoke regression test now asserts delayed hidden-tab lifecycle zeroizes unlocked DEK state after expiry.
- D2 proof expanded: Playwright smoke regression test now asserts deterministic re-unlock path recovers cleanly after lifecycle zeroization.
- D3 advanced: runtime zeroization now performs DOM sensitivity scrubbing (calendar payload attributes, cell content, modal/context-menu state, clipboard cache) before crypto-state reset.
- Validation snapshot refreshed: lifecycle smoke suite now passes `8/8` with supporting JS, PHPStan, and full backend test gates green.

## Evidence Requirements Per Cycle
Each cycle must record:
1. Files changed.
2. Tests added.
3. Static analysis result.
4. Integration result.
5. Status changes by workstream.
6. Remaining risk notes.

## Iteration Order
1. CorrelationBroker design and repository split.
2. Telemetry repository governance.
3. Admin integration enforcement proof.
4. Runtime lifecycle hardening.
