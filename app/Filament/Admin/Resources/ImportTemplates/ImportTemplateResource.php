<?php

namespace App\Filament\Admin\Resources\ImportTemplates;

use App\Filament\Admin\Resources\ImportTemplates\Pages\CreateImportTemplate;
use App\Filament\Admin\Resources\ImportTemplates\Pages\EditImportTemplate;
use App\Filament\Admin\Resources\ImportTemplates\Pages\ListImportTemplates;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Src\Domain\Import\FileType;
use Src\Domain\Import\Template\DestinationKind;
use Src\Domain\Import\Template\FieldSourceKind;
use Src\Domain\Import\Template\FieldType;
use Src\Domain\Import\Template\TransformKind;
use Src\Domain\Import\Template\ValidationKind;
use Src\Infrastructure\Persistence\Models\ImportTemplate;

class ImportTemplateResource extends Resource
{
    protected static ?string $model = ImportTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|\UnitEnum|null $navigationGroup = 'Importação';

    protected static ?string $navigationLabel = 'Templates de importação';

    protected static ?string $modelLabel = 'template';

    protected static ?string $pluralModelLabel = 'templates';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Identificação')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    Select::make('company_id')
                        ->label('Empresa')
                        ->relationship('company', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false)
                        ->placeholder('Global (template de nicho)')
                        ->helperText('Vazio = template de nicho global, reaproveitável por qualquer empresa.'),
                    TextInput::make('niche')
                        ->label('Nicho')
                        ->maxLength(255)
                        ->helperText('Ex.: veterinaria, farmacia, escola.'),
                    TextInput::make('dedup_key')
                        ->label('Campo de deduplicação (key)')
                        ->helperText('key de um dos campos abaixo; linhas repetidas nesse valor são rejeitadas. Vazio = sem dedup.'),
                ]),

            Section::make('Formato de origem')
                ->columns(3)
                ->schema([
                    Select::make('source_format.fileType')
                        ->label('Tipo de arquivo')
                        ->options(FileType::options())
                        ->default(FileType::Csv->value)
                        ->required()
                        ->native(false),
                    TextInput::make('source_format.delimiter')
                        ->label('Delimitador')
                        ->default(';')
                        ->maxLength(4),
                    TextInput::make('source_format.enclosure')
                        ->label('Delimitador de texto')
                        ->default('"')
                        ->maxLength(4),
                    Toggle::make('source_format.hasHeader')
                        ->label('1ª linha é cabeçalho')
                        ->default(true),
                    TextInput::make('source_format.encoding')
                        ->label('Encoding')
                        ->default('UTF-8')
                        ->maxLength(32),
                    TextInput::make('source_format.skipRows')
                        ->label('Linhas a pular')
                        ->numeric()
                        ->default(0),
                ]),

            Section::make('Campos')
                ->schema([
                    Repeater::make('fields')
                        ->label('Campos de saída')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('key')
                                    ->label('Chave (key)')
                                    ->required()
                                    ->helperText('Nome do campo de saída (ex.: nome, email).'),
                                TextInput::make('label')
                                    ->label('Rótulo')
                                    ->required(),
                                Select::make('type')
                                    ->label('Tipo')
                                    ->options(FieldType::options())
                                    ->default(FieldType::Text->value)
                                    ->required()
                                    ->native(false),
                                Toggle::make('required')
                                    ->label('Obrigatório'),
                                Select::make('source.kind')
                                    ->label('Origem do valor')
                                    ->options(FieldSourceKind::options())
                                    ->default(FieldSourceKind::Header->value)
                                    ->required()
                                    ->native(false),
                                TextInput::make('source.value')
                                    ->label('Coluna (cabeçalho/índice) ou valor fixo')
                                    ->helperText('Cabeçalho: nome da coluna. Índice: posição (0-based). Fixo: o próprio valor.'),
                            ]),
                            TagsInput::make('aliases')
                                ->label('Sinônimos (auto-mapeamento)')
                                ->helperText('Nomes alternativos de coluna que devem casar com este campo.'),
                            Repeater::make('transforms')
                                ->label('Transformações')
                                ->schema([
                                    Select::make('kind')
                                        ->label('Transformação')
                                        ->options(TransformKind::options())
                                        ->required()
                                        ->native(false),
                                    KeyValue::make('params')
                                        ->label('Parâmetros')
                                        ->keyLabel('Parâmetro')
                                        ->valueLabel('Valor'),
                                ])
                                ->collapsed()
                                ->addActionLabel('Adicionar transformação'),
                            Repeater::make('validations')
                                ->label('Validações')
                                ->schema([
                                    Select::make('kind')
                                        ->label('Validação')
                                        ->options(ValidationKind::options())
                                        ->required()
                                        ->native(false),
                                    KeyValue::make('params')
                                        ->label('Parâmetros')
                                        ->keyLabel('Parâmetro')
                                        ->valueLabel('Valor'),
                                ])
                                ->collapsed()
                                ->addActionLabel('Adicionar validação'),
                        ])
                        ->itemLabel(fn (array $state): ?string => $state['label'] ?? $state['key'] ?? null)
                        ->collapsible()
                        ->reorderable()
                        ->addActionLabel('Adicionar campo')
                        ->defaultItems(1),
                ]),

            Section::make('Destino')
                ->columns(2)
                ->schema([
                    Select::make('destination.kind')
                        ->label('Tipo de destino')
                        ->options(DestinationKind::options())
                        ->default(DestinationKind::ExportFile->value)
                        ->required()
                        ->native(false)
                        ->live(),
                    Select::make('destination.config.format')
                        ->label('Formato do arquivo')
                        ->options(['csv' => 'CSV', 'json' => 'JSON'])
                        ->default('csv')
                        ->native(false)
                        ->visible(fn ($get): bool => $get('destination.kind') === DestinationKind::ExportFile->value),
                    TextInput::make('destination.config.endpoint')
                        ->label('Endpoint')
                        ->url()
                        ->visible(fn ($get): bool => $get('destination.kind') === DestinationKind::RestApi->value)
                        ->required(fn ($get): bool => $get('destination.kind') === DestinationKind::RestApi->value),
                    Select::make('destination.config.method')
                        ->label('Método HTTP')
                        ->options(['post' => 'POST', 'put' => 'PUT', 'patch' => 'PATCH'])
                        ->default('post')
                        ->native(false)
                        ->visible(fn ($get): bool => $get('destination.kind') === DestinationKind::RestApi->value),
                    TextInput::make('destination.config.wrap_key')
                        ->label('Chave do lote (wrap)')
                        ->default('data')
                        ->visible(fn ($get): bool => $get('destination.kind') === DestinationKind::RestApi->value),
                    TextInput::make('destination.config.batch_size')
                        ->label('Tamanho do lote')
                        ->numeric()
                        ->default(100)
                        ->visible(fn ($get): bool => $get('destination.kind') === DestinationKind::RestApi->value),
                    KeyValue::make('destination.config.headers')
                        ->label('Cabeçalhos (autenticação)')
                        ->keyLabel('Cabeçalho')
                        ->valueLabel('Valor')
                        ->visible(fn ($get): bool => $get('destination.kind') === DestinationKind::RestApi->value),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Empresa')
                    ->placeholder('Global (nicho)')
                    ->badge(),
                TextColumn::make('niche')
                    ->label('Nicho')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('fields')
                    ->label('Campos')
                    ->badge()
                    ->getStateUsing(fn (ImportTemplate $record): int => count($record->fields ?? [])),
                TextColumn::make('destination')
                    ->label('Destino')
                    ->getStateUsing(fn (ImportTemplate $record): string => (string) ($record->destination['kind'] ?? '—')),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Normaliza os dados do Repeater (que o Filament indexa por UUID) para
     * listas, mantendo o shape que os value objects do domínio esperam.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizeFormData(array $data): array
    {
        if (isset($data['fields']) && is_array($data['fields'])) {
            $data['fields'] = array_values(array_map(static function (array $field): array {
                $field['transforms'] = array_values($field['transforms'] ?? []);
                $field['validations'] = array_values($field['validations'] ?? []);
                $field['aliases'] = array_values($field['aliases'] ?? []);

                return $field;
            }, $data['fields']));
        }

        return $data;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListImportTemplates::route('/'),
            'create' => CreateImportTemplate::route('/create'),
            'edit' => EditImportTemplate::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
