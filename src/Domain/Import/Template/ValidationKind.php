<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Tipos de validação aplicáveis ao valor já transformado de um campo.
 *
 * A obrigatoriedade não vive aqui: é o flag `required` do {@see TemplateField}.
 * Regras abaixo ignoram valores vazios (deixando a obrigatoriedade a cargo do
 * flag), exceto quando o próprio conceito exige presença.
 */
enum ValidationKind: string
{
    case Numeric = 'numeric';
    case Email = 'email';
    case Regex = 'regex';
    case MaxLength = 'max_length';
    case MinLength = 'min_length';
    case In = 'in';

    public function label(): string
    {
        return match ($this) {
            self::Numeric => 'Numérico',
            self::Email => 'E-mail válido',
            self::Regex => 'Expressão regular',
            self::MaxLength => 'Tamanho máximo',
            self::MinLength => 'Tamanho mínimo',
            self::In => 'Um dos valores permitidos',
        };
    }
}
