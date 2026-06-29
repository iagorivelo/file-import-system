<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Models;

use Database\Factories\UserSessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Registro de uma sessão de uso (login -> logout) para medir tempo ativo
 * e status online dos usuários.
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $session_id
 * @property Carbon $login_at
 * @property Carbon|null $last_activity_at
 * @property Carbon|null $logout_at
 * @property int|null $duration_seconds
 */
class UserSession extends Model
{
    /** @use HasFactory<UserSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'login_at',
        'last_activity_at',
        'logout_at',
        'duration_seconds',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'logout_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    public function isOpen(): bool
    {
        return $this->logout_at === null;
    }

    /**
     * Duração da sessão em segundos (encerrada ou em andamento).
     */
    public function elapsedSeconds(): int
    {
        $end = $this->logout_at ?? now();

        return (int) $this->login_at->diffInSeconds($end);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): UserSessionFactory
    {
        return UserSessionFactory::new();
    }
}
