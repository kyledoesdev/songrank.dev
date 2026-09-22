<?php

namespace App\Filament\Resources\Shows;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\Shows\Pages\ManageShows;
use App\Filament\Resources\Shows\Pages\ViewShow;
use App\Filament\Resources\Shows\RelationManagers\RankingsRelationManager;
use App\Models\Show;
use BackedEnum;
use Filament\Actions\DeleteAction;
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

class ShowResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = Show::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Microphone;

    protected static string|UnitEnum|null $navigationGroup = 'Spotify Entities';

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Show Details')
                    ->schema([
                        TextEntry::make('show_id')
                            ->label('Show ID')
                            ->icon(Heroicon::Identification)
                            ->url(fn (Show $show): string => $show->spotifyUrl())
                            ->openUrlInNewTab()
                            ->color('primary'),
                        TextEntry::make('name')
                            ->label('Name')
                            ->icon(Heroicon::Pencil),
                        TextEntry::make('description')
                            ->label('Description')
                            ->icon(Heroicon::DocumentText),
                        TextEntry::make('episode_count')
                            ->label('Episodes')
                            ->icon(Heroicon::MusicalNote),
                    ]),

                Section::make('Publisher Details')
                    ->schema([
                        TextEntry::make('publisher')
                            ->label('Publisher')
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
                TextColumn::make('show_id')
                    ->label('Show ID')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(),
                TextColumn::make('publisher')
                    ->label('Publisher')
                    ->searchable(),
                TextColumn::make('description')
                    ->label('Description')
                    ->searchable()
                    ->formatStateUsing(fn (Show $model) => Str::limit($model->description, 45)),
                TextColumn::make('episode_count')
                    ->label('Episodes'),
                TextColumn::make('rankings_count')
                    ->label('Rankings')
                    ->sortable(),
            ])
            ->filters([])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
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
            'index' => ManageShows::route('/'),
            'view' => ViewShow::route('/{record}'),
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
