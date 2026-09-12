# Backup and recovery

## Recovery objectives

Start with a 24-hour recovery point objective and a four-hour recovery time objective. Tighten these after measuring database size, media growth, and restore duration. Record every restore drill and revise the objectives from evidence.

## Backup scope

- MySQL schema and data, including jobs, notifications, subscriptions, audit logs, and tenant content.
- The private GCS media bucket and object metadata.
- The encrypted production environment, GCS service-account key, Nginx/Supervisor configuration, and TLS recovery material.
- Application release identifiers. Source releases are reproducible and should not be treated as the only backup.

## MySQL procedure

Run a daily consistent logical backup with a database account that has only the permissions needed for backup. Prefer credentials supplied through a root-owned MySQL option file rather than command-line arguments. Stream the dump into an encrypted, authenticated backup repository such as restic or an equivalent managed backup service. Do not retain unencrypted `.sql` files on the web server.

Example logical dump stage:

```bash
mysqldump --defaults-extra-file=/etc/ezyweb/mysql-backup.cnf --single-transaction --quick --routines --events --triggers --set-gtid-purged=OFF ezyweb | gzip -9 > /var/backups/ezyweb/ezyweb.sql.gz
```

Immediately move the result into encrypted off-host storage and remove the staging copy only after checksum verification. Keep daily backups for 14 days, weekly backups for eight weeks, and monthly backups for 12 months unless legal requirements specify otherwise.

For larger deployments, add encrypted MySQL physical backups and binary logs for point-in-time recovery. Monitor backup freshness, duration, size, checksum validation, and repository capacity.

## GCS media protection

Enable object versioning and a retention policy on the media bucket. Configure lifecycle transitions for old noncurrent versions only after confirming the tenant retention policy. Use a separate backup project or bucket with restricted deletion rights for protection against service-account compromise. Never make the media bucket public; Ezyweb serves controlled variants through the application.

## Restore drill

Perform a quarterly restore into an isolated environment:

1. Provision a clean MySQL database and private test GCS bucket.
2. Verify the backup checksum before decompression.
3. Restore the database with a dedicated restore credential.
4. Point an isolated environment at the restored database and copied media.
5. Run `php artisan migrate:status`, `/up/ready`, and the automated test/smoke checklist.
6. Verify at least two unrelated tenants, a published site, a media asset, a subscription, an enquiry, and an audit event.
7. Confirm tenant isolation and ensure no restored process sends real mail or notifications.
8. Record the achieved recovery point and elapsed recovery time.

## Emergency recovery

Declare the incident, stop writes with `php artisan down --render="errors::503"`, preserve logs and evidence, identify the last known-good recovery point, restore into new infrastructure, rotate compromised credentials, validate readiness, and only then update traffic. Keep the damaged system isolated for investigation. Never overwrite the only available backup during recovery.
