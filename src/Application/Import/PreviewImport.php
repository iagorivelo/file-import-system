<?php

declare(strict_types=1);

namespace Src\Application\Import;

use Src\Domain\Import\FileParser;
use Src\Domain\Import\Template\RowMapper;
use Src\Domain\Import\Template\Template;

/**
 * Caso de uso: pré-visualizar (dry-run) as primeiras linhas de um arquivo
 * aplicando o template, sem entregar ao destino. Usa o mesmo {@see RowMapper} da
 * importação real, então a prévia reflete exatamente o resultado.
 */
final readonly class PreviewImport
{
    public function __construct(
        private FileParser $parser,
        private RowMapper $mapper = new RowMapper,
    ) {}

    public function __invoke(string $filePath, Template $template, int $limit = 20): PreviewResult
    {
        $parsed = $this->parser->parse($filePath, $template->sourceFormat);

        $headerIndex = [];
        foreach ($parsed->headers as $index => $header) {
            $headerIndex[trim($header)] = $index;
        }

        $rows = [];
        $valid = 0;
        $invalid = 0;
        $lineNumber = $template->sourceFormat->skipRows + ($template->sourceFormat->hasHeader ? 1 : 0);

        foreach ($parsed->rows as $row) {
            $lineNumber++;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            if (count($rows) >= $limit) {
                break;
            }

            $mapped = $this->mapper->map($row, $headerIndex, $template);

            $rows[] = new PreviewRow($lineNumber, $mapped->output, $mapped->errors, $mapped->isValid());

            $mapped->isValid() ? $valid++ : $invalid++;
        }

        return new PreviewResult($parsed->headers, $rows, count($rows), $valid, $invalid);
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
}
