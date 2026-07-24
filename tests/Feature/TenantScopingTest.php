<?php

declare(strict_types=1);

use App\Filament\App\Pages\Programs;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Src\Application\Import\StartImport;
use Src\Application\Import\StartImportData;
use Src\Domain\Import\FileType;
use Src\Domain\User\UserRole;
use Src\Infrastructure\Import\Processors\TesteProcessor;
use Src\Infrastructure\Persistence\Models\Company;
use Src\Infrastructure\Persistence\Models\Program;
use Src\Infrastructure\Persistence\Models\User;

it('grava company_id (tenant) na importação iniciada', function () {
    Storage::fake('local');

    $company = Company::factory()->create();
    $user = User::factory()->create();
    $program = Program::factory()->create([
        'company_id' => $company->id,
        'processor_class' => TesteProcessor::class,
    ]);

    $storedPath = UploadedFile::fake()
        ->createWithContent('nomes.txt', "Ana\n")
        ->store('imports', 'local');

    $import = app(StartImport::class)(new StartImportData(
        userId: $user->id,
        programId: $program->id,
        originalFilename: 'nomes.txt',
        storedPath: $storedPath,
        type: FileType::Txt,
        companyId: $company->id,
    ));

    expect($import->refresh()->company_id)->toBe($company->id);
});

it('a página Programs lista apenas programas da empresa (tenant) atual', function () {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $admin->companies()->attach([$companyA->id, $companyB->id]);

    Program::factory()->create(['company_id' => $companyA->id, 'name' => 'Programa A', 'is_active' => true]);
    Program::factory()->create(['company_id' => $companyB->id, 'name' => 'Programa B', 'is_active' => true]);

    $this->actingAs($admin);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($companyA);

    $names = (new Programs)->getPrograms()->pluck('name')->all();

    expect($names)->toBe(['Programa A']);
});

it('sem empresa (tenant) atual, a página Programs não lista nada', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();
    Program::factory()->create(['company_id' => $company->id, 'is_active' => true]);

    $this->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('app'));
    // Nenhum tenant definido.

    expect((new Programs)->getPrograms())->toBeEmpty();
});
