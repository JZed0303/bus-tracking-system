# Bus Tracking System

Laravel 11 backend and admin/company web panel for real-time bus operations, trip execution, employee QR attendance, and dispatch communication.

## Core Modules

- Role-based admin and company portals
- Fleet, route, assignment, and trip management
- Bus-device API (Sanctum + ability-scoped tokens)
- Live GPS ingest and active bus map
- QR check-in/check-out with offline sync support
- Mid-trip incident handling and replacement transfer flow
- Bus/admin/company chat with broadcasting
- Audit trail and telemetry pruning utilities

## Tech Stack

- PHP 8.2+
- Laravel 11
- Laravel Passport + Sanctum
- Spatie Laravel Permission
- PostgreSQL (with PostGIS migrations present)
- Redis/Predis (recommended for queue/cache/broadcast scaling)
- Vite + React/Blade frontend assets

## Local Setup

1. Install dependencies:
```bash
composer install
npm install
```

2. Configure environment:
```bash
cp .env.example .env
php artisan key:generate
```

3. Configure DB credentials in `.env`, then run:
```bash
php artisan migrate --seed
```

4. Build assets:
```bash
npm run dev
```

5. Start app:
```bash
php artisan serve
```

## Operational Commands

- Run tests:
```bash
php artisan test
```

- Prune telemetry:
```bash
php artisan transport:prune-telemetry
php artisan transport:prune-telemetry --dry-run
php artisan transport:prune-telemetry --days=21
```

- Reset transport data (dangerous, non-production use):
```bash
php artisan transport:reset
```

## Key Environment Variables

### Telemetry retention
- `TRANSPORT_TELEMETRY_PRUNING_ENABLED=true`
- `TRANSPORT_TELEMETRY_PRUNE_AFTER_DAYS=14`
- `TRANSPORT_TELEMETRY_PRUNE_CHUNK_SIZE=5000`
- `TRANSPORT_TELEMETRY_PRUNE_SCHEDULE=02:30`

### API limits
- `BUS_API_RATE_LIMIT_PER_MINUTE=60`
- `BUS_GPS_RATE_LIMIT_PER_MINUTE=60`

### Company dashboard cache
- `COMPANY_DASHBOARD_ANALYTICS_CACHE_ENABLED=true`
- `COMPANY_DASHBOARD_ANALYTICS_CACHE_TTL_SECONDS=60`

## API Overview

### Authentication
- Bus device login: `POST /api/bus/login`
- Bus device logout: `POST /api/bus/logout`
- Broadcasting auth: `POST /api/broadcasting/auth`

### Core bus endpoints
- Context: `GET /api/bus/context`
- Dashboard: `GET /api/bus/dashboard`
- Trip start/end/incident:
  - `POST /api/bus/trip/start`
  - `POST /api/bus/trip/end`
  - `POST /api/bus/trip/incident`
- GPS ingest: `POST /api/bus/gps`
- QR scan:
  - `POST /api/bus/scan`
  - `POST /api/bus/scan/sync-offline`

## API Lifecycle and Versioning Policy

As of **March 5, 2026**, this system serves both:

- Legacy endpoints: `/api/...`
- Explicit v1 endpoints: `/api/v1/...`

Example equivalents:

- `POST /api/bus/login` and `POST /api/v1/bus/login`
- `POST /api/bus/gps` and `POST /api/v1/bus/gps`
- `POST /api/broadcasting/auth` and `POST /api/v1/broadcasting/auth`

Policy:

- New mobile/web integrations should use `/api/v1/...`.
- Legacy `/api/...` is maintained for backward compatibility.
- Breaking API changes must be released in a new version namespace (`/api/v2/...`), not inside `/api/v1/...`.
- Response contracts in `/api/v1/...` are backward compatible for additive changes only.

## Related Internal Docs

- Offline sync guide: `docs/OFFLINE_SYNC_API.md`
- Transfer and assignment-leg flow: `docs/TRANSFER_AND_LEG_GUIDE.md`
- Module management notes: `README_ModuleManagement.md`

## Security and Operations Notes

- Do not expose debug endpoints in production.
- Restrict super-admin-only routes and destructive tools by environment and policy.
- Run migration and queue workers under managed process supervision in production.

## License

Internal project repository. Follow your organization policy for usage and distribution.
