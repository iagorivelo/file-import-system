<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Domain\Import\FileType;
use Src\Domain\Import\Template\DestinationSpec;
use Src\Domain\Import\Template\FieldSource;
use Src\Domain\Import\Template\FieldType;
use Src\Domain\Import\Template\SourceFormat;
use Src\Domain\Import\Template\TemplateField;
use Src\Infrastructure\Persistence\Models\ImportTemplate;

/**
 * @extends Factory<ImportTemplate>
 */
class ImportTemplateFactory extends Factory
{
    protected $model = ImportTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Template '.fake()->unique()->words(2, true),
            'niche' => null,
            'source_format' => (new SourceFormat(FileType::Csv, delimiter: ';', hasHeader: true))->toArray(),
            'fields' => [
                (new TemplateField('nome', 'Nome', FieldType::Text, true, FieldSource::header('nome')))->toArray(),
            ],
            'destination' => DestinationSpec::exportFile()->toArray(),
            'dedup_key' => null,
        ];
    }
}
