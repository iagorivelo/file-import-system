<?php

declare(strict_types=1);

namespace Src\Infrastructure\Import\Processors;

use Src\Domain\Import\DestinationWriterFactory;
use Src\Domain\Import\FileParser;
use Src\Domain\Import\FileProcessor;
use Src\Domain\Import\FileType;
use Src\Domain\Import\ImportContext;
use Src\Domain\Import\ImportResult;
use Src\Domain\Import\Template\RowMapper;
use Src\Domain\Import\Template\Template;

/**
 * Processador genérico orientado a configuração.
 *
 * Em vez de codificar parsing/validação/destino, executa um {@see Template}
 * (dado): lê o arquivo pelo {@see FileParser}, mapeia cada linha coluna→campo,
 * aplica transformações e validações, deduplica e entrega ao destino resolvido
 * pelo {@see DestinationWriterFactory}. É o "modo configurável" — um Program que
 * aponta para um template roda por aqui, sem classe nova por importação.
 */
final class TemplateProcessor implements FileProcessor
{
    /** Máximo de mensagens de erro detalhadas (as excedentes viram resumo). */
    private const MAX_ERROR_MESSAGES = 50;

    public function __construct(
        private readonly FileParser $parser,
        private readonly DestinationWriterFactory $destinations,
        private readonly RowMapper $mapper = new RowMapper,
    ) {}

    public static function label(): string
    {
        return 'Motor configurável (template)';
    }

    /**
     * @return list<FileType>
     */
    public static function acceptedFileTypes(): array
    {
        // O template restringe o tipo real via SourceFormat; aqui declaramos os
        // formatos que o motor sabe ler hoje (Excel entra com o parser openspout).
        return [FileType::Txt, FileType::Csv];
    }

    public function process(ImportContext $context): ImportResult
    {
        $template = $context->template;

        if ($template === null) {
            return new ImportResult(
                errors: ['Nenhum template de importação configurado para este programa.'],
            );
        }

        $parsed = $this->parser->parse($context->filePath, $template->sourceFormat);

        $headerIndex = [];
        foreach ($parsed->headers as $index => $header) {
            $headerIndex[trim($header)] = $index;
        }

        $writer = $this->destinations->for($template->destination);
        $writer->begin($template->destination);

        $processed = 0;
        $failed = 0;
        $errors = [];
        $seen = [];
        $lineNumber = $template->sourceFormat->skipRows + ($template->sourceFormat->hasHeader ? 1 : 0);

        try {
            foreach ($parsed->rows as $row) {
                $lineNumber++;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $mapped = $this->mapper->map($row, $headerIndex, $template);

                if (! $mapped->isValid()) {
                    $failed++;
                    $this->pushError($errors, $lineNumber, implode('; ', $mapped->errors));

                    continue;
                }

                $duplicateKey = $this->duplicateKey($mapped->output, $template, $seen);

                if ($duplicateKey !== null) {
                    $failed++;
                    $this->pushError($errors, $lineNumber, "registro duplicado ({$template->dedupKey} = \"{$duplicateKey}\").");

                    continue;
                }

                $writer->write($mapped->output);
                $processed++;
            }
        } finally {
            $writer->finish();
        }

        $hidden = $failed - count($errors);
        if ($hidden > 0) {
            $errors[] = "… e mais {$hidden} linha(s) com erro.";
        }

        return new ImportResult(
            processedRows: $processed,
            failedRows: $failed,
            errors: $errors,
        );
    }

    /**
     * Devolve o valor da chave de dedup se a linha for duplicada; caso contrário
     * null (registrando a chave como vista).
     *
     * @param  array<string, string|int|float|bool|null>  $output
     * @param  array<string, true>  $seen
     */
    private function duplicateKey(array $output, Template $template, array &$seen): ?string
    {
        if ($template->dedupKey === null) {
            return null;
        }

        $key = (string) ($output[$template->dedupKey] ?? '');

        if ($key === '') {
            return null;
        }

        $normalized = mb_strtolower($key);

        if (isset($seen[$normalized])) {
            return $key;
        }

        $seen[$normalized] = true;

        return null;
    }

    /**
     * @param  list<string>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $cell) {
            if (trim($cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $errors
     */
    private function pushError(array &$errors, int $lineNumber, string $message): void
    {
        if (count($errors) < self::MAX_ERROR_MESSAGES) {
            $errors[] = "Linha {$lineNumber}: {$message}";
        }
    }
}
