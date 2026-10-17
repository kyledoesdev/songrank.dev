<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Models\ProLicense;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class ProLicensesRelationManager extends RelationManager
{
    protected static string $relationship = 'proLicenses';

    protected static ?string $title = 'Pro Licenses';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('uuid')
            ->columns([
                TextColumn::make('uuid')
                    ->label('License')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ProLicenseStatus $state): string => $state->label())
                    ->color(fn (ProLicenseStatus $state): string => $state->filamentColor()),
                TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn (ProLicenseSource $state): string => $state->label())
                    ->color(fn (ProLicenseSource $state): string => $state->filamentColor()),
                TextColumn::make('amount_total')
                    ->label('Total')
                    ->state(fn (ProLicense $record): string => $record->formattedTotal())
                    ->alignEnd(),
                TextColumn::make('purchased_at')
                    ->label('Purchased')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                    ->placeholder('Not yet paid')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
