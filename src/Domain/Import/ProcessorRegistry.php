<?php

declare(strict_types=1);

namespace Src\Domain\Import;

use Src\Domain\Import\Exceptions\UnknownProcessor;

/**
 * Catálogo de processadores ({@see FileProcessor}) disponíveis no sistema.
 *
 * Permite descobrir as rotinas existentes (para popular o select do painel) e
 * instanciar o processador correto a partir do nome da classe armazenado em um
 * programa.
 */
interface ProcessorRegistry
{
    /**
     * Todos os processadores registrados.
     *
     * @return list<class-string<FileProcessor>>
     */
    public function all(): array;

    /**
     * Mapa FQCN => rótulo, pronto para uso em um select.
     *
     * @return array<class-string<FileProcessor>, string>
     */
    public function options(): array;

    /**
     * Indica se a classe informada é um processador válido e registrado.
     */
    public function has(string $processor): bool;

    /**
     * Instancia o processador a partir do nome da classe.
     *
     * @param  class-string<FileProcessor>  $processor
     *
     * @throws UnknownProcessor quando a classe não é um processador válido.
     */
    public function make(string $processor): FileProcessor;
}
