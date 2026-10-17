<?php

namespace App\Filament\Resources\Tierlists\RelationManagers;

use App\Models\TierlistItem;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Entries';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('tier.name')
                    ->label('Tier')
                    ->badge(),
                TextColumn::make('entryable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state)),
                TextColumn::make('entryable.name')
                    ->label('Name')
                    ->state(fn (TierlistItem $record): ?string => $record->entryable?->name()),
                TextColumn::make('position')
                    ->sortable(),
            ])
            ->defaultSort('position', 'asc');
    }
}
