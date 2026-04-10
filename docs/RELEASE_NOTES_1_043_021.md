# PayCal Release Notes - v1.043.021

Release date: 2026-03-25

## Summary

This patch consolidates profile and billing UX behavior, aligns account-deletion safeguards across frontend and backend validation, and completes release-hygiene metadata synchronization for the active `1.043.x` line.

## Highlights

- Profile and billing UX consolidation:
  - Unified free vs premium presentation and normalized billing-state transitions.
  - Improved subscription status payload handling in settings/billing flows.
  - Removed a legacy `innerHTML` decode sink in organizations JS to satisfy enforced JS security sink policy.
  - Follow-up integration coverage for billing status payload behavior.

- Danger-zone safeguards alignment:
  - Account deletion now requires the explicit phrase `DELETE MY ACCOUNT` consistently.
  - Phrase enforcement is aligned across profile UI, JS validation, and backend controller checks.

- Organizations/profile language and interaction refinement:
  - Updated copy from personal-organization framing to profile-linked external organization wording.
  - Simplified Request Access and Delegate controls into side-by-side inline forms.
  - Removed obsolete premium/admin roadmap block from profile.

- Release hygiene and documentation:
  - `VERSION` updated to `1.043.021`.
  - Synced release metadata across README, both changelogs, and source-of-truth notes.
  - Added governance-discovery links on Transparency, Help, Policies, and About entry pages.

## Commit Scope (post v1.043.001)

- `2805e7f` Consolidate profile flows, billing integration, and unified toast handling
- `a7ea3a8` Fix PHPStan issues after profile and billing refactor
- `8aed2f7` JS readability sweep and strict network response contracts
- `5668174` Fix billing subscription status payload and test coverage
- `b96897d` Refine profile billing, danger zone, and org access copy

## Validation Snapshot

- Local pre-commit PHPStan Level 9 hook: PASS
- Push-time static verification: PASS
- Dev sync completed to commit `b96897d9ab674ba757fab8743786e6136f94c913`

## Related Docs

- `docs/CHANGELOG.md`
- `docs/v1.changelog.md`
- `ai-notes/PAYCAL_SOURCE_OF_TRUTH.md`
