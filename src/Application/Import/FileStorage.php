<?php

declare(strict_types=1);

namespace Src\Application\Import;

/**
 * Porta de saída para acessar o arquivo importado no armazenamento.
 */
interface FileStorage
{
    /**
     * Caminho absoluto do arquivo a partir do caminho relativo armazenado.
     */
    public function absolutePath(string $relativePath): string;

    public function delete(string $relativePath): void;
}
