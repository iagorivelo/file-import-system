<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Resultado do mapeamento de uma única linha: o registro de saída (campo→valor)
 * e a lista de erros por campo (vazia quando a linha é válida).
 */
final readonly class MappedRow
{
    /**
     * @param  array<string, string|int|float|bool|null>  $output
     * @param  list<string>  $errors
     */
    public function __construct(
        public array $output,
        public array $errors,
    ) {}

    public function isValid(): bool
    {
        return $this->errors === [];
    }
}
