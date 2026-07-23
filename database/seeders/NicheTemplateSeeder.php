<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Src\Domain\Import\FileType;
use Src\Domain\Import\Template\DestinationSpec;
use Src\Domain\Import\Template\FieldSource;
use Src\Domain\Import\Template\FieldType;
use Src\Domain\Import\Template\SourceFormat;
use Src\Domain\Import\Template\TemplateField;
use Src\Domain\Import\Template\ValidationKind;
use Src\Domain\Import\Template\ValidationRule;
use Src\Infrastructure\Persistence\Models\ImportTemplate;

/**
 * Semeia templates de nicho GLOBAIS (company_id nulo) — a "biblioteca" que uma
 * empresa adota e ajusta (via clonagem) sem depender do dono do sistema. Cada
 * campo traz aliases para alimentar o auto-mapeamento.
 */
class NicheTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->templates() as $niche => $definitions) {
            foreach ($definitions as $definition) {
                ImportTemplate::query()->updateOrCreate(
                    ['company_id' => null, 'niche' => $niche, 'name' => $definition['name']],
                    [
                        'source_format' => $definition['source_format'],
                        'fields' => $definition['fields'],
                        'destination' => $definition['destination'],
                        'dedup_key' => $definition['dedup_key'],
                    ],
                );
            }
        }
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function templates(): array
    {
        $csv = (new SourceFormat(FileType::Csv, delimiter: ';', hasHeader: true))->toArray();
        $export = DestinationSpec::exportFile()->toArray();

        $text = static fn (string $key, string $label, bool $required, array $aliases): array => (new TemplateField(
            $key, $label, FieldType::Text, $required, FieldSource::header($label), aliases: $aliases,
        ))->toArray();

        return [
            'veterinaria' => [[
                'name' => 'Cadastro de Pets',
                'source_format' => $csv,
                'destination' => $export,
                'dedup_key' => null,
                'fields' => [
                    $text('tutor_nome', 'Nome do Tutor', true, ['tutor', 'dono', 'responsavel', 'cliente']),
                    $text('pet_nome', 'Nome do Pet', true, ['pet', 'animal', 'paciente']),
                    $text('especie', 'Espécie', false, ['tipo', 'especie']),
                    $text('raca', 'Raça', false, ['raca']),
                ],
            ]],
            'farmacia' => [[
                'name' => 'Produtos',
                'source_format' => $csv,
                'destination' => $export,
                'dedup_key' => 'codigo',
                'fields' => [
                    $text('codigo', 'Código', true, ['cod', 'sku', 'ean', 'codigo de barras']),
                    $text('descricao', 'Descrição', true, ['produto', 'nome', 'descricao']),
                    (new TemplateField('preco', 'Preço', FieldType::Decimal, false, FieldSource::header('Preço'),
                        aliases: ['valor', 'preco', 'preco venda'],
                        validations: [new ValidationRule(ValidationKind::Numeric)]))->toArray(),
                    (new TemplateField('estoque', 'Estoque', FieldType::Integer, false, FieldSource::header('Estoque'),
                        aliases: ['qtd', 'quantidade', 'saldo'],
                        validations: [new ValidationRule(ValidationKind::Numeric)]))->toArray(),
                ],
            ]],
            'escola' => [[
                'name' => 'Alunos',
                'source_format' => $csv,
                'destination' => $export,
                'dedup_key' => 'matricula',
                'fields' => [
                    $text('matricula', 'Matrícula', true, ['ra', 'registro', 'matricula']),
                    $text('nome', 'Nome do Aluno', true, ['aluno', 'estudante', 'nome']),
                    $text('turma', 'Turma', false, ['classe', 'serie', 'turma']),
                    (new TemplateField('responsavel_email', 'E-mail do Responsável', FieldType::Email, false,
                        FieldSource::header('E-mail do Responsável'),
                        aliases: ['email', 'e mail', 'contato'],
                        validations: [new ValidationRule(ValidationKind::Email)]))->toArray(),
                ],
            ]],
        ];
    }
}
