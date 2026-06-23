# Site Ownership Consolidation Policy

Last updated: 2026-06-07
Status: Proposed (implementation-ready)
Scope: Site identity, ownership, sharing, and work-entry joins for earnings/businesses

## 1. Decision

Adopt a canonical single-owner site model:

- One site has one immutable owner tuple: `owner_type` + `owner_uuid`.
- Access is separate and additive through explicit shares.
- Legacy owner-prefixed site references remain read-compatible during migration only.

This resolves current mismatches between `business:site:{business_id}` refs and work keys that use non-aligned site IDs.

## 2. Policy Options

### Option A (recommended): Canonical Site UUID + Single Owner + Separate Sharing

- Canonical site identity is `site_uuid`.
- Ownership fields live on the site record.
- Sharing is modeled as principal grants (`user:{uuid}`, `business:{business_id}`).
- Legacy keys are read-only compatibility inputs.

### Option B: Overlay Registry on Existing Keys

- Keep existing `site:{owner_uuid}:{site_id}` write-path.
- Add registry mapping old refs to canonical metadata.
- Lower short-term risk, but preserves dual-write complexity longer.

### Option C: Business-Only Ownership for Business Context

- Any site used in business workflows must be business-owned.
- Strong governance, high migration friction.

## 3. Canonical Data Model

## 3.1 Site Record

Key:

- `site:{site_uuid}` (hash)

Required fields:

- `site_uuid`
- `site_name`
- `owner_type` (`user` | `business`)
- `owner_uuid` (user UUID or business ID)
- `status` (`active` | `archived`)
- `created_at`
- `updated_at`

Optional compatibility fields:

- `legacy_site_id`
- `legacy_owner_uuid`

## 3.2 Ownership Indexes

- `site:index:owner:user:{user_uuid}` (set of `site_uuid`)
- `site:index:owner:business:{business_id}` (set of `site_uuid`)

## 3.3 Share Grants

Canonical principal format:

- `user:{user_uuid}`
- `business:{business_id}`

Keys:

- `site:shares:{site_uuid}` (set of principals)
- `site:index:share:user:{user_uuid}` (set of `site_uuid`)
- `site:index:share:business:{business_id}` (set of `site_uuid`)

## 3.4 Legacy Compatibility Indexes

- `site:legacy_ref:{owner_uuid}:{site_id}` -> `site_uuid` (string)
- `site:legacy_id:{site_id}` (set of `site_uuid`)

`site:legacy_id:{site_id}` should normally contain one site UUID. Cardinality > 1 must be treated as ambiguous.

## 4. Work Record Contract

Target key shape:

- `work:{user_uuid}:{date}:{site_uuid}`

Required fields:

- `site_uuid`
- `site_name_snapshot`
- `site_owner_type_snapshot`
- `site_owner_uuid_snapshot`

Backward compatibility:

- Existing keys `work:{user_uuid}:{date}:{site_id}` remain readable.
- Read path must normalize legacy work records to canonical `site_uuid` before aggregation.

## 5. Normalization Algorithm (Read Path)

For each work entry:

1. If entry includes `site_uuid`, use it.
2. Else resolve by exact legacy ref if `site_owner_uuid` is present:
   - lookup `site:legacy_ref:{site_owner_uuid}:{site_id}`.
3. Else resolve by `site:legacy_id:{site_id}` only when cardinality is exactly 1.
4. If unresolved or ambiguous, classify as `orphaned_site_reference` and exclude from business analytics totals.
5. Emit diagnostics counter and sampled log with user/date/site key.

Do not guess ownership from business membership when multiple candidates exist.

## 6. Business Earnings Inclusion Rule

For selected business `business_id`:

1. Build `business_visible_site_uuids` as union of:
   - `site:index:owner:business:{business_id}`
   - `site:index:share:business:{business_id}`
2. Normalize each member work row to `site_uuid`.
3. Include row only when normalized `site_uuid` exists in `business_visible_site_uuids`.

This replaces matching against `{site_owner_uuid}:{site_id}` strings.

## 7. Permission Policy

### 7.1 Owner (user-owned site)

- Can: edit, archive, delete, share, unshare, transfer.

### 7.2 Business owner/manager (business-owned site)

- Can: edit, archive, share, assign.
- Cannot: transfer without ownership workflow.

### 7.3 Shared user/member

- Can: view, select, record work.
- Cannot: delete, archive, transfer, modify ownership.

## 8. UI Contract (Mandatory)

Every site reference must render:

- Ownership badge (`Personal` or `Business`)
- Access reason (`Owner`, `Shared via Business`, `Shared Directly`)
- Capability hint (`Can edit` or `View and record only`)

Applies to:

- Site picker
- Sites list
- Site detail
- Search results
- Business dashboards
- Work-entry history rows

## 9. Migration Plan

Phase 1: Read-only UI ownership badges from current data.

Phase 2: Introduce canonical `site_uuid` records and owner fields.

Phase 3: Add share keys and share indexes; keep compatibility reads.

Phase 4: Dual-write new/legacy work paths with normalization checks.

Phase 5: Backfill old work entries with `site_uuid` snapshots.

Phase 6: Switch earnings/business aggregations to canonical inclusion rule.

Phase 7: Remove legacy writes; keep legacy read fallback for one release window.

## 10. Guardrails and Observability

Track counters:

- `site_resolution_exact_legacy_ref`
- `site_resolution_unique_legacy_id`
- `site_resolution_ambiguous_legacy_id`
- `site_resolution_orphaned`

Operational threshold:

- Canonical-only mode cannot be enabled until ambiguous + orphaned ratios stay under agreed SLO for two consecutive release cycles.

## 11. Immediate Hotfix Guidance for Current Incident

Until full migration is live:

1. Add a resolver helper used by earnings business aggregation.
2. Normalize every work row to `site_uuid` or a deterministic legacy surrogate.
3. Join inclusion on normalized site identity set, not owner-prefixed site strings.
4. Surface unresolved rows in an admin diagnostics panel to prevent silent zeroing.

## 12. Non-Negotiable Principle

Every user must be able to answer, at every site touchpoint:

- Who owns this site?
- Why can I see this site?
- Can I modify this site?
