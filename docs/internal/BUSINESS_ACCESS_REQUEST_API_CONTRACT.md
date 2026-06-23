# Businesses Access Request API Contract

Date: 2026-03-26
Scope: Business access request lifecycle (request, list, approve, reject)

## Endpoints

- `POST /api/v1/businesses/access/request`
- `GET /api/v1/businesses/{businessId}/access/requests`
- `POST /api/v1/businesses/{businessId}/access/requests/approve`
- `POST /api/v1/businesses/{businessId}/access/requests/reject`

## Request/Response Shape

### `POST /api/v1/businesses/access/request`

Form fields:
- `owner_email` (required)
- `csrf_token` (optional client field; ignored by server guards when present)

Success payload (`data`):
- `request_id`
- `business_id`
- `owner_uuid`
- `status` (`pending`)
- `expires_at`

Failure conditions:
- invalid owner email
- owner account not found
- requester already active in business
- duplicate pending request for same requester/business (returns success with existing pending id)

Audit event:
- `access.requested`

### `GET /api/v1/businesses/{businessId}/access/requests`

Auth/permissions:
- authenticated user
- same business context
- must satisfy business access-management permissions

Success payload (`data`):
- `business_id`
- `requests[]` with:
  - `request_id`
  - `requester_uuid`
  - `requester_contact_email`
  - `status`
  - `created_at`
  - `expires_at`

### `POST /api/v1/businesses/{businessId}/access/requests/approve`

Form fields:
- `request_id` (required)
- `csrf_token` (optional client field)

Behavior:
- only `pending` requests are approvable
- creates/updates relationship as `member` with default scopes:
  - `sites.read`
  - `work.read`
- sets request status to `approved`
- clears active request pointer for requester/business

Success payload (`data`):
- `request_id`
- `business_id`
- `requester_uuid`
- `scopes[]`

Audit event:
- `access.request.approved`

### `POST /api/v1/businesses/{businessId}/access/requests/reject`

Form fields:
- `request_id` (required)
- `csrf_token` (optional client field)

Behavior:
- only `pending` requests are rejectable
- sets request status to `rejected`
- clears active request pointer for requester/business

Success payload (`data`):
- `request_id`
- `business_id`
- `requester_uuid`

Audit event:
- `access.request.rejected`

## Data Cleanup Guarantees

When an owned business is removed, the service removes:
- access request records (`business:access:request:{id}`)
- business index set (`business:access:request:business:{businessId}`)
- requester index links (`business:access:request:requester:{requesterUuid}`)
- active pointer keys (`business:access:request:active:{businessId}:{requesterUuid}`)
