<?php

declare(strict_types=1);

namespace Src\Domain\Import;

use Src\Domain\Import\Template\Template;

/**
 * Dados de entrada entregues a um {@see FileProcessor} no momento da execução.
 *
 * Carrega tudo o que o processador precisa para ler e tratar o arquivo, sem
 * acoplar o domínio a detalhes de framework ou persistência.
 *
 * `template` é preenchido quando o programa roda em "modo configurável"
 * (aponta para um {@see Template}); processadores em código o ignoram.
 */
final readonly class ImportContext
{
    public function __construct(
        public int $importId,
        public string $filePath,
        public FileType $type,
        public string $originalName,
        public ?Template $template = null,
    ) {}
}
