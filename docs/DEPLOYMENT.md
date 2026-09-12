# Ezyweb production deployment

This runbook targets Ubuntu, Nginx, PHP 8.4 FPM, MySQL 8, Redis, Supervisor, Cloudflare, and Google Cloud Storage. The canonical domains are `ezyweb.in`, `www.ezyweb.in`, `app.ezyweb.in`, `admin.ezyweb.in`, and `*.ezyweb.in`.

## Host preparation

Install Nginx, PHP 8.4 FPM with the extensions required by `composer check-platform-reqs`, MySQL 8, Redis, Supervisor, Composer 2, Node.js, and an ACME client. Create a non-login deployment user and `/var/www/ezyweb/releases`, `/var/www/ezyweb/shared`, and `/etc/ezyweb` with least-privilege ownership. The PHP worker user needs write access only to the shared `storage` directory and cached bootstrap data.

Never store `.env`, GCS JSON, database dumps, or TLS private keys in a release or source control. Put the environment file at `/var/www/ezyweb/shared/.env` and the GCS key at `/etc/ezyweb/gcs-service-account.json`, readable only by the PHP worker account.

## DNS and TLS

Create Cloudflare proxied records for the apex, `www`, `app`, `admin`, and wildcard `*`. Point them to the same origin. Use Full (strict) TLS and an origin certificate covering `ezyweb.in` and `*.ezyweb.in`, or obtain a DNS-01 ACME wildcard certificate. Restrict the origin firewall to Cloudflare networks and administrative access. Do not enable HSTS preload until every subdomain is permanently HTTPS-capable.

Install [`deployment/nginx/ezyweb.conf`](../deployment/nginx/ezyweb.conf), validate with `sudo nginx -t`, and reload Nginx. The Apache example is optional and is not the production target.

## First deployment

Create a timestamped release directory and deploy the reviewed source there. From that directory:

```bash
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
npm ci
npm run build
ln -s /var/www/ezyweb/shared/.env .env
rm -rf storage
ln -s /var/www/ezyweb/shared/storage storage
php artisan config:cache
php artisan event:cache
php artisan route:cache
php artisan view:cache
php artisan app:production-check
php artisan migrate --force
```

The `storage` replacement commands apply only to a newly created, verified release directory. Never run them against the shared storage target.

Switch `/var/www/ezyweb/current` atomically to the new release only after the build, configuration check, migration, and smoke test pass. Then run:

```bash
sudo supervisorctl reread
sudo supervisorctl update
php artisan queue:restart
```

Install the scheduler once:

```cron
* * * * * cd /var/www/ezyweb/current && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

## Zero-downtime release sequence

1. Verify a fresh database and GCS backup.
2. Build a new immutable release.
3. Run `php artisan app:production-check` and `php artisan migrate --force`.
4. Run `/up` and `/up/ready` against the new release locally.
5. Atomically update the `current` symlink.
6. Reload PHP FPM if PHP code is held by OPcache, then run `queue:restart`.
7. Verify marketing, login, dashboard authentication, one tenant hostname, media, contact submission, queue processing, and the scheduler.
8. Retain at least the previous two releases until the observation window closes.

## Rollback

If the migration is backward-compatible, repoint `current` to the previous release, reload PHP FPM, restart queues, and clear/rebuild framework caches. Never run `migrate:rollback` automatically. For an incompatible schema change, enter maintenance mode and use the tested database restore procedure in `BACKUP_AND_RECOVERY.md` before restoring application traffic.

## Production environment

Copy `.env.production.example` to the shared environment file and replace every blank secret. GCS credentials are mandatory before `MEDIA_DISK=gcs` can pass the production check. Use a dedicated service account limited to the Ezyweb media bucket. Configure SMTP/API mail delivery, Cloudflare Turnstile, Redis authentication, secure encrypted cookies, and `CSP_REPORT_ONLY=false` only after reviewing report-only violations.

## Release verification

```bash
php artisan about --only=environment,cache,drivers
php artisan app:production-check
php artisan migrate:status
php artisan schedule:list
php artisan queue:monitor redis:default --max=100
curl --fail --silent https://ezyweb.in/up
curl --fail --silent http://127.0.0.1/up/ready -H 'Host: ezyweb.in'
```
