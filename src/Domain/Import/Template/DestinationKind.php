<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Tipos de destino para onde as linhas mapeadas/validadas são entregues.
 *
 * MVP entrega em arquivo de exportação; REST API e demais drivers entram em
 * fases posteriores (ver plano).
 */
enum DestinationKind: string
{
    case ExportFile = 'export_file';
    case RestApi = 'rest_api';

    public function label(): string
    {
        return match ($this) {
            self::ExportFile => 'Arquivo de exportação',
            self::RestApi => 'API REST',
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
