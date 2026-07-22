<?php

declare(strict_types=1);

namespace Src\Application\Import;

use Src\Domain\Import\ImportContext;
use Src\Domain\Import\ProcessorRegistry;
use Src\Domain\Import\Template\Template;
use Src\Domain\Import\TemplateRepository;
use Src\Infrastructure\Persistence\Models\FileImport;
use Src\Infrastructure\Persistence\Models\Program;
use Throwable;

/**
 * Caso de uso: executar o processamento de uma importação.
 *
 * Resolve a classe processadora vinculada ao programa, executa a leitura/
 * tratamento do arquivo e atualiza o histórico com o resultado. Se o programa
 * roda em "modo configurável" (tem template), carrega o {@see Template} e o
 * injeta no contexto para o TemplateProcessor executá-lo.
 */
final readonly class RunImport
{
    public function __construct(
        private ProcessorRegistry $registry,
        private FileStorage $storage,
        private TemplateRepository $templates,
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
                template: $this->resolveTemplate($import->program),
            ));

            $import->markCompleted($result);
        } catch (Throwable $exception) {
            $import->markFailed($exception->getMessage());

            throw $exception;
        }
    }

    private function resolveTemplate(Program $program): ?Template
    {
        return $program->template_id !== null
            ? $this->templates->find($program->template_id)
            : null;
    }
}
