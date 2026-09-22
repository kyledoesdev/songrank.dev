<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Tierlists\Tables\TierlistTable;
use App\Filament\Resources\Tierlists\TierlistResource;
use App\Models\Tierlist;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class TierlistsRelationManager extends RelationManager
{
    protected static string $relationship = 'tierlists';

    protected static ?string $title = 'Tier Lists';

    public function table(Table $table): Table
    {
        return TierlistTable::configure($table)
            ->recordTitleAttribute('name')
            ->recordActions([
                ViewAction::make()
                    ->url(fn (Tierlist $record): string => TierlistResource::getUrl('view', ['record' => $record])),
                DeleteAction::make(),
            ]);
    }
}
