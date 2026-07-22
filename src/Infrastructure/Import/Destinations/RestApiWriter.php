<?php

declare(strict_types=1);

namespace Src\Infrastructure\Import\Destinations;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Src\Domain\Import\DestinationWriter;
use Src\Domain\Import\Template\DestinationSpec;

/**
 * Destino "API REST": entrega as linhas mapeadas ao sistema externo da empresa
 * via HTTP, em lotes (batch). Configuração no {@see DestinationSpec}:
 * `endpoint` (obrigatório), `method` (post|put|patch), `headers` (auth),
 * `batch_size` e `wrap_key` (chave que embrulha o lote no corpo JSON).
 */
final class RestApiWriter implements DestinationWriter
{
    private string $endpoint = '';

    private string $method = 'post';

    /** @var array<string, string> */
    private array $headers = [];

    private int $batchSize = 100;

    private string $wrapKey = 'data';

    /** @var list<array<string, string|int|float|bool|null>> */
    private array $buffer = [];

    public function begin(DestinationSpec $spec): void
    {
        $config = $spec->config;

        $this->endpoint = (string) ($config['endpoint'] ?? '');

        if ($this->endpoint === '') {
            throw new RuntimeException('Destino API REST sem endpoint configurado.');
        }

        $this->method = strtolower((string) ($config['method'] ?? 'post'));
        /** @var array<string, string> $headers */
        $headers = $config['headers'] ?? [];
        $this->headers = $headers;
        $this->batchSize = max(1, (int) ($config['batch_size'] ?? 100));
        $this->wrapKey = (string) ($config['wrap_key'] ?? 'data');
        $this->buffer = [];
    }

    public function write(array $row): void
    {
        $this->buffer[] = $row;

        if (count($this->buffer) >= $this->batchSize) {
            $this->flush();
        }
    }

    public function finish(): void
    {
        $this->flush();
    }

    private function flush(): void
    {
        if ($this->buffer === []) {
            return;
        }

        $payload = $this->wrapKey !== '' ? [$this->wrapKey => $this->buffer] : $this->buffer;

        $response = Http::withHeaders($this->headers)->{$this->method}($this->endpoint, $payload);

        if ($response->failed()) {
            throw new RuntimeException("Falha ao entregar no destino API REST (HTTP {$response->status()}).");
        }

        $this->buffer = [];
    }
}
