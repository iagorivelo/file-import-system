<?php

namespace App\Filament\Admin\Resources\FileImports\Pages;

use App\Filament\Admin\Resources\FileImports\FileImportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewFileImport extends ViewRecord
{
    protected static string $resource = FileImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
