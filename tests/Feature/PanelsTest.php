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

it('mostra os programas ativos para o usuário comum no painel app', function () {
    $user = User::factory()->create();
    Program::factory()->create(['name' => 'Acordos Teste', 'is_active' => true]);

    $this->actingAs($user)
        ->get('/app')
        ->assertSuccessful()
        ->assertSee('Acordos Teste');
});
