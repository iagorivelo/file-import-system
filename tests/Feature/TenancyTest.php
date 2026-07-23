<?php

declare(strict_types=1);

use Src\Infrastructure\Persistence\Models\Company;
use Src\Infrastructure\Persistence\Models\ImportTemplate;
use Src\Infrastructure\Persistence\Models\User;

/**
 * Fundação de dados da tenancy (banco único + escopo por empresa). Não ativa a
 * UI do Filament — valida relações, o contrato HasTenants e o escopo de dados.
 */
it('associa usuários a empresas e expõe os tenants do usuário', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();
    $outra = Company::factory()->create();

    $user = User::factory()->create();
    $user->companies()->attach([$companyA->id, $companyB->id]);

    $tenants = $user->getTenants(app('filament')->getPanel('app'));

    expect($tenants->pluck('id')->all())->toEqualCanonicalizing([$companyA->id, $companyB->id])
        ->and($user->canAccessTenant($companyA))->toBeTrue()
        ->and($user->canAccessTenant($companyB))->toBeTrue()
        ->and($user->canAccessTenant($outra))->toBeFalse();
});

it('escopa programas, importações e templates por empresa', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $companyA->programs()->create([
        'name' => 'A',
        'processor_class' => 'X',
    ]);
    $companyB->programs()->create([
        'name' => 'B',
        'processor_class' => 'Y',
    ]);

    ImportTemplate::factory()->create(['company_id' => $companyA->id]);

    expect($companyA->programs()->count())->toBe(1)
        ->and($companyA->programs()->first()->name)->toBe('A')
        ->and($companyB->programs()->count())->toBe(1)
        ->and($companyA->importTemplates()->count())->toBe(1);
});

it('distingue template de nicho global de template de empresa', function () {
    $company = Company::factory()->create();

    $global = ImportTemplate::factory()->create(['company_id' => null, 'niche' => 'veterinaria']);
    $daEmpresa = ImportTemplate::factory()->create(['company_id' => $company->id]);

    expect($global->isGlobal())->toBeTrue()
        ->and($daEmpresa->isGlobal())->toBeFalse()
        ->and(ImportTemplate::globalNiche()->pluck('id')->all())->toBe([$global->id]);
});
