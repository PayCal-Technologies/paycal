# Developer Philosophy

This document explains the engineering philosophy behind PayCal's backend and frontend implementation choices.

## Core Principles

1. Security and privacy before convenience.
2. Predictable behavior over clever behavior.
3. Explicit contracts over hidden coupling.
4. Accessibility and CSP compliance are non-negotiable.
5. Core remains usable without optional extension runtime.

## Architectural Boundaries

### Controllers are transport adapters

- Controllers validate request context, call domain services, and shape HTTP responses.
- Controllers should not become the source of truth for policy or calculations.

### Domain services own policy and invariants

- Domain classes own validation rules, role/scope checks, and lifecycle policy.
- Repositories own persistence mapping and normalization.

### Bridges isolate optional runtime features

- Bridge classes provide safe defaults when extension runtime is unavailable.
- Optional capability and hook integration must degrade gracefully.

## Security Philosophy

- Treat authentication/session code as security-critical infrastructure.
- Prefer deterministic cryptographic key derivation and stable envelope formats.
- Enforce least privilege for admin and privileged correlation paths.
- Changes in security-sensitive areas must preserve auditability and explicit denial paths.

## Data and Money Integrity

- Monetary and payroll calculations should be deterministic and integer-centric.
- Shared schema helpers define canonical payload shape and validation constraints.
- Configuration limits should be centrally defined and typed.

## Frontend and CSP Philosophy

- No inline scripts, no inline styles, and no inline event handlers.
- Rendering helpers should remain CSP-safe by design.
- UI endpoints and data-grid payloads should preserve stable contract shape.

## Configuration Philosophy

- Environment/bootstrap configuration is centralized and typed.
- New environment keys require typed accessors and clear defaults.
- System limits and defaults are treated as compatibility-sensitive contracts.

## Documentation and Maintainability

- File-level docs should explain purpose, responsibilities, and boundaries.
- Public/domain-level behavior changes should update docs and changelog context.
- Large refactors should preserve compatibility unless migration is explicitly introduced.
