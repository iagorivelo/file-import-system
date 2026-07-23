<?php

declare(strict_types=1);

namespace Src\Infrastructure\Import\Parsers;

use SplFileObject;
use Src\Domain\Import\FileParser;
use Src\Domain\Import\ParsedFile;
use Src\Domain\Import\Template\SourceFormat;

/**
 * Parser de arquivos delimitados (CSV/TXT) usando o SplFileObject nativo do PHP.
 *
 * Lê em streaming (generator) para suportar arquivos grandes sem estourar
 * memória. Respeita delimitador, enclosure, linhas a pular e presença de
 * cabeçalho definidos no {@see SourceFormat}. Fica atrás do contrato
 * {@see FileParser}, permitindo trocar por league/csv no futuro sem impacto.
 */
final class CsvFileParser implements FileParser
{
    public function parse(string $filePath, SourceFormat $format): ParsedFile
    {
        $file = new SplFileObject($filePath, 'rb');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::DROP_NEW_LINE);
        $file->setCsvControl(
            $format->delimiter !== '' ? $format->delimiter : ',',
            $format->enclosure !== '' ? $format->enclosure : '"',
            '', // escape vazio: CSV padrão (sem escaping legado por barra invertida)
        );

        $this->skip($file, $format->skipRows);

        $headers = [];

        if ($format->hasHeader) {
            $headerRow = $this->nextRow($file, $format->encoding);
            $headers = $headerRow === null
                ? []
                : array_map(static fn (string $h): string => trim($h), $headerRow);
        }

        return new ParsedFile($headers, $this->rows($file, $format->encoding));
    }

    /**
     * @return iterable<int, list<string>>
     */
    private function rows(SplFileObject $file, string $encoding): iterable
    {
        while (! $file->eof()) {
            $row = $this->nextRow($file, $encoding);

            if ($row === null) {
                continue;
            }

            yield $row;
        }
    }

    /**
     * Lê a próxima linha como lista posicional de strings, ou null se for uma
     * linha vazia/ inválida (que deve ser ignorada pelo chamador).
     *
     * @return list<string>|null
     */
    private function nextRow(SplFileObject $file, string $encoding): ?array
    {
        /** @var list<string|null>|false $current */
        $current = $file->current();
        $file->next();

        if ($current === false) {
            return null;
        }

        // Linha totalmente vazia vem como [null].
        if (count($current) === 1 && ($current[0] === null || $current[0] === '')) {
            return null;
        }

        return array_map(
            fn (?string $cell): string => $this->normalize($cell ?? '', $encoding),
            $current,
        );
    }

    private function normalize(string $value, string $encoding): string
    {
        $upper = strtoupper($encoding);

        if ($upper !== '' && $upper !== 'UTF-8' && $upper !== 'UTF8') {
            $converted = @mb_convert_encoding($value, 'UTF-8', $encoding);

            if ($converted !== false) {
                return $converted;
            }
        }

        return $value;
    }

    private function skip(SplFileObject $file, int $rows): void
    {
        for ($i = 0; $i < $rows && ! $file->eof(); $i++) {
            $file->current();
            $file->next();
        }
    }
}
