<?php

namespace App\Filament\Resources\Artists;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\Artists\Pages\ListArtists;
use App\Filament\Resources\Artists\Pages\ViewArtist;
use App\Filament\Resources\Artists\RelationManagers\RankingsRelationManager;
use App\Models\Artist;
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
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ArtistResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = Artist::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMusicalNote;

    protected static string|UnitEnum|null $navigationGroup = 'Song Rank';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('artist_img')
                    ->label('')
                    ->circular(),
                TextColumn::make('artist_name')
                    ->label('Artist Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rankings_count')
                    ->label('Rankings')
                    ->counts('rankings')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                    ->sortable(),
                TextColumn::make('updated_at')
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
                Section::make('Artist Details')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('artist_img')
                            ->label('Image')
                            ->circular(),
                        TextEntry::make('artist_name')
                            ->label('Name')
                            ->icon(Heroicon::MusicalNote),
                        TextEntry::make('artist_id')
                            ->label('Spotify ID')
                            ->icon(Heroicon::Identification)
                            ->url(fn (Artist $artist): string => $artist->spotifyUrl())
                            ->openUrlInNewTab()
                            ->color('primary'),
                        TextEntry::make('rankings_count')
                            ->label('Artist Rankings'),
                    ]),
            ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()->withCount('rankings');
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
            'index' => ListArtists::route('/'),
            'view' => ViewArtist::route('/{record}'),
        ];
    }
}
