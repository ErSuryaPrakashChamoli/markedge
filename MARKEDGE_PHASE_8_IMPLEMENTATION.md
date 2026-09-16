# Markedge — Phase 8 Implementation: Conversion Engine, Lead Capture, Attribution, CTA Tracking & First-Party Analytics

## 1. Objective

One deterministic conversion pipeline: visitor → page → CTA → form → validation → consent → attribution → Lead → conversion events → notification hook → admin reporting. No third-party analytics, no marketing automation.

## 2. Starting commit

`b3d9fb4` (Phase 7 approved). Phases 1–7 preserved; no existing architecture rewritten.

## 3. Lead capture architecture

- `App\Leads\LeadCaptureService::capture(LeadSubmission): LeadCaptureResult` is the only code that creates a Lead. `LeadSubmission` carries validated core fields, custom answers, server-resolved relations, the visitor's `Attribution`, the conversion path, consent state, the submission token and request metadata.
- `App\Livewire\LeadForm` validates (Phase 5 rules retained: server-side validation, honeypot, consent, published-form resolution, option whitelists) and delegates to the service. Every public form (contact, quote, consultation, demo, assessment, audit, landing pages, CTA/lead blocks) renders through this component, so every submission uses the pipeline.
- Inside one transaction the service: resolves the campaign (last-touch then first-touch `utm_campaign` against `campaigns.utm_campaign`), checks the duplicate policy, creates the Lead (Phase 2 columns; no duplicated fields), writes `form_submitted` and `lead_created` events. After commit it dispatches `LeadCreated`.

## 4. Form architecture

Forms remain CMS records (`forms`, `form_fields`). `form_id` on the lead is the form identity (keys such as `general-enquiry`, `quote-request`, `product-demo`). New column `forms.consent_text` (admin-editable consent statement, default text in `Form::DEFAULT_CONSENT_TEXT`). The block hydrator and entity templates keep passing trusted `context` (service, product, landing page) which is a Livewire `#[Locked]` property; the conversion path and submission token are also locked. Editing any of them from the client throws.

## 5. Attribution architecture

`app/Attribution/`: `Touch` (source, medium, campaign, term, content, referrer host, landing page, timestamp), `Attribution` (visitor UUID, first touch, last touch, visit count, last CTA click), `TouchDetector`, `Normaliser`, `AttributionCookie`, plus `App\Http\Middleware\CaptureAttribution` appended to the `web` group. The middleware runs on public HTML GET responses only (admin, livewire, preview, go, sitemap, robots and non-HTML are skipped), does no database work, and rewrites the cookie only when the state changed. On conversion the service copies the touches into the flat `first_*` / `last_*` lead columns (Phase 2) and stores `visitor_id`.

## 6. First-touch rules

Set once, on the visitor's first page view, and never overwritten. If the first visit carries no signal the first touch is stored with `source`/`medium` null (reported as "Direct / Unknown") plus the landing page and time. Tested: google then linkedin keeps google as first touch.

## 7. Last-touch rules

Replaced by every subsequent real touch (UTM, click id or external referrer). A direct revisit does not overwrite it (`markedge.attribution.direct_overwrites_last_touch = false`, documented and configurable). The first visit sets last = first.

## 8. UTM handling

`utm_source`, `utm_medium`, `utm_campaign`, `utm_term`, `utm_content` only. Non-string values are ignored; control characters stripped; whitespace collapsed; source/medium/campaign lower-cased; every value capped at 120 characters. `gclid` → google/cpc and `fbclid` → facebook/paid_social (the ids themselves are not stored). Values are only ever rendered through Blade escaping in the admin; never as HTML.

## 9. Referrer handling

Only the host is kept (never path or query). Internal referrers are ignored. Known hosts classify to organic/social; unknown external hosts are stored as `host / referral`. Absence of a referrer is reported as "Direct / Unknown", never asserted as direct.

## 10. Landing-page attribution

`first_landing_page` (first visit), `last_landing_page` (last touch) and `submitted_from_url` (conversion page: the internal path of the page that rendered the form, captured server-side at mount, validated as an internal path) are all kept separately.

## 11. CTA tracking

- Route `GET /go/{key}/{slot?}` (`cta.go`, throttle 60/min) → `CtaClickController`. The CTA is looked up by key and must be active; the destination is resolved by `CtaResolver` from the CTA record and passed through `CtaDestination::safe()`. Nothing in the request can change the destination (`to=` is ignored; `p=` is validated as an internal path; `e=` is only the entity name for the WhatsApp message).
- Allowed destinations: internal paths, absolute URLs on the site's own host or on `markedge.cta.allowed_external_hosts` (default `wa.me`, `api.whatsapp.com`) plus the Phase 6 redirect allow-list, and `tel:` / `mailto:`. `javascript:`, `data:`, `vbscript:`, protocol-relative and unknown hosts → 404.
- Records `cta_clicks` (cta, action, page path, visitor id, target, last-touch source/medium/campaign, matched campaign, timestamp) and a `cta_clicked` conversion event, increments `ctas.click_count` with a query-builder increment (no model events, so no content-cache invalidation), stores the click on the attribution cookie so the next lead within `cta_window_minutes` (60) gets `cta_id`.
- Any recording failure is reported and the redirect still happens (tested by dropping the table).
- `CtaResolver::trackedHref()` is used by the CTA button component, CTA block, header, footer, mobile drawer and sticky mobile bar. Tracked links carry `rel="nofollow"`, the response carries `X-Robots-Tag: noindex, nofollow`, and `/go` was already disallowed in robots.txt (Phase 6).

## 12. Conversion events

`conversion_events` + `ConversionEvent` model + `ConversionEventType` enum with exactly four types: `form_submitted`, `lead_created`, `cta_clicked`, `search_performed`. Columns: type, lead/form/cta/campaign ids, path, entity morph, visitor id, last-touch source/medium/campaign, small `meta` JSON (slot/action for CTA, query/results/type for search), timestamp. No request bodies, headers, cookies, IPs or personal data.

## 13. Duplicate / idempotency behaviour

- Idempotency: a 48-character token issued when the form renders is stored in `leads.submission_token` (unique). A retried submission with the same token returns the original lead and stores nothing.
- Duplicate policy: same form + same email (or phone when no email) within `markedge.leads.duplicate_window_hours` (24) → the new enquiry is still stored (nothing is lost) but linked via `duplicate_of_lead_id` and it does not trigger a notification. Different forms, or submissions after the window, are independent leads. Leads are never merged.
- Rate limit: `markedge.leads.submissions_per_minute` (5) per IP, enforced in the component with a validation message.

## 14. Consent

Forms with `requires_consent` require the checkbox (`accepted`). The lead stores `consent_given_at` and `consent_text` (the exact statement shown). Forms without the requirement store neither. Consent is never inferred and no marketing opt-in is implied.

## 15. Privacy / data minimisation

The cookie holds UTM values, referrer host, paths, a UUID and a CTA id: no personal data. IP is stored only when `MARKEDGE_STORE_LEAD_IP` is true (default off). Events hold ids, paths and dimensions only. When `privacy.attribution_requires_consent` is enabled in settings the cookie is only written once an `mk_consent` cookie exists.

## 16. Notification architecture

`App\Events\LeadCreated(leadId, duplicate)` is dispatched after commit. `App\Listeners\SendLeadNotification` (queued, auto-discovered) emails `App\Mail\NewLeadNotification` to the form's `notify_emails`, falling back to `leads.notify_emails` in settings; nothing is sent without recipients or for duplicates. Failures are reported and swallowed, so the stored lead is unaffected (tested). Slack, CRM and WhatsApp listeners can subscribe to the same event later.

## 17. Admin reporting

- `Admin → Leads → Conversion reports` (`ConversionReports`, gated by `leads.export`, i.e. Super Admin and Marketing Manager): Today / Yesterday / Last 7 / Last 30 / This month / Previous month / Custom, evaluated in `app.timezone` and labelled as such. Totals (leads, form submissions, CTA clicks, searches, leads preceded by a search) and tables for first-touch and last-touch source/medium, UTM campaign, matched campaign, form, service, product, solution, first landing page, conversion page, CTA clicks by CTA and by page. Top search terms are shown only to SEO/super-admin users and only as aggregates.
- Counts only: no conversion rate is displayed because there is no reliable visit denominator.
- `App\Reports\ConversionReport` uses `COUNT` / `GROUP BY` / joins; the test asserts nine grouped queries and no model hydration.
- Lead resource: conversion page, first landing page, consent state, CTA clicked and duplicate link on the Enquiry tab; Attribution tab and source/campaign columns visible with `leads.export`; Technical tab (visitor id, user agent, consent text, token) super-admin only. Sales sees the enquiry workflow only. CSV export now includes landing page, conversion page and consent time.
- Dashboard `LeadsOverview` adds "Today" and "CTA clicks (30 days)". The Phase 2 `LeadSources` page is replaced by the reports page.

## 18. Search-event handling

`App\Listeners\RecordSearchEvent` (sync, try/catch) stores a `search_performed` event with the lower-cased query (≤120 chars), result count, type filter and the anonymous visitor id. The report shows aggregated top terms and "leads preceded by a search" (same visitor id, search before the lead). No per-visitor history is displayed anywhere, and no causal claim is made.

## 19. Security

Forms: bindings only (SQL injection test), Blade escaping in public and admin (XSS test), Livewire CSRF, max lengths, honeypot, rate limit, idempotency, locked context (hidden-field tampering throws). Attribution: bounded and cleaned values, array parameters ignored, encrypted+signed cookie so tampering yields a fresh state, malformed shapes discarded. CTA: no user-supplied destination, scheme and host allow-list, inactive/unknown → 404, throttle. Admin: policies unchanged, reporting gated by `leads.export`, technical data super-admin only, Editor forbidden (tests).

## 20. Database changes

Two migrations: `create_conversion_events_table` and `add_conversion_tracking_columns` (leads: `submission_token` unique, `consent_text`, indexes on first/last medium and campaign and on `submitted_from_url`; cta_clicks: `visitor_id`, `target`, `source`, `medium`, `campaign`, index on `created_at`; forms: `consent_text`). Existing Phase 2 attribution columns reused unchanged.

## 21. Performance

Attribution middleware adds zero queries (tested: same query count with and without UTM). CTA click: one CTA lookup, two inserts, one increment. Reports are pure aggregation with bounded `LIMIT`s. Event tables are indexed on type, created_at and visitor id. Retention: `php artisan markedge:events-prune` (scheduled daily, `MARKEDGE_EVENT_RETENTION_DAYS`, default 400, minimum 30) deletes events and clicks only; leads are never deleted by it (tested).

## 22. Tests

38 new tests in `tests/Feature/Conversion/`: `AttributionTest` (9), `LeadCaptureTest` (12), `CtaClickTest` (9), `ReportingTest` (6), `SearchEventTest` (2). `HeaderTest` updated for the tracked CTA link. Full suite: 388 tests, 1,483 assertions, Pint clean, Vite built, JS bundle unchanged.

## 23. Manual verification (local MySQL)

- Journey: `/services/software-development?utm_source=google&utm_medium=cpc&utm_campaign=q4` → `/products?utm_source=linkedin&utm_medium=social` → direct `/about`: cookie set on the first visit, retained on the direct revisit.
- CTA click through `/go/request-consultation?p=/services/software-development` with that cookie: 302 to `/request-consultation`; stored click has path `/services/software-development`, target `/request-consultation`, source `linkedin`, medium `social` (last touch), first touch google preserved on the cookie.
- Tracked links present on the service page hero, band and header; `/go/unknown-key` and `/go/request-consultation/evil` → 404; `?to=https://evil.example` ignored.
- `/search?q=software` recorded a `search_performed` event with `{"query":"software","results":1}`.
- Contact page (temporarily published locally, reverted to draft afterwards) renders the form with the honeypot field, the submission token and source path `/contact` in the Livewire snapshot, and `noindex, nofollow` (non-indexable local environment). The seeded enquiry form does not require consent, so the consent statement is covered by the automated tests.
- Form submission end to end (lead + events + mail) is verified through the Livewire test harness; the local seeded conversion pages remain drafts by policy.

## 24. Deviations

- Reporting access reuses the existing `leads.export` permission instead of adding a new permission action to every subject; documented in the roles matrix semantics (Marketing Manager and Super Admin).
- Contact channel links in the sticky mobile bar (phone/WhatsApp from settings) are not tracked because they are not CTA records; CTA records with phone/email/WhatsApp actions are tracked.
- Email notification is implemented with the framework mailer (no external integration).

## 25. Deferred items

External analytics (GA4, GTM, pixels), Slack/CRM/WhatsApp delivery, cookie banner UI (the consent gate exists), device/browser parsing, spam scoring beyond honeypot and rate limiting, per-lead timeline UI, conversion rates once a reliable visit denominator exists.

## 26. Final commit

`feat: implement conversion tracking and attribution`
