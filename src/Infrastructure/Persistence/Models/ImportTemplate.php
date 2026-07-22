<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Models;

use Database\Factories\ImportTemplateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Src\Domain\Import\Template\Template;

/**
 * Persistência do "layout" de importação (config como dado). As colunas JSON
 * espelham os value objects do domínio; {@see self::toDomain()} reconstitui o
 * agregado {@see Template} consumido pelo TemplateProcessor.
 *
 * `company_id` nulo indica um template de nicho global (da plataforma),
 * reaproveitável por empresas via clonagem; preenchido, é um template da empresa.
 *
 * @property int $id
 * @property int|null $company_id
 * @property string $name
 * @property string|null $niche
 * @property array<string, mixed> $source_format
 * @property list<array<string, mixed>> $fields
 * @property array<string, mixed> $destination
 * @property string|null $dedup_key
 */
class ImportTemplate extends Model
{
    /** @use HasFactory<ImportTemplateFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'niche',
        'source_format',
        'fields',
        'destination',
        'dedup_key',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_format' => 'array',
            'fields' => 'array',
            'destination' => 'array',
        ];
    }

    /**
     * Reconstitui o value object de domínio a partir das colunas persistidas.
     */
    public function toDomain(): Template
    {
        return Template::fromArray([
            'id' => $this->id,
            'name' => $this->name,
            'niche' => $this->niche,
            'dedupKey' => $this->dedup_key,
            'sourceFormat' => $this->source_format,
            'fields' => $this->fields,
            'destination' => $this->destination,
        ]);
    }

    /**
     * Um template de nicho global (sem empresa dona) é reaproveitável por
     * qualquer empresa via clonagem.
     */
    public function isGlobal(): bool
    {
        return $this->company_id === null;
    }

    /**
     * Templates globais de nicho (da plataforma), disponíveis para adoção.
     *
     * @param  Builder<ImportTemplate>  $query
     */
    public function scopeGlobalNiche(Builder $query): void
    {
        $query->whereNull('company_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return HasMany<Program, $this>
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class, 'template_id');
    }

    protected static function newFactory(): ImportTemplateFactory
    {
        return ImportTemplateFactory::new();
    }
}
