<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Infrastructure\Import\Processors\TesteProcessor;
use Src\Infrastructure\Persistence\Models\Program;

/**
 * @extends Factory<Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $palette = ['#4b6043', '#74803d', '#3d5a80', '#8a5a44', '#5f4b8b'];

        return [
            'name' => 'Programa '.fake()->unique()->company(),
            'color' => fake()->randomElement($palette),
            'processor_class' => TesteProcessor::class,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
