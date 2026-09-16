<?php

use App\Models\Article;
use App\Models\Lead;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Gate;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

function userWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

it('grants every ability to the super admin', function () {
    $user = userWithRole('Super Admin');

    expect(Gate::forUser($user)->allows('publish', Article::factory()->create()))->toBeTrue()
        ->and(Gate::forUser($user)->allows('forceDelete', Lead::factory()->create()))->toBeTrue();
});

it('applies the configured permission matrix', function (string $role, string $ability, string $model, bool $expected) {
    $user = userWithRole($role);
    $record = $model::factory()->create();

    expect(Gate::forUser($user)->allows($ability, $record))->toBe($expected);
})->with([
    'content manager can update articles' => ['Content Manager', 'update', Article::class, true],
    'content manager cannot publish articles' => ['Content Manager', 'publish', Article::class, false],
    'editor can publish articles' => ['Editor', 'publish', Article::class, true],
    'content manager cannot update pages' => ['Content Manager', 'update', Page::class, false],
    'website manager can publish pages' => ['Website Manager', 'publish', Page::class, true],
    'website manager cannot update products' => ['Website Manager', 'update', Product::class, false],
    'product manager can publish products' => ['Product Manager', 'publish', Product::class, true],
    'product manager cannot view leads' => ['Product Manager', 'view', Lead::class, false],
    'sales can update leads' => ['Sales', 'update', Lead::class, true],
    'sales cannot delete leads' => ['Sales', 'delete', Lead::class, false],
    'marketing manager can update leads' => ['Marketing Manager', 'update', Lead::class, true],
    'seo manager can preview articles' => ['SEO Manager', 'preview', Article::class, true],
    'seo manager cannot update articles' => ['SEO Manager', 'update', Article::class, false],
]);

it('lets marketing managers export leads but not sales', function () {
    expect(Gate::forUser(userWithRole('Marketing Manager'))->allows('export', Lead::class))->toBeTrue()
        ->and(Gate::forUser(userWithRole('Sales'))->allows('export', Lead::class))->toBeFalse();
});

it('denies everything to a user without roles', function () {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', Article::class))->toBeFalse();
});
