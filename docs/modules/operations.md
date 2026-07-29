# Operations Suite (A–E)

Unified enterprise ops delivered under **Operations** (`/operations`).

## A. Audit logs

- Table: `audit_logs` (append-only)
- API: `GET /api/v1/audit-logs`
- Emitted on document upload/trash, workflow actions, share create/revoke/download, retention actions
- Permission: `audit.view`

## B. Notifications

- Laravel `notifications` table (database + mail)
- Workflow events notify submitter / step approvers
- API: `GET /notifications`, unread count, mark read / mark all
- Permission: `notifications.view`

## C. Sharing

- Table: `document_shares` (token, expiry, password, max downloads)
- Auth API: list/create/revoke
- Public: `GET /api/v1/public/shares/{token}`, `/download`
- UI: Operations → Sharing + guest page `/share/{token}`
- Permissions: `sharing.view`, `sharing.manage`

## D. Retention

- Tables: `retention_policies`, `retention_runs`
- Actions on expiry: `archive` | `soft_delete` | `flag`
- API: CRUD policies, run now, recent runs
- Scheduler: `php artisan retention:process` daily at 01:15
- Permissions: `retention.view`, `retention.manage`

## E. Reports

- Preview JSON + CSV export
- Types: `inventory`, `workflow`, `audit`, `shares`
- API: `GET /reports/preview`, `GET /reports/export`
- Permissions: `reports.view`, `reports.export`

## Frontend

Nav: **Operations** — tabs for Audit | Notifications | Sharing | Retention | Reports

## Out of scope (this batch)

F. cPanel deployment docs — still next if needed.
