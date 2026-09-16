<?php

use App\Models\Form;

it('returns enabled core fields ordered by sort order', function () {
    $form = Form::factory()->create([
        'core_fields' => [
            'message' => ['enabled' => true, 'required' => false, 'sort_order' => 3],
            'phone' => ['enabled' => false, 'required' => false, 'sort_order' => 2],
            'name' => ['enabled' => true, 'required' => true, 'sort_order' => 1],
        ],
    ]);

    expect(array_keys($form->enabledCoreFields()))->toBe(['name', 'message']);
});

it('removes its fields when the form is force deleted', function () {
    $form = Form::factory()->hasFields(2)->create();

    $form->forceDelete();

    expect(DB::table('form_fields')->count())->toBe(0);
});
