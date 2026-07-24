<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Tipo do campo de saída produzido por um {@see TemplateField}.
 *
 * Define o rótulo exibido na configuração e como o valor (já transformado) é
 * convertido para o tipo final entregue ao destino.
 */
enum FieldType: string
{
    case Text = 'text';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Date = 'date';
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Texto',
            self::Integer => 'Número inteiro',
            self::Decimal => 'Número decimal',
            self::Boolean => 'Booleano (sim/não)',
            self::Date => 'Data',
            self::Email => 'E-mail',
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

    /**
     * Converte o valor (string já transformada) para o tipo de saída.
     *
     * A validação de correção é responsabilidade das {@see ValidationRule};
     * aqui a conversão é lenient e devolve `null` para valores vazios.
     */
    public function cast(string $value): string|int|float|bool|null
    {
        if ($value === '') {
            return null;
        }

        return match ($this) {
            self::Integer => is_numeric($value) ? (int) $value : $value,
            self::Decimal => is_numeric(str_replace(',', '.', $value))
                ? (float) str_replace(',', '.', $value)
                : $value,
            self::Boolean => self::toBool($value),
            self::Text, self::Date, self::Email => $value,
        };
    }

    private static function toBool(string $value): bool|string
    {
        $normalized = mb_strtolower(trim($value));

        return match (true) {
            in_array($normalized, ['1', 'true', 'sim', 's', 'yes', 'y', 'v', 't'], true) => true,
            in_array($normalized, ['0', 'false', 'nao', 'não', 'n', 'no', 'f'], true) => false,
            default => $value,
        };
    }
}
