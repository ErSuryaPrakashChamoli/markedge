# Markedge — Production Deployment Checklist (Phase 10 §35/§36)

Assumes a release-directory layout (`/var/www/markedge/releases/<sha>` + `current` symlink) so new code is fully prepared before traffic switches. Commands run from the new release directory unless stated.

## 0. Before you start
- [ ] Release tagged in Git; changelog reviewed; `MARKEDGE_PHASE_*` docs current.
- [ ] Backups current and the last restore test is recorded (`docs/production/backup-and-recovery.md`).
- [ ] Migrations reviewed: additive only (Phase 10 adds none). Any migration touching `leads`, `conversion_events`, `cta_clicks`, `search_entries` is checked for online-DDL behaviour.

## 1. Prepare the release (no traffic impact)
1. `git clone --depth 1 --branch <tag>` into the new release directory.
2. `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`.
3. `npm ci && npm run build` (or copy `public/build` from CI). Assets are content-hashed and immutable.
4. Link shared paths: `.env`, `storage/`, `public/storage` → `storage/app/public` (`php artisan storage:link`).
5. `php artisan markedge:env-check` — must print "Environment check passed" (fails on debug mode, sync queue, missing key, non-https URL, unset indexability flag).
6. `php artisan optimize` (config, routes, views, events). Route caching is compatible: all routes are static or closure-free.
7. `php artisan migrate --force --no-interaction` — backward-compatible migrations only, run before the switch.
   **Warning:** never run `migrate:fresh`, `migrate:reset`, `db:wipe` or `migrate:rollback` against production without a verified backup and an explicit decision.

## 2. Switch traffic
8. Update the `current` symlink atomically (`ln -sfn releases/<sha> current_tmp && mv -Tf current_tmp current`).
9. Reload PHP-FPM (`systemctl reload php8.5-fpm`) so OPcache (validate_timestamps=0) picks up the new realpath.
10. `php artisan queue:restart` — workers finish their current job and restart on the new code; jobs carry ids, not model graphs, so in-flight jobs from the previous release remain compatible.
11. Scheduler needs no restart (cron runs `schedule:run` each minute from `current`); `onOneServer` locks prevent duplicate runs during overlap.

## 3. Verify
12. `curl -f https://host/health` and `curl -f https://host/health/ready` (expects `{"status":"ok",…}`).
13. Smoke tests: `/`, `/services`, one service page, one product page, `/search?q=software`, `/contact` (form renders), `/sitemap.xml`, `/robots.txt`, `/admin` login, Editorial desk, Review queue, Content health.
14. Check response headers on a public page: `Content-Security-Policy`, `X-Request-Id`, `Cache-Control: no-cache, private`; on `/build/*`: `immutable`.
15. Tail logs for 10 minutes: `storage/logs/laravel-*.log`, `queue:failed` empty, no scheduler failure entries.

## 4. Rollback
- Repoint `current` to the previous release, reload FPM, `php artisan queue:restart`, `php artisan optimize:clear && php artisan optimize` in that release.
- Migrations are additive, so the previous release runs against the new schema; only roll a migration back if it was the cause and a backup exists.
- Keep at least three releases on disk.

## Zero-downtime notes (application side)
- Assets are versioned (`/build/<hash>`), old assets remain until the release directory is pruned, so open pages keep working during the switch.
- Sessions and cache live in Redis/database, not in the release directory.
- Queue jobs carry ids only; workers are restarted gracefully.
- True zero downtime also depends on the load balancer draining connections; this checklist only guarantees the application side.
