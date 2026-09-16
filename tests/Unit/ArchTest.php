<?php

arch('enums are string or int backed and live in App\Enums')
    ->expect('App\Enums')
    ->toBeEnums()
    ->toBeStringBackedEnums()->ignoring('App\Enums\RedirectStatus');

arch('models extend Eloquent')
    ->expect('App\Models')
    ->classes()
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('policies extend the permission policy')
    ->expect('App\Policies')
    ->classes()
    ->toExtend('App\Policies\PermissionPolicy')
    ->ignoring('App\Policies\PermissionPolicy');

arch('no debugging calls remain')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
