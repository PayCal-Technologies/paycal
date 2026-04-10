# Theme Token Dictionary v1

**Version**: v1
**Date**: 2026-03-20
**Status**: Active (migration bridge period)

## 1) Layer Model

1. Foundation: raw palette/elevation values scoped per theme.
2. Semantic: role-based tokens consumed by multiple components.
3. Component: API tokens consumed directly by component CSS.
4. Legacy: temporary aliases retained for migration compatibility.

## 2) Required Semantic Tokens

1. `--color-bg`, `--color-bg-soft`, `--color-bg-elevated`, `--color-bg-overlay`
2. `--color-surface`, `--color-surface-muted`, `--color-surface-strong`
3. `--color-border`, `--color-border-soft`, `--color-border-strong`
4. `--color-text`, `--color-text-muted`, `--color-text-inverse`, `--color-text-disabled`
5. `--color-primary`, `--color-primary-hover`, `--color-primary-active`, `--color-primary-soft`, `--color-on-primary`
6. `--color-success`, `--color-warning`, `--color-danger`, `--color-info`
7. `--color-hover`, `--color-active`, `--color-focus-ring`, `--color-selection`, `--color-highlight`, `--color-disabled-bg`
8. `--elevation-1-bg`, `--elevation-2-bg`, `--elevation-3-bg`, `--overlay-backdrop`
9. `--shadow-sm`, `--shadow-md`, `--shadow-lg`

## 3) Required Component Tokens

1. Button: `--button-bg`, `--button-bg-hover`, `--button-bg-active`, `--button-text`, `--button-border`, `--button-border-active`
2. Button variants: `--button-primary-bg`, `--button-primary-text`, `--button-secondary-bg`, `--button-secondary-text`, `--button-danger-text`
3. Panel: `--panel-bg`, `--panel-text`, `--panel-border`, `--panel-head-bg`, `--panel-head-text`
4. Dialog: `--dialog-bg`, `--dialog-text`, `--dialog-border`, `--dialog-shadow`, `--dialog-overlay`
5. Calendar: `--calendar-bg`, `--calendar-border`, `--calendar-day-bg`, `--calendar-day-hover`, `--calendar-day-today`, `--calendar-day-selected`, `--calendar-event-bg`, `--calendar-event-text`, `--calendar-range-bg`

## 4) Migration Bridge Rules

1. Legacy tokens remain readable during migration but should not be used by new code.
2. New or edited components must consume component tokens only.
3. Theme files may define legacy aliases, but values should derive from semantic/component tokens.
4. Once a component family reaches zero legacy reads, remove its legacy aliases in a controlled PR.

## 5) Initial Legacy Alias Mapping

1. `--body-back -> --color-bg`
2. `--body-fore -> --color-text`
3. `--header-back -> --color-surface-strong`
4. `--header-fore -> --color-text`
5. `--footer-back -> --color-surface-strong`
6. `--footer-fore -> --color-text-muted`
7. `--panel-back -> --panel-bg`
8. `--panel-fore -> --panel-text`
9. `--panel-border-color -> --panel-border`
10. `--btn-back -> --button-bg`
11. `--btn-fore -> --button-text`
12. `--btn-border-colors -> --button-border`
13. `--btn-border-colors-active -> --button-border-active`
14. `--btn-primary-back -> --button-primary-bg`
15. `--btn-primary-fore -> --button-primary-text`
16. `--btn-secondary-back -> --button-secondary-bg`
17. `--btn-secondary-fore -> --button-secondary-text`
18. `--warning -> --color-danger` (temporary; split warning/danger where needed)
19. `--cal-day-back -> --calendar-day-bg`
20. `--cal-day-hover-back -> --calendar-day-hover`
21. `--cal-day-border -> --calendar-border`
