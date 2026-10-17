<?php

namespace App\Filament\Resources\Albums;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\Albums\Pages\ListAlbums;
use App\Filament\Resources\Albums\Pages\ViewAlbum;
use App\Filament\Resources\Artists\ArtistResource;
use App\Models\Album;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;
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
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AlbumResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = Album::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Spotify Entities';

    protected static ?string $recordTitleAttribute = 'name';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover')
                    ->label('')
                    ->circular(),
                TextColumn::make('album_id')
                    ->label('Album ID')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('artist.artist_name')
                    ->label('Artist')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_tracks')
                    ->label('Tracks')
                    ->sortable(),
                TextColumn::make('release_date')
                    ->label('Released')
                    ->sortable(),
                TextColumn::make('tierlist_items_count')
                    ->label('Tier List Items')
                    ->counts('tierlistItems')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Album Details')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('cover')
                            ->label('Cover')
                            ->circular(),
                        TextEntry::make('name')
                            ->label('Name')
                            ->icon(Heroicon::MusicalNote),
                        TextEntry::make('album_id')
                            ->label('Spotify ID')
                            ->icon(Heroicon::Identification)
                            ->url(fn (Album $album): string => $album->spotifyUrl())
                            ->openUrlInNewTab()
                            ->color('primary'),
                        TextEntry::make('artist.artist_name')
                            ->label('Artist')
                            ->url(fn (Album $album): ?string => $album->artist
                                ? ArtistResource::getUrl('view', ['record' => $album->artist])
                                : null)
                            ->color('primary')
                            ->icon(Heroicon::User),
                        TextEntry::make('total_tracks')
                            ->label('Tracks')
                            ->icon(Heroicon::QueueList),
                        TextEntry::make('release_date')
                            ->label('Release Date')
                            ->icon(Heroicon::Calendar),
                        TextEntry::make('tierlist_items_count')
                            ->label('Tier List Items'),
                    ]),
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withCount('tierlistItems')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAlbums::route('/'),
            'view' => ViewAlbum::route('/{record}'),
        ];
    }
}
