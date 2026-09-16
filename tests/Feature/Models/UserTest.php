<?php

use App\Models\User;
use Filament\Facades\Filament;
use Spatie\Permission\Models\Role;

it('allows an active user with a role to access the admin panel', function () {
    Role::findOrCreate('Sales', 'web');
    $user = User::factory()->create();
    $user->assignRole('Sales');

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeTrue();
});

it('denies panel access to a user without any role', function () {
    $user = User::factory()->create();

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});

it('denies panel access to an inactive user even with a role', function () {
    Role::findOrCreate('Sales', 'web');
    $user = User::factory()->inactive()->create();
    $user->assignRole('Sales');

    expect($user->canAccessPanel(Filament::getPanel('admin')))->toBeFalse();
});
