<?php

namespace App\Filament\Resources\CrateIcons\Pages;

use App\Filament\Resources\CrateIcons\CrateIconResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCrateIcon extends EditRecord
{
    protected static string $resource = CrateIconResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
