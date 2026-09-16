<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Author;
use App\Models\Campaign;
use App\Models\CaseStudy;
use App\Models\Client;
use App\Models\ConversionEvent;
use App\Models\Cta;
use App\Models\CtaClick;
use App\Models\Faq;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Industry;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductFeature;
use App\Models\ProductModule;
use App\Models\Redirect;
use App\Models\SearchEntry;
use App\Models\SeoMeta;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Setting;
use App\Models\SocialLink;
use App\Models\Solution;
use App\Models\Tag;
use App\Models\Technology;
use App\Models\Testimonial;
use App\Models\User;
use App\Observers\SearchableObserver;
use App\Observers\SeoMetaObserver;
use App\Policies\ActivityPolicy;
use App\Policies\MediaPolicy;
use App\Policies\RolePolicy;
use App\Search\Contracts\SearchEngine;
use App\Search\Engines\DatabaseSearchEngine;
use App\Search\SearchTypes;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SearchEngine::class, DatabaseSearchEngine::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        $this->registerMorphMap();
        $this->registerAuthorization();
        $this->registerRateLimiters();
        $this->registerSearchObservers();
    }

    protected function registerSearchObservers(): void
    {
        foreach (SearchTypes::TYPES as $definition) {
            $definition['model']::observe(SearchableObserver::class);
        }

        SeoMeta::observe(SeoMetaObserver::class);
    }

    protected function registerRateLimiters(): void
    {
        RateLimiter::for('preview', fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('search', fn (Request $request): Limit => Limit::perMinute(30)->by($request->ip()));
        RateLimiter::for('cta', fn (Request $request): Limit => Limit::perMinute(60)->by($request->ip()));
        RateLimiter::for('lead-form', fn (Request $request): Limit => Limit::perMinute((int) config('markedge.leads.submissions_per_minute', 5))->by($request->ip()));
    }

    /**
     * Stable aliases for polymorphic columns so class renames never break stored rows.
     */
    protected function registerMorphMap(): void
    {
        Relation::enforceMorphMap([
            'announcement' => Announcement::class,
            'article' => Article::class,
            'article_category' => ArticleCategory::class,
            'author' => Author::class,
            'campaign' => Campaign::class,
            'case_study' => CaseStudy::class,
            'client' => Client::class,
            'cta' => Cta::class,
            'cta_click' => CtaClick::class,
            'conversion_event' => ConversionEvent::class,
            'faq' => Faq::class,
            'form' => Form::class,
            'form_field' => FormField::class,
            'industry' => Industry::class,
            'landing_page' => LandingPage::class,
            'lead' => Lead::class,
            'menu' => Menu::class,
            'menu_item' => MenuItem::class,
            'page' => Page::class,
            'product' => Product::class,
            'product_feature' => ProductFeature::class,
            'product_module' => ProductModule::class,
            'redirect' => Redirect::class,
            'search_entry' => SearchEntry::class,
            'seo_meta' => SeoMeta::class,
            'service' => Service::class,
            'service_category' => ServiceCategory::class,
            'setting' => Setting::class,
            'social_link' => SocialLink::class,
            'solution' => Solution::class,
            'tag' => Tag::class,
            'technology' => Technology::class,
            'testimonial' => Testimonial::class,
            'user' => User::class,
        ]);
    }

    protected function registerAuthorization(): void
    {
        Gate::before(fn (User $user): ?bool => $user->isSuperAdmin() ? true : null);

        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
    }
}
