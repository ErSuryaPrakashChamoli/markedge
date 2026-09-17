# Markedge — Phase 12 Implementation: Content, SEO & Organic Acquisition

## 1. Executive Summary

Phase 12 replaced an empty catalogue (every service, solution, industry and product field was null, every page a placeholder) with a complete, honest content layer for the commercial website, organised around BUILD / OPERATE / GROW and delivered through the CMS and its editorial workflow. It was prepared on the current baseline at the user's direction, although the Phase 11B production gate remains unmet; publication on production happens through the same seeder and workflow once infrastructure exists.

Delivered as a versioned content library (`database/content/*.php`) applied by an idempotent seeder that never changes publication status:

- 24 service pages (BUILD 9, OPERATE 7, GROW 8), each with tagline, summary, overview, benefits, capabilities, process, deliverables, 2–3 genuine FAQs, SEO title and description, and related solutions, industries and products.
- 3 pillar pages, 8 solution pages, 13 industry pages (8 priority industries with distinct problem-led content, 5 concise drafts), 2 product pages limited to purpose and enquiry path, 8 site pages (home copy, About, Contact and five request pages with post-submission explanations and consent), privacy and cookie policy drafts written from the system's actual behaviour, 3 cornerstone article drafts, a keyword/intent map, five topic clusters, a CTA strategy, a 90-day editorial plan and an off-page governance process.
- Locally, 44 records were published through the Phase 9 workflow and audited: no placeholders, no fabricated claims, every CTA destination resolves, every internal link resolves, FAQ schema is generated only from rendered FAQs, search reindexed to 51 documents, query budgets unchanged.

Not published and explicitly open: product modules, integrations and screenshots (product owner), real authors (articles stay in draft), company facts for About (history, leadership, address), legal review of the policy drafts, hero imagery, and the five secondary industries.

## 2. Starting Commit

`e60f2d5` on `phase-11` (Phase 11 release candidate; Phase 11B produced no newer commit because no infrastructure existed). Work on branch `phase-12`.

## 3. Content Strategy

Principle: **information → trust → enquiry**. Commercial pages explain what a service is, what problem it solves, who needs it, what Markedge delivers, how an engagement runs and what to expect, then answer real questions and offer one appropriate next step. Informational content (articles) educates first and links to the commercial page and its CTA. Nothing is claimed that cannot be evidenced: no clients, counts, years, certifications, awards, results, testimonials, technology vendor lists or service levels.

## 4. Information Architecture

Existing CMS taxonomy reused without new entities:

```
Home
├── Technology (BUILD): Software Development · Web Development · Mobile Application Development · Product Development · Custom Business Applications · API Development & Integration · AI & Automation · UI/UX Design · Cloud Solutions
├── IT Infrastructure (OPERATE): IT AMC · Networking · Server & Infrastructure Management · Cloud Infrastructure · Cybersecurity · Backup & Disaster Recovery · IT Support
├── Digital Growth (GROW): Digital Marketing · SEO · Social Media Marketing · Performance Marketing · Content Marketing · Branding · Lead Generation · Conversion Optimisation
├── Products: Lead Management System · Recruitment Management System
├── Solutions (8) · Industries (13; 8 priority) · Insights · Case Studies (empty by design) · About · Contact + 5 request pages
```

Two seeded services differ from the brief's list by name only (Product Development and Cloud Solutions exist alongside Cloud Infrastructure); no duplicates were created. Solutions map business problems to services; industries map industry problems to services, solutions and products.

## 5. Keyword Research

Performed as qualitative intent research (no search-volume tooling is available in this environment; volumes are therefore not claimed). For each commercial page the library records the primary intent and the user problem in its copy; the mapping below records the target topic and search type. Geographic intent is not applied because no verified business location exists yet (see §31).

## 6. Keyword Mapping

| Page | Primary intent | Primary topic | Search type | Secondary topics |
|---|---|---|---|---|
| Software Development | commercial | software development services | commercial | custom software company, business software development |
| Web Development | commercial | web development services | commercial | corporate website development, web application development |
| Mobile Application Development | commercial | mobile app development services | commercial | Android/iOS app development, field app |
| Product Development | commercial | software product development | commercial | MVP development, SaaS product development |
| Custom Business Applications | commercial | custom business application development | commercial | workflow application, replace spreadsheets |
| API Development & Integration | commercial | API development and system integration | commercial | CRM ERP integration, middleware |
| AI & Automation | commercial | business process automation services | commercial | document automation, AI automation for business |
| UI/UX Design | commercial | UI UX design services | commercial | design system, usability testing |
| Cloud Solutions | commercial | cloud application development / migration | commercial | cloud migration services |
| IT AMC | commercial | IT AMC services | commercial | annual maintenance contract IT, managed IT |
| Networking | commercial | business network setup services | commercial | office network, wireless network design |
| Server & Infrastructure Management | commercial | server management services | commercial | infrastructure management |
| Cloud Infrastructure | commercial | managed cloud services | commercial | cloud operations, cloud cost management |
| Cybersecurity | commercial | cybersecurity services for business | commercial | security assessment, incident response readiness |
| Backup & Disaster Recovery | commercial | backup and disaster recovery services | commercial | business continuity, ransomware recovery |
| IT Support | commercial | IT support services for business | commercial | help desk, on-site support |
| Digital Marketing | commercial | digital marketing services | commercial | digital marketing agency B2B |
| SEO | commercial | SEO services | commercial | technical SEO, B2B SEO |
| Social Media Marketing | commercial | social media marketing services | commercial | B2B social media |
| Performance Marketing | commercial | performance marketing / paid media | commercial | PPC management, cost per lead |
| Content Marketing | commercial | content marketing services | commercial | B2B content strategy |
| Branding | commercial | branding services | commercial | positioning, visual identity |
| Lead Generation | commercial | B2B lead generation services | commercial | lead capture, qualification |
| Conversion Optimisation | commercial | conversion rate optimisation services | commercial | CRO, landing page optimisation |
| Lead Management System | commercial/product | lead management software | commercial | lead tracking software |
| Recruitment Management System | commercial/product | recruitment management software | commercial | applicant tracking, hiring workflow |
| Solutions (8) | commercial investigation | problem phrases (sales automation, recruitment automation, IT modernisation…) | commercial | mapped services |
| Industries (8 priority) | commercial investigation | "technology services for {industry}" | commercial | industry problems |
| Articles (3 drafts) | informational / commercial investigation | what SEO means for B2B; custom vs off-the-shelf; what an IT AMC includes | informational | cluster entry points |

One page per consolidated intent; variants are handled inside the page, never as separate pages.

## 7. Service Content

All 24 services follow the standard (what, problem, who, deliverables, engagement, expectations, FAQ, CTA) using the existing service fields: tagline, short description, overview, benefits, capabilities (features), process, deliverables, FAQs, SEO row and relations. Technology names are deliberately absent: the "technology/expertise" element is represented as approach and deliverables until the engineering team confirms the supported stack, at which point the technologies relation can be populated in the CMS without copy changes. Service-level targets, response times and ranking or ROI promises are explicitly declined in the copy.

## 8. Product Content

Product pages describe purpose, audience, benefits and use cases and route to a demo; modules, feature lists, integrations, security posture, deployment options and screenshots are **not stated** because they were not confirmed. The demo form is attached to both products. Open items for the product owner: module list, integrations, deployment model, security statement, screenshots with alt text.

## 9. Solution Content

Eight solutions each state the business problem, Markedge's approach and what changes, linked to the services, industries and products that deliver them. No outcome figures.

## 10. Industry Content

Eight priority industries (healthcare, manufacturing, real estate, education, professional services, retail, logistics, recruitment) have distinct context, challenges and relevant capabilities; none shares paragraphs. Five secondary industries (BFSI, technology, start-ups, SMEs, enterprises) carry concise unique copy and remain drafts for expansion.

## 11. About Content

Written from what is known: the three capabilities, the two products, the way engagements work and a short set of principles. Company history, leadership, team size, locations and contact details are absent, not invented; the page states nothing about them. A "Talk to us" CTA closes the page.

## 12. Conversion Pages

Footer links to unpublished pages (careers, terms, the policy drafts in review) and to the Recruitment Management System (still a draft product) are hidden by the seeder until those records are published, so the site never links to a 404; the consent statement refers to the privacy policy by name until the reviewed policy page is published. Contact plus five request pages (consultation, quote, demo, IT assessment, digital growth audit) now have intent-specific intros, an explanation of what happens after submission, the configured form with consent required, a privacy-policy reference in the consent statement, success and error states through the existing Livewire form, and attribution via Phase 8. All were published locally through the workflow; every active CTA now resolves to a 200 page (the Phase 11 finding is closed).

## 13. Insight Strategy

Editorial clusters map to the pillars and to buying questions: SEO & digital growth, software decisions, IT operations, sales & lead management, recruitment operations. Each cluster has one commercial hub (the service or product page) and informational, comparison and implementation spokes, all linking back to the hub and its CTA.

## 14. Article Standards

Introduction that states the problem, structured H2/H3 sections, practical guidance, internal links to the cluster hub, a cluster-appropriate CTA, real author, publication and update dates, metadata and Article schema (Phase 6). Word count is not a metric. Three cornerstone drafts exist; each follows the standard except authorship (§15).

## 15. Authors

No real author information was provided, so no author was created and the three articles remain drafts (content health flags "missing author" by design). Publication requires assigning a real author with an accurate bio in the CMS.

## 16. Case Studies

None exist; the section stays empty (the home case-study block renders nothing until real case studies are published). No fictional projects were created.

## 17. Testimonials

None; no seeded testimonials, and the schema engine still emits no Review or AggregateRating.

## 18. Internal Linking

Service → related services (same pillar), solutions, industries and products through explicit relations set by the library; solutions and industries link back to services and products; conversion pages are linked from the header CTA, every entity CTA, the footer "Get in touch" column added by the seeder, and the contact page. Article drafts link to their hub services. Audit: every internal link on every sitemap URL resolves (70 unique links, 0 broken) and no sitemap URL has fewer than two inbound links (median 128 inbound per page, driven by navigation, footer and related-content sections) (§34).

## 19. Topic Clusters

1. **SEO & digital growth** (hub: /services/seo, /services/digital-marketing): what SEO means for B2B (draft), technical SEO checklist, on-page vs off-page, local SEO when it applies, SEO audit explained, B2B SEO, measuring SEO honestly.
2. **Software decisions** (hub: /services/software-development, /services/custom-business-applications): custom vs off-the-shelf (draft), scoping an MVP, integration options when a system has no API, owning your source code.
3. **IT operations** (hub: /services/it-amc, /services/backup-disaster-recovery, /services/cybersecurity): what an IT AMC should include (draft), backup vs disaster recovery, security fundamentals for small businesses, network segmentation explained.
4. **Sales & lead management** (hub: /products/lead-management-system, /services/lead-generation): from enquiry to opportunity, attribution basics, follow-up discipline.
5. **Recruitment operations** (hub: /products/recruitment-management-system): structuring a hiring process, reducing time in stage, candidate communication.

## 20. On-Page SEO

Each published page has one H1 (entity name or page title), logical H2/H3 from the templates and copy, a specific SEO title and description (advisory guidance only, no hard limits, per Phase 6), descriptive anchors in related-content sections, and an intent-appropriate CTA. Titles are unique across the sitemap (audit §34).

## 21. Technical SEO

Unchanged Phase 6/10 architecture verified with the content in place: canonical per page, `index, follow` on commercial pages, `noindex, follow` on search, 410 for archived, valid sitemap with `lastmod`, robots allowing crawl and blocking private paths, redirects intact, image pipeline and query budgets intact (§33).

## 22. Structured Data

Verified per type on local pages: Organization, WebSite (SearchAction), WebPage and BreadcrumbList everywhere; Service on service and pillar pages; SoftwareApplication on products; FAQPage only where FAQs render (services, products, industries with FAQs). No Review, AggregateRating, Offer or price anywhere (test-guarded).

## 23. Image SEO

The Phase 10 pipeline (responsive WebP/AVIF, intrinsic dimensions, lazy loading, alt text from the media library) is in place, but no real imagery exists yet: pillar hero images, product screenshots and article images are open content tasks. Placeholders hold layout; nothing decorative is described as content.

## 24. Indexation

Indexable: published services, pillars, solutions, priority industries, products, home, about, contact and request pages. Not indexable by design: search, previews, `/go`, admin, drafts (careers, terms, five secondary industries, article drafts), review-state policy pages until published. Local verification used a temporary `MARKEDGE_INDEXABLE=true`; production keeps the Phase 6 flag rule.

## 25. Search Console

Not available: no production domain (Phase 11B). Procedure documented in the Phase 11 report; findings will be recorded once the property exists.

## 26. Content Health

Report after seeding (local MySQL): 0 invalid blocks, 0 broken links, 0 orphaned pages, 0 expired, 0 canonicalized, missing summary only on unpublished legal/careers drafts, missing image on the three pillar heroes and three article drafts, missing author on the three article drafts, 63 records without an editorial owner (no real users to assign). A local `noindex` left on the SEO service by Phase 5 verification was cleared. Two defects found and fixed while auditing: the orphan query used `||` (logical OR on MySQL) instead of string concatenation, and the per-record auditor lazy-loaded SEO rows.

## 27. Editorial Workflow

Content enters through the seeder as copy only; status is never set by the seeder except moving the two policy drafts to Review for legal sign-off. Locally, 44 records were taken Draft → Review → Published through `Publisher` (checklist passed for all), revisions recorded each change, and the search index synchronised. Production follows the same steps in the admin.

## 28. 90-Day Content Plan

Sized for a small team (one piece per week plus one hub refresh per fortnight). Priority order: (weeks 1–2) assign real authors, publish the three cornerstone articles, add pillar hero images and product facts; (weeks 3–6) SEO cluster: technical SEO checklist, SEO audit explained, measuring SEO honestly; software cluster: scoping an MVP, integration when a system has no API; (weeks 7–10) IT operations cluster: backup vs disaster recovery, security fundamentals, network segmentation; sales cluster: from enquiry to opportunity; (weeks 11–13) recruitment cluster: structuring a hiring process; expand the five secondary industries; review search console data (if production exists) and refresh titles on high-impression, low-click pages. Each piece carries cluster, intent, funnel stage, audience and CTA in the library format.

## 29. Organic Acquisition

Funnel mapping: awareness (cluster articles) → consideration (comparison and implementation articles, solutions) → decision (service, product, industry pages) → conversion (request pages) → enquiry (lead with attribution). Content success will be measured with first-party data only (impressions/clicks via Search Console when available, enquiries and CTA clicks via Phase 8 reporting); none exists yet: **not yet measurable**.

## 30. Off-Page Strategy

Legitimate only: distribute cluster content through the company's professional network profiles, industry communities and partner channels; earn references with useful guides and, when they exist, genuine case studies and original research; prospect relevant sites by topical fit and editorial quality, track outreach and links in a shared sheet, monitor brand mentions monthly. Prohibited: paid links, directory spam, PBNs, irrelevant guest posts, automated outreach.

## 31. Local SEO

Not implemented: no verified business address, office or service area exists in the CMS settings (contact settings are empty). When confirmed, organisation address and contact details go into settings and schema automatically; no city pages will be created without a real presence.

## 32. Conversion Strategy

CTA mapping by context using existing CTA records: services under Technology → Request a Consultation; IT Infrastructure → Request an IT Assessment (`request-it-assessment` on assessment-led services) or consultation; Digital Growth → Request a Digital Growth Audit; products → Book a Demo; articles → the hub's CTA; site default → Start a Conversation; header → Talk to Us. Conversion pages explain what is asked, why, and what happens next; forms request only the fields configured per form.

## 33. Performance Impact

Content increased page payloads and the number of related items but not query shapes. Local MySQL, warm, in-process (database cache store, so cache reads count as queries): home 33 queries / 109 ms / 137 kB HTML (Phase 10 after optimisation: 23 / 65 ms with sparse content); service 32 / 76–84 ms / 112 kB (was 23 / 50 ms); product 31 / 80 ms; solution 22 / 66 ms; industry 19 / 68 ms; about 14 / 43 ms; contact 19 / 48 ms; request page 20 / 53 ms; search 18 / 49 ms; sitemap 3 / 2.5 ms. The increase on home and entity pages comes from related-content sections now having real relations to load (one eager load per related collection, no N+1); the query-budget and N+1 tests still pass unchanged. HTML is served gzip/Brotli at the edge (Phase 10 nginx reference). Search index: 51 documents. Not re-measured on production.

## 34. Content Audit

Local audit with `MARKEDGE_INDEXABLE=true`: 58 sitemap URLs, duplicate URLs 0, duplicate titles 0, duplicate descriptions 0, 70 unique internal links crawled from every sitemap URL with 0 non-200 targets, low-inbound pages 0, CTA destinations 8/8 resolving to 200, placeholders in published copy 0, schema per type as listed in §22, search returning results for pillar terms. Answers to the ten audit questions are yes for every published page except "real author" for articles (drafts) and "images" (pending real assets).

## 35. Measurements

Pages created: 0 new entities (all content applied to existing records; 3 article drafts created). Published locally: 24 services, 3 pillars, 8 solutions, 8 industries, 8 pages (44 through the workflow; products already active). Articles: 3 drafts. Keyword mappings: 26 commercial pages + 8 solutions + 8 industries + 3 articles. Topic clusters: 5. Search index: 51 documents. Traffic, impressions, clicks, enquiries: **not yet measurable**.

## 36. Known Limitations

- Prepared before the Phase 11B production gate; nothing is live.
- No search-volume data; mapping is intent-based.
- Technologies, product modules/integrations/screenshots, authors, company facts, legal review and imagery are pending real inputs.
- Five industries remain concise drafts.
- Case studies and testimonials intentionally empty.

## 37. Deferred Work

Author assignment and article publication; product facts and screenshots; pillar and article imagery; legal review of policy drafts and the Terms page; Careers page; secondary industries; Search Console property and data loop; local SEO if a real presence is confirmed; cluster spokes per the 90-day plan.

## 38. Test Results

Recorded in the delivery message (full suite, Pint, Vite). New: `tests/Feature/Content/Phase12ContentTest.php` (completeness and honesty patterns, idempotency and status safety, schema and internal links on a published service, conversion pages with forms and consent).

## 39. Final Acceptance

Content, SEO architecture, internal linking, clusters, CTA strategy, editorial plan and off-page governance are complete and verified locally; acceptance items that depend on production (Search Console, real measurements), on real inputs (authors, product facts, imagery, legal sign-off) or on the Phase 11B gate remain open and are listed in §36–§37.
