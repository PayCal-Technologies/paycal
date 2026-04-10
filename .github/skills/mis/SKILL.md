---
name: mis
description: 'Run the PayCal release hygiene workflow. Use for "make it so", release sweep, git cleanup sweep, version bump, changelog sync, source-of-truth refresh, full validation, and release commit/tag work.'
argument-hint: 'Target version, release summary, tag intent, and any scope notes'
user-invocable: true
disable-model-invocation: true
---

# Make It So

Use this skill when the user wants a full PayCal release hygiene pass, including release metadata updates, validation, documentation refresh, and clean git history.

## Required Outcomes

- Keep release metadata aligned across `VERSION`, `README.md`, `docs/CHANGELOG.md`, and `docs/v1.changelog.md`.
- Refresh internal release notes in `ai-notes/PAYCAL_SOURCE_OF_TRUTH.md`.
- Update website-facing documentation pages when the release changes documented behavior, accessibility commitments, transparency statements, or operational guidance.
- Derive live counts from the current tree and current test suite. Do not copy stale README or changelog numbers forward.
- Run the full validation stack and fix blocking failures before committing.
- Create logical git commits with detailed messages. Do not dump unrelated release, docs, and repair work into one generic commit.
- Create or update an annotated tag when the user asked for a release cut.

## Operating Rules

- Default workspace and repo root: `<REPO_ROOT>`.
- Do not use Python for any step in this release workflow; use PHP, shell, Node, or Rust alternatives.
- When checking git status or diffs, always use the explicit repo path so multi-worktree output stays accurate.
- Do not use `--no-verify` to bypass hooks.
- If version, tag, or release summary is ambiguous, ask one concise clarifying question before editing.
- If unrelated files are already staged, use `git commit --only <paths>` so unrelated staged work is not included.
- Fix validation failures at the root cause. Do not silence or skip failing checks.
- For detail-oriented operator workflows, include explicit assumptions, exact file references, and a concise verification checklist in final reports.

## Procedure

1. Inspect current release state.
   - Read `VERSION`, `README.md`, `docs/CHANGELOG.md`, `docs/v1.changelog.md`, `ai-notes/PAYCAL_SOURCE_OF_TRUTH.md`, `composer.json`, `phpstan.neon`, `phpunit.xml`, and `package.json`.
   - Check `git -C <REPO_ROOT> status --short --branch` and recent tags before deciding the next version/tag.

2. Recompute live inventory.
   - Reconfirm PHPUnit suite layout from `phpunit.xml`.
   - Recompute test counts from the live suite or filesystem when README or source-of-truth numbers need refresh.
   - Reconfirm active toolchain notes from live config files instead of older docs.

3. Update release metadata.
   - Update `VERSION`.
   - Update `README.md` release notes, current release references, test inventory, and any toolchain badges or summaries affected by the release.
   - Update `docs/CHANGELOG.md`.
   - Update `docs/v1.changelog.md`.

4. Update supporting documentation.
   - Update `ai-notes/PAYCAL_SOURCE_OF_TRUTH.md`.
   - Review website documentation areas such as transparency, accessibility, help, or policy pages when the release changes user-visible behavior or public commitments.
   - Keep public wording in sync with contract tests when exact phrases are required.

5. Run the full validation stack.
   - Full PHPUnit suite: `cd <REPO_ROOT> && ./vendor/bin/phpunit -c phpunit.xml`
   - PHPStan Level 9: `cd <REPO_ROOT> && composer run phpstan:strict`
   - npm quality gate: this repo does not currently define `npm run check`, so use `cd <REPO_ROOT> && npm run test:js` as the active npm check until package scripts change.
   - WCAG and accessibility stack: `cd <REPO_ROOT> && npm run test:a11y:all`
   - Health verification: prefer checking the live `/api/health` endpoint when the app is reachable; if not reachable in the current environment, state that clearly and verify the health contract from `html/src/Controllers/HealthController.php` plus any related public documentation.

6. Resolve failures and re-run affected checks.
   - Do not stop after the first red result.
   - Re-run the failed gate after each relevant fix until the release sweep is green or genuinely blocked by environment constraints.

7. Commit in logical slices.
   - Separate release metadata edits, supporting documentation refreshes, and validation-driven code fixes when they are distinct changes.
   - Write detailed commit messages that describe what changed and why.
   - Suggested commit styles:
     - `release: cut vX.YYY.ZZZ metadata and changelog sync`
     - `docs: refresh source of truth and public release documentation`
     - `fix: resolve validation regressions blocking vX.YYY.ZZZ`
   - Create an annotated tag for the final release commit when requested.

8. Final report.
   - Summarize what changed.
   - Report validation results and any environment-limited checks.
   - Call out the exact commit SHAs and tag created, if any.