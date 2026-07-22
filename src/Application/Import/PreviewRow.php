<?php

declare(strict_types=1);

namespace Src\Application\Import;

/**
 * Uma linha do preview: número da linha no arquivo, o registro mapeado, os
 * erros (se houver) e se é válida.
 */
final readonly class PreviewRow
{
    /**
     * @param  array<string, string|int|float|bool|null>  $output
     * @param  list<string>  $errors
     */
    public function __construct(
        public int $lineNumber,
        public array $output,
        public array $errors,
        public bool $valid,
    ) {}
}
