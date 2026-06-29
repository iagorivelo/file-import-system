<?php

declare(strict_types=1);

namespace Src\Application\User;

use Src\Infrastructure\Persistence\Models\User;

/**
 * Caso de uso: alterar a senha de um usuário.
 *
 * O hash é aplicado pelo cast "hashed" do model.
 */
final readonly class ChangeUserPassword
{
    public function __invoke(User $user, string $newPassword): void
    {
        $user->update([
            'password' => $newPassword,
        ]);
    }
}
