<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\CommentsCreatedWidget;
use App\Filament\Widgets\CompletionTimeWidget;
use App\Filament\Widgets\LoginsWidget;
use App\Filament\Widgets\NewUsersWidget;
use App\Filament\Widgets\PaidUsersWidget;
use App\Filament\Widgets\RankingsCreatedWidget;
use App\Filament\Widgets\ReviewsCreatedWidget;
use App\Filament\Widgets\TierlistsCreatedWidget;
use App\Filament\Widgets\UserGeographyWidget;
use App\Filament\Widgets\UserPlatformWidget;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;

class Dashboard extends BaseDashboard
{
    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->section('Pro', Heroicon::Star, [
                PaidUsersWidget::class,
            ]),
            $this->section('Users', Heroicon::Users, [
                LoginsWidget::class,
                NewUsersWidget::class,
                UserGeographyWidget::class,
                UserPlatformWidget::class,
            ], columns: 4),
            $this->section('Content', Heroicon::Squares2x2, [
                RankingsCreatedWidget::class,
                TierlistsCreatedWidget::class,
                ReviewsCreatedWidget::class,
                CommentsCreatedWidget::class,
                CompletionTimeWidget::class,
            ]),
        ]);
    }

    /**
     * @param  list<class-string<Widget>>  $widgets
     */
    private function section(string $heading, Heroicon $icon, array $widgets, int $columns = 2): Section
    {
        return Section::make($heading)
            ->icon($icon)
            ->collapsible()
            ->schema([
                Grid::make($columns)
                    ->schema(fn (): array => $this->getWidgetsSchemaComponents($widgets)),
            ]);
    }
}
