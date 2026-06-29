<?php

declare(strict_types=1);

namespace Src\Infrastructure\Import\Processors;

use Src\Domain\Import\FileProcessor;
use Src\Domain\Import\FileType;
use Src\Domain\Import\ImportContext;
use Src\Domain\Import\ImportResult;
use Src\Infrastructure\Persistence\Models\TesteRecord;

/**
 * Programa "Teste": aceita apenas arquivos .txt, onde cada linha é o nome de
 * uma pessoa, e grava um registro em `testes_tb` para cada nome válido.
 *
 * Validações por linha:
 * - linhas vazias são ignoradas (não contam como erro);
 * - nomes com menos de 2 caracteres são rejeitados;
 * - nomes sem nenhuma letra são rejeitados;
 * - nomes repetidos dentro do mesmo arquivo são rejeitados.
 */
final class TesteProcessor implements FileProcessor
{
    /** Máximo de mensagens de erro detalhadas (as excedentes viram resumo). */
    private const MAX_ERROR_MESSAGES = 50;

    public static function label(): string
    {
        return 'Teste';
    }

    /**
     * @return list<FileType>
     */
    public static function acceptedFileTypes(): array
    {
        return [FileType::Txt];
    }

    public function process(ImportContext $context): ImportResult
    {
        $handle = @fopen($context->filePath, 'rb');

        if ($handle === false) {
            return new ImportResult(
                processedRows: 0,
                failedRows: 0,
                errors: ["Não foi possível abrir o arquivo: {$context->originalName}."],
            );
        }

        $processed = 0;
        $failed = 0;
        $errors = [];
        $seen = [];
        $lineNumber = 0;

        try {
            while (($line = fgets($handle)) !== false) {
                $lineNumber++;
                $nome = trim($line);

                // Linhas vazias são ignoradas (não contam como erro).
                if ($nome === '') {
                    continue;
                }

                $error = $this->validate($nome, $seen);

                if ($error !== null) {
                    $failed++;

                    if (count($errors) < self::MAX_ERROR_MESSAGES) {
                        $errors[] = "Linha {$lineNumber}: {$error}";
                    }

                    continue;
                }

                $seen[mb_strtolower($nome)] = true;

                TesteRecord::query()->create(['nome' => $nome]);

                $processed++;
            }
        } finally {
            fclose($handle);
        }

        // Resume os erros que excederam o limite de detalhamento.
        $hidden = $failed - count($errors);
        if ($hidden > 0) {
            $errors[] = "… e mais {$hidden} linha(s) com erro.";
        }

        return new ImportResult(
            processedRows: $processed,
            failedRows: $failed,
            errors: $errors,
        );
    }

    /**
     * Valida um nome; devolve a mensagem de erro ou null quando válido.
     *
     * @param  array<string, true>  $seen
     */
    private function validate(string $nome, array $seen): ?string
    {
        if (mb_strlen($nome) < 2) {
            return "nome muito curto (\"{$nome}\").";
        }

        if (preg_match('/\p{L}/u', $nome) !== 1) {
            return "nome sem letras (\"{$nome}\").";
        }

        if (isset($seen[mb_strtolower($nome)])) {
            return "nome duplicado (\"{$nome}\").";
        }

        return null;
    }
}
