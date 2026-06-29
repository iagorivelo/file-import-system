<?php

namespace App\Filament\Admin\Resources\Programs;

use App\Filament\Admin\Resources\Programs\Pages\CreateProgram;
use App\Filament\Admin\Resources\Programs\Pages\EditProgram;
use App\Filament\Admin\Resources\Programs\Pages\ListPrograms;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Src\Domain\Import\FileProcessor;
use Src\Domain\Import\ProcessorRegistry;
use Src\Infrastructure\Persistence\Models\Program;

class ProgramResource extends Resource
{
    protected static ?string $model = Program::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão';

    protected static ?string $navigationLabel = 'Programas';

    protected static ?string $modelLabel = 'programa';

    protected static ?string $pluralModelLabel = 'programas';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(255),
            ColorPicker::make('color')
                ->label('Cor da box')
                ->required()
                ->default('#4b6043'),
            Select::make('processor_class')
                ->label('Processador')
                ->options(fn (): array => app(ProcessorRegistry::class)->options())
                ->formatStateUsing(fn (?string $state): ?string => $state)
                ->required()
                ->searchable()
                ->native(false)
                ->helperText('Classe que fará a leitura e o tratamento do arquivo deste programa. '
                    .'Para adicionar opções, crie uma classe em src/Infrastructure/Import/Processors.'),
            Toggle::make('is_active')
                ->label('Ativo')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColorColumn::make('color')
                    ->label('Cor'),
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('processor_class')
                    ->label('Processador')
                    ->formatStateUsing(fn (string $state): string => is_subclass_of($state, FileProcessor::class)
                        ? $state::label()
                        : $state)
                    ->wrap(),
                TextColumn::make('file_imports_count')
                    ->label('Importações')
                    ->counts('fileImports')
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('Ativo')
                    ->boolean(),
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrograms::route('/'),
            'create' => CreateProgram::route('/create'),
            'edit' => EditProgram::route('/{record}/edit'),
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
