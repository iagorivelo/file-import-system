<?php

declare(strict_types=1);

namespace Src\Infrastructure\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Src\Application\Import\RunImport;

/**
 * Job que executa o processamento de uma importação de forma assíncrona,
 * delegando a regra para o caso de uso {@see RunImport}.
 */
class ProcessFileImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 600;

    public int $backoff = 10;

    public function __construct(
        public readonly int $importId,
    ) {}

    public function handle(RunImport $runImport): void
    {
        $runImport($this->importId);
    }
}
