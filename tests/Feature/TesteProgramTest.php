<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Src\Application\Import\StartImport;
use Src\Application\Import\StartImportData;
use Src\Domain\Import\FileType;
use Src\Domain\Import\ImportStatus;
use Src\Infrastructure\Import\Processors\TesteProcessor;
use Src\Infrastructure\Persistence\Models\Program;
use Src\Infrastructure\Persistence\Models\TesteRecord;
use Src\Infrastructure\Persistence\Models\User;

it('o programa Teste aceita apenas .txt', function () {
    expect(TesteProcessor::acceptedFileTypes())->toBe([FileType::Txt]);
});

it('importa um .txt e grava os nomes em testes_tb', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $program = Program::factory()->create([
        'name' => 'Teste',
        'processor_class' => TesteProcessor::class,
    ]);

    $storedPath = UploadedFile::fake()
        ->createWithContent('pessoas.txt', "Maria Silva\nJoão Souza\n\nAna Lima\n")
        ->store('imports', 'local');

    $import = app(StartImport::class)(new StartImportData(
        userId: $user->id,
        programId: $program->id,
        originalFilename: 'pessoas.txt',
        storedPath: $storedPath,
        type: FileType::Txt,
    ));

    $import->refresh();

    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->processed_rows)->toBe(3)
        ->and(TesteRecord::count())->toBe(3)
        ->and(TesteRecord::query()->orderBy('id')->pluck('nome')->all())
        ->toBe(['Maria Silva', 'João Souza', 'Ana Lima']);
});

it('valida nomes: ignora vazias e registra inválidos/duplicados como erro', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $program = Program::factory()->create(['processor_class' => TesteProcessor::class]);

    $content = implode("\n", [
        'Maria Silva',  // válido
        '',             // ignorado
        'Maria Silva',  // duplicado -> erro
        '123',          // sem letras -> erro
        'A',            // muito curto -> erro
        'João Souza',   // válido
    ])."\n";

    $storedPath = UploadedFile::fake()
        ->createWithContent('nomes.txt', $content)
        ->store('imports', 'local');

    $import = app(StartImport::class)(new StartImportData(
        userId: $user->id,
        programId: $program->id,
        originalFilename: 'nomes.txt',
        storedPath: $storedPath,
        type: FileType::Txt,
    ));

    $import->refresh();

    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->processed_rows)->toBe(2)
        ->and($import->failed_rows)->toBe(3)
        ->and($import->hasErrors())->toBeTrue()
        ->and($import->error_message)->toContain('duplicado', 'sem letras', 'muito curto')
        ->and(TesteRecord::count())->toBe(2);
});
