<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Domain\User\UserRole;
use Src\Infrastructure\Import\Processors\TesteProcessor;
use Src\Infrastructure\Persistence\Models\Company;
use Src\Infrastructure\Persistence\Models\Program;
use Src\Infrastructure\Persistence\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@fileimport.local'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'role' => UserRole::Admin,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        $commonUser = User::query()->updateOrCreate(
            ['email' => 'usuario@fileimport.local'],
            [
                'name' => 'Usuário Comum',
                'password' => 'password',
                'role' => UserRole::User,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        // Empresa (tenant) de exemplo; ambos os usuários pertencem a ela para
        // conseguirem acessar o painel /app sob tenancy.
        $company = Company::query()->updateOrCreate(
            ['name' => 'Empresa Demo'],
            ['niche' => 'generico', 'is_active' => true],
        );
        $company->users()->syncWithoutDetaching([$admin->getKey(), $commonUser->getKey()]);

        $program = Program::query()->updateOrCreate(
            ['name' => 'Teste'],
            [
                'company_id' => $company->id,
                'color' => '#4b6043',
                'processor_class' => TesteProcessor::class,
                'is_active' => true,
            ],
        );

        // Libera o programa "Teste" para o usuário comum de exemplo.
        $program->users()->syncWithoutDetaching([$commonUser->getKey()]);

        // Biblioteca de templates de nicho (globais) para adoção pelas empresas.
        $this->call(NicheTemplateSeeder::class);
    }
}
