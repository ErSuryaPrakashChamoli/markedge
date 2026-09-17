# Markedge — Phase 15 Implementation: Product Platform, Documentation & Product Marketing Foundation

## Starting commit
`e2815a4` (Phase 14) on branch `phase-15`.

## Final commit
The Phase 15 commit on branch `phase-15` (see `git log -1 phase-15`).

## Scope
Extend the Phase 3/12 generic product model into a platform: a four-level hierarchy (Product → Module → Feature → Capability), factual deployment and security statements, a public comparison page, per-product documentation as a first-class public entity wired into every discovery system, product interest from Phase 13 analytics in the admin, and product enquiries that continue to use the single lead pipeline.

## Architecture
```
Product (identity, slug, status, category = product_type, benefits, use cases, integrations,
         deployment[], security[], blocks, media, CTA, demo form, SEO)
 ├─ ProductModule ─┬─ ProductFeature (product_module_id nullable) ─┬─ ProductCapability
 │                 └─ …                                             └─ …
 ├─ ProductFeature (ungrouped, group_label)                       ─── ProductCapability
 ├─ ProductDocument (title, slug unique per product, section, excerpt, body, Publishable, SEO)
 └─ pageViews() → conversion_events (Phase 13, entity morph 'product')
```
Public routes: `/products/compare`, `/products/{slug}/docs`, `/products/{slug}/docs/{document}`. Documentation pages render through `PageRenderer` (same template path as every entity), get canonical/robots from `IndexabilityResolver`, breadcrumbs, `TechArticle` schema linked to the product, sitemap entries (page, index and comparison), search index type `product_document` (category = product), signed preview links and CTA resolution. A document is public only while it is published and its product is publicly visible.

## Changes
- Migration: `product_features.product_module_id`, table `product_capabilities`, `products.deployment` / `products.security` (JSON), table `product_documents`.
- Models `ProductCapability`, `ProductDocument`; relations on Product, ProductModule, ProductFeature; morph aliases `product_capability`, `product_document`; policies mapped to the `products` subject.
- `ProductComparison` service: matrix of modules and features by name across visible products; the page returns 404 with fewer than two visible products and a dash means "not listed", never "not available".
- `ProductDocumentBuilder` (TechArticle) registered in the schema graph; sitemap, search types/document builder, preview link types, public URL, breadcrumbs, related content (sibling documents) extended.
- Filament: Documentation relation manager (publish gated by `products.publish`; non-publishers can only save drafts), feature form with module selection and a capabilities repeater, Deployment and security tab with factual-statement helper text, product list columns Views (30d) and Leads (30d) from Phase 13 events and leads.
- Product page: modules list their features and capabilities; ungrouped features keep group labels; Deployment options / Security sections appear only when statements exist; a Documentation link appears only when published documents exist.
- Demo requests: unchanged path (`demoForm` → `LeadForm` → `LeadCaptureService`). Demo fields are configured per form in the Forms builder (Phase 8), which is the configurable demo-field mechanism.

## Migrations
1 (`create_product_platform_tables`), additive, reversible, MySQL 8.4 and SQLite tested.

## Tests
New: `tests/Feature/Products/ProductPlatformTest.php` (6). Full suite: 471 tests / 2,637 assertions passing (465 existing + 6 new).

## Security & privacy
Documentation and capabilities follow the product permissions; publishing requires `products.publish` and is enforced server-side, not only in the form options. Rich text passes through the existing sanitised prose component. Comparison and documentation pages carry no personal data. Screenshots remain real uploads only; nothing is generated. SVG upload block unchanged.

## Performance
Product page eager-loads modules → features → capabilities and counts published documents in the same query set; comparison loads visible products with modules and features in three queries; sitemap adds one grouped query for documentation indexes.

## Manual verification
Local MySQL: migration applied; the eight product routes registered; module → feature → capability creation, the documentation URL, sitemap generation and the comparison guard exercised with real rows inside a rolled-back transaction (the local sitemap is empty by design because MARKEDGE_INDEXABLE=false locally).

## Known limitations / NOT PROVIDED
Real module, feature, capability, deployment, security and documentation content for LMS and RMS: NOT PROVIDED (Phase 12 open items still stand). Screenshots: NOT PROVIDED. Product documentation uses simple publish states (Draft/Published/Archived), not the Phase 9 review/approval workflow or revisions.

## Deferred
Editorial workflow and revisions for documentation; documentation versioning per product release; per-product analytics page beyond the list columns and the Phase 13 interest report.

## Acceptance
Generic product architecture, hierarchy, comparison, enquiry via lead pipeline, configurable demo fields (via forms), real-screenshot policy, documentation foundation, factual security/deployment claims, product analytics: IMPLEMENTED and TESTED. Product content: BLOCKED BY EXTERNAL INPUT.

## Final status
IMPLEMENTED / TESTED (content pending product owner input).
