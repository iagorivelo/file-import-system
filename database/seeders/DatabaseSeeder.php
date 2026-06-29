<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Domain\User\UserRole;
use Src\Infrastructure\Import\Processors\TesteProcessor;
use Src\Infrastructure\Persistence\Models\Program;
use Src\Infrastructure\Persistence\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@fileimport.local'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'role' => UserRole::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        User::query()->updateOrCreate(
            ['email' => 'usuario@fileimport.local'],
            [
                'name' => 'Usuário Comum',
                'password' => 'password',
                'role' => UserRole::User,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        Program::query()->updateOrCreate(
            ['name' => 'Teste'],
            [
                'color' => '#4b6043',
                'processor_class' => TesteProcessor::class,
                'is_active' => true,
            ],
        );
    }
}
