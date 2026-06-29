<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Src\Application\Import\FileStorage;
use Src\Application\Import\ImportDispatcher;
use Src\Domain\Import\ProcessorRegistry;
use Src\Infrastructure\Import\DirectoryProcessorRegistry;
use Src\Infrastructure\Queue\QueuedImportDispatcher;
use Src\Infrastructure\Storage\LocalFileStorage;

/**
 * Liga as abstrações do domínio/aplicação às implementações da infraestrutura.
 */
class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProcessorRegistry::class, function ($app): DirectoryProcessorRegistry {
            return new DirectoryProcessorRegistry(
                container: $app,
                directory: (string) config('file_import.processors.directory'),
                namespace: (string) config('file_import.processors.namespace'),
            );
        });

        $this->app->bind(ImportDispatcher::class, QueuedImportDispatcher::class);

        $this->app->bind(FileStorage::class, function (): LocalFileStorage {
            return new LocalFileStorage(
                Storage::disk((string) config('file_import.storage.disk')),
            );
        });
    }
}
