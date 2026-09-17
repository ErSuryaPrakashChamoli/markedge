# Markedge — Backup & Recovery Runbook (Phase 10 §29)

A backup is not verified until a restore has been rehearsed. Record the date of the last successful restore test below.

## What must be backed up

| Asset | Where | Method | Frequency | Retention | Encryption / offsite |
|---|---|---|---|---|---|
| MySQL database | primary DB host | `mysqldump --single-transaction --routines --triggers --set-gtid-purged=OFF markedge` + binlogs for PITR | nightly full, binlogs continuous | 30 daily, 12 monthly | gzip + age/GPG, copied to object storage in another region |
| Uploaded media | `storage/app/public` (or the S3 bucket when object storage is enabled) | rsync/restic to object storage, or bucket versioning + cross-region replication | hourly incremental | 90 days of versions | server-side encryption at rest |
| Application code | Git (tagged releases) | `git tag vX.Y.Z` per deploy | per deploy | forever | repository host |
| Environment secrets | `.env` on the host / secret manager | secret manager or encrypted vault (never in Git) | on change | last 10 versions | vault encryption |
| Deployment configuration | nginx, PHP-FPM, systemd/Supervisor, cron | configuration repo or IaC | on change | forever | repository host |

Never back up: `storage/framework/*` (rebuildable), `public/build` (rebuildable from Git), `vendor` and `node_modules`.

## Restore procedure (database)

1. Put the site in maintenance: `php artisan down --secret=<token> --render=errors::503`.
2. Stop queue workers: `php artisan queue:pause` (or stop the systemd units) so no job writes during restore.
3. Create an empty database with `utf8mb4` / `utf8mb4_unicode_ci`.
4. `gunzip < backup.sql.gz | mysql markedge` then replay binlogs to the target time if needed (`mysqlbinlog --start-position … | mysql`).
5. `php artisan migrate --force` (no-op when the dump is current), `php artisan markedge:search-reindex`, `php artisan optimize:clear && php artisan optimize`.
6. `php artisan markedge:env-check`, then `curl -f https://host/health/ready`.
7. `php artisan up`, resume workers, smoke-test `/`, one service page, `/search?q=software`, `/contact`, `/admin` login, one editorial desk.

## Restore procedure (media)

1. Restore the media tree (or bucket objects) to the media disk path.
2. Conversions are regenerated on demand by the Media Library; to pre-warm: `php artisan media-library:regenerate --only-missing`.
3. Verify a hero image and a document download.

## Restore test log

| Date | Type | Backup taken | Restored to | Duration | Verified by | Result |
|---|---|---|---|---|---|---|
| (not yet performed) | | | | | | |
