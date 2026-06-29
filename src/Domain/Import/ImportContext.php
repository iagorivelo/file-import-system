<?php

declare(strict_types=1);

namespace Src\Domain\Import;

/**
 * Dados de entrada entregues a um {@see FileProcessor} no momento da execução.
 *
 * Carrega tudo o que o processador precisa para ler e tratar o arquivo, sem
 * acoplar o domínio a detalhes de framework ou persistência.
 */
final readonly class ImportContext
{
    public function __construct(
        public int $importId,
        public string $filePath,
        public FileType $type,
        public string $originalName,
    ) {}
}
