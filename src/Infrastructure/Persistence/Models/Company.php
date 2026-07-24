<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Models;

use Database\Factories\CompanyFactory;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Empresa (tenant) da plataforma. Isola dados por cliente em banco único, via
 * `company_id`. O `niche` orienta quais templates de nicho ficam disponíveis.
 *
 * Implementa os contratos de tenant do Filament para rotular a empresa no
 * seletor de tenants do painel.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $niche
 * @property bool $is_active
 */
class Company extends Model implements HasCurrentTenantLabel, HasName
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'niche',
        'is_active',
    ];

    protected static function booted(): void
    {
        // Garante um slug (URL amigável do tenant) sempre normalizado e único.
        // Vazio → deriva do nome; preenchido → normaliza o valor informado.
        static::saving(function (Company $company): void {
            $source = filled($company->slug) ? $company->slug : (string) $company->name;
            $company->slug = $company->uniqueSlug($source);
        });
    }

    /**
     * Faz o Filament (e o route model binding) resolverem a empresa pelo slug,
     * gerando rotas como /app/{slug} no lugar de /app/{id}.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Gera um slug único a partir de um texto, ignorando a própria empresa e
     * considerando registros soft-deleted (o índice único também os cobre).
     */
    protected function uniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'empresa';
        $slug = $base;
        $i = 2;

        while (
            static::query()
                ->withTrashed()
                ->where('slug', $slug)
                ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()))
                ->exists()
        ) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Nome exibido para o tenant (seletor de empresas do Filament).
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getCurrentTenantLabel(): string
    {
        return 'Empresa ativa';
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return HasMany<Program, $this>
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * @return HasMany<ImportTemplate, $this>
     */
    public function importTemplates(): HasMany
    {
        return $this->hasMany(ImportTemplate::class);
    }

    /**
     * @return HasMany<FileImport, $this>
     */
    public function fileImports(): HasMany
    {
        return $this->hasMany(FileImport::class);
    }

    protected static function newFactory(): CompanyFactory
    {
        return CompanyFactory::new();
    }
}
