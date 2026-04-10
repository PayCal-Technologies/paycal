# PayCal Extensions

This directory contains extension packages loaded by the Core runtime.

## Purpose

PayCal uses an extension-first seam model:

- Core keeps canonical domain/controller behavior and contracts.
- Extension packages provide capability toggles, hook listeners, and
	optional feature rendering overrides.
- Core consumes extension data through runtime/bridge boundaries so Core logic
	remains testable without hard-coupling to specific extension packages.

## Layout

- `basic/` public, baseline extension implementations
- `overrides/` private override implementations with precedence over `basic`

## Core vs Extensions

- **PayCal Core** contains shared logic, security boundaries, and API contracts.
- **Basic extensions** in this repository provide default extension behavior.
- **Third parties** can implement their own extension packages while keeping
	Core intact.
- **Canonical paycal.app** can run private extension variants to differentiate
	product behavior without forking core architecture.

## Loading Model

Core initializes extension runtime from `html/extensions/bootstrap.php`.

Runtime behavior:

1. Discover extension manifests in both roots.
2. Group by extension id.
3. If an override exists for an id, the basic extension with that id is skipped.
4. Load selected extension bootstrap files.
5. Expose active manifest metadata and capabilities.

## Discovery and Selection Rules

- Discovery roots are `basic/` and `overrides/`.
- Manifest id is the grouping key.
- If a valid enabled override manifest exists for an id, the matching basic
	package is skipped.
- Disabled packages remain discoverable in diagnostics but are not activated.

## Hook and Capability Contracts

- `hooks` is declarative manifest metadata and should reflect runtime listener
	registrations performed by package bootstrap files.
- `capabilities` is runtime-readable metadata used by Core bridge accessors.
- Hook listeners should be deterministic and side-effect scoped.
- Hook priorities use lower numbers for earlier execution.

## Required Manifest Fields

- `id` (string)
- `name` (string)
- `version` (string)
- `enabled` (bool)
- `capabilities` (array)
- `hooks` (array of hook names)
- `bootstrap` (string path relative to extension folder)

## Third-Party Authoring Guide

1. Create `html/extensions/overrides/<your-extension-id>/manifest.php`.
2. Keep `id` stable across versions to preserve precedence behavior.
3. Define `bootstrap` and register listeners through `HookBus` only from
	 bootstrap.
4. Keep payload handling defensive (`is_array`, `is_scalar`, safe defaults).
5. Prefer additive extension behavior and avoid changing Core internals.

## Operational Notes

- Runtime publishes active manifests and capability manifests through globals
	for compatibility with legacy integration points.
- Discovery/selection failures are logged but do not hard-crash app startup.
- Extension package directories should include local README notes describing
	package scope, activation steps, and owner expectations.
