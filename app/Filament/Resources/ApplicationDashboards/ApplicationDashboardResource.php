<?php

namespace App\Filament\Resources\ApplicationDashboards;

use App\Filament\Concerns\HasCachedNavigationBadge;
use App\Filament\Resources\ApplicationDashboards\Pages\ManageApplicationDashboards;
use App\Models\ApplicationDashboard;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ApplicationDashboardResource extends Resource
{
    use HasCachedNavigationBadge;

    protected static ?string $model = ApplicationDashboard::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    protected static ?string $navigationLabel = 'App Settings';

    protected static string|UnitEnum|null $navigationGroup = 'System';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                RichEditor::make('about_page')->columnSpanFull(),
                RichEditor::make('support_page')->columnSpanFull(),
                TextInput::make('version')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slideshow_speed')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                Textarea::make('seo_terms')
                    ->helperText('Comma-separated SEO keywords rendered in the meta keywords tag.')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('about_page')
                    ->limit(25),
                TextColumn::make('support_page')
                    ->limit(25),
                TextColumn::make('version')
                    ->searchable(),
                TextColumn::make('slideshow_speed')
                    ->searchable(),
                TextColumn::make('seo_terms')
                    ->limit(40),
                TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->dateTime('M j, Y g:i A', Auth::user()->timezone)
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageApplicationDashboards::route('/'),
        ];
    }
}
