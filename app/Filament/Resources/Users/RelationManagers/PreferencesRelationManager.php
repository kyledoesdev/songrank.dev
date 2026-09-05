<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class PreferencesRelationManager extends RelationManager
{
    protected static string $relationship = 'preferences';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('recieve_newsletter_emails')
                    ->boolean()
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('recieve_newsletter_emails')
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                ToggleColumn::make('recieve_newsletter_emails'),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
