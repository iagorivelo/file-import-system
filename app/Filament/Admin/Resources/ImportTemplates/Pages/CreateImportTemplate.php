<?php

namespace App\Filament\Admin\Resources\ImportTemplates\Pages;

use App\Filament\Admin\Resources\ImportTemplates\ImportTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateImportTemplate extends CreateRecord
{
    protected static string $resource = ImportTemplateResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return ImportTemplateResource::normalizeFormData($data);
    }
}
