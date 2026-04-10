# Theme Research Brief

**Theme**: `paycal`
**Variant Pair**: `paycal_light` / `paycal_dark`
**Date**: `2026-03-20`
**Owner**: `Copilot`

## 1) Intent

1. Preserve PayCal's cyan-forward identity with slate neutrals and readable enterprise contrast.
2. Keep recognizable current accent behavior while introducing semantic/component token layering.

## 2) Fresh References (Required)

1. Source note: Existing in-repo `paycal_dark` and `paycal_light` palettes used as baseline identity references.
2. Source note: Current production UI screenshots of calendar/settings routes reviewed for parity.
3. https://material.io/design/color/the-color-system.html
4. https://www.w3.org/WAI/WCAG21/quickref/

## 3) Palette Extraction

1. Dominant backgrounds: deep slate (`dark`) and paper white (`light`).
2. Surface/chrome: steel/smoke neutrals with subtle elevation separation.
3. Text: high-contrast primary, muted secondary for metadata.
4. Accent: cyan family (`primary`, hover, active).
5. Intent: maintain distinct danger red; keep warning/info/success semantically separate.

## 4) Foundation Token Proposal

1. Implemented in `html/css/paycal_dark/index.php` and `html/css/paycal_light/index.php` using `--foundation-*` + `--color-*` tokens.
2. Includes elevation, overlay, focus, and shadow roles.

## 5) Semantic -> Component Mapping

1. Buttons map through `--button-*` tokens.
2. Panel/dialog map through `--panel-*` and `--dialog-*` tokens.
3. Calendar states map through `--calendar-*` tokens.
4. Legacy aliases kept as bridge to existing shared CSS.

## 6) Accessibility Checks

1. Body text/background contrast preserved from existing high-contrast baseline.
2. Primary actions maintain readable text with `--color-on-primary`.
3. Focus ring token explicitly defined (`--color-focus-ring`).
4. Calendar hover/selected states retain readable foregrounds.

## 7) Migration Notes

1. Legacy aliases retained: `--body-*`, `--panel-*`, `--btn-*`, `--cal-day-*`, etc.
2. Shared button styles in `common/index.php` now consume component tokens.
3. Next pass should move calendar and panel consumers off legacy aliases.

## 8) Evidence

1. Token contract checker pilot passes for paycal files.
2. PHP lint passes on modified CSS-PHP files.

## 9) Sign-off

1. Design/visual sign-off: `pending`
2. Engineering sign-off: `2026-03-20`
3. Accessibility sign-off: `pending`
