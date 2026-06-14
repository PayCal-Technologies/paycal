# Branch governance (PayCal private + public)

PayCal uses a **local-authoritative CI model**. Mandatory quality gates run on your machine via hooks and `scripts/paycal checks:*` before commit, push, and promotion.

GitHub is **passive remote storage** (private source repo + public mirror). GitHub Actions may run as optional verification evidence; they are not required for normal development.

## Local-Authoritative CI Model

```text
Authority:     Local machine + scripts/paycal + hooks
Storage:       GitHub private repo, GitHub public repo
Optional:      GitHub Actions workflows (manual / informational)
Public safety: Signed commits, no force-push, no branch deletion (GitHub)
Private safety: Local hooks, policy-meta, test boundaries, public-health check
```

PayCal does **not** depend on GitHub-hosted CI as the source of truth. GitHub must not block development because of queued Actions, billing limits, or paid-plan branch-protection gaps on private repos.

### Mandatory local gates

```bash
scripts/paycal checks:policy-meta
scripts/paycal checks:test-boundaries
scripts/paycal checks:public-health          # from private, before promotion
composer run test:quick
composer run test:compliance                 # private: quick + soc2
# PHPStan L9 — pre-push and checks:public-health
# pre-commit + pre-push hooks
```

See `docs/PUBLIC_PROMOTION.md` for the promotion checklist.

## Target controls

| Control | Private repo | Public repo |
| --- | --- | --- |
| CI authority | **Local hooks + paycal checks** | **Local hooks + paycal checks** |
| GitHub branch protection | **Not used** (local-authoritative by design) | Safety only: signed commits, no force-push, no deletion |
| GitHub Actions | Optional verification | Optional verification |
| Review for workflow/hook edits | CODEOWNERS | CODEOWNERS |
| Promotion allowlist | Required before public push | N/A |

Optional workflow names are listed in `.github/optional-ci-checks.yml` for manual `gh workflow run` — they are **not** branch-enforced.

## Local hooks

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

## Test repo boundaries

See `html/tests/README.md`. Groups: `unit` (shared quick), `soc2` (private compliance), `private-moat` (public excluded).

`scripts/paycal checks:test-boundaries` validates grouping via policy-meta.

## Public GitHub safety (not CI authority)

Low-friction settings on public `main` only:

```bash
scripts/paycal ops:github-branch-protection public
```

Applies signed commits, linear history, blocks force-push and branch deletion. **Does not** require GitHub Actions checks.

## Private repo and GitHub Team

Private repo enforcement is **local-authoritative by design**. GitHub private branch protection is intentionally not used — it requires paid GitHub plan features and duplicates local gates.

## Emergency bypass

Use only for production incidents with a tracked follow-up issue. Log: who, when, why, and the remediation PR. Prefer fixing local gates over bypassing hooks.
