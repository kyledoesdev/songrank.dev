<?php

namespace App\Filament\Resources\ProLicenses\Schemas;

use App\Enums\Billing\ProLicenseSource;
use App\Enums\Billing\ProLicenseStatus;
use App\Filament\Resources\Users\UserResource;
use App\Models\ProLicense;
use Filament\Actions\Action;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;

class ProLicenseInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('License')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('uuid')
                        ->label('License ID')
                        ->fontFamily('mono')
                        ->copyable(),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (ProLicenseStatus $state): string => $state->label())
                        ->color(fn (ProLicenseStatus $state): string => $state->filamentColor()),
                    TextEntry::make('source')
                        ->badge()
                        ->formatStateUsing(fn (ProLicenseSource $state): string => $state->label())
                        ->color(fn (ProLicenseSource $state): string => $state->filamentColor()),
                    TextEntry::make('user.name')
                        ->label('User')
                        ->icon(Heroicon::User)
                        ->color('primary')
                        ->placeholder('Account deleted')
                        ->url(fn (ProLicense $record): ?string => static::userUrl($record))
                        ->suffixAction(
                            Action::make('viewUser')
                                ->label('Open user')
                                ->icon(Heroicon::ArrowTopRightOnSquare)
                                ->color('gray')
                                ->url(fn (ProLicense $record): ?string => static::userUrl($record))
                                ->visible(fn (ProLicense $record): bool => filled($record->user)),
                        ),
                    TextEntry::make('user.email')
                        ->label('Email')
                        ->placeholder('—')
                        ->copyable(),
                    TextEntry::make('notes')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ]),

            Section::make('Money')
                ->columns(3)
                ->columnSpanFull()
                ->visible(fn (ProLicense $record): bool => $record->isPaid())
                ->schema([
                    TextEntry::make('amount_subtotal')
                        ->label('Subtotal')
                        ->state(fn (ProLicense $record): string => $record->formattedSubtotal()),
                    TextEntry::make('amount_tax')
                        ->label('Tax')
                        ->state(fn (ProLicense $record): string => $record->formattedTax()),
                    TextEntry::make('amount_total')
                        ->label('Total')
                        ->state(fn (ProLicense $record): string => $record->formattedTotal())
                        ->weight('bold'),
                    TextEntry::make('amount_refunded')
                        ->label('Refunded')
                        ->state(fn (ProLicense $record): string => $record->formattedRefunded()),
                    TextEntry::make('currency')
                        ->label('Currency')
                        ->formatStateUsing(fn (?string $state): string => mb_strtoupper($state ?? '—')),
                    TextEntry::make('billing_country')
                        ->label('Billing Country')
                        ->placeholder('—'),
                ]),

            Section::make('Stripe')
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn (ProLicense $record): bool => $record->isPaid())
                ->schema([
                    TextEntry::make('stripe_checkout_session_id')
                        ->label('Checkout Session')
                        ->fontFamily('mono')
                        ->placeholder('—')
                        ->copyable(),
                    TextEntry::make('stripe_payment_intent_id')
                        ->label('Payment Intent')
                        ->fontFamily('mono')
                        ->placeholder('—')
                        ->copyable()
                        ->url(fn (ProLicense $record): ?string => $record->stripeDashboardUrl())
                        ->openUrlInNewTab(),
                    TextEntry::make('stripe_invoice_id')
                        ->label('Invoice')
                        ->fontFamily('mono')
                        ->placeholder('—')
                        ->copyable(),
                    TextEntry::make('stripe_customer_id')
                        ->label('Customer')
                        ->fontFamily('mono')
                        ->placeholder('—')
                        ->copyable(),
                    TextEntry::make('hosted_invoice_url')
                        ->label('Hosted Invoice')
                        ->placeholder('—')
                        ->url(fn (ProLicense $record): ?string => $record->hosted_invoice_url)
                        ->openUrlInNewTab(),
                    TextEntry::make('invoice_pdf_url')
                        ->label('Invoice PDF')
                        ->placeholder('—')
                        ->url(fn (ProLicense $record): ?string => $record->invoice_pdf_url)
                        ->openUrlInNewTab(),
                ]),

            Section::make('Timeline')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('created_at')
                        ->label('Created At')
                        ->dateTime('M j, Y g:i A', Auth::user()->timezone),
                    TextEntry::make('purchased_at')
                        ->label('Purchased At')
                        ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                        ->placeholder('Not yet paid'),
                    TextEntry::make('refunded_at')
                        ->label('Refunded At')
                        ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                        ->placeholder('—'),
                    TextEntry::make('revoked_at')
                        ->label('Revoked At')
                        ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                        ->placeholder('—'),
                ]),
        ]);
    }

    /**
     * Where the buyer's name points, or null once the account is gone.
     */
    private static function userUrl(ProLicense $record): ?string
    {
        return $record->user
            ? UserResource::getUrl('view', ['record' => $record->user])
            : null;
    }
}
