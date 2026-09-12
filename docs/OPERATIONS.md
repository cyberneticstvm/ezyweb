# Monitoring and operations

## Probes

`GET /up` is the lightweight liveness probe. `GET /up/ready` checks database and cache read/write readiness and returns HTTP 503 if either dependency is unavailable. Restrict readiness details to local or authenticated monitoring networks at Nginx.

Every response includes `X-Request-ID`. A valid upstream request ID is preserved; otherwise Ezyweb creates one. Application logs receive request, route, user, and—when available—tenant/website context without recording credentials or submitted message bodies.

## Required monitors

- Liveness every minute and readiness every one to five minutes.
- HTTPS certificate expiry, DNS resolution for apex and wildcard, and Cloudflare origin reachability.
- HTTP 5xx rate, latency percentiles, slow-request warnings, and slow-query warnings.
- Redis memory, evictions, connectivity, and persistence health.
- MySQL connections, replication/backup state, disk, locks, and slow queries.
- Queue depth, oldest-job age, failed jobs, worker process count, and retry volume.
- Scheduler heartbeat and the last successful subscription lifecycle run.
- Mail rejection/bounce rate and reminder/contact notification failures.
- Disk usage for releases/logs/backups and GCS usage versus plan allocations.
- Backup freshness and the outcome/date of the latest restore drill.

Suggested initial alerts are readiness failure for two consecutive probes, any sustained 5xx rate above 2%, p95 application latency above 1.5 seconds for 10 minutes, any failed critical job, queue depth above 100, no scheduler heartbeat for five minutes, disk above 80%, certificate expiry below 21 days, or backup age above 26 hours. Tune these thresholds using production baselines.

## Routine commands

```bash
php artisan schedule:list
php artisan queue:monitor redis:default --max=100
php artisan queue:failed
php artisan app:production-check
php artisan migrate:status
sudo supervisorctl status
```

Failed jobs and batches are pruned by the scheduler after their configured retention window. Investigate before retrying. Use `queue:retry <uuid>` only after correcting the cause and confirming the job is safe to execute again.

## Incident handling

1. Assign an incident owner and capture start time, impact, and request IDs.
2. Protect tenant data and stop harmful writes before optimizing availability.
3. Check recent deployments, readiness dependencies, queue failures, and correlated logs.
4. Roll back code only when the schema remains compatible; otherwise use the recovery runbook.
5. Communicate externally without exposing tenant or security-sensitive details.
6. Preserve logs, rotate affected secrets, and document corrective actions.

## Logging and error reporting

Use JSON-formatted centralized logs or a platform agent in production and retain application logs according to policy. A Sentry-compatible exception service can be added by configuring its official Laravel SDK when an account is selected; Phase 11 does not introduce an unconfigured vendor dependency. Scrub authorization headers, cookies, passwords, Turnstile tokens, GCS credentials, contact messages, resumes, and other tenant content before export.

## Security review cadence

Run dependency audits, static analysis, the full test suite, production build, and the browser smoke matrix before every release. Review super-admin audit logs weekly, access rights quarterly, and dependency/security advisories continuously. Rotate service-account and infrastructure credentials on role change, suspected exposure, and the organization’s scheduled rotation interval.
