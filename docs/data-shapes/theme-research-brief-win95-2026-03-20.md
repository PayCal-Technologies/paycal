# Theme Research Brief

**Theme**: `win95`
**Variant Pair**: `win95_light` / `win95_dark`
**Date**: `2026-03-20`
**Owner**: `Copilot`

## 1) Intent

1. Preserve classic Windows 95 visual language: neutral gray chrome, hard edges, deep blue selection/headlines.
2. Keep retro authenticity while meeting modern semantic token and accessibility requirements.

## 2) Fresh References (Required)

1. https://en.wikipedia.org/wiki/Windows_95
2. https://win98icons.alexmeub.com/
3. Source note: Existing in-repo `win95_light` and `win95_dark` token values reviewed for identity preservation.
4. Source note: Archive screenshots of Win95 shell/dialog chrome and title bars used to verify palette intent.

## 3) Palette Extraction

1. Dominant backgrounds: mid-gray (`#C0C0C0`) for light, charcoal/black for dark reinterpretation.
2. Surface/chrome: flat neutrals with strong border separation.
3. Text: black/near-black on light, white on dark.
4. Accent: classic navy (`#000080`) and brighter blue for focus/primary interactions.
5. Intent: red danger retained for destructive actions.

## 4) Foundation Token Proposal

1. Add `--foundation-*` for gray, navy, dark phosphor, and border shades.
2. Map to semantic `--color-*` tokens for bg/surface/text/border/primary/intent.

## 5) Semantic -> Component Mapping

1. `--button-*` use beveled-neutral mapping plus blue primary variant.
2. `--panel-*` and `--dialog-*` track chrome and titlebar semantics.
3. `--calendar-*` keep high-contrast day borders and hover clarity.

## 6) Accessibility Checks

1. Light mode text on chrome remains high contrast.
2. Dark mode text/background remains readable at day-cell scale.
3. Focus ring token explicitly present and visible.
4. Primary button text remains legible on blue backgrounds.

## 7) Migration Notes

1. Legacy aliases retained temporarily to avoid shared CSS regressions.
2. Existing odd value in legacy token (`btn-secondary-back`) normalized via semantic mapping.
3. Future pass should remove legacy aliases once consumers are migrated.

## 8) Evidence

1. Contract checker passes in converted mode after win95 migration.
2. PHP lint passes for both win95 theme files.

## 9) Sign-off

1. Design/visual sign-off: `pending`
2. Engineering sign-off: `2026-03-20`
3. Accessibility sign-off: `pending`
