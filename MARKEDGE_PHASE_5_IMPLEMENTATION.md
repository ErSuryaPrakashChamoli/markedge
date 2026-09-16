# MARKEDGE — PHASE 5 IMPLEMENTATION REPORT

## Public Website, Routing & Entity Templates

Date: 2026-09-16
Builds on: Phase 4 commit `bdbc676`

---

## 1. Summary

Phase 5 connects the Phase 4 CMS to the Phase 3 design system. Every published entity now has a public URL, a fixed-skeleton template with one flexible block slot, breadcrumbs, resolved SEO metadata and a contextual CTA. The homepage is CMS-driven, the 27 block types render through Blade components, related content comes from the real relationship graph, and the signed preview renders drafts through exactly the same templates.

```text
Filament CMS → Database → ContentResolver (published, slug, eager loaded)
             → PageRenderer (meta, breadcrumbs, CTA, related content, prepared blocks)
             → Entity template (pages/*.blade.php) → BlockRenderer → components/blocks/*
             → Phase 3 design system → Public website
```

| Area | Delivered |
|---|---|
| Routes | 23 public routes (see §2), CMS page catch-all last |
| Entity templates | Home, Page (6 templates), Service Category, Service, Product, Solution, Industry, Case Study, Article, Landing Page, plus 6 indexes |
| Block views | 27 (every whitelisted type) + labelled unknown-block notice in preview |
| Services | `ContentResolver`, `PageRenderer`, `BlockHydrator`, `BlockRenderer`, `MetaResolver` + `PageMeta`, `Breadcrumbs`, `RelatedContentResolver`, `CtaResolver::forEntity()` |
| Livewire | `LeadForm` (validation, honeypot, consent, Lead storage) |
| Tests | 257 passing (202 existing + 55 new) |
| Schema changes | None |

---

## 2. Public Routing

`routes/web.php`, fixed routes first, entity sections next, the CMS catch-all last. Every slug parameter is constrained to `[a-z0-9]+(?:-[a-z0-9]+)*`, so uppercase paths, dots and nested segments never reach a controller.

```text
/                                  HomeController          published "home" Page, structural fallback otherwise
/services                          ServiceController@index
/services/{slug}                   ServiceController@show  category first, then service (shared namespace)
/solutions, /solutions/{slug}      SolutionController
/industries, /industries/{slug}    IndustryController
/products, /products/{slug}        ProductController
/case-studies, /case-studies/{slug}  CaseStudyController (architecture's canonical URL for "Work")
/insights                          InsightsController@index
/insights/category/{slug}          InsightsController@category
/insights/tag/{slug}               InsightsController@tag      (noindex, follow)
/insights/author/{slug}            InsightsController@author   (indexable only with a bio)
/insights/{slug}                   InsightsController@show
/lp/{slug}                         LandingPageController       expired pages redirect
/preview/{type}/{id}               PreviewController           signed + throttled (Phase 4)
/styleguide                        config-gated
/{slug}                            PageController              CMS pages, including /contact
```

Reserved sections cannot be captured by the catch-all because they are registered first; `contact` stays a CMS page. `/products/123` and `/services/42` return 404 because ids are not slugs.

---

## 3. Entity Templates

All templates live in `resources/views/pages/` and use only Phase 3 primitives plus new shared sections in `components/sections/` (entity hero, feature list, process steps, technology strip, FAQ list, related grid, rich content, lead form) and cards in `components/cards/`.

| Template | Skeleton (architecture reference) |
|---|---|
| `services/show` | Hero → Overview → Benefits → Features → Process → Deliverables → Technologies → **blocks** → Industries → Solutions → Products → Case studies → Insights → FAQs → Related services → CTA (§10.2) |
| `services/category` | Hero → Description → Services → **blocks** → Products → FAQs → CTA |
| `products/show` | Hero (with Coming soon badge) → Overview → Features grouped → Modules → Benefits → Use cases → Screenshots → Integrations → Technologies → **blocks** → Industries → Testimonials → Case studies → FAQs → Solutions → Related services → Insights → Demo form or CTA (§9.1) |
| `solutions/show` | Hero → Problem → Approach → Services → Products → Outcomes → Technologies → **blocks** → Industries → Case studies (derived) → Insights → FAQs → CTA (§11) |
| `industries/show` | Hero → Description → Challenges → Solutions → Services → Products → Technologies → **blocks** → Case studies → Insights → FAQs → CTA (§12) |
| `case-studies/show` | Hero with client/industry/services → Outcomes strip → Challenge → Solution → Implementation → Results → Gallery → Technologies → **blocks** → Services → Products → CTA (§13) |
| `insights/show` | Header → Featured image → Body → Tags → Author → FAQs → Related capabilities → Keep reading → CTA (§14.2) |
| `landing/show` | Landing layout (minimal header, reduced footer) → **blocks** → injected lead form → FAQs → CTA (§22) |
| `page` | Hero unless the first block is a hero → **blocks** → template extras (contact/form: lead form + contact details; legal: narrow prose + last updated) → FAQs → CTA |
| `home` | Blocks only |

Every section is empty-safe: collections that are empty, rich text without text, and repeaters without titles render nothing.

---

## 4. CMS-to-Frontend Pipeline

- **`ContentResolver`** (`app/Services/Cms/`): one method per entity. Detail queries apply the `published()` scope (or `publiclyVisible()` for products), load every relation the template needs with published-only constraints, and return 410 for archived slugs. No controller queries the database directly.
- **`PageRenderer`**: maps an entity to its template and supplies `entity`, `meta`, `breadcrumbs`, `cta`, `blocks`, `related`, `preview`. Preview mode swaps `meta` for a no-index variant and passes `preview` down so forms disable and unknown blocks are labelled.
- **Controllers** in `app/Http/Controllers/Site/` are three to ten lines each.
- **Home fallback**: when no home Page is published, `/` renders `pages/home-fallback` from published categories, visible products and the default CTA. The brief's hero copy is used verbatim; nothing is invented.

---

## 5. Block Rendering

`BlockRenderer::prepare()` walks the stored JSON: entries that are not arrays, have no `type`, are unknown, or are not allowed on the host are dropped (labelled in preview only); disabled blocks are skipped; the rest are passed to `BlockHydrator`. The hydrator resolves ids to **published** records in the admin's chosen order, drops missing ids, computes CTA hrefs, image URLs and video embed URLs, and returns `null` for a block with nothing to show. `x-cms.blocks` renders each prepared entry through `<x-dynamic-component>` bound to `components/blocks/{key}.blade.php`; block keys come from the registry, never from the JSON, so no arbitrary component can be invoked.

---

## 6. Homepage

`/` renders the published Page with slug `home`. The seeded home page (still a draft) carries the fifteen-section narrative; Phase 5 tests render it with hero, capability intro, three service grids, products, industries, technologies, process, case studies, why-Markedge, insights and a CTA band. Sections whose data does not exist (case studies, testimonials, logos, articles) disappear. A single H1 is asserted.

---

## 7. Internal Linking

`RelatedContentResolver` implements §17.2 with published-only queries and limits: related services (manual pivot, then same-category siblings), products (pivot, then featured), solutions, industries, articles (pivot, then same category), case studies (pivot; for solutions derived through their services and products). Templates render these through `x-sections.related-grid`, which hides itself when empty. Breadcrumbs derive from hierarchy (Home › Services › Category › Service, Home › Insights › Category › Article) and only link to published parents.

---

## 8. SEO Meta Resolver

`App\Seo\MetaResolver` builds an immutable `PageMeta` through the fallback chain: entity `seo_meta` → entity content (title/name, excerpt/short description/tagline, hero/featured/logo image) → global settings (default title, suffix, description, sharing image, canonical host). Listing pages use `forListing()`. `x-seo.head` renders title, description, robots, canonical, Open Graph (type `article` for articles with published/modified times), X card (`summary_large_image` when an image exists) and a JSON-LD `@graph` slot that stays empty until the Phase 6 schema engine fills `PageMeta::$schema`.

- **No length limits anywhere**; a 220-character title is tested end to end.
- Canonicals are absolute, built from the configured app URL or `seo.canonical_host`, never from the request host.
- Robots follow the entity flags, but every environment with `MARKEDGE_INDEXABLE=false` (the default) emits `noindex, nofollow`. Production sets it to `true`.
- Tag archives are `noindex, follow`; author archives index only when the author has a bio; landing pages keep the Phase 4 no-index default.

---

## 9. Preview

`PreviewController` now calls `PageRenderer::render($record, preview: true)`, so drafts render through the real templates. Preview responses carry `X-Robots-Tag: noindex, nofollow, noarchive`, a no-index meta tag, no canonical, no schema, no announcement bar, no sticky CTA, disabled form submission and a preview banner. The Phase 4 placeholder preview view was removed.

---

## 10. Security

- Slug-only public lookups with a strict route constraint; unpublished, review, scheduled and draft content returns 404, archived returns 410.
- Block types are whitelisted by the registry; block JSON never selects a component or executes code. Unknown types are silently dropped publicly.
- All user-entered text is escaped; rich text fields (`prose`) render only content sanitised on save. Icon names are pattern-checked before being rendered. Integration links must start with `http`.
- Video embeds accept only YouTube and Vimeo ids extracted by regex and use the no-cookie YouTube domain.
- The lead form validates every field server-side, restricts select values to offered options, uses a honeypot, requires consent when configured, never stores in preview and never accepts a form that is inactive.
- Preview requires a valid signature and is rate limited.

---

## 11. Accessibility

One H1 per page (entity heroes and hero blocks), H2/H3 order within sections, skip link, `aria-current` breadcrumbs, accessible accordion FAQs (`aria-expanded`/`aria-controls`), labelled form fields with `aria-describedby` errors, `aria-live` success state, visible focus rings, sticky-first-column comparison tables with scoped headers, and a decorative ecosystem SVG marked `aria-hidden`. Reduced motion is honoured by the Phase 3 CSS.

---

## 12. Performance

- Relationship data is eager loaded in the resolver; the strict-mode lazy-loading guard runs in tests, so an N+1 in a listing would fail the suite.
- Query budgets are asserted: a fully related service page stays under 45 queries and the CMS home page with five data blocks under 35.
- Images use `x-ui.picture` (WebP conversions, `srcset`, dimensions, lazy loading; `fetchpriority=high` for hero and featured images).
- Model caching was removed: Laravel 13's cache stores refuse to unserialize application classes by default (`serializable_classes => false`), so the menu tree is cached as plain arrays and everything else relies on indexed queries. The 96.7 kB JavaScript bundle remains a Phase 10 item.

---

## 13. Tests

```text
php artisan test --compact  →  257 tests, 836 assertions, all passing
vendor/bin/pint --test      →  passed
npm run build               →  passed
```

New files: `tests/Feature/Site/{PublicRouting, HomePage, BlockRendering, SeoHead, RelatedContent, PreviewTemplates, QueryBudget}Test.php`, `tests/Feature/Livewire/LeadFormTest.php`. The Phase 4 `PreviewTest` was replaced by `PreviewTemplatesTest`; `ErrorPagesTest` now asserts the indexability switch.

---

## 14. Deviations

| Deviation | Reason |
|---|---|
| `/` falls back to a structural page when no home Page is published. | The brief requires the homepage to render with structural content only, while unpublished content must stay private. The fallback uses real records and the brief's own hero copy. |
| Lead form stores a `Lead` row on submit. | The brief asked for a submission interface; storing the core record through the existing model avoids a second lead system. Attribution, notifications, spam scoring and CTA click tracking remain Phase 7. |
| Eloquent models are no longer cached (CTA lookups, categories, social links, announcements). | Laravel 13 cache stores reject application classes on unserialize. Menu trees are cached as arrays instead. |
| `MARKEDGE_INDEXABLE` environment switch added. | Non-production environments must never index; per-entity robots settings apply only where the switch is on. |
| Author archives index only when a bio exists. | Avoids thin pages (architecture §3.1). |

---

## 15. Deferred Work

- Schema engine (`PageMeta::$schema` slot is ready), sitemap, robots.txt, redirect middleware and slug-change redirects, 410 handling for archived slugs behind redirects (Phase 6).
- Attribution middleware, notifications, spam scoring, CTA `/go` click tracking, rate limiting on the lead form (Phase 7).
- Public search (`/search`) and the insights search/filter UI (later phase).
- JavaScript bundle split, AVIF, HTTP caching headers, browser-level responsive verification at the listed breakpoints (Phase 10/12; layouts follow the Phase 3 responsive rules).

---

## 16. Files Changed

Recorded in the Phase 5 commit (see `git show --stat`). Highlights: `routes/web.php`, `app/Http/Controllers/Site/*`, `app/Services/Cms/{ContentResolver,PageRenderer,Breadcrumbs,RelatedContentResolver,MenuNode,MenuBuilder,CtaResolver}.php`, `app/Cms/Blocks/{BlockHydrator,BlockRenderer}.php`, `app/Seo/{PageMeta,MetaResolver}.php`, `app/Livewire/LeadForm.php`, `resources/views/pages/**`, `resources/views/components/{blocks,cards,sections,cms,seo,layouts,layout}/**`, `config/markedge.php`, `phpunit.xml`, tests.

---

## 17. Git Commit

"feat: build public website routing and entity templates" on branch main (run `git log --oneline -1` for the hash).
