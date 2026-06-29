<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Src\Infrastructure\Persistence\Models\User;

/**
 * Encerra a sessão aberta do usuário e calcula sua duração no logout.
 */
class RecordLogout
{
    public function handle(Logout $event): void
    {
        $user = $event->user;

        if (! $user instanceof User) {
            return;
        }

        $session = $user->sessions()
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first();

        if ($session === null) {
            return;
        }

        $now = now();

        $session->update([
            'logout_at' => $now,
            'last_activity_at' => $now,
            'duration_seconds' => (int) $session->login_at->diffInSeconds($now),
        ]);
    }
}
