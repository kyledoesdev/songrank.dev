<?php

namespace App\Filament\Resources\ProLicenses\Tables;

use App\Actions\Billing\RevokeProLicense;
use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Filament\Resources\Users\UserResource;
use App\Models\ProLicense;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ProLicensesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->icon(Heroicon::User)
                    ->color('primary')
                    ->placeholder('Account deleted')
                    ->url(fn (ProLicense $record): ?string => $record->user
                        ? UserResource::getUrl('view', ['record' => $record->user])
                        : null)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('uuid')
                    ->label('License')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->copyable()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (ProLicenseStatus $state): string => $state->label())
                    ->color(fn (ProLicenseStatus $state): string => $state->filamentColor())
                    ->sortable(),
                TextColumn::make('source')
                    ->badge()
                    ->formatStateUsing(fn (ProLicenseSource $state): string => $state->label())
                    ->color(fn (ProLicenseSource $state): string => $state->filamentColor())
                    ->sortable(),
                TextColumn::make('amount_total')
                    ->label('Total')
                    ->state(fn (ProLicense $record): string => $record->formattedTotal())
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('amount_tax')
                    ->label('Tax')
                    ->state(fn (ProLicense $record): string => $record->formattedTax())
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('billing_country')
                    ->label('Country')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('purchased_at')
                    ->label('Purchased')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                    ->placeholder('Not yet paid')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchable()
            ->filters([
                SelectFilter::make('status')
                    ->options(fn (): array => collect(ProLicenseStatus::cases())
                        ->mapWithKeys(fn (ProLicenseStatus $case): array => [$case->value => $case->label()])
                        ->all()),
                SelectFilter::make('source')
                    ->options(fn (): array => collect(ProLicenseSource::cases())
                        ->mapWithKeys(fn (ProLicenseSource $case): array => [$case->value => $case->label()])
                        ->all()),
                Filter::make('stripe_reference')
                    ->schema([
                        TextInput::make('reference')
                            ->label('Stripe ID')
                            ->placeholder('cs_… / pi_… / in_… / cus_…'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['reference'] ?? null,
                        fn (Builder $query, string $reference) => $query
                            ->where('stripe_checkout_session_id', $reference)
                            ->orWhere('stripe_payment_intent_id', $reference)
                            ->orWhere('stripe_invoice_id', $reference)
                            ->orWhere('stripe_customer_id', $reference),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('revoke')
                    ->icon(Heroicon::NoSymbol)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Revoke this license')
                    ->modalDescription('This removes Pro access immediately. It does not refund anything in Stripe — issue refunds from the Stripe dashboard, and the webhook will record them here.')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Why')
                            ->required()
                            ->rows(3),
                    ])
                    ->visible(fn (ProLicense $record): bool => $record->isActive())
                    ->action(function (ProLicense $record, array $data): void {
                        app(RevokeProLicense::class)->handle(
                            license: $record,
                            status: ProLicenseStatus::REVOKED,
                            attributes: ['notes' => $data['notes']],
                        );
                    })
                    ->successNotificationTitle('License revoked.'),
            ])
            ->toolbarActions([]);
    }
}
