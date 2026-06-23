# PayCal CI/CD — Executive Summary

**Governance model (current):** Local-authoritative CI · GitHub as passive remote storage
**Repos:** `PayCal-Technologies/paycal-private` · `PayCal-Technologies/paycal`

---

## Principle

```text
Local CI is the gate.
GitHub is the cabinet.
Signed commits prove identity on public main.
Promotion allowlists protect the moat.
```

PayCal does **not** pay GitHub to re-run checks already enforced locally. GitHub Actions are **optional verification workflows** — not required branch-protection checks.

---

## What We Built (still in force)

| Area | Implementation |
|------|----------------|
| Check-only hooks | No auto-mutate; `fix:*` commands |
| Allowlist promotion | `public-promotion-allowlist.txt` + scope guard |
| Policy meta | `checks:policy-meta` (hooks + boundary checker) |
| Test stratification | `soc2` / `private-moat` groups; public `phpunit.public.xml` |
| Public test health | 1246 quick tests green |
| Private compliance | `test:soc2` (40 tests) separate from quick |
| Cross-repo check | `checks:public-health` from private |
| Release ledger | `checks:ledger-private` + `release:candidate` records SHA in private `paycal-ledgers` |
| Optional GHA workflows | policy-meta, phpunit, repo-health, soc2-compliance, etc. |

---

## Local Authority Checklist (mandatory)

```bash
scripts/paycal checks:policy-meta
scripts/paycal checks:test-boundaries
scripts/paycal checks:ledger-private
scripts/paycal checks:public-health      # before promotion
scripts/paycal release:candidate         # after local gates pass
composer run test:quick
composer run test:compliance             # private
# PHPStan L9 — pre-push / public-health
# pre-commit + pre-push hooks
```

---

## GitHub Role (reframed)

| Function | Role |
|----------|------|
| Git remote | **Yes** — storage and mirror |
| Branch protection (private) | **No** — local-authoritative; Free plan + intentional |
| Branch protection (public) | **Safety only** — signed commits, no force-push, no deletion, linear history |
| Required Actions checks | **No** — removed; must not stall `main` |
| Actions workflows | **Optional** — manual `gh workflow run` for evidence |

Manifest: `.github/optional-ci-checks.yml` (renamed from `required-checks.yml`).

Apply public safety:

```bash
scripts/paycal ops:github-branch-protection public
```

---

## Public Promotion

See `docs/PUBLIC_PROMOTION.md`. GitHub Actions after push are informational.

---

## Docs Map

| File | Purpose |
|------|---------|
| `docs/BRANCH_GOVERNANCE.md` | Local-authoritative model + hooks |
| `docs/PUBLIC_PROMOTION.md` | Promotion checklist |
| `html/tests/README.md` | PHPUnit groups |
| `.github/optional-ci-checks.yml` | Optional workflow names |
| `.github/workflows/README.md` | Workflows are non-authoritative |

---

## Historical note

An earlier iteration treated GitHub CI as authoritative and applied 13 required Actions checks to public `main`. That model was **replaced** — checks were removed from branch protection to avoid queue/billing/plan friction while local gates remain strict.
