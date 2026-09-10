<?php

namespace App\Filament\Resources\ProLicenses;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\ProLicenses\Pages\ListProLicenses;
use App\Filament\Resources\ProLicenses\Pages\ViewProLicense;
use App\Filament\Resources\ProLicenses\Schemas\ProLicenseInfolist;
use App\Filament\Resources\ProLicenses\Tables\ProLicensesTable;
use App\Models\ProLicense;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProLicenseResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = ProLicense::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $recordTitleAttribute = 'uuid';

    protected static ?string $modelLabel = 'Pro License';

    /** Licenses are never edited by hand — the grant and revoke actions own every transition. */
    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProLicenseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProLicensesTable::configure($table);
    }

    /**
     * A support email carries one identifier; any of them should find the row.
     *
     * @return array<int, string>
     */
    public static function getGloballySearchableAttributes(): array
    {
        return [
            'uuid',
            'stripe_checkout_session_id',
            'stripe_payment_intent_id',
            'stripe_customer_id',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProLicenses::route('/'),
            'view' => ViewProLicense::route('/{record}'),
        ];
    }
}
