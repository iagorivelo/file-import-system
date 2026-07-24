<?php

namespace App\Filament\Admin\Resources\ImportTemplates\Pages;

use App\Filament\Admin\Resources\ImportTemplates\ImportTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImportTemplates extends ListRecords
{
    protected static string $resource = ImportTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
