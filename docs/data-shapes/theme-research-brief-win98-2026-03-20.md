# Theme Research Brief

**Theme**: `win98`
**Variant Pair**: `win98_light` / `win98_dark`
**Date**: `2026-03-20`
**Owner**: `GitHub Copilot`

## 1) Intent

1. Recreate Windows 98's cool teal desktop, neutral gray chrome, and blue caption-bar hierarchy in a way that still reads clearly inside PayCal.
2. Keep the recognizable CRT-era shell feel: flat desktop field, beveled gray surfaces, cobalt-to-sky title emphasis, and zero-radius geometry.

## 2) Fresh References (Required)

1. Reference A: `https://en.wikipedia.org/wiki/Windows_98` - confirms the shell character and explicitly notes two-color gradient title bars as a Windows 98 trait.
2. Reference B: `https://guidebookgallery.org/screenshots/win98` - screenshot gallery showing the canonical teal desktop, gray dialog chrome, and blue caption gradients across desktop, dialogs, and settings.
3. Reference C: `https://winworldpc.com/product/windows-98/98-second-edition` - release page and screenshots for Windows 98 SE, useful for verifying the broader shell mood and default window treatment.

## 3) Palette Extraction

1. Dominant background tones: saturated desktop teal in the light variant; deep oxidized teal for the dark reinterpretation.
2. Surface/chrome tones: light gray and warm gray-beige window bodies in the original; charcoal graphite in the dark variant.
3. Text tones: near-black on gray chrome in light mode; pale gray-white on dark chrome in dark mode.
4. Accent/brand tones: deep navy title bars, brightened blue highlight edge, and restrained cyan-blue selection tints.
5. Intent tones: classic dark red for danger, low-saturation green for success, amber-orange for warning, mid-blue for info.

## 4) Foundation Token Proposal

1. Light foundation centers on `#008080`, `#C0C0C0`, `#D4D0C8`, `#000080`, `#1084D0`, `#808080`, `#000000`, `#FFFFFF`.
2. Dark foundation centers on `#0A3F45`, `#2D2D2D`, `#1F1F1F`, `#0A2B6A`, `#1A63B5`, `#5F5F5F`, `#E6E6E6`, `#111111`.
3. Both variants keep square borders, shallow shadows, and strong caption contrast.

## 5) Semantic -> Component Mapping

1. Buttons use surface gray/graphite as the default and caption blue as the primary state.
2. Panels and dialogs stay on neutral chrome surfaces, with caption bars mapped to `--panel-head-*`.
3. Calendar states derive from surface plus softened blue mixes for hover, today, and selected cells.
4. Context menus and nav trays remain on the same chrome surface family to preserve shell consistency.

## 6) Accessibility Checks

1. Light body text uses dark text on gray chrome and inverse white on navy primary surfaces.
2. Dark body text uses pale text on teal/graphite surfaces and white on the primary caption blue.
3. Focus rings use brighter blue than the primary fill in both variants to stay visible on chrome and desktop fields.
4. Calendar selected and hover states keep text readable by tinting the base cell rather than replacing it entirely.

## 7) Migration Notes

1. Legacy aliases retained: `--body-*`, `--panel-*`, `--btn-*`, `--cal-*`, `--context-menu-*`, `--nav-menu-*`, `--system-tray-back`, `--warning`.
2. No legacy aliases removed in this pass.
3. Title-bar gradients remain in the legacy alias layer because shared consumers still read `--panel-head-back` directly.

## 8) Evidence

1. Fresh web references reviewed on `2026-03-20` before conversion.
2. Contract validation and PHP lint run after conversion.

## 9) Sign-off

1. Design/visual sign-off: `Pending`.
2. Engineering sign-off: `GitHub Copilot / 2026-03-20`.
3. Accessibility sign-off: `Pending`.