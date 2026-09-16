# MARKEDGE TECHNOLOGIES — PHASE 0 PROJECT AUDIT

Audit date: 2026-09-16
Repository: `/home/administrator/Documents/website/markedge`
Scope: read-only inspection. No application code was modified. The only file created is this document.

---

## 0. Executive Summary

The repository is a **fresh, unmodified Laravel 13 skeleton**. Nothing Markedge-specific exists yet: no domain models, no migrations beyond the framework defaults, no Filament, no Livewire, no Alpine, no public views beyond the stock welcome page, and no git history.

This is the best possible starting position for the Master Build Prompt. There is nothing to refactor and nothing to preserve except the framework scaffolding, which is current and correctly configured for PHP 8.5 and MySQL 8.4.

Key facts:

| Item | Finding |
|---|---|
| Framework | Laravel 13.32.0 (installed), constraint `^13.17` |
| PHP | 8.5.4 CLI (composer.json allows `^8.3`) |
| Database | MySQL 8.4.11, database `markedge` exists and is **empty** (0 tables, migrations never run) |
| Admin panel | Not installed (Filament absent) |
| Livewire / Alpine | Not installed |
| Tailwind | v4 via `@tailwindcss/vite` |
| Vite | v8 with `laravel-vite-plugin` v3 |
| Tests | Pest 5, 2 stock example tests, both passing |
| Version control | **Not a git repository** |
| Redis | PHP `redis` extension not loaded, no Redis server running |

---

## 1. Current Technology Stack

### Runtime

| Component | Version | Notes |
|---|---|---|
| PHP | 8.5.4 (NTS) | Extensions present: `pdo_mysql`, `gd`, `imagick`, `intl`, `mbstring`, `exif`, `zip`. **Missing: `redis`, `bcmath`.** |
| Composer | 2.9.5 | |
| Node.js | 22.22.1 | |
| MySQL | 8.4.11 (Ubuntu) | Local server, database `markedge` created and reachable |
| Redis | not installed | Neither server nor PHP extension |

### Composer dependencies (direct)

| Package | Installed | Purpose |
|---|---|---|
| laravel/framework | 13.32.0 | Core |
| laravel/tinker | 3.0.2 | REPL |
| laravel/boost (dev) | 2.9.0 | AI tooling / MCP |
| laravel/pail (dev) | 1.2.7 | Log tailing |
| laravel/pao (dev) | 1.1.5 | Agent-optimised test output |
| laravel/pint (dev) | 1.32.1 | Formatter |
| pestphp/pest (dev) | 5.2.0 | Test runner |
| pestphp/pest-plugin-laravel (dev) | 5.0.1 | |
| fakerphp/faker (dev) | 1.24.1 | |
| mockery/mockery (dev) | 1.6.15 | |
| nunomaduro/collision (dev) | 8.9.5 | |

No production packages beyond the framework. No Filament, Livewire, Spatie or media packages.

### NPM dependencies

| Package | Constraint | Purpose |
|---|---|---|
| tailwindcss | ^4.0.0 | Utility CSS (v4, CSS-first config) |
| @tailwindcss/vite | ^4.0.0 | Tailwind Vite integration |
| vite | ^8.0.0 | Bundler |
| laravel-vite-plugin | ^3.1 | Includes Bunny font helper |
| concurrently | ^10.0.3 | Used by `php artisan dev` |
| @laravel/multiplex (optional) | ^0.4.1 | Dev process multiplexer |

`package.json` uses `"type": "module"`. No Alpine, no Livewire, no animation or icon libraries.

### Package availability check (Packagist, verified 2026-09-16)

These are **not installed**; they are the latest stable releases available for Phase 2 planning:

| Package | Latest stable |
|---|---|
| filament/filament | v5.8.2 |
| livewire/livewire | v4.4.5 (Filament 5 depends on Livewire 4) |
| spatie/laravel-permission | 8.3.0 |
| spatie/laravel-medialibrary | 11.23.8 |
| spatie/laravel-sitemap | 8.2.0 |
| spatie/laravel-activitylog | 5.1.1 |

Compatibility with Laravel 13 must be confirmed at install time in Phase 2 (`composer require` will resolve or fail explicitly).

---

## 2. Existing Application Architecture

Stock Laravel 13 structure. Nothing custom.

```text
app/
  Http/Controllers/Controller.php      (empty abstract base)
  Models/User.php                       (stock, uses PHP attributes #[Fillable] / #[Hidden])
  Providers/AppServiceProvider.php      (empty register/boot)
bootstrap/
  app.php                               (stock: web routes, console routes, /up health check, JSON for api/*)
  providers.php
config/                                 (9 stock files: app, auth, cache, database, filesystems, logging, mail, queue, session, services)
routes/
  web.php                               (single closure route: GET / -> welcome view)
  console.php                           (stock inspire command)
database/
  migrations/                           (3 stock: users/password_resets/sessions, cache, jobs)
  factories/UserFactory.php
  seeders/DatabaseSeeder.php            (creates test@example.com)
resources/
  css/app.css                           (Tailwind v4 import + Instrument Sans theme token)
  js/app.js                             (empty)
  views/welcome.blade.php               (stock Laravel welcome page)
tests/
  Pest.php, TestCase.php, Feature/ExampleTest.php, Unit/ExampleTest.php
```

Observations:

- `bootstrap/app.php` has **no custom middleware** registered. Redirect middleware, attribution middleware, secure headers and preview middleware will all be new.
- There is **no `routes/api.php`** and no API scaffolding. Fine for this project; public site is server-rendered.
- The `User` model uses the Laravel 13 attribute style (`#[Fillable]`, `#[Hidden]`). New models should follow the same convention.
- No service classes, actions, policies, events, jobs or enums exist.

---

## 3. Existing Database Structure

### Configuration

| Setting | Value |
|---|---|
| `DB_CONNECTION` | `mysql` |
| `DB_DATABASE` | `markedge` |
| `CACHE_STORE` | `database` |
| `SESSION_DRIVER` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `FILESYSTEM_DISK` | `local` |
| Test DB (phpunit.xml) | `sqlite` `:memory:` |

### State

- MySQL connection works. Database `markedge` contains **0 tables**.
- `php artisan migrate:status` reports "Migration table not found". **Migrations have never been run.**
- A leftover `database/database.sqlite` file exists from the skeleton's default. It is unused now that the connection is MySQL. It is gitignored by `database/.gitignore`.

### Existing migrations (stock only)

| Migration | Tables |
|---|---|
| `0001_01_01_000000_create_users_table` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table` | `jobs`, `job_batches`, `failed_jobs` |

These are needed (auth, database cache, database queue, database sessions) and should be kept.

### Note on MySQL vs SQLite in tests

Feature tests run on in-memory SQLite. Any future use of MySQL-only features (full-text indexes, JSON functions, generated columns) must be written so migrations still run on SQLite, or the test connection must be switched to MySQL. This is a Phase 2 decision (see section 14).

---

## 4. Existing Routes

`php artisan route:list --except-vendor` shows exactly one application route:

| Method | URI | Handler |
|---|---|---|
| GET | `/` | Closure returning `welcome` view |

Plus the framework health check at `/up` and Boost's local dev routes (vendor). No `login` route exists, so the welcome page's auth links are hidden.

---

## 5. Existing Filament Resources

**None.** Filament is not installed. No panel provider, no resources, no admin routes, no admin user.

---

## 6. Existing Frontend

- `resources/views/welcome.blade.php`: the stock Laravel welcome page. It is not reusable for Markedge and will be replaced.
- `resources/css/app.css`: Tailwind v4 CSS-first config. Defines only `--font-sans: 'Instrument Sans'`. Contains a `@source` directive for pagination views and compiled Blade views. This file is the correct place for the Markedge design tokens (charcoal/orange palette, type scale, spacing) in Phase 3.
- `resources/js/app.js`: empty.
- `vite.config.js`: stock, plus Bunny Fonts loader for Instrument Sans (400/500/600). Font choice is a placeholder and can be changed in Phase 3.
- No layouts, no components, no partials, no Livewire components, no Alpine.

---

## 7. Existing Packages (summary)

See section 1. Only the framework and dev tooling are present. Everything the Master Build Prompt requires at the package level must be added:

- Admin: Filament 5 (pulls in Livewire 4, Alpine via Filament assets for the admin only).
- Public site interactivity: Livewire 4 (shared with Filament) and Alpine.js (needs explicit inclusion for the public bundle).
- Authorization: Spatie Permission (recommended; Filament has first-party integration patterns).
- Candidates for later phases, to be approved before install: media library, sitemap, activity log, sluggable, HTML purifier for rich text.

---

## 8. Existing Assets

| Path | Content |
|---|---|
| `public/build/` | A committed-looking Vite build of the stock CSS/JS plus Instrument Sans font files. Gitignored, so it is a local build artefact only. |
| `public/favicon.ico` | Stock Laravel favicon (placeholder). |
| `public/robots.txt` | Static `User-agent: * / Disallow:` (allows all). Will be replaced by a dynamic route in Phase 6. |
| `public/.htaccess`, `public/index.php` | Stock. |
| `storage/app/public` | Exists but `public/storage` symlink is **not** created yet. |

No Markedge logo, brand assets, product screenshots or images are present anywhere in the repository. These must be supplied by Markedge and are treated as `[PLACEHOLDER]` until then.

---

## 9. Existing Reusable Components

**None.** No Blade components, no Livewire components, no Filament components, no helpers, no traits, no service classes.

---

## 10. Potential Conflicts

1. **No git repository.** Phase requirement 48.12 ("Commit the phase") cannot be met until `git init` is run. Recommended: initialise git before Phase 1 documentation is committed. `.gitignore` is already correct.
2. **`.env` values are still defaults**: `APP_NAME=Laravel`, `APP_URL=http://localhost:8000`, `MAIL_FROM_ADDRESS=hello@example.com`. Must be updated (Phase 2 at the latest) so seeded settings, mail and URLs are not wrong.
3. **PHP version constraint** in `composer.json` is `^8.3` while the project mandates 8.5+. Harmless, but should be tightened to `^8.5` in Phase 2 so a deployment on an older PHP fails early.
4. **Redis absent.** The prompt says "Redis where useful". The current cache/session/queue drivers are `database`, which is a valid production configuration for a marketing site of this scale. Redis should be treated as an optional, later optimisation and never a hard dependency. No code should assume the `redis` extension exists.
5. **SQLite in tests vs MySQL in production.** Full-text search (planned for site search) and JSON column features differ. Migrations must guard MySQL-only statements, or tests must use MySQL. Decision deferred to Phase 2.
6. **Stock `welcome.blade.php` and `ExampleTest`.** The feature test asserts `GET /` returns 200. When the homepage becomes a CMS-driven page, this test must be rewritten, not deleted.
7. **Leftover `database/database.sqlite`.** Harmless but confusing. Recommend deleting it once the team confirms nobody uses it.
8. **Package compatibility with Laravel 13.** Filament 5.8, Livewire 4.4 and the Spatie packages are current, but their Laravel 13 support must be verified by the actual `composer require` in Phase 2. If any refuses to resolve, that is a blocking dependency decision and must be raised before proceeding.
9. **Bunny Fonts / Instrument Sans.** Fine as a placeholder, but the Phase 3 design system should decide on the final typeface deliberately. Self-hosting via the existing Vite fonts plugin is already wired up and should be kept for performance and privacy.

---

## 11. What Can Be Reused

Everything present is reusable framework scaffolding:

- Laravel 13 skeleton, `bootstrap/app.php`, all config files.
- The three stock migrations (users, cache, jobs). The `users` table will become the Filament admin user table.
- `User` model and factory (extend with roles in Phase 2, do not replace).
- Pest setup, `TestCase`, `phpunit.xml`.
- Tailwind v4 + Vite v8 pipeline and the self-hosted font mechanism.
- Boost tooling, Pint config, `.editorconfig`.
- `DatabaseSeeder` structure (its test user must be replaced with a proper admin seeder).

---

## 12. What Should Be Refactored

Nothing substantive exists to refactor. The only clean-ups:

| Item | Action | Phase |
|---|---|---|
| `routes/web.php` closure route | Replace with controller-backed routes | 5 |
| `welcome.blade.php` | Remove once the CMS homepage exists | 5 |
| `tests/Feature/ExampleTest.php` | Rewrite as a homepage test against real content | 5 |
| `DatabaseSeeder` test user | Replace with admin role and user seeder | 2 |
| `.env` defaults (`APP_NAME`, `APP_URL`, mail from) | Set real values | 2 |
| `composer.json` `php` constraint | Tighten to `^8.5` | 2 |
| `public/robots.txt` | Replace by dynamic route | 6 |
| `database/database.sqlite` | Delete | 2 |

---

## 13. What Needs to Be Created

Effectively the entire application. Grouped by phase:

### Phase 1 (documentation only)
- `MARKEDGE_ARCHITECTURE.md`: sitemap, ERD, models, relationships, routing, CMS block system, SEO/schema engine, product architecture, lead/attribution engine, component tree, Filament layout, permissions, caching, search, preview, governance.

### Phase 2 (database)
- Install Filament 5, Livewire 4, Spatie Permission (and any other approved packages).
- Migrations and models for: pages, page sections, navigation, service categories, services, products (with features/modules), solutions, industries, technologies, case studies, clients, testimonials, articles, categories, tags, authors, FAQs, forms, form fields, form submissions, leads, campaigns, landing pages, CTAs, redirects, media, SEO metadata (polymorphic), settings, social links, audit log, plus the pivot tables for internal linking.
- Enums for statuses.
- Factories for every model, seeders for roles, admin user, service categories and the two known products (names only, no fabricated content).

### Phase 3 (design system)
- Tailwind theme tokens, base layout, typography, buttons, cards, section wrappers, badges, form controls, header/footer shells, animation utilities, Alpine.js inclusion.

### Phase 4 (Filament CMS)
- Panel provider, all resources, relation managers, SEO form section, publishing/preview actions, dashboard widgets, role-aware navigation, policies.

### Phase 5 (public website)
- Controllers, route bindings on slugs, Blade component library, section block renderers, homepage, all listing and detail pages, contact.

### Phase 6 (SEO)
- Meta/OG/Twitter renderer with fallback hierarchy, canonical, robots, JSON-LD engine, breadcrumbs, sitemap, redirect middleware, noindex controls.

### Phase 7 (lead engine)
- Livewire form renderer, lead storage, attribution middleware/cookie, notifications, rate limiting, honeypot, duplicate handling, admin lead management.

### Phase 8 to 12
- Landing page engine, analytics tag injection from settings, performance work (image conversion, caching, indexes), security hardening (headers, upload validation, HTML sanitising), final QA.

---

## 14. Recommended Implementation Order

The Master Build Prompt's phase order is sound for this repository. Two adjustments are recommended:

1. **Initialise git before Phase 1 output is written**, so the audit and architecture documents form the first commits and every later phase can be committed as required.
2. **Install Filament, Livewire and Spatie Permission at the start of Phase 2**, not Phase 4. The `users` table, role tables and Filament's expectations affect migrations, so the dependency must be resolved before the schema is finalised. This also surfaces any Laravel 13 compatibility problem at the earliest possible moment.

Decisions to confirm before or during Phase 2 (none block Phase 1):

| Decision | Recommendation |
|---|---|
| Test database | Keep in-memory SQLite for speed, write migrations to be driver-safe (guard full-text indexes with a driver check). Revisit if search tests need MySQL. |
| Redis | Optional. Design cache tags/keys against Laravel's cache abstraction so switching `CACHE_STORE` to Redis later is config-only. |
| Media handling | Decide between a lightweight custom `media` table with Intervention/Imagick conversions and `spatie/laravel-medialibrary`. Recommendation to be argued in the architecture document. |
| Roles/permissions | Spatie Permission with Filament policies. |
| Rich text sanitising | HTML purifier package or Filament's rich editor with a strict allow-list. |

Proposed order:

```text
git init  →  Phase 1 Architecture  →  Phase 2 Database (+ core packages)
→  Phase 3 Design System  →  Phase 4 Filament CMS  →  Phase 5 Public Website
→  Phase 6 SEO  →  Phase 7 Lead Engine  →  Phase 8 Landing Pages
→  Phase 9 Analytics  →  Phase 10 Performance  →  Phase 11 Security  →  Phase 12 QA
```

---

## 15. Verification Performed During This Audit

| Check | Result |
|---|---|
| `php artisan test --compact` | 2 tests, 2 passed |
| `php artisan migrate:status` | Migration table not found (never migrated) |
| MySQL connectivity via tinker | Connected to `markedge`, 0 tables |
| `php artisan route:list --except-vendor` | 1 route (`GET /`) |
| `composer show --direct` | Listed in section 1 |
| `ls vendor` / `ls node_modules` for filament, livewire, spatie, alpine | None present |
| Packagist lookup for candidate packages | Versions listed in section 1 |

No files other than `MARKEDGE_PROJECT_AUDIT.md` were created or changed.
