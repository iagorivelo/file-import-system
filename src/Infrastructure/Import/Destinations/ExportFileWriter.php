<?php

declare(strict_types=1);

namespace Src\Infrastructure\Import\Destinations;

use RuntimeException;
use Src\Domain\Import\DestinationWriter;
use Src\Domain\Import\Template\DestinationSpec;

/**
 * Destino "arquivo de exportação": grava as linhas mapeadas em um CSV
 * normalizado dentro do diretório base configurado. O cabeçalho é escrito a
 * partir das chaves da primeira linha.
 *
 * O caminho gerado fica acessível via {@see self::path()} após {@see self::finish()},
 * para registro/entrega (download, SFTP) em camadas superiores.
 */
final class ExportFileWriter implements DestinationWriter
{
    /** @var resource|null */
    private $handle = null;

    private bool $headerWritten = false;

    private string $path = '';

    public function __construct(private readonly string $baseDir) {}

    public function begin(DestinationSpec $spec): void
    {
        if (! is_dir($this->baseDir) && ! @mkdir($this->baseDir, 0775, true) && ! is_dir($this->baseDir)) {
            throw new RuntimeException("Não foi possível criar o diretório de exportação: {$this->baseDir}");
        }

        $filename = 'export_'.date('Ymd_His').'_'.substr(md5(uniqid('', true)), 0, 8).'.csv';
        $this->path = rtrim($this->baseDir, '/\\').DIRECTORY_SEPARATOR.$filename;

        $handle = fopen($this->path, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Não foi possível abrir o arquivo de exportação: {$this->path}");
        }

        $this->handle = $handle;
        $this->headerWritten = false;
    }

    public function write(array $row): void
    {
        if ($this->handle === null) {
            return;
        }

        if (! $this->headerWritten) {
            fputcsv($this->handle, array_keys($row), ',', '"', '');
            $this->headerWritten = true;
        }

        fputcsv($this->handle, array_map($this->stringify(...), array_values($row)), ',', '"', '');
    }

    public function finish(): void
    {
        if ($this->handle !== null) {
            fclose($this->handle);
            $this->handle = null;
        }
    }

    public function path(): string
    {
        return $this->path;
    }

    private function stringify(string|int|float|bool|null $value): string
    {
        return match (true) {
            $value === null => '',
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
