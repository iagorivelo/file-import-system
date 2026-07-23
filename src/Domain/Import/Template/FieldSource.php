<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Origem do valor bruto de um campo em cada linha do arquivo.
 *
 * Dependendo do {@see FieldSourceKind}, o `value` significa: o nome do
 * cabeçalho (Header), a posição da coluna como string (Index) ou o próprio
 * valor fixo (Constant).
 */
final readonly class FieldSource
{
    public function __construct(
        public FieldSourceKind $kind,
        public string $value,
    ) {}

    public static function header(string $name): self
    {
        return new self(FieldSourceKind::Header, $name);
    }

    public static function index(int $position): self
    {
        return new self(FieldSourceKind::Index, (string) $position);
    }

    public static function constant(string $value): self
    {
        return new self(FieldSourceKind::Constant, $value);
    }

    /**
     * Resolve o valor bruto a partir de uma linha posicional.
     *
     * @param  list<string>  $row
     * @param  array<string, int>  $headerIndex  mapa nome-de-cabeçalho => índice
     */
    public function resolve(array $row, array $headerIndex): string
    {
        return match ($this->kind) {
            FieldSourceKind::Constant => $this->value,
            FieldSourceKind::Index => $row[(int) $this->value] ?? '',
            FieldSourceKind::Header => $this->resolveByHeader($row, $headerIndex),
        };
    }

    /**
     * @param  list<string>  $row
     * @param  array<string, int>  $headerIndex
     */
    private function resolveByHeader(array $row, array $headerIndex): string
    {
        $index = $headerIndex[trim($this->value)] ?? null;

        return $index === null ? '' : ($row[$index] ?? '');
    }

    /**
     * @param  array{kind: string, value: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            FieldSourceKind::from($data['kind']),
            (string) $data['value'],
        );
    }

    /**
     * @return array{kind: string, value: string}
     */
    public function toArray(): array
    {
        return ['kind' => $this->kind->value, 'value' => $this->value];
    }
}
