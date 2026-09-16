# Markedge — Phase 7 Implementation: Public Search, Content Discovery & Related Content

Status: complete. Builds on Phases 0–6 without changing their architecture. Commit: `feat: implement public search and content discovery`.

## 1. Scope delivered

- Public search at `GET /search` (parameters `q`, `type`, `category`, `page`), server-rendered, no JavaScript dependency.
- Search abstraction (`App\Search\Contracts\SearchEngine`) with a MySQL full-text implementation and an SQLite-compatible fallback for the test suite.
- Normalised search documents kept in sync by observers and a queued, self-verifying job.
- `php artisan markedge:search-reindex` chunked rebuild.
- Admin "Search index" diagnostics page with a rebuild action.
- Deterministic ranking, filters, pagination and all UX states.
- Related-content engine rebuilt on documented weights and reused by every entity template and the `related_content` block.
- Search pages `noindex, follow`, never in the sitemap, WebSite `SearchAction` now emitted.
- 34 new tests; full suite 350 tests / 1,242 assertions green; Pint and Vite clean.

Not implemented on purpose: analytics, search logging, lead attribution, AI or semantic search, external engines, autosuggest, admin control over ranking weights.

## 2. Architecture

```
Request → SearchController → QueryNormalizer → SearchQuery
                                   ↓
                        SearchEngine (interface)
                                   ↓
                     DatabaseSearchEngine (search_entries)
                                   ↑
   Model saved/deleted/restored → SearchableObserver ─┐
   SeoMeta saved/deleted        → SeoMetaObserver    ─┴→ SyncSearchEntry (queued, afterCommit)
                                                         → SearchIndexer → SearchDocumentBuilder → IndexabilityResolver
```

Files: `app/Search/{Contracts/SearchEngine, SearchTypes, QueryNormalizer, SearchQuery, SearchResult, SearchResults, SearchDocument, SearchDocumentBuilder, SearchIndexer, Engines/DatabaseSearchEngine}.php`, `app/Jobs/SyncSearchEntry.php`, `app/Observers/{SearchableObserver,SeoMetaObserver}.php`, `app/Events/SearchPerformed.php`, `app/Console/Commands/SearchReindex.php`, `app/Http/Controllers/Site/SearchController.php`, `app/Filament/Pages/SearchIndexStatus.php`, `app/Services/Cms/RelatedContentResolver.php` (rewritten).

## 3. Search engine abstraction

`SearchEngine` exposes `search(SearchQuery): SearchResults`, `index(SearchDocument)`, `remove(type, id)`, `removeAll()` and `stats()`. The application, the indexer, the command and the admin page only touch this interface. Swapping to Meilisearch or another engine later means one new class and one binding change in `AppServiceProvider::register()`. No provider-specific concepts leak into controllers or views.

## 4. Initial engine: database full text

`DatabaseSearchEngine` searches the `search_entries` table.

- MySQL: `MATCH(title, summary, body_text, keywords) AGAINST (? IN BOOLEAN MODE)` with every token as `+token*` (all terms required). Tokens under three characters (below the InnoDB minimum token size) fall back to a title `LIKE`.
- SQLite (tests) and any non-MySQL driver: bounded `LIKE` across the same four columns. Both paths share the same filters, ranking expression and ordering, so tests exercise the real ranking logic.
- If nothing matches with all terms required and the query has more than one token, the engine reruns with any-term matching and the page says so ("showing pages that match any of your words").

## 5. Search document model

Migration `2026_09_17_000001_add_discovery_columns_to_search_entries_table` adds `category_slug` (indexed), `category_label`, `keywords`, `weight` and rebuilds the MySQL full-text index over title, summary, body_text and keywords. Each row stores: morph type and id (unique), `kind`, title, summary (excerpt / short description / tagline, 300 chars), stripped body text (up to 20,000 chars), keywords (article tags, technologies, category pillar label), category slug and label, type weight, public path and published date. Only public-safe fields are stored; no admin notes, form data, leads or settings.

## 6. Eligible content types

Defined once in `SearchTypes::TYPES`: service, product, service_category, solution, industry, case_study, article, page. Base weights: service and product 30, service area and solution 25, industry and case study 20, page 15, article 10, plus 5 for featured records. Leads, users, forms, CTAs, menus, SEO rows, redirects, tags, authors and landing pages are never indexed (landing pages are noindex by default and outside organic discovery by design).

## 7. Eligibility rule

`SearchDocumentBuilder::forEntity()` returns `null` unless the record is of an eligible type, not soft-deleted, publicly visible (published / active) and the Phase 6 `IndexabilityResolver` marks it `discoverable`. `Indexability::$discoverable` is new: "not noindex and not canonicalized elsewhere", evaluated independently of the `MARKEDGE_INDEXABLE` environment flag. That flag controls search engines; on-site search and related content keep working on staging where the flag is off. There is no separate "searchable" checkbox; the CMS publication and SEO settings remain the single source of truth.

## 8. Index consistency

`SearchableObserver` is attached to all eight content models for `saved`, `deleted`, `restored` and `forceDeleted`; `SeoMetaObserver` re-syncs the owner when a noindex, canonical or sitemap setting changes. Both only dispatch `SyncSearchEntry`. The job runs after commit, re-loads the record (including soft-deleted rows) and re-evaluates eligibility at run time, so a job queued before an unpublish can never resurrect an entry. Missing records are removed by type and id. Covered events: create, edit, publish, unpublish, archive, schedule, restore, delete, slug change (URL updated), noindex on/off, canonical set/cleared.

## 9. Reindex command

`php artisan markedge:search-reindex [--chunk=500]` clears the index, streams every eligible model with `chunkById` and `with('seo')`, reports indexed/scanned counts per type and records `search.last_rebuilt_at` in settings. It never writes to any CMS table (tested).

## 10. Admin diagnostics

`Admin → SEO → Search index` (`SearchIndexStatus`, gated by `seo.view_any`) shows indexed documents, a factual health line (in sync / missing or stale documents found), the last full rebuild, and a per-type table of indexed, discoverable, missing and stale counts computed from live data. No fabricated score. "Rebuild index" (gated by `seo.update`, confirmation modal) runs the same rebuild synchronously and notifies with the count.

## 11. Search page

`resources/views/pages/search.blade.php` renders: hero with an accessible form (`role="search"`, labelled input, type select, category optgroups), and one of six states: empty (suggested terms and browse links), too short, error, results, no results (with "search all content" when filters are set), and out-of-range page. Results use `components/cards/search-result.blade.php` with a type badge, category context, dates for articles and case studies, title link and canonical URL. Pagination reuses `pagination/site.blade.php` and preserves the query string.

## 12. Query normalisation

`QueryNormalizer`: control characters and repeated whitespace collapsed, trimmed, capped at 120 characters (`MAX_LENGTH`), minimum 2 characters (`MIN_LENGTH`), at most 12 tokens; tokens are lower-cased, stripped of operators and punctuation, deduplicated. The user's wording is displayed as typed (escaped by Blade).

## 13. Ranking

Same expression on all drivers, ordered by relevance desc, `published_at` desc, `id` desc:

| Signal | Score |
|---|---|
| exact title match | 400 |
| title starts with the phrase | 250 |
| title contains the phrase | 150 |
| summary contains the phrase | 60 |
| keywords contain the first token | 40 |
| type weight (+ featured) | 10–35 |

Body matches contribute only through inclusion in the candidate set, so title > summary > body > taxonomy holds and results are stable across requests.

## 14. Filters and validation

`q` nullable string ≤ 1000, `type` in `SearchTypes::keys()`, `category` slug regex, `page` integer 1–1000. Invalid input returns a redirect with validation errors (never a 500); arrays and unknown values are rejected. Categories offered are published service areas and visible article categories.

## 15. Security

- SQL injection: all matching uses bindings; the full-text expression is a single bound parameter after stripping operators `+ - < > ( ) ~ * " @`; `LIKE` wildcards are escaped.
- Full-text operator abuse and parameter pollution tested (`q[]`, negative pages, unknown types).
- XSS: every echo is escaped by Blade; test asserts `<script>` never renders.
- DoS: length and token caps, `throttle:search` at 30 requests per minute per IP, `page` capped at 1000, bounded LIKE fallbacks.
- Errors: engine exceptions are reported and rendered as a friendly state without leaking messages or paths (tested with a failing engine).
- Only indexed (public) documents can ever appear; previews, drafts, internal records are excluded at index time (tested).

## 16. Search SEO

`SeoEngine::forListing('Search', …, '/search', indexable: false)` yields robots `noindex, follow`, canonical `/search` (never the query string), not in sitemap. `SitemapGenerator` has no search source, and a test asserts `/search` is absent. Reserved slug `search` was already in place.

## 17. SearchAction

Now that a real search endpoint exists, `WebSiteBuilder` emits `potentialAction` → `SearchAction` with `EntryPoint.urlTemplate = {site}/search?q={search_term_string}` and `query-input`. A test validates the graph and that the template's URL resolves.

## 18. Header search access

Desktop: magnifying-glass link (`aria-label="Search the site"`) before the CTA in `components/layout/header.blade.php`. Mobile: a search form at the top of the drawer in `components/navigation/mobile.blade.php`. Both are plain links/forms, keyboard accessible, no extra JavaScript; the bundle size is unchanged.

## 19. Related-content engine

`RelatedContentResolver` keeps its method signatures (`services`, `products`, `solutions`, `industries`, `articles`, `caseStudies`) and adds `discover()` for the `related_content` block. Weights (constants, tested):

| Signal | Weight |
|---|---|
| explicit CMS relationship | 100 + ordering bonus (admin order wins ties) |
| derived relationship (solution → case studies via its services/products) | 80 |
| same category (service area, article category) | 30 |
| shared industry (service/product ↔ case study) | 25 |
| shared tag (articles) | 20 per tag, capped at 60 |
| featured-product fallback | 5 |

Ties break on `published_at` desc then id desc. Every candidate must be discoverable (published, not noindex, not canonicalized elsewhere) and is never the host. No random filler: with no signals the section is omitted. The only fallback is featured products for hosts without explicit products, and it is documented as such. Candidate sets are bounded (12 per signal) and eager-load `seo` so indexability checks do not query.

## 20. Discovery component

Templates keep using `x-sections.related-grid`; the `related_content` block now receives `RelatedContentResolver::discover()` (mixed articles, case studies, solutions, services, strongest first). `PageRenderer` routes service-page products through the resolver so the featured fallback is consistent everywhere.

## 21. Caching

Search results are not cached: they are single indexed queries with bounded cost, and caching would risk serving unpublished or stale content. The index itself is the materialised cache and follows content changes via observers. Related content is computed per request from bounded queries; content-version keys from Phase 5 remain untouched. No Eloquent models are placed in the cache (Laravel 13 `serializable_classes` constraint).

## 22. Testing

`tests/Feature/Search/SearchPageTest.php` (15): states, filters, pagination, normalisation, min/max length, injection and operator abuse, XSS, engine failure, header access, sitemap exclusion, SearchAction, ranking, any-term fallback, out-of-range page.
`tests/Feature/Search/SearchIndexingTest.php` (11): publish/unpublish/archive, review/scheduled, noindex and canonical, slug change, delete/restore/force delete, taxonomy keywords, staging environment, internal records and previews, chunked rebuild with no CMS mutation, audit, stale-job safety, admin page and permissions.
`tests/Feature/Search/RelatedContentRankingTest.php` (8): weight order, exclusions, admin ordering and recency tie-break, category and industry signals, no filler, featured fallback, query budget, canonical links.
`tests/Feature/Seo/SchemaTest.php` updated for the SearchAction.

## 23. Manual verification (local, MySQL, `MARKEDGE_INDEXABLE=false`)

- `/search` empty state, `/search?q=software` (1 result), `/search?q=loan` and `/search?q=xyz-no-result` (no results), `/search?q=a` (too short): all 200.
- Filters: `type=service`, `type=product`, `category=technology` behave; `q[]=x` → 302; operator soup `+software* -(x) @y "` → results, no error; `page=999` → out-of-range state.
- Robots on search: `noindex, nofollow` locally (environment closed), `noindex, follow` under an indexable environment (test).
- `/sitemap.xml` contains no `/search`; header icon and mobile form present on `/`.
- `markedge:search-reindex` indexed 7 documents (5 published services/products/solutions/industries/category, 2 pages); index rows carry category slug, URL and weight.
- Related sections on the local service page are empty because no explicit relations exist and its only category sibling is noindex: correct, no filler.

## 24. Deviations and deferred items

- Ranking signal weights, tie-breaks and engine choice are hard-coded (architecture principle); admins do not tune them.
- `SearchPerformed` event exists with no listener; analytics is a later phase.
- Autosuggest, synonyms, typo tolerance and external engines (Meilisearch) are deferred; the interface is ready for them.
- Minimum token size on MySQL (`innodb_ft_min_token_size`, default 3) means 1–2 character terms match titles only.
- JS bundle unchanged at 96.7 kB gzipped (Phase 10 item).
