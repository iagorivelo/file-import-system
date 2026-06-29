<?php

declare(strict_types=1);

namespace Src\Infrastructure\Storage;

use Illuminate\Contracts\Filesystem\Filesystem;
use Src\Application\Import\FileStorage;

/**
 * Implementação da porta {@see FileStorage} sobre um disco do Laravel.
 */
final readonly class LocalFileStorage implements FileStorage
{
    public function __construct(
        private Filesystem $disk,
    ) {}

    public function absolutePath(string $relativePath): string
    {
        return $this->disk->path($relativePath);
    }

    public function delete(string $relativePath): void
    {
        $this->disk->delete($relativePath);
    }
}
