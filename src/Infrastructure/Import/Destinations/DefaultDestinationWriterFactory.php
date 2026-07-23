<?php

declare(strict_types=1);

namespace Src\Infrastructure\Import\Destinations;

use Src\Domain\Import\DestinationWriter;
use Src\Domain\Import\DestinationWriterFactory;
use Src\Domain\Import\Template\DestinationKind;
use Src\Domain\Import\Template\DestinationSpec;

/**
 * Resolve o writer conforme o tipo de destino do template: arquivo de
 * exportação (CSV normalizado) ou API REST (entrega HTTP em lote).
 */
final class DefaultDestinationWriterFactory implements DestinationWriterFactory
{
    public function __construct(private readonly string $exportDir) {}

    public function for(DestinationSpec $spec): DestinationWriter
    {
        return match ($spec->kind) {
            DestinationKind::ExportFile => new ExportFileWriter($this->exportDir),
            DestinationKind::RestApi => new RestApiWriter,
        };
    }
}
