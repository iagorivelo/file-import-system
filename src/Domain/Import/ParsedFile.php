<?php

declare(strict_types=1);

namespace Src\Domain\Import;

/**
 * Resultado de {@see FileParser::parse()}: os cabeçalhos detectados (vazio
 * quando o arquivo não tem cabeçalho) e um iterável preguiçoso das linhas de
 * dados, cada uma como lista posicional de strings.
 *
 * As linhas são um iterável (tipicamente um generator) para permitir leitura em
 * streaming de arquivos grandes sem carregar tudo em memória.
 */
final readonly class ParsedFile
{
    /**
     * @param  list<string>  $headers
     * @param  iterable<int, list<string>>  $rows
     */
    public function __construct(
        public array $headers,
        public iterable $rows,
    ) {}
}
