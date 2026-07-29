# Authentication Module

## Scope

Login, logout, remember me, forgot/reset password, email verification hooks, Sanctum tokens, session listing, device tracking, and login activity audit.

## Database

### `users` (UUID PK)

Enterprise fields: `username`, `phone`, `employee_id`, `is_active`, lockout fields, `last_login_*`, audit columns, soft deletes.

### `login_activities`

Who attempted login, status (`success|failed|locked|logout`), IP, browser, platform, device, failure reason.

### `user_devices`

Fingerprint-based device registry with revoke support.

### `personal_access_tokens`

Sanctum tokens with `uuidMorphs` for UUID users.

## API (`/api/v1`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/login` | No | Login (rate limited) |
| POST | `/auth/logout` | Yes | Logout + revoke token |
| GET | `/auth/me` | Yes | Current user |
| POST | `/auth/forgot-password` | No | Send reset link |
| POST | `/auth/reset-password` | No | Reset password |
| POST | `/auth/email/verification-notification` | Yes | Resend verification |
| GET | `/auth/email/verify/{id}/{hash}` | Yes + signed | Verify email |
| GET | `/auth/login-activities` | Yes | Login audit |
| GET | `/auth/devices` | Yes | Devices |
| DELETE | `/auth/devices/{id}` | Yes | Revoke device |
| POST | `/auth/logout-other-devices` | Yes | Kill other sessions |
| GET | `/auth/sessions` | Yes | DB sessions |
| DELETE | `/auth/sessions/{id}` | Yes | Revoke session |

## Architecture

```
app/Modules/Authentication/
  DTOs/
  Enums/
  Events/
  Http/Controllers|Requests|Resources/
  Models/
  Policies/
  Repositories/
  Services/
```

Patterns used: Repository, Service Layer, DTO, Policy, API Resources, Form Requests, Events.

## Frontend

- `/login`
- `/forgot-password`
- `/reset-password`
- `/dashboard` (auth shell)
- `/security/login-activity`
- `/security/sessions`
- `/email/verify`

## Security

- Server-side Form Request validation
- Auth endpoint rate limiting (`throttle:auth`)
- Password policy (12+ chars, mixed, numbers, symbols; uncompromised in production)
- Account lockout after failed attempts
- Session regeneration on login
- Soft-delete + audit columns on users/devices
- CSRF + Sanctum stateful SPA support

## ER (Authentication)

```
users 1──* login_activities
users 1──* user_devices
users 1──* personal_access_tokens
users *──* roles *──* permissions
```
