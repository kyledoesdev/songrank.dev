<?php

namespace App\Filament\Resources\LegalDocuments\Tables;

use App\Enums\LegalDocumentType;
use App\Models\LegalDocument;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LegalDocumentTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID'),
                TextColumn::make('type')
                    ->label('Document')
                    ->badge()
                    ->state(fn (LegalDocument $record): string => $record->type->label())
                    ->color(fn (LegalDocument $record): string => $record->type->filamentColor()),
                TextColumn::make('effective_at')
                    ->label('Effective From')
                    ->sortable()
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->sortable()
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone),
                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->sortable()
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone),
            ])
            ->defaultSort('effective_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->label('Document')
                    ->options(collect(LegalDocumentType::cases())
                        ->mapWithKeys(fn (LegalDocumentType $type) => [$type->value => $type->label()])
                    ),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
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
