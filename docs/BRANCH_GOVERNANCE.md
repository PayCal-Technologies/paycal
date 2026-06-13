# Branch governance (PayCal private + public)

PayCal treats **GitHub CI as authoritative** and local hooks as fast feedback. Hooks are helpful but bypassable (`--no-verify`); branch protection and required checks are the real boundary.

## Target controls

| Control | Private repo | Public repo |
| --- | --- | --- |
| Protected `main` | Required | Required |
| Required status checks | PHPUnit, PHPStan, security gates, JS, README/policy | Same core set |
| Pull request before merge | Recommended (even solo) | Recommended |
| Review for workflow/hook edits | Required via CODEOWNERS | Required via CODEOWNERS |
| Direct push to public `main` | N/A | Block |
| Emergency bypass | Documented, rare, audited | Same |

Configure these in GitHub repository settings (not in this repo). See `.github/required-checks.yml` for the intended check names.

## Local hooks (non-authoritative)

| Stage | Gate |
| --- | --- |
| pre-commit | Secrets scan, Composer state, README/VERSION check, staged PHP lint + PHPStan, docblock checks (no auto-mutate), quick tests |
| post-commit | Record verified HEAD stamp after successful pre-commit |
| pre-push | Public promotion allowlist (public remote only), README/VERSION check, policy meta-checks, full PHPStan, quick tests (skipped when HEAD matches verified stamp) |

Explicit fixers (run manually, review diff, stage intentionally):

```bash
scripts/paycal fix:readme-version
scripts/paycal fix:docblocks
```

## Public promotion

1. Land work on private `main` (or a promotion branch).
2. Build a patch from allowlisted paths only (`scripts/public-promotion-allowlist.txt`).
3. Run `scripts/paycal checks:public-promotion-scope main...HEAD`.
4. Apply patch in the public repo, open PR, wait for green CI, merge.
5. **Never promote while public `main` is red.**

## Emergency bypass

Use only for production incidents or broken gates with a tracked follow-up issue. Log: who, when, why, and the remediation PR.
