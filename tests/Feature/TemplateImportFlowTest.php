<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Src\Application\Import\StartImport;
use Src\Application\Import\StartImportData;
use Src\Domain\Import\FileType;
use Src\Domain\Import\ImportStatus;
use Src\Domain\Import\Template\DestinationSpec;
use Src\Domain\Import\Template\FieldSource;
use Src\Domain\Import\Template\FieldType;
use Src\Domain\Import\Template\SourceFormat;
use Src\Domain\Import\Template\TemplateField;
use Src\Domain\Import\Template\ValidationKind;
use Src\Domain\Import\Template\ValidationRule;
use Src\Infrastructure\Import\Processors\TemplateProcessor;
use Src\Infrastructure\Persistence\Models\ImportTemplate;
use Src\Infrastructure\Persistence\Models\Program;
use Src\Infrastructure\Persistence\Models\User;

/**
 * Fluxo ponta a ponta do MOTOR CONFIGURÁVEL: um Program em "modo template"
 * (sem classe dedicada) importa um CSV, aplica mapeamento/validações/dedup e
 * entrega no destino — provando o wiring Program.template_id → RunImport →
 * TemplateProcessor. Não renderiza UI (roda no host, sem depender de intl).
 */
it('importa via template configurável, sem classe dedicada por arquivo', function () {
    Storage::fake('local');

    $exportDir = sys_get_temp_dir().'/tpl_flow_'.uniqid();
    config()->set('file_import.exports.directory', $exportDir);

    $template = ImportTemplate::create([
        'name' => 'Clientes',
        'niche' => 'generico',
        'source_format' => (new SourceFormat(FileType::Csv, delimiter: ';', hasHeader: true))->toArray(),
        'fields' => [
            (new TemplateField('nome', 'Nome', FieldType::Text, true, FieldSource::header('Nome')))->toArray(),
            (new TemplateField('email', 'E-mail', FieldType::Email, true, FieldSource::header('E-mail'),
                validations: [new ValidationRule(ValidationKind::Email)]))->toArray(),
        ],
        'destination' => DestinationSpec::exportFile()->toArray(),
        'dedup_key' => 'nome',
    ]);

    $user = User::factory()->create();
    $program = Program::factory()->create([
        'name' => 'Clientes (template)',
        'processor_class' => TemplateProcessor::class,
        'template_id' => $template->id,
    ]);

    $content = implode("\n", [
        'Nome;E-mail',
        'Ana;ana@exemplo.com',       // válido
        'Bruno;bruno@exemplo.com',   // válido
        'Ana;ana2@exemplo.com',      // duplicado por nome -> erro
        'Carla;email-invalido',      // e-mail inválido -> erro
    ]);

    $storedPath = UploadedFile::fake()
        ->createWithContent('clientes.csv', $content)
        ->store('imports', 'local');

    $import = app(StartImport::class)(new StartImportData(
        userId: $user->id,
        programId: $program->id,
        originalFilename: 'clientes.csv',
        storedPath: $storedPath,
        type: FileType::Csv,
    ));

    $import->refresh();

    expect($import->status)->toBe(ImportStatus::Completed)
        ->and($import->processed_rows)->toBe(2)
        ->and($import->failed_rows)->toBe(2)
        ->and($import->error_message)->toContain('duplicado');

    // Destino "arquivo de exportação" gerou o CSV normalizado.
    $generated = glob($exportDir.'/export_*.csv') ?: [];
    expect($generated)->toHaveCount(1);

    $lines = array_values(array_filter(array_map('trim', file($generated[0]))));
    expect($lines[0])->toBe('nome,email')
        ->and($lines[1])->toBe('Ana,ana@exemplo.com')
        ->and($lines[2])->toBe('Bruno,bruno@exemplo.com');

    @unlink($generated[0]);
    @rmdir($exportDir);
});

it('programa em modo template sabe que usa template', function () {
    $template = ImportTemplate::factory()->create();
    $program = Program::factory()->create([
        'processor_class' => TemplateProcessor::class,
        'template_id' => $template->id,
    ]);

    expect($program->usesTemplate())->toBeTrue()
        ->and($program->template->toDomain()->name)->toBe($template->name);
});
