<?php

declare(strict_types=1);

namespace Src\Infrastructure\Import\Destinations;

use Src\Domain\Import\DestinationWriter;
use Src\Domain\Import\Template\DestinationSpec;

/**
 * Destino em memória: acumula as linhas escritas. Usado em testes e em
 * pré-visualizações (dry-run) do motor.
 */
final class ArrayDestinationWriter implements DestinationWriter
{
    /** @var list<array<string, string|int|float|bool|null>> */
    public array $rows = [];

    public function begin(DestinationSpec $spec): void
    {
        $this->rows = [];
    }

    public function write(array $row): void
    {
        $this->rows[] = $row;
    }

    public function finish(): void
    {
        // Nada a fazer: os dados já estão em memória.
    }
}
