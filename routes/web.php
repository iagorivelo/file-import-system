<?php

use Illuminate\Support\Facades\Route;

// A raiz não tem tela própria: manda o visitante direto para o login do painel
// do usuário. Usamos o nome da rota (e não "/app/login" fixo) para continuar
// funcionando caso o path() do painel mude.
Route::get('/', function () {
    return redirect()->route('filament.app.auth.login');
});
