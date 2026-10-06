<?php

namespace App\Filament\Resources\Rankings;

use App\Enums\RankingType;
use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Filters\HideIncompleteFilter;
use App\Filament\Resources\Rankings\Pages\EditRanking;
use App\Filament\Resources\Rankings\Pages\ListRankings;
use App\Filament\Resources\Rankings\Pages\ViewRanking;
use App\Filament\Resources\Rankings\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\Rankings\RelationManagers\SongsRelationManager;
use App\Filament\Resources\Rankings\Tables\RankingTable;
use App\Models\Ranking;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RankingResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = Ranking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ListBullet;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return config('app.name');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            DateTimePicker::make('completed_at')
                ->label('Completed At')
                ->afterStateHydrated(function (DateTimePicker $component, ?Ranking $record): void {
                    $component->state($record?->getAttributes()['completed_at'] ?? null);
                }),
            Toggle::make('is_ranked')
                ->label('Is Ranked'),
            Toggle::make('is_public')
                ->label('Is Public'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return RankingTable::configure($table)
            ->filters([
                HideIncompleteFilter::make(),
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
                Section::make('Ranking Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Creator')
                            ->icon(Heroicon::User),
                        TextEntry::make('name')
                            ->url(fn (Ranking $ranking) => route('ranking', ['id' => $ranking->getKey()]))
                            ->icon(Heroicon::Link),
                        TextEntry::make('type')
                            ->label('Type')
                            ->state(fn (Ranking $ranking): string => $ranking->type->label()),
                        TextEntry::make('completed_at')
                            ->icon(Heroicon::Clock)
                            ->label('Completed At'),
                        TextEntry::make('songs_count')
                            ->label('Tracks Ranked'),
                        IconEntry::make('is_ranked')
                            ->label('Is Ranked')
                            ->boolean(),
                        IconEntry::make('is_public')
                            ->label('Is Public')
                            ->boolean(),
                    ]),

                Section::make('Artist Details')
                    ->visible(fn (Ranking $record): bool => $record->type === RankingType::ARTIST)
                    ->schema([
                        TextEntry::make('source.artist_name')
                            ->label('Artist Name')
                            ->icon(Heroicon::MusicalNote),
                        TextEntry::make('source.artist_id')
                            ->label('Spotify ID'),
                    ]),

                Section::make('Playlist Details')
                    ->visible(fn (Ranking $record): bool => $record->type === RankingType::PLAYLIST)
                    ->schema([
                        TextEntry::make('source.name')
                            ->label('Playlist Name'),
                        TextEntry::make('source.description')
                            ->label('Playlist Description'),
                        TextEntry::make('source.track_count')
                            ->label('Tracks in Playlist'),
                    ]),

                Section::make('Show Details')
                    ->visible(fn (Ranking $record): bool => $record->type === RankingType::SHOW)
                    ->schema([
                        TextEntry::make('source.name')
                            ->label('Show Name'),
                        TextEntry::make('source.publisher')
                            ->label('Publisher'),
                        TextEntry::make('source.description')
                            ->label('Description'),
                        TextEntry::make('source.episode_count')
                            ->label('Total Episodes'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SongsRelationManager::class,
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRankings::route('/'),
            'view' => ViewRanking::route('/{record}'),
            'edit' => EditRanking::route('/{record}/edit'),
        ];
    }

    /** The table counts its own songs; RankingTable is shared with relation managers. */
    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withCount('songs')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function navigationBadgeCount(): int
    {
        return Ranking::query()->completed()->count();
    }
}
