<?php

declare(strict_types=1);

namespace Src\Infrastructure\Queue;

use Illuminate\Contracts\Bus\Dispatcher;
use Src\Application\Import\ImportDispatcher;

/**
 * Implementação da porta {@see ImportDispatcher} usando a fila do Laravel.
 */
final readonly class QueuedImportDispatcher implements ImportDispatcher
{
    public function __construct(
        private Dispatcher $bus,
    ) {}

    public function dispatch(int $importId): void
    {
        $this->bus->dispatch(new ProcessFileImportJob($importId));
    }
}
