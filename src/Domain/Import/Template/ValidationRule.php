<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Uma regra de validação aplicada ao valor já transformado de um campo.
 *
 * Devolve a mensagem de erro (legível) ou `null` quando o valor é válido.
 * Regras ignoram valor vazio — a obrigatoriedade é o flag `required` do
 * {@see TemplateField}.
 */
final readonly class ValidationRule
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function __construct(
        public ValidationKind $kind,
        public array $params = [],
    ) {}

    public function validate(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        return match ($this->kind) {
            ValidationKind::Numeric => is_numeric(str_replace(',', '.', $value))
                ? null
                : 'valor não numérico',
            ValidationKind::Email => filter_var($value, FILTER_VALIDATE_EMAIL) !== false
                ? null
                : 'e-mail inválido',
            ValidationKind::Regex => $this->matchesRegex($value),
            ValidationKind::MaxLength => mb_strlen($value) <= (int) ($this->params['max'] ?? PHP_INT_MAX)
                ? null
                : "excede o tamanho máximo ({$this->params['max']})",
            ValidationKind::MinLength => mb_strlen($value) >= (int) ($this->params['min'] ?? 0)
                ? null
                : "abaixo do tamanho mínimo ({$this->params['min']})",
            ValidationKind::In => $this->isInList($value),
        };
    }

    private function matchesRegex(string $value): ?string
    {
        $pattern = (string) ($this->params['pattern'] ?? '');

        if ($pattern === '') {
            return null;
        }

        return preg_match($pattern, $value) === 1 ? null : 'formato inválido';
    }

    private function isInList(string $value): ?string
    {
        /** @var list<string> $values */
        $values = $this->params['values'] ?? [];

        return in_array($value, $values, true) ? null : 'valor não permitido';
    }

    /**
     * @param  array{kind: string, params?: array<string, mixed>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ValidationKind::from($data['kind']),
            $data['params'] ?? [],
        );
    }

    /**
     * @return array{kind: string, params: array<string, mixed>}
     */
    public function toArray(): array
    {
        return ['kind' => $this->kind->value, 'params' => $this->params];
    }
}
