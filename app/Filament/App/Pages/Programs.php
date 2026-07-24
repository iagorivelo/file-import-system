<?php

namespace App\Filament\App\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Src\Application\Import\StartImport;
use Src\Application\Import\StartImportData;
use Src\Domain\Import\FileProcessor;
use Src\Domain\Import\FileType;
use Src\Infrastructure\Persistence\Models\Company;
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
     * Programas (boxes) ativos exibidos ao usuário, escopados à empresa (tenant)
     * atual.
     *
     * Administradores enxergam todos os programas da empresa; usuários comuns,
     * apenas os que o admin liberou para eles (também dentro da empresa).
     *
     * @return Collection<int, Program>
     */
    public function getPrograms(): Collection
    {
        $user = Auth::user();
        $companyId = $this->currentCompanyId();

        if (! $user instanceof User || $companyId === null) {
            return new Collection;
        }

        $query = $user->isAdmin()
            ? Program::query()
            : $user->programs();

        return $query
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * Importações recentes do usuário autenticado, dentro da empresa atual.
     *
     * @return Collection<int, FileImport>
     */
    public function getRecentImports(): Collection
    {
        return FileImport::query()
            ->where('user_id', Auth::id())
            ->when($this->currentCompanyId(), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->with('program')
            ->latest()
            ->limit(10)
            ->get();
    }

    /**
     * ID da empresa (tenant) atual do painel, ou null fora de contexto de tenancy.
     */
    protected function currentCompanyId(): ?int
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Company ? (int) $tenant->getKey() : null;
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
                $companyId = $this->currentCompanyId();

                // Defesa adicional: além das boxes já virem filtradas, garante
                // que o usuário só importe para um programa que ele acessa E que
                // pertence à empresa (tenant) atual.
                $user = Auth::user();
                if (! $user instanceof User
                    || ! $user->canAccessProgram($program)
                    || $program->company_id !== $companyId) {
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
                    companyId: $companyId,
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
                    ->when($this->currentCompanyId(), fn ($query, $companyId) => $query->where('company_id', $companyId))
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
            ? (string) $this->scopedPrograms()->whereKey($id)->value('name')
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
        $program = $this->scopedPrograms()->find($arguments['program'] ?? null);
        $processorClass = $program?->processor_class;

        if (is_string($processorClass) && is_subclass_of($processorClass, FileProcessor::class)) {
            return $processorClass::acceptedFileTypes();
        }

        return FileType::cases();
    }

    /**
     * Consulta base de programas restrita à empresa (tenant) atual — evita que
     * argumentos de ação (program id) vazem metadados de outra empresa.
     *
     * @return Builder<Program>
     */
    protected function scopedPrograms(): Builder
    {
        return Program::query()->where('company_id', $this->currentCompanyId());
    }
}
