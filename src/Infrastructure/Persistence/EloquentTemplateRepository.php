<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence;

use Src\Domain\Import\Template\Template;
use Src\Domain\Import\TemplateRepository;
use Src\Infrastructure\Persistence\Models\ImportTemplate;

/**
 * Implementação Eloquent do {@see TemplateRepository}: carrega o model
 * persistente e o reconstitui como value object de domínio.
 */
final class EloquentTemplateRepository implements TemplateRepository
{
    public function find(int $id): ?Template
    {
        return ImportTemplate::query()->find($id)?->toDomain();
    }
}
