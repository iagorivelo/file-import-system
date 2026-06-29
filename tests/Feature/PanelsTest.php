<?php

declare(strict_types=1);

use Src\Infrastructure\Persistence\Models\Program;
use Src\Infrastructure\Persistence\Models\User;

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
});

it('mostra ao usuário comum apenas os programas liberados para ele', function () {
    $user = User::factory()->create();
    $liberado = Program::factory()->create(['name' => 'Acordos Teste', 'is_active' => true]);
    $user->programs()->attach($liberado);

    $this->actingAs($user)
        ->get('/app')
        ->assertSuccessful()
        ->assertSee('Acordos Teste');
});

it('não mostra ao usuário um programa que não lhe foi liberado', function () {
    $user = User::factory()->create();
    Program::factory()->create(['name' => 'Programa Restrito', 'is_active' => true]);

    $this->actingAs($user)
        ->get('/app')
        ->assertSuccessful()
        ->assertDontSee('Programa Restrito');
});

it('um usuário não vê o programa liberado para outro usuário', function () {
    $user = User::factory()->create();
    $outro = User::factory()->create();
    $program = Program::factory()->create(['name' => 'Programa do Outro', 'is_active' => true]);
    $outro->programs()->attach($program);

    $this->actingAs($user)
        ->get('/app')
        ->assertSuccessful()
        ->assertDontSee('Programa do Outro');
});

it('exibe o campo de programas liberados no formulário de usuário', function () {
    $admin = User::factory()->admin()->create();
    Program::factory()->create(['name' => 'Programa X', 'is_active' => true]);

    $this->actingAs($admin)
        ->get('/admin/users/create')
        ->assertSuccessful()
        ->assertSee('Programas liberados');
});

it('administrador enxerga todos os programas no painel app', function () {
    $admin = User::factory()->admin()->create();
    Program::factory()->create(['name' => 'Programa Global', 'is_active' => true]);

    $this->actingAs($admin)
        ->get('/app')
        ->assertSuccessful()
        ->assertSee('Programa Global');
});
