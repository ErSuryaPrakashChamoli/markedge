# MARKEDGE — PHASE 6 IMPLEMENTATION REPORT

## SEO Engine, Structured Data, Sitemap, Robots, Redirects & Indexability

Date: 2026-09-16

---

## 1. Phase objective

Centralise every SEO decision: one indexability resolver drives robots meta, canonical, sitemap inclusion and structured-data eligibility; a schema engine emits a single JSON-LD graph from real data; `/sitemap.xml` and `/robots.txt` are generated; the redirects table is served with loop and destination protection; published slug changes create redirects; and the SEO Manager reports factual diagnostics.

## 2. Starting commit

`03266a0` — feat: build public website routing and entity templates (Phase 5).

## 3. Files/components added

| Area | Files |
|---|---|
| Indexability | `app/Seo/Indexability.php`, `app/Seo/IndexabilityResolver.php` |
| Engine | `app/Seo/SeoEngine.php` |
| Schema | `app/Seo/Schema/{SchemaBuilder,SchemaContext,SchemaGraphBuilder}.php`, `app/Seo/Schema/Concerns/OmitsEmptyValues.php`, `app/Seo/Schema/Builders/{Organization,WebSite,WebPage,Breadcrumb,Service,Article,Product,Faq}Builder.php` |
| Sitemap / robots | `app/Seo/Sitemap/SitemapGenerator.php`, `app/Seo/RobotsBuilder.php`, `app/Http/Controllers/Seo/{Sitemap,Robots}Controller.php` |
| Redirects | `app/Seo/RedirectResolver.php`, `app/Rules/SafeRedirectDestination.php`, `app/Services/Cms/SlugRedirects.php` |
| Diagnostics | `app/Seo/Diagnostics/{SeoAudit,SeoCheck}.php`, `app/Filament/Support/SeoDiagnosticsAction.php`, `resources/views/filament/seo-diagnostics.blade.php` |
| Tests | `tests/Feature/Seo/{Indexability,SeoConsistency,Schema,Sitemap,Robots,RedirectEngine,SlugRedirect,SeoAudit,LongSeoContent}Test.php` |

## 4. Files/components modified

`app/Seo/MetaResolver.php` (robots and canonical now come from the resolver), `app/Seo/PageMeta.php` (carries the decision), `app/Services/Cms/PageRenderer.php` (uses `SeoEngine`, collects rendered FAQs), `app/Http/Controllers/Site/*` (use `SeoEngine`, pass breadcrumbs), `app/Http/Controllers/PreviewController.php` (unchanged behaviour), `resources/views/components/seo/head.blade.php` (hex-encoded JSON-LD), `app/Models/Redirect.php` (destination guard, self-redirect guard), `app/Models/Concerns/HasSlug.php` (slug-change hook), `app/Filament/Resources/Redirects/RedirectResource.php` (safe destination rule), `app/Filament/Pages/SeoManager.php` (indexability, sitemap and schema columns, diagnostics modal), nine `Edit*` resource pages (SEO checks action), `routes/web.php` (`/sitemap.xml`, `/robots.txt`), `bootstrap/app.php` (404 → redirect lookup), `config/markedge.php` (sitemap and redirect settings). `public/robots.txt` was removed in favour of the dynamic route.

## 5. SEO architecture

`SeoEngine` is the single entry point used by `PageRenderer` and the listing controllers. It composes `MetaResolver` (title, description, Open Graph, X, article timestamps through the chain entity SEO → entity content → global settings), `IndexabilityResolver` (robots, canonical, sitemap, schema eligibility) and `SchemaGraphBuilder` (JSON-LD). The result is one immutable `PageMeta` rendered by `x-seo.head`. No field has a length limit; a 300-character title and 600-character description are saved through the admin and rendered in tests.

## 6. Indexability architecture

`IndexabilityResolver::forEntity()` returns an `Indexability` value with `indexable`, `follow`, `canonical`, `canonicalizedElsewhere`, `inSitemap`, `schemaEligible`, `preview` and a human-readable `reason`. Rules, in order: preview → noindex/nofollow/noarchive, no canonical, no sitemap, no schema; not publicly visible (draft, review, scheduled, archived, hidden) → nothing; environment switch `markedge.seo.indexable` off → noindex, nofollow everywhere; per-entity SEO flags; content-type defaults (landing pages and tags noindex, authors only with a bio); a foreign canonical marks the page canonicalized elsewhere (indexable, excluded from sitemap and schema); `include_in_sitemap` opt-out. Canonicals are absolute, built from `seo.canonical_host` or the configured app URL, never the request host. `forListing()` covers index pages.

## 7. Schema architecture

`SchemaGraphBuilder` runs the registered builders against a `SchemaContext` (entity, meta, decision, canonical URL, site URL, breadcrumb trail, rendered FAQs) and merges nodes by `@id` into one `@graph`: Organization (settings only; address and LocalBusiness only when configured), WebSite (no SearchAction because search does not exist), WebPage/CollectionPage (with breadcrumb and image references), BreadcrumbList (built from the exact trail the template renders, absolute URLs), Service (services and categories; no offers or ratings), Article (real author, dates, category, tags), SoftwareApplication for products (no offers, prices, ratings or reviews), FAQPage only for visible FAQs actually rendered (entity FAQs plus faq blocks). `OmitsEmptyValues` drops nulls, empties and any `[PLACEHOLDER` text. Validated overrides from `seo_meta.schema_overrides` merge into the primary node with `@context`, `@type`, `@id` and `@graph` protected, strings tag-stripped and depth limited; `schema_type` may only choose an allow-listed type. Output is encoded with `JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT`, so `</script>` can never break out of the element.

## 8. Sitemap architecture

`SitemapGenerator` builds `/sitemap.xml` (`application/xml`) from the home page, section indexes and every public content type, streaming each source with `chunkById(500)` and `with('seo')`, asking the indexability resolver per record and keeping only `inSitemap` entries. Entries are deduplicated by canonical URL, `lastmod` is the record's `updated_at` (section indexes use the newest published record; the fallback home entry has none), optional `changefreq`/`priority` come from the SEO row, and all values are XML-escaped. The XML is cached under the content-version key for 24 hours, so publishing invalidates it. In a non-indexable environment the sitemap is empty, matching robots and meta.

## 9. Robots architecture

`RobotsBuilder` emits `Disallow: /` when `markedge.seo.indexable` is false (local, staging, testing by default) and otherwise disallows `/admin`, `/preview`, `/livewire`, `/styleguide`, `/go`, `/up`, allows `/` and references `Sitemap:` on the configured host. Only configuration reaches the file; CMS content cannot alter it.

## 10. Redirect architecture

Redirects are consulted only when a request ends in a 404 (`bootstrap/app.php` exception renderer), so they can never shadow live routes or records. `RedirectResolver` normalises the path, looks up the single active row (`from_path` is unique, so priority is deterministic), follows internal chains up to `markedge.redirects.max_depth` (5) with cycle detection, responds with the row's status (301/302/307/308), `Location` and `X-Robots-Tag: noindex`, and records the hit after the response. Destinations must be site paths (not `//`), or `http(s)` URLs on the app host or the `MARKEDGE_REDIRECT_HOSTS` allow-list; `javascript:`, `data:`, `vbscript:`, control characters and foreign hosts are rejected by `SafeRedirectDestination` (admin) and by the model's `saving` guard (everywhere). Self-redirects and loops are rejected on save.

## 11. Slug-change behaviour

`HasSlug` fires `SlugRedirects::afterSlugChange()` on `updated` when the slug changed. A permanent redirect from the old public path to the new one is created only if the record was publicly visible before the save (published and past its date, product active or coming soon, visible category or author, tags always). Ordinary edits, unchanged slugs, drafts and records without a public URL create nothing. Existing redirects that pointed at the old path are repointed to the new path, and any redirect whose source equals the new path is removed so successive renames never loop. `updateOrCreate` on `from_path` prevents duplicates.

## 12. Security considerations

- XSS: every meta value is Blade-escaped; JSON-LD is hex-encoded; overrides are tag-stripped.
- JSON injection: overrides are sanitised recursively, identity keys protected, depth capped.
- Open redirects: destination allow-list at rule and model level; protocol-relative and script schemes rejected.
- Host header: canonicals, sitemap and robots use configuration, never the request host (tested with a spoofed `Host`).
- XML injection: locations and timestamps are escaped; changefreq and priority are validated.
- Robots injection: no CMS content is interpolated.
- Authorization: SEO Manager and diagnostics respect existing permissions; the redirects resource remains policy-gated.

## 13. Testing performed

Automated suite (see §14), formatter, production build, `route:list` review and a local HTTP inspection with `MARKEDGE_INDEXABLE=true` of the pages listed in §19.

## 14. Test count

316 tests, 1,098 assertions, all passing (257 before Phase 6, 59 new).

## 15. Vite/build result

`npm run build` — passed.

## 16. Pint result

`vendor/bin/pint --test` — passed.

## 17. Deviations

- Products emit `SoftwareApplication` (name, tagline, description, URL, application category from product type, provider) rather than `Product`, because the content model has no offers or ratings and `Product` rich results would be misleading.
- Case studies emit WebPage plus breadcrumbs only; no Article node, because a case study is not editorial content and no result properties exist.
- A canonicalized page is also excluded from schema (the architecture left this open); documented in the resolver reason.
- Non-indexable environments emit `noindex, nofollow` and an empty sitemap so meta, robots and sitemap always agree.

## 18. Intentionally deferred

Sitemap index splitting above 50,000 URLs (single urlset now, chunked generation ready), image/video sitemaps, hreflang, the CTA `/go` route (Phase 7), public search (so no `SearchAction`), analytics, performance tuning of the bundle.

## 19. Manual verification performed

Local server with a small published sample (Technology category, Software Development, SEO service marked noindex, Lead Management System active, Business Automation, Healthcare, About, Home, one verification article) — see the phase summary for the captured output: `<title>`, description, canonical, robots, OG/X tags, JSON-LD types, preview headers, `/legacy-services` 301, `/robots.txt`, `/sitemap.xml` validity and inclusion/exclusion.

## 20. Final Git commit

"feat: implement SEO engine, schema, sitemap and redirects" on branch main (run `git log --oneline -1` for the hash).
