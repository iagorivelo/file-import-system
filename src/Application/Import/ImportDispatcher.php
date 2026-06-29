<?php

declare(strict_types=1);

namespace Src\Application\Import;

/**
 * Porta de saída para enfileirar o processamento de uma importação.
 *
 * A camada de aplicação depende desta abstração; a infraestrutura fornece a
 * implementação concreta (fila do Laravel).
 */
interface ImportDispatcher
{
    public function dispatch(int $importId): void;
}
