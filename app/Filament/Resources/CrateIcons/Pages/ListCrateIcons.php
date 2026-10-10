<?php

namespace App\Filament\Resources\CrateIcons\Pages;

use App\Filament\Resources\CrateIcons\CrateIconResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCrateIcons extends ListRecords
{
    protected static string $resource = CrateIconResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
