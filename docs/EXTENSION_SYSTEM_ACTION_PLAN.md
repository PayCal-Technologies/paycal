# PayCal Core/Public + Extensions Action Plan

Date: 2026-04-03
Owner: Platform Engineering

## Goals

1. Keep Core files in place with no relocations.
2. Provide basic public extensions under `html/extensions/basic`.
3. Provide private override discovery under `html/extensions/overrides`.
4. Enforce precedence: override wins; basic is never loaded when override exists.
5. Provide manifest metadata usable at runtime: id, version, capabilities, hooks, compatibility.
6. Introduce a generalized hook bus for extension fanout.

## Core Boundary Rules

1. `/admin` is a temporary Core surface and must be treated as a private-extension migration target.
2. PayCal Core basic must not retain long-term ownership of `/admin` pages, `/admin` routing, or the Admin navbar/popover entry once the private extension is ready.
3. Future admin work should prefer extension seams and packaging decisions that make full removal of `/admin` from Core straightforward.

## Deliverables (Implemented)

- Runtime + hook bus: `html/extensions/runtime.php`
- Extension bootstrap entrypoint: `html/extensions/bootstrap.php`
- Admin surface seam + default-off gate: `html/src/Domain/AdminSurface.php`
- Admin page path ownership seam: `AdminSurface::pagePathIsEnabled(...)` driven by `admin.page.paths`
- Admin capability evaluator support: `ExtensionRuntime::capabilityEnabled(...)`
- Admin API registration gate: `html/api/index.php` conditionally registers admin controllers when Admin surface is enabled
- API controller ownership seam: `html/src/Domain/ApiControllerRegistry.php`
- Organization signal seam migrated to HookBus-only dispatch: `html/src/Domain/OrganizationSignalHooks.php`
- Basic extension package:
  - `html/extensions/basic/organization-signals/manifest.php`
  - `html/extensions/basic/organization-signals/bootstrap.php`
  - `html/extensions/basic/organization-signals/hooks.php`
- Override guidance and contract:
  - `html/extensions/overrides/README.md`
  - `html/extensions/overrides/admin-surface/manifest.php.example`

## Runtime Contracts

1. Discovery
- Scans `html/extensions/basic/*` and `html/extensions/overrides/*` for `manifest.php`.

2. Selection
- Grouped by extension id.
- If any override exists for id `X`, basic `X` is excluded.
- Selected extension must have `enabled=true`.

3. Loading
- Selected extension bootstrap is `require_once` loaded.
- Hook listeners register through `PayCal\Domain\Extensions\HookBus`.

4. Metadata
- Active manifests are available via:
  - `PayCal\Domain\Extensions\ExtensionRuntime::activeManifests()`
- Capability manifest is available via:
  - `PayCal\Domain\Extensions\ExtensionRuntime::capabilityManifest()`
- Global mirrors:
  - `$GLOBALS['PAYCAL_EXTENSION_MANIFESTS']`
  - `$GLOBALS['PAYCAL_EXTENSION_CAPABILITIES']`

## Hook System

- Register:
  - `HookBus::register(string $hookName, callable $callback, int $priority = 100, string $source = 'unknown')`
- Dispatch:
  - `HookBus::dispatch(string $hookName, array $payload = [])`
- Listener ordering:
  - Lower priority value runs first.

## Phased Next Steps

1. Migrate ad-hoc extension seams to HookBus-dispatched events.
2. Add contract tests for:
- manifest validation,
- override precedence,
- hook dispatch ordering,
- capability manifest shape.
3. Add admin diagnostics view for active extension manifests and hooks.
4. Build private overrides repo with matching extension ids and advanced capabilities.
5. Extract `/admin` into a private extension and remove the Admin navigation/menu/page surface from PayCal Core basic.
6. In private environments, enable `admin-surface` override package with capabilities `admin.surface.enabled=true` and explicit `admin.page.paths`.
