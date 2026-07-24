<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Como o valor bruto de um campo é obtido de cada linha do arquivo.
 */
enum FieldSourceKind: string
{
    /** Pela coluna com o nome de cabeçalho informado. */
    case Header = 'header';

    /** Pela posição (índice 0-based) da coluna. */
    case Index = 'index';

    /** Valor fixo, independente do arquivo. */
    case Constant = 'constant';

    public function label(): string
    {
        return match ($this) {
            self::Header => 'Coluna (por cabeçalho)',
            self::Index => 'Coluna (por posição)',
            self::Constant => 'Valor fixo',
        };
    }

    /**
     * @return array<string, string> value => label
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
