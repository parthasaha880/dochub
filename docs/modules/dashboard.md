# Dashboard Module

## Scope

Operational dashboard for EDAMS: KPI cards, upload/submission/approval trends, breakdown charts, recent documents, and recent workflow activity.

Built as a dedicated aggregation API so charts stay fast and Search can reuse similar filters later.

## API (`/api/v1`, auth:sanctum)

| Action | Endpoint |
|--------|----------|
| Summary | `GET /dashboard/summary?organization_id=&days=7..90` |

### Response highlights

- `kpis` — documents, folders, storage, trash, employees, workflow/approval counters, pending approvals for current user
- `trends` — daily labels + uploads / submissions / approvals series
- `breakdowns` — approval_status, document status, confidentiality, extension, department, workflow instance status
- `storage_reports` — donut-report payload matching ops dashboard:
  - `by_document_type` / `by_file_category` / `by_department` / `by_user` (`label`, `count`, `size_bytes`)
  - `disk` — quota (`EDAMS_STORAGE_QUOTA_GB`, default 10), used/free/over + rows
- `recent_documents` / `recent_actions` — activity widgets

## Permissions

`dashboard.view` (assigned to standard view roles + data entry)

## Frontend

Route: `/dashboard`

- Organization + range selector (7–90 days)
- **Storage report cards** (cyan header stripe): document types, file category, disk quota, department, users — doughnut + size table
- Trend line + recent workflow activity
- Chart.js via PrimeVue `Chart`

## Config

```env
EDAMS_STORAGE_QUOTA_GB=10
```

## Search (planned next)

Global/advanced Search will:

- Use MySQL FULLTEXT (`documents_search_fulltext`) instead of LIKE-only listing
- Support filters: org, folder, approval/status, confidentiality, type, date range, tags
- Offer saved searches + quick result preview
- Surface from Dashboard as a search entry point / “find in library” widget

No Search module code in this delivery — only this forward plan so Dashboard stays chart-focused.
