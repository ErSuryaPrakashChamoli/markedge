<?php

use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Leads\LeadResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\User;

it('redirects guests to the login page', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('lets a super admin open the dashboard and every resource', function () {
    $this->actingAs(adminUser());

    $this->get('/admin')->assertOk();
    $this->get(PageResource::getUrl('index'))->assertOk();
    $this->get(ProductResource::getUrl('index'))->assertOk();
    $this->get(LeadResource::getUrl('index'))->assertOk();
});

it('blocks inactive users from the panel even with a role', function () {
    $user = User::factory()->inactive()->create();
    $user->assignRole('Super Admin');

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('blocks users without a role', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('enforces resource access by role on direct URLs', function (string $role, string $resource, bool $allowed) {
    $this->actingAs(adminUser($role));

    $response = $this->get($resource::getUrl('index'));

    $allowed ? $response->assertOk() : $response->assertForbidden();
})->with([
    'sales can open leads' => ['Sales', LeadResource::class, true],
    'sales cannot open pages' => ['Sales', PageResource::class, false],
    'content manager can open articles' => ['Content Manager', ArticleResource::class, true],
    'content manager cannot open products' => ['Content Manager', ProductResource::class, false],
    'product manager can open products' => ['Product Manager', ProductResource::class, true],
    'product manager cannot open leads' => ['Product Manager', LeadResource::class, false],
    'website manager can open pages' => ['Website Manager', PageResource::class, true],
]);

it('hides navigation items the user cannot access', function () {
    $this->actingAs(adminUser('Sales'));

    $this->get('/admin')->assertOk()->assertSee('Enquiries')->assertDontSee('Global Settings');
});
