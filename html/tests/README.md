# PayCal test layout

Both the private and public repositories share most of `html/tests/`. **Which tests run** is controlled by PHPUnit groups and composer scripts — not by deleting files during promotion.

## Groups

| Group | Meaning | Public `test:quick` | Private `test:quick` |
| --- | --- | --- | --- |
| `unit` | Fast shared product/contract tests | Runs | Runs |
| `soc2` | SOC2 evidence + `Soc2Surface` seam | Excluded | Excluded (run via `test:soc2`) |
| `private-moat` | Asserts on private-only paths/classes | Excluded | Runs (assets exist) |
| `slow` / `stress` | Long-running | Excluded | Excluded |

## Composer scripts

| Script | Private | Public |
| --- | --- | --- |
| `test:quick` | Shared fast gate | Shared fast gate (uses `phpunit.public.xml`) |
| `test:soc2` | SOC2 compliance suite | Tagged tests only (evidence absent — expect skip/fail if run alone) |
| `test:compliance` | `test:quick` + `test:soc2` | Same shape; moat/soc2 excluded from quick |

## Adding a new test

1. **Promoted behavior** (both repos ship it) → `#[Group('unit')]` only; must pass `composer run test:quick` in **both** repos.
2. **SOC2 evidence** (`soc2/**`, bundle integrity) → `#[Group('soc2')]` on class; never rely on `unit` alone.
3. **Private-only asset** (`workspace.js.php`, `_archive/`, `Soc2Surface`, full earnings UI) → `#[Group('private-moat')]` on class or method; public `test:quick` excludes it.
4. Run `php scripts/test/check-test-repo-boundaries.php` before pushing.

## Verification (local-authoritative)

Mandatory before push/promotion — not GitHub Actions:

```bash
composer run test:quick
scripts/paycal checks:policy-meta
scripts/paycal checks:public-health   # from private before promotion
```

Optional GitHub workflows (`.github/workflows/`) may be run for evidence; see `.github/optional-ci-checks.yml`.
