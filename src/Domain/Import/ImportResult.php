<?php

declare(strict_types=1);

namespace Src\Domain\Import;

/**
 * Resultado do processamento de um arquivo por um {@see FileProcessor}.
 */
final readonly class ImportResult
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public int $processedRows = 0,
        public int $failedRows = 0,
        public array $errors = [],
    ) {}

    public static function empty(): self
    {
        return new self;
    }

    public function totalRows(): int
    {
        return $this->processedRows + $this->failedRows;
    }

    public function hasErrors(): bool
    {
        return $this->failedRows > 0 || $this->errors !== [];
    }
}
