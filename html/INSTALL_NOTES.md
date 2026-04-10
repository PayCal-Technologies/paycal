# Install Notes

Date: 2026-04-03
Owner: Platform Engineering

## Purpose

This file documents the practical differences between:

1. A fresh public PayCal Core install (no private extensions)
2. An enhanced install with private extensions enabled

## Fresh Public Repo (Core Only)

Expected behavior in a fresh clone/install:

1. Core routes and pages load as normal for public and standard authenticated features.
2. Extension runtime can initialize, but no private extension features are active unless extension packages are present and enabled.
3. Admin surface is disabled by default in Core.
4. Admin navigation is not shown by default.
5. Direct /admin access is denied/redirected by the central admin surface gate.
6. Admin API controllers are not registered unless the admin surface capability is enabled.

## Enhanced Install (With Extensions)

Expected behavior when private extensions are present and enabled:

1. Runtime discovers extension manifests from extension roots.
2. Capabilities and hooks are registered from active extensions.
3. Override precedence applies where override and basic share the same extension id.
4. Optional surfaces (including admin) can be re-enabled by extension capability flags.

## Admin Notes

Current policy:

1. /admin is treated as a private extension surface, not a Core-basic feature.
2. Core keeps the seam and gating logic, but does not expose Admin by default.
3. We may consider a basic Admin extension later.
4. For now, we are not offering any basic Admin extension.

Capability keys:

1. `admin.surface.enabled`:
   - `false` or missing: Admin surface remains disabled in Core basic.
   - `true`: Admin surface is enabled (navbar + /admin access for authorized admins).
2. `admin.nav.links`:
   - Missing or empty: Core does not render the Admin popover menu.
   - Present with valid items: extension owns Admin popover link composition.
3. `admin.page.paths`:
   - Missing or empty: page access falls back to `admin.nav.links` match prefixes.
   - Present: extension explicitly owns which `/admin/*` page paths are reachable.

Implementation notes:

1. Admin availability is controlled through the admin surface seam and extension capability values.
2. Fresh installs should be tested to confirm no Admin navbar entry and no direct /admin access.
3. Private extension installs should explicitly set the Admin capability to enable the Admin surface.

Private extension template:

1. Core ships an override template at `html/extensions/overrides/admin-surface/`.
2. In a private extension repo/overlay, copy `manifest.php.example` to `manifest.php` and keep `enabled => true`.
3. Ensure capability `admin.surface.enabled` is set to `true`.
4. Ensure capability `admin.page.paths` is set with the allowed `/admin/*` routes.
5. Ensure capability `admin.nav.links` is set with the desired Admin link list.

## Verification Checklist

Use this checklist when validating installation mode:

1. Fresh clone/install:
   - Admin menu is hidden.
   - /admin redirects away.
2. Enhanced extension install:
   - Admin menu appears when extension capability enables it.
   - /admin routes are accessible to authorized users.

## Private Admin Surface Activation (Reference)

Use this for private environments only:

1. Create `html/extensions/overrides/admin-surface/manifest.php` from the example.
2. Keep extension id `admin-surface`.
3. Set:
   - `enabled => true`
   - `capabilities['admin.surface.enabled'] => true`
   - `capabilities['admin.page.paths'] => [...]` for allowed admin routes
4. Sync/redeploy and verify Admin visibility + route access.
