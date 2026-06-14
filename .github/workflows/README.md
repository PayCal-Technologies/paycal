# Optional GitHub verification workflows

These workflows are **not PayCal's CI authority**. Mandatory gates run locally (hooks + `scripts/paycal checks:*`).

```bash
gh workflow run policy-meta.yml --repo PayCal-Technologies/paycal
gh workflow run repo-health.yml --repo PayCal-Technologies/paycal
```

Do not require these checks on `main`. See `.github/optional-ci-checks.yml`.
