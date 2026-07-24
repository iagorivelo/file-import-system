<?php

declare(strict_types=1);

use Src\Infrastructure\Persistence\Models\Company;
use Src\Infrastructure\Persistence\Models\ImportTemplate;
use Src\Infrastructure\Persistence\Models\Program;
use Src\Infrastructure\Persistence\Models\User;

it('semeia empresa demo, vincula usuários e escopa o programa Teste', function () {
    $this->seed();

    $company = Company::query()->where('name', 'Empresa Demo')->firstOrFail();

    expect($company->users()->count())->toBe(2)
        ->and(User::query()->where('email', 'usuario@fileimport.local')->exists())->toBeTrue();

    $program = Program::query()->where('name', 'Teste')->firstOrFail();
    expect($program->company_id)->toBe($company->id);

    // A biblioteca de nichos foi semeada (templates globais).
    expect(ImportTemplate::globalNiche()->count())->toBe(3);
});
