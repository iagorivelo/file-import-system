<?php

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Src\Application\User\ChangeUserPassword;
use Src\Application\User\SetUserActivation;
use Src\Domain\User\UserRole;
use Src\Infrastructure\Persistence\Models\User;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestão';

    protected static ?string $navigationLabel = 'Usuários';

    protected static ?string $modelLabel = 'usuário';

    protected static ?string $pluralModelLabel = 'usuários';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nome')
                ->required()
                ->maxLength(255),
            TextInput::make('email')
                ->label('E-mail')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Select::make('role')
                ->label('Classificação')
                ->options(UserRole::options())
                ->formatStateUsing(fn (UserRole|string|null $state): ?string => $state instanceof UserRole ? $state->value : $state)
                ->default(UserRole::User->value)
                ->required()
                ->native(false),
            Toggle::make('is_active')
                ->label('Conta ativa')
                ->default(true),
            Select::make('programs')
                ->label('Programas liberados')
                ->relationship('programs', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                ->native(false)
                ->helperText('Programas que este usuário poderá ver e importar no painel. '
                    .'Administradores têm acesso a todos, independentemente desta seleção.'),
            TextInput::make('password')
                ->label('Senha')
                ->password()
                ->revealable()
                ->required()
                ->minLength(8)
                ->visibleOn('create'),
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
                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('role')
                    ->label('Classificação')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => $state->isAdmin() ? 'warning' : 'gray'),
                IconColumn::make('is_active')
                    ->label('Ativa')
                    ->boolean(),
                TextColumn::make('status_online')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn (User $record): string => $record->isOnline() ? 'Online' : 'Offline')
                    ->color(fn (string $state): string => $state === 'Online' ? 'success' : 'gray'),
                TextColumn::make('active_time')
                    ->label('Tempo ativo')
                    ->getStateUsing(fn (User $record): string => self::formatDuration($record->totalActiveSeconds())),
                TextColumn::make('last_seen_at')
                    ->label('Visto por último')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Classificação')
                    ->options(UserRole::options()),
                TernaryFilter::make('is_active')
                    ->label('Conta ativa'),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('changePassword')
                    ->label('Alterar senha')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('warning')
                    ->schema([
                        TextInput::make('password')
                            ->label('Nova senha')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8),
                        TextInput::make('password_confirmation')
                            ->label('Confirmar senha')
                            ->password()
                            ->revealable()
                            ->required()
                            ->same('password'),
                    ])
                    ->action(function (User $record, array $data): void {
                        app(ChangeUserPassword::class)($record, $data['password']);

                        Notification::make()
                            ->title('Senha alterada com sucesso.')
                            ->success()
                            ->send();
                    }),
                Action::make('toggleActivation')
                    ->label(fn (User $record): string => $record->is_active ? 'Inativar' : 'Ativar')
                    ->icon(fn (User $record): Heroicon => $record->is_active ? Heroicon::OutlinedLockClosed : Heroicon::OutlinedLockOpen)
                    ->color(fn (User $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->disabled(fn (User $record): bool => $record->getKey() === Auth::id())
                    ->action(function (User $record): void {
                        app(SetUserActivation::class)($record, ! $record->is_active);

                        Notification::make()
                            ->title('Status da conta atualizado.')
                            ->success()
                            ->send();
                    }),
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '—';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}min";
        }

        return "{$minutes}min";
    }
}
