# Markedge — Phase 14 Implementation: Sales, CRM Foundation & Lead Lifecycle

## Starting commit
`22d6c32` (Phase 13) on branch `phase-14`.

## Final commit
The Phase 14 commit on branch `phase-14` (see `git log -1 phase-14`).

## Scope
Turn the Phase 8 enquiry inbox into a working sales pipeline without replacing it: a configurable lifecycle, ownership, priority and teams, a timeline, follow-ups with reminders, notes, configurable qualification questions, a board view, a sales dashboard, owner notifications, config-only SLA reporting and audited, permission-gated exports. The canonical lead pipeline (`LeadCaptureService`), attribution snapshot and Phase 13 analytics are unchanged; sales outcomes still feed the marketing funnel through `LeadObserver`.

## Architecture
```
LeadCaptureService (unchanged)  ──creates──▶  Lead (status New)
                                                 │
        LeadWorkflow (single writer of sales state)
        ├─ transition()  matrix from config, lock, timestamps, reason, timeline, LeadStageChanged
        ├─ assign()      owner/team, New → Assigned, timeline, LeadAssigned
        ├─ addNote()     timeline note
        ├─ scheduleFollowUp() / completeFollowUp()  lead_follow_ups + next_follow_up_at
        └─ qualify()     configured questions only, bounded answers
                                                 │
        LeadObserver (Phase 13) ──▶ lead_qualified / lead_converted analytics events
        NotifySalesParticipants (queued) ──▶ database notifications to the owner
        markedge:follow-up-reminders (hourly) ──▶ FollowUpDue, once per follow-up
```
Lifecycle: New → Assigned → Contacted → Qualified → Requirement understood → Proposal → Negotiation → Won (stored as `converted`) / Lost, plus Unqualified and Spam. Allowed moves are `markedge.sales.transitions`; Spam is reachable from every stage; closed leads can be reopened where the matrix says so. Lost and Unqualified require a reason from `markedge.sales.lost_reasons`.

## Changes
- `LeadStatus` extended (Assigned, Requirement understood, Proposal, Negotiation, Lost; Won label on the existing `converted` value); helpers for pipeline/closed/qualified sets and configured transitions.
- New enums `LeadPriority`, `LeadActivityType`, `FollowUpType`.
- Lead columns: priority, team, lost_reason, deal_value, qualification (JSON), stage_entered_at, next_follow_up_at, last_activity_at; indexes on owner/status, next follow-up and status/stage age. Existing rows get `stage_entered_at = created_at`.
- Tables `lead_activities` (append-only timeline) and `lead_follow_ups`.
- `App\Sales\LeadWorkflow`, `QualificationFields`, `LeadTimeline`, `SalesReport`, `SalesNotifier`; events `LeadAssigned`, `LeadStageChanged`, `FollowUpDue`; listener `NotifySalesParticipants`; command `markedge:follow-up-reminders` (scheduled hourly, guarded like the other jobs).
- Filament: enquiry edit form (stage limited to reachable stages, reason, owner, priority, team, deal value, qualification section); enquiry view with Move stage / Assign / Add note / Schedule follow-up / Complete follow-up actions and a Timeline tab; list columns and filters (priority, next follow-up, My leads, Open stages, Follow-up overdue, team); bulk assign and spam now run through the workflow; CSV export carries the new fields and remains audited and gated by `leads.export`.
- New pages: Sales pipeline (board, filters by owner/team/priority, SLA flag) and Sales dashboard (pipeline, outcomes, win rate, first-contact SLA, follow-ups, owners, lost reasons, priorities, teams, entered value, cohort by stage). Dashboard widget shows overdue follow-ups.
- Role `Sales Manager` (`leads.*` plus read access to CTAs, campaigns and forms). `Sales` unchanged (no export, no delete).
- `MarketingReport::funnel()` counts every stage at or beyond Qualified; `LeadObserver` records `lead_qualified` once on entering any of those stages.

## Migrations
2 (`add_sales_pipeline_columns_to_leads_table`, `create_lead_activities_and_follow_ups_tables`), additive, reversible, run on MySQL 8.4 locally and SQLite in tests.

## Configuration (all in `config/markedge.php` → `sales`)
| Key | Default | State |
| --- | --- | --- |
| transitions | matrix above | CONFIGURED |
| teams (`MARKEDGE_SALES_TEAMS`) | none | NOT CONFIGURED (pages say so) |
| currency (`MARKEDGE_SALES_CURRENCY`) | none | NOT CONFIGURED |
| sla.first_contact_hours (`MARKEDGE_SALES_FIRST_CONTACT_HOURS`) | none | NOT CONFIGURED (dashboard shows NOT CONFIGURED) |
| follow_up_reminder_hours | 24 | CONFIGURED |
| lost_reasons, qualification_fields | generic defaults | CONFIGURED (editable) |

## Tests
New: `tests/Feature/Sales/LeadWorkflowTest.php` (5) and `SalesPagesTest.php` (3). Full suite: 465 tests / 2,564 assertions passing (457 existing + 8 new); the lead resource, analytics and conversion tests pass unchanged.

## Security & privacy
Every change to sales state needs `leads.update`; exports need `leads.export` and are written to the activity log; the pipeline and dashboard need `leads.view_any` (Editors get 403). Notifications carry the enquiry id and name only, never email or phone, and never go to the actor. Timeline properties hold ids, stage values and question keys, never answers. Stage moves lock the lead row. Reasons and answers are bounded and whitelisted against configuration.

## Performance
Board: one grouped count plus one bounded query per stage. Dashboard: grouped aggregates, no per-row queries. Lead list eager-loads owner. `next_follow_up_at` is denormalised on the lead so overdue filtering is an indexed column comparison.

## Manual verification
Local MySQL: migrations applied; roles re-seeded; `SalesReport`, `LeadWorkflow` (Contacted → Lost with reason) and `LeadTimeline` exercised with real rows inside a rolled-back transaction, including the MySQL `TIMESTAMPDIFF` path; `markedge:follow-up-reminders` runs; routes `admin/sales-pipeline` and `admin/sales-dashboard` registered.

## Known limitations
No email or chat delivery of sales notifications yet (database notifications only; channel adapters are Phase 16). SLA covers first contact only. Ownership does not restrict visibility (all sales users see all leads; "My leads" is a filter). Deal values are free entries by sales with no currency conversion.

## Deferred
Per-stage SLA targets, owner-restricted visibility, follow-up email digests (Phase 16 automation).

## Acceptance
Lifecycle, ownership/priority/teams, timeline, follow-ups and reminders, notes, configurable qualification, pipeline views, sales dashboard without invented financials, notifications, config-driven SLA, audited gated exports: IMPLEMENTED and TESTED. Real sales data: NOT AVAILABLE.

## Final status
IMPLEMENTED / TESTED (production use pending Phase 11B).
