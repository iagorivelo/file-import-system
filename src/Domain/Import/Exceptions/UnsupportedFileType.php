<?php

declare(strict_types=1);

namespace Src\Domain\Import\Exceptions;

use RuntimeException;

final class UnsupportedFileType extends RuntimeException
{
    public static function for(string $type): self
    {
        return new self("Tipo de arquivo não suportado: [{$type}]. Aceitos: .txt e .csv.");
    }
}
