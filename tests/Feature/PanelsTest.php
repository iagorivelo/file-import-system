<?php

declare(strict_types=1);

use Src\Infrastructure\Persistence\Models\Company;
use Src\Infrastructure\Persistence\Models\Program;
use Src\Infrastructure\Persistence\Models\User;

/**
 * URL da página de programas escopada à empresa (tenant): /app/{tenant}. Sob
 * tenancy, o painel do usuário só existe dentro de uma empresa.
 */
function appHome(Company $company): string
{
    return '/app/'.$company->getKey();
}

it('redireciona a raiz para o login do painel do usuário', function () {
    $this->get('/')->assertRedirect(route('filament.app.auth.login'));
});

it('redireciona visitante para o login do painel admin', function () {
    $this->get('/admin')->assertRedirect('/admin/login');
});

it('permite que um administrador ativo acesse o painel admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin')->assertSuccessful();
});

it('bloqueia usuário comum no painel admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

it('bloqueia usuário inativo em qualquer painel', function () {
    $user = User::factory()->inactive()->create();

    $this->actingAs($user)->get('/app')->assertForbidden();
});

it('renderiza as telas de gestão do painel admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/users')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/programs')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/file-imports')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/companies')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/import-templates')->assertSuccessful();
});

it('renderiza o construtor de empresas e de templates no painel admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/companies/create')->assertSuccessful();
    $this->actingAs($admin)->get('/admin/import-templates/create')->assertSuccessful();
});

it('redireciona a raiz do painel app para a empresa (tenant) do usuário', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company);

    $this->actingAs($user)
        ->get('/app')
        ->assertRedirect(appHome($company));
});

it('bloqueia o acesso a uma empresa (tenant) à qual o usuário não pertence', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    // Usuário propositalmente NÃO vinculado à empresa.

    // O Filament esconde a existência do tenant: acesso negado vira 404, não 403.
    $this->actingAs($user)
        ->get(appHome($company))
        ->assertNotFound();
});

it('mostra ao usuário comum apenas os programas liberados para ele', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company);

    $liberado = Program::factory()->create([
        'company_id' => $company->id,
        'name' => 'Acordos Teste',
        'is_active' => true,
    ]);
    $user->programs()->attach($liberado);

    $this->actingAs($user)
        ->get(appHome($company))
        ->assertSuccessful()
        ->assertSee('Acordos Teste');
});

it('não mostra ao usuário um programa que não lhe foi liberado', function () {
    $user = User::factory()->create();
    $company = Company::factory()->create();
    $user->companies()->attach($company);

    Program::factory()->create([
        'company_id' => $company->id,
        'name' => 'Programa Restrito',
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(appHome($company))
        ->assertSuccessful()
        ->assertDontSee('Programa Restrito');
});

it('um usuário não vê o programa liberado para outro usuário', function () {
    $company = Company::factory()->create();

    $user = User::factory()->create();
    $user->companies()->attach($company);

    $outro = User::factory()->create();
    $outro->companies()->attach($company);

    $program = Program::factory()->create([
        'company_id' => $company->id,
        'name' => 'Programa do Outro',
        'is_active' => true,
    ]);
    $outro->programs()->attach($program);

    $this->actingAs($user)
        ->get(appHome($company))
        ->assertSuccessful()
        ->assertDontSee('Programa do Outro');
});

it('não mostra um programa de outra empresa, mesmo liberado ao usuário', function () {
    $user = User::factory()->create();
    $companyAtual = Company::factory()->create();
    $outraEmpresa = Company::factory()->create();
    $user->companies()->attach([$companyAtual->id, $outraEmpresa->id]);

    // Programa liberado ao usuário, mas pertencente a OUTRA empresa.
    $programaDeOutra = Program::factory()->create([
        'company_id' => $outraEmpresa->id,
        'name' => 'Programa de Outra Empresa',
        'is_active' => true,
    ]);
    $user->programs()->attach($programaDeOutra);

    $this->actingAs($user)
        ->get(appHome($companyAtual))
        ->assertSuccessful()
        ->assertDontSee('Programa de Outra Empresa');
});

it('exibe o campo de programas liberados no formulário de usuário', function () {
    $admin = User::factory()->admin()->create();
    Program::factory()->create(['name' => 'Programa X', 'is_active' => true]);

    $this->actingAs($admin)
        ->get('/admin/users/create')
        ->assertSuccessful()
        ->assertSee('Programas liberados');
});

it('administrador enxerga todos os programas da empresa no painel app', function () {
    $admin = User::factory()->admin()->create();
    $company = Company::factory()->create();
    $admin->companies()->attach($company);

    Program::factory()->create([
        'company_id' => $company->id,
        'name' => 'Programa Global',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(appHome($company))
        ->assertSuccessful()
        ->assertSee('Programa Global');
});
