<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Src\Application\User\ChangeUserPassword;
use Src\Application\User\SetUserActivation;
use Src\Infrastructure\Persistence\Models\User;

it('altera a senha de um usuário', function () {
    $user = User::factory()->create();

    app(ChangeUserPassword::class)($user, 'nova-senha-segura');

    expect(Hash::check('nova-senha-segura', $user->fresh()->password))->toBeTrue();
});

it('ativa e inativa a conta de um usuário', function () {
    $user = User::factory()->create(['is_active' => true]);

    app(SetUserActivation::class)($user, false);
    expect($user->fresh()->is_active)->toBeFalse();

    app(SetUserActivation::class)($user, true);
    expect($user->fresh()->is_active)->toBeTrue();
});
