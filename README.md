# EDAMS — Enterprise Document Archiving & Records Management System

Production-ready Laravel 13 + Vue 3 SPA for government-grade document archiving.

## Stack

- Laravel 13 / PHP 8.4
- MySQL 8+
- Vue 3 (Composition API) + Pinia + Vue Router + Vite
- Tailwind CSS 4 + PrimeVue 4
- Laravel Sanctum + Spatie Permission

**No Docker** — designed for XAMPP / cPanel deployment.

## Quick start (XAMPP)

1. Ensure MySQL is running and database `dochub` exists.
2. Copy env (already configured for local MySQL root with empty password):

```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run build
php artisan serve
```

3. Open `http://localhost:8000/login`

### Demo credentials

- Email: `admin@edams.local`
- Password: `Password@12345`

## Module status

| Module | Status |
|--------|--------|
| Foundation / Scaffolding | Done |
| Authentication | Done |
| Organization Management | Done |
| Users / Roles | Done |
| Document Management | Done |
| Workflow (approval levels) | Done |
| Dashboard (stats/charts) | Done |
| Search | Done |
| Audit / Notifications / Sharing / Retention / Reports | Done (Operations hub) |
| cPanel deployment docs | Next |

See [docs/modules/authentication.md](docs/modules/authentication.md), [organization](docs/modules/organization.md), [users](docs/modules/users.md), [documents](docs/modules/documents.md), [workflow](docs/modules/workflow.md), [dashboard](docs/modules/dashboard.md), [search](docs/modules/search.md), [operations](docs/modules/operations.md).

## Development

```bash
composer run dev
```

Runs Laravel server, queue worker, logs, and Vite together.

## Tests

```bash
php artisan test
```
