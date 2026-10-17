<?php

namespace App\Filament\Resources\PlanFeatures;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\PlanFeatures\Pages\CreatePlanFeature;
use App\Filament\Resources\PlanFeatures\Pages\EditPlanFeature;
use App\Filament\Resources\PlanFeatures\Pages\ListPlanFeatures;
use App\Filament\Resources\PlanFeatures\Schemas\PlanFeatureForm;
use App\Filament\Resources\PlanFeatures\Tables\PlanFeatureTable;
use App\Models\PlanFeature;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PlanFeatureResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = PlanFeature::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CheckBadge;

    protected static ?string $navigationLabel = 'Pricing Features';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    public static function form(Schema $schema): Schema
    {
        return PlanFeatureForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlanFeatureTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlanFeatures::route('/'),
            'create' => CreatePlanFeature::route('/create'),
            'edit' => EditPlanFeature::route('/{record}/edit'),
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
