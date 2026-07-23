<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Especificação do destino das linhas mapeadas: o tipo de conector e sua
 * configuração (ex.: formato do arquivo de exportação, endpoint/auth da API).
 */
final readonly class DestinationSpec
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public DestinationKind $kind,
        public array $config = [],
    ) {}

    public static function exportFile(string $format = 'csv'): self
    {
        return new self(DestinationKind::ExportFile, ['format' => $format]);
    }

    /**
     * @param  array<string, string>  $headers
     */
    public static function restApi(string $endpoint, array $headers = [], string $method = 'post', int $batchSize = 100, string $wrapKey = 'data'): self
    {
        return new self(DestinationKind::RestApi, [
            'endpoint' => $endpoint,
            'headers' => $headers,
            'method' => $method,
            'batch_size' => $batchSize,
            'wrap_key' => $wrapKey,
        ]);
    }

    /**
     * @param  array{kind: string, config?: array<string, mixed>}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            DestinationKind::from($data['kind']),
            $data['config'] ?? [],
        );
    }

    /**
     * @return array{kind: string, config: array<string, mixed>}
     */
    public function toArray(): array
    {
        return ['kind' => $this->kind->value, 'config' => $this->config];
    }
}
