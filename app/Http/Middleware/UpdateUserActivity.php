<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Src\Infrastructure\Persistence\Models\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * Atualiza o "visto por último" do usuário e a atividade da sessão aberta,
 * de forma econômica (apenas quando o último registro está defasado).
 */
class UpdateUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if (! $user instanceof User) {
            return $response;
        }

        $isStale = $user->last_seen_at === null
            || $user->last_seen_at->lt(now()->subMinutes(2));

        if ($isStale) {
            $user->forceFill(['last_seen_at' => now()])->saveQuietly();

            $user->sessions()
                ->whereNull('logout_at')
                ->latest('login_at')
                ->limit(1)
                ->update(['last_activity_at' => now()]);
        }

        return $response;
    }
}
