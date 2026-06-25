# Public promotion (private → public)

PayCal promotes allowlisted paths from **paycal-private** to **paycal**. GitHub Actions results are informational only; local gates are mandatory.

## Local-Authoritative CI Model

```text
1. Run private local gates.
2. Run public-health check from private.
3. Validate promotion allowlist.
4. Validate no private moat / SOC2 / Admin / Argus paths leak.
5. Push signed public commit.
```

GitHub Actions may run afterward; their result is evidence unless you explicitly invoked them for audit.

## Mandatory pre-promotion checklist

From **paycal-private**:

```bash
scripts/paycal checks:policy-meta
scripts/paycal checks:test-boundaries
composer run test:quick
composer run test:compliance
scripts/paycal checks:public-promotion-scope main...HEAD
scripts/paycal checks:public-health
```

In **paycal** (after applying patch):

```bash
scripts/paycal checks:policy-meta
composer run test:quick
vendor/bin/phpstan analyse --configuration=phpstan.neon --level=9 --no-progress --memory-limit=1G
git push origin main    # signed commit; hooks must pass
```

## Allowlist

Only paths matching `scripts/public-promotion-allowlist.txt` may appear in a promotion diff. The scope guard rejects moat paths (SOC2 admin, Argus, `html/js/businesses/`, etc.) regardless of allowlist.

## Never promote

- While local `checks:public-health` is red
- While `checks:public-promotion-scope` reports violations
- SOC2 evidence trees, `Soc2Surface`, or private-only JS partials
- Because GitHub Actions are queued or red (local gates already decided)

## Production static assets (SRI)

`paycal.app` serves from **`/var/www/paycal`** (not `paycal-private`). Promotion must update the public tree; `git pull` on private alone does not change prod.

PHP-FPM runs as `www-data` and cannot read `.git`, so cache-buster `?v=` uses the repo **`VERSION`** file, while SRI `integrity` is computed from the live file on disk. Nginx caches `.js` for 24h. If allowlisted JS changes without bumping **`VERSION`**, browsers can keep an old `calendar.js` and SRI will block it.

After any promotion that changes SRI-covered JS/CSS:

1. Bump **`VERSION`** (and README via hooks) when the release version changes.
2. Rely on `Render::assetCacheVersion()` (app version + file mtime in prod) on SRI script tags so intra-release file updates still bust cache.

## Optional GitHub evidence

```bash
gh workflow run repo-health.yml --repo PayCal-Technologies/paycal
gh workflow run public-repo-health.yml --repo PayCal-Technologies/paycal-private
```

These supplement local proof; they do not replace it.

## Signed commits

Public `main` requires signed commits (GitHub safety setting). Configure GPG or SSH commit signing locally before pushing.
