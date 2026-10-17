<?php

namespace App\Filament\Resources\Comments\Tables;

use App\Models\Comment;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
                    ->icon(Heroicon::User)
                    ->searchable(),
                TextColumn::make('commentable.name')
                    ->label('Commented On')
                    ->placeholder('Reply'),
                TextColumn::make('text')
                    ->html()
                    ->label('Comment')
                    ->searchable(['original_text']),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone),
                TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone),
            ])
            ->filters([
                SelectFilter::make('commentable_type')
                    ->label('Commented On')
                    ->options([
                        Ranking::class => 'Rankings',
                        Tierlist::class => 'Tier lists',
                        Review::class => 'Reviews',
                        Comment::class => 'Replies',
                    ]),
            ])
            ->recordActions([
                Action::make('viewOnSite')
                    ->label('View on site')
                    ->icon(Heroicon::ArrowTopRightOnSquare)
                    ->url(fn (Comment $record): string => $record->commentUrl(), shouldOpenInNewTab: true)
                    ->visible(fn (Comment $record): bool => filled($record->topLevel()->commentable)),
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
