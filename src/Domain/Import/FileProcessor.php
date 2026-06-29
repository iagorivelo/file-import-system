<?php

declare(strict_types=1);

namespace Src\Domain\Import;

/**
 * Contrato (Strategy) que toda rotina de importação deve implementar.
 *
 * Cada "programa" (box) do painel aponta para uma classe que implementa este
 * contrato. Ao importar um arquivo daquele programa, o sistema resolve a classe
 * correspondente e executa {@see self::process()}.
 *
 * Para criar uma nova rotina, basta criar uma classe que implemente esta
 * interface dentro do diretório de processadores — ela passa a aparecer
 * automaticamente na lista de seleção ao cadastrar/editar um programa.
 */
interface FileProcessor
{
    /**
     * Rótulo legível exibido no painel ao vincular o processador a um programa.
     */
    public static function label(): string;

    /**
     * Tipos de arquivo aceitos por este processador.
     *
     * O modal de importação usa esta lista para restringir o que o usuário
     * pode enviar para o programa correspondente.
     *
     * @return list<FileType>
     */
    public static function acceptedFileTypes(): array;

    /**
     * Lê e trata o arquivo importado, devolvendo o resultado do processamento.
     */
    public function process(ImportContext $context): ImportResult;
}
