<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Src\Infrastructure\Persistence\Models\User;

/**
 * Abre um registro de sessão e marca o usuário como ativo no login.
 */
class RecordSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $user->forceFill(['last_seen_at' => now()])->saveQuietly();

        $user->sessions()->create([
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
            'login_at' => now(),
            'last_activity_at' => now(),
        ]);
    }
}
