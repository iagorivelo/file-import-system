<?php

declare(strict_types=1);

use Src\Domain\Import\DestinationWriter;
use Src\Domain\Import\DestinationWriterFactory;
use Src\Domain\Import\FileType;
use Src\Domain\Import\ImportContext;
use Src\Domain\Import\Template\DestinationSpec;
use Src\Domain\Import\Template\FieldSource;
use Src\Domain\Import\Template\FieldType;
use Src\Domain\Import\Template\SourceFormat;
use Src\Domain\Import\Template\Template;
use Src\Domain\Import\Template\TemplateField;
use Src\Domain\Import\Template\TransformKind;
use Src\Domain\Import\Template\TransformRule;
use Src\Domain\Import\Template\ValidationKind;
use Src\Domain\Import\Template\ValidationRule;
use Src\Infrastructure\Import\Destinations\ArrayDestinationWriter;
use Src\Infrastructure\Import\Destinations\DefaultDestinationWriterFactory;
use Src\Infrastructure\Import\Parsers\CsvFileParser;
use Src\Infrastructure\Import\Processors\TemplateProcessor;

/**
 * Cria um CSV temporário e devolve seu caminho (removido no fim do teste).
 *
 * @param  list<string>  $lines
 */
function tmpCsv(array $lines): string
{
    $path = tempnam(sys_get_temp_dir(), 'tpl').'.csv';
    file_put_contents($path, implode("\n", $lines));

    return $path;
}

/** Factory que sempre devolve o mesmo writer, para inspeção nos testes. */
function factoryReturning(DestinationWriter $writer): DestinationWriterFactory
{
    return new class($writer) implements DestinationWriterFactory
    {
        public function __construct(private DestinationWriter $writer) {}

        public function for(DestinationSpec $spec): DestinationWriter
        {
            return $this->writer;
        }
    };
}

it('mapeia colunas por cabeçalho, aplica transforms/validações e deduplica', function () {
    $csv = tmpCsv([
        'Nome do Cliente;E-mail;Telefone;UF',
        ' joão silva ;JOAO@EXEMPLO.COM;(11) 99999-8888;sp',
        'Maria;maria@exemplo.com;11 3333 2222;RJ',
        'joão silva;joao2@exemplo.com;123;SP',   // duplicado por nome
        ';semnome@exemplo.com;999;SP',            // nome obrigatório
        'Ana;email-invalido;555;XX',              // e-mail inválido + UF fora da lista
    ]);

    $template = new Template(
        name: 'Clientes',
        sourceFormat: new SourceFormat(FileType::Csv, delimiter: ';', hasHeader: true),
        fields: [
            new TemplateField('nome', 'Nome', FieldType::Text, true, FieldSource::header('Nome do Cliente'),
                transforms: [new TransformRule(TransformKind::Trim), new TransformRule(TransformKind::TitleCase)]),
            new TemplateField('email', 'E-mail', FieldType::Email, true, FieldSource::header('E-mail'),
                transforms: [new TransformRule(TransformKind::Trim), new TransformRule(TransformKind::LowerCase)],
                validations: [new ValidationRule(ValidationKind::Email)]),
            new TemplateField('telefone', 'Telefone', FieldType::Text, false, FieldSource::header('Telefone'),
                transforms: [new TransformRule(TransformKind::OnlyDigits)]),
            new TemplateField('uf', 'UF', FieldType::Text, true, FieldSource::header('UF'),
                transforms: [new TransformRule(TransformKind::Trim), new TransformRule(TransformKind::UpperCase)],
                validations: [new ValidationRule(ValidationKind::In, ['values' => ['SP', 'RJ', 'MG']])]),
        ],
        destination: DestinationSpec::exportFile(),
        dedupKey: 'nome',
    );

    $writer = new ArrayDestinationWriter;
    $result = (new TemplateProcessor(new CsvFileParser, factoryReturning($writer)))
        ->process(new ImportContext(1, $csv, FileType::Csv, 'clientes.csv', $template));

    expect($result->processedRows)->toBe(2)
        ->and($result->failedRows)->toBe(3)
        ->and($writer->rows)->toHaveCount(2);

    expect($writer->rows[0])->toMatchArray([
        'nome' => 'João Silva',
        'email' => 'joao@exemplo.com',
        'telefone' => '11999998888',
        'uf' => 'SP',
    ]);

    $joined = implode("\n", $result->errors);
    expect($joined)->toContain('duplicado')
        ->and($joined)->toContain('obrigatório')
        ->and($joined)->toContain('e-mail inválido');

    @unlink($csv);
});

it('mapeia por índice e valor constante, sem cabeçalho, com cast de tipo', function () {
    $csv = tmpCsv(['abc;10', 'def;20']);

    $template = new Template(
        name: 'Lote',
        sourceFormat: new SourceFormat(FileType::Csv, delimiter: ';', hasHeader: false),
        fields: [
            new TemplateField('codigo', 'Código', FieldType::Text, true, FieldSource::index(0),
                transforms: [new TransformRule(TransformKind::UpperCase)]),
            new TemplateField('qtd', 'Quantidade', FieldType::Integer, true, FieldSource::index(1),
                validations: [new ValidationRule(ValidationKind::Numeric)]),
            new TemplateField('origem', 'Origem', FieldType::Text, true, FieldSource::constant('lote-1')),
        ],
        destination: DestinationSpec::exportFile(),
    );

    $writer = new ArrayDestinationWriter;
    $result = (new TemplateProcessor(new CsvFileParser, factoryReturning($writer)))
        ->process(new ImportContext(2, $csv, FileType::Csv, 'lote.csv', $template));

    expect($result->processedRows)->toBe(2)
        ->and($result->failedRows)->toBe(0)
        ->and($writer->rows[0])->toMatchArray(['codigo' => 'ABC', 'qtd' => 10, 'origem' => 'lote-1'])
        ->and($writer->rows[0]['qtd'])->toBeInt();

    @unlink($csv);
});

it('devolve erro amigável quando não há template configurado', function () {
    $result = (new TemplateProcessor(new CsvFileParser, factoryReturning(new ArrayDestinationWriter)))
        ->process(new ImportContext(3, 'inexistente', FileType::Csv, 'x.csv', null));

    expect($result->processedRows)->toBe(0)
        ->and($result->errors)->not->toBeEmpty();
});

it('grava um CSV normalizado via ExportFileWriter', function () {
    $csv = tmpCsv(['nome;idade', 'Ana;30', 'Bruno;25']);
    $exportDir = sys_get_temp_dir().'/tpl_exports_'.uniqid();

    $template = new Template(
        name: 'Export',
        sourceFormat: new SourceFormat(FileType::Csv, delimiter: ';', hasHeader: true),
        fields: [
            new TemplateField('nome', 'Nome', FieldType::Text, true, FieldSource::header('nome')),
            new TemplateField('idade', 'Idade', FieldType::Integer, true, FieldSource::header('idade')),
        ],
        destination: DestinationSpec::exportFile(),
    );

    $result = (new TemplateProcessor(new CsvFileParser, new DefaultDestinationWriterFactory($exportDir)))
        ->process(new ImportContext(4, $csv, FileType::Csv, 'export.csv', $template));

    expect($result->processedRows)->toBe(2);

    $generated = glob($exportDir.'/export_*.csv') ?: [];
    expect($generated)->toHaveCount(1);

    $lines = array_values(array_filter(array_map('trim', file($generated[0]))));
    expect($lines[0])->toBe('nome,idade')
        ->and($lines[1])->toBe('Ana,30')
        ->and($lines[2])->toBe('Bruno,25');

    @unlink($generated[0]);
    @unlink($csv);
    @rmdir($exportDir);
});
