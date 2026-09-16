<?php

use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('creates an admin user with a hashed password and roles', function () {
    $this->actingAs(adminUser());
    $role = Role::findByName('Content Manager');

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Priya Editor', 'email' => 'priya@example.test', 'password' => 'a-strong-password-123', 'is_active' => true, 'roles' => [$role->id]])
        ->call('create')
        ->assertHasNoFormErrors();

    $user = User::query()->where('email', 'priya@example.test')->first();

    expect($user)->not->toBeNull()
        ->and(Hash::check('a-strong-password-123', $user->password))->toBeTrue()
        ->and($user->hasRole('Content Manager'))->toBeTrue();
});

it('requires a role and a strong password', function () {
    $this->actingAs(adminUser());

    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Weak', 'email' => 'weak@example.test', 'password' => 'short'])
        ->call('create')
        ->assertHasFormErrors(['password', 'roles']);
});

it('keeps user management away from non super admins', function () {
    $this->actingAs(adminUser('Website Manager'));

    $this->get(UserResource::getUrl('index'))->assertForbidden();
});

it('lists the seeded roles with their permission counts', function () {
    $this->actingAs(adminUser());

    Livewire::test(ListRoles::class)->assertCanSeeTableRecords(Role::all());
});

it('never lets the super admin role be deleted from the table', function () {
    $this->actingAs(adminUser());

    Livewire::test(ListRoles::class)->assertTableActionHidden('delete', Role::findByName('Super Admin'));
});
