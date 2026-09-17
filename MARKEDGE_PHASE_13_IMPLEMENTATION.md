# Markedge — Phase 13 Implementation: Analytics, Attribution Intelligence & Marketing Performance

## Starting commit
`34668d9` (Phase 12) on branch `phase-13`.

## Final commit
The Phase 13 commit on branch `phase-13` (see `git log -1 phase-13`).

## Scope
Turn the Phase 8 conversion and attribution foundation into a first-party measurement layer: sessions and page views, one analytics service with privacy and bot rules, a controlled event taxonomy including sales outcomes, funnel, trend, path, content and interest reporting for marketing users, retention rules and tests. Nothing in the attribution architecture was replaced; first touch stays immutable.

## Architecture
```
Visitor (mk_attr visitor id)
 └─ Session (rolling 30-minute id stored in the same cookie)
     ├─ PageViewed   (public HTML 200, not bots/previews; entity + landing flag)
     ├─ SearchPerformed
     ├─ CtaClicked
     ├─ FormSubmitted → LeadCreated
     └─ LeadQualified / LeadConverted (from sales status changes)
```
[Analytics](app/Analytics/Analytics.php) is the only writer of `conversion_events`: it applies the consent gate (when `privacy.attribution_requires_consent` is on and no consent cookie exists, events are counted without visitor/session ids), bounds metadata (12 keys, 160 characters), normalises paths, attaches the visitor's last-touch source/medium/campaign and never throws. Page views are one bounded insert after the response is built. [MarketingReport](app/Reports/MarketingReport.php) extends the Phase 8 `ConversionReport` with traffic, funnel, trends (daily/weekly/monthly/quarterly in the application timezone), conversion paths (latest 500 leads, collapsed event sequences), content performance, product and service interest, and sessions by landing source.

## Changes
- Migration: `session_id` on `conversion_events` plus indexes `(type, path, created_at)` and `(session_id, type)`.
- `ConversionEventType`: `page_viewed`, `lead_qualified`, `lead_converted` added (seven types total, each with business meaning).
- `Attribution` cookie carries `sid`/`sat`; `touchSession()` rotates after the configured gap.
- `CaptureAttribution` records page views through `Analytics` and stores the rendered entity from `PageRenderer`.
- Search, CTA and lead-capture recorders now call `Analytics` (behaviour unchanged, one code path).
- `LeadObserver` records `lead_qualified` / `lead_converted` once per lead with the lead's own attribution.
- `ConversionReports` page renamed "Marketing analytics": traffic tiles, funnel with rates only where a denominator exists, trend with granularity switch, conversion paths, content performance, product and service interest, sessions by source.
- Retention: page views pruned after `MARKEDGE_PAGEVIEW_RETENTION_DAYS` (90); other events after 400 days; leads never touched.
- Config: `markedge.analytics.session_minutes`, `bot_pattern`, `pageview_retention_days`.

## Migrations
1 (`add_sessions_to_conversion_events_table`), additive, reversible.

## Tests
New: `tests/Feature/Analytics/PageViewTest.php` (6) and `MarketingReportTest.php` (5). Adjusted: attribution query-count test and query-budget helper exclude the single page-view insert; search-event test filters by type. Full suite: 457 tests / 2,403 assertions passing (446 existing + 11 new).

## Security & privacy
No personal data in events; identifiers are anonymous UUIDs, withheld entirely without consent when consent is required; UTM values remain bounded and normalised (Phase 8); marketing users see aggregates only (no UUIDs in the page, test-guarded); Sales cannot open the analytics page; technical fields remain super-admin.

## Performance
Page view: one insert per HTML page, no extra reads (settings memoised per request). Reports: GROUP BY aggregation; the full report builds in under 30 queries (test). Query budgets unchanged.

## Manual verification
Local: browsed public pages with a browser user agent and confirmed `page_viewed` rows with entity, session and landing flags; the marketing analytics page renders funnel, trend, paths and interest sections for a Marketing Manager.

## Known limitations
No real traffic exists, so every report is empty until production; `FormStarted` and `CTAViewed` were deliberately not added (no reliable server-side signal, and viewed-CTA counts would be noise); session and visitor counts are cookie-based and undercount cookie-blocking visitors, which is documented rather than corrected.

## Deferred
Daily roll-up table if raw page views grow beyond what 90-day retention keeps manageable; Search Console import once a production property exists.

## Acceptance
Analytics architecture, event taxonomy, UTM support (Phase 8), attribution reporting, funnel, dashboards, privacy controls, retention, authorization, tests and documentation: IMPLEMENTED and TESTED. Real-traffic results: NOT AVAILABLE.

## Final status
IMPLEMENTED / TESTED (production measurement pending Phase 11B).
