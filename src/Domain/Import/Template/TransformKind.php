<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Tipos de transformação aplicáveis ao valor bruto de um campo, em ordem,
 * antes da validação. Os parâmetros de cada transformação ficam na
 * {@see TransformRule} correspondente.
 */
enum TransformKind: string
{
    case Trim = 'trim';
    case UpperCase = 'upper';
    case LowerCase = 'lower';
    case TitleCase = 'title';
    case OnlyDigits = 'only_digits';
    case DefaultIfEmpty = 'default_if_empty';
    case Replace = 'replace';
    case DateFormat = 'date_format';

    public function label(): string
    {
        return match ($this) {
            self::Trim => 'Remover espaços das pontas',
            self::UpperCase => 'MAIÚSCULAS',
            self::LowerCase => 'minúsculas',
            self::TitleCase => 'Primeira Letra Maiúscula',
            self::OnlyDigits => 'Somente dígitos',
            self::DefaultIfEmpty => 'Valor padrão quando vazio',
            self::Replace => 'Substituir texto',
            self::DateFormat => 'Reformatar data',
        };
    }
}
