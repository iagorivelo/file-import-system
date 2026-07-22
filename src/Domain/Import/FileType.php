<?php

declare(strict_types=1);

namespace Src\Domain\Import;

use Src\Domain\Import\Exceptions\UnsupportedFileType;

/**
 * Tipos de arquivo aceitos na importação.
 */
enum FileType: string
{
    case Txt = 'txt';
    case Csv = 'csv';
    case Xlsx = 'xlsx';

    public function label(): string
    {
        return match ($this) {
            self::Txt => 'Texto (.txt)',
            self::Csv => 'CSV (.csv)',
            self::Xlsx => 'Excel (.xlsx)',
        };
    }

    /**
     * MIME types aceitos para o tipo (usado na validação de upload).
     *
     * @return list<string>
     */
    public function mimeTypes(): array
    {
        return match ($this) {
            self::Txt => ['text/plain'],
            self::Csv => ['text/csv', 'application/csv', 'text/plain'],
            self::Xlsx => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        };
    }

    /**
     * Indica se o tipo é lido como texto delimitado (CSV/TXT) por um parser de linha.
     */
    public function isDelimited(): bool
    {
        return $this === self::Txt || $this === self::Csv;
    }

    /**
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }

    public static function fromExtension(string $extension): self
    {
        $normalized = strtolower(ltrim($extension, '.'));

        return self::tryFrom($normalized)
            ?? throw UnsupportedFileType::for($normalized);
    }
}
