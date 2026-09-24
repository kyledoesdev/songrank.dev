<?php

namespace App\Filament\Resources\Playlists;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\Playlists\Pages\ManagePlaylists;
use App\Filament\Resources\Playlists\Pages\ViewPlaylist;
use App\Filament\Resources\Playlists\RelationManagers\RankingsRelationManager;
use App\Filament\Resources\Users\UserResource;
use App\Models\Playlist;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use UnitEnum;

class PlaylistResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = Playlist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::RectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Spotify Entities';

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Playlist Details')
                    ->schema([
                        TextEntry::make('playlist_id')
                            ->label('Playlist ID')
                            ->icon(Heroicon::RectangleGroup)
                            ->url(fn (Playlist $playlist): string => $playlist->spotifyUrl())
                            ->openUrlInNewTab()
                            ->color('primary'),
                        TextEntry::make('name')
                            ->label('Playlist Name')
                            ->icon(Heroicon::Pencil),
                        TextEntry::make('description')
                            ->label('Description')
                            ->icon(Heroicon::DocumentText),
                        TextEntry::make('track_count')
                            ->label('Tracks')
                            ->icon(Heroicon::MusicalNote),
                    ]),

                Section::make('Creator Details')
                    ->schema([
                        TextEntry::make('creator_id')
                            ->label('Spotify Creator ID')
                            ->icon(Heroicon::Identification)
                            ->url(fn (Playlist $playlist) => "https://open.spotify.com/user/{$playlist->creator_id}")
                            ->openUrlInNewTab()
                            ->color('primary'),
                        TextEntry::make('creator_name')
                            ->label('Creator Name')
                            ->url(fn (Playlist $playlist): ?string => $playlist->user
                                ? UserResource::getUrl('view', ['record' => $playlist->user])
                                : null)
                            ->color('primary')
                            ->icon(Heroicon::User),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->modifyQueryUsing(fn (Builder $query) => $query->withCount('rankings'))
            ->columns([
                ImageColumn::make('cover')
                    ->label('Cover')
                    ->circular(),
                TextColumn::make('playlist_id')
                    ->label('Playlist ID')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->formatStateUsing(fn (Playlist $model) => Str::limit($model->description, 45)),
                TextColumn::make('creator_id')
                    ->label('Creator ID')
                    ->searchable(),
                TextColumn::make('creator_name')
                    ->label('Creator Name')
                    ->searchable(),
                TextColumn::make('track_count')
                    ->label('Tracks')
                    ->searchable(),
                TextColumn::make('rankings_count')
                    ->label('Rankings')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [
            RankingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlaylists::route('/'),
            'view' => ViewPlaylist::route('/{record}'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
