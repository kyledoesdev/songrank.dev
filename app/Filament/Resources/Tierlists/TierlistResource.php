<?php

namespace App\Filament\Resources\Tierlists;

use App\Enums\TierlistType;
use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\Tierlists\Filters\CompletedFilter;
use App\Filament\Resources\Tierlists\Pages\EditTierlist;
use App\Filament\Resources\Tierlists\Pages\ListTierlists;
use App\Filament\Resources\Tierlists\Pages\ViewTierlist;
use App\Filament\Resources\Tierlists\RelationManagers\CommentsRelationManager;
use App\Filament\Resources\Tierlists\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Tierlists\Tables\TierlistTable;
use App\Models\Tierlist;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class TierlistResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = Tierlist::class;

    protected static ?string $navigationLabel = 'Tier Lists';

    protected static ?string $modelLabel = 'Tier List';

    protected static ?string $pluralModelLabel = 'Tier Lists';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Squares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Song Rank';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(30),
            DateTimePicker::make('completed_at')
                ->label('Completed At')
                ->afterStateHydrated(function (DateTimePicker $component, ?Tierlist $record): void {
                    $component->state($record?->getAttributes()['completed_at'] ?? null);
                }),
            Toggle::make('is_complete')
                ->label('Is Complete'),
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
        return TierlistTable::configure($table)
            ->filters([
                CompletedFilter::make(),
                SelectFilter::make('type')
                    ->options(collect(TierlistType::cases())->mapWithKeys(fn (TierlistType $type) => [$type->value => $type->label()])),
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
                Section::make('Tier List Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('user.name')
                            ->label('Creator')
                            ->icon(Heroicon::User),
                        TextEntry::make('name')
                            ->url(fn (Tierlist $record) => route('tierlist', ['id' => $record->getKey()]))
                            ->icon(Heroicon::Link),
                        TextEntry::make('type')
                            ->label('Type')
                            ->state(fn (Tierlist $record): string => $record->type->label()),
                        TextEntry::make('completed_at')
                            ->icon(Heroicon::Clock)
                            ->label('Completed At'),
                        TextEntry::make('items_count')
                            ->label('Entries'),
                        IconEntry::make('is_complete')
                            ->label('Is Complete')
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

                Section::make('Source')
                    ->visible(fn (Tierlist $record): bool => $record->source !== null)
                    ->schema([
                        TextEntry::make('source')
                            ->label('Source')
                            ->state(fn (Tierlist $record): ?string => $record->source?->name()),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
            CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTierlists::route('/'),
            'view' => ViewTierlist::route('/{record}'),
            'edit' => EditTierlist::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withCount('items')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    protected static function navigationBadgeCount(): int
    {
        return Tierlist::query()->completed()->count();
    }
}
