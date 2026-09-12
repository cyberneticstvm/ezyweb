# Ezyweb

Ezyweb is a production-oriented multi-tenant SaaS website builder for small businesses and organisations. The repository contains Phases 1–11 of the controlled implementation plan.

## Phase 1 stack

- Laravel 13, PHP 8.4+ production target
- Inertia.js, React, strict TypeScript
- Tailwind CSS 4 and shadcn/ui-compatible component conventions
- MySQL 8
- Redis via Predis
- Google Cloud Storage through Laravel's filesystem abstraction
- Pest, Laravel Pint, Larastan, ESLint, and Prettier

The local application domain is `http://ezyweb.test`. Production domain values are centralized in `config/domains.php` for `ezyweb.in`, `www.ezyweb.in`, `app.ezyweb.in`, and `admin.ezyweb.in`.

## Local setup

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate
npm run build
```

Production deployment, backups, restoration drills, monitoring, and incident procedures are documented in [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md), [`docs/BACKUP_AND_RECOVERY.md`](docs/BACKUP_AND_RECOVERY.md), and [`docs/OPERATIONS.md`](docs/OPERATIONS.md). Run `php artisan app:production-check` before every production release.

Configure the MySQL connection in `.env`. Start Redis before switching `SESSION_DRIVER`, `CACHE_STORE`, and `QUEUE_CONNECTION` to `redis`. Tests use isolated in-memory SQLite and do not require either service.

For GCS, set `GOOGLE_CLOUD_PROJECT_ID`, `GOOGLE_CLOUD_KEY_FILE`, and `GOOGLE_CLOUD_STORAGE_BUCKET`. Keep credential JSON outside the repository. `FILESYSTEM_DISK=local` remains safe for local development; `CLOUD_FILESYSTEM_DISK=gcs` identifies the cloud disk.

## Super-admin bootstrap

Set `SUPER_ADMIN_NAME`, `SUPER_ADMIN_EMAIL`, and `SUPER_ADMIN_PASSWORD`, then run:

```bash
php artisan db:seed
```

The seeder does nothing when the email or password is absent, so it never creates a predictable administrator account.

## Development and verification

```bash
composer dev
php artisan test
composer analyse
composer format
npm run types
npm run lint
npm run format:check
npm run build
```

## Current scope

Phase 1 includes the public SaaS homepage, authentication, dashboard shells, shared UI systems, infrastructure configuration, and automated foundation tests.

Phase 2 includes tenants, memberships, websites, verified platform domains, cached hostname resolution, tenant-scoped queries, policies, reserved-subdomain validation, live availability checks, and the transactional eight-step onboarding wizard.

Phase 3 includes the seeded Horizon, LocalPro, and Canvas template catalog; public live previews; tenant-scoped template selection; atomic template-switch snapshots; validated design tokens; five theme presets; WCAG contrast checks; and responsive live design previews.

Phase 4 includes tenant-scoped pages and page sections, a 30-type controlled section registry, multiple section variants, schema-driven content forms, per-device visibility, enable/disable controls, dnd-kit ordering with keyboard alternatives, duplication, confirmed removal, immediate responsive draft previews, and explicit draft revision tracking.

Phase 5 includes verified-domain public routing, published-state enforcement, server-rendered Blade tenant websites, template-specific Horizon, LocalPro, and Canvas presentation, automatic landing-page or multipage navigation, reusable section views, shared accessible header and footer, responsive device visibility, runtime content sanitization, and reserved system page slugs.

Phase 6 includes expiring signed draft and historical previews, authorization and no-index preview protection, validated and retry-safe publishing, immutable version snapshots, publication status and actor tracking, targeted tenant-site cache invalidation, queued cache warming, version history, and non-destructive restore workflows that create a new draft version.

Phase 7 includes separate tenant-scoped services, products, brands, testimonials, portfolio, gallery, team, FAQ, and careers data; unified blog, news, and event publishing with categories, tags, drafts and scheduling; configurable module limits; controlled management forms; sanitized long-form text; and immutable content-module publishing snapshots rendered by matching public website sections.

Phases 8–10 add contact and enquiry management, GCS-ready media handling, SEO, redirects, annual plans, feature limits, subscription lifecycle processing, reminders, suspension, and audited super-admin subscription controls.

Phase 11 adds nonce-aware security headers, structured request correlation, readiness probes, slow request/query and failed-job reporting, production configuration validation, keyboard and reduced-motion improvements, responsive browser verification, and operations runbooks.
