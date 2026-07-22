<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Sugestão de mapeamento de um campo do template para uma coluna (cabeçalho) do
 * arquivo, produzida pelo {@see AutoMapper}. `header` nulo indica que nenhuma
 * coluna casou com confiança suficiente (o usuário deve mapear manualmente).
 */
final readonly class ColumnSuggestion
{
    public function __construct(
        public string $fieldKey,
        public string $fieldLabel,
        public ?string $header,
        public float $score,
        public string $matchedBy,
    ) {}

    public function matched(): bool
    {
        return $this->header !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'fieldKey' => $this->fieldKey,
            'fieldLabel' => $this->fieldLabel,
            'header' => $this->header,
            'score' => $this->score,
            'matchedBy' => $this->matchedBy,
        ];
    }
}
