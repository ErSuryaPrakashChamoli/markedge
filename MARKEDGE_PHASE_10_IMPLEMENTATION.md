# Markedge — Phase 10 Implementation: Performance, Scalability & Production Readiness

## 1. Executive Summary

Phase 10 made the existing platform measurably lighter and production-safe without touching the CMS, SEO, attribution, search or editorial architecture:

- JavaScript on pages without forms fell from 96.0 kB to 19.7 kB gzipped (lean Alpine entry); form pages keep Livewire (96.0 kB, unchanged by design).
- Public page database work fell by roughly half (home 50 → 23 queries, service detail 48 → 23 on the local MySQL stack) by memoising the content version, settings, CTA and menu resolvers per request; the database cache store no longer answers the same key twenty times per page.
- Images now ship as `<picture>` with AVIF and WebP variants, intrinsic width/height (CLS 0 in Lighthouse), lazy loading below the fold and brand-asset protection for logos and avatars.
- Only two font weights are preloaded; fonts remain self-hosted with `font-display: swap`.
- Security headers, a nonce-based Content-Security-Policy on the public site, request correlation ids, configurable trusted proxies, environment-driven rate limits, `/health` (liveness) and `/health/ready` (readiness) endpoints, and a `markedge:env-check` command that fails on unsafe production configuration.
- Queue jobs are idempotent with explicit retries, backoff and timeouts; scheduled commands run on one server, never overlap and log failures.
- Production reference configuration (nginx, PHP, PHP-FPM, MySQL), CDN rules, deployment checklist with rollback, backup and recovery runbook, and a dependency-free repeatable load test.
- 21 new regression tests (security, cache leakage, personalisation leakage, health, error pages, environment validation, query budgets, search at 10k/50k/100k entries, sitemap memory at 20k URLs, build composition, cache invalidation). Every Phase 1–9 test still passes.

No new packages beyond two pinned front-end dev dependencies (`alpinejs`, `@alpinejs/collapse` at the exact version Livewire bundles). No migrations. No framework, Filament, Livewire or CMS replacement.

## 2. Starting Commit

`c184915` (Phase 9 approved and closed). Work done on branch `phase-10`.

## 3. Final Commit

The Phase 10 commit on branch `phase-10` ("feat: complete phase 10 performance and production readiness"); hash in the delivery message.

## 4. Environment

All measurements in this document are **Local / production-like** unless stated: Ubuntu, PHP 8.5.4 (OPcache loaded, Imagick with AVIF), Laravel 13.32.0, MySQL 8.4.11, Node 24 / Vite 8, `php artisan serve` (single PHP process, no FPM, no nginx, no Redis; cache, queue and sessions on MySQL, `APP_ENV=local`, `MARKEDGE_INDEXABLE=false`). Automated tests run on SQLite in memory with the array cache. Nothing here is a production measurement.

## 5. Baseline Measurements

Build (before): CSS 68.1 kB (12.3 kB gzip); JS one entry `app.js` 298.2 kB (96.7 kB gzip) on every page; four font weights (2 × WOFF2 + WOFF each), all four preloaded.

Warm in-process requests on the local MySQL database (query count, wall time, SQL time), before Phase 10:

| Page | Queries | ms | SQL ms |
|---|---|---|---|
| `/` | 50 | 54 | 14 |
| `/services` | 35 | 32 | 8 |
| service category | 39 | 41 | 12 |
| service detail | 48 | 47 | 13 |
| product detail | 50 | 47 | 14 |
| solution detail | 44 | 46 | 14 |
| industry detail | 42 | 42 | 12 |
| `/insights` | 35 | 33 | 10 |
| page | 34 | 33 | 9 |
| `/search?q=software` | 45 | 40 | 13 |
| `/search` | 40 | 37 | 11 |
| `/sitemap.xml` | 4 | 3 | 1 |
| `/robots.txt` | 2 | 2 | 1 |
| admin dashboard | 4 | 97 | 2 |
| review queue | 6 | 111 | 3 |
| content inventory | 7 | 124 | 5 |
| content health | 22 | 120 | 20 |
| SEO manager | 51 | 125 | 19 |
| search index status | 38 | 103 | 12 |
| leads | 12 | 115 | 4 |
| conversion reports | 21 | 100 | 7 |
| media library | 12 | 106 | 3 |
| editorial desk | 12 | 125 | 5 |

Query breakdown showed 22–30 of the public-page queries were `select * from cache where key in (?)`: the content version, settings and CTA lookups were re-read for every injected resolver instance. Memory per public request was 0.5–1.1 MB above the booted application. No landing page or article was published locally, so those rows come from the automated budget test instead.

## 6. Frontend Optimization

Entry points are now chosen per page in [app.blade.php](resources/views/components/layouts/app.blade.php): if the rendered page contains a Livewire component (`wire:snapshot`), the Livewire entry is loaded; otherwise the lean entry. `@livewireStyles` and `@livewireScriptConfig` are emitted only with the Livewire entry. The shared site behaviours (navigation, accordion, counter, reveal) live in `resources/js/site.js` and register against whichever Alpine is present. The unused `tabs` module was removed.

## 7. Image Optimization

- [HasStandardImageConversions](app/Models/Concerns/HasStandardImageConversions.php) with [Conversions](app/Media/Conversions.php): photographic collections (hero, featured, gallery, blocks, screenshots, image) get WebP q82 at 400/800/1600 and, when Imagick reports AVIF support ([ImageFormats](app/Media/ImageFormats.php)), AVIF q65 at the same widths. Brand collections (logo, avatar) keep the original plus WebP thumb/card at q92 and never get AVIF. OG images stay JPEG. Originals are preserved.
- [StoreMediaDimensions](app/Listeners/StoreMediaDimensions.php) records intrinsic width and height on upload; [picture.blade.php](resources/views/components/ui/picture.blade.php) emits `<picture>` with AVIF and WebP sources, `srcset`/`sizes`, computed `width`/`height` for the requested variant, `loading="lazy"`/`decoding="async"` below the fold and `loading="eager" fetchpriority="high"` for hero images.
- Verified locally with Imagick: a 2400×1500 hero upload stored `2400x1500`, generated `thumb/card/hero` (WebP) and `thumb_avif/card_avif/hero_avif`; a logo upload generated only `thumb/card/og`. Byte sizes from that flat-colour test image are not representative and are not reported as savings.
- `IMAGE_DRIVER=imagick` is documented in `.env.example` (GD builds keep WebP only, silently).

## 8. CSS Optimization

Tailwind 4 CSS-first tokens untouched. Production CSS is 68.1 kB (12.3 kB gzip): one design-token file, no CMS-generated CSS, no inline style controls. Inspection found no duplicate declarations worth removing; the file is dominated by generated utilities that Tailwind already tree-shakes from Blade sources. Light and dark sections, forms, cards, grids, header, mobile drawer and admin surfaces were verified by screenshots and the existing view tests. No change was needed.

## 9. JavaScript Optimization

| Bundle | Before (gzip) | After (gzip) | Where |
|---|---|---|---|
| `app.js` (Livewire + Alpine + site) | 96.7 kB | 96.0 kB | pages with a Livewire form only |
| `lean.js` (Alpine core + collapse + site) | – | 19.7 kB + 0.9 kB shared chunk | every other page |

Alpine is never duplicated on a page: the lean entry ships standalone Alpine 3.17.3 (the exact version inside Livewire 4.4's bundle) and the Livewire entry ships Livewire's. The collapse plugin is included in both. Lighthouse total byte weight on the service page: 310 KiB (mobile, includes fonts and CSS).

## 10. Font Optimization

Instrument Sans stays self-hosted through the Vite fonts plugin (four weights, WOFF2 with WOFF fallback, `font-display: swap`, subsetting by unicode-range as generated). Preloading is now limited to weights 400 and 600 (body and headings); 500 and 700 load on demand. A test asserts at most two font preloads.

## 11. Core Web Vitals Work

Lighthouse 12.8 lab runs (mobile emulation, simulated 4G throttling, headless Chromium, local `artisan serve`):

| Page | Performance | Accessibility | Best practices | SEO | LCP | CLS | TBT | TTFB |
|---|---|---|---|---|---|---|---|---|
| `/` | 96 | 96 | 100 | 66* | 2.3 s | 0 | 80 ms | 100 ms |
| service detail | 96 | 95 | 100 | 66* | 2.2 s | 0 | 60 ms | 80 ms |
| product detail | 96 | 95 | 100 | 66* | 2.3 s | 0 | 70 ms | 110 ms |
| `/search?q=software` | 95 | 96 | 100 | 66* | 2.3 s | 0 | 120 ms | 80 ms |

\* SEO scores fail only `is-crawlable` because the local environment is deliberately `noindex` (`MARKEDGE_INDEXABLE=false`); this is Phase 6 behaviour and passes on an indexable environment. These are lab numbers from a development machine, not field Core Web Vitals; no production measurement exists yet.

LCP: hero images are eager with high priority, fonts preloaded selectively, CSS is one file. INP: pages without forms no longer parse Livewire; TBT ≤ 120 ms. CLS: intrinsic image dimensions, reserved placeholder aspect ratios, `font-display: swap` with metric-compatible fallbacks; the announcement bar and sticky CTA are rendered server-side, not injected.

## 12. HTTP Caching

- Application: [SecurityHeaders](app/Http/Middleware/SecurityHeaders.php) forces `Cache-Control: no-store, private` on `/admin`, `/livewire`, `/preview`, `/go`, `/health`, `/up` and on every authenticated response. Public HTML keeps `no-cache, private` (it varies by first-party cookies). Sitemap and robots keep `public, max-age=3600`. Redirects from `/go` carry `X-Robots-Tag: noindex, nofollow`.
- Server: [nginx.conf.example](docs/production/nginx.conf.example) serves `/build/*` with `public, max-age=31536000, immutable` (content-hashed file names), media with 30 days, gzip for text types and Brotli when the module exists, and hides `X-Powered-By`.
- CDN rules and why `Vary: Cookie` is not used: [cdn-and-caching.md](docs/production/cdn-and-caching.md).

## 13. Application Caching

Audit results: menus (arrays), settings (array), sitemap XML (string), content-health scan (arrays) and the content version are cached and keyed by the content version; no Eloquent model, collection, closure or service instance is ever cached (the Laravel 13 `serializable_classes` constraint from Phase 5 stands). Changes: `ContentVersion`, `Settings`, `CtaResolver` and `MenuBuilder` are now request-scoped bindings with in-instance memoisation, so a page reads the version once and each CTA key once. The settings and CTA memos are version-aware: a save in the same request bumps the version and the next read reloads (caught by the Phase 5 settings test during the regression run and fixed). `ContentVersion::bump()` re-reads before incrementing so concurrent processes never lose a bump. Invalidation paths (publish, unpublish, edit, restore, schedule, slug change, SEO change, relation change) all pass through model saves that bump the version; covered by Phase 9 lifecycle tests plus the new settings/menu invalidation test.

## 14. Redis

Not required locally (phpredis is not installed here; `redis-cli` absent), fully supported in production: set `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis`, `SESSION_DRIVER=redis`, `REDIS_CLIENT=phpredis` and the connection variables already present in `.env.example`. Rate limiting and scheduler locks use the cache store automatically (database supports atomic locks through `cache_locks`, so Redis is an upgrade, not a requirement). `markedge:env-check` pings Redis when any driver references it. If Redis is unavailable the framework raises connection exceptions; the application does not add a silent fallback because a half-working cache/queue is worse than a visible outage (readiness returns 503 and monitoring alerts).

## 15. Database Optimization

Index audit (`SHOW INDEX` on all 23 important tables): every publishable table has `(status, published_at)`, unique slugs, category/author composites on articles and services, ordering indexes, unique morph pairs on `seo_meta` and `search_entries`, the MySQL full-text index, reporting indexes on leads (created_at, status, sources, mediums, campaigns, conversion page, visitor), `cta_clicks` (cta+created_at, created_at, visitor), `conversion_events` (type+created_at, created_at, visitor, entity), `content_revisions` (unique subject+version, subject+created_at), `editorial_comments` (subject+resolved_at), `notifications` (notifiable), `media` (model, order), `menu_items`, `activity_log`. Every query pattern observed in the breakdowns is covered. **No index was added or removed** (adding would be redundant; nothing was proven unnecessary). No migration in Phase 10.

## 16. Query Budget Results

[QueryBudgetTest](tests/Feature/Performance/QueryBudgetTest.php) seeds a realistic catalogue and asserts budgets (SQLite, array cache; measured → budget): home 7 → 10, services index 6 → 9, category 10 → 14, service detail 36 → 40, product 21 → 28, solution 17 → 23, industry 16 → 21, case study 10 → 14, article 22 → 29, insights 10 → 14, contact 9 → 12, landing page 10 → 14, search results 6 → 9, empty search 4 → 6. Two further tests fail on repeated query shapes (N+1 guards) for the service page and home.

After Phase 10 on the local MySQL stack (same method as §5): home 23 (was 50), services 10 (35), category 14 (39), service detail 23 (48), product 21 (50), solution 19 (44), industry 17 (42), insights 10 (35), page 12 (34), search results 18 (45), empty search 15 (40). The remaining cache reads are settings, menus and the version, once each.

## 17. N+1 Audit

Public controllers already eager-load relations (Phase 5/7). The breakdown found no per-row lookups; the repeated shapes on a service page are one `seo_meta`/`media` eager load per related-content collection (services, products, solutions, industries, articles, case studies), which is by design and bounded (12 candidates per signal). Admin surfaces use SQL unions and aggregates (Phase 9). Campaign targeting resolves once per request (memo keyed on the request object).

## 18. Search Performance

[ScaleTest](tests/Feature/Performance/ScaleTest.php) seeds 10,000 / 50,000 / 100,000 search entries and asserts ≤ 2 search queries (count + `limit 10` page) and ≤ 25 total queries. Timings (SQLite LIKE fallback, local): 27 ms, 57 ms, 90 ms for a filtered, paginated two-token query. On MySQL the boolean full-text index handles tokens ≥ 3 characters (`innodb_ft_min_token_size`), shorter tokens fall back to title `LIKE`; results are always `LIMIT 10` with an indexed count. Reindex is chunked (500 by id, no CMS writes) and scheduled weekly at 03:00 on one server; sync jobs are unique-until-processing so bursts of edits collapse into one job per record.

## 19. Sitemap Performance

Generation uses `chunkById(500)` with `seo` eager-loaded, deterministic ordering by id, one canonical per record (canonicalized or noindex records are excluded by the Phase 6 resolver), cached as a string for 24 h keyed by the content version. Test: 20,002 URLs (20,000 services + indexes) render with a peak memory delta below 96 MB (measured ≈ 20–30 MB on SQLite; the assertion is generous to avoid brittleness) in correct order. The sitemap protocol caps a file at 50,000 URLs / 50 MB; when the published inventory approaches 40,000 URLs, introduce a sitemap index (`/sitemap.xml` listing `/sitemap-{type}-{n}.xml`) reusing the same chunked generator per type. Not added now: the catalogue is three orders of magnitude smaller.

## 20. CMS Performance

`ContentResolver`, `MetaResolver`, `SeoEngine`, `IndexabilityResolver`, schema builders, `RelatedContentResolver`, block hydrator, `MenuBuilder`, `CtaResolver`, `CampaignTargeting` were read against the query breakdowns. Findings and actions: resolvers were stateless but re-instantiated per injection (fixed with scoped bindings); `IndexabilityResolver::absolute()` reads settings (now one cached read per request); block hydrators eager-load media/category per block (kept). No resolver serialises models into the cache.

## 21. Filament Performance

Admin pages measured 4–51 queries and 97–125 ms locally (Filament boot dominates). SEO manager (51) and search index status (38) evaluate indexability per row over small tables; both are bounded by pagination / the fixed type list and were left unchanged. Leads export streams CSV per selected record; tables paginate; media library eager-loads owners. The inventory view was fixed for a PHP 8.5 deprecation (null collection key) surfaced during measurement.

## 22. Queue Architecture

| Job / listener | Queue | Tries | Backoff | Timeout | Idempotency |
|---|---|---|---|---|---|
| `SyncSearchEntry` (type, id) | default, after commit | 3 | 10 s, 60 s, 300 s | 60 s | unique until processing per `type:id`; re-reads state at run time |
| `SendLeadNotification` | default | 3 | 30 s, 120 s, 600 s | 60 s | loads lead by id; skips duplicates |
| `NotifyEditorialParticipants` | default | 3 | 30 s, 120 s, 600 s | 60 s | loads record by alias+id; notifications are additive |
| Media conversions (Spatie) | default (config) | Spatie default | – | – | regenerates the same file names |

Jobs carry ids only. Failed jobs land in `failed_jobs` (`queue:failed` / `queue:retry`), pruned weekly after 30 days. Run workers with `--max-time=3600 --timeout=90 --tries=3` under Supervisor/systemd and restart them on deploy (`queue:restart`).

## 23. Scheduler Reliability

[routes/console.php](routes/console.php): `content:publish-scheduled` (every minute), `markedge:events-prune` (daily), `content:expiring-reminders` (08:00), `queue:prune-failed` (weekly), `markedge:search-reindex` (Sunday 03:00). Every entry is `withoutOverlapping(30)`, `onOneServer()` (cache lock) and `onFailure(Log::error)`. Publishing and unpublishing are transactional with row locks, so a repeated run cannot double-publish (Phase 9 tests).

## 24. PHP Production Readiness

[php.ini.example](docs/production/php.ini.example) and [php-fpm-pool.conf.example](docs/production/php-fpm-pool.conf.example): OPcache with `validate_timestamps=0` and `save_comments=1`, JIT, 256 MB memory, 20 MB uploads, 60 s execution, realpath cache, `display_errors=Off`, `log_errors=On`, timezone equal to `APP_TIMEZONE`, required extensions, FPM `pm.dynamic` sizing formula and slow log.

## 25. MySQL Production Readiness

[mysql.cnf.example](docs/production/mysql.cnf.example): utf8mb4/unicode_ci, UTC server timezone, strict SQL mode, connection sizing, buffer pool, durability, `innodb_ft_min_token_size`, slow-query log, index monitoring via `sys` schema, online-DDL notes for large tables, dump + binlog backups. All existing migrations are additive; none is destructive.

## 26. Security Headers

Every response: `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `X-Frame-Options: SAMEORIGIN`, `Permissions-Policy` (camera, microphone, geolocation, payment, usb, interest-cohort off), `X-Request-Id`. HTTPS responses with `MARKEDGE_HSTS=true`: `Strict-Transport-Security: max-age=31536000; includeSubDomains`. Public HTML: nonce-based CSP — `default-src 'self'; script-src 'self' 'nonce-…' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src/media-src 'self' data: blob: + MARKEDGE_MEDIA_ORIGINS; font-src 'self' data:; connect-src 'self'; frame-src youtube/youtube-nocookie/vimeo; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'` (+ `upgrade-insecure-requests` on HTTPS). Vite and Livewire pick the nonce up automatically; the one inline script (landing-page dataLayer hook) carries it; JSON-LD data blocks are not executable and are unaffected. `'unsafe-eval'` remains because Alpine evaluates `x-data`/`x-on` expressions at runtime; the CSP-safe Alpine build is a documented follow-up. Admin, Livewire and preview routes receive no CSP (Filament ships inline scripts) but keep frame protection and `no-store`. `MARKEDGE_CSP_REPORT_ONLY=true` switches to report-only for rollout.

## 27. Rate Limiting

| Limiter | Default / min per IP | Env | Applied to |
|---|---|---|---|
| `search` | 30 | `MARKEDGE_RATE_SEARCH` | `/search` |
| `cta` | 60 | `MARKEDGE_RATE_CTA` | `/go/*` |
| `preview` | 60 | `MARKEDGE_RATE_PREVIEW` | `/preview/*` |
| `health` | 60 | `MARKEDGE_RATE_HEALTH` | `/health*` |
| `lead-form` | 5 | `markedge.leads.submissions_per_minute` | Livewire lead submission |
| Filament login | 5 attempts (built in) | – | `/admin/login` |

Normal browsing is not limited. Limits key on the client IP, which is only correct behind a proxy when `TRUSTED_PROXIES` is set ([TrustProxies](app/Http/Middleware/TrustProxies.php)). The load test confirms the search limiter returns 429 under burst (see §34).

## 28. Health Checks

`GET /health` → `{"status":"ok"}` (liveness). `GET /health/ready` → `{"status":"ok|degraded","checks":{"database","cache","queue"}}` with 200/503, probes in [HealthChecks](app/Health/HealthChecks.php). Responses are `no-store`, never set cookies, are excluded from attribution and robots, and contain no hosts, versions, paths or exception text (tested with a failing probe carrying a fake host name). `/up` (framework) remains for simple pings.

## 29. Logging & Observability

- `X-Request-Id` on every response and in the log context (`AssignRequestId`), honouring a well-formed inbound id from the CDN.
- Logged: application exceptions (with request id), failed jobs (`failed_jobs` + log), scheduled command failures (`Scheduled command failed: …`), scheduled publish failures (`scheduled publish failed` activity), search index and lead notification failures (`report()` in listeners), health probe failures, CTA click recording failures (redirect still served).
- Never logged: passwords, tokens, cookies, secrets, lead payloads (activity log only records workflow fields; `RecordsActivity` whitelists attributes).
- Recommended production env: `LOG_CHANNEL=stack`, `LOG_STACK=daily`, `LOG_LEVEL=warning`, `LOG_DEPRECATIONS_CHANNEL=null`; ship `storage/logs/laravel-*.log` to the log platform and alert on `failed_jobs` growth and `/health/ready` ≠ 200.

## 30. Backup & Recovery

[backup-and-recovery.md](docs/production/backup-and-recovery.md): what to back up (database with binlogs, media, code tags, secrets, infrastructure config), frequency, retention, encryption and offsite copies, restore procedures for database and media, and a restore-test log. **No backup exists yet and no restore has been tested**; the runbook says so explicitly.

## 31. CDN Readiness

[cdn-and-caching.md](docs/production/cdn-and-caching.md): cacheable paths (`/build`, `/storage`, sitemap/robots), never-cache paths (`/admin`, `/livewire`, `/preview`, `/go`, `/health`, `/search`), HTML only with cookie-bypass rules because of `mk_attr` and campaign CTA targeting, headers to preserve, purge strategy, trusted proxies and object storage.

## 32. Storage Readiness

Media URLs come from the configured disk (`MEDIA_DISK`), conversions can live on a separate disk (`MEDIA_CONVERSIONS_DISK`), the CSP image allow-list is configurable (`MARKEDGE_MEDIA_ORIGINS`), and no code path assumes the public disk. Moving to S3-compatible storage is a configuration change plus a one-off file copy; not performed in Phase 10.

## 33. Deployment Readiness

[deployment-checklist.md](docs/production/deployment-checklist.md): release-directory layout, `composer install --no-dev`, `npm ci && npm run build`, shared paths and `storage:link`, `markedge:env-check`, `optimize`, `migrate --force` before the switch, atomic symlink switch, FPM reload, `queue:restart`, health and smoke checks, header verification, rollback, and the application-side zero-downtime guarantees (versioned assets, id-only jobs, additive migrations). Destructive commands are called out with warnings.

## 34. Load Testing

[scripts/perf/load-test.php](scripts/perf/load-test.php) (curl_multi, no dependencies) with `browse`, `search`, `assets` and `mixed` profiles. Results on the local single-process `php artisan serve` (MySQL cache/queue/session, no OPcache warm-up control, same machine as the client):

| Profile | Concurrency | Requests | Throughput | p50 | p95 | p99 | Errors |
|---|---|---|---|---|---|---|---|
| browse | 8 | 160 | 18.5 req/s | 425 ms | 482 ms | 496 ms | 0 % |
| search | 4 | 100 | 25.5 req/s | 130 ms | 238 ms | 290 ms | 0 % (70 × 429 from the 30/min search limiter, as designed) |
| mixed | 8 | 160 | 22.2 req/s | 369 ms | 438 ms | 453 ms | 0 % (35 × 429 search limiter) |

Per-page p50 under the mixed profile: home 310 ms, services 332 ms, service detail 365 ms, product 393 ms, solution 412 ms, industry 411 ms, insights 413 ms, search 345–389 ms. Single warm requests: home 100 ms, services 45 ms, service detail 52 ms, product 59 ms, search 31 ms, sitemap 30 ms, readiness 37 ms. These numbers characterise the application on one development process; production capacity must be measured on the target infrastructure (FPM pool, nginx, Redis, OPcache) with the same script.

## 35. Accessibility Verification

Lighthouse accessibility 95–96 on home, service, product and search. Verified in HTML/screenshots: skip link, labelled search controls, `aria-label` on icon buttons, `role="search"`, semantic headings, focus-visible ring utilities, reduced-motion handling in the reveal module. One pre-existing finding, unchanged by Phase 10 and **not fixed here because it is a brand-colour decision**: white text on the brand orange `#f26522` (primary buttons, sticky CTA) measures 3.15:1 and orange eyebrow/badge text on white or `#f8f8f7` measures 2.96–3.15:1, below WCAG AA 4.5:1 for normal text. Options for the design owner: darken the button token to `orange-700 #b8431a` (≈ 5.1:1) or enlarge/embolden button text to the large-text threshold. Recorded as a known limitation.

## 36. Browser Verification

Headless Chromium (snap, `--headless=new`): service page screenshots at 375×812, 768×1024, 1280×800 and 1920×1080 show header, breadcrumbs, hero, CTA band, footer and sticky mobile CTA laid out without horizontal overflow; DOM checks confirm the skip link, search and menu controls. Lighthouse runs above cover navigation, images, fonts and script execution. **Not browser-tested**: Safari/WebKit (not available on this machine), Livewire form interaction and Alpine drawer interaction in a real browser (covered by Livewire and view tests only), dark-mode toggling (the site uses section-level themes, not a user toggle). Status: PARTIAL.

## 37. Test Results

Full suite: 442 tests, 1,919 assertions, all passing (SQLite in memory); `vendor/bin/pint --test` passes; `npm run build` passes (CSS 68.1 kB / 12.3 kB gzip, `lean.js` 55.8 kB / 19.7 kB gzip, `app.js` 296.0 kB / 96.0 kB gzip, shared `site` chunk 2.0 kB / 0.9 kB gzip). Laravel log: the only local errors during the phase were the trait-constant defect in the picture component, fixed before commit; test-log entries (`testing.ERROR`) are deliberate failures raised by the error-page, readiness and notification-failure tests. New tests: `tests/Feature/Production/SecurityHeadersTest.php` (10), `tests/Feature/Production/BuildAndCacheTest.php` (4), `tests/Feature/Performance/QueryBudgetTest.php` (3), `tests/Feature/Performance/ScaleTest.php` (4 incl. dataset cases).

## 38. Known Limitations

- Lab-only performance data; no production or field measurements exist.
- CSP keeps `'unsafe-eval'` for Alpine and `'unsafe-inline'` for styles; admin has no CSP.
- Brand-colour contrast below AA for button and eyebrow text (pre-existing).
- Rate limits are per IP; shared-NAT visitors share a limit.
- Redis not exercised locally (phpredis absent).
- Sitemap index not implemented (documented threshold).
- Load test is single-process local; the 429s show limiter behaviour, not capacity.

## 39. Deferred Work

CSP-safe Alpine build and admin CSP; sitemap index when the inventory nears 40k URLs; object-storage migration; field Core Web Vitals collection (first-party only, per the analytics boundary); Brotli at the edge; brand token contrast decision; first backup and restore rehearsal on real infrastructure.

## 40. Production Checklist

See [deployment-checklist.md](docs/production/deployment-checklist.md) and the environment variables in `.env.example` (`TRUSTED_PROXIES`, `MARKEDGE_HSTS`, `MARKEDGE_CSP`, `MARKEDGE_CSP_REPORT_ONLY`, `MARKEDGE_MEDIA_ORIGINS`, `MARKEDGE_RATE_*`, `IMAGE_DRIVER`, `MARKEDGE_INDEXABLE`, Redis drivers). Gate every deploy on `php artisan markedge:env-check` and `/health/ready`.

## 41. Final Acceptance Status

All acceptance criteria in the Phase 10 brief are met with the limitations stated in §38 (lab-only measurements, PARTIAL browser verification without WebKit, pre-existing brand-colour contrast, no backup rehearsal yet on real infrastructure). Phase 10 is delivered on branch `phase-10`; the commit hash is reported in the delivery message.
