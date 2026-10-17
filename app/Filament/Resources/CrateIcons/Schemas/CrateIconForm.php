<?php

namespace App\Filament\Resources\CrateIcons\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CrateIconForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('icon')
                    ->required()
                    ->maxLength(255)
                    ->unique()
                    ->regex('/^fa-[a-z0-9-]+$/')
                    ->validationMessages(['regex' => 'Use one Font Awesome icon class, such as fa-record-vinyl.'])
                    ->live(debounce: 500)
                    ->prefix(fn (Get $get): HtmlString => new HtmlString('<i class="fa-solid '.e($get('icon')).'"></i>'))
                    ->helperText('A Font Awesome solid icon class, without the fa-solid. The preview shows whether the kit has it.'),
                Toggle::make('is_pro')
                    ->label('Pro only')
                    ->helperText('Shown locked to free accounts. A crate already using it keeps it.'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->helperText('Off hides it from the icon picker. A crate already using it keeps it.')
                    ->default(true),
            ]);
    }
}
