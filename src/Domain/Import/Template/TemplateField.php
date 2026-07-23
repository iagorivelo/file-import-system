<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Definição de um campo de saída do template: como obtê-lo do arquivo (source),
 * como transformá-lo, como validá-lo e qual seu tipo final.
 *
 * `aliases` alimenta o auto-mapeamento (casar cabeçalhos do arquivo com o campo).
 */
final readonly class TemplateField
{
    /**
     * @param  list<TransformRule>  $transforms
     * @param  list<ValidationRule>  $validations
     * @param  list<string>  $aliases
     */
    public function __construct(
        public string $key,
        public string $label,
        public FieldType $type,
        public bool $required,
        public FieldSource $source,
        public array $transforms = [],
        public array $validations = [],
        public array $aliases = [],
    ) {}

    /**
     * @param  array{
     *     key: string,
     *     label: string,
     *     type: string,
     *     required?: bool,
     *     source: array{kind: string, value: string},
     *     transforms?: list<array{kind: string, params?: array<string, string>}>,
     *     validations?: list<array{kind: string, params?: array<string, mixed>}>,
     *     aliases?: list<string>
     * }  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'],
            label: $data['label'],
            type: FieldType::from($data['type']),
            required: (bool) ($data['required'] ?? false),
            source: FieldSource::fromArray($data['source']),
            transforms: array_map(
                static fn (array $t): TransformRule => TransformRule::fromArray($t),
                $data['transforms'] ?? [],
            ),
            validations: array_map(
                static fn (array $v): ValidationRule => ValidationRule::fromArray($v),
                $data['validations'] ?? [],
            ),
            aliases: $data['aliases'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'type' => $this->type->value,
            'required' => $this->required,
            'source' => $this->source->toArray(),
            'transforms' => array_map(static fn (TransformRule $t): array => $t->toArray(), $this->transforms),
            'validations' => array_map(static fn (ValidationRule $v): array => $v->toArray(), $this->validations),
            'aliases' => $this->aliases,
        ];
    }
}
