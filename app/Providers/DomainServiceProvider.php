<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Src\Application\Import\FileStorage;
use Src\Application\Import\ImportDispatcher;
use Src\Domain\Import\DestinationWriterFactory;
use Src\Domain\Import\FileParser;
use Src\Domain\Import\ProcessorRegistry;
use Src\Domain\Import\TemplateRepository;
use Src\Infrastructure\Import\Destinations\DefaultDestinationWriterFactory;
use Src\Infrastructure\Import\DirectoryProcessorRegistry;
use Src\Infrastructure\Import\Parsers\CsvFileParser;
use Src\Infrastructure\Persistence\EloquentTemplateRepository;
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

        // Motor configurável (template): parser de origem e fábrica de destinos.
        $this->app->bind(FileParser::class, CsvFileParser::class);

        $this->app->bind(DestinationWriterFactory::class, function (): DefaultDestinationWriterFactory {
            return new DefaultDestinationWriterFactory(
                (string) config('file_import.exports.directory'),
            );
        });

        $this->app->bind(TemplateRepository::class, EloquentTemplateRepository::class);
    }
}
