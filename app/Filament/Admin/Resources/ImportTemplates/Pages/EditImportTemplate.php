<?php

namespace App\Filament\Admin\Resources\ImportTemplates\Pages;

use App\Filament\Admin\Resources\ImportTemplates\ImportTemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditImportTemplate extends EditRecord
{
    protected static string $resource = ImportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return ImportTemplateResource::normalizeFormData($data);
    }
}
