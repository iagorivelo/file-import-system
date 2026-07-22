<?php

declare(strict_types=1);

namespace Src\Infrastructure\Persistence\Models;

use Database\Factories\FileImportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Src\Domain\Import\FileType;
use Src\Domain\Import\ImportResult;
use Src\Domain\Import\ImportStatus;

/**
 * Registro (histórico) de uma importação de arquivo executada por um usuário
 * sobre um programa.
 *
 * @property int $id
 * @property int $user_id
 * @property int $program_id
 * @property string $original_filename
 * @property string $stored_path
 * @property FileType $file_type
 * @property ImportStatus $status
 * @property int $processed_rows
 * @property int $failed_rows
 * @property string|null $error_message
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 */
class FileImport extends Model
{
    /** @use HasFactory<FileImportFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'program_id',
        'original_filename',
        'stored_path',
        'file_type',
        'status',
        'processed_rows',
        'failed_rows',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $attributes = [
        'status' => ImportStatus::Pending->value,
        'processed_rows' => 0,
        'failed_rows' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_type' => FileType::class,
            'status' => ImportStatus::class,
            'processed_rows' => 'integer',
            'failed_rows' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => ImportStatus::Processing,
            'started_at' => now(),
        ]);
    }

    public function markCompleted(ImportResult $result): void
    {
        $this->update([
            'status' => ImportStatus::Completed,
            'processed_rows' => $result->processedRows,
            'failed_rows' => $result->failedRows,
            'error_message' => $result->errors !== [] ? implode("\n", $result->errors) : null,
            'finished_at' => now(),
        ]);
    }

    public function markFailed(string $message): void
    {
        $this->update([
            'status' => ImportStatus::Failed,
            'error_message' => $message,
            'finished_at' => now(),
        ]);
    }

    /**
     * Indica se a importação falhou por completo ou teve linhas com erro.
     */
    public function hasErrors(): bool
    {
        return $this->status === ImportStatus::Failed || $this->failed_rows > 0;
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    protected static function newFactory(): FileImportFactory
    {
        return FileImportFactory::new();
    }
}
