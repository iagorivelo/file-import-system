<?php

declare(strict_types=1);

namespace Src\Domain\Import;

use Src\Domain\Import\Template\Template;

/**
 * Recupera um {@see Template} (agregado de configuração) a partir do
 * armazenamento. A implementação Eloquent reconstitui o value object a partir
 * das colunas JSON do model persistente.
 */
interface TemplateRepository
{
    public function find(int $id): ?Template;
}
