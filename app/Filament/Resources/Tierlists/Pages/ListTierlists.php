<?php

namespace App\Filament\Resources\Tierlists\Pages;

use App\Filament\Resources\Tierlists\TierlistResource;
use Filament\Resources\Pages\ListRecords;

class ListTierlists extends ListRecords
{
    protected static string $resource = TierlistResource::class;
}
