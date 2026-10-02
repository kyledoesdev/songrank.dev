<?php

namespace App\Filament\Resources\PlanFeatures\Tables;

use App\Models\PlanFeature;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class PlanFeatureTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->searchable()
                    ->description(fn (PlanFeature $record): ?string => $record->renderedDetail()),
                TextColumn::make('rendered_label')
                    ->label('On the card')
                    ->state(fn (PlanFeature $record): string => $record->renderedLabel())
                    ->color('gray'),
                IconColumn::make('is_included')
                    ->label('Included')
                    ->boolean(),
                IconColumn::make('is_coming_soon')
                    ->label('Soon')
                    ->boolean(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
