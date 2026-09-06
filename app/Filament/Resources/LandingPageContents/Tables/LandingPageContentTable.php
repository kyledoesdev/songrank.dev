<?php

namespace App\Filament\Resources\LandingPageContents\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LandingPageContentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('text')
                    ->limit(60)
                    ->label('Content'),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->sortable()
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone),
            ])
            ->defaultSort('name', 'asc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
