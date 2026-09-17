<?php

/**
 * Phase 12 content library — pages. Blocks replace only the placeholder blocks seeded in Phase 4;
 * company facts (history, leadership, address) are deliberately absent until confirmed.
 */
return [
    'home' => [
        'excerpt' => 'Markedge Technologies builds software, operates IT infrastructure and grows businesses through digital marketing, with two products of its own: a Lead Management System and a Recruitment Management System.',
        'seo' => ['title' => 'Markedge Technologies | Build. Operate. Grow.', 'description' => 'Software development, IT infrastructure management and digital growth services, plus lead and recruitment management products, from Markedge Technologies.'],
        'replacements' => [
            'hero.subheading' => 'Custom software and applications, managed IT infrastructure, and digital marketing measured on enquiries: three capabilities from one accountable technology partner.',
            'split_content.body' => '<p>Most businesses buy technology in pieces: a developer here, an IT vendor there, an agency for marketing. The pieces rarely fit. Markedge brings the three together so the website generates enquiries the sales team can act on, the software fits the process, and the infrastructure underneath is maintained, secured and backed up.</p><p>Every engagement has a defined scope, a named owner and reporting you can read.</p>',
            'process.steps' => [
                ['title' => 'Discover', 'text' => 'Understand the business, the users and the systems already in place.'],
                ['title' => 'Define', 'text' => 'Agree scope, architecture, priorities and how success is measured.'],
                ['title' => 'Design', 'text' => 'Flows, interfaces and content planned before anything is built.'],
                ['title' => 'Build', 'text' => 'Deliver in reviewed increments with tests and documentation.'],
                ['title' => 'Deploy', 'text' => 'Release with monitoring, backups and a support path.'],
                ['title' => 'Operate', 'text' => 'Maintain, monitor and support under a defined agreement.'],
                ['title' => 'Grow', 'text' => 'Improve visibility, content and conversion from real data.'],
            ],
            'feature_grid.items' => [
                ['title' => 'Business-first technology', 'text' => 'Every recommendation starts with the business problem, not the tool.'],
                ['title' => 'Multi-disciplinary capability', 'text' => 'Engineering, infrastructure and marketing teams that work on the same outcome.'],
                ['title' => 'Built for evolution', 'text' => 'Architecture, documentation and ownership that let systems change without rewrites.'],
                ['title' => 'Product mindset', 'text' => 'We build and run our own products, so advice comes from practice.'],
                ['title' => 'Long-term partnership', 'text' => 'Defined agreements for maintenance, support and improvement after launch.'],
                ['title' => 'Measured', 'text' => 'First-party tracking connects work to enquiries and outcomes you can read.'],
            ],
        ],
    ],
    'about' => [
        'excerpt' => 'Markedge Technologies is a technology company that builds software, operates IT infrastructure and grows businesses online.',
        'seo' => ['title' => 'About Markedge Technologies', 'description' => 'What Markedge Technologies builds, operates and grows, how we work and how to contact us.'],
        'blocks' => [
            ['type' => 'rich_text', 'data' => ['body' => '<h2>What Markedge is</h2><p>Markedge Technologies is a technology company organised around three capabilities. We <strong>build</strong> software, web and mobile applications, integrations and automation. We <strong>operate</strong> IT infrastructure: networks, servers, cloud, security, backups and support. We help businesses <strong>grow</strong> through search, content, campaigns and conversion, measured with first-party data.</p><p>We also build our own products, a Lead Management System and a Recruitment Management System, which keeps our engineering and advice grounded in running software for real users.</p>', 'width' => 'narrow']],
            ['type' => 'rich_text', 'data' => ['body' => '<h2>How we work</h2><p>Every engagement starts by understanding the business problem and the systems already in place. We define scope and success measures in writing, deliver in increments you can review, and document what we build so it does not depend on us. Code, environments and accounts are owned by you.</p><p>For ongoing work such as maintenance, support and marketing, agreements state what is covered, what is monitored and how results are reported. We do not publish generic promises about response times, rankings or returns; those are agreed per engagement and reported honestly.</p>', 'width' => 'narrow']],
            ['type' => 'rich_text', 'data' => ['body' => '<h2>What we believe</h2><ul><li><strong>Technology should fit the business.</strong> The process comes first; the tool follows.</li><li><strong>Fundamentals before novelty.</strong> Backups, security, clean architecture and clear content outperform trends.</li><li><strong>Measure what matters.</strong> Enquiries, uptime, usage and time saved, not vanity metrics.</li><li><strong>Say what is true.</strong> If a result cannot be evidenced, we do not claim it.</li></ul>', 'width' => 'narrow']],
            ['type' => 'cta', 'data' => ['cta_key' => 'start-conversation', 'heading' => 'Talk to us about what you are trying to do.', 'theme' => 'dark']],
        ],
    ],
    'contact' => [
        'template' => 'contact',
        'excerpt' => 'Contact Markedge Technologies about software, IT infrastructure or digital growth.',
        'seo' => ['title' => 'Contact Markedge Technologies', 'description' => 'Get in touch with Markedge Technologies about software development, IT infrastructure or digital growth. Tell us what you are working on and we will respond.'],
        'form_key' => 'general-enquiry',
        'form' => ['heading' => 'Tell us what you are working on', 'intro' => 'Share a few details about your business and what you need. We reply to every enquiry, and we use what you send us only to respond to it.', 'success_message' => 'Thank you. Your message has reached us and a member of the Markedge team will reply to you directly.', 'requires_consent' => true],
        'blocks' => [
            ['type' => 'contact_form', 'data' => ['form_key' => 'general-enquiry', 'heading' => 'Contact Markedge', 'intro' => 'Whether you need software built, IT looked after or more enquiries from your website, start here. Tell us the problem; we will suggest the right next step.', 'show_contact_details' => true]],
            ['type' => 'rich_text', 'data' => ['body' => '<h2>What happens after you send an enquiry</h2><p>Your message is recorded with the page it came from and assigned to a member of the team, who replies directly. If a call is the right next step, we will propose one. We ask only for the details needed to respond, and we never add you to marketing lists without your consent.</p>', 'width' => 'narrow']],
        ],
    ],
    'privacy-policy' => [
        'template' => 'legal',
        'review_only' => true,
        'excerpt' => 'How Markedge Technologies collects, uses and protects information submitted through this website.',
        'seo' => ['title' => 'Privacy Policy | Markedge Technologies', 'description' => 'How Markedge Technologies collects, uses, stores and protects information submitted through this website, including enquiry forms and first-party cookies.'],
        'blocks' => [
            ['type' => 'rich_text', 'data' => ['body' => '<p><em>Draft prepared from the website\'s actual behaviour for legal review before publication. Company registration details, governing law and the data protection contact must be added by Markedge before this page goes live.</em></p><h2>What this policy covers</h2><p>This policy explains what information this website collects, why, how it is stored and who can see it. It applies to visitors of the Markedge Technologies website and to enquiries submitted through it.</p><h2>Information you give us</h2><p>When you submit an enquiry, quote, consultation, demo, assessment or audit form, we store the details you enter (typically name, e-mail address, phone number, company and your message), the page the form was on, and the time of submission. Forms that require consent record the consent statement you accepted and when. We use this information to respond to your enquiry and, where you have asked for one, to arrange a call or demonstration. We do not add you to marketing lists without your explicit consent.</p><h2>Information collected automatically</h2><p>The website sets a first-party cookie that records how you arrived (for example a campaign parameter or the referring website\'s host name), the first page you landed on and an anonymous visitor identifier. It contains no personal data and is used only to understand which channels lead to enquiries. A session cookie supports security features such as form protection. We do not use third-party analytics or advertising trackers. IP addresses are not stored with enquiries unless explicitly enabled for security purposes.</p><h2>How long we keep information</h2><p>Enquiry records are retained for the period defined in our data-retention settings and then deleted. Anonymous measurement records are deleted after a fixed retention period. You may ask us to delete your enquiry earlier.</p><h2>Who can access it</h2><p>Enquiries are visible only to authorised Markedge staff whose role requires it. Technical details are restricted to administrators. Access is logged.</p><h2>Your rights</h2><p>You can ask what information we hold about you, ask for it to be corrected or deleted, or withdraw consent, by contacting us through the contact page.</p><h2>Changes</h2><p>We will update this page when the way the website handles information changes, and show the date of the last update.</p>', 'width' => 'narrow']],
        ],
    ],
    'cookie-policy' => [
        'template' => 'legal',
        'review_only' => true,
        'excerpt' => 'The cookies this website sets and what they do.',
        'seo' => ['title' => 'Cookie Policy | Markedge Technologies', 'description' => 'The first-party cookies set by the Markedge Technologies website, what each one does and how long it lasts.'],
        'blocks' => [
            ['type' => 'rich_text', 'data' => ['body' => '<p><em>Draft prepared from the website\'s actual cookie behaviour for review before publication.</em></p><h2>Cookies this website sets</h2><table><thead><tr><th>Cookie</th><th>Purpose</th><th>Duration</th></tr></thead><tbody><tr><td>Session cookie</td><td>Keeps your session and protects forms against forgery.</td><td>Until the session ends (two hours of inactivity)</td></tr><tr><td>XSRF-TOKEN</td><td>Security token for form submissions.</td><td>Two hours</td></tr><tr><td>mk_attr</td><td>First-party attribution: how you arrived (campaign parameters or referring site), first landing page and an anonymous visitor identifier. No personal data.</td><td>90 days</td></tr></tbody></table><h2>What we do not use</h2><p>No third-party analytics, advertising or social-media tracking cookies are set by this website.</p><h2>Managing cookies</h2><p>You can delete or block cookies in your browser settings. Blocking the session cookie prevents forms from working; blocking the attribution cookie only affects our understanding of which channels bring visitors.</p>', 'width' => 'narrow']],
        ],
    ],
    'request-consultation' => [
        'template' => 'form',
        'excerpt' => 'Request a consultation with Markedge about a software, infrastructure or growth requirement.',
        'seo' => ['title' => 'Request a Consultation | Markedge Technologies', 'description' => 'Request a consultation with Markedge Technologies to discuss a software, IT infrastructure or digital growth requirement.'],
        'form_key' => 'consultation',
        'form' => ['heading' => 'Request a consultation', 'intro' => 'Tell us about the requirement and the area of interest. We will arrange a conversation with the right specialist.', 'success_message' => 'Thank you. We will contact you to arrange the consultation.', 'requires_consent' => true],
        'blocks' => [
            ['type' => 'rich_text', 'data' => ['body' => '<h2>What a consultation covers</h2><p>A consultation is a working conversation, not a sales pitch. We listen to the problem, ask about the systems and constraints involved, and give you a clear view of the options, including the option of doing nothing. If an engagement makes sense, we outline scope and next steps in writing.</p>', 'width' => 'narrow']],
            ['type' => 'lead_form', 'data' => ['form_key' => 'consultation', 'layout' => 'card']],
        ],
    ],
    'request-quote' => [
        'template' => 'form',
        'excerpt' => 'Request a quote from Markedge for a defined project or service.',
        'seo' => ['title' => 'Request a Quote | Markedge Technologies', 'description' => 'Request a quote from Markedge Technologies for a software, IT infrastructure or digital growth project.'],
        'form_key' => 'quote-request',
        'form' => ['heading' => 'Request a quote', 'intro' => 'Describe the project, the service you need and your expected timeline. We will come back with questions or a proposal.', 'success_message' => 'Thank you. We will review your requirement and respond with questions or a proposal.', 'requires_consent' => true],
        'blocks' => [
            ['type' => 'rich_text', 'data' => ['body' => '<h2>How quoting works</h2><p>Accurate quotes need a clear scope. After you submit the request we may ask a few questions or propose a short discovery call, then provide a written proposal with scope, deliverables, timeline and commercial terms. There is no obligation.</p>', 'width' => 'narrow']],
            ['type' => 'lead_form', 'data' => ['form_key' => 'quote-request', 'layout' => 'card']],
        ],
    ],
    'request-demo' => [
        'template' => 'form',
        'excerpt' => 'Request a demo of the Markedge Lead Management System or Recruitment Management System.',
        'seo' => ['title' => 'Request a Product Demo | Markedge Technologies', 'description' => 'Request a demo of the Markedge Lead Management System or Recruitment Management System, walked through with your own scenarios.'],
        'form_key' => 'product-demo',
        'form' => ['heading' => 'Request a product demo', 'intro' => 'Choose the product and tell us about your team. We will walk through the product with your scenarios.', 'success_message' => 'Thank you. We will contact you to schedule the demo.', 'requires_consent' => true],
        'blocks' => [
            ['type' => 'rich_text', 'data' => ['body' => '<h2>What to expect from a demo</h2><p>A demo is a guided walk-through of the product as it is today, using scenarios from your own process. We answer questions about configuration, integration and rollout, and follow up in writing with anything we could not cover live.</p>', 'width' => 'narrow']],
            ['type' => 'lead_form', 'data' => ['form_key' => 'product-demo', 'layout' => 'card']],
        ],
    ],
    'request-it-assessment' => [
        'template' => 'form',
        'excerpt' => 'Request an IT assessment of your infrastructure, security and backups.',
        'seo' => ['title' => 'Request an IT Assessment | Markedge Technologies', 'description' => 'Request an assessment of your IT infrastructure, security and backups from Markedge Technologies.'],
        'form_key' => 'it-assessment',
        'form' => ['heading' => 'Request an IT assessment', 'intro' => 'Tell us about your environment and the number of users or devices. We will propose the assessment scope.', 'success_message' => 'Thank you. We will contact you to scope the assessment.', 'requires_consent' => true],
        'blocks' => [
            ['type' => 'rich_text', 'data' => ['body' => '<h2>What an IT assessment looks at</h2><p>An assessment inventories your devices, servers, network, cloud services, backups and security controls, identifies risks in order of impact, and results in a written report with prioritised recommendations. It is the starting point for an IT AMC or a modernisation plan, and you keep the report whether or not you engage us further.</p>', 'width' => 'narrow']],
            ['type' => 'lead_form', 'data' => ['form_key' => 'it-assessment', 'layout' => 'card']],
        ],
    ],
    'request-digital-growth-audit' => [
        'template' => 'form',
        'excerpt' => 'Request a digital growth audit of your website, search visibility and enquiry path.',
        'seo' => ['title' => 'Request a Digital Growth Audit | Markedge Technologies', 'description' => 'Request an audit of your website, search visibility, content and enquiry path from Markedge Technologies.'],
        'form_key' => 'digital-growth-audit',
        'form' => ['heading' => 'Request a digital growth audit', 'intro' => 'Share your website address and your goals. We will review and come back with findings.', 'success_message' => 'Thank you. We will review your website and contact you with findings.', 'requires_consent' => true],
        'blocks' => [
            ['type' => 'rich_text', 'data' => ['body' => '<h2>What the audit covers</h2><p>The audit reviews your website\'s technical SEO, how well pages match what customers search for, the content that exists and what is missing, and the path from visit to enquiry. You receive written findings with priorities. It is not a ranking guarantee; it is an honest picture of where you stand and what would help.</p>', 'width' => 'narrow']],
            ['type' => 'lead_form', 'data' => ['form_key' => 'digital-growth-audit', 'layout' => 'card']],
        ],
    ],
];
