<?php

namespace App\Filament\Resources\CrateIcons\Tables;

use App\Models\CrateIcon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class CrateIconTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('icon')
                    ->label('Preview')
                    ->formatStateUsing(fn (string $state): HtmlString => new HtmlString('<i class="fa-solid '.e($state).' fa-lg"></i>')),
                TextColumn::make('name')
                    ->searchable(['name', 'icon'])
                    ->description(fn (CrateIcon $record): string => $record->icon),
                TextColumn::make('crates_count')
                    ->label('Crates')
                    ->counts('crates'),
                IconColumn::make('is_pro')
                    ->label('Pro')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('position')
            ->reorderable('position')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
