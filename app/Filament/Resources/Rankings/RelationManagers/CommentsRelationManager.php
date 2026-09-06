<?php

namespace App\Filament\Resources\Rankings\RelationManagers;

use App\Filament\Resources\Comments\CommentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comments';

    protected static ?string $relatedResource = CommentResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('commentator.name')
                    ->label('User'),
                TextColumn::make('text')
                    ->html()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ]);
    }
}
