<?php

declare(strict_types=1);

use Database\Seeders\NicheTemplateSeeder;
use Src\Domain\Import\FileType;
use Src\Domain\Import\ImportContext;
use Src\Infrastructure\Import\Destinations\DefaultDestinationWriterFactory;
use Src\Infrastructure\Import\Parsers\CsvFileParser;
use Src\Infrastructure\Import\Processors\TemplateProcessor;
use Src\Infrastructure\Persistence\Models\ImportTemplate;

it('semeia templates de nicho globais e reconstitui cada um', function () {
    $this->seed(NicheTemplateSeeder::class);

    $globais = ImportTemplate::globalNiche()->get();

    expect($globais)->toHaveCount(3)
        ->and($globais->pluck('niche')->all())
        ->toEqualCanonicalizing(['veterinaria', 'farmacia', 'escola']);

    // Cada template semeado reconstitui num value object de domínio válido.
    $globais->each(function (ImportTemplate $t): void {
        $domain = $t->toDomain();
        expect($domain->fields)->not->toBeEmpty()
            ->and($domain->name)->toBe($t->name);
    });
});

it('um template de nicho semeado importa um arquivo ponta a ponta', function () {
    $this->seed(NicheTemplateSeeder::class);

    $farmacia = ImportTemplate::globalNiche()->where('niche', 'farmacia')->firstOrFail();

    $path = tempnam(sys_get_temp_dir(), 'niche').'.csv';
    file_put_contents($path, implode("\n", [
        'Código;Descrição;Preço;Estoque',
        '001;Dipirona;9,90;100',
        '002;Paracetamol;12,50;50',
        '001;Dipirona repetida;9,90;10',   // código duplicado -> erro (dedup)
    ]));

    $processor = new TemplateProcessor(
        new CsvFileParser,
        new DefaultDestinationWriterFactory(sys_get_temp_dir()),
    );
    $result = $processor->process(
        new ImportContext(1, $path, FileType::Csv, 'produtos.csv', $farmacia->toDomain()),
    );

    expect($result->processedRows)->toBe(2)
        ->and($result->failedRows)->toBe(1)
        ->and(implode("\n", $result->errors))->toContain('duplicado');

    @unlink($path);
});
