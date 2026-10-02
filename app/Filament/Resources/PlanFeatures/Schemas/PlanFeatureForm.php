<?php

namespace App\Filament\Resources\PlanFeatures\Schemas;

use App\Enums\Billing\Plan;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlanFeatureForm
{
    public static function configure(Schema $schema): Schema
    {
        $placeholders = implode(', ', array_keys(Plan::FREE->placeholders()));

        return $schema
            ->components([
                Select::make('plan')
                    ->options(collect(Plan::cases())->mapWithKeys(fn (Plan $plan) => [$plan->value => $plan->label()]))
                    ->required(),
                TextInput::make('label')
                    ->required()
                    ->maxLength(255)
                    ->helperText("Placeholders are filled from the billing config for the chosen plan: {$placeholders}"),
                TextInput::make('detail')
                    ->maxLength(255)
                    ->helperText('A smaller second line under the label. Takes the same placeholders.'),
                Toggle::make('is_included')
                    ->label('Included')
                    ->helperText('Off shows a cross instead of a check.')
                    ->default(true),
                Toggle::make('is_coming_soon')
                    ->label('Coming soon')
                    ->helperText('Tagged "Soon" and listed last on the card.'),
            ]);
    }
}
