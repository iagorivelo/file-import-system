<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Domain\Import\FileType;
use Src\Domain\Import\ImportStatus;
use Src\Infrastructure\Persistence\Models\FileImport;
use Src\Infrastructure\Persistence\Models\Program;
use Src\Infrastructure\Persistence\Models\User;

/**
 * @extends Factory<FileImport>
 */
class FileImportFactory extends Factory
{
    protected $model = FileImport::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'program_id' => Program::factory(),
            'original_filename' => fake()->word().'.csv',
            'stored_path' => 'imports/'.fake()->uuid().'.csv',
            'file_type' => FileType::Csv,
            'status' => ImportStatus::Pending,
            'processed_rows' => 0,
            'failed_rows' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ImportStatus::Completed,
            'processed_rows' => fake()->numberBetween(10, 1000),
            'failed_rows' => fake()->numberBetween(0, 10),
            'started_at' => now()->subMinutes(2),
            'finished_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ImportStatus::Failed,
            'error_message' => 'Falha ao processar o arquivo.',
            'started_at' => now()->subMinutes(2),
            'finished_at' => now(),
        ]);
    }
}
