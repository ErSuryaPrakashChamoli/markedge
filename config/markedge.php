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
            'force_delete', 'publish', 'preview', 'reorder', 'export',
        ],
    ],

    'roles' => [
        'Super Admin' => ['*'],
        'Website Manager' => [
            'pages.*', 'menus.*', 'announcements.*', 'media.*', 'faqs.*',
            'settings.view_any', 'settings.update',
            'service_categories.view_any', 'service_categories.view', 'service_categories.update', 'service_categories.reorder', 'service_categories.preview',
            'services.view_any', 'services.view', 'services.update', 'services.reorder', 'services.preview',
            'seo.view_any', 'seo.update',
        ],
        'Content Manager' => [
            'articles.view_any', 'articles.view', 'articles.create', 'articles.update', 'articles.delete', 'articles.restore', 'articles.preview',
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
