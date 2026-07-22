<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Src\Domain\User\UserRole;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property UserRole $role
 * @property bool $is_active
 * @property Carbon|null $last_seen_at
 */
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Controla o acesso aos painéis Filament:
     * - painel "admin": somente administradores ativos;
     * - painel "app": qualquer usuário ativo.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return match ($panel->getId()) {
            'admin' => $this->role->isAdmin(),
            default => true,
        };
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    /**
     * Empresas (tenants) às quais o usuário pertence.
     *
     * @return BelongsToMany<Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }

    /**
     * Tenants disponíveis para o usuário no painel (tenancy do Filament).
     *
     * @return Collection<int, Company>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->companies;
    }

    /**
     * Autoriza o usuário a operar dentro de uma empresa específica.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->companies()->whereKey($tenant->getKey())->exists();
    }

    /**
     * Programas que o admin liberou para este usuário acessar no painel /app.
     *
     * @return BelongsToMany<Program, $this>
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(Program::class);
    }

    /**
     * Indica se o usuário pode ver/importar um programa. Administradores têm
     * acesso a todos; usuários comuns, apenas aos programas vinculados a eles.
     */
    public function canAccessProgram(Program $program): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->programs()->whereKey($program->getKey())->exists();
    }

    /**
     * Considera o usuário "online" se houve atividade recente.
     */
    public function isOnline(): bool
    {
        if ($this->last_seen_at === null) {
            return false;
        }

        $threshold = (int) config('file_import.online_threshold_minutes', 5);

        return $this->last_seen_at->gt(now()->subMinutes($threshold));
    }

    /**
     * Tempo total ativo do usuário, em segundos (sessões encerradas + sessão
     * atual em aberto, se houver).
     */
    public function totalActiveSeconds(): int
    {
        $closed = (int) $this->sessions()
            ->whereNotNull('duration_seconds')
            ->sum('duration_seconds');

        $open = $this->sessions()
            ->whereNull('logout_at')
            ->get()
            ->sum(fn (UserSession $session): int => $session->elapsedSeconds());

        return $closed + (int) $open;
    }

    /**
     * @return HasMany<UserSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    /**
     * @return HasMany<FileImport, $this>
     */
    public function fileImports(): HasMany
    {
        return $this->hasMany(FileImport::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
