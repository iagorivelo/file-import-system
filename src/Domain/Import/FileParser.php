<?php

declare(strict_types=1);

namespace Src\Domain\Import;

use Src\Domain\Import\Template\SourceFormat;

/**
 * Contrato de leitura de um arquivo de origem em cabeçalhos + linhas.
 *
 * Implementações concretas (CSV/TXT via SplFileObject, Excel via openspout)
 * vivem na infraestrutura; o domínio só conhece esta porta.
 */
interface FileParser
{
    public function parse(string $filePath, SourceFormat $format): ParsedFile;
}
