# Admin Surface Override Package (Private)

This package controls whether the `/admin` surface is enabled.

## Purpose

PayCal Core basic defaults Admin to disabled. Private environments can opt in by enabling this override package.

## Activation

1. Copy `manifest.php.example` to `manifest.php`.
2. Keep `id` as `admin-surface`.
3. Ensure `enabled` is `true`.
4. Ensure capability `admin.surface.enabled` is `true`.
5. Declare `admin.page.paths` for the allowed `/admin/*` routes in your environment.

## Notes

1. This package currently only declares capability metadata (`admin.surface.enabled`, `admin.page.paths`, and `admin.nav.links`).
2. No runtime hooks are required for this package.
