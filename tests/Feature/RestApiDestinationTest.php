<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Src\Domain\Import\FileType;
use Src\Domain\Import\ImportContext;
use Src\Domain\Import\Template\DestinationSpec;
use Src\Domain\Import\Template\FieldSource;
use Src\Domain\Import\Template\FieldType;
use Src\Domain\Import\Template\SourceFormat;
use Src\Domain\Import\Template\Template;
use Src\Domain\Import\Template\TemplateField;
use Src\Infrastructure\Import\Destinations\DefaultDestinationWriterFactory;
use Src\Infrastructure\Import\Parsers\CsvFileParser;
use Src\Infrastructure\Import\Processors\TemplateProcessor;

it('entrega as linhas mapeadas ao destino API REST em lote', function () {
    Http::fake(['https://erp.exemplo.com/*' => Http::response(['ok' => true], 200)]);

    $path = tempnam(sys_get_temp_dir(), 'rest').'.csv';
    file_put_contents($path, implode("\n", [
        'nome;email',
        'Ana;ana@x.com',
        'Bruno;bruno@x.com',
    ]));

    $template = new Template(
        name: 'Clientes → ERP',
        sourceFormat: new SourceFormat(FileType::Csv, delimiter: ';', hasHeader: true),
        fields: [
            new TemplateField('nome', 'Nome', FieldType::Text, true, FieldSource::header('nome')),
            new TemplateField('email', 'E-mail', FieldType::Text, true, FieldSource::header('email')),
        ],
        destination: DestinationSpec::restApi(
            endpoint: 'https://erp.exemplo.com/clientes',
            headers: ['Authorization' => 'Bearer tok'],
            wrapKey: 'registros',
        ),
    );

    $processor = new TemplateProcessor(
        new CsvFileParser,
        new DefaultDestinationWriterFactory(sys_get_temp_dir()),
    );
    $result = $processor->process(new ImportContext(1, $path, FileType::Csv, 'clientes.csv', $template));

    expect($result->processedRows)->toBe(2);

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://erp.exemplo.com/clientes'
            && $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer tok')
            && $request['registros'][0]['nome'] === 'Ana'
            && $request['registros'][1]['email'] === 'bruno@x.com';
    });

    @unlink($path);
});

it('falha a importação se o destino API REST retornar erro', function () {
    Http::fake(['*' => Http::response('erro', 500)]);

    $path = tempnam(sys_get_temp_dir(), 'rest').'.csv';
    file_put_contents($path, "nome\nAna\n");

    $template = new Template(
        name: 'X',
        sourceFormat: new SourceFormat(FileType::Csv, delimiter: ';', hasHeader: true),
        fields: [new TemplateField('nome', 'Nome', FieldType::Text, true, FieldSource::header('nome'))],
        destination: DestinationSpec::restApi(endpoint: 'https://erp.exemplo.com/x'),
    );

    $processor = new TemplateProcessor(
        new CsvFileParser,
        new DefaultDestinationWriterFactory(sys_get_temp_dir()),
    );

    expect(fn () => $processor->process(new ImportContext(1, $path, FileType::Csv, 'x.csv', $template)))
        ->toThrow(RuntimeException::class);

    @unlink($path);
});
