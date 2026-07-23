<?php

declare(strict_types=1);

use Src\Application\Import\PreviewImport;
use Src\Domain\Import\FileType;
use Src\Domain\Import\Template\DestinationSpec;
use Src\Domain\Import\Template\FieldSource;
use Src\Domain\Import\Template\FieldType;
use Src\Domain\Import\Template\SourceFormat;
use Src\Domain\Import\Template\Template;
use Src\Domain\Import\Template\TemplateField;
use Src\Domain\Import\Template\ValidationKind;
use Src\Domain\Import\Template\ValidationRule;
use Src\Infrastructure\Import\Parsers\CsvFileParser;

function previewTemplate(): Template
{
    return new Template(
        name: 'Clientes',
        sourceFormat: new SourceFormat(FileType::Csv, delimiter: ';', hasHeader: true),
        fields: [
            new TemplateField('nome', 'Nome', FieldType::Text, true, FieldSource::header('nome')),
            new TemplateField('email', 'E-mail', FieldType::Email, true, FieldSource::header('email'),
                validations: [new ValidationRule(ValidationKind::Email)]),
        ],
        destination: DestinationSpec::exportFile(),
    );
}

it('pré-visualiza linhas mapeadas sem entregar ao destino', function () {
    $path = tempnam(sys_get_temp_dir(), 'pv').'.csv';
    file_put_contents($path, implode("\n", [
        'nome;email',
        'Ana;ana@x.com',        // válida
        'Bruno;invalido',       // e-mail inválido
        '',                     // vazia -> ignorada
        'Carla;carla@x.com',    // válida
    ]));

    $result = (new PreviewImport(new CsvFileParser))($path, previewTemplate());

    expect($result->headers)->toBe(['nome', 'email'])
        ->and($result->previewed)->toBe(3)          // vazia foi ignorada
        ->and($result->validCount)->toBe(2)
        ->and($result->invalidCount)->toBe(1)
        ->and($result->rows[0]->output)->toMatchArray(['nome' => 'Ana', 'email' => 'ana@x.com'])
        ->and($result->rows[0]->valid)->toBeTrue()
        ->and($result->rows[1]->valid)->toBeFalse()
        ->and($result->rows[1]->errors)->not->toBeEmpty();

    @unlink($path);
});

it('respeita o limite de linhas do preview', function () {
    $path = tempnam(sys_get_temp_dir(), 'pv').'.csv';
    $lines = ['nome;email'];
    for ($i = 1; $i <= 10; $i++) {
        $lines[] = "Pessoa {$i};p{$i}@x.com";
    }
    file_put_contents($path, implode("\n", $lines));

    $result = (new PreviewImport(new CsvFileParser))($path, previewTemplate(), limit: 3);

    expect($result->previewed)->toBe(3)
        ->and($result->rows)->toHaveCount(3);

    @unlink($path);
});
