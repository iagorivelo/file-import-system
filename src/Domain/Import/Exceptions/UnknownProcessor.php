<?php

declare(strict_types=1);

namespace Src\Domain\Import\Exceptions;

use RuntimeException;

final class UnknownProcessor extends RuntimeException
{
    public static function for(string $processor): self
    {
        return new self("Processador desconhecido ou inválido: [{$processor}].");
    }
}
