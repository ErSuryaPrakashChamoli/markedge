# Markedge — Phase 11 Implementation: Production Deployment, Real-Infrastructure Validation & Go-Live

## 1. Executive Summary

**Markedge is not deployed to production.** No production infrastructure exists or was provided for this phase: this workstation has no root/sudo, no nginx, PHP-FPM or Redis, no public domain, DNS, certificate, SMTP credentials, CDN account or monitoring service, and no remote host or cloud credentials were found anywhere in the environment. Rather than fabricate a go-live, Phase 11 delivered everything that can be executed truthfully:

- A complete, repeatable **staging rehearsal on this host** under production settings (`APP_DEBUG=false`, cached config/routes/views, least-privilege database user, daily rotated logs at warning level, CSP on): deployed through the new release-directory `deploy.sh`, served by PHP's built-in server (the only web tier available without root), with a systemd-managed queue worker, cron-driven scheduler, cron-driven monitor and cron-driven backups.
- A **real backup and a real restore rehearsal** (dump → separate database → application smoke test), a controlled **queue failure** with retries and failed-job visibility, **scheduled publish/unpublish through cron**, cache invalidation across content/SEO/menu/CTA/revision, **personalisation isolation** between two visitors, security headers and error pages, exposed-file probes, SEO/sitemap/robots/redirect checks on an indexable environment, **browser-driven Chromium verification** of Livewire and Alpine interactions (mobile drawer, search, CTA redirect, lead form with consent), admin role authorisation including login throttling, a real lead captured end to end with attribution, consent and conversion events, accessibility checks, Lighthouse on the indexable site (SEO 100), and a controlled load test.
- Production operations tooling committed without secrets: `.env.production.example`, `scripts/deploy/{deploy,rollback,smoke-test}.sh`, `scripts/ops/{backup,restore-rehearsal,monitor}.sh`, plus the Phase 10 nginx/PHP/FPM/MySQL references.

Status per the Phase 11 rule: **READY** (Phase 10) → **rehearsed on staging** (this phase) → **NOT DEPLOYED, NOT PRODUCTION-VALIDATED**. Everything below is labelled Staging (local host) or Not measured.

## 2. Starting Commit

`2625328` on `phase-10`; work on branch `phase-11`.

## 3. Final Commit

Reported in the delivery message (branch `phase-11`).

## 4. Production Environment

Not provisioned. Requirements are documented: Ubuntu LTS host (or container), nginx, PHP 8.5 FPM, MySQL 8.4, Redis 7, Supervisor/systemd, cron, object storage optional, CDN optional. **Staging rehearsal host:** Ubuntu 26.04.1, 8 vCPU, 15 GB RAM, PHP 8.5.4 CLI with OPcache, MySQL 8.4.11 (local), no Redis, no root.

## 5. Server Configuration

Not measured on production. Staging: release layout `~/markedge-staging/{releases/<stamp>,current,shared/{.env,storage}}` created by `deploy.sh`; shared `.env` mode 600; `storage/` shared across releases; `public/storage` symlinked; two releases retained after a deploy + rollback rehearsal. Hardening items that require root (firewall, SSH policy, service users, unattended upgrades) are documented in §20 and were **not applied**.

## 6. Nginx

Not deployed (no root). Reference configuration: `docs/production/nginx.conf.example` (public root only, immutable `/build`, gzip/Brotli, upload limit, PHP-FPM socket, hidden-file deny, HTTP→HTTPS). Staging used `php artisan serve`; exposed-file probes there returned 404 for `/.env`, `/.git/HEAD`, `/composer.json`, `/artisan`, `/vendor/autoload.php` and 403 for `/storage/logs/laravel.log` because only `public/` is served, but the nginx rules remain **unverified** on a real server.

## 7. PHP-FPM

Not deployed (no FPM binary, no root). Reference pool: `docs/production/php-fpm-pool.conf.example`. Staging ran PHP 8.5.4 CLI server; OPcache enabled for web requests (`opcache.enable=1`, CLI off).

## 8. PHP Extensions

Present on the rehearsal host: pdo_mysql, mysqli, mbstring, openssl, tokenizer, xml, ctype, json, fileinfo, intl, gd, imagick (AVIF-capable), exif, sodium, zip, opcache. **Missing:** `redis` (phpredis) and `bcmath` (not required by the application). Production must add `php8.5-redis` when Redis is used.

## 9. MySQL

Staging: MySQL 8.4.11, `utf8mb4` server charset, strict SQL mode (`ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`), `max_connections=151`, server collation `utf8mb4_0900_ai_ci` (the application database is created with `utf8mb4_unicode_ci` as configured). Dedicated least-privilege user `markedge_app@127.0.0.1` with `SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, REFERENCES, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES` on `markedge_staging.*` only (verified: sees only its database, no PROCESS/SUPER). The application never used root. Migrations: 38 applied, 0 pending after deploy; no migration was created in Phases 10–11.

## 10. Redis

**Not tested.** No Redis server or phpredis on this host. Staging used the database drivers for cache, queue, sessions and rate limiting (atomic locks via `cache_locks` verified through `onOneServer` scheduler runs). Production configuration and checks are in `.env.production.example` and `markedge:env-check` (pings Redis when any driver references it).

## 11. Application Configuration

Staging `.env` (no secrets committed): `APP_ENV=staging` (the environment gate correctly refused `production` without HTTPS and secure cookies: `APP_URL must be https`, `SESSION_SECURE_COOKIE=true`), `APP_DEBUG=false`, generated `APP_KEY`, `LOG_STACK=daily`, `LOG_LEVEL=warning`, `LOG_DAILY_DAYS=14`, database cache/queue/session, `MAIL_MAILER=log`, `MARKEDGE_INDEXABLE=true` (staging is not public), CSP on, HSTS off (no TLS). `php artisan optimize` produced config, route, view, event, blade-icons and Filament caches in the release. `markedge:env-check` passed for staging. `.env.production.example` documents every production value with placeholders.

## 12. Domain & DNS

**Not configured / Not measured.** No domain exists; the only hostname in the repository is the `markedge.example` placeholder. Canonical-host behaviour was verified only in that requests via `localhost:8081` still emit the configured canonical (`http://127.0.0.1:8081`); www/apex redirects belong to nginx/CDN and are documented in `docs/production/nginx.conf.example`.

## 13. SSL/TLS

**Not measured.** No certificate. HSTS stays disabled (`MARKEDGE_HSTS=false`) until HTTPS is permanently enforced; the header logic is covered by tests. Cookies on staging: `mk_attr` and session are `HttpOnly; SameSite=Lax`; `Secure` is absent by design over HTTP and becomes mandatory via `SESSION_SECURE_COOKIE=true` in production (enforced by `env-check`).

## 14. CDN/Cloudflare

**Not configured.** Rules documented in `docs/production/cdn-and-caching.md`. Application-side guarantees verified on staging: `/go/*` and admin `no-store`, public HTML `no-cache, private`, personalised responses never shared-cacheable (§18, §23).

## 15. Storage

Staging: local public disk. A 920 kB JPEG uploaded to a service hero collection was processed by the systemd worker into `thumb/card/hero` WebP (hero 111 kB) and `thumb_avif/card_avif/hero_avif` (hero 68 kB), dimensions `2000×1250` stored, files `644` under `storage/app/public` (`775` directory, owner-only writable), `hero_avif` served over HTTP as `image/avif`. Object storage: not configured; migration path in the CDN document.

## 16. Queue Workers

Staging: `~/.config/systemd/user/markedge-queue.service` running `queue:work database --tries=3 --max-time=3600 --timeout=90` with `Restart=always`. Verified: jobs execute (search sync, editorial notifications, lead notification, media conversions); `queue:restart` recycled the worker (new PID, `NRestarts` incremented) and a job dispatched afterwards was processed; deploy script restarts workers. Production must use root-level systemd/Supervisor units under the application user (documented in `php-fpm-pool.conf.example`).

## 17. Scheduler

Staging: user crontab `* * * * * php artisan schedule:run`. Verified 13 consecutive minute runs in `scheduler.log`; a service scheduled for +50 s was published by the next run (`published (scheduled)` at 03:59:02) and appeared in search and sitemap; a published service with `unpublish_at` +50 s was taken offline in the same run (`unpublished (expired)`), removed from search and sitemap, 404 publicly. Exactly one audit entry per transition (no duplicate publication); all schedule entries are `withoutOverlapping` + `onOneServer` + `onFailure` logging (Phase 10).

## 18. Cache

Staging validation, all reflected on the very next public request: content edit (name), SEO description, header menu item, CTA destination (`/go/talk-to-us` → new target), revision restore (page went to draft/404 as designed), republish (restored content live). Personalised CTA responses carry `Cache-Control: no-cache, private`.

## 19. Security Headers

Verified on staging responses: `Content-Security-Policy` with per-response nonce (scripts carry it; Livewire, Alpine, forms, navigation and the lead form worked under it in Chromium with no console errors), `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN`, `Permissions-Policy`, `X-Request-Id`; admin `Cache-Control: … no-store, private`. HSTS: not measured (no TLS). No weakening was needed.

## 20. Firewall/Server Hardening

**Not applied** (no root). Required on production and documented: `ufw` allowing 22/80/443 only, SSH key-only with root login disabled, MySQL and Redis bound to localhost/private network with authentication, unattended security upgrades, separate deploy user and `www-data` runtime user, `storage/` and `bootstrap/cache` writable by the runtime user only, project directory not world-writable.

## 21. Health Checks

Staging: `/health` → `{"status":"ok"}`; `/health/ready` → `{"status":"ok","checks":{"database":"ok","cache":"ok","queue":"ok"}}`, `no-store`, no cookies, no hosts/versions/paths. Dependency failure verified in the automated suite (mocked probe → 503 "degraded" without leaking the fake host name); a live dependency outage was **not** induced on staging.

## 22. Logging

Staging: `daily` channel, 14 days retention, `warning` level, deprecations off. The rehearsal lead's e-mail and phone appear in no log (`laravel-*`, `queue-worker`, `scheduler`, `monitor`, `serve`); the stored failed-job exception contained neither the database password nor the app key; the lead's activity entry carries no personal attributes. Note: with `LOG_LEVEL=warning` the `log` mail transport's debug entries are suppressed, which is correct for production (real SMTP is used there).

## 23. Monitoring

**PARTIAL.** `scripts/ops/monitor.sh` ran from cron every 5 minutes on staging and manually: checks readiness, 5xx/429 rates in the access log, failed jobs, scheduler entry, disk, backup age and certificate expiry; an induced outage (wrong port) produced an `ALERT readiness returned 000` entry and exit code 1. No external uptime/SSL/backup monitoring service exists; alert routing (`ALERT_MAIL`) is not connected. Production requires an external uptime monitor and log/alert shipping in addition to this script.

## 24. Mail

**Delivery not tested** (no SMTP credentials, no sender domain, so no SPF/DKIM/DMARC). Verified on staging through the `log` transport: the lead notification renders with subject `New enquiry: Phase 11 Rehearsal via General Enquiry`, correct recipient and sender, form and page in the body; the queued listener ran without failure.

## 25. Backup Strategy

Implemented on staging with `scripts/ops/backup.sh` (mysqldump `--single-transaction --quick --no-tablespaces`, gzip, optional `age` encryption, media tarball, `umask 077` → files `600`, directory `700`, 30-day retention, daily cron at 02:15). Three backups exist (`~/markedge-backups/db`). **Offsite copy and encryption were not exercised** (no object storage or key); the runbook `docs/production/backup-and-recovery.md` defines frequency, retention, encryption and offsite requirements.

## 26. Restore Test

**Performed.** Backup `markedge-20260917-092139.sql.gz` (taken 2026-09-17 09:21:39 IST from the source database) restored with `scripts/ops/restore-rehearsal.sh` into the separate database `markedge_restore`: restore 2 s, 64 tables, 38 migrations, 12 pages, 24 services, 2 products, 0 orphaned SEO or media rows; the application ran against the copy (`DB_DATABASE=markedge_restore`) with `/`, `/services`, a service page, search, sitemap and readiness all 200; relationship check (service → category, SEO row, media) passed. Total rehearsal 6 s. The same dump seeded the staging database. Problems: the first backup attempt used the app user without `--no-tablespaces` (warning) and wrote group-readable files; both fixed in the script.

## 27. Lead Capture Verification

Performed on staging with a real browser (Chromium via DevTools protocol): visited `/`, clicked a `/go` CTA, opened `/lp/phase-11-rehearsal`, filled name/email/phone/message, ticked consent, submitted through Livewire, saw the success state. Stored lead: form `general-enquiry`, landing page `phase-11-rehearsal`, conversion page `/lp/phase-11-rehearsal`, first touch Direct/Unknown landing `/`, last touch Direct/Unknown landing `/`, CTA `talk-to-us` credited (clicked within the window), consent recorded with statement, submission token set, IP not stored; events `form_submitted` and `lead_created`; honeypot untouched. The lead, its events and clicks were deleted afterwards. Idempotency and duplicate policy remain covered by the Phase 8 suite.

## 28. CTA Verification

Staging: tracked links present in header (`talk-to-us`), band/footer (`start-conversation`), service (`request-consultation`), product (`book-demo`), landing page and sticky mobile bar; `/go/{key}` recorded a click and redirected (302) to the resolved destination for each; unknown key → 404; a tampered `to=` parameter was ignored. Note: CTA destinations `/contact`, `/request-*` are **draft pages in the seeded data** — publishing them is a go-live content task. "Redirect still works when recording fails" is covered by the automated suite only.

## 29. Search Verification

Staging: common query (results), 2-character query (title match), no-result query, `type=service`, `category=technology`, `page=2` (out-of-range state), SQL-injection and XSS payloads (no results, escaped), array parameter (302 validation), archived content absent, published rehearsal article found; burst of 35 requests → 429 from the search limiter. Query counts: 18 on results (§41).

## 30. SEO Verification

Staging (indexable): home `index, follow`, canonical, OG, description, schema `Organization/WebSite(SearchAction)/WebPage`; service page adds `BreadcrumbList`, `Service`, `ImageObject`; product `SoftwareApplication`; article `Article` with `Person` author, dates and breadcrumbs; search `noindex, follow` with canonical `/search`; preview signed (403 when not signed/logged in; noindex/no canonical/no tracking covered by tests); archived service → 410.

## 31. Sitemap

Staging: 200, valid XML, 17 URLs, 0 duplicates, single host, `lastmod` on entries, no drafts, no archived, no `/search`, scheduled-then-published service added and expired service removed. Submission to webmaster tools: **not possible** without a domain.

## 32. Robots

Staging: allows `/`, disallows `/admin`, `/preview`, `/livewire`, `/styleguide`, `/go`, `/up`, `/health`, references the sitemap on the configured host. Production must set `MARKEDGE_INDEXABLE=true` only on the canonical live domain (the Phase 6 flag; `env-check` fails when unset in production).

## 33. Redirects

Slug-change and revision-restore redirects, chain collapsing, loop refusal, external allow-list and self-redirect refusal are covered by the Phase 6/9 suites (all green). On staging only CTA `/go` redirects and the archived 410 were exercised; Phase 6 CMS redirects were **not** exercised manually.

## 34. Admin Verification

Staging via Chromium: Super Admin logs in, dashboard widgets, leads, conversion reports, review queue, content health; Sales sees enquiries but is refused on content inventory, conversion reports and pages; Content Manager opens articles, refused on leads; Marketing Manager opens conversion reports; inactive user cannot log in; user without a role is refused; wrong password shows "These credentials do not match our records."; the sixth failed attempt shows "Too many login attempts". CSRF: a token-less POST to the Livewire endpoint returns 419. All temporary users were deleted.

## 35. Browser Verification

Chromium 3-way: desktop and mobile behavioural runs (navigation, drawer open/close with Escape, sticky mobile CTA, skip-link focus, search submit, `/go` redirect, Livewire form submission, lean vs Livewire bundle per page, no console errors other than the draft `/contact` 404 destination), admin run (§34), accessibility run (§44). **WebKit/Safari: not available** on this host → not verified.

## 36. Lighthouse

Lighthouse 12.8, mobile emulation, simulated throttling, headless Chromium, staging host (indexable), 2026-09-17 04:00–04:02 UTC, single run each (no cherry-picking):

| URL | Perf | A11y | BP | SEO | LCP | CLS | TBT | TTFB |
|---|---|---|---|---|---|---|---|---|
| `/` | 96 | 96 | 100 | 100 | 2.1 s | 0 | 100 ms | 80 ms |
| `/services/software-development` | 96 | 95 | 100 | 100 | 2.2 s | 0 | 80 ms | 80 ms |
| `/products/lead-management-system` | 97 | 95 | 100 | 100 | 2.1 s | 0 | 100 ms | 70 ms |
| `/insights/phase-11-rehearsal-article` | 97 | 95 | 100 | 100 | 2.1 s | 0 | 70 ms | 70 ms |

The Phase 10 SEO penalty (local noindex) is gone on the indexable staging site. Only failing binary audit: colour contrast (brand orange, retained per the design-owner decision).

## 37. Load Testing

Staging only (PHP built-in server, single process, same machine as the client), `scripts/perf/load-test.php`, concurrency 4, 120 requests: browse 24.0 req/s, p50 162 ms, p95 204 ms, p99 221 ms, 0 errors; mixed 27.5 req/s, p50 153 ms, p95 176 ms, p99 189 ms, 0 errors; CPU 9–16 % user, memory steady, MySQL max used connections 7. Not production capacity.

## 38. Data Privacy

Only the form's configured fields are stored; consent timestamp and statement recorded; IP not stored (`MARKEDGE_STORE_LEAD_IP=false`); user agent stored (documented Phase 8 behaviour); technical tab super-admin only; exports gated by `leads.export`; no personal data in any staging log or activity properties; backups `600` in a `700` directory. No new data collection was introduced.

## 39. Security Testing

Automated only: `composer audit` (no advisories), `npm audit --omit=dev` (0 vulnerabilities), tracked-file secrets scan (none; `.env` ignored, no keys/certificates tracked), header inspection, exposed-file probes, error-page leak scan, CSRF (419), rate limiting (429), login throttling, authorization matrix, personalisation isolation. **No professional penetration test and no TLS scan were performed.**

## 40. Deployment Procedure

`scripts/deploy/deploy.sh <tag>` (clone → link shared `.env`/storage → `composer install --no-dev` → `npm ci && npm run build` → `markedge:env-check` → `storage:link` + `optimize` → `migrate --force` → atomic symlink switch → FPM reload hook → `queue:restart` → optional health gate with automatic rollback → prune). Rehearsed twice on staging, including the health gate. Release strategy: development → review → tests → staging deploy + `smoke-test.sh` → backup → production deploy → health → smoke → monitor.

## 41. Rollback Procedure

`scripts/deploy/rollback.sh` repoints `current` to the previous release, reloads FPM, clears/rebuilds caches and restarts workers; rehearsed on staging (`20260917093702` → `20260917092251`, readiness 200 afterwards). Database restoration is a separate decision per the runbook; migrations are additive so the previous release runs on the newer schema.

## 42. Production Smoke Test

`scripts/deploy/smoke-test.sh` (public pages, search, sitemap, robots, health, 404, admin login, exposed files, CSP/nosniff/request-id/no-store/immutable headers). Manual checklist items (conversion, admin workflow, queue, scheduler, cache, database, Redis, backup) map to §16–§28 of this report and `docs/production/deployment-checklist.md`.

## 43. Known Limitations

- No production infrastructure: nothing is deployed, no domain, TLS, HSTS, CDN, Redis, SMTP, firewall or external monitoring was exercised.
- The staging web tier was PHP's built-in server, not nginx + PHP-FPM; nginx rules are reference-only.
- Restore rehearsal used a dump with no media files (source database had no uploads at backup time); media restore was verified only by directory copy.
- WebKit not tested; Chromium only.
- Seeded conversion pages (`/contact`, `/request-*`) are drafts; they must be published before go-live or CTA destinations 404.
- Brand-orange contrast finding retained (design owner decision pending).
- Staging services on this workstation were stopped after validation (systemd unit disabled, cron entries removed, server stopped); the release directory, shared `.env` (mode 600) and backups remain under the home directory for inspection.

## 44. Deferred Work

Provision production (host, domain, DNS, certificate, nginx/FPM/Redis/Supervisor per the reference files), run `deploy.sh` there, execute `smoke-test.sh`, connect SMTP with SPF/DKIM/DMARC and send a controlled lead, submit the sitemap to webmaster tools, connect an external uptime/SSL/backup monitor, rehearse backup encryption + offsite copy + restore on the real host, professional penetration test, WebKit verification, publish the seeded conversion pages.

## 45. Final Acceptance

**NOT APPROVED.** Deployment, domain, TLS, Redis, mail delivery, external monitoring and production validation criteria are unmet because no infrastructure exists. Every application-side criterion that can be validated without infrastructure was validated on staging and is repeatable with the committed scripts.
