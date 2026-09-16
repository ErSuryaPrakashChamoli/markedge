<?php

use App\Enums\ProductStatus;
use App\Enums\PublishStatus;
use App\Models\CaseStudy;
use App\Models\Client;
use App\Models\Form;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Spatie\Permission\Models\Role;

it('seeds structure without any fabricated business proof', function () {
    config(['markedge.admin.email' => null, 'markedge.admin.password' => null]);

    $this->seed(DatabaseSeeder::class);

    expect(Role::count())->toBe(count(config('markedge.roles')))
        ->and(ServiceCategory::count())->toBe(3)
        ->and(Service::count())->toBe(24)
        ->and(Service::published()->count())->toBe(0)
        ->and(Product::count())->toBe(2)
        ->and(Product::where('status', ProductStatus::Draft)->count())->toBe(2)
        ->and(Form::count())->toBe(8)
        ->and(Page::where('slug', Page::HOME_SLUG)->exists())->toBeTrue()
        ->and(Page::where('status', '!=', PublishStatus::Draft)->count())->toBe(0)
        ->and(Menu::where('key', 'header')->first()->items()->count())->toBeGreaterThan(5)
        ->and(Client::count())->toBe(0)
        ->and(Testimonial::count())->toBe(0)
        ->and(CaseStudy::count())->toBe(0)
        ->and(User::count())->toBe(0);
});

it('is idempotent', function () {
    config(['markedge.admin.email' => null, 'markedge.admin.password' => null]);

    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Service::count())->toBe(24)
        ->and(Form::count())->toBe(8)
        ->and(Page::count())->toBe(12)
        ->and(Menu::where('key', 'footer')->first()->rootItems()->count())->toBe(5);
});

it('creates the super admin from environment credentials', function () {
    config(['markedge.admin.email' => 'owner@example.test', 'markedge.admin.password' => 'a-long-password-123']);

    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'owner@example.test')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->isSuperAdmin())->toBeTrue()
        ->and($admin->is_active)->toBeTrue();
});

it('links form pages to their seeded forms', function () {
    config(['markedge.admin.email' => null, 'markedge.admin.password' => null]);

    $this->seed(DatabaseSeeder::class);

    expect(Page::where('slug', 'request-demo')->first()->form->key)->toBe('product-demo');
});
