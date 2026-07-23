<?php

declare(strict_types=1);

namespace Src\Domain\Import;

use Src\Domain\Import\Template\DestinationSpec;

/**
 * Resolve o {@see DestinationWriter} adequado para a especificação de destino
 * do template (arquivo de exportação, API REST, etc.).
 */
interface DestinationWriterFactory
{
    public function for(DestinationSpec $spec): DestinationWriter;
}
