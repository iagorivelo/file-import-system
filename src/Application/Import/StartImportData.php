<?php

declare(strict_types=1);

namespace Src\Application\Import;

use Src\Domain\Import\FileType;

/**
 * Dados de entrada para iniciar uma importação.
 *
 * `companyId` é a empresa (tenant) dona da importação; nulo apenas em contextos
 * sem tenancy (ex.: testes de pipeline puro).
 */
final readonly class StartImportData
{
    public function __construct(
        public int $userId,
        public int $programId,
        public string $originalFilename,
        public string $storedPath,
        public FileType $type,
        public ?int $companyId = null,
    ) {}
}
