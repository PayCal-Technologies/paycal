# Redis Optimization Transparency Hub — Faster reports, smoother calendars, same privacy-first design

## Why this change matters now

PayCal now handles larger calendars, reports, and business workspaces much faster.

As your history grows, page speed has to keep up. Users can now save entries and open their calendars and reports without the same multi-second waits that used to stack up as records accumulated.

This is a product speed upgrade, not a feature gamble: same core flows, same guardrails, same encrypted work model.

## What improved

In earlier versions, some core paths had to scan through lots of stored entries to find the right records. That worked on smaller datasets, but as work history and team size grew, the extra read work was visible to users as slower monthly views, slower reports, and lag on weekly save/recalc.

We switched those hot paths to use maintained lookup lists so PayCal can pull exactly what is needed:

- Faster calendar month builds
- Faster personal reports
- Faster team/business report pages
- Faster weekly recalc after edits

What users should feel:

- Less “wait and refresh” moments after saves
- Reports opening near-instantly once data is already warmed
- Better interaction feel in larger teams and busier calendars

## Why we started with scans

The first implementation used broad key discovery patterns because they were straightforward and correct, and they let us ship quickly while the feature set was still changing.

That was the right move early on. It is not the same move we want at production scale for repeated, high-traffic reads.

## What changed (in plain words)

We now keep track of where records belong as they are written, so later reads do not have to search the whole store.

Think of it as a phone contact list:
- Instead of guessing which entries might match by scanning every last name in the city, PayCal now keeps direct lists for the people you ask for.
- We did not weaken encryption or move into decrypted storage.
- We kept the data model compatibility checks needed for long-lived records and mixed historical states.

The result is that pages that used to be “scan and filter everything” now do “open the right list and read only matching records.”

## Results

| Area improved        | Before | After | What users notice |
| -------------------- | ------: | ----: | ---------------- |
| Personal reports      | ~2.8 seconds | ~22 ms | Reports open much faster |
| Business reports      | ~2.9 seconds | ~110 ms | Team reports stay practical at scale |
| Calendar builds       | ~4.9 seconds | ~28 ms | Calendar views feel smoother |
| Weekly recalculation  | ~33.8 seconds | ~75 ms | Save → recalc loops feel immediate |

The important improvement is not the multiplier. It is removing noticeable waiting from actions users repeat often.

## What did not change

- Encrypted work entries remain encrypted in Redis.
- Access controls, user permissions, and protected-work visibility logic still apply.
- PayCal did not stop preserving encrypted auditability for team/business workflows.

## What we are still watching

- Production p95 behavior at very large tenant sizes
- Index growth / cleanup under sustained high write volume
- Controlled rebuild flows for historical compatibility debt
- Regression monitoring for calendar and weekly recalc under mixed DEK/KEK entry sets

## What this means for security

Performance did not buy back privacy.

Indexes now hold lookup metadata (IDs, timestamps, status references), not decrypted work payload. The heavy cryptographic and access gates remain where they were.

This is still the same privacy-first baseline: useful fast reads with strict handling of decrypted data.

## Technical notes

The implementation path and detailed technical details are tracked below for users who want the full story.

### How performance was measured

We benchmarked old and new paths with the same seeded dataset and same flow shapes.

- Report path benchmark: `tools/benchmark_reports_compare.php`
- Calendar + weekly recalc benchmark: `tools/work_entry_index_benchmark.php`

Command sample:

```bash
php tools/benchmark_reports_compare.php \
  --records=25000 --members=100 --year=2026 \
  --runs=5 --warmup-runs=1 \
  --seed=1 --cleanup=1 \
  --json=tmp/redis-reports-benchmark-25000-100-warmup.json
```

Median results were:

- Personal reports: 2.8s -> 22ms
- Business reports: 2.9s -> 110ms

and for calendar / weekly recalc:

- Calendar build: 4.9s -> 28ms
- Weekly recalc: 33.8s -> 75ms

### Compatibility and migration behavior

Part of this work includes compatibility handling for older/partly migrated records so historical data still returns correctly while indexes are kept authoritative for lookup performance.

### 2026 roadmap snapshots

Recent project milestones that directly informed this shift:

- `56411342` — Resolve Redis remediation audit drift (2026-06-19)
- `2e4538f2` — Redis compatibility audit record (2026-06-19)
- `b5d6bffb` — Work-entry snapshot hardening (2026-06-19)
- `771d8986` — Remove dead Redis compatibility fallbacks (2026-06-19)
- `cd8d23b8` — Harden business relationship indexes (2026-06-18)
- `db9f796d` / `f8b7f96b` — Workspace caching and business workspace performance work (2026-06-10 to 2026-06-11)
- `3db2229b` / `2b3eafb8` / `f197a008` — Members/reporting path performance improvements and cache/TTL hardening (June 2026)
- `e588dc4e` — Transparency publishing cadence to keep user-facing updates visible (2026-06-30)

## Final summary

The optimization is a backend change in how core read paths find work records. The practical result is a large reduction in visible waiting:

- Faster report and calendar opens
- Faster weekly recalculation
- No privacy tradeoff in the encrypted work model

As data scales, this shift keeps the app responsive where users spend the most time: calendar saves, weekly workflows, and team reporting.

If you want the technical details, we are still happy to walk through the compatibility marker behavior, index rebuild tooling, and Redis maintenance playbook in full depth.
