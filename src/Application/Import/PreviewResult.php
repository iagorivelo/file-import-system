<?php

declare(strict_types=1);

namespace Src\Application\Import;

/**
 * Resultado de um dry-run ({@see PreviewImport}): os cabeçalhos detectados e as
 * primeiras linhas já mapeadas/validadas, com contagem de válidas/ inválidas —
 * sem entregar nada ao destino. Consumido pela UI para o usuário conferir o
 * mapeamento antes de importar de verdade.
 */
final readonly class PreviewResult
{
    /**
     * @param  list<string>  $headers
     * @param  list<PreviewRow>  $rows
     */
    public function __construct(
        public array $headers,
        public array $rows,
        public int $previewed,
        public int $validCount,
        public int $invalidCount,
    ) {}
}
