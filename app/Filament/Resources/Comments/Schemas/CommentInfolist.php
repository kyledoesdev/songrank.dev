<?php

namespace App\Filament\Resources\Comments\Schemas;

use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\Rankings\RankingResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Comment;
use App\Models\Ranking;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class CommentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Comment Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID')
                            ->badge(),
                        TextEntry::make('parent_id')
                            ->label('Parent Comment')
                            ->placeholder('None (Top Level)')
                            ->icon(fn (?int $state): ?Heroicon => $state ? Heroicon::Link : null)
                            ->url(fn (Comment $record): ?string => $record->parent_id
                                ? CommentResource::getUrl('view', ['record' => $record->parent_id])
                                : null),
                        TextEntry::make('original_text')
                            ->columnSpanFull()
                            ->prose(),
                        TextEntry::make('text')
                            ->columnSpanFull()
                            ->prose(),
                        KeyValueEntry::make('extra')
                            ->columnSpanFull()
                            ->placeholder('No extra data'),
                    ]),

                Section::make('Relationships')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('commentator.name')
                            ->label('User')
                            ->placeholder('Deleted user')
                            ->icon(fn (Comment $record): ?Heroicon => $record->commentator ? Heroicon::Link : null)
                            ->url(fn (Comment $record): ?string => $record->commentator
                                ? UserResource::getUrl('view', ['record' => $record->commentator])
                                : null),
                        TextEntry::make('commentable')
                            ->label('Commented On')
                            ->placeholder('Deleted')
                            ->icon(fn (Comment $record): ?Heroicon => $record->commentable ? Heroicon::Link : null)
                            ->state(fn (Comment $record): ?string => static::commentableLabel($record))
                            ->url(fn (Comment $record): ?string => static::commentableUrl($record)),
                    ]),

                Section::make('Status & Timestamps')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('approved_at')
                            ->label('Approved')
                            ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                            ->placeholder('Not Approved')
                            ->icon(fn (mixed $state): Heroicon => $state ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle)
                            ->iconColor(fn (mixed $state): string => $state ? 'success' : 'danger'),
                        TextEntry::make('created_at')
                            ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                            ->since(),
                        TextEntry::make('updated_at')
                            ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                            ->since(),
                    ]),
            ]);
    }

    /** `commentable` is polymorphic: a comment hangs off a ranking, or off another comment when it is a reply. */
    protected static function commentableLabel(Comment $record): ?string
    {
        return match (true) {
            $record->commentable instanceof Ranking => $record->commentable->name,
            $record->commentable instanceof Comment => "Comment #{$record->commentable->getKey()}",
            default => null,
        };
    }

    protected static function commentableUrl(Comment $record): ?string
    {
        return match (true) {
            $record->commentable instanceof Ranking => RankingResource::getUrl('view', ['record' => $record->commentable]),
            $record->commentable instanceof Comment => CommentResource::getUrl('view', ['record' => $record->commentable]),
            default => null,
        };
    }
}
