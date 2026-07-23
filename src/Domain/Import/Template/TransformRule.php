<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

use DateTimeImmutable;
use Throwable;

/**
 * Uma transformação aplicável ao valor bruto de um campo.
 *
 * As transformações são aplicadas em ordem, antes da validação e do cast de
 * tipo. Cada tipo ({@see TransformKind}) consome parâmetros próprios em `params`.
 */
final readonly class TransformRule
{
    /**
     * @param  array<string, string>  $params
     */
    public function __construct(
        public TransformKind $kind,
        public array $params = [],
    ) {}

    public function apply(string $value): string
    {
        return match ($this->kind) {
            TransformKind::Trim => trim($value),
            TransformKind::UpperCase => mb_strtoupper($value),
            TransformKind::LowerCase => mb_strtolower($value),
            TransformKind::TitleCase => mb_convert_case($value, MB_CASE_TITLE),
            TransformKind::OnlyDigits => preg_replace('/\D+/', '', $value) ?? '',
            TransformKind::DefaultIfEmpty => $value === '' ? ($this->params['value'] ?? '') : $value,
            TransformKind::Replace => str_replace(
                $this->params['search'] ?? '',
                $this->params['replace'] ?? '',
                $value,
            ),
            TransformKind::DateFormat => $this->reformatDate($value),
        };
    }

    /**
     * Reformata uma data de `from` para `to` (padrão de saída Y-m-d).
     *
     * Se a data não puder ser interpretada, o valor original é preservado para
     * que uma {@see ValidationRule} possa sinalizar o erro.
     */
    private function reformatDate(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $from = $this->params['from'] ?? null;
        $to = $this->params['to'] ?? 'Y-m-d';

        try {
            $date = $from !== null && $from !== ''
                ? DateTimeImmutable::createFromFormat($from, $value)
                : new DateTimeImmutable($value);
        } catch (Throwable) {
            return $value;
        }

        return $date instanceof DateTimeImmutable ? $date->format($to) : $value;
    }

    /**
     * @param  array{kind: string, params?: array<string, string>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            TransformKind::from($data['kind']),
            $data['params'] ?? [],
        );
    }

    /**
     * @return array{kind: string, params: array<string, string>}
     */
    public function toArray(): array
    {
        return ['kind' => $this->kind->value, 'params' => $this->params];
    }
}
