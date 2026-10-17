<?php

namespace App\Filament\Resources\CrateIcons;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\CrateIcons\Pages\CreateCrateIcon;
use App\Filament\Resources\CrateIcons\Pages\EditCrateIcon;
use App\Filament\Resources\CrateIcons\Pages\ListCrateIcons;
use App\Filament\Resources\CrateIcons\Schemas\CrateIconForm;
use App\Filament\Resources\CrateIcons\Tables\CrateIconTable;
use App\Models\CrateIcon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CrateIconResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = CrateIcon::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Folder;

    protected static ?string $navigationLabel = 'Crate Icons';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CrateIconForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CrateIconTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrateIcons::route('/'),
            'create' => CreateCrateIcon::route('/create'),
            'edit' => EditCrateIcon::route('/{record}/edit'),
        ];
    }
}
