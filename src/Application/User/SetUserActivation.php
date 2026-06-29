<?php

declare(strict_types=1);

namespace Src\Application\User;

use Src\Infrastructure\Persistence\Models\User;

/**
 * Caso de uso: ativar ou inativar a conta de um usuário.
 */
final readonly class SetUserActivation
{
    public function __invoke(User $user, bool $active): void
    {
        $user->update([
            'is_active' => $active,
        ]);
    }
}
