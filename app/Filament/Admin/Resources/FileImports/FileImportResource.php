<?php

namespace App\Filament\Admin\Resources\FileImports;

use App\Filament\Admin\Resources\FileImports\Pages\ListFileImports;
use App\Filament\Admin\Resources\FileImports\Pages\ViewFileImport;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Src\Domain\Import\FileType;
use Src\Domain\Import\ImportStatus;
use Src\Infrastructure\Persistence\Models\FileImport;

class FileImportResource extends Resource
{
    protected static ?string $model = FileImport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|\UnitEnum|null $navigationGroup = 'Importações';

    protected static ?string $navigationLabel = 'Histórico';

    protected static ?string $modelLabel = 'importação';

    protected static ?string $pluralModelLabel = 'importações';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('program.name')->label('Programa'),
            TextEntry::make('user.name')->label('Usuário'),
            TextEntry::make('original_filename')->label('Arquivo'),
            TextEntry::make('file_type')
                ->label('Tipo')
                ->badge()
                ->formatStateUsing(fn (FileType $state): string => $state->label()),
            TextEntry::make('status')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn (ImportStatus $state): string => $state->label())
                ->color(fn (ImportStatus $state): string => $state->color()),
            TextEntry::make('processed_rows')->label('Linhas processadas')->numeric(),
            TextEntry::make('failed_rows')->label('Linhas com erro')->numeric(),
            TextEntry::make('error_message')
                ->label('Mensagem de erro')
                ->placeholder('—')
                ->columnSpanFull(),
            TextEntry::make('started_at')->label('Iniciado em')->dateTime('d/m/Y H:i:s')->placeholder('—'),
            TextEntry::make('finished_at')->label('Concluído em')->dateTime('d/m/Y H:i:s')->placeholder('—'),
            TextEntry::make('created_at')->label('Criado em')->dateTime('d/m/Y H:i:s'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('program.name')
                    ->label('Programa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Usuário')
                    ->searchable(),
                TextColumn::make('original_filename')
                    ->label('Arquivo')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('file_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (FileType $state): string => $state->label()),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (ImportStatus $state): string => $state->label())
                    ->color(fn (ImportStatus $state): string => $state->color()),
                TextColumn::make('processed_rows')
                    ->label('OK')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('failed_rows')
                    ->label('Erros')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(fn (): array => collect(ImportStatus::cases())
                        ->mapWithKeys(fn (ImportStatus $status): array => [$status->value => $status->label()])
                        ->all()),
                SelectFilter::make('program')
                    ->label('Programa')
                    ->relationship('program', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
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
            'index' => ListFileImports::route('/'),
            'view' => ViewFileImport::route('/{record}'),
        ];
    }
}
