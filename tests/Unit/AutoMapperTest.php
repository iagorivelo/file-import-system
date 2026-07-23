<?php

declare(strict_types=1);

use Src\Application\Import\AutoMapColumns;
use Src\Domain\Import\FileType;
use Src\Domain\Import\Template\AutoMapper;
use Src\Domain\Import\Template\DestinationSpec;
use Src\Domain\Import\Template\FieldSource;
use Src\Domain\Import\Template\FieldType;
use Src\Domain\Import\Template\SourceFormat;
use Src\Domain\Import\Template\Template;
use Src\Domain\Import\Template\TemplateField;
use Src\Infrastructure\Import\Parsers\CsvFileParser;

/**
 * @param  list<array{string, string, list<string>}>  $fields  [key, label, aliases]
 */
function templateWithFields(array $fields): Template
{
    return new Template(
        name: 'T',
        sourceFormat: new SourceFormat(FileType::Csv, delimiter: ';', hasHeader: true),
        fields: array_map(
            static fn (array $f): TemplateField => new TemplateField(
                $f[0], $f[1], FieldType::Text, false, FieldSource::header($f[0]), aliases: $f[2],
            ),
            $fields,
        ),
        destination: DestinationSpec::exportFile(),
    );
}

it('casa cabeçalhos por nome normalizado, alias e similaridade', function () {
    $template = templateWithFields([
        ['nome', 'Nome', ['cliente', 'nome completo']],
        ['email', 'E-mail', ['e mail', 'correio']],
        ['telefone', 'Telefone', ['fone', 'celular']],
        ['documento', 'CPF', ['cpf', 'documento']],
    ]);

    $headers = ['Nome do Cliente', 'E-MAIL', 'Celular', 'Observações'];

    $suggestions = (new AutoMapper)->suggest($headers, $template);
    $byField = collect($suggestions)->keyBy('fieldKey');

    expect($byField['nome']->header)->toBe('Nome do Cliente')
        ->and($byField['email']->header)->toBe('E-MAIL')
        ->and($byField['email']->matchedBy)->toBe('exact')
        ->and($byField['telefone']->header)->toBe('Celular')   // via alias "celular"
        ->and($byField['documento']->matched())->toBeFalse();  // nada casou -> manual
});

it('não atribui o mesmo cabeçalho a dois campos', function () {
    $template = templateWithFields([
        ['nome', 'Nome', []],
        ['nome2', 'Nome', []],
    ]);

    $suggestions = (new AutoMapper)->suggest(['Nome'], $template);
    $headers = array_filter(array_map(fn ($s) => $s->header, $suggestions));

    expect($headers)->toHaveCount(1); // só um campo recebe o único cabeçalho
});

it('AutoMapColumns lê o cabeçalho do arquivo e sugere o mapa', function () {
    $path = tempnam(sys_get_temp_dir(), 'am').'.csv';
    file_put_contents($path, "Nome Completo;Correio Eletronico\nAna;ana@x.com\n");

    $template = templateWithFields([
        ['nome', 'Nome', ['nome completo']],
        ['email', 'E-mail', ['correio eletronico']],
    ]);

    $suggestions = (new AutoMapColumns(new CsvFileParser))($path, $template);
    $byField = collect($suggestions)->keyBy('fieldKey');

    expect($byField['nome']->header)->toBe('Nome Completo')
        ->and($byField['email']->header)->toBe('Correio Eletronico');

    @unlink($path);
});
