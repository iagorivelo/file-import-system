<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Models;

use Database\Factories\ProgramFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Src\Domain\Import\FileProcessor;

/**
 * Um "programa" (box) exibido no painel. Cada programa aponta para a classe
 * processadora ({@see FileProcessor}) que será executada ao importar um arquivo.
 *
 * Quando `template_id` está preenchido, o programa roda em "modo configurável":
 * `processor_class` é o TemplateProcessor e a lógica vem do {@see ImportTemplate}
 * vinculado. Caso contrário, roda em "modo código" (classe dedicada).
 *
 * @property int $id
 * @property string $name
 * @property string $color
 * @property class-string<FileProcessor> $processor_class
 * @property int|null $template_id
 * @property bool $is_active
 */
class Program extends Model
{
    /** @use HasFactory<ProgramFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'color',
        'processor_class',
        'template_id',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'company_id' => 'integer',
            'template_id' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Indica se o programa roda pelo motor configurável (template).
     */
    public function usesTemplate(): bool
    {
        return $this->template_id !== null;
    }

    /**
     * @return BelongsTo<ImportTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ImportTemplate::class, 'template_id');
    }

    /**
     * Inicial exibida na box (primeira letra do nome).
     */
    public function initial(): string
    {
        return mb_strtoupper(mb_substr(trim($this->name), 0, 1));
    }

    /**
     * @return HasMany<FileImport, $this>
     */
    public function fileImports(): HasMany
    {
        return $this->hasMany(FileImport::class);
    }

    /**
     * Usuários autorizados a ver/importar este programa no painel /app.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    protected static function newFactory(): ProgramFactory
    {
        return ProgramFactory::new();
    }
}
