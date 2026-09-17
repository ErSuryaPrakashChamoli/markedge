<?php

/**
 * Product platform content (Phase 15 hierarchy) for the two Markedge products. Modules, features
 * and capabilities describe what each product does in functional terms; nothing here states
 * metrics, certifications, named third-party integrations or hosting guarantees. Deployment and
 * security statements are limited to product behaviour and should be re-confirmed by the product
 * owner before launch (see MARKEDGE_PROJECT_FINAL_AUDIT.md, external input register).
 */
return [
    'lead-management-system' => [
        'product_type' => 'Business platform',
        'long_description' => '<p>Enquiries arrive from websites, calls, referrals and campaigns, and too often live in inboxes and spreadsheets where follow-up depends on memory. The Lead Management System gives sales and marketing teams a single record of every enquiry: where it came from, who owns it, what has happened and what happens next.</p><p>Every lead moves through a stage pipeline you configure, with owners, priorities, follow-up reminders and a complete timeline. Managers see the pipeline as a board and as reports; marketing sees which sources, campaigns and pages produce enquiries that actually convert.</p><p>It is built by Markedge for the businesses we work with on lead generation and sales management, and it connects naturally to the website lead capture and attribution we deliver under our Grow services. Request a demo to see the current product with your own scenarios.</p>',
        'deployment' => [
            ['label' => 'Cloud hosted by Markedge', 'text' => 'Markedge operates the platform for you, including updates. Hosting region and service terms are agreed in your proposal.'],
            ['label' => 'Hosted on your infrastructure', 'text' => 'Deployable on a server or cloud account you own, with Markedge providing installation and support under an AMC.'],
        ],
        'security' => [
            ['label' => 'Role-based access', 'text' => 'Every user has a role; permissions decide who can view, edit, assign, export or delete leads.'],
            ['label' => 'Activity trail', 'text' => 'Stage moves, assignments, notes and exports are recorded with the user and time, so every change is traceable.'],
            ['label' => 'Encrypted connections', 'text' => 'The application is served over HTTPS; browser sessions use secure, HttpOnly cookies.'],
            ['label' => 'Your data stays yours', 'text' => 'Leads can be exported at any time, and retention rules for old records are configurable.'],
        ],
        'modules' => [
            [
                'name' => 'Lead capture',
                'summary' => 'Every enquiry, from every channel, becomes one structured record with its source.',
                'highlights' => ['Website forms with attribution', 'Manual entry for calls and walk-ins', 'Duplicate detection'],
                'features' => [
                    ['title' => 'Web form capture', 'description' => 'Forms on your website create leads directly, with the page, campaign and source captured automatically.', 'capabilities' => [
                        ['name' => 'Configurable form fields', 'description' => 'Core contact fields plus your own questions, with validation and consent text.'],
                        ['name' => 'First and last touch attribution', 'description' => 'UTM parameters, referrer and landing page are stored on the lead and never overwritten.'],
                        ['name' => 'Spam protection', 'description' => 'Honeypot fields and rate limiting keep bots out without CAPTCHAs.'],
                    ]],
                    ['title' => 'Manual and API entry', 'description' => 'Sales staff add phone and walk-in enquiries; integrations post leads through an authenticated API.', 'capabilities' => [
                        ['name' => 'Quick-add form', 'description' => 'Add a lead in seconds with the fields your team actually needs.'],
                        ['name' => 'API with keys and abilities', 'description' => 'Integrations create and read leads with scoped API keys and idempotent requests.'],
                    ]],
                    ['title' => 'Duplicate handling', 'description' => 'Repeat enquiries from the same person are linked to the original instead of creating noise.', 'capabilities' => [
                        ['name' => 'Match on e-mail or phone', 'description' => 'Duplicates within a configurable window are linked, never silently dropped.'],
                        ['name' => 'Spam marking', 'description' => 'Unwanted records are marked as spam and excluded from every report.'],
                    ]],
                ],
            ],
            [
                'name' => 'Pipeline and follow-up',
                'summary' => 'A configurable stage pipeline with owners, priorities, reminders and a complete timeline.',
                'highlights' => ['Board and list views', 'Follow-up reminders', 'Lost-reason tracking'],
                'features' => [
                    ['title' => 'Configurable stages', 'description' => 'New, Assigned, Contacted, Qualified, Proposal, Negotiation, Won and Lost by default; allowed moves are configuration.', 'capabilities' => [
                        ['name' => 'Stage rules', 'description' => 'Decide which moves are permitted and which closes need a reason.'],
                        ['name' => 'Pipeline board', 'description' => 'See every open lead by stage, owner and priority at a glance.'],
                    ]],
                    ['title' => 'Follow-ups and reminders', 'description' => 'Schedule calls, e-mails and meetings against a lead; owners are reminded before they are due.', 'capabilities' => [
                        ['name' => 'Due-date reminders', 'description' => 'Internal notifications for follow-ups due soon or overdue.'],
                        ['name' => 'Outcome logging', 'description' => 'Completing a follow-up records what happened on the timeline.'],
                    ]],
                    ['title' => 'Qualification and notes', 'description' => 'Capture budget, authority, need and timeline with questions you define, plus free-text notes.', 'capabilities' => [
                        ['name' => 'Configurable qualification questions', 'description' => 'Select, text and yes/no questions stored on the lead.'],
                        ['name' => 'Timeline', 'description' => 'Capture, stage moves, assignments, notes and follow-ups in one chronological view.'],
                    ]],
                ],
            ],
            [
                'name' => 'Team and assignment',
                'summary' => 'Ownership, teams, priorities and automation rules keep enquiries moving.',
                'highlights' => ['Owner and team assignment', 'Priority levels', 'Automation rules'],
                'features' => [
                    ['title' => 'Ownership and teams', 'description' => 'Assign leads to people or teams; owners are notified and see their own queue.', 'capabilities' => [
                        ['name' => 'My leads and team views', 'description' => 'Filter the pipeline to your own or your team\'s leads.'],
                        ['name' => 'Bulk assignment', 'description' => 'Reassign many leads at once when people change or join.'],
                    ]],
                    ['title' => 'Automation rules', 'description' => 'When a lead arrives or changes stage, rules can assign, prioritise, add notes, schedule follow-ups or notify.', 'capabilities' => [
                        ['name' => 'Trigger, conditions, actions', 'description' => 'Rules are configured from a fixed vocabulary; no code is involved.'],
                        ['name' => 'Run log', 'description' => 'Every rule run is recorded, so automation is observable and safe to retry.'],
                    ]],
                ],
            ],
            [
                'name' => 'Reporting and attribution',
                'summary' => 'Know which sources, campaigns and pages produce enquiries that convert.',
                'highlights' => ['Source and campaign reports', 'Sales dashboard', 'Audited exports'],
                'features' => [
                    ['title' => 'Marketing reports', 'description' => 'Leads by source, medium, campaign, form, page and CTA, with funnel rates where a denominator exists.', 'capabilities' => [
                        ['name' => 'Conversion paths', 'description' => 'The sequence of visits and clicks before an enquiry, from first-party data only.'],
                        ['name' => 'Trend reporting', 'description' => 'Daily, weekly, monthly and quarterly views in your timezone.'],
                    ]],
                    ['title' => 'Sales dashboard', 'description' => 'Open pipeline, outcomes, win rate, overdue follow-ups and owner performance.', 'capabilities' => [
                        ['name' => 'First-contact SLA', 'description' => 'Set a target and see which leads are waiting too long.'],
                        ['name' => 'Lost reasons', 'description' => 'Understand why deals are lost, by period.'],
                    ]],
                    ['title' => 'Exports', 'description' => 'Permission-controlled CSV exports, each one recorded in the activity log.', 'capabilities' => [
                        ['name' => 'Filtered exports', 'description' => 'Export exactly the leads you have filtered.'],
                    ]],
                ],
            ],
        ],
        'documents' => [
            ['slug' => 'getting-started', 'section' => 'Getting started', 'title' => 'Getting started with the Lead Management System', 'excerpt' => 'Set up users, roles and your first form, then watch enquiries arrive.', 'body' => '<h2>1. Create users and roles</h2><p>Add your sales and marketing users and give each a role. Roles control who can view, edit, assign and export leads.</p><h2>2. Configure your pipeline</h2><p>Review the default stages and adjust which moves are allowed and which closes require a reason. Set teams, priorities and, if you use one, a first-contact target.</p><h2>3. Connect a form</h2><p>Create a form with the fields you need and place it on your website. Every submission becomes a lead with its source, campaign and landing page recorded.</p><h2>4. Work the pipeline</h2><p>Use the board to see what is new, assign owners, schedule follow-ups and move leads through stages as you talk to prospects.</p>'],
            ['slug' => 'pipeline-and-follow-ups', 'section' => 'Working with leads', 'title' => 'Pipeline stages and follow-ups', 'excerpt' => 'How stages, owners, priorities and follow-up reminders work together.', 'body' => '<h2>Stages</h2><p>Each lead sits in exactly one stage. Moving a lead records who moved it and when. Closing as Lost or Unqualified asks for a reason so you can report on it later.</p><h2>Owners and priorities</h2><p>Assigning an owner notifies that person and, for a new lead, moves it to Assigned. Priority (low to urgent) orders the board and can be set by automation rules.</p><h2>Follow-ups</h2><p>Schedule a call, e-mail, meeting or task with a due date. Owners are reminded before it is due; completing it records the outcome on the timeline.</p>'],
            ['slug' => 'reports-and-exports', 'section' => 'Reporting', 'title' => 'Reports, attribution and exports', 'excerpt' => 'Where the numbers come from and who can export them.', 'body' => '<h2>Attribution</h2><p>Every lead stores its first touch (the visit that brought the person to you) and last touch (the visit before the enquiry). Reports use these to attribute leads to sources, campaigns and pages.</p><h2>Rates</h2><p>Funnel and win rates appear only where the denominator was measured. If a rate is blank, the underlying count was zero.</p><h2>Exports</h2><p>Exports require a permission and are written to the activity log with the user and the number of records.</p>'],
        ],
    ],
    'recruitment-management-system' => [
        'product_type' => 'Business platform',
        'long_description' => '<p>Recruitment involves many people and many steps: approving a vacancy, sourcing candidates, screening, scheduling interviews, collecting feedback and making an offer. When that runs over e-mail and spreadsheets, candidates wait, feedback gets lost and nobody can see where a hire is stuck.</p><p>The Recruitment Management System gives HR teams, hiring managers and recruiters a shared, structured view of every vacancy and candidate. Each vacancy has a pipeline of stages you configure; each candidate has a profile, documents, interview history and feedback in one place; and reports show time in stage, pipeline health and outcomes per vacancy.</p><p>Request a demo to see the current product with your own hiring process. Module, integration and deployment details are confirmed during the demo so your evaluation reflects the product as it is today.</p>',
        'deployment' => [
            ['label' => 'Cloud hosted by Markedge', 'text' => 'Markedge operates the platform for you, including updates. Hosting region and service terms are agreed in your proposal.'],
            ['label' => 'Hosted on your infrastructure', 'text' => 'Deployable on a server or cloud account you own, with Markedge providing installation and support under an AMC.'],
        ],
        'security' => [
            ['label' => 'Role-based access', 'text' => 'Recruiters, hiring managers and interviewers see only the vacancies and candidates their role allows.'],
            ['label' => 'Activity trail', 'text' => 'Stage moves, feedback, offers and exports are recorded with the user and time.'],
            ['label' => 'Encrypted connections', 'text' => 'The application is served over HTTPS; browser sessions use secure, HttpOnly cookies.'],
            ['label' => 'Candidate data controls', 'text' => 'Candidate records can be exported or removed on request, and retention periods are configurable.'],
        ],
        'modules' => [
            [
                'name' => 'Vacancies and requisitions',
                'summary' => 'Open a vacancy with a clear brief, approval and a hiring team.',
                'highlights' => ['Requisition approval', 'Hiring team per vacancy', 'Job descriptions and criteria'],
                'features' => [
                    ['title' => 'Requisition workflow', 'description' => 'Raise a requisition, route it for approval and open the vacancy once approved.', 'capabilities' => [
                        ['name' => 'Approval steps', 'description' => 'Configurable approvers per department or grade.'],
                        ['name' => 'Vacancy brief', 'description' => 'Role, location, employment type, salary band (optional) and must-have criteria in one record.'],
                    ]],
                    ['title' => 'Hiring team', 'description' => 'Assign a recruiter, hiring manager and interviewers to each vacancy with their own permissions.', 'capabilities' => [
                        ['name' => 'Per-vacancy roles', 'description' => 'Interviewers see only the candidates they are scheduled to meet.'],
                    ]],
                ],
            ],
            [
                'name' => 'Candidate pipeline',
                'summary' => 'Every applicant tracked through configurable stages with documents and history.',
                'highlights' => ['Configurable stages', 'Candidate profiles and documents', 'Duplicate detection'],
                'features' => [
                    ['title' => 'Candidate profiles', 'description' => 'Contact details, CV and documents, source, notes and the full history of every application.', 'capabilities' => [
                        ['name' => 'Document storage', 'description' => 'CVs, portfolios and certificates attached to the candidate.'],
                        ['name' => 'Source tracking', 'description' => 'Know whether a candidate came from a job board, referral, agency or your careers page.'],
                        ['name' => 'Duplicate detection', 'description' => 'Repeat applications are linked to the existing profile.'],
                    ]],
                    ['title' => 'Stage pipeline', 'description' => 'Applied, Screening, Interview, Assessment, Offer, Hired and Rejected by default; stages and moves are configuration.', 'capabilities' => [
                        ['name' => 'Kanban board per vacancy', 'description' => 'Drag-free stage moves with a reason for rejections.'],
                        ['name' => 'Bulk actions', 'description' => 'Move, reject or message many candidates at once.'],
                    ]],
                ],
            ],
            [
                'name' => 'Interviews and feedback',
                'summary' => 'Schedule interviews, collect structured feedback and decide with the whole panel.',
                'highlights' => ['Interview scheduling', 'Scorecards', 'Panel decisions'],
                'features' => [
                    ['title' => 'Scheduling', 'description' => 'Plan interview rounds, assign interviewers and record the mode (in person, phone or video).', 'capabilities' => [
                        ['name' => 'Interview rounds', 'description' => 'Multiple rounds per candidate with their own panel.'],
                        ['name' => 'Reminders', 'description' => 'Interviewers are reminded of upcoming interviews and pending feedback.'],
                    ]],
                    ['title' => 'Structured feedback', 'description' => 'Scorecards with the criteria from the vacancy brief, so feedback is comparable across candidates.', 'capabilities' => [
                        ['name' => 'Configurable scorecards', 'description' => 'Criteria, scales and a recommendation per interviewer.'],
                        ['name' => 'Feedback deadlines', 'description' => 'Pending feedback is visible on the vacancy dashboard.'],
                    ]],
                ],
            ],
            [
                'name' => 'Offers and handover',
                'summary' => 'From approved offer to a clean handover for onboarding.',
                'highlights' => ['Offer approval', 'Offer status tracking', 'Onboarding handover checklist'],
                'features' => [
                    ['title' => 'Offer management', 'description' => 'Draft an offer, route it for approval and track acceptance or decline with reasons.', 'capabilities' => [
                        ['name' => 'Offer approvals', 'description' => 'Approvers by grade or department, recorded on the candidate.'],
                        ['name' => 'Decline reasons', 'description' => 'Understand why offers are refused.'],
                    ]],
                    ['title' => 'Handover checklist', 'description' => 'Once accepted, a checklist captures the documents and details onboarding needs.', 'capabilities' => [
                        ['name' => 'Configurable checklist', 'description' => 'Items per location or employment type.'],
                    ]],
                ],
            ],
            [
                'name' => 'Reporting',
                'summary' => 'Pipeline, time in stage and outcomes per vacancy, recruiter and source.',
                'highlights' => ['Vacancy dashboards', 'Time-in-stage reports', 'Audited exports'],
                'features' => [
                    ['title' => 'Vacancy and pipeline reports', 'description' => 'Candidates by stage, ageing, pending feedback and offers per vacancy.', 'capabilities' => [
                        ['name' => 'Time in stage', 'description' => 'See where candidates wait longest.'],
                        ['name' => 'Source effectiveness', 'description' => 'Applications, interviews and hires by source.'],
                    ]],
                    ['title' => 'Exports', 'description' => 'Permission-controlled exports, each recorded in the activity log.', 'capabilities' => [
                        ['name' => 'Filtered exports', 'description' => 'Export the candidates or vacancies you have filtered.'],
                    ]],
                ],
            ],
        ],
        'documents' => [
            ['slug' => 'getting-started', 'section' => 'Getting started', 'title' => 'Getting started with the Recruitment Management System', 'excerpt' => 'Set up roles, stages and your first vacancy.', 'body' => '<h2>1. Users and roles</h2><p>Add recruiters, hiring managers and interviewers. Roles decide what each person sees and can change.</p><h2>2. Stages and scorecards</h2><p>Review the default pipeline stages and define the scorecard criteria interviewers will use.</p><h2>3. Open a vacancy</h2><p>Raise a requisition with the brief and criteria, route it for approval, then assign the hiring team.</p><h2>4. Add candidates</h2><p>Create candidate profiles manually or from your careers page and move them through the stages as interviews happen.</p>'],
            ['slug' => 'interviews-and-feedback', 'section' => 'Hiring workflow', 'title' => 'Interviews, scorecards and decisions', 'excerpt' => 'How interview rounds, structured feedback and panel decisions work.', 'body' => '<h2>Rounds</h2><p>Each candidate can have several interview rounds, each with its own interviewers and mode. Interviewers are reminded before the interview and while feedback is pending.</p><h2>Scorecards</h2><p>Feedback is entered against the criteria defined for the vacancy, with a scale and a recommendation. Comparable scorecards make panel decisions faster.</p><h2>Decisions</h2><p>The hiring manager moves the candidate forward, to offer or to rejected, with a reason that appears in reports.</p>'],
            ['slug' => 'reports-and-exports', 'section' => 'Reporting', 'title' => 'Vacancy reports and exports', 'excerpt' => 'Pipeline health, time in stage, source effectiveness and audited exports.', 'body' => '<h2>Vacancy dashboard</h2><p>Every vacancy shows candidates by stage, how long they have waited, feedback that is pending and offers in progress.</p><h2>Sources</h2><p>Applications, interviews and hires are reported by source so you can see which channels deliver.</p><h2>Exports</h2><p>Exports require a permission and are recorded in the activity log with the user and record count.</p>'],
        ],
    ],
];
