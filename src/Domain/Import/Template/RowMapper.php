<?php

declare(strict_types=1);

namespace Src\Domain\Import\Template;

/**
 * Mapeia uma linha do arquivo para o registro de saída de um {@see Template}:
 * resolve a origem de cada campo, aplica as transformações, valida e converte o
 * tipo. Serviço puro e sem estado — compartilhado pelo TemplateProcessor
 * (importação real) e pelo PreviewImport (dry-run), garantindo comportamento
 * idêntico entre prévia e execução.
 */
final class RowMapper
{
    /**
     * @param  list<string>  $row
     * @param  array<string, int>  $headerIndex  mapa nome-de-cabeçalho => índice
     */
    public function map(array $row, array $headerIndex, Template $template): MappedRow
    {
        $output = [];
        $errors = [];

        foreach ($template->fields as $field) {
            $value = $field->source->resolve($row, $headerIndex);

            foreach ($field->transforms as $transform) {
                $value = $transform->apply($value);
            }

            $error = $this->validateField($field, $value);

            if ($error !== null) {
                $errors[] = "{$field->label}: {$error}";

                continue;
            }

            $output[$field->key] = $field->type->cast($value);
        }

        return new MappedRow($output, $errors);
    }

    private function validateField(TemplateField $field, string $value): ?string
    {
        if ($field->required && $value === '') {
            return 'campo obrigatório';
        }

        foreach ($field->validations as $rule) {
            $error = $rule->validate($value);

            if ($error !== null) {
                return $error;
            }
        }

        return null;
    }
}
