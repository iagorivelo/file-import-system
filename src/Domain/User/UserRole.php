<?php

declare(strict_types=1);

namespace Src\Domain\User;

/**
 * Classificação do usuário no sistema.
 *
 * Define o nível de acesso: administradores gerenciam usuários e programas,
 * usuários comuns apenas executam importações.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::User => 'Usuário',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * @return array<string, string> value => label, útil para selects no painel.
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }
}
