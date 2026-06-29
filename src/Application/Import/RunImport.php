<?php

declare(strict_types=1);

namespace Src\Application\Import;

use Src\Domain\Import\ImportContext;
use Src\Domain\Import\ProcessorRegistry;
use Src\Infrastructure\Persistence\Models\FileImport;
use Throwable;

/**
 * Caso de uso: executar o processamento de uma importação.
 *
 * Resolve a classe processadora vinculada ao programa, executa a leitura/
 * tratamento do arquivo e atualiza o histórico com o resultado.
 */
final readonly class RunImport
{
    public function __construct(
        private ProcessorRegistry $registry,
        private FileStorage $storage,
    ) {}

    public function __invoke(int $importId): void
    {
        /** @var FileImport $import */
        $import = FileImport::query()->findOrFail($importId);

        $import->markProcessing();

        try {
            $processor = $this->registry->make($import->program->processor_class);

            $result = $processor->process(new ImportContext(
                importId: $import->id,
                filePath: $this->storage->absolutePath($import->stored_path),
                type: $import->file_type,
                originalName: $import->original_filename,
            ));

            $import->markCompleted($result);
        } catch (Throwable $exception) {
            $import->markFailed($exception->getMessage());

            throw $exception;
        }
    }
}
