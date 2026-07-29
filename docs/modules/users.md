# Users & Roles Module

## Scope

Full admin management of users, Spatie roles, permissions, permission groups, and role hierarchy.

## API (`/api/v1`, auth:sanctum)

| Resource | Endpoints |
|----------|-----------|
| Users | CRUD `/users` |
| Roles | CRUD `/roles` + `GET /roles/options/list` |
| Permissions | CRUD `/permissions` + groups/grouped/options |

### User payload extras

- `roles`: array of role names
- `permissions`: array of direct permission names
- password required on create; optional on update

### Guards

- Cannot deactivate/delete self
- Cannot remove own `super_admin` role
- Cannot delete last super admin
- System roles cannot be deleted or renamed
- Permissions assigned to roles cannot be deleted

## Permissions

- `users.view` / `users.manage`
- `roles.view` / `roles.manage`
- `permissions.view` / `permissions.manage`

`Gate::before` grants all abilities to `super_admin`.

## Frontend

Route: `/users`

Tabs:

1. **Users** — search, role filter, create/edit with MultiSelect roles
2. **Roles** — hierarchy level, permission checkboxes grouped by permission group
3. **Permissions** — CRUD with group assignment

## Architecture

```
app/Modules/Users/
  Http/Controllers|Requests|Resources/
  Policies/
  Repositories/
  Services/
```
