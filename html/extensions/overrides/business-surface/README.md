# Business Surface Override Package (Public)

This package enables a minimal `/business/*` workspace shell for public deployments.

## Purpose

PayCal Core defaults the full business workspace to private-only implementations.
Public environments opt in through this override package to expose IA preview pages.

## Activation

1. Keep `manifest.php` present with `enabled` set to `true`.
2. Ensure capability `business.surface.enabled` is `true`.
3. Declare `business.page.paths` for allowed `/business/*` routes.

## Notes

1. Pages under `html/business/` are intentionally minimal placeholders.
2. Each page includes the public extension disclaimer footer partial.
3. No runtime hooks are required for this package.
