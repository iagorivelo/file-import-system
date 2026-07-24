<?php

declare(strict_types=1);

namespace Src\Application\Import;

use Src\Domain\Import\ImportStatus;
use Src\Infrastructure\Persistence\Models\FileImport;

/**
 * Caso de uso: registrar uma nova importação e enfileirar seu processamento.
 */
final readonly class StartImport
{
    public function __construct(
        private ImportDispatcher $dispatcher,
    ) {}

    public function __invoke(StartImportData $data): FileImport
    {
        $import = FileImport::query()->create([
            'company_id' => $data->companyId,
            'user_id' => $data->userId,
            'program_id' => $data->programId,
            'original_filename' => $data->originalFilename,
            'stored_path' => $data->storedPath,
            'file_type' => $data->type,
            'status' => ImportStatus::Pending,
        ]);

        $this->dispatcher->dispatch($import->id);

        return $import;
    }
}
