<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Agregado de configuração de uma importação (o "layout") como dado, não código.
 *
 * Reúne o formato de origem, os campos de saída (com mapeamento, transformações
 * e validações), a chave de deduplicação e o destino. É reconstituído a partir
 * das colunas JSON do model persistente e consumido pelo motor genérico
 * (TemplateProcessor).
 *
 * `niche` nulo + `companyId` nulo indica template global de nicho; um template
 * de empresa carrega ambos preenchidos conforme o caso.
 */
final readonly class Template
{
    /**
     * @param  list<TemplateField>  $fields
     */
    public function __construct(
        public string $name,
        public SourceFormat $sourceFormat,
        public array $fields,
        public DestinationSpec $destination,
        public ?string $dedupKey = null,
        public ?string $niche = null,
        public ?int $id = null,
    ) {}

    /**
     * @param  array{
     *     name: string,
     *     sourceFormat: array<string, mixed>,
     *     fields: list<array<string, mixed>>,
     *     destination: array{kind: string, config?: array<string, mixed>},
     *     dedupKey?: string|null,
     *     niche?: string|null,
     *     id?: int|null
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            sourceFormat: SourceFormat::fromArray($data['sourceFormat']),
            fields: array_map(
                static fn (array $f): TemplateField => TemplateField::fromArray($f),
                $data['fields'],
            ),
            destination: DestinationSpec::fromArray($data['destination']),
            dedupKey: $data['dedupKey'] ?? null,
            niche: $data['niche'] ?? null,
            id: $data['id'] ?? null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'niche' => $this->niche,
            'dedupKey' => $this->dedupKey,
            'sourceFormat' => $this->sourceFormat->toArray(),
            'fields' => array_map(static fn (TemplateField $f): array => $f->toArray(), $this->fields),
            'destination' => $this->destination->toArray(),
        ];
    }
}
