# Organization Management Module

## Hierarchy (3NF)

```
Organization
 ├── Branch (nested via parent_id)
 │    └── Office
 ├── Department (optional branch_id, nested via parent_id)
 │    ├── Section
 │    │    └── Unit
 │    └── Unit
 ├── Designation
 └── Employee → User, Department, Section, Unit, Branch, Office, Designation, Manager
```

All primary keys are UUID. Soft deletes + audit columns (`created_by`, `updated_by`, `deleted_by`) are applied.

## API (`/api/v1`, auth:sanctum)

| Resource | Endpoints |
|----------|-----------|
| Organizations | CRUD + `GET /organizations/{id}/tree` + options |
| Branches | CRUD + options |
| Departments | CRUD + options |
| Sections | CRUD + options |
| Units | CRUD + options |
| Offices | CRUD + options |
| Designations | CRUD + options |
| Employees | CRUD + options |

## Permissions

- `organization.view` / `organization.manage`
- `employees.view` / `employees.manage`

`super_admin` passes all gates via `Gate::before`.

## Frontend

Route: `/organization`

- Active organization selector
- Structure tree (PrimeVue Tree)
- Tabbed managers for all 8 entities with search, create, edit, delete

## Demo seed

Organization `EDAMS` with HQ branch, IT department, Archives section, Digitization unit, office, designation, and employee linked to `admin@edams.local`.

## Architecture

```
app/Modules/Organization/
  Enums/ Models/ Policies/
  Repositories/ Services/
  Http/Controllers|Requests|Resources/
```
