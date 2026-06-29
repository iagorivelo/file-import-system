<?php

namespace App\Filament\Admin\Resources\FileImports\Pages;

use App\Filament\Admin\Resources\FileImports\FileImportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFileImports extends ListRecords
{
    protected static string $resource = FileImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
