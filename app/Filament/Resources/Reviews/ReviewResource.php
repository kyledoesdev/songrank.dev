<?php

namespace App\Filament\Resources\Reviews;

use App\Enums\ReviewType;
use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Filters\HideIncompleteFilter;
use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Pages\ViewReview;
use App\Filament\Resources\Reviews\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\Reviews\Tables\ReviewTable;
use App\Models\Review;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ReviewResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Star;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('app.name');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(60),
            Toggle::make('is_public')
                ->label('Is Public'),
            Toggle::make('comments_enabled')
                ->label('Comments Enabled'),
            Toggle::make('comments_replies_enabled')
                ->label('Comment Replies Enabled'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return ReviewTable::configure($table)
            ->filters([
                HideIncompleteFilter::make(),
                SelectFilter::make('type')
                    ->options(collect(ReviewType::cases())->mapWithKeys(fn (ReviewType $type) => [$type->value => $type->label()])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Review Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Creator')
                            ->icon(Heroicon::User),
                        TextEntry::make('name')
                            ->url(fn (Review $record) => route('review', ['id' => $record->getKey()]))
                            ->icon(Heroicon::Link),
                        TextEntry::make('type')
                            ->label('Type')
                            ->state(fn (Review $record): string => $record->type->label()),
                        TextEntry::make('subject')
                            ->label('Subject')
                            ->state(fn (Review $record): ?string => $record->subject?->name())
                            ->url(fn (Review $record): ?string => $record->subject?->spotifyUrl())
                            ->openUrlInNewTab()
                            ->icon(Heroicon::Link),
                        TextEntry::make('stars')
                            ->label('Score')
                            ->state(fn (Review $record): ?string => $record->hasScore() ? "{$record->score()}/10" : null)
                            ->placeholder('Not scored'),
                        TextEntry::make('published_at')
                            ->icon(Heroicon::Clock)
                            ->label('Published At'),
                        TextEntry::make('comments_count')
                            ->label('Comments'),
                        IconEntry::make('is_published')
                            ->label('Is Published')
                            ->boolean(),
                        IconEntry::make('is_public')
                            ->label('Is Public')
                            ->boolean(),
                        IconEntry::make('comments_enabled')
                            ->label('Comments Enabled')
                            ->boolean(),
                        IconEntry::make('comments_replies_enabled')
                            ->label('Comment Replies')
                            ->boolean(),
                    ]),

                Section::make('Body')
                    ->schema([
                        TextEntry::make('body')
                            ->hiddenLabel()
                            ->html()
                            ->prose(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'view' => ViewReview::route('/{record}'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withCount('comments')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function navigationBadgeCount(): int
    {
        return Review::query()->published()->count();
    }
}
