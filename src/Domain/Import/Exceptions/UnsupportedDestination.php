<?php

declare(strict_types=1);

namespace Src\Domain\Import\Exceptions;

use RuntimeException;
use Src\Domain\Import\Template\DestinationKind;

final class UnsupportedDestination extends RuntimeException
{
    public static function for(DestinationKind $kind): self
    {
        return new self("Destino não suportado (ainda): [{$kind->value}].");
    }
}
