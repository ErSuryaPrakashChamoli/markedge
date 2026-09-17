# Markedge — Phase 16 Implementation: Business Intelligence, Automation, Integrations & AI-Readiness

## Starting commit
`bfbe0a7` (Phase 15) on branch `phase-16`.

## Final commit
The Phase 16 commit on branch `phase-16` (see `git log -1 phase-16`).

## Scope
Close the platform loop: a role-aware business intelligence page over the Phase 13 and 14 reports, a configurable and observable automation framework, notification channel adapters that degrade safely, AI-readiness interfaces with no model behind them, a versioned key-authenticated API that reuses the single lead pipeline, and an operations dashboard that reports facts only.

## Architecture
```
Events (LeadCreated, LeadStageChanged, LeadAssigned) ──▶ TriggerAutomation ──▶ RunAutomation (queued)
markedge:automation-scan (hourly: follow-up overdue, lead idle) ─────────────▶ AutomationEngine
   AutomationEngine: claim run (unique idempotency key) → ConditionMatcher → ActionRunner → AutomationRun
   ActionRunner → LeadWorkflow (assign, stage, note, follow-up) / ChannelRegistry (notify)
ChannelRegistry → DatabaseChannel | MailChannel | WebhookChannel  (each: isConfigured, status, send; never throws)
                → NotificationDelivery log (outcome only, idempotency key)
API /api/v1 → throttle:api → AuthenticateApiKey (hash compare, abilities) → controllers → LeadCaptureService / reports
App\Ai\Contracts\{LeadSummarizer, ContentAssistant, SalesAssistant, KnowledgeAssistant} → UnavailableAssistant (NOT CONFIGURED)
Business intelligence page ← BusinessReport (MarketingReport + SalesReport, shared timezone buckets)
Operations page ← OperationsStatus (health probes, queue, automation, channels, API, data, environment)
```

## Changes
- Migration: `automation_rules`, `automation_runs` (unique `idempotency_key`), `api_keys` (hash only), `notification_deliveries`.
- Automation: `RuleVocabulary` (closed set of fields, operators and actions, validated on save and on run), `ConditionMatcher`, `ActionRunner` (acts through `LeadWorkflow` so timeline, analytics and notifications stay consistent), `AutomationEngine` (idempotent claims, recorded failures, retries up to 3 with backoff via `RetryAutomationRun`), `RunAutomation` job, `TriggerAutomation` listener, `markedge:automation-scan` (hourly, one occurrence per lead per day), Filament resource for rules (Super Admin, Sales Manager).
- Channels: `NotificationChannel` contract, `DatabaseChannel`, `MailChannel` (log/array mailers count as NOT CONFIGURED), `WebhookChannel` (https only, HMAC signature, timeout, retry), `ChannelRegistry` with delivery log and idempotency.
- AI-readiness: four interfaces, `AiResponse` envelope, `UnavailableAssistant`, `AiServiceProvider`. No provider SDK, key or prompt exists in the codebase.
- API v1: `GET /health`, `GET /products`, `GET /products/{slug}`, `GET /leads`, `GET /leads/{id}`, `POST /leads`, `GET /analytics/summary`; Bearer API keys with abilities (`leads:read`, `leads:write`, `products:read`, `analytics:read`), per-key rate limit (`MARKEDGE_RATE_API`, default 60/min), `Idempotency-Key` honoured through the lead `submission_token`, attribution from UTM fields (first touch set once at creation), consent enforced when the form requires it, reads and writes audited in the activity log (`api`), API-key resource (plain key shown once, revoke).
- Business intelligence page: executive summary (funnel sessions → leads → qualified → won, aligned trend daily/weekly/monthly/quarterly), marketing (sources, content), sales (pipeline, outcomes, owners), product (views, leads, won), each gated by permission.
- Operations page (Super Admin): dependency probes, queue and failed jobs, automation runs, channel status and outcomes, API usage, data volumes, environment flags, recent automation failures.
- `MarketingReport::bucketExpression()` accepts a column so sales and marketing trends share the same buckets.

## Migrations
1 (`create_automation_and_api_tables`), additive, reversible.

## Configuration
| Key | Default | State |
| --- | --- | --- |
| `MARKEDGE_RATE_API` | 60 | CONFIGURED |
| `MARKEDGE_WEBHOOK_URL` / `_SECRET` | none | NOT CONFIGURED |
| `MARKEDGE_AI_PROVIDER` | none | NOT CONFIGURED (no implementation exists) |
| Mail channel | follows `MAIL_MAILER` | NOT CONFIGURED locally (log mailer) |

## Tests
New: `tests/Feature/Platform/AutomationTest.php` (5), `ApiTest.php` (4), `IntelligenceAndOperationsTest.php` (3). Full suite: 483 tests / 2,751 assertions passing (471 existing + 12 new).

## Security & privacy
API keys stored as SHA-256 hashes, compared by hash, revocable, optionally expiring; 401 never distinguishes unknown from revoked. Abilities enforced per route. Rate limit per key. Lead writes go through the same validation, consent and pipeline as the website; no attribution overwrite. Analytics endpoint returns aggregates only. Automation rules cannot reference arbitrary fields or run code; notification placeholders expose workflow fields only. Webhook is https-only and signed. Delivery log stores no bodies. Operations page is Super Admin only.

## Performance
Automation is queued and short-circuits when no active rule listens to a trigger. The scheduled scan chunks leads and writes one run per rule per lead per day. BI and operations pages use grouped aggregates and health probes only.

## Manual verification
Local MySQL and the running local server: 7 API routes registered; unauthenticated call 401; a temporary key (deleted afterwards) returned products and analytics and got 403 for leads without the ability; `markedge:automation-scan` ran; schedule lists the four Markedge commands; `OperationsStatus` reported database/cache/queue ok, mail and webhook NOT CONFIGURED, AI NOT CONFIGURED.

## Known limitations
No LLM, no external monitoring, no e-mail or webhook endpoint configured; the scheduler is not verified from inside the app; automation acts on leads only (content automation deferred); API has no OAuth or per-user tokens (keys are integration-scoped).

## Deferred
Content-side automation triggers, API resources for content, OAuth, provider-backed AI implementations behind the shipped interfaces.

## Acceptance
Role-aware dashboards, business funnel, trend reporting, performance views, automation framework (idempotent, retry-safe, observable), rule engine, channel adapters, AI-readiness interfaces without LLM, versioned authenticated rate-limited API, auditability, data integrity, operational dashboards: IMPLEMENTED and TESTED. External endpoints and providers: NOT CONFIGURED.

## Final status
IMPLEMENTED / TESTED (integrations pending external configuration).
