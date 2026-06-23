# PayCal Release Ledger

Production does not follow `main`. Production follows a promotion decision recorded outside the app repository.

```text
Git SHA -> candidate -> promotion -> deployment manifest -> deployment receipt -> runtime proof
```

The release invariant is:

```text
approved SHA == manifest SHA == deployed SHA == runtime SHA
```

## Ledger Repository

Primary release state lives in a separate private corporate repository:

```text
/private/var/www/paycal-ledgers
```

The repo is product- and target-scoped so PayCal Technologies can release more than one product without redesigning release state:

```text
paycal-ledgers/
  LEDGER_POLICY.md
  schemas/
  products/
    paycal/
      PRODUCT.md
      targets.json
      environments/
        dev.paycal.app/
          desired.json
          latest-receipt.json
          runtime-proof.json
          last-known-good.json
        prod.paycal.app/
          desired.json
          latest-receipt.json
          runtime-proof.json
          last-known-good.json
      releases/
      deployments/
      runtime/
      evidence/
    .template/
  corporate/
```

`paycal-private` remains source authority. `paycal-ledgers` is deployment state and evidence authority.

## Command Surface

```bash
scripts/paycal checks:ledger-private
scripts/paycal release:candidate
scripts/paycal release:promote dev --reason "Dev validation"
scripts/paycal release:promote prod --reason "Production release"
scripts/paycal deploy:desired prod
scripts/paycal deploy:record-receipt prod --healthcheck-result pass
scripts/paycal deploy:record-runtime prod --runtime-sha "$(git rev-parse HEAD)"
scripts/paycal deploy:status prod
scripts/paycal deploy:rollback prod --reason "Rollback to last known-good"
scripts/paycal release:evidence --target prod.paycal.app
```

For future products:

```bash
scripts/paycal ledger:init-product gridcal
scripts/paycal ledger:promote gridcal prod.gridcal.example <sha> --reason "Production release"
scripts/paycal ledger:status gridcal prod.gridcal.example
```

Use `PAYCAL_LEDGER_ROOT` and `PAYCAL_LEDGER_PRODUCT` to target another ledger repo or product.

`checks:ledger-private` is part of the local-authoritative CI/CD setup. It fails if the ledger root is not a Git repo or if its GitHub remote is not private.

For speed, the GitHub visibility lookup is cached in the ledger repo's `.git` directory for one hour. Set `PAYCAL_LEDGER_VISIBILITY_CACHE_TTL=0` to force a fresh visibility check.

## Policy

- `main` is not a deployment instruction.
- Prod can only deploy SHAs that were promoted for `prod`.
- Every deploy creates a receipt.
- Rollback uses the previous known-good SHA, not branch state.
- Public repo promotion remains separate from private app release.
