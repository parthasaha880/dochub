# Workflow Module

## Scope

Multi-level **sequential** approval workflows for documents:

- Workflow definitions with ordered steps
- Approvers by **role and/or specific users**
- Submit / approve / reject / return / cancel / resubmit
- Approval inbox
- Full action timeline (audit trail of decisions)
- Document `approval_status` driven only by workflow transitions (not free-form metadata edits)
- Stats + recent activity endpoints for Dashboard consumption

## Database

| Table | Purpose |
|-------|---------|
| `workflows` | Org-scoped definition (code, default, active, optional category) |
| `workflow_steps` | Ordered levels; optional `role_id` |
| `workflow_step_approvers` | Specific user assignees per step |
| `workflow_instances` | Submission runtime state + current step |
| `workflow_actions` | Immutable history of actions |

## Lifecycle

1. Document starts as `draft`
2. **Submit** → instance `in_progress`, document `under_review`, current step = level 1
3. Approver at current step **Approve** → advance to next level, or finalize `approved`
4. **Reject** → instance + document `rejected` (resubmit allowed)
5. **Return** → instance + document `returned` (resubmit allowed)
6. **Cancel** (submitter or workflow manager) → instance `cancelled`, document back to `draft`

## API (`/api/v1`, auth:sanctum)

| Action | Endpoint |
|--------|----------|
| CRUD definitions | `GET/POST/PUT/DELETE /workflows` |
| Inbox | `GET /workflows/inbox` |
| Instances / history | `GET /workflows/instances` |
| Instance detail | `GET /workflows/instances/{id}` |
| Submit | `POST /workflows/submit` |
| Approve / Reject / Return / Cancel | `POST /workflows/instances/{id}/approve\|reject\|return\|cancel` |
| Document status | `GET /workflows/documents/{document}/status` |
| Stats (Dashboard) | `GET /workflows/stats?organization_id=` |
| Recent actions | `GET /workflows/recent?organization_id=` |

## Permissions

`workflow.view`, `workflow.manage`, `workflow.submit`, `workflow.approve`

## Frontend

Route: `/workflow`

- **Approval inbox** — act on pending steps
- **Workflow definitions** — create/edit multi-level flows
- **History** — instance list + timeline detail

Documents library: send icon submits draft/returned/rejected documents.

Dashboard: pending/in-progress counts + recent workflow activity (full charts module still next).

## Demo seed

`WorkflowDemoSeeder` creates **Standard Two-Level Approval** (`STD-2LVL`) for org `EDAMS` (Manager → Organization Admin), with admin also listed as explicit approver so the demo user can walk the full path.
