<?php

declare(strict_types=1);

namespace Src\Application\Import;

use Src\Domain\Import\FileParser;
use Src\Domain\Import\Template\AutoMapper;
use Src\Domain\Import\Template\ColumnSuggestion;
use Src\Domain\Import\Template\Template;

/**
 * Caso de uso: dado um arquivo de amostra e um template, sugerir o mapeamento
 * das colunas do arquivo para os campos do template (auto-mapeamento).
 *
 * Lê apenas o cabeçalho via {@see FileParser} e delega o casamento ao
 * {@see AutoMapper}. Usado na UI para pré-preencher o mapa que a empresa confirma.
 */
final readonly class AutoMapColumns
{
    public function __construct(
        private FileParser $parser,
        private AutoMapper $autoMapper = new AutoMapper,
    ) {}

    /**
     * @return list<ColumnSuggestion>
     */
    public function __invoke(string $filePath, Template $template): array
    {
        $parsed = $this->parser->parse($filePath, $template->sourceFormat);

        return $this->autoMapper->suggest($parsed->headers, $template);
    }
}
