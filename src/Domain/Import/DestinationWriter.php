<?php

declare(strict_types=1);

namespace Src\Domain\Import;

use Src\Domain\Import\Template\DestinationSpec;

/**
 * Porta de entrega das linhas mapeadas/validadas a um destino externo.
 *
 * Ciclo de vida: {@see self::begin()} uma vez, {@see self::write()} por linha
 * válida e {@see self::finish()} ao final (flush/fechamento/envio em lote).
 */
interface DestinationWriter
{
    public function begin(DestinationSpec $spec): void;

    /**
     * @param  array<string, string|int|float|bool|null>  $row
     */
    public function write(array $row): void;

    public function finish(): void;
}
