<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Src\Infrastructure\Persistence\Models\User;
use Src\Infrastructure\Persistence\Models\UserSession;

/**
 * @extends Factory<UserSession>
 */
class UserSessionFactory extends Factory
{
    protected $model = UserSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $login = now()->subHours(fake()->numberBetween(1, 48));

        return [
            'user_id' => User::factory(),
            'session_id' => fake()->uuid(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'login_at' => $login,
            'last_activity_at' => (clone $login)->addMinutes(fake()->numberBetween(5, 120)),
            'logout_at' => null,
            'duration_seconds' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(function (array $attributes): array {
            /** @var Carbon $login */
            $login = $attributes['login_at'];
            $logout = (clone $login)->addMinutes(fake()->numberBetween(5, 240));

            return [
                'logout_at' => $logout,
                'last_activity_at' => $logout,
                'duration_seconds' => (int) $login->diffInSeconds($logout),
            ];
        });
    }
}
