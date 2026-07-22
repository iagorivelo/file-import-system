<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

use Src\Domain\Import\FileType;

/**
 * Especificação de como ler o arquivo de origem: formato, delimitador, se há
 * cabeçalho, encoding e (para Excel) a aba.
 */
final readonly class SourceFormat
{
    public function __construct(
        public FileType $fileType,
        public string $delimiter = ',',
        public string $enclosure = '"',
        public bool $hasHeader = true,
        public string $encoding = 'UTF-8',
        public ?string $sheet = null,
        public int $skipRows = 0,
    ) {}

    /**
     * @param  array{
     *     fileType: string,
     *     delimiter?: string,
     *     enclosure?: string,
     *     hasHeader?: bool,
     *     encoding?: string,
     *     sheet?: string|null,
     *     skipRows?: int
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            fileType: FileType::from($data['fileType']),
            delimiter: $data['delimiter'] ?? ',',
            enclosure: $data['enclosure'] ?? '"',
            hasHeader: (bool) ($data['hasHeader'] ?? true),
            encoding: $data['encoding'] ?? 'UTF-8',
            sheet: $data['sheet'] ?? null,
            skipRows: (int) ($data['skipRows'] ?? 0),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'fileType' => $this->fileType->value,
            'delimiter' => $this->delimiter,
            'enclosure' => $this->enclosure,
            'hasHeader' => $this->hasHeader,
            'encoding' => $this->encoding,
            'sheet' => $this->sheet,
            'skipRows' => $this->skipRows,
        ];
    }
}
