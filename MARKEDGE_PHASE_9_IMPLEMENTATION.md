# Markedge — Phase 9 Implementation: Content Operations, Editorial Workflow, Personalisation & CMS Maturity

## 1. Objective

Mature the Phase 4 publishing lifecycle into a governed editorial operation: role-based transitions, review queue, approvals and change requests, ownership, internal comments, deterministic revisions with compare and restore, robust scheduled publishing and unpublishing, factual content health, a content inventory, media usage, deterministic campaign targeting and internal notifications. No public redesign; editors control content, code controls structure.

## 2. Starting commit

`55de297` (Phase 8 approved). Phases 1–8 preserved.

## 3. Editorial workflow

The five statuses stay (Draft, Review, Scheduled, Published, Archived). [Publisher](app/Services/Cms/Publisher.php) now owns an explicit transition matrix:

| From | To | Ability |
|---|---|---|
| Draft | Review | update (submit for review) |
| Review | Draft | review (request changes) |
| Review / Draft | Scheduled, Published | publish |
| Scheduled | Published | publish (scheduler runs as system) |
| Published / Scheduled | Draft | publish (unpublish) |
| Any except Archived | Archived | publish |
| Archived | Draft | publish (restore to draft) |

Approval is an explicit step recorded on the record (`approved_at`, `approved_by`) without a new status: a reviewer approves, someone with publish rights publishes or schedules. Any content edit after approval clears it. Every transition runs in a transaction under `lockForUpdate`, rejects same-status or illegal moves, and writes an activity entry with the actor, from and to state, and reason where relevant. Duplicate publication is therefore impossible.

## 4. Permission model

Existing `{subject}.{action}` permissions plus one new action, `review` (approve, request changes, assign). Mapping: submit for review → `update`; approve / request changes / assign → `review`; schedule, publish, unpublish, archive, restore-to-draft → `publish`; restore a revision → `update`. Roles updated in `config/markedge.php`: Website Manager gains `services.review` and `service_categories.review`; Content Manager gains `articles.review` (edit, submit, review, no publish); Editor keeps full article rights including publish; Marketing Manager keeps landing pages; Super Admin everything. No role names are hard-coded in logic; `PermissionPolicy::review()` maps the ability.

## 5. Ownership

New columns on the eight workflow tables: `owner_id`, `reviewer_id`, `submitted_at`, `approved_at`, `approved_by`, `unpublish_at`, `expiry_reminded_at`. Existing `created_by` / `updated_by` are reused for author and last editor. Assignment (`Publisher::assign`) changes only the two user columns (`saveQuietly`, logged as `assigned`), never URL, canonical, status or content.

## 6. Review queue

`Admin → Editorial → Review queue` (`ReviewQueue`) shows content in review or scheduled with title, type, status, owner, reviewer, author, submitted date, scheduled date and last modified, plus every inventory filter. It is the inventory with preset status filters, built on one SQL `UNION ALL` ([ContentInventoryQuery](app/Editorial/ContentInventoryQuery.php)).

## 7. Editorial comments

`editorial_comments` (morph, user, type comment|change_request, body, resolved_at/by). Managed on the Editorial desk: anyone who can update or review the record may comment; reviewers or the comment author resolve. Comments are only ever loaded in the admin; a test asserts a comment body never appears on the public page or in search.

## 8. Revision architecture

`content_revisions` (morph, `version`, `snapshot` JSON, `checksum`, `reason`, `created_by`, `created_at`, unique on subject + version). [RevisionSnapshot](app/Editorial/RevisionSnapshot.php) stores content attributes (fillable minus publication and ownership fields, including blocks in order with types and data), the SEO row, media ids per collection (references only, no binaries) and related ids for known pivots. [RevisionManager::capture](app/Editorial/RevisionManager.php) runs on every save of a workflow model, skips saves that do not change the snapshot checksum (status or assignment only), and folds consecutive saves by the same user within `markedge.editorial.revision_coalesce_seconds` (20s) into one version so one form submission yields one revision. Version numbers are allocated under a row lock; the unique index is the final guard. History is pruned beyond `revisions_per_record` (100). Soft-deleting content keeps its revisions; force delete removes revisions and comments.

## 9. Version restoration

Editorial desk → Version history → Compare shows a readable table of changes (content fields, blocks by position and type, SEO fields, media and relation counts) via [RevisionDiff](app/Editorial/RevisionDiff.php); the raw JSON is available to super admins only. Restore requires `update`, validates the snapshot ([RevisionSnapshotValidator](app/Editorial/RevisionSnapshotValidator.php): attributes limited to the type's revisioned fields, valid slug, blocks validated through the block registry so unknown or invalid blocks are refused, SEO fields whitelisted, canonical must be a URL or path, schema overrides must be a JSON object), refuses versions belonging to another record, and refuses stale restores when the current version is not the one the user saw. It applies content, SEO, relations and media order, then, if the record was live or in review, returns it to Draft through the Publisher (logged as "unpublished (version restored)"), and appends a new version "Restored from vN". Nothing is ever published by a restore.

## 10. Scheduling

`content:publish-scheduled` (every minute) now publishes due records in chunks, re-running the publish checklist, and records failures as "scheduled publish failed" activity instead of publishing invalid content. Publication goes through the same transition path, so the content version bump, search sync, sitemap eligibility and events all happen. `Publisher::schedule()` accepts an optional unpublish date and rejects `unpublish_at <= publish_at`; the resource form requires a publish date when status is Scheduled and validates the unpublish date is after it.

## 11. Expiration

`unpublish_at` on every workflow table. The same command runs `unpublishDue()`: published records past the date return to Draft (never Archived), logged as "unpublished (expired)", dispatching `ContentUnpublished`; observers drop them from search, the content version bump drops them from the sitemap, and the public route returns 404 through the existing resolver. A past unpublish date blocks publishing until cleared. `content:expiring-reminders` (daily 08:00) notifies owner, reviewer and creator once per record `expiry_reminder_days` (3) before the date.

## 12. Content health

No score. [ContentHealthAuditor](app/Editorial/ContentHealthAuditor.php) gives per-record findings with severity and the fix: missing title, missing summary and SEO description, missing featured/hero image, missing author or category, invalid blocks, no resolvable CTA, no related content, broken internal links, links to unpublished content, noindex, canonicalized elsewhere, expired, no owner. Available as the "Content health" header action on every edit page. `Admin → Editorial → Content health` ([ContentHealthReport](app/Editorial/ContentHealthReport.php)) aggregates in SQL: awaiting review, scheduled, published/modified in 7 days, expired, expiring soon, missing summary, missing image, missing author, noindex, canonicalized, unowned, plus a chunked scan ([ContentHealthScanner](app/Editorial/ContentHealthScanner.php), cached per content version, bounded to 200 findings) for invalid blocks, broken internal links (checked against published paths, section indexes and active redirects) and orphaned pages (published pages with no menu item and no inbound internal link).

## 13. Content inventory

`Admin → Editorial → Content inventory`: one paginated SQL view over all eight types with filters for type, status, owner, reviewer, author, category slug, publish and modification dates, indexability (indexable / noindex / canonicalized), expired only, search and sort. Rows link to Edit and to the Editorial desk. Only types the user may view are included. No second CMS table.

## 14. Media governance

Existing alt/caption/description fields kept. The Media Library now shows factual usage ("Article: Title (published)", "Page #12 (missing, unused)") and an "Unused (owner missing)" filter ([MediaUsage](app/Editorial/MediaUsage.php)) driven by `NOT EXISTS` against every owner table, respecting soft deletes. Deletion stays an explicit permission-gated action. SVG remains rejected (test) and the file-type policy is unchanged.

## 15. Personalisation foundation

[CampaignTargeting](app/Services/Cms/CampaignTargeting.php): a single deterministic rule. When a Campaign is running and has "Show this CTA to campaign visitors" enabled, visitors whose last touch is that `utm_campaign` see the campaign's CTA instead of the entity/default CTA. Everyone else, previews and crawlers see the default. It affects only CTA choice, never copy, canonical, robots, schema or sitemap (tested). The rule is shown in plain language on the campaign form. No personal or sensitive data is involved; the input is the first-party attribution cookie from Phase 8.

## 16. Campaign integration

Reuses the existing Campaign model (`personalize_cta` boolean added). Campaign CTAs still resolve through `CtaResolver`, so destination security, tracking and the `/go` allow-list apply unchanged.

## 17. Notifications

Events in `app/Events/Content/` (`ContentSubmittedForReview`, `ContentApproved`, `ContentChangesRequested`, `ContentScheduled`, `ContentPublished`, `ContentUnpublished`, `ContentArchived`, `ContentRestored`, `ContentAssigned`, `ContentVersionRestored`, `ContentExpiringSoon`) implement `EditorialEvent`. One queued listener, `NotifyEditorialParticipants`, calls [EditorialNotifier](app/Editorial/EditorialNotifier.php), which sends Filament database notifications (bell in the admin) to reviewer on submission, owner/creator on changes requested and approval, owner/creator/reviewer on schedule, publish and expiry, and the assignees on assignment, never the actor, and never for ordinary edits. Failures are reported and swallowed; a test drops the notifications table and publication still succeeds. Activity log remains the audit trail; events are for reactions.

## 18. Search integration

Unchanged Phase 7 observers: every transition saves the record, so the queued sync re-checks eligibility. Tests cover scheduled publish → indexed, expiry → removed, restore → draft removed, republish → indexed with restored content.

## 19. Sitemap integration

Publication changes bump the content version (existing `BumpsContentVersion`), which keys the sitemap cache. Tests assert scheduled publication adds the URL and expiry removes it.

## 20. SEO integration

Restoring a revision restores the SEO row through the same whitelist and validation the SEO form uses; invalid canonical URLs, non-object schema overrides and unknown fields are refused. Indexability decisions still come from `IndexabilityResolver`.

## 21. Redirect integration

Content is applied before any status change, so a restored slug on a live record goes through `HasSlug` → `SlugRedirects` and creates the same permanent redirect (and drops the reverse one) as a manual edit. Tested.

## 22. Security

Authorization on every Publisher method (`update`, `review`, `publish`) when a user is present; scheduler runs as system. Filament actions are additionally hidden by policy. Revision restore requires `update`, validates ownership of the version, rejects malformed snapshots, foreign attributes, unknown blocks, invalid SEO and non-integer ids. Comments and the desk require `view`; commenting requires `update` or `review`. Inventory, review queue, health and widget only include types the user may view. Media permissions unchanged, SVG blocked. Targeting has no client-controlled input beyond the campaign parameter that already drives attribution.

## 23. Performance

Inventory, review queue, health counts and the personal widget aggregate in SQL over a `UNION ALL` of the real tables with bounded pagination. Revision and comment lists paginate (20). Bulk actions process in chunks and report skipped/failed records. Scheduler and reminders chunk by id. The block/link scan chunks by 200 and caches per content version. No public route gained queries.

## 24. Database changes

Four migrations: editorial columns on the eight content tables plus `campaigns.personalize_cta`; `content_revisions`; `editorial_comments`; Laravel `notifications`. Explicit short index names for MySQL. Foreign keys to users use `nullOnDelete`.

## 25. Tests

New in `tests/Feature/Editorial/`: `WorkflowTest` (8), `RevisionTest` (8), `SchedulingTest` (6), `HealthAndInventoryTest` (4), `CommentsAndNotificationsTest` (3), `MediaAndTargetingTest` (4): 33 tests. Full suite: 421 tests, 1,720 assertions, Pint clean, Vite built (JS bundle unchanged at 96.7 kB gzipped). Existing Phase 1–8 tests continue to pass without modification.

## 26. Manual verification

Performed on the local MySQL database with a temporary Super Admin (removed afterwards), the new `review` permissions seeded, revision coalescing disabled and the database queue drained after each transition:

- Draft created → v1; content health listed missing description, image, author, category, no related content, a broken internal link and no owner. After adding the summary and fixing the link, those findings cleared (v2 recorded).
- Assign → submit for review → request changes ("Needs a stronger intro.") returned it to Draft with one change-request comment → resubmit → approve (approved flag set).
- Schedule with an unpublish date; `content:publish-scheduled` published it ("published (scheduled)") and, after the queued sync, the article was searchable.
- Live edit created v4; restoring v2 produced v5 ("Restored from v2"), returned the article to Draft ("unpublished (version restored)") and removed it from search; republishing indexed the restored content.
- Unpublish date moved into the past → scheduler ran "Unpublished 1 expired record(s)", status Draft (not Archived), removed from search; publishing with the past date was rejected with the checklist message; clearing it and archiving succeeded.
- Audit trail contained: created, assigned, submitted for review, changes requested, approved, scheduled, published (scheduled), unpublished (version restored), version restored, published, unpublished (expired), archived, with actor and from/to state.
- A Sales user could not publish (AuthorizationException); restoring as Sales is refused by the service and the desk action is hidden; cross-record restore is refused ("does not belong").
- Force-deleting the article removed its revisions and comments. Admin pages (review queue, inventory, content health, editorial desk, notifications bell) were verified through the Livewire test harness; the local site is served without admin users.

## 27. Deviations

- Approval is a flag on the record rather than a sixth status, so the Phase 4 lifecycle stays intact.
- Reporting/inventory pages are custom Livewire pages over a SQL union rather than Filament tables, because Filament tables need one Eloquent model.
- Media ownership is the owning record (uploader is not tracked by the media library).
- Revision preview (previewing an old version publicly) is not implemented; the existing signed preview shows the current draft.

## 28. Deferred items

Revision previews, restoring deleted media binaries, richer diffing inside rich text, per-block comments, notification channels beyond the admin bell (email/Slack), AI or behavioural personalisation (out of scope by design).

## 29. Final commit

`feat: implement editorial workflow, revisions and content operations`
