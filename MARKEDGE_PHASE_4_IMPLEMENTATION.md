# MARKEDGE — PHASE 4 IMPLEMENTATION REPORT

## Filament CMS & Admin Control Center

Date: 2026-09-16
Stack: Laravel 13.32 · PHP 8.5 · Filament 5.8 · Livewire 4.4 · Spatie Permission 8.3 · Spatie Media Library 11.23 · Spatie Activitylog 5.1

---

## 1. Summary

Phase 4 turns the Filament panel at `/admin` into the operational control centre described in `MARKEDGE_ARCHITECTURE.md` §25. Every Phase 2 domain model that an administrator needs to manage now has a resource, the typed block system from §6 has its admin editor, and publishing, preview, SEO, media, leads, marketing, settings, users, roles and the audit trail are all manageable without developer involvement.

| Area | Delivered |
|---|---|
| Resources | 28 (100 files) across 12 navigation groups |
| Reusable Filament helpers | 11 support classes + 2 generic relation managers |
| Custom pages | Global Settings, SEO Manager, Lead Sources |
| Dashboard widgets | 5, each gated by policy and showing real data only |
| Block editor | 27 whitelisted block types with per-block validation contracts |
| Workflow | Draft → Review → Scheduled / Published → Archived through one `Publisher` service, plus a scheduled command |
| Preview | Signed, expiring, no-index links for 9 entity types |
| Tests | 202 passing (119 existing + 83 new), 0 skipped |
| Schema changes | None |

---

## 2. Admin Architecture

Navigation groups and resources (`app/Providers/Filament/AdminPanelProvider.php`):

```text
Dashboard        ContentOverview · PublishingQueue · LeadsOverview · RecentLeads · RecentActivity
Website          Pages · Navigation (menus + items) · Announcements · Social Links · Global Settings
Services         Service Categories · Services · Technologies
Solutions        Solutions
Products         Products (features, modules, FAQs, technologies as relation managers)
Industries       Industries
Work             Clients · Testimonials · Case Studies
Insights         Articles · Categories · Tags · Authors · FAQs
Marketing        Landing Pages · Campaigns · Forms (extra fields as relation manager) · CTAs
Leads            Enquiries · Lead Sources
SEO              SEO Manager · Redirects
Media            Media Library
System           Users · Roles · Activity log
```

Every resource lives in `app/Filament/Resources/{Plural}/` with an embedded form and table and its page classes. Shared building blocks live in `app/Filament/Support/`:

| Helper | Purpose |
|---|---|
| `SlugField` | Name/title input that suggests a slug on create only; slug input with regex, uniqueness and reserved-slug rules |
| `PublishingFields` | Status, publish date, featured and order fields; non-publishers only see Draft and Review |
| `SeoFields` | The shared SEO panel saved through the polymorphic `seo` relationship |
| `MediaFields` | Image, gallery and document uploads with the approved type policy |
| `CtaSelect`, `RelationSelect` | CTA picker and many-to-many pickers |
| `AuditSection` | Created/updated by and when |
| `BlockBuilder` | The typed block editor bound to a host type |
| `PreviewAction` | Signed preview link action |
| `PublishActions` | Record and bulk workflow actions |
| `Columns` | Status, featured, date and order columns and filters |
| `FaqsRelationManager`, `TechnologiesRelationManager` | Generic polymorphic relation managers reused by 8 resources |

Panel settings: brand name and colours from the design system, `strictAuthorization()` so a missing policy method denies instead of allowing, collapsible sidebar.

---

## 3. Roles & Permissions

Phase 2 remains the source of truth: permissions are `{subject}.{action}` rows seeded from `config/markedge.php`, every model has a policy extending `PermissionPolicy`, and `Gate::before` grants Super Admin everything.

Phase 4 adds:

- `MediaPolicy` (subject `media`) registered for the Spatie `Media` model, next to the existing Role and Activity policies.
- Filament resources rely on those policies for `viewAny/view/create/update/delete/restore/forceDelete`. Custom pages implement `canAccess()` against `settings.*`, `seo.*` and `leads.*` permissions.
- Workflow actions check `publish` (or `update` for "submit for review") per record. Bulk actions apply a transition only to records the user may publish and report skipped ones.
- Status selects only offer statuses the user may set, and the CSV export is authorised against `leads.export`.
- Users need `is_active` and at least one role to enter the panel (`User::canAccessPanel`). Users cannot deactivate or delete themselves; the Super Admin role cannot be renamed, edited or deleted.
- Roles are edited through a searchable, bulk-toggleable permission list drawn from the database.

Tests cover direct-URL access per role, inactive users, hidden navigation, hidden actions and bulk export visibility.

---

## 4. Content Workflow

`App\Services\Cms\Publisher` is the only place statuses change:

```text
submitForReview  Draft → Review                      (update permission)
publish          any → Published, checklist enforced (publish permission)
schedule         any → Scheduled (future) or Published (past date)
unpublish        Published/Scheduled → Draft
archive          any → Archived (public URL returns 410 in Phase 5/6)
publishDue       Scheduled with a past date → Published (command)
```

Each transition writes an activity-log entry with the old and new status. The checklist merges block validation (§5) with model-specific rules: a case study needs a challenge and a solution; a landing page needs a form or a CTA. Failures surface as a persistent notification listing the problems.

Scheduled publishing: `content:publish-scheduled` runs every minute (`routes/console.php`). The published scope already ignores future-dated records, so the command only flips statuses.

---

## 5. Block Editor

`app/Cms/Blocks/` implements architecture §6:

- `Block` (abstract): key, label, icon, allowed hosts, Filament fields, validation rules, shared display settings (enabled, theme light/dark/neutral, anchor id).
- `BlockRegistry`: the whitelist of 27 block classes, `forHost()` filtering and `validate()` which rejects malformed entries, unknown types, disallowed hosts and invalid data.
- `Fields`: shared field factories (heading, intro, rich text, image, CTA, record pickers, mode, layout) so every block asks for content the same way.

Block types: hero, capability_intro, split_content, rich_text, image_content, video, feature_grid, stats, service_grid, product_showcase, solution_grid, industry_grid, technology_grid, logo_cloud, process, timeline, case_study_grid, testimonials, faq, comparison, cta, lead_form, contact_form, article_grid, related_content, related_services, related_products.

Editors can add, reorder (buttons and drag), clone, collapse, enable/disable and delete sections. They cannot set margins, padding, font sizes, colours, classes, HTML or scripts. Record pickers search on demand instead of preloading catalogues. The `stats` block requires an explicit attestation that figures are genuine.

Validation happens twice: Filament validates each block's fields on save, and the `Publisher` re-validates the stored tree through the registry before anything goes live, so an unknown or malformed block can be saved as a draft but never published.

Hosts: `page`, `landing_page`, `product`, `service`, `service_category`, `solution`, `industry`, `case_study` each get only the blocks the architecture allows.

---

## 6. SEO

`SeoFields` is attached to Pages, Service Categories, Services, Products, Solutions, Industries, Case Studies, Articles, Categories, Authors and Landing Pages and saves to the polymorphic `seo_meta` row through the `seo` relationship.

- **No blocking length validation.** Title and description show a live counter with advisory ranges (50–60 and 150–160 characters) and an explicit "longer is allowed" note. A test saves a 220-character title and a 600-character description.
- Canonical URL, robots index/follow, Open Graph and X fields, OG/X images (media collections on the SEO row), schema type override from an allow-list, schema overrides as a JSON object that may not set `@context`, `@type` or `@id`, sitemap inclusion, priority and change frequency.
- Landing pages are created with `robots_index = false` and `include_in_sitemap = false` by default.
- The **SEO Manager** page shows coverage per published content type and a filterable table of every SEO record with a deep link to the owning record's SEO tab.
- Global defaults (title suffix, default title/description, sharing image, canonical host, X handle) live in Global Settings. The fallback chain itself is rendered by the Phase 6 meta resolver; nothing is duplicated in resources.

---

## 7. Media

The Spatie Media Library plugin provides uploads on each owning record (hero, logo, gallery, screenshots, featured, avatar, OG images). `MediaFields` enforces the policy: JPEG, PNG and WebP only, 5 MB per image, 20 MB PDFs in document collections, image editor enabled. **SVG is rejected everywhere**, including logos, until an SVG sanitiser is added (architecture §24.2 allowed sanitised logos; see Deviations). AVIF conversions remain deferred.

The **Media Library** resource lists every file with preview, owner, collection, type, size and upload date, filters by type, owner, collection and missing alt text, and edits name, alt text, caption and description. Uploads that are not attached to a record are not supported (see Deferred).

---

## 8. Leads

`LeadResource` is sales-facing: a table with name, contact, interest (product or service), source, campaign, owner, status and received date; filters for status, form, campaign, service, product, industry, owner, source and date range; a detail page with Enquiry, Attribution (first and last touch side by side) and a Technical tab visible only to Super Admins; an edit form limited to status, owner, contacted/closed dates and notes.

Attribution columns are never editable in the panel; they are written once at capture (Phase 7). Bulk actions: assign owner, mark as spam, export CSV (authorised by `leads.export` and logged), delete. Leads cannot be created manually. **Lead Sources** reports first-touch, last-touch, campaign and form counts for 7/30/90/365 days, excluding spam.

---

## 9. Marketing

- **Forms**: name, key, type, heading, intro, submit label, honeypot and consent toggles; one fieldset per core lead field (show, required, label, placeholder, order); success mode (message or redirect to a page); notification addresses and optional auto-reply. Extra fields are managed in a relation manager with a controlled type list, manual or entity-sourced options, an allow-listed validation set (min, max, URL) and optional mapping to a lead column.
- **Landing pages**: campaign, form, CTA, navigation/footer toggles, extra dataLayer variables, blocks, expiry with redirect, SEO defaulting to no-index.
- **Campaigns**: UTM parameters with a live example URL, channel, status, dates, default landing page, form and CTA, conversion labels, notes. Leads count appears in the table.
- **CTAs**: internal name and key, headline, body, primary and secondary actions (URL, route, form, WhatsApp, phone, email), contextual WhatsApp message with `{entity}`, variant from the fixed set, click count.

---

## 10. Preview

`App\Services\Cms\PreviewLink` issues `URL::temporarySignedRoute('preview.show', …)` links valid for `markedge.preview.ttl_hours` (24 h default) for pages, service categories, services, products, solutions, industries, case studies, articles and landing pages. The `Preview` action appears on edit pages and table rows only when the user holds the `preview` permission.

`GET /preview/{type}/{id}` is protected by the `signed` middleware and a 60/min rate limit. It renders the draft through the public layout with a preview banner, `X-Robots-Tag: noindex, nofollow, noarchive`, `Cache-Control: no-store`, a no-index meta tag and no canonical, schema or tracking. Blocks render through `x-cms.blocks`, which shows a labelled placeholder for any block whose public view has not been built yet (Phase 5). Tests cover signed, unsigned, expired and unknown links.

---

## 11. Audit

Spatie Activitylog records attribute changes on editorial and configuration models through the existing `RecordsActivity` concern, and the `Publisher` logs every workflow transition with old and new status. Lead exports are logged with the row count. The **Activity log** resource is read-only with filters by subject, event, user and date and a detail view of before/after values. The **Recent activity** dashboard widget shows the latest entries to users allowed to view the log.

---

## 12. Security

- Authorization: policies on every resource, `strictAuthorization()`, per-record checks in actions and bulk actions, `canAccess()` on custom pages, self-protection on users, protected Super Admin role.
- Mass assignment: models keep explicit fillable lists; forms only write declared fields.
- HTML: rich editor toolbars are restricted; content is sanitised on save by the purifier (Phase 2) and rendered through the `prose` component.
- Uploads: MIME allow-lists, size caps, no SVG, public-disk paths for settings images and block images, media library validation for everything else.
- URLs: menu links, CTA links, redirects and announcement links accept site paths or `https://` URLs only; redirects reject self-targets and loops.
- Tracking IDs are validated by pattern and stored as public identifiers; no secrets are stored in settings.
- Preview links are signed, expiring and rate limited.

---

## 13. Tests

```text
php artisan test --compact  →  202 tests, 610 assertions, all passing
vendor/bin/pint --test      →  passed
npm run build               →  passed
```

New test files: `tests/Feature/Filament/{PanelAccess, PageResource, LeadResource, RedirectResource, GlobalSettings, UserAndRoleResource, ContentResources, MenuResource, Dashboard}Test.php`, `tests/Feature/Cms/Blocks/BlockRegistryTest.php`, `tests/Feature/Services/Cms/PublisherTest.php`, `tests/Feature/Http/PreviewTest.php`. The Filament test group seeds roles and sets the current panel in `tests/Pest.php`.

---

## 14. Deviations

| Deviation | Reason |
|---|---|
| Block images are stored as paths on the public disk (`blocks/`) rather than media-library items attached to the host's `blocks` collection. | The media upload field cannot scope one collection per block inside a Builder; a path keeps the editor simple. The Phase 5 renderer reads the path. |
| SVG is rejected for logos as well as other images. | No SVG sanitiser is installed; accepting SVG without one would weaken the upload policy. |
| Media Library is browse/edit only; there is no standalone upload. | Media Library items must belong to a model (architecture §24.1 trade-off). A shared asset library would need a new table. |
| Block validation runs on the dehydrated tree at publish time rather than as a form rule. | Rich-text state is not HTML until the form is dehydrated, so a form-level rule produced false errors. Field rules still validate on save. |
| Sitemap and Schema pages are not provided under SEO. | Both depend on the Phase 6 engine; adding status screens now would duplicate logic. |
| `RelationSelect` preloads small catalogues (services, products, industries, solutions) and searches large ones (articles). | Practical performance balance for the current catalogue sizes. |

---

## 15. Deferred Work

- Public block views (`components/blocks/*`), the entity templates that the preview will reuse, and the 410 handling for archived content (Phase 5).
- Meta resolver fallback chain, schema builders, sitemap, robots, redirect middleware and slug-change redirects (Phase 6).
- Lead capture, attribution middleware, notifications, spam guard and CTA click tracking (Phase 7).
- Standalone media uploads / shared asset library, SVG sanitising, AVIF conversions (later phases, on approval).
- Filament MFA enforcement for Super Admin (Phase 11 security hardening).

---

## 16. Git

Commit "Phase 4: Filament CMS and admin control center" on branch main (run `git log --oneline -1` for the hash).
