<?php

namespace App\Filament\Resources\ProLicenses\Pages;

use App\Actions\Billing\GrantProLicense;
use App\Enums\Billing\ProLicenseSource;
use App\Filament\Resources\ProLicenses\ProLicenseResource;
use App\Filament\Resources\ProLicenses\Widgets\ProLicenseOverview;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListProLicenses extends ListRecords
{
    protected static string $resource = ProLicenseResource::class;

    /**
     * @return array<int, class-string>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            ProLicenseOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('grant')
                ->label('Grant a license')
                ->icon(Heroicon::Gift)
                ->modalHeading('Grant a license')
                ->modalDescription('Gives the user Pro immediately, with no payment behind it. Use for beta testers, support gestures and gifts.')
                ->schema([
                    Select::make('user_id')
                        ->label('User')
                        ->options(fn (): array => User::query()
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all())
                        ->searchable()
                        ->required(),

                    /* Paid sources are excluded: only Stripe may call a license a purchase. */
                    Select::make('source')
                        ->label('Type')
                        ->options(fn (): array => collect(ProLicenseSource::cases())
                            ->reject(fn (ProLicenseSource $case): bool => $case->isPaid())
                            ->mapWithKeys(fn (ProLicenseSource $case): array => [$case->value => $case->label()])
                            ->all())
                        ->default(ProLicenseSource::COMP->value)
                        ->selectablePlaceholder(false)
                        ->required(),

                    Textarea::make('notes')
                        ->label('Why')
                        ->placeholder('Beta tester for tier lists. / Gift from Jane for the charity stream.')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(GrantProLicense::class)->handle(
                        user: User::findOrFail($data['user_id']),
                        source: ProLicenseSource::from($data['source']),
                        attributes: ['notes' => $data['notes']],
                    );
                })
                ->successNotificationTitle('License granted.'),
        ];
    }
}
