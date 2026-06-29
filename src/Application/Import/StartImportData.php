<?php

declare(strict_types=1);

namespace Src\Application\Import;

use Src\Domain\Import\FileType;

/**
 * Dados de entrada para iniciar uma importação.
 */
final readonly class StartImportData
{
    public function __construct(
        public int $userId,
        public int $programId,
        public string $originalFilename,
        public string $storedPath,
        public FileType $type,
    ) {}
}
