<?php

namespace App\Filament\App\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Src\Application\Import\StartImport;
use Src\Application\Import\StartImportData;
use Src\Domain\Import\FileProcessor;
use Src\Domain\Import\FileType;
use Src\Infrastructure\Persistence\Models\FileImport;
use Src\Infrastructure\Persistence\Models\Program;
use Src\Infrastructure\Persistence\Models\User;

class Programs extends Page
{
    protected string $view = 'filament.app.pages.programs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Programas';

    protected static ?string $title = 'Programas';

    protected static ?string $slug = '/';

    /**
     * Programas (boxes) ativos exibidos ao usuário.
     *
     * Administradores enxergam todos os programas; usuários comuns, apenas os
     * que o admin liberou para eles.
     *
     * @return Collection<int, Program>
     */
    public function getPrograms(): Collection
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return new Collection();
        }

        $query = $user->isAdmin()
            ? Program::query()
            : $user->programs();

        return $query
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Importações recentes do usuário autenticado.
     *
     * @return Collection<int, FileImport>
     */
    public function getRecentImports(): Collection
    {
        return FileImport::query()
            ->where('user_id', Auth::id())
            ->with('program')
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * Ação de importação disparada ao clicar em uma box.
     */
    public function importAction(): Action
    {
        return Action::make('import')
            ->label('Importar arquivo')
            ->icon(Heroicon::OutlinedDocumentArrowUp)
            ->modalHeading(fn (array $arguments): string => 'Importar arquivo — '.$this->programName($arguments))
            ->modalSubmitActionLabel('Enviar para processamento')
            ->schema(function (array $arguments): array {
                $types = $this->acceptedTypesFor($arguments);

                $schema = [];

                // O select só aparece quando o programa aceita mais de um tipo.
                if (count($types) > 1) {
                    $options = [];
                    foreach ($types as $type) {
                        $options[$type->value] = $type->label();
                    }

                    $schema[] = Select::make('file_type')
                        ->label('Tipo do arquivo')
                        ->options($options)
                        ->required()
                        ->native(false);
                }

                $mimeTypes = [];
                foreach ($types as $type) {
                    foreach ($type->mimeTypes() as $mime) {
                        $mimeTypes[] = $mime;
                    }
                }

                $extensions = implode(', ', array_map(
                    static fn (FileType $type): string => '.'.$type->value,
                    $types,
                ));

                $schema[] = FileUpload::make('file')
                    ->label('Arquivo')
                    ->required()
                    ->acceptedFileTypes(array_values(array_unique($mimeTypes)))
                    ->disk((string) config('file_import.storage.disk'))
                    ->directory((string) config('file_import.storage.directory'))
                    ->maxSize((int) config('file_import.max_file_size_kb'))
                    ->storeFileNamesIn('original_names')
                    ->helperText("Formatos aceitos: {$extensions}.");

                return $schema;
            })
            ->action(function (array $arguments, array $data): void {
                $program = Program::query()->findOrFail($arguments['program']);

                // Defesa adicional: além das boxes já virem filtradas, garante
                // que o usuário não importe para um programa sem permissão.
                $user = Auth::user();
                if (! $user instanceof User || ! $user->canAccessProgram($program)) {
                    Notification::make()
                        ->title('Você não tem permissão para importar neste programa.')
                        ->danger()
                        ->send();

                    return;
                }

                $types = $this->acceptedTypesFor($arguments);
                $type = count($types) === 1
                    ? $types[0]
                    : FileType::from((string) $data['file_type']);

                $storedPath = (string) $data['file'];
                $originalNames = $data['original_names'] ?? [];
                $originalName = is_array($originalNames)
                    ? ($originalNames[$storedPath] ?? basename($storedPath))
                    : basename($storedPath);

                app(StartImport::class)(new StartImportData(
                    userId: (int) Auth::id(),
                    programId: (int) $program->getKey(),
                    originalFilename: $originalName,
                    storedPath: $storedPath,
                    type: $type,
                ));

                Notification::make()
                    ->title('Importação enviada para processamento.')
                    ->body('Acompanhe o status na lista de importações recentes.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Ação somente-leitura com os detalhes (e erros) de uma importação.
     */
    public function viewImportAction(): Action
    {
        return Action::make('viewImport')
            ->label('Detalhes')
            ->modalHeading('Detalhes da importação')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Fechar')
            ->modalContent(function (array $arguments) {
                $import = FileImport::query()
                    ->with('program')
                    ->whereKey($arguments['import'] ?? null)
                    ->where('user_id', Auth::id())
                    ->first();

                return view('filament.app.modals.import-details', [
                    'import' => $import,
                ]);
            });
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    protected function programName(array $arguments): string
    {
        $id = $arguments['program'] ?? null;

        return $id !== null
            ? (string) Program::query()->whereKey($id)->value('name')
            : '';
    }

    /**
     * Tipos de arquivo aceitos pelo processador do programa selecionado.
     *
     * @param  array<string, mixed>  $arguments
     * @return list<FileType>
     */
    protected function acceptedTypesFor(array $arguments): array
    {
        $program = Program::query()->find($arguments['program'] ?? null);
        $processorClass = $program?->processor_class;

        if (is_string($processorClass) && is_subclass_of($processorClass, FileProcessor::class)) {
            return $processorClass::acceptedFileTypes();
        }

        return FileType::cases();
    }
}
