<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Reserved slugs
    |--------------------------------------------------------------------------
    |
    | First URL segments owned by fixed routes. CMS pages may never use them,
    | because /{slug} is the catch-all route registered last.
    |
    */

    'reserved_slugs' => [
        'services', 'solutions', 'industries', 'products', 'case-studies',
        'insights', 'lp', 'search', 'admin', 'preview', 'go', 'sitemap.xml',
        'robots.txt', 'up', 'storage', 'livewire', 'vendor', 'api',
        'filament',
    ],

    /*
    |--------------------------------------------------------------------------
    | Initial administrator
    |--------------------------------------------------------------------------
    */

    'admin' => [
        'name' => env('MARKEDGE_ADMIN_NAME', 'Markedge Admin'),
        'email' => env('MARKEDGE_ADMIN_EMAIL'),
        'password' => env('MARKEDGE_ADMIN_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Styleguide
    |--------------------------------------------------------------------------
    |
    | The /styleguide page renders every design-system primitive. Never enable in production.
    |
    */

    'styleguide_enabled' => env('MARKEDGE_STYLEGUIDE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Privacy
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SEO
    |--------------------------------------------------------------------------
    |
    | Only production should be indexable. Every other environment emits noindex
    | regardless of per-entity settings.
    |
    */

    'seo' => [
        'indexable' => env('MARKEDGE_INDEXABLE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sitemap & redirects
    |--------------------------------------------------------------------------
    */

    'sitemap' => [
        'cache_minutes' => env('MARKEDGE_SITEMAP_CACHE_MINUTES', 1440),
        'chunk' => 500,
    ],

    'redirects' => [
        // External hosts that redirects may point to. Empty means internal paths and the site's own host only.
        'allowed_external_hosts' => array_values(array_filter(array_map('trim', explode(',', (string) env('MARKEDGE_REDIRECT_HOSTS', ''))))),
        'max_depth' => 5,
    ],

    'preview' => [
        'ttl_hours' => env('MARKEDGE_PREVIEW_TTL_HOURS', 24),
    ],

    'privacy' => [
        'store_ip' => env('MARKEDGE_STORE_LEAD_IP', false),
        'lead_retention_days' => env('MARKEDGE_LEAD_RETENTION_DAYS', 730),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    |
    | Every permission is "{subject}.{action}". Roles list permission patterns;
    | "pages.*" expands to every action for that subject.
    |
    */

    /*
    | First-party attribution cookie (architecture §20). No personal data, no third parties.
    */
    'attribution' => [
        'cookie' => 'mk_attr',
        'cookie_days' => 90,
        'max_length' => 120,
        // A visit with no campaign or external referrer never overwrites the stored last touch.
        'direct_overwrites_last_touch' => false,
        // A CTA click is linked to a lead only when the form is submitted within this window.
        'cta_window_minutes' => 60,
    ],

    'leads' => [
        'duplicate_window_hours' => 24,
        'submissions_per_minute' => 5,
    ],

    'cta' => [
        // External hosts CTA destinations may redirect to, in addition to markedge.redirects.allowed_external_hosts.
        'allowed_external_hosts' => ['wa.me', 'api.whatsapp.com'],
    ],

    'analytics' => [
        'event_retention_days' => (int) env('MARKEDGE_EVENT_RETENTION_DAYS', 400),
        // Page views are high-volume and only feed aggregates; they are pruned sooner (Phase 13 §8.8).
        'pageview_retention_days' => (int) env('MARKEDGE_PAGEVIEW_RETENTION_DAYS', 90),
        'session_minutes' => 30,
        'bot_pattern' => '/bot|crawl|spider|slurp|lighthouse|headlesschrome|curl|wget|python-requests|httpclient|facebookexternalhit|preview|monitor/i',
    ],

    /*
    | Editorial operations (Phase 9): reminders before scheduled unpublishing, revision retention
    | and the window in which consecutive saves by the same user fold into one revision.
    */
    /*
    | Production hardening (Phase 10). Everything here is environment-driven so infrastructure
    | values never live in code.
    */
    'security' => [
        // Comma-separated proxy IPs/CIDRs, or "*" behind a CDN/load balancer that sets X-Forwarded-* headers.
        'trusted_proxies' => env('TRUSTED_PROXIES'),
        // Send Strict-Transport-Security on HTTPS responses. Enable only on HTTPS-only deployments.
        'hsts' => (bool) env('MARKEDGE_HSTS', false),
        'hsts_max_age' => (int) env('MARKEDGE_HSTS_MAX_AGE', 31536000),
        // Content-Security-Policy for the public site (admin surfaces are excluded, see SecurityHeaders).
        'csp' => (bool) env('MARKEDGE_CSP', true),
        'csp_report_only' => (bool) env('MARKEDGE_CSP_REPORT_ONLY', false),
        // Extra origins allowed to serve images/media (e.g. a CDN or S3 bucket host).
        'media_origins' => array_values(array_filter(array_map('trim', explode(',', (string) env('MARKEDGE_MEDIA_ORIGINS', ''))))),
        'frame_origins' => ['https://www.youtube.com', 'https://www.youtube-nocookie.com', 'https://player.vimeo.com'],
    ],

    'rate_limits' => [
        'search' => (int) env('MARKEDGE_RATE_SEARCH', 30),
        'cta' => (int) env('MARKEDGE_RATE_CTA', 60),
        'preview' => (int) env('MARKEDGE_RATE_PREVIEW', 60),
        'health' => (int) env('MARKEDGE_RATE_HEALTH', 60),
    ],

    'editorial' => [
        'expiry_reminder_days' => 3,
        'revisions_per_record' => 100,
        'revision_coalesce_seconds' => 20,
    ],

    'permissions' => [
        'subjects' => [
            'pages', 'menus', 'settings', 'announcements', 'service_categories',
            'services', 'technologies', 'solutions', 'products', 'industries',
            'case_studies', 'clients', 'testimonials', 'articles',
            'article_categories', 'tags', 'authors', 'faqs', 'landing_pages',
            'campaigns', 'forms', 'ctas', 'leads', 'redirects', 'seo', 'media',
            'users', 'roles', 'activity',
        ],
        'actions' => [
            'view_any', 'view', 'create', 'update', 'delete', 'restore',
            'force_delete', 'publish', 'preview', 'reorder', 'export', 'review',
        ],
    ],

    'roles' => [
        'Super Admin' => ['*'],
        'Website Manager' => [
            'pages.*', 'menus.*', 'announcements.*', 'media.*', 'faqs.*',
            'settings.view_any', 'settings.update',
            'service_categories.view_any', 'service_categories.view', 'service_categories.update', 'service_categories.reorder', 'service_categories.preview', 'service_categories.review',
            'services.view_any', 'services.view', 'services.update', 'services.reorder', 'services.preview', 'services.review',
            'seo.view_any', 'seo.update',
        ],
        'Content Manager' => [
            'articles.view_any', 'articles.view', 'articles.create', 'articles.update', 'articles.delete', 'articles.restore', 'articles.preview', 'articles.review',
            'article_categories.*', 'tags.*', 'authors.*', 'faqs.*', 'media.*',
        ],
        'Editor' => [
            'articles.*', 'article_categories.*', 'tags.*', 'authors.*', 'faqs.*', 'media.*',
        ],
        'SEO Manager' => [
            'seo.*', 'redirects.*',
            'pages.view_any', 'pages.view', 'pages.preview',
            'services.view_any', 'services.view', 'services.preview',
            'service_categories.view_any', 'service_categories.view',
            'products.view_any', 'products.view', 'products.preview',
            'solutions.view_any', 'solutions.view',
            'industries.view_any', 'industries.view',
            'case_studies.view_any', 'case_studies.view',
            'articles.view_any', 'articles.view', 'articles.preview',
            'landing_pages.view_any', 'landing_pages.view',
        ],
        'Marketing Manager' => [
            'landing_pages.*', 'campaigns.*', 'forms.*', 'ctas.*', 'media.*',
            'leads.view_any', 'leads.view', 'leads.update', 'leads.export',
            'settings.view_any',
        ],
        'Product Manager' => [
            'products.*', 'technologies.*', 'faqs.*', 'media.*',
            'testimonials.view_any', 'testimonials.view',
        ],
        'Sales' => [
            'leads.view_any', 'leads.view', 'leads.update',
            'ctas.view_any', 'ctas.view',
        ],
    ],

];
