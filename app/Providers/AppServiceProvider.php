<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Author;
use App\Models\Campaign;
use App\Models\CaseStudy;
use App\Models\Client;
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
use App\Policies\ActivityPolicy;
use App\Policies\RolePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        $this->registerMorphMap();
        $this->registerAuthorization();
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
    }
}
