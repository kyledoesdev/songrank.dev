<?php

namespace App\Filament\Resources\Tierlists\Pages;

use App\Filament\Resources\Tierlists\TierlistResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTierlist extends ViewRecord
{
    protected static string $resource = TierlistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
