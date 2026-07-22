<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Sugere automaticamente o mapeamento entre os cabeçalhos de um arquivo e os
 * campos de um {@see Template}, casando por nome normalizado (sem acentos/caixa/
 * pontuação), aliases e similaridade textual. É o que tira o dono do sistema do
 * meio: a empresa sobe o arquivo e já recebe o mapa sugerido para confirmar.
 *
 * Serviço puro e determinístico (sem estado, sem framework).
 */
final class AutoMapper
{
    /** Score mínimo para sugerir uma coluna (abaixo disso: sem sugestão). */
    private const THRESHOLD = 0.60;

    /**
     * @param  list<string>  $headers
     * @return list<ColumnSuggestion> uma sugestão por campo, na ordem do template
     */
    public function suggest(array $headers, Template $template): array
    {
        $normalizedHeaders = [];
        foreach ($headers as $header) {
            $normalizedHeaders[$header] = $this->normalize($header);
        }

        // Calcula todos os pares (campo, cabeçalho) e escolhe gulosamente os de
        // maior score, sem reutilizar cabeçalho já atribuído.
        $pairs = [];
        foreach ($template->fields as $field) {
            foreach ($normalizedHeaders as $header => $normalizedHeader) {
                [$score, $matchedBy] = $this->score($field, $normalizedHeader);
                $pairs[] = [$field->key, $header, $score, $matchedBy];
            }
        }

        usort($pairs, static fn (array $a, array $b): int => $b[2] <=> $a[2]);

        $chosenHeaders = [];
        $chosenByField = [];
        foreach ($pairs as [$fieldKey, $header, $score, $matchedBy]) {
            if ($score < self::THRESHOLD) {
                continue;
            }
            if (isset($chosenByField[$fieldKey]) || isset($chosenHeaders[$header])) {
                continue;
            }
            $chosenByField[$fieldKey] = ['header' => $header, 'score' => $score, 'matchedBy' => $matchedBy];
            $chosenHeaders[$header] = true;
        }

        $suggestions = [];
        foreach ($template->fields as $field) {
            $match = $chosenByField[$field->key] ?? null;
            $suggestions[] = new ColumnSuggestion(
                fieldKey: $field->key,
                fieldLabel: $field->label,
                header: $match['header'] ?? null,
                score: $match !== null ? round($match['score'], 3) : 0.0,
                matchedBy: $match['matchedBy'] ?? 'none',
            );
        }

        return $suggestions;
    }

    /**
     * Melhor score do campo contra um cabeçalho normalizado, considerando key,
     * label e aliases.
     *
     * @return array{0: float, 1: string}
     */
    private function score(TemplateField $field, string $normalizedHeader): array
    {
        if ($normalizedHeader === '') {
            return [0.0, 'none'];
        }

        $best = 0.0;
        $bestBy = 'none';

        $exactCandidates = array_merge([$field->key, $field->label], $field->aliases);

        foreach ($exactCandidates as $candidate) {
            $normalizedCandidate = $this->normalize((string) $candidate);

            if ($normalizedCandidate === '') {
                continue;
            }

            $score = $this->similarity($normalizedCandidate, $normalizedHeader);
            $by = $score >= 0.999 ? 'exact' : 'fuzzy';

            if ($score > $best) {
                $best = $score;
                $bestBy = $by;
            }
        }

        return [$best, $bestBy];
    }

    private function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 1.0;
        }

        // Contido um no outro é sinal forte (ex.: "nome" em "nome do cliente").
        if (str_contains($a, $b) || str_contains($b, $a)) {
            return 0.9;
        }

        similar_text($a, $b, $percent);

        return $percent / 100;
    }

    /**
     * Normaliza para comparação: minúsculas, sem acentos, pontuação vira espaço,
     * espaços colapsados.
     */
    private function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));

        $accents = [
            'á' => 'a', 'à' => 'a', 'ã' => 'a', 'â' => 'a', 'ä' => 'a',
            'é' => 'e', 'ê' => 'e', 'è' => 'e', 'ë' => 'e',
            'í' => 'i', 'î' => 'i', 'ì' => 'i', 'ï' => 'i',
            'ó' => 'o', 'õ' => 'o', 'ô' => 'o', 'ò' => 'o', 'ö' => 'o',
            'ú' => 'u', 'û' => 'u', 'ù' => 'u', 'ü' => 'u',
            'ç' => 'c', 'ñ' => 'n',
        ];
        $value = strtr($value, $accents);

        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }
}
