# Theme Research Brief Template

**Theme**: `<theme_name>`
**Variant Pair**: `<theme_light>` / `<theme_dark>`
**Date**: `<YYYY-MM-DD>`
**Owner**: `<name>`

## 1) Intent

1. Core visual direction in one sentence.
2. What must stay recognizable from the legacy implementation.

## 2) Fresh References (Required)

1. Reference A: `<url or source note>`
2. Reference B: `<url or source note>`
3. Optional C: `<url or source note>`
4. Optional D: `<url or source note>`

## 3) Palette Extraction

1. Dominant background tones.
2. Surface/chrome tones.
3. Text tones (primary/muted/inverse).
4. Accent/brand tones.
5. Intent tones (success, warning, danger, info).

## 4) Foundation Token Proposal

1. `--color-bg`, `--color-bg-soft`, `--color-bg-elevated`, `--color-bg-overlay`
2. `--color-surface`, `--color-surface-muted`, `--color-surface-strong`
3. `--color-border`, `--color-border-soft`, `--color-border-strong`
4. `--color-text`, `--color-text-muted`, `--color-text-inverse`, `--color-text-disabled`
5. `--color-primary`, `--color-primary-hover`, `--color-primary-active`, `--color-primary-soft`, `--color-on-primary`
6. `--color-success`, `--color-warning`, `--color-danger`, `--color-info`
7. `--color-hover`, `--color-active`, `--color-focus-ring`, `--color-selection`, `--color-highlight`, `--color-disabled-bg`
8. `--elevation-1-bg`, `--elevation-2-bg`, `--elevation-3-bg`, `--overlay-backdrop`
9. `--shadow-sm`, `--shadow-md`, `--shadow-lg`

## 5) Semantic -> Component Mapping

1. Button mappings (`--button-*`).
2. Panel/card/dialog mappings.
3. Calendar mappings (`--calendar-*`).
4. Table/list state mappings.

## 6) Accessibility Checks

1. Body text on background passes target contrast.
2. Muted text remains readable.
3. Primary button text contrast is valid in normal/hover/active.
4. Focus ring is clearly visible on both light and dark surfaces.
5. Calendar day, selected day, and event text are readable.

## 7) Migration Notes

1. Legacy tokens retained temporarily (list).
2. Legacy tokens removed in this pass (list).
3. Known tradeoffs and follow-ups.

## 8) Evidence

1. Route screenshots: `calendar`, `settings`, `auth`, `earnings`, `admin`.
2. Any before/after diffs or QA notes.

## 9) Sign-off

1. Design/visual sign-off: `<name/date>`
2. Engineering sign-off: `<name/date>`
3. Accessibility sign-off: `<name/date>`
