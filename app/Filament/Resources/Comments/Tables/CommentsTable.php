<?php

namespace App\Filament\Resources\Comments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CommentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('commentator.name')
                    ->label('User Name')
                    ->icon(Heroicon::User),
                TextColumn::make('commentable.name')
                    ->label('Commented On')
                    ->placeholder('Reply'),
                TextColumn::make('text')
                    ->html()
                    ->label('Comment'),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
