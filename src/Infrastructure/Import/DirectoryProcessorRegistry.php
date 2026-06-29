<?php

declare(strict_types=1);

namespace Src\Infrastructure\Import;

use Illuminate\Contracts\Container\Container;
use ReflectionClass;
use Src\Domain\Import\Exceptions\UnknownProcessor;
use Src\Domain\Import\FileProcessor;
use Src\Domain\Import\ProcessorRegistry;

/**
 * Registry que descobre os processadores varrendo um diretório.
 *
 * Toda classe instanciável que implemente {@see FileProcessor} dentro do
 * diretório configurado passa a ficar disponível automaticamente.
 */
final class DirectoryProcessorRegistry implements ProcessorRegistry
{
    /** @var list<class-string<FileProcessor>>|null */
    private ?array $processors = null;

    public function __construct(
        private readonly Container $container,
        private readonly string $directory,
        private readonly string $namespace,
    ) {}

    public function all(): array
    {
        if ($this->processors !== null) {
            return $this->processors;
        }

        if (! is_dir($this->directory)) {
            return $this->processors = [];
        }

        $processors = [];

        foreach (glob($this->directory.DIRECTORY_SEPARATOR.'*.php') ?: [] as $file) {
            /** @var class-string $class */
            $class = $this->namespace.'\\'.basename($file, '.php');

            if (! class_exists($class)) {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isInstantiable() && $reflection->implementsInterface(FileProcessor::class)) {
                /** @var class-string<FileProcessor> $class */
                $processors[] = $class;
            }
        }

        sort($processors);

        return $this->processors = $processors;
    }

    public function options(): array
    {
        $options = [];

        foreach ($this->all() as $class) {
            $options[$class] = $class::label();
        }

        return $options;
    }

    public function has(string $processor): bool
    {
        return in_array($processor, $this->all(), true);
    }

    public function make(string $processor): FileProcessor
    {
        if (! $this->has($processor)) {
            throw UnknownProcessor::for($processor);
        }

        /** @var FileProcessor */
        return $this->container->make($processor);
    }
}
